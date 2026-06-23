<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MaintenanceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboard()
    {
        /*
        |--------------------------------------------------------------------------
        | REPORT COUNTS
        |--------------------------------------------------------------------------
        */

        $pendingReports = DB::table('reports_table')
            ->where('report_current_status', 'Pending')
            ->count();

        $urgentReports = DB::table('reports_table')
            ->where('report_urgency_level', 'Urgent')
            ->count();

        $resolvedReports = DB::table('reports_table')
            ->where('report_current_status', 'Resolved')
            ->count();

        $rejectedReports = DB::table('reports_table')
            ->where('report_current_status', 'Rejected')
            ->count();

        $replacementReports = DB::table('reports_table')
            ->where('report_current_status', 'For Replacement')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT COUNTS
        |--------------------------------------------------------------------------
        */

        $underMaintenance = DB::table('equipment_table')
            ->where(
                'equipment_inventory_status',
                'Under Maintenance'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | BORROWING COUNTS
        |--------------------------------------------------------------------------
        */

        $borrowedEquipment = DB::table('borrowing_records_table')
            ->where('borrowing_status', 'Borrowed')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE SCHEDULE COUNTS
        |--------------------------------------------------------------------------
        */

        $overdueMaintenance = DB::table('maintenance_schedules_table')
            ->where(
                'maintenance_schedule_status',
                'Overdue'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | RETURN DASHBOARD
        |--------------------------------------------------------------------------
        */

        return view(
            'maintenance-personnel.dashboard',
            compact(
                'pendingReports',
                'urgentReports',
                'resolvedReports',
                'rejectedReports',
                'replacementReports',
                'underMaintenance',
                'borrowedEquipment',
                'overdueMaintenance'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REUSABLE REPORT QUERY
    |--------------------------------------------------------------------------
    */

    private function reportsQuery()
    {
        $request = request();
        $showArchive = $request->archive == 1;
        $isArchiveMode = $request->boolean('archive');

        return DB::table('reports_table')

            /*
            |--------------------------------------------------------------------------
            | JOINS
            |--------------------------------------------------------------------------
            */

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )

            /*
            |--------------------------------------------------------------------------
            | SEARCH SYSTEM
            |--------------------------------------------------------------------------
            */

            ->when(

                $request->filled('search'),

                function ($query) use ($request) {

                    $query->where(function ($subQuery) use ($request) {

                        $subQuery

                            ->where(
                                'reports_table.report_id',
                                'LIKE',
                                '%' . $request->search . '%'
                            )

                            ->orWhere(
                                'equipment_table.equipment_name',
                                'LIKE',
                                $request->search . '%'
                            )

                            ->orWhere(
                                'rooms_table.room_name',
                                'LIKE',
                                $request->search . '%'
                            )

                            ->orWhere(
                                'reporters_table.reporter_full_name',
                                'LIKE',
                                $request->search . '%'
                            );

                    });

                }

            )

            ->when(
                $request->filled('status'),
                function ($query) use ($request, $isArchiveMode) {

                    if (
                        $isArchiveMode &&
                        !in_array(
                            $request->status,
                            ['Resolved', 'Rejected', 'For Replacement']
                        )
                    ) {
                        return;
                    }

                    $query->where(
                        'reports_table.report_current_status',
                        $request->status
                    );

                }
            )

            /* ADD THIS HERE */
            ->when(
                $request->filled('urgency'),
                function ($query) use ($request) {

                    $query->where(
                        'reports_table.report_urgency_level',
                        $request->urgency
                    );

                }
            )

            //REPORTS ARCHIVE SWITCH
            ->when(
                $showArchive,
                function ($query) {
                    $query->where(
                        'reports_table.report_is_archived',
                        true
                    );
                },
                function ($query) {
                    $query->where(
                        'reports_table.report_is_archived',
                        false
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | ORDERING
            |--------------------------------------------------------------------------
            */

            ->orderBy(
                'reports_table.report_submitted_at',
                'DESC'
            )

            /*
            |--------------------------------------------------------------------------
            | SELECT DATA
            |--------------------------------------------------------------------------
            */

            ->select(

                'reports_table.*',

                'rooms_table.room_name',

                'equipment_table.equipment_name',

                'reporters_table.reporter_full_name',

                'reporters_table.reporter_employee_id'

            );
    }

    public function allReports()
    {
        $reports = $this->reportsQuery()

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INCOMING REPORTS
    |--------------------------------------------------------------------------
    */

    public function incomingReports()
    {
        $reports = $this->reportsQuery()

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | URGENT REPORTS
    |--------------------------------------------------------------------------
    */

    public function urgentReports()
    {
        $reports = $this->reportsQuery()

            ->where(
                'report_urgency_level',
                'Urgent'
            )

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING REPORTS
    |--------------------------------------------------------------------------
    */

    public function pendingReports()
    {
        $reports = $this->reportsQuery()

            ->where(
                'report_current_status',
                'Pending'
            )

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PROCESSING REPORTS
    |--------------------------------------------------------------------------
    */

    public function processingReports()
    {
        $reports = $this->reportsQuery()

            ->where(
                'report_current_status',
                'Processing'
            )

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVED REPORTS
    |--------------------------------------------------------------------------
    */

    public function resolvedReports()
    {
        $reports = $this->reportsQuery()

            ->where(
                'report_current_status',
                'Resolved'
            )

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REPLACEMENT REPORTS
    |--------------------------------------------------------------------------
    */

    public function replacementReports()
    {
        $reports = $this->reportsQuery()

            ->where(
                'report_current_status',
                'For Replacement'
            )

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECTED REPORTS
    |--------------------------------------------------------------------------
    */

    public function rejectedReports()
    {
        $reports = $this->reportsQuery()

            ->where(
                'report_current_status',
                'Rejected'
            )

            ->paginate(10)

            ->withQueryString();

        return view(
            'maintenance-personnel.reports.all-reports',
            compact('reports')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REPORT DETAILS
    |--------------------------------------------------------------------------
    */

    public function reportDetails(int $id)
    {
        /*
        |--------------------------------------------------------------------------
        | RETURN SINGLE REPORT
        |--------------------------------------------------------------------------
        */

        $report = DB::table('reports_table')

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->leftJoin(
                'floors_table',
                'rooms_table.room_floor_id',
                '=',
                'floors_table.floor_id'
            )

            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )

            ->select(

                'reports_table.*',

                'rooms_table.room_name',

                'buildings_table.building_name',

                'equipment_table.equipment_name',

                'equipment_table.equipment_inventory_status',

                'reporters_table.reporter_full_name',

                'reporters_table.reporter_employee_id',

                'reporters_table.reporter_contact_number'

            )

            ->where(
                'reports_table.report_id',
                $id
            )

            ->first();

        /*
        |--------------------------------------------------------------------------
        | DEBUG
        |--------------------------------------------------------------------------
        */

        //dd($report);

        /*
        |--------------------------------------------------------------------------
        | RELATED REPORTS
        |--------------------------------------------------------------------------
        */

        $relatedReports = collect();

        return view(
            'maintenance-personnel.reports.report-details',
            compact(
                'report',
                'relatedReports'
            )
        );
    }


    public function assignReportPage($id)
    {
        $report = DB::table('reports_table')
            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->where(
                'reports_table.report_id',
                $id
            )
            ->first();

        if (!$report) {
            return redirect()
                ->back()
                ->with('error', 'Report not found.');
        }

        if ($report->report_current_status !== 'Pending') {
            return redirect()
                ->to('/maintenance/reports/details/' . $id)
                ->with('error', 'Only pending reports can be assigned.');
        }

        $personnel = DB::table('users_table')
            ->where(
                'user_role_id',
                2
            )
            ->orderBy('user_full_name')
            ->get();

        return view(
            'maintenance-personnel.reports.assign-report',
            compact(
                'report',
                'personnel'
            )
        );
    }

    public function assignReport(Request $request, $id)
    {
        $request->validate([

            'personnel_id' => 'required'

        ]);

        $report = DB::table('reports_table')
            ->where('report_id', $id)
            ->first();

        if (!$report) {
            return redirect()
                ->back()
                ->with('error', 'Report not found.');
        }

        if ($report->report_current_status !== 'Pending') {
            return redirect()
                ->back()
                ->with('error', 'Only pending reports can be assigned.');
        }

        DB::table('reports_table')

            ->where(
                'report_id',
                $id
            )

            ->update([

                'report_assigned_personnel_id'
                    =>
                    $request->personnel_id,

                'report_current_status'
                    =>
                    'Processing',

                'report_updated_at'
                    =>
                    now()

            ]);

        return redirect()

            ->to(
                '/maintenance/reports/details/' . $id
            )

            ->with(
                'success',
                'Report assigned successfully.'
            );
    }

    public function updateStatusPage($id)
    {
        $report = DB::table('reports_table')
            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->where(
                'reports_table.report_id',
                $id
            )
            ->first();

        return view(
            'maintenance-personnel.reports.update-status',
            compact('report')
        );
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
            'remarks' => 'nullable|string',
            'proof_image' => 'nullable|image|max:5120'
        ]);

        $report = DB::table('reports_table')
            ->where('report_id', $id)
            ->first();

        if (!$report) {
            return redirect()
                ->back()
                ->with('error', 'Report not found.');
        }

        $undoRequested = (bool) $request->boolean('undo');
        $newStatus = $request->status;

        if ($undoRequested) {

            DB::table('reports_table')
                ->where('report_id', $id)
                ->update([
                    'report_current_status' => $newStatus,
                    'report_updated_at' => now()
                ]);

            return redirect()
                ->back()
                ->with('success', 'Status reverted successfully.');
        }

        $allowedTransitions = [
            'Pending' => ['Processing', 'Rejected'],
            'Processing' => ['Resolved', 'For Replacement'],
        ];

        if (
            !isset($allowedTransitions[$report->report_current_status])
            || !in_array($newStatus, $allowedTransitions[$report->report_current_status], true)
        ) {
            return redirect()
                ->back()
                ->with('error', 'This status cannot be changed to the selected value.');
        }

        $imagePath = null;

        if($request->hasFile('proof_image')){

            $imagePath = $request
                ->file('proof_image')
                ->store(
                    'maintenance-proofs',
                    'public'
                );
        }

        $update = [
            'report_current_status' => $newStatus,
            'report_updated_at' => now()
        ];

        if($newStatus === 'Resolved'){

            $update['report_resolution_notes']
                = $request->remarks;

            $update['report_resolution_image']
                = $imagePath;
        }

        if($newStatus === 'Rejected'){

            $update['report_rejection_notes']
                = $request->remarks;
        }

        if($newStatus === 'For Replacement'){

            $update['report_replacement_notes']
                = $request->remarks;

            $update['report_replacement_image']
                = $imagePath;

            $update['report_replacement_submitted_to_purchaser']
                = 1;
        }

        if($newStatus === 'For Replacement'){

            $existingNotification = DB::table(
                'notifications_table'
            )
            ->where(
                'notification_type',
                'replacement'
            )
            ->where(
                'notification_message',
                'Report #'.$id.' requires replacement.'
            )
            ->exists();

            if(!$existingNotification){

                DB::table('notifications_table')
                    ->insert([

                        'notification_user_id' => 3,

                        'notification_title'
                            => 'Replacement Request',

                        'notification_message'
                            => 'Report #'.$id.' requires replacement.',

                        'notification_type'
                            => 'replacement',

                        'notification_created_at'
                            => now()

                    ]);

            }
        }

        if($newStatus === 'For Replacement'){

            $existingProcurement = DB::table(
                'procurement_requests_table'
            )
            ->where(
                'procurement_request_report_id',
                $id
            )
            ->exists();

            if(!$existingProcurement){

                DB::table(
                    'procurement_requests_table'
                )->insert([

                    'procurement_request_report_id'
                        => $id,

                    'procurement_request_status'
                        => 'Pending',

                    'procurement_request_created_by'
                        => Auth::id()

                ]);

            }
        }

        if (
            $report->report_current_status === 'Pending'
            && $newStatus === 'Processing'
            && Auth::id()
        ) {
            $update['report_assigned_personnel_id'] = Auth::id();
        }

        DB::table('reports_table')
            ->where('report_id', $id)
            ->update($update);

        return redirect()
            ->back()
            ->with('success', 'Report status updated successfully.')
            ->with('undo_report_id', $id)
            ->with('undo_previous_status', $report->report_current_status);
    }

    public function archiveReport($id)
    {
        DB::table('reports_table')
            ->where('report_id', $id)
            ->update([
                'report_is_archived' => true
            ]);

        return back()
            ->with(
                'success',
                'Report archived successfully.'
            );
    }

    public function restoreReport($id)
    {
        DB::table('reports_table')
            ->where('report_id', $id)
            ->update([
                'report_is_archived' => false
            ]);

        return back()
            ->with(
                'success',
                'Report restored successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT INVENTORY
    |--------------------------------------------------------------------------
    */

    public function equipmentInventory(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTER DATA
        |--------------------------------------------------------------------------
        */

        $categories = DB::table(
            'equipment_categories_table'
        )
        ->orderBy(
            'equipment_category_name'
        )
        ->get();

        $rooms = DB::table(
            'rooms_table'
        )
        ->orderBy(
            'room_name'
        )
        ->get();

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD COUNTS
        |--------------------------------------------------------------------------
        */

        $totalEquipment = DB::table(
            'equipment_table'
        )->count();

        $activeEquipment = DB::table(
            'equipment_table'
        )
        ->where(
            'equipment_inventory_status',
            'Active'
        )
        ->count();

        $underMaintenanceEquipment = DB::table(
            'equipment_table'
        )
        ->where(
            'equipment_inventory_status',
            'Under Maintenance'
        )
        ->count();

        $borrowedEquipment = DB::table(
            'equipment_table'
        )
        ->where(
            'equipment_inventory_status',
            'Borrowed'
        )
        ->count();

        $disposedEquipment = DB::table(
            'equipment_table'
        )
        ->where(
            'equipment_inventory_status',
            'Disposed'
        )
        ->count();

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT QUERY
        |--------------------------------------------------------------------------
        */

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

                'equipment_table.*',

                'equipment_categories_table.equipment_category_name',

                'rooms_table.room_name'

            );

        /*
        |--------------------------------------------------------------------------
        | SEARCH FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'equipment_table.equipment_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_brand_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_asset_tag',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_serial_number',
                    'LIKE',
                    '%' . $request->search . '%'
                );

            });
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category')) {

            $query->where(
                'equipment_table.equipment_category_id',
                $request->category
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ROOM FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('room')) {

            $query->where(
                'equipment_table.equipment_room_id',
                $request->room
            );
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'equipment_table.equipment_inventory_status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $equipment = $query

            ->orderBy(
                'equipment_table.equipment_name',
                'asc'
            )

            ->paginate(10)

            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'maintenance-personnel.equipment.index',
            compact(
                'equipment',
                'categories',
                'rooms',
                'totalEquipment',
                'activeEquipment',
                'underMaintenanceEquipment',
                'borrowedEquipment',
                'disposedEquipment'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW EQUIPMENT
    |--------------------------------------------------------------------------
    */

    public function viewEquipment($id)
    {
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

            ->select(

                'equipment_table.*',

                'equipment_categories_table.equipment_category_name',

                'rooms_table.room_name'

            )

            ->where(
                'equipment_table.equipment_id',
                $id
            )

            ->first();

        if (!$equipment) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Equipment not found.'
                );

        }

        return view(
            'maintenance-personnel.equipment.view',
            compact('equipment')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE EQUIPMENT
    |--------------------------------------------------------------------------
    */
    public function createEquipment()
    {
        $categories = DB::table(
            'equipment_categories_table'
        )->get();

        $rooms = DB::table(
            'rooms_table'
        )->get();

        return view(
            'maintenance-personnel.equipment.create',
            compact(
                'categories',
                'rooms'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE EQUIPMENT
    |--------------------------------------------------------------------------
    */
    public function storeEquipment(Request $request)
    {
        $request->validate([

            'equipment_name' => 'required',

            'equipment_category_id' => 'required',

            'equipment_room_id' => 'required',

            'equipment_quantity' => 'required'

        ]);

        DB::table('equipment_table')
            ->insert([

                'equipment_category_id'
                    => $request->equipment_category_id,

                'equipment_room_id'
                    => $request->equipment_room_id,

                'equipment_asset_tag'
                    => $request->equipment_asset_tag,

                'equipment_name'
                    => $request->equipment_name,

                'equipment_brand_name'
                    => $request->equipment_brand_name,

                'equipment_model'
                    => $request->equipment_model,

                'equipment_serial_number'
                    => $request->equipment_serial_number,

                'equipment_quantity'
                    => $request->equipment_quantity,

                'equipment_condition_status'
                    => $request->equipment_condition_status,

                'equipment_inventory_status'
                    => $request->equipment_inventory_status,

                'equipment_purchase_date'
                    => $request->equipment_purchase_date,

                'equipment_warranty_expiration'
                    => $request->equipment_warranty_expiration,

                'equipment_is_borrowable'
                    => $request->has('equipment_is_borrowable')

            ]);

        return redirect(
            '/maintenance/equipment/inventory'
        )->with(
            'success',
            'Equipment added successfully.'
        );
    }

    
    /*
    |--------------------------------------------------------------------------
    | UPDATE EQUIPMENT
    |--------------------------------------------------------------------------
    */

    public function updateEquipment(
        Request $request,
        $id
    )
    {
        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->update([

                'equipment_category_id'
                    => $request->equipment_category_id,

                'equipment_room_id'
                    => $request->equipment_room_id,

                'equipment_asset_tag'
                    => $request->equipment_asset_tag,

                'equipment_name'
                    => $request->equipment_name,

                'equipment_brand_name'
                    => $request->equipment_brand_name,

                'equipment_model'
                    => $request->equipment_model,

                'equipment_serial_number'
                    => $request->equipment_serial_number,

                'equipment_quantity'
                    => $request->equipment_quantity,

                'equipment_condition_status'
                    => $request->equipment_condition_status,

                'equipment_inventory_status'
                    => $request->equipment_inventory_status

            ]);

        return back()->with(
            'success',
            'Equipment updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT TRANSFER & HISTORY
    |--------------------------------------------------------------------------
    */

    public function equipmentTransferHistory(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | ROOMS FOR TRANSFER MODAL
        |--------------------------------------------------------------------------
        */

        $rooms = DB::table('rooms_table')

            ->orderBy(
                'room_name',
                'asc'
            )

            ->get();

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT LIST
        |--------------------------------------------------------------------------
        */

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

            ->select(

                'equipment_table.*',

                'equipment_categories_table.equipment_category_name',

                'rooms_table.room_name'

            )

            ->orderBy(
                'equipment_table.equipment_name',
                'asc'
            )

            ->get();

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'maintenance-personnel.equipment.transfer-equipment',
            compact(
                'equipment',
                'rooms'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT HISTORY API
    |--------------------------------------------------------------------------
    */

    public function getEquipmentHistory($id)
    {
        $history = DB::table(
            'equipment_maintenance_history_table'
        )

        ->where(
            'equipment_maintenance_equipment_id',
            $id
        )

        ->orderBy(
            'equipment_maintenance_created_at',
            'desc'
        )

        ->get();

        return response()->json(
            $history
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSFER EQUIPMENT
    |--------------------------------------------------------------------------
    */

    public function transferEquipment(Request $request)
    {
        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $request->equipment_id
            )

            ->first();

        if (!$equipment) {

            return back()->with(
                'error',
                'Equipment not found.'
            );
        }

        DB::table(
            'equipment_transfer_history_table'
        )

        ->insert([

            'equipment_id' =>
                $equipment->equipment_id,

            'from_room_id' =>
                $equipment->equipment_room_id,

            'to_room_id' =>
                $request->room_id,

            'remarks' =>
                $request->remarks,

            'created_at' =>
                now()

        ]);

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $equipment->equipment_id
            )

            ->update([

                'equipment_room_id' =>
                    $request->room_id

            ]);

        return back()->with(
            'success',
            'Equipment transferred successfully.'
        );
    }

    public function storeMaintenanceHistory(Request $request)
    {
        $imagePath = null;

        if ($request->hasFile('proof_image')) {

            $imagePath = $request
                ->file('proof_image')
                ->store(
                    'maintenance-history',
                    'public'
                );
        }

        DB::table(
            'equipment_maintenance_history_table'
        )
        ->insert([

            'equipment_maintenance_equipment_id'
                => $request->equipment_id,

            'equipment_maintenance_status'
                => $request->status,

            'equipment_maintenance_findings'
                => $request->findings,

            'equipment_maintenance_repair_action'
                => $request->repair_action,

            'equipment_maintenance_proof_image'
                => $imagePath,

            'equipment_maintenance_created_at'
                => now()

        ]);

        return back();
    }

    public function getTransferHistory($id)
    {
        return DB::table(
            'equipment_transfer_history_table'
        )

        ->leftJoin(
            'rooms_table as from_room',
            'equipment_transfer_history_table.from_room_id',
            '=',
            'from_room.room_id'
        )

        ->leftJoin(
            'rooms_table as to_room',
            'equipment_transfer_history_table.to_room_id',
            '=',
            'to_room.room_id'
        )

        ->where(
            'equipment_transfer_history_table.equipment_id',
            $id
        )

        ->select(
            'equipment_transfer_history_table.*',
            'from_room.room_name as from_room_name',
            'to_room.room_name as to_room_name'
        )

        ->orderBy(
            'created_at',
            'desc'
        )

        ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | QR CODE TOOLS
    |--------------------------------------------------------------------------
    */

    public function qrTools()
    {
        $equipment = DB::table('equipment_table')

            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )

            ->select(
                'equipment_table.*',
                'equipment_categories_table.equipment_category_name'
            )

            ->orderBy(
                'equipment_name',
                'asc'
            )

            ->get();

        return view(
            'maintenance-personnel.equipment.qr-code-generator',
            compact('equipment')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE QR
    |--------------------------------------------------------------------------
    */

    public function generateQr($id)
    {
        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->first();

        if (!$equipment) {

            return back()->with(
                'error',
                'Equipment not found.'
            );
        }

        $qrCode = 'QR-' . str_pad(
            $equipment->equipment_id,
            6,
            '0',
            STR_PAD_LEFT
        );

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->update([

                'equipment_qr_code' => $qrCode

            ]);

        return back()->with(
            'success',
            'QR generated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QR IMAGE
    |--------------------------------------------------------------------------
    */

    public function qrImage($code)
    {
        $url = url(
            '/equipment/' . $code
        );

        return response(

            QrCode::format('svg')
                ->size(300)
                ->generate($url)

        )

        ->header(
            'Content-Type',
            'image/svg+xml'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT LOOKUP BY QR
    |--------------------------------------------------------------------------
    */

    public function equipmentByQr($qrCode)
    {
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
                'equipment_qr_code',
                $qrCode
            )

            ->select(
                'equipment_table.*',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name'
            )

            ->first();

        if (!$equipment) {

            abort(404);

        }

        /*
        |--------------------------------------------------------------------------
        | SAVE SCAN LOG
        |--------------------------------------------------------------------------
        */

        DB::table(
            'qr_code_logs_table'
        )

        ->insert([

            'qr_code_equipment_id' =>
                $equipment->equipment_id,

            'qr_code_scanned_by' =>
                Auth::check()
                    ? Auth::id()
                    : null,

            'qr_code_scan_location' =>
                request()->ip(),

            'qr_code_scan_device' =>
                request()->userAgent(),

            'qr_code_scanned_at' =>
                now()

        ]);

        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE HISTORY
        |--------------------------------------------------------------------------
        */

        $maintenanceHistory = DB::table(
            'equipment_maintenance_history_table'
        )

        ->where(
            'equipment_maintenance_equipment_id',
            $equipment->equipment_id
        )

        ->orderBy(
            'equipment_maintenance_created_at',
            'desc'
        )

        ->get();

        /*
        |--------------------------------------------------------------------------
        | TRANSFER HISTORY
        |--------------------------------------------------------------------------
        */

        $transferHistory = DB::table(
            'equipment_transfer_history_table'
        )

        ->leftJoin(
            'rooms_table as from_room',
            'equipment_transfer_history_table.from_room_id',
            '=',
            'from_room.room_id'
        )

        ->leftJoin(
            'rooms_table as to_room',
            'equipment_transfer_history_table.to_room_id',
            '=',
            'to_room.room_id'
        )

        ->where(
            'equipment_transfer_history_table.equipment_id',
            $equipment->equipment_id
        )

        ->select(

            'equipment_transfer_history_table.*',

            'from_room.room_name as from_room_name',

            'to_room.room_name as to_room_name'

        )

        ->orderBy(
            'created_at',
            'desc'
        )

        ->get();

        return view(
            'public.equipment-qr-view',
            compact(
                'equipment',
                'maintenanceHistory',
                'transferHistory'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BORROWING LIST - BORROWING MODULE
    |--------------------------------------------------------------------------
    */

    public function borrowing()
    {
        $borrowings = DB::table('borrowing_records_table')

            ->leftJoin(
                'equipment_table',
                'borrowing_records_table.borrowing_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->select(
                'borrowing_records_table.*',
                'equipment_table.equipment_name'
            )

            ->orderBy(
                'borrowing_created_at',
                'desc'
            )

            ->get();

        foreach($borrowings as $record){

            if(

                $record->borrowing_status === 'Borrowed'

                &&

                $record->borrowing_expected_return_date

                &&

                now()->toDateString() >
                $record->borrowing_expected_return_date

            ){

                DB::table(
                    'borrowing_records_table'
                )

                ->where(
                    'borrowing_record_id',
                    $record->borrowing_record_id
                )

                ->update([

                    'borrowing_status'
                        => 'Overdue'

                ]);

                $record->borrowing_status
                    = 'Overdue';
            }
        }

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_is_borrowable',
                1
            )

            ->where(
                'equipment_inventory_status',
                'Active'
            )

            ->orderBy(
                'equipment_name',
                'asc'
            )

            ->get();

        return view(
            'maintenance-personnel.borrowing.index',
            compact('borrowings', 'equipment')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE BORROWING
    |--------------------------------------------------------------------------
    */

    public function storeBorrowing(Request $request)
    {
        DB::table('borrowing_records_table')

            ->insert([

                'borrowing_equipment_id'
                    => $request->borrowing_equipment_id,

                'borrowing_borrower_name'
                    => $request->borrowing_borrower_name,

                'borrowing_borrower_department'
                    => $request->borrowing_borrower_department,

                'borrowing_quantity'
                    => $request->borrowing_quantity,

                'borrowing_equipment_condition'
                    => $request->borrowing_equipment_condition,

                'borrowing_date'
                    => $request->borrowing_date,

                'borrowing_expected_return_date'
                    => $request->borrowing_expected_return_date,

                'borrowing_purpose'
                    => $request->borrowing_purpose,

                'borrowing_destination_location'
                    => $request->borrowing_destination_location,

                'borrowing_authorized_by'
                    => $request->borrowing_authorized_by,

                'borrowing_remarks'
                    => $request->borrowing_remarks,

                'borrowing_status'
                    => 'Borrowed',

                'borrowing_created_at'
                    => now()

            ]);

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $request->borrowing_equipment_id
            )

            ->update([

                'equipment_inventory_status'
                    => 'Borrowed'

            ]);

        return back()->with(
            'success',
            'Equipment borrowed successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RETURN EQUIPMENT
    |--------------------------------------------------------------------------
    */

    public function returnEquipment(Request $request)
    {
        $record = DB::table(
            'borrowing_records_table'
        )

        ->where(
            'borrowing_record_id',
            $request->borrowing_record_id
        )

        ->first();

        if(!$record){

            return back()->with(
                'error',
                'Borrowing record not found.'
            );
        }

        DB::table(
            'borrowing_records_table'
        )

        ->where(
            'borrowing_record_id',
            $request->borrowing_record_id
        )

        ->update([

            'borrowing_status'
                => 'Returned',

            'borrowing_actual_return_date'
                => now()

        ]);

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $record->borrowing_equipment_id
            )

            ->update([

                'equipment_inventory_status' => 'Active',

                'equipment_condition_status' => $request->return_condition

            ]);

        return back()->with(
            'success',
            'Equipment returned successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MAINTENANCE SCHEDULES
    |--------------------------------------------------------------------------
    */

    public function schedules()
    {
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
                'equipment_table.equipment_name',
                'rooms_table.room_name'
            )

            ->orderBy(
                'maintenance_schedule_next_date',
                'asc'
            )

            ->get();

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_inventory_status',
                'Active'
            )

            ->orderBy(
                'equipment_name'
            )

            ->get();

        return view(
            'maintenance-personnel.maintenance-schedules.index',
            compact(
                'schedules',
                'equipment'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function storeSchedule(Request $request)
    {
        DB::table('maintenance_schedules_table')

            ->insert([

                'maintenance_schedule_equipment_id'
                    => $request->equipment_id,

                'maintenance_schedule_title'
                    => $request->title,

                'maintenance_schedule_description'
                    => $request->description,

                'maintenance_schedule_frequency'
                    => $request->frequency,

                'maintenance_schedule_next_date'
                    => $request->next_date,

                'maintenance_schedule_status'
                    => 'Active'
            ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Maintenance schedule created.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function completeSchedule(Request $request)
    {
        $schedule = DB::table(
            'maintenance_schedules_table'
        )

        ->where(
            'maintenance_schedule_id',
            $request->schedule_id
        )

        ->first();

        $proofImage = null;

        if(
            $request->hasFile(
                'proof_image'
            )
        ){

            $proofImage = $request
                ->file('proof_image')
                ->store(
                    'maintenance-proofs',
                    'public'
                );
        }

        DB::table(
            'equipment_maintenance_history_table'
        )

        ->insert([

            'equipment_maintenance_equipment_id'
                => $schedule->maintenance_schedule_equipment_id,

            'equipment_maintenance_personnel_id'
                => 

                Auth::check()
                    ? Auth::id()
                    : null,

            'equipment_maintenance_findings'
                => $request->findings,

            'equipment_maintenance_repair_action'
                => $request->repair_action,

            'equipment_maintenance_status'
                => $request->maintenance_status,

            'equipment_maintenance_completed_at'
                => now(),

            'equipment_maintenance_created_at'
                => now(),

            'equipment_maintenance_proof_image'
                => $proofImage

        ]);

        DB::table(
            'maintenance_schedules_table'
        )

        ->where(
            'maintenance_schedule_id',
            $request->schedule_id
        )

        ->update([

            'maintenance_schedule_status'
                => 'Completed',

            'maintenance_schedule_last_date'
                => now()

        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Maintenance completed successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | RESCHEDULE MAINTENANCE
    |--------------------------------------------------------------------------
    */

    public function rescheduleSchedule(Request $request)
    {
        $schedule = DB::table(
            'maintenance_schedules_table'
        )

        ->where(
            'maintenance_schedule_id',
            $request->schedule_id
        )

        ->first();

        DB::table(
            'maintenance_schedules_table'
        )

        ->where(
            'maintenance_schedule_id',
            $request->schedule_id
        )

        ->update([

            'maintenance_schedule_next_date'
                => $request->new_date,

            'maintenance_schedule_description'
                => $schedule->maintenance_schedule_description .

                "\n\nReschedule Reason:\n" .

                $request->reason

        ]);

        return back()->with(
            'success',
            'Schedule rescheduled successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function deleteSchedule(Request $request)
    {
        DB::table(
            'maintenance_schedules_table'
        )

        ->where(
            'maintenance_schedule_id',
            $request->schedule_id
        )

        ->delete();

        return back()->with(
            'success',
            'Schedule deleted successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DISPOSAL LIST
    |--------------------------------------------------------------------------
    */

    public function disposal()
    {
        $disposals = DB::table('disposal_records_table')

            ->leftJoin(
                'equipment_table',
                'disposal_records_table.disposal_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )

            ->select(

                'disposal_records_table.*',

                'equipment_table.equipment_name',

                'equipment_table.equipment_condition_status',

                'equipment_categories_table.equipment_category_name'

            )

            ->orderBy(
                'disposal_disposed_at',
                'desc'
            )

            ->get();

        $equipment = DB::table('equipment_table')

            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )

            ->where(
                'equipment_inventory_status',
                '!=',
                'Disposed'
            )

            ->select(

                'equipment_table.*',

                'equipment_categories_table.equipment_category_name'

            )

            ->orderBy(
                'equipment_name'
            )

            ->get();

        return view(
            'maintenance-personnel.disposal.index',
            compact(
                'disposals',
                'equipment'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DISPOSE EQUIPMENT
    |--------------------------------------------------------------------------
    */

    public function storeDisposal(Request $request)
    {
        DB::table(
            'disposal_records_table'
        )

        ->insert([

            'disposal_equipment_id'
                => $request->equipment_id,

            'disposal_reason'
                => $request->reason,

            'disposal_area_location'
                => $request->location,

            'disposal_approved_by'
                => Auth::id(),

            'disposal_disposed_at'
                => now()

        ]);

        DB::table('equipment_table')

            ->where(
                'equipment_id',
                $request->equipment_id
            )

            ->update([

                'equipment_inventory_status'
                    => 'Disposed'

            ]);

        return back()->with(
            'success',
            'Equipment disposed successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE DISPOSAL RECORD
    |--------------------------------------------------------------------------
    */

    public function deleteDisposal(Request $request)
    {
        DB::table(
            'disposal_records_table'
        )

        ->where(
            'disposal_record_id',
            $request->disposal_id
        )

        ->delete();

        return back()->with(
            'success',
            'Disposal record deleted.'
        );
    }
}