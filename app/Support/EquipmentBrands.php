<?php

namespace App\Support;

use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Brand catalog for inventory intake (brands_table + equipment history).
 */
class EquipmentBrands
{
    public const TABLE = 'brands_table';

    public static function maxLength(): int
    {
        return InputLimits::BRAND;
    }

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * Labels for searchable select (option value = brand name).
     *
     * @return Collection<int, string>
     */
    public static function options(): Collection
    {
        $names = collect();

        if (self::tableReady()) {
            $query = Brand::query()->orderBy('brand_name');
            if (Schema::hasColumn(self::TABLE, 'brand_status')) {
                $query->where('brand_status', 'Active');
            }
            $names = $names->merge($query->pluck('brand_name'));
        }

        if (
            Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_brand_name')
        ) {
            $names = $names->merge(
                DB::table('equipment_table')
                    ->whereNotNull('equipment_brand_name')
                    ->where('equipment_brand_name', '!=', '')
                    ->distinct()
                    ->orderBy('equipment_brand_name')
                    ->pluck('equipment_brand_name')
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

        $query = Brand::query()->orderBy('brand_name');

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where('brand_name', 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public static function update(int $brandId, string $name, string $status = 'Active'): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        $brand = Brand::findOrFail($brandId);
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $max = self::maxLength();

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter the brand name.']);
        }
        if (mb_strlen($name) > $max) {
            throw ValidationException::withMessages(['name' => 'Brand name may not be longer than '.$max.' characters.']);
        }
        if (! in_array($status, ['Active', 'Inactive'], true)) {
            throw ValidationException::withMessages(['status' => 'Status must be Active or Inactive.']);
        }

        $dup = Brand::query()
            ->whereRaw('LOWER(brand_name) = ?', [mb_strtolower($name)])
            ->where('brand_id', '!=', $brandId)
            ->exists();
        if ($dup) {
            throw ValidationException::withMessages(['name' => 'That brand already exists.']);
        }

        $brand->update([
            'brand_name' => $name,
            'brand_status' => $status,
            'brand_updated_at' => now(),
        ]);
    }

    public static function delete(int $brandId): void
    {
        if (! self::tableReady()) {
            abort(404);
        }

        $brand = Brand::findOrFail($brandId);

        if (
            Schema::hasTable('requisition_issue_slip_items_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')
            && DB::table('requisition_issue_slip_items_table')->where('ris_item_brand_id', $brand->brand_id)->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => 'Cannot delete this brand because it is used on one or more RIS items.',
            ]);
        }

        $brand->delete();
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
            throw ValidationException::withMessages(['name' => 'Type the brand name first.']);
        }
        if (mb_strlen($name) > $max) {
            throw ValidationException::withMessages(['name' => 'Brand name may not be longer than '.$max.' characters.']);
        }

        if (! self::tableReady()) {
            return (object) [
                'id' => $name,
                'name' => $name,
                'existing' => false,
            ];
        }

        $existing = Brand::query()
            ->whereRaw('LOWER(brand_name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (
                Schema::hasColumn(self::TABLE, 'brand_status')
                && strcasecmp((string) $existing->brand_status, 'Active') !== 0
            ) {
                $existing->update([
                    'brand_status' => 'Active',
                    'brand_updated_at' => now(),
                ]);
            }

            $label = mb_substr(trim((string) $existing->brand_name), 0, $max);

            return (object) [
                'id' => $label,
                'name' => $label,
                'existing' => true,
            ];
        }

        $now = now();
        Brand::create([
            'brand_name' => $name,
            'brand_status' => 'Active',
            'brand_created_at' => $now,
            'brand_updated_at' => $now,
        ]);

        return (object) [
            'id' => $name,
            'name' => $name,
            'existing' => false,
        ];
    }
}
