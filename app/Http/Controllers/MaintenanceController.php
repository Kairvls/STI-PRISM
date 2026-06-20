<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                                '%' . $request->search . '%'
                            )

                            ->orWhere(
                                'rooms_table.room_name',
                                'LIKE',
                                '%' . $request->search . '%'
                            )

                            ->orWhere(
                                'reporters_table.reporter_full_name',
                                'LIKE',
                                '%' . $request->search . '%'
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

                'reporters_table.reporter_full_name'

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
            'status' => 'required'
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
            $update = [
                'report_current_status' => $newStatus,
                'report_updated_at' => now()
            ];

            DB::table('reports_table')
                ->where('report_id', $id)
                ->update($update);

            return redirect()
                ->back()
                ->with('success', 'Status reverted successfully.');
        }

        $allowedTransitions = [
            'Pending' => ['Processing', 'Rejected'],
            'Processing' => ['Resolved', 'Rejected', 'For Replacement'],
        ];

        if (
            !isset($allowedTransitions[$report->report_current_status])
            || !in_array($newStatus, $allowedTransitions[$report->report_current_status], true)
        ) {
            return redirect()
                ->back()
                ->with('error', 'This status cannot be changed to the selected value.');
        }

        $update = [
            'report_current_status' => $newStatus,
            'report_updated_at' => now()
        ];

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
}