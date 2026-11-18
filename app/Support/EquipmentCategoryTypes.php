<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Maintained equipment names (types) inside each classification.
 * Drives inventory auto-prediction and the Add-to-inventory name catalog.
 */
class EquipmentCategoryTypes
{
    public const TABLE = 'equipment_category_types_table';

    public static function maxLength(): int
    {
        return InputLimits::NAME;
    }

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * @return Collection<int, object>
     */
    public static function forCategory(int $categoryId, bool $activeOnly = true): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $query = DB::table(self::TABLE)
            ->where('equipment_category_id', $categoryId)
            ->orderBy('type_name');

        if ($activeOnly) {
            $query->where('type_is_active', true);
        }

        return $query->get();
    }

    /**
     * All active types with classification name (for wizard pickers / detect).
     *
     * @return Collection<int, object{type_id:int,type_name:string,equipment_category_id:int,equipment_category_name:string,type_aliases:?string}>
     */
    public static function options(): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return DB::table(self::TABLE)
            ->join(
                'equipment_categories_table',
                'equipment_categories_table.equipment_category_id',
                '=',
                self::TABLE.'.equipment_category_id'
            )
            ->where(self::TABLE.'.type_is_active', true)
            ->orderBy('equipment_categories_table.equipment_category_name')
            ->orderBy(self::TABLE.'.type_name')
            ->get([
                self::TABLE.'.type_id',
                self::TABLE.'.type_name',
                self::TABLE.'.type_aliases',
                self::TABLE.'.equipment_category_id',
                'equipment_categories_table.equipment_category_name',
            ]);
    }

    /**
     * Counts keyed by category id.
     *
     * @return array<int, int>
     */
    public static function countsByCategory(): array
    {
        if (! self::tableReady()) {
            return [];
        }

        return DB::table(self::TABLE)
            ->where('type_is_active', true)
            ->select('equipment_category_id', DB::raw('COUNT(*) as c'))
            ->groupBy('equipment_category_id')
            ->pluck('c', 'equipment_category_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function needlesFor(string $typeName, ?string $aliases = null): array
    {
        $needles = [];
        $push = function (string $raw) use (&$needles) {
            $value = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $raw) ?? ''));
            if ($value === '' || in_array($value, $needles, true)) {
                return;
            }
            $needles[] = $value;
        };

        $push($typeName);

        foreach (preg_split('/[,;|]+/', (string) $aliases) ?: [] as $alias) {
            $push((string) $alias);
        }

        // Longer needles first so "ethernet cable" beats "cable".
        usort($needles, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $needles;
    }

    /**
     * Detect classification name from an equipment name using the maintained list.
     */
    public static function detectCategoryName(?string $equipmentName): ?string
    {
        $match = self::detectType($equipmentName);

        return $match?->equipment_category_name;
    }

    /**
     * @return object{type_id:int,type_name:string,equipment_category_id:int,equipment_category_name:string}|null
     */
    public static function detectType(?string $equipmentName): ?object
    {
        $name = mb_strtolower(trim((string) $equipmentName));
        if ($name === '') {
            return null;
        }

        $best = null;
        $bestLen = 0;

        foreach (self::options() as $type) {
            foreach (self::needlesFor((string) $type->type_name, $type->type_aliases ?? null) as $needle) {
                if ($needle === '' || ! str_contains($name, $needle)) {
                    continue;
                }
                $len = mb_strlen($needle);
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $best = $type;
                }
            }
        }

        return $best;
    }

    /**
     * Rules payload for browser auto-detect (replaces hardcoded SuggestedIssues rules when ready).
     *
     * @param  iterable<int, object>  $categories
     * @return array{ids: array<string, int>, rules: list<array{needles: list<string>, category: string}>}
     */
    public static function categoryDetectPayload($categories): array
    {
        $ids = [];
        foreach ($categories as $category) {
            $ids[$category->equipment_category_name] = (int) $category->equipment_category_id;
        }

        $rules = [];
        foreach (self::options() as $type) {
            $needles = self::needlesFor((string) $type->type_name, $type->type_aliases ?? null);
            if ($needles === []) {
                continue;
            }
            $rules[] = [
                'needles' => $needles,
                'category' => (string) $type->equipment_category_name,
                'type' => (string) $type->type_name,
            ];
        }

        // Prefer longer needles across types.
        usort($rules, function ($a, $b) {
            $la = max(array_map('mb_strlen', $a['needles'] ?: ['']));
            $lb = max(array_map('mb_strlen', $b['needles'] ?: ['']));

            return $lb <=> $la;
        });

        return [
            'ids' => $ids,
            'rules' => $rules,
        ];
    }

    /**
     * @return object{type_id:int,type_name:string,existing:bool}
     */
    public static function quickAdd(int $categoryId, string $name, ?string $aliases = null): object
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $max = self::maxLength();

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Type the equipment name first.']);
        }
        if (mb_strlen($name) > $max) {
            throw ValidationException::withMessages(['name' => 'Equipment name may not be longer than '.$max.' characters.']);
        }
        if (! self::tableReady()) {
            throw ValidationException::withMessages(['name' => 'Equipment types are not available yet. Run migrations.']);
        }

        $categoryExists = DB::table('equipment_categories_table')
            ->where('equipment_category_id', $categoryId)
            ->exists();
        if (! $categoryExists) {
            throw ValidationException::withMessages(['name' => 'Classification not found.']);
        }

        $aliases = self::normalizeAliases($aliases);

        $existing = DB::table(self::TABLE)
            ->where('equipment_category_id', $categoryId)
            ->whereRaw('LOWER(type_name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            $update = [
                'type_is_active' => true,
                'type_updated_at' => now(),
            ];
            if ($aliases !== null) {
                $update['type_aliases'] = $aliases;
            }
            DB::table(self::TABLE)->where('type_id', $existing->type_id)->update($update);

            return (object) [
                'type_id' => (int) $existing->type_id,
                'type_name' => (string) $existing->type_name,
                'existing' => true,
            ];
        }

        $id = (int) DB::table(self::TABLE)->insertGetId([
            'equipment_category_id' => $categoryId,
            'type_name' => $name,
            'type_aliases' => $aliases,
            'type_is_active' => true,
            'type_created_at' => now(),
            'type_updated_at' => now(),
        ]);

        return (object) [
            'type_id' => $id,
            'type_name' => $name,
            'existing' => false,
        ];
    }

    public static function update(int $typeId, string $name, ?string $aliases = null, bool $active = true): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        $row = DB::table(self::TABLE)->where('type_id', $typeId)->first();
        if (! $row) {
            abort(404);
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $max = self::maxLength();
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter the equipment name.']);
        }
        if (mb_strlen($name) > $max) {
            throw ValidationException::withMessages(['name' => 'Equipment name may not be longer than '.$max.' characters.']);
        }

        $dup = DB::table(self::TABLE)
            ->where('equipment_category_id', $row->equipment_category_id)
            ->whereRaw('LOWER(type_name) = ?', [mb_strtolower($name)])
            ->where('type_id', '!=', $typeId)
            ->exists();
        if ($dup) {
            throw ValidationException::withMessages(['name' => 'That equipment name already exists in this classification.']);
        }

        DB::table(self::TABLE)->where('type_id', $typeId)->update([
            'type_name' => $name,
            'type_aliases' => self::normalizeAliases($aliases),
            'type_is_active' => $active,
            'type_updated_at' => now(),
        ]);
    }

    public static function delete(int $typeId): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        DB::table(self::TABLE)->where('type_id', $typeId)->delete();
    }

    public static function normalizeAliases(?string $aliases): ?string
    {
        if ($aliases === null) {
            return null;
        }

        $parts = collect(preg_split('/[,;|]+/', $aliases) ?: [])
            ->map(fn ($part) => trim(preg_replace('/\s+/u', ' ', (string) $part) ?? ''))
            ->filter()
            ->unique(fn ($part) => mb_strtolower($part))
            ->values();

        if ($parts->isEmpty()) {
            return null;
        }

        return $parts->implode(', ');
    }
}
