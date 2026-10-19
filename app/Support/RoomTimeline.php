<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Lifecycle timeline for a room: setup, equipment moves, inspections,
 * maintenance, reports, and borrowing — same event shape as EquipmentTimeline.
 */
class RoomTimeline
{
    public static function eventTypes(): array
    {
        return [
            'created' => 'Room created',
            'setup' => 'Room setup',
            'equipment' => 'Equipment',
            'inspection' => 'Inspection',
            'maintenance' => 'Maintenance',
            'report' => 'Report',
            'borrowing' => 'Borrowing',
        ];
    }

    public static function stages(): array
    {
        return [
            'created' => 'Created',
            'setup' => 'Setup & changes',
            'inventory' => 'Equipment',
            'inspection' => 'Inspections',
            'maintenance' => 'Maintenance',
            'report' => 'Reports',
            'borrowing' => 'Borrowing',
        ];
    }

    /**
     * @param  array{from?:mixed,to?:mixed,search?:string,types?:mixed}  $filters
     * @return array{
     *     room: ?array,
     *     events: list<array<string,mixed>>,
     *     counts: array<string,int>,
     *     stages: array<string,string>,
     *     stage_counts: array<string,int>,
     *     summary: array<string,mixed>
     * }
     */
    public static function forRoom(int $roomId, array $filters = []): array
    {
        $room = self::loadRoom($roomId);
        if (! $room) {
            return [
                'room' => null,
                'events' => [],
                'counts' => [],
                'stages' => self::stages(),
                'stage_counts' => [],
                'summary' => self::emptySummary(),
            ];
        }

        $from = self::parseFilterDate($filters['from'] ?? null, true);
        $to = self::parseFilterDate($filters['to'] ?? null, false);
        $search = trim((string) ($filters['search'] ?? ''));

        $events = collect()
            ->merge(self::createdEvents($room))
            ->merge(self::activityEvents($roomId))
            ->merge(self::equipmentTransferEvents($roomId))
            ->merge(self::inspectionEvents($roomId))
            ->merge(self::maintenanceEvents($roomId))
            ->merge(self::reportEvents($roomId))
            ->merge(self::borrowingEvents($roomId));

        $events = self::filterEvents($events, $from, $to)
            ->map(fn (array $event) => self::withStage($event))
            ->when($search !== '', fn (Collection $c) => $c->filter(function (array $event) use ($search) {
                $haystack = strtolower(implode(' ', [
                    $event['title'] ?? '',
                    $event['description'] ?? '',
                    $event['type_label'] ?? '',
                    $event['stage_label'] ?? '',
                    $event['meta']['actor'] ?? '',
                    $event['meta']['equipment'] ?? '',
                    $event['meta']['ticket'] ?? '',
                ]));

                return str_contains($haystack, strtolower($search));
            }))
            ->sortByDesc(fn ($event) => $event['occurred_at'] ?? '')
            ->values();

        $counts = $events
            ->groupBy('type')
            ->map(fn (Collection $group) => $group->count())
            ->all();

        $stageCounts = collect(self::stages())
            ->map(fn ($label, $stage) => $events->where('stage', $stage)->count())
            ->filter()
            ->all();

        return [
            'room' => $room,
            'events' => $events->all(),
            'counts' => $counts,
            'stages' => self::stages(),
            'stage_counts' => $stageCounts,
            'summary' => self::summary($roomId, $room),
        ];
    }

    private static function loadRoom(int $roomId): ?array
    {
        if (! Schema::hasTable('rooms_table')) {
            return null;
        }

        $row = DB::table('rooms_table')
            ->leftJoin('floors_table', 'rooms_table.room_floor_id', '=', 'floors_table.floor_id')
            ->leftJoin('buildings_table', 'floors_table.floor_building_id', '=', 'buildings_table.building_id')
            ->where('rooms_table.room_id', $roomId)
            ->select(
                'rooms_table.*',
                'floors_table.floor_level',
                'buildings_table.building_name'
            )
            ->first();

        if (! $row) {
            return null;
        }

        $departments = RoomDepartments::forRooms([$roomId])[$roomId] ?? [];

        return [
            'id' => (int) $row->room_id,
            'name' => (string) $row->room_name,
            'type' => (string) ($row->room_type ?: 'Room'),
            'status' => (string) ($row->room_status ?: RoomStatuses::NORMAL),
            'floor' => (string) ($row->floor_level ?: ''),
            'building' => (string) ($row->building_name ?: ''),
            'created_at' => self::dateString($row->room_created_at ?? null),
            'updated_at' => self::dateString($row->room_updated_at ?? null),
            'departments' => $departments,
        ];
    }

    private static function emptySummary(): array
    {
        return [
            'status' => RoomStatuses::NORMAL,
            'open_reports' => 0,
            'times_reported' => 0,
            'equipment_count' => 0,
            'for_replacement' => 0,
            'last_reported_at' => null,
            'last_inspection_at' => null,
        ];
    }

    private static function summary(int $roomId, array $room): array
    {
        $summary = self::emptySummary();
        $summary['status'] = $room['status'] ?? RoomStatuses::NORMAL;

        if (Schema::hasTable('equipment_table')) {
            $summary['equipment_count'] = (int) DB::table('equipment_table')
                ->where('equipment_room_id', $roomId)
                ->where(function ($q) {
                    $q->whereNull('equipment_inventory_status')
                        ->orWhereNotIn('equipment_inventory_status', ['Disposed']);
                })
                ->count();

            $summary['for_replacement'] = (int) DB::table('equipment_table')
                ->where('equipment_room_id', $roomId)
                ->where('equipment_inventory_status', 'For Replacement')
                ->count();
        }

        if (Schema::hasTable('reports_table')) {
            $reports = DB::table('reports_table')
                ->where(fn ($q) => ReportGrouping::whereTicketTouchesRoom($q, $roomId))
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($q) => $q->where('report_is_archived', false)
                );

            $summary['times_reported'] = (int) (clone $reports)->count();
            $summary['open_reports'] = (int) (clone $reports)
                ->whereNotIn('report_current_status', ['Resolved', 'Rejected'])
                ->count();
            $summary['last_reported_at'] = self::dateString(
                (clone $reports)->max('report_submitted_at')
            );
        }

        if (Schema::hasTable(RoomInspections::LOGS)) {
            $summary['last_inspection_at'] = self::dateString(
                DB::table(RoomInspections::LOGS)
                    ->where('room_inspection_log_room_id', $roomId)
                    ->max('room_inspection_log_inspected_at')
            );
        }

        return $summary;
    }

    private static function createdEvents(array $room): Collection
    {
        if (empty($room['created_at'])) {
            return collect();
        }

        $deptLabel = RoomDepartments::label($room['departments'] ?? []);

        return collect([
            self::makeEvent(
                'created',
                $room['created_at'],
                'Room created',
                trim(($room['building'] ? $room['building'].' · ' : '').($room['floor'] ?: '').' · '.$room['type'])
                    .($deptLabel !== '—' ? ' · Departments: '.$deptLabel : ''),
                ['dot' => 'bg-indigo-500']
            ),
        ]);
    }

    private static function activityEvents(int $roomId): Collection
    {
        if (! Schema::hasTable('room_activity_logs_table')) {
            return collect();
        }

        $actorNames = [];
        if (Schema::hasTable('users_table')) {
            // filled below after query
        }

        $rows = DB::table('room_activity_logs_table')
            ->where('room_id', $roomId)
            // Transfer history already covers these with richer room-to-room detail.
            ->whereNotIn('activity_type', ['equipment_transfer_in', 'equipment_transfer_out'])
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        $userIds = $rows->pluck('user_id')->filter()->unique()->values();
        if ($userIds->isNotEmpty() && Schema::hasTable('users_table')) {
            $actorNames = DB::table('users_table')
                ->whereIn('user_id', $userIds->all())
                ->pluck('user_full_name', 'user_id')
                ->all();
        }

        $equipmentNames = [];
        $equipmentIds = $rows->pluck('equipment_id')->filter()->unique()->values();
        if ($equipmentIds->isNotEmpty() && Schema::hasTable('equipment_table')) {
            $equipmentNames = DB::table('equipment_table')
                ->whereIn('equipment_id', $equipmentIds->all())
                ->pluck('equipment_name', 'equipment_id')
                ->all();
        }

        return $rows->map(function ($row) use ($actorNames, $equipmentNames) {
            $type = match ((string) ($row->activity_type ?? '')) {
                'room_updated' => 'setup',
                'equipment_added', 'equipment_transfer_in', 'equipment_transfer_out',
                'equipment_updated', 'equipment_archived' => 'equipment',
                default => 'setup',
            };

            $dot = match ((string) ($row->activity_type ?? '')) {
                'equipment_transfer_in' => 'bg-violet-500',
                'equipment_transfer_out' => 'bg-violet-400',
                'equipment_added' => 'bg-sky-500',
                'equipment_archived' => 'bg-slate-500',
                'room_updated' => 'bg-amber-500',
                default => 'bg-slate-400',
            };

            $equipment = $equipmentNames[(int) ($row->equipment_id ?? 0)] ?? null;

            return self::makeEvent(
                $type,
                $row->created_at,
                (string) ($row->activity_title ?: 'Room activity'),
                (string) ($row->activity_description ?: ''),
                [
                    'dot' => $dot,
                    'actor' => $actorNames[(int) ($row->user_id ?? 0)] ?? null,
                    'equipment' => $equipment,
                    'activity_type' => $row->activity_type,
                ]
            );
        });
    }

    private static function equipmentTransferEvents(int $roomId): Collection
    {
        if (! Schema::hasTable('equipment_transfer_history_table')) {
            return collect();
        }

        $rows = DB::table('equipment_transfer_history_table as th')
            ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 'th.equipment_id')
            ->leftJoin('rooms_table as from_room', 'from_room.room_id', '=', 'th.from_room_id')
            ->leftJoin('rooms_table as to_room', 'to_room.room_id', '=', 'th.to_room_id')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'th.transferred_by')
            ->where(function ($q) use ($roomId) {
                $q->where('th.from_room_id', $roomId)
                    ->orWhere('th.to_room_id', $roomId);
            })
            ->orderByDesc('th.created_at')
            ->limit(200)
            ->get([
                'th.*',
                'e.equipment_name',
                'from_room.room_name as from_room_name',
                'to_room.room_name as to_room_name',
                'u.user_full_name as transferred_by_name',
            ]);

        return $rows->map(function ($row) use ($roomId) {
            $into = (int) ($row->to_room_id ?? 0) === $roomId;
            $other = $into
                ? ($row->from_room_name ?: 'another room')
                : ($row->to_room_name ?: 'another room');
            $name = $row->equipment_name ?: 'Equipment';

            return self::makeEvent(
                'equipment',
                $row->created_at,
                $into ? 'Equipment received' : 'Equipment transferred out',
                $into
                    ? "{$name} moved in from {$other}."
                    : "{$name} moved out to {$other}.",
                [
                    'dot' => $into ? 'bg-violet-500' : 'bg-violet-400',
                    'actor' => $row->transferred_by_name,
                    'equipment' => $name,
                    'transferred_by' => $row->transferred_by_name,
                ]
            );
        });
    }

    private static function inspectionEvents(int $roomId): Collection
    {
        if (! Schema::hasTable(RoomInspections::LOGS)) {
            return collect();
        }

        $rows = DB::table(RoomInspections::LOGS.' as log')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'log.room_inspection_log_inspected_by')
            ->where('log.room_inspection_log_room_id', $roomId)
            ->orderByDesc(DB::raw('COALESCE(log.room_inspection_log_inspected_at, log.room_inspection_log_scheduled_for)'))
            ->limit(150)
            ->get([
                'log.*',
                'u.user_full_name as inspector_name',
            ]);

        return $rows->map(function ($row) {
            $result = (string) ($row->room_inspection_log_result ?: 'Completed');
            $issues = (int) ($row->room_inspection_log_issue_count ?? 0);
            $title = $row->room_inspection_log_title
                ? (string) $row->room_inspection_log_title
                : 'Room inspection';

            $parts = array_filter([
                'Result: '.$result,
                $issues > 0 ? $issues.' '.Str::plural('issue', $issues) : 'No issues logged',
                filled($row->room_inspection_log_findings) ? 'Findings: '.$row->room_inspection_log_findings : null,
                filled($row->room_inspection_log_action_taken) ? 'Action: '.$row->room_inspection_log_action_taken : null,
            ]);

            return self::makeEvent(
                'inspection',
                $row->room_inspection_log_inspected_at ?: $row->room_inspection_log_scheduled_for,
                $title,
                implode(' · ', $parts),
                [
                    'dot' => $issues > 0 || strcasecmp($result, 'Failed') === 0 ? 'bg-amber-500' : 'bg-emerald-500',
                    'actor' => $row->inspector_name,
                    'result' => $result,
                    'issue_count' => $issues,
                ]
            );
        });
    }

    private static function maintenanceEvents(int $roomId): Collection
    {
        $events = collect();

        if (Schema::hasTable('equipment_maintenance_history_table') && Schema::hasTable('equipment_table')) {
            $history = DB::table('equipment_maintenance_history_table as h')
                ->join('equipment_table as e', 'e.equipment_id', '=', 'h.equipment_maintenance_equipment_id')
                ->leftJoin('users_table as u', 'u.user_id', '=', 'h.equipment_maintenance_personnel_id')
                ->where('e.equipment_room_id', $roomId)
                ->orderByDesc(DB::raw('COALESCE(h.equipment_maintenance_completed_at, h.equipment_maintenance_created_at)'))
                ->limit(150)
                ->get([
                    'h.*',
                    'e.equipment_name',
                    'u.user_full_name as performed_by_name',
                ]);

            foreach ($history as $row) {
                $name = $row->equipment_name ?: 'Equipment';
                $status = (string) ($row->equipment_maintenance_status ?: 'Resolved');
                $title = 'Maintenance · '.$status;
                $desc = collect([
                    $name,
                    filled($row->equipment_maintenance_findings ?? null) ? 'Findings: '.$row->equipment_maintenance_findings : null,
                    filled($row->equipment_maintenance_repair_action ?? null) ? 'Action: '.$row->equipment_maintenance_repair_action : null,
                ])->filter()->implode(' · ');

                $meta = [
                    'dot' => 'bg-orange-500',
                    'actor' => $row->performed_by_name,
                    'equipment' => $name,
                ];
                if ($row->equipment_maintenance_repair_cost !== null) {
                    $meta['repair_cost'] = (float) $row->equipment_maintenance_repair_cost;
                }
                if ($row->equipment_maintenance_downtime_hours !== null) {
                    $meta['downtime_hours'] = (float) $row->equipment_maintenance_downtime_hours;
                }
                if (filled($row->equipment_maintenance_parts_used ?? null)) {
                    $meta['parts_used'] = (string) $row->equipment_maintenance_parts_used;
                }

                $events->push(self::makeEvent(
                    'maintenance',
                    $row->equipment_maintenance_completed_at ?: $row->equipment_maintenance_created_at,
                    $title,
                    $desc ?: "Work recorded on {$name}.",
                    $meta
                ));
            }
        }

        if (Schema::hasTable('maintenance_schedules_table') && Schema::hasTable('equipment_table')) {
            $schedules = DB::table('maintenance_schedules_table as s')
                ->join('equipment_table as e', 'e.equipment_id', '=', 's.maintenance_schedule_equipment_id')
                ->where('e.equipment_room_id', $roomId)
                ->whereIn('s.maintenance_schedule_status', ['Active', 'Overdue'])
                ->whereNotNull('s.maintenance_schedule_next_date')
                ->orderByDesc('s.maintenance_schedule_next_date')
                ->limit(80)
                ->get([
                    's.*',
                    'e.equipment_name',
                ]);

            foreach ($schedules as $row) {
                $name = $row->equipment_name ?: 'Equipment';
                $status = (string) ($row->maintenance_schedule_status ?: 'Active');
                $overdue = $status === 'Overdue'
                    || Carbon::parse($row->maintenance_schedule_next_date)->lt(today());

                $events->push(self::makeEvent(
                    'maintenance',
                    $row->maintenance_schedule_next_date,
                    ($row->maintenance_schedule_title ?: 'Scheduled maintenance').($overdue ? ' (overdue)' : ''),
                    "{$name} · ".($row->maintenance_schedule_frequency ?: 'Schedule'),
                    [
                        'dot' => $overdue ? 'bg-rose-500' : 'bg-orange-400',
                        'equipment' => $name,
                        'status' => $overdue ? 'Overdue' : $status,
                    ]
                ));
            }
        }

        return $events;
    }

    private static function reportEvents(int $roomId): Collection
    {
        if (! Schema::hasTable('reports_table')) {
            return collect();
        }

        $rows = DB::table('reports_table')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
            ->where(fn ($q) => ReportGrouping::whereTicketTouchesRoom($q, $roomId))
            ->when(
                Schema::hasColumn('reports_table', 'report_is_archived'),
                fn ($q) => $q->where('reports_table.report_is_archived', false)
            )
            ->orderByDesc('reports_table.report_submitted_at')
            ->limit(250)
            ->get([
                'reports_table.*',
                'equipment_table.equipment_name',
                'reporters_table.reporter_full_name',
            ]);

        return $rows->map(function ($row) {
            $status = (string) ($row->report_current_status ?: 'Pending');
            $ticket = ReportGrouping::ticketCode($row);
            $equipment = $row->equipment_name
                ?: ($row->report_unlisted_equipment_name ?: null);
            $severity = ReportSeverity::meta(ReportSeverity::forReport($row))['label'] ?? null;
            $problem = trim((string) ($row->report_problem_description ?: ''));

            $parts = array_filter([
                $equipment ? 'Equipment: '.$equipment : null,
                $severity ? 'Severity: '.$severity : null,
                'Status: '.$status,
                $problem !== '' ? $problem : null,
            ]);

            $dot = match ($status) {
                'Resolved' => 'bg-emerald-500',
                'For Replacement' => 'bg-amber-500',
                'Processing' => 'bg-sky-500',
                'Rejected' => 'bg-slate-400',
                default => 'bg-rose-500',
            };

            return self::makeEvent(
                'report',
                $row->report_submitted_at,
                $ticket.' · '.$status,
                implode(' · ', $parts),
                [
                    'dot' => $dot,
                    'actor' => $row->reporter_full_name ?: ($row->report_reporter_employee_id ?: null),
                    'equipment' => $equipment,
                    'ticket' => $ticket,
                    'status' => $status,
                ]
            );
        });
    }

    private static function borrowingEvents(int $roomId): Collection
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return collect();
        }

        $query = DB::table('borrowing_records_table as b')
            ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 'b.borrowing_equipment_id');

        $hasFrom = Schema::hasColumn('borrowing_records_table', 'borrowing_from_room_id');
        if ($hasFrom) {
            $query->where(function ($q) use ($roomId) {
                $q->where('b.borrowing_from_room_id', $roomId)
                    ->orWhere('e.equipment_room_id', $roomId);
            });
        } else {
            $query->where('e.equipment_room_id', $roomId);
        }

        $rows = $query
            ->orderByDesc(DB::raw('COALESCE(b.borrowing_date, b.borrowing_created_at)'))
            ->limit(100)
            ->get([
                'b.*',
                'e.equipment_name',
            ]);

        return $rows->flatMap(function ($row) {
            $events = collect();
            $name = $row->equipment_name ?: 'Equipment';
            $borrower = $row->borrowing_borrower_name ?: 'Borrower';
            $status = (string) ($row->borrowing_status ?: 'Borrowed');

            if (! empty($row->borrowing_date) || ! empty($row->borrowing_created_at)) {
                $events->push(self::makeEvent(
                    'borrowing',
                    $row->borrowing_date ?: $row->borrowing_created_at,
                    'Borrowed from room',
                    "{$name} issued to {$borrower}.",
                    [
                        'dot' => $status === 'Overdue' ? 'bg-rose-500' : 'bg-cyan-500',
                        'actor' => $borrower,
                        'equipment' => $name,
                        'borrower' => $borrower,
                    ]
                ));
            }

            if (! empty($row->borrowing_actual_return_date)) {
                $events->push(self::makeEvent(
                    'borrowing',
                    $row->borrowing_actual_return_date,
                    'Returned to room',
                    "{$name} returned by {$borrower}.",
                    [
                        'dot' => 'bg-emerald-500',
                        'actor' => $borrower,
                        'equipment' => $name,
                        'borrower' => $borrower,
                    ]
                ));
            }

            return $events;
        });
    }

    private static function withStage(array $event): array
    {
        $stage = match ($event['type'] ?? '') {
            'created' => 'created',
            'setup' => 'setup',
            'equipment' => 'inventory',
            'inspection' => 'inspection',
            'maintenance' => 'maintenance',
            'report' => 'report',
            'borrowing' => 'borrowing',
            default => 'setup',
        };

        $event['stage'] = $stage;
        $event['stage_label'] = self::stages()[$stage] ?? ucfirst($stage);

        return $event;
    }

    private static function makeEvent(
        string $type,
        $occurredAt,
        string $title,
        string $description,
        array $meta = []
    ): array {
        return [
            'type' => $type,
            'type_label' => self::eventTypes()[$type] ?? ucfirst($type),
            'occurred_at' => self::dateString($occurredAt),
            'title' => $title,
            'description' => $description,
            'meta' => $meta,
        ];
    }

    private static function parseFilterDate(mixed $value, bool $startOfDay): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $startOfDay ? $value->copy()->startOfDay() : $value->copy()->endOfDay();
        }

        $date = Carbon::parse((string) $value);

        return $startOfDay ? $date->copy()->startOfDay() : $date->copy()->endOfDay();
    }

    private static function filterEvents(Collection $events, ?Carbon $from, ?Carbon $to): Collection
    {
        return $events->filter(function ($event) use ($from, $to) {
            if (empty($event['occurred_at'])) {
                return $from === null && $to === null;
            }

            $occurred = Carbon::parse($event['occurred_at']);

            if ($from && $occurred->lt($from)) {
                return false;
            }

            if ($to && $occurred->gt($to)) {
                return false;
            }

            return true;
        });
    }

    private static function dateString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
