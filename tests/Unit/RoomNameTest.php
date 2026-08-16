<?php

namespace Tests\Unit;

use App\Support\RoomName;
use PHPUnit\Framework\TestCase;

class RoomNameTest extends TestCase
{
    public function test_computer_lab_aliases_match(): void
    {
        $this->assertTrue(RoomName::matches('ComLab 1', 'Computer Laboratory 1'));
        $this->assertTrue(RoomName::matches('Computer Lab 1', 'CL1'));
        $this->assertTrue(RoomName::matches('Com Lab 01', 'Computer Laboratory 1'));
    }

    public function test_typo_lab_names_match(): void
    {
        $this->assertTrue(RoomName::matches('Computer Laborator 2', 'Computer Laboratory 2'));
    }

    public function test_different_room_numbers_do_not_match(): void
    {
        $this->assertFalse(RoomName::matches('ComLab 1', 'ComLab 2'));
        $this->assertFalse(RoomName::matches('Lecture Room 3', 'Lecture Room 4'));
    }

    public function test_unrelated_rooms_do_not_match(): void
    {
        $this->assertFalse(RoomName::matches('Faculty Room', 'School Clinic'));
    }

    public function test_duplicate_group_count(): void
    {
        $this->assertSame(0, RoomName::duplicateGroupCount(['ComLab 1', 'ComLab 2']));
        $this->assertSame(1, RoomName::duplicateGroupCount([
            'ComLab 1',
            'Computer Laboratory 1',
            'Lecture Room 2',
        ]));
    }
}
