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
    private const COUNT_KEYS = ['ordered', 'received', 'stocked', 'in_storage', 'deployed', 'disposed', 'pending_stock'];

    /**
     * Latest $limit receiving reports with their lines and stocked units.
     *
     * @return array{reports: array<int, array>, lines: array<int, array>, totals: array<string, int>}
     */
    public static function summary(?int $limit = 40): array
    {
        $empty = [
            'reports' => [],
            'lines' => [],
            'totals' => self::emptyTotals(),
        ];

        if (
            ! Schema::hasTable('receiving_report_items_table')
            || ! Schema::hasTable('receiving_reports_table')
        ) {
            return $empty;
        }

        $base = DB::table('receiving_report_items_table as ri')
            ->join('receiving_reports_table as rr', 'rr.receiving_report_id', '=', 'ri.receiving_report_id')
            ->whereIn('rr.receiving_report_status', BackOrders::RECEIVED_RR_STATUSES)
            ->where('ri.receiving_report_item_quantity', '>', 0);

        $reportIds = (clone $base)
            ->select('rr.receiving_report_id')
            ->distinct()
            ->orderByDesc('rr.receiving_report_id')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->pluck('rr.receiving_report_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($reportIds === []) {
            return $empty;
        }

        $select = [
            'ri.receiving_report_item_id',
            'ri.receiving_report_id',
            'ri.receiving_report_item_article',
            'ri.receiving_report_item_quantity',
            'rr.receiving_report_form_number',
            'rr.receiving_report_status',
        ];
        foreach ([
            'receiving_report_items_table' => [
                'receiving_report_item_ordered_qty' => 'ri.receiving_report_item_ordered_qty',
                'receiving_report_item_unit' => 'ri.receiving_report_item_unit',
                'receiving_report_item_unit_price' => 'ri.receiving_report_item_unit_price',
                'receiving_report_item_amount' => 'ri.receiving_report_item_amount',
                'receiving_report_item_supplier_id' => 'ri.receiving_report_item_supplier_id',
                'receiving_report_item_supplier_name' => 'ri.receiving_report_item_supplier_name',
            ],
            'receiving_reports_table' => [
                'receiving_report_date' => 'rr.receiving_report_date',
                'receiving_report_delivery_date' => 'rr.receiving_report_delivery_date',
                'receiving_report_invoice_no' => 'rr.receiving_report_invoice_no',
                'receiving_report_dr_no' => 'rr.receiving_report_dr_no',
                'receiving_report_supplier_id' => 'rr.receiving_report_supplier_id',
                'receiving_report_request_check_id' => 'rr.receiving_report_request_check_id',
                'receiving_report_atp_id' => 'rr.receiving_report_atp_id',
            ],
        ] as $table => $columns) {
            foreach ($columns as $column => $aliased) {
                if (Schema::hasColumn($table, $column)) {
                    $select[] = $aliased;
                }
            }
        }

        $rows = (clone $base)
            ->whereIn('rr.receiving_report_id', $reportIds)
            ->orderByDesc('rr.receiving_report_id')
            ->orderBy('ri.receiving_report_item_id')
            ->get($select);

        if ($rows->isEmpty()) {
            return $empty;
        }

        $itemIds = $rows->pluck('receiving_report_item_id')->map(fn ($id) => (int) $id)->all();
        $equipmentByItem = self::equipmentBuckets($itemIds);
        $stockedByItem = ReceivableStockLines::stockedQuantities($itemIds);
        $poByAtp = self::poNumbersForRows($rows);
        $atpNumbers = self::atpNumbers($rows);
        $supplierNames = self::supplierNames($rows);

        $lines = [];
        $reports = [];
        $totals = self::emptyTotals();

        foreach ($rows as $row) {
            $itemId = (int) $row->receiving_report_item_id;
            $reportId = (int) $row->receiving_report_id;
            $bucket = $equipmentByItem->get($itemId, self::emptyBucket());

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
            $rrNumber = trim((string) ($row->receiving_report_form_number ?? ''));
            if ($rrNumber === '') {
                $rrNumber = 'RR #'.$reportId;
            }

            $supplierName = trim((string) ($row->receiving_report_item_supplier_name ?? ''));
            if ($supplierName === '') {
                $supplierId = (int) ($row->receiving_report_item_supplier_id ?? $row->receiving_report_supplier_id ?? 0);
                $supplierName = $supplierId > 0 ? (string) ($supplierNames[$supplierId] ?? '') : '';
            }

            $unitCost = isset($row->receiving_report_item_unit_price) && $row->receiving_report_item_unit_price !== null
                ? (float) $row->receiving_report_item_unit_price
                : null;
            $amount = isset($row->receiving_report_item_amount) && $row->receiving_report_item_amount !== null
                ? (float) $row->receiving_report_item_amount
                : ($unitCost !== null ? round($unitCost * $received, 2) : null);
            if ($unitCost === null && $amount !== null && $received > 0) {
                $unitCost = round($amount / $received, 2);
            }

            $line = [
                'receiving_report_item_id' => $itemId,
                'receiving_report_id' => $reportId,
                'rr_number' => $rrNumber,
                'po_number' => $poNumber,
                'article' => trim((string) ($row->receiving_report_item_article ?? 'Item')),
                'unit' => $row->receiving_report_item_unit ?? null,
                'unit_cost' => $unitCost,
                'amount' => $amount,
                'ordered' => $ordered,
                'received' => $received,
                'stocked' => $stocked,
                'in_storage' => $inStorage,
                'deployed' => $deployed,
                'disposed' => $disposed,
                'pending_stock' => max(0, $received - $stocked),
                'units' => $bucket['units'],
            ];

            $lines[] = $line;
            foreach (self::COUNT_KEYS as $key) {
                $totals[$key] += $line[$key];
            }

            if (! isset($reports[$reportId])) {
                $date = $row->receiving_report_date ?? $row->receiving_report_delivery_date ?? null;
                $reports[$reportId] = [
                    'receiving_report_id' => $reportId,
                    'rr_number' => $rrNumber,
                    'po_number' => $poNumber,
                    'supplier_name' => $supplierName !== '' ? $supplierName : null,
                    'date' => $date ? (string) $date : null,
                    'atp_number' => $atpId > 0 ? ($atpNumbers[$atpId] ?? null) : null,
                    'invoice_no' => trim((string) ($row->receiving_report_invoice_no ?? '')) ?: null,
                    'dr_no' => trim((string) ($row->receiving_report_dr_no ?? '')) ?: null,
                    'amount' => 0.0,
                    'totals' => self::emptyTotals(),
                    'lines' => [],
                ];
            }
            if ($reports[$reportId]['supplier_name'] === null && $supplierName !== '') {
                $reports[$reportId]['supplier_name'] = $supplierName;
            }
            if ($reports[$reportId]['po_number'] === null && $poNumber !== null) {
                $reports[$reportId]['po_number'] = $poNumber;
            }
            foreach (self::COUNT_KEYS as $key) {
                $reports[$reportId]['totals'][$key] += $line[$key];
            }
            $reports[$reportId]['amount'] += (float) ($amount ?? 0);
            $reports[$reportId]['lines'][] = $line;
        }

        return [
            'reports' => array_values($reports),
            'lines' => array_map(function (array $line) {
                unset($line['units']);

                return $line;
            }, $lines),
            'totals' => $totals,
        ];
    }

    /**
     * @param  array<int, int>  $itemIds
     * @return Collection<int, array{stocked:int,in_storage:int,deployed:int,disposed:int,units:array<int, array>}>
     */
    private static function equipmentBuckets(array $itemIds): Collection
    {
        $buckets = collect();
        foreach ($itemIds as $id) {
            $buckets[$id] = self::emptyBucket();
        }

        if ($itemIds === [] || ! Schema::hasTable('equipment_table')) {
            return $buckets;
        }

        $columns = [
            'e.equipment_id',
            'e.equipment_quantity',
            'e.equipment_inventory_status',
            'r.room_type',
            'r.room_name',
        ];
        foreach (['equipment_name', 'equipment_asset_tag', 'equipment_serial_number', 'equipment_condition_status', 'equipment_tracking_mode'] as $column) {
            if (Schema::hasColumn('equipment_table', $column)) {
                $columns[] = 'e.'.$column;
            }
        }

        $rows = collect();
        if (Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
            $rows = $rows->concat(
                DB::table('equipment_table as e')
                    ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
                    ->whereIn('e.equipment_receiving_report_item_id', $itemIds)
                    ->get(array_merge($columns, ['e.equipment_receiving_report_item_id as item_id']))
            );
        }
        if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
            $rows = $rows->concat(
                DB::table('equipment_table as e')
                    ->join('receiving_report_items_table as ri', 'ri.receiving_report_item_equipment_id', '=', 'e.equipment_id')
                    ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
                    ->whereIn('ri.receiving_report_item_id', $itemIds)
                    ->get(array_merge($columns, ['ri.receiving_report_item_id as item_id']))
            );
        }

        $seen = [];
        foreach ($rows->sortBy('equipment_id') as $row) {
            $itemId = (int) $row->item_id;
            $equipmentId = (int) $row->equipment_id;
            if (! $buckets->has($itemId) || isset($seen[$equipmentId])) {
                continue;
            }
            $seen[$equipmentId] = true;

            $qty = max(1, (int) ($row->equipment_quantity ?? 1));
            $status = strtolower(trim((string) ($row->equipment_inventory_status ?? '')));
            $bucket = $buckets[$itemId];
            $bucket['stocked'] += $qty;

            if ($status === 'disposed') {
                $placement = 'disposed';
                $bucket['disposed'] += $qty;
            } elseif (RoomCategories::isStorageType($row->room_type ?? null)) {
                $placement = 'storage';
                $bucket['in_storage'] += $qty;
            } else {
                $placement = ($row->room_name ?? null) === null ? 'unplaced' : 'deployed';
                $bucket['deployed'] += $qty;
            }

            $bucket['units'][] = [
                'equipment_id' => $equipmentId,
                'name' => trim((string) ($row->equipment_name ?? '')) ?: 'Equipment #'.$equipmentId,
                'asset_tag' => $row->equipment_asset_tag ?? null,
                'serial' => $row->equipment_serial_number ?? null,
                'quantity' => $qty,
                'tracking' => $row->equipment_tracking_mode ?? null,
                'condition' => $row->equipment_condition_status ?? null,
                'room_name' => $row->room_name ?? null,
                'placement' => $placement,
                'view_url' => url('/maintenance/equipment/view/'.$equipmentId),
            ];
            $buckets[$itemId] = $bucket;
        }

        return $buckets;
    }

    /**
     * @return array<int, string>
     */
    private static function supplierNames(Collection $rows): array
    {
        $ids = $rows->map(fn ($row) => (int) ($row->receiving_report_item_supplier_id ?? $row->receiving_report_supplier_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty() || ! Schema::hasTable('suppliers_table')) {
            return [];
        }

        $query = DB::table('suppliers_table as s')->whereIn('s.supplier_id', $ids->all());
        $parts = [];
        if (Schema::hasTable('physical_suppliers_table')) {
            $query->leftJoin('physical_suppliers_table as ps', 'ps.supplier_id', '=', 's.supplier_id');
            $parts[] = 'ps.company_name';
        }
        if (Schema::hasTable('online_suppliers_table')) {
            $query->leftJoin('online_suppliers_table as os', 'os.supplier_id', '=', 's.supplier_id');
            $parts[] = 'os.shop_name';
        }
        if ($parts === []) {
            return [];
        }

        return $query
            ->get(['s.supplier_id', DB::raw('COALESCE('.implode(', ', $parts).') as supplier_name')])
            ->filter(fn ($row) => trim((string) $row->supplier_name) !== '')
            ->mapWithKeys(fn ($row) => [(int) $row->supplier_id => (string) $row->supplier_name])
            ->all();
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
            ->where('po.purchase_order_status', '!=', PurchaseOrderBasket::STATUS_CANCELLED)
            ->pluck('po.purchase_order_number', 'poa.authority_purchase_id')
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    /**
     * Run after poNumbersForRows(), which resolves the ATP id from the linked RFC.
     *
     * @return array<int, string>
     */
    private static function atpNumbers(Collection $rows): array
    {
        $atpIds = $rows->map(fn ($row) => (int) ($row->receiving_report_atp_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        if (
            $atpIds->isEmpty()
            || ! Schema::hasTable('authority_to_purchase_table')
            || ! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_form_number')
        ) {
            return [];
        }

        return DB::table('authority_to_purchase_table')
            ->whereIn('authority_purchase_id', $atpIds->all())
            ->whereNotNull('authority_purchase_form_number')
            ->pluck('authority_purchase_form_number', 'authority_purchase_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->all();
    }

    /**
     * @return array{stocked:int,in_storage:int,deployed:int,disposed:int,units:array<int, array>}
     */
    private static function emptyBucket(): array
    {
        return [
            'stocked' => 0,
            'in_storage' => 0,
            'deployed' => 0,
            'disposed' => 0,
            'units' => [],
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function emptyTotals(): array
    {
        return array_fill_keys(self::COUNT_KEYS, 0);
    }
}
