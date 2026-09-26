<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipment_table')) {
            Schema::table('equipment_table', function (Blueprint $table) {
                if (! Schema::hasColumn('equipment_table', 'equipment_replaces_id')) {
                    $table->unsignedBigInteger('equipment_replaces_id')->nullable()->index();
                }
                if (! Schema::hasColumn('equipment_table', 'equipment_replaced_by_id')) {
                    $table->unsignedBigInteger('equipment_replaced_by_id')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('equipment_maintenance_history_table')) {
            Schema::table('equipment_maintenance_history_table', function (Blueprint $table) {
                if (! Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_parts_used')) {
                    $table->string('equipment_maintenance_parts_used', 500)->nullable();
                }
                if (! Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_repair_cost')) {
                    $table->decimal('equipment_maintenance_repair_cost', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_downtime_hours')) {
                    $table->decimal('equipment_maintenance_downtime_hours', 8, 2)->nullable();
                }
            });
        }

        if (Schema::hasTable('disposal_records_table')) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                if (! Schema::hasColumn('disposal_records_table', 'disposal_residual_value')) {
                    $table->decimal('disposal_residual_value', 12, 2)->nullable();
                }
            });
        }

        if (! Schema::hasTable('equipment_condition_history_table')) {
            Schema::create('equipment_condition_history_table', function (Blueprint $table) {
                $table->bigIncrements('condition_history_id');
                $table->unsignedBigInteger('equipment_id')->index();
                $table->string('condition_from', 50)->nullable();
                $table->string('condition_to', 50)->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->string('change_source', 80)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('equipment_condition_history_table')) {
            Schema::dropIfExists('equipment_condition_history_table');
        }

        if (Schema::hasTable('equipment_table')) {
            Schema::table('equipment_table', function (Blueprint $table) {
                foreach (['equipment_replaces_id', 'equipment_replaced_by_id'] as $column) {
                    if (Schema::hasColumn('equipment_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('equipment_maintenance_history_table')) {
            Schema::table('equipment_maintenance_history_table', function (Blueprint $table) {
                foreach ([
                    'equipment_maintenance_parts_used',
                    'equipment_maintenance_repair_cost',
                    'equipment_maintenance_downtime_hours',
                ] as $column) {
                    if (Schema::hasColumn('equipment_maintenance_history_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (
            Schema::hasTable('disposal_records_table')
            && Schema::hasColumn('disposal_records_table', 'disposal_residual_value')
        ) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                $table->dropColumn('disposal_residual_value');
            });
        }
    }
};
