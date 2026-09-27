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
     * Departments with their people counts, for the management page.
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

        return DB::table('departments_table as dept')
            ->leftJoinSub($people, 'p', 'p.custodian_department_id', '=', 'dept.department_id')
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
            ]);
    }

    /**
     * @return array{active: int, archived: int, assigned_people: int, unassigned_people: int}
     */
    public static function stats(): array
    {
        if (! self::tableReady()) {
            return ['active' => 0, 'archived' => 0, 'assigned_people' => 0, 'unassigned_people' => 0];
        }

        return [
            'active' => DB::table('departments_table')->where('department_is_archived', false)->count(),
            'archived' => DB::table('departments_table')->where('department_is_archived', true)->count(),
            'assigned_people' => DB::table('custodians_table')->whereNotNull('custodian_department_id')->count(),
            'unassigned_people' => DB::table('custodians_table')->whereNull('custodian_department_id')->count(),
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
