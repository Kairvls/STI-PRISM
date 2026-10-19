<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Official Receipt (OR) on Receiving Report:
 * check amount vs OR amount → change returned to cashier;
 * OR amount becomes the final settled amount on the linked DV.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receiving_reports_table')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_or_amount')) {
                    $table->decimal('receiving_report_or_amount', 12, 2)->nullable()->after('receiving_report_invoice_no');
                }
                if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_check_amount')) {
                    $table->decimal('receiving_report_check_amount', 12, 2)->nullable()->after('receiving_report_or_amount');
                }
                if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_change_amount')) {
                    $table->decimal('receiving_report_change_amount', 12, 2)->nullable()->after('receiving_report_check_amount');
                }
                if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_or_attachment_path')) {
                    $table->string('receiving_report_or_attachment_path', 500)->nullable()->after('receiving_report_change_amount');
                }
                if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_or_attachment_name')) {
                    $table->string('receiving_report_or_attachment_name', 255)->nullable()->after('receiving_report_or_attachment_path');
                }
                if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_or_attachment_mime')) {
                    $table->string('receiving_report_or_attachment_mime', 120)->nullable()->after('receiving_report_or_attachment_name');
                }
            });
        }

        if (Schema::hasTable('disbursement_vouchers_table')) {
            Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_or_amount')) {
                    $table->decimal('dv_or_amount', 12, 2)->nullable()->after('dv_amount');
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_change_amount')) {
                    $table->decimal('dv_change_amount', 12, 2)->nullable()->after('dv_or_amount');
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_or_synced_from_rr_id')) {
                    $table->unsignedBigInteger('dv_or_synced_from_rr_id')->nullable()->after('dv_change_amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receiving_reports_table')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                foreach ([
                    'receiving_report_or_amount',
                    'receiving_report_check_amount',
                    'receiving_report_change_amount',
                    'receiving_report_or_attachment_path',
                    'receiving_report_or_attachment_name',
                    'receiving_report_or_attachment_mime',
                ] as $column) {
                    if (Schema::hasColumn('receiving_reports_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('disbursement_vouchers_table')) {
            Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
                foreach (['dv_or_amount', 'dv_change_amount', 'dv_or_synced_from_rr_id'] as $column) {
                    if (Schema::hasColumn('disbursement_vouchers_table', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
