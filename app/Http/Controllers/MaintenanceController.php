<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MaintenanceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    // =====================================================
    // REPLACE YOUR CURRENT dashboard() METHOD
    // STARTING HERE
    // =====================================================

    public function dashboard()
    {
        // =====================================================
        // CURRENT LOGGED IN USER
        // =====================================================

        $user = Auth::user();


        // =====================================================
        // ACTIVE / OPEN REPORT STATISTICS
        // =====================================================

        $pendingReports = DB::table('reports_table')
            ->where('report_current_status', 'Pending')
            ->where('report_is_archived', false)
            ->count();


        $urgentReports = DB::table('reports_table')
            ->where('report_urgency_level', 'Urgent')
            ->whereNotIn(
                'report_current_status',
                [
                    'Resolved',
                    'Rejected',
                    'For Replacement',
                ]
            )
            ->where('report_is_archived', false)
            ->count();


        $underMaintenance = DB::table('equipment_table')
            ->where(
                'equipment_inventory_status',
                'Under Maintenance'
            )
            ->count();


        $borrowedEquipment = DB::table('borrowing_records_table')
            ->where('borrowing_status', 'Borrowed')
            ->count();


        $overdueMaintenance = DB::table('maintenance_schedules_table')
            ->where('maintenance_schedule_status', 'Overdue')
            ->count();


        // =====================================================
        // PENDING REPORTS SUBMITTED TODAY
        // =====================================================

        $pendingReportsToday = DB::table('reports_table')
            ->where('report_current_status', 'Pending')
            ->whereDate('report_submitted_at', today())
            ->where('report_is_archived', false)
            ->count();


        // =====================================================
        // URGENT REPORT LIST
        // LATEST 5 ACTIVE URGENT REPORTS
        // =====================================================

        $urgentReportList = DB::table('reports_table')

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

            ->where(
                'reports_table.report_urgency_level',
                'Urgent'
            )

            ->whereNotIn(
                'reports_table.report_current_status',
                [
                    'Resolved',
                    'Rejected',
                    'For Replacement',
                ]
            )

            ->where(
                'reports_table.report_is_archived',
                false
            )

            ->select(
                'reports_table.*',

                'rooms_table.room_name',

                'floors_table.floor_level',

                'buildings_table.building_name',

                'equipment_table.equipment_name',

                'reporters_table.reporter_full_name'
            )

            ->orderBy(
                'reports_table.report_submitted_at',
                'desc'
            )

            ->limit(5)

            ->get();


        // =====================================================
        // BUILDINGS
        // =====================================================

        $buildings = DB::table('buildings_table')

            ->orderBy(
                'building_name',
                'asc'
            )

            ->get();


        // =====================================================
        // FLOORS
        // =====================================================

        $floors = DB::table('floors_table')

            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )

            ->select(
                'floors_table.floor_id',

                'floors_table.floor_building_id',

                'floors_table.floor_level',

                'buildings_table.building_name'
            )

            ->orderBy(
                'floors_table.floor_id',
                'asc'
            )

            ->get();


        // =====================================================
        // ROOMS WITH BUILDING, FLOOR, AND EQUIPMENT COUNT
        // =====================================================

        $rooms = DB::table('rooms_table')

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

            ->where(
                'rooms_table.room_is_archived',
                false
            )

            ->select(
                'rooms_table.*',

                'floors_table.floor_id',

                'floors_table.floor_building_id',

                'floors_table.floor_level',

                'buildings_table.building_id',

                'buildings_table.building_name'
            )

            ->selectSub(function ($query) {

                $query
                    ->from('equipment_table')

                    ->selectRaw(
                        'COALESCE(SUM(equipment_quantity), 0)'
                    )

                    ->whereColumn(
                        'equipment_table.equipment_room_id',
                        'rooms_table.room_id'
                    )

                    ->where(
                        'equipment_table.equipment_inventory_status',
                        '!=',
                        'Disposed'
                    );

            }, 'equipment_count')

            ->orderBy(
                'buildings_table.building_id',
                'asc'
            )

            ->orderBy(
                'floors_table.floor_id',
                'asc'
            )

            ->orderBy(
                'rooms_table.room_name',
                'asc'
            )

            ->get();


        // =====================================================
        // ADD DASHBOARD INFORMATION TO EVERY ROOM
        // =====================================================

        $rooms->transform(function ($room) {


            // =================================================
            // ACTIVE REPORT COUNT
            // =================================================

            $room->active_report_count =
                DB::table('reports_table')

                    ->where(
                        'report_room_id',
                        $room->room_id
                    )

                    ->whereNotIn(
                        'report_current_status',
                        [
                            'Resolved',
                            'Rejected',
                            'For Replacement',
                        ]
                    )

                    ->where(
                        'report_is_archived',
                        false
                    )

                    ->count();


            // =================================================
            // ACTIVE URGENT REPORT COUNT
            // =================================================

            $room->urgent_report_count =
                DB::table('reports_table')

                    ->where(
                        'report_room_id',
                        $room->room_id
                    )

                    ->where(
                        'report_urgency_level',
                        'Urgent'
                    )

                    ->whereNotIn(
                        'report_current_status',
                        [
                            'Resolved',
                            'Rejected',
                            'For Replacement',
                        ]
                    )

                    ->where(
                        'report_is_archived',
                        false
                    )

                    ->count();


            // =================================================
            // UNDER MAINTENANCE EQUIPMENT COUNT
            // =================================================

            $room->maintenance_equipment_count =
                DB::table('equipment_table')

                    ->where(
                        'equipment_room_id',
                        $room->room_id
                    )

                    ->where(
                        'equipment_inventory_status',
                        'Under Maintenance'
                    )

                    ->count();


            // =================================================
            // DETERMINE ROOM DASHBOARD STATUS
            // =================================================

            if ($room->urgent_report_count > 0) {

                $room->dashboard_status = 'critical';

                $room->dashboard_label = 'Critical';

            } elseif ($room->active_report_count > 0) {

                $room->dashboard_status = 'needs-repair';

                $room->dashboard_label = 'Repair';

            } elseif ($room->maintenance_equipment_count > 0) {

                $room->dashboard_status = 'maintenance';

                $room->dashboard_label = 'Maintenance';

            } else {

                $room->dashboard_status = 'available';

                $room->dashboard_label = 'Good';

            }


            return $room;

        });


        // =====================================================
        // GROUP ROOMS BY FLOOR
        // PRESERVED FOR EXISTING BLADE COMPATIBILITY
        // =====================================================

        $roomsByFloor = $rooms->groupBy('floor_id');


        // =====================================================
        // NEW DASHBOARD DATA
        //
        // REPORT TREND
        // LAST 30 DAYS
        // =====================================================

        $reportTrendStartDate =
            now()
                ->copy()
                ->subDays(29)
                ->startOfDay();


        $reportTrendEndDate =
            now()
                ->copy()
                ->endOfDay();


        // =====================================================
        // GET DATABASE COUNTS GROUPED BY DATE
        // =====================================================

        $reportTrendRows = DB::table('reports_table')

            ->selectRaw(
                '
                DATE(report_submitted_at) AS report_date,
                COUNT(*) AS report_count
                '
            )

            ->whereBetween(
                'report_submitted_at',
                [
                    $reportTrendStartDate,
                    $reportTrendEndDate,
                ]
            )

            ->groupByRaw(
                'DATE(report_submitted_at)'
            )

            ->orderByRaw(
                'DATE(report_submitted_at)'
            )

            ->get()

            ->keyBy('report_date');


        // =====================================================
        // BUILD ALL 30 DAYS
        //
        // DAYS WITHOUT REPORTS MUST STILL APPEAR AS ZERO
        // =====================================================

        $reportTrendLabels = [];

        $reportTrendData = [];


        for ($i = 29; $i >= 0; $i--) {

            $date =
                now()
                    ->copy()
                    ->subDays($i);


            $databaseDate =
                $date->format('Y-m-d');


            $reportTrendLabels[] =
                $date->format('M j');


            $reportTrendData[] =
                (int) (
                    $reportTrendRows
                        ->get($databaseDate)
                        ->report_count
                    ?? 0
                );

        }

        // =====================================================
        // ACTIVE REPORT TREND
        // LAST 30 DAYS
        //
        // COUNTS REPORTS SUBMITTED EACH DAY
        // THAT ARE STILL PENDING OR PROCESSING
        // =====================================================

        $activeReportTrendRows = DB::table('reports_table')

            ->selectRaw(
                '
                DATE(report_submitted_at) AS report_date,
                COUNT(*) AS report_count
                '
            )

            ->whereIn(
                'report_current_status',
                [
                    'Pending',
                    'Processing',
                ]
            )

            ->where(
                'report_is_archived',
                false
            )

            ->whereBetween(
                'report_submitted_at',
                [
                    $reportTrendStartDate,
                    $reportTrendEndDate,
                ]
            )

            ->groupByRaw(
                'DATE(report_submitted_at)'
            )

            ->orderByRaw(
                'DATE(report_submitted_at)'
            )

            ->get()

            ->keyBy('report_date');


        // =====================================================
        // BUILD ALL 30 DAYS
        // DAYS WITHOUT ACTIVE REPORTS = ZERO
        // =====================================================

        $activeReportTrendLabels = [];

        $activeReportTrendData = [];


        for ($i = 29; $i >= 0; $i--) {

            $date = now()
                ->copy()
                ->subDays($i);


            $databaseDate =
                $date->format('Y-m-d');


            $activeReportTrendLabels[] =
                $date->format('M j');


            $activeReportTrendData[] =
                (int) (
                    $activeReportTrendRows
                        ->get($databaseDate)
                        ->report_count
                    ?? 0
                );

        }


        // =====================================================
        // NEW DASHBOARD DATA
        //
        // REPORT STATUS DONUT CHART
        // ALL NON ARCHIVED REPORTS
        // =====================================================

        $reportStatusRows = DB::table('reports_table')

            ->select(
                'report_current_status'
            )

            ->selectRaw(
                'COUNT(*) AS status_count'
            )

            ->where(
                'report_is_archived',
                false
            )

            ->groupBy(
                'report_current_status'
            )

            ->pluck(
                'status_count',
                'report_current_status'
            );


        // =====================================================
        // FIXED STATUS ORDER
        //
        // THIS KEEPS THE CHART ORDER CONSISTENT
        // =====================================================

        $reportStatusChart = [

            'labels' => [

                'Pending',

                'Processing',

                'Resolved',

                'For Replacement',

                'Rejected',

            ],

            'data' => [

                (int) $reportStatusRows->get(
                    'Pending',
                    0
                ),

                (int) $reportStatusRows->get(
                    'Processing',
                    0
                ),

                (int) $reportStatusRows->get(
                    'Resolved',
                    0
                ),

                (int) $reportStatusRows->get(
                    'For Replacement',
                    0
                ),

                (int) $reportStatusRows->get(
                    'Rejected',
                    0
                ),

            ],

        ];


        // =====================================================
        // NEW DASHBOARD DATA
        //
        // TOP 5 LOCATIONS WITH THE MOST REPORTS
        // =====================================================

        $reportsByLocation = DB::table('reports_table')

            ->join(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->where(
                'reports_table.report_is_archived',
                false
            )

            ->select(
                'rooms_table.room_id',

                'rooms_table.room_name'
            )

            ->selectRaw(
                'COUNT(reports_table.report_id) AS report_count'
            )

            ->groupBy(
                'rooms_table.room_id',

                'rooms_table.room_name'
            )

            ->orderByDesc(
                'report_count'
            )

            ->limit(5)

            ->get();


        // =====================================================
        // NEW DASHBOARD DATA
        //
        // RESOLUTION RATE
        //
        // COMPLETED REPORTS ARE:
        //
        // RESOLVED
        // REJECTED
        // FOR REPLACEMENT
        //
        // RESOLUTION RATE =
        //
        // RESOLVED / COMPLETED REPORTS * 100
        // =====================================================

        $resolvedReportCount =
            DB::table('reports_table')

                ->where(
                    'report_current_status',
                    'Resolved'
                )

                ->count();


        $completedReportCount =
            DB::table('reports_table')

                ->whereIn(
                    'report_current_status',
                    [
                        'Resolved',
                        'Rejected',
                        'For Replacement',
                    ]
                )

                ->count();


        $resolutionRate =
            $completedReportCount > 0

                ? round(

                    (
                        $resolvedReportCount
                        /
                        $completedReportCount
                    )

                    * 100

                )

                : 0;


        // =====================================================
        // NEW DASHBOARD DATA
        //
        // LOCATION HEALTH SUMMARY
        // =====================================================

        $locationHealth = [

            'normal' =>

                $rooms
                    ->where(
                        'dashboard_status',
                        'available'
                    )
                    ->count(),


            'maintenance_needed' =>

                $rooms
                    ->whereIn(
                        'dashboard_status',
                        [
                            'needs-repair',
                            'maintenance',
                        ]
                    )
                    ->count(),


            'critical' =>

                $rooms
                    ->where(
                        'dashboard_status',
                        'critical'
                    )
                    ->count(),

        ];


        // =====================================================
        // NEW DASHBOARD DATA
        //
        // MOST REPORTED LOCATION
        //
        // REUSE TOP LOCATION QUERY RESULT
        // =====================================================

        $mostReportedLocation =
            $reportsByLocation->first();


        // =====================================================
        // RECENT ACTIVITIES
        // LATEST 5 RECORDS
        // =====================================================

        $recentActivities = DB::table('reports_table')

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
                'reports_table.report_is_archived',
                false
            )

            ->select(
                'reports_table.report_id',

                'reports_table.report_current_status',

                'reports_table.report_urgency_level',

                'reports_table.report_problem_description',

                'reports_table.report_submitted_at',

                'reports_table.report_updated_at',

                'rooms_table.room_name',

                'equipment_table.equipment_name'
            )

            ->orderBy(
                'reports_table.report_updated_at',
                'desc'
            )

            ->limit(5)

            ->get()

            ->map(function ($report) {


                // =================================================
                // DEFAULT ACTIVITY
                // =================================================

                $title =
                    'Report Submitted';


                $icon =
                    'file-plus';


                $background =
                    '#dbeafe';


                $color =
                    '#0037c7';


                $activityDate =
                    $report->report_submitted_at;


                // =================================================
                // URGENT PENDING REPORT
                // =================================================

                if (

                    $report->report_urgency_level
                    ===
                    'Urgent'

                    &&

                    $report->report_current_status
                    ===
                    'Pending'

                ) {

                    $title =
                        'Urgent Report Submitted';


                    $icon =
                        'alert-triangle';


                    $background =
                        '#fee2e2';


                    $color =
                        '#dc2626';

                }


                // =================================================
                // PROCESSING
                // =================================================

                if (
                    $report->report_current_status
                    ===
                    'Processing'
                ) {

                    $title =
                        'Report Processing Started';


                    $icon =
                        'wrench';


                    $background =
                        '#fef3c7';


                    $color =
                        '#d97706';


                    $activityDate =
                        $report->report_updated_at;

                }


                // =================================================
                // RESOLVED
                // =================================================

                if (
                    $report->report_current_status
                    ===
                    'Resolved'
                ) {

                    $title =
                        'Report Resolved';


                    $icon =
                        'check-circle';


                    $background =
                        '#d1fae5';


                    $color =
                        '#16a34a';


                    $activityDate =
                        $report->report_updated_at;

                }


                // =================================================
                // FOR REPLACEMENT
                // =================================================

                if (
                    $report->report_current_status
                    ===
                    'For Replacement'
                ) {

                    $title =
                        'Replacement Requested';


                    $icon =
                        'package-search';


                    $background =
                        '#fef3c7';


                    $color =
                        '#d97706';


                    $activityDate =
                        $report->report_updated_at;

                }


                // =================================================
                // REJECTED
                // =================================================

                if (
                    $report->report_current_status
                    ===
                    'Rejected'
                ) {

                    $title =
                        'Report Rejected';


                    $icon =
                        'x-circle';


                    $background =
                        '#fee2e2';


                    $color =
                        '#dc2626';


                    $activityDate =
                        $report->report_updated_at;

                }


                // =================================================
                // ACTIVITY DESCRIPTION
                // =================================================

                $equipmentName =
                    $report->equipment_name
                    ?? 'Unlisted Equipment';


                $roomName =
                    $report->room_name
                    ?? 'Unknown Room';


                $description =
                    $equipmentName
                    . ' at '
                    . $roomName;


                return (object) [

                    'report_id' =>
                        $report->report_id,

                    'title' =>
                        $title,

                    'description' =>
                        $description,

                    'icon' =>
                        $icon,

                    'background' =>
                        $background,

                    'color' =>
                        $color,

                    'created_at' =>
                        $activityDate,

                ];

            })

            ->sortByDesc(
                'created_at'
            )

            ->values();

        // =====================================================
        // CURRENT CALENDAR MONTH
        // =====================================================

        $calendarStartDate = now()
            ->copy()
            ->startOfMonth()
            ->startOfDay();


        $calendarEndDate = now()
            ->copy()
            ->endOfMonth()
            ->endOfDay();


        // =====================================================
        // REPORTS FOR CURRENT MONTH
        // =====================================================

        $calendarReports = DB::table('reports_table')

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

            ->whereBetween(
                'reports_table.report_submitted_at',
                [
                    $calendarStartDate,
                    $calendarEndDate,
                ]
            )

            ->where(
                'reports_table.report_is_archived',
                false
            )

            ->select(

                'reports_table.report_id',

                'reports_table.report_submitted_at',

                'reports_table.report_urgency_level',

                'reports_table.report_current_status',

                'reports_table.report_unlisted_equipment_name',

                'rooms_table.room_name',

                'equipment_table.equipment_name'

            )

            ->orderBy(
                'reports_table.report_submitted_at',
                'asc'
            )

            ->get();


        // =====================================================
        // MAINTENANCE SCHEDULES FOR CURRENT MONTH
        // =====================================================

        $calendarSchedules = DB::table('maintenance_schedules_table')

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

            ->whereNotNull(
                'maintenance_schedules_table.maintenance_schedule_next_date'
            )

            ->whereBetween(
                'maintenance_schedules_table.maintenance_schedule_next_date',
                [
                    $calendarStartDate->toDateString(),
                    $calendarEndDate->toDateString(),
                ]
            )

            ->select(

                'maintenance_schedules_table.maintenance_schedule_id',

                'maintenance_schedules_table.maintenance_schedule_title',

                'maintenance_schedules_table.maintenance_schedule_description',

                'maintenance_schedules_table.maintenance_schedule_frequency',

                'maintenance_schedules_table.maintenance_schedule_next_date',

                'maintenance_schedules_table.maintenance_schedule_last_date',

                'maintenance_schedules_table.maintenance_schedule_status',

                'equipment_table.equipment_name',

                'rooms_table.room_name'

            )

            ->orderBy(
                'maintenance_schedules_table.maintenance_schedule_next_date',
                'asc'
            )

            ->get();


        // =====================================================
        // BUILD CALENDAR EVENTS
        //
        // ONE COLLECTION USED BY THE BLADE CALENDAR
        // =====================================================

        $calendarEvents = collect();


        // =====================================================
        // ADD REPORT EVENTS
        // =====================================================

        foreach ($calendarReports as $report) {

            $equipmentName =
                $report->equipment_name
                ??
                $report->report_unlisted_equipment_name
                ??
                'Unlisted Equipment';


            $calendarEvents->push([

                'id' =>
                    $report->report_id,

                'type' =>
                    'report',

                'date' =>
                    \Carbon\Carbon::parse(
                        $report->report_submitted_at
                    )->format('Y-m-d'),

                'time' =>
                    \Carbon\Carbon::parse(
                        $report->report_submitted_at
                    )->format('H:i'),

                'title' =>
                    $report->report_urgency_level === 'Urgent'
                        ? 'Urgent Report'
                        : 'Report Submitted',

                'description' =>
                    $equipmentName,

                'location' =>
                    $report->room_name
                    ?? 'Unknown Room',

                'urgent' =>
                    $report->report_urgency_level === 'Urgent',

                'status' =>
                    $report->report_current_status,

                'url' =>
                    url(
                        '/maintenance/reports/details/'
                        .
                        $report->report_id
                    ),

            ]);

        }


        // =====================================================
        // ADD SCHEDULE EVENTS
        // =====================================================

        foreach ($calendarSchedules as $schedule) {

            $scheduleDate = \Carbon\Carbon::parse(
                $schedule->maintenance_schedule_next_date
            );


            $calendarEvents->push([

                'id' =>
                    $schedule->maintenance_schedule_id,

                'type' =>
                    'schedule',

                'date' =>
                    $scheduleDate->format('Y-m-d'),


                // =================================================
                // YOUR DATABASE STORES ONLY A DATE, NOT A TIME
                // =================================================

                'time' =>
                    null,


                // =================================================
                // USE THE REAL SCHEDULE TITLE
                // =================================================

                'title' =>
                    $schedule->maintenance_schedule_title
                    ?? 'Maintenance Schedule',


                // =================================================
                // SHOW EQUIPMENT NAME AS THE MAIN DESCRIPTION
                // =================================================

                'description' =>
                    $schedule->equipment_name
                    ?? $schedule->maintenance_schedule_description
                    ?? 'Scheduled Maintenance',


                'location' =>
                    $schedule->room_name
                    ?? 'Unknown Room',


                'urgent' =>
                    false,


                'status' =>
                    $schedule->maintenance_schedule_status,


                'frequency' =>
                    $schedule->maintenance_schedule_frequency,


                // =================================================
                // TEMPORARY URL
                //
                // CHANGE THIS LATER IF YOUR REAL SCHEDULE DETAILS
                // ROUTE USES A DIFFERENT URL
                // =================================================

                'url' =>
                    url(
                        '/maintenance/schedules/'
                        .
                        $schedule->maintenance_schedule_id
                    ),

            ]);

        }


        // =====================================================
        // SORT CALENDAR EVENTS BY DATE AND TIME
        // =====================================================

        $calendarEvents = $calendarEvents
            ->sortBy(function ($event) {

                return
                    $event['date']
                    .
                    ' '
                    .
                    ($event['time'] ?? '00:00');

            })
            ->values();

        // =====================================================
        // GROUP EVENTS BY DATE
        //
        // EXAMPLE:
        //
        // 2026-07-14 => [
        //     REPORT,
        //     REPORT,
        //     SCHEDULE,
        // ]
        // =====================================================

        $calendarEventsByDate =
            $calendarEvents->groupBy('date');


        // =====================================================
        // CURRENT MONTH CALENDAR TOTALS
        // =====================================================

        $calendarReportCount =
            $calendarEvents
                ->where('type', 'report')
                ->count();


        $calendarScheduleCount =
            $calendarEvents
                ->where('type', 'schedule')
                ->count();


        $calendarUrgentCount =
            $calendarEvents
                ->where('type', 'report')
                ->where('urgent', true)
                ->count();


        // =====================================================
        // DEFAULT SELECTED DATE
        //
        // TODAY IF TODAY IS INSIDE CURRENT CALENDAR MONTH
        // =====================================================

        $calendarSelectedDate =
            now()->format('Y-m-d');


        // =====================================================
        // PRESERVE YOUR EXISTING CURRENT MONTH ACTIVITY CHART
        //
        // WE WILL REMOVE IT FROM THE NEW BLADE LATER,
        // BUT KEEPING THE VARIABLE PREVENTS EXISTING BLADE ERRORS
        // WHILE YOU ARE BETWEEN PART 1 AND PART 2.
        // =====================================================

        $currentMonthStart =
            now()->startOfMonth();


        $currentMonthEnd =
            now()->endOfMonth();


        $monthlyReportDates =
            DB::table('reports_table')

                ->whereBetween(
                    'report_submitted_at',
                    [
                        $currentMonthStart,
                        $currentMonthEnd,
                    ]
                )

                ->pluck(
                    'report_submitted_at'
                );


        $reportActivityChart = [

            $monthlyReportDates
                ->filter(function ($date) {

                    $day =
                        \Carbon\Carbon::parse($date)->day;


                    return
                        $day >= 1
                        &&
                        $day <= 7;

                })
                ->count(),


            $monthlyReportDates
                ->filter(function ($date) {

                    $day =
                        \Carbon\Carbon::parse($date)->day;


                    return
                        $day >= 8
                        &&
                        $day <= 14;

                })
                ->count(),


            $monthlyReportDates
                ->filter(function ($date) {

                    $day =
                        \Carbon\Carbon::parse($date)->day;


                    return
                        $day >= 15
                        &&
                        $day <= 21;

                })
                ->count(),


            $monthlyReportDates
                ->filter(function ($date) {

                    $day =
                        \Carbon\Carbon::parse($date)->day;


                    return
                        $day >= 22
                        &&
                        $day <= 28;

                })
                ->count(),


            $monthlyReportDates
                ->filter(function ($date) {

                    $day =
                        \Carbon\Carbon::parse($date)->day;


                    return
                        $day >= 29;

                })
                ->count(),

        ];

        // =====================================================
        // EQUIPMENT CONDITION CHART
        //
        // CURRENT SNAPSHOT OF ALL REGISTERED EQUIPMENT.
        //
        // NO MONTH FILTER.
        // NO YEAR FILTER.
        // NO DROPDOWN.
        //
        // THE CHART AUTOMATICALLY UPDATES WHEN AN
        // EQUIPMENT CONDITION CHANGES.
        // =====================================================

        $equipmentConditionRows =
            DB::table('equipment_table')

                ->select(
                    'equipment_condition_status'
                )

                ->selectRaw(
                    'COUNT(*) AS condition_count'
                )

                // =================================================
                // ONLY INCLUDE EQUIPMENT WITH A CONDITION
                // =================================================

                ->whereNotNull(
                    'equipment_condition_status'
                )

                // =================================================
                // GROUP EQUIPMENT BY CURRENT CONDITION
                // =================================================

                ->groupBy(
                    'equipment_condition_status'
                )

                ->pluck(
                    'condition_count',
                    'equipment_condition_status'
                );


        // =====================================================
        // EQUIPMENT CONDITION CHART DATA
        //
        // FIXED ORDER IS IMPORTANT FOR YOUR CONCENTRIC
        // BUBBLE CHART DESIGN.
        //
        // THE JAVASCRIPT SORTS THESE FROM HIGHEST TO LOWEST
        // BEFORE DRAWING THE CIRCLES.
        // =====================================================

        $equipmentConditionChart = [

            'labels' => [

                'Good',

                'Damaged',

                'Under Maintenance',

                'Disposed',

            ],


            'data' => [

                (int) $equipmentConditionRows->get(
                    'Good',
                    0
                ),

                (int) $equipmentConditionRows->get(
                    'Damaged',
                    0
                ),

                (int) $equipmentConditionRows->get(
                    'Under Maintenance',
                    0
                ),

                (int) $equipmentConditionRows->get(
                    'Disposed',
                    0
                ),

            ],

        ];

        // =====================================================
        // MAINTENANCE SCHEDULE WORKLOAD
        //
        // CURRENT PERIOD:
        // NEXT 30 DAYS INCLUDING TODAY
        //
        // PREVIOUS PERIOD:
        // PREVIOUS 30 DAYS BEFORE TODAY
        //
        // BOTH DATASETS USE THE SAME 30 X AXIS POSITIONS
        // SO THEY CAN BE COMPARED IN THE LINE CHART.
        // =====================================================


        // =====================================================
        // CURRENT PERIOD DATE RANGE
        // TODAY UNTIL 29 DAYS FROM TODAY
        // =====================================================

        $maintenanceWorkloadStartDate =
            now()
                ->copy()
                ->startOfDay();


        $maintenanceWorkloadEndDate =
            now()
                ->copy()
                ->addDays(29)
                ->endOfDay();


        // =====================================================
        // PREVIOUS PERIOD DATE RANGE
        // 30 DAYS BEFORE TODAY UNTIL YESTERDAY
        // =====================================================

        $maintenancePreviousWorkloadStartDate =
            now()
                ->copy()
                ->subDays(30)
                ->startOfDay();


        $maintenancePreviousWorkloadEndDate =
            now()
                ->copy()
                ->subDay()
                ->endOfDay();


        // =====================================================
        // CURRENT PERIOD QUERY
        //
        // GET ACTIVE MAINTENANCE SCHEDULES FOR NEXT 30 DAYS
        // =====================================================

        $maintenanceWorkloadRows =

            DB::table(
                'maintenance_schedules_table'
            )

                ->selectRaw(
                    '
                    maintenance_schedule_next_date AS schedule_date,
                    COUNT(*) AS schedule_count
                    '
                )

                ->whereNotNull(
                    'maintenance_schedule_next_date'
                )

                ->where(
                    'maintenance_schedule_status',
                    'Active'
                )

                ->whereBetween(

                    'maintenance_schedule_next_date',

                    [

                        $maintenanceWorkloadStartDate
                            ->toDateString(),

                        $maintenanceWorkloadEndDate
                            ->toDateString(),

                    ]

                )

                ->groupBy(
                    'maintenance_schedule_next_date'
                )

                ->orderBy(
                    'maintenance_schedule_next_date'
                )

                ->get()

                ->keyBy(
                    'schedule_date'
                );


        // =====================================================
        // PREVIOUS PERIOD QUERY
        //
        // GET MAINTENANCE SCHEDULE COUNTS FROM PREVIOUS 30 DAYS
        //
        // DO NOT FILTER ONLY ACTIVE HERE.
        //
        // WHY:
        //
        // A SCHEDULE FROM THE PREVIOUS PERIOD MAY NOW BE:
        // COMPLETED
        // OVERDUE
        // ACTIVE
        //
        // FILTERING ONLY ACTIVE WOULD REMOVE VALID HISTORICAL DATA.
        // =====================================================

        $maintenancePreviousWorkloadRows =

            DB::table(
                'maintenance_schedules_table'
            )

                ->selectRaw(
                    '
                    maintenance_schedule_next_date AS schedule_date,
                    COUNT(*) AS schedule_count
                    '
                )

                ->whereNotNull(
                    'maintenance_schedule_next_date'
                )

                ->whereBetween(

                    'maintenance_schedule_next_date',

                    [

                        $maintenancePreviousWorkloadStartDate
                            ->toDateString(),

                        $maintenancePreviousWorkloadEndDate
                            ->toDateString(),

                    ]

                )

                ->groupBy(
                    'maintenance_schedule_next_date'
                )

                ->orderBy(
                    'maintenance_schedule_next_date'
                )

                ->get()

                ->keyBy(
                    'schedule_date'
                );


        // =====================================================
        // BUILD CHART DATA
        //
        // BOTH ARRAYS MUST CONTAIN EXACTLY 30 VALUES.
        //
        // CURRENT:
        // INDEX 0 = TODAY
        // INDEX 29 = 29 DAYS FROM TODAY
        //
        // PREVIOUS:
        // INDEX 0 = 30 DAYS AGO
        // INDEX 29 = YESTERDAY
        // =====================================================

        $maintenanceWorkloadLabels = [];

        $maintenanceWorkloadData = [];

        $maintenancePreviousWorkloadData = [];


        for ($i = 0; $i < 30; $i++) {


            // =================================================
            // CURRENT PERIOD DATE
            // =================================================

            $currentDate =

                now()
                    ->copy()
                    ->addDays($i);


            $currentDatabaseDate =

                $currentDate
                    ->format('Y-m-d');


            // =================================================
            // PREVIOUS PERIOD DATE
            //
            // INDEX 0  = 30 DAYS AGO
            // INDEX 29 = YESTERDAY
            // =================================================

            $previousDate =

                now()
                    ->copy()
                    ->subDays(30)
                    ->addDays($i);


            $previousDatabaseDate =

                $previousDate
                    ->format('Y-m-d');


            // =================================================
            // X AXIS LABEL
            //
            // LABELS DISPLAY THE CURRENT PERIOD DATES.
            // =================================================

            $maintenanceWorkloadLabels[] =

                $currentDate
                    ->format('M j');


            // =================================================
            // CURRENT PERIOD VALUE
            // =================================================

            $maintenanceWorkloadData[] =

                (int) (

                    optional(

                        $maintenanceWorkloadRows
                            ->get($currentDatabaseDate)

                    )->schedule_count

                    ?? 0

                );


            // =================================================
            // PREVIOUS PERIOD VALUE
            // =================================================

            $maintenancePreviousWorkloadData[] =

                (int) (

                    optional(

                        $maintenancePreviousWorkloadRows
                            ->get($previousDatabaseDate)

                    )->schedule_count

                    ?? 0

                );

        }

        // =====================================================
        // MINI DASHBOARD CHARTS
        // LAST 7 DAYS
        // =====================================================

        $miniChartStartDate = now()
            ->copy()
            ->subDays(6)
            ->startOfDay();

        $miniChartEndDate = now()
            ->copy()
            ->endOfDay();


        // =====================================================
        // URGENT REPORTS
        // =====================================================

        $urgentChartRows = DB::table('reports_table')

            ->selectRaw('
                DATE(report_submitted_at) AS chart_date,
                COUNT(*) AS chart_count
            ')

            ->whereBetween(
                'report_submitted_at',
                [
                    $miniChartStartDate,
                    $miniChartEndDate,
                ]
            )

            ->where(
                'report_urgency_level',
                'Urgent'
            )

            ->whereNotIn(
                'report_current_status',
                [
                    'Resolved',
                    'Rejected',
                    'For Replacement',
                ]
            )

            ->groupByRaw(
                'DATE(report_submitted_at)'
            )

            ->orderByRaw(
                'DATE(report_submitted_at)'
            )

            ->get()

            ->keyBy('chart_date');


        // =====================================================
        // MAINTENANCE HISTORY
        // =====================================================

        $maintenanceChartRows = DB::table(
            'equipment_maintenance_history_table'
        )

            ->selectRaw('
                DATE(equipment_maintenance_created_at) AS chart_date,
                COUNT(*) AS chart_count
            ')

            ->whereBetween(
                'equipment_maintenance_created_at',
                [
                    $miniChartStartDate,
                    $miniChartEndDate,
                ]
            )

            ->groupByRaw(
                'DATE(equipment_maintenance_created_at)'
            )

            ->orderByRaw(
                'DATE(equipment_maintenance_created_at)'
            )

            ->get()

            ->keyBy('chart_date');


        // =====================================================
        // BORROWING HISTORY
        // =====================================================

        $borrowedChartRows = DB::table(
            'borrowing_records_table'
        )

            ->selectRaw('
                borrowing_date AS chart_date,
                COUNT(*) AS chart_count
            ')

            ->whereBetween(
                'borrowing_date',
                [
                    $miniChartStartDate->toDateString(),
                    $miniChartEndDate->toDateString(),
                ]
            )

            ->groupBy(
                'borrowing_date'
            )

            ->orderBy(
                'borrowing_date'
            )

            ->get()

            ->keyBy('chart_date');


        // =====================================================
        // BUILD COMPLETE 7 DAY ARRAYS
        // =====================================================

        $miniChartLabels = [];

        $urgentChartData = [];

        $maintenanceChartData = [];

        $borrowedChartData = [];


        for ($i = 6; $i >= 0; $i--) {

            $date = now()
                ->copy()
                ->subDays($i);

            $databaseDate =
                $date->format('Y-m-d');

            $miniChartLabels[] =
                $date->format('D');


            $urgentChartData[] =

                (int)(

                    optional(

                        $urgentChartRows
                            ->get($databaseDate)

                    )->chart_count

                    ?? 0

                );


            $maintenanceChartData[] =

                (int)(

                    optional(

                        $maintenanceChartRows
                            ->get($databaseDate)

                    )->chart_count

                    ?? 0

                );


            $borrowedChartData[] =

                (int)(

                    optional(

                        $borrowedChartRows
                            ->get($databaseDate)

                    )->chart_count

                    ?? 0

                );

        }


        // =====================================================
        // RETURN DASHBOARD VIEW
        // =====================================================

        return view(

            'maintenance-personnel.dashboard',

            compact(

                // =================================================
                // USER
                // =================================================

                'user',


                // =================================================
                // EXISTING STATISTICS
                // =================================================

                'pendingReports',

                'pendingReportsToday',

                'urgentReports',

                'underMaintenance',

                'borrowedEquipment',

                'overdueMaintenance',


                // =================================================
                // EXISTING DASHBOARD DATA
                // =================================================

                'urgentReportList',

                'buildings',

                'floors',

                'roomsByFloor',

                'recentActivities',

                'reportActivityChart',


                // =================================================
                // NEW DASHBOARD DATA
                // =================================================

                'reportTrendLabels',

                'reportTrendData',

                'reportStatusChart',

                'reportsByLocation',

                'resolutionRate',

                'locationHealth',

                'mostReportedLocation',

                'calendarEvents',

                'calendarEventsByDate',

                'calendarReportCount',

                'calendarScheduleCount',

                'calendarUrgentCount',

                'calendarSelectedDate',

                'equipmentConditionChart',

                'activeReportTrendLabels',

                'activeReportTrendData',

                'maintenanceWorkloadLabels',

                'maintenanceWorkloadData',

                'maintenancePreviousWorkloadData',

                'miniChartLabels',

                'urgentChartData',

                'maintenanceChartData',

                'borrowedChartData',

            )

        );
    }

    // =====================================================
    // REPLACE YOUR CURRENT dashboard() METHOD
    // END HERE
    // =====================================================

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

            // =====================================================
            // JOIN ASSIGNED MAINTENANCE PERSONNEL HERE
            // =====================================================

            ->leftJoin(
                'users_table as assigned_personnel',
                'reports_table.report_assigned_personnel_id',
                '=',
                'assigned_personnel.user_id'
            )


            // =====================================================
            // JOIN ASSIGNED PURCHASER HERE
            // =====================================================

            ->leftJoin(
                'users_table as assigned_purchaser',
                'reports_table.report_assigned_purchaser_id',
                '=',
                'assigned_purchaser.user_id'
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

                'reporters_table.reporter_employee_id',

                // =====================================================
                // ASSIGNED MAINTENANCE PERSONNEL NAME HERE
                // =====================================================

                'assigned_personnel.user_full_name
                    as assigned_personnel_name',


                // =====================================================
                // ASSIGNED PURCHASER NAME HERE
                // =====================================================

                'assigned_purchaser.user_full_name
                    as assigned_purchaser_name',

            );
    }


    // =====================================================
    // REUSABLE EQUIPMENT DASHBOARD DATA
    // USED BY ALL REPORT PAGES
    // =====================================================

    // =====================================================
    // REUSABLE REPORT DASHBOARD DATA
    // USED BY ALL REPORT PAGES
    // =====================================================

    private function reportDashboardData()
    {
        // =====================================================
        // TOTAL REPORTS
        // =====================================================

        $totalReports = DB::table('reports_table')
            ->count();


        // =====================================================
        // PENDING REPORTS
        // =====================================================

        $pendingReports = DB::table('reports_table')

            ->where(
                'report_current_status',
                'Pending'
            )

            ->count();


        // =====================================================
        // PROCESSING REPORTS
        // =====================================================

        $processingReports = DB::table('reports_table')

            ->where(
                'report_current_status',
                'Processing'
            )

            ->count();


        // =====================================================
        // RESOLVED REPORTS
        // =====================================================

        $resolvedReports = DB::table('reports_table')

            ->where(
                'report_current_status',
                'Resolved'
            )

            ->count();

        // =====================================================
        // REPORT STATUS MONTHLY PERCENTAGE HELPER
        // =====================================================

        $calculateStatusMonthlyPercentage = function ($status) {

            // =====================================================
            // CURRENT MONTH COUNT
            // =====================================================

            $currentMonthCount = DB::table('reports_table')

                ->where(
                    'report_current_status',
                    $status
                )

                ->whereBetween(
                    'report_submitted_at',
                    [
                        now()->copy()->startOfMonth(),
                        now()->copy()->endOfMonth(),
                    ]
                )

                ->count();


            // =====================================================
            // PREVIOUS MONTH COUNT
            // =====================================================

            $previousMonthCount = DB::table('reports_table')

                ->where(
                    'report_current_status',
                    $status
                )

                ->whereBetween(
                    'report_submitted_at',
                    [
                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->endOfMonth(),
                    ]
                )

                ->count();


            // =====================================================
            // CALCULATE PERCENTAGE CHANGE
            // =====================================================

            if ($previousMonthCount > 0) {

                return
                    (
                        (
                            $currentMonthCount
                            - $previousMonthCount
                        )
                        / $previousMonthCount
                    )
                    * 100;

            }


            // =====================================================
            // PREVIOUS MONTH = 0
            // CURRENT MONTH HAS RECORDS
            // =====================================================

            if ($currentMonthCount > 0) {

                return null;

            }


            // =====================================================
            // BOTH MONTHS = 0
            // =====================================================

            return 0;
        };


        // =====================================================
        // CALCULATE EACH STATUS PERCENTAGE
        // =====================================================

        $pendingMonthlyPercentage =
            $calculateStatusMonthlyPercentage('Pending');


        $processingMonthlyPercentage =
            $calculateStatusMonthlyPercentage('Processing');


        $resolvedMonthlyPercentage =
            $calculateStatusMonthlyPercentage('Resolved');


        // =====================================================
        // CURRENT MONTH REPORTS
        // =====================================================

        $currentMonthReports = DB::table('reports_table')

            ->whereBetween(
                'report_submitted_at',
                [
                    now()->copy()->startOfMonth(),
                    now()->copy()->endOfMonth(),
                ]
            )

            ->count();


        // =====================================================
        // PREVIOUS MONTH REPORTS
        // =====================================================

        $previousMonthReports = DB::table('reports_table')

            ->whereBetween(
                'report_submitted_at',
                [
                    now()
                        ->copy()
                        ->subMonthNoOverflow()
                        ->startOfMonth(),

                    now()
                        ->copy()
                        ->subMonthNoOverflow()
                        ->endOfMonth(),
                ]
            )

            ->count();


        // =====================================================
        // REPORT MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthReports > 0) {

            $reportMonthlyPercentage =
                (
                    (
                        $currentMonthReports
                        - $previousMonthReports
                    )
                    / $previousMonthReports
                )
                * 100;

        } elseif ($currentMonthReports > 0) {

            $reportMonthlyPercentage = null;

        } else {

            $reportMonthlyPercentage = 0;

        }


        // =====================================================
        // REPORTS PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyReportRows = DB::table('reports_table')

            ->selectRaw(
                '
                YEAR(report_submitted_at) AS report_year,
                MONTH(report_submitted_at) AS report_month,
                COUNT(*) AS report_count
                '
            )

            ->where(
                'report_submitted_at',
                '>=',
                now()
                    ->copy()
                    ->subMonths(11)
                    ->startOfMonth()
            )

            ->groupByRaw(
                '
                YEAR(report_submitted_at),
                MONTH(report_submitted_at)
                '
            )

            ->orderByRaw(
                '
                YEAR(report_submitted_at),
                MONTH(report_submitted_at)
                '
            )

            ->get()

            ->keyBy(function ($row) {

                return
                    $row->report_year
                    . '-'
                    . str_pad(
                        $row->report_month,
                        2,
                        '0',
                        STR_PAD_LEFT
                    );

            });


        // =====================================================
        // BUILD ALL 12 MONTHS
        // FILL MISSING MONTHS WITH ZERO
        // =====================================================

        $reportMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $reportMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyReportRows
                            ->get($key)
                            ->report_count
                        ?? 0
                    ),

            ]);

        }


        // =====================================================
        // RETURN REPORT DASHBOARD VARIABLES
        // =====================================================

        return compact(
            'totalReports',
            'pendingReports',
            'processingReports',
            'resolvedReports',
            'reportMonthlyPercentage',
            'reportMonthlyTrend',
            'pendingMonthlyPercentage',

            'processingMonthlyPercentage',

            'resolvedMonthlyPercentage'
        );
    }


    

    

    public function allReports()
    {
        $reports = $this->reportsQuery()
            ->paginate(10)
            ->withQueryString();

        return $this->reportsView($reports);
    }

    /*
    |--------------------------------------------------------------------------
    | INCOMING REPORTS E - 1
    |--------------------------------------------------------------------------
    */

    public function incomingReports()
    {
        $reports = $this->reportsQuery()

            ->paginate(10)

            ->withQueryString();

        return $this->reportsView($reports);
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

        return $this->reportsView($reports);
    }

    // =====================================================
    // TODAY'S REPORTS
    // =====================================================

    public function todayReports()
    {
        // =====================================================
        // GET REPORTS SUBMITTED TODAY
        // =====================================================

        $reports = $this->reportsQuery()

            ->whereDate(
                'reports_table.report_submitted_at',
                today()
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // USE YOUR EXISTING REPORTS PAGE
        // =====================================================

        return $this->reportsView($reports);
    }

    // =====================================================
    // RETURN REPORTS PAGE WITH SHARED DASHBOARD DATA
    // =====================================================

    private function reportsView($reports)
    {
        return view(
            'maintenance-personnel.reports.all-reports',

            array_merge(
                [
                    'reports' => $reports,
                ],

                // =====================================================
                // ADD SHARED REPORT DASHBOARD DATA HERE
                // =====================================================

                $this->reportDashboardData()
            )
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

        return $this->reportsView($reports);
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

        return $this->reportsView($reports);
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

        return $this->reportsView($reports);
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

        return $this->reportsView($reports);
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

        return $this->reportsView($reports);
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

    // =====================================================
    // ASSIGN REPORT TO MAINTENANCE PERSONNEL
    // =====================================================

    public function assignReport(Request $request, $id)
    {
        // =====================================================
        // VALIDATE PERSONNEL ID HERE
        // =====================================================

        $request->validate([

            'personnel_id' =>
                'required|integer|exists:users_table,user_id',

        ]);


        // =====================================================
        // DATABASE TRANSACTION HERE
        // =====================================================

        return DB::transaction(function () use (
            $request,
            $id
        ) {


            // =================================================
            // LOCK REPORT ROW HERE
            // =================================================

            $report = DB::table('reports_table')

                ->where(
                    'report_id',
                    $id
                )

                ->lockForUpdate()

                ->first();


            // =================================================
            // REPORT MUST EXIST
            // =================================================

            if (!$report) {

                return back()->with(
                    'error',
                    'Report not found.'
                );

            }


            // =================================================
            // REPORT MUST NOT BE ARCHIVED
            // =================================================

            if ($report->report_is_archived) {

                return back()->with(
                    'error',
                    'Archived reports cannot be assigned.'
                );

            }


            // =================================================
            // REPORT MUST STILL BE PENDING
            // =================================================

            if (
                $report->report_current_status
                !==
                'Pending'
            ) {

                return back()->with(
                    'error',
                    'Only pending reports can be assigned.'
                );

            }


            // =================================================
            // PURCHASER MUST NOT ALREADY OWN REPORT
            // =================================================

            if (
                $report->report_assigned_purchaser_id
                !==
                null
            ) {

                return back()->with(
                    'error',
                    'The Purchaser is already handling this urgent report.'
                );

            }


            // =================================================
            // MAINTENANCE PERSONNEL MUST NOT ALREADY BE ASSIGNED
            // =================================================

            if (
                $report->report_assigned_personnel_id
                !==
                null
            ) {

                return back()->with(
                    'error',
                    'Another maintenance personnel is already assigned to this report.'
                );

            }


            // =================================================
            // MAKE SURE SELECTED USER IS MAINTENANCE PERSONNEL
            //
            // YOUR CURRENT CONTROLLER USES ROLE ID 2
            // FOR MAINTENANCE PERSONNEL.
            // =================================================

            $validPersonnel = DB::table('users_table')

                ->where(
                    'user_id',
                    $request->personnel_id
                )

                ->where(
                    'user_role_id',
                    2
                )

                ->exists();


            if (!$validPersonnel) {

                return back()->with(
                    'error',
                    'Selected user is not a maintenance personnel.'
                );

            }


            // =================================================
            // ASSIGN REPORT HERE
            // =================================================

            DB::table('reports_table')

                ->where(
                    'report_id',
                    $id
                )

                ->update([

                    'report_assigned_personnel_id' =>
                        $request->personnel_id,

                    'report_current_status' =>
                        'Processing',

                    'report_updated_at' =>
                        now(),

                ]);


            // =================================================
            // RETURN REPORT DETAILS PAGE
            // =================================================

            return redirect()

                ->to(
                    '/maintenance/reports/details/' . $id
                )

                ->with(
                    'success',
                    'Report assigned successfully.'
                );

        });
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

    // =====================================================
    // UPDATE REPORT STATUS
    // MAINTENANCE PERSONNEL
    // =====================================================

    public function updateStatus(Request $request, $id)
    {
        // =====================================================
        // VALIDATE REQUEST HERE
        // =====================================================

        $request->validate([

            'status' =>
                'required|in:Processing,Resolved,For Replacement,Rejected',

            'remarks' =>
                'nullable|string',

            'proof_image' =>
                'nullable|image|max:5120',

        ]);


        // =====================================================
        // STORE IMAGE BEFORE DATABASE TRANSACTION
        //
        // FILE STORAGE SHOULD NOT BE DONE WHILE HOLDING
        // A DATABASE ROW LOCK.
        // =====================================================

        $imagePath = null;


        if ($request->hasFile('proof_image')) {

            $imagePath = $request
                ->file('proof_image')
                ->store(
                    'maintenance-proofs',
                    'public'
                );

        }


        // =====================================================
        // CURRENT MAINTENANCE PERSONNEL ID HERE
        // =====================================================

        $personnelId = Auth::id();


        // =====================================================
        // STATUS REQUESTED BY USER HERE
        // =====================================================

        $newStatus = $request->status;


        // =====================================================
        // CHECK IF THIS IS AN UNDO REQUEST HERE
        // =====================================================

        $undoRequested =
            (bool) $request->boolean('undo');


        // =====================================================
        // DATABASE TRANSACTION HERE
        // =====================================================

        return DB::transaction(function () use (

            $request,

            $id,

            $personnelId,

            $newStatus,

            $undoRequested,

            $imagePath

        ) {


            // =================================================
            // LOCK REPORT ROW HERE
            //
            // ONLY ONE REQUEST CAN MODIFY THIS REPORT
            // AT A TIME.
            // =================================================

            $report = DB::table('reports_table')

                ->where(
                    'report_id',
                    $id
                )

                ->lockForUpdate()

                ->first();


            // =================================================
            // STOP IF REPORT DOES NOT EXIST
            // =================================================

            if (!$report) {

                return back()->with(
                    'error',
                    'Report not found.'
                );

            }


            // =================================================
            // BLOCK ARCHIVED REPORTS
            // =================================================

            if ($report->report_is_archived) {

                return back()->with(
                    'error',
                    'Archived reports cannot be updated.'
                );

            }


            // =================================================
            // HANDLE UNDO REQUEST HERE
            //
            // KEEPING YOUR CURRENT UNDO BEHAVIOR.
            //
            // NOTE:
            // THIS SHOULD LATER BE REFACTORED BECAUSE ACCEPTING
            // A STATUS FROM THE REQUEST IS NOT STRONG ENOUGH
            // SERVER SIDE VALIDATION FOR UNDO.
            // =================================================

            // =====================================================
            // HANDLE UNDO REQUEST HERE
            //
            // ONLY ALLOW:
            //
            // PROCESSING -> PENDING
            //
            // THIS IS SAFE BECAUSE START PROCESSING HAS NOT CREATED
            // PROCUREMENT REQUESTS OR RESOLUTION DATA.
            // =====================================================

            if ($undoRequested) {

                // =================================================
                // REPORT MUST CURRENTLY BE PROCESSING
                // =================================================

                if (
                    $report->report_current_status
                    !==
                    'Processing'
                ) {

                    return back()->with(
                        'error',
                        'This report cannot be reverted.'
                    );

                }


                // =================================================
                // REPORT MUST BELONG TO CURRENT MAINTENANCE USER
                // =================================================

                if (
                    (int) $report->report_assigned_personnel_id
                    !==
                    (int) $personnelId
                ) {

                    return back()->with(
                        'error',
                        'You are not assigned to this report.'
                    );

                }


                // =================================================
                // PURCHASER MUST NOT OWN REPORT
                // =================================================

                if (
                    $report->report_assigned_purchaser_id
                    !==
                    null
                ) {

                    return back()->with(
                        'error',
                        'This urgent report is being handled by the Purchaser.'
                    );

                }


                // =================================================
                // RETURN REPORT TO PENDING
                //
                // IMPORTANT:
                // CLEAR MAINTENANCE ASSIGNMENT SO ANOTHER PERSONNEL
                // CAN CLAIM THE REPORT.
                // =================================================

                DB::table('reports_table')

                    ->where(
                        'report_id',
                        $id
                    )

                    ->update([

                        'report_current_status' =>
                            'Pending',

                        'report_assigned_personnel_id' =>
                            null,

                        'report_updated_at' =>
                            now(),

                    ]);


                return back()->with(
                    'success',
                    'Report returned to Pending successfully.'
                );

            }


            // =================================================
            // ALLOWED MAINTENANCE STATUS TRANSITIONS HERE
            // =================================================

            $allowedTransitions = [

                'Pending' => [
                    'Processing',
                    'Rejected',
                ],

                'Processing' => [
                    'Resolved',
                    'For Replacement',
                ],

            ];


            // =================================================
            // VALIDATE STATUS TRANSITION HERE
            // =================================================

            if (

                !isset(
                    $allowedTransitions[
                        $report->report_current_status
                    ]
                )

                ||

                !in_array(

                    $newStatus,

                    $allowedTransitions[
                        $report->report_current_status
                    ],

                    true

                )

            ) {

                return back()->with(
                    'error',
                    'This status cannot be changed to the selected value.'
                );

            }


            // =================================================
            // PENDING REPORT ACTIONS HERE
            // =================================================

            if (
                $report->report_current_status
                ===
                'Pending'
            ) {


                // =================================================
                // PURCHASER ALREADY CLAIMED REPORT
                // =================================================

                if (
                    $report->report_assigned_purchaser_id
                    !==
                    null
                ) {

                    return back()->with(
                        'error',
                        'The Purchaser is already handling this urgent report.'
                    );

                }


                // =================================================
                // ANOTHER MAINTENANCE PERSONNEL ALREADY ASSIGNED
                // =================================================

                if (
                    $report->report_assigned_personnel_id
                    !==
                    null
                ) {

                    return back()->with(
                        'error',
                        'Another maintenance personnel is already assigned to this report.'
                    );

                }


                // =================================================
                // START PROCESSING
                //
                // CLAIM REPORT FOR CURRENT MAINTENANCE PERSONNEL.
                // =================================================

                if (
                    $newStatus
                    ===
                    'Processing'
                ) {

                    DB::table('reports_table')

                        ->where(
                            'report_id',
                            $id
                        )

                        ->update([

                            'report_current_status' =>
                                'Processing',

                            'report_assigned_personnel_id' =>
                                $personnelId,

                            'report_updated_at' =>
                                now(),

                        ]);


                    return back()

                        ->with(
                            'success',
                            'Report is now being processed.'
                        )

                        ->with(
                            'undo_report_id',
                            $id
                        )

                        ->with(
                            'undo_previous_status',
                            $report->report_current_status
                        );

                }


                // =================================================
                // REJECT PENDING REPORT HERE
                //
                // REJECTION DOES NOT ASSIGN THE REPORT.
                // =================================================

                if (
                    $newStatus
                    ===
                    'Rejected'
                ) {

                    DB::table('reports_table')

                        ->where(
                            'report_id',
                            $id
                        )

                        ->update([

                            'report_current_status' =>
                                'Rejected',

                            'report_rejection_notes' =>
                                $request->remarks,

                            'report_updated_at' =>
                                now(),

                        ]);


                    return back()

                        ->with(
                            'success',
                            'Report rejected successfully.'
                        )

                        ->with(
                            'undo_report_id',
                            $id
                        )

                        ->with(
                            'undo_previous_status',
                            $report->report_current_status
                        );

                }

            }


            // =================================================
            // PROCESSING REPORT ACTIONS HERE
            // =================================================

            if (
                $report->report_current_status
                ===
                'Processing'
            ) {


                // =================================================
                // BLOCK MAINTENANCE FROM MODIFYING REPORT
                // CLAIMED BY PURCHASER
                // =================================================

                if (
                    $report->report_assigned_purchaser_id
                    !==
                    null
                ) {

                    return back()->with(
                        'error',
                        'This urgent report is being handled by the Purchaser.'
                    );

                }


                // =================================================
                // ONLY ASSIGNED MAINTENANCE PERSONNEL CAN UPDATE
                // THE PROCESSING REPORT.
                // =================================================

                if (
                    (int) $report->report_assigned_personnel_id
                    !==
                    (int) $personnelId
                ) {

                    return back()->with(
                        'error',
                        'You are not assigned to this report.'
                    );

                }


                // =================================================
                // RESOLVE REPORT HERE
                // =================================================

                if (
                    $newStatus
                    ===
                    'Resolved'
                ) {

                    DB::table('reports_table')

                        ->where(
                            'report_id',
                            $id
                        )

                        ->update([

                            'report_current_status' =>
                                'Resolved',

                            'report_resolution_notes' =>
                                $request->remarks,

                            'report_resolution_image' =>
                                $imagePath,

                            'report_updated_at' =>
                                now(),

                        ]);


                    return back()

                        ->with(
                            'success',
                            'Report resolved successfully.'
                        )

                        ->with(
                            'undo_report_id',
                            $id
                        )

                        ->with(
                            'undo_previous_status',
                            $report->report_current_status
                        );

                }


                // =================================================
                // SEND REPORT FOR REPLACEMENT HERE
                // =================================================

                if (
                    $newStatus
                    ===
                    'For Replacement'
                ) {


                    // =================================================
                    // UPDATE REPORT HERE
                    // =================================================

                    DB::table('reports_table')

                        ->where(
                            'report_id',
                            $id
                        )

                        ->update([

                            'report_current_status' =>
                                'For Replacement',

                            'report_replacement_notes' =>
                                $request->remarks,

                            'report_replacement_image' =>
                                $imagePath,

                            'report_replacement_submitted_to_purchaser' =>
                                1,

                            'report_updated_at' =>
                                now(),

                        ]);


                    // =================================================
                    // CREATE PURCHASER NOTIFICATION HERE
                    // =================================================

                    $existingNotification =
                        DB::table('notifications_table')

                            ->where(
                                'notification_type',
                                'replacement'
                            )

                            ->where(
                                'notification_message',
                                'Report #' . $id . ' requires replacement.'
                            )

                            ->exists();


                    if (!$existingNotification) {

                        DB::table('notifications_table')

                            ->insert([

                                'notification_user_id' =>
                                    3,

                                'notification_title' =>
                                    'Replacement Request',

                                'notification_message' =>
                                    'Report #' . $id . ' requires replacement.',

                                'notification_type' =>
                                    'replacement',

                                'notification_created_at' =>
                                    now(),

                            ]);

                    }


                    // =================================================
                    // CREATE PROCUREMENT REQUEST HERE
                    // =================================================

                    $existingProcurement =
                        DB::table('procurement_requests_table')

                            ->where(
                                'procurement_request_report_id',
                                $id
                            )

                            ->exists();


                    if (!$existingProcurement) {

                        DB::table('procurement_requests_table')

                            ->insert([

                                'procurement_request_report_id' =>
                                    $id,

                                'procurement_request_status' =>
                                    'Pending',

                                'procurement_request_created_by' =>
                                    $personnelId,

                            ]);

                    }


                    return back()

                        ->with(
                            'success',
                            'Report submitted for replacement successfully.'
                        )

                        ->with(
                            'undo_report_id',
                            $id
                        )

                        ->with(
                            'undo_previous_status',
                            $report->report_current_status
                        );

                }

            }


            // =================================================
            // FALLBACK RESPONSE HERE
            // =================================================

            return back()->with(
                'error',
                'Unable to update this report.'
            );

        });
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
    | EQUIPMENT INVENTORY & STATUS E - 2
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


        // =====================================================
        // TOTAL EQUIPMENT
        // =====================================================

        $totalEquipment =
            DB::table('equipment_table')
                ->count();


        // =====================================================
        // ACTIVE EQUIPMENT
        // =====================================================

        $activeEquipment =
            DB::table('equipment_table')

                ->where(
                    'equipment_inventory_status',
                    'Active'
                )

                ->count();


        // =====================================================
        // UNDER MAINTENANCE EQUIPMENT
        // =====================================================

        $underMaintenanceEquipment =
            DB::table('equipment_table')

                ->where(
                    'equipment_inventory_status',
                    'Under Maintenance'
                )

                ->count();


        // =====================================================
        // BORROWED EQUIPMENT
        // =====================================================

        $borrowedEquipment =
            DB::table('equipment_table')

                ->where(
                    'equipment_inventory_status',
                    'Borrowed'
                )

                ->count();


        // =====================================================
        // DISPOSED EQUIPMENT
        // =====================================================

        $disposedEquipment =
            DB::table('equipment_table')

                ->where(
                    'equipment_inventory_status',
                    'Disposed'
                )

                ->count();


        // =====================================================
        // CURRENT MONTH EQUIPMENT REGISTRATIONS
        // =====================================================

        $currentMonthEquipment =
            DB::table('equipment_table')

                ->whereBetween(
                    'equipment_created_at',
                    [
                        now()
                            ->copy()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // PREVIOUS MONTH EQUIPMENT REGISTRATIONS
        // =====================================================

        $previousMonthEquipment =
            DB::table('equipment_table')

                ->whereBetween(
                    'equipment_created_at',
                    [
                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // EQUIPMENT MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthEquipment > 0) {

            $equipmentMonthlyPercentage =
                (
                    (
                        $currentMonthEquipment
                        - $previousMonthEquipment
                    )
                    / $previousMonthEquipment
                )
                * 100;

        } elseif ($currentMonthEquipment > 0) {

            // =====================================================
            // PREVIOUS MONTH = 0
            // CURRENT MONTH HAS EQUIPMENT
            // =====================================================

            $equipmentMonthlyPercentage = null;

        } else {

            // =====================================================
            // BOTH MONTHS = 0
            // =====================================================

            $equipmentMonthlyPercentage = 0;

        }


        // =====================================================
        // ACTIVE EQUIPMENT PERCENTAGE
        // PERCENTAGE OF ALL EQUIPMENT
        // =====================================================

        $activeEquipmentPercentage =
            $totalEquipment > 0

                ? (
                    $activeEquipment
                    / $totalEquipment
                ) * 100

                : 0;


        // =====================================================
        // UNDER MAINTENANCE EQUIPMENT PERCENTAGE
        // PERCENTAGE OF ALL EQUIPMENT
        // =====================================================

        $underMaintenanceEquipmentPercentage =
            $totalEquipment > 0

                ? (
                    $underMaintenanceEquipment
                    / $totalEquipment
                ) * 100

                : 0;


        // =====================================================
        // DISPOSED EQUIPMENT PERCENTAGE
        // PERCENTAGE OF ALL EQUIPMENT
        // =====================================================

        $disposedEquipmentPercentage =
            $totalEquipment > 0

                ? (
                    $disposedEquipment
                    / $totalEquipment
                ) * 100

                : 0;


        // =====================================================
        // EQUIPMENT REGISTERED PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyEquipmentRows =
            DB::table('equipment_table')

                ->selectRaw(
                    '
                    YEAR(equipment_created_at)
                        AS equipment_year,

                    MONTH(equipment_created_at)
                        AS equipment_month,

                    COUNT(*)
                        AS equipment_count
                    '
                )

                ->whereNotNull(
                    'equipment_created_at'
                )

                ->where(
                    'equipment_created_at',
                    '>=',
                    now()
                        ->copy()
                        ->subMonths(11)
                        ->startOfMonth()
                )

                ->groupByRaw(
                    '
                    YEAR(equipment_created_at),
                    MONTH(equipment_created_at)
                    '
                )

                ->orderByRaw(
                    '
                    YEAR(equipment_created_at),
                    MONTH(equipment_created_at)
                    '
                )

                ->get()

                ->keyBy(function ($row) {

                    return
                        $row->equipment_year
                        . '-'
                        . str_pad(
                            $row->equipment_month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                });


        // =====================================================
        // BUILD COMPLETE 12 MONTH EQUIPMENT TREND
        // FILL MISSING MONTHS WITH ZERO
        // =====================================================

        $equipmentMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $equipmentMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyEquipmentRows
                            ->get($key)
                            ->equipment_count
                        ?? 0
                    ),

            ]);

        }


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

                // =====================================================
                // EQUIPMENT DASHBOARD COUNTS
                // =====================================================

                'totalEquipment',
                'activeEquipment',
                'underMaintenanceEquipment',
                'borrowedEquipment',
                'disposedEquipment',

                // =====================================================
                // EQUIPMENT MONTHLY DATA
                // =====================================================

                'currentMonthEquipment',
                'previousMonthEquipment',
                'equipmentMonthlyPercentage',

                // =====================================================
                // EQUIPMENT STATUS PERCENTAGES
                // =====================================================

                'activeEquipmentPercentage',
                'underMaintenanceEquipmentPercentage',
                'disposedEquipmentPercentage',

                // =====================================================
                // EQUIPMENT 12 MONTH TREND
                // =====================================================

                'equipmentMonthlyTrend'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT CATEGORIES
    |--------------------------------------------------------------------------
    */

    public function equipmentCategories(Request $request)
    {
        // =====================================================
        // BUILD CATEGORY QUERY
        // =====================================================

        $query = DB::table('equipment_categories_table')

            ->leftJoin(
                'equipment_table',
                'equipment_categories_table.equipment_category_id',
                '=',
                'equipment_table.equipment_category_id'
            )

            ->select(
                'equipment_categories_table.equipment_category_id',
                'equipment_categories_table.equipment_category_name',

                DB::raw(
                    'COUNT(equipment_table.equipment_id) AS equipment_count'
                )
            )

            ->groupBy(
                'equipment_categories_table.equipment_category_id',
                'equipment_categories_table.equipment_category_name'
            );


        // =====================================================
        // SEARCH CATEGORY
        // =====================================================

        if ($request->filled('search')) {

            $query->where(
                'equipment_categories_table.equipment_category_name',
                'LIKE',
                '%' . $request->search . '%'
            );

        }


        // =====================================================
        // GET CATEGORY COUNTS
        // =====================================================

        $totalCategories =
            DB::table('equipment_categories_table')
                ->count();


        $categoriesInUse =
            DB::table('equipment_table')

                ->whereNotNull('equipment_category_id')

                ->distinct()

                ->count('equipment_category_id');


        $unusedCategories =
            $totalCategories - $categoriesInUse;


        $totalEquipment =
            DB::table('equipment_table')
                ->count();


        // =====================================================
        // PAGINATION
        // =====================================================

        $categories = $query

            ->orderBy(
                'equipment_categories_table.equipment_category_name',
                'asc'
            )

            ->paginate(10)

            ->withQueryString();

        // =====================================================
        // TOTAL CATEGORIES
        // =====================================================

        $totalCategories =
            DB::table('equipment_categories_table')
                ->count();


        // =====================================================
        // CATEGORIES CURRENTLY USED
        // =====================================================

        $categoriesInUse =
            DB::table('equipment_categories_table')

                ->join(
                    'equipment_table',
                    'equipment_categories_table.equipment_category_id',
                    '=',
                    'equipment_table.equipment_category_id'
                )

                ->distinct()

                ->count(
                    'equipment_categories_table.equipment_category_id'
                );


        // =====================================================
        // UNUSED CATEGORIES
        // =====================================================

        $unusedCategories =
            $totalCategories
            - $categoriesInUse;


        // =====================================================
        // EQUIPMENT WITH CATEGORY
        // =====================================================

        $categorizedEquipment =
            DB::table('equipment_table')

                ->whereNotNull(
                    'equipment_category_id'
                )

                ->count();


        // =====================================================
        // CATEGORY USAGE PERCENTAGES
        // =====================================================

        $categoriesInUsePercentage =
            $totalCategories > 0

                ? (
                    $categoriesInUse
                    / $totalCategories
                ) * 100

                : 0;


        $unusedCategoriesPercentage =
            $totalCategories > 0

                ? (
                    $unusedCategories
                    / $totalCategories
                ) * 100

                : 0;


        $totalEquipment =
            DB::table('equipment_table')
                ->count();


        $categorizedEquipmentPercentage =
            $totalEquipment > 0

                ? (
                    $categorizedEquipment
                    / $totalEquipment
                ) * 100

                : 0;

        // =====================================================
        // CURRENT MONTH CATEGORY CREATION
        // =====================================================

        $currentMonthCategories =
            DB::table('equipment_categories_table')

                ->whereBetween(
                    'equipment_category_created_at',
                    [
                        now()
                            ->copy()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // PREVIOUS MONTH CATEGORY CREATION
        // =====================================================

        $previousMonthCategories =
            DB::table('equipment_categories_table')

                ->whereBetween(
                    'equipment_category_created_at',
                    [
                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // CATEGORY MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthCategories > 0) {

            $categoryMonthlyPercentage =
                (
                    (
                        $currentMonthCategories
                        - $previousMonthCategories
                    )
                    / $previousMonthCategories
                )
                * 100;

        } elseif ($currentMonthCategories > 0) {

            // =====================================================
            // PREVIOUS MONTH = 0
            // CURRENT MONTH HAS NEW CATEGORIES
            // =====================================================

            $categoryMonthlyPercentage = null;

        } else {

            // =====================================================
            // BOTH MONTHS = 0
            // =====================================================

            $categoryMonthlyPercentage = 0;

        }


        // =====================================================
        // CATEGORYS CREATED PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyCategoryRows =
            DB::table('equipment_categories_table')

                ->selectRaw(
                    '
                    YEAR(equipment_category_created_at) AS category_year,
                    MONTH(equipment_category_created_at) AS category_month,
                    COUNT(*) AS category_count
                    '
                )

                ->whereNotNull(
                    'equipment_category_created_at'
                )

                ->where(
                    'equipment_category_created_at',
                    '>=',
                    now()
                        ->copy()
                        ->subMonths(11)
                        ->startOfMonth()
                )

                ->groupByRaw(
                    '
                    YEAR(equipment_category_created_at),
                    MONTH(equipment_category_created_at)
                    '
                )

                ->orderByRaw(
                    '
                    YEAR(equipment_category_created_at),
                    MONTH(equipment_category_created_at)
                    '
                )

                ->get()

                ->keyBy(function ($row) {

                    return
                        $row->category_year
                        . '-'
                        . str_pad(
                            $row->category_month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                });


        // =====================================================
        // BUILD COMPLETE 12 MONTH CATEGORY TREND
        // =====================================================

        $categoryMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $categoryMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyCategoryRows
                            ->get($key)
                            ->category_count
                        ?? 0
                    ),

            ]);

        }


        // =====================================================
        // RETURN CATEGORY PAGE
        // =====================================================

        return view(
            'maintenance-personnel.equipment.categories',

            compact(
                'categories',

                'totalCategories',
                'categoriesInUse',
                'unusedCategories',

                'categorizedEquipment',
                'totalEquipment',

                'categoriesInUsePercentage',
                'unusedCategoriesPercentage',
                'categorizedEquipmentPercentage',

                'categoryMonthlyPercentage',
                'categoryMonthlyTrend'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE EQUIPMENT CATEGORY
    |--------------------------------------------------------------------------
    */

    public function storeEquipmentCategory(Request $request)
    {
        // =====================================================
        // VALIDATE CATEGORY
        // =====================================================

        $validated = $request->validate([

            'equipment_category_name' =>
                'required|string|max:255|unique:equipment_categories_table,equipment_category_name',

        ]);


        // =====================================================
        // INSERT CATEGORY
        // =====================================================

        DB::table('equipment_categories_table')

            ->insert([

                'equipment_category_name' =>
                    trim($validated['equipment_category_name']),

                'equipment_category_created_at' =>
                    now(),

            ]);


        // =====================================================
        // RETURN TO CATEGORY PAGE
        // =====================================================

        return back()->with(
            'success',
            'Equipment category added successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE EQUIPMENT CATEGORY
    |--------------------------------------------------------------------------
    */

    public function updateEquipmentCategory(
        Request $request,
        $id
    )
    {
        // =====================================================
        // CHECK CATEGORY EXISTS
        // =====================================================

        $category =
            DB::table('equipment_categories_table')

                ->where(
                    'equipment_category_id',
                    $id
                )

                ->first();


        if (!$category) {

            return back()->with(
                'error',
                'Equipment category not found.'
            );

        }


        // =====================================================
        // VALIDATE CATEGORY
        // =====================================================

        $validated = $request->validate([

            'equipment_category_name' => [

                'required',

                'string',

                'max:255',

                'unique:equipment_categories_table,equipment_category_name,' .
                    $id .
                    ',equipment_category_id',

            ],

        ]);


        // =====================================================
        // UPDATE CATEGORY
        // =====================================================

        DB::table('equipment_categories_table')

            ->where(
                'equipment_category_id',
                $id
            )

            ->update([

                'equipment_category_name' =>
                    trim($validated['equipment_category_name']),

            ]);


        return back()->with(
            'success',
            'Equipment category updated successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE EQUIPMENT CATEGORY
    |--------------------------------------------------------------------------
    */

    public function deleteEquipmentCategory($id)
    {
        // =====================================================
        // CHECK CATEGORY EXISTS
        // =====================================================

        $category =
            DB::table('equipment_categories_table')

                ->where(
                    'equipment_category_id',
                    $id
                )

                ->first();


        if (!$category) {

            return back()->with(
                'error',
                'Equipment category not found.'
            );

        }


        // =====================================================
        // CHECK IF EQUIPMENT IS USING CATEGORY
        // =====================================================

        $equipmentCount =
            DB::table('equipment_table')

                ->where(
                    'equipment_category_id',
                    $id
                )

                ->count();


        // =====================================================
        // BLOCK DELETE WHEN CATEGORY IS IN USE
        // =====================================================

        if ($equipmentCount > 0) {

            return back()->with(
                'error',
                'This category cannot be deleted because equipment is still assigned to it.'
            );

        }


        // =====================================================
        // DELETE CATEGORY
        // =====================================================

        DB::table('equipment_categories_table')

            ->where(
                'equipment_category_id',
                $id
            )

            ->delete();


        return back()->with(
            'success',
            'Equipment category deleted successfully.'
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
        )
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('room_is_archived', false)
            )
            ->get();

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
                    => $request->has('equipment_is_borrowable'),

                'equipment_created_at'
                    => now()

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
                    => $request->equipment_inventory_status,

                'equipment_is_borrowable'
                    => $request->has('equipment_is_borrowable'),

            ]);

        return back()->with(
            'success',
            'Equipment updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT TRANSFER & HISTORY E - 2.0
    |--------------------------------------------------------------------------
    */

    public function equipmentTransferHistory(Request $request)
    {
        // =====================================================
        // ROOMS FOR FILTER AND TRANSFER MODAL
        // =====================================================

        $rooms = DB::table('rooms_table')

            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) =>
                    $query->where('room_is_archived', false)
            )

            ->orderBy(
                'room_name',
                'asc'
            )

            ->get();


        // =====================================================
        // CATEGORIES FOR CATEGORY FILTER
        // =====================================================

        $categories = DB::table('equipment_categories_table')

            ->orderBy(
                'equipment_category_name',
                'asc'
            )

            ->get();


        // =====================================================
        // BUILD EQUIPMENT QUERY
        // APPLY FILTERS BEFORE PAGINATION
        // =====================================================

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


        // =====================================================
        // SEARCH FILTER
        // =====================================================

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'equipment_table.equipment_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_asset_tag',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_brand_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_model',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_table.equipment_serial_number',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_categories_table.equipment_category_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'rooms_table.room_name',
                    'LIKE',
                    '%' . $request->search . '%'
                );

            });

        }


        // =====================================================
        // CATEGORY FILTER
        // =====================================================

        if ($request->filled('category')) {

            $query->where(
                'equipment_table.equipment_category_id',
                $request->category
            );

        }


        // =====================================================
        // ROOM FILTER
        // =====================================================

        if ($request->filled('room')) {

            $query->where(
                'equipment_table.equipment_room_id',
                $request->room
            );

        }


        // =====================================================
        // STATUS FILTER
        // =====================================================

        if ($request->filled('status')) {

            $query->where(
                'equipment_table.equipment_inventory_status',
                $request->status
            );

        }


        // =====================================================
        // PAGINATE FILTERED EQUIPMENT
        // =====================================================

        $equipment = $query

            ->orderBy(
                'equipment_table.equipment_name',
                'asc'
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // ADD TRANSFER DASHBOARD DATA HERE
        // =====================================================


        // =====================================================
        // TOTAL TRANSFER RECORDS
        // =====================================================

        $totalTransferRecords =
            DB::table('equipment_transfer_history_table')
                ->count();


        // =====================================================
        // CURRENT MONTH TRANSFERS
        // =====================================================

        $currentMonthTransfers =
            DB::table('equipment_transfer_history_table')

                ->whereBetween(
                    'created_at',
                    [
                        now()->copy()->startOfMonth(),
                        now()->copy()->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // PREVIOUS MONTH TRANSFERS
        // =====================================================

        $previousMonthTransfers =
            DB::table('equipment_transfer_history_table')

                ->whereBetween(
                    'created_at',
                    [
                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthTransfers > 0) {

            $transferMonthlyPercentage =
                (
                    (
                        $currentMonthTransfers
                        - $previousMonthTransfers
                    )
                    / $previousMonthTransfers
                )
                * 100;

        } elseif ($currentMonthTransfers > 0) {

            $transferMonthlyPercentage = null;

        } else {

            $transferMonthlyPercentage = 0;

        }


        // =====================================================
        // UNIQUE EQUIPMENT TRANSFERRED
        // =====================================================

        $equipmentTransferred =
            DB::table('equipment_transfer_history_table')

                ->distinct()

                ->count('equipment_id');


        // =====================================================
        // UNIQUE DESTINATION ROOMS INVOLVED
        // =====================================================

        $roomsInvolved =
            DB::table('equipment_transfer_history_table')

                ->whereNotNull('to_room_id')

                ->distinct()

                ->count('to_room_id');


        // =====================================================
        // TOTAL EQUIPMENT
        // USED TO CALCULATE TRANSFER COVERAGE
        // =====================================================

        $totalEquipment =
            DB::table('equipment_table')
                ->count();


        // =====================================================
        // EQUIPMENT TRANSFERRED PERCENTAGE
        // PERCENTAGE OF ALL EQUIPMENT THAT HAS TRANSFER HISTORY
        // =====================================================

        $equipmentTransferredPercentage =
            $totalEquipment > 0

                ? (
                    $equipmentTransferred
                    / $totalEquipment
                ) * 100

                : 0;


        // =====================================================
        // TOTAL ROOMS
        // USED TO CALCULATE ROOM TRANSFER COVERAGE
        // =====================================================

        $totalRooms =
            DB::table('rooms_table')

                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($query) =>
                        $query->where('room_is_archived', false)
                )

                ->count();


        // =====================================================
        // ROOMS INVOLVED PERCENTAGE
        // PERCENTAGE OF ALL ROOMS USED AS TRANSFER DESTINATIONS
        // =====================================================

        $roomsInvolvedPercentage =
            $totalRooms > 0

                ? (
                    $roomsInvolved
                    / $totalRooms
                ) * 100

                : 0;


        // =====================================================
        // TRANSFERS PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyTransferRows =
            DB::table('equipment_transfer_history_table')

                ->selectRaw(
                    '
                    YEAR(created_at) AS transfer_year,
                    MONTH(created_at) AS transfer_month,
                    COUNT(*) AS transfer_count
                    '
                )

                ->where(
                    'created_at',
                    '>=',
                    now()
                        ->copy()
                        ->subMonths(11)
                        ->startOfMonth()
                )

                ->groupByRaw(
                    '
                    YEAR(created_at),
                    MONTH(created_at)
                    '
                )

                ->orderByRaw(
                    '
                    YEAR(created_at),
                    MONTH(created_at)
                    '
                )

                ->get()

                ->keyBy(function ($row) {

                    return
                        $row->transfer_year
                        . '-'
                        . str_pad(
                            $row->transfer_month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                });


        // =====================================================
        // BUILD COMPLETE 12 MONTH TREND
        // =====================================================

        $transferMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $transferMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyTransferRows
                            ->get($key)
                            ->transfer_count
                        ?? 0
                    ),

            ]);

        }


        // =====================================================
        // RETURN VIEW
        // =====================================================

        return view(
            'maintenance-personnel.equipment.transfer-equipment',

            compact(
                'equipment',
                'rooms',
                'categories',

                // =================================================
                // TRANSFER DASHBOARD VARIABLES
                // =================================================

                'totalTransferRecords',
                'currentMonthTransfers',
                'previousMonthTransfers',
                'transferMonthlyPercentage',

                'equipmentTransferred',
                'totalEquipment',
                'equipmentTransferredPercentage',

                'roomsInvolved',
                'totalRooms',
                'roomsInvolvedPercentage',

                'transferMonthlyTrend'
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

    // =====================================================
    // GET EQUIPMENT TRANSFER HISTORY
    // =====================================================

    public function getTransferHistory($id)
    {

        // =====================================================
        // GET TRANSFER RECORDS
        // =====================================================

        $history = DB::table(
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

            'equipment_transfer_history_table.transfer_id',

            'equipment_transfer_history_table.equipment_id',

            'equipment_transfer_history_table.from_room_id',

            'equipment_transfer_history_table.to_room_id',

            'equipment_transfer_history_table.remarks',

            'equipment_transfer_history_table.created_at',

            'from_room.room_name as from_room_name',

            'to_room.room_name as to_room_name'

        )

        ->orderBy(
            'equipment_transfer_history_table.created_at',
            'desc'
        )

        ->get();


        // =====================================================
        // RETURN JSON RESPONSE
        // =====================================================

        return response()->json($history);

    }

    /*
    |--------------------------------------------------------------------------
    | QR CODE TOOLS E - 2.1
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | QR CODE TOOLS E - 2.1
    |--------------------------------------------------------------------------
    */

    

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
        | MAINTENANCE HISTORY E - 2.2
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
        | TRANSFER HISTORY E - 2.3
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
    | BORROWING LIST - BORROWING MODULE - E 2.4
    |--------------------------------------------------------------------------
    */

    public function borrowing(Request $request)
    {
        // =====================================================
        // UPDATE OVERDUE BORROWING RECORDS
        // =====================================================

        DB::table('borrowing_records_table')

            ->where(
                'borrowing_status',
                'Borrowed'
            )

            ->whereNotNull(
                'borrowing_expected_return_date'
            )

            ->whereDate(
                'borrowing_expected_return_date',
                '<',
                today()
            )

            ->update([

                'borrowing_status' => 'Overdue',

            ]);


        // =====================================================
        // GET BORROWING RECORDS
        // =====================================================

        // =====================================================
        // BUILD BORROWING RECORDS QUERY
        // SEARCH AND FILTERS ARE APPLIED BEFORE PAGINATION
        // =====================================================

        $query = DB::table('borrowing_records_table')

            ->leftJoin(
                'equipment_table',
                'borrowing_records_table.borrowing_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->select(
                'borrowing_records_table.*',
                'equipment_table.equipment_name'
            );


        // =====================================================
        // SEARCH FILTER
        // SEARCHES ALL BORROWING RECORDS
        // =====================================================

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'equipment_table.equipment_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'borrowing_records_table.borrowing_borrower_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'borrowing_records_table.borrowing_borrower_department',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'borrowing_records_table.borrowing_authorized_by',
                    'LIKE',
                    '%' . $request->search . '%'
                );

            });

        }


        // =====================================================
        // STATUS FILTER
        // =====================================================

        if ($request->filled('status')) {

            $query->where(
                'borrowing_records_table.borrowing_status',
                $request->status
            );

        }


        // =====================================================
        // PAGINATE FILTERED BORROWING RECORDS
        // =====================================================

        $borrowings = $query

            ->orderBy(
                'borrowing_records_table.borrowing_created_at',
                'desc'
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // GET AVAILABLE BORROWABLE EQUIPMENT
        // =====================================================

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


        // =====================================================
        // TOTAL BORROWING RECORDS
        // =====================================================

        $totalBorrowingRecords =
            DB::table('borrowing_records_table')
                ->count();


        // =====================================================
        // ON LOAN
        //
        // BORROWED + OVERDUE ARE BOTH STILL OUT
        // =====================================================

        $onLoanBorrowings =
            DB::table('borrowing_records_table')

                ->whereIn(
                    'borrowing_status',
                    [
                        'Borrowed',
                        'Overdue',
                    ]
                )

                ->count();


        // =====================================================
        // RETURNED
        // =====================================================

        $returnedBorrowings =
            DB::table('borrowing_records_table')

                ->where(
                    'borrowing_status',
                    'Returned'
                )

                ->count();


        // =====================================================
        // OVERDUE
        // =====================================================

        $overdueBorrowings =
            DB::table('borrowing_records_table')

                ->where(
                    'borrowing_status',
                    'Overdue'
                )

                ->count();


        // =====================================================
        // CURRENT MONTH BORROWING RECORDS
        // =====================================================

        $currentMonthBorrowings =
            DB::table('borrowing_records_table')

                ->whereBetween(
                    'borrowing_created_at',
                    [
                        now()->copy()->startOfMonth(),
                        now()->copy()->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // PREVIOUS MONTH BORROWING RECORDS
        // =====================================================

        $previousMonthBorrowings =
            DB::table('borrowing_records_table')

                ->whereBetween(
                    'borrowing_created_at',
                    [
                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthBorrowings > 0) {

            $borrowingMonthlyPercentage =
                (
                    (
                        $currentMonthBorrowings
                        - $previousMonthBorrowings
                    )
                    / $previousMonthBorrowings
                )
                * 100;

        } elseif ($currentMonthBorrowings > 0) {

            $borrowingMonthlyPercentage = null;

        } else {

            $borrowingMonthlyPercentage = 0;

        }


        // =====================================================
        // ON LOAN PERCENTAGE
        // =====================================================

        $onLoanPercentage =
            $totalBorrowingRecords > 0

                ? (
                    $onLoanBorrowings
                    / $totalBorrowingRecords
                ) * 100

                : 0;


        // =====================================================
        // RETURNED PERCENTAGE
        // =====================================================

        $returnedPercentage =
            $totalBorrowingRecords > 0

                ? (
                    $returnedBorrowings
                    / $totalBorrowingRecords
                ) * 100

                : 0;


        // =====================================================
        // OVERDUE PERCENTAGE
        // =====================================================

        $overduePercentage =
            $totalBorrowingRecords > 0

                ? (
                    $overdueBorrowings
                    / $totalBorrowingRecords
                ) * 100

                : 0;


        // =====================================================
        // BORROWING RECORDS PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyBorrowingRows =
            DB::table('borrowing_records_table')

                ->selectRaw(
                    '
                    YEAR(borrowing_created_at) AS borrowing_year,
                    MONTH(borrowing_created_at) AS borrowing_month,
                    COUNT(*) AS borrowing_count
                    '
                )

                ->where(
                    'borrowing_created_at',
                    '>=',
                    now()
                        ->copy()
                        ->subMonths(11)
                        ->startOfMonth()
                )

                ->groupByRaw(
                    '
                    YEAR(borrowing_created_at),
                    MONTH(borrowing_created_at)
                    '
                )

                ->orderByRaw(
                    '
                    YEAR(borrowing_created_at),
                    MONTH(borrowing_created_at)
                    '
                )

                ->get()

                ->keyBy(function ($row) {

                    return
                        $row->borrowing_year
                        . '-'
                        . str_pad(
                            $row->borrowing_month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                });


        // =====================================================
        // BUILD COMPLETE 12 MONTH TREND
        // =====================================================

        $borrowingMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $borrowingMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyBorrowingRows
                            ->get($key)
                            ->borrowing_count
                        ?? 0
                    ),

            ]);

        }


        // =====================================================
        // RETURN BORROWING PAGE
        // =====================================================

        return view(
            'maintenance-personnel.borrowing.index',

            compact(
                'borrowings',
                'equipment',

                // =================================================
                // BORROWING DASHBOARD VARIABLES
                // =================================================

                'totalBorrowingRecords',
                'currentMonthBorrowings',
                'previousMonthBorrowings',
                'borrowingMonthlyPercentage',

                'onLoanBorrowings',
                'onLoanPercentage',

                'returnedBorrowings',
                'returnedPercentage',

                'overdueBorrowings',
                'overduePercentage',

                'borrowingMonthlyTrend'
            )
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
    | INFRASTRUCTURE - 3
    

    public function infrastructure()
    {
        $buildings = DB::table('buildings_table')

            ->orderBy(
                'building_name'
            )

            ->get();

        $floors = DB::table('floors_table')

            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )

            ->select(
                'floors_table.*',
                'buildings_table.building_name'
            )

            ->get();

        $rooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('rooms_table.room_is_archived', false)
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
            'rooms_table.room_id',
            '=',
            'equipment_table.equipment_room_id'
        )

        ->groupBy(
            'rooms_table.room_id'
        )

        ->select(
            'rooms_table.*',
            'floors_table.floor_level',
            'buildings_table.building_name',
            DB::raw(
                'COUNT(equipment_table.equipment_id)
                as equipment_count'
            )
        )

        ->get();

        $totalBuildings = $buildings->count();

        $totalFloors = $floors->count();

        $totalRooms = $rooms->count();

        return view(
            'maintenance-personnel.buildings.index',
            compact(
                'buildings',
                'floors',
                'rooms',
                'totalBuildings',
                'totalFloors',
                'totalRooms'
            )
        );
    }

    public function storeBuilding(Request $request)
    {
        DB::table('buildings_table')

            ->insert([

                'building_name'
                    => $request->building_name

            ]);

        return back()
            ->with(
                'success',
                'Building added successfully.'
            );
    }

    public function updateBuilding(
        Request $request,
        $id
    )
    {
        DB::table(
            'buildings_table'
        )

        ->where(
            'building_id',
            $id
        )

        ->update([

            'building_name'
                =>
                $request->building_name

        ]);

        return back();
    }

    public function deleteBuilding($id)
    {
        DB::table(
            'buildings_table'
        )

        ->where(
            'building_id',
            $id
        )

        ->delete();

        return back();
    }

    public function storeFloor(Request $request)
    {
        DB::table('floors_table')

            ->insert([

                'floor_building_id'
                    => $request->building_id,

                'floor_level'
                    => $request->floor_level

            ]);

        return back()
            ->with(
                'success',
                'Floor added successfully.'
            );
    }

    public function updateFloor(
        Request $request,
        $id
    )
    {
        DB::table(
            'floors_table'
        )

        ->where(
            'floor_id',
            $id
        )

        ->update([

            'floor_building_id'
                =>
                $request->building_id,

            'floor_level'
                =>
                $request->floor_level

        ]);

        return back();
    }

    public function deleteFloor($id)
    {
        DB::table(
            'floors_table'
        )

        ->where(
            'floor_id',
            $id
        )

        ->delete();

        return back();
    }

    public function storeRoom(Request $request)
    {
        DB::table('rooms_table')

            ->insert([

                'room_floor_id'
                    => $request->floor_id,

                'room_name'
                    => $request->room_name

            ]);

        return back()
            ->with(
                'success',
                'Room added successfully.'
            );
    }

    public function updateRoom(
        Request $request,
        $id
    )
    {
        DB::table(
            'rooms_table'
        )

        ->where(
            'room_id',
            $id
        )

        ->update([

            'room_floor_id'
                =>
                $request->floor_id,

            'room_name'
                =>
                $request->room_name

        ]);

        return back();
    }

    public function deleteRoom($id)
    {
        DB::table(
            'rooms_table'
        )

        ->where(
            'room_id',
            $id
        )

        ->delete();

        return back();
    } */





    public function roomDetails($id)
    {
        $room = DB::table('rooms_table')

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

            ->where(
                'room_id',
                $id
            )

            ->first();

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_room_id',
                $id
            )

            ->get();

        return view(
            'maintenance-personnel.rooms.room-details',
            compact(
                'room',
                'equipment'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MAINTENANCE SCHEDULES - 4
    |--------------------------------------------------------------------------
    */

    public function schedules(Request $request)
    {
        // =====================================================
        // UPDATE EXPIRED ACTIVE SCHEDULES TO OVERDUE
        // =====================================================

        DB::table('maintenance_schedules_table')

            ->where(
                'maintenance_schedule_status',
                'Active'
            )

            ->whereNotNull(
                'maintenance_schedule_next_date'
            )

            ->whereDate(
                'maintenance_schedule_next_date',
                '<',
                today()
            )

            ->update([

                'maintenance_schedule_status' => 'Overdue',

            ]);


        // =====================================================
        // BASE MAINTENANCE SCHEDULE QUERY
        // USED BY TABLE AND CALENDAR
        // =====================================================

        $schedulesQuery = DB::table('maintenance_schedules_table')

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
            );


        // =====================================================
        // ALL SCHEDULES FOR CALENDAR
        // DO NOT PAGINATE THIS
        // =====================================================

        $calendarSchedulesData = (clone $schedulesQuery)

            ->orderBy(
                'maintenance_schedules_table.maintenance_schedule_next_date',
                'asc'
            )

            ->get();


        // =====================================================
        // BUILD TABLE QUERY
        // FILTERS APPLY ONLY TO THE SCHEDULE LIST
        // CALENDAR DATA REMAINS COMPLETE
        // =====================================================

        $tableSchedulesQuery = clone $schedulesQuery;


        // =====================================================
        // SEARCH FILTER
        // =====================================================

        if ($request->filled('search')) {

            $tableSchedulesQuery->where(function ($query) use ($request) {

                $query->where(
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
                    'maintenance_schedules_table.maintenance_schedule_title',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'maintenance_schedules_table.maintenance_schedule_description',
                    'LIKE',
                    '%' . $request->search . '%'
                );

            });

        }


        // =====================================================
        // FREQUENCY FILTER
        // =====================================================

        if ($request->filled('frequency')) {

            $tableSchedulesQuery->where(
                'maintenance_schedules_table.maintenance_schedule_frequency',
                $request->frequency
            );

        }


        // =====================================================
        // STATUS FILTER
        // =====================================================

        if ($request->filled('status')) {

            $tableSchedulesQuery->where(
                'maintenance_schedules_table.maintenance_schedule_status',
                $request->status
            );

        }


        // =====================================================
        // PAGINATE FILTERED SCHEDULES
        // =====================================================

        $schedules = $tableSchedulesQuery

            ->orderBy(
                'maintenance_schedules_table.maintenance_schedule_next_date',
                'asc'
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // GET ACTIVE EQUIPMENT FOR CREATE SCHEDULE MODAL
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->where(
                'equipment_inventory_status',
                'Active'
            )

            ->select(
                'equipment_table.*',
                'rooms_table.room_name'
            )

            ->orderBy(
                'equipment_name'
            )

            ->get();


        // =====================================================
        // ADD SCHEDULE DASHBOARD DATA HERE
        // =====================================================


        // =====================================================
        // TOTAL SCHEDULES
        // =====================================================

        $totalSchedules =
            DB::table('maintenance_schedules_table')
                ->count();


        // =====================================================
        // UPCOMING MAINTENANCE
        //
        // ACTIVE SCHEDULES DUE AFTER TODAY
        // =====================================================

        $upcomingMaintenance =
            DB::table('maintenance_schedules_table')

                ->where(
                    'maintenance_schedule_status',
                    'Active'
                )

                ->whereNotNull(
                    'maintenance_schedule_next_date'
                )

                ->whereDate(
                    'maintenance_schedule_next_date',
                    '>',
                    today()
                )

                ->count();


        // =====================================================
        // COMPLETED MAINTENANCE
        // =====================================================

        $completedMaintenance =
            DB::table('maintenance_schedules_table')

                ->where(
                    'maintenance_schedule_status',
                    'Completed'
                )

                ->count();


        // =====================================================
        // OVERDUE MAINTENANCE
        // =====================================================

        $overdueMaintenance =
            DB::table('maintenance_schedules_table')

                ->where(
                    'maintenance_schedule_status',
                    'Overdue'
                )

                ->count();


        // =====================================================
        // OUTSTANDING SCHEDULES
        //
        // ACTIVE + OVERDUE
        // =====================================================

        $outstandingSchedules =
            DB::table('maintenance_schedules_table')

                ->whereIn(
                    'maintenance_schedule_status',
                    [
                        'Active',
                        'Overdue',
                    ]
                )

                ->count();


        // =====================================================
        // CURRENT MONTH CREATED SCHEDULES
        // =====================================================

        $currentMonthSchedules =
            DB::table('maintenance_schedules_table')

                ->whereBetween(
                    'maintenance_schedule_created_at',
                    [
                        now()->copy()->startOfMonth(),
                        now()->copy()->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // PREVIOUS MONTH CREATED SCHEDULES
        // =====================================================

        $previousMonthSchedules =
            DB::table('maintenance_schedules_table')

                ->whereBetween(
                    'maintenance_schedule_created_at',
                    [
                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthSchedules > 0) {

            $scheduleMonthlyPercentage =
                (
                    (
                        $currentMonthSchedules
                        - $previousMonthSchedules
                    )
                    / $previousMonthSchedules
                )
                * 100;

        } elseif ($currentMonthSchedules > 0) {

            $scheduleMonthlyPercentage = null;

        } else {

            $scheduleMonthlyPercentage = 0;

        }


        // =====================================================
        // UPCOMING PERCENTAGE
        // =====================================================

        $upcomingMaintenancePercentage =
            $outstandingSchedules > 0

                ? (
                    $upcomingMaintenance
                    / $outstandingSchedules
                ) * 100

                : 0;


        // =====================================================
        // COMPLETED PERCENTAGE
        // =====================================================

        $completedMaintenancePercentage =
            $totalSchedules > 0

                ? (
                    $completedMaintenance
                    / $totalSchedules
                ) * 100

                : 0;


        // =====================================================
        // OVERDUE PERCENTAGE
        // =====================================================

        $overdueMaintenancePercentage =
            $outstandingSchedules > 0

                ? (
                    $overdueMaintenance
                    / $outstandingSchedules
                ) * 100

                : 0;


        // =====================================================
        // SCHEDULES CREATED PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyScheduleRows =
            DB::table('maintenance_schedules_table')

                ->selectRaw(
                    '
                    YEAR(maintenance_schedule_created_at)
                        AS schedule_year,

                    MONTH(maintenance_schedule_created_at)
                        AS schedule_month,

                    COUNT(*)
                        AS schedule_count
                    '
                )

                ->where(
                    'maintenance_schedule_created_at',
                    '>=',
                    now()
                        ->copy()
                        ->subMonths(11)
                        ->startOfMonth()
                )

                ->groupByRaw(
                    '
                    YEAR(maintenance_schedule_created_at),
                    MONTH(maintenance_schedule_created_at)
                    '
                )

                ->orderByRaw(
                    '
                    YEAR(maintenance_schedule_created_at),
                    MONTH(maintenance_schedule_created_at)
                    '
                )

                ->get()

                ->keyBy(function ($row) {

                    return
                        $row->schedule_year
                        . '-'
                        . str_pad(
                            $row->schedule_month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                });


        // =====================================================
        // BUILD COMPLETE 12 MONTH TREND
        // =====================================================

        $scheduleMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $scheduleMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyScheduleRows
                            ->get($key)
                            ->schedule_count
                        ?? 0
                    ),

            ]);

        }


        // =====================================================
        // RETURN VIEW
        // =====================================================

        return view(
            'maintenance-personnel.maintenance-schedules.index',

            compact(
                'schedules',
                'equipment',

                // =================================================
                // SCHEDULE DASHBOARD VARIABLES
                // =================================================
                'calendarSchedulesData',

                'totalSchedules',
                'currentMonthSchedules',
                'previousMonthSchedules',
                'scheduleMonthlyPercentage',

                'upcomingMaintenance',
                'upcomingMaintenancePercentage',

                'completedMaintenance',
                'completedMaintenancePercentage',

                'overdueMaintenance',
                'overdueMaintenancePercentage',

                'outstandingSchedules',

                'scheduleMonthlyTrend'
            )
        );
    }

    // =====================================================
    // TODAY'S MAINTENANCE SCHEDULES
    // =====================================================

    public function todaySchedules()
    {
        // =====================================================
        // GET SCHEDULES DUE TODAY
        // =====================================================

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

            ->whereDate(
                'maintenance_schedules_table.maintenance_schedule_next_date',
                today()
            )

            ->select(

                'maintenance_schedules_table.*',

                'equipment_table.equipment_name',

                'equipment_table.equipment_inventory_status',

                'rooms_table.room_name'

            )

            ->orderBy(
                'maintenance_schedules_table.maintenance_schedule_next_date',
                'asc'
            )

            ->get();


        // =====================================================
        // RETURN TODAY'S SCHEDULE PAGE
        // =====================================================

        return view(
            'maintenance-personnel.schedules.today',
            compact('schedules')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function storeSchedule(Request $request)
    {
        $request->validate([
            'equipment_id' => ['required', 'integer', 'exists:equipment_table,equipment_id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frequency' => ['required', 'string', 'max:100'],
            'next_date' => ['required', 'date'],
        ]);

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
        $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:maintenance_schedules_table,maintenance_schedule_id'],
            'findings' => ['required', 'string'],
            'repair_action' => ['required', 'string'],
            'maintenance_status' => ['required', 'string', 'max:100'],
            'proof_image' => ['nullable', 'image', 'max:4096'],
        ]);

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
        $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:maintenance_schedules_table,maintenance_schedule_id'],
            'new_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
        ]);

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
        $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:maintenance_schedules_table,maintenance_schedule_id'],
        ]);

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

    public function disposal(Request $request)
    {
        // =====================================================
        // BUILD DISPOSAL RECORDS QUERY
        // SEARCH AND FILTERS APPLY BEFORE PAGINATION
        // =====================================================

        $query = DB::table('disposal_records_table')

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
            );


        // =====================================================
        // SEARCH FILTER
        // SEARCHES THE ENTIRE DISPOSAL RECORDS TABLE
        // =====================================================

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'equipment_table.equipment_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'equipment_categories_table.equipment_category_name',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'disposal_records_table.disposal_reason',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'disposal_records_table.disposal_area_location',
                    'LIKE',
                    '%' . $request->search . '%'
                );

            });

        }


        // =====================================================
        // CATEGORY FILTER
        // =====================================================

        if ($request->filled('category')) {

            $query->where(
                'equipment_table.equipment_category_id',
                $request->category
            );

        }


        // =====================================================
        // CONDITION FILTER
        // =====================================================

        if ($request->filled('condition')) {

            $query->where(
                'equipment_table.equipment_condition_status',
                $request->condition
            );

        }


        // =====================================================
        // REASON FILTER
        // =====================================================

        if ($request->filled('reason')) {

            $query->where(
                'disposal_records_table.disposal_reason',
                $request->reason
            );

        }


        // =====================================================
        // PAGINATE FILTERED DISPOSAL RECORDS
        // =====================================================

        $disposals = $query

            ->orderBy(
                'disposal_records_table.disposal_disposed_at',
                'desc'
            )

            ->paginate(10)

            ->withQueryString();

        // =====================================================
        // GET CATEGORIES FOR CATEGORY FILTER
        // =====================================================

        $categories = DB::table('equipment_categories_table')

            ->orderBy(
                'equipment_category_name',
                'asc'
            )

            ->get();


        // =====================================================
        // GET EQUIPMENT AVAILABLE FOR DISPOSAL
        // =====================================================

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


        // =====================================================
        // ADD DISPOSAL DASHBOARD DATA HERE
        // =====================================================


        // =====================================================
        // TOTAL DISPOSAL RECORDS
        // =====================================================

        $totalDisposalRecords =
            DB::table('disposal_records_table')
                ->count();


        // =====================================================
        // DAMAGED DISPOSALS
        //
        // DISPOSAL RECORDS WHOSE EQUIPMENT CONDITION
        // IS CURRENTLY DAMAGED
        // =====================================================

        $damagedDisposals =
            DB::table('disposal_records_table')

                ->join(
                    'equipment_table',
                    'disposal_records_table.disposal_equipment_id',
                    '=',
                    'equipment_table.equipment_id'
                )

                ->where(
                    'equipment_table.equipment_condition_status',
                    'Damaged'
                )

                ->count();


        // =====================================================
        // CURRENTLY DISPOSED EQUIPMENT
        // =====================================================

        $disposedEquipment =
            DB::table('equipment_table')

                ->where(
                    'equipment_inventory_status',
                    'Disposed'
                )

                ->count();


        // =====================================================
        // TOTAL EQUIPMENT
        // =====================================================

        $totalEquipment =
            DB::table('equipment_table')
                ->count();


        // =====================================================
        // CURRENT MONTH DISPOSALS
        // =====================================================

        $currentMonthDisposals =
            DB::table('disposal_records_table')

                ->whereBetween(
                    'disposal_disposed_at',
                    [
                        now()->copy()->startOfMonth(),
                        now()->copy()->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // PREVIOUS MONTH DISPOSALS
        // =====================================================

        $previousMonthDisposals =
            DB::table('disposal_records_table')

                ->whereBetween(
                    'disposal_disposed_at',
                    [
                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->startOfMonth(),

                        now()
                            ->copy()
                            ->subMonthNoOverflow()
                            ->endOfMonth(),
                    ]
                )

                ->count();


        // =====================================================
        // MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthDisposals > 0) {

            $disposalMonthlyPercentage =
                (
                    (
                        $currentMonthDisposals
                        - $previousMonthDisposals
                    )
                    / $previousMonthDisposals
                )
                * 100;

        } elseif ($currentMonthDisposals > 0) {

            $disposalMonthlyPercentage = null;

        } else {

            $disposalMonthlyPercentage = 0;

        }


        // =====================================================
        // DAMAGED DISPOSAL PERCENTAGE
        // =====================================================

        $damagedDisposalsPercentage =
            $totalDisposalRecords > 0

                ? (
                    $damagedDisposals
                    / $totalDisposalRecords
                ) * 100

                : 0;


        // =====================================================
        // DISPOSED EQUIPMENT PERCENTAGE
        // =====================================================

        $disposedEquipmentPercentage =
            $totalEquipment > 0

                ? (
                    $disposedEquipment
                    / $totalEquipment
                ) * 100

                : 0;


        // =====================================================
        // DISPOSALS PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyDisposalRows =
            DB::table('disposal_records_table')

                ->selectRaw(
                    '
                    YEAR(disposal_disposed_at) AS disposal_year,
                    MONTH(disposal_disposed_at) AS disposal_month,
                    COUNT(*) AS disposal_count
                    '
                )

                ->where(
                    'disposal_disposed_at',
                    '>=',
                    now()
                        ->copy()
                        ->subMonths(11)
                        ->startOfMonth()
                )

                ->groupByRaw(
                    '
                    YEAR(disposal_disposed_at),
                    MONTH(disposal_disposed_at)
                    '
                )

                ->orderByRaw(
                    '
                    YEAR(disposal_disposed_at),
                    MONTH(disposal_disposed_at)
                    '
                )

                ->get()

                ->keyBy(function ($row) {

                    return
                        $row->disposal_year
                        . '-'
                        . str_pad(
                            $row->disposal_month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );

                });


        // =====================================================
        // BUILD COMPLETE 12 MONTH TREND
        // =====================================================

        $disposalMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $disposalMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyDisposalRows
                            ->get($key)
                            ->disposal_count
                        ?? 0
                    ),

            ]);

        }


        // =====================================================
        // RETURN DISPOSAL PAGE
        // =====================================================

        return view(
            'maintenance-personnel.disposal.index',

            compact(
                'disposals',
                'equipment',
                'categories',

                // =================================================
                // DISPOSAL DASHBOARD VARIABLES
                // =================================================

                'totalDisposalRecords',

                'damagedDisposals',
                'damagedDisposalsPercentage',

                'disposedEquipment',
                'totalEquipment',
                'disposedEquipmentPercentage',

                'currentMonthDisposals',
                'previousMonthDisposals',
                'disposalMonthlyPercentage',

                'disposalMonthlyTrend'
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



    /*
    |--------------------------------------------------------------------------
    | REPORTERS MODULE E - 5
    |--------------------------------------------------------------------------
    */


    // =====================================================
    // REUSABLE REPORTER DASHBOARD DATA
    // ADD DIRECTLY ABOVE reporters()
    // =====================================================

    private function reporterDashboardData()
    {
        // =====================================================
        // TOTAL REPORTERS
        // =====================================================

        $totalReporters = DB::table('reporters_table')
            ->count();


        // =====================================================
        // REPORTERS WITH EMAIL
        // =====================================================

        $reportersWithEmail = DB::table('reporters_table')

            ->whereNotNull(
                'reporter_email_address'
            )

            ->where(
                'reporter_email_address',
                '!=',
                ''
            )

            ->count();


        // =====================================================
        // REPORTERS WITH CONTACT
        // =====================================================

        $reportersWithContact = DB::table('reporters_table')

            ->whereNotNull(
                'reporter_contact_number'
            )

            ->where(
                'reporter_contact_number',
                '!=',
                ''
            )

            ->count();


        // =====================================================
        // CURRENT MONTH REPORTERS
        // =====================================================

        $currentMonthReporters = DB::table('reporters_table')

            ->whereBetween(
                'reporter_created_at',
                [
                    now()->copy()->startOfMonth(),
                    now()->copy()->endOfMonth(),
                ]
            )

            ->count();


        // =====================================================
        // PREVIOUS MONTH REPORTERS
        // =====================================================

        $previousMonthReporters = DB::table('reporters_table')

            ->whereBetween(
                'reporter_created_at',
                [
                    now()
                        ->copy()
                        ->subMonthNoOverflow()
                        ->startOfMonth(),

                    now()
                        ->copy()
                        ->subMonthNoOverflow()
                        ->endOfMonth(),
                ]
            )

            ->count();


        // =====================================================
        // MONTHLY PERCENTAGE CHANGE
        // =====================================================

        if ($previousMonthReporters > 0) {

            $reporterMonthlyPercentage =
                (
                    (
                        $currentMonthReporters
                        - $previousMonthReporters
                    )
                    / $previousMonthReporters
                )
                * 100;

        } elseif ($currentMonthReporters > 0) {

            $reporterMonthlyPercentage = null;

        } else {

            $reporterMonthlyPercentage = 0;

        }


        // =====================================================
        // EMAIL COVERAGE PERCENTAGE
        // =====================================================

        $emailCoveragePercentage =
            $totalReporters > 0
                ? ($reportersWithEmail / $totalReporters) * 100
                : 0;


        // =====================================================
        // CONTACT COVERAGE PERCENTAGE
        // =====================================================

        $contactCoveragePercentage =
            $totalReporters > 0
                ? ($reportersWithContact / $totalReporters) * 100
                : 0;


        // =====================================================
        // REPORTERS PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        $monthlyReporterRows = DB::table('reporters_table')

            ->selectRaw(
                '
                YEAR(reporter_created_at) AS reporter_year,
                MONTH(reporter_created_at) AS reporter_month,
                COUNT(*) AS reporter_count
                '
            )

            ->where(
                'reporter_created_at',
                '>=',
                now()
                    ->copy()
                    ->subMonths(11)
                    ->startOfMonth()
            )

            ->groupByRaw(
                '
                YEAR(reporter_created_at),
                MONTH(reporter_created_at)
                '
            )

            ->orderByRaw(
                '
                YEAR(reporter_created_at),
                MONTH(reporter_created_at)
                '
            )

            ->get()

            ->keyBy(function ($row) {

                return
                    $row->reporter_year
                    . '-'
                    . str_pad(
                        $row->reporter_month,
                        2,
                        '0',
                        STR_PAD_LEFT
                    );

            });


        // =====================================================
        // BUILD COMPLETE 12 MONTH TREND
        // =====================================================

        $reporterMonthlyTrend = collect();


        for ($i = 11; $i >= 0; $i--) {

            $month = now()
                ->copy()
                ->subMonths($i)
                ->startOfMonth();


            $key = $month->format('Y-m');


            $reporterMonthlyTrend->push([

                'month' =>
                    $month->format('M'),

                'count' =>
                    (int) (
                        $monthlyReporterRows
                            ->get($key)
                            ->reporter_count
                        ?? 0
                    ),

            ]);

        }


        // =====================================================
        // RETURN ALL REPORTER DASHBOARD VARIABLES
        // =====================================================

        return compact(
            'totalReporters',
            'reportersWithEmail',
            'reportersWithContact',
            'currentMonthReporters',
            'previousMonthReporters',
            'reporterMonthlyPercentage',
            'emailCoveragePercentage',
            'contactCoveragePercentage',
            'reporterMonthlyTrend'
        );
    }


    public function reporters(Request $request)
    {
        // =====================================================
        // REPORTER HISTORY MODE VARIABLES
        // =====================================================

        $historyReporter = null;

        $reportHistory = null;


        // =====================================================
        // CHECK IF REPORTER HISTORY MODE IS ACTIVE
        // =====================================================

        if ($request->filled('history')) {

            // =================================================
            // GET SELECTED REPORTER
            // =================================================

            $historyReporter = DB::table('reporters_table')

                ->where(
                    'reporter_id',
                    $request->history
                )

                ->first();


            // =================================================
            // REPORTER NOT FOUND
            // =================================================

            if (!$historyReporter) {

                return redirect()

                    ->to('/maintenance/reporters')

                    ->with(
                        'error',
                        'Reporter not found.'
                    );

            }


            // =================================================
            // BUILD REPORTER HISTORY QUERY
            // =================================================

            $historyQuery = DB::table('reports_table')

                ->leftJoin(
                    'equipment_table',
                    'reports_table.report_equipment_id',
                    '=',
                    'equipment_table.equipment_id'
                )

                ->leftJoin(
                    'rooms_table',
                    'reports_table.report_room_id',
                    '=',
                    'rooms_table.room_id'
                )

                ->where(
                    'reports_table.report_reporter_employee_id',
                    $historyReporter->reporter_employee_id
                )

                ->select(

                    'reports_table.*',

                    'equipment_table.equipment_name',

                    'rooms_table.room_name'

                );


            // =================================================
            // REPORTER HISTORY SEARCH
            // =================================================

            if ($request->filled('history_search')) {

                $historySearch = trim(
                    $request->history_search
                );


                $historyQuery->where(

                    function ($query) use ($historySearch) {

                        $query

                            // =================================
                            // REPORT ID
                            // =================================

                            ->whereRaw(
                                'CAST(reports_table.report_id AS CHAR) LIKE ?',
                                ['%' . $historySearch . '%']
                            )


                            // =================================
                            // EQUIPMENT NAME
                            // =================================

                            ->orWhere(
                                'equipment_table.equipment_name',
                                'LIKE',
                                '%' . $historySearch . '%'
                            )


                            // =================================
                            // UNLISTED EQUIPMENT NAME
                            // =================================

                            ->orWhere(
                                'reports_table.report_unlisted_equipment_name',
                                'LIKE',
                                '%' . $historySearch . '%'
                            )


                            // =================================
                            // SUGGESTED ISSUE
                            // =================================

                            ->orWhere(
                                'reports_table.report_suggested_issue',
                                'LIKE',
                                '%' . $historySearch . '%'
                            )


                            // =================================
                            // PROBLEM DESCRIPTION
                            // =================================

                            ->orWhere(
                                'reports_table.report_problem_description',
                                'LIKE',
                                '%' . $historySearch . '%'
                            )


                            // =================================
                            // ROOM NAME
                            // =================================

                            ->orWhere(
                                'rooms_table.room_name',
                                'LIKE',
                                '%' . $historySearch . '%'
                            );

                    }

                );

            }


            // =================================================
            // REPORTER HISTORY STATUS FILTER
            // =================================================

            if (
                $request->filled('history_status')

                && in_array(

                    $request->history_status,

                    [
                        'Pending',
                        'Processing',
                        'Resolved',
                        'For Replacement',
                        'Rejected',
                    ],

                    true

                )
            ) {

                $historyQuery->where(

                    'reports_table.report_current_status',

                    $request->history_status

                );

            }


            // =================================================
            // PAGINATE REPORTER HISTORY
            //
            // USE A DIFFERENT PAGE PARAMETER FROM REPORTERS
            // =================================================

            $reportHistory = $historyQuery

                ->orderBy(
                    'reports_table.report_submitted_at',
                    'desc'
                )

                ->paginate(
                    10,
                    ['*'],
                    'history_page'
                )

                ->withQueryString();


            // =================================================
            // DO NOT RUN REPORTER DIRECTORY SEARCH OR PAGINATION
            //
            // HISTORY MODE ONLY NEEDS REPORTER HISTORY DATA
            // =================================================

            $reporters = null;

        } else {

            // =====================================================
            // NORMAL REPORTER DIRECTORY QUERY
            // =====================================================

            $query = DB::table('reporters_table');


            // =====================================================
            // REPORTER SEARCH
            // =====================================================

            if ($request->filled('search')) {

                $search = trim(
                    $request->search
                );


                $query->where(

                    function ($q) use ($search) {

                        $q

                            ->where(
                                'reporter_employee_id',
                                'LIKE',
                                '%' . $search . '%'
                            )

                            ->orWhere(
                                'reporter_full_name',
                                'LIKE',
                                '%' . $search . '%'
                            )

                            ->orWhere(
                                'reporter_email_address',
                                'LIKE',
                                '%' . $search . '%'
                            )

                            ->orWhere(
                                'reporter_contact_number',
                                'LIKE',
                                '%' . $search . '%'
                            );

                    }

                );

            }


            // =====================================================
            // REPORTER STATUS FILTER
            // =====================================================

            if (
                $request->filled('status')

                && in_array(

                    $request->status,

                    [
                        'Active',
                        'Inactive',
                    ],

                    true

                )
            ) {

                $query->where(

                    'reporter_status',

                    $request->status

                );

            }


            // =====================================================
            // PAGINATE REPORTERS
            // =====================================================

            $reporters = $query

                ->orderBy(
                    'reporter_full_name',
                    'asc'
                )

                ->paginate(
                    10,
                    ['*'],
                    'page'
                )

                ->withQueryString();

        }


        // =====================================================
        // RETURN SAME REPORTERS PAGE
        // =====================================================

        return view(

            'maintenance-personnel.reporters.index',

            array_merge(

                [
                    'reporters' => $reporters,

                    'historyReporter' => $historyReporter,

                    'reportHistory' => $reportHistory,
                ],

                $this->reporterDashboardData()

            )

        );
    }

    // =====================================================
    // DEACTIVATE REPORTER
    // =====================================================

    public function deactivateReporter($id)
    {
        // =====================================================
        // FIND REPORTER
        // =====================================================

        $reporter = DB::table('reporters_table')
            ->where('reporter_id', $id)
            ->first();


        if (!$reporter) {

            return back()->with(
                'error',
                'Reporter not found.'
            );

        }


        // =====================================================
        // PREVENT UNNECESSARY UPDATE
        // =====================================================

        if ($reporter->reporter_status === 'Inactive') {

            return back()->with(
                'error',
                'Reporter is already inactive.'
            );

        }


        // =====================================================
        // DEACTIVATE REPORTER
        // RECORD REMAINS IN DATABASE
        // =====================================================

        DB::table('reporters_table')

            ->where('reporter_id', $id)

            ->update([

                'reporter_status' => 'Inactive',

            ]);


        return back()->with(
            'success',
            'Reporter deactivated successfully.'
        );
    }


    // =====================================================
    // REACTIVATE REPORTER
    // =====================================================

    public function reactivateReporter($id)
    {
        // =====================================================
        // FIND REPORTER
        // =====================================================

        $reporter = DB::table('reporters_table')
            ->where('reporter_id', $id)
            ->first();


        if (!$reporter) {

            return back()->with(
                'error',
                'Reporter not found.'
            );

        }


        // =====================================================
        // PREVENT UNNECESSARY UPDATE
        // =====================================================

        if ($reporter->reporter_status === 'Active') {

            return back()->with(
                'error',
                'Reporter is already active.'
            );

        }


        // =====================================================
        // REACTIVATE REPORTER
        // =====================================================

        DB::table('reporters_table')

            ->where('reporter_id', $id)

            ->update([

                'reporter_status' => 'Active',

            ]);


        return back()->with(
            'success',
            'Reporter reactivated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE REPORTER
    |--------------------------------------------------------------------------
    */

    public function storeReport(Request $request)
    {
        // =====================================================
        // VALIDATE REPORT FORM
        // =====================================================

        $request->validate([
            'report_reporter_employee_id' => 'required|string',
            'report_room_id' => 'required|integer',
            'report_equipment_id' => 'nullable|integer',
            'report_equipment_manual' => 'nullable|string|max:255',
            'report_suggested_issue' => 'nullable|string|max:255',
            'report_problem_description' => 'nullable|string',
            'report_urgency_level' => 'required|in:Urgent,Non-Urgent',
            'report_uploaded_image' => 'nullable|image|max:5120',
        ]);


        // =====================================================
        // FIND REPORTER
        // =====================================================

        $reporter = DB::table('reporters_table')

            ->where(
                'reporter_employee_id',
                $request->report_reporter_employee_id
            )

            ->first();


        // =====================================================
        // REPORTER DOES NOT EXIST
        // =====================================================

        if (!$reporter) {

            return back()

                ->withErrors([
                    'report_reporter_employee_id'
                        => 'Employee ID not recognized.',
                ])

                ->withInput();
        }


        // =====================================================
        // BLOCK INACTIVE REPORTER
        // =====================================================

        if ($reporter->reporter_status !== 'Active') {

            return back()

                ->withErrors([
                    'report_reporter_employee_id'
                        => 'Reporter account is inactive. You cannot submit reports.',
                ])

                ->withInput();
        }


        // =====================================================
        // VALIDATE EQUIPMENT SELECTION
        // MUST SELECT EQUIPMENT OR ENTER MANUALLY
        // =====================================================

        if (
            !$request->filled('report_equipment_id')
            && !$request->filled('report_equipment_manual')
        ) {

            return back()

                ->withErrors([
                    'report_equipment_id'
                        => 'Please select or enter an equipment.',
                ])

                ->withInput();
        }


        // =====================================================
        // UPLOAD REPORT IMAGE
        // =====================================================

        $imagePath = null;

        if ($request->hasFile('report_uploaded_image')) {

            $imagePath = $request
                ->file('report_uploaded_image')
                ->store(
                    'report-images',
                    'public'
                );
        }


        // =====================================================
        // INSERT REPORT
        // ONLY ACTIVE REPORTERS CAN REACH THIS PART
        // =====================================================

        DB::table('reports_table')
            ->insert([

                'report_reporter_employee_id'
                    => $reporter->reporter_employee_id,

                'report_room_id'
                    => $request->report_room_id,

                'report_equipment_id'
                    => $request->report_equipment_id,

                'report_unlisted_equipment_name'
                    => $request->report_equipment_manual,

                'report_problem_description'
                    => $request->report_problem_description,

                'report_suggested_issue'
                    => $request->report_suggested_issue,

                'report_urgency_level'
                    => $request->report_urgency_level,

                'report_current_status'
                    => 'Pending',

                'report_uploaded_image'
                    => $imagePath,

                'report_is_overdue'
                    => false,

                'report_is_archived'
                    => false,

                'report_submitted_at'
                    => now(),

                'report_updated_at'
                    => now(),

            ]);


        // =====================================================
        // RETURN SUCCESS
        // =====================================================

        return back()->with(
            'success',
            'Report submitted successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE REPORTER
    |--------------------------------------------------------------------------
    */

    public function updateReporter(Request $request)
    {
        DB::table('reporters_table')

            ->where(
                'reporter_id',
                $request->reporter_id
            )

            ->update([

                'reporter_employee_id'
                    => $request->employee_id,

                'reporter_full_name'
                    => $request->full_name,

                'reporter_email_address'
                    => $request->email,

                'reporter_contact_number'
                    => $request->contact

            ]);

        return back()->with(
            'success',
            'Reporter updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE REPORTER
    |--------------------------------------------------------------------------
    */

    public function deleteReporter(Request $request)
    {
        DB::table('reporters_table')

            ->where(
                'reporter_id',
                $request->reporter_id
            )

            ->delete();

        return back()->with(
            'success',
            'Reporter deleted successfully.'
        );
    }


    // =====================================================
    // REPORTER HISTORY
    // SHOWS ALL REPORTS SUBMITTED BY ONE REPORTER
    // =====================================================

    public function reporterHistory(
        Request $request,
        $id
    )
    {
        // =====================================================
        // GET REPORTER
        // =====================================================

        $reporter = DB::table('reporters_table')

            ->where(
                'reporter_id',
                $id
            )

            ->first();


        // =====================================================
        // REPORTER NOT FOUND
        // =====================================================

        if (!$reporter) {

            return redirect()

                ->to('/maintenance/reporters')

                ->with(
                    'error',
                    'Reporter not found.'
                );

        }


        // =====================================================
        // BUILD REPORT HISTORY QUERY
        // ONLY REPORTS SUBMITTED BY THIS REPORTER
        // =====================================================

        $query = DB::table('reports_table')

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->where(
                'reports_table.report_reporter_employee_id',
                $reporter->reporter_employee_id
            )

            ->select(

                'reports_table.*',

                'equipment_table.equipment_name',

                'rooms_table.room_name'

            );


        // =====================================================
        // SEARCH
        // SEARCHES ALL REPORTS OF THIS REPORTER
        // =====================================================

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'reports_table.report_id',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'reports_table.report_suggested_issue',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'reports_table.report_problem_description',
                    'LIKE',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'reports_table.report_unlisted_equipment_name',
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
                );

            });

        }


        // =====================================================
        // STATUS FILTER
        // VALIDATE ALLOWED REPORT STATUSES
        // =====================================================

        if (
            $request->filled('status')
            && in_array(
                $request->status,
                [
                    'Pending',
                    'Processing',
                    'Resolved',
                    'For Replacement',
                    'Rejected',
                ],
                true
            )
        ) {

            $query->where(
                'reports_table.report_current_status',
                $request->status
            );

        }


        // =====================================================
        // PAGINATE FILTERED REPORT HISTORY
        // =====================================================

        $reports = $query

            ->orderBy(
                'reports_table.report_submitted_at',
                'desc'
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // BASE QUERY FOR DASHBOARD COUNTS
        // COUNTS ARE NOT AFFECTED BY SEARCH OR STATUS FILTER
        // =====================================================

        $reporterReportsQuery = DB::table('reports_table')

            ->where(
                'report_reporter_employee_id',
                $reporter->reporter_employee_id
            );


        // =====================================================
        // TOTAL REPORTS
        // =====================================================

        $totalReports =

            (clone $reporterReportsQuery)

                ->count();


        // =====================================================
        // PENDING REPORTS
        // =====================================================

        $pendingReports =

            (clone $reporterReportsQuery)

                ->where(
                    'report_current_status',
                    'Pending'
                )

                ->count();


        // =====================================================
        // PROCESSING REPORTS
        // =====================================================

        $processingReports =

            (clone $reporterReportsQuery)

                ->where(
                    'report_current_status',
                    'Processing'
                )

                ->count();


        // =====================================================
        // RESOLVED REPORTS
        // =====================================================

        $resolvedReports =

            (clone $reporterReportsQuery)

                ->where(
                    'report_current_status',
                    'Resolved'
                )

                ->count();


        // =====================================================
        // FOR REPLACEMENT REPORTS
        // =====================================================

        $replacementReports =

            (clone $reporterReportsQuery)

                ->where(
                    'report_current_status',
                    'For Replacement'
                )

                ->count();


        // =====================================================
        // REJECTED REPORTS
        // =====================================================

        $rejectedReports =

            (clone $reporterReportsQuery)

                ->where(
                    'report_current_status',
                    'Rejected'
                )

                ->count();


        // =====================================================
        // RETURN REPORTER HISTORY PAGE
        // =====================================================

        return view(
            'maintenance-personnel.reporters.history',

            compact(
                'reporter',
                'reports',
                'totalReports',
                'pendingReports',
                'processingReports',
                'resolvedReports',
                'replacementReports',
                'rejectedReports'
            )
        );
    }



















    // =====================================================
    // ALERTS AND ACTIVITY CENTER
    // =====================================================

    // =====================================================
    // ALERTS AND ACTIVITY CENTER
    // =====================================================

    public function notifications(Request $request)
    {
        // =====================================================
        // CURRENT USER
        // =====================================================

        $userId = Auth::id();


        // =====================================================
        // FILTER VALUES
        // =====================================================

        $period =
            $request->get('period', 'today');

        $category =
            $request->get('category', 'all');


        // =====================================================
        // ALLOWED FILTERS
        // =====================================================

        $allowedPeriods = [
            'today',
            'week',
            'month',
            'year',
        ];

        $allowedCategories = [
            'all',
            'Reports',
            'Maintenance',
            'Equipment',
        ];


        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'today';
        }


        if (!in_array($category, $allowedCategories, true)) {
            $category = 'all';
        }


        // =====================================================
        // REUSABLE BASE QUERY
        // =====================================================

        $notificationQuery = function () use ($userId) {

            // =====================================================
            // BASE QUERY
            // =====================================================

            $query = DB::table('notifications_table')


                // =====================================================
                // CURRENT USER READ RECEIPT
                // =====================================================

                ->leftJoin(
                    'notification_reads_table',
                    function ($join) use ($userId) {

                        $join->on(
                            'notifications_table.notification_id',
                            '=',
                            'notification_reads_table.notification_id'
                        );

                        $join->where(
                            'notification_reads_table.user_id',
                            '=',
                            $userId
                        );

                    }
                );


            // =====================================================
            // APPLY ACCESS RULES
            // =====================================================

            return $this->applyMaintenanceNotificationAccess(
                $query,
                $userId
            );
        };


        // =====================================================
        // MAIN QUERY
        // =====================================================

        $query = $notificationQuery();


        // =====================================================
        // PERIOD FILTER
        // =====================================================

        switch ($period) {

            case 'week':

                $query->whereBetween(
                    'notifications_table.notification_created_at',
                    [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]
                );

                break;


            case 'month':

                $query
                    ->whereYear(
                        'notifications_table.notification_created_at',
                        now()->year
                    )

                    ->whereMonth(
                        'notifications_table.notification_created_at',
                        now()->month
                    );

                break;


            case 'year':

                $query->whereYear(
                    'notifications_table.notification_created_at',
                    now()->year
                );

                break;


            default:

                $query->whereDate(
                    'notifications_table.notification_created_at',
                    today()
                );

                break;

        }


        // =====================================================
        // CATEGORY FILTER
        // =====================================================

        if ($category !== 'all') {

            $query->where(
                'notifications_table.notification_category',
                $category
            );

        }


        // =====================================================
        // GET NOTIFICATIONS
        //
        // is_read = 1 IF CURRENT USER HAS A READ RECORD
        // =====================================================

        $notifications = $query

            ->select(
                'notifications_table.*'
            )

            ->selectRaw(
                'CASE
                    WHEN notification_reads_table.notification_read_id IS NULL
                    THEN 0
                    ELSE 1
                END AS is_read'
            )

            ->orderByDesc(
                'notifications_table.notification_created_at'
            )

            ->paginate(15)

            ->withQueryString();


        // =====================================================
        // COUNT QUERY WITHOUT JOIN
        // =====================================================

        $countQuery = function () use ($userId) {

            // =====================================================
            // BASE QUERY
            // =====================================================

            $query =
                DB::table('notifications_table');


            // =====================================================
            // APPLY ACCESS RULES
            // =====================================================

            return $this->applyMaintenanceNotificationAccess(
                $query,
                $userId
            );
        };


        // =====================================================
        // PERIOD COUNTS
        // =====================================================

        $todayCount = $countQuery()

            ->whereDate(
                'notification_created_at',
                today()
            )

            ->count();


        $weekCount = $countQuery()

            ->whereBetween(
                'notification_created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]
            )

            ->count();


        $monthCount = $countQuery()

            ->whereYear(
                'notification_created_at',
                now()->year
            )

            ->whereMonth(
                'notification_created_at',
                now()->month
            )

            ->count();


        $yearCount = $countQuery()

            ->whereYear(
                'notification_created_at',
                now()->year
            )

            ->count();


        // =====================================================
        // UNREAD COUNT FOR CURRENT USER
        // =====================================================

        $unreadCount = $countQuery()

            ->whereNotExists(function ($query) use ($userId) {

                $query
                    ->select(DB::raw(1))

                    ->from('notification_reads_table')

                    ->whereColumn(
                        'notification_reads_table.notification_id',
                        'notifications_table.notification_id'
                    )

                    ->where(
                        'notification_reads_table.user_id',
                        $userId
                    );

            })

            ->count();


        // =====================================================
        // RETURN PAGE
        // =====================================================

        return view(
            'maintenance-personnel.notifications.index',
            compact(
                'notifications',
                'period',
                'category',
                'todayCount',
                'weekCount',
                'monthCount',
                'yearCount',
                'unreadCount'
            )
        );
    }

    // =====================================================
    // OPEN NOTIFICATION
    // =====================================================

    // =====================================================
    // OPEN NOTIFICATION
    // =====================================================

    // =====================================================
    // OPEN NOTIFICATION
    // =====================================================

    public function openNotification($id)
    {
        // =====================================================
        // CURRENT USER
        // =====================================================

        $userId = Auth::id();


        // =====================================================
        // STOP IF USER IS NOT AUTHENTICATED
        // =====================================================

        if (!$userId) {
            abort(401);
        }


        // =====================================================
        // BASE NOTIFICATION QUERY
        // =====================================================

        $query = DB::table('notifications_table')

            ->where(
                'notifications_table.notification_id',
                $id
            );


        // =====================================================
        // APPLY MAINTENANCE NOTIFICATION ACCESS RULES
        // =====================================================

        $notification =
            $this->applyMaintenanceNotificationAccess(
                $query,
                $userId
            )
            ->first();


        // =====================================================
        // NOTIFICATION DOES NOT EXIST
        // OR USER CANNOT ACCESS IT
        // =====================================================

        if (!$notification) {
            abort(404);
        }


        // =====================================================
        // CREATE READ RECEIPT
        //
        // UNIQUE CONSTRAINT ON:
        // notification_id + user_id
        //
        // PREVENTS DUPLICATE READ RECEIPTS
        // =====================================================

        DB::table('notification_reads_table')

            ->insertOrIgnore([

                'notification_id' =>
                    $notification->notification_id,

                'user_id' =>
                    $userId,

                'notification_read_at' =>
                    now(),

            ]);


        // =====================================================
        // GET DESTINATION URL
        // =====================================================

        $destination =
            $notification->notification_url;


        // =====================================================
        // NO DESTINATION URL
        // RETURN TO NOTIFICATIONS PAGE
        // =====================================================

        if (!$destination) {

            return redirect(
                '/maintenance/notifications'
            );

        }


        // =====================================================
        // SECURITY CHECK
        //
        // ONLY ALLOW INTERNAL MAINTENANCE ROUTES
        // =====================================================

        if (!str_starts_with(
            $destination,
            '/maintenance/'
        )) {

            return redirect(
                '/maintenance/notifications'
            );

        }


        // =====================================================
        // REDIRECT TO RELATED RECORD
        // =====================================================

        return redirect($destination);
    }


    // =====================================================
    // MARK ALL ACCESSIBLE NOTIFICATIONS AS READ
    // =====================================================

    public function markAllNotificationsAsRead()
    {
        // =====================================================
        // CURRENT USER
        // =====================================================

        $userId = Auth::id();


        // =====================================================
        // STOP IF USER IS NOT AUTHENTICATED
        // =====================================================

        if (!$userId) {
            abort(401);
        }


        // =====================================================
        // BASE NOTIFICATION QUERY
        // =====================================================

        $query =
            DB::table('notifications_table');


        // =====================================================
        // APPLY SAME ACCESS RULES USED BY:
        //
        // notifications()
        // openNotification()
        // =====================================================

        $query =
            $this->applyMaintenanceNotificationAccess(
                $query,
                $userId
            );


        // =====================================================
        // GET ONLY ACCESSIBLE UNREAD NOTIFICATION IDS
        // =====================================================

        $notificationIds = $query

            ->whereNotExists(function ($query) use ($userId) {

                // =====================================================
                // CHECK IF CURRENT USER ALREADY HAS READ RECEIPT
                // =====================================================

                $query
                    ->select(DB::raw(1))

                    ->from('notification_reads_table')

                    ->whereColumn(
                        'notification_reads_table.notification_id',
                        'notifications_table.notification_id'
                    )

                    ->where(
                        'notification_reads_table.user_id',
                        $userId
                    );

            })


            // =====================================================
            // GET NOTIFICATION IDS ONLY
            // =====================================================

            ->pluck(
                'notifications_table.notification_id'
            );


        // =====================================================
        // NOTHING TO MARK AS READ
        // =====================================================

        if ($notificationIds->isEmpty()) {

            return back()->with(
                'success',
                'All notifications are already read.'
            );

        }


        // =====================================================
        // CURRENT TIMESTAMP
        // =====================================================

        $now = now();


        // =====================================================
        // BUILD READ RECEIPT ROWS
        // =====================================================

        $readRows = $notificationIds

            ->map(function ($notificationId) use (
                $userId,
                $now
            ) {

                return [

                    'notification_id' =>
                        $notificationId,

                    'user_id' =>
                        $userId,

                    'notification_read_at' =>
                        $now,

                ];

            })

            ->all();


        // =====================================================
        // INSERT READ RECEIPTS
        //
        // insertOrIgnore() IS AN EXTRA SAFETY CHECK
        // IF THE UNIQUE CONSTRAINT ALREADY EXISTS
        // =====================================================

        DB::table('notification_reads_table')

            ->insertOrIgnore($readRows);


        // =====================================================
        // RETURN TO NOTIFICATIONS PAGE
        // =====================================================

        return back()->with(
            'success',
            'All notifications marked as read.'
        );
    }

    private function applyMaintenanceNotificationAccess(
        $query,
        $userId
    )
    {
        return $query->where(function ($query) use ($userId) {

            // =====================================================
            // PERSONAL NOTIFICATION
            // =====================================================

            $query->where(
                'notifications_table.notification_user_id',
                $userId
            )


            // =====================================================
            // OR MAINTENANCE PERSONNEL BROADCAST
            // =====================================================

            ->orWhere(function ($query) {

                $query
                    ->whereNull(
                        'notifications_table.notification_user_id'
                    )

                    ->where(
                        'notifications_table.notification_target_role',
                        'Maintenance Personnel'
                    );

            });

        });
    }
}
