<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('authority_to_purchase_table')
            && !Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')
        ) {
            Schema::table('authority_to_purchase_table', function (Blueprint $table) {
                $table->longText('authority_purchase_received_by_signature')->nullable()
                    ->after('authority_purchase_received_by_name');
            });
        }

        if (Schema::hasTable('request_check_table')
            && !Schema::hasColumn('request_check_table', 'request_check_requested_by_signature')
        ) {
            Schema::table('request_check_table', function (Blueprint $table) {
                $table->longText('request_check_requested_by_signature')->nullable()
                    ->after('request_check_requested_by');
            });
        }

        if (Schema::hasTable('receiving_reports_table')
            && !Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_name')
        ) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->string('receiving_report_received_by_name', 255)->nullable()
                    ->after('receiving_report_delivery_date');
            });

            // Legacy rows stored the typed name in the signature column.
            DB::table('receiving_reports_table')
                ->whereNotNull('receiving_report_received_by_signature')
                ->where('receiving_report_received_by_signature', '!=', '')
                ->where('receiving_report_received_by_signature', 'not like', 'data:image/%')
                ->update([
                    'receiving_report_received_by_name' => DB::raw('receiving_report_received_by_signature'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('authority_to_purchase_table')
            && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')
        ) {
            Schema::table('authority_to_purchase_table', function (Blueprint $table) {
                $table->dropColumn('authority_purchase_received_by_signature');
            });
        }

        if (Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', 'request_check_requested_by_signature')
        ) {
            Schema::table('request_check_table', function (Blueprint $table) {
                $table->dropColumn('request_check_requested_by_signature');
            });
        }

        if (Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_name')
        ) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->dropColumn('receiving_report_received_by_name');
            });
        }
    }
};
