<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

                $request->search,

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
            'maintenance-personnel.reports.incoming-reports',
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
            'maintenance-personnel.reports.urgent-reports',
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
            'maintenance-personnel.reports.pending-reports',
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
            'maintenance-personnel.reports.processing-reports',
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
            'maintenance-personnel.reports.resolved-reports',
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
            'maintenance-personnel.reports.replacement-reports',
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
            'maintenance-personnel.reports.rejected-reports',
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
        $report = DB::table('reports_table')

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
                'buildings_table',
                'rooms_table.room_building_id',
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

            /*
            |--------------------------------------------------------------------------
            | SELECT DATA
            |--------------------------------------------------------------------------
            */

            ->select(

                'reports_table.*',

                'rooms_table.room_name',

                'buildings_table.building_name',

                'equipment_table.equipment_name',

                'equipment_table.equipment_inventory_status',

                'reporters_table.reporter_full_name',

                'reporters_table.reporter_contact_number'

            )

            /*
            |--------------------------------------------------------------------------
            | REPORT FILTER
            |--------------------------------------------------------------------------
            */

            ->where(
                'reports_table.report_id',
                $id
            )

            /*
            |--------------------------------------------------------------------------
            | RETURN SINGLE REPORT
            |--------------------------------------------------------------------------
            */

            ->firstOrFail();

        return view(
            'maintenance-personnel.reports.report-details',
            compact('report')
        );
    }
}