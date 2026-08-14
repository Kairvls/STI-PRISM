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
                'Rejected by President',
                'Archived',
                'Pending'
            ) NOT NULL DEFAULT 'Draft'
        ");
    }

    public function down(): void
    {
        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected by President')
            ->update(['ris_status' => 'Rejected']);

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
    }
};
