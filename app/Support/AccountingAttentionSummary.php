<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    private const RFC_INCOMING = ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];

    private const LIQ_INCOMING = ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];

    /**
     * Actionable accounting workload counts (dashboard queue cards).
     *
     * @return array{
     *     atpPending: int,
     *     rfcPending: int,
     *     fundsAwaiting: int,
     *     liqPending: int,
     *     liqOverdue: int,
     *     attentionTotal: int
     * }
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $atpPending = 0;
        $rfcPending = 0;
        $fundsAwaiting = 0;
        $liqPending = 0;
        $liqOverdue = 0;

        if (Schema::hasTable('authority_to_purchase_table')) {
            $atpPending = (int) DB::table('authority_to_purchase_table')
                ->where('authority_purchase_status', 'Pending')
                ->whereNotNull('authority_purchase_submitted_at')
                ->where(function ($q) {
                    $q->whereNull('authority_purchase_is_archived')
                        ->orWhere('authority_purchase_is_archived', 0);
                })
                ->count();
        }

        if (Schema::hasTable('request_check_table')) {
            $rfcPending = (int) DB::table('request_check_table')
                ->whereIn('request_check_status', self::RFC_INCOMING)
                ->count();

            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $fundsAwaiting = (int) DB::table('request_check_table')
                    ->where('request_check_status', 'Approved')
                    ->whereNull('request_check_funds_released_at')
                    ->count();
            }
        }

        if (Schema::hasTable('liquidation_reports_table')) {
            $liqPending = (int) DB::table('liquidation_reports_table')
                ->whereIn('liquidation_report_status', self::LIQ_INCOMING)
                ->count();

            if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_submission_deadline')) {
                $liqOverdue = (int) DB::table('liquidation_reports_table')
                    ->whereIn('liquidation_report_status', self::LIQ_INCOMING)
                    ->whereDate('liquidation_report_submission_deadline', '<', today())
                    ->count();
            }
        }

        self::$cached = [
            'atpPending' => $atpPending,
            'rfcPending' => $rfcPending,
            'fundsAwaiting' => $fundsAwaiting,
            'liqPending' => $liqPending,
            'liqOverdue' => $liqOverdue,
            'attentionTotal' => $atpPending + $rfcPending + $fundsAwaiting + $liqPending,
        ];

        return self::$cached;
    }
}
