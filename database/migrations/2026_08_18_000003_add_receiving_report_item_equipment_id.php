<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receiving_report_items_table')) {
            return;
        }

        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (!Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_unit_price')) {
                $table->decimal('receiving_report_item_unit_price', 12, 2)->nullable()->after('receiving_report_item_article');
            }
            if (!Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_amount')) {
                $table->decimal('receiving_report_item_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
                $table->unsignedBigInteger('receiving_report_item_equipment_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('receiving_report_items_table')) {
            return;
        }

        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
                $table->dropColumn('receiving_report_item_equipment_id');
            }
        });
    }
};
