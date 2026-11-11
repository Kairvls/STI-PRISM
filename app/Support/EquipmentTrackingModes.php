<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk vs Individual rules for inventory stocking.
 * Computer Equipment must be Individual (one identity per unit).
 * Other categories (furniture, consumables, etc.) may use Bulk quantity piles.
 */
class EquipmentTrackingModes
{
    public const BULK = 'Bulk';

    public const INDIVIDUAL = 'Individual';

    /** Category-name fragments that may stay Bulk even on an RR line. */
    public const CONSUMABLE_NEEDLES = [
        'consumable',
        'consumables',
        'supply',
        'supplies',
        'office supply',
        'office supplies',
        'ink',
        'toner',
        'cartridge',
        'paper',
        'bond paper',
        'cleaning',
        'janitorial',
        'chemical',
    ];

    public static function categoryName(?int $categoryId = null, ?string $categoryName = null): string
    {
        $name = strtolower(trim((string) $categoryName));
        if ($name === '' && $categoryId && $categoryId > 0 && Schema::hasTable('equipment_categories_table')) {
            $name = strtolower(trim((string) DB::table('equipment_categories_table')
                ->where('equipment_category_id', $categoryId)
                ->value('equipment_category_name')));
        }

        return $name;
    }

    public static function isConsumableCategory(?int $categoryId = null, ?string $categoryName = null): bool
    {
        $name = self::categoryName($categoryId, $categoryName);
        if ($name === '') {
            return false;
        }

        foreach (self::CONSUMABLE_NEEDLES as $needle) {
            if (str_contains($name, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** Computer parts that belong in COMPSETs / unique asset tracking. */
    public static function isComputerEquipmentCategory(?int $categoryId = null, ?string $categoryName = null): bool
    {
        $name = self::categoryName($categoryId, $categoryName);

        return $name !== '' && str_contains($name, 'computer equipment');
    }

    /** Computer Equipment must stock as Individual units. */
    public static function requiresIndividualForCategory(?int $categoryId = null, ?string $categoryName = null): bool
    {
        return self::isComputerEquipmentCategory($categoryId, $categoryName);
    }

    /**
     * @return array{Bulk: bool, Individual: bool} allowed modes for a category
     */
    public static function allowedForRrCategory(?int $categoryId = null, ?string $categoryName = null): array
    {
        if (self::requiresIndividualForCategory($categoryId, $categoryName)) {
            return [self::BULK => false, self::INDIVIDUAL => true];
        }

        return [self::BULK => true, self::INDIVIDUAL => true];
    }
}
