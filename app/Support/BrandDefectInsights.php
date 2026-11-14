<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brands most often linked to maintenance defect reports — for purchaser
 * predictive guidance before ordering the same brand again.
 */
class BrandDefectInsights
{
    public const RISK_HIGH = 'high';

    public const RISK_ELEVATED = 'elevated';

    public const RISK_WATCH = 'watch';

    /** Minimum reports before a brand appears on the dashboard notice. */
    public const NOTICE_MIN_REPORTS = 3;

    /**
     * Rank equipment brands by how often they appear on defect reports.
     *
     * @return Collection<int, object{
     *     brand: string,
     *     brand_key: string,
     *     brand_id: int|null,
     *     report_count: int,
     *     equipment_count: int,
     *     last_reported_at: string|null,
     *     last_reported: string,
     *     sample_equipment: array<int, string>,
     *     risk: string,
     *     risk_label: string,
     *     avoid_ordering: bool,
     *     brand_status: string|null
     * }>
     */
    public static function ranked(int $limit = 25, ?int $months = 24): Collection
    {
        if (! Schema::hasTable('reports_table')
            || ! Schema::hasTable('equipment_table')
            || ! Schema::hasColumn('equipment_table', 'equipment_brand_name')) {
            return collect();
        }

        $brandExpr = "TRIM(equipment_table.equipment_brand_name)";
        $since = $months !== null && $months > 0
            ? now()->subMonths($months)->startOfDay()
            : null;
        $reportedAtSql = self::reportedAtSql();

        // Prefer report_items (multi-item reports); fall back to report header equipment.
        $rows = collect();
        if (Schema::hasTable('report_items_table')
            && Schema::hasColumn('report_items_table', 'report_item_equipment_id')) {
            $q = DB::table('report_items_table')
                ->join('equipment_table', 'report_items_table.report_item_equipment_id', '=', 'equipment_table.equipment_id')
                ->leftJoin('reports_table', 'report_items_table.report_id', '=', 'reports_table.report_id')
                ->whereNotNull('equipment_table.equipment_brand_name')
                ->whereRaw("{$brandExpr} != ''");

            if ($since !== null && $reportedAtSql !== null) {
                $q->whereRaw("{$reportedAtSql} >= ?", [$since]);
            }

            $rows = $q
                ->selectRaw("{$brandExpr} as brand")
                ->selectRaw('COUNT(DISTINCT report_items_table.report_id) as report_count')
                ->selectRaw('COUNT(DISTINCT equipment_table.equipment_id) as equipment_count')
                ->when(
                    $reportedAtSql !== null,
                    fn ($query) => $query->selectRaw("MAX({$reportedAtSql}) as last_reported_at"),
                    fn ($query) => $query->selectRaw('NULL as last_reported_at')
                )
                ->groupBy(DB::raw($brandExpr))
                ->orderByDesc('report_count')
                ->orderBy('brand')
                ->limit(max(1, $limit))
                ->get();
        }

        if ($rows->isEmpty()) {
            $q = DB::table('reports_table')
                ->join('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
                ->whereNotNull('equipment_table.equipment_brand_name')
                ->whereRaw("{$brandExpr} != ''");

            if ($since !== null && $reportedAtSql !== null) {
                $q->whereRaw("{$reportedAtSql} >= ?", [$since]);
            }

            $rows = $q
                ->selectRaw("{$brandExpr} as brand")
                ->selectRaw('COUNT(DISTINCT reports_table.report_id) as report_count')
                ->selectRaw('COUNT(DISTINCT equipment_table.equipment_id) as equipment_count')
                ->when(
                    $reportedAtSql !== null,
                    fn ($query) => $query->selectRaw("MAX({$reportedAtSql}) as last_reported_at"),
                    fn ($query) => $query->selectRaw('NULL as last_reported_at')
                )
                ->groupBy(DB::raw($brandExpr))
                ->orderByDesc('report_count')
                ->orderBy('brand')
                ->limit(max(1, $limit))
                ->get();
        }

        if ($rows->isEmpty()) {
            return collect();
        }

        $brandMeta = self::brandMetaByName($rows->pluck('brand')->all());
        $samples = self::sampleEquipmentByBrand($rows->pluck('brand')->all());

        return $rows->map(function ($row) use ($brandMeta, $samples) {
            $name = trim((string) $row->brand);
            $key = mb_strtolower($name);
            $meta = $brandMeta[$key] ?? null;
            $count = (int) $row->report_count;
            $risk = self::riskForCount($count);
            $lastAt = $row->last_reported_at ?? null;

            return (object) [
                'brand' => $name,
                'brand_key' => $key,
                'brand_id' => $meta['id'] ?? null,
                'report_count' => $count,
                'equipment_count' => (int) $row->equipment_count,
                'last_reported_at' => $lastAt ? (string) $lastAt : null,
                'last_reported' => $lastAt ? Carbon::parse($lastAt)->format('M d, Y') : '—',
                'sample_equipment' => $samples[$key] ?? [],
                'risk' => $risk,
                'risk_label' => self::riskLabel($risk),
                'avoid_ordering' => (bool) ($meta['avoid'] ?? false),
                'brand_status' => $meta['status'] ?? null,
            ];
        })->values();
    }

    /**
     * Top brands for dashboard / notice (high + elevated only by default).
     *
     * @return Collection<int, object>
     */
    public static function notice(int $limit = 5): Collection
    {
        return self::ranked($limit * 2)
            ->filter(fn ($row) => $row->report_count >= self::NOTICE_MIN_REPORTS || $row->avoid_ordering)
            ->take($limit)
            ->values();
    }

    public static function riskForCount(int $count): string
    {
        return match (true) {
            $count >= 8 => self::RISK_HIGH,
            $count >= 4 => self::RISK_ELEVATED,
            default => self::RISK_WATCH,
        };
    }

    public static function riskLabel(string $risk): string
    {
        return match ($risk) {
            self::RISK_HIGH => 'High failure reports',
            self::RISK_ELEVATED => 'Elevated reports',
            default => 'Watch',
        };
    }

    /**
     * Brands marked “avoid ordering” (case-insensitive name → true).
     *
     * @return array<string, true>
     */
    public static function avoidOrderingKeys(): array
    {
        if (! Schema::hasTable('brands_table')
            || ! Schema::hasColumn('brands_table', 'brand_avoid_ordering')) {
            return [];
        }

        return DB::table('brands_table')
            ->where('brand_avoid_ordering', 1)
            ->pluck('brand_name')
            ->mapWithKeys(fn ($name) => [mb_strtolower(trim((string) $name)) => true])
            ->all();
    }

    /**
     * Best available timestamp expression on reports_table.
     */
    private static function reportedAtSql(): ?string
    {
        $parts = [];
        foreach (['report_submitted_at', 'report_last_reported_at', 'report_updated_at'] as $column) {
            if (Schema::hasColumn('reports_table', $column)) {
                $parts[] = 'reports_table.'.$column;
            }
        }

        if ($parts === []) {
            return null;
        }

        return count($parts) === 1 ? $parts[0] : 'COALESCE('.implode(', ', $parts).')';
    }

    /**
     * @param  array<int, string>  $names
     * @return array<string, array{id: int, status: string|null, avoid: bool}>
     */
    private static function brandMetaByName(array $names): array
    {
        if ($names === [] || ! Schema::hasTable('brands_table')) {
            return [];
        }

        $hasAvoid = Schema::hasColumn('brands_table', 'brand_avoid_ordering');
        $select = ['brand_id', 'brand_name', 'brand_status'];
        if ($hasAvoid) {
            $select[] = 'brand_avoid_ordering';
        }

        $meta = [];
        foreach (DB::table('brands_table')->get($select) as $brand) {
            $key = mb_strtolower(trim((string) $brand->brand_name));
            $meta[$key] = [
                'id' => (int) $brand->brand_id,
                'status' => $brand->brand_status !== null ? (string) $brand->brand_status : null,
                'avoid' => $hasAvoid && (int) ($brand->brand_avoid_ordering ?? 0) === 1,
            ];
        }

        return $meta;
    }

    /**
     * @param  array<int, string>  $names
     * @return array<string, array<int, string>>
     */
    private static function sampleEquipmentByBrand(array $names): array
    {
        if ($names === [] || ! Schema::hasTable('equipment_table')) {
            return [];
        }

        $keys = collect($names)
            ->map(fn ($n) => mb_strtolower(trim((string) $n)))
            ->filter()
            ->unique()
            ->values();
        $samples = [];

        foreach ($keys as $key) {
            $samples[$key] = DB::table('equipment_table')
                ->whereRaw('LOWER(TRIM(equipment_brand_name)) = ?', [$key])
                ->whereNotNull('equipment_name')
                ->where('equipment_name', '!=', '')
                ->orderBy('equipment_name')
                ->limit(3)
                ->pluck('equipment_name')
                ->map(fn ($n) => trim((string) $n))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $samples;
    }
}
