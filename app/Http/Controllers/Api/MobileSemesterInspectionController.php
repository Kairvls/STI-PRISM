<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SemesterInspections;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileSemesterInspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! SemesterInspections::tablesReady()) {
            return response()->json([
                'success' => true,
                'campaigns' => [],
                'message' => 'Semester inspection tables are not ready.',
            ]);
        }

        $includeClosed = $request->boolean('include_closed');

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

        if ($includeClosed) {
            $query->whereIn('campaign_status', ['Active', 'In Progress', 'Completed']);
        } else {
            $query->whereIn('campaign_status', ['Active', 'In Progress']);
        }

        $campaigns = $query->get()->map(function ($campaign) {
            return $this->serializeCampaign($campaign);
        })->values();

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! SemesterInspections::tablesReady()) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found.'], 404);
        }

        $filter = $request->query('filter', 'all'); // all|pending|inspected
        $search = trim((string) $request->query('search', ''));
        $room = trim((string) $request->query('room', ''));
        $limit = min((int) $request->query('limit', 200), 500);

        $itemsQuery = DB::table('semester_inspection_items_table')
            ->join(
                'equipment_table',
                'equipment_table.equipment_id',
                '=',
                'semester_inspection_items_table.item_equipment_id'
            )
            ->leftJoin(
                'rooms_table',
                'rooms_table.room_id',
                '=',
                'equipment_table.equipment_room_id'
            )
            ->leftJoin(
                'equipment_categories_table',
                'equipment_categories_table.equipment_category_id',
                '=',
                'equipment_table.equipment_category_id'
            )
            ->where('item_campaign_id', $id)
            ->select(
                'semester_inspection_items_table.*',
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'rooms_table.room_name',
                'equipment_categories_table.equipment_category_name'
            )
            ->orderByRaw("CASE item_status WHEN 'Pending' THEN 0 ELSE 1 END")
            ->orderBy('rooms_table.room_name')
            ->orderBy('equipment_table.equipment_name');

        if ($filter === 'pending') {
            $itemsQuery->where('item_status', 'Pending');
        } elseif ($filter === 'inspected') {
            $itemsQuery->where('item_status', 'Inspected');
        }

        if ($search !== '') {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('equipment_table.equipment_name', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_asset_tag', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_qr_code', 'like', "%{$search}%")
                    ->orWhere('rooms_table.room_name', 'like', "%{$search}%");
            });
        }

        if ($room !== '') {
            $itemsQuery->where('rooms_table.room_name', $room);
        }

        $items = $itemsQuery->limit($limit)->get()->map(function ($row) {
            return $this->serializeItem($row);
        })->values();

        $rooms = DB::table('semester_inspection_items_table')
            ->join(
                'equipment_table',
                'equipment_table.equipment_id',
                '=',
                'semester_inspection_items_table.item_equipment_id'
            )
            ->leftJoin(
                'rooms_table',
                'rooms_table.room_id',
                '=',
                'equipment_table.equipment_room_id'
            )
            ->where('item_campaign_id', $id)
            ->whereNotNull('rooms_table.room_name')
            ->where('rooms_table.room_name', '!=', '')
            ->distinct()
            ->orderBy('rooms_table.room_name')
            ->pluck('rooms_table.room_name')
            ->values();

        return response()->json([
            'success' => true,
            'campaign' => $this->serializeCampaign($campaign),
            'items' => $items,
            'rooms' => $rooms,
            'conditions' => SemesterInspections::CONDITIONS,
        ]);
    }

    public function resolveByQr(Request $request, int $id): JsonResponse
    {
        if (! SemesterInspections::tablesReady()) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found.'], 404);
        }

        $qr = trim((string) $request->query('qr', ''));
        if ($qr === '') {
            return response()->json(['message' => 'QR code is required.'], 422);
        }

        $item = DB::table('semester_inspection_items_table')
            ->join(
                'equipment_table',
                'equipment_table.equipment_id',
                '=',
                'semester_inspection_items_table.item_equipment_id'
            )
            ->leftJoin(
                'rooms_table',
                'rooms_table.room_id',
                '=',
                'equipment_table.equipment_room_id'
            )
            ->leftJoin(
                'equipment_categories_table',
                'equipment_categories_table.equipment_category_id',
                '=',
                'equipment_table.equipment_category_id'
            )
            ->where('item_campaign_id', $id)
            ->where('equipment_table.equipment_qr_code', $qr)
            ->select(
                'semester_inspection_items_table.*',
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'rooms_table.room_name',
                'equipment_categories_table.equipment_category_name'
            )
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'This equipment is not part of this semester inspection campaign.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'campaign' => $this->serializeCampaign($campaign),
            'item' => $this->serializeItem($item),
            'conditions' => SemesterInspections::CONDITIONS,
        ]);
    }

    public function inspect(Request $request, int $id, int $itemId): JsonResponse
    {
        if (! SemesterInspections::tablesReady()) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $campaign = $this->findCampaign($id);
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found.'], 404);
        }

        if (in_array($campaign->campaign_status, ['Completed', 'Cancelled'], true)) {
            return response()->json([
                'message' => 'This campaign is closed and cannot accept new inspections.',
            ], 422);
        }

        if (in_array($campaign->campaign_status, ['Draft'], true)) {
            return response()->json([
                'message' => 'This campaign is still a draft. Activate it on web first.',
            ], 422);
        }

        $validated = $request->validate([
            'item_condition' => ['required', 'in:'.implode(',', SemesterInspections::CONDITIONS)],
            'item_findings' => ['required', 'string', 'max:5000'],
            'item_action_taken' => ['nullable', 'string', 'max:5000'],
            'apply_status' => ['nullable'],
            'proof_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $item = DB::table('semester_inspection_items_table')
            ->where('item_id', $itemId)
            ->where('item_campaign_id', $id)
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Inspection item not found.'], 404);
        }

        $proofPath = $item->item_proof_image;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('semester-inspections', 'public');
        }

        $mapping = SemesterInspections::statusMapping($validated['item_condition']);
        $applyStatus = filter_var(
            $request->input('apply_status', true),
            FILTER_VALIDATE_BOOLEAN
        );
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
                    .' on campaign "'.$campaign->campaign_title.'" (mobile).',
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
            $message .= ' All equipment in this campaign have been inspected.';
        }

        $updated = DB::table('semester_inspection_items_table')
            ->join(
                'equipment_table',
                'equipment_table.equipment_id',
                '=',
                'semester_inspection_items_table.item_equipment_id'
            )
            ->leftJoin(
                'rooms_table',
                'rooms_table.room_id',
                '=',
                'equipment_table.equipment_room_id'
            )
            ->leftJoin(
                'equipment_categories_table',
                'equipment_categories_table.equipment_category_id',
                '=',
                'equipment_table.equipment_category_id'
            )
            ->where('item_id', $itemId)
            ->select(
                'semester_inspection_items_table.*',
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'rooms_table.room_name',
                'equipment_categories_table.equipment_category_name'
            )
            ->first();

        $freshCampaign = $this->findCampaign($id);

        return response()->json([
            'success' => true,
            'message' => $message,
            'item' => $updated ? $this->serializeItem($updated) : null,
            'campaign' => $freshCampaign ? $this->serializeCampaign($freshCampaign) : null,
            'progress' => $progress,
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

    private function serializeCampaign(object $campaign): array
    {
        $progress = SemesterInspections::campaignProgress((int) $campaign->campaign_id);

        return [
            'id' => (int) $campaign->campaign_id,
            'title' => (string) $campaign->campaign_title,
            'academic_year' => $campaign->campaign_academic_year,
            'semester' => (string) ($campaign->campaign_semester ?? ''),
            'status' => (string) $campaign->campaign_status,
            'due_date' => $campaign->campaign_due_date,
            'start_date' => $campaign->campaign_start_date,
            'scope_type' => (string) ($campaign->campaign_scope_type ?? 'campus'),
            'scope_label' => SemesterInspections::scopeLabel($campaign),
            'notes' => $campaign->campaign_notes,
            'progress' => $progress,
        ];
    }

    private function serializeItem(object $row): array
    {
        return [
            'id' => (int) $row->item_id,
            'campaign_id' => (int) $row->item_campaign_id,
            'equipment_id' => (int) $row->item_equipment_id,
            'status' => (string) ($row->item_status ?? 'Pending'),
            'condition' => $row->item_condition,
            'findings' => $row->item_findings,
            'action_taken' => $row->item_action_taken,
            'inspected_at' => $row->item_inspected_at,
            'equipment_name' => (string) ($row->equipment_name ?? 'Equipment'),
            'asset_tag' => $row->equipment_asset_tag,
            'qr_code' => $row->equipment_qr_code,
            'room' => (string) ($row->room_name ?? ''),
            'category' => (string) ($row->equipment_category_name ?? ''),
            'inventory_status' => $row->equipment_inventory_status,
            'condition_status' => $row->equipment_condition_status,
        ];
    }
}
