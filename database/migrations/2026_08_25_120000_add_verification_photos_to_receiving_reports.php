<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return;
        }

        if (!Schema::hasColumn('receiving_reports_table', 'receiving_report_verification_photos')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->json('receiving_report_verification_photos')->nullable()->after('receiving_report_second_count_signature');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return;
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_verification_photos')) {
            Schema::table('receiving_reports_table', function (Blueprint $table) {
                $table->dropColumn('receiving_report_verification_photos');
            });
        }
    }
};
