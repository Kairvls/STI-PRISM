<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Completed receiving-report lines that guide / feed maintenance inventory stocking.
 */
class ReceivableStockLines
{
    /**
     * Lines still needing stock (received qty greater than already-stocked qty).
     *
     * @return Collection<int, object>
     */
    public static function pending(): Collection
    {
        return self::guideLines()
            ->filter(fn ($line) => (int) ($line->remaining_qty ?? 0) > 0)
            ->values();
    }

    /**
     * All completed/accepted RR lines with PO context — used as the Add-to-stock guide list.
     *
     * @return Collection<int, object>
     */
    public static function guideLines(): Collection
    {
        $rows = self::baseRows();
        if ($rows->isEmpty()) {
            return collect();
        }

        $stockedByItem = self::stockedQuantities(
            $rows->pluck('receiving_report_item_id')->map(fn ($id) => (int) $id)->all()
        );

        return self::hydrate($rows)->map(function ($line) use ($stockedByItem) {
            $itemId = (int) $line->receiving_report_item_id;
            $received = (int) ($line->quantity ?? 0);
            $stocked = (int) ($stockedByItem[$itemId] ?? 0);
            $remaining = max(0, $received - $stocked);

            $line->received_qty = $received;
            $line->stocked_qty = $stocked;
            $line->remaining_qty = $remaining;
            $line->is_selectable = $remaining > 0;
            $line->status_label = $remaining <= 0
                ? 'Fully stocked'
                : ($stocked > 0 ? 'Partial · '.$remaining.' left' : 'Ready to stock');

            $labelParts = array_filter([
                $line->rr_number,
                $line->po_number ? 'PO '.$line->po_number : null,
                $line->article,
                'Recv '.$received,
                $remaining > 0 ? ('Left '.$remaining) : 'Done',
            ]);
            $line->label = implode(' · ', $labelParts);

            // Prefill form qty with remaining when this line can still be stocked.
            $line->quantity = $remaining > 0 ? $remaining : $received;

            return $line;
        })->values();
    }

    public static function findPendingItem(int $itemId): ?object
    {
        return self::pending()->firstWhere('receiving_report_item_id', $itemId);
    }

    /**
     * @return Collection<int, object>
     */
    private static function baseRows(): Collection
    {
        if (
            ! Schema::hasTable('receiving_reports_table')
            || ! Schema::hasTable('receiving_report_items_table')
        ) {
            return collect();
        }

        $query = DB::table('receiving_report_items_table as ri')
            ->join('receiving_reports_table as rr', 'rr.receiving_report_id', '=', 'ri.receiving_report_id')
            ->whereIn('rr.receiving_report_status', ['Completed', 'Accepted'])
            ->where('ri.receiving_report_item_quantity', '>', 0);

        if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_condition')) {
            $query->where(function ($q) {
                $q->whereNull('ri.receiving_report_item_condition')
                    ->orWhereNotIn('ri.receiving_report_item_condition', ['bad_order', 'bad']);
            });
        }

        $select = [
            'ri.receiving_report_item_id',
            'ri.receiving_report_id',
            'ri.receiving_report_item_article',
            'ri.receiving_report_item_quantity',
            'ri.receiving_report_item_unit',
        ];

        foreach ([
            'receiving_report_items_table' => [
                'receiving_report_item_ordered_qty' => 'ri.receiving_report_item_ordered_qty',
                'receiving_report_item_unit_price' => 'ri.receiving_report_item_unit_price',
                'receiving_report_item_amount' => 'ri.receiving_report_item_amount',
                'receiving_report_item_condition' => 'ri.receiving_report_item_condition',
                'receiving_report_item_supplier_id' => 'ri.receiving_report_item_supplier_id',
                'receiving_report_item_supplier_name' => 'ri.receiving_report_item_supplier_name',
                'receiving_report_item_equipment_id' => 'ri.receiving_report_item_equipment_id',
            ],
            'receiving_reports_table' => [
                'receiving_report_form_number' => 'rr.receiving_report_form_number',
                'receiving_report_date' => 'rr.receiving_report_date',
                'receiving_report_delivery_date' => 'rr.receiving_report_delivery_date',
                'receiving_report_invoice_no' => 'rr.receiving_report_invoice_no',
                'receiving_report_dr_no' => 'rr.receiving_report_dr_no',
                'receiving_report_supplier_id' => 'rr.receiving_report_supplier_id',
                'receiving_report_atp_id' => 'rr.receiving_report_atp_id',
                'receiving_report_request_check_id' => 'rr.receiving_report_request_check_id',
            ],
        ] as $table => $columns) {
            foreach ($columns as $column => $aliased) {
                if (Schema::hasColumn($table, $column)) {
                    $select[] = $aliased;
                }
            }
        }

        return $query
            ->orderByDesc('rr.receiving_report_id')
            ->orderBy('ri.receiving_report_item_id')
            ->get($select);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private static function hydrate(Collection $rows): Collection
    {
        $supplierIds = $rows->map(function ($row) {
            return (int) ($row->receiving_report_item_supplier_id
                ?? $row->receiving_report_supplier_id
                ?? 0);
        })->filter()->unique()->values();

        $suppliers = collect();
        if ($supplierIds->isNotEmpty() && Schema::hasTable('suppliers_table')) {
            $query = DB::table('suppliers_table as s')->whereIn('s.supplier_id', $supplierIds->all());

            if (Schema::hasTable('physical_suppliers_table')) {
                $query->leftJoin('physical_suppliers_table as ps', 'ps.supplier_id', '=', 's.supplier_id');
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $query->leftJoin('online_suppliers_table as os', 'os.supplier_id', '=', 's.supplier_id');
            }

            $nameExpr = "COALESCE("
                .(Schema::hasTable('physical_suppliers_table') ? 'ps.company_name, ' : '')
                .(Schema::hasTable('online_suppliers_table') ? 'os.shop_name, ' : '')
                ."'Unnamed supplier')";

            $suppliers = $query
                ->get([
                    's.supplier_id',
                    DB::raw($nameExpr.' as supplier_name'),
                ])
                ->keyBy('supplier_id');
        }

        $atpIds = $rows->map(fn ($row) => (int) ($row->receiving_report_atp_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        $rfcIds = $rows->map(fn ($row) => (int) ($row->receiving_report_request_check_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        $rfcToAtp = collect();
        if (
            $rfcIds->isNotEmpty()
            && Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', 'request_check_authority_purchase_id')
        ) {
            $rfcToAtp = DB::table('request_check_table')
                ->whereIn('request_check_id', $rfcIds->all())
                ->pluck('request_check_authority_purchase_id', 'request_check_id');
        }

        foreach ($rows as $row) {
            if (empty($row->receiving_report_atp_id) && ! empty($row->receiving_report_request_check_id)) {
                $resolved = (int) ($rfcToAtp[(int) $row->receiving_report_request_check_id] ?? 0);
                if ($resolved > 0) {
                    $row->receiving_report_atp_id = $resolved;
                    $atpIds->push($resolved);
                }
            }
        }
        $atpIds = $atpIds->filter()->unique()->values();

        $atpMeta = collect();
        if ($atpIds->isNotEmpty() && Schema::hasTable('authority_to_purchase_table')) {
            $atpCols = ['authority_purchase_id', 'authority_purchase_date'];
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_form_number')) {
                $atpCols[] = 'authority_purchase_form_number';
            }
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_supplier_id')) {
                $atpCols[] = 'authority_purchase_supplier_id';
            }
            $atpMeta = DB::table('authority_to_purchase_table')
                ->whereIn('authority_purchase_id', $atpIds->all())
                ->get($atpCols)
                ->keyBy('authority_purchase_id');
        }

        $poByAtp = collect();
        if (
            $atpIds->isNotEmpty()
            && Schema::hasTable('purchase_order_atps_table')
            && Schema::hasTable('purchase_orders_table')
        ) {
            $poByAtp = DB::table('purchase_order_atps_table as poa')
                ->join('purchase_orders_table as po', 'po.purchase_order_id', '=', 'poa.purchase_order_id')
                ->whereIn('poa.authority_purchase_id', $atpIds->all())
                ->where('po.purchase_order_status', '!=', PurchaseOrderBasket::STATUS_CANCELLED)
                ->get([
                    'poa.authority_purchase_id',
                    'po.purchase_order_id',
                    'po.purchase_order_number',
                ])
                ->keyBy('authority_purchase_id');
        }

        return $rows->map(function ($row) use ($suppliers, $atpMeta, $poByAtp) {
            $atpId = (int) ($row->receiving_report_atp_id ?? 0);
            $atp = $atpId > 0 ? $atpMeta->get($atpId) : null;
            $po = $atpId > 0 ? $poByAtp->get($atpId) : null;

            $supplierId = (int) ($row->receiving_report_item_supplier_id
                ?? $row->receiving_report_supplier_id
                ?? ($atp->authority_purchase_supplier_id ?? 0)
                ?? 0);
            $supplierName = trim((string) ($row->receiving_report_item_supplier_name ?? ''));
            if ($supplierName === '' && $supplierId > 0) {
                $supplierName = (string) ($suppliers->get($supplierId)->supplier_name ?? '');
            }

            $qty = (int) ($row->receiving_report_item_quantity ?? 0);
            $ordered = isset($row->receiving_report_item_ordered_qty) && $row->receiving_report_item_ordered_qty !== null
                ? (int) $row->receiving_report_item_ordered_qty
                : null;
            $unitPrice = isset($row->receiving_report_item_unit_price) && $row->receiving_report_item_unit_price !== null
                ? (float) $row->receiving_report_item_unit_price
                : null;
            $amount = isset($row->receiving_report_item_amount) && $row->receiving_report_item_amount !== null
                ? (float) $row->receiving_report_item_amount
                : null;
            $unitCost = $unitPrice;
            if ($unitCost === null && $amount !== null && $qty > 0) {
                $unitCost = round($amount / $qty, 2);
            }

            $purchaseDate = $row->receiving_report_date
                ?? $row->receiving_report_delivery_date
                ?? ($atp->authority_purchase_date ?? null);

            $article = trim((string) ($row->receiving_report_item_article ?? 'Received item'));
            $rrNumber = trim((string) ($row->receiving_report_form_number ?? ''));
            if ($rrNumber === '') {
                $rrNumber = 'RR #'.$row->receiving_report_id;
            }
            $poNumber = trim((string) ($po->purchase_order_number ?? ''));
            $atpNumber = trim((string) ($atp->authority_purchase_form_number ?? ''));
            $invoiceNo = trim((string) ($row->receiving_report_invoice_no ?? ''));
            $drNo = trim((string) ($row->receiving_report_dr_no ?? ''));
            $rrDate = $row->receiving_report_date ?? $row->receiving_report_delivery_date ?? null;

            return (object) [
                'receiving_report_item_id' => (int) $row->receiving_report_item_id,
                'receiving_report_id' => (int) $row->receiving_report_id,
                'rr_number' => $rrNumber,
                'po_number' => $poNumber !== '' ? $poNumber : null,
                'purchase_order_id' => $po ? (int) $po->purchase_order_id : null,
                'atp_number' => $atpNumber !== '' ? $atpNumber : null,
                'authority_purchase_id' => $atpId > 0 ? $atpId : null,
                'article' => $article,
                'quantity' => $qty,
                'ordered_qty' => $ordered,
                'unit' => $row->receiving_report_item_unit ?? null,
                'unit_cost' => $unitCost,
                'amount' => $amount,
                'condition' => $row->receiving_report_item_condition ?? null,
                'supplier_id' => $supplierId > 0 ? $supplierId : null,
                'supplier_name' => $supplierName !== '' ? $supplierName : null,
                'purchase_date' => $purchaseDate ? (string) $purchaseDate : null,
                'rr_date' => $rrDate ? (string) $rrDate : null,
                'invoice_no' => $invoiceNo !== '' ? $invoiceNo : null,
                'dr_no' => $drNo !== '' ? $drNo : null,
                'label' => '',
            ];
        })->values();
    }

    /**
     * Stocked quantity per RR line (equipment reverse FK and legacy RR→equipment FK).
     *
     * @param  array<int, int>  $itemIds
     * @return array<int, int>
     */
    public static function stockedQuantities(array $itemIds): array
    {
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
        $totals = array_fill_keys($itemIds, 0);
        if ($itemIds === [] || ! Schema::hasTable('equipment_table')) {
            return $totals;
        }

        $seenEquipment = [];

        if (Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
            $rows = DB::table('equipment_table')
                ->whereIn('equipment_receiving_report_item_id', $itemIds)
                ->get(['equipment_id', 'equipment_quantity', 'equipment_receiving_report_item_id']);
            foreach ($rows as $row) {
                $eqId = (int) $row->equipment_id;
                $itemId = (int) $row->equipment_receiving_report_item_id;
                if (! isset($totals[$itemId]) || isset($seenEquipment[$eqId])) {
                    continue;
                }
                $seenEquipment[$eqId] = true;
                $totals[$itemId] += max(1, (int) ($row->equipment_quantity ?? 1));
            }
        }

        if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
            $links = DB::table('receiving_report_items_table as ri')
                ->join('equipment_table as e', 'e.equipment_id', '=', 'ri.receiving_report_item_equipment_id')
                ->whereIn('ri.receiving_report_item_id', $itemIds)
                ->whereNotNull('ri.receiving_report_item_equipment_id')
                ->where('ri.receiving_report_item_equipment_id', '>', 0)
                ->get([
                    'ri.receiving_report_item_id',
                    'e.equipment_id',
                    'e.equipment_quantity',
                ]);
            foreach ($links as $row) {
                $eqId = (int) $row->equipment_id;
                $itemId = (int) $row->receiving_report_item_id;
                if (! isset($totals[$itemId]) || isset($seenEquipment[$eqId])) {
                    continue;
                }
                $seenEquipment[$eqId] = true;
                $totals[$itemId] += max(1, (int) ($row->equipment_quantity ?? 1));
            }
        }

        return $totals;
    }
}
