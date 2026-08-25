<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receiving_reports_table')
            && !Schema::hasColumn('receiving_reports_table', 'receiving_report_created_by')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->bigInteger('receiving_report_created_by')->nullable()->after('receiving_report_status');
            });
        }

        if (Schema::hasTable('liquidation_reports_table')
            && !Schema::hasColumn('liquidation_reports_table', 'liquidation_report_created_by')) {
            Schema::table('liquidation_reports_table', function (Blueprint $table) {
                $table->bigInteger('liquidation_report_created_by')->nullable()->after('liquidation_report_status');
            });
        }

        // Backfill from submitted_by where available.
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_created_by')) {
            DB::table('receiving_reports_table')
                ->whereNull('receiving_report_created_by')
                ->whereNotNull('receiving_report_submitted_by')
                ->update([
                    'receiving_report_created_by' => DB::raw('receiving_report_submitted_by'),
                ]);
        }

        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_created_by')) {
            DB::table('liquidation_reports_table')
                ->whereNull('liquidation_report_created_by')
                ->whereNotNull('liquidation_report_submitted_by')
                ->update([
                    'liquidation_report_created_by' => DB::raw('liquidation_report_submitted_by'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_created_by')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->dropColumn('receiving_report_created_by');
            });
        }

        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_created_by')) {
            Schema::table('liquidation_reports_table', function (Blueprint $table) {
                $table->dropColumn('liquidation_report_created_by');
            });
        }
    }
};
