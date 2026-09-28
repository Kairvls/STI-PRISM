<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Extra President dashboard figures: approved spend, decision speed, pending aging,
 * what happened to approved RIS downstream, direct approvals and top requested items.
 */
class PresidentDashboardInsights
{
    private const RIS = 'requisition_issue_slip_table';
    private const ITEMS = 'requisition_issue_slip_items_table';
    private const STALLED_AFTER_DAYS = 7;

    public static function build(): array
    {
        return [
            'spend' => self::safe(fn () => self::spend(), ['pendingAmount' => 0.0, 'thisMonth' => 0.0, 'lastMonth' => 0.0, 'thisMonthCount' => 0, 'directCount' => 0, 'directAmount' => 0.0]),
            'decisionHours' => self::safe(fn () => self::averageDecisionHours(), null),
            'decisions' => self::safe(fn () => self::recentDecisions(), ['approved' => 0, 'rejected' => 0, 'returned' => 0, 'total' => 0, 'days' => 30]),
            'aging' => self::safe(fn () => self::pendingAging(), ['buckets' => [], 'oldest' => null, 'total' => 0]),
            'pipeline' => self::safe(fn () => self::pipeline(), ['steps' => [], 'stalled' => 0, 'stalledAfter' => self::STALLED_AFTER_DAYS, 'stalledList' => [], 'total' => 0]),
            'directApprovals' => self::safe(fn () => self::recentDirectApprovals(), collect()),
            'topItems' => self::safe(fn () => self::topItems(), collect()),
        ];
    }

    private static function safe(callable $callback, $fallback)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::warning('President dashboard insight failed: '.$e->getMessage());

            return $fallback;
        }
    }

    /** President-approved or directly approved RIS. */
    private static function scopeApproved($query, string $prefix = '')
    {
        return $query->where(function ($q) use ($prefix) {
            PresidentAttentionSummary::scopePresidentApproved($q, $prefix);
            $q->orWhere($prefix.'ris_status', RisWorkflow::DIRECTLY_APPROVED);
        });
    }

    private static function scopeActive($query, string $prefix = '')
    {
        if (Schema::hasColumn(self::RIS, 'ris_is_archived')) {
            $query->where(fn ($q) => $q->whereNull($prefix.'ris_is_archived')->orWhere($prefix.'ris_is_archived', 0));
        }

        return $query;
    }

    private static function amountSub()
    {
        return DB::table(self::ITEMS)
            ->select('ris_id', DB::raw('COALESCE(SUM(ris_total_amount), 0) as amount'))
            ->groupBy('ris_id');
    }

    private static function approvedAmountBetween(Carbon $from, Carbon $to): array
    {
        $query = DB::table(self::RIS.' as ris')
            ->leftJoinSub(self::amountSub(), 'amt', 'amt.ris_id', '=', 'ris.ris_id')
            ->whereBetween('ris.ris_approved_by_date', [$from->toDateString(), $to->toDateString()]);
        self::scopeApproved($query, 'ris.');
        self::scopeActive($query, 'ris.');

        $row = $query->selectRaw('COUNT(*) as c, COALESCE(SUM(amt.amount), 0) as a')->first();

        return ['count' => (int) ($row->c ?? 0), 'amount' => (float) ($row->a ?? 0)];
    }

    private static function spend(): array
    {
        $pending = DB::table(self::RIS.' as ris')
            ->leftJoinSub(self::amountSub(), 'amt', 'amt.ris_id', '=', 'ris.ris_id');
        PresidentAttentionSummary::scopePendingApproval($pending, 'ris.');
        self::scopeActive($pending, 'ris.');

        $thisMonth = self::approvedAmountBetween(now()->startOfMonth(), now()->endOfMonth());
        $lastMonth = self::approvedAmountBetween(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth());

        $direct = DB::table(self::RIS.' as ris')
            ->leftJoinSub(self::amountSub(), 'amt', 'amt.ris_id', '=', 'ris.ris_id')
            ->where('ris.ris_status', RisWorkflow::DIRECTLY_APPROVED)
            ->whereBetween(DB::raw('COALESCE(ris.ris_direct_approval_at, ris.ris_approved_by_date)'), [now()->startOfMonth(), now()->endOfMonth()]);
        self::scopeActive($direct, 'ris.');
        $directRow = $direct->selectRaw('COUNT(*) as c, COALESCE(SUM(amt.amount), 0) as a')->first();

        return [
            'pendingAmount' => (float) $pending->sum('amt.amount'),
            'thisMonth' => $thisMonth['amount'],
            'thisMonthCount' => $thisMonth['count'],
            'lastMonth' => $lastMonth['amount'],
            'directCount' => (int) ($directRow->c ?? 0),
            'directAmount' => (float) ($directRow->a ?? 0),
        ];
    }

    /**
     * Average hours between Administrator forwarding an RIS and the President's decision (last 90 days).
     */
    private static function averageDecisionHours(): ?float
    {
        if (!Schema::hasTable('approval_logs_table')) {
            return null;
        }

        $logs = DB::table('approval_logs_table')
            ->where('approval_log_reference_type', 'RIS')
            ->where(function ($q) {
                $q->where(fn ($f) => $f->where('approval_log_level', 'Admin')->where('approval_log_approval_status', 'Forwarded to President'))
                    ->orWhere('approval_log_level', 'President');
            })
            ->where('approval_log_approved_at', '>=', now()->subDays(90))
            ->orderBy('approval_log_approved_at')
            ->get(['approval_log_reference_id', 'approval_log_level', 'approval_log_approval_status', 'approval_log_approved_at']);

        $hours = [];
        foreach ($logs->groupBy('approval_log_reference_id') as $group) {
            $forwarded = $group->first(fn ($log) => $log->approval_log_level === 'Admin');
            if (!$forwarded) {
                continue;
            }
            $decision = $group->first(fn ($log) => $log->approval_log_level === 'President'
                && in_array($log->approval_log_approval_status, ['Approved', 'Rejected'], true)
                && $log->approval_log_approved_at >= $forwarded->approval_log_approved_at);
            if ($decision) {
                $hours[] = Carbon::parse($forwarded->approval_log_approved_at)->diffInMinutes(Carbon::parse($decision->approval_log_approved_at)) / 60;
            }
        }

        return $hours === [] ? null : round(array_sum($hours) / count($hours), 1);
    }

    /** RIS the President approved, rejected or returned in the last 30 days (latest decision per RIS). */
    private static function recentDecisions(): array
    {
        $days = 30;
        if (!Schema::hasTable('approval_logs_table')) {
            return ['approved' => 0, 'rejected' => 0, 'returned' => 0, 'total' => 0, 'days' => $days];
        }

        $latest = DB::table('approval_logs_table')
            ->where('approval_log_reference_type', 'RIS')
            ->where('approval_log_level', 'President')
            ->where('approval_log_approved_at', '>=', now()->subDays($days))
            ->orderBy('approval_log_approved_at')
            ->get(['approval_log_reference_id', 'approval_log_approval_status'])
            ->keyBy('approval_log_reference_id')
            ->pluck('approval_log_approval_status');

        $approved = $latest->filter(fn ($s) => $s === 'Approved')->count();
        $rejected = $latest->filter(fn ($s) => $s === 'Rejected')->count();

        return [
            'approved' => $approved,
            'rejected' => $rejected,
            'returned' => $latest->count() - $approved - $rejected,
            'total' => $latest->count(),
            'days' => $days,
        ];
    }

    private static function pendingAging(): array
    {
        $query = DB::table(self::RIS.' as ris')
            ->leftJoinSub(self::amountSub(), 'amt', 'amt.ris_id', '=', 'ris.ris_id');
        PresidentAttentionSummary::scopePendingApproval($query, 'ris.');
        self::scopeActive($query, 'ris.');

        $forwardedSub = Schema::hasTable('approval_logs_table')
            ? DB::table('approval_logs_table')
                ->where('approval_log_reference_type', 'RIS')
                ->where('approval_log_level', 'Admin')
                ->where('approval_log_approval_status', 'Forwarded to President')
                ->select('approval_log_reference_id', DB::raw('MAX(approval_log_approved_at) as forwarded_at'))
                ->groupBy('approval_log_reference_id')
            : null;
        if ($forwardedSub) {
            $query->leftJoinSub($forwardedSub, 'fwd', 'fwd.approval_log_reference_id', '=', 'ris.ris_id');
        }

        $rows = $query->get(array_filter([
            'ris.ris_id',
            'ris.ris_form_number',
            'ris.ris_submitted_at',
            'ris.ris_created_at',
            'amt.amount',
            $forwardedSub ? 'fwd.forwarded_at' : null,
        ]))->map(function ($row) {
            $since = $row->forwarded_at ?? $row->ris_submitted_at ?? $row->ris_created_at;
            $row->since = $since ? Carbon::parse($since) : null;
            $row->days = $row->since ? (int) $row->since->copy()->startOfDay()->diffInDays(today()) : 0;

            return $row;
        });

        $buckets = [
            ['label' => 'Today', 'count' => $rows->where('days', 0)->count()],
            ['label' => '1–3 days', 'count' => $rows->filter(fn ($r) => $r->days >= 1 && $r->days <= 3)->count()],
            ['label' => '4–7 days', 'count' => $rows->filter(fn ($r) => $r->days >= 4 && $r->days <= 7)->count()],
            ['label' => 'Over 7 days', 'count' => $rows->filter(fn ($r) => $r->days > 7)->count()],
        ];

        $oldest = $rows->sortByDesc('days')->first();

        return [
            'buckets' => $buckets,
            'total' => $rows->count(),
            'oldest' => $oldest ? (object) [
                'id' => (int) $oldest->ris_id,
                'number' => $oldest->ris_form_number ?: 'RIS #'.$oldest->ris_id,
                'days' => $oldest->days,
                'amount' => (float) ($oldest->amount ?? 0),
            ] : null,
        ];
    }

    /**
     * How far approved RIS have moved: ATP prepared → ATP authorized → funds released → delivered.
     */
    private static function pipeline(): array
    {
        $approved = self::scopeActive(self::scopeApproved(DB::table(self::RIS)))
            ->get(['ris_id', 'ris_form_number', 'ris_approved_by_date']);
        $ids = $approved->pluck('ris_id')->map(fn ($id) => (int) $id)->all();
        $total = count($ids);

        $withAtp = $authorized = $funded = $delivered = [];
        if ($total > 0 && Schema::hasTable('authority_to_purchase_table')) {
            $atps = DB::table('authority_to_purchase_table')
                ->whereIn('authority_purchase_ris_id', $ids)
                ->where(fn ($q) => $q->whereNull('authority_purchase_is_archived')->orWhere('authority_purchase_is_archived', 0))
                ->get(['authority_purchase_id', 'authority_purchase_ris_id', 'authority_purchase_status']);

            $withAtp = $atps->pluck('authority_purchase_ris_id')->unique()->all();
            $authorized = $atps->where('authority_purchase_status', 'Approved')->pluck('authority_purchase_ris_id')->unique()->all();
            $atpToRis = $atps->pluck('authority_purchase_ris_id', 'authority_purchase_id');

            if ($atpToRis->isNotEmpty() && Schema::hasTable('request_check_table')) {
                $funded = DB::table('request_check_table')
                    ->whereIn('request_check_authority_purchase_id', $atpToRis->keys())
                    ->whereNotNull('request_check_funds_released_at')
                    ->pluck('request_check_authority_purchase_id')
                    ->map(fn ($atpId) => $atpToRis[$atpId] ?? null)
                    ->filter()->unique()->all();
            }

            if ($atpToRis->isNotEmpty() && Schema::hasTable('receiving_reports_table') && Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
                $delivered = DB::table('receiving_reports_table')
                    ->whereIn('receiving_report_atp_id', $atpToRis->keys())
                    ->whereIn('receiving_report_status', BackOrders::RECEIVED_RR_STATUSES)
                    ->pluck('receiving_report_atp_id')
                    ->map(fn ($atpId) => $atpToRis[$atpId] ?? null)
                    ->filter()->unique()->all();
            }
        }

        $withAtpLookup = array_flip(array_map('intval', $withAtp));
        $stalledRows = $approved
            ->reject(fn ($ris) => isset($withAtpLookup[(int) $ris->ris_id]) || empty($ris->ris_approved_by_date))
            ->map(function ($ris) {
                $ris->days = (int) Carbon::parse($ris->ris_approved_by_date)->startOfDay()->diffInDays(today());

                return $ris;
            })
            ->filter(fn ($ris) => $ris->days >= self::STALLED_AFTER_DAYS)
            ->sortByDesc('days')
            ->values();

        $stalledAmounts = $stalledRows->isEmpty() ? collect() : DB::table(self::ITEMS)
            ->whereIn('ris_id', $stalledRows->take(4)->pluck('ris_id'))
            ->groupBy('ris_id')
            ->selectRaw('ris_id, COALESCE(SUM(ris_total_amount), 0) as amount')
            ->pluck('amount', 'ris_id');

        $step = fn (string $label, string $hint, int $count) => [
            'label' => $label,
            'hint' => $hint,
            'count' => $count,
            'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
        ];

        return [
            'total' => $total,
            'stalled' => $stalledRows->count(),
            'stalledAfter' => self::STALLED_AFTER_DAYS,
            'stalledList' => $stalledRows->take(4)->map(fn ($ris) => (object) [
                'id' => (int) $ris->ris_id,
                'number' => $ris->ris_form_number ?: 'RIS #'.$ris->ris_id,
                'days' => $ris->days,
                'amount' => (float) ($stalledAmounts[$ris->ris_id] ?? 0),
            ])->all(),
            'steps' => [
                $step('Approved', 'By you or directly by Administrator', $total),
                $step('ATP prepared', 'Purchaser created the Authority to Purchase', count($withAtp)),
                $step('ATP authorized', 'Accounting approved the purchase', count($authorized)),
                $step('Funds released', 'Check or cash advance handed over', count($funded)),
                $step('Delivered', 'Items received and counted', count($delivered)),
            ],
        ];
    }

    private static function recentDirectApprovals(): Collection
    {
        $query = DB::table(self::RIS.' as ris')
            ->leftJoinSub(self::amountSub(), 'amt', 'amt.ris_id', '=', 'ris.ris_id')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'ris.ris_direct_approval_by')
            ->where('ris.ris_status', RisWorkflow::DIRECTLY_APPROVED);
        self::scopeActive($query, 'ris.');

        return $query
            ->orderByDesc(DB::raw('COALESCE(ris.ris_direct_approval_at, ris.ris_approved_by_date)'))
            ->orderByDesc('ris.ris_id')
            ->limit(4)
            ->get([
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_direct_approval_reason',
                'ris.ris_direct_approval_at',
                'ris.ris_approved_by_date',
                'amt.amount',
                'u.user_full_name as approver',
            ])
            ->map(fn ($row) => (object) [
                'id' => (int) $row->ris_id,
                'number' => $row->ris_form_number ?: 'RIS #'.$row->ris_id,
                'reason' => trim((string) ($row->ris_direct_approval_reason ?? '')),
                'approver' => $row->approver ?: 'Administrator',
                'amount' => (float) ($row->amount ?? 0),
                'at' => ($row->ris_direct_approval_at ?? $row->ris_approved_by_date) ? Carbon::parse($row->ris_direct_approval_at ?? $row->ris_approved_by_date) : null,
            ]);
    }

    /**
     * Highest-value requested items across RIS approved in the last 90 days.
     */
    private static function topItems(): Collection
    {
        $query = DB::table(self::ITEMS.' as item')
            ->join(self::RIS.' as ris', 'ris.ris_id', '=', 'item.ris_id')
            ->where('ris.ris_approved_by_date', '>=', now()->subDays(90)->toDateString());
        self::scopeApproved($query, 'ris.');
        self::scopeActive($query, 'ris.');

        $rows = $query
            ->selectRaw("TRIM(item.ris_item_name_description) as name, SUM(item.ris_quantity_requested) as qty, SUM(item.ris_total_amount) as amount, COUNT(DISTINCT item.ris_id) as requests")
            ->groupBy(DB::raw('TRIM(item.ris_item_name_description)'))
            ->orderByDesc('amount')
            ->limit(5)
            ->get();

        $max = max(1, (float) $rows->max('amount'));

        return $rows->map(fn ($row) => (object) [
            'name' => $row->name ?: 'Unnamed item',
            'qty' => (int) $row->qty,
            'amount' => (float) $row->amount,
            'requests' => (int) $row->requests,
            'share' => (int) round(((float) $row->amount / $max) * 100),
        ]);
    }
}
