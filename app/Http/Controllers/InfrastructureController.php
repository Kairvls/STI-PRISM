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
        $hasRoomArchive = Schema::hasColumn('rooms_table', 'room_is_archived');

        $floors = Floor::query()->with('building')
            ->withCount([
                'rooms' => fn ($query) => $hasRoomArchive
                    ? $query->where('room_is_archived', false)
                    : $query,
            ])
            ->orderBy('floor_building_id')->orderBy('floor_level')->get();
        $rooms = Room::query()->with(['floor.building', 'equipment'])
            ->when($hasRoomArchive, fn ($query) => $query->where('room_is_archived', false))
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

        // ----------------------------------------------------
        // LOAD EXISTING CAMPUS FOR THE WIZARD
        // ----------------------------------------------------

        $campus = Building::query()

            ->with([

                'floors.rooms.equipment'

            ])

            ->first();

        $wizardCampus = $this->buildWizardCampusData($campus);

        return view('maintenance-personnel.infrastructure.monitor', [
            'buildings' => Building::query()->orderBy('building_name')->get(),
            'floors' => $floors,
            'rooms' => $rooms,
            'categories' => $categories,
            'wizardCampus' => $wizardCampus,
            'requestedFloorId' => (int) $request->integer('floor'),
        ]);
    }

    public function loadCampus(): JsonResponse
    {
        $campus = Building::query()
            ->with([
                'floors.rooms.equipment'
            ])
            ->first();

        return response()->json(
            $this->buildWizardCampusData($campus)
        );
    }

    public function storeCampus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'building_name' => ['required', 'string', 'max:255'],

            'building_logo' => ['nullable', 'string', 'max:255'],

            'building_address' => ['nullable', 'string'],
            'floors' => ['required', 'array', 'min:1'],
            'floors.*.level' => [

                'required',

                'string',

                'max:50',

            ],
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
            // ---------- CREATE OR UPDATE SINGLE CAMPUS ----------

            $building = Building::query()->first();

            if ($building) {

                $building->update([

                    'building_name' => $validated['building_name'],

                    'building_logo' => $validated['building_logo'] ?? null,

                    'building_address' => $validated['building_address'] ?? null,

                ]);

                $buildingId = $building->building_id;

            } else {

                $buildingId = DB::table('buildings_table')->insertGetId([

                    'building_name' => $validated['building_name'],

                    'building_logo' => $validated['building_logo'] ?? null,

                    'building_address' => $validated['building_address'] ?? null,

                ]);

            }
            // ----------------------------------------------------
            // EXISTING FLOOR CACHE
            // ----------------------------------------------------

            $existingFloors = Floor::query()
                ->where('floor_building_id', $buildingId)
                ->get()
                ->keyBy('floor_level');

            $processedFloorIds = [];
            foreach ($validated['floors'] as $floorIndex => $floorData) {
                // ----------------------------------------------------
                // CREATE OR UPDATE FLOOR
                // ----------------------------------------------------

                $floor = $existingFloors->get($floorData['level']);

                if ($floor) {

                    $floorId = $floor->floor_id;

                    $processedFloorIds[] = $floorId;

                } else {

                    $floorId = DB::table('floors_table')->insertGetId([

                        'floor_building_id' => $buildingId,

                        'floor_level' => $floorData['level'],

                    ]);

                    $processedFloorIds[] = $floorId;

                }
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

            // ----------------------------------------------------
            // REMOVE FLOORS NO LONGER PRESENT
            // ----------------------------------------------------

            Floor::query()

                ->where('floor_building_id', $buildingId)

                ->whereNotIn('floor_id', $processedFloorIds)

                ->delete();
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

    public function updateRoom(Request $request, Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
        ]);

        $room->update([
            'room_name' => $validated['room_name'],
        ]);

        return response()->json([
            'message' => 'Room name updated.',
            'room' => [
                'id' => $room->room_id,
                'name' => $room->room_name,
            ],
        ]);
    }

    public function archiveRoom(Request $request, Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($room, $validated): void {
            $equipmentSnapshots = DB::table('equipment_table')
                ->where('equipment_room_id', $room->room_id)
                ->pluck('equipment_name', 'equipment_id');
            $equipmentIds = $equipmentSnapshots->keys();

            if ($equipmentIds->isNotEmpty() && Schema::hasTable('maintenance_schedules_table')) {
                DB::table('maintenance_schedules_table')
                    ->whereIn('maintenance_schedule_equipment_id', $equipmentIds)
                    ->delete();
            }

            if ($equipmentIds->isNotEmpty() && Schema::hasTable('reports_table')) {
                if (Schema::hasColumn('reports_table', 'report_unlisted_equipment_name')) {
                    $equipmentSnapshots->each(function (?string $equipmentName, int $equipmentId): void {
                        DB::table('reports_table')
                            ->where('report_equipment_id', $equipmentId)
                            ->whereNull('report_unlisted_equipment_name')
                            ->update([
                                'report_unlisted_equipment_name' => $equipmentName ?: 'Archived room equipment',
                            ]);
                    });
                }

                DB::table('reports_table')
                    ->whereIn('report_equipment_id', $equipmentIds)
                    ->update(['report_equipment_id' => null]);
            }

            DB::table('equipment_table')
                ->where('equipment_room_id', $room->room_id)
                ->delete();

            $metadata = $room->room_metadata ?: [];
            $metadata['archived_snapshot'] = [
                'room_name' => $room->room_name,
                'room_type' => $room->room_type,
                'room_status' => $room->room_status,
                'equipment_ids_removed' => $equipmentIds->values()->all(),
                'archived_at' => now()->toDateTimeString(),
            ];

            $room->update([
                'room_is_archived' => true,
                'room_archived_at' => now(),
                'room_archived_reason' => $validated['reason'] ?: 'Archived from layout editor',
                'room_metadata' => $metadata,
            ]);
        });

        return response()->json([
            'message' => 'Room archived and live details cleared.',
        ]);
    }

    private function buildWizardCampusData(?Building $campus): array
    {
        if (!$campus) {

            return [

                'building_name' => '',

                'building_logo' => null,

                'building_address' => null,

                'floors' => [],

            ];

        }

        return [

            'building_name' => $campus->building_name,

            'building_logo' => $campus->building_logo,

            'building_address' => $campus->building_address,

            'floors' => $campus->floors

                ->sortBy(function ($floor) {

                    return (int) filter_var(
                        $floor->floor_level,
                        FILTER_SANITIZE_NUMBER_INT
                    );

                })

                ->values()

                ->map(function ($floor) {

                    return [

                        'id' => $floor->floor_id,

                        'level' => $floor->floor_level,

                        'rooms' => $floor->rooms

                            ->values()

                            ->map(function ($room) {

                                return [

                                    'id' => $room->room_id,

                                    'name' => $room->room_name,

                                    'type' => $room->room_type,

                                    'status' => $room->room_status,

                                    'equipment' => $room->equipment

                                        ->values()

                                        ->map(function ($equipment) {

                                            return [

                                                'id' => $equipment->equipment_id,

                                                'name' => $equipment->equipment_name,

                                                'category_id' => $equipment->equipment_category_id,

                                                'quantity' => $equipment->equipment_quantity,

                                                'condition' => $equipment->equipment_condition_status,

                                                'zone' => $equipment->equipment_current_location,

                                            ];

                                        })

                                        ->values(),

                                ];

                            })

                            ->values(),

                    ];

                })

                ->values(),

        ];
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
