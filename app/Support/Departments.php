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
        return Schema::hasTable('departments_table')
            && Schema::hasColumn('custodians_table', 'custodian_department_id');
    }

    /**
     * Departments with their people and equipment counts, for the management page.
     */
    public static function summary(string $search = '', string $filter = 'active'): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $people = DB::table('custodians_table')
            ->whereNotNull('custodian_department_id')
            ->groupBy('custodian_department_id')
            ->select(
                'custodian_department_id',
                DB::raw('COUNT(*) as people_count'),
                DB::raw("SUM(CASE WHEN custodian_status != 'Inactive' THEN 1 ELSE 0 END) as active_people_count")
            );

        $hasEquipment = Schema::hasColumn('equipment_table', 'equipment_department_id');
        $equipment = DB::table('equipment_table')
            ->whereNotNull('equipment_department_id')
            ->where('equipment_inventory_status', '!=', 'Disposed')
            ->groupBy('equipment_department_id')
            ->select(
                'equipment_department_id',
                DB::raw('COUNT(*) as equipment_count'),
                DB::raw('COALESCE(SUM(equipment_quantity), 0) as equipment_units')
            );

        return DB::table('departments_table as dept')
            ->leftJoinSub($people, 'p', 'p.custodian_department_id', '=', 'dept.department_id')
            ->when($hasEquipment, fn ($q) => $q->leftJoinSub($equipment, 'eq', 'eq.equipment_department_id', '=', 'dept.department_id'))
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
                DB::raw($hasEquipment ? 'COALESCE(eq.equipment_count, 0) as equipment_count' : '0 as equipment_count'),
                DB::raw($hasEquipment ? 'COALESCE(eq.equipment_units, 0) as equipment_units' : '0 as equipment_units'),
            ]);
    }

    /**
     * @return array{active: int, archived: int, assigned_people: int, unassigned_people: int, charged_equipment: int}
     */
    public static function stats(): array
    {
        if (! self::tableReady()) {
            return ['active' => 0, 'archived' => 0, 'assigned_people' => 0, 'unassigned_people' => 0, 'charged_equipment' => 0];
        }

        return [
            'active' => DB::table('departments_table')->where('department_is_archived', false)->count(),
            'archived' => DB::table('departments_table')->where('department_is_archived', true)->count(),
            'assigned_people' => DB::table('custodians_table')->whereNotNull('custodian_department_id')->count(),
            'unassigned_people' => DB::table('custodians_table')->whereNull('custodian_department_id')->count(),
            'charged_equipment' => Schema::hasColumn('equipment_table', 'equipment_department_id')
                ? DB::table('equipment_table')
                    ->whereNotNull('equipment_department_id')
                    ->where('equipment_inventory_status', '!=', 'Disposed')
                    ->count()
                : 0,
        ];
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

        $people = DB::table('custodians_table as c')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'c.custodian_room_id')
            ->when($held, fn ($q) => $q->leftJoinSub($held, 'h', 'h.assignment_custodian_id', '=', 'c.custodian_id'))
            ->where('c.custodian_department_id', $departmentId)
            ->orderByRaw("CASE WHEN c.custodian_status = 'Inactive' THEN 1 ELSE 0 END")
            ->orderBy('c.custodian_full_name')
            ->get([
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

        $hasDepartment = Schema::hasColumn('equipment_table', 'equipment_department_id');
        $peopleIds = $people->pluck('custodian_id')->all();

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

        return ['people' => $people, 'equipment' => $equipment];
    }

    public static function peopleCount(int $departmentId): int
    {
        return DB::table('custodians_table')->where('custodian_department_id', $departmentId)->count();
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
