<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Defective to equipment condition enums.
     * Final set: Good, Under Maintenance, Defective, Damaged, Disposed.
     */
    public function up(): void
    {
        if (Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_condition_status')
        ) {
            DB::statement(
                "ALTER TABLE equipment_table MODIFY equipment_condition_status
                ENUM('Good','Under Maintenance','Defective','Damaged','Disposed')
                NOT NULL DEFAULT 'Good'"
            );
        }

        if (Schema::hasTable('workstation_template_slots_table')
            && Schema::hasColumn('workstation_template_slots_table', 'workstation_template_slot_default_status')
        ) {
            DB::statement(
                "ALTER TABLE workstation_template_slots_table MODIFY workstation_template_slot_default_status
                ENUM('Good','Under Maintenance','Defective','Damaged','Disposed')
                NULL DEFAULT NULL"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_condition_status')
        ) {
            DB::table('equipment_table')
                ->where('equipment_condition_status', 'Defective')
                ->update(['equipment_condition_status' => 'Damaged']);

            DB::statement(
                "ALTER TABLE equipment_table MODIFY equipment_condition_status
                ENUM('Good','Damaged','Under Maintenance','Disposed')
                NOT NULL DEFAULT 'Good'"
            );
        }

        if (Schema::hasTable('workstation_template_slots_table')
            && Schema::hasColumn('workstation_template_slots_table', 'workstation_template_slot_default_status')
        ) {
            DB::table('workstation_template_slots_table')
                ->where('workstation_template_slot_default_status', 'Defective')
                ->update(['workstation_template_slot_default_status' => 'Damaged']);

            DB::statement(
                "ALTER TABLE workstation_template_slots_table MODIFY workstation_template_slot_default_status
                ENUM('Good','Damaged','Under Maintenance','Disposed')
                NULL DEFAULT NULL"
            );
        }
    }
};
