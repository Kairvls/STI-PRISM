<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Many departments can be assigned to one room (and the reverse).
 */
class RoomDepartments
{
    public const TABLE = 'room_departments_table';

    public static function ready(): bool
    {
        return Schema::hasTable(self::TABLE)
            && Schema::hasTable('departments_table')
            && Schema::hasTable('rooms_table');
    }

    /**
     * @param  iterable<int>  $roomIds
     * @return array<int, list<array{id:int,name:string}>>
     */
    public static function forRooms(iterable $roomIds): array
    {
        $ids = collect($roomIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty() || ! self::ready()) {
            return [];
        }

        $rows = DB::table(self::TABLE.' as rd')
            ->join('departments_table as d', 'd.department_id', '=', 'rd.department_id')
            ->whereIn('rd.room_id', $ids->all())
            ->orderBy('d.department_name')
            ->get(['rd.room_id', 'd.department_id', 'd.department_name']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->room_id][] = [
                'id' => (int) $row->department_id,
                'name' => (string) $row->department_name,
            ];
        }

        // Legacy single-column fallback when pivot has no rows for a room yet.
        if (Schema::hasColumn('rooms_table', 'room_department_id')) {
            $legacy = DB::table('rooms_table as r')
                ->join('departments_table as d', 'd.department_id', '=', 'r.room_department_id')
                ->whereIn('r.room_id', $ids->all())
                ->whereNotNull('r.room_department_id')
                ->get(['r.room_id', 'd.department_id', 'd.department_name']);

            foreach ($legacy as $row) {
                $roomId = (int) $row->room_id;
                $deptId = (int) $row->department_id;
                $already = collect($map[$roomId] ?? [])->contains(fn ($d) => $d['id'] === $deptId);
                if (! $already) {
                    $map[$roomId][] = [
                        'id' => $deptId,
                        'name' => (string) $row->department_name,
                    ];
                }
            }
        }

        return $map;
    }

    /**
     * @return list<int>
     */
    public static function idsForRoom(int $roomId): array
    {
        return collect(self::forRooms([$roomId])[$roomId] ?? [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Rooms linked to each department (pivot + legacy room_department_id).
     *
     * @param  iterable<int>  $departmentIds
     * @return array<int, list<array{id:int,name:string}>>
     */
    public static function forDepartments(iterable $departmentIds): array
    {
        $ids = collect($departmentIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty() || ! self::ready()) {
            return [];
        }

        $map = [];
        $rows = DB::table(self::TABLE.' as rd')
            ->join('rooms_table as r', 'r.room_id', '=', 'rd.room_id')
            ->whereIn('rd.department_id', $ids->all())
            ->orderBy('r.room_name')
            ->get(['rd.department_id', 'r.room_id', 'r.room_name']);

        foreach ($rows as $row) {
            $deptId = (int) $row->department_id;
            $map[$deptId][] = [
                'id' => (int) $row->room_id,
                'name' => (string) $row->room_name,
            ];
        }

        if (Schema::hasColumn('rooms_table', 'room_department_id')) {
            $legacy = DB::table('rooms_table')
                ->whereIn('room_department_id', $ids->all())
                ->whereNotNull('room_department_id')
                ->orderBy('room_name')
                ->get(['room_department_id', 'room_id', 'room_name']);

            foreach ($legacy as $row) {
                $deptId = (int) $row->room_department_id;
                $roomId = (int) $row->room_id;
                $already = collect($map[$deptId] ?? [])->contains(fn ($room) => $room['id'] === $roomId);
                if (! $already) {
                    $map[$deptId][] = [
                        'id' => $roomId,
                        'name' => (string) $row->room_name,
                    ];
                }
            }
        }

        foreach ($map as $deptId => $rooms) {
            $map[$deptId] = collect($rooms)
                ->unique('id')
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        }

        return $map;
    }

    /**
     * Replace the department set for a room.
     *
     * @param  list<int|string|null>  $departmentIds
     */
    public static function sync(int $roomId, array $departmentIds): void
    {
        if (! self::ready()) {
            return;
        }

        $ids = collect($departmentIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        DB::table(self::TABLE)->where('room_id', $roomId)->delete();

        if ($ids->isEmpty()) {
            if (Schema::hasColumn('rooms_table', 'room_department_id')) {
                DB::table('rooms_table')->where('room_id', $roomId)->update([
                    'room_department_id' => null,
                ]);
            }

            return;
        }

        $valid = DB::table('departments_table')
            ->whereIn('department_id', $ids->all())
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $now = now();
        foreach ($valid as $departmentId) {
            DB::table(self::TABLE)->insert([
                'room_id' => $roomId,
                'department_id' => $departmentId,
                'created_at' => $now,
            ]);
        }

        // Keep legacy column in sync with the first assigned department.
        if (Schema::hasColumn('rooms_table', 'room_department_id')) {
            DB::table('rooms_table')->where('room_id', $roomId)->update([
                'room_department_id' => $valid[0] ?? null,
            ]);
        }
    }

    /**
     * Department ids keyed by room, for the inspection picker.
     *
     * @return array<int, list<int>>
     */
    public static function idsByRoom(): array
    {
        if (! self::ready()) {
            return [];
        }

        $map = [];
        DB::table(self::TABLE)
            ->orderBy('room_id')
            ->orderBy('department_id')
            ->get(['room_id', 'department_id'])
            ->each(function ($row) use (&$map) {
                $map[(int) $row->room_id][] = (int) $row->department_id;
            });

        return $map;
    }

    /**
     * @param  list<array{id:int,name:string}>  $departments
     */
    public static function label(array $departments): string
    {
        if ($departments === []) {
            return '—';
        }

        return collect($departments)->pluck('name')->implode(', ');
    }
}
