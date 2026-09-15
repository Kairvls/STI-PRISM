<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receiving_report_items_table')) {
            return;
        }

        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_ordered_qty')) {
                $table->unsignedInteger('receiving_report_item_ordered_qty')->nullable()->after('receiving_report_item_quantity');
            }
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_condition')) {
                $table->string('receiving_report_item_condition', 20)->nullable()->after('receiving_report_item_ordered_qty');
            }
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_condition_remarks')) {
                $table->string('receiving_report_item_condition_remarks', 500)->nullable()->after('receiving_report_item_condition');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('receiving_report_items_table')) {
            return;
        }

        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            foreach ([
                'receiving_report_item_condition_remarks',
                'receiving_report_item_condition',
                'receiving_report_item_ordered_qty',
            ] as $col) {
                if (Schema::hasColumn('receiving_report_items_table', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
