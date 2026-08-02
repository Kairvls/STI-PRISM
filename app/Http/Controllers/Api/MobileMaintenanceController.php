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