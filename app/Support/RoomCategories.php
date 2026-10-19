<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Room types used on rooms_table.room_type.
 * Backed by room_types_table when migrated; falls back to built-in defaults.
 */
class RoomCategories
{
    public const STORAGE_TYPE = 'Storage / Stockroom';

    public const TABLE = 'room_types_table';

    /**
     * Built-in defaults used before/without the types table, and as seed source.
     *
     * @return array<string, string> name => label
     */
    public static function defaults(): array
    {
        return [
            'Lecture Room' => 'Lecture Room',
            'Computer Laboratory' => 'Computer Laboratory',
            'HM Room' => 'HM Room / Bar',
            'Hotel Room Simulation' => 'Hotel Room Simulation',
            'Faculty Room' => 'Faculty Room',
            'Office' => 'Office',
            'Library' => 'Library',
            'School Clinic' => 'School Clinic',
            'Maintenance' => 'Maintenance',
            self::STORAGE_TYPE => 'Storage / Stockroom',
        ];
    }

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE)
            && Schema::hasColumn(self::TABLE, 'room_type_name');
    }

    /**
     * Active types for dropdowns: name => display label.
     * Archived types are included when they match $keepName so edits stay valid.
     *
     * @return array<string, string>
     */
    public static function options(?string $keepName = null): array
    {
        if (! self::tableReady()) {
            $options = self::defaults();
            if ($keepName && $keepName !== '' && ! isset($options[$keepName])) {
                $options[$keepName] = $keepName;
            }

            return $options;
        }

        $rows = DB::table(self::TABLE)
            ->where(function ($q) use ($keepName) {
                $q->where('room_type_is_archived', false);
                if (filled($keepName)) {
                    $q->orWhere('room_type_name', $keepName);
                }
            })
            ->orderBy('room_type_sort')
            ->orderBy('room_type_name')
            ->get(['room_type_name', 'room_type_label']);

        $options = [];
        foreach ($rows as $row) {
            $name = (string) $row->room_type_name;
            $options[$name] = (string) ($row->room_type_label ?: $name);
        }

        if (filled($keepName) && ! isset($options[$keepName])) {
            $options[$keepName] = $keepName;
        }

        return $options !== [] ? $options : self::defaults();
    }

    /**
     * @return list<string>
     */
    public static function values(?string $keepName = null): array
    {
        return array_keys(self::options($keepName));
    }

    public static function isStorageType(?string $roomType): bool
    {
        $name = trim((string) $roomType);
        if ($name === '') {
            return false;
        }

        if ($name === self::STORAGE_TYPE) {
            return true;
        }

        if (! self::tableReady()) {
            return false;
        }

        return (bool) DB::table(self::TABLE)
            ->where('room_type_name', $name)
            ->where('room_type_is_storage', true)
            ->exists();
    }

    /**
     * All types for the manage UI (including archived).
     */
    public static function manageList(string $search = ''): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        // Count in PHP to avoid collation mismatches between rooms_table.room_type
        // (often utf8mb4_unicode_ci) and room_types_table (MySQL 8 default 0900_ai_ci).
        $roomCounts = [];
        if (Schema::hasTable('rooms_table') && Schema::hasColumn('rooms_table', 'room_type')) {
            $rows = DB::table('rooms_table')
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($q) => $q->where(function ($inner) {
                        $inner->where('room_is_archived', false)->orWhereNull('room_is_archived');
                    })
                )
                ->whereNotNull('room_type')
                ->where('room_type', '!=', '')
                ->groupBy('room_type')
                ->select('room_type', DB::raw('COUNT(*) as rooms_count'))
                ->get();

            foreach ($rows as $row) {
                $roomCounts[mb_strtolower(trim((string) $row->room_type))] = (int) $row->rooms_count;
            }
        }

        return DB::table(self::TABLE.' as t')
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('t.room_type_name', 'like', "%{$search}%")
                    ->orWhere('t.room_type_label', 'like', "%{$search}%");
            }))
            ->orderBy('t.room_type_is_archived')
            ->orderBy('t.room_type_sort')
            ->orderBy('t.room_type_name')
            ->get(['t.*'])
            ->map(function ($type) use ($roomCounts) {
                $key = mb_strtolower(trim((string) $type->room_type_name));
                $type->rooms_count = $roomCounts[$key] ?? 0;

                return $type;
            });
    }

    public static function find(int $id): ?object
    {
        if (! self::tableReady()) {
            return null;
        }

        return DB::table(self::TABLE)->where('room_type_id', $id)->first();
    }

    public static function create(array $data): int
    {
        $now = now();
        $name = trim((string) ($data['room_type_name'] ?? ''));
        $label = trim((string) ($data['room_type_label'] ?? ''));
        $maxSort = (int) DB::table(self::TABLE)->max('room_type_sort');

        return (int) DB::table(self::TABLE)->insertGetId([
            'room_type_name' => $name,
            'room_type_label' => $label !== '' && $label !== $name ? $label : null,
            'room_type_is_storage' => ! empty($data['room_type_is_storage']),
            'room_type_is_archived' => false,
            'room_type_sort' => $maxSort + 10,
            'room_type_created_at' => $now,
            'room_type_updated_at' => $now,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        $current = self::find($id);
        if (! $current) {
            return;
        }

        $name = trim((string) ($data['room_type_name'] ?? $current->room_type_name));
        $label = trim((string) ($data['room_type_label'] ?? ''));
        $oldName = (string) $current->room_type_name;

        DB::table(self::TABLE)->where('room_type_id', $id)->update([
            'room_type_name' => $name,
            'room_type_label' => $label !== '' && $label !== $name ? $label : null,
            'room_type_is_storage' => ! empty($data['room_type_is_storage']),
            'room_type_updated_at' => now(),
        ]);

        if ($oldName !== $name && Schema::hasTable('rooms_table') && Schema::hasColumn('rooms_table', 'room_type')) {
            DB::table('rooms_table')->where('room_type', $oldName)->update([
                'room_type' => $name,
            ]);
        }
    }

    public static function setArchived(int $id, bool $archived): void
    {
        DB::table(self::TABLE)->where('room_type_id', $id)->update([
            'room_type_is_archived' => $archived,
            'room_type_updated_at' => now(),
        ]);
    }

    public static function delete(int $id): bool
    {
        $current = self::find($id);
        if (! $current) {
            return false;
        }

        $inUse = Schema::hasTable('rooms_table') && Schema::hasColumn('rooms_table', 'room_type')
            ? DB::table('rooms_table')->where('room_type', $current->room_type_name)->exists()
            : false;

        if ($inUse) {
            return false;
        }

        DB::table(self::TABLE)->where('room_type_id', $id)->delete();

        return true;
    }
}
