<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE requisition_issue_slip_table
            MODIFY COLUMN ris_status ENUM(
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
                'Approved',
                'Directly Approved',
                'Rejected',
                'Archived',
                'Pending'
            ) NOT NULL DEFAULT 'Draft'
        ");

        DB::statement("
            ALTER TABLE approval_logs_table
            MODIFY COLUMN approval_log_approval_status ENUM(
                'Approved',
                'Rejected',
                'Directly Approved',
                'Co-signed',
                'Submitted',
                'Under Review',
                'Resubmitted',
                'Pending'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Directly Approved')
            ->update(['ris_status' => 'Approved']);

        DB::statement("
            ALTER TABLE requisition_issue_slip_table
            MODIFY COLUMN ris_status ENUM(
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
                'Approved',
                'Rejected',
                'Archived',
                'Pending'
            ) NOT NULL DEFAULT 'Draft'
        ");

        DB::statement("
            ALTER TABLE approval_logs_table
            MODIFY COLUMN approval_log_approval_status ENUM(
                'Approved',
                'Rejected'
            ) NOT NULL
        ");
    }
};
