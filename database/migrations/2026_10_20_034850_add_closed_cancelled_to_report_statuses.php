<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finalize report statuses:
     * Pending, Processing, For Replacement, Resolved, Rejected, Closed / Cancelled
     */
    public function up(): void
    {
        $enum = "'Pending','Processing','For Replacement','Resolved','Rejected','Closed / Cancelled'";

        if (Schema::hasTable('reports_table')
            && Schema::hasColumn('reports_table', 'report_current_status')
        ) {
            DB::statement(
                "ALTER TABLE reports_table MODIFY report_current_status
                ENUM({$enum}) NOT NULL DEFAULT 'Pending'"
            );
        }

        if (Schema::hasTable('report_items_table')
            && Schema::hasColumn('report_items_table', 'report_item_status')
        ) {
            DB::statement(
                "ALTER TABLE report_items_table MODIFY report_item_status
                ENUM({$enum}) NOT NULL DEFAULT 'Pending'"
            );
        }
    }

    public function down(): void
    {
        $enum = "'Pending','Processing','Resolved','For Replacement','Rejected'";

        if (Schema::hasTable('reports_table')
            && Schema::hasColumn('reports_table', 'report_current_status')
        ) {
            DB::table('reports_table')
                ->where('report_current_status', 'Closed / Cancelled')
                ->update(['report_current_status' => 'Rejected']);

            DB::statement(
                "ALTER TABLE reports_table MODIFY report_current_status
                ENUM({$enum}) NOT NULL DEFAULT 'Pending'"
            );
        }

        if (Schema::hasTable('report_items_table')
            && Schema::hasColumn('report_items_table', 'report_item_status')
        ) {
            DB::table('report_items_table')
                ->where('report_item_status', 'Closed / Cancelled')
                ->update(['report_item_status' => 'Rejected']);

            DB::statement(
                "ALTER TABLE report_items_table MODIFY report_item_status
                ENUM({$enum}) NOT NULL DEFAULT 'Pending'"
            );
        }
    }
};
