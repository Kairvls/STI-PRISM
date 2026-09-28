<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data for the Purchaser dashboard. Workload counts come from PurchaserAttentionSummary (same numbers as
 * the daily reminder); spend and document figures come from PurchaserHistory (strictly this account's own).
 */
class PurchaserDashboard
{
    public const STAGES = [
        'draft' => 'Draft',
        'review' => 'In review',
        'attention' => 'Needs action',
        'done' => 'Completed',
    ];

    public static function build(int $userId): array
    {
        $documents = PurchaserHistory::allDocuments($userId)
            ->each(fn ($doc) => $doc->stage = self::stage($doc));
        $active = $documents->reject(fn ($doc) => $doc->is_archived);

        $thisMonth = PurchaserHistory::summary($userId, ['from' => now()->startOfMonth()->toDateString()]);
        $lastMonth = PurchaserHistory::summary($userId, [
            'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
        ]);
        $allTime = PurchaserHistory::summary($userId, []);

        return [
            'attention' => PurchaserAttentionSummary::counts(),
            'handovers' => DraftHandover::incomingCounts(),
            'openBackOrders' => self::openBackOrders($documents),
            'backOrderReports' => self::backOrderReports($documents),
            'thisMonth' => $thisMonth,
            'spendChange' => self::percentChange($thisMonth['approved_spend'], $lastMonth['approved_spend']),
            'lastMonthSpend' => (float) $lastMonth['approved_spend'],
            'allTime' => $allTime,
            'awaitingReview' => $active->where('stage', 'review')->count(),
            'pipeline' => self::pipeline($active),
            'needsAction' => $active->where('stage', 'attention')->take(5)->values(),
            'needsActionCount' => $active->where('stage', 'attention')->count(),
            'recent' => $documents->take(6)->values(),
            'monthlySpend' => PurchaserHistory::monthlySpend($userId),
            'topSuppliers' => PurchaserHistory::suppliers($userId, [])->take(4)->values(),
            'replacementCounts' => self::replacementCounts(),
        ];
    }

    /**
     * Where a document sits in its own workflow.
     */
    public static function stage(object $doc): string
    {
        $status = mb_strtolower(trim($doc->status));

        return match (true) {
            $status === '' || str_starts_with($status, 'draft') => 'draft',
            str_contains($status, 'revision') || in_array($status, ['returned', 'incomplete'], true) => 'attention',
            $status === 'accepted' => 'review',
            $doc->tone === 'green' => 'done',
            $doc->tone === 'red' => 'closed',
            default => 'review',
        };
    }

    /**
     * Active documents per type, split by stage.
     *
     * @return array<string, array{label: string, total: int, stages: array<string, int>}>
     */
    private static function pipeline(Collection $active): array
    {
        $pipeline = [];
        foreach (PurchaserHistory::DOCUMENT_TYPES as $type => $label) {
            $ofType = $active->where('type', $type);
            $stages = [];
            foreach (array_keys(self::STAGES) as $stage) {
                $stages[$stage] = $ofType->where('stage', $stage)->count();
            }
            $pipeline[$type] = [
                'label' => $label,
                'total' => $ofType->count(),
                'stages' => $stages,
            ];
        }

        return $pipeline;
    }

    private static function openBackOrders(Collection $documents): int
    {
        $rrIds = $documents->where('type', 'rr')->pluck('id')->all();
        if ($rrIds === [] || ! BackOrders::supported()) {
            return 0;
        }

        return (int) DB::table(BackOrders::TABLE)
            ->whereIn('back_order_root_receiving_report_id', $rrIds)
            ->whereIn('back_order_status', BackOrders::UNRESOLVED)
            ->count();
    }

    /**
     * This account's RRs that still have unresolved back orders, newest first.
     *
     * @return Collection<int, object{doc: object, open: int, missing: int, damaged: int, amount: float, articles: array<int, string>}>
     */
    private static function backOrderReports(Collection $documents): Collection
    {
        $rrDocs = $documents->where('type', 'rr')->reject(fn ($doc) => $doc->is_archived)->keyBy('id');
        if ($rrDocs->isEmpty() || ! BackOrders::supported()) {
            return collect();
        }

        $grouped = DB::table(BackOrders::TABLE)
            ->whereIn('back_order_root_receiving_report_id', $rrDocs->keys()->all())
            ->whereIn('back_order_status', BackOrders::UNRESOLVED)
            ->get(['back_order_root_receiving_report_id', 'back_order_type', 'back_order_quantity', 'back_order_unit_price', 'back_order_article'])
            ->groupBy('back_order_root_receiving_report_id');

        return $rrDocs
            ->filter(fn ($doc, $id) => $grouped->has($id))
            ->map(function ($doc, $id) use ($grouped) {
                $rows = $grouped->get($id);

                return (object) [
                    'doc' => $doc,
                    'open' => $rows->count(),
                    'missing' => (int) $rows->where('back_order_type', 'short')->sum('back_order_quantity'),
                    'damaged' => (int) $rows->where('back_order_type', 'damaged')->sum('back_order_quantity'),
                    'amount' => (float) $rows->sum(fn ($row) => (int) $row->back_order_quantity * (float) $row->back_order_unit_price),
                    'articles' => $rows->pluck('back_order_article')->map(fn ($a) => trim((string) $a))->filter()->unique()->values()->all(),
                ];
            })
            ->values();
    }

    private static function percentChange(float $current, float $previous): ?int
    {
        if ($previous <= 0) {
            return null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    /**
     * @return array{pending: int, approved: int, completed: int}
     */
    private static function replacementCounts(): array
    {
        $counts = DB::table('procurement_requests_table')
            ->where('procurement_request_is_archived', false)
            ->select('procurement_request_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('procurement_request_status')
            ->pluck('aggregate', 'procurement_request_status');

        return [
            'pending' => (int) PurchaserAttentionSummary::counts()['pendingReplacementRequests'],
            'approved' => (int) ($counts['Approved'] ?? 0),
            'completed' => (int) ($counts['Completed'] ?? 0),
        ];
    }
}
