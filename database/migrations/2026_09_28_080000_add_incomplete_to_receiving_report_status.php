<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receiving_reports_table') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE receiving_reports_table
            MODIFY receiving_report_status ENUM(
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
                'Incomplete',
                'Completed',
                'Returned'
            ) NOT NULL DEFAULT 'Draft'
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('receiving_reports_table') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('receiving_reports_table')
            ->where('receiving_report_status', 'Incomplete')
            ->update(['receiving_report_status' => 'Completed']);

        DB::statement("
            ALTER TABLE receiving_reports_table
            MODIFY receiving_report_status ENUM(
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
                'Completed',
                'Returned'
            ) NOT NULL DEFAULT 'Draft'
        ");
    }
};
