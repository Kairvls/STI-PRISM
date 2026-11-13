<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Borrowing event-type registry (Campus activity, City / LGU event, custom names).
 */
class EventTypes
{
    public const TABLE = 'borrowing_event_types_table';

    public const MAX_NAME = 50;

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * Active types for the Borrow Equipment picker.
     *
     * @return Collection<int, object{event_type_id:int,event_type_name:string}>
     */
    public static function options(): Collection
    {
        if (! self::tableReady()) {
            return collect(self::fallbackDefaults())->map(fn ($name, $i) => (object) [
                'event_type_id' => $i + 1,
                'event_type_name' => $name,
            ]);
        }

        return DB::table(self::TABLE)
            ->where('event_type_is_active', true)
            ->orderBy('event_type_sort')
            ->orderBy('event_type_name')
            ->get(['event_type_id', 'event_type_name']);
    }

    /**
     * @return Collection<int, object>
     */
    public static function manageList(?string $search = null): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $query = DB::table(self::TABLE)
            ->orderByDesc('event_type_is_active')
            ->orderBy('event_type_sort')
            ->orderBy('event_type_name');

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where('event_type_name', 'like', '%'.$search.'%');
        }

        return $query->get();
    }

    public static function isActiveName(?string $name): bool
    {
        $name = trim((string) $name);
        if ($name === '') {
            return false;
        }

        if (! self::tableReady()) {
            return collect(self::fallbackDefaults())
                ->contains(fn ($row) => mb_strtolower($row) === mb_strtolower($name));
        }

        return DB::table(self::TABLE)
            ->where('event_type_is_active', true)
            ->whereRaw('LOWER(event_type_name) = ?', [mb_strtolower($name)])
            ->exists();
    }

    /**
     * Quick-add for searchable select. Returns id = name so option value stores the label.
     *
     * @return object{event_type_id:int,event_type_name:string,existing:bool}
     */
    public static function quickAdd(string $name): object
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Type the event type name first.']);
        }
        if (mb_strlen($name) > self::MAX_NAME) {
            throw ValidationException::withMessages(['name' => 'Event type may not be longer than '.self::MAX_NAME.' characters.']);
        }

        if (! self::tableReady()) {
            throw ValidationException::withMessages(['name' => 'Event types are not available yet. Run migrations.']);
        }

        $existing = DB::table(self::TABLE)
            ->whereRaw('LOWER(event_type_name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (! (bool) ($existing->event_type_is_active ?? false)) {
                DB::table(self::TABLE)
                    ->where('event_type_id', $existing->event_type_id)
                    ->update([
                        'event_type_is_active' => true,
                        'event_type_updated_at' => now(),
                    ]);
            }

            return (object) [
                'event_type_id' => (int) $existing->event_type_id,
                'event_type_name' => (string) $existing->event_type_name,
                'existing' => true,
            ];
        }

        $sort = (int) (DB::table(self::TABLE)->max('event_type_sort') ?? 0) + 10;
        $id = (int) DB::table(self::TABLE)->insertGetId([
            'event_type_name' => $name,
            'event_type_is_active' => true,
            'event_type_sort' => $sort,
            'event_type_created_at' => now(),
            'event_type_updated_at' => now(),
        ]);

        return (object) [
            'event_type_id' => $id,
            'event_type_name' => $name,
            'existing' => false,
        ];
    }

    public static function create(string $name): int
    {
        return self::quickAdd($name)->event_type_id;
    }

    public static function update(int $id, string $name, bool $active = true): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter the event type name.']);
        }
        if (mb_strlen($name) > self::MAX_NAME) {
            throw ValidationException::withMessages(['name' => 'Event type may not be longer than '.self::MAX_NAME.' characters.']);
        }

        $dup = DB::table(self::TABLE)
            ->whereRaw('LOWER(event_type_name) = ?', [mb_strtolower($name)])
            ->where('event_type_id', '!=', $id)
            ->exists();
        if ($dup) {
            throw ValidationException::withMessages(['name' => 'That event type already exists.']);
        }

        DB::table(self::TABLE)
            ->where('event_type_id', $id)
            ->update([
                'event_type_name' => $name,
                'event_type_is_active' => $active,
                'event_type_updated_at' => now(),
            ]);
    }

    public static function archive(int $id): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        DB::table(self::TABLE)
            ->where('event_type_id', $id)
            ->update([
                'event_type_is_active' => false,
                'event_type_updated_at' => now(),
            ]);
    }

    public static function restore(int $id): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        DB::table(self::TABLE)
            ->where('event_type_id', $id)
            ->update([
                'event_type_is_active' => true,
                'event_type_updated_at' => now(),
            ]);
    }

    /**
     * @return list<string>
     */
    public static function fallbackDefaults(): array
    {
        return [
            'Campus activity',
            'Other school / partner',
            'City / LGU event',
            'Other',
        ];
    }
}
