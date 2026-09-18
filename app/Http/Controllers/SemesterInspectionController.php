<?php

namespace App\Http\Controllers;

use App\Support\EquipmentLifecycle;
use App\Support\SemesterInspections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SemesterInspectionController extends Controller
{
    public function index(Request $request)
    {
        if (! SemesterInspections::tablesReady()) {
            return view('maintenance-personnel.semester-inspections.index', [
                'campaigns' => collect(),
                'stats' => ['active' => 0, 'dueSoon' => 0, 'overdue' => 0, 'completed' => 0],
                'tablesMissing' => true,
            ]);
        }

        $status = $request->get('status', 'all');
        $search = trim((string) $request->get('search', ''));

        $query = DB::table('semester_inspection_campaigns_table')
            ->leftJoin(
                'buildings_table',
                'buildings_table.building_id',
                '=',
                'semester_inspection_campaigns_table.campaign_scope_building_id'
            )
            ->leftJoin(
                'floors_table',
                'floors_table.floor_id',
                '=',
                'semester_inspection_campaigns_table.campaign_scope_floor_id'
            )
            ->select(
                'semester_inspection_campaigns_table.*',
                'buildings_table.building_name',
                'floors_table.floor_level'
            )
            ->orderByRaw("CASE campaign_status
                WHEN 'In Progress' THEN 0
                WHEN 'Active' THEN 1
                WHEN 'Draft' THEN 2
                WHEN 'Completed' THEN 3
                ELSE 4 END")
            ->orderBy('campaign_due_date');

        if ($status !== 'all' && in_array($status, SemesterInspections::STATUSES, true)) {
            $query->where('campaign_status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('campaign_title', 'like', "%{$search}%")
                    ->orWhere('campaign_academic_year', 'like', "%{$search}%")
                    ->orWhere('campaign_semester', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(12)->withQueryString();

        $campaigns->getCollection()->transform(function ($campaign) {
            $campaign->progress = SemesterInspections::campaignProgress((int) $campaign->campaign_id);
            $campaign->scope_label = SemesterInspections::scopeLabel($campaign);

            return $campaign;
        });

        $today = today()->toDateString();
        $soon = now()->addDays(7)->toDateString();

        $stats = [
            'active' => (int) DB::table('semester_inspection_campaigns_table')
                ->whereIn('campaign_status', ['Active', 'In Progress'])
                ->count(),
            'dueSoon' => (int) DB::table('semester_inspection_campaigns_table')
                ->whereIn('campaign_status', ['Active', 'In Progress'])
                ->whereDate('campaign_due_date', '>=', $today)
                ->whereDate('campaign_due_date', '<=', $soon)
                ->count(),
            'overdue' => (int) DB::table('semester_inspection_campaigns_table')
                ->whereIn('campaign_status', ['Active', 'In Progress'])
                ->whereDate('campaign_due_date', '<', $today)
                ->count(),
            'completed' => (int) DB::table('semester_inspection_campaigns_table')
                ->where('campaign_status', 'Completed')
                ->count(),
        ];

        return view('maintenance-personnel.semester-inspections.index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'tablesMissing' => false,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create()
    {
        $buildings = Schema::hasTable('buildings_table')
            ? DB::table('buildings_table')->orderBy('building_name')->get()
            : collect();

        $floors = Schema::hasTable('floors_table')
            ? DB::table('floors_table')
                ->leftJoin('buildings_table', 'buildings_table.building_id', '=', 'floors_table.floor_building_id')
                ->select(
                    'floors_table.floor_id',
                    'floors_table.floor_building_id',
                    'floors_table.floor_level',
                    'buildings_table.building_name'
                )
                ->orderBy('buildings_table.building_name')
                ->orderBy('floors_table.floor_level')
                ->get()
            : collect();

        $year = (int) now()->format('Y');
        $defaultAcademicYear = $year.'-'.($year + 1);

        return view('maintenance-personnel.semester-inspections.create', [
            'buildings' => $buildings,
            'floors' => $floors,
            'semesters' => SemesterInspections::SEMESTERS,
            'defaultAcademicYear' => $defaultAcademicYear,
        ]);
    }

    public function store(Request $request)
    {
        if (! SemesterInspections::tablesReady()) {
            return back()->with('error', 'Semester inspection tables are not ready. Run migrations first.');
        }

        $validated = $request->validate([
            'campaign_title' => ['required', 'string', 'max:255'],
            'campaign_academic_year' => ['nullable', 'string', 'max:32'],
            'campaign_semester' => ['required', 'in:'.implode(',', SemesterInspections::SEMESTERS)],
            'campaign_start_date' => ['nullable', 'date'],
            'campaign_due_date' => ['required', 'date'],
            'campaign_scope_type' => ['required', 'in:campus,building,floor'],
            'campaign_scope_building_id' => ['nullable', 'integer'],
            'campaign_scope_floor_id' => ['nullable', 'integer'],
            'campaign_notes' => ['nullable', 'string'],
            'activate' => ['nullable', 'boolean'],
        ]);

        $scopeType = $validated['campaign_scope_type'];
        $buildingId = $validated['campaign_scope_building_id'] ?? null;
        $floorId = $validated['campaign_scope_floor_id'] ?? null;

        if ($scopeType === 'building' && ! $buildingId) {
            return back()->withErrors(['campaign_scope_building_id' => 'Select a building.'])->withInput();
        }

        if ($scopeType === 'floor' && ! $floorId) {
            return back()->withErrors(['campaign_scope_floor_id' => 'Select a floor.'])->withInput();
        }

        if ($scopeType === 'floor' && $floorId && Schema::hasTable('floors_table')) {
            $floor = DB::table('floors_table')->where('floor_id', $floorId)->first();
            if ($floor) {
                $buildingId = (int) $floor->floor_building_id;
            }
        }

        if ($scopeType === 'campus') {
            $buildingId = null;
            $floorId = null;
        }

        $equipmentIds = SemesterInspections::equipmentQueryForScope($scopeType, $buildingId ? (int) $buildingId : null, $floorId ? (int) $floorId : null)
            ->pluck('equipment_table.equipment_id')
            ->unique()
            ->values()
            ->all();

        if (count($equipmentIds) === 0) {
            return back()
                ->withErrors(['campaign_scope_type' => 'No active equipment found for this scope.'])
                ->withInput();
        }

        $activate = $request->boolean('activate', true);
        $status = $activate ? 'Active' : 'Draft';

        $campaignId = null;

        DB::transaction(function () use (
            $validated,
            $scopeType,
            $buildingId,
            $floorId,
            $equipmentIds,
            $status,
            &$campaignId
        ) {
            $campaignId = DB::table('semester_inspection_campaigns_table')->insertGetId([
                'campaign_title' => $validated['campaign_title'],
                'campaign_academic_year' => $validated['campaign_academic_year'] ?? null,
                'campaign_semester' => $validated['campaign_semester'],
                'campaign_start_date' => $validated['campaign_start_date'] ?? null,
                'campaign_due_date' => $validated['campaign_due_date'],
                'campaign_scope_type' => $scopeType,
                'campaign_scope_building_id' => $buildingId,
                'campaign_scope_floor_id' => $floorId,
                'campaign_status' => $status,
                'campaign_notes' => $validated['campaign_notes'] ?? null,
                'campaign_created_by' => Auth::id(),
                'campaign_created_at' => now(),
                'campaign_updated_at' => now(),
            ]);

            $rows = [];
            $now = now();
            foreach ($equipmentIds as $equipmentId) {
                $rows[] = [
                    'item_campaign_id' => $campaignId,
                    'item_equipment_id' => $equipmentId,
                    'item_status' => 'Pending',
                    'item_created_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('semester_inspection_items_table')->insert($chunk);
            }

            DB::table('audit_logs_table')->insert([
                'audit_log_user_id' => Auth::id(),
                'audit_log_action' => 'Created semester inspection',
                'audit_log_module' => 'Semester Inspection',
                'audit_log_table_name' => 'semester_inspection_campaigns_table',
                'audit_log_reference_id' => $campaignId,
                'audit_log_description' => 'Created "'.$validated['campaign_title'].'" with '.count($equipmentIds).' equipment item(s).',
                'audit_log_ip_address' => request()->ip(),
                'audit_log_created_at' => now(),
            ]);
        });

        return redirect()
            ->to('/maintenance/semester-inspections/'.$campaignId)
            ->with('success', 'Semester inspection campaign created with '.count($equipmentIds).' equipment item(s).');
    }

    public function show(Request $request, int $id)
    {
        if (! SemesterInspections::tablesReady()) {
            abort(404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            abort(404);
        }

        $progress = SemesterInspections::campaignProgress($id);
        $filter = $request->get('filter', 'all');
        $search = trim((string) $request->get('search', ''));
        $roomId = $request->get('room_id');
        $assetTag = trim((string) $request->get('asset_tag', ''));

        $itemsQuery = DB::table('semester_inspection_items_table')
            ->join(
                'equipment_table',
                'equipment_table.equipment_id',
                '=',
                'semester_inspection_items_table.item_equipment_id'
            )
            ->leftJoin(
                'equipment_categories_table',
                'equipment_categories_table.equipment_category_id',
                '=',
                'equipment_table.equipment_category_id'
            )
            ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
            ->leftJoin('floors_table', 'floors_table.floor_id', '=', 'rooms_table.room_floor_id')
            ->leftJoin('buildings_table', 'buildings_table.building_id', '=', 'floors_table.floor_building_id')
            ->where('item_campaign_id', $id)
            ->select(
                'semester_inspection_items_table.*',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_brand_name',
                'equipment_table.equipment_model',
                'equipment_table.equipment_serial_number',
                'equipment_table.equipment_tracking_mode',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_id',
                'rooms_table.room_name',
                'floors_table.floor_level',
                'buildings_table.building_name'
            )
            ->orderByRaw("CASE item_status WHEN 'Pending' THEN 0 ELSE 1 END")
            ->orderBy('buildings_table.building_name')
            ->orderBy('floors_table.floor_level')
            ->orderBy('rooms_table.room_name')
            ->orderBy('equipment_table.equipment_name');

        if ($filter === 'pending') {
            $itemsQuery->where('item_status', 'Pending');
        } elseif ($filter === 'inspected') {
            $itemsQuery->where('item_status', 'Inspected');
        } elseif ($filter === 'defects') {
            $itemsQuery->whereIn('item_condition', ['Malfunctioning', 'Defective', 'Destroyed']);
        } elseif (in_array($filter, SemesterInspections::CONDITIONS, true)) {
            $itemsQuery->where('item_condition', $filter);
        }

        if ($roomId) {
            $itemsQuery->where('rooms_table.room_id', (int) $roomId);
        }

        if ($assetTag === '__none') {
            $itemsQuery->where(function ($q) {
                $q->whereNull('equipment_table.equipment_asset_tag')
                    ->orWhere('equipment_table.equipment_asset_tag', '');
            });
        } elseif ($assetTag !== '') {
            $itemsQuery->where('equipment_table.equipment_asset_tag', $assetTag);
        }

        if ($search !== '') {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('equipment_table.equipment_name', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_asset_tag', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_qr_code', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_brand_name', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_model', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_serial_number', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_tracking_mode', 'like', "%{$search}%")
                    ->orWhere('equipment_categories_table.equipment_category_name', 'like', "%{$search}%")
                    ->orWhere('rooms_table.room_name', 'like', "%{$search}%");
            });
        }

        $items = $itemsQuery->paginate(40)->withQueryString();

        $rooms = DB::table('semester_inspection_items_table')
            ->join('equipment_table', 'equipment_table.equipment_id', '=', 'semester_inspection_items_table.item_equipment_id')
            ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
            ->where('item_campaign_id', $id)
            ->whereNotNull('rooms_table.room_id')
            ->select('rooms_table.room_id', 'rooms_table.room_name', DB::raw('COUNT(*) as item_count'))
            ->groupBy('rooms_table.room_id', 'rooms_table.room_name')
            ->orderBy('rooms_table.room_name')
            ->get();

        $assetTags = DB::table('semester_inspection_items_table')
            ->join('equipment_table', 'equipment_table.equipment_id', '=', 'semester_inspection_items_table.item_equipment_id')
            ->where('item_campaign_id', $id)
            ->whereNotNull('equipment_table.equipment_asset_tag')
            ->where('equipment_table.equipment_asset_tag', '!=', '')
            ->distinct()
            ->orderBy('equipment_table.equipment_asset_tag')
            ->pluck('equipment_table.equipment_asset_tag');

        return view('maintenance-personnel.semester-inspections.show', [
            'campaign' => $campaign,
            'progress' => $progress,
            'items' => $items,
            'rooms' => $rooms,
            'assetTags' => $assetTags,
            'filter' => $filter,
            'search' => $search,
            'roomId' => $roomId,
            'assetTag' => $assetTag,
            'conditions' => SemesterInspections::CONDITIONS,
            'scopeLabel' => SemesterInspections::scopeLabel($campaign),
        ]);
    }

    public function inspect(Request $request, int $id, int $itemId)
    {
        if (! SemesterInspections::tablesReady()) {
            abort(404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            abort(404);
        }

        if (in_array($campaign->campaign_status, ['Completed', 'Cancelled'], true)) {
            return back()->with('error', 'This campaign is closed and cannot accept new inspections.');
        }

        $validated = $request->validate([
            'item_condition' => ['required', 'in:'.implode(',', SemesterInspections::CONDITIONS)],
            'item_findings' => ['required', 'string', 'max:5000'],
            'item_action_taken' => ['nullable', 'string', 'max:5000'],
            'apply_status' => ['nullable', 'boolean'],
            'proof_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $item = DB::table('semester_inspection_items_table')
            ->where('item_id', $itemId)
            ->where('item_campaign_id', $id)
            ->first();

        if (! $item) {
            abort(404);
        }

        $proofPath = $item->item_proof_image;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('semester-inspections', 'public');
        }

        $mapping = SemesterInspections::statusMapping($validated['item_condition']);
        $applyStatus = $request->boolean('apply_status', true);
        $appliedInventory = null;
        $appliedCondition = null;

        DB::transaction(function () use (
            $validated,
            $item,
            $itemId,
            $id,
            $campaign,
            $proofPath,
            $mapping,
            $applyStatus,
            &$appliedInventory,
            &$appliedCondition
        ) {
            if ($applyStatus) {
                $equipmentUpdate = [];
                if (! empty($mapping['condition'])) {
                    $equipmentUpdate['equipment_condition_status'] = $mapping['condition'];
                    $appliedCondition = $mapping['condition'];
                }
                if (! empty($mapping['inventory'])) {
                    $equipmentUpdate['equipment_inventory_status'] = $mapping['inventory'];
                    $appliedInventory = $mapping['inventory'];
                }
                if ($equipmentUpdate !== []) {
                    DB::table('equipment_table')
                        ->where('equipment_id', $item->item_equipment_id)
                        ->where(function ($q) {
                            $q->whereNull('equipment_inventory_status')
                                ->orWhere('equipment_inventory_status', '!=', 'Disposed');
                        })
                        ->update($equipmentUpdate);
                }
            }

            DB::table('semester_inspection_items_table')
                ->where('item_id', $itemId)
                ->update([
                    'item_status' => 'Inspected',
                    'item_condition' => $validated['item_condition'],
                    'item_findings' => $validated['item_findings'],
                    'item_action_taken' => $validated['item_action_taken'] ?? null,
                    'item_proof_image' => $proofPath,
                    'item_inventory_status_applied' => $appliedInventory,
                    'item_condition_status_applied' => $appliedCondition,
                    'item_inspected_by' => Auth::id(),
                    'item_inspected_at' => now(),
                    'item_updated_at' => now(),
                ]);

            if (in_array($campaign->campaign_status, ['Draft', 'Active'], true)) {
                DB::table('semester_inspection_campaigns_table')
                    ->where('campaign_id', $id)
                    ->update([
                        'campaign_status' => 'In Progress',
                        'campaign_updated_at' => now(),
                    ]);
            }

            $equipment = DB::table('equipment_table')
                ->where('equipment_id', $item->item_equipment_id)
                ->first();

            DB::table('audit_logs_table')->insert([
                'audit_log_user_id' => Auth::id(),
                'audit_log_action' => 'Inspected equipment',
                'audit_log_module' => 'Semester Inspection',
                'audit_log_table_name' => 'semester_inspection_items_table',
                'audit_log_reference_id' => $itemId,
                'audit_log_description' => 'Marked '.($equipment->equipment_name ?? 'equipment')
                    .' as '.$validated['item_condition']
                    .' on campaign "'.$campaign->campaign_title.'".',
                'audit_log_ip_address' => request()->ip(),
                'audit_log_created_at' => now(),
            ]);

            if (Schema::hasTable('equipment_maintenance_history_table')) {
                DB::table('equipment_maintenance_history_table')->insert([
                    'equipment_maintenance_equipment_id' => $item->item_equipment_id,
                    'equipment_maintenance_personnel_id' => Auth::id(),
                    'equipment_maintenance_findings' => '[Semester inspection] '.$validated['item_findings'],
                    'equipment_maintenance_repair_action' => $validated['item_action_taken']
                        ?? ('Condition recorded: '.$validated['item_condition']),
                    'equipment_maintenance_status' => $validated['item_condition'] === 'OK' ? 'Resolved' : 'Escalated',
                    'equipment_maintenance_completed_at' => now(),
                    'equipment_maintenance_created_at' => now(),
                    'equipment_maintenance_proof_image' => $proofPath,
                ]);
            }
        });

        $progress = SemesterInspections::campaignProgress($id);
        $message = 'Inspection saved.';
        if ($progress['pending'] === 0 && $progress['total'] > 0) {
            $message .= ' All equipment in this campaign have been inspected. You can mark the campaign complete.';
        }

        return back()->with('success', $message);
    }

    public function complete(Request $request, int $id)
    {
        if (! SemesterInspections::tablesReady()) {
            abort(404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            abort(404);
        }

        if ($campaign->campaign_status === 'Completed') {
            return back()->with('error', 'Campaign is already completed.');
        }

        if ($campaign->campaign_status === 'Cancelled') {
            return back()->with('error', 'Cancelled campaigns cannot be completed.');
        }

        $force = $request->boolean('force');
        $progress = SemesterInspections::campaignProgress($id);

        if (! $force && $progress['pending'] > 0) {
            return back()->with(
                'error',
                $progress['pending'].' equipment still pending. Inspect remaining items or force-complete.'
            );
        }

        DB::table('semester_inspection_campaigns_table')
            ->where('campaign_id', $id)
            ->update([
                'campaign_status' => 'Completed',
                'campaign_completed_at' => now(),
                'campaign_updated_at' => now(),
            ]);

        DB::table('audit_logs_table')->insert([
            'audit_log_user_id' => Auth::id(),
            'audit_log_action' => 'Completed semester inspection',
            'audit_log_module' => 'Semester Inspection',
            'audit_log_table_name' => 'semester_inspection_campaigns_table',
            'audit_log_reference_id' => $id,
            'audit_log_description' => 'Completed campaign "'.$campaign->campaign_title.'"'
                .($force && $progress['pending'] > 0 ? ' (forced with '.$progress['pending'].' pending).' : '.'),
            'audit_log_ip_address' => $request->ip(),
            'audit_log_created_at' => now(),
        ]);

        return redirect()
            ->to('/maintenance/semester-inspections/'.$id)
            ->with('success', 'Semester inspection campaign marked complete.');
    }

    public function cancel(int $id)
    {
        if (! SemesterInspections::tablesReady()) {
            abort(404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            abort(404);
        }

        if (in_array($campaign->campaign_status, ['Completed', 'Cancelled'], true)) {
            return back()->with('error', 'This campaign is already closed.');
        }

        DB::table('semester_inspection_campaigns_table')
            ->where('campaign_id', $id)
            ->update([
                'campaign_status' => 'Cancelled',
                'campaign_updated_at' => now(),
            ]);

        return back()->with('success', 'Campaign cancelled.');
    }

    public function activate(int $id)
    {
        if (! SemesterInspections::tablesReady()) {
            abort(404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            abort(404);
        }

        if ($campaign->campaign_status !== 'Draft') {
            return back()->with('error', 'Only draft campaigns can be activated.');
        }

        DB::table('semester_inspection_campaigns_table')
            ->where('campaign_id', $id)
            ->update([
                'campaign_status' => 'Active',
                'campaign_updated_at' => now(),
            ]);

        return back()->with('success', 'Campaign activated. Reminders will fire near the due date.');
    }

    public function markForReplacement(Request $request, int $equipmentId)
    {
        $equipment = DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->first();

        if (! $equipment) {
            abort(404);
        }

        if (strcasecmp((string) $equipment->equipment_inventory_status, 'Disposed') === 0) {
            return back()->with('error', 'Disposed equipment cannot be marked for replacement.');
        }

        DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->update([
                'equipment_inventory_status' => 'For Replacement',
                'equipment_condition_status' => $equipment->equipment_condition_status === 'Good'
                    ? 'Damaged'
                    : ($equipment->equipment_condition_status ?: 'Damaged'),
            ]);

        DB::table('audit_logs_table')->insert([
            'audit_log_user_id' => Auth::id(),
            'audit_log_action' => 'Marked for replacement',
            'audit_log_module' => 'Equipment Lifecycle',
            'audit_log_table_name' => 'equipment_table',
            'audit_log_reference_id' => $equipmentId,
            'audit_log_description' => 'Suggested replacement for aging asset "'.($equipment->equipment_name ?? 'equipment').'".',
            'audit_log_ip_address' => $request->ip(),
            'audit_log_created_at' => now(),
        ]);

        return back()->with('success', 'Equipment marked for replacement.');
    }

    public function replacementSuggestions(Request $request)
    {
        $alerts = EquipmentLifecycle::agingAlerts(100);

        return view('maintenance-personnel.semester-inspections.replacements', [
            'alerts' => $alerts,
            'defaultYears' => EquipmentLifecycle::DEFAULT_USEFUL_LIFE_YEARS,
        ]);
    }

    private function findCampaign(int $id): ?object
    {
        return DB::table('semester_inspection_campaigns_table')
            ->leftJoin(
                'buildings_table',
                'buildings_table.building_id',
                '=',
                'semester_inspection_campaigns_table.campaign_scope_building_id'
            )
            ->leftJoin(
                'floors_table',
                'floors_table.floor_id',
                '=',
                'semester_inspection_campaigns_table.campaign_scope_floor_id'
            )
            ->where('campaign_id', $id)
            ->select(
                'semester_inspection_campaigns_table.*',
                'buildings_table.building_name',
                'floors_table.floor_level'
            )
            ->first();
    }
}
