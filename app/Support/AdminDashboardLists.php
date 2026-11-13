<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Short lists for the administrator dashboard.
 */
class AdminDashboardLists
{
    /**
     * @return array<string, array{total: int, rows: list<array<string, mixed>>}>
     */
    public static function build(): array
    {
        return [
            'borrows' => self::safe(fn () => self::overdueBorrows()),
            'schedules' => self::safe(fn () => self::overdueSchedules()),
            'disposed' => self::safe(fn () => self::disposedEquipment()),
            'rooms' => self::safe(fn () => self::roomsReportedToday()),
            'prs' => self::safe(fn () => self::latestProcurement()),
            'transfers' => self::safe(fn () => self::transferRequests()),
            'disposals' => self::safe(fn () => self::disposalRequests()),
        ];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function safe(callable $callback): array
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            return ['total' => 0, 'rows' => []];
        }
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function overdueBorrows(): array
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return ['total' => 0, 'rows' => []];
        }

        $query = DB::table('borrowing_records_table as b')
            ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 'b.borrowing_equipment_id')
            ->where(function ($q) {
                $q->where('b.borrowing_status', 'Overdue')
                    ->orWhere(function ($late) {
                        $late->where('b.borrowing_status', 'Borrowed')
                            ->whereNotNull('b.borrowing_expected_return_date')
                            ->whereDate('b.borrowing_expected_return_date', '<', today());
                    });
            });

        $total = (int) (clone $query)->count();
        $rows = $query
            ->orderByDesc('b.borrowing_record_id')
            ->limit(3)
            ->get(array_merge([
                'b.borrowing_record_id',
                'b.borrowing_borrower_name',
                'b.borrowing_expected_return_date',
                'e.equipment_name',
            ], self::equipmentColumns('e')))
            ->map(fn ($row) => [
                'title' => $row->equipment_name ?: 'Borrowed item',
                'meta' => self::line(
                    $row->borrowing_borrower_name ?: null,
                    EquipmentIdentity::label($row) ?: null,
                    self::date($row->borrowing_expected_return_date, 'Due ')
                ),
                'href' => route('admin.borrowing.index', ['status' => 'Overdue']),
            ])
            ->all();

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function overdueSchedules(): array
    {
        if (! Schema::hasTable('maintenance_schedules_table')) {
            return ['total' => 0, 'rows' => []];
        }

        $query = DB::table('maintenance_schedules_table as s')
            ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 's.maintenance_schedule_equipment_id')
            ->where(function ($q) {
                $q->where('s.maintenance_schedule_status', 'Overdue')
                    ->orWhere(function ($active) {
                        $active->where('s.maintenance_schedule_status', 'Active')
                            ->whereDate('s.maintenance_schedule_next_date', '<', today());
                    });
            });

        $total = (int) (clone $query)->count();
        $rows = $query
            ->orderBy('s.maintenance_schedule_next_date')
            ->limit(3)
            ->get(array_merge([
                's.maintenance_schedule_id',
                's.maintenance_schedule_title',
                's.maintenance_schedule_next_date',
                'e.equipment_name',
            ], self::equipmentColumns('e')))
            ->map(fn ($row) => [
                'title' => $row->maintenance_schedule_title ?: ($row->equipment_name ?: 'Schedule'),
                'meta' => self::line(
                    $row->equipment_name ?: null,
                    EquipmentIdentity::label($row) ?: null,
                    self::date($row->maintenance_schedule_next_date, 'Due ')
                ),
                'href' => route('admin.operations.schedules', ['filter' => 'overdue']),
            ])
            ->all();

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function disposedEquipment(): array
    {
        if (! Schema::hasTable('disposal_records_table') || ! Schema::hasTable('equipment_table')) {
            return ['total' => 0, 'rows' => []];
        }

        $query = DB::table('disposal_records_table as d')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'd.disposal_equipment_id');
        if (Schema::hasColumn('disposal_records_table', 'disposal_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('d.disposal_is_archived')->orWhere('d.disposal_is_archived', 0);
            });
        }

        $total = (int) (clone $query)->count();
        $rows = $query
            ->orderByDesc('d.disposal_disposed_at')
            ->limit(3)
            ->get(array_merge([
                'd.disposal_disposed_at',
                'e.equipment_name',
            ], self::equipmentColumns('e')))
            ->map(fn ($row) => [
                'title' => $row->equipment_name ?: 'Disposed equipment',
                'meta' => self::line(
                    EquipmentIdentity::label($row) ?: null,
                    self::date($row->disposal_disposed_at)
                ),
                'href' => route('admin.disposal.index'),
            ])
            ->all();

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function roomsReportedToday(): array
    {
        if (! Schema::hasTable('reports_table') || ! Schema::hasTable('rooms_table') || ! Schema::hasColumn('reports_table', 'report_room_id')) {
            return ['total' => 0, 'rows' => []];
        }

        $dateColumn = Schema::hasColumn('reports_table', 'report_submitted_at')
            ? 'reports_table.report_submitted_at'
            : (Schema::hasColumn('reports_table', 'report_created_at') ? 'reports_table.report_created_at' : null);
        if ($dateColumn === null) {
            return ['total' => 0, 'rows' => []];
        }

        $query = DB::table('reports_table')
            ->join('rooms_table', 'rooms_table.room_id', '=', 'reports_table.report_room_id')
            ->whereDate($dateColumn, today());
        if (Schema::hasColumn('reports_table', 'report_is_archived')) {
            $query->where(function ($q) {
                $q->where('reports_table.report_is_archived', 0)->orWhereNull('reports_table.report_is_archived');
            });
        }

        $grouped = $query
            ->groupBy('rooms_table.room_id', 'rooms_table.room_name')
            ->select('rooms_table.room_id', 'rooms_table.room_name', DB::raw('COUNT(*) as report_count'))
            ->orderByDesc('report_count')
            ->orderBy('rooms_table.room_name')
            ->get();

        return [
            'total' => $grouped->count(),
            'rows' => $grouped->take(5)->map(fn ($row) => [
                'title' => $row->room_name ?: 'Room',
                'meta' => ((int) $row->report_count === 1 ? '1 report' : ((int) $row->report_count).' reports').' today',
                'href' => route('admin.operations.reports'),
            ])->all(),
        ];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function latestProcurement(): array
    {
        if (! Schema::hasTable('requisition_issue_slip_table') || ! Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            return ['total' => 0, 'rows' => []];
        }

        $dateSql = Schema::hasColumn('requisition_issue_slip_table', 'ris_submitted_at')
            ? 'COALESCE(requisition_issue_slip_table.ris_submitted_at, requisition_issue_slip_table.ris_created_at)'
            : 'requisition_issue_slip_table.ris_created_at';

        $query = DB::table('requisition_issue_slip_table')
            ->whereNotNull('ris_request_no')
            ->where('ris_request_no', '!=', '');

        $total = (int) (clone $query)->count();
        $amountSql = Schema::hasTable('requisition_issue_slip_items_table')
            ? '(SELECT COALESCE(SUM(items.ris_total_amount), 0) FROM requisition_issue_slip_items_table items WHERE items.ris_id = requisition_issue_slip_table.ris_id)'
            : '0';

        $rows = $query
            ->orderByRaw($dateSql.' desc')
            ->limit(3)
            ->get([
                'ris_id',
                'ris_request_no',
                'ris_form_number',
                'ris_status',
                DB::raw($dateSql.' as activity_at'),
                DB::raw($amountSql.' as total_amount'),
            ])
            ->map(function ($row) {
                $pr = RequestExplorer::normalize((string) $row->ris_request_no);
                $href = preg_match('/^PR-\d{4,6}-\d{1,6}$/', $pr)
                    ? route('admin.requests.show', ['requestNo' => $pr])
                    : route('admin.procurement-review');

                return [
                    'title' => $pr !== '' ? $pr : ($row->ris_form_number ?: 'Procurement'),
                    'meta' => self::line($row->ris_form_number ?: null, $row->ris_status ?: null, self::date($row->activity_at)),
                    'amount' => (float) $row->total_amount,
                    'href' => $href,
                ];
            })
            ->all();

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function transferRequests(): array
    {
        if (! TransferRequests::tableReady()) {
            return ['total' => 0, 'rows' => []];
        }

        $pending = (int) DB::table('equipment_transfer_requests_table')
            ->where('transfer_request_status', TransferRequests::PENDING)
            ->count();
        $rows = DB::table('equipment_transfer_requests_table as t')
            ->where('t.transfer_request_status', TransferRequests::PENDING)
            ->leftJoin('users_table as u', 'u.user_id', '=', 't.transfer_request_requested_by')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 't.transfer_request_room_id')
            ->orderByDesc('t.transfer_request_requested_at')
            ->limit(3)
            ->get([
                't.transfer_request_id',
                't.transfer_request_status',
                't.transfer_request_equipment_ids',
                't.transfer_request_requested_at',
                'u.user_full_name',
                'r.room_name',
            ]);

        return [
            'total' => $pending,
            'rows' => $rows->map(fn ($row) => [
                'title' => self::equipmentSummary($row->transfer_request_equipment_ids ?? null),
                'meta' => self::line($row->transfer_request_status, $row->user_full_name, $row->room_name, self::date($row->transfer_request_requested_at)),
                'href' => route('admin.inventory.transfer-requests'),
            ])->all(),
        ];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    private static function disposalRequests(): array
    {
        if (! DisposalRequests::tableReady()) {
            return ['total' => 0, 'rows' => []];
        }

        $pending = (int) DB::table('equipment_disposal_requests_table')
            ->where('disposal_request_status', DisposalRequests::PENDING)
            ->count();
        $rows = DB::table('equipment_disposal_requests_table as d')
            ->where('d.disposal_request_status', DisposalRequests::PENDING)
            ->leftJoin('users_table as u', 'u.user_id', '=', 'd.disposal_request_requested_by')
            ->orderByDesc('d.disposal_request_requested_at')
            ->limit(3)
            ->get([
                'd.disposal_request_id',
                'd.disposal_request_status',
                'd.disposal_request_equipment_ids',
                'd.disposal_request_reason',
                'd.disposal_request_requested_at',
                'u.user_full_name',
            ]);

        return [
            'total' => $pending,
            'rows' => $rows->map(fn ($row) => [
                'title' => self::equipmentSummary($row->disposal_request_equipment_ids ?? null),
                'meta' => self::line($row->disposal_request_status, $row->disposal_request_reason, $row->user_full_name, self::date($row->disposal_request_requested_at)),
                'href' => route('admin.disposal.requests'),
            ])->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function equipmentColumns(string $alias): array
    {
        if (! Schema::hasTable('equipment_table')) {
            return [];
        }

        return array_values(array_map(
            fn (string $column) => $alias.'.'.substr($column, strlen('equipment_table.')),
            EquipmentIdentity::columns()
        ));
    }

    private static function equipmentSummary(mixed $json): string
    {
        $ids = array_values(array_filter(array_map('intval', (array) json_decode((string) $json, true))));
        if ($ids === [] || ! Schema::hasTable('equipment_table')) {
            return 'Equipment request';
        }

        $name = (string) DB::table('equipment_table')->where('equipment_id', $ids[0])->value('equipment_name');
        $label = $name !== '' ? $name : 'Equipment';
        $extra = count($ids) - 1;

        return $extra > 0 ? $label.' +'.$extra : $label;
    }

    private static function date(mixed $value, string $prefix = ''): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return $prefix.Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function line(mixed ...$parts): string
    {
        return collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter(fn ($part) => $part !== '')
            ->implode(' · ');
    }
}
