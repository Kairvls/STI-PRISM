<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Equipment model catalog for inventory intake.
 */
class EquipmentModels
{
    public const TABLE = 'equipment_models_table';

    public static function maxLength(): int
    {
        return InputLimits::MODEL;
    }

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * Labels for searchable select (option value = model name).
     *
     * @return Collection<int, string>
     */
    public static function options(): Collection
    {
        $names = collect();

        if (self::tableReady()) {
            $names = $names->merge(
                DB::table(self::TABLE)
                    ->where('model_is_active', true)
                    ->orderBy('model_name')
                    ->pluck('model_name')
            );
        }

        if (
            Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_model')
        ) {
            $names = $names->merge(
                DB::table('equipment_table')
                    ->whereNotNull('equipment_model')
                    ->where('equipment_model', '!=', '')
                    ->distinct()
                    ->orderBy('equipment_model')
                    ->pluck('equipment_model')
            );
        }

        $max = self::maxLength();
        $seen = [];

        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->map(fn ($name) => mb_substr($name, 0, $max))
            ->filter(function ($name) use (&$seen) {
                $key = mb_strtolower($name);
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;

                return true;
            })
            ->sort(fn ($a, $b) => strcasecmp($a, $b))
            ->values();
    }

    /**
     * Admin Classification module list (includes inactive).
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function manageList(?string $search = null, int $perPage = 15)
    {
        if (! self::tableReady()) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $query = DB::table(self::TABLE)
            ->orderByDesc('model_is_active')
            ->orderBy('model_name');

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where('model_name', 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public static function update(int $modelId, string $name, bool $active = true): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        $row = DB::table(self::TABLE)->where('model_id', $modelId)->first();
        if (! $row) {
            abort(404);
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $max = self::maxLength();
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter the model name.']);
        }
        if (mb_strlen($name) > $max) {
            throw ValidationException::withMessages(['name' => 'Model may not be longer than '.$max.' characters.']);
        }

        $dup = DB::table(self::TABLE)
            ->whereRaw('LOWER(model_name) = ?', [mb_strtolower($name)])
            ->where('model_id', '!=', $modelId)
            ->exists();
        if ($dup) {
            throw ValidationException::withMessages(['name' => 'That model already exists.']);
        }

        DB::table(self::TABLE)->where('model_id', $modelId)->update([
            'model_name' => $name,
            'model_is_active' => $active,
            'model_updated_at' => now(),
        ]);
    }

    public static function delete(int $modelId): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        DB::table(self::TABLE)->where('model_id', $modelId)->delete();
    }

    /**
     * Quick-add for searchable select. Returns id = name so the option value stores the label.
     *
     * @return object{id:string,name:string,existing:bool}
     */
    public static function quickAdd(string $name): object
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $max = self::maxLength();

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Type the model name first.']);
        }
        if (mb_strlen($name) > $max) {
            throw ValidationException::withMessages(['name' => 'Model may not be longer than '.$max.' characters.']);
        }

        if (! self::tableReady()) {
            return (object) [
                'id' => $name,
                'name' => $name,
                'existing' => false,
            ];
        }

        $existing = DB::table(self::TABLE)
            ->whereRaw('LOWER(model_name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (! (bool) ($existing->model_is_active ?? false)) {
                DB::table(self::TABLE)
                    ->where('model_id', $existing->model_id)
                    ->update([
                        'model_is_active' => true,
                        'model_updated_at' => now(),
                    ]);
            }

            $label = mb_substr(trim((string) $existing->model_name), 0, $max);

            return (object) [
                'id' => $label,
                'name' => $label,
                'existing' => true,
            ];
        }

        DB::table(self::TABLE)->insert([
            'model_name' => $name,
            'model_is_active' => true,
            'model_created_at' => now(),
            'model_updated_at' => now(),
        ]);

        return (object) [
            'id' => $name,
            'name' => $name,
            'existing' => false,
        ];
    }
}
