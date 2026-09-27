<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Funding for ATPs approved through a Purchase Order:
 * one payment path per PO, then
 *  - Cash Advance: one funding request for the whole PO;
 *  - Request for Check: one funding request per supplier (a check has one payee).
 */
class PurchaseOrderFunding
{
    public const KEY_PREFIX = 'po:';

    /**
     * Approved PO that the ATP was approved through, or null.
     */
    public static function approvedPoIdForAtp(int $atpId): ?int
    {
        $poId = PurchaseOrderBasket::poIdForAtp($atpId);
        if (! $poId) {
            return null;
        }

        $status = DB::table('purchase_orders_table')
            ->where('purchase_order_id', $poId)
            ->value('purchase_order_status');

        return $status === PurchaseOrderBasket::STATUS_APPROVED ? $poId : null;
    }

    /**
     * @param  array<int, int>  $atpIds
     * @return array<int, int> ATP ids that belong to an approved PO
     */
    public static function poFundedAtpIds(array $atpIds): array
    {
        $atpIds = array_values(array_unique(array_filter(array_map('intval', $atpIds))));
        if ($atpIds === [] || ! PurchaseOrderBasket::tablesExist()) {
            return [];
        }

        return DB::table('purchase_order_atps_table')
            ->join('purchase_orders_table', 'purchase_orders_table.purchase_order_id', '=', 'purchase_order_atps_table.purchase_order_id')
            ->where('purchase_orders_table.purchase_order_status', PurchaseOrderBasket::STATUS_APPROVED)
            ->whereIn('purchase_order_atps_table.authority_purchase_id', $atpIds)
            ->pluck('purchase_order_atps_table.authority_purchase_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function paymentPathForPo(int $poId): ?string
    {
        $paths = DB::table('authority_to_purchase_table')
            ->whereIn('authority_purchase_id', PurchaseOrderBasket::atpIdsForPo($poId))
            ->pluck('authority_purchase_payment_path')
            ->filter()
            ->unique()
            ->values();

        return $paths->count() === 1 ? (string) $paths->first() : null;
    }

    /**
     * Apply one payment path to every ATP on an approved PO. Returns an error message or null.
     */
    public static function setPaymentPath(int $poId, string $path): ?string
    {
        if (! ProcurementPaymentPath::isValid($path)) {
            return 'Choose Request for Check or Cash Advance.';
        }

        $order = DB::table('purchase_orders_table')->where('purchase_order_id', $poId)->first();
        if (! $order || (string) $order->purchase_order_status !== PurchaseOrderBasket::STATUS_APPROVED) {
            return 'Payment path can only be chosen after the Purchase Order is approved.';
        }

        $atpIds = PurchaseOrderBasket::atpIdsForPo($poId);
        if ($atpIds === []) {
            return 'This Purchase Order has no ATPs.';
        }

        if (RfcAtpLinks::fundedAtpIds($atpIds) !== []) {
            return 'Payment path cannot be changed after a funding request has been created for this Purchase Order.';
        }

        $update = [
            'authority_purchase_payment_path' => $path,
            'authority_purchase_updated_at' => now(),
        ];
        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_payment_path_chosen_at')) {
            $update['authority_purchase_payment_path_chosen_at'] = now();
        }

        DB::table('authority_to_purchase_table')
            ->whereIn('authority_purchase_id', $atpIds)
            ->where('authority_purchase_status', 'Approved')
            ->update($update);

        return null;
    }

    /**
     * Fundable groups from approved POs for the given funding type.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fundingGroups(string $fundingType, ?int $onlyPoId = null, ?int $ignoreRfcId = null): array
    {
        if (! PurchaseOrderBasket::tablesExist() || ! ProcurementPaymentPath::isValid($fundingType)) {
            return [];
        }

        $orders = DB::table('purchase_orders_table')
            ->where('purchase_order_status', PurchaseOrderBasket::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('purchase_order_is_archived')->orWhere('purchase_order_is_archived', 0);
            })
            ->when($onlyPoId, fn ($q) => $q->where('purchase_order_id', $onlyPoId))
            ->when(
                PurchaserDocumentAccess::isProcurementActor(),
                fn ($q) => $q->where(function ($inner) {
                    $inner->where('purchase_order_created_by', Auth::id())
                        ->orWhereNull('purchase_order_created_by');
                })
            )
            ->orderByDesc('purchase_order_id')
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $links = DB::table('purchase_order_atps_table')
            ->whereIn('purchase_order_id', $orders->pluck('purchase_order_id'))
            ->orderBy('purchase_order_atp_id')
            ->get()
            ->groupBy('purchase_order_id');

        $allAtpIds = $links->flatten(1)->pluck('authority_purchase_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($allAtpIds === []) {
            return [];
        }

        $funded = array_flip(RfcAtpLinks::fundedAtpIds($allAtpIds, $ignoreRfcId));
        $atps = self::atpRows($allAtpIds)->keyBy('authority_purchase_id');
        $totals = DB::table('authority_to_purchase_items_table')
            ->whereIn('authority_purchase_id', $allAtpIds)
            ->select('authority_purchase_id', DB::raw('SUM(atp_amount) as total'))
            ->groupBy('authority_purchase_id')
            ->pluck('total', 'authority_purchase_id');

        $requester = (string) (Auth::user()->user_full_name ?? '');
        $groups = [];

        foreach ($orders as $order) {
            $poLabel = PurchaseOrderBasket::displayNumber($order);
            $candidates = collect($links->get($order->purchase_order_id, []))
                ->map(fn ($link) => $atps->get((int) $link->authority_purchase_id))
                ->filter(fn ($atp) => $atp
                    && $atp->authority_purchase_status === 'Approved'
                    && empty($atp->authority_purchase_is_archived)
                    && ($atp->authority_purchase_payment_path ?? null) === $fundingType
                    && ! isset($funded[(int) $atp->authority_purchase_id]))
                ->values();

            if ($candidates->isEmpty()) {
                continue;
            }

            $buckets = $fundingType === ProcurementPaymentPath::CASH_ADVANCE
                ? ['' => $candidates]
                : $candidates->groupBy(fn ($atp) => (string) ($atp->authority_purchase_supplier_id ?? ''))->all();

            foreach ($buckets as $supplierId => $bucket) {
                $bucket = collect($bucket)->values();
                $atpIds = $bucket->pluck('authority_purchase_id')->map(fn ($id) => (int) $id)->all();
                $supplierName = PurchaseOrderBasket::supplierDisplay($bucket->first());
                $amount = round($bucket->sum(fn ($atp) => (float) ($totals[$atp->authority_purchase_id] ?? 0)), 2);
                $key = self::KEY_PREFIX.$order->purchase_order_id
                    .($fundingType === ProcurementPaymentPath::CASH_ADVANCE ? '' : ':s:'.($supplierId === '' ? '0' : $supplierId));

                $atpLabels = $bucket->map(fn ($atp) => PurchaseOrderBasket::atpListLabel($atp))->all();
                $purposes = $bucket
                    ->map(fn ($atp) => trim((string) ($atp->equipment_name ?? $atp->report_unlisted_equipment_name ?? $atp->ris_purpose_description ?? '')))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $groups[] = [
                    'key' => $key,
                    'purchase_order_id' => (int) $order->purchase_order_id,
                    'purchase_order_label' => $poLabel,
                    'supplier_id' => $supplierId === '' ? null : (int) $supplierId,
                    'supplier_name' => $supplierName,
                    'atp_ids' => $atpIds,
                    'atp_labels' => $atpLabels,
                    'label' => $poLabel
                        .($fundingType === ProcurementPaymentPath::CASH_ADVANCE ? ' · whole PO' : ' · '.$supplierName)
                        .' · '.count($atpIds).' ATP'.(count($atpIds) === 1 ? '' : 's'),
                    'payee' => $fundingType === ProcurementPaymentPath::CASH_ADVANCE ? $requester : $supplierName,
                    'amount' => $amount,
                    'purpose' => mb_substr(
                        $poLabel.' ('.implode(', ', $atpLabels).')'.($purposes !== [] ? ': '.implode('; ', $purposes) : ''),
                        0,
                        5000
                    ),
                ];
            }
        }

        return $groups;
    }

    /**
     * Re-derive a group from its key so the client cannot choose arbitrary ATPs.
     */
    public static function resolveGroup(string $key, string $fundingType, ?int $ignoreRfcId = null): ?array
    {
        if (! self::isGroupKey($key)) {
            return null;
        }

        preg_match('/^po:(\d+)/', $key, $m);
        foreach (self::fundingGroups($fundingType, (int) $m[1], $ignoreRfcId) as $group) {
            if ($group['key'] === $key) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Adds funding_path, funding_locked, funding_open_groups and funding_requests to approved orders.
     * Expects PurchaseOrderBasket::attachToOrders() to have run (linked_atps).
     */
    public static function attachFundingState($orders): void
    {
        foreach (collect($orders) as $order) {
            $order->funding_path = null;
            $order->funding_locked = false;
            $order->funding_open_groups = [];
            $order->funding_requests = [];

            if ((string) ($order->purchase_order_status ?? '') !== PurchaseOrderBasket::STATUS_APPROVED) {
                continue;
            }

            $linked = collect($order->linked_atps ?? []);
            $atpIds = $linked->pluck('authority_purchase_id')->map(fn ($id) => (int) $id)->all();
            if ($atpIds === []) {
                continue;
            }

            $paths = $linked->pluck('authority_purchase_payment_path')->filter()->unique()->values();
            $order->funding_path = $paths->count() === 1 ? (string) $paths->first() : null;

            $labels = $linked->mapWithKeys(fn ($atp) => [(int) $atp->authority_purchase_id => PurchaseOrderBasket::atpListLabel($atp)]);
            $rfcs = Schema::hasTable('request_check_table')
                ? DB::table('request_check_table')
                    ->where(function ($q) use ($atpIds) {
                        $q->whereIn('request_check_authority_purchase_id', $atpIds);
                        if (RfcAtpLinks::tableExists()) {
                            $q->orWhereIn('request_check_id', function ($sub) use ($atpIds) {
                                $sub->select('request_check_id')
                                    ->from('request_check_atps_table')
                                    ->whereIn('authority_purchase_id', $atpIds);
                            });
                        }
                    })
                    ->orderBy('request_check_id')
                    ->get()
                : collect();

            $linkMap = RfcAtpLinks::atpIdsForMany(
                $rfcs->mapWithKeys(fn ($rfc) => [(int) $rfc->request_check_id => (int) ($rfc->request_check_authority_purchase_id ?? 0) ?: null])->all()
            );

            $order->funding_requests = $rfcs->map(fn ($rfc) => (object) [
                'id' => (int) $rfc->request_check_id,
                'form_number' => $rfc->request_check_form_number ?? null,
                'status' => (string) $rfc->request_check_status,
                'funding_type' => $rfc->request_check_funding_type ?? null,
                'payee' => $rfc->request_check_payee ?? null,
                'amount' => $rfc->request_check_amount_figures,
                'atp_labels' => array_values(array_filter(array_map(
                    fn ($atpId) => $labels[$atpId] ?? null,
                    $linkMap[(int) $rfc->request_check_id] ?? []
                ))),
            ])->all();

            $order->funding_locked = RfcAtpLinks::fundedAtpIds($atpIds) !== [];

            if ($order->funding_path) {
                $order->funding_open_groups = self::fundingGroups($order->funding_path, (int) $order->purchase_order_id);
            }
        }
    }

    public static function isGroupKey(?string $key): bool
    {
        return is_string($key) && preg_match('/^po:\d+(?::s:\d+)?$/', $key) === 1;
    }

    /**
     * Error when a set of ATPs cannot be funded together by one request, else null.
     *
     * @param  array<int, int>  $atpIds
     */
    public static function groupConsistencyError(array $atpIds, string $fundingType): ?string
    {
        if (count($atpIds) < 2) {
            return null;
        }

        $poIds = DB::table('purchase_order_atps_table')
            ->join('purchase_orders_table', 'purchase_orders_table.purchase_order_id', '=', 'purchase_order_atps_table.purchase_order_id')
            ->where('purchase_orders_table.purchase_order_status', PurchaseOrderBasket::STATUS_APPROVED)
            ->whereIn('purchase_order_atps_table.authority_purchase_id', $atpIds)
            ->pluck('purchase_order_atps_table.purchase_order_id', 'purchase_order_atps_table.authority_purchase_id');

        if ($poIds->count() !== count($atpIds) || $poIds->unique()->count() !== 1) {
            return 'All ATPs on one funding request must belong to the same approved Purchase Order.';
        }

        if ($fundingType === ProcurementPaymentPath::REQUEST_FOR_CHECK) {
            $suppliers = DB::table('authority_to_purchase_table')
                ->whereIn('authority_purchase_id', $atpIds)
                ->pluck('authority_purchase_supplier_id')
                ->map(fn ($id) => (int) $id)
                ->unique();
            if ($suppliers->count() > 1) {
                return 'A Request for Check pays one supplier. Create one Request for Check per supplier on this Purchase Order.';
            }
        }

        return null;
    }

    private static function atpRows(array $atpIds)
    {
        return DB::table('authority_to_purchase_table')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->leftJoin('requisition_issue_slip_table', 'authority_to_purchase_table.authority_purchase_ris_id', '=', 'requisition_issue_slip_table.ris_id')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->whereIn('authority_to_purchase_table.authority_purchase_id', $atpIds)
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_status',
                'authority_to_purchase_table.authority_purchase_is_archived',
                'authority_to_purchase_table.authority_purchase_payment_path',
                'authority_to_purchase_table.authority_purchase_supplier_id',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name',
                'requisition_issue_slip_table.ris_purpose_description',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name'
            )
            ->get();
    }
}
