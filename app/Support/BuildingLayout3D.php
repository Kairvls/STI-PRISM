<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Floors and rooms (with maintenance status) consumed by the 3D building viewer
 * in resources/views/partials/building-3d.
 */
class BuildingLayout3D
{
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

    public static function rooms(): Collection
    {
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

        $roomIds = $rooms->pluck('room_id')->all();

        $reportCounts = empty($roomIds) ? collect() : DB::table('reports_table')
            ->whereIn('report_room_id', $roomIds)
            ->whereNotIn('report_current_status', self::CLOSED_REPORT_STATUSES)
            ->where('report_is_archived', false)
            ->groupBy('report_room_id')
            ->selectRaw(
                "report_room_id, COUNT(*) as active_total, SUM(CASE WHEN report_urgency_level = 'Urgent' THEN 1 ELSE 0 END) as urgent_total"
            )
            ->get()
            ->keyBy('report_room_id');

        $maintenanceCounts = empty($roomIds) ? collect() : DB::table('equipment_table')
            ->whereIn('equipment_room_id', $roomIds)
            ->where('equipment_inventory_status', 'Under Maintenance')
            ->groupBy('equipment_room_id')
            ->selectRaw('equipment_room_id, COUNT(*) as total')
            ->pluck('total', 'equipment_room_id');

        return $rooms->transform(function ($room) use ($reportCounts, $maintenanceCounts) {
            $reports = $reportCounts->get($room->room_id);

            $room->active_report_count = (int) ($reports->active_total ?? 0);
            $room->urgent_report_count = (int) ($reports->urgent_total ?? 0);
            $room->maintenance_equipment_count = (int) ($maintenanceCounts[$room->room_id] ?? 0);

            if ($room->urgent_report_count > 0) {
                $room->dashboard_status = 'critical';
                $room->dashboard_label = 'Critical';
            } elseif ($room->active_report_count > 0) {
                $room->dashboard_status = 'needs-repair';
                $room->dashboard_label = 'Repair';
            } elseif ($room->maintenance_equipment_count > 0) {
                $room->dashboard_status = 'maintenance';
                $room->dashboard_label = 'Maintenance';
            } else {
                $room->dashboard_status = 'available';
                $room->dashboard_label = 'Good';
            }

            return $room;
        });
    }
}
