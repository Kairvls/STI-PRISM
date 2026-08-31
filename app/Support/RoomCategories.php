<?php

namespace App\Support;

class RoomCategories
{
    public const STORAGE_TYPE = 'Storage / Stockroom';

    public static function options(): array
    {
        return [
            'Lecture Room' => 'Lecture Room',
            'Computer Laboratory' => 'Computer Laboratory',
            'HM Room' => 'HM Room / Bar',
            'Hotel Room Simulation' => 'Hotel Room Simulation',
            'Faculty Room' => 'Faculty Room',
            'Office' => 'Office',
            'Library' => 'Library',
            'School Clinic' => 'School Clinic',
            self::STORAGE_TYPE => 'Storage / Stockroom',
        ];
    }

    public static function values(): array
    {
        return array_keys(self::options());
    }

    public static function isStorageType(?string $roomType): bool
    {
        return trim((string) $roomType) === self::STORAGE_TYPE;
    }
}
