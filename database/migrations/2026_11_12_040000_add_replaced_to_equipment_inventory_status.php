<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Replaced to equipment inventory status enum.
     * Used after a computer-set (or similar) swap retires the old unit.
     */
    public function up(): void
    {
        if (! Schema::hasTable('equipment_table')
            || ! Schema::hasColumn('equipment_table', 'equipment_inventory_status')
        ) {
            return;
        }

        DB::statement(
            "ALTER TABLE equipment_table MODIFY equipment_inventory_status
            ENUM('Active','Borrowed','Under Maintenance','For Replacement','Replaced','Disposed')
            DEFAULT 'Active'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipment_table')
            || ! Schema::hasColumn('equipment_table', 'equipment_inventory_status')
        ) {
            return;
        }

        DB::table('equipment_table')
            ->where('equipment_inventory_status', 'Replaced')
            ->update(['equipment_inventory_status' => 'For Replacement']);

        DB::statement(
            "ALTER TABLE equipment_table MODIFY equipment_inventory_status
            ENUM('Active','Borrowed','Under Maintenance','For Replacement','Disposed')
            DEFAULT 'Active'"
        );
    }
};
