<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One ATP per supplier on an RIS.
 *
 * An RIS whose lines name two or more suppliers is "split": each supplier's
 * lines get their own ATP. An RIS with a single supplier (or none) keeps one
 * ATP for the whole RIS. ATPs saved before the split existed have
 * authority_purchase_ris_supplier_split = 0 and keep covering the whole RIS.
 */
class AtpRisSuppliers
{
    private static ?bool $hasSplitColumn = null;

    public static function hasSplitColumn(): bool
    {
        return self::$hasSplitColumn ??= Schema::hasTable('authority_to_purchase_table')
            && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_ris_supplier_split');
    }

    /**
     * Supplier groups per RIS.
     *
     * @param  iterable<int>  $risIds
     * @return array<int, array{split: bool, groups: array<int, Collection>}>
     *         groups keyed by supplier_id (0 = no supplier on a non-split RIS)
     */
    public static function groups(iterable $risIds): array
    {
        $risIds = collect($risIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($risIds->isEmpty()) {
            return [];
        }

        $headerSuppliers = DB::table('requisition_issue_slip_table')
            ->whereIn('ris_id', $risIds)
            ->pluck('ris_supplier_id', 'ris_id');

        $query = DB::table('requisition_issue_slip_items_table')
            ->whereIn('requisition_issue_slip_items_table.ris_id', $risIds)
            ->orderBy('requisition_issue_slip_items_table.ris_item_id');

        $select = ['requisition_issue_slip_items_table.*'];
        if (
            Schema::hasTable('uom_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')
        ) {
            $query->leftJoin('uom_table', 'uom_table.uom_id', '=', 'requisition_issue_slip_items_table.ris_item_uom_id');
            $select[] = 'uom_table.uom_name';
        }

        $itemsByRis = $query->select($select)->get()->groupBy('ris_id');

        $result = [];
        foreach ($risIds as $risId) {
            $items = collect($itemsByRis->get($risId, []));
            $header = (int) ($headerSuppliers[$risId] ?? 0);

            $supplierOf = function ($item) use ($header) {
                $line = (int) ($item->ris_item_supplier_id ?? 0);

                return $line > 0 ? $line : $header;
            };

            $distinct = $items->map($supplierOf)->filter()->unique()->values();

            if ($distinct->count() < 2) {
                $result[$risId] = [
                    'split' => false,
                    'groups' => [(int) ($distinct->first() ?? 0) => $items->values()],
                ];

                continue;
            }

            // Lines without a supplier ride with the first supplier's ATP.
            $first = (int) $distinct->first();
            $groups = [];
            foreach ($distinct as $supplierId) {
                $groups[(int) $supplierId] = collect();
            }
            foreach ($items as $item) {
                $supplierId = $supplierOf($item) ?: $first;
                $groups[$supplierId]->push($item);
            }

            $result[$risId] = ['split' => true, 'groups' => $groups];
        }

        return $result;
    }

    /**
     * What live ATPs (not rejected, not archived) already cover per RIS.
     *
     * @param  iterable<int>  $risIds
     * @return array<int, array{whole: bool, suppliers: array<int, true>}>
     */
    public static function coverage(iterable $risIds, ?int $exceptAtpId = null): array
    {
        $risIds = collect($risIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($risIds->isEmpty() || !Schema::hasTable('authority_to_purchase_table')) {
            return [];
        }

        $columns = ['authority_purchase_id', 'authority_purchase_ris_id', 'authority_purchase_supplier_id'];
        if (self::hasSplitColumn()) {
            $columns[] = 'authority_purchase_ris_supplier_split';
        }

        $rows = self::liveAtpsQuery()
            ->whereIn('authority_purchase_ris_id', $risIds)
            ->when($exceptAtpId, fn ($q) => $q->where('authority_purchase_id', '!=', $exceptAtpId))
            ->get($columns);

        $result = [];
        foreach ($rows as $row) {
            $risId = (int) $row->authority_purchase_ris_id;
            $result[$risId] ??= ['whole' => false, 'suppliers' => []];

            if (!(int) ($row->authority_purchase_ris_supplier_split ?? 0)) {
                $result[$risId]['whole'] = true;

                continue;
            }

            $supplierId = (int) ($row->authority_purchase_supplier_id ?? 0);
            if ($supplierId > 0) {
                $result[$risId]['suppliers'][$supplierId] = true;
            }
        }

        return $result;
    }

    /**
     * Supplier groups that still need an ATP.
     *
     * @return array<int, Collection> supplier_id => RIS lines
     */
    public static function openGroups(array $groupInfo, ?array $coverage): array
    {
        $coverage ??= ['whole' => false, 'suppliers' => []];

        if ($coverage['whole']) {
            return [];
        }

        if (!$groupInfo['split']) {
            return $coverage['suppliers'] === [] ? $groupInfo['groups'] : [];
        }

        return array_filter(
            $groupInfo['groups'],
            fn ($items, $supplierId) => !isset($coverage['suppliers'][$supplierId]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @param  iterable<int>  $risIds
     * @return array<int, array{total: int, open: int}> supplier groups per RIS
     */
    public static function progress(iterable $risIds): array
    {
        $groups = self::groups($risIds);
        $coverage = self::coverage(array_keys($groups));

        $result = [];
        foreach ($groups as $risId => $info) {
            $result[$risId] = [
                'total' => count($info['groups']),
                'open' => count(self::openGroups($info, $coverage[$risId] ?? null)),
            ];
        }

        return $result;
    }

    /**
     * Per-supplier ATP status for multi-supplier RISes (single-supplier RISes are omitted).
     *
     * @param  iterable<int>  $risIds
     * @return array<int, array<int, array{position: int, supplier_id: int, name: string, items: int, atp: object|null}>>
     */
    public static function breakdown(iterable $risIds): array
    {
        $groups = array_filter(self::groups($risIds), fn ($info) => $info['split']);
        if ($groups === []) {
            return [];
        }

        $names = self::supplierNames(collect($groups)->flatMap(fn ($info) => array_keys($info['groups'])));

        $columns = [
            'authority_purchase_id',
            'authority_purchase_ris_id',
            'authority_purchase_supplier_id',
            'authority_purchase_form_number',
            'authority_purchase_status',
            'authority_purchase_submitted_at',
            'authority_purchase_rejection_reason',
        ];
        if (self::hasSplitColumn()) {
            $columns[] = 'authority_purchase_ris_supplier_split';
        }

        $atpsByRis = self::liveAtpsQuery()
            ->whereIn('authority_purchase_ris_id', array_keys($groups))
            ->orderBy('authority_purchase_id')
            ->get($columns)
            ->groupBy('authority_purchase_ris_id');

        $result = [];
        foreach ($groups as $risId => $info) {
            $atps = collect($atpsByRis->get($risId, []));
            $whole = $atps->first(fn ($row) => !(int) ($row->authority_purchase_ris_supplier_split ?? 0));
            $position = 0;

            foreach ($info['groups'] as $supplierId => $items) {
                $result[$risId][] = [
                    'position' => ++$position,
                    'supplier_id' => (int) $supplierId,
                    'name' => $names[$supplierId] ?? 'Supplier #'.$supplierId,
                    'items' => $items->count(),
                    'atp' => $whole ?: $atps->first(
                        fn ($row) => (int) ($row->authority_purchase_ris_supplier_split ?? 0) === 1
                            && (int) $row->authority_purchase_supplier_id === (int) $supplierId
                    ),
                ];
            }
        }

        return $result;
    }

    /**
     * Validate the supplier chosen for a new or edited ATP on an RIS.
     *
     * @return array{split: bool, error: string|null}
     */
    public static function checkSupplierForRis(int $risId, ?int $supplierId, ?int $exceptAtpId = null): array
    {
        $info = self::groups([$risId])[$risId] ?? ['split' => false, 'groups' => []];
        $coverage = self::coverage([$risId], $exceptAtpId)[$risId] ?? null;
        $open = self::openGroups($info, $coverage);

        if (!$info['split']) {
            return [
                'split' => false,
                'error' => $open === [] ? 'An Authority to Purchase already exists for the selected RIS.' : null,
            ];
        }

        if (!$supplierId) {
            return ['split' => true, 'error' => 'This RIS buys from several suppliers. Pick which supplier this ATP is for.'];
        }

        if (!isset($info['groups'][$supplierId])) {
            return ['split' => true, 'error' => 'The selected supplier is not on this RIS. Each ATP must be for one of the RIS suppliers.'];
        }

        if (!isset($open[$supplierId])) {
            return ['split' => true, 'error' => 'An Authority to Purchase already exists for this supplier on the selected RIS.'];
        }

        return ['split' => true, 'error' => null];
    }

    /**
     * Supplier display names for the given ids (active or not).
     *
     * @param  iterable<int>  $supplierIds
     * @return array<int, string>
     */
    public static function supplierNames(iterable $supplierIds): array
    {
        $ids = collect($supplierIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->whereIn('suppliers_table.supplier_id', $ids)
            ->get([
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name',
            ])
            ->mapWithKeys(fn ($row) => [
                (int) $row->supplier_id => (string) (
                    $row->supplier_store_type === 'Online Store'
                        ? ($row->shop_name ?: 'Online supplier')
                        : ($row->company_name ?: 'Supplier')
                ),
            ])
            ->all();
    }

    private static function liveAtpsQuery()
    {
        return DB::table('authority_to_purchase_table')
            ->where(function ($q) {
                $q->whereNull('authority_purchase_is_archived')
                    ->orWhere('authority_purchase_is_archived', 0);
            })
            ->where('authority_purchase_status', '!=', 'Rejected');
    }
}
