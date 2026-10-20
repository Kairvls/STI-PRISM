<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * The department / office a person in the people directory belongs to.
 * Separate from rooms: a department is an organisational unit, a room is a place.
 */
class Departments
{
    public const FILTERS = [
        'active' => 'Active',
        'archived' => 'Archived',
        'all' => 'All',
    ];

    public static function tableReady(): bool
    {
        if (! Schema::hasTable('departments_table')) {
            return false;
        }

        return Schema::hasColumn('departments_table', 'department_id')
            && (
                Schema::hasColumn('custodians_table', 'custodian_department_id')
                || Schema::hasColumn('reporters_table', 'reporter_department_id')
                || Schema::hasColumn('equipment_table', 'equipment_department_id')
            );
    }

    /**
     * Departments with their people and equipment counts, for the management page.
     */
    public static function summary(string $search = '', string $filter = 'active'): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $people = Persons::tableReady()
            ? DB::table('persons_table')
                ->whereNotNull('person_department_id')
                ->groupBy('person_department_id')
                ->select(
                    'person_department_id as custodian_department_id',
                    DB::raw('COUNT(*) as people_count'),
                    DB::raw("SUM(CASE WHEN person_status != 'Inactive' THEN 1 ELSE 0 END) as active_people_count")
                )
            : DB::table('custodians_table')
                ->whereNotNull('custodian_department_id')
                ->groupBy('custodian_department_id')
                ->select(
                    'custodian_department_id',
                    DB::raw('COUNT(*) as people_count'),
                    DB::raw("SUM(CASE WHEN custodian_status != 'Inactive' THEN 1 ELSE 0 END) as active_people_count")
                );

        $equipment = self::equipmentMembershipByDepartment();
        $hasEquipmentMembership = $equipment !== null;

        $rows = DB::table('departments_table as dept')
            ->leftJoinSub($people, 'p', 'p.custodian_department_id', '=', 'dept.department_id')
            ->when($hasEquipmentMembership, fn ($q) => $q->leftJoinSub($equipment, 'eq', 'eq.department_id', '=', 'dept.department_id'))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('dept.department_name', 'like', "%{$search}%")
                    ->orWhere('dept.department_description', 'like', "%{$search}%");
            }))
            ->when($filter === 'active', fn ($q) => $q->where('dept.department_is_archived', false))
            ->when($filter === 'archived', fn ($q) => $q->where('dept.department_is_archived', true))
            ->orderBy('dept.department_is_archived')
            ->orderBy('dept.department_name')
            ->get([
                'dept.*',
                DB::raw('COALESCE(p.people_count, 0) as people_count'),
                DB::raw('COALESCE(p.active_people_count, 0) as active_people_count'),
                DB::raw($hasEquipmentMembership ? 'COALESCE(eq.equipment_count, 0) as equipment_count' : '0 as equipment_count'),
                DB::raw($hasEquipmentMembership ? 'COALESCE(eq.equipment_units, 0) as equipment_units' : '0 as equipment_units'),
            ]);

        $roomsByDepartment = RoomDepartments::forDepartments(
            $rows->pluck('department_id')->all()
        );

        return $rows->map(function ($dept) use ($roomsByDepartment) {
            $rooms = $roomsByDepartment[(int) $dept->department_id] ?? [];
            $dept->rooms = $rooms;
            $dept->room_count = count($rooms);

            return $dept;
        });
    }

    /**
     * @return array{active: int, archived: int, assigned_people: int, unassigned_people: int, charged_equipment: int, department_equipment: int}
     */
    public static function stats(): array
    {
        if (! self::tableReady()) {
            return [
                'active' => 0,
                'archived' => 0,
                'assigned_people' => 0,
                'unassigned_people' => 0,
                'charged_equipment' => 0,
                'department_equipment' => 0,
            ];
        }

        $membership = self::equipmentMembershipByDepartment();
        $departmentEquipment = $membership
            ? (int) DB::query()->fromSub($membership, 'eq')->sum('equipment_count')
            : 0;

        return [
            'active' => DB::table('departments_table')->where('department_is_archived', false)->count(),
            'archived' => DB::table('departments_table')->where('department_is_archived', true)->count(),
            'assigned_people' => Persons::tableReady()
                ? DB::table('persons_table')->whereNotNull('person_department_id')->count()
                : DB::table('custodians_table')->whereNotNull('custodian_department_id')->count(),
            'unassigned_people' => Persons::tableReady()
                ? DB::table('persons_table')->whereNull('person_department_id')->count()
                : DB::table('custodians_table')->whereNull('custodian_department_id')->count(),
            // Same union total as the table (kept key for callers; alias added below).
            'charged_equipment' => $departmentEquipment,
            'department_equipment' => $departmentEquipment,
        ];
    }

    /**
     * Per-department distinct equipment counts: charged to the office OR actively
     * issued to someone who belongs to that office.
     *
     * @return \Illuminate\Database\Query\Builder|null
     */
    public static function equipmentMembershipByDepartment()
    {
        $hasCharge = Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_department_id');
        $hasHeld = PropertyAssignments::tableReady()
            && Schema::hasTable('equipment_table');

        if (! $hasCharge && ! $hasHeld) {
            return null;
        }

        $branches = [];

        if ($hasCharge) {
            $branches[] = DB::table('equipment_table')
                ->whereNotNull('equipment_department_id')
                ->where('equipment_inventory_status', '!=', 'Disposed')
                ->select(
                    'equipment_department_id as department_id',
                    'equipment_id',
                    'equipment_quantity'
                );
        }

        if ($hasHeld) {
            $held = DB::table('property_assignments_table as pa')
                ->join('equipment_table as e', 'e.equipment_id', '=', 'pa.assignment_equipment_id')
                ->join('custodians_table as c', 'c.custodian_id', '=', 'pa.assignment_custodian_id')
                ->where('pa.assignment_status', 'Active')
                ->where('e.equipment_inventory_status', '!=', 'Disposed');

            if (Persons::tableReady()) {
                $held->join('persons_table as p', 'p.person_id', '=', 'c.person_id')
                    ->whereNotNull('p.person_department_id')
                    ->select(
                        'p.person_department_id as department_id',
                        'e.equipment_id',
                        'e.equipment_quantity'
                    );
            } else {
                $held->whereNotNull('c.custodian_department_id')
                    ->select(
                        'c.custodian_department_id as department_id',
                        'e.equipment_id',
                        'e.equipment_quantity'
                    );
            }

            $branches[] = $held;
        }

        $union = array_shift($branches);
        foreach ($branches as $branch) {
            $union = $union->unionAll($branch);
        }

        $unique = DB::query()
            ->fromSub($union, 'membership_raw')
            ->groupBy('department_id', 'equipment_id')
            ->select(
                'department_id',
                'equipment_id',
                DB::raw('MAX(equipment_quantity) as equipment_quantity')
            );

        return DB::query()
            ->fromSub($unique, 'membership')
            ->groupBy('department_id')
            ->select(
                'department_id',
                DB::raw('COUNT(*) as equipment_count'),
                DB::raw('COALESCE(SUM(equipment_quantity), 0) as equipment_units')
            );
    }

    /**
     * Active departments for dropdowns. An archived department is still
     * included when it is the one currently selected, so edits keep it.
     */
    public static function options(?int $keepId = null): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return DB::table('departments_table')
            ->where(function ($q) use ($keepId) {
                $q->where('department_is_archived', false);
                if ($keepId) {
                    $q->orWhere('department_id', $keepId);
                }
            })
            ->orderBy('department_name')
            ->get(['department_id', 'department_name', 'department_is_archived']);
    }

    public static function find(int $departmentId): ?object
    {
        if (! self::tableReady()) {
            return null;
        }

        return DB::table('departments_table')->where('department_id', $departmentId)->first();
    }

    public static function create(array $data): int
    {
        $now = now();

        return (int) DB::table('departments_table')->insertGetId(self::columns($data) + [
            'department_is_archived' => false,
            'department_created_by' => Auth::id(),
            'department_created_at' => $now,
            'department_updated_at' => $now,
        ]);
    }

    public static function update(int $departmentId, array $data): void
    {
        DB::table('departments_table')
            ->where('department_id', $departmentId)
            ->update(self::columns($data) + ['department_updated_at' => now()]);
    }

    public static function setArchived(int $departmentId, bool $archived): void
    {
        DB::table('departments_table')
            ->where('department_id', $departmentId)
            ->update(['department_is_archived' => $archived, 'department_updated_at' => now()]);
    }

    /**
     * People in a department, and the equipment the office answers for: items charged
     * to it during a transfer plus items actively issued to its people.
     *
     * @return array{people: Collection, equipment: Collection}
     */
    public static function members(int $departmentId): array
    {
        $hasAssignments = PropertyAssignments::tableReady();

        $held = $hasAssignments
            ? DB::table('property_assignments_table')
                ->where('assignment_status', 'Active')
                ->groupBy('assignment_custodian_id')
                ->select('assignment_custodian_id', DB::raw('COUNT(*) as held_count'))
            : null;

        if (Persons::tableReady()) {
            $peopleRows = DB::table('persons_table as p')
                ->leftJoin('custodians_table as c', 'c.person_id', '=', 'p.person_id')
                ->leftJoin('rooms_table as r', 'r.room_id', '=', 'c.custodian_room_id')
                ->when($held, fn ($q) => $q->leftJoinSub($held, 'h', 'h.assignment_custodian_id', '=', 'c.custodian_id'))
                ->where('p.person_department_id', $departmentId)
                ->orderByRaw("CASE WHEN p.person_status = 'Inactive' THEN 1 ELSE 0 END")
                ->orderBy('p.person_full_name')
                ->get([
                    'p.person_id',
                    'c.custodian_id',
                    'p.person_employee_id as custodian_employee_id',
                    'p.person_full_name as custodian_full_name',
                    'p.person_employment_type as custodian_position',
                    'p.person_email_address as custodian_email_address',
                    'p.person_contact_number as custodian_contact_number',
                    'p.person_status as custodian_status',
                    'r.room_name',
                    DB::raw($held ? 'COALESCE(h.held_count, 0) as held_count' : '0 as held_count'),
                ]);

            // One display row per person; prefer the custodian row that holds the most items.
            $people = $peopleRows
                ->groupBy(fn ($row) => (int) $row->person_id)
                ->map(function (Collection $rows) {
                    return $rows->sort(function ($a, $b) {
                        $held = (int) ($b->held_count ?? 0) <=> (int) ($a->held_count ?? 0);
                        if ($held !== 0) {
                            return $held;
                        }

                        return (int) ($b->custodian_id ?? 0) <=> (int) ($a->custodian_id ?? 0);
                    })->first();
                })
                ->values();

            $peopleIds = $peopleRows->pluck('custodian_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        } else {
            $people = DB::table('custodians_table as c')
                ->leftJoin('rooms_table as r', 'r.room_id', '=', 'c.custodian_room_id')
                ->when($held, fn ($q) => $q->leftJoinSub($held, 'h', 'h.assignment_custodian_id', '=', 'c.custodian_id'))
                ->where('c.custodian_department_id', $departmentId)
                ->orderByRaw("CASE WHEN c.custodian_status = 'Inactive' THEN 1 ELSE 0 END")
                ->orderBy('c.custodian_full_name')
                ->get([
                    DB::raw('NULL as person_id'),
                    'c.custodian_id',
                    'c.custodian_employee_id',
                    'c.custodian_full_name',
                    'c.custodian_position',
                    'c.custodian_email_address',
                    'c.custodian_contact_number',
                    'c.custodian_status',
                    'r.room_name',
                    DB::raw($held ? 'COALESCE(h.held_count, 0) as held_count' : '0 as held_count'),
                ]);

            $peopleIds = $people->pluck('custodian_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        }

        $hasDepartment = Schema::hasColumn('equipment_table', 'equipment_department_id');

        if (! $hasDepartment && ($peopleIds === [] || ! $hasAssignments)) {
            return ['people' => $people, 'equipment' => collect()];
        }

        $equipment = DB::table('equipment_table as e')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->leftJoin('equipment_categories_table as cat', 'cat.equipment_category_id', '=', 'e.equipment_category_id')
            ->when($hasAssignments, fn ($q) => $q
                ->leftJoin('property_assignments_table as pa', function ($join) {
                    $join->on('pa.assignment_equipment_id', '=', 'e.equipment_id')
                        ->where('pa.assignment_status', 'Active');
                })
                ->leftJoin('custodians_table as holder', 'holder.custodian_id', '=', 'pa.assignment_custodian_id'))
            ->where('e.equipment_inventory_status', '!=', 'Disposed')
            ->where(function ($q) use ($hasDepartment, $hasAssignments, $peopleIds, $departmentId) {
                if ($hasDepartment) {
                    $q->where('e.equipment_department_id', $departmentId);
                }
                if ($hasAssignments && $peopleIds !== []) {
                    $q->orWhereIn('pa.assignment_custodian_id', $peopleIds);
                }
            })
            ->orderBy('e.equipment_name')
            ->orderBy('e.equipment_id')
            ->get(array_merge([
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_serial_number',
                'e.equipment_quantity',
                'e.equipment_condition_status',
                'e.equipment_inventory_status',
                'e.equipment_image',
                'r.room_name',
                'cat.equipment_category_name',
                DB::raw($hasDepartment ? 'e.equipment_department_id' : 'NULL as equipment_department_id'),
            ], $hasAssignments ? [
                'holder.custodian_id as holder_id',
                'holder.custodian_full_name as holder_name',
                'holder.custodian_department_id as holder_department_id',
                'pa.assignment_issued_at',
            ] : [
                DB::raw('NULL as holder_id'),
                DB::raw('NULL as holder_name'),
                DB::raw('NULL as holder_department_id'),
                DB::raw('NULL as assignment_issued_at'),
            ]));

        // Distinct equipment rows (multiple Active assignments should not inflate the list).
        $equipment = $equipment->unique('equipment_id')->values();

        return ['people' => $people, 'equipment' => $equipment];
    }

    public static function peopleCount(int $departmentId): int
    {
        if (Persons::tableReady()) {
            return (int) DB::table('persons_table')->where('person_department_id', $departmentId)->count();
        }

        return (int) DB::table('custodians_table')->where('custodian_department_id', $departmentId)->count();
    }

    /**
     * When an item is issued to a person, charge it to their department if unset.
     */
    public static function syncEquipmentChargeFromHolder(int $equipmentId, int $custodianId): void
    {
        if (
            $equipmentId < 1
            || $custodianId < 1
            || ! Schema::hasTable('equipment_table')
            || ! Schema::hasColumn('equipment_table', 'equipment_department_id')
        ) {
            return;
        }

        $departmentId = 0;
        if (Persons::tableReady()) {
            $departmentId = (int) DB::table('custodians_table as c')
                ->join('persons_table as p', 'p.person_id', '=', 'c.person_id')
                ->where('c.custodian_id', $custodianId)
                ->value('p.person_department_id');
        }
        if ($departmentId < 1 && Schema::hasColumn('custodians_table', 'custodian_department_id')) {
            $departmentId = (int) DB::table('custodians_table')
                ->where('custodian_id', $custodianId)
                ->value('custodian_department_id');
        }

        if ($departmentId < 1) {
            return;
        }

        DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->where(function ($q) {
                $q->whereNull('equipment_department_id')
                    ->orWhere('equipment_department_id', 0);
            })
            ->update(['equipment_department_id' => $departmentId]);
    }

    /**
     * Deletes a department nobody belongs to. Departments with people must be archived instead.
     */
    public static function delete(int $departmentId): void
    {
        $count = self::peopleCount($departmentId);
        if ($count > 0) {
            throw new RuntimeException(
                "{$count} ".($count === 1 ? 'person belongs' : 'people belong')
                .' to this department. Archive it instead, or move them to another department first.'
            );
        }

        DB::table('departments_table')->where('department_id', $departmentId)->delete();
    }

    private static function columns(array $data): array
    {
        $description = trim((string) ($data['department_description'] ?? ''));

        return [
            'department_name' => trim((string) $data['department_name']),
            'department_description' => $description !== '' ? $description : null,
        ];
    }
}
