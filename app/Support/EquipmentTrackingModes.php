<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk vs Individual rules for inventory stocking.
 * Tracked equipment must be Individual; Bulk is only for true consumables / supplies.
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

    public static function isConsumableCategory(?int $categoryId = null, ?string $categoryName = null): bool
    {
        $name = strtolower(trim((string) $categoryName));
        if ($name === '' && $categoryId && $categoryId > 0 && Schema::hasTable('equipment_categories_table')) {
            $name = strtolower(trim((string) DB::table('equipment_categories_table')
                ->where('equipment_category_id', $categoryId)
                ->value('equipment_category_name')));
        }

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

    /** RR-linked equipment (non-consumable) must stock as Individual units. */
    public static function requiresIndividualForCategory(?int $categoryId = null, ?string $categoryName = null): bool
    {
        return ! self::isConsumableCategory($categoryId, $categoryName);
    }

    /**
     * @return array{Bulk: bool, Individual: bool} allowed modes for an RR line given its category
     */
    public static function allowedForRrCategory(?int $categoryId = null, ?string $categoryName = null): array
    {
        if (self::isConsumableCategory($categoryId, $categoryName)) {
            return [self::BULK => true, self::INDIVIDUAL => true];
        }

        return [self::BULK => false, self::INDIVIDUAL => true];
    }
}
