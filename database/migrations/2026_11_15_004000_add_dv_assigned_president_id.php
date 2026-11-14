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
            if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_president_id')) {
                $table->unsignedBigInteger('dv_assigned_president_id')->nullable()->after('dv_assigned_reviewer_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return;
        }

        Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_president_id')) {
                $table->dropColumn('dv_assigned_president_id');
            }
        });
    }
};
