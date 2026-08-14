<?php

namespace App\Support;

class RoomCategories
{
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
        ];
    }

    public static function values(): array
    {
        return array_keys(self::options());
    }
}
