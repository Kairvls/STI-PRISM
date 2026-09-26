<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Procurement → inventory open balance by RR line (Ordered / Received / Stocked / Deployed / Storage / Disposed).
 */
class EquipmentOpenBalance
{
    /**
     * @return array{lines: array<int, array>, totals: array<string, int>}
     */
    public static function summary(?int $limit = 40): array
    {
        if (
            ! Schema::hasTable('receiving_report_items_table')
            || ! Schema::hasTable('receiving_reports_table')
        ) {
            return [
                'lines' => [],
                'totals' => self::emptyTotals(),
            ];
        }

        $select = [
            'ri.receiving_report_item_id',
            'ri.receiving_report_id',
            'ri.receiving_report_item_article',
            'ri.receiving_report_item_quantity',
            'rr.receiving_report_form_number',
            'rr.receiving_report_status',
        ];
        if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_ordered_qty')) {
            $select[] = 'ri.receiving_report_item_ordered_qty';
        }
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')) {
            $select[] = 'rr.receiving_report_request_check_id';
        }
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
            $select[] = 'rr.receiving_report_atp_id';
        }

        $rows = DB::table('receiving_report_items_table as ri')
            ->join('receiving_reports_table as rr', 'rr.receiving_report_id', '=', 'ri.receiving_report_id')
            ->whereIn('rr.receiving_report_status', ['Completed', 'Accepted'])
            ->where('ri.receiving_report_item_quantity', '>', 0)
            ->orderByDesc('rr.receiving_report_id')
            ->orderBy('ri.receiving_report_item_id')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get($select);

        if ($rows->isEmpty()) {
            return [
                'lines' => [],
                'totals' => self::emptyTotals(),
            ];
        }

        $itemIds = $rows->pluck('receiving_report_item_id')->map(fn ($id) => (int) $id)->all();
        $equipmentByItem = self::equipmentBuckets($itemIds);
        $stockedByItem = ReceivableStockLines::stockedQuantities($itemIds);
        $poByAtp = self::poNumbersForRows($rows);

        $lines = [];
        $totals = self::emptyTotals();

        foreach ($rows as $row) {
            $itemId = (int) $row->receiving_report_item_id;
            $bucket = $equipmentByItem->get($itemId, [
                'stocked' => 0,
                'in_storage' => 0,
                'deployed' => 0,
                'disposed' => 0,
            ]);

            $ordered = (int) ($row->receiving_report_item_ordered_qty ?? $row->receiving_report_item_quantity ?? 0);
            $received = (int) ($row->receiving_report_item_quantity ?? 0);
            // Prefer dual-link stocked qty so RR→equipment and equipment→RR stay aligned.
            $stocked = max((int) $bucket['stocked'], (int) ($stockedByItem[$itemId] ?? 0));
            $inStorage = (int) $bucket['in_storage'];
            $deployed = (int) $bucket['deployed'];
            $disposed = (int) $bucket['disposed'];
            if ($stocked > (int) $bucket['stocked'] && ($inStorage + $deployed + $disposed) === 0) {
                $inStorage = $stocked;
            }

            $atpId = (int) ($row->receiving_report_atp_id ?? 0);
            $poNumber = $atpId > 0 ? ($poByAtp[$atpId] ?? null) : null;

            $line = [
                'receiving_report_item_id' => $itemId,
                'rr_number' => $row->receiving_report_form_number ?: ('RR #'.$row->receiving_report_id),
                'po_number' => $poNumber,
                'article' => trim((string) ($row->receiving_report_item_article ?? 'Item')),
                'ordered' => $ordered,
                'received' => $received,
                'stocked' => $stocked,
                'in_storage' => $inStorage,
                'deployed' => $deployed,
                'disposed' => $disposed,
                'pending_stock' => max(0, $received - $stocked),
            ];

            $lines[] = $line;
            foreach (['ordered', 'received', 'stocked', 'in_storage', 'deployed', 'disposed'] as $key) {
                $totals[$key] += $line[$key];
            }
            $totals['pending_stock'] += $line['pending_stock'];
        }

        return [
            'lines' => $lines,
            'totals' => $totals,
        ];
    }

    /**
     * @param  array<int, int>  $itemIds
     * @return Collection<int, array{stocked:int,in_storage:int,deployed:int,disposed:int}>
     */
    private static function equipmentBuckets(array $itemIds): Collection
    {
        $buckets = collect();
        foreach ($itemIds as $id) {
            $buckets[$id] = [
                'stocked' => 0,
                'in_storage' => 0,
                'deployed' => 0,
                'disposed' => 0,
            ];
        }

        if ($itemIds === [] || ! Schema::hasTable('equipment_table')) {
            return $buckets;
        }

        $query = DB::table('equipment_table as e')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id');

        if (Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
            $query->whereIn('e.equipment_receiving_report_item_id', $itemIds);
            $equipSelect = [
                'e.equipment_id',
                'e.equipment_quantity',
                'e.equipment_inventory_status',
                'e.equipment_receiving_report_item_id as item_id',
                'r.room_type',
            ];
        } elseif (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
            $query->join(
                'receiving_report_items_table as ri',
                'ri.receiving_report_item_equipment_id',
                '=',
                'e.equipment_id'
            )->whereIn('ri.receiving_report_item_id', $itemIds);
            $equipSelect = [
                'e.equipment_id',
                'e.equipment_quantity',
                'e.equipment_inventory_status',
                'ri.receiving_report_item_id as item_id',
                'r.room_type',
            ];
        } else {
            return $buckets;
        }

        $rows = $query->get($equipSelect);

        foreach ($rows as $row) {
            $itemId = (int) $row->item_id;
            if (! $buckets->has($itemId)) {
                continue;
            }
            $qty = max(1, (int) ($row->equipment_quantity ?? 1));
            $status = strtolower(trim((string) ($row->equipment_inventory_status ?? '')));
            $bucket = $buckets[$itemId];
            $bucket['stocked'] += $qty;

            if ($status === 'disposed') {
                $bucket['disposed'] += $qty;
            } elseif (RoomCategories::isStorageType($row->room_type ?? null)) {
                $bucket['in_storage'] += $qty;
            } else {
                $bucket['deployed'] += $qty;
            }
            $buckets[$itemId] = $bucket;
        }

        return $buckets;
    }

    /**
     * @return array<int, string>
     */
    private static function poNumbersForRows(Collection $rows): array
    {
        $atpIds = $rows->map(fn ($row) => (int) ($row->receiving_report_atp_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        $rfcIds = $rows->map(fn ($row) => (int) ($row->receiving_report_request_check_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        if (
            $rfcIds->isNotEmpty()
            && Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', 'request_check_authority_purchase_id')
        ) {
            $map = DB::table('request_check_table')
                ->whereIn('request_check_id', $rfcIds->all())
                ->pluck('request_check_authority_purchase_id', 'request_check_id');
            foreach ($rows as $row) {
                if (empty($row->receiving_report_atp_id) && ! empty($row->receiving_report_request_check_id)) {
                    $resolved = (int) ($map[(int) $row->receiving_report_request_check_id] ?? 0);
                    if ($resolved > 0) {
                        $row->receiving_report_atp_id = $resolved;
                        $atpIds->push($resolved);
                    }
                }
            }
            $atpIds = $atpIds->filter()->unique()->values();
        }

        if (
            $atpIds->isEmpty()
            || ! Schema::hasTable('purchase_order_atps_table')
            || ! Schema::hasTable('purchase_orders_table')
        ) {
            return [];
        }

        return DB::table('purchase_order_atps_table as poa')
            ->join('purchase_orders_table as po', 'po.purchase_order_id', '=', 'poa.purchase_order_id')
            ->whereIn('poa.authority_purchase_id', $atpIds->all())
            ->pluck('po.purchase_order_number', 'poa.authority_purchase_id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private static function emptyTotals(): array
    {
        return [
            'ordered' => 0,
            'received' => 0,
            'stocked' => 0,
            'in_storage' => 0,
            'deployed' => 0,
            'disposed' => 0,
            'pending_stock' => 0,
        ];
    }
}
