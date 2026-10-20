<?php

namespace App\Support;

/**
 * Canonical equipment condition values (equipment_condition_status).
 */
class EquipmentConditions
{
    public const GOOD = 'Good';

    public const UNDER_MAINTENANCE = 'Under Maintenance';

    public const DEFECTIVE = 'Defective';

    public const DAMAGED = 'Damaged';

    public const DISPOSED = 'Disposed';

    /** @var list<string> */
    public const ALL = [
        self::GOOD,
        self::UNDER_MAINTENANCE,
        self::DEFECTIVE,
        self::DAMAGED,
        self::DISPOSED,
    ];

    public static function ruleIn(): string
    {
        return 'in:'.implode(',', self::ALL);
    }

    /**
     * Inventory status implied when saving a condition (unless set explicitly).
     */
    public static function syncInventoryStatus(string $condition): string
    {
        return match ($condition) {
            self::DISPOSED => EquipmentInventoryStatuses::DISPOSED,
            self::UNDER_MAINTENANCE => EquipmentInventoryStatuses::UNDER_MAINTENANCE,
            self::DEFECTIVE => EquipmentInventoryStatuses::FOR_REPLACEMENT,
            default => EquipmentInventoryStatuses::ACTIVE,
        };
    }
}
