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
                if (! Schema::hasColumn('equipment_table', 'equipment_stocked_by')) {
                    $table->unsignedBigInteger('equipment_stocked_by')->nullable()->after('equipment_acquired_date');
                }
                if (! Schema::hasColumn('equipment_table', 'equipment_qr_issued_at')) {
                    $table->timestamp('equipment_qr_issued_at')->nullable()->after('equipment_qr_code');
                }
                if (! Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
                    $table->unsignedBigInteger('equipment_receiving_report_item_id')->nullable()->index();
                }
                if (! Schema::hasColumn('equipment_table', 'equipment_stock_lot_code')) {
                    $table->string('equipment_stock_lot_code', 80)->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('disposal_records_table')) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                if (! Schema::hasColumn('disposal_records_table', 'disposal_method')) {
                    $table->string('disposal_method', 80)->nullable()->after('disposal_reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('equipment_table')) {
            Schema::table('equipment_table', function (Blueprint $table) {
                foreach ([
                    'equipment_stocked_by',
                    'equipment_qr_issued_at',
                    'equipment_receiving_report_item_id',
                    'equipment_stock_lot_code',
                ] as $column) {
                    if (Schema::hasColumn('equipment_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (
            Schema::hasTable('disposal_records_table')
            && Schema::hasColumn('disposal_records_table', 'disposal_method')
        ) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                $table->dropColumn('disposal_method');
            });
        }
    }
};
