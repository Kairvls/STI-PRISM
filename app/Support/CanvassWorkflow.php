<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CanvassWorkflow
{
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_AWAITING = 'Awaiting for review';
    public const STATUS_REVIEWING = 'Reviewing - Awaiting for approval';
    public const STATUS_RETURNED = 'Returned';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_CONVERTED = 'Converted to RIS';

    public const ITEM_PENDING = 'Pending';
    public const ITEM_APPROVED = 'Approved';
    public const ITEM_RETURNED = 'Returned';

    public const MIN_SUPPLIERS_PER_ITEM = 2;
    public const MAX_SUPPLIERS_PER_ITEM = 3;
    public const MAX_OFFERS_PER_SUPPLIER = 10;
    public const MAX_ITEMS = 30;
    public const MAX_ATTACHMENTS = 5;
    public const MAX_ATTACHMENT_KB = 2048; // 2 MB per file
    public const MAX_ATTACHMENTS_TOTAL_BYTES = 7340032; // 7 MB total

    public static function tablesExist(): bool
    {
        return Schema::hasTable('canvass_table')
            && Schema::hasTable('canvass_items_table')
            && Schema::hasTable('canvass_suppliers_table')
            && Schema::hasTable('canvass_offers_table');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_AWAITING,
            self::STATUS_REVIEWING,
            self::STATUS_RETURNED,
            self::STATUS_APPROVED,
            self::STATUS_CONVERTED,
        ];
    }

    public static function pendingReviewStatuses(): array
    {
        return [
            self::STATUS_AWAITING,
            self::STATUS_REVIEWING,
        ];
    }

    /**
     * Editable by Purchaser while Draft, Awaiting for review, or Returned.
     * Locked once School Admin starts reviewing, approves, or converts to RIS.
     */
    public static function canEdit(?object $canvass): bool
    {
        if (! $canvass || (int) ($canvass->canvass_is_archived ?? 0) === 1) {
            return false;
        }

        return in_array((string) ($canvass->canvass_status ?? ''), [
            self::STATUS_DRAFT,
            self::STATUS_AWAITING,
            self::STATUS_RETURNED,
        ], true);
    }

    public static function canReview(?object $canvass): bool
    {
        if (! $canvass || (int) ($canvass->canvass_is_archived ?? 0) === 1) {
            return false;
        }

        return in_array((string) ($canvass->canvass_status ?? ''), self::pendingReviewStatuses(), true);
    }

    public static function canConvertToRis(?object $canvass): bool
    {
        if (! $canvass || (int) ($canvass->canvass_is_archived ?? 0) === 1) {
            return false;
        }

        return (string) ($canvass->canvass_status ?? '') === self::STATUS_APPROVED
            && empty($canvass->canvass_ris_id);
    }

    public static function displayNumber(?object $canvass): string
    {
        if (! $canvass) {
            return 'Canvass';
        }

        $num = trim((string) ($canvass->canvass_number ?? ''));
        if ($num !== '') {
            return $num;
        }

        return 'Draft Canvass #'.(int) ($canvass->canvass_id ?? 0);
    }

    public static function allocateNumber(): string
    {
        for ($attempt = 0; $attempt < 15; $attempt++) {
            $candidate = 'CAN-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $exists = DB::table('canvass_table')->where('canvass_number', $candidate)->exists();
            if (! $exists) {
                return $candidate;
            }
        }

        return 'CAN-'.strtoupper(Str::random(8));
    }

    /**
     * Rounds up to whole offer units when an offer is sold in packs/boxes.
     */
    public static function computeOfferUnits(int $requestedQty, float $unitsPerPack): int
    {
        $qty = max(0, $requestedQty);
        if ($qty === 0) {
            return 0;
        }

        $pack = $unitsPerPack > 0 ? $unitsPerPack : 1.0;

        return (int) ceil($qty / $pack);
    }

    public static function computeOfferTotal(int $requestedQty, float $unitsPerPack, float $unitPrice): float
    {
        $offerUnits = self::computeOfferUnits($requestedQty, $unitsPerPack);

        return round($offerUnits * max(0.0, $unitPrice), 2);
    }

    public static function effectiveUnitCost(float $offerUnitPrice, float $unitsPerPack): float
    {
        $pack = $unitsPerPack > 0 ? $unitsPerPack : 1.0;

        return round(max(0.0, $offerUnitPrice) / $pack, 2);
    }

    /**
     * @return array{label: string, class: string}
     */
    public static function statusBadge(string $status): array
    {
        return match ($status) {
            self::STATUS_DRAFT => [
                'label' => 'Draft',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            ],
            self::STATUS_AWAITING => [
                'label' => 'Awaiting for review',
                'class' => 'bg-amber-400/90 text-slate-950 border-amber-400',
            ],
            self::STATUS_REVIEWING => [
                'label' => 'Reviewing - Awaiting for approval',
                'class' => 'bg-blue-600 text-white border-blue-600',
            ],
            self::STATUS_RETURNED => [
                'label' => 'Returned for revision',
                'class' => 'bg-orange-100 text-orange-800 border-orange-200',
            ],
            self::STATUS_APPROVED => [
                'label' => 'Approved',
                'class' => 'bg-emerald-600 text-white border-emerald-600',
            ],
            self::STATUS_CONVERTED => [
                'label' => 'Converted to RIS',
                'class' => 'bg-teal-600 text-white border-teal-600',
            ],
            default => [
                'label' => $status !== '' ? $status : 'Draft',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            ],
        };
    }

    /**
     * Hydrate a canvass with its items, suppliers, brand offers, insights, and attachments.
     *
     * @return array{
     *   canvass: object,
     *   items: Collection,
     *   attachments: Collection,
     *   lowest_total_sum: float,
     *   approved_total_sum: float
     * }|null
     */
    public static function loadFull(int $canvassId): ?array
    {
        if (! self::tablesExist()) {
            return null;
        }

        $canvass = DB::table('canvass_table as c')
            ->leftJoin('users_table as owner', 'owner.user_id', '=', 'c.canvass_owner_user_id')
            ->leftJoin('users_table as reviewer', 'reviewer.user_id', '=', 'c.canvass_reviewed_by')
            ->leftJoin('requisition_issue_slip_table as ris', 'ris.ris_id', '=', 'c.canvass_ris_id')
            ->where('c.canvass_id', $canvassId)
            ->select([
                'c.*',
                'owner.user_full_name as owner_name',
                'reviewer.user_full_name as reviewer_name',
                'ris.ris_form_number as linked_ris_number',
                'ris.ris_status as linked_ris_status',
            ])
            ->first();

        if (! $canvass) {
            return null;
        }

        $rawItems = DB::table('canvass_items_table as ci')
            ->leftJoin('items_table as it', 'it.item_id', '=', 'ci.item_id')
            ->leftJoin('uom_table as u', 'u.uom_id', '=', 'ci.uom_id')
            ->where('ci.canvass_id', $canvassId)
            ->orderBy('ci.canvass_item_sort_order')
            ->orderBy('ci.canvass_item_id')
            ->select([
                'ci.*',
                'it.item_name as catalog_item_name',
                'u.uom_name',
            ])
            ->get();

        $itemIds = $rawItems->pluck('canvass_item_id')->all();

        $rawSuppliers = $itemIds === []
            ? collect()
            : DB::table('canvass_suppliers_table as cs')
                ->leftJoin('suppliers_table as s', 's.supplier_id', '=', 'cs.supplier_id')
                ->leftJoin('physical_suppliers_table as ps', 'ps.supplier_id', '=', 's.supplier_id')
                ->leftJoin('online_suppliers_table as os', 'os.supplier_id', '=', 's.supplier_id')
                ->whereIn('cs.canvass_item_id', $itemIds)
                ->orderBy('cs.canvass_supplier_sort_order')
                ->orderBy('cs.canvass_supplier_id')
                ->select([
                    'cs.*',
                    's.supplier_store_type',
                    's.supplier_is_blacklisted',
                    's.supplier_blacklist_reason',
                    DB::raw('COALESCE(ps.company_name, os.shop_name, CONCAT("Supplier #", cs.supplier_id)) as supplier_display_name'),
                ])
                ->get();

        $supplierRowIds = $rawSuppliers->pluck('canvass_supplier_id')->all();

        $rawOffers = $supplierRowIds === []
            ? collect()
            : DB::table('canvass_offers_table as co')
                ->leftJoin('brands_table as b', 'b.brand_id', '=', 'co.brand_id')
                ->leftJoin('uom_table as ou', 'ou.uom_id', '=', 'co.offer_uom_id')
                ->whereIn('co.canvass_supplier_id', $supplierRowIds)
                ->orderBy('co.offer_sort_order')
                ->orderBy('co.canvass_offer_id')
                ->select([
                    'co.*',
                    'b.brand_name',
                    'ou.uom_name as offer_uom_name',
                ])
                ->get();

        $supplierInsights = [];
        try {
            $supplierInsights = SupplierDeliveryInsights::bySupplierId(24);
        } catch (\Throwable) {
            $supplierInsights = [];
        }

        $brandInsights = [];
        try {
            if (class_exists(BrandDefectInsights::class) && method_exists(BrandDefectInsights::class, 'byBrandId')) {
                $brandInsights = BrandDefectInsights::byBrandId();
            }
        } catch (\Throwable) {
            $brandInsights = [];
        }

        $offersBySupplier = $rawOffers->groupBy('canvass_supplier_id');
        $suppliersByItem = $rawSuppliers->groupBy('canvass_item_id');

        $allocationsByItem = ($itemIds !== [] && Schema::hasTable('canvass_item_allocations_table'))
            ? DB::table('canvass_item_allocations_table')
                ->whereIn('canvass_item_id', $itemIds)
                ->get()
                ->groupBy('canvass_item_id')
            : collect();

        $lowestTotalSum = 0.0;
        $approvedTotalSum = 0.0;

        $items = $rawItems->map(function ($item) use (
            $suppliersByItem,
            $offersBySupplier,
            $allocationsByItem,
            $supplierInsights,
            $brandInsights,
            &$lowestTotalSum,
            &$approvedTotalSum
        ) {
            $qty = max(1, (int) $item->canvass_item_quantity);
            $flatOffers = collect();
            $itemAllocations = ($allocationsByItem->get($item->canvass_item_id) ?? collect())
                ->keyBy('canvass_offer_id');

            $suppliers = ($suppliersByItem->get($item->canvass_item_id) ?? collect())->map(function ($sup) use (
                $offersBySupplier,
                $itemAllocations,
                $qty,
                $item,
                $supplierInsights,
                $brandInsights,
                &$flatOffers
            ) {
                $supInsight = $supplierInsights[(int) $sup->supplier_id] ?? null;
                $sup->caution_risk = $supInsight->risk ?? null;
                $sup->caution_issues = (int) ($supInsight->issue_count ?? 0);

                $offers = ($offersBySupplier->get($sup->canvass_supplier_id) ?? collect())->map(function ($offer) use (
                    $qty,
                    $item,
                    $itemAllocations,
                    $sup,
                    $brandInsights,
                    &$flatOffers
                ) {
                    $unitsPerPack = max(0.01, (float) ($offer->offer_units_per_pack ?? 1));
                    $unitPrice = max(0.0, (float) ($offer->offer_unit_price ?? 0));
                    $offerUnits = self::computeOfferUnits($qty, $unitsPerPack);
                    $total = self::computeOfferTotal($qty, $unitsPerPack, $unitPrice);
                    $alloc = $itemAllocations->get($offer->canvass_offer_id);

                    $offer->computed_offer_units = $offerUnits;
                    $offer->computed_total = $total;
                    $offer->effective_unit_cost = self::effectiveUnitCost($unitPrice, $unitsPerPack);
                    $offer->supplier_id = (int) $sup->supplier_id;
                    $offer->supplier_display_name = (string) $sup->supplier_display_name;
                    $offer->supplier_is_blacklisted = (bool) ($sup->supplier_is_blacklisted ?? false);
                    $offer->supplier_caution_risk = $sup->caution_risk;
                    $offer->is_selected = $alloc !== null
                        || (int) ($item->canvass_item_selected_offer_id ?? 0) === (int) $offer->canvass_offer_id;
                    $offer->approved_offer_units = $alloc ? (int) $alloc->approved_offer_units : ($offer->is_selected ? $offerUnits : 0);
                    $offer->approved_amount = $alloc ? (float) $alloc->approved_amount : ($offer->is_selected ? $total : 0.0);

                    $bInsight = $brandInsights[(int) $offer->brand_id] ?? null;
                    $offer->brand_avoid = (bool) ($bInsight->is_avoided ?? false);
                    $offer->brand_defect_count = (int) ($bInsight->defect_count ?? 0);

                    $flatOffers->push($offer);

                    return $offer;
                })->values();

                $sup->offers = $offers;

                return $sup;
            })->values();

            $sortedOffers = $flatOffers->sortBy('computed_total')->values();
            $lowestPrice = $sortedOffers->isNotEmpty() ? (float) $sortedOffers->first()->computed_total : null;

            foreach ($sortedOffers as $offer) {
                $offer->is_lowest = $lowestPrice !== null && abs((float) $offer->computed_total - $lowestPrice) < 0.005;
            }

            if ($lowestPrice !== null) {
                $lowestTotalSum += $lowestPrice;
            }

            $selectedOffers = $sortedOffers->filter(fn ($o) => $o->is_selected)->values();
            $selectedOffer = $selectedOffers->first();
            foreach ($selectedOffers as $so) {
                $approvedTotalSum += (float) $so->approved_amount;
            }

            $item->suppliers = $suppliers;
            $item->comparison_offers = $sortedOffers;
            $item->lowest_total = $lowestPrice;
            $item->selected_offer = $selectedOffer;
            $item->selected_offers = $selectedOffers;

            return $item;
        })->values();

        $attachments = Schema::hasTable('canvass_attachments_table')
            ? DB::table('canvass_attachments_table')
                ->where('canvass_id', $canvassId)
                ->orderBy('canvass_attachment_id')
                ->get()
            : collect();

        return [
            'canvass' => $canvass,
            'items' => $items,
            'attachments' => $attachments,
            'lowest_total_sum' => round($lowestTotalSum, 2),
            'approved_total_sum' => round($approvedTotalSum, 2),
        ];
    }

    /**
     * Build the RIS create-modal prefill payload from an approved canvass.
     *
     * @return array{
     *   canvass_id: int,
     *   canvass_number: string,
     *   procurement_request_id: ?int,
     *   purpose: string,
     *   urgency: string,
     *   purchaser_signature: ?string,
     *   items: list<array<string, mixed>>
     * }|null
     */
    public static function risPrefillPayload(int $canvassId): ?array
    {
        $loaded = self::loadFull($canvassId);
        if (! $loaded) {
            return null;
        }

        $canvass = $loaded['canvass'];
        if (! self::canConvertToRis($canvass)) {
            return null;
        }

        $risItems = [];
        foreach ($loaded['items'] as $item) {
            $offer = $item->selected_offer;
            if (! $offer) {
                continue;
            }

            $qty = max(1, (int) $item->canvass_item_quantity);
            $brandId = (string) $offer->brand_id;
            $supplierId = (string) $offer->supplier_id;

            $risItems[] = [
                'name_description' => (string) ($item->canvass_item_name ?: $item->catalog_item_name),
                'brand_ids' => $brandId !== '' ? [$brandId] : [],
                'brand_qtys' => $brandId !== '' ? [$brandId => $qty] : [],
                'brand_id' => $brandId,
                'supplier_ids' => $supplierId !== '' ? [$supplierId] : [],
                'supplier_id' => $supplierId,
                'uom_id' => (string) ($item->uom_id ?? ''),
                'quantity_requested' => $qty,
                'quantity_issued' => '',
                'unit_cost' => number_format((float) $offer->effective_unit_cost, 2, '.', ''),
            ];
        }

        if ($risItems === []) {
            return null;
        }

        return [
            'canvass_id' => (int) $canvass->canvass_id,
            'canvass_number' => self::displayNumber($canvass),
            'procurement_request_id' => $canvass->canvass_procurement_request_id
                ? (int) $canvass->canvass_procurement_request_id
                : null,
            'purpose' => (string) ($canvass->canvass_purpose ?? ''),
            'urgency' => RisWorkflow::normalizeUrgency($canvass->canvass_urgency ?? null),
            'purchaser_signature' => filled($canvass->canvass_purchaser_signature ?? null)
                ? (string) $canvass->canvass_purchaser_signature
                : null,
            'items' => $risItems,
        ];
    }

    public static function pendingReviewCount(): int
    {
        if (! self::tablesExist()) {
            return 0;
        }

        return (int) DB::table('canvass_table')
            ->where('canvass_is_archived', 0)
            ->whereIn('canvass_status', self::pendingReviewStatuses())
            ->count();
    }

    public static function purchaserAttentionCount(?int $userId = null): int
    {
        if (! self::tablesExist()) {
            return 0;
        }

        $q = DB::table('canvass_table')
            ->where('canvass_is_archived', 0)
            ->whereIn('canvass_status', [self::STATUS_RETURNED, self::STATUS_APPROVED])
            ->whereNull('canvass_ris_id');

        if ($userId && ! RoleAccess::isAdmin()) {
            $q->where(function ($sub) use ($userId) {
                $sub->where('canvass_owner_user_id', $userId)
                    ->orWhereNull('canvass_owner_user_id');
            });
        }

        return (int) $q->count();
    }

    /**
     * Auto-create the Directly Approved RIS record AND per-supplier ATP draft records
     * once every item in a Canvass has been approved by the School Administrator.
     *
     * @return array{ris_id: int, ris_number: string, atp_ids: list<int>}
     */
    public static function createRisAndAtpsFromApprovedCanvass(int $canvassId, int $adminUserId): array
    {
        $loaded = self::loadFull($canvassId);
        if (! $loaded) {
            return ['ris_id' => 0, 'ris_number' => '', 'atp_ids' => []];
        }

        $canvass = $loaded['canvass'];
        if (! empty($canvass->canvass_ris_id)) {
            return [
                'ris_id' => (int) $canvass->canvass_ris_id,
                'ris_number' => (string) ($canvass->linked_ris_number ?? ''),
                'atp_ids' => [],
            ];
        }

        $now = now();
        $today = $now->toDateString();
        $ownerId = (int) ($canvass->canvass_owner_user_id ?: $adminUserId);
        $ownerName = trim((string) ($canvass->owner_name ?? ''))
            ?: (string) (DB::table('users_table')->where('user_id', $ownerId)->value('user_full_name') ?: 'Purchaser');
        $adminName = (string) (DB::table('users_table')->where('user_id', $adminUserId)->value('user_full_name') ?: 'School Administrator');

        $formNumber = RisWorkflow::allocateFormNumberOnSubmit();
        $requestNo = filled($canvass->canvass_request_no ?? null)
            ? (string) $canvass->canvass_request_no
            : ProcurementRequestNumber::allocate();

        $firstItemName = (string) ($loaded['items']->first()?->canvass_item_name ?? '');
        $manualTitle = trim($firstItemName ?: (string) ($canvass->canvass_purpose ?? ''));
        $manualTitle = $manualTitle !== '' ? mb_substr($manualTitle, 0, 255) : null;

        $adminSig = trim((string) ($canvass->canvass_admin_signature ?? ''));
        $purchaserSig = trim((string) ($canvass->canvass_purchaser_signature ?? ''));

        $risPayload = [
            'ris_procurement_request_id' => $canvass->canvass_procurement_request_id ?: null,
            'ris_form_number' => $formNumber,
            'ris_supplier_id' => null,
            'ris_purpose_description' => $canvass->canvass_purpose ?: null,
            'ris_status' => RisWorkflow::DIRECTLY_APPROVED,
            'ris_requested_by_signature' => $ownerName,
            'ris_requested_by_date' => $canvass->canvass_submitted_at
                ? \Carbon\Carbon::parse($canvass->canvass_submitted_at)->toDateString()
                : $today,
            'ris_approved_by_signature' => $adminSig !== '' ? $adminSig : $adminName,
            'ris_approved_by_date' => $today,
            'ris_issued_by_signature' => $adminSig !== '' ? $adminSig : $adminName,
            'ris_issued_by_date' => $today,
            'ris_received_by_signature' => null,
            'ris_received_by_date' => null,
            'ris_submitted_by' => $ownerId,
            'ris_submitted_at' => $canvass->canvass_submitted_at ?: $now,
            'ris_created_at' => $now,
            'ris_updated_at' => $now,
        ];

        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_canvass_id')) {
            $risPayload['ris_canvass_id'] = $canvassId;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_type')) {
            $risPayload['ris_request_type'] = RisWorkflow::requestType(
                $canvass->canvass_procurement_request_id ? (int) $canvass->canvass_procurement_request_id : null
            );
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_urgency')) {
            $risPayload['ris_urgency'] = RisWorkflow::normalizeUrgency($canvass->canvass_urgency ?? null);
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_manual_title')) {
            $risPayload['ris_manual_title'] = $manualTitle;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_created_by')) {
            $risPayload['ris_created_by'] = $ownerId;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            $risPayload['ris_request_no'] = $requestNo;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_checked_by_signature')) {
            $risPayload['ris_checked_by_signature'] = $adminSig !== '' ? $adminSig : $adminName;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_checked_by_date')) {
            $risPayload['ris_checked_by_date'] = $today;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_approved_by_name')) {
            $risPayload['ris_approved_by_name'] = $adminName;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approved_at')) {
            $risPayload['ris_direct_approved_at'] = $now;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approved_by')) {
            $risPayload['ris_direct_approved_by'] = $adminUserId;
        }

        $risId = (int) DB::table('requisition_issue_slip_table')->insertGetId($risPayload);

        if ($purchaserSig !== '') {
            RisWorkflow::storeRequestedBySignature($risId, $purchaserSig);
        }

        // Insert RIS item rows & group lines per supplier for per-supplier ATP drafts.
        $hasUomCol = Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id');
        $hasBrandCol = Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id');
        $hasBrandIdsCol = Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_ids');
        $hasBrandQtyCol = Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_quantities');
        $hasSupCol = Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id');
        $hasSupIdsCol = Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_ids');

        $risItemRows = [];
        $atpLinesBySupplier = [];

        foreach ($loaded['items'] as $item) {
            $itemName = (string) ($item->canvass_item_name ?: $item->catalog_item_name);
            $selectedOffers = $item->selected_offers ?? collect();
            foreach ($selectedOffers as $offer) {
                $approvedUnits = max(1, (int) ($offer->approved_offer_units ?? $offer->computed_offer_units ?? 1));
                $offerPrice = round((float) ($offer->offer_unit_price ?? 0), 2);
                $supId = (int) $offer->supplier_id;
                $brandId = (int) $offer->brand_id;
                $uomId = $offer->offer_uom_id ? (int) $offer->offer_uom_id : ($item->uom_id ? (int) $item->uom_id : null);
                $uomLabel = (string) ($offer->offer_uom_name ?: ($item->uom_name ?: 'Unit'));

                $row = [
                    'ris_id' => $risId,
                    'ris_item_name_description' => $itemName,
                    'ris_quantity_requested' => $approvedUnits,
                    'ris_quantity_issued' => $approvedUnits,
                    'ris_unit_cost' => $offerPrice,
                ];
                if ($hasUomCol) {
                    $row['ris_item_uom_id'] = $uomId;
                }
                if ($hasBrandCol) {
                    $row['ris_item_brand_id'] = $brandId ?: null;
                }
                if ($hasBrandIdsCol) {
                    $row['ris_item_brand_ids'] = $brandId ? json_encode([$brandId]) : null;
                }
                if ($hasBrandQtyCol) {
                    $row['ris_item_brand_quantities'] = $brandId ? json_encode([(string) $brandId => $approvedUnits]) : null;
                }
                if ($hasSupCol) {
                    $row['ris_item_supplier_id'] = $supId ?: null;
                }
                if ($hasSupIdsCol) {
                    $row['ris_item_supplier_ids'] = $supId ? json_encode([$supId]) : null;
                }
                $risItemRows[] = $row;

                $desc = $itemName;
                if (! empty($offer->brand_name)) {
                    $desc .= ' ('.$offer->brand_name.')';
                }
                $atpLinesBySupplier[$supId][] = [
                    'description' => $desc,
                    'quantity' => $approvedUnits,
                    'unit' => $uomLabel,
                    'unit_price' => $offerPrice,
                ];
            }
        }

        if ($risItemRows !== []) {
            DB::table('requisition_issue_slip_items_table')->insert($risItemRows);
        }

        // Copy supporting canvass attachments onto the RIS
        if (
            Schema::hasTable('canvass_attachments_table')
            && Schema::hasTable('ris_attachments_table')
        ) {
            foreach ($loaded['attachments'] as $att) {
                DB::table('ris_attachments_table')->insert([
                    'ris_id' => $risId,
                    'ris_attachment_original_name' => $att->canvass_attachment_original_name,
                    'ris_attachment_stored_path' => $att->canvass_attachment_stored_path,
                    'ris_attachment_mime_type' => $att->canvass_attachment_mime_type,
                    'ris_attachment_size' => (int) ($att->canvass_attachment_size ?? 0),
                    'ris_attachment_uploaded_by' => $att->canvass_attachment_uploaded_by ?? $ownerId,
                    'ris_attachment_created_at' => $now,
                ]);
            }
        }

        // Auto-create one per-supplier ATP draft for every winning supplier
        $atpIds = [];
        if (
            $atpLinesBySupplier !== []
            && Schema::hasTable('authority_to_purchase_table')
            && Schema::hasTable('authority_to_purchase_items_table')
        ) {
            $isSplit = count($atpLinesBySupplier) > 1;
            $hasSplitCol = AtpRisSuppliers::hasSplitColumn();
            $hasAtpReqNo = Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no');

            foreach ($atpLinesBySupplier as $supId => $lines) {
                $atpPayload = [
                    'authority_purchase_ris_id' => $risId,
                    'authority_purchase_form_number' => null,
                    'authority_purchase_supplier_id' => $supId > 0 ? $supId : null,
                    'authority_purchase_date' => $today,
                    'authority_purchase_received_by_name' => null,
                    'authority_purchase_reference_po_no' => null,
                    'authority_purchase_status' => 'Pending',
                    'authority_purchase_created_by' => $ownerId,
                    'authority_purchase_is_archived' => 0,
                    'authority_purchase_created_at' => $now,
                    'authority_purchase_updated_at' => $now,
                    'authority_purchase_submitted_by' => null,
                    'authority_purchase_submitted_at' => null,
                ];
                if ($hasSplitCol) {
                    $atpPayload['authority_purchase_ris_supplier_split'] = $isSplit ? 1 : 0;
                }
                if ($hasAtpReqNo) {
                    $atpPayload['authority_purchase_request_no'] = $requestNo;
                }

                $atpId = (int) DB::table('authority_to_purchase_table')->insertGetId($atpPayload);
                $atpIds[] = $atpId;

                $atpItems = [];
                foreach ($lines as $line) {
                    $atpItems[] = [
                        'authority_purchase_id' => $atpId,
                        'atp_description' => $line['description'],
                        'atp_quantity' => $line['quantity'],
                        'atp_unit' => $line['unit'],
                        'atp_unit_price' => $line['unit_price'],
                        'atp_amount' => round($line['quantity'] * $line['unit_price'], 2),
                    ];
                }
                if ($atpItems !== []) {
                    DB::table('authority_to_purchase_items_table')->insert($atpItems);
                }
            }
        }

        // Link Canvass to the newly created RIS
        DB::table('canvass_table')
            ->where('canvass_id', $canvassId)
            ->update([
                'canvass_ris_id' => $risId,
                'canvass_status' => self::STATUS_APPROVED,
                'canvass_updated_at' => $now,
            ]);

        return [
            'ris_id' => $risId,
            'ris_number' => $formNumber,
            'atp_ids' => $atpIds,
        ];
    }
}
