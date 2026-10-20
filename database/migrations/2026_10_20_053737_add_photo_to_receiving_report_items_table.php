<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_photo_path')) {
                $table->string('receiving_report_item_photo_path', 500)->nullable()->after('receiving_report_item_damage_remarks');
            }
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_photo_name')) {
                $table->string('receiving_report_item_photo_name', 255)->nullable()->after('receiving_report_item_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            foreach (['receiving_report_item_photo_path', 'receiving_report_item_photo_name'] as $column) {
                if (Schema::hasColumn('receiving_report_items_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
