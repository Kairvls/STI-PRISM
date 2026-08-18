<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 2026_08_15_120000 moved every Approved row with any signature to
     * "Approved by the President". Admin-forward plain-text names are not
     * presidential signatures unless a President approval log exists.
     */
    public function up(): void
    {
        $noPresidentLog = function ($query) {
            $query->select(DB::raw(1))
                ->from('approval_logs_table')
                ->whereColumn('approval_logs_table.approval_log_reference_id', 'requisition_issue_slip_table.ris_id')
                ->where('approval_logs_table.approval_log_reference_type', 'RIS')
                ->where('approval_logs_table.approval_log_level', 'President')
                ->where('approval_logs_table.approval_log_approval_status', 'Approved');
        };

        $plainOrEmptyApprovedBy = function ($q) {
            $q->whereNull('ris_approved_by_signature')
                ->orWhere('ris_approved_by_signature', '')
                ->orWhere('ris_approved_by_signature', 'not like', 'data:image%');
        };

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved by the President')
            ->where($plainOrEmptyApprovedBy)
            ->where(function ($q) {
                $q->whereNull('ris_issued_by_signature')
                    ->orWhere('ris_issued_by_signature', '');
            })
            ->whereNotExists($noPresidentLog)
            ->update([
                'ris_status' => 'Forwarded to President',
                'ris_approved_by_signature' => null,
                'ris_approved_by_date' => null,
            ]);

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved by the President')
            ->where($plainOrEmptyApprovedBy)
            ->whereNotNull('ris_issued_by_signature')
            ->where('ris_issued_by_signature', '!=', '')
            ->whereNotExists($noPresidentLog)
            ->update([
                'ris_status' => 'Directly Approved',
                'ris_approved_by_signature' => null,
                'ris_approved_by_date' => null,
            ]);
    }

    public function down(): void
    {
        // Irreversible data repair.
    }
};
