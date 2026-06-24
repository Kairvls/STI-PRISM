<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InfrastructureController extends Controller
{
    public function index(Request $request): View
    {
        $floors = Floor::query()->with('building')->withCount('rooms')
            ->orderBy('floor_building_id')->orderBy('floor_level')->get();
        $rooms = Room::query()->with(['floor.building', 'equipment'])
            ->orderBy('room_floor_id')->orderBy('room_name')->get();
        $roomIds = $rooms->pluck('room_id');
        $reportMetrics = collect();
        $frequentProblems = collect();
        $schedules = collect();

        if ($roomIds->isNotEmpty() && Schema::hasTable('reports_table')) {
            $reportMetrics = DB::table('reports_table')->whereIn('report_room_id', $roomIds)
                ->groupBy('report_room_id')
                ->selectRaw("report_room_id,
                    SUM(report_current_status NOT IN ('Resolved', 'Rejected')) AS active_count,
                    SUM(DATE(report_submitted_at) = CURDATE()) AS today_count,
                    SUM(report_submitted_at >= ?) AS week_count,
                    SUM(report_submitted_at >= ?) AS month_count", [now()->startOfWeek(), now()->startOfMonth()])
                ->get()->keyBy('report_room_id');

            $frequentProblems = DB::table('reports_table')->whereIn('report_room_id', $roomIds)
                ->whereNotNull('report_problem_description')
                ->groupBy('report_room_id', 'report_problem_description')
                ->selectRaw('report_room_id, report_problem_description, COUNT(*) AS occurrences')
                ->orderByDesc('occurrences')->get()->groupBy('report_room_id')
                ->map(fn ($items) => $items->take(3)->values());
        }

        if ($roomIds->isNotEmpty() && Schema::hasTable('maintenance_schedules_table')) {
            $schedules = DB::table('maintenance_schedules_table')
                ->join('equipment_table', 'maintenance_schedule_equipment_id', '=', 'equipment_id')
                ->whereIn('equipment_room_id', $roomIds)
                ->where('maintenance_schedule_status', '!=', 'Completed')
                ->orderBy('maintenance_schedule_next_date')
                ->select('equipment_room_id', 'equipment_name', 'maintenance_schedule_title',
                    'maintenance_schedule_next_date', 'maintenance_schedule_status')
                ->get()->groupBy('equipment_room_id')->map(fn ($items) => $items->take(4)->values());
        }

        $rooms->each(function (Room $room) use ($reportMetrics, $frequentProblems, $schedules): void {
            $metric = $reportMetrics->get($room->room_id);
            $room->monitoring = [
                'active_reports' => (int) ($metric->active_count ?? 0),
                'today_reports' => (int) ($metric->today_count ?? 0),
                'week_reports' => (int) ($metric->week_count ?? 0),
                'month_reports' => (int) ($metric->month_count ?? 0),
                'frequent_problems' => $frequentProblems->get($room->room_id, collect())->all(),
                'schedules' => $schedules->get($room->room_id, collect())->all(),
            ];
        });

        $categories = Schema::hasTable('equipment_categories_table')
            ? DB::table('equipment_categories_table')->orderBy('equipment_category_name')->get()
            : collect();

        return view('maintenance-personnel.infrastructure.monitor', [
            'buildings' => Building::query()->orderBy('building_name')->get(),
            'floors' => $floors,
            'rooms' => $rooms,
            'categories' => $categories,
            'requestedFloorId' => (int) $request->integer('floor'),
        ]);
    }

    public function storeCampus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'building_name' => ['required', 'string', 'max:255'],
            'floors' => ['required', 'array', 'min:1'],
            'floors.*.level' => ['required', Rule::in(['2nd Floor', '3rd Floor'])],
            'floors.*.rooms' => ['required', 'array', 'min:1'],
            'floors.*.rooms.*.name' => ['required', 'string', 'max:255'],
            'floors.*.rooms.*.type' => ['required', Rule::in(['Lecture Room', 'Computer Laboratory', 'Hospitality Suite', 'Office', 'Library', 'Canteen', 'Clinic', 'Utility'])],
            'floors.*.rooms.*.status' => ['required', Rule::in(['Normal', 'Maintenance Needed', 'Critical'])],
            'floors.*.rooms.*.equipment' => ['nullable', 'array'],
            'floors.*.rooms.*.equipment.*.name' => ['required', 'string', 'max:255'],
            'floors.*.rooms.*.equipment.*.category_id' => ['nullable', 'integer', 'exists:equipment_categories_table,equipment_category_id'],
            'floors.*.rooms.*.equipment.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'floors.*.rooms.*.equipment.*.condition' => ['required', Rule::in(['Good', 'Damaged', 'Under Maintenance'])],
            'floors.*.rooms.*.equipment.*.zone' => ['required', Rule::in(['Front Wall', 'Center Ceiling', 'Left Row Pods', 'Right Row Pods', 'Rear Wall', 'Storage'])],
        ]);

        DB::transaction(function () use ($validated): void {
            $buildingId = DB::table('buildings_table')->insertGetId(['building_name' => $validated['building_name']]);
            foreach ($validated['floors'] as $floorIndex => $floorData) {
                $floorId = DB::table('floors_table')->insertGetId([
                    'floor_building_id' => $buildingId, 'floor_level' => $floorData['level'],
                ]);
                foreach ($floorData['rooms'] as $roomIndex => $roomData) {
                    $position = $this->defaultRoomPosition($roomIndex);
                    $roomId = DB::table('rooms_table')->insertGetId([
                        'room_floor_id' => $floorId, 'room_name' => $roomData['name'],
                        'room_type' => $roomData['type'], 'room_status' => $roomData['status'],
                        'room_color' => $this->roomColor($roomData['type']),
                        'room_x' => $position['x'], 'room_y' => $position['y'],
                        'room_width' => 150, 'room_height' => 105,
                        'room_metadata' => json_encode(['wizard_floor_index' => $floorIndex]),
                    ]);
                    foreach ($roomData['equipment'] ?? [] as $equipment) {
                        $values = [
                            'equipment_category_id' => $equipment['category_id'] ?: null,
                            'equipment_room_id' => $roomId, 'equipment_name' => $equipment['name'],
                            'equipment_quantity' => $equipment['quantity'],
                            'equipment_condition_status' => $equipment['condition'],
                            'equipment_inventory_status' => $equipment['condition'] === 'Under Maintenance' ? 'Under Maintenance' : 'Active',
                            'equipment_current_location' => $equipment['zone'], 'equipment_is_borrowable' => false,
                        ];
                        if (Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
                            $values['equipment_placement_zone'] = $equipment['zone'];
                        }
                        DB::table('equipment_table')->insert($values);
                    }
                }
            }
        });

        return redirect()->route('maintenance.infrastructure.index')
            ->with('success', 'Campus structure, rooms, and initial equipment were created.');
    }

    public function saveLayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'floor_id' => ['required', 'integer', 'exists:floors_table,floor_id'],
            'rooms' => ['required', 'array'],
            'rooms.*.id' => ['required', 'integer'],
            'rooms.*.x' => ['required', 'integer', 'min:0', 'max:1800'],
            'rooms.*.y' => ['required', 'integer', 'min:0', 'max:900'],
            'rooms.*.width' => ['required', 'integer', 'min:100', 'max:600'],
            'rooms.*.height' => ['required', 'integer', 'min:70', 'max:450'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['rooms'] as $room) {
                Room::query()->whereKey($room['id'])->where('room_floor_id', $validated['floor_id'])
                    ->update([
                        'room_x' => $room['x'],
                        'room_y' => $room['y'],
                        'room_width' => $room['width'],
                        'room_height' => $room['height'],
                    ]);
            }
        });

        return response()->json(['message' => 'Layout saved successfully.']);
    }

    private function defaultRoomPosition(int $index): array
    {
        return ['x' => 70 + (($index % 6) * 190), 'y' => 80 + ((int) floor($index / 6) * 190)];
    }

    private function roomColor(string $type): string
    {
        return match ($type) {
            'Computer Laboratory' => '#FFF200', 'Hospitality Suite' => '#F39200',
            'Library' => '#A78BFA', 'Canteen' => '#84CC16', 'Clinic' => '#FB7185',
            'Office' => '#22C55E', 'Utility' => '#94A3B8', default => '#60A5FA',
        };
    }
}
