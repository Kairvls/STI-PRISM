<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    private const CLOSED_REPORT_STATUSES = ['Resolved', 'Rejected', 'For Replacement'];

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
        $maintenanceRooms = self::underMaintenanceRoomIds($roomIds, $from, $to, $isLive);

        return $rooms->transform(function ($room) use ($reportCounts, $maintenanceRooms, $departments, $from, $to, $preset) {
            $counts = $reportCounts[(int) $room->room_id] ?? [
                'total' => 0,
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ];

            $room->departments = $departments[(int) $room->room_id] ?? [];
            $room->report_count = (int) $counts['total'];
            $room->critical_report_count = (int) $counts['critical'];
            $room->high_report_count = (int) $counts['high'];
            $room->medium_report_count = (int) $counts['medium'];
            $room->low_report_count = (int) $counts['low'];
            $room->under_maintenance_scheduled = isset($maintenanceRooms[(int) $room->room_id]);

            // Legacy keys kept so older JS still reads something sensible.
            $room->active_report_count = $room->report_count;
            $room->urgent_report_count = $room->critical_report_count;
            $room->maintenance_equipment_count = $room->under_maintenance_scheduled ? 1 : 0;

            if ($room->critical_report_count > 0) {
                $room->dashboard_status = self::STATUS_CRITICAL;
                $room->dashboard_label = RoomStatuses::CRITICAL;
                $room->status_color = self::COLOR_CRITICAL;
            } elseif ($room->under_maintenance_scheduled) {
                $room->dashboard_status = self::STATUS_UNDER_MAINTENANCE;
                $room->dashboard_label = RoomStatuses::UNDER_MAINTENANCE;
                $room->status_color = self::COLOR_UNDER_MAINTENANCE;
            } else {
                $room->dashboard_status = self::STATUS_NORMAL;
                $room->dashboard_label = RoomStatuses::NORMAL;
                $room->status_color = self::COLOR_NORMAL;
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
        $isLive = $preset === 'today' || ($from->isSameDay(today()) && $to->isSameDay(today()));
        $hasSeverity = ReportSeverity::hasColumns();

        $query = self::reportsLocatedInRoomQuery($roomId, $from, $to, $isLive);

        $rows = $query
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

        $underMaintenance = isset(self::underMaintenanceRoomIds([$roomId], $from, $to, $isLive)[$roomId]);
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
            ->whereDate('reports_table.report_submitted_at', '>=', $from->toDateString())
            ->whereDate('reports_table.report_submitted_at', '<=', $to->toDateString());

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
     * Reports whose resolved location is this room.
     */
    private static function reportsLocatedInRoomQuery(int $roomId, Carbon $from, Carbon $to, bool $isLive)
    {
        $hasItems = Schema::hasTable('report_items_table');
        $hasItemRoom = $hasItems && Schema::hasColumn('report_items_table', 'report_item_room_id');

        $query = DB::table('reports_table')
            ->where('reports_table.report_is_archived', false)
            ->whereDate('reports_table.report_submitted_at', '>=', $from->toDateString())
            ->whereDate('reports_table.report_submitted_at', '<=', $to->toDateString());

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

        return $query->whereRaw("{$locationExpr} = ?", [$roomId]);
    }

    /**
     * Rooms with an active/overdue room-inspection schedule overlapping the period.
     *
     * @param  list<int>  $roomIds
     * @return array<int, true>
     */
    private static function underMaintenanceRoomIds(array $roomIds, Carbon $from, Carbon $to, bool $isLive): array
    {
        if ($roomIds === [] || ! RoomInspections::ready()) {
            return [];
        }

        $query = DB::table(RoomInspections::SCHEDULES)
            ->whereIn('room_inspection_schedule_room_id', $roomIds)
            ->whereIn('room_inspection_schedule_status', ['Active', 'Overdue']);

        if ($isLive) {
            // Due today or overdue — currently scheduled room maintenance.
            $query->whereDate('room_inspection_schedule_next_date', '<=', $to->toDateString());
        } else {
            // Look back: schedules whose next due date falls inside the selected window.
            $query->whereDate('room_inspection_schedule_next_date', '>=', $from->toDateString())
                ->whereDate('room_inspection_schedule_next_date', '<=', $to->toDateString());
        }

        $ids = $query->pluck('room_inspection_schedule_room_id');

        $map = [];
        foreach ($ids as $id) {
            $map[(int) $id] = true;
        }

        return $map;
    }
}
