<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receiving_reports_table')) {
            return;
        }

        if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->unsignedBigInteger('receiving_report_atp_id')->nullable()->index('rr_atp_idx');
            });
        }

        if (
            Schema::hasTable('request_check_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
        ) {
            DB::table('receiving_reports_table')
                ->join('request_check_table', 'request_check_table.request_check_id', '=', 'receiving_reports_table.receiving_report_request_check_id')
                ->whereNull('receiving_reports_table.receiving_report_atp_id')
                ->whereNotNull('request_check_table.request_check_authority_purchase_id')
                ->update([
                    'receiving_reports_table.receiving_report_atp_id' => DB::raw('request_check_table.request_check_authority_purchase_id'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receiving_reports_table') && Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->dropIndex('rr_atp_idx');
                $table->dropColumn('receiving_report_atp_id');
            });
        }
    }
};
