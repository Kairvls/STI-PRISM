<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('equipment_maintenance_history_table')
            || ! Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_downtime_hours')
        ) {
            return;
        }

        // decimal(8,2) only holds 999999.99. Measured hours follow the
        // 7-digit + 2-decimal cap (9999999.99), which needs decimal(9,2).
        DB::statement(
            'ALTER TABLE equipment_maintenance_history_table MODIFY equipment_maintenance_downtime_hours DECIMAL(9,2) NULL'
        );
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('equipment_maintenance_history_table')
            || ! Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_downtime_hours')
        ) {
            return;
        }

        DB::statement(
            'ALTER TABLE equipment_maintenance_history_table MODIFY equipment_maintenance_downtime_hours DECIMAL(8,2) NULL'
        );
    }
};
