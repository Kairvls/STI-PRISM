<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaserAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * Actionable purchaser workload counts (same sources as the dashboard cards).
     *
     * @return array{
     *     pendingReplacementRequests: int,
     *     availableUrgentReports: int,
     *     risReadyForAtp: int,
     *     atpReadyForRfc: int,
     *     rfcReadyForRr: int,
     *     rrReadyForLiq: int,
     *     attentionTotal: int
     * }
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $pendingReplacementRequests = (int) DB::table('procurement_requests_table')
            ->where('procurement_request_status', 'Pending')
            ->count();

        $availableUrgentReports = (int) DB::table('reports_table')
            ->where('report_urgency_level', 'Urgent')
            ->where('report_current_status', 'Pending')
            ->where('report_is_archived', 0)
            ->whereNull('report_assigned_personnel_id')
            ->whereNull('report_assigned_purchaser_id')
            ->count();

        $risReadyForAtp = (int) RisWorkflow::applyEligibleForAtpScope(
            DB::table('requisition_issue_slip_table')
        )
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('authority_to_purchase_table')
                    ->whereColumn(
                        'authority_to_purchase_table.authority_purchase_ris_id',
                        'requisition_issue_slip_table.ris_id'
                    );
            })
            ->count();

        $atpReadyForRfc = 0;
        $rfcReadyForRr = 0;
        $rrReadyForLiq = 0;

        if (Schema::hasTable('authority_to_purchase_table')) {
            $atpReadyForRfc = (int) DB::table('authority_to_purchase_table')
                ->where('authority_purchase_status', 'Approved')
                ->where(function ($q) {
                    $q->whereNull('authority_purchase_is_archived')
                        ->orWhere('authority_purchase_is_archived', 0);
                })
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('request_check_table')
                        ->whereColumn(
                            'request_check_table.request_check_authority_purchase_id',
                            'authority_to_purchase_table.authority_purchase_id'
                        )
                        ->where('request_check_status', '!=', 'Rejected');

                    if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
                        $query->where(function ($inner) {
                            $inner->whereNull('request_check_is_archived')
                                ->orWhere('request_check_is_archived', 0);
                        });
                    }
                })
                ->count();
        }

        if (Schema::hasTable('request_check_table')) {
            $rfcReadyQuery = DB::table('request_check_table')
                ->where('request_check_status', 'Approved');

            if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
                $rfcReadyQuery->where(function ($q) {
                    $q->whereNull('request_check_is_archived')
                        ->orWhere('request_check_is_archived', 0);
                });
            }

            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $rfcReadyQuery->whereNotNull('request_check_funds_released_at');
            }

            if (
                Schema::hasTable('receiving_reports_table')
                && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
            ) {
                $rfcReadyQuery->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('receiving_reports_table')
                        ->whereColumn(
                            'receiving_reports_table.receiving_report_request_check_id',
                            'request_check_table.request_check_id'
                        )
                        ->where('receiving_report_status', '!=', 'Returned');

                    if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
                        $query->where(function ($inner) {
                            $inner->whereNull('receiving_report_is_archived')
                                ->orWhere('receiving_report_is_archived', 0);
                        });
                    }
                });
            }

            $rfcReadyForRr = (int) $rfcReadyQuery->count();
        }

        if (Schema::hasTable('receiving_reports_table')) {
            $rrReadyQuery = DB::table('receiving_reports_table')
                ->where('receiving_report_status', 'Completed');

            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
                $rrReadyQuery->where(function ($q) {
                    $q->whereNull('receiving_report_is_archived')
                        ->orWhere('receiving_report_is_archived', 0);
                });
            }

            if (
                Schema::hasTable('liquidation_reports_table')
                && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')
            ) {
                $rrReadyQuery
                    ->leftJoin(
                        'request_check_table',
                        'receiving_reports_table.receiving_report_request_check_id',
                        '=',
                        'request_check_table.request_check_id'
                    )
                    ->leftJoin(
                        'authority_to_purchase_table',
                        'request_check_table.request_check_authority_purchase_id',
                        '=',
                        'authority_to_purchase_table.authority_purchase_id'
                    )
                    ->where(function ($q) {
                        $q->where('authority_to_purchase_table.authority_purchase_payment_path', ProcurementPaymentPath::CASH_ADVANCE)
                            ->orWhere('request_check_table.request_check_funding_type', ProcurementPaymentPath::CASH_ADVANCE);
                    })
                    ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('liquidation_reports_table')
                        ->whereColumn(
                            'liquidation_reports_table.liquidation_report_receiving_report_id',
                            'receiving_reports_table.receiving_report_id'
                        )
                        ->where('liquidation_report_status', '!=', 'Rejected');

                    if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived')) {
                        $query->where(function ($inner) {
                            $inner->whereNull('liquidation_report_is_archived')
                                ->orWhere('liquidation_report_is_archived', 0);
                        });
                    }
                });
            }

            $rrReadyForLiq = (int) $rrReadyQuery->count();
        }

        self::$cached = [
            'pendingReplacementRequests' => $pendingReplacementRequests,
            'availableUrgentReports' => $availableUrgentReports,
            'risReadyForAtp' => $risReadyForAtp,
            'atpReadyForRfc' => $atpReadyForRfc,
            'rfcReadyForRr' => $rfcReadyForRr,
            'rrReadyForLiq' => $rrReadyForLiq,
            'attentionTotal' => $pendingReplacementRequests
                + $availableUrgentReports
                + $risReadyForAtp
                + $atpReadyForRfc
                + $rfcReadyForRr
                + $rrReadyForLiq,
        ];

        return self::$cached;
    }
}
