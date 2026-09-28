<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Administrator dashboard: budget payouts, supplier reliability and equipment watch lists.
 */
class AdminDashboardInsights
{
    /**
     * Funds actually released (Request for Check and Cash Advance), keyed by release year.
     *
     * @param  array<int, int>  $years
     * @return array<int, float>
     */
    public static function releasedByYear(array $years): array
    {
        $years = array_values(array_unique(array_map('intval', $years)));
        if ($years === [] || ! Schema::hasTable('request_check_table')) {
            return [];
        }

        return DB::table('request_check_table')
            ->whereNotNull('request_check_funds_released_at')
            ->where('request_check_is_archived', 0)
            ->whereIn(DB::raw('YEAR(request_check_funds_released_at)'), $years)
            ->groupBy(DB::raw('YEAR(request_check_funds_released_at)'))
            ->selectRaw('YEAR(request_check_funds_released_at) as release_year, SUM(COALESCE(request_check_amount_figures, 0)) as amount')
            ->pluck('amount', 'release_year')
            ->mapWithKeys(fn ($amount, $year) => [(int) $year => (float) $amount])
            ->all();
    }

    /**
     * Share of each supplier's submitted Receiving Reports that recorded a back order.
     *
     * @return array{rows: Collection, deliveries: int, incomplete: int}
     */
    public static function supplierReliability(int $limit = 5): array
    {
        $result = ['rows' => collect(), 'deliveries' => 0, 'incomplete' => 0];
        if (! Schema::hasTable('receiving_reports_table') || ! BackOrders::supported()) {
            return $result;
        }

        $backOrders = DB::table(BackOrders::TABLE)
            ->groupBy('back_order_receiving_report_id')
            ->selectRaw('back_order_receiving_report_id as rr_id')
            ->selectRaw('SUM(back_order_quantity) as qty')
            ->selectRaw("SUM(CASE WHEN back_order_type = 'damaged' THEN back_order_quantity ELSE 0 END) as damaged_qty");

        $query = DB::table('receiving_reports_table as rr');
        $nameParts = [];
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id') && Schema::hasTable('authority_to_purchase_table')) {
            $query->leftJoin('authority_to_purchase_table as atp', 'atp.authority_purchase_id', '=', 'rr.receiving_report_atp_id');
            if (Schema::hasTable('physical_suppliers_table')) {
                $query->leftJoin('physical_suppliers_table as ps', 'ps.supplier_id', '=', 'atp.authority_purchase_supplier_id');
                $nameParts[] = 'ps.company_name';
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $query->leftJoin('online_suppliers_table as os', 'os.supplier_id', '=', 'atp.authority_purchase_supplier_id');
                $nameParts[] = 'os.shop_name';
            }
        }
        $nameParts[] = "NULLIF(TRIM(rr.receiving_report_received_from), '')";
        $nameParts[] = "'Unnamed supplier'";
        $supplierExpr = 'COALESCE('.implode(', ', $nameParts).')';

        $rows = $query
            ->leftJoinSub($backOrders, 'bo', 'bo.rr_id', '=', 'rr.receiving_report_id')
            ->where(fn ($q) => $q->whereNull('rr.receiving_report_is_archived')->orWhere('rr.receiving_report_is_archived', 0))
            ->where('rr.receiving_report_status', '!=', 'Draft')
            ->groupBy(DB::raw($supplierExpr))
            ->selectRaw($supplierExpr.' as supplier')
            ->selectRaw('COUNT(*) as deliveries')
            ->selectRaw('SUM(CASE WHEN bo.rr_id IS NULL THEN 0 ELSE 1 END) as incomplete')
            ->selectRaw('COALESCE(SUM(bo.qty), 0) as short_qty')
            ->selectRaw('COALESCE(SUM(bo.damaged_qty), 0) as damaged_qty')
            ->get()
            ->map(function ($row) {
                $deliveries = (int) $row->deliveries;
                $incomplete = (int) $row->incomplete;

                return (object) [
                    'supplier' => $row->supplier,
                    'deliveries' => $deliveries,
                    'incomplete' => $incomplete,
                    'rate' => $deliveries > 0 ? (int) round($incomplete / $deliveries * 100) : 0,
                    'missingQty' => max(0, (int) $row->short_qty - (int) $row->damaged_qty),
                    'damagedQty' => (int) $row->damaged_qty,
                ];
            });

        $result['deliveries'] = (int) $rows->sum('deliveries');
        $result['incomplete'] = (int) $rows->sum('incomplete');
        $result['rows'] = $rows
            ->sort(fn ($a, $b) => [$b->rate, $b->deliveries] <=> [$a->rate, $a->deliveries])
            ->take($limit)
            ->values();

        return $result;
    }

    /**
     * Equipment whose warranty ends within the next $days days.
     *
     * @return array{rows: Collection, total: int}
     */
    public static function warrantyWatch(int $days = 30, int $limit = 4): array
    {
        $result = ['rows' => collect(), 'total' => 0];
        if (! Schema::hasColumn('equipment_table', 'equipment_warranty_expiration')) {
            return $result;
        }

        $today = now()->startOfDay();
        $base = DB::table('equipment_table as e')
            ->whereNotNull('e.equipment_warranty_expiration')
            ->whereBetween('e.equipment_warranty_expiration', [$today->toDateString(), $today->copy()->addDays($days)->toDateString()])
            ->where(fn ($q) => $q->whereNull('e.equipment_inventory_status')->orWhere('e.equipment_inventory_status', '!=', 'Disposed'));

        $result['total'] = (clone $base)->count();
        $result['rows'] = $base
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->orderBy('e.equipment_warranty_expiration')
            ->limit($limit)
            ->get([
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_brand_name',
                'e.equipment_asset_tag',
                'e.equipment_warranty_expiration',
                'r.room_name',
            ])
            ->map(function ($row) use ($today) {
                $ends = Carbon::parse($row->equipment_warranty_expiration)->startOfDay();

                return (object) [
                    'id' => (int) $row->equipment_id,
                    'name' => $row->equipment_name ?: 'Equipment #'.$row->equipment_id,
                    'meta' => collect([$row->equipment_brand_name, $row->equipment_asset_tag, $row->room_name])->filter()->implode(' · '),
                    'endsAt' => $ends,
                    'daysLeft' => (int) $today->diffInDays($ends),
                ];
            });

        return $result;
    }

    /**
     * Equipment flagged For Replacement that has not been disposed yet.
     *
     * @return array{rows: Collection, total: int}
     */
    public static function disposalWatch(int $limit = 4): array
    {
        $result = ['rows' => collect(), 'total' => 0];
        if (! Schema::hasTable('equipment_table')) {
            return $result;
        }

        $base = DB::table('equipment_table as e')->where('e.equipment_inventory_status', 'For Replacement');
        $result['total'] = (clone $base)->count();

        $hasHistory = Schema::hasTable('equipment_maintenance_history_table');
        if ($hasHistory) {
            $flagged = DB::table('equipment_maintenance_history_table')
                ->where('equipment_maintenance_status', 'For Replacement')
                ->groupBy('equipment_maintenance_equipment_id')
                ->selectRaw('equipment_maintenance_equipment_id as equipment_id, MAX(equipment_maintenance_created_at) as flagged_at');
            $base->leftJoinSub($flagged, 'fl', 'fl.equipment_id', '=', 'e.equipment_id');
        }

        $result['rows'] = $base
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->when($hasHistory, fn ($q) => $q->orderByRaw('fl.flagged_at IS NULL')->orderBy('fl.flagged_at'))
            ->orderBy('e.equipment_id')
            ->limit($limit)
            ->get(array_filter([
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_condition_status',
                'r.room_name',
                $hasHistory ? 'fl.flagged_at' : null,
            ]))
            ->map(fn ($row) => (object) [
                'id' => (int) $row->equipment_id,
                'name' => $row->equipment_name ?: 'Equipment #'.$row->equipment_id,
                'meta' => collect([$row->room_name ?: 'No room', $row->equipment_condition_status])->filter()->implode(' · '),
                'flaggedAt' => ! empty($row->flagged_at) ? Carbon::parse($row->flagged_at) : null,
            ]);

        return $result;
    }
}
