<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purchaser daily-reminder items. Each item is a named scope shared by the reminder count and the
 * list it links to (?focus=key), so the number on the reminder is exactly the rows the list shows.
 */
class PurchaserAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    public const FOCUS_REPLACEMENT_PENDING = 'pending-requests';

    public const FOCUS_URGENT_UNCLAIMED = 'unclaimed-urgent';

    public const FOCUS_RIS_READY_FOR_ATP = 'ready-for-atp';

    public const FOCUS_ATP_READY_FOR_RFC = 'ready-for-rfc';

    public const FOCUS_RFC_READY_FOR_RR = 'ready-for-rr';

    public const FOCUS_RR_READY_FOR_LIQ = 'ready-for-liquidation';

    /**
     * Shared items are open work any purchaser may pick up; "mine" items are the signed-in purchaser's own documents.
     */
    private const FOCUS = [
        self::FOCUS_REPLACEMENT_PENDING => [
            'label' => 'Pending replacement requests',
            'description' => 'Replacement requests from Maintenance waiting for a purchaser to approve or reject.',
            'scope' => 'shared',
        ],
        self::FOCUS_URGENT_UNCLAIMED => [
            'label' => 'Unclaimed urgent reports',
            'description' => 'Pending urgent reports that no maintenance personnel or purchaser has taken yet.',
            'scope' => 'shared',
        ],
        self::FOCUS_RIS_READY_FOR_ATP => [
            'label' => 'Approved RIS ready for ATP',
            'description' => 'Approved RIS that do not have an Authority to Purchase yet.',
            'scope' => 'mine',
        ],
        self::FOCUS_ATP_READY_FOR_RFC => [
            'label' => 'Approved ATP ready for funding',
            'description' => 'Approved ATP without an active Request for Check or Cash Advance.',
            'scope' => 'mine',
        ],
        self::FOCUS_RFC_READY_FOR_RR => [
            'label' => 'Funded RFC ready for Receiving Report',
            'description' => 'Approved requests with released funds and no active Receiving Report.',
            'scope' => 'mine',
        ],
        self::FOCUS_RR_READY_FOR_LIQ => [
            'label' => 'Receiving Reports ready for liquidation',
            'description' => 'Completed Cash Advance Receiving Reports with no open back orders and no active Liquidation Report.',
            'scope' => 'mine',
        ],
    ];

    /**
     * @return array{label: string, description: string, scope: string, clear_url: string}|null
     */
    public static function focus(?string $key, string $clearUrl): ?array
    {
        if ($key === null || !isset(self::FOCUS[$key])) {
            return null;
        }

        return self::FOCUS[$key] + ['clear_url' => $clearUrl];
    }

    /**
     * Focus details when the current request was opened from the given reminder item, otherwise null.
     * Picking a status afterwards is a normal filter, so it replaces the reminder focus.
     */
    public static function focusFor(Request $request, string $key): ?array
    {
        if ($request->query('focus') !== $key || $request->filled('status')) {
            return null;
        }

        return self::focus($key, $request->fullUrlWithoutQuery(['focus', 'page']));
    }

    /**
     * Actionable purchaser workload counts.
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

        $pendingReplacementRequests = (int) self::scopeReplacementPending(DB::table('procurement_requests_table'))->count();
        $availableUrgentReports = (int) self::scopeUrgentUnclaimed(DB::table('reports_table'))->count();
        $risReadyForAtp = (int) self::scopeRisReadyForAtp(DB::table('requisition_issue_slip_table'))->count();

        $atpReadyForRfc = Schema::hasTable('authority_to_purchase_table')
            ? (int) self::scopeAtpReadyForRfc(DB::table('authority_to_purchase_table'))->count()
            : 0;
        $rfcReadyForRr = Schema::hasTable('request_check_table')
            ? (int) self::scopeRfcReadyForRr(DB::table('request_check_table'))->count()
            : 0;
        $rrReadyForLiq = Schema::hasTable('receiving_reports_table')
            ? (int) self::scopeRrReadyForLiq(DB::table('receiving_reports_table'))->count()
            : 0;

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

    public static function scopeReplacementPending($query)
    {
        return $query
            ->where('procurement_requests_table.procurement_request_status', 'Pending')
            ->where('procurement_requests_table.procurement_request_is_archived', false)
            // The replacement list inner-joins the originating report.
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('reports_table as pr_report')
                    ->whereColumn('pr_report.report_id', 'procurement_requests_table.procurement_request_report_id');
            });
    }

    /**
     * Matches the urgent reports list, which shows one card per open equipment group.
     */
    public static function scopeUrgentUnclaimed($query)
    {
        return $query
            ->where('reports_table.report_urgency_level', 'Urgent')
            ->where('reports_table.report_current_status', 'Pending')
            ->where('reports_table.report_is_archived', false)
            ->whereNull('reports_table.report_assigned_personnel_id')
            ->whereNull('reports_table.report_assigned_purchaser_id')
            ->where(function ($groupQuery) {
                $groupQuery
                    ->whereNull('reports_table.report_equipment_id')
                    ->orWhereRaw(
                        'reports_table.report_id = (
                            SELECT MAX(duplicate_reports.report_id)
                            FROM reports_table AS duplicate_reports
                            WHERE duplicate_reports.report_equipment_id = reports_table.report_equipment_id
                              AND duplicate_reports.report_room_id = reports_table.report_room_id
                              AND duplicate_reports.report_is_archived = 0
                              AND duplicate_reports.report_current_status IN (?, ?, ?, ?)
                              AND '.ReportGrouping::groupBucketSql('duplicate_reports').'
                                = '.ReportGrouping::groupBucketSql('reports_table').'
                        )',
                        ReportGrouping::groupedStatuses()
                    );
            });
    }

    public static function scopeRisReadyForAtp($query)
    {
        RisWorkflow::applyEligibleForAtpScope($query);
        self::whereNotArchived($query, 'requisition_issue_slip_table', 'ris_is_archived');
        PurchaserDocumentAccess::scopeOwned($query, 'ris', 'requisition_issue_slip_table');

        return $query->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('authority_to_purchase_table as ready_atp')
                ->whereColumn('ready_atp.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id');
        });
    }

    public static function scopeAtpReadyForRfc($query)
    {
        $query->where('authority_to_purchase_table.authority_purchase_status', 'Approved');
        self::whereNotArchived($query, 'authority_to_purchase_table', 'authority_purchase_is_archived');
        PurchaserDocumentAccess::scopeOwned($query, 'atp', 'authority_to_purchase_table');

        $hasRfcArchive = Schema::hasColumn('request_check_table', 'request_check_is_archived');

        $query->whereNotExists(function ($sub) use ($hasRfcArchive) {
            $sub->select(DB::raw(1))
                ->from('request_check_table as ready_rfc')
                ->whereColumn('ready_rfc.request_check_authority_purchase_id', 'authority_to_purchase_table.authority_purchase_id')
                ->where('ready_rfc.request_check_status', '!=', 'Rejected');
            if ($hasRfcArchive) {
                self::whereNotArchived($sub, 'ready_rfc.request_check_is_archived');
            }
        });

        if (RfcAtpLinks::tableExists()) {
            $query->whereNotExists(function ($sub) use ($hasRfcArchive) {
                $sub->select(DB::raw(1))
                    ->from('request_check_atps_table as ready_link')
                    ->join('request_check_table as ready_link_rfc', 'ready_link_rfc.request_check_id', '=', 'ready_link.request_check_id')
                    ->whereColumn('ready_link.authority_purchase_id', 'authority_to_purchase_table.authority_purchase_id')
                    ->where('ready_link_rfc.request_check_status', '!=', 'Rejected');
                if ($hasRfcArchive) {
                    self::whereNotArchived($sub, 'ready_link_rfc.request_check_is_archived');
                }
            });
        }

        return $query;
    }

    public static function scopeRfcReadyForRr($query)
    {
        $query->where('request_check_table.request_check_status', 'Approved');
        self::whereNotArchived($query, 'request_check_table', 'request_check_is_archived');
        PurchaserDocumentAccess::scopeOwned($query, 'rfc', 'request_check_table');

        if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            $query->whereNotNull('request_check_table.request_check_funds_released_at');
        }

        if (
            Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
        ) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('receiving_reports_table as ready_rr')
                    ->whereColumn('ready_rr.receiving_report_request_check_id', 'request_check_table.request_check_id')
                    ->where('ready_rr.receiving_report_status', '!=', 'Returned');
                if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
                    self::whereNotArchived($sub, 'ready_rr.receiving_report_is_archived');
                }
            });
        }

        return $query;
    }

    /**
     * Same eligibility the Liquidation Report form uses when listing Receiving Reports.
     */
    public static function scopeRrReadyForLiq($query)
    {
        $query->where('receiving_reports_table.receiving_report_status', 'Completed');
        self::whereNotArchived($query, 'receiving_reports_table', 'receiving_report_is_archived');
        PurchaserDocumentAccess::scopeOwned($query, 'rr', 'receiving_reports_table');

        $atpIdSql = Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')
            ? 'COALESCE(receiving_reports_table.receiving_report_atp_id, (SELECT liq_rfc_atp.request_check_authority_purchase_id FROM request_check_table AS liq_rfc_atp WHERE liq_rfc_atp.request_check_id = receiving_reports_table.receiving_report_request_check_id))'
            : '(SELECT liq_rfc_atp.request_check_authority_purchase_id FROM request_check_table AS liq_rfc_atp WHERE liq_rfc_atp.request_check_id = receiving_reports_table.receiving_report_request_check_id)';

        $query->where(function ($cash) use ($atpIdSql) {
            $cash->whereExists(function ($sub) use ($atpIdSql) {
                $sub->select(DB::raw(1))
                    ->from('authority_to_purchase_table as liq_atp')
                    ->where('liq_atp.authority_purchase_payment_path', ProcurementPaymentPath::CASH_ADVANCE)
                    ->whereRaw('liq_atp.authority_purchase_id = '.$atpIdSql);
            })->orWhereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('request_check_table as liq_rfc')
                    ->whereColumn('liq_rfc.request_check_id', 'receiving_reports_table.receiving_report_request_check_id')
                    ->where('liq_rfc.request_check_funding_type', ProcurementPaymentPath::CASH_ADVANCE);
            });
        });

        if (Schema::hasTable('liquidation_reports_table')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('liquidation_reports_table as ready_liq')
                    ->whereColumn('ready_liq.liquidation_report_receiving_report_id', 'receiving_reports_table.receiving_report_id')
                    ->whereIn('ready_liq.liquidation_report_status', [
                        'Draft', 'Submitted', 'Under Review', 'Minor Revision', 'Resubmitted', 'Pending Admin Approval',
                    ]);
                self::whereNotArchived($sub, 'ready_liq.liquidation_report_is_archived');
            });
        }

        if (BackOrders::supported()) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from(BackOrders::TABLE)
                    ->whereColumn(BackOrders::TABLE.'.back_order_root_receiving_report_id', 'receiving_reports_table.receiving_report_id')
                    ->whereIn(BackOrders::TABLE.'.back_order_status', BackOrders::UNRESOLVED);
            });
        }

        return $query;
    }

    private static function whereNotArchived($query, string $table, ?string $column = null): void
    {
        if ($column !== null && !Schema::hasColumn($table, $column)) {
            return;
        }

        $qualified = $column !== null ? $table.'.'.$column : $table;
        $query->where(function ($q) use ($qualified) {
            $q->whereNull($qualified)->orWhere($qualified, 0);
        });
    }
}
