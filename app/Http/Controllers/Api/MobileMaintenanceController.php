<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class MobileMaintenanceController extends Controller
{
    // =====================================================
    // MICROSOFT LOGIN
    // =====================================================

    public function login(Request $request)
    {

    }

    // =====================================================
    // LIST EQUIPMENT
    // =====================================================

    public function listEquipment(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $limit = min((int) $request->query('limit', 100), 200);

        $query = DB::table('equipment_table')
            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->select(
                'equipment_table.equipment_id as id',
                'equipment_table.equipment_qr_code as qr_code',
                'equipment_table.equipment_asset_tag as asset_tag',
                'equipment_table.equipment_name as name',
                'equipment_table.equipment_brand_name as brand',
                'equipment_table.equipment_model as model',
                'equipment_table.equipment_serial_number as serial_number',
                'rooms_table.room_name as room',
                'equipment_categories_table.equipment_category_name as category',
                'equipment_table.equipment_condition_status as condition',
                'equipment_table.equipment_inventory_status as inventory_status',
                'equipment_table.equipment_warranty_expiration as warranty_expiration'
            )
            // Mobile only surfaces QR-tagged equipment: a generated QR code is
            // the enrollment gate for maintenance monitoring/scheduling.
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            ->orderBy('equipment_table.equipment_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('equipment_table.equipment_name', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_asset_tag', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_qr_code', 'like', "%{$search}%")
                    ->orWhere('rooms_table.room_name', 'like', "%{$search}%")
                    ->orWhere('equipment_table.equipment_brand_name', 'like', "%{$search}%");
            });
        }

        $equipment = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'equipment' => $equipment,
        ]);
    }

    // =====================================================
    // LIST ALL MAINTENANCE HISTORY
    // =====================================================

    public function listHistory(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 100);

        $history = DB::table('equipment_maintenance_history_table')
            ->leftJoin(
                'users_table',
                'equipment_maintenance_history_table.equipment_maintenance_personnel_id',
                '=',
                'users_table.user_id'
            )
            ->leftJoin(
                'equipment_table',
                'equipment_maintenance_history_table.equipment_maintenance_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->select(
                'equipment_maintenance_history_table.*',
                'users_table.user_full_name',
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_qr_code',
                'rooms_table.room_name'
            )
            // Mobile only surfaces QR-tagged equipment.
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            ->orderBy(
                'equipment_maintenance_created_at',
                'desc'
            )
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    // =====================================================
    // LIST ALL MAINTENANCE SCHEDULES
    // =====================================================

    public function listSchedules(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 100);

        // Keep overdue flag fresh for Active schedules past next_date.
        DB::table('maintenance_schedules_table')
            ->where('maintenance_schedule_status', 'Active')
            ->whereNotNull('maintenance_schedule_next_date')
            ->whereDate('maintenance_schedule_next_date', '<', today())
            ->update([
                'maintenance_schedule_status' => 'Overdue',
            ]);

        $schedules = DB::table('maintenance_schedules_table')
            ->leftJoin(
                'equipment_table',
                'maintenance_schedules_table.maintenance_schedule_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->select(
                'maintenance_schedules_table.*',
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_qr_code',
                'rooms_table.room_name'
            )
            // Mobile only surfaces schedules whose equipment has a QR code.
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            ->orderByRaw("
                CASE maintenance_schedule_status
                    WHEN 'Overdue' THEN 0
                    WHEN 'Active' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy(
                'maintenance_schedule_next_date',
                'asc'
            )
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'schedules' => $schedules,
        ]);
    }

    // =====================================================
    // RECENT ACTIVITY (HOME DASHBOARD)
    // =====================================================

    public function recent(): JsonResponse
    {
        // Active schedules within this many days are treated as "Due soon".
        $dueSoonDays = 7;

        // Keep overdue flag fresh for Active schedules past next_date.
        DB::table('maintenance_schedules_table')
            ->where('maintenance_schedule_status', 'Active')
            ->whereNotNull('maintenance_schedule_next_date')
            ->whereDate('maintenance_schedule_next_date', '<', today())
            ->update([
                'maintenance_schedule_status' => 'Overdue',
            ]);

        // Mobile only counts QR-tagged equipment (the monitoring gate).
        $equipmentCount = DB::table('equipment_table')
            ->whereNotNull('equipment_qr_code')
            ->where('equipment_qr_code', '!=', '')
            ->count();

        $underMaintenance = DB::table('equipment_table')
            ->where('equipment_inventory_status', 'Under Maintenance')
            ->whereNotNull('equipment_qr_code')
            ->where('equipment_qr_code', '!=', '')
            ->count();

        // Only count schedules whose equipment has a QR code (mobile scope).
        $overdueSchedules = DB::table('maintenance_schedules_table')
            ->join(
                'equipment_table',
                'maintenance_schedules_table.maintenance_schedule_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->where('maintenance_schedule_status', 'Overdue')
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            ->count();

        $dueSoonSchedulesCount = DB::table('maintenance_schedules_table')
            ->join(
                'equipment_table',
                'maintenance_schedules_table.maintenance_schedule_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->where('maintenance_schedule_status', 'Active')
            ->whereNotNull('maintenance_schedule_next_date')
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            ->whereDate('maintenance_schedule_next_date', '>=', today())
            ->whereDate(
                'maintenance_schedule_next_date',
                '<=',
                today()->addDays($dueSoonDays)
            )
            ->count();

        $scheduleSelect = function ($query) {
            return $query
                ->leftJoin(
                    'equipment_table',
                    'maintenance_schedules_table.maintenance_schedule_equipment_id',
                    '=',
                    'equipment_table.equipment_id'
                )
                ->leftJoin(
                    'rooms_table',
                    'equipment_table.equipment_room_id',
                    '=',
                    'rooms_table.room_id'
                )
                ->select(
                    'maintenance_schedules_table.*',
                    'equipment_table.equipment_id',
                    'equipment_table.equipment_name',
                    'equipment_table.equipment_qr_code',
                    'rooms_table.room_name'
                )
                // Mobile scope: schedules for QR-tagged equipment only.
                ->whereNotNull('equipment_table.equipment_qr_code')
                ->where('equipment_table.equipment_qr_code', '!=', '');
        };

        $recentHistory = DB::table('equipment_maintenance_history_table')
            ->leftJoin(
                'users_table',
                'equipment_maintenance_history_table.equipment_maintenance_personnel_id',
                '=',
                'users_table.user_id'
            )
            ->leftJoin(
                'equipment_table',
                'equipment_maintenance_history_table.equipment_maintenance_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->select(
                'equipment_maintenance_history_table.*',
                'users_table.user_full_name',
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_qr_code',
                'rooms_table.room_name'
            )
            // Mobile scope: recent fixes for QR-tagged equipment only.
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            ->orderBy('equipment_maintenance_created_at', 'desc')
            ->limit(3)
            ->get();

        $dueSoonSchedules = $scheduleSelect(
            DB::table('maintenance_schedules_table')
        )
            ->where('maintenance_schedule_status', 'Active')
            ->whereNotNull('maintenance_schedule_next_date')
            ->whereDate('maintenance_schedule_next_date', '>=', today())
            ->whereDate(
                'maintenance_schedule_next_date',
                '<=',
                today()->addDays($dueSoonDays)
            )
            ->orderBy('maintenance_schedule_next_date', 'asc')
            ->limit(3)
            ->get();

        $upcomingSchedules = $scheduleSelect(
            DB::table('maintenance_schedules_table')
        )
            ->whereIn('maintenance_schedule_status', ['Active', 'Overdue'])
            ->orderByRaw("
                CASE maintenance_schedule_status
                    WHEN 'Overdue' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('maintenance_schedule_next_date', 'asc')
            ->limit(5)
            ->get();

        $attentionEquipment = DB::table('equipment_table')
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )
            ->select(
                'equipment_table.equipment_id as id',
                'equipment_table.equipment_qr_code as qr_code',
                'equipment_table.equipment_asset_tag as asset_tag',
                'equipment_table.equipment_name as name',
                'equipment_table.equipment_brand_name as brand',
                'equipment_table.equipment_model as model',
                'equipment_table.equipment_serial_number as serial_number',
                'rooms_table.room_name as room',
                'equipment_categories_table.equipment_category_name as category',
                'equipment_table.equipment_condition_status as condition',
                'equipment_table.equipment_inventory_status as inventory_status',
                'equipment_table.equipment_warranty_expiration as warranty_expiration'
            )
            ->whereIn('equipment_inventory_status', [
                'Under Maintenance',
                'For Replacement',
            ])
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            // Most-recent first (newest-added as recency proxy).
            ->orderByDesc('equipment_table.equipment_created_at')
            ->orderByDesc('equipment_table.equipment_id')
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'equipment_count' => $equipmentCount,
                'under_maintenance' => $underMaintenance,
                'overdue_schedules' => $overdueSchedules,
                'due_soon_schedules' => $dueSoonSchedulesCount,
                'due_soon_days' => $dueSoonDays,
            ],
            'recent_history' => $recentHistory,
            'due_soon_schedules' => $dueSoonSchedules,
            'upcoming_schedules' => $upcomingSchedules,
            'attention_equipment' => $attentionEquipment,
        ]);
    }

    // =====================================================
    // GET EQUIPMENT BY QR
    // =====================================================

    public function equipment($qr): JsonResponse
    {
        // =====================================================
        // FIND EQUIPMENT USING QR CODE
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )

            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->where(
                'equipment_table.equipment_qr_code',
                $qr
            )

            ->select(
                'equipment_table.*',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name'
            )

            ->first();

        // =====================================================
        // EQUIPMENT NOT FOUND
        // =====================================================

        if (!$equipment) {

            return response()->json([
                'success' => false,
                'message' => 'Equipment not found.'
            ], 404);

        }

        // =====================================================
        // GET MAINTENANCE SCHEDULE
        // =====================================================

        $schedule = DB::table('maintenance_schedules_table')

            ->where(
                'maintenance_schedule_equipment_id',
                $equipment->equipment_id
            )

            ->first();

        // =====================================================
        // GET RECENT MAINTENANCE HISTORY
        // =====================================================

        $history = DB::table('equipment_maintenance_history_table')

            ->where(
                'equipment_maintenance_equipment_id',
                $equipment->equipment_id
            )

            ->orderBy(
                'equipment_maintenance_created_at',
                'desc'
            )

            ->limit(5)

            ->get();

        // =====================================================
        // RETURN COMPLETE EQUIPMENT PROFILE
        // =====================================================

        return response()->json([

            'success' => true,

            'equipment' => [

                'id' => $equipment->equipment_id,

                'qr_code' => $equipment->equipment_qr_code,

                'asset_tag' => $equipment->equipment_asset_tag,

                'name' => $equipment->equipment_name,

                'brand' => $equipment->equipment_brand_name,

                'model' => $equipment->equipment_model,

                'serial_number' => $equipment->equipment_serial_number,

                'room' => $equipment->room_name,

                'category' => $equipment->equipment_category_name,

                'condition' => $equipment->equipment_condition_status,

                'inventory_status' => $equipment->equipment_inventory_status,

                'purchase_date' => $equipment->equipment_purchase_date,

                'warranty_expiration' => $equipment->equipment_warranty_expiration,

            ],

            'schedule' => $schedule,

            'recent_history' => $history,

        ]);
    }

    // =====================================================
    // UPDATE EQUIPMENT
    // =====================================================

    public function updateEquipment(Request $request, $id): JsonResponse
    {
        // =====================================================
        // VALIDATE REQUEST
        // =====================================================

        $validated = $request->validate([

            'asset_tag' => [
                'nullable',
                'string',
                'max:255'
            ],

            'brand_name' => [
                'nullable',
                'string',
                'max:255'
            ],

            'model' => [
                'nullable',
                'string',
                'max:255'
            ],

            'serial_number' => [
                'nullable',
                'string',
                'max:255'
            ],

            'condition_status' => [
                'required',
                'in:Good,Damaged,Under Maintenance,Disposed'
            ],

            'inventory_status' => [
                'required',
                'in:Active,Under Maintenance,Borrowed,For Replacement,Disposed'
            ],

            'current_location' => [
                'nullable',
                'string',
                'max:255'
            ],

        ]);

        // =====================================================
        // CHECK IF EQUIPMENT EXISTS
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->first();

        if (!$equipment) {

            return response()->json([

                'success' => false,

                'message' => 'Equipment not found.'

            ], 404);

        }

        // =====================================================
        // UPDATE EQUIPMENT
        // =====================================================

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->update([

                'equipment_asset_tag'
                    => $validated['asset_tag'],

                'equipment_brand_name'
                    => $validated['brand_name'],

                'equipment_model'
                    => $validated['model'],

                'equipment_serial_number'
                    => $validated['serial_number'],

                'equipment_condition_status'
                    => $validated['condition_status'],

                'equipment_inventory_status'
                    => $validated['inventory_status'],

                'equipment_current_location'
                    => $validated['current_location'],

            ]);

        // =====================================================
        // RETURN UPDATED EQUIPMENT
        // =====================================================

        $updatedEquipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->first();

        return response()->json([

            'success' => true,

            'message' => 'Equipment updated successfully.',

            'equipment' => $updatedEquipment,

        ]);
    }

    // =====================================================
    // GET MAINTENANCE HISTORY
    // =====================================================

    public function history($equipmentId): JsonResponse
    {
        $history = DB::table('equipment_maintenance_history_table')

            ->leftJoin(
                'users_table',
                'equipment_maintenance_history_table.equipment_maintenance_personnel_id',
                '=',
                'users_table.user_id'
            )

            ->where(
                'equipment_maintenance_equipment_id',
                $equipmentId
            )

            ->select(

                'equipment_maintenance_history_table.*',

                'users_table.user_full_name'

            )

            ->orderBy(
                'equipment_maintenance_created_at',
                'desc'
            )

            ->get();

        return response()->json([

            'success' => true,

            'history' => $history

        ]);
    }

    // =====================================================
    // STORE MAINTENANCE HISTORY
    // =====================================================

    public function storeHistory(Request $request): JsonResponse
    {
        // =====================================================
        // VALIDATE REQUEST
        // =====================================================

        $validated = $request->validate([

            'equipment_id' => [
                'required',
                'exists:equipment_table,equipment_id'
            ],

            'personnel_id' => [
                'required',
                'exists:users_table,user_id'
            ],

            'report_id' => [
                'nullable',
                'exists:reports_table,report_id'
            ],

            'findings' => [
                'required',
                'string'
            ],

            'repair_action' => [
                'nullable',
                'string'
            ],

            'replacement_remarks' => [
                'nullable',
                'string'
            ],

            'status' => [
                'required',
                'in:Pending,Processing,Resolved,For Replacement'
            ],

            'proof_image' => [
                'nullable',
                'image',
                'max:5120'
            ],

        ]);

        // =====================================================
        // UPLOAD PROOF IMAGE
        // =====================================================

        $proofImage = null;

        if ($request->hasFile('proof_image')) {

            $proofImage = $request
                ->file('proof_image')
                ->store(
                    'maintenance-history',
                    'public'
                );

        }

        // =====================================================
        // SAVE MAINTENANCE HISTORY
        // =====================================================

        DB::table('equipment_maintenance_history_table')

            ->insert([

                'equipment_maintenance_equipment_id'
                    => $validated['equipment_id'],

                'equipment_maintenance_report_id'
                    => $validated['report_id'],

                'equipment_maintenance_personnel_id'
                    => $validated['personnel_id'],

                'equipment_maintenance_findings'
                    => $validated['findings'],

                'equipment_maintenance_repair_action'
                    => $validated['repair_action'],

                'equipment_maintenance_replacement_remarks'
                    => $validated['replacement_remarks'],

                'equipment_maintenance_status'
                    => $validated['status'],

                'equipment_maintenance_completed_at'
                    => now(),

                'equipment_maintenance_created_at'
                    => now(),

                'equipment_maintenance_proof_image'
                    => $proofImage,

            ]);

        // =====================================================
        // DETERMINE NEW EQUIPMENT STATUS
        // =====================================================

        $inventoryStatus = match ($validated['status']) {

            'Resolved' => 'Active',

            'Processing' => 'Under Maintenance',

            'For Replacement' => 'For Replacement',

            default => 'Under Maintenance',

        };

        // =====================================================
        // UPDATE EQUIPMENT TABLE
        // =====================================================

        $conditionStatus = match ($validated['status']) {

            'Resolved' => 'Good',

            'Processing' => 'Under Maintenance',

            'Pending' => 'Under Maintenance',

            'For Replacement' => 'Damaged',

        };

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $validated['equipment_id']
            )

            ->update([

                'equipment_inventory_status' => $inventoryStatus,

                'equipment_condition_status' => $conditionStatus,

            ]);

        // =====================================================
        // RETURN RESPONSE
        // =====================================================

        return response()->json([

            'success' => true,

            'message' => 'Maintenance record saved successfully.'

        ], 201);
    }

    // =====================================================
    // GET MAINTENANCE SCHEDULE
    // =====================================================

    public function schedule($equipmentId): JsonResponse
    {
        // =====================================================
        // GET MAINTENANCE SCHEDULES
        // =====================================================

        $schedules = DB::table('maintenance_schedules_table')

            ->where(
                'maintenance_schedule_equipment_id',
                $equipmentId
            )

            ->orderBy(
                'maintenance_schedule_next_date',
                'asc'
            )

            ->get();

        // =====================================================
        // NO SCHEDULE FOUND
        // =====================================================

        if ($schedules->isEmpty()) {

            return response()->json([

                'success' => false,

                'message' => 'No maintenance schedule found.'

            ], 404);

        }

        // =====================================================
        // RETURN MAINTENANCE SCHEDULES
        // =====================================================

        return response()->json([

            'success' => true,

            'schedules' => $schedules

        ]);
    }

    // =====================================================
    // CREATE MAINTENANCE SCHEDULE
    // =====================================================

    public function storeSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => [
                'required',
                'integer',
                'exists:equipment_table,equipment_id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'frequency' => [
                'required',
                'string',
                'max:100',
            ],
            'next_date' => [
                'required',
                'date',
            ],
        ]);

        $scheduleId = DB::table('maintenance_schedules_table')->insertGetId([
            'maintenance_schedule_equipment_id' => $validated['equipment_id'],
            'maintenance_schedule_title' => $validated['title'],
            'maintenance_schedule_description' => $validated['description'] ?? null,
            'maintenance_schedule_frequency' => $validated['frequency'],
            'maintenance_schedule_next_date' => $validated['next_date'],
            'maintenance_schedule_status' => 'Active',
        ]);

        $schedule = DB::table('maintenance_schedules_table')
            ->where('maintenance_schedule_id', $scheduleId)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance schedule created successfully.',
            'schedule' => $schedule,
        ], 201);
    }

    // =====================================================
    // UPDATE MAINTENANCE SCHEDULE
    // =====================================================

    public function updateSchedule(Request $request, $scheduleId): JsonResponse
    {
        // =====================================================
        // VALIDATE REQUEST
        // =====================================================

        $validated = $request->validate([

            'title' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'frequency' => [
                'required',
                'string',
                'max:100'
            ],

            'next_date' => [
                'required',
                'date'
            ],

            'last_date' => [
                'nullable',
                'date'
            ],

            'status' => [
                'required',
                'in:Active,Completed,Overdue'
            ],

        ]);

        // =====================================================
        // CHECK IF SCHEDULE EXISTS
        // =====================================================

        $exists = DB::table('maintenance_schedules_table')

            ->where(
                'maintenance_schedule_id',
                $scheduleId
            )

            ->exists();

        if (!$exists) {

            return response()->json([

                'success' => false,

                'message' => 'Maintenance schedule not found.'

            ], 404);

        }

        // =====================================================
        // UPDATE SCHEDULE(S)
        // =====================================================

        DB::table('maintenance_schedules_table')

            ->where(
                'maintenance_schedule_id',
                $scheduleId
            )

            ->update([

                'maintenance_schedule_title'
                    => $validated['title'],

                'maintenance_schedule_description'
                    => $validated['description'],

                'maintenance_schedule_frequency'
                    => $validated['frequency'],

                'maintenance_schedule_next_date'
                    => $validated['next_date'],

                'maintenance_schedule_last_date'
                    => $validated['last_date'],

                'maintenance_schedule_status'
                    => $validated['status'],

            ]);

        // =====================================================
        // RETURN RESPONSE
        // =====================================================

        return response()->json([

            'success' => true,

            'message' => 'Maintenance schedule updated successfully.'

        ]);
    }
}