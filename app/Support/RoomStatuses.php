<?php

namespace App\Support;

/**
 * Canonical room_status values on rooms_table.
 */
class RoomStatuses
{
    public const NORMAL = 'Normal';

    public const UNDER_MAINTENANCE = 'Under Maintenance';

    public const CRITICAL = 'Critical';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::NORMAL,
            self::UNDER_MAINTENANCE,
            self::CRITICAL,
        ];
    }

    /**
     * Map legacy / loose labels onto the three allowed statuses.
     */
    public static function normalize(?string $status): string
    {
        $value = trim((string) $status);

        return match (true) {
            $value === '' => self::NORMAL,
            strcasecmp($value, self::CRITICAL) === 0 => self::CRITICAL,
            strcasecmp($value, self::UNDER_MAINTENANCE) === 0,
            strcasecmp($value, 'Maintenance Needed') === 0 => self::UNDER_MAINTENANCE,
            default => self::NORMAL,
        };
    }
}
