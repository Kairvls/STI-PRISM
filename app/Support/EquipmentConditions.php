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

    /**
     * Conditions that can be chosen when adding or editing equipment.
     * Disposed is set only by the Equipment Disposal module.
     *
     * @var list<string>
     */
    public const ASSIGNABLE = [
        self::GOOD,
        self::UNDER_MAINTENANCE,
        self::DEFECTIVE,
        self::DAMAGED,
    ];

    /**
     * Conditions that may be sent to Equipment Disposal.
     *
     * @var list<string>
     */
    public const DISPOSABLE = [
        self::DEFECTIVE,
        self::DAMAGED,
    ];

    /**
     * Conditions that cannot be borrowed. Under Maintenance is also an inventory status.
     *
     * @var list<string>
     */
    public const NOT_BORROWABLE = [
        self::UNDER_MAINTENANCE,
        self::DEFECTIVE,
        self::DAMAGED,
    ];

    public static function ruleIn(): string
    {
        return 'in:'.implode(',', self::ALL);
    }

    public static function assignableRule(): string
    {
        return 'in:'.implode(',', self::ASSIGNABLE);
    }

    public static function isDisposable(?string $condition): bool
    {
        return in_array((string) $condition, self::DISPOSABLE, true);
    }

    public static function blocksBorrowing(?string $condition): bool
    {
        return in_array((string) $condition, self::NOT_BORROWABLE, true);
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
