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

        // Expand enum so old and new labels can coexist during the rename.
        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Normal','Under Maintenance','Critical','Available','Unavailable') NOT NULL DEFAULT 'Normal'");

        DB::table('rooms_table')
            ->where('room_status', 'Normal')
            ->update(['room_status' => 'Available']);

        DB::table('rooms_table')
            ->whereIn('room_status', ['Under Maintenance', 'Critical'])
            ->update(['room_status' => 'Unavailable']);

        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Available','Unavailable') NOT NULL DEFAULT 'Available'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('rooms_table') || ! Schema::hasColumn('rooms_table', 'room_status')) {
            return;
        }

        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Normal','Under Maintenance','Critical','Available','Unavailable') NOT NULL DEFAULT 'Available'");

        DB::table('rooms_table')
            ->where('room_status', 'Available')
            ->update(['room_status' => 'Normal']);

        DB::table('rooms_table')
            ->where('room_status', 'Unavailable')
            ->update(['room_status' => 'Under Maintenance']);

        DB::statement("ALTER TABLE rooms_table MODIFY room_status ENUM('Normal','Under Maintenance','Critical') NOT NULL DEFAULT 'Normal'");
    }
};
