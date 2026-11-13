<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Borrowing event-name registry (e.g. City Founding Anniversary, Intramurals).
 */
class EventNames
{
    public const TABLE = 'borrowing_event_names_table';

    public const MAX_NAME = 50;

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * @return Collection<int, object{event_name_id:int,event_name_label:string}>
     */
    public static function options(): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return DB::table(self::TABLE)
            ->where('event_name_is_active', true)
            ->orderBy('event_name_sort')
            ->orderBy('event_name_label')
            ->get(['event_name_id', 'event_name_label']);
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
            ->orderByDesc('event_name_is_active')
            ->orderBy('event_name_sort')
            ->orderBy('event_name_label');

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where('event_name_label', 'like', '%'.$search.'%');
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
            return true;
        }

        return DB::table(self::TABLE)
            ->where('event_name_is_active', true)
            ->whereRaw('LOWER(event_name_label) = ?', [mb_strtolower($name)])
            ->exists();
    }

    /**
     * @return object{event_name_id:int,event_name_label:string,existing:bool}
     */
    public static function quickAdd(string $name): object
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Type the event name first.']);
        }
        if (mb_strlen($name) > self::MAX_NAME) {
            throw ValidationException::withMessages(['name' => 'Event name may not be longer than '.self::MAX_NAME.' characters.']);
        }

        if (! self::tableReady()) {
            throw ValidationException::withMessages(['name' => 'Event names are not available yet. Run migrations.']);
        }

        $existing = DB::table(self::TABLE)
            ->whereRaw('LOWER(event_name_label) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (! (bool) ($existing->event_name_is_active ?? false)) {
                DB::table(self::TABLE)
                    ->where('event_name_id', $existing->event_name_id)
                    ->update([
                        'event_name_is_active' => true,
                        'event_name_updated_at' => now(),
                    ]);
            }

            return (object) [
                'event_name_id' => (int) $existing->event_name_id,
                'event_name_label' => (string) $existing->event_name_label,
                'existing' => true,
            ];
        }

        $sort = (int) (DB::table(self::TABLE)->max('event_name_sort') ?? 0) + 10;
        $id = (int) DB::table(self::TABLE)->insertGetId([
            'event_name_label' => $name,
            'event_name_is_active' => true,
            'event_name_sort' => $sort,
            'event_name_created_at' => now(),
            'event_name_updated_at' => now(),
        ]);

        return (object) [
            'event_name_id' => $id,
            'event_name_label' => $name,
            'existing' => false,
        ];
    }

    public static function create(string $name): int
    {
        return self::quickAdd($name)->event_name_id;
    }

    public static function archive(int $id): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        DB::table(self::TABLE)
            ->where('event_name_id', $id)
            ->update([
                'event_name_is_active' => false,
                'event_name_updated_at' => now(),
            ]);
    }

    public static function restore(int $id): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        DB::table(self::TABLE)
            ->where('event_name_id', $id)
            ->update([
                'event_name_is_active' => true,
                'event_name_updated_at' => now(),
            ]);
    }
}
