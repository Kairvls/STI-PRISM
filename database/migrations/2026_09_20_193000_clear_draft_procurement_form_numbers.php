<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drafts should not hold official form numbers — assign only on submit.
     */
    public function up(): void
    {
        if (Schema::hasTable('authority_to_purchase_table')
            && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_form_number')) {
            $query = DB::table('authority_to_purchase_table')->whereNotNull('authority_purchase_form_number');

            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_submitted_at')) {
                $query->whereNull('authority_purchase_submitted_at');
            } elseif (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_status')) {
                $query->where('authority_purchase_status', 'Draft');
            }

            $update = ['authority_purchase_form_number' => null];
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_updated_at')) {
                $update['authority_purchase_updated_at'] = now();
            }
            $query->update($update);
        }

        if (Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', 'request_check_form_number')
            && Schema::hasColumn('request_check_table', 'request_check_status')) {
            $update = ['request_check_form_number' => null];
            if (Schema::hasColumn('request_check_table', 'request_check_updated_at')) {
                $update['request_check_updated_at'] = now();
            }
            DB::table('request_check_table')
                ->where('request_check_status', 'Draft')
                ->whereNotNull('request_check_form_number')
                ->update($update);
        }

        if (Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_status')) {
            $update = ['receiving_report_form_number' => null];
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_updated_at')) {
                $update['receiving_report_updated_at'] = now();
            }
            DB::table('receiving_reports_table')
                ->where('receiving_report_status', 'Draft')
                ->whereNotNull('receiving_report_form_number')
                ->update($update);
        }

        if (Schema::hasTable('liquidation_reports_table')
            && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_form_number')
            && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_status')) {
            $update = ['liquidation_report_form_number' => null];
            if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_updated_at')) {
                $update['liquidation_report_updated_at'] = now();
            }
            DB::table('liquidation_reports_table')
                ->where('liquidation_report_status', 'Draft')
                ->whereNotNull('liquidation_report_form_number')
                ->update($update);
        }
    }

    public function down(): void
    {
        // Irreversible: cleared draft form numbers are not restored.
    }
};
