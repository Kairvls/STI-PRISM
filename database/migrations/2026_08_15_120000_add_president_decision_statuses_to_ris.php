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

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->where(function ($q) {
                $q->whereNull('ris_approved_by_signature')
                    ->orWhere('ris_approved_by_signature', '');
            })
            ->update(['ris_status' => 'Forwarded to President']);

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->whereNotNull('ris_approved_by_signature')
            ->where('ris_approved_by_signature', '!=', '')
            ->update(['ris_status' => 'Approved by the President']);

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected by President')
            ->update(['ris_status' => 'Rejected by the President']);
    }

    public function down(): void
    {
        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Forwarded to President')
            ->update(['ris_status' => 'Approved']);

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved by the President')
            ->update(['ris_status' => 'Approved']);

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected by the President')
            ->update(['ris_status' => 'Rejected by President']);

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
};
