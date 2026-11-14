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
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_check_collected_at')) {
                    $table->timestamp('dv_check_collected_at')->nullable()->after('dv_check_released_by');
                }
                if (! Schema::hasColumn('disbursement_vouchers_table', 'dv_check_collected_by')) {
                    $table->unsignedBigInteger('dv_check_collected_by')->nullable()->after('dv_check_collected_at');
                }
            });
        }

        if (Schema::hasTable('request_check_table')) {
            Schema::table('request_check_table', function (Blueprint $table) {
                if (! Schema::hasColumn('request_check_table', 'request_check_funds_collected_at')) {
                    $after = Schema::hasColumn('request_check_table', 'request_check_funds_released_by')
                        ? 'request_check_funds_released_by'
                        : (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')
                            ? 'request_check_funds_released_at'
                            : null);
                    if ($after) {
                        $table->timestamp('request_check_funds_collected_at')->nullable()->after($after);
                    } else {
                        $table->timestamp('request_check_funds_collected_at')->nullable();
                    }
                }
                if (! Schema::hasColumn('request_check_table', 'request_check_funds_collected_by')) {
                    $after = Schema::hasColumn('request_check_table', 'request_check_funds_collected_at')
                        ? 'request_check_funds_collected_at'
                        : null;
                    if ($after) {
                        $table->unsignedBigInteger('request_check_funds_collected_by')->nullable()->after($after);
                    } else {
                        $table->unsignedBigInteger('request_check_funds_collected_by')->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('disbursement_vouchers_table')) {
            Schema::table('disbursement_vouchers_table', function (Blueprint $table) {
                foreach (['dv_check_collected_by', 'dv_check_collected_at'] as $col) {
                    if (Schema::hasColumn('disbursement_vouchers_table', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('request_check_table')) {
            Schema::table('request_check_table', function (Blueprint $table) {
                foreach (['request_check_funds_collected_by', 'request_check_funds_collected_at'] as $col) {
                    if (Schema::hasColumn('request_check_table', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
