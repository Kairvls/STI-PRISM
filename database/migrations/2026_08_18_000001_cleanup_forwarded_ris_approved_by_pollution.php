<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repair RIS rows where Admin forward wrote status Approved
     * with a plain-text Admin name in Approved by (President field).
     */
    public function up(): void
    {
        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->where(function ($q) {
                $q->whereNull('ris_approved_by_signature')
                    ->orWhere('ris_approved_by_signature', '');
            })
            ->update([
                'ris_status' => 'Forwarded to President',
                'ris_approved_by_signature' => null,
                'ris_approved_by_date' => null,
            ]);

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->whereNotNull('ris_approved_by_signature')
            ->where('ris_approved_by_signature', '!=', '')
            ->where('ris_approved_by_signature', 'not like', 'data:image%')
            ->update([
                'ris_status' => 'Forwarded to President',
                'ris_approved_by_signature' => null,
                'ris_approved_by_date' => null,
            ]);

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected by President')
            ->update(['ris_status' => 'Rejected by the President']);
    }

    public function down(): void
    {
        // Irreversible data repair — cannot restore Admin names that were cleared.
    }
};
