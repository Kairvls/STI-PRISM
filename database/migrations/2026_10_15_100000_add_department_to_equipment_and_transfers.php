<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_table', function (Blueprint $table) {
            if (! Schema::hasColumn('equipment_table', 'equipment_department_id')) {
                $table->unsignedBigInteger('equipment_department_id')
                    ->nullable()
                    ->after('equipment_room_id')
                    ->index('equipment_department_idx');
            }
        });

        Schema::table('equipment_transfer_history_table', function (Blueprint $table) {
            if (! Schema::hasColumn('equipment_transfer_history_table', 'to_department_id')) {
                $table->unsignedBigInteger('to_department_id')->nullable()->after('to_room_id');
            }
            if (! Schema::hasColumn('equipment_transfer_history_table', 'to_custodian_id')) {
                $table->unsignedBigInteger('to_custodian_id')->nullable()->after('to_department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_transfer_history_table', function (Blueprint $table) {
            foreach (['to_custodian_id', 'to_department_id'] as $column) {
                if (Schema::hasColumn('equipment_transfer_history_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('equipment_table', function (Blueprint $table) {
            if (Schema::hasColumn('equipment_table', 'equipment_department_id')) {
                $table->dropIndex('equipment_department_idx');
                $table->dropColumn('equipment_department_id');
            }
        });
    }
};
