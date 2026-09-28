<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Administrator dashboard: latest Receiving Reports that have back orders.
 */
class AdminReceivingSummary
{
    private const RR = 'receiving_reports_table';

    /**
     * @return array{rows: Collection, total: int, open: int}
     */
    public static function build(int $limit = 4): array
    {
        $summary = ['rows' => collect(), 'total' => 0, 'open' => 0];
        if (! Schema::hasTable(self::RR) || ! BackOrders::supported()) {
            return $summary;
        }

        $perRr = DB::table(BackOrders::TABLE.' as bo')
            ->join(self::RR.' as rr', 'rr.receiving_report_id', '=', 'bo.back_order_root_receiving_report_id')
            ->where(fn ($q) => $q->whereNull('rr.receiving_report_is_archived')->orWhere('rr.receiving_report_is_archived', 0))
            ->groupBy('bo.back_order_root_receiving_report_id')
            ->selectRaw('bo.back_order_root_receiving_report_id as rr_id, MAX(bo.back_order_created_at) as latest_at, COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN bo.back_order_status IN ('.implode(',', array_fill(0, count(BackOrders::UNRESOLVED), '?')).') THEN 1 ELSE 0 END) as open_count', BackOrders::UNRESOLVED);

        $summary['total'] = (clone $perRr)->get()->count();
        $summary['open'] = (int) DB::table(BackOrders::TABLE)->whereIn('back_order_status', BackOrders::UNRESOLVED)->count();

        $latest = $perRr->orderByDesc('latest_at')->orderByDesc('rr_id')->limit($limit)->get()->keyBy('rr_id');
        if ($latest->isEmpty()) {
            return $summary;
        }

        $rrs = DB::table(self::RR.' as rr')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'rr.receiving_report_created_by')
            ->whereIn('rr.receiving_report_id', $latest->keys()->all())
            ->get([
                'rr.receiving_report_id',
                'rr.receiving_report_form_number',
                'rr.receiving_report_status',
                'rr.receiving_report_received_from',
                'u.user_full_name as purchaser',
            ])
            ->keyBy('receiving_report_id');

        $backOrders = DB::table(BackOrders::TABLE)
            ->whereIn('back_order_root_receiving_report_id', $latest->keys()->all())
            ->orderBy('back_order_id')
            ->get()
            ->groupBy('back_order_root_receiving_report_id');

        $summary['rows'] = $latest->map(function ($agg, $rrId) use ($rrs, $backOrders) {
            $rr = $rrs->get($rrId);
            $items = $backOrders->get($rrId, collect());
            $open = $items->filter(fn ($bo) => BackOrders::isUnresolved($bo->back_order_status));

            return (object) [
                'id' => (int) $rrId,
                'number' => ($rr->receiving_report_form_number ?? null) ?: 'RR #'.$rrId,
                'status' => $rr->receiving_report_status ?? null,
                'supplier' => trim((string) ($rr->receiving_report_received_from ?? '')) ?: null,
                'purchaser' => $rr->purchaser ?? null,
                'latestAt' => $agg->latest_at ? Carbon::parse($agg->latest_at) : null,
                'total' => (int) $agg->total_count,
                'open' => (int) $agg->open_count,
                'openQty' => (int) $open->sum('back_order_quantity'),
                'openValue' => (float) $open->sum(fn ($bo) => (int) $bo->back_order_quantity * (float) $bo->back_order_unit_price),
                'backOrders' => $items->map(fn ($bo) => (object) [
                    'number' => BackOrderNumber::label($bo),
                    'article' => $bo->back_order_article,
                    'qty' => (int) $bo->back_order_quantity,
                    'type' => BackOrders::TYPES[$bo->back_order_type] ?? 'Missing',
                    'open' => BackOrders::isUnresolved($bo->back_order_status),
                    'status' => BackOrders::statusLabel($bo->back_order_status),
                ])->values(),
            ];
        })->values();

        return $summary;
    }
}
