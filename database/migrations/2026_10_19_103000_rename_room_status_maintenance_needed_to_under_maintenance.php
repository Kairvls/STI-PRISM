<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms_table') || ! Schema::hasColumn('rooms_table', 'room_status')) {
            return;
        }

        // Expand enum so both labels can coexist during the rename.
        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Normal','Maintenance Needed','Under Maintenance','Critical') NOT NULL DEFAULT 'Normal'");

        DB::table('rooms_table')
            ->where('room_status', 'Maintenance Needed')
            ->update(['room_status' => 'Under Maintenance']);

        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Normal','Under Maintenance','Critical') NOT NULL DEFAULT 'Normal'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms_table') || ! Schema::hasColumn('rooms_table', 'room_status')) {
            return;
        }

        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Normal','Maintenance Needed','Under Maintenance','Critical') NOT NULL DEFAULT 'Normal'");

        DB::table('rooms_table')
            ->where('room_status', 'Under Maintenance')
            ->update(['room_status' => 'Maintenance Needed']);

        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Normal','Maintenance Needed','Critical') NOT NULL DEFAULT 'Normal'");
    }
};
