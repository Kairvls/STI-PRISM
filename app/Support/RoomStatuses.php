<?php

namespace App\Support;

/**
 * Canonical room_status values on rooms_table.
 */
class RoomStatuses
{
    public const AVAILABLE = 'Available';

    public const UNAVAILABLE = 'Unavailable';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::AVAILABLE,
            self::UNAVAILABLE,
        ];
    }

    /**
     * Map legacy / loose labels onto the allowed statuses.
     */
    public static function normalize(?string $status): string
    {
        $value = trim((string) $status);

        return match (true) {
            $value === '' => self::AVAILABLE,
            strcasecmp($value, self::UNAVAILABLE) === 0,
            strcasecmp($value, 'Critical') === 0,
            strcasecmp($value, 'Under Maintenance') === 0,
            strcasecmp($value, 'Maintenance Needed') === 0 => self::UNAVAILABLE,
            strcasecmp($value, self::AVAILABLE) === 0,
            strcasecmp($value, 'Normal') === 0 => self::AVAILABLE,
            default => self::AVAILABLE,
        };
    }

    /**
     * DB values that count as the given canonical status (includes legacy rows).
     *
     * @return list<string>
     */
    public static function storedValuesFor(string $status): array
    {
        $normalized = self::normalize($status);

        return match ($normalized) {
            self::UNAVAILABLE => [
                self::UNAVAILABLE,
                'Critical',
                'Under Maintenance',
                'Maintenance Needed',
            ],
            default => [
                self::AVAILABLE,
                'Normal',
            ],
        };
    }
}
