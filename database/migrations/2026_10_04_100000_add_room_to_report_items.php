<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_items_table') || Schema::hasColumn('report_items_table', 'report_item_room_id')) {
            return;
        }

        // A ticket can now hold equipment from several rooms; each item remembers where it was reported.
        Schema::table('report_items_table', function (Blueprint $table) {
            $table->unsignedBigInteger('report_item_room_id')->nullable()->after('report_item_unlisted_equipment_name');
            $table->index('report_item_room_id');
        });

        DB::statement(
            'UPDATE report_items_table AS i
             INNER JOIN reports_table AS r ON r.report_id = i.report_id
             SET i.report_item_room_id = r.report_room_id
             WHERE i.report_item_room_id IS NULL'
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('report_items_table') && Schema::hasColumn('report_items_table', 'report_item_room_id')) {
            Schema::table('report_items_table', function (Blueprint $table) {
                $table->dropIndex(['report_item_room_id']);
                $table->dropColumn('report_item_room_id');
            });
        }
    }
};
