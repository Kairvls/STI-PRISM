<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Models\Equipment;
use App\Models\Asset;
use App\Models\WorkstationSlot;
use App\Models\RoomActivityLog;
use App\Models\CampusSetupSetting;
use App\Support\RoomName;
use App\Support\EquipmentQrCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class InfrastructureController extends Controller
{
    public function index(Request $request): View
    {
        $canManageCampusSetup = $this->canManageCampusSetup();

        $hasRoomArchive = Schema::hasColumn('rooms_table', 'room_is_archived');

        $floors = Floor::query()->with('building')
            ->withCount([
                'rooms' => fn ($query) => $hasRoomArchive
                    ? $query->where('room_is_archived', false)
                    : $query,
            ])
            ->orderBy('floor_building_id')->orderBy('floor_level')->get();
        $rooms = Room::query()->with(['floor.building', 'equipment.category', 'equipment.supplier'])
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

                'equipment_count' => $room->equipment->count(),

                'equipment_quantity' => $room->equipment->sum('equipment_quantity'),

                'equipment_good' => $room->equipment
                    ->where('equipment_condition_status', 'Good')
                    ->count(),

                'equipment_damaged' => $room->equipment
                    ->where('equipment_condition_status', 'Damaged')
                    ->count(),

                'equipment_maintenance' => $room->equipment
                    ->where('equipment_condition_status', 'Under Maintenance')
                    ->count(),

                'equipment_disposed' => $room->equipment
                    ->where('equipment_condition_status', 'Disposed')
                    ->count(),

                'active_reports' => (int) ($metric->active_count ?? 0),

                'today_reports' => (int) ($metric->today_count ?? 0),

                'week_reports' => (int) ($metric->week_count ?? 0),

                'month_reports' => (int) ($metric->month_count ?? 0),

                'frequent_problems' => $frequentProblems
                    ->get($room->room_id, collect())
                    ->all(),

                'schedules' => $schedules
                    ->get($room->room_id, collect())
                    ->all(),

                'history'=>RoomActivityLog::where(

                        'room_id',

                        $room->room_id

                    )

                    ->latest('created_at')

                    ->take(100)

                    ->get(),

                'room_information' => [

                    'last_inspection' => DB::table('equipment_maintenance_history_table')
                        ->join(
                            'equipment_table',
                            'equipment_maintenance_history_table.equipment_maintenance_equipment_id',
                            '=',
                            'equipment_table.equipment_id'
                        )
                        ->where(
                            'equipment_table.equipment_room_id',
                            $room->room_id
                        )
                        ->max('equipment_maintenance_completed_at'),

                    'next_maintenance' => DB::table('maintenance_schedules_table')
                        ->join(
                            'equipment_table',
                            'maintenance_schedules_table.maintenance_schedule_equipment_id',
                            '=',
                            'equipment_table.equipment_id'
                        )
                        ->where(
                            'equipment_table.equipment_room_id',
                            $room->room_id
                        )
                        ->min('maintenance_schedule_next_date'),

                    'last_updated' => $room->room_updated_at,

                    'status' => $room->room_status,

                ],

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

                'floors.rooms.equipment.category'

            ])

            ->first();

        $wizardCampus = $this->buildWizardCampusData($campus);

        $usedAssetTags = DB::table('equipment_table')
            ->whereNotNull('equipment_asset_tag')
            ->where('equipment_asset_tag', '!=', '')
            ->whereNotIn('equipment_inventory_status', ['Disposed'])
            ->pluck('equipment_asset_tag');

        return view('maintenance-personnel.infrastructure.monitor', [
            'buildings' => Building::query()->orderBy('building_name')->get(),
            'floors' => $floors,
            'rooms' => $rooms,
            'categories' => $categories,
            'wizardCampus' => $wizardCampus,
            'canManageCampusSetup' => $canManageCampusSetup,
            'requestedFloorId' => (int) $request->integer('floor'),
            'usedAssetTags' => $usedAssetTags,
        ]);
    }

    public function loadCampus(): JsonResponse
    {
        abort_unless($this->canManageCampusSetup(), 403);

        $campus = Building::query()
            ->with([
                'floors.rooms.equipment.category'
            ])
            ->first();

        return response()->json(
            $this->buildWizardCampusData($campus)
        );
    }

    public function verifySetupUnlockCredential(Request $request): JsonResponse
    {
        abort_unless($this->canManageCampusSetup(), 403);

        $validated = $request->validate([
            'unlock_credential' => ['required', 'string', 'max:255'],
        ]);

        $input = (string) $validated['unlock_credential'];

        $setting = CampusSetupSetting::query()->first();
        $configCode = (string) config('app.campus_setup_unlock_code', '');

        $matchesSavedPin = $setting && !empty($setting->campus_setup_pin_hash)
            ? Hash::check($input, (string) $setting->campus_setup_pin_hash)
            : false;

        $matchesConfigCode = $configCode !== '' && hash_equals($configCode, $input);

        if (!$matchesSavedPin && !$matchesConfigCode) {
            return response()->json([
                'message' => 'Invalid unlock credential.',
            ], 422);
        }

        return response()->json([
            'message' => 'Credential verified.',
        ]);
    }

    public function roomEquipment(Room $room): JsonResponse
    {
        return response()->json(

            $room->equipment()
                ->with('category')
                ->get()
                ->map(function ($equipment) {

                    return [

                        'id' => $equipment->equipment_id,

                        'name' => $equipment->equipment_name,

                        'category' => $equipment->equipment_category_id,

                        'category_name' => optional($equipment->category)->equipment_category_name,

                        'quantity' => (int) ($equipment->equipment_quantity ?? 1),

                        'tracking_mode' => $equipment->equipment_tracking_mode ?: 'Individual',

                        'condition' => $equipment->equipment_condition_status,

                        'asset_tag' => $equipment->equipment_asset_tag,

                        'serial_number' => $equipment->equipment_serial_number,

                        'brand' => $equipment->equipment_brand_name,

                        'model' => $equipment->equipment_model,

                        'location' => $equipment->equipment_current_location,

                        'placement_zone' => $equipment->equipment_placement_zone,

                        'x' => (int) $equipment->equipment_position_x,

                        'y' => (int) $equipment->equipment_position_y,

                        'width' => (int) ($equipment->equipment_width ?? 120),

                        'height' => (int) ($equipment->equipment_height ?? 96),

                        'rotation' => (int) ($equipment->equipment_rotation ?? 0),

                    ];

                })

        );
    }

    public function getLayout(Room $room): JsonResponse
    {
        $room->load([
            'workstationSlots.template.slots',
            'workstationSlots.assets.category',
        ]);

        return response()->json([
            'room' => [
                'id' => $room->room_id,
                'name' => $room->room_name,
                'layout_mode' => $room->room_layout_mode ?? 'loose_equipment',
                'layout_version' => (int) ($room->room_layout_version ?? 1),
            ],
            'workstation_slots' => $room->workstationSlots->map(function (WorkstationSlot $slot) {
                return [
                    'id' => $slot->workstation_slot_id,
                    'room_id' => $slot->room_id,
                    'template_id' => $slot->workstation_template_id,
                    'label' => $slot->workstation_slot_label,
                    'code' => $slot->workstation_slot_code,
                    'orientation' => $slot->workstation_slot_orientation,
                    'x' => (float) $slot->workstation_slot_position_x,
                    'y' => (float) $slot->workstation_slot_position_y,
                    'width' => (int) $slot->workstation_slot_width,
                    'height' => (int) $slot->workstation_slot_height,
                    'status' => $slot->workstation_slot_status,
                    'assets' => $slot->assets->map(function (Asset $asset) {
                        return [
                            'id' => $asset->equipment_id,
                            'name' => $asset->equipment_name,
                            'category' => optional($asset->category)->equipment_category_name,
                            'condition' => $asset->equipment_condition_status,
                            'inventory_status' => $asset->equipment_inventory_status,
                            'location' => $asset->equipment_current_location,
                            'serial_number' => $asset->equipment_serial_number,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function updateCoordinates(Request $request, WorkstationSlot $workstationSlot): JsonResponse
    {
        $validated = $request->validate([
            'x' => ['required', 'numeric', 'min:0', 'max:100'],
            'y' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $workstationSlot->update([
            'workstation_slot_position_x' => $validated['x'],
            'workstation_slot_position_y' => $validated['y'],
        ]);

        return response()->json([
            'message' => 'Workstation slot coordinates updated successfully.',
            'slot' => [
                'id' => $workstationSlot->workstation_slot_id,
                'x' => (float) $workstationSlot->workstation_slot_position_x,
                'y' => (float) $workstationSlot->workstation_slot_position_y,
            ],
        ]);
    }

    public function storeWorkstationSlots(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'exists:workstation_templates_table,workstation_template_id'],
            'count' => ['required', 'integer', 'min:1', 'max:50'],
            'start_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'start_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'spacing_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'orientation' => ['required', Rule::in(['north', 'east', 'south', 'west'])],
        ]);

        $template = DB::table('workstation_templates_table')
            ->where('workstation_template_id', $validated['template_id'])
            ->first();

        if (!$template) {
            return response()->json(['message' => 'Template not found.'], 404);
        }

        $createdSlots = [];

        DB::transaction(function () use ($room, $validated, $template, &$createdSlots): void {
            $existingCount = DB::table('workstation_slots_table')
                ->where('room_id', $room->room_id)
                ->count();

            for ($index = 0; $index < (int) $validated['count']; $index++) {
                $positionX = min(100, (float) $validated['start_x'] + (($validated['spacing_x']) * $index));
                $slot = DB::table('workstation_slots_table')->insertGetId([
                    'room_id' => $room->room_id,
                    'workstation_template_id' => $template->workstation_template_id,
                    'workstation_slot_label' => sprintf('WS %02d', $existingCount + $index + 1),
                    'workstation_slot_code' => sprintf('WS-%d-%02d', $room->room_id, $existingCount + $index + 1),
                    'workstation_slot_orientation' => $validated['orientation'],
                    'workstation_slot_position_x' => $positionX,
                    'workstation_slot_position_y' => (float) $validated['start_y'],
                    'workstation_slot_width' => (int) ($template->workstation_template_default_width ?? 140),
                    'workstation_slot_height' => (int) ($template->workstation_template_default_height ?? 100),
                    'workstation_slot_status' => 'Active',
                    'workstation_slot_meta' => json_encode([
                        'generator' => 'row',
                        'template_code' => $template->workstation_template_code,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdSlots[] = DB::table('workstation_slots_table')
                    ->where('workstation_slot_id', $slot)
                    ->first();
            }
        });

        return response()->json([
            'message' => 'Workstation row created successfully.',
            'slots' => collect($createdSlots)->map(function ($slot) {
                return [
                    'id' => $slot->workstation_slot_id,
                    'room_id' => $slot->room_id,
                    'template_id' => $slot->workstation_template_id,
                    'label' => $slot->workstation_slot_label,
                    'code' => $slot->workstation_slot_code,
                    'orientation' => $slot->workstation_slot_orientation,
                    'x' => (float) $slot->workstation_slot_position_x,
                    'y' => (float) $slot->workstation_slot_position_y,
                    'width' => (int) $slot->workstation_slot_width,
                    'height' => (int) $slot->workstation_slot_height,
                    'status' => $slot->workstation_slot_status,
                    'assets' => [],
                ];
            })->values(),
        ]);
    }

    public function storeCampus(Request $request): RedirectResponse
    {
        abort_unless($this->canManageCampusSetup(), 403);

        $request->merge($this->sanitizeCampusWizardPayload($request->all()));

        $validated = $request->validateWithBag('campusWizard', [
            'building_name' => ['required', 'string', 'max:255'],

            'building_logo' => ['nullable', 'string', 'max:255'],

            'building_address' => ['nullable', 'string'],
            'floors' => ['required', 'array', 'min:1'],
            'floors.*.level' => [

                'required',

                'string',

                'max:50',

            ],
            'floors.*.rooms' => ['nullable', 'array'],
            'floors.*.rooms.*.name' => ['required', 'string', 'max:255'],
            'floors.*.rooms.*.type' => ['required', Rule::in([
                'Lecture Room', 'Computer Laboratory', 'Hospitality Suite', 'Office',
                'Library', 'Canteen', 'Clinic', 'Utility', 'Hallway', 'Exit',
                'Restroom', 'Elevator', 'Stairs', 'HM Room', 'Hotel Room Simulation',
                'Faculty Room', 'School Clinic',
            ])],
            'floors.*.rooms.*.status' => ['required', Rule::in(['Normal', 'Maintenance Needed', 'Critical'])],
            'floors.*.rooms.*.equipment' => ['nullable', 'array'],
            'floors.*.rooms.*.equipment.*.name' => ['required', 'string', 'max:255'],
            'floors.*.rooms.*.equipment.*.category_id' => ['nullable', 'integer', 'exists:equipment_categories_table,equipment_category_id'],
            'floors.*.rooms.*.equipment.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'floors.*.rooms.*.equipment.*.condition' => ['required', Rule::in(['Good', 'Damaged', 'Under Maintenance', 'Disposed'])],
            'floors.*.rooms.*.equipment.*.zone' => [
                'required',
                'string',
                'max:64',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $zone = trim((string) $value);
                    if (in_array($zone, ['Holding', 'Floor'], true)) {
                        return;
                    }
                    if (preg_match('/^Row\s+\d+$/i', $zone)) {
                        return;
                    }
                    // Legacy campus drafts / older saved drafts
                    if (in_array($zone, [
                        'Front Wall',
                        'Center Ceiling',
                        'Left Row Pods',
                        'Right Row Pods',
                        'Rear Wall',
                        'Storage',
                    ], true)) {
                        return;
                    }
                    $fail('Invalid placement. Use Holding, Floor, or a row (e.g. Row 1).');
                },
            ],
        ]);

        $totalSubmittedRooms = collect($validated['floors'] ?? [])->sum(function ($floor) {
            return count($floor['rooms'] ?? []);
        });

        if ($totalSubmittedRooms < 1) {
            throw ValidationException::withMessages([
                'floors' => ['Add at least one room before saving campus updates.'],
            ])->errorBag('campusWizard');
        }

        DB::transaction(function () use ($validated): void {
            $roomsHasCreatedAt = Schema::hasColumn('rooms_table', 'created_at');
            $roomsHasUpdatedAt = Schema::hasColumn('rooms_table', 'updated_at');
            $equipmentHasCreatedAt = Schema::hasColumn('equipment_table', 'created_at');
            $equipmentHasUpdatedAt = Schema::hasColumn('equipment_table', 'updated_at');

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

                $submittedRooms = [];

                foreach (($floorData['rooms'] ?? []) as $roomData) {
                    $roomName = trim((string) ($roomData['name'] ?? ''));

                    if ($roomName === '') {
                        continue;
                    }

                    foreach ($submittedRooms as $submitted) {
                        if (RoomName::matches($roomName, $submitted)) {
                            throw ValidationException::withMessages([
                                'floors' => [
                                    "Duplicate room name '{$roomData['name']}' found in {$floorData['level']}.",
                                ],
                            ])->errorBag('campusWizard');
                        }
                    }

                    $submittedRooms[] = $roomName;
                }

                $existingRooms = DB::table('rooms_table')
                    ->when(
                        Schema::hasColumn('rooms_table', 'room_is_archived'),
                        fn ($q) => $q->where(function ($query) {
                            $query->where('room_is_archived', false)->orWhereNull('room_is_archived');
                        })
                    )
                    ->get(['room_name']);

                foreach (($floorData['rooms'] ?? []) as $roomIndex => $roomData) {
                    $matched = $existingRooms->first(
                        fn ($existing) => RoomName::matches((string) $roomData['name'], (string) $existing->room_name)
                    );

                    if ($matched) {
                        throw ValidationException::withMessages([
                            'floors' => [
                                "Room '{$roomData['name']}' already exists as '{$matched->room_name}'.",
                            ],
                        ])->errorBag('campusWizard');
                    }

                    $position = $this->defaultRoomPosition($roomIndex);
                    $roomValues = [
                        'room_floor_id' => $floorId,
                        'room_name' => $roomData['name'],
                        'room_type' => $roomData['type'],
                        'room_status' => $roomData['status'],
                        'room_color' => $this->roomColor($roomData['type']),
                        'room_x' => $position['x'],
                        'room_y' => $position['y'],
                        'room_width' => 150,
                        'room_height' => 105,
                        'room_metadata' => json_encode(['wizard_floor_index' => $floorIndex]),
                    ];

                    if ($roomsHasCreatedAt) {
                        $roomValues['created_at'] = now();
                    }

                    if ($roomsHasUpdatedAt) {
                        $roomValues['updated_at'] = now();
                    }

                    if (Schema::hasColumn('rooms_table', 'room_is_archived')) {
                        $roomValues['room_is_archived'] = false;
                    }

                    $roomId = DB::table('rooms_table')->insertGetId($roomValues);

                    foreach ($roomData['equipment'] ?? [] as $equipment) {
                        // =====================================
                        // Default equipment position by location
                        // =====================================

                        $position = $this->zonePosition($equipment['zone']);
                        $values = [
                            'equipment_category_id' => $equipment['category_id'] ?: null,
                            'equipment_room_id' => $roomId,
                            'equipment_name' => $equipment['name'],
                            'equipment_quantity' => $equipment['quantity'],
                            'equipment_condition_status' => $equipment['condition'],
                            'equipment_inventory_status' => $equipment['condition'] === 'Under Maintenance' ? 'Under Maintenance' : 'Active',
                            'equipment_current_location' => $equipment['zone'], 'equipment_is_borrowable' => false,

                            'equipment_position_x' => $position['x'],

                            'equipment_position_y' => $position['y'],
                        ];

                        if ($equipmentHasUpdatedAt) {
                            $values['updated_at'] = now();
                        }
                        if (Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
                            $values['equipment_placement_zone'] = $equipment['zone'];
                        }

                        if ($equipmentHasCreatedAt) {
                            $values['created_at'] = now();
                        }

                        $equipmentId = DB::table('equipment_table')->insertGetId($values);
                        EquipmentQrCodes::assignIfEligible((int) $equipmentId);
                    }
                }
            }

        });

        return redirect()->route('maintenance.infrastructure.index')
            ->with('success', 'Campus setup saved successfully.');
    }

    public function saveLayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'floor_id' => ['required', 'integer', 'exists:floors_table,floor_id'],
            'rooms' => ['required', 'array'],
            'rooms.*.id' => ['required', 'integer'],
            'rooms.*.x' => ['required', 'integer', 'min:0', 'max:1800'],
            'rooms.*.y' => ['required', 'integer', 'min:0', 'max:900'],
            'rooms.*.width' => ['required','integer','min:20','max:600'],
            'rooms.*.height' => ['required', 'integer','min:80','max:450'],
            'rooms.*.rotation' => ['required', 'integer', 'min:0', 'max:360'],
            'rooms.*.color' => ['nullable', 'string', 'max:32'],
            'equipment' => ['nullable', 'array'],

            'equipment.*.id' => ['required', 'integer'],

            'equipment.*.x' => ['required', 'integer', 'min:0', 'max:100'],

            'equipment.*.y' => ['required', 'integer', 'min:0', 'max:100'],

            'equipment.*.zone' => ['nullable', 'string', 'max:100'],

            'equipment.*.width' => ['required','integer','min:20','max:1000'],

            'equipment.*.height' => ['required','integer','min:20','max:1000'],

            'equipment.*.rotation' => ['required','integer','min:0','max:360'],

            'comlab_room_id' => ['nullable', 'integer', 'exists:rooms_table,room_id'],

            'comlab_rows' => ['nullable', 'array'],

            'comlab_rows.*' => ['string', 'max:64'],

            'comlab_row_layouts' => ['nullable', 'array'],

            'comlab_row_layouts.*.name' => ['required', 'string', 'max:64'],

            'comlab_row_layouts.*.x' => ['required', 'integer', 'min:0', 'max:100'],

            'comlab_row_layouts.*.y' => ['required', 'integer', 'min:0', 'max:100'],

            'comlab_row_layouts.*.width' => ['required', 'integer', 'min:40', 'max:480'],

            'comlab_row_layouts.*.height' => ['required', 'integer', 'min:32', 'max:160'],

            'comlab_row_layouts.*.rotation' => ['required', 'integer', 'min:0', 'max:360'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['rooms'] as $room) {
                $roomModel = Room::query()->whereKey($room['id'])->where('room_floor_id', $validated['floor_id'])->first();

                if (!$roomModel) {
                    continue;
                }

                $metadata = $roomModel->room_metadata ?? [];
                $metadata['rotation'] = $room['rotation'];

                $roomModel->update([
                    'room_x' => $room['x'],
                    'room_y' => $room['y'],
                    'room_width' => $room['width'],
                    'room_height' => $room['height'],
                    'room_color' => $room['color'] ?? $roomModel->room_color,
                    'room_metadata' => $metadata,
                ]);
            }

            foreach ($validated['equipment'] ?? [] as $equipment) {

                Equipment::query()
                    ->whereKey($equipment['id'])
                    ->update([

                        'equipment_position_x' => $equipment['x'],

                        'equipment_position_y' => $equipment['y'],

                        'equipment_width' => $equipment['width'],

                        'equipment_height' => $equipment['height'],

                        'equipment_rotation' => $equipment['rotation'],

                        'equipment_current_location' => $equipment['zone'] ?? null,

                        'equipment_placement_zone' => $equipment['zone'] ?? null,

                    ]);

            }

            if (
                !empty($validated['comlab_room_id'])
                && (isset($validated['comlab_rows']) || isset($validated['comlab_row_layouts']))
            ) {
                $comlabRoom = Room::query()->find($validated['comlab_room_id']);

                if ($comlabRoom) {
                    $metadata = $comlabRoom->room_metadata ?? [];

                    if (isset($validated['comlab_row_layouts'])) {
                        $metadata['comlab_row_layouts'] = array_values($validated['comlab_row_layouts']);
                        $metadata['comlab_rows'] = array_column($validated['comlab_row_layouts'], 'name');
                    } elseif (isset($validated['comlab_rows'])) {
                        $metadata['comlab_rows'] = array_values($validated['comlab_rows']);
                    }

                    $comlabRoom->update(['room_metadata' => $metadata]);
                }
            }
        });

        return response()->json(['message' => 'Layout saved successfully.']);
    }

    public function updateRoom(Request $request, Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        $validated = $request->validate([

            'room_name'=>[
                'required',
                'string',
                'max:255'
            ],

            'room_type'=>[
                'nullable',
                'string',
                'max:255'
            ],

            'room_color'=>[
                'nullable',
                'string',
                'max:32'
            ],

            'room_status'=>[
                'nullable',
                'string',
                'max:255'
            ],

        ]);

        $room->update([

            'room_name'=>$validated['room_name'],

            'room_type'=>$validated['room_type'] ?? $room->room_type,

            'room_color'=>$validated['room_color'] ?? $room->room_color,

            'room_status'=>$validated['room_status'] ?? $room->room_status,

        ]);

        RoomActivityLog::create([

            'room_id'=>$room->room_id,

            'user_id'=>Auth::check()
                    ? Auth::id()
                    : null,

            'activity_type'=>'room_updated',

            'activity_title'=>'Room Updated',

            'activity_description' =>
                'Renamed to '.$room->room_name.
                ' and status changed to '.$room->room_status,

            'created_at'=>now()

        ]);

        return response()->json([
            'message' => 'Room name updated.',
            'room' => [
                'id' => $room->room_id,
                'name' => $room->room_name,
                'color' => $room->room_color,
            ],
        ]);
    }

    public function archiveRoom(Request $request, Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $equipmentCount = DB::table('equipment_table')
            ->where('equipment_room_id', $room->room_id)
            ->count();

        if ($equipmentCount > 0) {
            return response()->json([
                'code' => 'equipment_present',
                'equipment_count' => $equipmentCount,
                'message' => 'Transfer all equipment to another room before archiving this room.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($room, $validated): void {
                if (Schema::hasTable('workstation_slots_table') && Schema::hasColumn('workstation_slots_table', 'room_id')) {
                    $slotIds = DB::table('workstation_slots_table')
                        ->where('room_id', $room->room_id)
                        ->pluck('id');

                    if ($slotIds->isNotEmpty() && Schema::hasTable('workstation_slot_assets_table')) {
                        $slotKey = Schema::hasColumn('workstation_slot_assets_table', 'workstation_slot_id')
                            ? 'workstation_slot_id'
                            : (Schema::hasColumn('workstation_slot_assets_table', 'slot_id') ? 'slot_id' : null);
                        if ($slotKey) {
                            DB::table('workstation_slot_assets_table')
                                ->whereIn($slotKey, $slotIds)
                                ->delete();
                        }
                    }

                    DB::table('workstation_slots_table')
                        ->where('room_id', $room->room_id)
                        ->delete();
                }

                $metadata = $room->room_metadata ?: [];
                $metadata['archived_snapshot'] = [
                    'room_name' => $room->room_name,
                    'room_type' => $room->room_type,
                    'room_status' => $room->room_status,
                    'equipment_ids_removed' => [],
                    'archived_at' => now()->toDateTimeString(),
                ];

                $room->update([
                    'room_is_archived' => true,
                    'room_archived_at' => now(),
                    'room_archived_reason' => $validated['reason'] ?: 'Archived from layout editor',
                    'room_metadata' => $metadata,
                ]);
            });

            RoomActivityLog::create([
                'room_id' => $room->room_id,
                'user_id' => Auth::check() ? Auth::id() : null,
                'activity_type' => 'room_archived',
                'activity_title' => 'Room Archived',
                'activity_description' => $validated['reason'] ?: 'Room archived.',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to archive room.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'message' => 'Room archived and live details cleared.',
        ]);
    }

    public function updateEquipment(
        Request $request,
        Equipment $equipment
    ): JsonResponse {

        $validated = $request->validate([

            'equipment_name' => [
                'required',
                'string',
                'max:255'
            ],

            'equipment_category_id' => [
                'nullable',
                'integer',
                'exists:equipment_categories_table,equipment_category_id'
            ],

            'equipment_quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'equipment_condition_status' => [
                'required',
                Rule::in([
                    'Good',
                    'Damaged',
                    'Under Maintenance',
                    'Disposed'
                ])
            ],

            'equipment_current_location' => [
                'nullable',
                'string',
                'max:255'
            ],

        ]);

        // ===============================
        // Sync inventory status
        // ===============================
        

        $inventoryStatus = match ($validated['equipment_condition_status']) {

            'Disposed' => 'Disposed',

            'Under Maintenance' => 'Under Maintenance',

            default => 'Active',

        };

        $validated['equipment_inventory_status'] = $inventoryStatus;

        // =====================================
        // Automatically move equipment when
        // placement/location changes
        // =====================================

        $position = $this->zonePosition(
            $validated['equipment_current_location']
        );

        $validated['equipment_position_x'] = $position['x'];

        $validated['equipment_position_y'] = $position['y'];

        if (
            Schema::hasColumn(
                'equipment_table',
                'equipment_placement_zone'
            )
        ) {
            $validated['equipment_placement_zone']
                = $validated['equipment_current_location'];
        }

        $equipment->update($validated);

        RoomActivityLog::create([

            'room_id'=>$equipment->equipment_room_id,

            'equipment_id'=>$equipment->equipment_id,

            'user_id'=>Auth::check()
                    ? Auth::id()
                    : null,

            'activity_type'=>'equipment_updated',

            'activity_title'=>'Equipment Updated',

            'activity_description'=>

                $equipment->equipment_name.' was updated.',

            'created_at'=>now()

        ]);

        return response()->json([

            'success' => true,

            'message' => 'Equipment updated successfully.',

            'equipment' => $equipment->fresh('category'),

        ]);

    }

    public function transferEquipment(
        Request $request,
        Equipment $equipment
    ): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => [
                'required',
                'exists:rooms_table,room_id'
            ]
        ]);

        $this->moveEquipmentToRoom($equipment, (int) $validated['room_id']);

        return response()->json([
            'success' => true
        ]);
    }

    public function transferRoomEquipment(Request $request, Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        $validated = $request->validate([
            'transfers' => ['required', 'array', 'min:1'],
            'transfers.*.equipment_id' => [
                'required',
                'integer',
                'exists:equipment_table,equipment_id',
            ],
            'transfers.*.room_id' => [
                'required',
                'integer',
                'exists:rooms_table,room_id',
            ],
        ]);

        $moved = 0;

        DB::transaction(function () use ($validated, $room, &$moved): void {
            foreach ($validated['transfers'] as $transfer) {
                $equipmentId = (int) $transfer['equipment_id'];
                $destinationId = (int) $transfer['room_id'];

                if ($destinationId === (int) $room->room_id) {
                    throw ValidationException::withMessages([
                        'transfers' => ['Destination room must be different from the current room.'],
                    ]);
                }

                $equipment = Equipment::query()->findOrFail($equipmentId);
                if ((int) $equipment->equipment_room_id !== (int) $room->room_id) {
                    throw ValidationException::withMessages([
                        'transfers' => ['All selected equipment must belong to this room.'],
                    ]);
                }

                $this->moveEquipmentToRoom($equipment, $destinationId);
                $moved++;
            }
        });

        return response()->json([
            'success' => true,
            'moved' => $moved,
            'message' => $moved === 1
                ? '1 asset transferred.'
                : "{$moved} assets transferred.",
        ]);
    }

    private function moveEquipmentToRoom(Equipment $equipment, int $destinationRoomId): void
    {
        $oldRoom = $equipment->equipment_room_id;

        if ((int) $oldRoom === $destinationRoomId) {
            return;
        }

        $equipment->update([
            'equipment_room_id' => $destinationRoomId,
            // Clear layout placement so it lands in holding at the destination.
            'equipment_placement_zone' => 'Holding',
            'equipment_current_location' => 'Holding',
            'equipment_position_x' => 50,
            'equipment_position_y' => 90,
        ]);

        RoomActivityLog::create([
            'room_id' => $oldRoom,
            'equipment_id' => $equipment->equipment_id,
            'user_id' => Auth::check() ? Auth::id() : null,
            'activity_type' => 'equipment_transfer_out',
            'activity_title' => 'Equipment Transferred',
            'activity_description' => $equipment->equipment_name . ' moved to another room.',
            'created_at' => now(),
        ]);

        RoomActivityLog::create([
            'room_id' => $destinationRoomId,
            'equipment_id' => $equipment->equipment_id,
            'user_id' => Auth::check() ? Auth::id() : null,
            'activity_type' => 'equipment_transfer_in',
            'activity_title' => 'Equipment Received',
            'activity_description' => $equipment->equipment_name . ' transferred into this room.',
            'created_at' => now(),
        ]);
    }

    public function archiveEquipment(
        Equipment $equipment
    ): JsonResponse
    {
        $equipment->update([

            'equipment_condition_status'=>'Disposed',

            'equipment_inventory_status'=>'Disposed',

        ]);

        RoomActivityLog::create([

            'room_id'=>$equipment->equipment_room_id,

            'equipment_id'=>$equipment->equipment_id,

            'user_id'=>Auth::check()
                    ? Auth::id()
                    : null,

            'activity_type'=>'equipment_archived',

            'activity_title'=>'Equipment Archived',

            'activity_description'=>

                $equipment->equipment_name.' was archived.',

            'created_at'=>now()

        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function storeEquipment(
        Request $request
    ): JsonResponse
    {
        $validated = $request->validate([

            'equipment_room_id'=>[
                'required',
                'exists:rooms_table,room_id'
            ],

            'equipment_name'=>[
                'required',
                'string',
                'max:255'
            ],

            'equipment_category_id'=>[
                'nullable',
                'exists:equipment_categories_table,equipment_category_id'
            ],

            'equipment_quantity'=>[
                'required',
                'integer',
                'min:1',
                'max:200',
            ],

            'equipment_tracking_mode'=>[

                'required',

                Rule::in([

                'Bulk',

                'Individual'

                ])

                ],

            'equipment_condition_status'=>[
                'required',
                Rule::in(['Good', 'Damaged', 'Under Maintenance', 'Disposed']),
            ],

            'equipment_current_location'=>[
                'nullable',
                'string'
            ],

            'equipment_placement_zone'=>[
                'nullable',
                'string',
                'max:255',
            ],

            'equipment_brand_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'equipment_model' => [
                'nullable',
                'string',
                'max:255',
            ],

            'equipment_warranty_expiration' => [
                'nullable',
                'date',
            ],

            'equipment_asset_tag' => [
                'nullable',
                'string',
                'max:255',
            ],

            'equipment_serial_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'equipment_is_borrowable' => [
                'nullable',
                'boolean',
            ],

            'items' => [
                'nullable',
                'array',
                'max:200',
            ],

            'items.*.equipment_asset_tag' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.equipment_serial_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.equipment_brand_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.equipment_model' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.equipment_condition_status' => [
                'nullable',
                Rule::in(['Good', 'Damaged', 'Under Maintenance', 'Disposed']),
            ],

            'items.*.equipment_warranty_expiration' => [
                'nullable',
                'date',
            ],

            'items.*.equipment_placement_zone' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.equipment_current_location' => [
                'nullable',
                'string',
                'max:255',
            ],

        ]);

        $items = array_values($validated['items'] ?? []);

        if (
            ($validated['equipment_tracking_mode'] ?? '') === 'Individual'
            && count($items) > 0
            && count($items) !== (int) $validated['equipment_quantity']
        ) {
            throw ValidationException::withMessages([
                'items' => 'Item count must match the quantity when adding individually tracked equipment.',
            ]);
        }

        $this->assertUniqueEquipmentIdentifiers($items, $validated);

        $defaultInventoryStatus = match ($validated['equipment_condition_status']) {

            'Disposed' => 'Disposed',

            'Under Maintenance' => 'Under Maintenance',

            default => 'Active',

        };

        $borrowable = filter_var(
            $validated['equipment_is_borrowable'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        // =====================================
        // Default position from selected placement zone
        // =====================================

        $defaultZone = $validated['equipment_current_location']
            ?? $validated['equipment_placement_zone']
            ?? null;

        $position = $this->zonePosition((string) ($defaultZone ?? ''));

        $created = collect();

        DB::transaction(function () use (

            $validated,

            $items,

            $defaultInventoryStatus,

            $defaultZone,

            $position,

            $borrowable,

            &$created

        ) {

            if ($validated['equipment_tracking_mode'] === 'Bulk') {

                $created->push(Equipment::create([

                    'equipment_room_id' => $validated['equipment_room_id'],
                    'equipment_name' => $validated['equipment_name'],
                    'equipment_category_id' => $validated['equipment_category_id'] ?? null,
                    'equipment_quantity' => $validated['equipment_quantity'],
                    'equipment_tracking_mode' => 'Bulk',
                    'equipment_condition_status' => $validated['equipment_condition_status'],
                    'equipment_brand_name' => $validated['equipment_brand_name'] ?? null,
                    'equipment_model' => $validated['equipment_model'] ?? null,
                    'equipment_warranty_expiration' => $validated['equipment_warranty_expiration'] ?? null,
                    'equipment_asset_tag' => $validated['equipment_asset_tag'] ?? null,
                    'equipment_serial_number' => $validated['equipment_serial_number'] ?? null,
                    'equipment_is_borrowable' => $borrowable,
                    'equipment_current_location' => $defaultZone,
                    'equipment_inventory_status' => $defaultInventoryStatus,
                    'equipment_position_x' => $position['x'],
                    'equipment_position_y' => $position['y'],
                    'equipment_placement_zone' => $defaultZone,

                ]));

                return;
            }

            $rows = count($items) > 0
                ? $items
                : array_fill(0, (int) $validated['equipment_quantity'], []);

            foreach ($rows as $item) {
                $condition = $item['equipment_condition_status']
                    ?? $validated['equipment_condition_status'];

                $inventoryStatus = match ($condition) {
                    'Disposed' => 'Disposed',
                    'Under Maintenance' => 'Under Maintenance',
                    default => 'Active',
                };

                $itemZone = $item['equipment_placement_zone']
                    ?? $item['equipment_current_location']
                    ?? $defaultZone;

                $itemPosition = $this->zonePosition((string) ($itemZone ?? ''));

                $created->push(Equipment::create([
                    'equipment_room_id' => $validated['equipment_room_id'],
                    'equipment_name' => $validated['equipment_name'],
                    'equipment_category_id' => $validated['equipment_category_id'] ?? null,
                    'equipment_quantity' => 1,
                    'equipment_tracking_mode' => 'Individual',
                    'equipment_condition_status' => $condition,
                    'equipment_brand_name' => $item['equipment_brand_name']
                        ?? ($validated['equipment_brand_name'] ?? null),
                    'equipment_model' => $item['equipment_model']
                        ?? ($validated['equipment_model'] ?? null),
                    'equipment_warranty_expiration' => $item['equipment_warranty_expiration']
                        ?? ($validated['equipment_warranty_expiration'] ?? null),
                    'equipment_asset_tag' => $item['equipment_asset_tag']
                        ?? ($validated['equipment_asset_tag'] ?? null),
                    'equipment_serial_number' => $item['equipment_serial_number']
                        ?? ($validated['equipment_serial_number'] ?? null),
                    'equipment_is_borrowable' => $borrowable,
                    'equipment_current_location' => $itemZone,
                    'equipment_inventory_status' => $inventoryStatus,
                    'equipment_position_x' => $itemPosition['x'],
                    'equipment_position_y' => $itemPosition['y'],
                    'equipment_placement_zone' => $itemZone,
                ]));
            }

        });

        $equipment = $created->last();

        if (!$equipment) {

            return response()->json([

                'message' => 'Equipment could not be created.'

            ], 500);

        }

        $count = $created->count();
        $name = $equipment->equipment_name;

        RoomActivityLog::create([

            'room_id'=>$equipment->equipment_room_id,

            'equipment_id'=>$equipment->equipment_id,

            'user_id'=>Auth::check()
                    ? Auth::id()
                    : null,

            'activity_type'=>'equipment_added',

            'activity_title'=>'Equipment Added',

            'activity_description'=> $count > 1
                ? "Added {$count} × {$name}."
                : "{$name} was added.",

            'created_at'=>now()

        ]);

        return response()->json([

            'success'=>true,

            'created_count' => $count,

            'equipment'=>$equipment

        ]);
    }

    /**
     * Ensure asset tags / serials are unique within the batch and the database.
     */
    private function assertUniqueEquipmentIdentifiers(array $items, array $validated): void
    {
        $assetTags = [];
        $serials = [];

        $candidates = count($items) > 0
            ? $items
            : [[
                'equipment_asset_tag' => $validated['equipment_asset_tag'] ?? null,
                'equipment_serial_number' => $validated['equipment_serial_number'] ?? null,
            ]];

        foreach ($candidates as $index => $item) {
            $tag = trim((string) ($item['equipment_asset_tag'] ?? ''));
            $serial = trim((string) ($item['equipment_serial_number'] ?? ''));

            if ($tag !== '') {
                $key = mb_strtolower($tag);
                if (isset($assetTags[$key])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.equipment_asset_tag" => 'Duplicate asset tag in this batch.',
                    ]);
                }
                $assetTags[$key] = true;

                $exists = DB::table('equipment_table')
                    ->whereRaw('LOWER(equipment_asset_tag) = ?', [$key])
                    ->whereNotIn('equipment_inventory_status', ['Disposed'])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        "items.{$index}.equipment_asset_tag" => "Asset tag \"{$tag}\" is already in use.",
                    ]);
                }
            }

            if ($serial !== '') {
                $key = mb_strtolower($serial);
                if (isset($serials[$key])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.equipment_serial_number" => 'Duplicate serial number in this batch.',
                    ]);
                }
                $serials[$key] = true;

                $exists = DB::table('equipment_table')
                    ->whereRaw('LOWER(equipment_serial_number) = ?', [$key])
                    ->whereNotIn('equipment_inventory_status', ['Disposed'])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        "items.{$index}.equipment_serial_number" => "Serial number \"{$serial}\" is already in use.",
                    ]);
                }
            }
        }
    }

    private function buildWizardCampusData(?Building $campus): array
    {
        if (! $campus) {
            return [
                'building_name' => '',
                'building_logo' => null,
                'building_address' => null,
                'setup_locked' => false,
                'floors' => [],
            ];
        }

        return [
            'building_name' => $campus->building_name,
            'building_logo' => $campus->building_logo,
            'building_address' => $campus->building_address,
            'setup_locked' => $campus->floors->isNotEmpty(),
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
                        'rooms' => [],
                    ];
                })
                ->values(),
        ];
    }

    private function sanitizeCampusWizardPayload(array $payload): array
    {
        $payload['floors'] = collect($payload['floors'] ?? [])
            ->map(function ($floor) {
                $floor['rooms'] = collect($floor['rooms'] ?? [])
                    ->map(function ($room) {
                        $room['name'] = trim((string) ($room['name'] ?? ''));
                        $room['equipment'] = collect($room['equipment'] ?? [])
                            ->map(function ($equipment) {
                                $equipment['name'] = trim((string) ($equipment['name'] ?? ''));

                                return $equipment;
                            })
                            ->filter(fn ($equipment) => $this->campusWizardEquipmentHasMeaningfulData($equipment))
                            ->values()
                            ->all();

                        return $room;
                    })
                    ->filter(fn ($room) => $this->campusWizardRoomHasMeaningfulData($room))
                    ->values()
                    ->all();

                return $floor;
            })
            ->values()
            ->all();

        return $payload;
    }

    private function campusWizardRoomHasMeaningfulData(array $room): bool
    {
        if (trim((string) ($room['name'] ?? '')) !== '') {
            return true;
        }

        return collect($room['equipment'] ?? [])->contains(
            fn ($equipment) => $this->campusWizardEquipmentHasMeaningfulData((array) $equipment)
        );
    }

    private function campusWizardEquipmentHasMeaningfulData(array $equipment): bool
    {
        return trim((string) ($equipment['name'] ?? '')) !== ''
            || trim((string) ($equipment['category_id'] ?? '')) !== '';
    }

    private function defaultRoomPosition(int $index): array
    {
        return ['x' => 70 + (($index % 6) * 190), 'y' => 80 + ((int) floor($index / 6) * 190)];
    }

    private function roomColor(string $type): string
    {
        return match ($type) {
            'Lecture Room' => '#84CC16',
            'Computer Laboratory' => '#FFF200', 'Hospitality Suite' => '#F39200',
            'HM Room' => '#FB7185', 'Hotel Room Simulation' => '#EA580C',
            'Library' => '#A78BFA', 'Canteen' => '#84CC16', 'Clinic' => '#FB7185',
            'School Clinic' => '#FB7185', 'Faculty Room' => '#22C55E',
            'Office' => '#22C55E', 'Utility' => '#94A3B8',
            'Hallway' => '#CBD5E1', 'Exit' => '#22C55E', 'Restroom' => '#38BDF8',
            'Elevator' => '#64748B', 'Stairs' => '#94A3B8',
            default => '#60A5FA',
        };
    }

    // =====================================
    // Default Equipment Position
    // Place BELOW roomColor()
    // =====================================

    private function zonePosition(string $zone): array
    {
        $normalized = trim($zone);

        if ($normalized === 'Holding') {
            return ['x' => 50, 'y' => 90];
        }

        if ($normalized === 'Floor') {
            return ['x' => 78, 'y' => 52];
        }

        if (preg_match('/^Row\s+(\d+)$/i', $normalized, $matches)) {
            $n = max(1, (int) $matches[1]);

            return [
                'x' => 38,
                'y' => min(78, 20 + ($n * 18)),
            ];
        }

        return match ($normalized) {

            'Front Wall' => [
                'x' => 50,
                'y' => 15,
            ],

            'Rear Wall' => [
                'x' => 50,
                'y' => 85,
            ],

            'Left Row Pods' => [
                'x' => 18,
                'y' => 50,
            ],

            'Right Row Pods' => [
                'x' => 82,
                'y' => 50,
            ],

            'Center Ceiling' => [
                'x' => 50,
                'y' => 48,
            ],

            'Storage' => [
                'x' => 88,
                'y' => 88,
            ],

            default => [
                'x' => 50,
                'y' => 50,
            ],
        };
    }

    private function canManageCampusSetup(): bool
    {
        $roleId = (int) optional(Auth::user())->user_role_id;

        // Treat maintenance personnel as setup admins for campus wizard actions.
        return in_array($roleId, [1, 2], true);
    }
}
