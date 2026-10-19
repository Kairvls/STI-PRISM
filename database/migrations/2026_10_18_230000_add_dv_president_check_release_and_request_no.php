<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('disbursement_vouchers_table')) {
            Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_request_no')) {
                    $table->string('dv_request_no', 32)->nullable()->after('dv_form_number');
                    $table->index('dv_request_no');
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_president_submitted_at')) {
                    $table->timestamp('dv_president_submitted_at')->nullable();
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_president_submitted_by')) {
                    $table->unsignedBigInteger('dv_president_submitted_by')->nullable();
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_president_approved_at')) {
                    $table->timestamp('dv_president_approved_at')->nullable();
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_president_approved_by')) {
                    $table->unsignedBigInteger('dv_president_approved_by')->nullable();
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_president_signed_name')) {
                    $table->string('dv_president_signed_name', 255)->nullable();
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_president_revision_reason')) {
                    $table->text('dv_president_revision_reason')->nullable();
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_check_released_at')) {
                    $table->timestamp('dv_check_released_at')->nullable();
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_check_released_by')) {
                    $table->unsignedBigInteger('dv_check_released_by')->nullable();
                }
            });
        }

        $requestNoTables = [
            'requisition_issue_slip_table' => 'ris_request_no',
            'authority_to_purchase_table' => 'authority_purchase_request_no',
            'request_check_table' => 'request_check_request_no',
            'receiving_reports_table' => 'receiving_report_request_no',
            'liquidation_reports_table' => 'liquidation_report_request_no',
        ];

        foreach ($requestNoTables as $tableName => $column) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, $column)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->string($column, 32)->nullable();
                $table->index($column);
            });
        }

        if (Schema::hasTable('request_check_table')
            && ! Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            Schema::table('request_check_table', function (Blueprint $table) {
                $table->timestamp('request_check_funds_released_at')->nullable();
                $table->unsignedBigInteger('request_check_funds_released_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('disbursement_vouchers_table')) {
            Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
                foreach ([
                    'dv_request_no',
                    'dv_president_submitted_at',
                    'dv_president_submitted_by',
                    'dv_president_approved_at',
                    'dv_president_approved_by',
                    'dv_president_signed_name',
                    'dv_president_revision_reason',
                    'dv_check_released_at',
                    'dv_check_released_by',
                ] as $col) {
                    if (Schema::hasColumn('disbursement_vouchers_table', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        $requestNoTables = [
            'requisition_issue_slip_table' => 'ris_request_no',
            'authority_to_purchase_table' => 'authority_purchase_request_no',
            'request_check_table' => 'request_check_request_no',
            'receiving_reports_table' => 'receiving_report_request_no',
            'liquidation_reports_table' => 'liquidation_report_request_no',
        ];

        foreach ($requestNoTables as $tableName => $column) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
