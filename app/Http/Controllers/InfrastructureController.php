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
use App\Support\ComputerSets;
use App\Support\EquipmentTrackingModes;
use App\Support\LayoutEquipmentPayload;
use App\Support\PropertyAssignments;
use App\Support\EquipmentAcquisition;
use App\Support\RoleAccess;
use App\Support\BuildingLayout3D;
use App\Support\RoomCategories;
use App\Support\RoomStatusPalette;
use App\Support\RoomStatuses;
use App\Support\TransferLocks;
use App\Support\EquipmentConditions;
use App\Support\EquipmentIdentity;
use App\Support\EquipmentInventoryStatuses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $rooms = Room::query()->with([
                'floor.building',
                'equipment.category',
            ])
            ->when($hasRoomArchive, fn ($query) => $query->where('room_is_archived', false))
            ->orderBy('room_floor_id')->orderBy('room_name')->get();
        $roomIds = $rooms->pluck('room_id')->map(fn ($id) => (int) $id)->filter()->values();
        $reportMetrics = collect();
        $frequentProblems = collect();
        $schedules = collect();
        $lastInspections = collect();
        $nextMaintenances = collect();

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
                ->selectRaw('report_room_id, report_problem_description, COUNT(*) AS occurrences, MAX(report_submitted_at) AS last_reported_at')
                ->orderByDesc('occurrences')->get()->groupBy('report_room_id')
                ->map(fn ($items) => $items->take(3)->values());
        }

        if ($roomIds->isNotEmpty() && Schema::hasTable('maintenance_schedules_table')) {
            $schedules = DB::table('maintenance_schedules_table')
                ->join('equipment_table', 'maintenance_schedule_equipment_id', '=', 'equipment_id')
                ->whereIn('equipment_room_id', $roomIds)
                ->where('maintenance_schedule_status', '!=', 'Completed')
                ->orderBy('maintenance_schedule_next_date')
                ->select(array_values(array_unique(array_merge([
                    'equipment_room_id',
                    'equipment_name',
                    'maintenance_schedule_title',
                    'maintenance_schedule_next_date',
                    'maintenance_schedule_status',
                ], EquipmentIdentity::columns('equipment_table')))))
                ->get()->groupBy('equipment_room_id')->map(fn ($items) => $items->take(4)->values());

            $nextMaintenances = DB::table('maintenance_schedules_table')
                ->join('equipment_table', 'maintenance_schedules_table.maintenance_schedule_equipment_id', '=', 'equipment_table.equipment_id')
                ->whereIn('equipment_table.equipment_room_id', $roomIds)
                ->groupBy('equipment_table.equipment_room_id')
                ->selectRaw('equipment_table.equipment_room_id as room_id, MIN(maintenance_schedule_next_date) as next_date')
                ->pluck('next_date', 'room_id');
        }

        if ($roomIds->isNotEmpty() && Schema::hasTable('equipment_maintenance_history_table')) {
            $lastInspections = DB::table('equipment_maintenance_history_table')
                ->join(
                    'equipment_table',
                    'equipment_maintenance_history_table.equipment_maintenance_equipment_id',
                    '=',
                    'equipment_table.equipment_id'
                )
                ->whereIn('equipment_table.equipment_room_id', $roomIds)
                ->groupBy('equipment_table.equipment_room_id')
                ->selectRaw('equipment_table.equipment_room_id as room_id, MAX(equipment_maintenance_completed_at) as last_at')
                ->pluck('last_at', 'room_id');
        }

        // Same live status rules as 3D campus monitoring (Critical / Under Maintenance / Normal).
        $layoutStatusByRoom = BuildingLayout3D::rooms(['preset' => 'today'])->keyBy('room_id');

        $rooms->each(function (Room $room) use (
            $reportMetrics,
            $frequentProblems,
            $schedules,
            $layoutStatusByRoom,
            $lastInspections,
            $nextMaintenances
        ): void {
            $this->hydrateRoomMonitorPayload(
                $room,
                $layoutStatusByRoom->get($room->room_id),
                $reportMetrics->get($room->room_id),
                $frequentProblems->get($room->room_id, collect()),
                $schedules->get($room->room_id, collect()),
                $lastInspections->get($room->room_id),
                $nextMaintenances->get($room->room_id),
                withHistory: false
            );
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

        $requestedFloorId = (int) $request->integer('floor');
        $requestedRoomId = (int) $request->integer('room');
        if ($requestedFloorId <= 0 && $requestedRoomId > 0) {
            $requestedFloorId = (int) ($rooms->firstWhere('room_id', $requestedRoomId)->room_floor_id ?? 0);
        }

        $defaultFloorId = $this->campusDefaultFloorId($floors);
        if ($requestedFloorId <= 0 && $defaultFloorId > 0) {
            $requestedFloorId = $defaultFloorId;
        }

        return view('admin.infrastructure.monitor', [
            'buildings' => Building::query()->orderBy('building_name')->get(),
            'floors' => $floors,
            'rooms' => $rooms,
            'categories' => $categories,
            'wizardCampus' => $wizardCampus,
            'canManageCampusSetup' => $canManageCampusSetup,
            'requestedFloorId' => $requestedFloorId,
            'defaultFloorId' => $defaultFloorId,
            'defaultFloorUrl' => route('admin.infrastructure.default-floor.update'),
            'usedAssetTags' => $usedAssetTags,
            'buildingStatusUrl' => route('admin.infrastructure.status'),
            'statusPaletteUrl' => route('admin.infrastructure.status-palette.update'),
            'statusPalette' => RoomStatusPalette::colors(),
            'statusPaletteDefaults' => RoomStatusPalette::defaults(),
        ]);
    }

    public function updateDefaultFloor(Request $request): JsonResponse
    {
        abort_unless($this->canManageCampusSetup(), 403);

        $validated = $request->validate([
            'floor_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $floorId = (int) ($validated['floor_id'] ?? 0);
        if ($floorId > 0 && ! Floor::query()->where('floor_id', $floorId)->exists()) {
            return response()->json(['message' => 'That floor no longer exists.'], 422);
        }

        if (! Schema::hasTable('campus_setup_settings_table')) {
            return response()->json(['message' => 'Campus settings are unavailable.'], 503);
        }

        if (! Schema::hasColumn('campus_setup_settings_table', 'default_floor_id')) {
            return response()->json(['message' => 'Default floor setting is not installed yet. Run migrations.'], 503);
        }

        $setting = CampusSetupSetting::query()->first() ?? new CampusSetupSetting();
        $setting->default_floor_id = $floorId > 0 ? $floorId : null;
        $setting->save();

        $floor = $floorId > 0
            ? Floor::query()->with('building')->find($floorId)
            : null;

        return response()->json([
            'message' => $floor
                ? 'Default floor set to '.$floor->floor_level.'.'
                : 'Default floor cleared.',
            'default_floor_id' => $floorId > 0 ? $floorId : null,
            'default_floor_label' => $floor?->floor_level,
        ]);
    }

    public function buildingStatus(Request $request): JsonResponse
    {
        $period = [
            'preset' => (string) $request->query('preset', 'today'),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        [$from, $to, $preset] = BuildingLayout3D::resolvePeriod($period);

        return response()->json([
            'rooms' => BuildingLayout3D::statusSnapshot([
                'preset' => $preset,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]),
            'period' => [
                'preset' => $preset,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'palette' => RoomStatusPalette::colors(),
        ]);
    }

    public function updateStatusPalette(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'normal' => ['nullable', 'string', 'max:32'],
            'under_maintenance' => ['nullable', 'string', 'max:32'],
            'critical' => ['nullable', 'string', 'max:32'],
            'reset' => ['sometimes', 'boolean'],
        ]);

        $colors = ! empty($validated['reset'])
            ? RoomStatusPalette::reset()
            : RoomStatusPalette::update($validated);

        return response()->json([
            'message' => 'Status colors updated.',
            'palette' => $colors,
            'defaults' => RoomStatusPalette::defaults(),
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

    public function roomEquipment(Request $request, Room $room): JsonResponse
    {
        $equipment = $room->equipment()->with('category')->get();
        $custodians = PropertyAssignments::activeByEquipment($equipment->pluck('equipment_id')->all());

        $equipmentPayload = $equipment
            ->map(fn ($item) => LayoutEquipmentPayload::fromModel($item, $custodians[$item->equipment_id] ?? null))
            ->values();

        if (! $request->boolean('with_computer_sets')) {
            return response()->json($equipmentPayload);
        }

        return response()->json([
            'equipment' => $equipmentPayload,
            'computer_sets' => ComputerSets::layoutPayloadForRoom((int) $room->room_id),
        ]);
    }

    /**
     * Lazy HTML for the Building Rooms Layout inspector (one room only).
     */
    public function roomInspector(Room $room): View
    {
        abort_if((bool) ($room->room_is_archived ?? false), 404);

        $room->loadMissing(['floor.building', 'equipment.category']);

        $hasRoomArchive = Schema::hasColumn('rooms_table', 'room_is_archived');
        $roomId = (int) $room->room_id;
        $layout = BuildingLayout3D::rooms(['preset' => 'today'], [$roomId])->first();
        $metric = null;
        $frequentProblems = collect();
        $schedules = collect();
        $lastInspection = null;
        $nextMaintenance = null;

        if (Schema::hasTable('reports_table')) {
            $metric = DB::table('reports_table')->where('report_room_id', $roomId)
                ->selectRaw("
                    SUM(report_current_status NOT IN ('Resolved', 'Rejected')) AS active_count,
                    SUM(DATE(report_submitted_at) = CURDATE()) AS today_count,
                    SUM(report_submitted_at >= ?) AS week_count,
                    SUM(report_submitted_at >= ?) AS month_count
                ", [now()->startOfWeek(), now()->startOfMonth()])
                ->first();

            $frequentProblems = DB::table('reports_table')
                ->where('report_room_id', $roomId)
                ->whereNotNull('report_problem_description')
                ->groupBy('report_problem_description')
                ->selectRaw('report_problem_description, COUNT(*) AS occurrences, MAX(report_submitted_at) AS last_reported_at')
                ->orderByDesc('occurrences')
                ->limit(3)
                ->get();
        }

        if (Schema::hasTable('maintenance_schedules_table')) {
            $schedules = DB::table('maintenance_schedules_table')
                ->join('equipment_table', 'maintenance_schedule_equipment_id', '=', 'equipment_id')
                ->where('equipment_room_id', $roomId)
                ->where('maintenance_schedule_status', '!=', 'Completed')
                ->orderBy('maintenance_schedule_next_date')
                ->select(array_values(array_unique(array_merge([
                    'equipment_room_id',
                    'equipment_name',
                    'maintenance_schedule_title',
                    'maintenance_schedule_next_date',
                    'maintenance_schedule_status',
                ], EquipmentIdentity::columns('equipment_table')))))
                ->limit(4)
                ->get();

            $nextMaintenance = DB::table('maintenance_schedules_table')
                ->join(
                    'equipment_table',
                    'maintenance_schedules_table.maintenance_schedule_equipment_id',
                    '=',
                    'equipment_table.equipment_id'
                )
                ->where('equipment_table.equipment_room_id', $roomId)
                ->min('maintenance_schedule_next_date');
        }

        if (Schema::hasTable('equipment_maintenance_history_table')) {
            $lastInspection = DB::table('equipment_maintenance_history_table')
                ->join(
                    'equipment_table',
                    'equipment_maintenance_history_table.equipment_maintenance_equipment_id',
                    '=',
                    'equipment_table.equipment_id'
                )
                ->where('equipment_table.equipment_room_id', $roomId)
                ->max('equipment_maintenance_completed_at');
        }

        $this->hydrateRoomMonitorPayload(
            $room,
            $layout,
            $metric,
            $frequentProblems,
            $schedules,
            $lastInspection,
            $nextMaintenance,
            withHistory: true
        );

        // Keep destination list light: room names only (no campus-wide department hydrate).
        $destinationRooms = Room::query()
            ->when($hasRoomArchive, fn ($query) => $query->where('room_is_archived', false))
            ->orderBy('room_name')
            ->get(['room_id', 'room_name', 'room_type', 'room_is_archived']);

        foreach ($destinationRooms as $destination) {
            $destination->departments = [];
        }

        $categories = Schema::hasTable('equipment_categories_table')
            ? DB::table('equipment_categories_table')->orderBy('equipment_category_name')->get()
            : collect();

        return view('admin.infrastructure.monitor-drawer-room', [
            'room' => $room,
            'categories' => $categories,
            'transferDestinationRooms' => $this->mapTransferDestinationRooms($destinationRooms),
        ]);
    }

    /**
     * Active RR-linked equipment currently in storage, available to place into this room.
     */
    public function placeableStock(Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        if (RoomCategories::isStorageType($room->room_type ?? null)) {
            return response()->json([
                'items' => [],
                'message' => 'Select a non-storage room to place equipment from stock.',
            ]);
        }

        if (! Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
            return response()->json(['items' => []]);
        }

        $storageRoomIds = $this->storageRoomIds();
        if ($storageRoomIds === []) {
            return response()->json(['items' => []]);
        }

        $query = DB::table('equipment_table as e')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->leftJoin(
                'equipment_categories_table as c',
                'c.equipment_category_id',
                '=',
                'e.equipment_category_id'
            )
            ->whereIn('e.equipment_room_id', $storageRoomIds)
            ->where('e.equipment_inventory_status', 'Active')
            ->where('e.equipment_receiving_report_item_id', '>', 0)
            ->where('e.equipment_room_id', '!=', $room->room_id)
            ->orderBy('e.equipment_name')
            ->orderBy('e.equipment_id');

        if (Schema::hasTable('receiving_report_items_table')) {
            $query->leftJoin(
                'receiving_report_items_table as rri',
                'rri.receiving_report_item_id',
                '=',
                'e.equipment_receiving_report_item_id'
            );
            if (Schema::hasTable('receiving_reports_table')) {
                $query->leftJoin(
                    'receiving_reports_table as rr',
                    'rr.receiving_report_id',
                    '=',
                    'rri.receiving_report_id'
                );
            }
        }

        $select = [
            'e.equipment_id',
            'e.equipment_name',
            'e.equipment_asset_tag',
            'e.equipment_serial_number',
            'e.equipment_brand_name',
            'e.equipment_model',
            'e.equipment_condition_status',
            'e.equipment_inventory_status',
            'e.equipment_room_id',
            'e.equipment_receiving_report_item_id',
            'r.room_name as storage_room_name',
            'c.equipment_category_name',
        ];

        if (Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')) {
            $select[] = 'rr.receiving_report_form_number';
            $select[] = 'rr.receiving_report_id';
        }

        $rows = $query->get($select);
        $loans = TransferLocks::openLoans($rows->pluck('equipment_id')->all());

        $items = $rows
            ->reject(fn ($row) => TransferLocks::reason($row, $loans) !== null)
            ->map(function ($row) {
                $rrNo = trim((string) ($row->receiving_report_form_number ?? ''));
                $rrId = (int) ($row->receiving_report_id ?? 0);

                return [
                    'id' => (int) $row->equipment_id,
                    'name' => (string) ($row->equipment_name ?? 'Equipment'),
                    'asset_tag' => $row->equipment_asset_tag,
                    'serial_number' => $row->equipment_serial_number,
                    'brand' => $row->equipment_brand_name,
                    'model' => $row->equipment_model,
                    'condition' => $row->equipment_condition_status,
                    'category_name' => $row->equipment_category_name,
                    'storage_room_id' => (int) $row->equipment_room_id,
                    'storage_room_name' => (string) ($row->storage_room_name ?? 'Storage'),
                    'receiving_report_item_id' => (int) $row->equipment_receiving_report_item_id,
                    'receiving_report_id' => $rrId > 0 ? $rrId : null,
                    'receiving_report_number' => $rrNo !== ''
                        ? $rrNo
                        : ($rrId > 0 ? 'RR #'.$rrId : null),
                ];
            })
            ->values()
            ->all();

        return response()->json(['items' => $items]);
    }

    /**
     * Move selected storage RR-linked assets into this room (Holding).
     */
    public function placeFromStorage(Request $request, Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        if (RoomCategories::isStorageType($room->room_type ?? null)) {
            throw ValidationException::withMessages([
                'room' => ['Cannot place equipment into a storage room from this action.'],
            ]);
        }

        $validated = $request->validate([
            'equipment_ids' => ['required', 'array', 'min:1'],
            'equipment_ids.*' => ['required', 'integer', 'exists:equipment_table,equipment_id'],
            'department_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $destinationId = (int) $room->room_id;
        $departmentId = $this->resolvedTransferDepartmentId($validated['department_id'] ?? null);
        $storageRoomIds = $this->storageRoomIds();
        $hasRrFk = Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id');

        if ($storageRoomIds === []) {
            throw ValidationException::withMessages([
                'equipment_ids' => ['No storage rooms are configured.'],
            ]);
        }

        $moved = 0;

        DB::transaction(function () use (
            $validated,
            $destinationId,
            $departmentId,
            $storageRoomIds,
            $hasRrFk,
            &$moved
        ): void {
            foreach ($validated['equipment_ids'] as $equipmentId) {
                $equipment = Equipment::query()->findOrFail((int) $equipmentId);

                if (! in_array((int) $equipment->equipment_room_id, $storageRoomIds, true)) {
                    throw ValidationException::withMessages([
                        'equipment_ids' => [
                            ($equipment->equipment_name ?: 'Equipment').' is not in storage.',
                        ],
                    ]);
                }

                if ($hasRrFk && (int) ($equipment->equipment_receiving_report_item_id ?? 0) < 1) {
                    throw ValidationException::withMessages([
                        'equipment_ids' => [
                            ($equipment->equipment_name ?: 'Equipment').' is not linked to an RR. Stock it from Inventory first.',
                        ],
                    ]);
                }

                if ((string) ($equipment->equipment_inventory_status ?? '') !== 'Active') {
                    throw ValidationException::withMessages([
                        'equipment_ids' => [
                            ($equipment->equipment_name ?: 'Equipment').' must be Active to place.',
                        ],
                    ]);
                }

                $this->moveEquipmentToRoom($equipment, $destinationId, $departmentId);
                $moved++;
            }
        });

        return response()->json([
            'success' => true,
            'moved' => $moved,
            'message' => $moved === 1
                ? '1 asset placed from storage.'
                : "{$moved} assets placed from storage.",
        ]);
    }

    /**
     * @return list<int>
     */
    private function storageRoomIds(): array
    {
        $query = Room::query()->select(['room_id', 'room_type']);
        if (Schema::hasColumn('rooms_table', 'room_is_archived')) {
            $query->where('room_is_archived', false);
        }

        return $query
            ->get()
            ->filter(fn (Room $room) => RoomCategories::isStorageType($room->room_type ?? null))
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
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
            'building_name' => ['required', 'string', 'max:'.\App\Support\InputLimits::BUILDING_NAME],

            'building_logo' => ['nullable', 'string', 'max:'.\App\Support\InputLimits::NAME],

            'building_address' => ['nullable', 'string'],
            'floors' => ['required', 'array', 'min:1'],
            'floors.*.level' => [

                'required',

                'string',

                'max:50',

            ],
            'floors.*.rooms' => ['nullable', 'array'],
            'floors.*.rooms.*.name' => ['required', 'string', 'max:'.\App\Support\InputLimits::ROOM_NAME],
            'floors.*.rooms.*.type' => ['required', Rule::in(array_values(array_unique(array_merge(
                RoomCategories::values(),
                [
                    'Hospitality Suite', 'Canteen', 'Clinic', 'Utility', 'Hallway', 'Exit',
                    'Restroom', 'Elevator', 'Stairs',
                ]
            ))))],
            'floors.*.rooms.*.status' => ['required', Rule::in(RoomStatuses::all())],
            'floors.*.rooms.*.equipment' => ['nullable', 'array'],
            'floors.*.rooms.*.equipment.*.name' => ['required', 'string', 'max:'.\App\Support\InputLimits::NAME],
            'floors.*.rooms.*.equipment.*.category_id' => ['nullable', 'integer', 'exists:equipment_categories_table,equipment_category_id'],
            'floors.*.rooms.*.equipment.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'floors.*.rooms.*.equipment.*.condition' => ['required', Rule::in(EquipmentConditions::ALL)],
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
                        'room_status' => RoomStatuses::normalize($roomData['status'] ?? null),
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

        return redirect()->route('admin.infrastructure.index')
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

            'computer_sets' => ['nullable', 'array'],
            'computer_sets.*.id' => ['nullable', 'integer', 'min:1'],
            'computer_sets.*.name' => ['nullable', 'string', 'max:120'],
            'computer_sets.*.placement_zone' => ['nullable', 'string', 'max:120'],
            'computer_sets.*.members' => ['nullable', 'array'],
            'computer_sets.*.members.*.id' => ['required', 'integer', 'min:1'],
            'computer_sets.*.members.*.role' => ['nullable', 'string', 'max:40'],
        ]);

        $syncedComputerSets = [];

        try {
            DB::transaction(function () use ($validated, &$syncedComputerSets): void {
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
                        // room_color is unused for map fill (global status palette).
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

                if (
                    ! empty($validated['comlab_room_id'])
                    && array_key_exists('computer_sets', $validated)
                    && ComputerSets::tablesReady()
                ) {
                    $syncedComputerSets = ComputerSets::syncRoomFromLayout(
                        (int) $validated['comlab_room_id'],
                        $validated['computer_sets'] ?? []
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Layout saved successfully.',
            'computer_sets' => $syncedComputerSets,
        ]);
    }

    public function updateRoom(Request $request, Room $room): JsonResponse
    {
        abort_if($room->room_is_archived, 404);

        $validated = $request->validate([

            'room_name'=>[
                'required',
                'string',
                'max:'.\App\Support\InputLimits::ROOM_NAME,
            ],

            'room_type'=>[
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::NAME,
            ],

            // room_status and room_color are not editable here:
            // status is layout-derived; colors come from the global status palette.
        ]);

        $room->update([

            'room_name'=>$validated['room_name'],

            'room_type'=>$validated['room_type'] ?? $room->room_type,

        ]);

        RoomActivityLog::create([

            'room_id'=>$room->room_id,

            'user_id'=>Auth::check()
                    ? Auth::id()
                    : null,

            'activity_type'=>'room_updated',

            'activity_title'=>'Room Updated',

            'activity_description' =>
                'Updated room details to '.$room->room_name.
                ' ('.$room->room_type.')',

            'created_at'=>now()

        ]);

        return response()->json([
            'message' => 'Room updated.',
            'room' => [
                'id' => $room->room_id,
                'name' => $room->room_name,
                'type' => $room->room_type,
                'image_url' => $this->roomImageUrl($room->room_image),
            ],
        ]);
    }

    /**
     * Upload or replace the room photo shown in the Room Inspector.
     */
    public function uploadRoomImage(Request $request, Room $room): JsonResponse
    {
        abort_if((bool) ($room->room_is_archived ?? false), 404);

        if (! Schema::hasColumn('rooms_table', 'room_image')) {
            return response()->json([
                'message' => 'Room images are not available yet. Run migrations first.',
            ], 422);
        }

        $validated = $request->validate([
            'room_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ]);

        $previous = $room->room_image;
        $path = $request->file('room_image')->store('room-images', 'public');

        $room->update([
            'room_image' => $path,
        ]);

        $this->deleteRoomImageIfUnused($previous);

        RoomActivityLog::create([
            'room_id' => $room->room_id,
            'user_id' => Auth::check() ? Auth::id() : null,
            'activity_type' => 'room_image_updated',
            'activity_title' => 'Room Photo Updated',
            'activity_description' => 'Updated the photo for '.$room->room_name.'.',
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Room photo updated.',
            'room' => [
                'id' => $room->room_id,
                'image' => $path,
                'image_url' => $this->roomImageUrl($path),
            ],
        ]);
    }

    /**
     * Remove the room photo from the Room Inspector.
     */
    public function deleteRoomImage(Room $room): JsonResponse
    {
        abort_if((bool) ($room->room_is_archived ?? false), 404);

        if (! Schema::hasColumn('rooms_table', 'room_image')) {
            return response()->json([
                'message' => 'Room images are not available yet. Run migrations first.',
            ], 422);
        }

        $previous = $room->room_image;
        if (! filled($previous)) {
            return response()->json([
                'message' => 'This room has no photo.',
                'room' => [
                    'id' => $room->room_id,
                    'image' => null,
                    'image_url' => null,
                ],
            ]);
        }

        $room->update([
            'room_image' => null,
        ]);

        $this->deleteRoomImageIfUnused($previous);

        RoomActivityLog::create([
            'room_id' => $room->room_id,
            'user_id' => Auth::check() ? Auth::id() : null,
            'activity_type' => 'room_image_removed',
            'activity_title' => 'Room Photo Removed',
            'activity_description' => 'Removed the photo for '.$room->room_name.'.',
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Room photo removed.',
            'room' => [
                'id' => $room->room_id,
                'image' => null,
                'image_url' => null,
            ],
        ]);
    }

    public function archiveRoom(Request $request, Room $room): JsonResponse
    {
        return response()->json([
            'code' => 'room_permanent',
            'message' => 'Rooms cannot be archived. A room stays in the building — edit its type, name, or status instead; changes are kept in history.',
        ], 422);
    }

    public function updateEquipment(
        Request $request,
        Equipment $equipment
    ): JsonResponse {

        $validated = $request->validate([

            'equipment_name' => [
                'required',
                'string',
                'max:'.\App\Support\InputLimits::NAME,
            ],

            'equipment_category_id' => [
                'nullable',
                'integer',
                'exists:equipment_categories_table,equipment_category_id'
            ],

            'equipment_quantity' => [
                'required',
                'integer',
                'min:1',
                'max:'.\App\Support\InputLimits::QTY,
            ],

            'equipment_condition_status' => [
                'required',
                Rule::in(EquipmentConditions::ALL),
            ],

            'equipment_current_location' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::LOCATION,
            ],

        ]);

        // ===============================
        // Sync inventory status
        // ===============================

        $validated['equipment_inventory_status'] = EquipmentConditions::syncInventoryStatus(
            $validated['equipment_condition_status']
        );

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
                'exists:rooms_table,room_id',
            ],
            'department_id' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $departmentId = $this->resolvedTransferDepartmentId($validated['department_id'] ?? null);

        $this->moveEquipmentToRoom($equipment, (int) $validated['room_id'], $departmentId);

        return response()->json([
            'success' => true,
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
            'transfers.*.department_id' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $moved = 0;

        DB::transaction(function () use ($validated, $room, &$moved): void {
            foreach ($validated['transfers'] as $transfer) {
                $equipmentId = (int) $transfer['equipment_id'];
                $destinationId = (int) $transfer['room_id'];
                $departmentId = $this->resolvedTransferDepartmentId($transfer['department_id'] ?? null);

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

                $this->moveEquipmentToRoom($equipment, $destinationId, $departmentId);
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

    private function resolvedTransferDepartmentId(mixed $departmentId): ?int
    {
        $id = (int) ($departmentId ?? 0);
        if ($id <= 0) {
            return null;
        }

        if (
            Schema::hasTable('departments_table')
            && ! DB::table('departments_table')->where('department_id', $id)->exists()
        ) {
            throw ValidationException::withMessages([
                'department_id' => ['Selected department was not found.'],
            ]);
        }

        return $id;
    }

    private function moveEquipmentToRoom(Equipment $equipment, int $destinationRoomId, ?int $departmentId = null): void
    {
        $oldRoom = $equipment->equipment_room_id;
        $sameRoom = (int) $oldRoom === $destinationRoomId;

        if ($sameRoom && $departmentId === null) {
            return;
        }

        if ($locked = TransferLocks::reason($equipment)) {
            throw ValidationException::withMessages(['transfers' => [$locked]]);
        }

        $updates = [];

        if (! $sameRoom) {
            $updates = [
                'equipment_room_id' => $destinationRoomId,
                // Clear layout placement so it lands in holding at the destination.
                'equipment_placement_zone' => 'Holding',
                'equipment_current_location' => 'Holding',
                'equipment_position_x' => 50,
                'equipment_position_y' => 90,
            ];
        }

        if ($departmentId !== null && Schema::hasColumn('equipment_table', 'equipment_department_id')) {
            $updates['equipment_department_id'] = $departmentId;
        }

        if ($updates !== []) {
            $equipment->update($updates);
        }

        $transferId = null;
        if (Schema::hasTable('equipment_transfer_history_table')) {
            $history = [
                'equipment_id' => $equipment->equipment_id,
                'from_room_id' => $oldRoom,
                'to_room_id' => $destinationRoomId,
                'transferred_by' => Auth::check() ? Auth::id() : null,
                'remarks' => 'Transferred via Building Layout',
                'created_at' => now(),
            ];

            if ($departmentId !== null && Schema::hasColumn('equipment_transfer_history_table', 'to_department_id')) {
                $history['to_department_id'] = $departmentId;
            }

            $transferId = DB::table('equipment_transfer_history_table')->insertGetId($history);
        }

        if (! $sameRoom) {
            $summary = ($equipment->equipment_name ?? 'Equipment').' was transferred via Building Layout.';

            RoomActivityLog::create([
                'room_id' => $oldRoom,
                'equipment_id' => $equipment->equipment_id,
                'user_id' => Auth::check() ? Auth::id() : null,
                'activity_type' => 'equipment_transfer_out',
                'activity_title' => 'Equipment Transferred',
                'activity_description' => $equipment->equipment_name.' moved to another room.',
                'created_at' => now(),
            ]);

            RoomActivityLog::create([
                'room_id' => $destinationRoomId,
                'equipment_id' => $equipment->equipment_id,
                'user_id' => Auth::check() ? Auth::id() : null,
                'activity_type' => 'equipment_transfer_in',
                'activity_title' => 'Equipment Received',
                'activity_description' => $equipment->equipment_name.' transferred into this room.',
                'created_at' => now(),
            ]);

            if (Schema::hasTable('notifications_table')) {
                DB::table('notifications_table')->insertOrIgnore([
                    'notification_user_id' => null,
                    'notification_target_role' => 'Maintenance Personnel',
                    'notification_title' => 'Equipment Transferred',
                    'notification_message' => $summary,
                    'notification_type' => 'equipment_transferred',
                    'notification_category' => 'Equipment',
                    'notification_reference_type' => 'equipment_transfer',
                    'notification_reference_id' => $transferId,
                    'notification_url' => '/maintenance/equipment/deployed',
                    'notification_event_key' => 'equipment_transferred_layout_'.($transferId ?: $equipment->equipment_id.'_'.now()->timestamp),
                    'notification_created_at' => now(),
                ]);
            }

            if (Schema::hasTable('audit_logs_table')) {
                DB::table('audit_logs_table')->insert([
                    'audit_log_user_id' => Auth::id(),
                    'audit_log_action' => 'Transferred equipment',
                    'audit_log_module' => 'Equipment',
                    'audit_log_table_name' => 'equipment_table',
                    'audit_log_reference_id' => $equipment->equipment_id,
                    'audit_log_description' => $summary,
                    'audit_log_created_at' => now(),
                ]);
            }
        }
    }

    public function archiveEquipment(
        Equipment $equipment
    ): JsonResponse
    {
        if ($locked = TransferLocks::reason($equipment)) {
            throw ValidationException::withMessages(['equipment' => [$locked]]);
        }

        DB::transaction(function () use ($equipment) {
            $equipment->update([
                'equipment_condition_status' => 'Disposed',
                'equipment_inventory_status' => 'Disposed',
            ]);

            if (Schema::hasTable('disposal_records_table')) {
                $exists = DB::table('disposal_records_table')
                    ->where('disposal_equipment_id', $equipment->equipment_id)
                    ->when(
                        Schema::hasColumn('disposal_records_table', 'disposal_is_archived'),
                        fn ($q) => $q->where(function ($inner) {
                            $inner->whereNull('disposal_is_archived')->orWhere('disposal_is_archived', 0);
                        })
                    )
                    ->exists();

                if (! $exists) {
                    $insert = [
                        'disposal_equipment_id' => $equipment->equipment_id,
                        'disposal_reason' => 'Archived via Building Layout',
                        'disposal_approved_by' => Auth::id(),
                        'disposal_disposed_at' => now(),
                    ];
                    if (Schema::hasColumn('disposal_records_table', 'disposal_method')) {
                        $insert['disposal_method'] = 'Archive';
                    }
                    if (Schema::hasColumn('disposal_records_table', 'disposal_area_location')) {
                        $insert['disposal_area_location'] = $equipment->equipment_current_location
                            ?? $equipment->equipment_placement_zone
                            ?? null;
                    }
                    DB::table('disposal_records_table')->insert($insert);
                }
            }

            RoomActivityLog::create([
                'room_id' => $equipment->equipment_room_id,
                'equipment_id' => $equipment->equipment_id,
                'user_id' => Auth::check() ? Auth::id() : null,
                'activity_type' => 'equipment_archived',
                'activity_title' => 'Equipment Archived',
                'activity_description' => $equipment->equipment_name.' was archived.',
                'created_at' => now(),
            ]);

            if (Schema::hasTable('audit_logs_table')) {
                DB::table('audit_logs_table')->insert([
                    'audit_log_user_id' => Auth::id(),
                    'audit_log_action' => 'Archived equipment',
                    'audit_log_module' => 'Equipment',
                    'audit_log_table_name' => 'equipment_table',
                    'audit_log_reference_id' => $equipment->equipment_id,
                    'audit_log_description' => ($equipment->equipment_name ?? 'Equipment').' was archived via Building Layout.',
                    'audit_log_created_at' => now(),
                ]);
            }
        });

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
                'max:'.\App\Support\InputLimits::NAME,
            ],

            'equipment_category_id'=>[
                'nullable',
                'exists:equipment_categories_table,equipment_category_id'
            ],

            'equipment_quantity'=>[
                'required',
                'integer',
                'min:1',
                'max:'.min(200, \App\Support\InputLimits::QTY),
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
                Rule::in(EquipmentConditions::ALL),
            ],

            'equipment_current_location'=>[
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::LOCATION,
            ],

            'equipment_placement_zone'=>[
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::LOCATION,
            ],

            'equipment_brand_name' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::BRAND,
            ],

            'equipment_model' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::MODEL,
            ],

            'equipment_warranty_expiration' => [
                'nullable',
                'date',
            ],

            'equipment_asset_tag' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::ASSET_TAG,
            ],

            'equipment_serial_number' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::SERIAL,
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
                'max:'.\App\Support\InputLimits::ASSET_TAG,
            ],

            'items.*.equipment_serial_number' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::SERIAL,
            ],

            'items.*.equipment_brand_name' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::BRAND,
            ],

            'items.*.equipment_model' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::MODEL,
            ],

            'items.*.equipment_condition_status' => [
                'nullable',
                Rule::in(EquipmentConditions::ALL),
            ],

            'items.*.equipment_warranty_expiration' => [
                'nullable',
                'date',
            ],

            'items.*.equipment_placement_zone' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::LOCATION,
            ],

            'items.*.equipment_current_location' => [
                'nullable',
                'string',
                'max:'.\App\Support\InputLimits::LOCATION,
            ],

            'equipment_purchase_date' => ['nullable', 'date'],

            'equipment_acquired_date' => ['nullable', 'date'],

            'equipment_purchase_cost' => ['nullable', 'numeric', 'min:0'],

            'equipment_useful_life_years' => ['nullable', 'integer', 'min:1', 'max:50'],

            'custodian_id' => ['nullable', 'integer', 'min:1'],

            'replaces_equipment_id' => ['nullable', 'integer', 'min:1'],

        ] + EquipmentAcquisition::validationRules());

        $items = array_values($validated['items'] ?? []);

        if (
            ($validated['equipment_tracking_mode'] ?? '') === EquipmentTrackingModes::BULK
            && EquipmentTrackingModes::requiresIndividualForCategory(
                (int) ($validated['equipment_category_id'] ?? 0)
            )
        ) {
            throw ValidationException::withMessages([
                'equipment_tracking_mode' => 'Computer Equipment must be stocked as Individual units (one identity per piece). Use quantity to add many.',
            ]);
        }

        $custodianId = (int) ($validated['custodian_id'] ?? 0);
        if ($custodianId > 0) {
            $targetRoomType = DB::table('rooms_table')
                ->where('room_id', $validated['equipment_room_id'])
                ->value('room_type');
            if (RoomCategories::isStorageType($targetRoomType)) {
                throw ValidationException::withMessages([
                    'custodian_id' => 'Equipment in a storage room cannot be assigned to a person. Deploy it to a room first.',
                ]);
            }
            if (($validated['equipment_tracking_mode'] ?? '') !== 'Individual') {
                throw ValidationException::withMessages([
                    'custodian_id' => 'Only individually tracked equipment can be assigned to a person.',
                ]);
            }
        }

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

        $defaultInventoryStatus = EquipmentConditions::syncInventoryStatus(
            $validated['equipment_condition_status']
        );

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

        $intakeMeta = ['equipment_acquired_date' => $validated['equipment_acquired_date'] ?? now()->toDateString()];
        if (Schema::hasColumn('equipment_table', 'equipment_stocked_by') && Auth::id()) {
            $intakeMeta['equipment_stocked_by'] = Auth::id();
        }

        $detailColumns = EquipmentAcquisition::manualColumns($validated);
        foreach (['equipment_purchase_date', 'equipment_purchase_cost', 'equipment_useful_life_years'] as $column) {
            if (
                array_key_exists($column, $validated)
                && $validated[$column] !== null
                && $validated[$column] !== ''
                && Schema::hasColumn('equipment_table', $column)
            ) {
                $detailColumns[$column] = $validated[$column];
            }
        }
        $replacesId = (int) ($validated['replaces_equipment_id'] ?? 0);

        DB::transaction(function () use (

            $validated,

            $items,

            $defaultInventoryStatus,

            $defaultZone,

            $position,

            $borrowable,

            $intakeMeta,

            $detailColumns,

            $replacesId,

            $custodianId,

            &$created

        ) {

            if ($validated['equipment_tracking_mode'] === 'Bulk') {

                $created->push(Equipment::create($intakeMeta + [

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

                $this->applyIntakeDetails($created, $detailColumns, $replacesId, 0);

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

                $created->push(Equipment::create($intakeMeta + [
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

            $this->applyIntakeDetails($created, $detailColumns, $replacesId, $custodianId);

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
     * Acquisition / purchase columns, replacement link, and custodian issue for
     * equipment created from the room layout.
     */
    private function applyIntakeDetails($created, array $detailColumns, int $replacesId, int $custodianId): void
    {
        $ids = $created->pluck('equipment_id')->map(fn ($id) => (int) $id)->filter()->values()->all();
        if ($ids === []) {
            return;
        }

        if ($detailColumns !== []) {
            DB::table('equipment_table')->whereIn('equipment_id', $ids)->update($detailColumns);
        }

        if ($replacesId > 0 && Schema::hasColumn('equipment_table', 'equipment_replaces_id')) {
            DB::table('equipment_table')
                ->whereIn('equipment_id', $ids)
                ->update(['equipment_replaces_id' => $replacesId]);
            if (Schema::hasColumn('equipment_table', 'equipment_replaced_by_id')) {
                DB::table('equipment_table')
                    ->where('equipment_id', $replacesId)
                    ->update(['equipment_replaced_by_id' => $ids[0]]);
            }
        }

        if ($custodianId > 0) {
            try {
                PropertyAssignments::issueMany($ids, $custodianId, ['notes' => 'Issued on intake']);
            } catch (\Illuminate\Database\QueryException $e) {
                throw $e;
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['custodian_id' => $e->getMessage()]);
            }
        }
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

    private function roomImageUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    private function deleteRoomImageIfUnused(?string $path): void
    {
        if (! filled($path) || ! Schema::hasColumn('rooms_table', 'room_image')) {
            return;
        }

        $stillUsed = Room::query()
            ->where('room_image', $path)
            ->exists();

        if ($stillUsed) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
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
            'Storage / Stockroom' => '#78716C',
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

    /**
     * Attach layout fields + monitoring payload used by the map catalog / inspector.
     */
    private function hydrateRoomMonitorPayload(
        Room $room,
        mixed $layout,
        mixed $metric,
        mixed $frequentProblems,
        mixed $schedules,
        mixed $lastInspection,
        mixed $nextMaintenance,
        bool $withHistory = false
    ): void {
        $layoutStatus = (string) (data_get($layout, 'dashboard_status') ?? BuildingLayout3D::STATUS_NORMAL);
        $layoutLabel = (string) (data_get($layout, 'dashboard_label') ?? RoomStatuses::NORMAL);
        $statusColor = (string) (data_get($layout, 'status_color') ?? RoomStatusPalette::colorForStatus($layoutStatus));

        $room->layout_status = $layoutStatus;
        $room->layout_label = $layoutLabel;
        $room->status_color = $statusColor;
        $room->departments = data_get($layout, 'departments') ?? [];
        $room->report_count = (int) (data_get($layout, 'report_count') ?? 0);
        $room->critical_report_count = (int) (data_get($layout, 'critical_report_count') ?? 0);
        $room->high_report_count = (int) (data_get($layout, 'high_report_count') ?? 0);
        $room->medium_report_count = (int) (data_get($layout, 'medium_report_count') ?? 0);
        $room->low_report_count = (int) (data_get($layout, 'low_report_count') ?? 0);
        $room->under_maintenance_scheduled = (bool) (data_get($layout, 'under_maintenance_scheduled') ?? false);
        $room->maintenance_scopes = data_get($layout, 'maintenance_scopes') ?? [];
        $room->maintenance_schedules = data_get($layout, 'maintenance_schedules') ?? [];
        $room->room_inspection_next_date = data_get($layout, 'room_inspection_next_date');

        $equipment = $room->relationLoaded('equipment') ? $room->equipment : collect();

        $monitoring = [
            'equipment_count' => $equipment->count(),
            'equipment_quantity' => $equipment->sum('equipment_quantity'),
            'equipment_good' => $equipment->where('equipment_condition_status', EquipmentConditions::GOOD)->count(),
            'equipment_damaged' => $equipment->where('equipment_condition_status', EquipmentConditions::DAMAGED)->count(),
            'equipment_defective' => $equipment->where('equipment_condition_status', EquipmentConditions::DEFECTIVE)->count(),
            'equipment_maintenance' => $equipment->where('equipment_condition_status', EquipmentConditions::UNDER_MAINTENANCE)->count(),
            'equipment_disposed' => $equipment->where('equipment_condition_status', EquipmentConditions::DISPOSED)->count(),
            'active_reports' => (int) (data_get($layout, 'report_count') ?? data_get($metric, 'active_count') ?? 0),
            'today_reports' => (int) (data_get($metric, 'today_count') ?? 0),
            'week_reports' => (int) (data_get($metric, 'week_count') ?? 0),
            'month_reports' => (int) (data_get($metric, 'month_count') ?? 0),
            'critical_reports' => (int) (data_get($layout, 'critical_report_count') ?? 0),
            'high_reports' => (int) (data_get($layout, 'high_report_count') ?? 0),
            'medium_reports' => (int) (data_get($layout, 'medium_report_count') ?? 0),
            'low_reports' => (int) (data_get($layout, 'low_report_count') ?? 0),
            'frequent_problems' => collect($frequentProblems)->values()->all(),
            'schedules' => collect($schedules)->values()->all(),
            'room_information' => [
                'last_inspection' => $lastInspection,
                'next_maintenance' => data_get($layout, 'room_inspection_next_date') ?? $nextMaintenance,
                'last_updated' => $room->room_updated_at,
                'status' => $layoutLabel,
                'layout_status' => $layoutStatus,
                'status_color' => $statusColor,
                'under_maintenance_scheduled' => (bool) (data_get($layout, 'under_maintenance_scheduled') ?? false),
                'maintenance_scopes' => data_get($layout, 'maintenance_scopes') ?? [],
                'maintenance_schedules' => data_get($layout, 'maintenance_schedules') ?? [],
                'room_inspection_next_date' => data_get($layout, 'room_inspection_next_date'),
                'critical_report_count' => (int) (data_get($layout, 'critical_report_count') ?? 0),
            ],
        ];

        if ($withHistory) {
            $monitoring['history'] = RoomActivityLog::query()
                ->where('room_id', $room->room_id)
                ->latest('created_at')
                ->take(20)
                ->get();
        } else {
            $monitoring['history'] = [];
        }

        $room->monitoring = $monitoring;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Room>  $rooms
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mapTransferDestinationRooms($rooms)
    {
        return collect($rooms)
            ->filter(fn ($destination) => ! ($destination->room_is_archived ?? false))
            ->map(function ($destination) {
                $departments = collect($destination->departments ?? [])
                    ->map(function ($dept) {
                        if (is_array($dept)) {
                            return [
                                'id' => (int) ($dept['id'] ?? 0),
                                'name' => (string) ($dept['name'] ?? ''),
                                'equipment_count' => (int) ($dept['equipment_count'] ?? 0),
                            ];
                        }

                        return [
                            'id' => 0,
                            'name' => (string) $dept,
                            'equipment_count' => 0,
                        ];
                    })
                    ->filter(fn ($dept) => $dept['id'] > 0 && $dept['name'] !== '')
                    ->values()
                    ->all();

                array_unshift($departments, [
                    'id' => 0,
                    'name' => 'Entire room',
                    'equipment_count' => 0,
                ]);

                return [
                    'id' => (int) $destination->room_id,
                    'name' => (string) $destination->room_name,
                    'type' => (string) ($destination->room_type ?: 'Room'),
                    'departments' => $departments,
                ];
            })
            ->values();
    }

    /**
     * Shared campus default floor for Rooms Layout (falls back to 0 if unset/invalid).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Floor>  $floors
     */
    private function campusDefaultFloorId($floors): int
    {
        if (
            ! Schema::hasTable('campus_setup_settings_table')
            || ! Schema::hasColumn('campus_setup_settings_table', 'default_floor_id')
        ) {
            return 0;
        }

        $savedId = (int) (CampusSetupSetting::query()->value('default_floor_id') ?? 0);
        if ($savedId <= 0) {
            return 0;
        }

        return $floors->contains(fn ($floor) => (int) $floor->floor_id === $savedId)
            ? $savedId
            : 0;
    }

    private function canManageCampusSetup(): bool
    {
        return RoleAccess::isAdmin(Auth::user());
    }
}
