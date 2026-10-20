<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_unit_photos')) {
                $table->json('receiving_report_item_unit_photos')->nullable()->after('receiving_report_item_photo_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_unit_photos')) {
                $table->dropColumn('receiving_report_item_unit_photos');
            }
        });
    }
};
