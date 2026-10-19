<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return;
        }

        Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_date')) {
                $table->date('dv_date')->nullable()->after('dv_form_number');
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_particulars')) {
                $table->text('dv_particulars')->nullable()->after('dv_payee');
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_account_lines')) {
                $table->json('dv_account_lines')->nullable()->after('dv_amount');
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_prepared_by_name')) {
                $table->string('dv_prepared_by_name', 255)->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_checked_by_name')) {
                $table->string('dv_checked_by_name', 255)->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_cdv_number')) {
                $table->string('dv_cdv_number', 64)->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_check_number')) {
                $table->string('dv_check_number', 64)->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_received_by_name')) {
                $table->string('dv_received_by_name', 255)->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_received_date')) {
                $table->date('dv_received_date')->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_admin_signed_name')) {
                $table->string('dv_admin_signed_name', 255)->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_admin_signature')) {
                $table->longText('dv_admin_signature')->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_accountant_signed_name')) {
                $table->string('dv_accountant_signed_name', 255)->nullable();
            }
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_accountant_signature')) {
                $table->longText('dv_accountant_signature')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return;
        }

        Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
            foreach ([
                'dv_date',
                'dv_particulars',
                'dv_account_lines',
                'dv_prepared_by_name',
                'dv_checked_by_name',
                'dv_cdv_number',
                'dv_check_number',
                'dv_received_by_name',
                'dv_received_date',
                'dv_admin_signed_name',
                'dv_admin_signature',
                'dv_accountant_signed_name',
                'dv_accountant_signature',
            ] as $col) {
                if (Schema::hasColumn('disbursement_vouchers_table', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
