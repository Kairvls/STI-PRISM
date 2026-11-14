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
            'lifespan' => self::safe(fn () => self::nearestLifespanBroadcasts(false)),
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

        $hasSlip = Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no');
        $overdue = self::overdueBorrowQuery()
            ->orderByDesc('b.borrowing_record_id')
            ->get($hasSlip
                ? ['b.borrowing_record_id', 'b.borrowing_transaction_no']
                : ['b.borrowing_record_id']);

        $slips = [];
        foreach ($overdue as $row) {
            $slip = $hasSlip ? trim((string) ($row->borrowing_transaction_no ?? '')) : '';
            $key = $slip !== '' ? 'slip:'.$slip : 'row:'.(int) $row->borrowing_record_id;
            if (! isset($slips[$key])) {
                $slips[$key] = $slip;
            }
        }

        $latest = array_slice($slips, 0, 3, true);
        $slipNos = array_values(array_filter($latest, fn ($slip) => $slip !== ''));
        $soloIds = [];
        foreach ($latest as $key => $slip) {
            if ($slip === '' && preg_match('/^row:(\d+)$/', $key, $match)) {
                $soloIds[] = (int) $match[1];
            }
        }

        $grouped = [];
        if ($latest !== []) {
            $itemQuery = DB::table('borrowing_records_table as b')
                ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 'b.borrowing_equipment_id')
                ->where(function ($query) use ($slipNos, $soloIds, $hasSlip) {
                    $started = false;
                    if ($hasSlip && $slipNos !== []) {
                        $query->whereIn('b.borrowing_transaction_no', $slipNos);
                        $started = true;
                    }
                    if ($soloIds !== []) {
                        $started
                            ? $query->orWhereIn('b.borrowing_record_id', $soloIds)
                            : $query->whereIn('b.borrowing_record_id', $soloIds);
                    }
                })
                ->orderBy('b.borrowing_record_id')
                ->get(self::borrowPreviewColumns($hasSlip));

            foreach ($itemQuery as $row) {
                $slip = $hasSlip ? trim((string) ($row->borrowing_transaction_no ?? '')) : '';
                $key = $slip !== '' ? 'slip:'.$slip : 'row:'.(int) $row->borrowing_record_id;
                $grouped[$key][] = $row;
            }
        }

        $rows = [];
        foreach ($latest as $key => $slip) {
            $items = $grouped[$key] ?? [];
            if ($items === []) {
                continue;
            }
            $first = $items[0];
            $due = collect($items)
                ->filter(fn ($item) => in_array($item->borrowing_status, ['Overdue', 'Borrowed'], true))
                ->map(fn ($item) => $item->borrowing_expected_return_date ?? null)
                ->filter()
                ->sort()
                ->first();
            $count = count($items);
            $overdueDays = self::daysUnreturned($due);
            $identities = collect($items)
                ->map(fn ($item) => EquipmentIdentity::label($item))
                ->filter()
                ->unique()
                ->values();
            $rows[] = [
                'title' => $slip !== '' ? $slip : ($first->equipment_name ?: 'Borrowed item'),
                'meta' => self::line(
                    $first->borrowing_borrower_name ?: null,
                    $count === 1 ? ($identities->first() ?: 'No asset tag / serial') : ($count.' equipment'),
                    self::date($due, 'Due ')
                ),
                'overdue_days' => $overdueDays,
                'badge' => $overdueDays !== null
                    ? ($overdueDays === 1 ? '1d overdue' : $overdueDays.'d overdue')
                    : 'Overdue',
                'href' => $slip !== ''
                    ? route('admin.borrowing.index', ['slip' => $slip])
                    : route('admin.borrowing.index', ['record' => (int) $first->borrowing_record_id]),
                'preview' => [
                    'slip' => $slip !== '' ? $slip : ($first->equipment_name ?: 'Borrowed item'),
                    'borrower' => trim((string) ($first->borrowing_borrower_name ?? '')),
                    'due' => self::date($due, 'Due ') ?? '',
                    'overdue_days' => $overdueDays,
                    'event' => trim((string) ($first->borrowing_event_name ?? '')),
                    'href' => $slip !== ''
                        ? route('admin.borrowing.index', ['slip' => $slip])
                        : route('admin.borrowing.index', ['record' => (int) $first->borrowing_record_id]),
                    'items' => array_map(fn ($item) => [
                        'name' => trim((string) ($item->equipment_name ?? '')) ?: 'Equipment',
                        'identity' => EquipmentIdentity::label($item),
                        'status' => trim((string) ($item->borrowing_status ?? '')),
                        'overdue_days' => self::daysUnreturned($item->borrowing_expected_return_date ?? null, (string) ($item->borrowing_status ?? '')),
                        'qty' => max(1, (int) ($item->borrowing_quantity ?? 1)),
                    ], $items),
                ],
            ];
        }

        return ['total' => count($slips), 'rows' => $rows];
    }

    private static function overdueBorrowQuery()
    {
        return DB::table('borrowing_records_table as b')
            ->where(function ($q) {
                $q->where('b.borrowing_status', 'Overdue')
                    ->orWhere(function ($late) {
                        $late->where('b.borrowing_status', 'Borrowed')
                            ->whereNotNull('b.borrowing_expected_return_date')
                            ->whereDate('b.borrowing_expected_return_date', '<', today());
                    });
            });
    }

    /**
     * @return list<string>
     */
    private static function borrowPreviewColumns(bool $hasSlip): array
    {
        $columns = [
            'b.borrowing_record_id',
            'b.borrowing_borrower_name',
            'b.borrowing_expected_return_date',
            'b.borrowing_status',
            'e.equipment_name',
        ];
        if ($hasSlip) {
            $columns[] = 'b.borrowing_transaction_no';
        }
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_quantity')) {
            $columns[] = 'b.borrowing_quantity';
        }
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_event_name')) {
            $columns[] = 'b.borrowing_event_name';
        }

        return array_merge($columns, self::equipmentColumns('e'));
    }

    /**
     * Maintenance schedules for the dashboard calendar, across every stored due date.
     *
     * @return array{today: string, href: string, years: list<int>, events: list<array<string, string>>}
     */
    public static function maintenanceCalendar(): array
    {
        $today = today();
        $href = route('admin.schedules.all');
        $payload = [
            'today' => $today->toDateString(),
            'href' => $href,
            'years' => [(int) $today->year],
            'events' => [],
        ];

        $events = [];

        try {
            if (Schema::hasTable('maintenance_schedules_table')) {
                $query = DB::table('maintenance_schedules_table as s')
                    ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 's.maintenance_schedule_equipment_id')
                    ->whereNotNull('s.maintenance_schedule_next_date')
                    ->orderBy('s.maintenance_schedule_next_date')
                    ->orderBy('s.maintenance_schedule_id');

                $columns = array_merge([
                    's.maintenance_schedule_title',
                    's.maintenance_schedule_next_date',
                    's.maintenance_schedule_status',
                    's.maintenance_schedule_frequency',
                    'e.equipment_name',
                ], EquipmentIdentity::columns('e'));

                if (Schema::hasTable('rooms_table') && Schema::hasColumn('equipment_table', 'equipment_room_id')) {
                    $query->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id');
                    $columns[] = 'r.room_name';
                }

                foreach ($query->get($columns) as $row) {
                    $when = Carbon::parse($row->maintenance_schedule_next_date)->startOfDay();
                    $status = (string) ($row->maintenance_schedule_status ?: 'Active');
                    if (strcasecmp($status, 'Completed') !== 0 && $when->lt($today)) {
                        $status = 'Overdue';
                    }
                    $title = $row->maintenance_schedule_title ?: ($row->equipment_name ?: 'Equipment maintenance');
                    $events[] = [
                        'date' => $when->toDateString(),
                        'date_label' => $when->format('M j, Y'),
                        'title' => $title,
                        'equipment' => (string) ($row->equipment_name ?? ''),
                        'room' => isset($row->room_name) ? (string) $row->room_name : '',
                        'frequency' => (string) ($row->maintenance_schedule_frequency ?: ''),
                        'identity' => EquipmentIdentity::label($row),
                        'status' => $status,
                        'meta' => self::line(
                            ($row->equipment_name && $row->equipment_name !== $row->maintenance_schedule_title) ? $row->equipment_name : null,
                            isset($row->room_name) ? $row->room_name : null,
                            $row->maintenance_schedule_frequency ?: null
                        ),
                        'badge' => 'Equipment',
                        'tone' => 'equipment',
                        'href' => route('admin.schedules.all', ['search' => $title]),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        try {
            if (RoomInspections::ready()) {
                $roomHref = route('admin.room-inspections.index');
                $roomQuery = DB::table(RoomInspections::SCHEDULES.' as s')
                    ->whereNotNull('s.room_inspection_schedule_next_date')
                    ->orderBy('s.room_inspection_schedule_next_date');
                $roomColumns = [
                    's.room_inspection_schedule_title',
                    's.room_inspection_schedule_next_date',
                    's.room_inspection_schedule_status',
                    's.room_inspection_schedule_frequency',
                ];
                if (Schema::hasTable('rooms_table')) {
                    $roomQuery->leftJoin('rooms_table as r', 'r.room_id', '=', 's.room_inspection_schedule_room_id');
                    $roomColumns[] = 'r.room_name';
                }

                foreach ($roomQuery->get($roomColumns) as $row) {
                    $when = Carbon::parse($row->room_inspection_schedule_next_date)->startOfDay();
                    $status = (string) ($row->room_inspection_schedule_status ?: 'Active');
                    if (strcasecmp($status, 'Completed') !== 0 && $when->lt($today)) {
                        $status = 'Overdue';
                    }
                    $title = $row->room_inspection_schedule_title ?: 'Room inspection';
                    $events[] = [
                        'date' => $when->toDateString(),
                        'date_label' => $when->format('M j, Y'),
                        'title' => $title,
                        'equipment' => '',
                        'room' => isset($row->room_name) ? (string) $row->room_name : '',
                        'frequency' => (string) ($row->room_inspection_schedule_frequency ?: ''),
                        'identity' => '',
                        'status' => $status,
                        'meta' => self::line(
                            isset($row->room_name) ? $row->room_name : null,
                            $row->room_inspection_schedule_frequency ?: null
                        ),
                        'badge' => 'Room',
                        'tone' => 'room',
                        'href' => SchedulePortal::schedulesUrl('/rooms'),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        try {
            if (Schema::hasTable('semester_inspection_campaigns_table')) {
                $campaigns = DB::table('semester_inspection_campaigns_table')
                    ->whereNotNull('campaign_due_date')
                    ->whereNotIn('campaign_status', ['Cancelled', 'Archived'])
                    ->orderBy('campaign_due_date')
                    ->get([
                        'campaign_id',
                        'campaign_title',
                        'campaign_academic_year',
                        'campaign_semester',
                        'campaign_due_date',
                        'campaign_status',
                    ]);

                foreach ($campaigns as $row) {
                    $when = Carbon::parse($row->campaign_due_date)->startOfDay();
                    $status = (string) ($row->campaign_status ?: 'Active');
                    if (! in_array($status, ['Completed', 'Cancelled', 'Archived'], true) && $when->lt($today)) {
                        $status = 'Overdue';
                    }
                    $events[] = [
                        'date' => $when->toDateString(),
                        'date_label' => $when->format('M j, Y'),
                        'title' => $row->campaign_title ?: 'Semestral inspection',
                        'equipment' => '',
                        'room' => 'Campus',
                        'frequency' => 'Semestral',
                        'identity' => '',
                        'status' => $status,
                        'meta' => self::line($row->campaign_semester, $row->campaign_academic_year),
                        'badge' => 'Semestral',
                        'tone' => 'semestral',
                        'href' => SchedulePortal::semesterUrl('/'.$row->campaign_id),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        usort($events, fn (array $a, array $b) => [$a['date'], $a['title']] <=> [$b['date'], $b['title']]);

        $payload['years'] = collect($events)
            ->map(fn (array $event) => (int) substr($event['date'], 0, 4))
            ->push((int) $today->year)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $payload['events'] = $events;

        return $payload;
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
                $isPr = preg_match('/^PR-\d{4,6}-\d{1,6}$/', $pr) === 1;
                $href = $isPr
                    ? route('admin.requests.show', ['requestNo' => $pr])
                    : route('admin.procurement-review');

                return [
                    'title' => $pr !== '' ? $pr : ($row->ris_form_number ?: 'Procurement'),
                    'meta' => self::line($row->ris_form_number ?: null, $row->ris_status ?: null, self::date($row->activity_at)),
                    'amount' => (float) $row->total_amount,
                    'href' => $href,
                    'preview_url' => $isPr ? $href.'?preview=1' : null,
                ];
            })
            ->all();

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    public static function nearestLifespanBroadcasts(bool $maintenance = false): array
    {
        $ranked = EquipmentLifecycle::catalog();
        if ($ranked->isEmpty() && ! Schema::hasTable('equipment_table')) {
            return ['total' => 0, 'due' => [], 'rows' => []];
        }

        $shown = $ranked->take(3);

        return [
            'total' => $ranked->count(),
            'due' => self::reachedUsefulLife($ranked, $maintenance),
            'rows' => $shown->map(fn ($row) => self::lifespanPayload($row, $maintenance))->all(),
        ];
    }

    /**
     * Equipment that has already reached its useful life, with stock that can replace it.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $ranked
     * @return list<array<string, mixed>>
     */
    public static function reachedUsefulLife(\Illuminate\Support\Collection $ranked, bool $maintenance = false): array
    {
        return $ranked
            ->filter(fn ($row) => (int) ($row->years_remaining ?? 0) <= 0)
            ->values()
            ->map(function ($row) use ($maintenance) {
                $payload = self::lifespanPayload($row, $maintenance);
                $start = $row->lifespan_start ?? null;
                $reached = null;
                if ($start) {
                    try {
                        $reached = Carbon::parse($start)->addYears((int) $payload['lifespan'])->format('M j, Y');
                    } catch (\Throwable) {
                        $reached = null;
                    }
                }
                $payload['reached_on'] = $reached ?: 'Date not on file';
                $payload['stock'] = ReplacementStock::briefForEquipment((int) $payload['id']);

                return $payload;
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function lifespanPayload(object $row, bool $maintenance = false): array
    {
        $yearsLeft = (int) ($row->years_remaining ?? 0);
        $stored = $row->set_useful_life_years ?? null;
        $lifespanYears = $stored !== null && $stored !== ''
            ? (int) $stored
            : (int) ($row->useful_life_years ?? EquipmentLifecycle::DEFAULT_USEFUL_LIFE_YEARS);
        $suggestion = EquipmentLifecycle::suggestAction($yearsLeft, $row->equipment_inventory_status ?? null);
        $id = (int) $row->equipment_id;

        return [
            'id' => $id,
            'title' => $row->equipment_name ?: 'Equipment',
            'identity' => EquipmentIdentity::label($row) ?: 'No asset tag / serial',
            'room' => (string) ($row->room_name ?? ''),
            'age' => (int) ($row->age_years ?? 0),
            'lifespan' => $lifespanYears,
            'meta' => self::line(
                EquipmentIdentity::label($row) ?: 'No asset tag / serial',
                $row->room_name ?? null,
                'Age '.(int) ($row->age_years ?? 0).'y'
            ),
            'hint' => $suggestion['hint'],
            'tone' => $suggestion['tone'],
            'href' => $maintenance
                ? route('maintenance.lifespan-broadcasts.index', ['equipment' => $id])
                : route('admin.lifespan-broadcasts.index', ['equipment' => $id]),
            'update' => $maintenance
                ? route('maintenance.lifespan-broadcasts.update', $id)
                : route('admin.lifespan-broadcasts.update', $id),
        ];
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

    private static function daysUnreturned(mixed $due, ?string $status = null): ?int
    {
        if ($status !== null && ! in_array($status, ['Overdue', 'Borrowed'], true)) {
            return null;
        }
        if (blank($due)) {
            return null;
        }

        try {
            $dueDate = Carbon::parse($due)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($dueDate->greaterThanOrEqualTo(today())) {
            return null;
        }

        return max(1, (int) abs($dueDate->diffInDays(today())));
    }

    private static function unreturnedLabel(?int $days): ?string
    {
        if ($days === null) {
            return null;
        }

        return $days === 1 ? '1 day not returned' : $days.' days not returned';
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
