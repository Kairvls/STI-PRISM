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
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_serial_number')) {
                $table->text('receiving_report_item_serial_number')->nullable()->after('receiving_report_item_damaged_qty');
            }
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_brand')) {
                $table->string('receiving_report_item_brand', 150)->nullable()->after('receiving_report_item_article');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('receiving_report_items_table')) {
            return;
        }

        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_serial_number')) {
                $table->dropColumn('receiving_report_item_serial_number');
            }
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_brand')) {
                $table->dropColumn('receiving_report_item_brand');
            }
        });
    }
};
