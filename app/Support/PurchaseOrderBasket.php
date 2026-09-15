<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseOrderBasket
{
    public const MAX_ATPS = 8;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_SUBMITTED = 'Submitted';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public const STATUS_CANCELLED = 'Cancelled';

    public static function tablesExist(): bool
    {
        return Schema::hasTable('purchase_orders_table')
            && Schema::hasTable('purchase_order_atps_table');
    }

    public static function attachEligibleDraftAtps(int $createdBy): array
    {
        if (! self::tablesExist() || $createdBy < 1) {
            return [];
        }

        $eligibleIds = self::eligibleDraftAtpIds($createdBy);
        $touched = [];

        foreach ($eligibleIds as $atpId) {
            $poId = self::attachAtp((int) $atpId, $createdBy);
            if ($poId > 0) {
                $touched[$poId] = $poId;
            }
        }

        return array_values($touched);
    }

    public static function attachAtp(int $atpId, int $createdBy): int
    {
        if (! self::tablesExist() || $atpId < 1 || $createdBy < 1) {
            return 0;
        }

        if (self::poIdForAtp($atpId)) {
            return (int) self::poIdForAtp($atpId);
        }

        if (! self::isEligibleDraftAtp($atpId, $createdBy)) {
            return 0;
        }

        $poId = self::resolveOpenPoId($createdBy);
        if ($poId < 1) {
            return 0;
        }

        if (self::openSlots($poId) < 1) {
            $poId = self::createOrder($createdBy);
        }

        if ($poId < 1 || self::openSlots($poId) < 1) {
            return 0;
        }

        DB::table('purchase_order_atps_table')->insert([
            'purchase_order_id' => $poId,
            'authority_purchase_id' => $atpId,
            'created_at' => now(),
        ]);

        DB::table('purchase_orders_table')
            ->where('purchase_order_id', $poId)
            ->update(['purchase_order_updated_at' => now()]);

        return $poId;
    }

    public static function detachAtp(int $atpId, ?int $poId = null): bool
    {
        if (! self::tablesExist() || $atpId < 1) {
            return false;
        }

        $query = DB::table('purchase_order_atps_table')
            ->where('authority_purchase_id', $atpId);

        if ($poId) {
            $query->where('purchase_order_id', $poId);
        }

        $link = $query->first();
        if (! $link) {
            return false;
        }

        $order = DB::table('purchase_orders_table')
            ->where('purchase_order_id', $link->purchase_order_id)
            ->first();

        if (! $order || ! in_array((string) ($order->purchase_order_status ?? ''), [
            self::STATUS_DRAFT,
            self::STATUS_CANCELLED,
        ], true)) {
            return false;
        }

        DB::table('purchase_order_atps_table')
            ->where('purchase_order_atp_id', $link->purchase_order_atp_id)
            ->delete();

        DB::table('purchase_orders_table')
            ->where('purchase_order_id', $link->purchase_order_id)
            ->update(['purchase_order_updated_at' => now()]);

        return true;
    }

    public static function resolveOpenPoId(int $createdBy): int
    {
        $openId = self::latestOpenDraftId($createdBy);
        if ($openId) {
            return $openId;
        }

        return self::createOrder($createdBy);
    }

    public static function createOrder(int $createdBy): int
    {
        if (! self::tablesExist() || $createdBy < 1) {
            return 0;
        }

        return (int) DB::table('purchase_orders_table')->insertGetId([
            'purchase_order_number' => null,
            'purchase_order_status' => self::STATUS_DRAFT,
            'purchase_order_created_by' => $createdBy,
            'purchase_order_is_archived' => 0,
            'purchase_order_created_at' => now(),
            'purchase_order_updated_at' => now(),
        ]);
    }

    public static function openSlots(int $poId): int
    {
        return max(0, self::MAX_ATPS - self::atpCount($poId));
    }

    public static function atpCount(int $poId): int
    {
        if (! self::tablesExist() || $poId < 1) {
            return 0;
        }

        return (int) DB::table('purchase_order_atps_table')
            ->where('purchase_order_id', $poId)
            ->count();
    }

    public static function poIdForAtp(int $atpId): ?int
    {
        if (! self::tablesExist() || $atpId < 1) {
            return null;
        }

        $id = DB::table('purchase_order_atps_table')
            ->where('authority_purchase_id', $atpId)
            ->value('purchase_order_id');

        return $id ? (int) $id : null;
    }

    public static function atpIdsForPo(int $poId): array
    {
        if (! self::tablesExist() || $poId < 1) {
            return [];
        }

        return DB::table('purchase_order_atps_table')
            ->where('purchase_order_id', $poId)
            ->orderBy('purchase_order_atp_id')
            ->pluck('authority_purchase_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>|iterable  $orders
     */
    public static function attachToOrders($orders): void
    {
        $orders = collect($orders);
        if ($orders->isEmpty() || ! self::tablesExist()) {
            return;
        }

        $poIds = $orders->pluck('purchase_order_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($poIds->isEmpty()) {
            return;
        }

        $links = DB::table('purchase_order_atps_table')
            ->whereIn('purchase_order_id', $poIds)
            ->orderBy('purchase_order_atp_id')
            ->get()
            ->groupBy('purchase_order_id');

        $atpIds = $links->flatten(1)->pluck('authority_purchase_id')->unique()->values();
        $atps = $atpIds->isEmpty()
            ? collect()
            : DB::table('authority_to_purchase_table')
                ->leftJoin(
                    'suppliers_table',
                    'authority_to_purchase_table.authority_purchase_supplier_id',
                    '=',
                    'suppliers_table.supplier_id'
                )
                ->leftJoin(
                    'physical_suppliers_table',
                    'suppliers_table.supplier_id',
                    '=',
                    'physical_suppliers_table.supplier_id'
                )
                ->leftJoin(
                    'online_suppliers_table',
                    'suppliers_table.supplier_id',
                    '=',
                    'online_suppliers_table.supplier_id'
                )
                ->leftJoin(
                    'requisition_issue_slip_table',
                    'authority_to_purchase_table.authority_purchase_ris_id',
                    '=',
                    'requisition_issue_slip_table.ris_id'
                )
                ->whereIn('authority_to_purchase_table.authority_purchase_id', $atpIds)
                ->select(
                    'authority_to_purchase_table.*',
                    'requisition_issue_slip_table.ris_form_number',
                    'suppliers_table.supplier_store_type',
                    'physical_suppliers_table.company_name',
                    'online_suppliers_table.shop_name'
                )
                ->get()
                ->keyBy('authority_purchase_id');

        $totals = $atpIds->isEmpty()
            ? collect()
            : DB::table('authority_to_purchase_items_table')
                ->whereIn('authority_purchase_id', $atpIds)
                ->select('authority_purchase_id', DB::raw('SUM(atp_amount) as total_amount'))
                ->groupBy('authority_purchase_id')
                ->pluck('total_amount', 'authority_purchase_id');

        foreach ($orders as $order) {
            $poId = (int) ($order->purchase_order_id ?? 0);
            $poLinks = $links->get($poId, collect());
            $lineAtps = $poLinks->map(function ($link) use ($atps, $totals) {
                $atp = $atps->get($link->authority_purchase_id);
                if (! $atp) {
                    return null;
                }
                $atp->po_total_amount = (float) ($totals[$atp->authority_purchase_id] ?? 0);
                $atp->supplier_display = self::supplierDisplay($atp);

                return $atp;
            })->filter()->values();

            $order->linked_atps = $lineAtps;
            $order->atp_count = $lineAtps->count();
            $order->open_slots = max(0, self::MAX_ATPS - $lineAtps->count());
            $first = $lineAtps->first();
            $order->atp_display = $first
                ? trim((string) ($first->authority_purchase_form_number ?: ('ATP #'.$first->authority_purchase_id)))
                    .($lineAtps->count() > 1 ? ' +'.($lineAtps->count() - 1).' more' : '')
                : 'No ATPs';
            $order->atp_numbers = $lineAtps->map(
                fn ($a) => (string) ($a->authority_purchase_form_number ?: ('#'.$a->authority_purchase_id))
            )->all();
            $order->po_total_amount = $lineAtps->sum(fn ($a) => (float) ($a->po_total_amount ?? 0));
        }
    }

    /**
     * Next PO No. for the current year-month on submit: PO-YYYYMM-00001
     */
    public static function allocateNumberOnSubmit(): string
    {
        $ym = now()->format('Ym');
        $prefix = 'PO-'.$ym.'-';
        $max = 0;

        foreach (
            DB::table('purchase_orders_table')
                ->whereNotNull('purchase_order_number')
                ->where('purchase_order_number', 'like', $prefix.'%')
                ->pluck('purchase_order_number') as $number
        ) {
            if (preg_match('/^PO-'.preg_quote($ym, '/').'-(\d{5})$/', (string) $number, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = min($max + 1, 99999);

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Display label: real PO-YYYYMM-00001 when assigned, else PO-YYYYMM-DRAFT.
     */
    public static function displayNumber(?object $order): string
    {
        $stored = trim((string) ($order->purchase_order_number ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $ym = now()->format('Ym');
        $createdAt = $order->purchase_order_created_at ?? null;
        if (! empty($createdAt)) {
            try {
                $ym = \Carbon\Carbon::parse($createdAt)->format('Ym');
            } catch (\Throwable $e) {
                // keep current month
            }
        }

        return 'PO-'.$ym.'-DRAFT';
    }

    public static function isEligibleDraftAtp(int $atpId, ?int $ownerId = null): bool
    {
        $query = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $atpId)
            ->where('authority_purchase_status', 'Pending')
            ->whereNull('authority_purchase_submitted_at');

        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_is_archived')) {
            $query->where(function ($q) {
                $q->where('authority_purchase_is_archived', 0)
                    ->orWhereNull('authority_purchase_is_archived');
            });
        }

        if ($ownerId && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_created_by')) {
            $query->where('authority_purchase_created_by', $ownerId);
        }

        return $query->exists() && ! self::poIdForAtp($atpId);
    }

    /**
     * @return array<int, int>
     */
    public static function eligibleDraftAtpIds(int $createdBy): array
    {
        if (! Schema::hasTable('authority_to_purchase_table')) {
            return [];
        }

        $linked = self::tablesExist()
            ? DB::table('purchase_order_atps_table')->pluck('authority_purchase_id')->all()
            : [];

        $query = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_status', 'Pending')
            ->whereNull('authority_purchase_submitted_at')
            ->orderBy('authority_purchase_id');

        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_created_by')) {
            $query->where('authority_purchase_created_by', $createdBy);
        }

        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_is_archived')) {
            $query->where(function ($q) {
                $q->where('authority_purchase_is_archived', 0)
                    ->orWhereNull('authority_purchase_is_archived');
            });
        }

        if ($linked !== []) {
            $query->whereNotIn('authority_purchase_id', $linked);
        }

        return $query->pluck('authority_purchase_id')->map(fn ($id) => (int) $id)->all();
    }

    private static function latestOpenDraftId(int $createdBy): ?int
    {
        $rows = DB::table('purchase_orders_table')
            ->where('purchase_order_status', self::STATUS_DRAFT)
            ->where('purchase_order_created_by', $createdBy)
            ->where(function ($q) {
                $q->where('purchase_order_is_archived', 0)
                    ->orWhereNull('purchase_order_is_archived');
            })
            ->orderByDesc('purchase_order_id')
            ->get();

        foreach ($rows as $row) {
            if (self::openSlots((int) $row->purchase_order_id) > 0) {
                return (int) $row->purchase_order_id;
            }
        }

        return null;
    }

    private static function supplierDisplay(object $atp): string
    {
        if (($atp->supplier_store_type ?? null) === 'Online Store') {
            return (string) ($atp->shop_name ?: 'Online supplier');
        }

        return (string) ($atp->company_name ?: 'Supplier');
    }
}
