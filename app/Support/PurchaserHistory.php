<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-account purchase history for Purchaser accounts (primary or additional role).
 *
 * Attribution is strict: a record belongs to the person who created it, or — for older rows
 * without a creator — to the person who submitted it. Unowned legacy rows are never shown,
 * so every purchaser's history is unique to them.
 */
class PurchaserHistory
{
    public const DOCUMENT_TYPES = [
        'ris' => 'RIS',
        'atp' => 'Authority to Purchase',
        'po' => 'Purchase Order',
        'rfc' => 'Request Fund',
        'rr' => 'Receiving Report',
        'liq' => 'Liquidation',
    ];

    public const STATUS_FILTERS = [
        'approved' => 'Approved',
        'review' => 'In review',
        'revision' => 'Returned for revision',
        'rejected' => 'Rejected',
    ];

    /**
     * Accounts holding the Purchaser role, either as primary role or as an additional role.
     *
     * @return Collection<int, object{user_id: int, name: string, employee_id: ?string, is_primary: bool, role_label: string}>
     */
    public static function accounts(): Collection
    {
        $query = DB::table('users_table')
            ->select('user_id', 'user_full_name', 'user_username', 'user_employee_id', 'user_role_id')
            ->where('user_role_id', RoleAccess::PURCHASER);

        if (Schema::hasTable('user_roles_table')) {
            $query->orWhereIn(
                'user_id',
                DB::table('user_roles_table')->select('user_id')->where('role_id', RoleAccess::PURCHASER)
            );
        }

        return $query
            ->orderBy('user_full_name')
            ->get()
            ->map(fn ($user) => self::describeAccount($user))
            ->sortBy(fn ($account) => [$account->is_primary ? 0 : 1, $account->name])
            ->values();
    }

    public static function findAccount(int $userId): ?object
    {
        return self::accounts()->firstWhere('user_id', $userId);
    }

    public static function describeAccount(object $user): object
    {
        $isPrimary = (int) ($user->user_role_id ?? 0) === RoleAccess::PURCHASER;
        $name = trim((string) ($user->user_full_name ?? '')) ?: (string) ($user->user_username ?? 'User #'.$user->user_id);

        $primaryLabel = RoleAccess::portalMeta()[(int) ($user->user_role_id ?? 0)]['label'] ?? 'User';

        return (object) [
            'user_id' => (int) $user->user_id,
            'name' => $name,
            'employee_id' => $user->user_employee_id ?? null,
            'is_primary' => $isPrimary,
            'role_label' => $isPrimary
                ? 'Purchaser (primary role)'
                : 'Purchaser (additional role) · '.$primaryLabel,
        ];
    }

    // =====================================================
    // PURCHASED ITEMS (ATP lines)
    // =====================================================

    /**
     * @param  array{search?: ?string, from?: ?string, to?: ?string, status?: ?string}  $filters
     */
    public static function purchasedItems(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = DB::table('authority_to_purchase_items_table as item')
            ->join('authority_to_purchase_table as atp', 'atp.authority_purchase_id', '=', 'item.authority_purchase_id')
            ->leftJoin('requisition_issue_slip_table as ris', 'ris.ris_id', '=', 'atp.authority_purchase_ris_id');
        self::joinSupplier($query, 'atp.authority_purchase_supplier_id');

        self::scopeOwnedAtp($query, $userId);
        self::scopeNotDraftAtp($query);
        self::applyAtpPeriod($query, $filters);
        self::applyAtpStatus($query, $filters['status'] ?? null);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('item.atp_description', 'LIKE', $like)
                    ->orWhere('atp.authority_purchase_form_number', 'LIKE', $like)
                    ->orWhere('ris.ris_form_number', 'LIKE', $like)
                    ->orWhere('ps.company_name', 'LIKE', $like)
                    ->orWhere('os.shop_name', 'LIKE', $like);
            });
        }

        $items = $query
            ->select(
                'item.atp_item_id',
                'item.atp_description',
                'item.atp_quantity',
                'item.atp_unit',
                'item.atp_unit_price',
                DB::raw('COALESCE(item.atp_amount, item.atp_quantity * item.atp_unit_price) as line_amount'),
                'atp.authority_purchase_id',
                'atp.authority_purchase_form_number',
                'atp.authority_purchase_status',
                'atp.authority_purchase_submitted_at',
                'atp.authority_purchase_rejection_reason',
                'atp.authority_purchase_is_archived',
                'atp.authority_purchase_payment_path',
                DB::raw(self::atpDateSql().' as purchase_date'),
                'ris.ris_form_number',
                DB::raw('COALESCE(ps.company_name, os.shop_name) as supplier_name')
            )
            ->orderByDesc('purchase_date')
            ->orderByDesc('atp.authority_purchase_id')
            ->orderBy('item.atp_item_id')
            ->paginate($perPage, ['*'], 'page')
            ->withQueryString();

        $delivery = self::deliveryStatusFor(
            $items->getCollection()->pluck('authority_purchase_id')->map(fn ($id) => (int) $id)->unique()->all()
        );

        foreach ($items as $item) {
            $item->status = self::atpStatus($item);
            $item->delivery = $item->status['key'] === 'approved'
                ? ($delivery[(int) $item->authority_purchase_id] ?? ['label' => 'Awaiting delivery', 'tone' => 'slate'])
                : null;
        }

        return $items;
    }

    // =====================================================
    // SUMMARY + SUPPLIERS
    // =====================================================

    /**
     * ATP-level rows (one per purchase) for the period, with item totals.
     */
    private static function purchaseRows(int $userId, array $filters): Collection
    {
        $totals = DB::table('authority_to_purchase_items_table')
            ->select(
                'authority_purchase_id',
                DB::raw('SUM(COALESCE(atp_amount, atp_quantity * atp_unit_price)) as total_amount'),
                DB::raw('SUM(COALESCE(atp_quantity, 0)) as total_qty'),
                DB::raw('COUNT(*) as line_count')
            )
            ->groupBy('authority_purchase_id');

        $query = DB::table('authority_to_purchase_table as atp')
            ->leftJoinSub($totals, 't', 't.authority_purchase_id', '=', 'atp.authority_purchase_id');
        self::joinSupplier($query, 'atp.authority_purchase_supplier_id');

        self::scopeOwnedAtp($query, $userId);
        self::scopeNotDraftAtp($query);
        self::applyAtpPeriod($query, $filters);

        return $query
            ->select(
                'atp.authority_purchase_id',
                'atp.authority_purchase_status',
                'atp.authority_purchase_submitted_at',
                'atp.authority_purchase_rejection_reason',
                'atp.authority_purchase_supplier_id',
                DB::raw(self::atpDateSql().' as purchase_date'),
                DB::raw('COALESCE(ps.company_name, os.shop_name) as supplier_name'),
                DB::raw('COALESCE(t.total_amount, 0) as total_amount'),
                DB::raw('COALESCE(t.total_qty, 0) as total_qty'),
                DB::raw('COALESCE(t.line_count, 0) as line_count')
            )
            ->get()
            ->each(function ($row) {
                $row->status_key = self::atpStatus($row)['key'];
            });
    }

    /**
     * @return array<string, int|float|string|null>
     */
    public static function summary(int $userId, array $filters): array
    {
        $rows = self::purchaseRows($userId, $filters);
        $approved = $rows->where('status_key', 'approved');
        $delivery = self::deliveryStatusFor($approved->pluck('authority_purchase_id')->map(fn ($id) => (int) $id)->all());

        $lastPurchase = $rows->max('purchase_date');

        return [
            'purchases' => $rows->count(),
            'approved' => $approved->count(),
            'in_review' => $rows->where('status_key', 'review')->count(),
            'returned' => $rows->whereIn('status_key', ['revision', 'rejected'])->count(),
            'approved_spend' => (float) $approved->sum('total_amount'),
            'requested_spend' => (float) $rows->sum('total_amount'),
            'items_purchased' => (int) $approved->sum('total_qty'),
            'suppliers' => $approved->pluck('authority_purchase_supplier_id')->filter()->unique()->count(),
            'delivered' => collect($delivery)->where('key', 'delivered')->count(),
            'last_purchase' => $lastPurchase ? Carbon::parse($lastPurchase)->format('M d, Y') : null,
        ];
    }

    /**
     * Suppliers this person bought from (approved purchases), highest spend first.
     */
    public static function suppliers(int $userId, array $filters): Collection
    {
        return self::purchaseRows($userId, $filters)
            ->where('status_key', 'approved')
            ->groupBy(fn ($row) => (int) ($row->authority_purchase_supplier_id ?? 0))
            ->map(function (Collection $group, int $supplierId) {
                return (object) [
                    'supplier_id' => $supplierId ?: null,
                    'name' => $group->first()->supplier_name ?: 'Unspecified supplier',
                    'purchases' => $group->count(),
                    'items' => (int) $group->sum('total_qty'),
                    'spend' => (float) $group->sum('total_amount'),
                    'last_purchase' => $group->max('purchase_date'),
                ];
            })
            ->sortByDesc('spend')
            ->values();
    }

    /**
     * Approved spend per month for the last $months months (for the mini trend chart).
     *
     * @return array<int, array{label: string, amount: float}>
     */
    public static function monthlySpend(int $userId, int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $rows = self::purchaseRows($userId, ['from' => $start->toDateString()])
            ->where('status_key', 'approved');

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $series[$key] = ['label' => $month->format('M'), 'amount' => 0.0];
        }

        foreach ($rows as $row) {
            if (! $row->purchase_date) {
                continue;
            }
            $key = Carbon::parse($row->purchase_date)->format('Y-m');
            if (isset($series[$key])) {
                $series[$key]['amount'] += (float) $row->total_amount;
            }
        }

        return array_values($series);
    }

    // =====================================================
    // DOCUMENT TIMELINE (everything the person authored)
    // =====================================================

    public static function documents(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $type = $filters['type'] ?? null;
        $docs = self::allDocuments($userId, $type && isset(self::DOCUMENT_TYPES[$type]) ? [$type] : null);

        $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));

        $docs = $docs
            ->filter(function ($doc) use ($from, $to, $search) {
                $date = $doc->date ? Carbon::parse($doc->date) : null;
                if ($from && (! $date || $date->lt($from))) {
                    return false;
                }
                if ($to && (! $date || $date->gt($to))) {
                    return false;
                }
                if ($search !== '') {
                    $haystack = mb_strtolower($doc->number.' '.$doc->title.' '.$doc->status);

                    return str_contains($haystack, $search);
                }

                return true;
            })
            ->sortByDesc(fn ($doc) => $doc->date ?? '')
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $docs->forPage($page, $perPage)->values(),
            $docs->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        ))->withQueryString();
    }

    /**
     * Every document the person authored, newest first.
     *
     * @param  array<int, string>|null  $types
     */
    public static function allDocuments(int $userId, ?array $types = null): Collection
    {
        $docs = collect();
        foreach ($types ?? array_keys(self::DOCUMENT_TYPES) as $type) {
            $docs = $docs->merge(self::documentRows($type, $userId));
        }

        return $docs->sortByDesc(fn ($doc) => $doc->date ?? '')->values();
    }

    /**
     * Document counts by type (all time) for the tab chips.
     *
     * @return array<string, int>
     */
    public static function documentCounts(int $userId): array
    {
        $counts = [];
        foreach (array_keys(self::DOCUMENT_TYPES) as $type) {
            $counts[$type] = self::documentRows($type, $userId)->count();
        }

        return $counts;
    }

    private static function documentRows(string $type, int $userId): Collection
    {
        return match ($type) {
            'ris' => self::risDocuments($userId),
            'atp' => self::atpDocuments($userId),
            'po' => self::poDocuments($userId),
            'rfc' => self::rfcDocuments($userId),
            'rr' => self::rrDocuments($userId),
            'liq' => self::liqDocuments($userId),
            default => collect(),
        };
    }

    private static function risDocuments(int $userId): Collection
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return collect();
        }

        $totals = DB::table('requisition_issue_slip_items_table')
            ->select('ris_id', DB::raw('SUM(ris_total_amount) as total_amount'))
            ->groupBy('ris_id');

        $query = DB::table('requisition_issue_slip_table as ris')
            ->leftJoinSub($totals, 't', 't.ris_id', '=', 'ris.ris_id');

        $createdCol = Schema::hasColumn('requisition_issue_slip_table', 'ris_created_by') ? 'ris.ris_created_by' : null;
        self::scopeOwner($query, $createdCol, 'ris.ris_submitted_by', $userId);

        $titleSql = Schema::hasColumn('requisition_issue_slip_table', 'ris_manual_title')
            ? 'COALESCE(NULLIF(ris.ris_manual_title, \'\'), ris.ris_purpose_description)'
            : 'ris.ris_purpose_description';

        return $query
            ->select(
                'ris.ris_id as id',
                'ris.ris_form_number as number',
                'ris.ris_status as status',
                'ris.ris_is_archived as is_archived',
                DB::raw('COALESCE(ris.ris_submitted_at, ris.ris_created_at) as date'),
                DB::raw($titleSql.' as title'),
                DB::raw('t.total_amount as amount')
            )
            ->get()
            ->map(fn ($row) => self::documentObject('ris', $row, [
                'route' => 'ris.index',
                'params' => ['view_ris' => $row->id],
                'fallback' => 'Draft RIS',
            ]));
    }

    private static function atpDocuments(int $userId): Collection
    {
        $totals = DB::table('authority_to_purchase_items_table')
            ->select('authority_purchase_id', DB::raw('SUM(COALESCE(atp_amount, atp_quantity * atp_unit_price)) as total_amount'))
            ->groupBy('authority_purchase_id');

        $query = DB::table('authority_to_purchase_table as atp')
            ->leftJoinSub($totals, 't', 't.authority_purchase_id', '=', 'atp.authority_purchase_id')
            ->leftJoin('requisition_issue_slip_table as ris', 'ris.ris_id', '=', 'atp.authority_purchase_ris_id');
        self::joinSupplier($query, 'atp.authority_purchase_supplier_id');
        self::scopeOwnedAtp($query, $userId);

        return $query
            ->select(
                'atp.authority_purchase_id as id',
                'atp.authority_purchase_form_number as number',
                'atp.authority_purchase_status',
                'atp.authority_purchase_submitted_at',
                'atp.authority_purchase_rejection_reason',
                'atp.authority_purchase_is_archived as is_archived',
                DB::raw('COALESCE(atp.authority_purchase_submitted_at, atp.authority_purchase_created_at) as date'),
                DB::raw('COALESCE(ps.company_name, os.shop_name) as supplier_name'),
                'ris.ris_form_number',
                DB::raw('t.total_amount as amount')
            )
            ->get()
            ->map(function ($row) {
                $status = self::atpStatus($row);
                $row->status = $status['label'];
                $row->title = trim(($row->supplier_name ?: 'No supplier yet').($row->ris_form_number ? ' · RIS '.$row->ris_form_number : ''));

                return self::documentObject('atp', $row, [
                    'route' => 'atp.index',
                    'params' => ['view_atp' => $row->id],
                    'fallback' => 'Draft ATP',
                    'tone' => $status['tone'],
                ]);
            });
    }

    private static function poDocuments(int $userId): Collection
    {
        if (! Schema::hasTable('purchase_orders_table')) {
            return collect();
        }

        $query = DB::table('purchase_orders_table as po');
        self::scopeOwner($query, 'po.purchase_order_created_by', 'po.purchase_order_submitted_by', $userId);

        if (Schema::hasTable('purchase_order_atps_table')) {
            $totals = DB::table('purchase_order_atps_table as link')
                ->join('authority_to_purchase_items_table as item', 'item.authority_purchase_id', '=', 'link.authority_purchase_id')
                ->select('link.purchase_order_id', DB::raw('SUM(COALESCE(item.atp_amount, item.atp_quantity * item.atp_unit_price)) as total_amount'), DB::raw('COUNT(DISTINCT link.authority_purchase_id) as atp_count'))
                ->groupBy('link.purchase_order_id');
            $query->leftJoinSub($totals, 't', 't.purchase_order_id', '=', 'po.purchase_order_id');
            $amountSql = 't.total_amount';
            $countSql = 't.atp_count';
        } else {
            $amountSql = 'NULL';
            $countSql = '0';
        }

        return $query
            ->select(
                'po.purchase_order_id as id',
                'po.purchase_order_number',
                'po.purchase_order_created_at',
                'po.purchase_order_status as status',
                'po.purchase_order_is_archived as is_archived',
                DB::raw('COALESCE(po.purchase_order_submitted_at, po.purchase_order_created_at) as date'),
                DB::raw($amountSql.' as amount'),
                DB::raw($countSql.' as atp_count')
            )
            ->get()
            ->map(function ($row) {
                $row->number = PurchaseOrderBasket::displayNumber($row);
                $count = (int) $row->atp_count;
                $row->title = $count.' '.($count === 1 ? 'ATP' : 'ATPs').' grouped';

                return self::documentObject('po', $row, [
                    'route' => 'purchase-orders.index',
                    'params' => ['view_po' => $row->id],
                ]);
            });
    }

    private static function rfcDocuments(int $userId): Collection
    {
        if (! Schema::hasTable('request_check_table')) {
            return collect();
        }

        $query = DB::table('request_check_table as rfc');
        self::scopeOwner($query, 'rfc.request_check_requested_by_user_id', 'rfc.request_check_submitted_by', $userId);

        return $query
            ->select(
                'rfc.request_check_id as id',
                'rfc.request_check_form_number as number',
                'rfc.request_check_status as status',
                'rfc.request_check_funding_type',
                'rfc.request_check_payee',
                'rfc.request_check_is_archived as is_archived',
                DB::raw('COALESCE(rfc.request_check_submitted_at, rfc.request_check_created_at) as date'),
                'rfc.request_check_amount_figures as amount'
            )
            ->get()
            ->map(function ($row) {
                $row->title = ProcurementPaymentPath::label($row->request_check_funding_type)
                    .($row->request_check_payee ? ' · '.$row->request_check_payee : '');

                return self::documentObject('rfc', $row, [
                    'route' => 'rfc.index',
                    'params' => ['view_rfc' => $row->id, 'fund' => $row->request_check_funding_type],
                    'fallback' => 'Draft request',
                    'type_label' => ProcurementPaymentPath::label($row->request_check_funding_type),
                ]);
            });
    }

    private static function rrDocuments(int $userId): Collection
    {
        if (! Schema::hasTable('receiving_reports_table')) {
            return collect();
        }

        $totals = DB::table('receiving_report_items_table')
            ->select('receiving_report_id', DB::raw('SUM(receiving_report_item_amount) as total_amount'))
            ->groupBy('receiving_report_id');

        $query = DB::table('receiving_reports_table as rr')
            ->leftJoinSub($totals, 't', 't.receiving_report_id', '=', 'rr.receiving_report_id');

        $createdCol = Schema::hasColumn('receiving_reports_table', 'receiving_report_created_by') ? 'rr.receiving_report_created_by' : null;
        self::scopeOwner($query, $createdCol, 'rr.receiving_report_submitted_by', $userId);

        return $query
            ->select(
                'rr.receiving_report_id as id',
                'rr.receiving_report_form_number as number',
                'rr.receiving_report_status as status',
                'rr.receiving_report_is_archived as is_archived',
                DB::raw('COALESCE(rr.receiving_report_submitted_at, rr.receiving_report_created_at) as date'),
                DB::raw('rr.receiving_report_received_from as title'),
                DB::raw('t.total_amount as amount')
            )
            ->get()
            ->map(fn ($row) => self::documentObject('rr', $row, [
                'route' => 'rr.index',
                'params' => ['view_rr' => $row->id],
                'fallback' => 'Draft RR',
            ]));
    }

    private static function liqDocuments(int $userId): Collection
    {
        if (! Schema::hasTable('liquidation_reports_table')) {
            return collect();
        }

        $query = DB::table('liquidation_reports_table as liq');
        $createdCol = Schema::hasColumn('liquidation_reports_table', 'liquidation_report_created_by') ? 'liq.liquidation_report_created_by' : null;
        self::scopeOwner($query, $createdCol, 'liq.liquidation_report_submitted_by', $userId);

        return $query
            ->select(
                'liq.liquidation_report_id as id',
                'liq.liquidation_report_form_number as number',
                'liq.liquidation_report_status as status',
                'liq.liquidation_report_is_archived as is_archived',
                DB::raw('COALESCE(liq.liquidation_report_submitted_at, liq.liquidation_report_created_at) as date'),
                DB::raw('liq.liquidation_report_purpose as title'),
                DB::raw('COALESCE(liq.liquidation_report_summary_actual_expense, liq.liquidation_report_amount_advance) as amount')
            )
            ->get()
            ->map(fn ($row) => self::documentObject('liq', $row, [
                'route' => 'liq.index',
                'params' => ['view_liq' => $row->id],
                'fallback' => 'Draft liquidation',
            ]));
    }

    /**
     * @param  array{route: string, params: array<string, mixed>, fallback?: string, tone?: string, type_label?: string}  $meta
     */
    private static function documentObject(string $type, object $row, array $meta): object
    {
        $isArchived = (int) ($row->is_archived ?? 0) === 1;
        $params = $meta['params'];
        if ($isArchived) {
            $params['view'] = 'archive';
        }

        return (object) [
            'type' => $type,
            'type_label' => $meta['type_label'] ?? self::DOCUMENT_TYPES[$type],
            'id' => (int) $row->id,
            'number' => trim((string) ($row->number ?? '')) ?: ($meta['fallback'] ?? '—'),
            'title' => trim(strip_tags((string) ($row->title ?? ''))),
            'status' => (string) ($row->status ?? ''),
            'tone' => $meta['tone'] ?? self::toneForStatus((string) ($row->status ?? '')),
            'amount' => $row->amount !== null ? (float) $row->amount : null,
            'date' => $row->date,
            'is_archived' => $isArchived,
            'route' => $meta['route'],
            'params' => $params,
        ];
    }

    // =====================================================
    // STATUS HELPERS
    // =====================================================

    /**
     * @return array{key: string, label: string, tone: string}
     */
    public static function atpStatus(object $row): array
    {
        $status = (string) ($row->authority_purchase_status ?? 'Pending');
        $submitted = ! empty($row->authority_purchase_submitted_at);
        $hasReason = trim((string) ($row->authority_purchase_rejection_reason ?? '')) !== '';

        return match (true) {
            $status === 'Approved' => ['key' => 'approved', 'label' => 'Approved', 'tone' => 'green'],
            $status === 'Rejected' => ['key' => 'rejected', 'label' => 'Rejected', 'tone' => 'red'],
            $submitted => ['key' => 'review', 'label' => 'In review', 'tone' => 'amber'],
            $hasReason => ['key' => 'revision', 'label' => 'Minor Revision', 'tone' => 'orange'],
            default => ['key' => 'draft', 'label' => 'Draft', 'tone' => 'slate'],
        };
    }

    public static function toneForStatus(string $status): string
    {
        $s = mb_strtolower($status);

        return match (true) {
            $s === '' => 'slate',
            str_contains($s, 'reject') || $s === 'returned' || $s === 'cancelled' => 'red',
            str_contains($s, 'revision') || $s === 'incomplete' => 'orange',
            str_contains($s, 'approved') || in_array($s, ['completed', 'accepted'], true) => 'green',
            $s === 'draft' || $s === 'archived' => 'slate',
            default => 'amber',
        };
    }

    /**
     * Delivery state per ATP based on its Receiving Reports.
     *
     * @param  array<int, int>  $atpIds
     * @return array<int, array{key: string, label: string, tone: string}>
     */
    public static function deliveryStatusFor(array $atpIds): array
    {
        $atpIds = array_values(array_filter(array_unique($atpIds)));
        if ($atpIds === [] || ! Schema::hasTable('receiving_reports_table')) {
            return [];
        }

        $hasAtpColumn = Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id');
        $atpKeySql = $hasAtpColumn
            ? 'COALESCE(rr.receiving_report_atp_id, rfc.request_check_authority_purchase_id)'
            : 'rfc.request_check_authority_purchase_id';

        $rows = DB::table('receiving_reports_table as rr')
            ->leftJoin('request_check_table as rfc', 'rfc.request_check_id', '=', 'rr.receiving_report_request_check_id')
            ->whereIn(DB::raw($atpKeySql), $atpIds)
            ->select(DB::raw($atpKeySql.' as atp_id'), 'rr.receiving_report_status')
            ->get();

        $rank = ['Completed' => 3, 'Incomplete' => 2];
        $best = [];
        foreach ($rows as $row) {
            $atpId = (int) $row->atp_id;
            $score = $rank[$row->receiving_report_status] ?? 1;
            if (! isset($best[$atpId]) || $score > $best[$atpId]) {
                $best[$atpId] = $score;
            }
        }

        return array_map(fn (int $score) => match ($score) {
            3 => ['key' => 'delivered', 'label' => 'Delivered', 'tone' => 'green'],
            2 => ['key' => 'partial', 'label' => 'Partially delivered', 'tone' => 'orange'],
            default => ['key' => 'receiving', 'label' => 'In receiving', 'tone' => 'blue'],
        }, $best);
    }

    // =====================================================
    // QUERY HELPERS
    // =====================================================

    private static function atpDateSql(): string
    {
        return 'COALESCE(atp.authority_purchase_date, DATE(atp.authority_purchase_submitted_at), DATE(atp.authority_purchase_created_at))';
    }

    private static function joinSupplier($query, string $supplierColumn): void
    {
        $query
            ->leftJoin('physical_suppliers_table as ps', 'ps.supplier_id', '=', $supplierColumn)
            ->leftJoin('online_suppliers_table as os', 'os.supplier_id', '=', $supplierColumn);
    }

    /**
     * Owned by $userId: created it, or (no recorded creator) submitted it.
     */
    private static function scopeOwner($query, ?string $createdCol, string $submittedCol, int $userId): void
    {
        $query->where(function ($q) use ($createdCol, $submittedCol, $userId) {
            if ($createdCol === null) {
                $q->where($submittedCol, $userId);

                return;
            }

            $q->where($createdCol, $userId)
                ->orWhere(function ($legacy) use ($createdCol, $submittedCol, $userId) {
                    $legacy->whereNull($createdCol)->where($submittedCol, $userId);
                });
        });
    }

    private static function scopeOwnedAtp($query, int $userId): void
    {
        self::scopeOwner($query, 'atp.authority_purchase_created_by', 'atp.authority_purchase_submitted_by', $userId);
    }

    /**
     * A purchase is an ATP that has left draft (submitted at least once or decided).
     */
    private static function scopeNotDraftAtp($query): void
    {
        $query->where(function ($q) {
            $q->where('atp.authority_purchase_status', '!=', 'Pending')
                ->orWhereNotNull('atp.authority_purchase_submitted_at')
                ->orWhere(function ($r) {
                    $r->whereNotNull('atp.authority_purchase_rejection_reason')
                        ->where('atp.authority_purchase_rejection_reason', '!=', '');
                });
        });
    }

    private static function applyAtpPeriod($query, array $filters): void
    {
        if (! empty($filters['from'])) {
            $query->whereRaw(self::atpDateSql().' >= ?', [Carbon::parse($filters['from'])->toDateString()]);
        }
        if (! empty($filters['to'])) {
            $query->whereRaw(self::atpDateSql().' <= ?', [Carbon::parse($filters['to'])->toDateString()]);
        }
    }

    private static function applyAtpStatus($query, ?string $status): void
    {
        match ($status) {
            'approved' => $query->where('atp.authority_purchase_status', 'Approved'),
            'rejected' => $query->where('atp.authority_purchase_status', 'Rejected'),
            'review' => $query->where('atp.authority_purchase_status', 'Pending')
                ->whereNotNull('atp.authority_purchase_submitted_at'),
            'revision' => $query->where('atp.authority_purchase_status', 'Pending')
                ->whereNull('atp.authority_purchase_submitted_at')
                ->whereNotNull('atp.authority_purchase_rejection_reason')
                ->where('atp.authority_purchase_rejection_reason', '!=', ''),
            default => null,
        };
    }
}
