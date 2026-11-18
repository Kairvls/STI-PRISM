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
                'President Revision',
                'Returned by the President',
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
    }

    public function down(): void
    {
        DB::table('requisition_issue_slip_table')
            ->whereIn('ris_status', ['President Revision', 'Returned by the President'])
            ->update(['ris_status' => 'Minor Revision']);

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
    }
};
