<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    public const RFC_INCOMING = ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];

    public const LIQ_INCOMING = ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];

    private const REVIEWER_COLUMNS = [
        'authority_to_purchase_table' => 'authority_purchase_assigned_reviewer_id',
        'request_check_table' => 'request_check_assigned_reviewer_id',
        'liquidation_reports_table' => 'liquidation_report_assigned_reviewer_id',
    ];

    /**
     * Base query limited to the current reviewer's queue, matching what the Accounting lists show.
     */
    public static function queue(string $table)
    {
        $query = DB::table($table);
        $column = self::REVIEWER_COLUMNS[$table] ?? null;

        if ($column && Schema::hasColumn($table, $column)) {
            ReviewerAssignment::applyQueueFilter($query, $table.'.'.$column);
        }

        return $query;
    }

    /**
     * ATPs waiting for Accounting review. Bundled ATPs are reviewed via their Purchase Order instead.
     */
    public static function scopeAtpIncoming($query): void
    {
        $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
            ->whereNotNull('authority_to_purchase_table.authority_purchase_submitted_at')
            ->where(function ($q) {
                $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                    ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
            });

        if (!PurchaseOrderBasket::tablesExist()) {
            return;
        }

        $query->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('purchase_order_atps_table')
                ->join(
                    'purchase_orders_table',
                    'purchase_order_atps_table.purchase_order_id',
                    '=',
                    'purchase_orders_table.purchase_order_id'
                )
                ->whereColumn(
                    'purchase_order_atps_table.authority_purchase_id',
                    'authority_to_purchase_table.authority_purchase_id'
                )
                ->whereIn('purchase_orders_table.purchase_order_status', [
                    PurchaseOrderBasket::STATUS_SUBMITTED,
                    PurchaseOrderBasket::STATUS_APPROVED,
                ]);
        });
    }

    public static function scopeRfcActive($query): void
    {
        if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });
        }
    }

    /**
     * RFCs waiting for Accounting review.
     */
    public static function scopeRfcIncoming($query): void
    {
        self::scopeRfcActive($query);
        $query->whereIn('request_check_table.request_check_status', self::RFC_INCOMING);
    }

    /**
     * Approved RFCs whose funds have not been released yet.
     */
    public static function scopeRfcFunds($query): void
    {
        self::scopeRfcActive($query);
        $query->where('request_check_table.request_check_status', 'Approved');

        if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            $query->whereNull('request_check_table.request_check_funds_released_at');
        } else {
            $query->whereRaw('0 = 1');
        }
    }

    public static function scopeLiqActive($query): void
    {
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('liquidation_reports_table.liquidation_report_is_archived')
                    ->orWhere('liquidation_reports_table.liquidation_report_is_archived', 0);
            });
        }
    }

    /**
     * Liquidation reports waiting for Accounting review.
     */
    public static function scopeLiqIncoming($query): void
    {
        self::scopeLiqActive($query);
        $query->whereIn('liquidation_reports_table.liquidation_report_status', self::LIQ_INCOMING);
    }

    /**
     * Incoming liquidation reports whose submission deadline is before today.
     */
    public static function scopeLiqOverdue($query): void
    {
        self::scopeLiqIncoming($query);

        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_submission_deadline')) {
            $query->whereDate('liquidation_reports_table.liquidation_report_submission_deadline', '<', today()->toDateString());
        } else {
            $query->whereRaw('0 = 1');
        }
    }

    /**
     * Daily-reminder items and the list each one opens with ?focus=key.
     *
     * @return array<string, array{label:string, description:string, scope:string, status:string, table:string, route:string}>
     */
    public static function focusOptions(): array
    {
        return [
            'atp-review' => [
                'label' => 'ATP awaiting review',
                'description' => 'Submitted Authority to Purchase documents still Pending (bundled ATPs are reviewed through their Purchase Order).',
                'scope' => 'mine',
                'status' => 'incoming',
                'table' => 'authority_to_purchase_table',
                'route' => 'accounting.atp.index',
            ],
            'rfc-review' => [
                'label' => 'Request Checks pending review',
                'description' => 'Request Checks in Pending, Submitted, Under Review or Resubmitted.',
                'scope' => 'mine',
                'status' => 'incoming',
                'table' => 'request_check_table',
                'route' => 'accounting.rfc.index',
            ],
            'funds' => [
                'label' => 'Approved RFCs waiting for funds release',
                'description' => 'Approved Request Checks whose funds have not been marked as released.',
                'scope' => 'mine',
                'status' => 'funds',
                'table' => 'request_check_table',
                'route' => 'accounting.rfc.index',
            ],
            'liq-review' => [
                'label' => 'Liquidation reports pending review',
                'description' => 'Liquidation reports in Pending, Submitted, Under Review or Resubmitted.',
                'scope' => 'mine',
                'status' => 'incoming',
                'table' => 'liquidation_reports_table',
                'route' => 'accounting.liq.index',
            ],
            'overdue' => [
                'label' => 'Overdue liquidation reports',
                'description' => 'Liquidation reports pending review whose submission deadline has passed.',
                'scope' => 'mine',
                'status' => 'incoming',
                'table' => 'liquidation_reports_table',
                'route' => 'accounting.liq.index',
            ],
        ];
    }

    public static function applyFocus($query, string $key): void
    {
        match ($key) {
            'atp-review' => self::scopeAtpIncoming($query),
            'rfc-review' => self::scopeRfcIncoming($query),
            'funds' => self::scopeRfcFunds($query),
            'liq-review' => self::scopeLiqIncoming($query),
            'overdue' => self::scopeLiqOverdue($query),
        };
    }

    /**
     * Resolve ?focus= for one list page; returns null for unknown keys or keys that belong to another list.
     *
     * @return array{key:string, label:string, description:string, clear_url:string, scope:string, status:string}|null
     */
    public static function focusFor(Request $request, string $table): ?array
    {
        $key = (string) $request->query('focus', '');
        $option = self::focusOptions()[$key] ?? null;
        if (!$option || $option['table'] !== $table) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $option['label'],
            'description' => $option['description'],
            'clear_url' => route($option['route'], ['status' => $option['status']]),
            'scope' => $option['scope'],
            'status' => $option['status'],
        ];
    }

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

        $atpPending = self::countFocus('atp-review');
        $rfcPending = self::countFocus('rfc-review');
        $fundsAwaiting = self::countFocus('funds');
        $liqPending = self::countFocus('liq-review');
        $liqOverdue = self::countFocus('overdue');

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

    /**
     * Rows in the signed-in reviewer's queue matching one reminder item.
     */
    public static function countFocus(string $key): int
    {
        $table = self::focusOptions()[$key]['table'] ?? null;
        if ($table === null || !Schema::hasTable($table)) {
            return 0;
        }

        $query = self::queue($table);
        self::applyFocus($query, $key);

        return (int) $query->count();
    }
}
