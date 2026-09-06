<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return;
        }

        // Drawn signature data-URLs exceed MySQL TEXT (64KB); widen to LONGTEXT.
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_signature')) {
            DB::statement('ALTER TABLE receiving_reports_table MODIFY receiving_report_received_by_signature LONGTEXT NULL');
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_second_count_signature')) {
            DB::statement('ALTER TABLE receiving_reports_table MODIFY receiving_report_second_count_signature LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return;
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_signature')) {
            DB::statement('ALTER TABLE receiving_reports_table MODIFY receiving_report_received_by_signature TEXT NULL');
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_second_count_signature')) {
            DB::statement('ALTER TABLE receiving_reports_table MODIFY receiving_report_second_count_signature TEXT NULL');
        }
    }
};
