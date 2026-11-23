<?php

namespace App\Support;

/**
 * Canonical equipment inventory status values (equipment_inventory_status).
 */
class EquipmentInventoryStatuses
{
    public const ACTIVE = 'Active';

    /** Inventory-list label for Active: still in stock, not deployed. */
    public const IN_STOCK_LABEL = 'In Stock';

    public const BORROWED = 'Borrowed';

    public const UNDER_MAINTENANCE = 'Under Maintenance';

    public const FOR_REPLACEMENT = 'For Replacement';

    /** Swapped out by a newer unit; awaiting repair decision or disposal. */
    public const REPLACED = 'Replaced';

    /** Sent to Equipment Disposal; still restorable until finalized. */
    public const PENDING_DISPOSAL = 'Pending Disposal';

    public const DISPOSED = 'Disposed';

    /** @var list<string> */
    public const ALL = [
        self::ACTIVE,
        self::BORROWED,
        self::UNDER_MAINTENANCE,
        self::FOR_REPLACEMENT,
        self::REPLACED,
        self::PENDING_DISPOSAL,
        self::DISPOSED,
    ];

    /**
     * Statuses that can be chosen when adding or editing equipment.
     * Loans, replacement, and disposal are set by their own records.
     *
     * @var list<string>
     */
    public const ASSIGNABLE = [
        self::ACTIVE,
        self::UNDER_MAINTENANCE,
    ];

    /** @var list<string> */
    public const MANUAL = [
        self::ACTIVE,
        self::UNDER_MAINTENANCE,
    ];

    public static function ruleIn(): string
    {
        return 'in:'.implode(',', self::ALL);
    }

    public static function assignableRule(): string
    {
        return 'in:'.implode(',', self::ASSIGNABLE);
    }

    /**
     * Label for the inventory list. Active stock is shown as in stock;
     * deployed equipment keeps the stored Active label.
     */
    public static function display(?string $status, bool $inInventory = false): string
    {
        $status = (string) $status;

        if ($inInventory && $status === self::ACTIVE) {
            return self::IN_STOCK_LABEL;
        }

        return $status;
    }
}
