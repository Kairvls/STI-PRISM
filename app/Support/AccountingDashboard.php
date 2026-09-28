<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingDashboard
{
    public const QUEUE_TYPES = [
        'atp' => 'Authority to Purchase',
        'po' => 'Purchase Order',
        'rfc' => 'Request Check',
        'funds' => 'Funds release',
        'liq' => 'Liquidation',
    ];

    /** Queue items waiting longer than this many days are flagged as slow. */
    public const SLOW_AFTER_DAYS = 3;

    public static function build(): array
    {
        $queue = self::queue();
        $review = $queue->where('type', '!=', 'funds');
        $funds = $queue->where('type', 'funds');

        $thisMonth = self::released(now()->startOfMonth(), now()->endOfMonth());
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonth = self::released($lastMonthStart, $lastMonthStart->copy()->endOfMonth());

        $cashAdvances = self::outstandingCashAdvances();

        return [
            'queue' => $queue->take(8)->values(),
            'queueCount' => $queue->count(),
            'queueByType' => collect(self::QUEUE_TYPES)->map(fn ($label, $type) => [
                'label' => $label,
                'count' => $queue->where('type', $type)->count(),
                'amount' => (float) $queue->where('type', $type)->sum('amount'),
            ])->all(),
            'review' => [
                'count' => $review->count(),
                'amount' => (float) $review->sum('amount'),
                'urgent' => $review->where('urgent', true)->count(),
                'slow' => $review->where('slow', true)->count(),
            ],
            'funds' => [
                'count' => $funds->count(),
                'amount' => (float) $funds->sum('amount'),
                'oldest_days' => $funds->max('waiting_days'),
            ],
            'releasedThisMonth' => $thisMonth,
            'releasedLastMonth' => $lastMonth,
            'releasedChange' => $lastMonth['amount'] > 0
                ? (int) round((($thisMonth['amount'] - $lastMonth['amount']) / $lastMonth['amount']) * 100)
                : null,
            'cashAdvances' => $cashAdvances->take(5)->values(),
            'cashAdvanceCount' => $cashAdvances->count(),
            'cashAdvanceAmount' => (float) $cashAdvances->sum('amount'),
            'upcomingDeadlines' => self::upcomingDeadlines(),
            'decisions' => self::recentDecisions(),
            'poCounts' => self::poCounts(),
        ];
    }

    /**
     * Everything waiting on the signed-in reviewer: urgent first, then oldest first.
     */
    public static function queue(): Collection
    {
        return collect()
            ->merge(self::atpQueue())
            ->merge(self::poQueue())
            ->merge(self::rfcQueue('rfc'))
            ->merge(self::rfcQueue('funds'))
            ->merge(self::liqQueue())
            ->sort(function ($a, $b) {
                if ($a->urgent !== $b->urgent) {
                    return $a->urgent ? -1 : 1;
                }

                return strcmp((string) $a->since, (string) $b->since);
            })
            ->values();
    }

    private static function atpQueue(): Collection
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return collect();
        }

        $query = AccountingAttentionSummary::queue('authority_to_purchase_table');
        AccountingAttentionSummary::scopeAtpIncoming($query);
        $query->leftJoin('requisition_issue_slip_table', 'authority_to_purchase_table.authority_purchase_ris_id', '=', 'requisition_issue_slip_table.ris_id')
            ->leftJoin('physical_suppliers_table as ps', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'ps.supplier_id')
            ->leftJoin('online_suppliers_table as os', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'os.supplier_id')
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_submitted_at',
                'authority_to_purchase_table.authority_purchase_created_at',
                'requisition_issue_slip_table.ris_form_number',
                DB::raw('COALESCE(ps.company_name, os.shop_name) as supplier_name'),
                DB::raw('(SELECT SUM(COALESCE(i.atp_amount, i.atp_quantity * i.atp_unit_price)) FROM authority_to_purchase_items_table i WHERE i.authority_purchase_id = authority_to_purchase_table.authority_purchase_id) as atp_total')
            );
        DocumentUrgency::select($query, 'ATP');

        return $query->get()->map(fn ($row) => self::item(
            'atp',
            $row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id,
            $row->supplier_name ?: 'No supplier',
            $row->ris_form_number ?: null,
            $row->atp_total,
            $row->authority_purchase_submitted_at ?? $row->authority_purchase_created_at,
            DocumentUrgency::isUrgent('ATP', $row),
            'Review',
            url('/accounting/authority-to-purchase/'.$row->authority_purchase_id)
        ));
    }

    private static function poQueue(): Collection
    {
        if (!PurchaseOrderBasket::tablesExist()) {
            return collect();
        }

        $query = DB::table('purchase_orders_table')
            ->where('purchase_order_status', PurchaseOrderBasket::STATUS_SUBMITTED)
            ->where(fn ($q) => $q->whereNull('purchase_order_is_archived')->orWhere('purchase_order_is_archived', 0));
        if (Schema::hasColumn('purchase_orders_table', 'purchase_order_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'purchase_orders_table.purchase_order_assigned_reviewer_id');
        }
        DocumentUrgency::select($query, 'PO');

        $orders = $query->get();
        PurchaseOrderBasket::attachToOrders($orders);

        return $orders->map(fn ($row) => self::item(
            'po',
            $row->purchase_order_number ?: 'PO-'.$row->purchase_order_id,
            optional(collect($row->linked_atps ?? [])->first())->supplier_display ?: 'Bundled ATPs',
            (int) ($row->atp_count ?? 0).((int) ($row->atp_count ?? 0) === 1 ? ' ATP' : ' ATPs'),
            $row->po_total_amount ?? null,
            $row->purchase_order_submitted_at ?? $row->purchase_order_created_at,
            DocumentUrgency::isUrgent('PO', $row),
            'Review',
            url('/accounting/purchase-orders/'.$row->purchase_order_id)
        ));
    }

    private static function rfcQueue(string $type): Collection
    {
        if (!Schema::hasTable('request_check_table')) {
            return collect();
        }

        $query = AccountingAttentionSummary::queue('request_check_table');
        $type === 'funds'
            ? AccountingAttentionSummary::scopeRfcFunds($query)
            : AccountingAttentionSummary::scopeRfcIncoming($query);
        $query->leftJoin('authority_to_purchase_table', 'request_check_table.request_check_authority_purchase_id', '=', 'authority_to_purchase_table.authority_purchase_id')
            ->select('request_check_table.*', 'authority_to_purchase_table.authority_purchase_form_number');
        DocumentUrgency::select($query, 'RFC');

        return $query->get()->map(function ($row) use ($type) {
            $fundLabel = ProcurementPaymentPath::labels()[$row->request_check_funding_type ?? ''] ?? 'Request for Check';
            $since = $type === 'funds'
                ? ($row->request_check_approved_at ?? $row->request_check_accounting_verified_at ?? $row->request_check_updated_at)
                : ($row->request_check_submitted_at ?? $row->request_check_created_at ?? $row->request_check_date);

            return self::item(
                $type,
                $row->request_check_form_number ?: 'RFC-'.$row->request_check_id,
                $row->request_check_payee ?: ($row->request_check_requested_by ?: '—'),
                $fundLabel.($row->authority_purchase_form_number ? ' · '.$row->authority_purchase_form_number : ''),
                $row->request_check_amount_figures,
                $since,
                DocumentUrgency::isUrgent('RFC', $row),
                $type === 'funds' ? 'Release' : 'Review',
                url('/accounting/request-check/'.$row->request_check_id)
            );
        });
    }

    private static function liqQueue(): Collection
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            return collect();
        }

        $query = AccountingAttentionSummary::queue('liquidation_reports_table');
        AccountingAttentionSummary::scopeLiqIncoming($query);
        $query->leftJoin('receiving_reports_table', 'liquidation_reports_table.liquidation_report_receiving_report_id', '=', 'receiving_reports_table.receiving_report_id')
            ->select('liquidation_reports_table.*', 'receiving_reports_table.receiving_report_form_number');
        DocumentUrgency::select($query, 'LIQ');

        return $query->get()->map(fn ($row) => self::item(
            'liq',
            $row->liquidation_report_form_number ?: 'LIQ-'.$row->liquidation_report_id,
            $row->liquidation_report_employee_name ?: '—',
            $row->receiving_report_form_number ?: null,
            $row->liquidation_report_summary_actual_expense ?? $row->liquidation_report_amount_advance,
            $row->liquidation_report_submitted_at ?? $row->liquidation_report_date_submitted ?? $row->liquidation_report_created_at,
            DocumentUrgency::isUrgent('LIQ', $row),
            'Review',
            url('/accounting/liquidation-reports/'.$row->liquidation_report_id)
        ));
    }

    private static function item(string $type, string $ref, string $who, ?string $related, $amount, $since, bool $urgent, string $action, string $url): object
    {
        $sinceAt = $since ? Carbon::parse($since) : null;
        $waitingDays = $sinceAt ? (int) $sinceAt->diffInDays(now()) : null;

        return (object) [
            'type' => $type,
            'type_label' => self::QUEUE_TYPES[$type],
            'ref' => $ref,
            'who' => $who,
            'related' => $related,
            'amount' => $amount !== null ? (float) $amount : null,
            'since' => $sinceAt?->toDateTimeString(),
            'waiting' => $sinceAt?->diffForHumans(['parts' => 1, 'syntax' => CarbonInterface::DIFF_ABSOLUTE]),
            'waiting_days' => $waitingDays,
            'slow' => $waitingDays !== null && $waitingDays >= self::SLOW_AFTER_DAYS,
            'urgent' => $urgent,
            'action' => $action,
            'url' => $url,
        ];
    }

    /**
     * @return array{amount: float, count: int}
     */
    private static function released(Carbon $from, Carbon $to): array
    {
        if (!Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            return ['amount' => 0.0, 'count' => 0];
        }

        $row = DB::table('request_check_table')
            ->whereBetween('request_check_funds_released_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(request_check_amount_figures), 0) as amount, COUNT(*) as count')
            ->first();

        return ['amount' => (float) $row->amount, 'count' => (int) $row->count];
    }

    /**
     * Released cash advances that have no approved liquidation yet, oldest release first.
     */
    public static function outstandingCashAdvances(): Collection
    {
        if (!Schema::hasColumn('request_check_table', 'request_check_funds_released_at')
            || !Schema::hasColumn('request_check_table', 'request_check_funding_type')) {
            return collect();
        }

        $hasLiquidation = Schema::hasTable('liquidation_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id');

        $query = DB::table('request_check_table')
            ->where('request_check_funding_type', ProcurementPaymentPath::CASH_ADVANCE)
            ->whereNotNull('request_check_funds_released_at')
            ->orderBy('request_check_funds_released_at');
        AccountingAttentionSummary::scopeRfcActive($query);

        $select = ['request_check_table.*'];
        if ($hasLiquidation) {
            $query->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('liquidation_reports_table as lq')
                ->join('receiving_reports_table as lrr', 'lrr.receiving_report_id', '=', 'lq.liquidation_report_receiving_report_id')
                ->whereColumn('lrr.receiving_report_request_check_id', 'request_check_table.request_check_id')
                ->where('lq.liquidation_report_status', 'Approved'));
            $select[] = DB::raw("(SELECT lq2.liquidation_report_status FROM liquidation_reports_table lq2
                INNER JOIN receiving_reports_table lrr2 ON lrr2.receiving_report_id = lq2.liquidation_report_receiving_report_id
                WHERE lrr2.receiving_report_request_check_id = request_check_table.request_check_id
                ORDER BY lq2.liquidation_report_id DESC LIMIT 1) as liquidation_status");
        }

        return $query->select($select)->get()->map(function ($row) {
            $releasedAt = Carbon::parse($row->request_check_funds_released_at);

            return (object) [
                'ref' => $row->request_check_form_number ?: 'RFC-'.$row->request_check_id,
                'payee' => $row->request_check_payee ?: '—',
                'amount' => (float) $row->request_check_amount_figures,
                'released_at' => $releasedAt,
                'days' => (int) $releasedAt->diffInDays(now()),
                'liquidation_status' => $row->liquidation_status ?? null,
                'url' => url('/accounting/request-check/'.$row->request_check_id),
            ];
        });
    }

    /**
     * Pending liquidation reports with a deadline, soonest (or most overdue) first.
     */
    private static function upcomingDeadlines(): Collection
    {
        if (!Schema::hasColumn('liquidation_reports_table', 'liquidation_report_submission_deadline')) {
            return collect();
        }

        $query = AccountingAttentionSummary::queue('liquidation_reports_table');
        AccountingAttentionSummary::scopeLiqIncoming($query);

        return $query->whereNotNull('liquidation_report_submission_deadline')
            ->orderBy('liquidation_report_submission_deadline')
            ->limit(4)
            ->get()
            ->map(function ($row) {
                $deadline = Carbon::parse($row->liquidation_report_submission_deadline)->startOfDay();
                $days = (int) today()->diffInDays($deadline, false);

                return (object) [
                    'ref' => $row->liquidation_report_form_number ?: 'LIQ-'.$row->liquidation_report_id,
                    'who' => $row->liquidation_report_employee_name ?: '—',
                    'deadline' => $deadline,
                    'days' => $days,
                    'label' => match (true) {
                        $days < 0 => abs($days).' '.\Illuminate\Support\Str::plural('day', abs($days)).' overdue',
                        $days === 0 => 'Due today',
                        $days === 1 => 'Due tomorrow',
                        default => 'Due in '.$days.' days',
                    },
                    'tone' => $days < 0 ? 'red' : ($days <= 1 ? 'amber' : 'blue'),
                    'url' => url('/accounting/liquidation-reports/'.$row->liquidation_report_id),
                ];
            });
    }

    /**
     * Latest Accounting decisions (approvals, returns, fund releases), skipping "opened for review" entries.
     */
    private static function recentDecisions(int $limit = 6): Collection
    {
        if (!Schema::hasTable('approval_logs_table')) {
            return collect();
        }

        $logs = DB::table('approval_logs_table')
            ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
            ->where('approval_log_level', 'Accounting')
            ->where('approval_log_approval_status', '!=', 'Under Review')
            ->orderByDesc('approval_log_approved_at')
            ->orderByDesc('approval_log_id')
            ->limit($limit)
            ->select('approval_logs_table.*', 'users_table.user_full_name')
            ->get();

        $documents = [
            'ATP' => ['authority_to_purchase_table', 'authority_purchase_id', 'authority_purchase_form_number', '/accounting/authority-to-purchase/', 'Authority to Purchase'],
            'PO' => ['purchase_orders_table', 'purchase_order_id', 'purchase_order_number', '/accounting/purchase-orders/', 'Purchase Order'],
            'RFC' => ['request_check_table', 'request_check_id', 'request_check_form_number', '/accounting/request-check/', 'Request Check'],
            'LIQ' => ['liquidation_reports_table', 'liquidation_report_id', 'liquidation_report_form_number', '/accounting/liquidation-reports/', 'Liquidation'],
        ];
        $aliases = ['LR' => 'LIQ', 'LIQUIDATION' => 'LIQ', 'REQUEST CHECK' => 'RFC'];

        $numbers = [];
        foreach ($logs->groupBy(fn ($log) => $aliases[strtoupper((string) $log->approval_log_reference_type)] ?? strtoupper((string) $log->approval_log_reference_type)) as $type => $group) {
            if (!isset($documents[$type]) || !Schema::hasTable($documents[$type][0])) {
                continue;
            }
            [$table, $key, $number] = $documents[$type];
            $numbers[$type] = DB::table($table)->whereIn($key, $group->pluck('approval_log_reference_id'))->pluck($number, $key);
        }

        return $logs->map(function ($log) use ($documents, $aliases, $numbers) {
            $type = $aliases[strtoupper((string) $log->approval_log_reference_type)] ?? strtoupper((string) $log->approval_log_reference_type);
            $doc = $documents[$type] ?? null;
            $status = (string) $log->approval_log_approval_status;
            $remarks = trim((string) $log->approval_log_approval_remarks);
            $isRelease = str_starts_with(strtolower($remarks), 'funds released');

            return (object) [
                'action' => $isRelease ? 'Funds released' : $status,
                'type_label' => $doc[4] ?? $log->approval_log_reference_type,
                'ref' => $numbers[$type][$log->approval_log_reference_id] ?? null,
                'by' => $log->user_full_name ?: 'Accounting',
                'remarks' => $remarks,
                'at' => $log->approval_log_approved_at ? Carbon::parse($log->approval_log_approved_at) : null,
                'tone' => match (true) {
                    $isRelease => 'blue',
                    str_contains(strtolower($status), 'reject') => 'red',
                    str_contains(strtolower($status), 'revision') || str_contains(strtolower($status), 'return') => 'amber',
                    default => 'green',
                },
                'url' => $doc ? url($doc[3].$log->approval_log_reference_id) : null,
            ];
        });
    }

    /**
     * @return array{pending: int, revision: int, approved: int}
     */
    private static function poCounts(): array
    {
        if (!PurchaseOrderBasket::tablesExist()) {
            return ['pending' => 0, 'revision' => 0, 'approved' => 0];
        }

        $base = function () {
            $query = DB::table('purchase_orders_table')
                ->where(fn ($q) => $q->whereNull('purchase_order_is_archived')->orWhere('purchase_order_is_archived', 0));
            if (Schema::hasColumn('purchase_orders_table', 'purchase_order_assigned_reviewer_id')) {
                ReviewerAssignment::applyQueueFilter($query, 'purchase_orders_table.purchase_order_assigned_reviewer_id');
            }

            return $query;
        };

        return [
            'pending' => $base()->where('purchase_order_status', PurchaseOrderBasket::STATUS_SUBMITTED)->count(),
            'revision' => $base()->where('purchase_order_status', PurchaseOrderBasket::STATUS_DRAFT)->whereNotNull('purchase_order_revision_reason')->count(),
            'approved' => $base()->where('purchase_order_status', PurchaseOrderBasket::STATUS_APPROVED)->count(),
        ];
    }
}
