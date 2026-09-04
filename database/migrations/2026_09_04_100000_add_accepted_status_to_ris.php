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
                'Accepted',
                'Approved',
                'Forwarded to President',
                'Approved by the President',
                'Directly Approved',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
                'Archived',
                'Pending'
            ) NOT NULL DEFAULT 'Draft'
        ");

        try {
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
                    'Pending',
                    'Accepted',
                    'Forwarded to President',
                    'Minor Revision',
                    'Admin Approved'
                ) NOT NULL
            ");
        } catch (\Throwable $e) {
            // Keep going if logs enum cannot be altered.
        }
    }

    public function down(): void
    {
        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Accepted')
            ->update(['ris_status' => 'Submitted']);

        DB::statement("
            ALTER TABLE requisition_issue_slip_table
            MODIFY COLUMN ris_status ENUM(
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
                'Approved',
                'Forwarded to President',
                'Approved by the President',
                'Directly Approved',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
                'Archived',
                'Pending'
            ) NOT NULL DEFAULT 'Draft'
        ");
    }
};
