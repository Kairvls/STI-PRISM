<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_table')) {
            return;
        }

        Schema::table('equipment_table', function (Blueprint $table) {
            if (! Schema::hasColumn('equipment_table', 'equipment_acquisition_source')) {
                $table->string('equipment_acquisition_source', 40)->nullable()->after('equipment_supplier_id');
            }
            if (! Schema::hasColumn('equipment_table', 'equipment_supplier_name')) {
                $table->string('equipment_supplier_name', 255)->nullable()->after('equipment_acquisition_source');
            }
            if (! Schema::hasColumn('equipment_table', 'equipment_reference_number')) {
                $table->string('equipment_reference_number', 120)->nullable()->after('equipment_supplier_name');
            }
            if (! Schema::hasColumn('equipment_table', 'equipment_acquisition_notes')) {
                $table->string('equipment_acquisition_notes', 500)->nullable()->after('equipment_reference_number');
            }
        });

        if (Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
            DB::table('equipment_table')
                ->whereNotNull('equipment_receiving_report_item_id')
                ->where('equipment_receiving_report_item_id', '>', 0)
                ->whereNull('equipment_acquisition_source')
                ->update(['equipment_acquisition_source' => 'procurement']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipment_table')) {
            return;
        }

        Schema::table('equipment_table', function (Blueprint $table) {
            foreach ([
                'equipment_acquisition_source',
                'equipment_supplier_name',
                'equipment_reference_number',
                'equipment_acquisition_notes',
            ] as $column) {
                if (Schema::hasColumn('equipment_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
