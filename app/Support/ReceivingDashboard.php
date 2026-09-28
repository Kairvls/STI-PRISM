<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Data for the Receiving Officer dashboard: second-count queue, back-order tracking,
 * incomplete receiving reports, supplier issues and dock activity.
 */
class ReceivingDashboard
{
    private const RR = 'receiving_reports_table';
    private const ITEMS = 'receiving_report_items_table';
    private const COUNTED_STATUSES = ['Completed', 'Accepted', 'Incomplete'];

    /** Unresolved back-order statuses in the order they move through the board. */
    public const BOARD = [
        BackOrders::STATUS_OPEN => ['label' => 'Needs action', 'hint' => 'Purchaser has not decided yet', 'tone' => 'ink'],
        BackOrders::STATUS_WAITING_RESTOCK => ['label' => 'Waiting for restock', 'hint' => 'Same supplier will deliver later', 'tone' => 'mid'],
        BackOrders::STATUS_REFUNDED => ['label' => 'Refunded', 'hint' => 'Money back, replacement pending', 'tone' => 'soft'],
        BackOrders::STATUS_RECEIVING => ['label' => 'For your second count', 'hint' => 'Replacement arrived at the dock', 'tone' => 'accent'],
    ];

    public static function build(): array
    {
        $backOrders = self::safe(fn () => self::backOrderRows(), collect());
        $unresolved = $backOrders->filter(fn ($bo) => BackOrders::isUnresolved($bo->back_order_status))->values();
        $counts = ReceivingAttentionSummary::counts();

        return [
            'counts' => $counts,
            'urgentCount' => self::safe(fn () => self::urgentQueueCount(), 0),
            'countedThisMonth' => self::safe(fn () => self::countedInMonth(now()), 0),
            'countedLastMonth' => self::safe(fn () => self::countedInMonth(now()->subMonthNoOverflow()), 0),
            'queue' => self::safe(fn () => self::queueRows($unresolved), ['rows' => collect(), 'total' => 0]),
            'recentlyCounted' => self::safe(fn () => self::recentlyCounted(), collect()),
            'board' => self::board($unresolved),
            'snapshot' => self::snapshot($backOrders, $unresolved),
            'incomplete' => self::safe(fn () => self::incompleteReports($backOrders), collect()),
            'suppliers' => self::supplierIssues($backOrders),
            'week' => self::safe(fn () => self::weekStrip(), []),
            'activity' => self::safe(fn () => self::recentActivity(), collect()),
        ];
    }

    private static function safe(callable $callback, $fallback)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::warning('Receiving dashboard query failed: '.$e->getMessage());

            return $fallback;
        }
    }

    private static function hasRr(): bool
    {
        return Schema::hasTable(self::RR);
    }

    private static function rrColumn(string $column): bool
    {
        return Schema::hasColumn(self::RR, $column);
    }

    private static function submittedExpr(): string
    {
        $columns = array_values(array_filter(
            ['receiving_report_submitted_at', 'receiving_report_created_at', 'receiving_report_date'],
            fn ($column) => self::rrColumn($column)
        ));

        if ($columns === []) {
            return 'NULL';
        }

        return count($columns) === 1
            ? self::RR.'.'.$columns[0]
            : 'COALESCE('.implode(', ', array_map(fn ($c) => self::RR.'.'.$c, $columns)).')';
    }

    private static function ageDays($raw): ?int
    {
        if (empty($raw)) {
            return null;
        }

        try {
            return max(0, (int) Carbon::parse($raw)->startOfDay()->diffInDays(today()));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function urgentQueueCount(): int
    {
        if (!self::hasRr() || !($sql = DocumentUrgency::sql('RR', self::RR))) {
            return 0;
        }

        $query = ReceivingAttentionSummary::queue();
        ReceivingAttentionSummary::scopeQueue($query);

        return (int) $query->whereRaw($sql)->count();
    }

    private static function countedInMonth(Carbon $month): int
    {
        if (!self::hasRr() || !self::rrColumn('receiving_report_second_count_at')) {
            return 0;
        }

        $query = DB::table(self::RR)
            ->whereIn('receiving_report_status', self::COUNTED_STATUSES)
            ->whereBetween('receiving_report_second_count_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ]);
        ReceivingAttentionSummary::scopeActive($query);

        return (int) $query->count();
    }

    /**
     * Supplier name for an RR: the typed "received from", then the linked supplier record.
     */
    private static function withSupplier($query): string
    {
        $parts = [];
        if (self::rrColumn('receiving_report_received_from')) {
            $parts[] = "NULLIF(TRIM(".self::RR.".receiving_report_received_from), '')";
        }
        if (self::rrColumn('receiving_report_supplier_id')) {
            if (Schema::hasTable('physical_suppliers_table')) {
                $query->leftJoin('physical_suppliers_table as rd_ps', 'rd_ps.supplier_id', '=', self::RR.'.receiving_report_supplier_id');
                $parts[] = 'rd_ps.company_name';
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $query->leftJoin('online_suppliers_table as rd_os', 'rd_os.supplier_id', '=', self::RR.'.receiving_report_supplier_id');
                $parts[] = 'rd_os.shop_name';
            }
        }
        if (Schema::hasTable(self::ITEMS)) {
            $parts[] = 'rd_items.first_supplier';
        }

        return $parts === [] ? 'NULL' : 'COALESCE('.implode(', ', $parts).')';
    }

    private static function joinItemTotals($query): void
    {
        if (!Schema::hasTable(self::ITEMS)) {
            return;
        }

        $sub = DB::table(self::ITEMS)
            ->select(
                'receiving_report_id',
                DB::raw('COUNT(*) as line_count'),
                DB::raw('COALESCE(SUM(receiving_report_item_quantity), 0) as total_qty'),
                DB::raw('COALESCE(SUM(receiving_report_item_amount), 0) as total_amount'),
                DB::raw('GROUP_CONCAT(receiving_report_item_article ORDER BY receiving_report_item_id SEPARATOR "||") as articles'),
                DB::raw('MAX(receiving_report_item_supplier_name) as first_supplier')
            )
            ->groupBy('receiving_report_id');

        $query->leftJoinSub($sub, 'rd_items', 'rd_items.receiving_report_id', '=', self::RR.'.receiving_report_id');
    }

    /**
     * @return array{rows: Collection, total: int}
     */
    private static function queueRows(Collection $unresolved): array
    {
        if (!self::hasRr()) {
            return ['rows' => collect(), 'total' => 0];
        }

        $query = ReceivingAttentionSummary::queue();
        ReceivingAttentionSummary::scopeQueue($query);
        self::joinItemTotals($query);
        $supplierExpr = self::withSupplier($query);
        $submittedExpr = self::submittedExpr();
        $hasItems = Schema::hasTable(self::ITEMS);

        $query->select(array_filter([
            self::RR.'.receiving_report_id',
            self::RR.'.receiving_report_status',
            self::rrColumn('receiving_report_form_number') ? self::RR.'.receiving_report_form_number' : null,
            DB::raw($supplierExpr.' as supplier_name'),
            DB::raw($submittedExpr.' as submitted_at'),
            $hasItems ? 'rd_items.line_count' : null,
            $hasItems ? 'rd_items.total_qty' : null,
            $hasItems ? 'rd_items.total_amount' : null,
            $hasItems ? 'rd_items.articles' : null,
        ]));
        DocumentUrgency::select($query, 'RR', self::RR);
        DocumentUrgency::orderUrgentFirst($query, 'RR', self::RR);

        $total = (clone $query)->count();
        $replacementRoots = $unresolved
            ->where('back_order_status', BackOrders::STATUS_RECEIVING)
            ->pluck('back_order_root_receiving_report_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $rows = $query
            ->orderByRaw($submittedExpr.' ASC')
            ->orderBy(self::RR.'.receiving_report_id')
            ->limit(6)
            ->get()
            ->map(function ($row) use ($replacementRoots) {
                $articles = array_values(array_filter(explode('||', (string) ($row->articles ?? ''))));
                $age = self::ageDays($row->submitted_at ?? null);

                return (object) [
                    'id' => (int) $row->receiving_report_id,
                    'number' => $row->receiving_report_form_number ?? ('RR #'.$row->receiving_report_id),
                    'status' => $row->receiving_report_status,
                    'supplier' => $row->supplier_name ?: 'Supplier not set',
                    'lines' => (int) ($row->line_count ?? 0),
                    'qty' => (int) ($row->total_qty ?? 0),
                    'amount' => (float) ($row->total_amount ?? 0),
                    'articles' => $articles,
                    'submitted_at' => !empty($row->submitted_at) ? Carbon::parse($row->submitted_at) : null,
                    'age' => $age,
                    'is_leftover' => $age !== null && $age > 0,
                    'is_urgent' => DocumentUrgency::isUrgent('RR', $row),
                    'has_replacement' => in_array((int) $row->receiving_report_id, $replacementRoots, true),
                ];
            });

        return ['rows' => $rows, 'total' => $total];
    }

    private static function recentlyCounted(): Collection
    {
        if (!self::hasRr() || !self::rrColumn('receiving_report_second_count_at')) {
            return collect();
        }

        $query = DB::table(self::RR)
            ->whereIn(self::RR.'.receiving_report_status', self::COUNTED_STATUSES)
            ->whereNotNull(self::RR.'.receiving_report_second_count_at');
        ReceivingAttentionSummary::scopeActive($query);
        self::joinItemTotals($query);
        $supplierExpr = self::withSupplier($query);
        $hasItems = Schema::hasTable(self::ITEMS);

        return $query
            ->select(array_filter([
                self::RR.'.receiving_report_id',
                self::RR.'.receiving_report_status',
                self::rrColumn('receiving_report_form_number') ? self::RR.'.receiving_report_form_number' : null,
                self::RR.'.receiving_report_second_count_at',
                DB::raw($supplierExpr.' as supplier_name'),
                $hasItems ? 'rd_items.total_qty' : null,
            ]))
            ->orderByDesc(self::RR.'.receiving_report_second_count_at')
            ->limit(3)
            ->get()
            ->map(fn ($row) => (object) [
                'id' => (int) $row->receiving_report_id,
                'number' => $row->receiving_report_form_number ?? ('RR #'.$row->receiving_report_id),
                'status' => $row->receiving_report_status,
                'supplier' => $row->supplier_name ?: 'Supplier not set',
                'qty' => (int) ($row->total_qty ?? 0),
                'counted_at' => Carbon::parse($row->receiving_report_second_count_at),
            ]);
    }

    private static function backOrderRows(): Collection
    {
        if (!BackOrders::supported()) {
            return collect();
        }

        $query = DB::table(BackOrders::TABLE.' as bo');
        $select = ['bo.*'];
        if (self::hasRr()) {
            $query->leftJoin(self::RR.' as root', 'root.receiving_report_id', '=', 'bo.back_order_root_receiving_report_id');
            $select[] = 'root.receiving_report_form_number as root_rr_number';
            $select[] = 'root.receiving_report_status as root_rr_status';
        }

        return $query->select($select)->orderBy('bo.back_order_created_at')->orderBy('bo.back_order_id')->get();
    }

    private static function boValue(object $bo): float
    {
        return (float) ($bo->back_order_quantity ?? 0) * (float) ($bo->back_order_unit_price ?? 0);
    }

    private static function board(Collection $unresolved): array
    {
        $board = [];
        foreach (self::BOARD as $status => $meta) {
            $rows = $unresolved->where('back_order_status', $status)->values();
            $board[$status] = $meta + [
                'count' => $rows->count(),
                'units' => (int) $rows->sum('back_order_quantity'),
                'tickets' => $rows->take(3)->map(fn ($bo) => (object) [
                    'id' => (int) $bo->back_order_id,
                    'number' => BackOrderNumber::label($bo),
                    'article' => $bo->back_order_article ?: 'Item',
                    'qty' => (int) $bo->back_order_quantity,
                    'unit' => $bo->back_order_unit ?: 'pcs',
                    'type' => $bo->back_order_type,
                    'type_label' => BackOrders::TYPES[$bo->back_order_type] ?? ucfirst((string) $bo->back_order_type),
                    'reason' => $bo->back_order_reason ? BackOrders::reasonLabel($bo->back_order_reason) : null,
                    'supplier' => $status === BackOrders::STATUS_RECEIVING && !empty($bo->back_order_replacement_supplier_name)
                        ? $bo->back_order_replacement_supplier_name
                        : ($bo->back_order_supplier_name ?: 'Supplier not set'),
                    'rr_number' => $bo->root_rr_number ?? null,
                    'root_id' => (int) ($bo->back_order_root_receiving_report_id ?? 0),
                    'value' => self::boValue($bo),
                    'age' => self::ageDays($bo->back_order_created_at ?? null),
                    'is_cash_advance' => ($bo->back_order_payment_path ?? null) === ProcurementPaymentPath::CASH_ADVANCE,
                ])->all(),
            ];
        }

        return $board;
    }

    private static function snapshot(Collection $all, Collection $unresolved): array
    {
        $monthStart = now()->startOfMonth();
        $fulfilledThisMonth = $all->filter(function ($bo) use ($monthStart) {
            if (BackOrders::isUnresolved($bo->back_order_status) || empty($bo->back_order_resolved_at)) {
                return false;
            }

            return Carbon::parse($bo->back_order_resolved_at)->gte($monthStart);
        });

        $oldest = $unresolved->first();

        $reasons = $unresolved
            ->groupBy(fn ($bo) => $bo->back_order_reason ?: '_none')
            ->map(fn ($rows, $key) => [
                'label' => $key === '_none' ? 'Not recorded' : BackOrders::reasonLabel($key),
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'total' => $unresolved->count(),
            'units' => (int) $unresolved->sum('back_order_quantity'),
            'value' => (float) $unresolved->sum(fn ($bo) => self::boValue($bo)),
            'byStatus' => collect(self::BOARD)->map(fn ($meta, $status) => [
                'label' => $meta['label'],
                'tone' => $meta['tone'],
                'count' => $unresolved->where('back_order_status', $status)->count(),
            ])->all(),
            'missing' => [
                'lines' => $unresolved->where('back_order_type', 'short')->count(),
                'units' => (int) $unresolved->where('back_order_type', 'short')->sum('back_order_quantity'),
            ],
            'damaged' => [
                'lines' => $unresolved->where('back_order_type', 'damaged')->count(),
                'units' => (int) $unresolved->where('back_order_type', 'damaged')->sum('back_order_quantity'),
            ],
            'reasons' => $reasons,
            'oldestDays' => $oldest ? self::ageDays($oldest->back_order_created_at ?? null) : null,
            'oldestArticle' => $oldest->back_order_article ?? null,
            'fulfilledThisMonth' => $fulfilledThisMonth->count(),
            'allTime' => $all->count(),
        ];
    }

    private static function incompleteReports(Collection $backOrders): Collection
    {
        if (!self::hasRr()) {
            return collect();
        }

        $query = DB::table(self::RR)->where(self::RR.'.receiving_report_status', 'Incomplete');
        ReceivingAttentionSummary::scopeActive($query);
        self::joinItemTotals($query);
        $supplierExpr = self::withSupplier($query);
        $countedCol = self::rrColumn('receiving_report_second_count_at')
            ? self::RR.'.receiving_report_second_count_at'
            : self::submittedExpr();

        $rows = $query
            ->select(array_filter([
                self::RR.'.receiving_report_id',
                self::rrColumn('receiving_report_form_number') ? self::RR.'.receiving_report_form_number' : null,
                DB::raw($supplierExpr.' as supplier_name'),
                DB::raw($countedCol.' as counted_at'),
            ]))
            ->orderByRaw($countedCol.' ASC')
            ->limit(5)
            ->get();

        $byRoot = $backOrders->groupBy(fn ($bo) => (int) $bo->back_order_root_receiving_report_id);

        return $rows->map(function ($row) use ($byRoot) {
            $lines = $byRoot->get((int) $row->receiving_report_id, collect());
            $open = $lines->filter(fn ($bo) => BackOrders::isUnresolved($bo->back_order_status));
            $total = $lines->count();

            return (object) [
                'id' => (int) $row->receiving_report_id,
                'number' => $row->receiving_report_form_number ?? ('RR #'.$row->receiving_report_id),
                'supplier' => $row->supplier_name ?: 'Supplier not set',
                'counted_at' => !empty($row->counted_at) ? Carbon::parse($row->counted_at) : null,
                'total' => $total,
                'settled' => $total - $open->count(),
                'open' => $open->count(),
                'missing_units' => (int) $open->where('back_order_type', 'short')->sum('back_order_quantity'),
                'damaged_units' => (int) $open->where('back_order_type', 'damaged')->sum('back_order_quantity'),
                'awaiting_count' => $open->where('back_order_status', BackOrders::STATUS_RECEIVING)->count(),
                'percent' => $total > 0 ? (int) round((($total - $open->count()) / $total) * 100) : 0,
            ];
        });
    }

    private static function supplierIssues(Collection $backOrders): array
    {
        $rows = $backOrders
            ->groupBy(fn ($bo) => trim((string) ($bo->back_order_supplier_name ?? '')) ?: 'Supplier not set')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'lines' => $rows->count(),
                'missing' => (int) $rows->where('back_order_type', 'short')->sum('back_order_quantity'),
                'damaged' => (int) $rows->where('back_order_type', 'damaged')->sum('back_order_quantity'),
                'open' => $rows->filter(fn ($bo) => BackOrders::isUnresolved($bo->back_order_status))->count(),
            ])
            ->map(fn ($row) => $row + ['units' => $row['missing'] + $row['damaged']])
            ->sortByDesc('units')
            ->take(5)
            ->values();

        return [
            'rows' => $rows->all(),
            'maxUnits' => max(1, (int) $rows->max('units')),
        ];
    }

    /**
     * Last seven days: reports submitted to Receiving vs. second counts finished.
     *
     * @return list<array{date: Carbon, arrived: int, counted: int}>
     */
    private static function weekStrip(): array
    {
        $start = today()->subDays(6);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $days[$date->toDateString()] = ['date' => $date, 'arrived' => 0, 'counted' => 0];
        }

        if (!self::hasRr()) {
            return array_values($days);
        }

        $submittedExpr = self::submittedExpr();
        $arrived = DB::table(self::RR)
            ->where('receiving_report_status', '!=', 'Draft')
            ->whereRaw('DATE('.$submittedExpr.') >= ?', [$start->toDateString()])
            ->selectRaw('DATE('.$submittedExpr.') as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $counted = self::rrColumn('receiving_report_second_count_at')
            ? DB::table(self::RR)
                ->whereNotNull('receiving_report_second_count_at')
                ->whereDate('receiving_report_second_count_at', '>=', $start->toDateString())
                ->selectRaw('DATE(receiving_report_second_count_at) as d, COUNT(*) as c')
                ->groupBy('d')
                ->pluck('c', 'd')
            : collect();

        foreach ($days as $key => $day) {
            $days[$key]['arrived'] = (int) ($arrived[$key] ?? 0);
            $days[$key]['counted'] = (int) ($counted[$key] ?? 0);
        }

        return array_values($days);
    }

    private static function recentActivity(): Collection
    {
        if (!Schema::hasTable('receiving_logs_table')) {
            return collect();
        }

        $query = DB::table('receiving_logs_table as log')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'log.receiving_log_officer_id');
        $select = ['log.*', 'u.user_full_name as officer_name'];
        if (self::hasRr()) {
            $query->leftJoin(self::RR.' as rr', 'rr.receiving_report_id', '=', 'log.receiving_report_id');
            $select[] = 'rr.receiving_report_form_number';
        }

        return $query->select($select)
            ->orderByDesc('log.receiving_log_created_at')
            ->orderByDesc('log.receiving_log_id')
            ->limit(6)
            ->get()
            ->map(function ($log) {
                $action = (string) ($log->receiving_log_action ?? 'Update');
                $status = strtolower((string) ($log->receiving_log_status ?? ''));
                $lower = strtolower($action);

                $tone = match (true) {
                    $status === 'returned' || str_contains($lower, 'return'),
                    $status === 'incomplete' || str_contains($lower, 'back order') => 'accent',
                    in_array($status, ['delivered', 'completed', 'accepted'], true) || str_contains($lower, 'second count') => 'ink',
                    default => 'soft',
                };

                return (object) [
                    'action' => $action,
                    'remarks' => $log->receiving_log_remarks ?? null,
                    'rr_number' => $log->receiving_report_form_number ?? null,
                    'officer' => $log->officer_name ?? null,
                    'at' => !empty($log->receiving_log_created_at) ? Carbon::parse($log->receiving_log_created_at) : null,
                    'tone' => $tone,
                ];
            });
    }
}
