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
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_president_signature')) {
                $table->longText('dv_president_signature')->nullable()->after('dv_president_signed_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return;
        }

        Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_president_signature')) {
                $table->dropColumn('dv_president_signature');
            }
        });
    }
};
