<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Suppliers with repeated back-order problems — missing parts, damaged
 * deliveries, and unresolved cases with no refund — for purchaser caution.
 */
class SupplierDeliveryInsights
{
    public const RISK_HIGH = 'high';

    public const RISK_ELEVATED = 'elevated';

    public const RISK_WATCH = 'watch';

    /** Minimum issue events before a supplier appears on the dashboard notice. */
    public const NOTICE_MIN_ISSUES = 2;

    /**
     * Rank suppliers by back-order delivery problems.
     *
     * @return Collection<int, object{
     *     supplier: string,
     *     supplier_key: string,
     *     supplier_id: int|null,
     *     issue_count: int,
     *     damaged_count: int,
     *     missing_count: int,
     *     no_refund_count: int,
     *     quantity_affected: int,
     *     last_issue_at: string|null,
     *     last_issue: string,
     *     patterns: array<int, string>,
     *     pattern_summary: string,
     *     sample_articles: array<int, string>,
     *     risk: string,
     *     risk_label: string,
     *     avoid_ordering: bool,
     *     blacklist_reason: string|null
     * }>
     */
    public static function ranked(int $limit = 25, ?int $months = 24): Collection
    {
        if (! BackOrders::supported()) {
            return collect();
        }

        $since = $months !== null && $months > 0
            ? now()->subMonths($months)->startOfDay()
            : null;

        $query = DB::table(BackOrders::TABLE)
            ->whereNotNull('back_order_supplier_name')
            ->whereRaw("TRIM(back_order_supplier_name) != ''");

        if ($since !== null) {
            $query->where('back_order_created_at', '>=', $since);
        }

        $rows = $query
            ->selectRaw('back_order_supplier_id')
            ->selectRaw('TRIM(back_order_supplier_name) as supplier_name')
            ->selectRaw('COUNT(*) as issue_count')
            ->selectRaw("SUM(CASE WHEN back_order_type = 'damaged' OR back_order_reason = 'damaged' THEN 1 ELSE 0 END) as damaged_count")
            ->selectRaw("SUM(CASE WHEN back_order_type = 'short' THEN 1 ELSE 0 END) as missing_count")
            ->selectRaw("SUM(CASE WHEN back_order_status IN ('open', 'waiting_restock') AND back_order_refund_amount IS NULL THEN 1 ELSE 0 END) as no_refund_count")
            ->selectRaw('COALESCE(SUM(back_order_quantity), 0) as quantity_affected')
            ->selectRaw('MAX(back_order_created_at) as last_issue_at')
            ->groupBy('back_order_supplier_id', DB::raw('TRIM(back_order_supplier_name)'))
            ->orderByDesc('issue_count')
            ->orderBy('supplier_name')
            ->limit(max(1, $limit * 3))
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        // Rank by display name so the same vendor is not split across id/null rows.
        $merged = [];
        foreach ($rows as $row) {
            $name = trim((string) $row->supplier_name);
            $nameKey = mb_strtolower($name);
            if ($nameKey === '') {
                continue;
            }

            $id = $row->back_order_supplier_id !== null ? (int) $row->back_order_supplier_id : null;
            $id = ($id !== null && $id > 0) ? $id : null;

            if (! isset($merged[$nameKey])) {
                $merged[$nameKey] = (object) [
                    'supplier_id' => $id,
                    'supplier_name' => $name,
                    'issue_count' => 0,
                    'damaged_count' => 0,
                    'missing_count' => 0,
                    'no_refund_count' => 0,
                    'quantity_affected' => 0,
                    'last_issue_at' => null,
                ];
            }

            $bucket = $merged[$nameKey];
            $bucket->issue_count += (int) $row->issue_count;
            $bucket->damaged_count += (int) $row->damaged_count;
            $bucket->missing_count += (int) $row->missing_count;
            $bucket->no_refund_count += (int) $row->no_refund_count;
            $bucket->quantity_affected += (int) $row->quantity_affected;
            if (strlen($name) > strlen((string) $bucket->supplier_name)) {
                $bucket->supplier_name = $name;
            }
            if ($id !== null && ! $bucket->supplier_id) {
                $bucket->supplier_id = $id;
            }
            $lastAt = $row->last_issue_at ?? null;
            if ($lastAt && ($bucket->last_issue_at === null || (string) $lastAt > (string) $bucket->last_issue_at)) {
                $bucket->last_issue_at = $lastAt;
            }
        }

        $merged = collect($merged)
            ->sortByDesc('issue_count')
            ->take(max(1, $limit))
            ->values();

        $meta = self::supplierMeta($merged->pluck('supplier_id')->filter()->map(fn ($id) => (int) $id)->all());
        $samples = self::sampleArticles($merged);

        return $merged->map(function ($row) use ($meta, $samples) {
            $name = trim((string) $row->supplier_name);
            $id = $row->supplier_id ? (int) $row->supplier_id : null;
            $key = $id ? 'id:'.$id : 'name:'.mb_strtolower($name);
            $info = $id ? ($meta[$id] ?? null) : null;
            $displayName = $info['name'] ?? $name;
            $issueCount = (int) $row->issue_count;
            $damaged = (int) $row->damaged_count;
            $missing = (int) $row->missing_count;
            $noRefund = (int) $row->no_refund_count;
            $patterns = self::patterns($damaged, $missing, $noRefund);
            $risk = self::riskForIssues($issueCount, $damaged, $missing, $noRefund);
            $lastAt = $row->last_issue_at ?? null;

            return (object) [
                'supplier' => $displayName,
                'supplier_key' => $key,
                'supplier_id' => $id,
                'issue_count' => $issueCount,
                'damaged_count' => $damaged,
                'missing_count' => $missing,
                'no_refund_count' => $noRefund,
                'quantity_affected' => (int) $row->quantity_affected,
                'last_issue_at' => $lastAt ? (string) $lastAt : null,
                'last_issue' => $lastAt ? Carbon::parse($lastAt)->format('M d, Y') : '—',
                'patterns' => $patterns,
                'pattern_summary' => $patterns !== [] ? implode(' · ', $patterns) : 'Delivery issues',
                'sample_articles' => $samples[$key] ?? [],
                'risk' => $risk,
                'risk_label' => self::riskLabel($risk),
                'avoid_ordering' => (bool) ($info['blacklisted'] ?? false),
                'blacklist_reason' => $info['reason'] ?? null,
            ];
        })->values();
    }

    /**
     * Top problem suppliers for dashboard / notice.
     *
     * @return Collection<int, object>
     */
    public static function notice(int $limit = 5): Collection
    {
        return self::ranked($limit * 2)
            ->filter(fn ($row) => $row->issue_count >= self::NOTICE_MIN_ISSUES || $row->avoid_ordering)
            ->take($limit)
            ->values();
    }

    /**
     * Supplier id → insight row for RIS dropdown sorting / notices.
     *
     * @return array<int, object>
     */
    public static function bySupplierId(?int $months = 24): array
    {
        return self::ranked(100, $months)
            ->filter(fn ($row) => $row->supplier_id)
            ->mapWithKeys(fn ($row) => [(int) $row->supplier_id => $row])
            ->all();
    }

    /**
     * Sort weight for RIS lists: lower = better (shown first).
     */
    public static function sortWeight(?object $insight, bool $blacklisted = false): int
    {
        if ($blacklisted) {
            return 40;
        }

        if (! $insight) {
            return 0;
        }

        $base = match ($insight->risk ?? self::RISK_WATCH) {
            self::RISK_HIGH => 30,
            self::RISK_ELEVATED => 20,
            default => ($insight->issue_count ?? 0) > 0 ? 10 : 0,
        };

        return $base + min(9, (int) ($insight->issue_count ?? 0));
    }

    /**
     * Short notice line shown under the RIS supplier field.
     */
    public static function latestNotice(?object $insight, ?string $blacklistReason = null): string
    {
        $payload = self::noticePayload($insight, $blacklistReason);

        return (string) ($payload['reason'] ?? 'This supplier is marked as not recommended.');
    }

    /**
     * Structured notice for RIS supplier caution UI.
     *
     * @return array{
     *     title: string,
     *     short: string,
     *     detail: string,
     *     chips: array<int, string>,
     *     issues_label: string,
     *     risk: string,
     *     risk_key: string,
     *     reason: string
     * }
     */
    public static function noticePayload(?object $insight, ?string $blacklistReason = null): array
    {
        if ($insight && (int) ($insight->issue_count ?? 0) > 0) {
            $chips = [];
            if ((int) ($insight->damaged_count ?? 0) > 0) {
                $chips[] = (int) $insight->damaged_count.' damaged';
            }
            if ((int) ($insight->missing_count ?? 0) > 0) {
                $chips[] = (int) $insight->missing_count.' missing';
            }
            if ((int) ($insight->no_refund_count ?? 0) > 0) {
                $chips[] = (int) $insight->no_refund_count.' no refund';
            }

            $issues = (int) $insight->issue_count;
            $issuesLabel = $issues.' back-order issue'.($issues === 1 ? '' : 's');
            $risk = (string) ($insight->risk_label ?? self::riskLabel(self::RISK_WATCH));
            $riskKey = (string) ($insight->risk ?? self::RISK_WATCH);
            $summary = $chips !== [] ? implode(', ', $chips) : (string) ($insight->pattern_summary ?? 'delivery issues');
            $shortRisk = match ($riskKey) {
                self::RISK_HIGH => 'High risk',
                self::RISK_ELEVATED => 'Elevated risk',
                default => 'Watch',
            };

            return [
                'title' => 'Latest delivery record',
                'short' => $issues.' issues · '.$shortRisk,
                'detail' => $summary.' · '.$issuesLabel.' ('.$risk.')',
                'chips' => $chips,
                'issues_label' => $issuesLabel,
                'risk' => $risk,
                'risk_key' => $riskKey,
                'reason' => 'Latest: '.$summary.' · '.$issuesLabel.' ('.$risk.').',
            ];
        }

        $reason = trim((string) ($blacklistReason ?? ''));
        if ($reason === '') {
            $reason = 'This supplier is marked as not recommended.';
        }

        return [
            'title' => 'Supplier caution',
            'short' => 'Caution · Flagged',
            'detail' => $reason,
            'chips' => [],
            'issues_label' => '',
            'risk' => 'Flagged',
            'risk_key' => self::RISK_ELEVATED,
            'reason' => $reason,
        ];
    }

    public static function riskForIssues(int $issues, int $damaged, int $missing, int $noRefund): string
    {
        $score = ($damaged * 2) + ($missing * 2) + ($noRefund * 2) + $issues;

        return match (true) {
            $score >= 10 || $issues >= 5 => self::RISK_HIGH,
            $score >= 5 || $issues >= 3 => self::RISK_ELEVATED,
            default => self::RISK_WATCH,
        };
    }

    public static function riskLabel(string $risk): string
    {
        return match ($risk) {
            self::RISK_HIGH => 'High delivery risk',
            self::RISK_ELEVATED => 'Repeated issues',
            default => 'Watch',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function patterns(int $damaged, int $missing, int $noRefund): array
    {
        $patterns = [];
        if ($damaged > 0) {
            $patterns[] = 'Damaged deliveries';
        }
        if ($missing > 0) {
            $patterns[] = 'Missing parts';
        }
        if ($noRefund > 0) {
            $patterns[] = 'No refund yet';
        }

        return $patterns;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array{name: string, blacklisted: bool, reason: string|null}>
     */
    private static function supplierMeta(array $ids): array
    {
        if ($ids === [] || ! Schema::hasTable('suppliers_table')) {
            return [];
        }

        $nameExpr = "COALESCE(physical_suppliers_table.company_name, online_suppliers_table.shop_name, CONCAT('Supplier #', suppliers_table.supplier_id))";

        $rows = DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->whereIn('suppliers_table.supplier_id', $ids)
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_is_blacklisted',
                'suppliers_table.supplier_blacklist_reason',
                DB::raw("{$nameExpr} as supplier_name")
            )
            ->get();

        $meta = [];
        foreach ($rows as $row) {
            $meta[(int) $row->supplier_id] = [
                'name' => trim((string) $row->supplier_name),
                'blacklisted' => (int) ($row->supplier_is_blacklisted ?? 0) === 1,
                'reason' => $row->supplier_blacklist_reason !== null
                    ? (string) $row->supplier_blacklist_reason
                    : null,
            ];
        }

        return $meta;
    }

    /**
     * @param  Collection<int, object>  $suppliers
     * @return array<string, array<int, string>>
     */
    private static function sampleArticles(Collection $suppliers): array
    {
        if ($suppliers->isEmpty() || ! BackOrders::supported()) {
            return [];
        }

        $samples = [];
        foreach ($suppliers as $row) {
            $id = $row->supplier_id ? (int) $row->supplier_id : null;
            $name = trim((string) $row->supplier_name);
            $key = $id ? 'id:'.$id : 'name:'.mb_strtolower($name);

            $q = DB::table(BackOrders::TABLE)
                ->whereNotNull('back_order_article')
                ->where('back_order_article', '!=', '')
                ->orderByDesc('back_order_created_at')
                ->limit(3);

            if ($id) {
                $q->where('back_order_supplier_id', $id);
            } else {
                $q->whereRaw('LOWER(TRIM(back_order_supplier_name)) = ?', [mb_strtolower($name)]);
            }

            $samples[$key] = $q->pluck('back_order_article')
                ->map(fn ($n) => trim((string) $n))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $samples;
    }
}
