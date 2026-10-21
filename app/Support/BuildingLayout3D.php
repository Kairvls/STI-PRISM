<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Floors and rooms consumed by the 3D building viewer
 * in resources/views/partials/building-3d.
 *
 * Room color status (priority):
 * 1. Critical — open Critical-severity reports in the period → red
 * 2. Under Maintenance — active room inspection schedule covering the period → yellow
 * 3. Normal — High/Medium/Low reports or none → blue
 */
class BuildingLayout3D
{
    public const STATUS_CRITICAL = 'critical';

    public const STATUS_UNDER_MAINTENANCE = 'under-maintenance';

    public const STATUS_NORMAL = 'normal';

    public const COLOR_CRITICAL = '#EF4444';

    public const COLOR_UNDER_MAINTENANCE = '#F59E0B';

    public const COLOR_NORMAL = '#3B82F6';

    private const CLOSED_REPORT_STATUSES = ['Resolved', 'Rejected', 'For Replacement', 'Closed / Cancelled'];

    public static function floors(): Collection
    {
        return DB::table('floors_table')
            ->leftJoin('buildings_table', 'floors_table.floor_building_id', '=', 'buildings_table.building_id')
            ->select(
                'floors_table.floor_id',
                'floors_table.floor_building_id',
                'floors_table.floor_level',
                'buildings_table.building_name'
            )
            ->orderBy('floors_table.floor_id', 'asc')
            ->get();
    }

    /**
     * @param  array{preset?:string,from?:string,to?:string}|null  $period
     */
    public static function rooms(?array $period = null): Collection
    {
        [$from, $to, $preset] = self::resolvePeriod($period);
        $isLive = $preset === 'today' || ($from->isSameDay(today()) && $to->isSameDay(today()));

        $rooms = DB::table('rooms_table')
            ->leftJoin('floors_table', 'rooms_table.room_floor_id', '=', 'floors_table.floor_id')
            ->leftJoin('buildings_table', 'floors_table.floor_building_id', '=', 'buildings_table.building_id')
            ->where('rooms_table.room_is_archived', false)
            ->select(
                'rooms_table.*',
                'floors_table.floor_id',
                'floors_table.floor_building_id',
                'floors_table.floor_level',
                'buildings_table.building_id',
                'buildings_table.building_name'
            )
            ->selectSub(function ($query) {
                $query->from('equipment_table')
                    ->selectRaw('COALESCE(SUM(equipment_quantity), 0)')
                    ->whereColumn('equipment_table.equipment_room_id', 'rooms_table.room_id')
                    ->where('equipment_table.equipment_inventory_status', '!=', 'Disposed');
            }, 'equipment_count')
            ->orderBy('buildings_table.building_id', 'asc')
            ->orderBy('floors_table.floor_id', 'asc')
            ->orderBy('rooms_table.room_name', 'asc')
            ->get();

        $roomIds = $rooms->pluck('room_id')->map(fn ($id) => (int) $id)->all();
        $departments = RoomDepartments::forRooms($roomIds);
        $reportCounts = self::reportCountsByRoom($roomIds, $from, $to, $isLive);
        $maintenanceByRoom = self::underMaintenanceByRoom($roomIds, $from, $to, $isLive);
        $palette = RoomStatusPalette::colors();

        return $rooms->transform(function ($room) use ($reportCounts, $maintenanceByRoom, $departments, $from, $to, $preset, $palette) {
            $counts = $reportCounts[(int) $room->room_id] ?? [
                'total' => 0,
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ];

            $maintenance = $maintenanceByRoom[(int) $room->room_id] ?? [
                'schedules' => [],
                'scopes' => [],
                'next_date' => null,
            ];

            $room->departments = $departments[(int) $room->room_id] ?? [];
            $room->report_count = (int) $counts['total'];
            $room->critical_report_count = (int) $counts['critical'];
            $room->high_report_count = (int) $counts['high'];
            $room->medium_report_count = (int) $counts['medium'];
            $room->low_report_count = (int) $counts['low'];
            $room->under_maintenance_scheduled = $maintenance['schedules'] !== [];
            $room->maintenance_schedules = $maintenance['schedules'];
            $room->maintenance_scopes = $maintenance['scopes'];
            $room->room_inspection_next_date = $maintenance['next_date'];

            // Legacy keys kept so older JS still reads something sensible.
            $room->active_report_count = $room->report_count;
            $room->urgent_report_count = $room->critical_report_count;
            $room->maintenance_equipment_count = $room->under_maintenance_scheduled ? 1 : 0;

            if ($room->critical_report_count > 0) {
                $room->dashboard_status = self::STATUS_CRITICAL;
                $room->dashboard_label = RoomStatuses::CRITICAL;
                $room->status_color = $palette['critical'];
            } elseif ($room->under_maintenance_scheduled) {
                $room->dashboard_status = self::STATUS_UNDER_MAINTENANCE;
                $room->dashboard_label = RoomStatuses::UNDER_MAINTENANCE;
                $room->status_color = $palette['under_maintenance'];
            } else {
                $room->dashboard_status = self::STATUS_NORMAL;
                $room->dashboard_label = RoomStatuses::NORMAL;
                $room->status_color = $palette['normal'];
            }

            $room->period_preset = $preset;
            $room->period_from = $from->toDateString();
            $room->period_to = $to->toDateString();

            return $room;
        });
    }

    /**
     * Reports for one room in a period, Critical → Low.
     * Location comes from report items / equipment room, then report_room_id.
     *
     * @param  array{preset?:string,from?:string,to?:string}|null  $period
     * @return array{period:array{preset:string,from:string,to:string},counts:array<string,int>,reports:list<array<string,mixed>>}
     */
    public static function roomDetail(int $roomId, ?array $period = null): array
    {
        [$from, $to, $preset] = self::resolvePeriod($period);
        $cacheKey = 'building3d.room.'.$roomId.'.'.$preset.'.'.$from->toDateString().'.'.$to->toDateString();

        return Cache::remember($cacheKey, now()->addSeconds(20), function () use ($roomId, $from, $to, $preset) {
            return self::buildRoomDetail($roomId, $from, $to, $preset);
        });
    }

    /**
     * @return array{period:array{preset:string,from:string,to:string},counts:array<string,int>,reports:list<array<string,mixed>>}
     */
    private static function buildRoomDetail(int $roomId, Carbon $from, Carbon $to, string $preset): array
    {
        $isLive = $preset === 'today' || ($from->isSameDay(today()) && $to->isSameDay(today()));
        $hasSeverity = ReportSeverity::hasColumns();
        $reportIds = self::reportIdsLocatedInRoom($roomId, $from, $to, $isLive);

        $rows = $reportIds === []
            ? collect()
            : DB::table('reports_table')
                ->whereIn('reports_table.report_id', $reportIds)
                ->orderByRaw($hasSeverity
                    ? ReportSeverity::orderSql('reports_table')
                    : "CASE WHEN reports_table.report_urgency_level = 'Urgent' THEN 0 ELSE 1 END")
                ->orderByDesc('reports_table.report_submitted_at')
                ->limit(200)
                ->get(array_values(array_filter([
                    'reports_table.report_id',
                    'reports_table.report_current_status',
                    'reports_table.report_urgency_level',
                    'reports_table.report_submitted_at',
                    'reports_table.report_problem_description',
                    'reports_table.report_suggested_issue',
                    $hasSeverity ? 'reports_table.report_severity' : null,
                ])));

        $counts = ['total' => 0, 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        $reports = [];
        $seen = [];

        foreach ($rows as $row) {
            $id = (int) $row->report_id;
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $level = $hasSeverity
                ? ReportSeverity::forReport($row)
                : ReportSeverity::fromUrgency($row->report_urgency_level ?? null);
            $level = ReportSeverity::isLevel($level) ? $level : ReportSeverity::MEDIUM;
            $key = strtolower($level);
            $counts['total']++;
            if (isset($counts[$key])) {
                $counts[$key]++;
            }

            $description = trim((string) ($row->report_problem_description ?? ''));
            if ($description === '') {
                $description = trim((string) ($row->report_suggested_issue ?? ''));
            }

            $reports[] = [
                'id' => $id,
                'ticket_code' => ReportGrouping::ticketCode($row),
                'status' => (string) ($row->report_current_status ?? ''),
                'severity' => $level,
                'submitted_at' => $row->report_submitted_at
                    ? Carbon::parse($row->report_submitted_at)->format('M j, Y g:i A')
                    : '',
                'description' => mb_substr($description, 0, 160),
            ];
        }

        $maintenance = self::underMaintenanceByRoom([$roomId], $from, $to, $isLive)[$roomId] ?? [
            'schedules' => [],
            'scopes' => [],
            'next_date' => null,
        ];
        $underMaintenance = $maintenance['schedules'] !== [];
        $layoutStatus = $counts['critical'] > 0
            ? self::STATUS_CRITICAL
            : ($underMaintenance ? self::STATUS_UNDER_MAINTENANCE : self::STATUS_NORMAL);

        return [
            'period' => [
                'preset' => $preset,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'counts' => $counts,
            'under_maintenance_scheduled' => $underMaintenance,
            'maintenance_scopes' => $maintenance['scopes'],
            'maintenance_schedules' => $maintenance['schedules'],
            'room_inspection_next_date' => $maintenance['next_date'],
            'layout_status' => $layoutStatus,
            'layout_label' => match ($layoutStatus) {
                self::STATUS_CRITICAL => RoomStatuses::CRITICAL,
                self::STATUS_UNDER_MAINTENANCE => RoomStatuses::UNDER_MAINTENANCE,
                default => RoomStatuses::NORMAL,
            },
            'reports' => $reports,
        ];
    }

    /**
     * Compact status payload for recoloring the 3D scene after a period change.
     *
     * @param  array{preset?:string,from?:string,to?:string}|null  $period
     * @return list<array<string,mixed>>
     */
    public static function statusSnapshot(?array $period = null): array
    {
        [$from, $to, $preset] = self::resolvePeriod($period);
        $cacheKey = 'building3d.status.'.$preset.'.'.$from->toDateString().'.'.$to->toDateString();

        return Cache::remember($cacheKey, now()->addSeconds(20), function () use ($period) {
            return self::buildStatusSnapshot($period);
        });
    }

    /**
     * @param  array{preset?:string,from?:string,to?:string}|null  $period
     * @return list<array<string,mixed>>
     */
    private static function buildStatusSnapshot(?array $period): array
    {
        return self::rooms($period)->map(function ($room) {
            return [
                'id' => (int) $room->room_id,
                'status' => $room->dashboard_status,
                'label' => $room->dashboard_label,
                'color' => $room->status_color,
                'departments' => $room->departments,
                'reportCount' => (int) $room->report_count,
                'criticalReportCount' => (int) $room->critical_report_count,
                'highReportCount' => (int) $room->high_report_count,
                'mediumReportCount' => (int) $room->medium_report_count,
                'lowReportCount' => (int) $room->low_report_count,
                'underMaintenanceScheduled' => (bool) $room->under_maintenance_scheduled,
                'maintenanceScopes' => $room->maintenance_scopes ?? [],
                'maintenanceSchedules' => $room->maintenance_schedules ?? [],
                'roomInspectionNextDate' => $room->room_inspection_next_date,
                'periodPreset' => $room->period_preset,
                'periodFrom' => $room->period_from,
                'periodTo' => $room->period_to,
            ];
        })->values()->all();
    }

    /**
     * @param  array{preset?:string,from?:string,to?:string}|null  $period
     * @return array{0:Carbon,1:Carbon,2:string}
     */
    public static function resolvePeriod(?array $period): array
    {
        $preset = strtolower(trim((string) ($period['preset'] ?? 'today')));
        $fromInput = trim((string) ($period['from'] ?? ''));
        $toInput = trim((string) ($period['to'] ?? ''));

        if ($preset === 'custom' || ($fromInput !== '' && $toInput !== '')) {
            $from = Carbon::parse($fromInput !== '' ? $fromInput : today())->startOfDay();
            $to = Carbon::parse($toInput !== '' ? $toInput : $from)->endOfDay();
            if ($to->lt($from)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [$from, $to, 'custom'];
        }

        return match ($preset) {
            'week', 'this_week' => [today()->startOfWeek(), today()->endOfDay(), 'week'],
            'month', 'this_month' => [today()->startOfMonth(), today()->endOfDay(), 'month'],
            'year', 'this_year' => [today()->startOfYear(), today()->endOfDay(), 'year'],
            default => [today()->startOfDay(), today()->endOfDay(), 'today'],
        };
    }

    /**
     * Count reports per room using item/equipment location, then report_room_id.
     *
     * @param  list<int>  $roomIds
     * @return array<int, array{total:int,critical:int,high:int,medium:int,low:int}>
     */
    private static function reportCountsByRoom(array $roomIds, Carbon $from, Carbon $to, bool $isLive): array
    {
        if ($roomIds === []) {
            return [];
        }

        $hasSeverity = ReportSeverity::hasColumns();
        $hasItems = Schema::hasTable('report_items_table');
        $hasItemRoom = $hasItems && Schema::hasColumn('report_items_table', 'report_item_room_id');

        $query = DB::table('reports_table')
            ->where('reports_table.report_is_archived', false)
            ->whereBetween('reports_table.report_submitted_at', [
                $from->copy()->startOfDay()->toDateTimeString(),
                $to->copy()->endOfDay()->toDateTimeString(),
            ]);

        if ($isLive) {
            $query->whereNotIn('reports_table.report_current_status', self::CLOSED_REPORT_STATUSES);
        }

        if ($hasItems) {
            $query->leftJoin('report_items_table', 'report_items_table.report_id', '=', 'reports_table.report_id')
                ->leftJoin('equipment_table as item_equipment', 'item_equipment.equipment_id', '=', 'report_items_table.report_item_equipment_id');
        }

        $query->leftJoin('equipment_table as report_equipment', 'report_equipment.equipment_id', '=', 'reports_table.report_equipment_id');

        $locationExpr = $hasItemRoom
            ? 'COALESCE(report_items_table.report_item_room_id, item_equipment.equipment_room_id, report_equipment.equipment_room_id, reports_table.report_room_id)'
            : ($hasItems
                ? 'COALESCE(item_equipment.equipment_room_id, report_equipment.equipment_room_id, reports_table.report_room_id)'
                : 'COALESCE(report_equipment.equipment_room_id, reports_table.report_room_id)');

        $select = [
            'reports_table.report_id',
            'reports_table.report_urgency_level',
            DB::raw("{$locationExpr} as resolved_room_id"),
        ];
        if ($hasSeverity) {
            $select[] = 'reports_table.report_severity';
        }

        $rows = $query
            ->whereRaw("{$locationExpr} IN (".implode(',', array_map('intval', $roomIds)).')')
            ->get($select);

        $map = [];
        $seen = [];

        foreach ($rows as $row) {
            $roomId = (int) ($row->resolved_room_id ?? 0);
            $reportId = (int) $row->report_id;
            if ($roomId <= 0 || isset($seen[$roomId][$reportId])) {
                continue;
            }
            $seen[$roomId][$reportId] = true;

            if (! isset($map[$roomId])) {
                $map[$roomId] = ['total' => 0, 'critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
            }

            $level = $hasSeverity
                ? ReportSeverity::forReport($row)
                : ReportSeverity::fromUrgency($row->report_urgency_level ?? null);
            $level = ReportSeverity::isLevel($level) ? $level : ReportSeverity::MEDIUM;
            $key = strtolower($level);

            $map[$roomId]['total']++;
            if (isset($map[$roomId][$key])) {
                $map[$roomId][$key]++;
            }
        }

        return $map;
    }

    /**
     * Report ids whose location resolves to this room.
     * Separate indexed lookups stay faster than one joined COALESCE scan.
     *
     * @return list<int>
     */
    private static function reportIdsLocatedInRoom(int $roomId, Carbon $from, Carbon $to, bool $isLive): array
    {
        $period = function () use ($from, $to, $isLive) {
            $query = DB::table('reports_table')
                ->where('reports_table.report_is_archived', false)
                ->whereBetween('reports_table.report_submitted_at', [
                    $from->copy()->startOfDay()->toDateTimeString(),
                    $to->copy()->endOfDay()->toDateTimeString(),
                ]);

            if ($isLive) {
                $query->whereNotIn('reports_table.report_current_status', self::CLOSED_REPORT_STATUSES);
            }

            return $query;
        };

        $ids = $period()
            ->where('reports_table.report_room_id', $roomId)
            ->pluck('reports_table.report_id');

        $ids = $ids->merge(
            $period()
                ->join('equipment_table', 'equipment_table.equipment_id', '=', 'reports_table.report_equipment_id')
                ->where('equipment_table.equipment_room_id', $roomId)
                ->pluck('reports_table.report_id')
        );

        if (Schema::hasTable('report_items_table')) {
            $itemQuery = $period()
                ->join('report_items_table', 'report_items_table.report_id', '=', 'reports_table.report_id');

            if (Schema::hasColumn('report_items_table', 'report_item_room_id')) {
                $itemQuery->where(function ($query) use ($roomId) {
                    $query->where('report_items_table.report_item_room_id', $roomId)
                        ->orWhereIn('report_items_table.report_item_equipment_id', function ($sub) use ($roomId) {
                            $sub->select('equipment_id')
                                ->from('equipment_table')
                                ->where('equipment_room_id', $roomId);
                        });
                });
            } else {
                $itemQuery->whereIn('report_items_table.report_item_equipment_id', function ($sub) use ($roomId) {
                    $sub->select('equipment_id')
                        ->from('equipment_table')
                        ->where('equipment_room_id', $roomId);
                });
            }

            $ids = $ids->merge($itemQuery->pluck('reports_table.report_id'));
        }

        return $ids
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Active/overdue room-inspection schedules overlapping the period, keyed by room.
     *
     * @param  list<int>  $roomIds
     * @return array<int, array{schedules: list<array<string,mixed>>, scopes: list<string>, next_date: ?string}>
     */
    private static function underMaintenanceByRoom(array $roomIds, Carbon $from, Carbon $to, bool $isLive): array
    {
        if ($roomIds === [] || ! RoomInspections::ready()) {
            return [];
        }

        $hasDepartmentColumn = Schema::hasColumn(
            RoomInspections::SCHEDULES,
            'room_inspection_schedule_department_id'
        );

        $select = [
            's.room_inspection_schedule_id',
            's.room_inspection_schedule_room_id',
            's.room_inspection_schedule_title',
            's.room_inspection_schedule_status',
            's.room_inspection_schedule_next_date',
            's.room_inspection_schedule_frequency',
        ];

        $query = DB::table(RoomInspections::SCHEDULES.' as s')
            ->whereIn('s.room_inspection_schedule_room_id', $roomIds)
            ->whereIn('s.room_inspection_schedule_status', ['Active', 'Overdue']);

        if ($hasDepartmentColumn) {
            $select[] = 's.room_inspection_schedule_department_id';
            if (Schema::hasTable('departments_table')) {
                $query->leftJoin(
                    'departments_table as dept',
                    'dept.department_id',
                    '=',
                    's.room_inspection_schedule_department_id'
                );
                $select[] = 'dept.department_name';
            }
        }

        if ($isLive) {
            $query->whereDate('s.room_inspection_schedule_next_date', '<=', $to->toDateString());
        } else {
            $query->whereDate('s.room_inspection_schedule_next_date', '>=', $from->toDateString())
                ->whereDate('s.room_inspection_schedule_next_date', '<=', $to->toDateString());
        }

        $rows = $query
            ->orderBy('s.room_inspection_schedule_next_date')
            ->get($select);

        $map = [];
        foreach ($rows as $row) {
            $roomId = (int) $row->room_inspection_schedule_room_id;
            $deptId = $hasDepartmentColumn
                ? (int) ($row->room_inspection_schedule_department_id ?? 0)
                : 0;
            $scope = $deptId > 0
                ? (string) ($row->department_name ?? 'Department')
                : 'Entire room';

            if (! isset($map[$roomId])) {
                $map[$roomId] = [
                    'schedules' => [],
                    'scopes' => [],
                    'next_date' => null,
                ];
            }

            $nextDate = $row->room_inspection_schedule_next_date
                ? Carbon::parse($row->room_inspection_schedule_next_date)->toDateString()
                : null;

            $map[$roomId]['schedules'][] = [
                'id' => (int) $row->room_inspection_schedule_id,
                'title' => (string) ($row->room_inspection_schedule_title ?? 'Room inspection'),
                'status' => (string) ($row->room_inspection_schedule_status ?? ''),
                'next_date' => $nextDate,
                'frequency' => (string) ($row->room_inspection_schedule_frequency ?? ''),
                'department_id' => $deptId > 0 ? $deptId : null,
                'scope' => $scope,
                'entire_room' => $deptId <= 0,
            ];

            if (! in_array($scope, $map[$roomId]['scopes'], true)) {
                $map[$roomId]['scopes'][] = $scope;
            }

            if (
                $nextDate
                && (
                    $map[$roomId]['next_date'] === null
                    || $nextDate < $map[$roomId]['next_date']
                )
            ) {
                $map[$roomId]['next_date'] = $nextDate;
            }
        }

        return $map;
    }
}
