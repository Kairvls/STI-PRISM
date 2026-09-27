<?php

namespace App\Support;

use App\Models\PropertyAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PropertyAssignments
{
    public const RETURN_CONDITIONS = ['Good', 'Damaged'];

    public const OFFICE_ROOM_TYPES = ['Office', 'Faculty Room', 'Library', 'School Clinic'];

    public const PEOPLE_FILTERS = [
        'all' => 'Everyone',
        'holding' => 'Holding property',
        'none' => 'No property',
        'inactive' => 'Inactive',
    ];

    public static function tableReady(): bool
    {
        return Schema::hasTable('property_assignments_table')
            && Schema::hasTable('custodians_table')
            && Schema::hasColumn('property_assignments_table', 'assignment_custodian_id');
    }

    /**
     * @return array{active: int, custodians: int, offices: int, unassigned: int, people: int}
     */
    public static function stats(): array
    {
        if (! self::tableReady()) {
            return ['active' => 0, 'custodians' => 0, 'offices' => 0, 'unassigned' => 0, 'people' => 0];
        }

        $active = DB::table('property_assignments_table as pa')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'pa.assignment_equipment_id')
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE);

        $unassigned = DB::table('equipment_table as e')
            ->join('rooms_table as room', 'room.room_id', '=', 'e.equipment_room_id')
            ->whereIn('room.room_type', self::OFFICE_ROOM_TYPES);
        self::scopeUnassigned($unassigned);

        return [
            'active' => (clone $active)->count(),
            'custodians' => (clone $active)->distinct()->count('pa.assignment_custodian_id'),
            'offices' => (clone $active)->distinct()->count('e.equipment_room_id'),
            'unassigned' => $unassigned->count(),
            'people' => DB::table('custodians_table')->where('custodian_status', '!=', 'Inactive')->count(),
        ];
    }

    /**
     * The people directory with each person's active item count.
     */
    public static function peopleSummary(string $search = '', string $filter = 'all', ?int $departmentId = null, int $perPage = 20)
    {
        $departmentsReady = Departments::tableReady();

        $active = DB::table('property_assignments_table as pa')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'pa.assignment_equipment_id')
            ->leftJoin('rooms_table as room', 'room.room_id', '=', 'e.equipment_room_id')
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->groupBy('pa.assignment_custodian_id')
            ->select(
                'pa.assignment_custodian_id',
                DB::raw('COUNT(*) as active_count'),
                DB::raw('MAX(pa.assignment_issued_at) as last_issued_at'),
                DB::raw("GROUP_CONCAT(DISTINCT room.room_name ORDER BY room.room_name SEPARATOR ', ') as room_names")
            );

        $query = Custodians::withDepartment(DB::table('custodians_table as person'))
            ->leftJoinSub($active, 'a', 'a.assignment_custodian_id', '=', 'person.custodian_id')
            ->leftJoin('rooms_table as home', 'home.room_id', '=', 'person.custodian_room_id')
            ->when($search !== '', function ($query) use ($search, $departmentsReady) {
                $query->where(function ($q) use ($search, $departmentsReady) {
                    $q->where('person.custodian_full_name', 'like', "%{$search}%")
                        ->orWhere('person.custodian_employee_id', 'like', "%{$search}%")
                        ->orWhere('person.custodian_position', 'like', "%{$search}%");
                    if ($departmentsReady) {
                        $q->orWhere('dept.department_name', 'like', "%{$search}%");
                    }
                });
            })
            ->when($departmentId && $departmentsReady, fn ($query) => $query->where('person.custodian_department_id', $departmentId));

        match ($filter) {
            'holding' => $query->whereNotNull('a.active_count'),
            'none' => $query->whereNull('a.active_count')->where('person.custodian_status', '!=', 'Inactive'),
            'inactive' => $query->where('person.custodian_status', 'Inactive'),
            default => $query->where(function ($q) {
                $q->where('person.custodian_status', '!=', 'Inactive')->orWhereNotNull('a.active_count');
            }),
        };

        return $query
            ->orderByDesc('active_count')
            ->orderBy('person.custodian_full_name')
            ->select(
                'person.*',
                Custodians::departmentNameColumn(),
                'home.room_name as home_room_name',
                DB::raw('COALESCE(a.active_count, 0) as active_count'),
                'a.last_issued_at',
                'a.room_names'
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Office-type rooms plus any room that holds assigned property.
     */
    public static function roomsSummary(string $search = '', int $perPage = 20)
    {
        $assigned = DB::table('property_assignments_table as pa')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'pa.assignment_equipment_id')
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->groupBy('e.equipment_room_id')
            ->select(
                'e.equipment_room_id as room_id',
                DB::raw('COUNT(*) as assigned_count'),
                DB::raw('COUNT(DISTINCT pa.assignment_custodian_id) as custodian_count')
            );

        $unassigned = DB::table('equipment_table as e')
            ->groupBy('e.equipment_room_id')
            ->select('e.equipment_room_id as room_id', DB::raw('COUNT(*) as unassigned_count'));
        self::scopeUnassigned($unassigned);

        return DB::table('rooms_table as room')
            ->leftJoin('floors_table as f', 'f.floor_id', '=', 'room.room_floor_id')
            ->leftJoin('buildings_table as b', 'b.building_id', '=', 'f.floor_building_id')
            ->leftJoinSub($assigned, 'a', 'a.room_id', '=', 'room.room_id')
            ->leftJoinSub($unassigned, 'u', 'u.room_id', '=', 'room.room_id')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('room.room_is_archived', false)
            )
            ->where(function ($query) {
                $query->whereNotNull('a.assigned_count')
                    ->orWhereIn('room.room_type', self::OFFICE_ROOM_TYPES);
            })
            ->when($search !== '', fn ($query) => $query->where('room.room_name', 'like', "%{$search}%"))
            ->orderByDesc(DB::raw('COALESCE(a.assigned_count, 0)'))
            ->orderBy('room.room_name')
            ->select(
                'room.room_id',
                'room.room_name',
                'room.room_type',
                'f.floor_level',
                'b.building_name',
                DB::raw('COALESCE(a.assigned_count, 0) as assigned_count'),
                DB::raw('COALESCE(a.custodian_count, 0) as custodian_count'),
                DB::raw('COALESCE(u.unassigned_count, 0) as unassigned_count')
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    public static function activeForCustodian(int $custodianId): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return self::itemQuery()
            ->where('pa.assignment_custodian_id', $custodianId)
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->orderBy('current_room.room_name')
            ->orderBy('e.equipment_name')
            ->get();
    }

    public static function pastForCustodian(int $custodianId, int $limit = 50): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return self::itemQuery()
            ->where('pa.assignment_custodian_id', $custodianId)
            ->where('pa.assignment_status', '!=', PropertyAssignment::STATUS_ACTIVE)
            ->orderByDesc('pa.assignment_returned_at')
            ->limit($limit)
            ->get();
    }

    public static function activeForRoom(int $roomId): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return self::itemQuery()
            ->where('e.equipment_room_id', $roomId)
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->orderBy('person.custodian_full_name')
            ->orderBy('e.equipment_name')
            ->get();
    }

    public static function unassignedForRoom(int $roomId): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $query = DB::table('equipment_table as e')
            ->leftJoin('equipment_categories_table as c', 'c.equipment_category_id', '=', 'e.equipment_category_id')
            ->where('e.equipment_room_id', $roomId)
            ->orderBy('e.equipment_name')
            ->select(
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_serial_number',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_condition_status',
                'c.equipment_category_name'
            );
        self::scopeUnassigned($query);

        return $query->get();
    }

    /**
     * Individually tracked, in-service items with no active custodian.
     */
    private static function scopeUnassigned($query): void
    {
        $query->where(function ($q) {
            $q->where('e.equipment_tracking_mode', 'Individual')
                ->orWhere('e.equipment_quantity', 1);
        })
            ->where(function ($q) {
                $q->whereNull('e.equipment_inventory_status')
                    ->orWhereNotIn('e.equipment_inventory_status', ['Disposed', 'Borrowed']);
            })
            ->where(function ($q) {
                $q->whereNull('e.equipment_condition_status')
                    ->orWhere('e.equipment_condition_status', '!=', 'Disposed');
            })
            ->whereNotExists(function ($sub) {
                $sub->from('property_assignments_table as open_pa')
                    ->whereColumn('open_pa.assignment_equipment_id', 'e.equipment_id')
                    ->where('open_pa.assignment_status', PropertyAssignment::STATUS_ACTIVE);
            });
    }

    private static function itemQuery()
    {
        return DB::table('property_assignments_table as pa')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'pa.assignment_equipment_id')
            ->leftJoin('equipment_categories_table as c', 'c.equipment_category_id', '=', 'e.equipment_category_id')
            ->leftJoin('rooms_table as current_room', 'current_room.room_id', '=', 'e.equipment_room_id')
            ->leftJoin('workstation_slots_table as slot', 'slot.workstation_slot_id', '=', 'e.workstation_slot_id')
            ->leftJoin('custodians_table as person', 'person.custodian_id', '=', 'pa.assignment_custodian_id')
            ->leftJoin('users_table as issuer', 'issuer.user_id', '=', 'pa.assignment_issued_by')
            ->select(
                'pa.*',
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_serial_number',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_condition_status',
                'e.equipment_inventory_status',
                'e.equipment_tracking_mode',
                'c.equipment_category_name',
                'current_room.room_id as current_room_id',
                'current_room.room_name as current_room_name',
                'slot.workstation_slot_label',
                'person.custodian_full_name',
                'person.custodian_employee_id',
                'person.custodian_position',
                'issuer.user_full_name as issued_by_name'
            );
    }

    public static function current(int $equipmentId): ?object
    {
        if (! self::tableReady()) {
            return null;
        }

        return self::baseQuery()
            ->where('pa.assignment_equipment_id', $equipmentId)
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->orderByDesc('pa.assignment_issued_at')
            ->first();
    }

    /**
     * Current custodian per equipment id, shaped for the room layout.
     *
     * @param  array<int>  $equipmentIds
     * @return array<int, array{assignment_id: int, custodian_id: int, name: ?string, employee_id: ?string, position: ?string, document_no: ?string, since: ?string, person_url: string}>
     */
    public static function activeByEquipment(array $equipmentIds): array
    {
        $equipmentIds = array_values(array_unique(array_filter(array_map('intval', $equipmentIds))));
        if ($equipmentIds === [] || ! self::tableReady()) {
            return [];
        }

        $custodians = [];
        foreach (array_chunk($equipmentIds, 500) as $chunk) {
            DB::table('property_assignments_table as pa')
                ->leftJoin('custodians_table as person', 'person.custodian_id', '=', 'pa.assignment_custodian_id')
                ->whereIn('pa.assignment_equipment_id', $chunk)
                ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
                ->get([
                    'pa.assignment_id',
                    'pa.assignment_equipment_id',
                    'pa.assignment_custodian_id',
                    'pa.assignment_document_no',
                    'pa.assignment_issued_at',
                    'person.custodian_full_name',
                    'person.custodian_employee_id',
                    'person.custodian_position',
                ])
                ->each(function ($row) use (&$custodians) {
                    $custodians[(int) $row->assignment_equipment_id] = [
                        'assignment_id' => (int) $row->assignment_id,
                        'custodian_id' => (int) $row->assignment_custodian_id,
                        'name' => $row->custodian_full_name,
                        'employee_id' => $row->custodian_employee_id,
                        'position' => $row->custodian_position,
                        'document_no' => $row->assignment_document_no,
                        'since' => $row->assignment_issued_at,
                        'person_url' => url('/maintenance/property-assignments/people/'.$row->assignment_custodian_id),
                    ];
                });
        }

        return $custodians;
    }

    /**
     * Per-room counts for the 3D building view. Unassigned items only count in
     * office-type rooms or rooms that already have custodians, so lecture rooms
     * full of shared equipment stay quiet.
     *
     * @param  array<int>  $roomIds
     * @return array<int, array{assigned: int, custodians: int, unassigned: int}>
     */
    public static function roomSummaries(array $roomIds): array
    {
        $roomIds = array_values(array_unique(array_filter(array_map('intval', $roomIds))));
        if ($roomIds === [] || ! self::tableReady()) {
            return [];
        }

        $assigned = DB::table('property_assignments_table as pa')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'pa.assignment_equipment_id')
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->whereIn('e.equipment_room_id', $roomIds)
            ->groupBy('e.equipment_room_id')
            ->get([
                'e.equipment_room_id as room_id',
                DB::raw('COUNT(*) as assigned_count'),
                DB::raw('COUNT(DISTINCT pa.assignment_custodian_id) as custodian_count'),
            ])
            ->keyBy('room_id');

        $unassignedQuery = DB::table('equipment_table as e')
            ->whereIn('e.equipment_room_id', $roomIds)
            ->groupBy('e.equipment_room_id')
            ->select('e.equipment_room_id as room_id', DB::raw('COUNT(*) as unassigned_count'));
        self::scopeUnassigned($unassignedQuery);
        $unassigned = $unassignedQuery->get()->keyBy('room_id');

        $officeRoomIds = DB::table('rooms_table')
            ->whereIn('room_id', $roomIds)
            ->whereIn('room_type', self::OFFICE_ROOM_TYPES)
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $summaries = [];
        foreach ($roomIds as $roomId) {
            $assignedCount = (int) ($assigned[$roomId]->assigned_count ?? 0);
            $tracksPeople = $assignedCount > 0 || in_array($roomId, $officeRoomIds, true);
            if (! $tracksPeople) {
                continue;
            }

            $summaries[$roomId] = [
                'assigned' => $assignedCount,
                'custodians' => (int) ($assigned[$roomId]->custodian_count ?? 0),
                'unassigned' => (int) ($unassigned[$roomId]->unassigned_count ?? 0),
            ];
        }

        return $summaries;
    }

    /**
     * People holding property in the given rooms, for "find a person" in the 3D view.
     *
     * @param  array<int>  $roomIds
     * @return array<int, array{id: int, name: string, employeeId: ?string, itemCount: int, rooms: array<int, array{roomId: int, count: int}>}>
     */
    public static function custodianDirectory(array $roomIds): array
    {
        $roomIds = array_values(array_unique(array_filter(array_map('intval', $roomIds))));
        if ($roomIds === [] || ! self::tableReady()) {
            return [];
        }

        $rows = DB::table('property_assignments_table as pa')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'pa.assignment_equipment_id')
            ->join('custodians_table as person', 'person.custodian_id', '=', 'pa.assignment_custodian_id')
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->whereIn('e.equipment_room_id', $roomIds)
            ->groupBy('person.custodian_id', 'person.custodian_full_name', 'person.custodian_employee_id', 'e.equipment_room_id')
            ->orderBy('person.custodian_full_name')
            ->get([
                'person.custodian_id',
                'person.custodian_full_name',
                'person.custodian_employee_id',
                'e.equipment_room_id as room_id',
                DB::raw('COUNT(*) as item_count'),
            ]);

        $people = [];
        foreach ($rows as $row) {
            $id = (int) $row->custodian_id;
            $people[$id] ??= [
                'id' => $id,
                'name' => (string) $row->custodian_full_name,
                'employeeId' => $row->custodian_employee_id,
                'itemCount' => 0,
                'rooms' => [],
            ];
            $people[$id]['itemCount'] += (int) $row->item_count;
            $people[$id]['rooms'][] = ['roomId' => (int) $row->room_id, 'count' => (int) $row->item_count];
        }

        return array_values($people);
    }

    public static function inspectionColumnsReady(): bool
    {
        return self::tableReady()
            && Schema::hasColumn('semester_inspection_items_table', 'item_custodian_id')
            && Schema::hasColumn('semester_inspection_items_table', 'item_custodian_verified');
    }

    /**
     * SQL for the custodian of an inspection item: the live holder while the
     * item is pending, and the person recorded at inspection time afterwards.
     */
    public static function inspectionCustodianExpression(): string
    {
        return "CASE WHEN semester_inspection_items_table.item_status = 'Pending'"
            .' THEN live_pa.assignment_custodian_id'
            .' ELSE semester_inspection_items_table.item_custodian_id END';
    }

    /**
     * Adds custodian columns to a query over semester_inspection_items_table
     * (already joined to equipment_table).
     */
    public static function joinInspectionCustodian($query)
    {
        if (! self::inspectionColumnsReady()) {
            return $query->addSelect(
                DB::raw('NULL as custodian_id'),
                DB::raw('NULL as custodian_name'),
                DB::raw('NULL as custodian_employee_id'),
                DB::raw('NULL as custodian_verified_at')
            );
        }

        return self::joinInspectionCustodianTables($query)
            ->addSelect(
                'custodian.custodian_id as custodian_id',
                'custodian.custodian_full_name as custodian_name',
                'custodian.custodian_employee_id as custodian_employee_id',
                'live_pa.assignment_verified_at as custodian_verified_at'
            );
    }

    private static function joinInspectionCustodianTables($query)
    {
        return $query
            ->leftJoin('property_assignments_table as live_pa', function ($join) {
                $join->on('live_pa.assignment_equipment_id', '=', 'semester_inspection_items_table.item_equipment_id')
                    ->where('live_pa.assignment_status', PropertyAssignment::STATUS_ACTIVE);
            })
            ->leftJoin('custodians_table as custodian', function ($join) {
                $join->on(DB::raw(self::inspectionCustodianExpression()), '=', 'custodian.custodian_id');
            });
    }

    /**
     * Filter values: '' (everyone), 'assigned', 'unassigned', 'unconfirmed', or a custodian id.
     */
    public static function applyInspectionCustodianFilter($query, string $filter): void
    {
        if ($filter === '' || ! self::inspectionColumnsReady()) {
            return;
        }

        $expression = self::inspectionCustodianExpression();

        if ($filter === 'assigned') {
            $query->whereRaw("({$expression}) IS NOT NULL");
        } elseif ($filter === 'unassigned') {
            $query->whereRaw("({$expression}) IS NULL");
        } elseif ($filter === 'unconfirmed') {
            $query->where('semester_inspection_items_table.item_status', 'Inspected')
                ->where('semester_inspection_items_table.item_custodian_verified', false);
        } elseif (ctype_digit($filter)) {
            $query->whereRaw("({$expression}) = ?", [(int) $filter]);
        }
    }

    /**
     * People holding items in a campaign, for the custodian filter.
     */
    public static function inspectionCustodianOptions(int $campaignId): Collection
    {
        if (! self::inspectionColumnsReady()) {
            return collect();
        }

        return self::joinInspectionCustodianTables(DB::table('semester_inspection_items_table'))
            ->where('semester_inspection_items_table.item_campaign_id', $campaignId)
            ->whereNotNull('custodian.custodian_id')
            ->groupBy('custodian.custodian_id', 'custodian.custodian_full_name')
            ->orderBy('custodian.custodian_full_name')
            ->get([
                'custodian.custodian_id',
                'custodian.custodian_full_name',
                DB::raw('COUNT(*) as item_count'),
            ]);
    }

    /**
     * Stamps the custodian check made during a semester inspection.
     *
     * @param  bool|null  $verified  true = seen with the custodian, false = could not confirm, null = not checked
     * @return array{item_custodian_id: ?int, item_custodian_verified: ?bool}
     */
    public static function recordInspectionCheck(int $equipmentId, ?bool $verified): array
    {
        if (! self::inspectionColumnsReady()) {
            return [];
        }

        $assignment = DB::table('property_assignments_table')
            ->where('assignment_equipment_id', $equipmentId)
            ->where('assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->orderByDesc('assignment_issued_at')
            ->first(['assignment_id', 'assignment_custodian_id']);

        if (! $assignment) {
            return ['item_custodian_id' => null, 'item_custodian_verified' => null];
        }

        if ($verified === true) {
            DB::table('property_assignments_table')
                ->where('assignment_id', $assignment->assignment_id)
                ->update([
                    'assignment_verified_at' => now(),
                    'assignment_verified_by' => Auth::id(),
                    'assignment_updated_at' => now(),
                ]);
        }

        return [
            'item_custodian_id' => (int) $assignment->assignment_custodian_id,
            'item_custodian_verified' => $verified,
        ];
    }

    /**
     * Audit log suffix for a custodian check, e.g. " (custodian Juan Dela Cruz: confirmed)".
     */
    public static function inspectionCheckNote(array $check): string
    {
        $custodianId = $check['item_custodian_id'] ?? null;
        if (! $custodianId) {
            return '';
        }

        $name = DB::table('custodians_table')->where('custodian_id', $custodianId)->value('custodian_full_name') ?: 'custodian';
        $result = match ($check['item_custodian_verified'] ?? null) {
            true => 'confirmed',
            false => 'could not confirm',
            default => 'not checked',
        };

        return " (custodian {$name}: {$result})";
    }

    /**
     * Read-only list for the public report form: item names and rooms only.
     */
    public static function reportableForEmployee(string $employeeId): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $custodianId = Custodians::idForEmployee($employeeId);
        if (! $custodianId) {
            return collect();
        }

        $query = DB::table('equipment_table')
            ->join('property_assignments_table as pa', 'pa.assignment_equipment_id', '=', 'equipment_table.equipment_id')
            ->join('rooms_table as room', 'room.room_id', '=', 'equipment_table.equipment_room_id')
            ->where('pa.assignment_custodian_id', $custodianId)
            ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE);

        return ReportGrouping::applyReporterEquipmentFilters($query)
            ->orderBy('room.room_name')
            ->orderBy('equipment_table.equipment_name')
            ->limit(50)
            ->get([
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'room.room_id',
                'room.room_name',
            ]);
    }

    public static function history(int $equipmentId, int $limit = 20): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return self::baseQuery()
            ->where('pa.assignment_equipment_id', $equipmentId)
            ->orderByDesc('pa.assignment_issued_at')
            ->orderByDesc('pa.assignment_id')
            ->limit($limit)
            ->get();
    }

    /**
     * Reason the equipment cannot be issued to a person, or null when it can.
     */
    public static function blocker(object $equipment): ?string
    {
        if (! self::tableReady()) {
            return 'Property assignments are not set up yet. Run the database migrations first.';
        }

        $isIndividual = ($equipment->equipment_tracking_mode ?? null) === 'Individual'
            || (int) ($equipment->equipment_quantity ?? 0) === 1;
        if (! $isIndividual) {
            return 'Only individually tracked items (quantity of 1) can be assigned to a person. Bulk stock stays assigned to the room.';
        }

        if (($equipment->equipment_inventory_status ?? null) === 'Disposed'
            || ($equipment->equipment_condition_status ?? null) === 'Disposed') {
            return 'Disposed equipment cannot be assigned.';
        }

        if (($equipment->equipment_inventory_status ?? null) === 'Borrowed') {
            return 'This item is currently borrowed. Return it from borrowing before assigning it.';
        }

        if (empty($equipment->equipment_room_id)) {
            return 'Deploy this item to a room before assigning it to a person.';
        }

        $roomType = DB::table('rooms_table')
            ->where('room_id', $equipment->equipment_room_id)
            ->value('room_type');
        if (RoomCategories::isStorageType($roomType)) {
            return 'This item is still in storage. Deploy it to the person\'s room before assigning it.';
        }

        return null;
    }

    /**
     * Issue the equipment to a person. If someone already holds it, their
     * assignment is closed as Transferred in the same transaction.
     */
    public static function issue(int $equipmentId, int $custodianId, array $data = []): PropertyAssignment
    {
        return DB::transaction(function () use ($equipmentId, $custodianId, $data) {
            $equipment = DB::table('equipment_table')
                ->where('equipment_id', $equipmentId)
                ->lockForUpdate()
                ->first();

            if (! $equipment) {
                throw new RuntimeException('Equipment not found.');
            }

            if ($reason = self::blocker($equipment)) {
                throw new RuntimeException($reason);
            }

            $custodianActive = DB::table('custodians_table')
                ->where('custodian_id', $custodianId)
                ->where('custodian_status', Custodians::STATUS_ACTIVE)
                ->exists();
            if (! $custodianActive) {
                throw new RuntimeException('Choose an active person from the directory.');
            }

            $existing = PropertyAssignment::query()
                ->where('assignment_equipment_id', $equipmentId)
                ->active()
                ->lockForUpdate()
                ->get();

            if ($existing->contains('assignment_custodian_id', $custodianId)) {
                throw new RuntimeException('This item is already assigned to that person.');
            }

            $userId = Auth::id();
            $now = now();

            foreach ($existing as $previous) {
                $previous->update([
                    'assignment_status' => PropertyAssignment::STATUS_TRANSFERRED,
                    'assignment_returned_at' => $now,
                    'assignment_returned_by' => $userId,
                ]);
            }

            $assignment = PropertyAssignment::create([
                'assignment_equipment_id' => $equipmentId,
                'assignment_custodian_id' => $custodianId,
                'assignment_room_id' => $equipment->equipment_room_id,
                'assignment_slot_id' => $equipment->workstation_slot_id ?? null,
                'assignment_status' => PropertyAssignment::STATUS_ACTIVE,
                'assignment_document_no' => self::cleanText($data['document_no'] ?? null),
                'assignment_notes' => self::cleanText($data['notes'] ?? null),
                'assignment_issued_by' => $userId,
                'assignment_issued_at' => $now,
            ]);

            if ($assignment->assignment_document_no === null) {
                $assignment->update([
                    'assignment_document_no' => sprintf('PA-%s-%05d', $now->format('Y'), $assignment->assignment_id),
                ]);
            }

            return $assignment;
        });
    }

    /**
     * Issue several items to one person under a single document number.
     * All-or-nothing: if any item is blocked, nothing is issued.
     *
     * @param  array<int>  $equipmentIds
     * @return Collection<int, PropertyAssignment>
     */
    public static function issueMany(array $equipmentIds, int $custodianId, array $data = []): Collection
    {
        $equipmentIds = array_values(array_unique(array_filter(array_map('intval', $equipmentIds))));
        if ($equipmentIds === []) {
            throw new RuntimeException('Select at least one item to assign.');
        }

        return DB::transaction(function () use ($equipmentIds, $custodianId, $data) {
            $issued = collect();

            foreach ($equipmentIds as $equipmentId) {
                try {
                    $assignment = self::issue($equipmentId, $custodianId, $data);
                } catch (RuntimeException $e) {
                    $name = DB::table('equipment_table')->where('equipment_id', $equipmentId)->value('equipment_name')
                        ?: 'Item #'.$equipmentId;

                    throw new RuntimeException($name.': '.$e->getMessage());
                }

                if (blank($data['document_no'] ?? null)) {
                    $data['document_no'] = $assignment->assignment_document_no;
                }

                $issued->push($assignment);
            }

            return $issued;
        });
    }

    /**
     * Unassigned, in-service items a person could be given. Without a search or
     * room filter this shows office-type rooms, the person's home room, and
     * rooms the person already holds items in.
     */
    public static function assignableItems(int $custodianId, string $search = '', ?int $roomId = null, int $limit = 60): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $query = self::assignableItemsBase()
            ->leftJoin('equipment_categories_table as c', 'c.equipment_category_id', '=', 'e.equipment_category_id')
            ->orderBy('room.room_name')
            ->orderBy('e.equipment_name')
            ->limit($limit)
            ->select(
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_serial_number',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_condition_status',
                'c.equipment_category_name',
                'room.room_id',
                'room.room_name',
                'room.room_type'
            );

        if ($roomId) {
            $query->where('room.room_id', $roomId);
        } elseif ($search === '') {
            $roomIds = DB::table('property_assignments_table as pa')
                ->join('equipment_table as held', 'held.equipment_id', '=', 'pa.assignment_equipment_id')
                ->where('pa.assignment_custodian_id', $custodianId)
                ->where('pa.assignment_status', PropertyAssignment::STATUS_ACTIVE)
                ->whereNotNull('held.equipment_room_id')
                ->distinct()
                ->pluck('held.equipment_room_id')
                ->all();

            $homeRoomId = DB::table('custodians_table')->where('custodian_id', $custodianId)->value('custodian_room_id');
            if ($homeRoomId) {
                $roomIds[] = $homeRoomId;
            }

            $query->where(function ($q) use ($roomIds) {
                $q->whereIn('room.room_type', self::OFFICE_ROOM_TYPES);
                if ($roomIds !== []) {
                    $q->orWhereIn('room.room_id', $roomIds);
                }
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('e.equipment_name', 'like', "%{$search}%")
                    ->orWhere('e.equipment_asset_tag', 'like', "%{$search}%")
                    ->orWhere('e.equipment_serial_number', 'like', "%{$search}%")
                    ->orWhere('room.room_name', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Rooms that still have unassigned, assignable items, for the room filter.
     */
    public static function assignableRoomOptions(): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return self::assignableItemsBase()
            ->groupBy('room.room_id', 'room.room_name')
            ->orderBy('room.room_name')
            ->get(['room.room_id', 'room.room_name', DB::raw('COUNT(*) as item_count')]);
    }

    private static function assignableItemsBase()
    {
        $query = DB::table('equipment_table as e')
            ->join('rooms_table as room', 'room.room_id', '=', 'e.equipment_room_id')
            ->where(function ($q) {
                $q->whereNull('room.room_type')
                    ->orWhere('room.room_type', '!=', RoomCategories::STORAGE_TYPE);
            })
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where('room.room_is_archived', false)
            );
        self::scopeUnassigned($query);

        return $query;
    }

    public static function returnAssignment(int $assignmentId, string $condition, ?string $notes = null): PropertyAssignment
    {
        if (! in_array($condition, self::RETURN_CONDITIONS, true)) {
            throw new RuntimeException('Choose the condition the item was returned in.');
        }

        return DB::transaction(function () use ($assignmentId, $condition, $notes) {
            $assignment = PropertyAssignment::query()
                ->whereKey($assignmentId)
                ->lockForUpdate()
                ->first();

            if (! $assignment || $assignment->assignment_status !== PropertyAssignment::STATUS_ACTIVE) {
                throw new RuntimeException('This assignment is no longer active.');
            }

            $assignment->update([
                'assignment_status' => PropertyAssignment::STATUS_RETURNED,
                'assignment_returned_at' => now(),
                'assignment_returned_by' => Auth::id(),
                'assignment_return_condition' => $condition,
                'assignment_return_notes' => self::cleanText($notes),
            ]);

            $equipment = DB::table('equipment_table')
                ->where('equipment_id', $assignment->assignment_equipment_id)
                ->first(['equipment_condition_status']);

            if ($equipment && $condition === 'Damaged' && $equipment->equipment_condition_status !== 'Damaged') {
                DB::table('equipment_table')
                    ->where('equipment_id', $assignment->assignment_equipment_id)
                    ->update(['equipment_condition_status' => 'Damaged']);

                EquipmentConditionHistory::record(
                    (int) $assignment->assignment_equipment_id,
                    $equipment->equipment_condition_status,
                    'Damaged',
                    'Property return',
                    $assignment->assignment_return_notes
                );
            }

            return $assignment;
        });
    }

    private static function baseQuery()
    {
        return DB::table('property_assignments_table as pa')
            ->leftJoin('custodians_table as person', 'person.custodian_id', '=', 'pa.assignment_custodian_id')
            ->leftJoin('rooms_table as room', 'room.room_id', '=', 'pa.assignment_room_id')
            ->leftJoin('workstation_slots_table as slot', 'slot.workstation_slot_id', '=', 'pa.assignment_slot_id')
            ->leftJoin('users_table as issuer', 'issuer.user_id', '=', 'pa.assignment_issued_by')
            ->leftJoin('users_table as receiver', 'receiver.user_id', '=', 'pa.assignment_returned_by')
            ->select(
                'pa.*',
                'person.custodian_full_name',
                'person.custodian_employee_id',
                'person.custodian_position',
                'room.room_name',
                'slot.workstation_slot_label',
                'issuer.user_full_name as issued_by_name',
                'receiver.user_full_name as returned_by_name'
            );
    }

    private static function cleanText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
