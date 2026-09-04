<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * Actionable admin workload counts (RIS review / cosign / amendments).
     *
     * @return array{
     *     pendingRis: int,
     *     awaitingCosign: int,
     *     amendRis: int,
     *     attentionTotal: int
     * }
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $pendingRis = 0;
        $awaitingCosign = 0;
        $amendRis = 0;

        if (Schema::hasTable('requisition_issue_slip_table')) {
            $base = DB::table('requisition_issue_slip_table')
                ->whereNotNull('ris_requested_by_date');

            $pendingRis = (int) (clone $base)
                ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending'])
                ->count();

            $amendRis = (int) (clone $base)
                ->whereIn('ris_status', ['Minor Revision', 'Rejected'])
                ->count();

            $acceptedForDecision = (int) (clone $base)
                ->where('ris_status', 'Accepted')
                ->count();

            // Sign RIS workload: Accepted (decision) + President-approved awaiting Issued by
            $awaitingIssuedBy = (int) DB::table('requisition_issue_slip_table')
                ->where(function ($q) {
                    $q->where('ris_status', 'Approved by the President')
                        ->orWhere(function ($legacy) {
                            $legacy->where('ris_status', 'Approved')
                                ->whereNotNull('ris_approved_by_signature')
                                ->where('ris_approved_by_signature', '!=', '')
                                ->where('ris_approved_by_signature', 'like', 'data:image%');
                        });
                })
                ->where(function ($unsigned) {
                    $unsigned->whereNull('ris_issued_by_signature')
                        ->orWhere('ris_issued_by_signature', '');
                })
                ->count();

            $awaitingCosign = $acceptedForDecision + $awaitingIssuedBy;
        }

        self::$cached = [
            'pendingRis' => $pendingRis,
            'awaitingCosign' => $awaitingCosign,
            'amendRis' => $amendRis,
            'attentionTotal' => $pendingRis + $awaitingCosign + $amendRis,
        ];

        return self::$cached;
    }
}
