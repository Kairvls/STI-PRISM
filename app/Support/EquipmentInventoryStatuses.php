<?php

namespace App\Support;

/**
 * Canonical equipment inventory status values (equipment_inventory_status).
 */
class EquipmentInventoryStatuses
{
    public const ACTIVE = 'Active';

    public const BORROWED = 'Borrowed';

    public const UNDER_MAINTENANCE = 'Under Maintenance';

    public const FOR_REPLACEMENT = 'For Replacement';

    public const DISPOSED = 'Disposed';

    /** @var list<string> */
    public const ALL = [
        self::ACTIVE,
        self::BORROWED,
        self::UNDER_MAINTENANCE,
        self::FOR_REPLACEMENT,
        self::DISPOSED,
    ];

    public static function ruleIn(): string
    {
        return 'in:'.implode(',', self::ALL);
    }
}
