<?php

namespace App\Http\Controllers;

use App\Services\ReportSubmissionService;
use App\Support\Custodians;
use App\Support\Departments;
use App\Support\EquipmentTimeline;
use App\Support\EquipmentAcquisition;
use App\Support\EquipmentBatchIntake;
use App\Support\AdminPortal;
use App\Support\IssueChecks;
use App\Support\MaintenanceAttentionSummary;
use App\Support\ReportGrouping;
use App\Support\ReplacementRequestBasket;
use App\Support\ReplacementStock;
use App\Support\ReportItems;
use App\Support\PersonNames;
use App\Support\ReporterApprovals;
use App\Support\ReporterImport;
use App\Support\ReporterPins;
use App\Support\ReportSeverity;
use App\Support\RoomCategories;
use App\Support\RoomDepartments;
use App\Support\RoomName;
use App\Support\RoomStatuses;
use App\Support\SuggestedIssues;
use App\Support\EquipmentQrCodes;
use App\Support\EquipmentViewReturn;
use App\Support\ReportDetailsReturn;
use App\Support\EquipmentLifecycle;
use App\Support\EquipmentAuditPack;
use App\Support\EquipmentConditionHistory;
use App\Support\EquipmentMonitoring;
use App\Support\EquipmentNextMaintenance;
use App\Support\EquipmentOpenBalance;
use App\Support\EquipmentTrackingModes;
use App\Support\EquipmentSchedules;
use App\Support\PropertyAssignments;
use App\Support\ReceivableStockLines;
use App\Support\ReceivableStockImporter;
use App\Support\SemesterInspections;
use App\Support\StockMovementHistory;
use App\Support\ReplacementDeliveries;
use App\Support\RoleAccess;
use App\Support\TransferDestinations;
use App\Support\DisposalRequests;
use App\Support\TransferLocks;
use App\Support\TransferRequests;
use App\Support\WorkflowNotifier;
use App\Support\BuildingLayout3D;
use App\Models\RoomActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
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

        // =====================================================
        // TOTAL EQUIPMENT
        // USED BY EQUIPMENT STATISTICS CARD
        // =====================================================

        $totalEquipment = DB::table('equipment_table')
            ->where(
                'equipment_inventory_status',
                '!=',
                'Disposed'
            )
            ->count();


        $borrowedEquipment = DB::table('borrowing_records_table')
            ->where('borrowing_status', 'Borrowed')
            ->count();

        DB::table('borrowing_records_table')
            ->where('borrowing_status', 'Borrowed')
            ->whereNotNull('borrowing_expected_return_date')
            ->whereDate('borrowing_expected_return_date', '<', today())
            ->update(['borrowing_status' => 'Overdue']);


        // =====================================================
        // PENDING REPORTS SUBMITTED TODAY
        // =====================================================

        $pendingReportsToday = DB::table('reports_table')
            ->where('report_current_status', 'Pending')
            ->whereDate('report_submitted_at', today())
            ->where('report_is_archived', false)
            ->count();


        // =====================================================
        // MARK OPEN REPORTS AS OVERDUE
        // Submitted before today and still Pending / Processing
        // =====================================================

        $closedReportStatuses = \App\Support\ReportStatuses::closed();

        DB::table('reports_table')
            ->whereIn('report_current_status', $closedReportStatuses)
            ->where('report_is_overdue', true)
            ->update(['report_is_overdue' => false]);

        DB::table('reports_table')
            ->where('report_urgency_level', 'Urgent')
            ->whereIn('report_current_status', ['Pending', 'Processing'])
            ->where('report_is_archived', false)
            ->whereDate('report_submitted_at', '<', today())
            ->where('report_is_overdue', false)
            ->update(['report_is_overdue' => true]);

        DB::table('reports_table')
            ->where('report_urgency_level', 'Non-Urgent')
            ->whereIn('report_current_status', ['Pending', 'Processing'])
            ->where('report_is_overdue', true)
            ->update(['report_is_overdue' => false]);

        ReportGrouping::applyNonUrgentReminderWindow(
            DB::table('reports_table')
                ->where('report_urgency_level', 'Non-Urgent')
                ->whereIn('report_current_status', ['Pending', 'Processing'])
                ->where('report_is_archived', false)
                ->where('report_is_overdue', false)
        )->update(['report_is_overdue' => true]);


        // =====================================================
        // DAILY REMINDER COUNTS
        // Urgent: pending or overdue
        // Non-urgent: remind from preferred date, or after 2 days (Medium) / 5 days (Low) if none
        // =====================================================

        $attentionCounts = MaintenanceAttentionSummary::counts();
        $urgentReportsNeedingAction = $attentionCounts['urgentReportsNeedingAction'];
        $nonUrgentReportsNeedingAction = $attentionCounts['nonUrgentReportsNeedingAction'];
        $overdueMaintenance = $attentionCounts['overdueMaintenance'];


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
        // DASHBOARD QUICK ACTION MODAL DATA
        // =====================================================


        // =====================================================
        // EQUIPMENT CATEGORIES
        // USED BY ADD EQUIPMENT MODAL
        // =====================================================

        $categories = DB::table('equipment_categories_table')
            ->orderBy('equipment_category_name', 'asc')
            ->get();

        $usedAssetTags = DB::table('equipment_table')
            ->whereNotNull('equipment_asset_tag')
            ->where('equipment_asset_tag', '!=', '')
            ->whereNotIn('equipment_inventory_status', ['Disposed'])
            ->pluck('equipment_asset_tag');


        // =====================================================
        // EQUIPMENT
        // USED BY SCHEDULE AND BORROWING MODALS
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->select(
                'equipment_table.*',
                'rooms_table.room_name'
            )

            ->where(
                'equipment_table.equipment_inventory_status',
                '!=',
                'Disposed'
            )

            ->orderBy(
                'equipment_table.equipment_name',
                'asc'
            )

            ->get();

        // Create-schedule modal: Active equipment with a generated QR (matches Schedules module)
        $scheduleEquipment = DB::table('equipment_table')
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->where('equipment_inventory_status', 'Active')
            ->whereNotNull('equipment_table.equipment_qr_code')
            ->where('equipment_table.equipment_qr_code', '!=', '')
            ->select(
                'equipment_table.*',
                'rooms_table.room_name'
            )
            ->orderBy('equipment_name')
            ->get();

        $scheduleEquipmentJson = $scheduleEquipment->map(fn ($item) => [
            'id' => (int) $item->equipment_id,
            'name' => (string) $item->equipment_name,
            'room' => (string) ($item->room_name ?? ''),
            'qr' => (string) ($item->equipment_qr_code ?? ''),
            'assetTag' => (string) ($item->equipment_asset_tag ?? ''),
        ])->values();


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

            'labels' => \App\Support\ReportStatuses::ALL,

            'data' => collect(\App\Support\ReportStatuses::ALL)
                ->map(fn (string $label) => (int) $reportStatusRows->get($label, 0))
                ->all(),

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

        $rooms = BuildingLayout3D::rooms();

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

        // =====================================================
        // RECENT ACTIVITIES
        // REAL MAINTENANCE PERSONNEL AUDIT LOGS
        // =====================================================

        $recentActivities = DB::table('audit_logs_table')

            // =================================================
            // ONLY ACTIVITIES PERFORMED BY CURRENT USER
            // =================================================

            ->where(
                'audit_log_user_id',
                Auth::id()
            )

            // =================================================
            // ONLY MAINTENANCE RELATED ACTIVITIES
            //
            // THIS PREVENTS PURCHASER OR OTHER MODULE LOGS
            // FROM APPEARING HERE.
            // =================================================

            ->whereNotNull(
                'audit_log_module'
            )

            // =================================================
            // NEWEST ACTIVITIES FIRST
            // =================================================

            ->orderByDesc(
                'audit_log_created_at'
            )

            ->limit(5)

            ->get()

            // =================================================
            // CONVERT AUDIT LOG DATA INTO THE FORMAT EXPECTED
            // BY YOUR EXISTING DASHBOARD.BLADE.PHP
            // =================================================

            ->map(function ($activity) {

                // =================================================
                // BASIC ACTIVITY INFORMATION
                // =================================================

                $activity->title =
                    $activity->audit_log_action
                    ?? 'System Activity';

                $activity->description =
                    $activity->audit_log_description
                    ?? 'Activity recorded.';

                $activity->created_at =
                    $activity->audit_log_created_at;


                // =================================================
                // REPORT ID
                //
                // ONLY SET THIS WHEN THE ACTIVITY BELONGS
                // TO REPORTS_TABLE.
                // =================================================

                $activity->report_id =
                    $activity->audit_log_table_name === 'reports_table'
                        ? $activity->audit_log_reference_id
                        : null;

                // =================================================
                // ACTIVITY DESTINATION
                //
                // DETERMINE WHERE THE VIEW BUTTON SHOULD GO
                // BASED ON THE AUDIT LOG SOURCE.
                // =================================================

                $activity->url = match (
                    $activity->audit_log_table_name
                ) {

                    'reports_table' =>
                        $activity->audit_log_reference_id
                            ? url(
                                '/maintenance/reports/details/'
                                . $activity->audit_log_reference_id
                            )
                            : null,

                    'equipment_table' =>
                        $activity->audit_log_reference_id
                            ? url(
                                '/maintenance/equipment/view/'
                                . $activity->audit_log_reference_id
                            )
                            : null,

                    'maintenance_schedules_table' =>
                        url('/maintenance/schedules'),

                    'borrowing_records_table' =>
                        RoleAccess::isAdmin(auth()->user()) ? url('/admin/borrowing') : null,

                    'rooms_table' =>
                        $activity->audit_log_reference_id
                            ? url('/maintenance/property-assignments/rooms/'.$activity->audit_log_reference_id)
                            : null,

                    default =>
                        null,
                };


                // =================================================
                // ICON BASED ON MODULE
                // =================================================

                $activity->icon = match (
                    $activity->audit_log_module
                ) {

                    'Reports' =>
                        'clipboard-list',

                    'Equipment' =>
                        'monitor',

                    'Schedules' =>
                        'calendar',

                    'Borrowing' =>
                        'package',

                    'Infrastructure' =>
                        'building-2',

                    default =>
                        'activity',
                };


                // =================================================
                // DEFAULT ICON APPEARANCE
                // =================================================

                $activity->background =
                    '#f3f4f6';

                $activity->color =
                    '#374151';


                return $activity;
            });

        // =====================================================
        // ACTIVITY PREVIEW
        // LATEST 15 ACTIVITIES FOR DASHBOARD MODAL
        // =====================================================

        $activityPreview = DB::table('audit_logs_table')

            // =================================================
            // ONLY CURRENT LOGGED IN MAINTENANCE PERSONNEL
            // =================================================

            ->where(
                'audit_log_user_id',
                Auth::id()
            )

            // =================================================
            // ONLY MODULE BASED ACTIVITIES
            // =================================================

            ->whereNotNull(
                'audit_log_module'
            )

            // =================================================
            // NEWEST FIRST
            // =================================================

            ->orderByDesc(
                'audit_log_created_at'
            )

            ->limit(15)

            ->get()

            // =================================================
            // PREPARE FOR DASHBOARD MODAL
            // =================================================

            ->map(function ($activity) {

                // =================================================
                // ACTIVITY INFORMATION
                // =================================================

                $activity->title =
                    $activity->audit_log_action
                    ?? 'System Activity';

                $activity->description =
                    $activity->audit_log_description
                    ?? 'Activity recorded.';

                $activity->created_at =
                    $activity->audit_log_created_at;


                // =================================================
                // ICON
                // =================================================

                $activity->icon = match (
                    $activity->audit_log_module
                ) {
                    'Reports' =>
                        'clipboard-list',

                    'Equipment' =>
                        'monitor',

                    'Schedules' =>
                        'calendar',

                    'Borrowing' =>
                        'package',

                    'Infrastructure' =>
                        'building-2',

                    default =>
                        'activity',
                };


                // =================================================
                // ACTIVITY DESTINATION
                // =================================================

                $activity->url = match (
                    $activity->audit_log_table_name
                ) {

                    'reports_table' =>
                        $activity->audit_log_reference_id
                            ? url(
                                '/maintenance/reports/details/'
                                . $activity->audit_log_reference_id
                            )
                            : null,

                    'equipment_table' =>
                        $activity->audit_log_reference_id
                            ? url(
                                '/maintenance/equipment/view/'
                                . $activity->audit_log_reference_id
                            )
                            : null,

                    'maintenance_schedules_table' =>
                        url('/maintenance/schedules'),

                    'borrowing_records_table' =>
                        RoleAccess::isAdmin(auth()->user()) ? url('/admin/borrowing') : null,

                    'rooms_table' =>
                        $activity->audit_log_reference_id
                            ? url('/maintenance/property-assignments/rooms/'.$activity->audit_log_reference_id)
                            : null,

                    default =>
                        null,
                };


                return $activity;
            });

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

            'labels' => \App\Support\EquipmentConditions::ALL,

            'data' => collect(\App\Support\EquipmentConditions::ALL)
                ->map(fn (string $label) => (int) $equipmentConditionRows->get($label, 0))
                ->all(),

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
        // LIFECYCLE + SEMESTER INSPECTION SNAPSHOTS
        // =====================================================

        $lifecycleAlerts = EquipmentLifecycle::agingAlerts(5);
        $warrantyAlerts = EquipmentMonitoring::warrantyAlerts(5);
        $semesterInspectionDue = SemesterInspections::activeCampaignsDueSoon(7, 4);
        $metricsDashboard = $this->dashboardMetricsBundle();


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

                'urgentReportsNeedingAction',

                'nonUrgentReportsNeedingAction',

                'urgentReports',

                'totalEquipment',

                'underMaintenance',

                'borrowedEquipment',

                'overdueMaintenance',


                // =================================================
                // EXISTING DASHBOARD DATA
                // =================================================

                'urgentReportList',

                'recentActivities',

                'activityPreview',

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

                'categories',

                'rooms',

                'equipment',

                'scheduleEquipment',

                'scheduleEquipmentJson',

                'usedAssetTags',

                'lifecycleAlerts',
                'warrantyAlerts',

                'semesterInspectionDue',

                'metricsDashboard',

            )

        );
    }

    public function buildingLayout(): View
    {
        $floors = BuildingLayout3D::floors();
        $roomsByFloor = BuildingLayout3D::rooms()->groupBy('floor_id');

        return view('maintenance-personnel.building-layout', compact('floors', 'roomsByFloor'));
    }

    /**
     * Period / year slices for the dashboard Metrics + Active Workload widgets.
     *
     * @return array{
     *     period: string,
     *     periods: array<string, array<string, mixed>>,
     *     years: array<int, array<string, mixed>>,
     *     periodLabels: array<string, string>
     * }
     */
    private function dashboardMetricsBundle(): array
    {
        $now = now();
        $yearNow = (int) $now->format('Y');

        $buildSlice = function ($from, $to): array {
            $statusQuery = DB::table('reports_table')
                ->where('report_is_archived', false);

            if ($from && $to) {
                $statusQuery->whereBetween('report_submitted_at', [$from, $to]);
            }

            $statusRows = $statusQuery
                ->select('report_current_status')
                ->selectRaw('COUNT(*) AS status_count')
                ->groupBy('report_current_status')
                ->pluck('status_count', 'report_current_status');

            $pending = (int) $statusRows->get('Pending', 0);
            $processing = (int) $statusRows->get('Processing', 0);
            $resolved = (int) $statusRows->get('Resolved', 0);
            $replacement = (int) $statusRows->get('For Replacement', 0);
            $rejected = (int) $statusRows->get('Rejected', 0);
            $submitted = $pending + $processing + $resolved + $replacement + $rejected;
            $accepted = max(0, $submitted - $rejected);
            $actioned = $processing + $resolved + $replacement;
            $closed = $resolved + $replacement;

            $overdueQuery = DB::table('maintenance_schedules_table')
                ->where(function ($query) {
                    $query
                        ->where('maintenance_schedule_status', 'Overdue')
                        ->orWhere(function ($activePastDue) {
                            $activePastDue
                                ->where('maintenance_schedule_status', 'Active')
                                ->whereDate(
                                    'maintenance_schedule_next_date',
                                    '<',
                                    today()
                                );
                        });
                });

            if ($from && $to) {
                $overdueQuery->whereBetween(
                    'maintenance_schedule_next_date',
                    [$from->toDateString(), $to->toDateString()]
                );
            }

            $overdue = (int) $overdueQuery->count();
            $open = $pending + $overdue;
            $fillPercent = $open > 0
                ? min(92, max(18, (int) round(($pending / max(1, $open)) * 100)))
                : 22;
            $handled = $open > 0
                ? max(0, 100 - (int) round(($overdue / max(1, $open)) * 100))
                : 100;

            return [
                'pending' => $pending,
                'overdue' => $overdue,
                'open' => $open,
                'handled' => $handled,
                'fill_percent' => $fillPercent,
                'wave_y' => 200 - (($fillPercent / 100) * 200),
                'reports' => [
                    'pending' => $pending,
                    'processing' => $processing,
                    'resolved' => $resolved,
                    'replacement' => $replacement,
                    'rejected' => $rejected,
                    'submitted' => $submitted,
                    'accepted' => $accepted,
                    'actioned' => $actioned,
                    'closed' => $closed,
                ],
            ];
        };

        $periods = [
            'week' => $buildSlice($now->copy()->startOfWeek(), $now->copy()->endOfWeek()),
            'month' => $buildSlice($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            'year' => $buildSlice($now->copy()->startOfYear(), $now->copy()->endOfYear()),
            'all' => $buildSlice(null, null),
        ];

        $years = [];
        for ($year = $yearNow; $year >= $yearNow - 3; $year--) {
            $years[$year] = $buildSlice(
                $now->copy()->setYear($year)->startOfYear(),
                $now->copy()->setYear($year)->endOfYear()
            );
        }

        return [
            'period' => 'all',
            'year' => $yearNow,
            'periods' => $periods,
            'years' => $years,
            'periodLabels' => [
                'week' => 'Week',
                'month' => 'Month',
                'year' => 'Year',
                'all' => 'All',
            ],
        ];
    }

    // =====================================================
    // REPLACE YOUR CURRENT dashboard() METHOD
    // END HERE
    // =====================================================

    private function applyReportStatusUpdate($id, $report, array $updates): void
    {
        DB::table('reports_table')
            ->where('report_id', $id)
            ->update($updates);

        if (ReportItems::tableExists()) {
            ReportItems::ensureLegacyItem($report);

            $itemExtra = [];
            $status = $updates['report_current_status'] ?? null;

            if (array_key_exists('report_resolution_notes', $updates)) {
                $itemExtra['report_item_resolution_notes'] = $updates['report_resolution_notes'];
            }
            if (array_key_exists('report_resolution_image', $updates)) {
                $itemExtra['report_item_resolution_image'] = $updates['report_resolution_image'];
            }
            if (array_key_exists('report_replacement_notes', $updates)) {
                $itemExtra['report_item_replacement_notes'] = $updates['report_replacement_notes'];
            }
            if (array_key_exists('report_replacement_image', $updates)) {
                $itemExtra['report_item_replacement_image'] = $updates['report_replacement_image'];
            }
            if (array_key_exists('report_rejection_notes', $updates)) {
                $itemExtra['report_item_rejection_notes'] = $updates['report_rejection_notes'];
            }

            if ($status) {
                $payload = array_merge($itemExtra, [
                    'report_item_status' => $status,
                    'report_item_updated_at' => now(),
                ]);

                $query = DB::table('report_items_table')
                    ->where('report_id', $id);

                // Don't overwrite items that already have a final outcome
                // when applying a whole-report resolve/replace/reject.
                if (in_array($status, ReportItems::terminalStatuses(), true)) {
                    $query->whereIn('report_item_status', ReportItems::openStatuses());
                }

                $query->update($payload);
                ReportItems::refreshParentStatus((int) $id);
            }
        }

        ReportGrouping::syncOpenSiblings($report, $updates);
    }

    /*
    |--------------------------------------------------------------------------
    | REUSABLE REPORT QUERY
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function submittedDateRange(mixed $from, mixed $to): array
    {
        $start = $this->submittedFilterDate($from);
        $end = $this->submittedFilterDate($to);

        if ($start && $end && $start > $end) {
            return [$end, $start];
        }

        return [$start, $end];
    }

    private function submittedFilterDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function reportsQuery()
    {
        $request = request();
        $showArchive = $request->archive == 1;
        $isArchiveMode = $request->boolean('archive');
        $exactTicketId = preg_match('/^RPT-\d{8}-\d+$/i', trim((string) $request->search))
            ? ReportGrouping::parseTicketSearch((string) $request->search)
            : null;

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
                    $search = trim((string) $request->search);
                    $ticketId = ReportGrouping::parseTicketSearch($search);

                    $query->where(function ($subQuery) use ($search, $ticketId) {
                        $subQuery
                            ->where(
                                'reports_table.report_id',
                                'LIKE',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'equipment_table.equipment_name',
                                'LIKE',
                                $search.'%'
                            )
                            ->orWhere(
                                'rooms_table.room_name',
                                'LIKE',
                                $search.'%'
                            )
                            ->orWhere(
                                'reporters_table.reporter_full_name',
                                'LIKE',
                                $search.'%'
                            )
                            ->orWhere(
                                'reports_table.report_reporter_employee_id',
                                'LIKE',
                                '%'.$search.'%'
                            );

                        if ($ticketId !== null) {
                            $subQuery->orWhere('reports_table.report_id', $ticketId);
                        }
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

                    if (ReportSeverity::isLevel($request->urgency) && ReportSeverity::hasColumns()) {
                        $query->where('reports_table.report_severity', $request->urgency);

                        return;
                    }

                    // Match the badge: a stack with any urgent report counts as Urgent.
                    $query->whereRaw(
                        "CASE WHEN COALESCE(open_report_group.has_urgent, 0) = 1 THEN 'Urgent' ELSE reports_table.report_urgency_level END = ?",
                        [$request->urgency]
                    );

                }
            )

            ->when(
                $request->filled('from') || $request->filled('to'),
                function ($query) use ($request) {
                    [$from, $to] = $this->submittedDateRange($request->input('from'), $request->input('to'));

                    if ($from) {
                        $query->whereDate('reports_table.report_submitted_at', '>=', $from);
                    }

                    if ($to) {
                        $query->whereDate('reports_table.report_submitted_at', '<=', $to);
                    }
                }
            )

            ->when(
                $request->filled('reporter'),
                function ($query) use ($request, $showArchive) {
                    $reporterId = (string) $request->reporter;

                    // A stacked row also matches when this reporter filed any report in its stack.
                    $query->where(function ($reporterQuery) use ($reporterId, $showArchive) {
                        $reporterQuery->where('reports_table.report_reporter_employee_id', $reporterId);

                        if (! $showArchive) {
                            $reporterQuery->orWhereExists(function ($stack) use ($reporterId) {
                                $stack
                                    ->select(DB::raw(1))
                                    ->from('reports_table as stacked_reports')
                                    ->whereColumn('stacked_reports.report_equipment_id', 'reports_table.report_equipment_id')
                                    ->whereColumn('stacked_reports.report_room_id', 'reports_table.report_room_id')
                                    ->where('stacked_reports.report_is_archived', false)
                                    ->whereIn('stacked_reports.report_current_status', ReportGrouping::groupedStatuses())
                                    ->whereRaw(
                                        ReportGrouping::groupBucketSql('stacked_reports').' = '.ReportGrouping::groupBucketSql('reports_table')
                                    )
                                    ->where('stacked_reports.report_reporter_employee_id', $reporterId);
                            });
                        }
                    });
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
                function ($query) use ($exactTicketId) {
                    $query->where(
                        'reports_table.report_is_archived',
                        false
                    );

                    // Shared with the daily reminder counts so both see the same rows.
                    MaintenanceAttentionSummary::applyActiveGroupRows($query, $exactTicketId);
                }
            )

            ->tap(fn ($query) => MaintenanceAttentionSummary::joinReportGroupStats($query))

            /*
            |--------------------------------------------------------------------------
            | ORDERING
            | Group the whole list by the priority shown on the row. Inside each
            | priority, open work stays above later statuses, Reported before stays
            | above first-time reports, and the newest report is on top.
            |--------------------------------------------------------------------------
            */

            ->when(
                ReportSeverity::hasColumns(),
                fn ($query) => $query->orderByRaw($this->displayedPriorityOrderSql())
            )

            ->orderByRaw(
                "CASE
                    WHEN reports_table.report_current_status IN ('Pending', 'Processing') THEN 0
                    ELSE 1
                END"
            )

            ->when(
                ReportItems::tableExists(),
                fn ($query) => $query->orderByRaw($this->reportedBeforeOrderSql())
            )

            ->orderByRaw(
                'COALESCE(open_report_group.group_last_reported_at, '.ReportGrouping::lastReportedSql().') DESC'
            )
            ->orderByDesc('reports_table.report_id')

            /*
            |--------------------------------------------------------------------------
            | SELECT DATA
            |--------------------------------------------------------------------------
            */

            ->select(array_values(array_filter([

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

                Schema::hasColumn('reports_table', 'report_related_count')
                    ? DB::raw('COALESCE(open_report_group.open_count, reports_table.report_related_count, 1) as grouped_report_count')
                    : DB::raw('COALESCE(open_report_group.open_count, 1) as grouped_report_count'),

                DB::raw("CASE WHEN open_report_group.has_urgent = 1 THEN 'Urgent' ELSE reports_table.report_urgency_level END as grouped_urgency"),

                DB::raw('COALESCE(open_report_group.group_last_reported_at, '.ReportGrouping::lastReportedSql().') as grouped_last_reported_at'),

            ])));
    }

    // =====================================================
    // RETURN A SINGLE REPORT CARD
    // =====================================================

    public function reportCard($id)
    {
        $report = $this->reportsQuery()
            ->where('reports_table.report_id', $id)
            ->first();

        abort_if(!$report, 404);

        $this->attachEquipmentReportHistory(collect([$report]));

        return view(
            'components.tables.partials.report-card',
            compact('report')
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

        return $this->reportsView($reports, true);
    }

    /*
    |--------------------------------------------------------------------------
    | INCOMING REPORTS E - 1
    |--------------------------------------------------------------------------
    */

    public function incomingReports()
    {
        $attentionFocus = $this->reminderFocus(request(), 'reports.incoming', ['status', 'urgency', 'archive', 'view']);

        $reports = $this->reportsQuery()

            ->when($attentionFocus, fn ($query) => MaintenanceAttentionSummary::scopeNonUrgentPending($query))

            ->paginate(10)

            ->withQueryString();

        return $this->reportsView($reports, false, $attentionFocus);
    }

    /**
     * A daily reminder link (?focus=...) replaces the page's own status/priority/archive
     * filters so the list matches the reminder count; search can still narrow it.
     */
    private function reminderFocus(Request $request, string $page, array $overriddenFilters): ?array
    {
        $focus = $request->query('focus');
        $details = MaintenanceAttentionSummary::focusDetails($page, is_string($focus) ? $focus : null);

        if (! $details) {
            return null;
        }

        foreach ($overriddenFilters as $filter) {
            $request->query->remove($filter);
        }

        return $details + [
            'clear_url' => $request->fullUrlWithoutQuery(['focus', 'page']),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | URGENT REPORTS
    |--------------------------------------------------------------------------
    */

    public function urgentReports()
    {
        $attentionFocus = $this->reminderFocus(request(), 'reports.urgent', ['status', 'urgency', 'archive', 'view']);

        $reports = $this->reportsQuery()

            ->whereRaw(
                "CASE WHEN COALESCE(open_report_group.has_urgent, 0) = 1 THEN 'Urgent' ELSE reports_table.report_urgency_level END = 'Urgent'"
            )

            ->when($attentionFocus, fn ($query) => MaintenanceAttentionSummary::scopeUrgentAction($query, (int) Auth::id()))

            ->paginate(10)

            ->withQueryString();

        return $this->reportsView($reports, false, $attentionFocus);
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

            ->whereRaw(
                'DATE('.ReportGrouping::lastReportedSql().') = ?',
                [today()->toDateString()]
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

    private function attachEquipmentReportHistory($reports): void
    {
        $equipmentIds = collect($reports)
            ->pluck('report_equipment_id')
            ->filter()
            ->unique()
            ->values();

        if ($equipmentIds->isEmpty()) {
            foreach ($reports as $report) {
                $report->equipment_report_history = collect();
            }

            return;
        }

        $history = DB::table('reports_table')
            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )
            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->whereIn('reports_table.report_equipment_id', $equipmentIds)
            ->orderByDesc('reports_table.report_submitted_at')
            ->select(
                'reports_table.report_id',
                'reports_table.report_equipment_id',
                'reports_table.report_reporter_employee_id',
                'reports_table.report_urgency_level',
                'reports_table.report_current_status',
                'reports_table.report_suggested_issue',
                'reports_table.report_problem_description',
                'reports_table.report_submitted_at',
                'reports_table.report_is_archived',
                'reporters_table.reporter_full_name',
                'rooms_table.room_name'
            )
            ->get()
            ->groupBy('report_equipment_id');

        foreach ($reports as $report) {
            $report->equipment_report_history = $history->get(
                $report->report_equipment_id,
                collect()
            );
        }
    }

    private function sortReportsUrgentFirst($reports)
    {
        return $reports
            ->sort(function ($a, $b) {
                $aRank = $this->reportListSortRank($a);
                $bRank = $this->reportListSortRank($b);

                foreach ($aRank as $index => $value) {
                    if ($value !== $bRank[$index]) {
                        return $value <=> $bRank[$index];
                    }
                }

                return 0;
            })
            ->values();
    }

    /**
     * Same priority the table shows, including the Urgent bump up to High.
     */
    private function displayedPriorityOrderSql(): string
    {
        $severity = "CASE reports_table.report_severity
            WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 2 END";
        $urgency = "CASE WHEN COALESCE(open_report_group.has_urgent, 0) = 1 THEN 'Urgent' ELSE reports_table.report_urgency_level END";

        return "CASE WHEN ({$urgency}) = 'Urgent' THEN LEAST({$severity}, 1) ELSE {$severity} END";
    }

    /**
     * 0 when this ticket's equipment was already on an earlier open report.
     */
    private function reportedBeforeOrderSql(): string
    {
        return "CASE WHEN EXISTS (
            SELECT 1
            FROM report_items_table AS current_item
            INNER JOIN report_items_table AS earlier_item
                ON earlier_item.report_item_equipment_id = current_item.report_item_equipment_id
               AND earlier_item.report_id <> current_item.report_id
            INNER JOIN reports_table AS earlier_report
                ON earlier_report.report_id = earlier_item.report_id
            WHERE current_item.report_id = reports_table.report_id
              AND current_item.report_item_equipment_id IS NOT NULL
              AND current_item.report_item_status IN ('Pending', 'Processing')
              AND earlier_item.report_item_status IN ('Pending', 'Processing')
              AND earlier_report.report_is_archived = 0
              AND (
                    earlier_report.report_submitted_at < reports_table.report_submitted_at
                    OR (
                        earlier_report.report_submitted_at = reports_table.report_submitted_at
                        AND earlier_report.report_id < reports_table.report_id
                    )
              )
        ) THEN 0 ELSE 1 END";
    }

    /**
     * The priority shown on the row groups the whole list. Inside each priority,
     * open work stays above later statuses, Reported before is stacked first,
     * and the newest report is on top.
     *
     * @return list<int>
     */
    private function reportListSortRank($report): array
    {
        $status = $report->report_current_status ?? '';
        $open = in_array($status, ['Pending', 'Processing'], true) ? 0 : 1;
        $severity = match (ReportSeverity::forReport($report)) {
            'Critical' => 0,
            'High' => 1,
            'Low' => 3,
            default => 2,
        };
        $reportedBefore = (int) ($report->repeat_flagged_count ?? 0) > 0 ? 0 : 1;
        $stamp = strtotime((string) ($report->grouped_last_reported_at ?? ReportGrouping::lastReportedAt($report) ?? '')) ?: 0;

        return [$severity, $open, $reportedBefore, $stamp > 0 ? -$stamp : 0, -1 * (int) ($report->report_id ?? 0)];
    }

    private function reportsView($reports, bool $showReportStats = false, ?array $attentionFocus = null)
    {
        $reports->getCollection()->transform(function ($report) {
            if (isset($report->grouped_report_count)) {
                $report->report_related_count = (int) $report->grouped_report_count;
            }

            if (!empty($report->grouped_urgency)) {
                $report->report_urgency_level = $report->grouped_urgency;
            }

            return $report;
        });

        $this->attachEquipmentReportHistory($reports->getCollection());
        ReportItems::attachToReports($reports->getCollection());
        ReportItems::attachTimelines($reports->getCollection());

        $reports->setCollection(
            $this->sortReportsUrgentFirst($reports->getCollection())
        );

        return view(
            'maintenance-personnel.reports.all-reports',

            array_merge(
                [
                    'reports' => $reports,
                    'allReports' => $showReportStats,
                    'attentionFocus' => $attentionFocus,
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

            // =====================================================
            // ROOM
            // =====================================================

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            // =====================================================
            // FLOOR
            // =====================================================

            ->leftJoin(
                'floors_table',
                'rooms_table.room_floor_id',
                '=',
                'floors_table.floor_id'
            )

            // =====================================================
            // BUILDING
            // =====================================================

            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )

            // =====================================================
            // EQUIPMENT
            // =====================================================

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            // =====================================================
            // REPORTER
            // =====================================================

            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )

            ->leftJoin(
                'users_table as logged_by_user',
                'reports_table.report_logged_by',
                '=',
                'logged_by_user.user_id'
            )

            // =====================================================
            // SELECT REPORT INFORMATION
            // =====================================================

            ->select(

                // =================================================
                // REPORT
                // =================================================

                'reports_table.*',

                // =================================================
                // ROOM
                // =================================================

                'rooms_table.room_name',

                // =================================================
                // FLOOR
                // THIS WAS MISSING
                // =================================================

                'floors_table.floor_level',

                // =================================================
                // BUILDING
                // =================================================

                'buildings_table.building_name',

                // =================================================
                // EQUIPMENT
                // =================================================

                'equipment_table.equipment_name',

                'equipment_table.equipment_inventory_status',

                // =================================================
                // REPORTER
                // =================================================

                'reporters_table.reporter_full_name',

                'reporters_table.reporter_employee_id',

                'reporters_table.reporter_contact_number',

                'logged_by_user.user_full_name as report_logged_by_name'

            )

            // =====================================================
            // FIND REPORT
            // =====================================================

            ->where(
                'reports_table.report_id',
                $id
            )

            ->first();


        /*
        |--------------------------------------------------------------------------
        | REPORT NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$report) {

            abort(
                404,
                'Report not found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DEBUG
        |--------------------------------------------------------------------------
        */

        // dd($report);


        /*
        |--------------------------------------------------------------------------
        | RELATED REPORTS
        |--------------------------------------------------------------------------
        */

        $relatedReports = collect();

        ReportItems::ensureLegacyItem($report);
        $report->report_items = ReportItems::forReport((int) $report->report_id);
        ReportItems::annotateRepeats([$report]);
        $reportItems = $report->report_items;
        $report->equipment_display = ReportItems::labelForReport($report, $reportItems);
        $report->issue_display = ReportItems::issueLabelForReport($report, $reportItems);


        /*
        |--------------------------------------------------------------------------
        | RETURN REPORT DETAILS VIEW
        |--------------------------------------------------------------------------
        */

        $reportBack = ReportDetailsReturn::resolve(request());

        return view(
            'maintenance-personnel.reports.report-details',
            compact(
                'report',
                'relatedReports',
                'reportItems',
                'reportBack'
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



    /**
     * Status / severity changes belong to Maintenance work, not admin monitoring.
     */
    private function ensureCanManageReportProgress(): void
    {
        if (! RoleAccess::hasRole(RoleAccess::MAINTENANCE) || RoleAccess::adminShell()) {
            abort(403, 'Only Maintenance personnel can update report progress.');
        }
    }

    public function updateStatusPage($id)
    {
        $this->ensureCanManageReportProgress();

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
            abort(404, 'Report not found.');
        }

        ReportItems::ensureLegacyItem($report);
        $report->report_items = ReportItems::forReport((int) $report->report_id);
        ReportItems::annotateRepeats([$report]);
        $reportItems = $report->report_items;
        $report->equipment_display = ReportItems::labelForReport($report, $reportItems);
        $report->issue_display = ReportItems::issueLabelForReport($report, $reportItems);

        return view(
            'maintenance-personnel.reports.update-status',
            compact('report', 'reportItems')
        );
    }

    // =====================================================
    // UPDATE REPORT STATUS
    // MAINTENANCE PERSONNEL
    // =====================================================

    public function updateReportSeverity(Request $request, int $id)
    {
        $this->ensureCanManageReportProgress();

        if (!ReportSeverity::hasColumns()) {
            return back()->with('error', 'Automatic priority is not set up yet. Run the database migrations first.');
        }

        $validated = $request->validate([
            'severity' => 'required|in:' . implode(',', ReportSeverity::levels()),
            'severity_reason' => 'required|string|min:5|max:300',
        ], [
            'severity_reason.required' => 'Please give a reason for changing the priority.',
            'severity_reason.min' => 'Please give a clearer reason (at least 5 characters).',
        ]);

        $report = DB::table('reports_table')->where('report_id', $id)->first();

        if (!$report) {
            return back()->with('error', 'Report not found.');
        }

        if ($report->report_is_archived) {
            return back()->with('error', 'Archived reports cannot be updated.');
        }

        if (in_array($report->report_current_status, ['Resolved', 'Rejected'], true)) {
            return back()->with('error', 'Closed reports keep the priority they had.');
        }

        $newLevel = $validated['severity'];
        $oldLevel = ReportSeverity::forReport($report);
        $reason = trim($validated['severity_reason']);
        $changedBy = Auth::user()?->user_full_name ?: 'Maintenance';

        if ($newLevel === $oldLevel && $report->report_severity === $newLevel) {
            return back()->with('error', 'The report is already ' . $newLevel . ' priority.');
        }

        DB::table('reports_table')->where('report_id', $id)->update([
            'report_severity' => $newLevel,
            'report_urgency_level' => ReportSeverity::urgencyFor($newLevel),
            'report_severity_is_manual' => true,
            'report_severity_reason' => mb_substr('Changed by ' . $changedBy . ' (was ' . $oldLevel . '): ' . $reason, 0, 500),
            'report_updated_at' => now(),
        ]);

        $this->logActivity(
            'Changed report priority',
            'Reports',
            'reports_table',
            $id,
            'Changed Report #' . $id . ' priority from ' . $oldLevel . ' to ' . $newLevel . '. Reason: ' . $reason
        );

        return back()->with('success', 'Priority changed to ' . $newLevel . '.');
    }

    public function updateStatus(Request $request, $id)
    {
        $this->ensureCanManageReportProgress();

        // =====================================================
        // VALIDATE REQUEST HERE
        // =====================================================

        $request->validate([

            'status' =>
                'required|'.\App\Support\ReportStatuses::updateRuleIn(),

            'remarks' =>
                'nullable|string|'.\App\Support\InputLimits::notes(),

            'proof_image' =>
                'nullable|image|max:5120',

            'report_item_ids' =>
                'nullable|array',

            'report_item_ids.*' =>
                'integer',

            'replacement_choice' =>
                'nullable|array',

            'replacement_choice.*' =>
                'nullable|string|max:20',

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
        // DATABASE TRANSACTION HERE
        // =====================================================

        $equipmentForReplacement = [];

        $response = DB::transaction(function () use (

            $request,

            $id,

            $personnelId,

            $newStatus,

            $imagePath,

            &$equipmentForReplacement

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
            // ALLOWED MAINTENANCE STATUS TRANSITIONS HERE
            // =================================================

            $allowedTransitions = \App\Support\ReportStatuses::transitions();


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

                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'Processing',
                        'report_assigned_personnel_id' => $personnelId,
                        'report_updated_at' => now(),
                    ]);

                        $this->logActivity(
                            'Started processing report',
                            'Reports',
                            'reports_table',
                            (int) $id,
                            'Started processing Report #' . $id . '.'
                        );


                    return back()

                        ->with(
                            'success',
                            'Report is now being processed.'
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

                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'Rejected',
                        'report_rejection_notes' => $request->remarks,
                        'report_updated_at' => now(),
                    ]);

                        $this->logActivity(
                            'Rejected report',
                            'Reports',
                            'reports_table',
                            (int) $id,
                            'Rejected Report #' . $id . '.'
                        );


                    return back()

                        ->with(
                            'success',
                            'Report rejected successfully.'
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

                    ReportItems::ensureLegacyItem($report);
                    $allItems = ReportItems::forReport((int) $id);
                    $selectedItemIds = collect($request->input('report_item_ids', []))
                        ->map(fn ($itemId) => (int) $itemId)
                        ->filter(fn ($itemId) => $itemId > 0)
                        ->unique()
                        ->values();

                    if ($allItems->count() > 1 && $selectedItemIds->isNotEmpty()) {
                        $targets = $allItems->whereIn('report_item_id', $selectedItemIds->all());

                        if ($targets->isEmpty()) {
                            return back()->with(
                                'error',
                                'Select at least one equipment item to resolve.'
                            );
                        }

                        foreach ($targets as $item) {
                            ReportItems::updateItem((int) $item->report_item_id, 'Resolved', [
                                'report_item_resolution_notes' => $request->remarks,
                                'report_item_resolution_image' => $imagePath,
                            ]);
                        }

                        $this->logActivity(
                            'Resolved report items',
                            'Reports',
                            'reports_table',
                            (int) $id,
                            'Resolved '. $targets->count() .' equipment item(s) on Report #' . $id . '.'
                        );

                        return back()
                            ->with(
                                'success',
                                'Selected equipment marked as resolved. Remaining items can still be fixed or sent for replacement.'
                            );
                    }

                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'Resolved',
                        'report_resolution_notes' => $request->remarks,
                        'report_resolution_image' => $imagePath,
                        'report_updated_at' => now(),
                    ]);

                        $this->logActivity(
                            'Resolved report',
                            'Reports',
                            'reports_table',
                            (int) $id,
                            'Resolved Report #' . $id . '.'
                        );


                    return back()

                        ->with(
                            'success',
                            'Report resolved successfully.'
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


                    ReportItems::ensureLegacyItem($report);
                    $allItems = ReportItems::forReport((int) $id);
                    $selectedItemIds = collect($request->input('report_item_ids', []))
                        ->map(fn ($itemId) => (int) $itemId)
                        ->filter(fn ($itemId) => $itemId > 0)
                        ->unique()
                        ->values();

                    $stockStep = $this->fulfillReplacementFromStock(
                        $request,
                        $report,
                        $allItems,
                        $selectedItemIds,
                        (int) $personnelId,
                        $imagePath
                    );

                    if ($stockStep['response']) {
                        return $stockStep['response'];
                    }

                    if ($stockStep['swapped'] !== []) {
                        $allItems = ReportItems::forReport((int) $id);
                        $selectedItemIds = collect(array_keys($stockStep['procurement']));
                    }

                    $partialReplacement = $allItems->count() > 1 && $selectedItemIds->isNotEmpty();

                    if ($partialReplacement) {
                        $targets = $allItems->whereIn('report_item_id', $selectedItemIds->all());

                        if ($targets->isEmpty()) {
                            return back()->with(
                                'error',
                                'Select at least one equipment item for replacement.'
                            );
                        }

                        foreach ($targets as $item) {
                            ReportItems::updateItem((int) $item->report_item_id, 'For Replacement', [
                                'report_item_replacement_notes' => $request->remarks,
                                'report_item_replacement_image' => $imagePath,
                            ]);

                            if (!empty($item->report_item_equipment_id)) {
                                $equipmentForReplacement[] = (int) $item->report_item_equipment_id;
                            }
                        }

                        DB::table('reports_table')
                            ->where('report_id', $id)
                            ->update([
                                'report_replacement_notes' => $request->remarks,
                                'report_replacement_image' => $imagePath,
                                'report_replacement_submitted_to_purchaser' => 1,
                                'report_updated_at' => now(),
                            ]);

                        ReportItems::refreshParentStatus((int) $id);

                        $this->logActivity(
                            'Submitted report items for replacement',
                            'Reports',
                            'reports_table',
                            (int) $id,
                            'Submitted '. $targets->count() .' equipment item(s) on Report #' . $id . ' for replacement.'
                        );
                    } else {
                        // =================================================
                        // UPDATE REPORT HERE
                        // =================================================

                        $this->applyReportStatusUpdate($id, $report, [
                            'report_current_status' => 'For Replacement',
                            'report_replacement_notes' => $request->remarks,
                            'report_replacement_image' => $imagePath,
                            'report_replacement_submitted_to_purchaser' => 1,
                            'report_updated_at' => now(),
                        ]);

                        $replacementItems = $allItems->filter(
                            fn ($item) => in_array($item->report_item_status, ReportItems::openStatuses(), true)
                        );

                        if ($replacementItems->isEmpty() && $allItems->isEmpty() && !empty($report->report_equipment_id)) {
                            $replacementItems = collect([(object) [
                                'report_item_equipment_id' => $report->report_equipment_id,
                            ]]);
                        }

                        foreach ($replacementItems as $item) {
                            $equipmentId = (int) ($item->report_item_equipment_id ?? 0);
                            if ($equipmentId > 0) {
                                $equipmentForReplacement[] = $equipmentId;
                            }
                        }

                        $this->logActivity(
                            'Submitted report for replacement',
                            'Reports',
                            'reports_table',
                            (int) $id,
                            'Submitted Report #' . $id . ' for equipment replacement.'
                        );
                    }


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


                    ReplacementRequestBasket::attachUnlinkedForReplacementItems(
                        (int) $id,
                        (int) $personnelId
                    );

                    $this->logProcurementReplacements((int) $id, $stockStep['procurement'], $request->remarks);

                    $message = $partialReplacement
                        ? 'Selected equipment submitted for replacement. Other items on this report can still be resolved separately.'
                        : 'Report submitted for replacement successfully.';

                    if ($stockStep['swapped'] !== []) {
                        $message = 'Swapped in from inventory stock: '.implode(' ', $stockStep['swapped'])
                            .' The rest was sent to the Purchaser as a procurement request.';
                    }

                    return back()

                        ->with(
                            'success',
                            $message
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

        foreach (array_unique($equipmentForReplacement) as $equipmentId) {
            ReportGrouping::markEquipmentForReplacement((int) $equipmentId);
        }

        return $response;
    }

    public function replacementLog(Request $request)
    {
        $type = in_array($request->query('type'), [ReplacementStock::STOCK_SWAP, ReplacementStock::PROCUREMENT], true)
            ? $request->query('type')
            : null;
        $search = trim((string) $request->query('search', ''));

        $counts = ReplacementStock::tableReady()
            ? DB::table(ReplacementStock::TABLE)
                ->selectRaw('replacement_log_fulfillment_type as type, COUNT(*) as total')
                ->groupBy('replacement_log_fulfillment_type')
                ->pluck('total', 'type')
                ->all()
            : [];

        $logs = ReplacementStock::tableReady()
            ? DB::table(ReplacementStock::TABLE.' as l')
                ->leftJoin('users_table as u', 'u.user_id', '=', 'l.replacement_log_performed_by')
                ->leftJoin('rooms_table as from_room', 'from_room.room_id', '=', 'l.replacement_log_from_room_id')
                ->leftJoin('rooms_table as to_room', 'to_room.room_id', '=', 'l.replacement_log_to_room_id')
                ->when($type, fn ($q) => $q->where('l.replacement_log_fulfillment_type', $type))
                ->when($search !== '', function ($q) use ($search) {
                    $like = '%'.$search.'%';
                    $q->where(function ($w) use ($like, $search) {
                        $w->where('l.replacement_log_replaced_label', 'like', $like)
                            ->orWhere('l.replacement_log_replaced_asset_tag', 'like', $like)
                            ->orWhere('l.replacement_log_replacement_label', 'like', $like)
                            ->orWhere('l.replacement_log_replacement_asset_tag', 'like', $like)
                            ->orWhere('u.user_full_name', 'like', $like);
                        if (ctype_digit(ltrim($search, '#'))) {
                            $w->orWhere('l.replacement_log_report_id', (int) ltrim($search, '#'));
                        }
                    });
                })
                ->orderByDesc('l.replacement_log_performed_at')
                ->orderByDesc('l.replacement_log_id')
                ->select(
                    'l.*',
                    'u.user_full_name as performed_by_name',
                    'from_room.room_name as from_room_name',
                    'to_room.room_name as to_room_name'
                )
                ->paginate(20)
                ->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        return view('maintenance-personnel.equipment.replacement-log', compact('logs', 'counts', 'type', 'search'));
    }

    /**
     * Stockroom units that could replace the report items about to be sent "For Replacement".
     */
    public function replacementStock(Request $request, $id)
    {
        $report = DB::table('reports_table')->where('report_id', $id)->first();
        if (! $report || $report->report_is_archived) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        ReportItems::ensureLegacyItem($report);

        $selected = collect((array) $request->input('item_ids', []))
            ->map(fn ($itemId) => (int) $itemId)
            ->filter()
            ->all();

        $items = ReportItems::forReport((int) $id)
            ->filter(fn ($item) => in_array($item->report_item_status, ReportItems::openStatuses(), true))
            ->when($selected !== [], fn ($items) => $items->whereIn('report_item_id', $selected))
            ->values();

        return response()->json(ReplacementStock::summary($items));
    }

    /**
     * Swaps stock units in for the items the user picked them for. Items with stock but no
     * decision are refused; the rest are returned for the procurement flow.
     *
     * @return array{response: mixed, swapped: array<int, string>, procurement: array<int, bool>}
     */
    private function fulfillReplacementFromStock(
        Request $request,
        object $report,
        \Illuminate\Support\Collection $allItems,
        \Illuminate\Support\Collection $selectedItemIds,
        int $personnelId,
        ?string $imagePath
    ): array {
        $result = ['response' => null, 'swapped' => [], 'procurement' => []];

        if ($allItems->isEmpty()) {
            return $result;
        }

        $targets = ($allItems->count() > 1 && $selectedItemIds->isNotEmpty())
            ? $allItems->whereIn('report_item_id', $selectedItemIds->all())
            : $allItems;
        $targets = $targets
            ->filter(fn ($item) => in_array($item->report_item_status, ReportItems::openStatuses(), true))
            ->values();

        $choices = (array) $request->input('replacement_choice', []);
        $swaps = [];
        $usedUnits = [];

        foreach ($targets as $item) {
            $itemId = (int) $item->report_item_id;
            $candidateIds = ReplacementStock::candidateIdsFor($item);
            $choice = trim((string) ($choices[$itemId] ?? ''));
            $name = ReportItems::displayName($item);

            if ($candidateIds === []) {
                $result['procurement'][$itemId] = false;
                continue;
            }

            if ($choice === '') {
                $result['response'] = back()->with(
                    'error',
                    'The inventory still has stock for '.$name.'. Pick a stock unit to swap in, or choose to proceed with a procurement request.'
                );

                return $result;
            }

            if ($choice === 'procurement') {
                $result['procurement'][$itemId] = true;
                continue;
            }

            $unitId = (int) $choice;
            if (! in_array($unitId, $candidateIds, true) || isset($usedUnits[$unitId])) {
                $result['response'] = back()->with(
                    'error',
                    'The stock unit picked for '.$name.' is no longer available. Check the stock again.'
                );

                return $result;
            }

            $usedUnits[$unitId] = true;
            $swaps[] = [$item, $unitId];
        }

        if ($swaps === []) {
            return $result;
        }

        try {
            DB::transaction(function () use ($request, $report, $allItems, $swaps, $personnelId, $imagePath, &$result) {
                foreach ($swaps as [$item, $unitId]) {
                    $note = $this->swapInStockUnit($request, $report, $item, $unitId, $personnelId);
                    $resolution = trim($note.' '.trim((string) $request->remarks));

                    if ($allItems->count() > 1) {
                        ReportItems::updateItem((int) $item->report_item_id, 'Resolved', [
                            'report_item_resolution_notes' => $resolution,
                            'report_item_resolution_image' => $imagePath,
                        ]);
                    } else {
                        $this->applyReportStatusUpdate($report->report_id, $report, [
                            'report_current_status' => 'Resolved',
                            'report_resolution_notes' => $resolution,
                            'report_resolution_image' => $imagePath,
                            'report_updated_at' => now(),
                        ]);
                    }

                    $result['swapped'][] = $note;
                }
            });
        } catch (\RuntimeException $e) {
            $result['swapped'] = [];
            $result['response'] = back()->with('error', $e->getMessage());

            return $result;
        }

        $this->logActivity(
            'Replaced equipment from inventory stock',
            'Reports',
            'reports_table',
            (int) $report->report_id,
            'Report #'.$report->report_id.': '.implode(' ', $result['swapped'])
        );

        if ($result['procurement'] === []) {
            $result['response'] = back()->with(
                'success',
                'Replaced from inventory stock. No procurement request was needed. '.implode(' ', $result['swapped'])
            );
        }

        return $result;
    }

    /**
     * Deploys a stock unit where the reported unit was, sends the reported unit to the
     * stockroom as For Replacement, carries over its custodian, and logs the mapping.
     * Must run inside a transaction; throws RuntimeException when the swap cannot happen.
     */
    private function swapInStockUnit(Request $request, object $report, object $item, int $unitId, int $personnelId): string
    {
        $noDestination = ['type' => null, 'department_id' => null, 'custodian_id' => null, 'label' => null, 'error' => null];
        $name = ReportItems::displayName($item);

        $unit = DB::table('equipment_table')->where('equipment_id', $unitId)->lockForUpdate()->first();
        $storageRoom = $unit ? DB::table('rooms_table')->where('room_id', $unit->equipment_room_id)->first() : null;
        if (! $unit || ! $storageRoom || ! RoomCategories::isStorageType($storageRoom->room_type ?? null)) {
            throw new \RuntimeException('The stock unit picked for '.$name.' is no longer in storage. Check the stock again.');
        }

        $replacedId = (int) ($item->report_item_equipment_id ?? 0);
        $replaced = $replacedId > 0
            ? DB::table('equipment_table')->where('equipment_id', $replacedId)->lockForUpdate()->first()
            : null;

        $targetRoomId = (int) ($replaced->equipment_room_id ?? 0)
            ?: (int) ($item->report_item_room_id ?? 0)
            ?: (int) ($report->report_room_id ?? 0);
        $targetRoom = $targetRoomId > 0 ? DB::table('rooms_table')->where('room_id', $targetRoomId)->first() : null;

        if (! $targetRoom) {
            throw new \RuntimeException($name.' has no room on file, so the stock unit has nowhere to go. Proceed with a procurement request instead.');
        }
        if (RoomCategories::isStorageType($targetRoom->room_type ?? null)) {
            throw new \RuntimeException($name.' is already in storage. Proceed with a procurement request instead.');
        }

        $unitTag = $unit->equipment_asset_tag ?: 'ID #'.$unit->equipment_id;
        $replacedTag = $replaced ? ($replaced->equipment_asset_tag ?: 'ID #'.$replaced->equipment_id) : null;

        $this->recordInventoryTransfer(
            $request,
            $unit,
            $storageRoom,
            $targetRoom,
            $noDestination,
            false,
            'Stock swap for Report #'.$report->report_id.($replacedTag ? ': replaces '.$replacedTag.'.' : '.'),
            $personnelId,
            $personnelId
        );

        $unitUpdate = [
            'equipment_inventory_status' => 'Active',
            'equipment_replaces_id' => $replaced->equipment_id ?? null,
        ];

        $replacedIsSingle = $replaced
            && (($replaced->equipment_tracking_mode ?? null) === 'Individual' || (int) ($replaced->equipment_quantity ?? 1) <= 1);

        if ($replaced && (int) $replaced->equipment_room_id === (int) $targetRoom->room_id) {
            $unitUpdate += [
                'equipment_department_id' => $replaced->equipment_department_id,
                'equipment_placement_zone' => $replaced->equipment_placement_zone,
                'equipment_current_location' => $replaced->equipment_current_location,
                'equipment_position_x' => $replaced->equipment_position_x,
                'equipment_position_y' => $replaced->equipment_position_y,
                'equipment_rotation' => $replaced->equipment_rotation,
            ];

            if ($replacedIsSingle) {
                $unitUpdate += [
                    'workstation_slot_id' => $replaced->workstation_slot_id,
                    'workstation_id' => $replaced->workstation_id,
                    'workstation_template_slot_id' => $replaced->workstation_template_slot_id,
                ];
            }
        }

        DB::table('equipment_table')->where('equipment_id', $unit->equipment_id)->update($unitUpdate);

        $custodianNote = '';

        if ($replacedIsSingle) {
            $assignment = PropertyAssignments::current((int) $replaced->equipment_id);

            DB::table('equipment_table')
                ->where('equipment_id', $replaced->equipment_id)
                ->update([
                    'equipment_replaced_by_id' => $unit->equipment_id,
                    'workstation_slot_id' => null,
                    'workstation_id' => null,
                    'workstation_template_slot_id' => null,
                ]);

            $this->recordInventoryTransfer(
                $request,
                DB::table('equipment_table')->where('equipment_id', $replaced->equipment_id)->first(),
                $targetRoom,
                $storageRoom,
                $noDestination,
                false,
                'Swapped out for '.$unitTag.' (Report #'.$report->report_id.'). Waiting for disposal.',
                $personnelId,
                $personnelId
            );

            ReportGrouping::markEquipmentForReplacement((int) $replaced->equipment_id);

            if ($assignment) {
                PropertyAssignments::returnAssignment(
                    (int) $assignment->assignment_id,
                    'Damaged',
                    'Swapped out for '.$unitTag.' (Report #'.$report->report_id.').'
                );

                try {
                    PropertyAssignments::issue((int) $unit->equipment_id, (int) $assignment->assignment_custodian_id, [
                        'notes' => 'Replacement for '.$replacedTag.' (Report #'.$report->report_id.').',
                    ]);
                    $custodianNote = ' Issued to '.($assignment->custodian_full_name ?? 'the same person').'.';
                } catch (\RuntimeException $e) {
                    $custodianNote = ' Not re-issued to '.($assignment->custodian_full_name ?? 'the previous holder').': '.$e->getMessage();
                }
            }
        }

        ReplacementStock::log([
            'replacement_log_report_id' => $report->report_id,
            'replacement_log_report_item_id' => $item->report_item_id,
            'replacement_log_replaced_equipment_id' => $replaced->equipment_id ?? null,
            'replacement_log_replaced_label' => ReplacementStock::label($replaced, $name),
            'replacement_log_replaced_asset_tag' => $replaced->equipment_asset_tag ?? null,
            'replacement_log_replacement_equipment_id' => $unit->equipment_id,
            'replacement_log_replacement_label' => ReplacementStock::label($unit),
            'replacement_log_replacement_asset_tag' => $unit->equipment_asset_tag,
            'replacement_log_fulfillment_type' => ReplacementStock::STOCK_SWAP,
            'replacement_log_stock_was_available' => true,
            'replacement_log_from_room_id' => $storageRoom->room_id,
            'replacement_log_to_room_id' => $targetRoom->room_id,
            'replacement_log_notes' => $request->remarks,
            'replacement_log_performed_by' => $personnelId,
        ]);

        return $name.($replacedTag ? ' ('.$replacedTag.')' : '').' replaced with '.$unitTag
            .' from '.$storageRoom->room_name.'.'.$custodianNote;
    }

    /**
     * @param  array<int, bool>  $items  report item id => whether stock was available (manual override)
     */
    private function logProcurementReplacements(int $reportId, array $items, ?string $remarks): void
    {
        foreach ($items as $itemId => $stockWasAvailable) {
            $item = DB::table('report_items_table')->where('report_item_id', $itemId)->first();
            if (! $item || $item->report_item_status !== 'For Replacement') {
                continue;
            }

            $equipment = ! empty($item->report_item_equipment_id)
                ? DB::table('equipment_table')->where('equipment_id', $item->report_item_equipment_id)->first()
                : null;

            ReplacementStock::log([
                'replacement_log_report_id' => $reportId,
                'replacement_log_report_item_id' => $itemId,
                'replacement_log_replaced_equipment_id' => $equipment->equipment_id ?? null,
                'replacement_log_replaced_label' => ReplacementStock::label($equipment, $item->report_item_unlisted_equipment_name ?: 'Unlisted equipment'),
                'replacement_log_replaced_asset_tag' => $equipment->equipment_asset_tag ?? null,
                'replacement_log_fulfillment_type' => ReplacementStock::PROCUREMENT,
                'replacement_log_stock_was_available' => $stockWasAvailable,
                'replacement_log_procurement_request_id' => DB::table('procurement_request_items_table')
                    ->where('report_item_id', $itemId)
                    ->orderByDesc('procurement_request_item_id')
                    ->value('procurement_request_id'),
                'replacement_log_to_room_id' => $item->report_item_room_id ?? ($equipment->equipment_room_id ?? null),
                'replacement_log_notes' => $remarks,
            ]);
        }
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
        | PAGE SCOPE (sidebar routes)
        | /inventory = storage stock; /all = full register
        |--------------------------------------------------------------------------
        */

        $scope = $request->is('maintenance/equipment/all', 'maintenance/equipment/all/*', 'admin/equipment/all')
            ? 'all'
            : 'stock';

        $storageType = RoomCategories::STORAGE_TYPE;
        $isStockPage = $scope === 'stock';

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

        $allRooms = DB::table(
            'rooms_table'
        )
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('room_is_archived', false)
            )
            ->orderByRaw(
                'CASE WHEN room_type = ? THEN 0 ELSE 1 END',
                [$storageType]
            )
            ->orderBy(
                'room_name'
            )
            ->get();

        $storageRooms = $allRooms
            ->filter(fn ($room) => RoomCategories::isStorageType($room->room_type ?? null))
            ->values();

        $rooms = $isStockPage ? $storageRooms : $allRooms;

        $defaultStorageRoomId = optional($storageRooms->first())->room_id;

        $storageJoin = function ($query) use ($storageType) {
            return $query
                ->join(
                    'rooms_table',
                    'equipment_table.equipment_room_id',
                    '=',
                    'rooms_table.room_id'
                )
                ->where('rooms_table.room_type', $storageType);
        };

        // =====================================================
        // TOTAL EQUIPMENT (fleet or storage-scoped)
        // =====================================================

        if ($isStockPage) {
            $liveStockQuery = $storageJoin(DB::table('equipment_table'))
                ->whereNotIn('equipment_table.equipment_inventory_status', ['Disposed', TransferLocks::BORROWED]);

            $totalEquipment = (clone $liveStockQuery)->count();
            $stockTotalQuantity = (int) (clone $liveStockQuery)
                ->sum('equipment_table.equipment_quantity');
            $stockTypeCount = (int) (clone $liveStockQuery)
                ->select('equipment_table.equipment_name')
                ->distinct()
                ->count('equipment_table.equipment_name');
            $storageRoomsWithStock = (int) (clone $liveStockQuery)
                ->select('equipment_table.equipment_room_id')
                ->distinct()
                ->count('equipment_table.equipment_room_id');

            $activeEquipment = 0;
            $underMaintenanceEquipment = 0;
            $borrowedEquipment = 0;
            $forReplacementEquipment = 0;
            $disposedEquipment = 0;
        } else {
            $stockTotalQuantity = 0;
            $stockTypeCount = 0;
            $storageRoomsWithStock = 0;

            $statusCountRows = DB::table('equipment_table')
                ->select('equipment_inventory_status', DB::raw('COUNT(*) as total'))
                ->groupBy('equipment_inventory_status')
                ->pluck('total', 'equipment_inventory_status');

            $totalEquipment = (int) $statusCountRows->sum();

            $activeEquipment = (int) $statusCountRows->get('Active', 0);
            $underMaintenanceEquipment = (int) $statusCountRows->get('Under Maintenance', 0);
            $borrowedEquipment = (int) $statusCountRows->get('Borrowed', 0);
            $forReplacementEquipment = (int) $statusCountRows->get('For Replacement', 0);
            $disposedEquipment = (int) $statusCountRows->get('Disposed', 0);
        }

        // =====================================================
        // CURRENT / PREVIOUS MONTH REGISTRATIONS
        // =====================================================

        if ($isStockPage) {
            $currentMonthEquipment = $storageJoin(DB::table('equipment_table'))
                ->whereBetween(
                    'equipment_table.equipment_created_at',
                    [
                        now()->copy()->startOfMonth(),
                        now()->copy()->endOfMonth(),
                    ]
                )
                ->count();

            $previousMonthEquipment = $storageJoin(DB::table('equipment_table'))
                ->whereBetween(
                    'equipment_table.equipment_created_at',
                    [
                        now()->copy()->subMonthNoOverflow()->startOfMonth(),
                        now()->copy()->subMonthNoOverflow()->endOfMonth(),
                    ]
                )
                ->count();
        } else {
            $currentMonthEquipment =
                DB::table('equipment_table')
                    ->whereBetween(
                        'equipment_created_at',
                        [
                            now()->copy()->startOfMonth(),
                            now()->copy()->endOfMonth(),
                        ]
                    )
                    ->count();

            $previousMonthEquipment =
                DB::table('equipment_table')
                    ->whereBetween(
                        'equipment_created_at',
                        [
                            now()->copy()->subMonthNoOverflow()->startOfMonth(),
                            now()->copy()->subMonthNoOverflow()->endOfMonth(),
                        ]
                    )
                    ->count();
        }

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
            (! $isStockPage && $totalEquipment > 0)

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
            (! $isStockPage && $totalEquipment > 0)

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
            (! $isStockPage && $totalEquipment > 0)

                ? (
                    $disposedEquipment
                    / $totalEquipment
                ) * 100

                : 0;


        // =====================================================
        // EQUIPMENT REGISTERED PER MONTH
        // LAST 12 MONTHS
        // =====================================================

        if ($isStockPage) {
            $monthlyEquipmentRows = $storageJoin(DB::table('equipment_table'))
                ->selectRaw('
                    YEAR(equipment_table.equipment_created_at) AS equipment_year,
                    MONTH(equipment_table.equipment_created_at) AS equipment_month,
                    COUNT(*) AS equipment_count
                ')
                ->whereNotNull('equipment_table.equipment_created_at')
                ->where(
                    'equipment_table.equipment_created_at',
                    '>=',
                    now()->copy()->subMonths(11)->startOfMonth()
                )
                ->groupByRaw('
                    YEAR(equipment_table.equipment_created_at),
                    MONTH(equipment_table.equipment_created_at)
                ')
                ->orderByRaw('
                    YEAR(equipment_table.equipment_created_at),
                    MONTH(equipment_table.equipment_created_at)
                ')
                ->get()
                ->keyBy(function ($row) {
                    return $row->equipment_year.'-'.str_pad((string) $row->equipment_month, 2, '0', STR_PAD_LEFT);
                });
        } else {
            $monthlyEquipmentRows =
                DB::table('equipment_table')
                    ->selectRaw('
                    YEAR(equipment_created_at)
                        AS equipment_year,

                    MONTH(equipment_created_at)
                        AS equipment_month,

                    COUNT(*)
                        AS equipment_count
                    ')
                    ->whereNotNull('equipment_created_at')
                    ->where(
                        'equipment_created_at',
                        '>=',
                        now()->copy()->subMonths(11)->startOfMonth()
                    )
                    ->groupByRaw('
                    YEAR(equipment_created_at),
                    MONTH(equipment_created_at)
                    ')
                    ->orderByRaw('
                    YEAR(equipment_created_at),
                    MONTH(equipment_created_at)
                    ')
                    ->get()
                    ->keyBy(function ($row) {
                        return $row->equipment_year
                            .'-'
                            .str_pad($row->equipment_month, 2, '0', STR_PAD_LEFT);
                    });
        }

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

                'rooms_table.room_name',

                'rooms_table.room_type'

            );

        EquipmentNextMaintenance::apply($query);


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

        if ($isStockPage) {
            // Borrowed stock is off campus until it is returned.
            $query->whereNotIn(
                'equipment_table.equipment_inventory_status',
                ['Disposed', TransferLocks::BORROWED]
            )->where(
                'rooms_table.room_type',
                $storageType
            );
        } elseif ($request->filled('status')) {

            $query->where(
                'equipment_table.equipment_inventory_status',
                $request->status
            );

        } else {
            // "All" = live inventory (includes For Replacement); Disposed stays on its own tab
            $query->whereNotIn(
                'equipment_table.equipment_inventory_status',
                ['Disposed']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $stockTransferIds = [];
        if ($isStockPage) {
            $stockTransferIds = (clone $query)
                ->orderBy('equipment_table.equipment_id')
                ->pluck('equipment_table.equipment_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $equipment = $query
            ->orderBy('equipment_table.equipment_created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $deployRooms = $allRooms
            ->reject(fn ($room) => RoomCategories::isStorageType($room->room_type ?? null))
            ->values();

        $usedAssetTags = DB::table('equipment_table')
            ->whereNotNull('equipment_asset_tag')
            ->where('equipment_asset_tag', '!=', '')
            ->whereNotIn('equipment_inventory_status', ['Disposed'])
            ->pluck('equipment_asset_tag');

        $pendingReceivableLines = $isStockPage
            ? ReceivableStockLines::pending()
            : collect();
        $pendingReceivableCount = $pendingReceivableLines->count();
        $pendingReplacementLines = $isStockPage
            ? $pendingReceivableLines->filter(fn ($line) => ! empty($line->is_replacement))->values()
            : collect();
        $pendingReplacementReceivableCount = $pendingReplacementLines->count();

        $openBalance = $isStockPage
            ? EquipmentOpenBalance::summary(50)
            : ['lines' => [], 'totals' => []];

        $monitoring = $isStockPage
            ? [
                'ghost' => EquipmentMonitoring::ghostStock(8),
                'warranty' => EquipmentMonitoring::warrantyAlerts(8),
                'ghost_count' => EquipmentMonitoring::ghostStockCount(),
                'warranty_count' => EquipmentMonitoring::warrantyAlertCount(),
            ]
            : null;

        $movementHistory = $isStockPage ? StockMovementHistory::paginate($request) : null;
        $movementCounts = $isStockPage ? StockMovementHistory::counts() : null;

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
                'storageRooms',
                'deployRooms',
                'stockTransferIds',
                'usedAssetTags',
                'pendingReceivableCount',
                'pendingReplacementReceivableCount',
                'pendingReplacementLines',
                'openBalance',
                'monitoring',
                'movementHistory',
                'movementCounts',
                'scope',
                'isStockPage',
                'defaultStorageRoomId',
                'stockTotalQuantity',
                'stockTypeCount',
                'storageRoomsWithStock',

                // =====================================================
                // EQUIPMENT DASHBOARD COUNTS
                // =====================================================

                'totalEquipment',
                'activeEquipment',
                'underMaintenanceEquipment',
                'borrowedEquipment',
                'forReplacementEquipment',
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
    | DEPLOYED STOCKS (equipment assigned to rooms)
    |--------------------------------------------------------------------------
    */
    public function deployedStocks(Request $request)
    {
        $categories = DB::table('equipment_categories_table')
            ->orderBy('equipment_category_name')
            ->get();

        $rooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('room_is_archived', false)
            )
            ->orderByRaw(
                'CASE WHEN room_type = ? THEN 0 ELSE 1 END',
                [RoomCategories::STORAGE_TYPE]
            )
            ->orderBy('room_name')
            ->get();

        $deployedUrl = url($request->is('admin/*') ? '/admin/inventory/deployed' : '/maintenance/equipment/deployed');
        $includeStorage = $request->boolean('include_storage');
        $focusEquipmentId = 0;
        $attentionFocus = null;
        $transferRecordId = max(0, (int) $request->query('record', 0));
        if ($transferRecordId > 0) {
            $focusEquipmentId = (int) DB::table('equipment_transfer_history_table')
                ->where('transfer_id', $transferRecordId)
                ->value('equipment_id');
            foreach (['search', 'category', 'room', 'department', 'status', 'on_floor'] as $filter) {
                $request->query->remove($filter);
            }
            // The item may have been moved back to storage since.
            $includeStorage = true;
            $equipmentName = trim((string) DB::table('equipment_table')->where('equipment_id', $focusEquipmentId)->value('equipment_name'));
            $attentionFocus = \App\Support\RecordFocus::make(
                $equipmentName !== '' ? $equipmentName : 'Transfer #'.$transferRecordId,
                $deployedUrl,
                'transferred equipment'
            );
        }

        $roomFilter = $request->filled('room')
            ? $rooms->firstWhere('room_id', (int) $request->room)
            : null;
        if ($roomFilter && RoomCategories::isStorageType($roomFilter->room_type ?? null)) {
            $includeStorage = true;
        }

        $hasDepartment = Schema::hasColumn('equipment_table', 'equipment_department_id')
            && Schema::hasTable('departments_table');
        $departmentFilter = $hasDepartment && $request->filled('department')
            ? (int) $request->department
            : 0;
        if ($departmentFilter > 0) {
            // A department owns equipment wherever it sits, including storage.
            $includeStorage = true;
        }

        $base = DB::table('equipment_table')
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
            ->whereNotNull('equipment_table.equipment_room_id')
            ->whereNotIn('equipment_table.equipment_inventory_status', ['Disposed']);

        if ($focusEquipmentId > 0) {
            $base->where('equipment_table.equipment_id', $focusEquipmentId);
        }

        $onLoanOffCampus = (clone $base)
            ->where('equipment_table.equipment_inventory_status', TransferLocks::BORROWED)
            ->where(function ($query) {
                $query->whereNull('rooms_table.room_type')
                    ->orWhere('rooms_table.room_type', '!=', RoomCategories::STORAGE_TYPE);
            })
            ->count();

        if ($request->filled('status')) {
            $base->where('equipment_table.equipment_inventory_status', $request->status);
        } elseif ($focusEquipmentId === 0) {
            // Borrowed items are off campus, so they are not counted as deployed.
            $base->where('equipment_table.equipment_inventory_status', '!=', TransferLocks::BORROWED);
        }

        if (! $includeStorage) {
            $base->where(function ($query) {
                $query->whereNull('rooms_table.room_type')
                    ->orWhere('rooms_table.room_type', '!=', RoomCategories::STORAGE_TYPE);
            });
        }

        if ($request->filled('room')) {
            $base->where('equipment_table.equipment_room_id', $request->room);
        }

        if ($request->filled('category')) {
            $base->where('equipment_table.equipment_category_id', $request->category);
        }

        if ($departmentFilter > 0) {
            $base->where('equipment_table.equipment_department_id', $departmentFilter);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $base->where(function ($query) use ($search) {
                $query->where('equipment_table.equipment_name', 'LIKE', $search)
                    ->orWhere('equipment_table.equipment_brand_name', 'LIKE', $search)
                    ->orWhere('equipment_table.equipment_model', 'LIKE', $search)
                    ->orWhere('equipment_table.equipment_asset_tag', 'LIKE', $search)
                    ->orWhere('equipment_table.equipment_serial_number', 'LIKE', $search)
                    ->orWhere('rooms_table.room_name', 'LIKE', $search);
            });
        }

        if ($request->boolean('on_floor')) {
            $base->where(function ($query) {
                $query->whereNull('equipment_table.equipment_placement_zone')
                    ->orWhere('equipment_table.equipment_placement_zone', '!=', 'Holding');
            })->where(function ($query) {
                $query->whereNull('equipment_table.equipment_current_location')
                    ->orWhere('equipment_table.equipment_current_location', '!=', 'Holding');
            });
        }

        $totals = (clone $base)
            ->selectRaw('COUNT(DISTINCT equipment_table.equipment_room_id) as rooms_with_stock')
            ->selectRaw('COALESCE(SUM(equipment_table.equipment_quantity), 0) as total_quantity')
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(NULLIF(equipment_table.equipment_placement_zone, ''), equipment_table.equipment_current_location, '') = 'Holding' THEN 0 ELSE equipment_table.equipment_quantity END), 0) as on_floor_quantity")
            ->first();

        $unitQuery = (clone $base)->select(
            'equipment_table.*',
            'equipment_categories_table.equipment_category_name',
            'rooms_table.room_name',
            'rooms_table.room_type'
        );
        if ($hasDepartment) {
            $unitQuery->leftJoin('departments_table as unit_dept', 'equipment_table.equipment_department_id', '=', 'unit_dept.department_id')
                ->addSelect('unit_dept.department_name as unit_department_name');
        }

        $equipment = $unitQuery
            ->orderBy('rooms_table.room_name')
            ->orderBy('equipment_table.equipment_name')
            ->orderBy('equipment_table.equipment_asset_tag')
            ->paginate(15)
            ->withQueryString();

        $pendingTransferIds = TransferRequests::pendingEquipmentIds();
        $pendingDisposalIds = DisposalRequests::pendingEquipmentIds();
        $openLoans = TransferLocks::openLoans($equipment->pluck('equipment_id')->all());
        foreach ($equipment as $row) {
            $row->transfer_pending = isset($pendingTransferIds[(int) $row->equipment_id]);
            $row->disposal_pending = isset($pendingDisposalIds[(int) $row->equipment_id]);
            $row->open_loan = $openLoans->get((int) $row->equipment_id);
            $row->transfer_lock = TransferLocks::reason($row, $openLoans);
        }

        $transfersThisMonth = 0;
        $transfersLastMonth = 0;
        if (Schema::hasTable('equipment_transfer_history_table')) {
            $transfersThisMonth = DB::table('equipment_transfer_history_table')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();
            $transfersLastMonth = DB::table('equipment_transfer_history_table')
                ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
                ->count();
        }

        $isAdmin = RoleAccess::isAdmin(Auth::user());

        return view('maintenance-personnel.equipment.deployed', [
            'equipment' => $equipment,
            'categories' => $categories,
            'rooms' => $rooms,
            'deployRooms' => $rooms->reject(fn ($room) => RoomCategories::isStorageType($room->room_type ?? null))->values(),
            'roomsWithStock' => (int) ($totals->rooms_with_stock ?? 0),
            'totalDeployedQty' => (int) ($totals->total_quantity ?? 0),
            'onFloorQty' => (int) ($totals->on_floor_quantity ?? 0),
            'transfersThisMonth' => $transfersThisMonth,
            'transfersLastMonth' => $transfersLastMonth,
            'transferNeedsApproval' => TransferRequests::requiresApproval(),
            'pendingTransferCount' => TransferRequests::pendingCount(),
            'transferActionBase' => $isAdmin ? '/admin/inventory' : '/maintenance/equipment',
            'transferRequestsUrl' => $isAdmin ? '/admin/inventory/transfer-requests' : '/maintenance/equipment/transfer-requests',
            'disposeAction' => $request->is('admin/*') || ! RoleAccess::hasRole(RoleAccess::MAINTENANCE)
                ? '/admin/inventory/dispose'
                : '/maintenance/disposal/store',
            'disposalNeedsApproval' => DisposalRequests::requiresApproval(),
            'pendingDisposalCount' => DisposalRequests::pendingCount(),
            'disposalRequestsUrl' => $isAdmin ? '/admin/disposal/requests' : '/maintenance/disposal/requests',
            'transferPeople' => Custodians::assignable(),
            'transferDepartments' => Departments::options(),
            'attentionFocus' => $attentionFocus,
            'deployedUrl' => $deployedUrl,
            'onLoanOffCampus' => $request->filled('status') ? 0 : $onLoanOffCampus,
            'borrowingUrl' => RoleAccess::isAdmin(Auth::user()) ? route('admin.borrowing.index') : null,
            'filters' => [
                'search' => $request->get('search', ''),
                'room' => $request->get('room', ''),
                'category' => $request->get('category', ''),
                'department' => $departmentFilter ?: '',
                'status' => $request->get('status', ''),
                'on_floor' => $request->boolean('on_floor'),
                'include_storage' => $includeStorage,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ROOMS DIRECTORY
    |--------------------------------------------------------------------------
    */

    public function roomsDirectory(Request $request)
    {
        $sort = $request->get('sort', 'room_name');
        $dir = $request->get('dir') === 'desc' ? 'desc' : 'asc';
        $orderColumns = [
            'room_name' => 'rooms_table.room_name',
            'type' => 'rooms_table.room_type',
            'floor' => 'floors_table.floor_level',
            'equipment' => 'equipment_count',
        ];
        $orderBy = $orderColumns[$sort] ?? 'rooms_table.room_name';

        $query = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where('rooms_table.room_is_archived', false)
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
                function ($join) {
                    $join->on(
                        'rooms_table.room_id',
                        '=',
                        'equipment_table.equipment_room_id'
                    );
                }
            )
            ->select(
                'rooms_table.room_id',
                'rooms_table.room_name',
                'rooms_table.room_type',
                'rooms_table.room_floor_id',
                'rooms_table.room_status',
                'floors_table.floor_level',
                'buildings_table.building_id',
                'buildings_table.building_name',
                // Match Building Layout "Assets registered": total quantity in the room.
                DB::raw('COALESCE(SUM(equipment_table.equipment_quantity), 0) as equipment_count'),
                DB::raw('COUNT(equipment_table.equipment_id) as archive_equipment_count')
            )
            ->groupBy(
                'rooms_table.room_id',
                'rooms_table.room_name',
                'rooms_table.room_type',
                'rooms_table.room_floor_id',
                'rooms_table.room_status',
                'floors_table.floor_level',
                'buildings_table.building_id',
                'buildings_table.building_name'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rooms_table.room_name', 'like', '%'.$search.'%')
                    ->orWhere('buildings_table.building_name', 'like', '%'.$search.'%')
                    ->orWhere('floors_table.floor_level', 'like', '%'.$search.'%');
                if (RoomDepartments::ready()) {
                    $q->orWhereExists(function ($sub) use ($search) {
                        $sub->select(DB::raw(1))
                            ->from(RoomDepartments::TABLE.' as rd')
                            ->join('departments_table as d', 'd.department_id', '=', 'rd.department_id')
                            ->whereColumn('rd.room_id', 'rooms_table.room_id')
                            ->where('d.department_name', 'like', '%'.$search.'%');
                    });
                } elseif (Schema::hasColumn('rooms_table', 'room_department_id') && Schema::hasTable('departments_table')) {
                    $q->orWhereExists(function ($sub) use ($search) {
                        $sub->select(DB::raw(1))
                            ->from('departments_table as d')
                            ->whereColumn('d.department_id', 'rooms_table.room_department_id')
                            ->where('d.department_name', 'like', '%'.$search.'%');
                    });
                }
            });
        }

        if ($request->filled('building')) {
            $query->where('buildings_table.building_id', $request->building);
        }

        $rooms = $query
            ->orderBy($orderBy, $dir)
            ->paginate(10)
            ->withQueryString();

        $departmentsByRoom = RoomDepartments::forRooms($rooms->getCollection()->pluck('room_id'));
        $rooms->getCollection()->transform(function ($room) use ($departmentsByRoom) {
            $depts = $departmentsByRoom[(int) $room->room_id] ?? [];
            $room->departments = $depts;
            $room->department_ids = collect($depts)->pluck('id')->all();
            $room->department_name = RoomDepartments::label($depts);

            return $room;
        });

        $buildings = DB::table('buildings_table')
            ->orderBy('building_name')
            ->get();

        $floors = DB::table('floors_table')
            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )
            ->select(
                'floors_table.floor_id',
                'floors_table.floor_level',
                'buildings_table.building_name'
            )
            ->orderBy('buildings_table.building_name')
            ->orderBy('floors_table.floor_level')
            ->get();

        $roomTypes = RoomCategories::options();
        $departments = Departments::options();
        $managedRoomTypes = RoomCategories::manageList(trim((string) $request->get('type_search', '')));
        $roomTypesReady = RoomCategories::tableReady();

        $activeRoomsQuery = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where('room_is_archived', false)
            );

        $totalRooms = (clone $activeRoomsQuery)->count();

        $normalRooms = (clone $activeRoomsQuery)
            ->where('room_status', 'Normal')
            ->count();

        $needsAttentionRooms = (clone $activeRoomsQuery)
            ->whereIn('room_status', [RoomStatuses::UNDER_MAINTENANCE, RoomStatuses::CRITICAL])
            ->count();

        $roomsWithEquipment = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where('rooms_table.room_is_archived', false)
            )
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('equipment_table')
                    ->whereColumn(
                        'equipment_table.equipment_room_id',
                        'rooms_table.room_id'
                    )
                    ->whereNotIn(
                        'equipment_table.equipment_inventory_status',
                        ['Disposed']
                    );
            })
            ->count();

        $roomsWithEquipmentPercentage = $totalRooms > 0
            ? ($roomsWithEquipment / $totalRooms) * 100
            : 0;

        $needsAttentionPercentage = $totalRooms > 0
            ? ($needsAttentionRooms / $totalRooms) * 100
            : 0;

        $normalRoomsPercentage = $totalRooms > 0
            ? ($normalRooms / $totalRooms) * 100
            : 0;

        $createdAtColumn = Schema::hasColumn('rooms_table', 'room_created_at')
            ? 'room_created_at'
            : (Schema::hasColumn('rooms_table', 'created_at') ? 'created_at' : null);

        $currentMonthRooms = 0;
        $previousMonthRooms = 0;
        $roomMonthlyPercentage = 0;
        $roomMonthlyTrend = collect();

        if ($createdAtColumn) {
            $currentMonthRooms = DB::table('rooms_table')
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($q) => $q->where('room_is_archived', false)
                )
                ->whereBetween($createdAtColumn, [
                    now()->copy()->startOfMonth(),
                    now()->copy()->endOfMonth(),
                ])
                ->count();

            $previousMonthRooms = DB::table('rooms_table')
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($q) => $q->where('room_is_archived', false)
                )
                ->whereBetween($createdAtColumn, [
                    now()->copy()->subMonthNoOverflow()->startOfMonth(),
                    now()->copy()->subMonthNoOverflow()->endOfMonth(),
                ])
                ->count();

            if ($previousMonthRooms > 0) {
                $roomMonthlyPercentage =
                    (($currentMonthRooms - $previousMonthRooms) / $previousMonthRooms) * 100;
            } elseif ($currentMonthRooms > 0) {
                $roomMonthlyPercentage = null;
            } else {
                $roomMonthlyPercentage = 0;
            }

            $monthlyRoomRows = DB::table('rooms_table')
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($q) => $q->where('room_is_archived', false)
                )
                ->selectRaw(
                    'YEAR('.$createdAtColumn.') AS room_year, MONTH('.$createdAtColumn.') AS room_month, COUNT(*) AS room_count'
                )
                ->where($createdAtColumn, '>=', now()->copy()->subMonths(11)->startOfMonth())
                ->groupBy('room_year', 'room_month')
                ->get()
                ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->room_year, $row->room_month));

            $roomMonthlyTrend = collect(range(0, 11))->map(function ($offset) use ($monthlyRoomRows) {
                $month = now()->copy()->subMonths(11 - $offset)->startOfMonth();
                $key = $month->format('Y-m');

                return [
                    'label' => $month->format('M Y'),
                    'count' => (int) ($monthlyRoomRows[$key]->room_count ?? 0),
                ];
            });
        } else {
            $roomMonthlyTrend = collect(range(0, 11))->map(fn () => [
                'label' => '',
                'count' => 0,
            ]);
        }

        $roomNames = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where('room_is_archived', false)
            )
            ->pluck('room_name');

        $duplicateRoomGroups = RoomName::duplicateGroupCount($roomNames);

        $historyRoom = null;
        $reportHistory = null;
        $roomLifecycleEvents = collect();
        $roomLifecycleSummary = [];
        $roomLifecycleStageCounts = [];
        $historyPeriod = '';
        $historyMonth = null;

        if ($request->filled('history')) {
            $historyRoom = DB::table('rooms_table')
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
                ->where('rooms_table.room_id', $request->history)
                ->select(
                    'rooms_table.*',
                    'floors_table.floor_level',
                    'buildings_table.building_name'
                )
                ->first();

            if (! $historyRoom) {
                return redirect()
                    ->route('admin.rooms.index')
                    ->with('error', 'Room not found.');
            }

            $roomId = (int) $historyRoom->room_id;
            $historyFrom = null;
            $historyTo = null;

            try {
                if ($request->filled('history_month')) {
                    $historyMonth = \Carbon\Carbon::createFromFormat('Y-m', $request->history_month)->startOfMonth();
                    $historyFrom = $historyMonth->copy()->startOfMonth();
                    $historyTo = $historyMonth->copy()->endOfMonth();
                } elseif ($request->filled('history_date')) {
                    $day = \Carbon\Carbon::parse($request->history_date);
                    $historyFrom = $day->copy()->startOfDay();
                    $historyTo = $day->copy()->endOfDay();
                } else {
                    $historyPeriod = (string) $request->get('history_period', '');
                    $periodStarts = [
                        'today' => now()->copy()->startOfDay(),
                        'week' => now()->copy()->startOfWeek(),
                        'month' => now()->copy()->startOfMonth(),
                        'year' => now()->copy()->startOfYear(),
                    ];
                    if (isset($periodStarts[$historyPeriod])) {
                        $historyFrom = $periodStarts[$historyPeriod];
                    }
                }
            } catch (\Throwable $e) {
                $historyFrom = null;
                $historyTo = null;
                $historyMonth = null;
                $historyPeriod = '';
            }

            $historyQuery = DB::table('reports_table')
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
                ->leftJoin(
                    'users_table as assigned_personnel',
                    'reports_table.report_assigned_personnel_id',
                    '=',
                    'assigned_personnel.user_id'
                )
                ->leftJoin(
                    'rooms_table',
                    'reports_table.report_room_id',
                    '=',
                    'rooms_table.room_id'
                )
                ->where(fn ($q) => ReportGrouping::whereTicketTouchesRoom($q, $roomId))
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($q) => $q->where('reports_table.report_is_archived', false)
                )
                ->select(
                    'reports_table.*',
                    'equipment_table.equipment_name',
                    'reporters_table.reporter_full_name',
                    'reporters_table.reporter_employee_id',
                    'assigned_personnel.user_full_name as assigned_personnel_name',
                    'rooms_table.room_name'
                );

            if ($historyFrom) {
                $historyQuery->where('reports_table.report_submitted_at', '>=', $historyFrom);
            }
            if ($historyTo) {
                $historyQuery->where('reports_table.report_submitted_at', '<=', $historyTo);
            }

            if ($request->filled('history_search')) {
                $historySearch = trim((string) $request->history_search);
                $historyQuery->where(function ($query) use ($historySearch) {
                    $query->whereRaw(
                        'CAST(reports_table.report_id AS CHAR) LIKE ?',
                        ['%'.$historySearch.'%']
                    )
                        ->orWhere('equipment_table.equipment_name', 'like', '%'.$historySearch.'%')
                        ->orWhere('reports_table.report_unlisted_equipment_name', 'like', '%'.$historySearch.'%')
                        ->orWhere('reports_table.report_suggested_issue', 'like', '%'.$historySearch.'%')
                        ->orWhere('reports_table.report_problem_description', 'like', '%'.$historySearch.'%')
                        ->orWhere('reporters_table.reporter_full_name', 'like', '%'.$historySearch.'%');
                });
            }

            if (
                $request->filled('history_status')
                && in_array(
                    $request->history_status,
                    ['Pending', 'Processing', 'Resolved', 'For Replacement', 'Rejected'],
                    true
                )
            ) {
                $historyQuery->where(
                    'reports_table.report_current_status',
                    $request->history_status
                );
            }

            $reportHistory = $historyQuery
                ->orderByDesc('reports_table.report_submitted_at')
                ->paginate(10, ['*'], 'history_page')
                ->withQueryString();

            ReportItems::attachToReports($reportHistory->getCollection());
            ReportItems::attachTimelines($reportHistory->getCollection());

            $lifecycle = \App\Support\RoomTimeline::forRoom($roomId, [
                'from' => $historyFrom,
                'to' => $historyTo,
                'search' => $request->input('history_search'),
            ]);

            $roomLifecycleEvents = collect($lifecycle['events'] ?? []);
            $roomLifecycleSummary = $lifecycle['summary'] ?? [];
            $roomLifecycleStageCounts = $lifecycle['stage_counts'] ?? [];
        }

        return view(
            'admin.rooms.index',
            compact(
                'rooms',
                'buildings',
                'floors',
                'roomTypes',
                'departments',
                'managedRoomTypes',
                'roomTypesReady',
                'totalRooms',
                'normalRooms',
                'normalRoomsPercentage',
                'needsAttentionRooms',
                'needsAttentionPercentage',
                'roomsWithEquipment',
                'roomsWithEquipmentPercentage',
                'roomMonthlyPercentage',
                'roomMonthlyTrend',
                'duplicateRoomGroups',
                'sort',
                'dir',
                'historyRoom',
                'reportHistory',
                'roomLifecycleEvents',
                'roomLifecycleSummary',
                'roomLifecycleStageCounts',
                'historyPeriod',
                'historyMonth'
            )
        );
    }

    public function storeDirectoryRoom(Request $request)
    {
        $roomTypes = RoomCategories::values();

        // Support both single-room legacy fields and multi-row rooms[] payload.
        if (! $request->has('rooms') && $request->filled('room_name')) {
            $request->merge([
                'rooms' => [[
                    'room_name' => $request->input('room_name'),
                    'room_floor_id' => $request->input('room_floor_id'),
                    'room_type' => $request->input('room_type'),
                    'room_status' => $request->input('room_status'),
                    'department_ids' => $request->input('department_ids', $request->input('room_department_id') ? [$request->input('room_department_id')] : []),
                ]],
            ]);
        }

        $validated = $request->validate([
            'rooms' => ['required', 'array', 'min:1', 'max:50'],
            'rooms.*.room_name' => ['required', 'string', 'max:255'],
            'rooms.*.room_floor_id' => ['required', 'exists:floors_table,floor_id'],
            'rooms.*.room_type' => ['nullable', 'in:'.implode(',', $roomTypes)],
            'rooms.*.room_status' => ['nullable', Rule::in(RoomStatuses::all())],
            'rooms.*.department_ids' => ['nullable', 'array'],
            'rooms.*.department_ids.*' => [
                'integer',
                Rule::exists('departments_table', 'department_id'),
            ],
        ]);

        $rows = collect($validated['rooms'])
            ->map(function ($row) {
                $departmentIds = collect($row['department_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'room_name' => trim($row['room_name']),
                    'room_floor_id' => (int) $row['room_floor_id'],
                    'room_type' => ($row['room_type'] ?? null) ?: 'Lecture Room',
                    'room_status' => RoomStatuses::normalize($row['room_status'] ?? null),
                    'department_ids' => $departmentIds,
                ];
            })
            ->filter(fn ($row) => $row['room_name'] !== '')
            ->values();

        if ($rows->isEmpty()) {
            return back()
                ->withErrors(['rooms' => 'Add at least one room with a name.'])
                ->withInput();
        }

        $errors = [];
        $seenNames = [];

        foreach ($rows as $index => $row) {
            $name = $row['room_name'];

            foreach ($seenNames as $otherIndex => $otherName) {
                if (RoomName::matches($name, $otherName)) {
                    $errors['rooms.'.$index.'.room_name'] =
                        '“'.$name.'” duplicates “'.$otherName.'” in this form (row '.($otherIndex + 1).').';
                    break;
                }
            }

            if (! isset($errors['rooms.'.$index.'.room_name'])) {
                $matched = $this->matchingRoomName($name);
                if ($matched) {
                    $errors['rooms.'.$index.'.room_name'] =
                        '“'.$name.'” is the same room as “'.$matched->room_name.'”. Spellings like ComLab 1, Com Lab 1, and Computer Laboratory 1 are treated as one room.';
                }
            }

            $seenNames[$index] = $name;
        }

        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        $floorIndexes = [];

        DB::transaction(function () use ($rows, &$floorIndexes) {
            foreach ($rows as $row) {
                $floorId = $row['room_floor_id'];

                if (! isset($floorIndexes[$floorId])) {
                    $floorIndexes[$floorId] = DB::table('rooms_table')
                        ->where('room_floor_id', $floorId)
                        ->count();
                }

                $index = $floorIndexes[$floorId];
                $floorIndexes[$floorId]++;

                $values = [
                    'room_floor_id' => $floorId,
                    'room_name' => $row['room_name'],
                    'room_type' => $row['room_type'],
                    'room_status' => $row['room_status'],
                    'room_x' => 70 + (($index % 6) * 190),
                    'room_y' => 80 + ((int) floor($index / 6) * 190),
                    'room_width' => 150,
                    'room_height' => 105,
                ];

                if (Schema::hasColumn('rooms_table', 'room_color')) {
                    $values['room_color'] = '#60A5FA';
                }

                if (Schema::hasColumn('rooms_table', 'room_is_archived')) {
                    $values['room_is_archived'] = false;
                }

                if (Schema::hasColumn('rooms_table', 'room_created_at')) {
                    $values['room_created_at'] = now();
                }

                if (Schema::hasColumn('rooms_table', 'room_updated_at')) {
                    $values['room_updated_at'] = now();
                }

                if (Schema::hasColumn('rooms_table', 'created_at')) {
                    $values['created_at'] = now();
                }

                if (Schema::hasColumn('rooms_table', 'updated_at')) {
                    $values['updated_at'] = now();
                }

                $roomId = (int) DB::table('rooms_table')->insertGetId($values);
                RoomDepartments::sync($roomId, $row['department_ids'] ?? []);
            }
        });

        $count = $rows->count();

        return redirect()
            ->route('admin.rooms.index')
            ->with(
                'success',
                $count === 1 ? 'Room added.' : "{$count} rooms added."
            );
    }

    public function updateDirectoryRoom(Request $request, $id)
    {
        $room = DB::table('rooms_table')->where('room_id', $id)->first();
        if (! $room) {
            return back()->withErrors(['room_name' => 'Room not found.']);
        }

        $roomTypes = RoomCategories::values($room->room_type ?? null);
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'room_floor_id' => ['required', 'exists:floors_table,floor_id'],
            'room_type' => ['nullable', 'in:'.implode(',', $roomTypes)],
            'room_status' => ['nullable', Rule::in(RoomStatuses::all())],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => [
                'integer',
                Rule::exists('departments_table', 'department_id'),
            ],
        ]);

        $matched = $this->matchingRoomName($validated['room_name'], (int) $id);
        if ($matched) {
            return back()
                ->withErrors([
                    'room_name' => '“'.$validated['room_name'].'” is the same room as “'.$matched->room_name.'”.',
                ])
                ->withInput();
        }

        $departmentIds = collect($validated['department_ids'] ?? [])
            ->map(fn ($deptId) => (int) $deptId)
            ->filter(fn ($deptId) => $deptId > 0)
            ->unique()
            ->values()
            ->all();

        $values = [
            'room_name' => trim($validated['room_name']),
            'room_floor_id' => $validated['room_floor_id'],
            'room_type' => $validated['room_type'] ?: $room->room_type,
            'room_status' => RoomStatuses::normalize($validated['room_status'] ?? $room->room_status),
        ];
        if (Schema::hasColumn('rooms_table', 'room_updated_at')) {
            $values['room_updated_at'] = now();
        }
        if (Schema::hasColumn('rooms_table', 'updated_at')) {
            $values['updated_at'] = now();
        }

        $oldDeptIds = RoomDepartments::idsForRoom((int) $id);
        $oldDeptLabel = RoomDepartments::label(RoomDepartments::forRooms([(int) $id])[(int) $id] ?? []);
        $newDeptLabel = $departmentIds === []
            ? '—'
            : DB::table('departments_table')
                ->whereIn('department_id', $departmentIds)
                ->orderBy('department_name')
                ->pluck('department_name')
                ->implode(', ');

        $changes = [];
        if (trim((string) $room->room_name) !== $values['room_name']) {
            $changes[] = 'name “'.$room->room_name.'” → “'.$values['room_name'].'”';
        }
        if ((int) $room->room_floor_id !== (int) $values['room_floor_id']) {
            $changes[] = 'floor updated';
        }
        if ((string) ($room->room_type ?? '') !== (string) $values['room_type']) {
            $changes[] = 'type “'.($room->room_type ?: '—').'” → “'.$values['room_type'].'”';
        }
        if ((string) ($room->room_status ?? '') !== (string) $values['room_status']) {
            $changes[] = 'status “'.($room->room_status ?: '—').'” → “'.$values['room_status'].'”';
        }
        $oldSorted = $oldDeptIds;
        sort($oldSorted);
        $newSorted = $departmentIds;
        sort($newSorted);
        if ($oldSorted !== $newSorted) {
            $changes[] = 'departments “'.$oldDeptLabel.'” → “'.$newDeptLabel.'”';
        }

        DB::table('rooms_table')->where('room_id', $id)->update($values);
        RoomDepartments::sync((int) $id, $departmentIds);

        if ($changes !== [] && Schema::hasTable('room_activity_logs_table')) {
            RoomActivityLog::create([
                'room_id' => (int) $id,
                'user_id' => Auth::id(),
                'activity_type' => 'room_updated',
                'activity_title' => 'Room Updated',
                'activity_description' => implode('; ', $changes),
                'created_at' => now(),
            ]);
        }

        return redirect()->route('admin.rooms.index')->with('success', 'Room updated.');
    }

    public function archiveDirectoryRoom(Request $request, $id)
    {
        return redirect()
            ->route('admin.rooms.index')
            ->with(
                'error',
                'Rooms cannot be archived. A room stays in the building — edit its type, name, or status instead; changes are kept in history.'
            );
    }

    public function mergeDuplicateRooms()
    {
        $rooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where(function ($query) {
                    $query->where('room_is_archived', false)->orWhereNull('room_is_archived');
                })
            )
            ->orderBy('room_id')
            ->get();

        $groups = [];
        foreach ($rooms as $room) {
            $placed = false;
            foreach ($groups as $index => $group) {
                if (RoomName::matches($room->room_name, $group[0]->room_name)) {
                    $groups[$index][] = $room;
                    $placed = true;
                    break;
                }
            }
            if (! $placed) {
                $groups[] = [$room];
            }
        }

        $merged = 0;
        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }
            $keep = $group[0];
            foreach (array_slice($group, 1) as $duplicate) {
                DB::table('equipment_table')
                    ->where('equipment_room_id', $duplicate->room_id)
                    ->update(['equipment_room_id' => $keep->room_id]);

                if (Schema::hasColumn('rooms_table', 'room_is_archived')) {
                    DB::table('rooms_table')->where('room_id', $duplicate->room_id)->update([
                        'room_is_archived' => true,
                        'room_archived_at' => Schema::hasColumn('rooms_table', 'room_archived_at') ? now() : null,
                    ]);
                } else {
                    DB::table('rooms_table')->where('room_id', $duplicate->room_id)->delete();
                }
                $merged++;
            }
        }

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', $merged > 0 ? "Merged {$merged} duplicate room(s)." : 'No duplicate rooms found.');
    }

    public function storeRoomType(Request $request)
    {
        abort_unless(RoomCategories::tableReady(), 503, 'Run php artisan migrate to enable room types.');

        $validated = $request->validate([
            'room_type_name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('room_types_table', 'room_type_name'),
            ],
            'room_type_label' => ['nullable', 'string', 'max:120'],
            'room_type_is_storage' => ['nullable', 'boolean'],
        ]);

        RoomCategories::create([
            'room_type_name' => $validated['room_type_name'],
            'room_type_label' => $validated['room_type_label'] ?? null,
            'room_type_is_storage' => $request->boolean('room_type_is_storage'),
        ]);

        return redirect()
            ->route('admin.rooms.index', ['manage_types' => 1])
            ->with('success', 'Room type added.');
    }

    public function updateRoomType(Request $request, int $id)
    {
        abort_unless(RoomCategories::tableReady(), 503, 'Run php artisan migrate to enable room types.');

        $type = RoomCategories::find($id);
        if (! $type) {
            return back()->with('error', 'Room type not found.');
        }

        $validated = $request->validate([
            'room_type_name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('room_types_table', 'room_type_name')->ignore($id, 'room_type_id'),
            ],
            'room_type_label' => ['nullable', 'string', 'max:120'],
            'room_type_is_storage' => ['nullable', 'boolean'],
        ]);

        RoomCategories::update($id, [
            'room_type_name' => $validated['room_type_name'],
            'room_type_label' => $validated['room_type_label'] ?? null,
            'room_type_is_storage' => $request->boolean('room_type_is_storage'),
        ]);

        return redirect()
            ->route('admin.rooms.index', ['manage_types' => 1])
            ->with('success', 'Room type updated.');
    }

    public function archiveRoomType(Request $request, int $id)
    {
        abort_unless(RoomCategories::tableReady(), 503, 'Run php artisan migrate to enable room types.');

        $type = RoomCategories::find($id);
        if (! $type) {
            return back()->with('error', 'Room type not found.');
        }

        $archive = ! $request->boolean('restore');
        RoomCategories::setArchived($id, $archive);

        return redirect()
            ->route('admin.rooms.index', ['manage_types' => 1])
            ->with('success', $archive ? 'Room type archived.' : 'Room type restored.');
    }

    public function destroyRoomType(int $id)
    {
        abort_unless(RoomCategories::tableReady(), 503, 'Run php artisan migrate to enable room types.');

        $type = RoomCategories::find($id);
        if (! $type) {
            return back()->with('error', 'Room type not found.');
        }

        if (! RoomCategories::delete($id)) {
            return redirect()
                ->route('admin.rooms.index', ['manage_types' => 1])
                ->with('error', 'Cannot delete “'.$type->room_type_name.'” while rooms still use it. Archive it instead, or reassign those rooms first.');
        }

        return redirect()
            ->route('admin.rooms.index', ['manage_types' => 1])
            ->with('success', 'Room type deleted.');
    }

    private function matchingRoomName(string $name, ?int $ignoreId = null)
    {
        $existingRooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where(function ($query) {
                    $query->where('room_is_archived', false)->orWhereNull('room_is_archived');
                })
            )
            ->when($ignoreId, fn ($q) => $q->where('room_id', '!=', $ignoreId))
            ->get(['room_id', 'room_name']);

        return $existingRooms->first(
            fn ($existing) => RoomName::matches($name, (string) $existing->room_name)
        );
    }

    public function roomEquipmentPeek($id)
    {
        $room = DB::table('rooms_table')
            ->where('room_id', $id)
            ->first();

        if (! $room) {
            return response()->json(['message' => 'Room not found.'], 404);
        }

        $items = DB::table('equipment_table')
            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )
            ->where('equipment_table.equipment_room_id', $id)
            ->whereNotIn('equipment_table.equipment_inventory_status', ['Disposed', 'For Replacement'])
            ->orderBy('equipment_table.equipment_name')
            ->orderBy('equipment_table.equipment_asset_tag')
            ->orderBy('equipment_table.equipment_serial_number')
            ->select(
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_quantity',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_serial_number',
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_tracking_mode',
                'equipment_categories_table.equipment_category_name'
            )
            ->get()
            ->map(function ($item) {
                $isBulk = ($item->equipment_tracking_mode ?? 'Individual') === 'Bulk';
                $identifiers = collect([
                    filled($item->equipment_asset_tag) ? 'Tag '.$item->equipment_asset_tag : null,
                    (! $isBulk && filled($item->equipment_serial_number)) ? 'Serial '.$item->equipment_serial_number : null,
                    filled($item->equipment_qr_code) ? 'QR '.$item->equipment_qr_code : null,
                    $isBulk ? 'Qty '.(int) $item->equipment_quantity : null,
                ])->filter()->values()->all();

                return [
                    'equipment_id' => (int) $item->equipment_id,
                    'equipment_name' => (string) $item->equipment_name,
                    'equipment_quantity' => (int) ($item->equipment_quantity ?? 1),
                    'equipment_inventory_status' => (string) ($item->equipment_inventory_status ?? ''),
                    'equipment_category_name' => (string) ($item->equipment_category_name ?? 'Uncategorized'),
                    'equipment_asset_tag' => (string) ($item->equipment_asset_tag ?? ''),
                    'equipment_serial_number' => (string) ($item->equipment_serial_number ?? ''),
                    'equipment_qr_code' => (string) ($item->equipment_qr_code ?? ''),
                    'equipment_tracking_mode' => (string) ($item->equipment_tracking_mode ?? 'Individual'),
                    'uniqueness' => $identifiers !== []
                        ? implode(' · ', $identifiers)
                        : 'No asset tag / serial',
                ];
            })
            ->values();

        $deployedBase = request()->is('admin/*')
            ? url('/admin/inventory/deployed')
            : url('/maintenance/equipment/deployed');

        return response()->json([
            'room' => $room->room_name,
            'count' => $items->count(),
            'items' => $items,
            'inventory_url' => $deployedBase.'?room='.$id,
        ]);
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
            'admin.categories.index',

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
                'required|string|max:'.\App\Support\InputLimits::CATEGORY_NAME.'|unique:equipment_categories_table,equipment_category_name',

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
            'Equipment classification added successfully.'
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
                'Equipment classification not found.'
            );

        }


        // =====================================================
        // VALIDATE CATEGORY
        // =====================================================

        $validated = $request->validate([

            'equipment_category_name' => [

                'required',

                'string',

                'max:'.\App\Support\InputLimits::CATEGORY_NAME,

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
            'Equipment classification updated successfully.'
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
                'Equipment classification not found.'
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
            'Equipment classification deleted successfully.'
        );
    }

    public function suggestedIssues(Request $request)
    {
        $hasComponentColumn = Schema::hasColumn(
            'issue_templates_table',
            'issue_template_component'
        );

        $query = DB::table('issue_templates_table')
            ->leftJoin(
                'equipment_categories_table',
                'issue_templates_table.issue_template_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )
            ->select(
                'issue_templates_table.*',
                'equipment_categories_table.equipment_category_name'
            );

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('issue_templates_table.issue_template_name', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('equipment_categories_table.equipment_category_name', 'LIKE', '%'.$request->search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('issue_templates_table.issue_template_category_id', $request->category);
        }

        $issues = $query
            ->orderBy('equipment_categories_table.equipment_category_name')
            ->when(
                $hasComponentColumn,
                fn ($q) => $q->orderBy('issue_templates_table.issue_template_component')
            )
            ->orderBy('issue_templates_table.issue_template_name')
            ->paginate(12)
            ->withQueryString();

        $categories = DB::table('equipment_categories_table')
            ->orderBy('equipment_category_name')
            ->get();

        $components = SuggestedIssues::components();

        $totalIssues = DB::table('issue_templates_table')->count();

        $categoriesCovered = (int) DB::table('issue_templates_table')
            ->whereNotNull('issue_template_category_id')
            ->selectRaw('COUNT(DISTINCT issue_template_category_id) as aggregate')
            ->value('aggregate');

        $totalCategories = max(1, $categories->count());
        $categoriesCoveredPercentage = ($categoriesCovered / $totalCategories) * 100;

        if ($hasComponentColumn) {
            $componentSpecificIssues = DB::table('issue_templates_table')
                ->whereNotNull('issue_template_component')
                ->where('issue_template_component', '!=', '')
                ->count();
        } else {
            $componentSpecificIssues = 0;
        }

        $categoryWideIssues = max(0, $totalIssues - $componentSpecificIssues);
        $categoryWidePercentage = $totalIssues > 0
            ? ($categoryWideIssues / $totalIssues) * 100
            : 0;
        $componentSpecificPercentage = $totalIssues > 0
            ? ($componentSpecificIssues / $totalIssues) * 100
            : 0;

        $currentMonthIssues = DB::table('issue_templates_table')
            ->whereBetween('issue_template_created_at', [
                now()->copy()->startOfMonth(),
                now()->copy()->endOfMonth(),
            ])
            ->count();

        $previousMonthIssues = DB::table('issue_templates_table')
            ->whereBetween('issue_template_created_at', [
                now()->copy()->subMonthNoOverflow()->startOfMonth(),
                now()->copy()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->count();

        if ($previousMonthIssues > 0) {
            $issuesMonthlyPercentage =
                (($currentMonthIssues - $previousMonthIssues) / $previousMonthIssues) * 100;
        } elseif ($currentMonthIssues > 0) {
            $issuesMonthlyPercentage = null;
        } else {
            $issuesMonthlyPercentage = 0;
        }

        $monthlyIssueRows = DB::table('issue_templates_table')
            ->selectRaw('
                YEAR(issue_template_created_at) AS issue_year,
                MONTH(issue_template_created_at) AS issue_month,
                COUNT(*) AS issue_count
            ')
            ->whereNotNull('issue_template_created_at')
            ->where(
                'issue_template_created_at',
                '>=',
                now()->copy()->subMonths(11)->startOfMonth()
            )
            ->groupByRaw('
                YEAR(issue_template_created_at),
                MONTH(issue_template_created_at)
            ')
            ->orderByRaw('
                YEAR(issue_template_created_at),
                MONTH(issue_template_created_at)
            ')
            ->get()
            ->keyBy(function ($row) {
                return $row->issue_year.'-'.str_pad((string) $row->issue_month, 2, '0', STR_PAD_LEFT);
            });

        $issuesMonthlyTrend = collect();

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');

            $issuesMonthlyTrend->push([
                'month' => $month->format('M Y'),
                'count' => (int) ($monthlyIssueRows[$key]->issue_count ?? 0),
            ]);
        }

        $checkCounts = IssueChecks::countsByTemplate();
        $selfFixesTotal = IssueChecks::selfFixCount();
        $selfFixesThisMonth = IssueChecks::selfFixCount(now()->startOfMonth());

        return view(
            'admin.suggested-issues.index',
            compact(
                'checkCounts',
                'selfFixesTotal',
                'selfFixesThisMonth',
                'issues',
                'categories',
                'components',
                'totalIssues',
                'categoriesCovered',
                'categoriesCoveredPercentage',
                'categoryWideIssues',
                'categoryWidePercentage',
                'componentSpecificIssues',
                'componentSpecificPercentage',
                'issuesMonthlyPercentage',
                'issuesMonthlyTrend'
            )
        );
    }

    public function storeSuggestedIssue(Request $request)
    {
        $validated = $request->validate([
            'issue_template_category_id' => 'required|exists:equipment_categories_table,equipment_category_id',
            'issue_template_name' => 'required|string|max:255',
            'issue_template_component' => 'nullable|string|max:64',
            'issue_template_severity' => 'nullable|in:' . implode(',', ReportSeverity::levels()),
        ]);

        $payload = [
            'issue_template_category_id' => $validated['issue_template_category_id'],
            'issue_template_name' => trim($validated['issue_template_name']),
            'issue_template_created_at' => now(),
        ];

        if (Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            $payload['issue_template_component'] = ($validated['issue_template_component'] ?? null) ?: null;
        }

        if (ReportSeverity::hasTemplateColumn()) {
            $payload['issue_template_severity'] = ($validated['issue_template_severity'] ?? null) ?: null;
        }

        DB::table('issue_templates_table')->insert($payload);

        return back()->with('success', 'Suggested issue added.');
    }

    public function updateSuggestedIssue(Request $request, $id)
    {
        $validated = $request->validate([
            'issue_template_category_id' => 'required|exists:equipment_categories_table,equipment_category_id',
            'issue_template_name' => 'required|string|max:255',
            'issue_template_component' => 'nullable|string|max:64',
            'issue_template_severity' => 'nullable|in:' . implode(',', ReportSeverity::levels()),
        ]);

        $payload = [
            'issue_template_category_id' => $validated['issue_template_category_id'],
            'issue_template_name' => trim($validated['issue_template_name']),
        ];

        if (Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            $payload['issue_template_component'] = ($validated['issue_template_component'] ?? null) ?: null;
        }

        if (ReportSeverity::hasTemplateColumn()) {
            $payload['issue_template_severity'] = ($validated['issue_template_severity'] ?? null) ?: null;
        }

        DB::table('issue_templates_table')
            ->where('issue_template_id', $id)
            ->update($payload);

        return back()->with('success', 'Suggested issue updated.');
    }

    public function deleteSuggestedIssue($id)
    {
        if (IssueChecks::ready()) {
            DB::table(IssueChecks::TABLE)->where('issue_template_id', $id)->delete();
        }

        DB::table('issue_templates_table')
            ->where('issue_template_id', $id)
            ->delete();

        return back()->with('success', 'Suggested issue deleted.');
    }

    public function equipmentHistory(Request $request)
    {
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
            ->leftJoin('reports_table', function ($join) {
                $join->on('reports_table.report_equipment_id', '=', 'equipment_table.equipment_id');
            })
            ->select(
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_serial_number',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name',
                DB::raw('COUNT(DISTINCT reports_table.report_id) as report_count'),
                DB::raw('MAX(reports_table.report_submitted_at) as last_reported_at')
            )
            ->groupBy(
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_serial_number',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name'
            );

        if (Schema::hasTable('equipment_transfer_history_table')) {
            $query->addSelect(DB::raw(
                '(SELECT COUNT(*) FROM equipment_transfer_history_table
                  WHERE equipment_transfer_history_table.equipment_id = equipment_table.equipment_id) as transfer_count'
            ));
            $query->addSelect(DB::raw(
                '(SELECT MAX(created_at) FROM equipment_transfer_history_table
                  WHERE equipment_transfer_history_table.equipment_id = equipment_table.equipment_id) as last_transfer_at'
            ));
        } else {
            $query->addSelect(DB::raw('0 as transfer_count'));
            $query->addSelect(DB::raw('NULL as last_transfer_at'));
        }

        if (Schema::hasTable('equipment_maintenance_history_table')) {
            $query->addSelect(DB::raw(
                '(SELECT COUNT(*) FROM equipment_maintenance_history_table
                  WHERE equipment_maintenance_history_table.equipment_maintenance_equipment_id = equipment_table.equipment_id) as maintenance_count'
            ));
            $query->addSelect(DB::raw(
                '(SELECT MAX(COALESCE(equipment_maintenance_completed_at, equipment_maintenance_created_at))
                  FROM equipment_maintenance_history_table
                  WHERE equipment_maintenance_history_table.equipment_maintenance_equipment_id = equipment_table.equipment_id) as last_maintenance_at'
            ));
        } else {
            $query->addSelect(DB::raw('0 as maintenance_count'));
            $query->addSelect(DB::raw('NULL as last_maintenance_at'));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $search = '%'.$request->search.'%';
                $q->where('equipment_table.equipment_name', 'LIKE', $search)
                    ->orWhere('rooms_table.room_name', 'LIKE', $search)
                    ->orWhere('equipment_categories_table.equipment_category_name', 'LIKE', $search)
                    ->orWhere('equipment_table.equipment_asset_tag', 'LIKE', $search)
                    ->orWhere('equipment_table.equipment_serial_number', 'LIKE', $search);
            });
        }

        $selectedTypes = collect((array) $request->input('types', []))
            ->filter()
            ->values()
            ->all();

        $matchingIds = EquipmentTimeline::matchingEquipmentIds(
            $request->input('date_from'),
            $request->input('date_to'),
            $selectedTypes ?: null
        );

        if ($matchingIds !== null) {
            if ($matchingIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('equipment_table.equipment_id', $matchingIds);
            }
        }

        $equipment = $query
            ->orderByDesc(DB::raw('COALESCE(MAX(reports_table.report_submitted_at), equipment_table.equipment_created_at)'))
            ->orderBy('equipment_table.equipment_name')
            ->paginate(12)
            ->withQueryString();

        $equipment->getCollection()->transform(function ($item) {
            $timestamps = collect([
                $item->last_reported_at,
                $item->last_transfer_at ?? null,
                $item->last_maintenance_at ?? null,
            ])->filter();

            $item->last_activity_at = $timestamps->max();
            $item->event_count = (int) $item->report_count
                + (int) ($item->transfer_count ?? 0)
                + (int) ($item->maintenance_count ?? 0);

            return $item;
        });

        $totalEquipment = DB::table('equipment_table')->count();

        $equipmentWithReports = (int) DB::table('reports_table')
            ->whereNotNull('report_equipment_id')
            ->selectRaw('COUNT(DISTINCT report_equipment_id) as aggregate')
            ->value('aggregate');

        $equipmentWithReportsPercentage = $totalEquipment > 0
            ? ($equipmentWithReports / $totalEquipment) * 100
            : 0;

        $totalReports = DB::table('reports_table')
            ->whereNotNull('report_equipment_id')
            ->count();

        $openReports = DB::table('reports_table')
            ->whereNotNull('report_equipment_id')
            ->whereIn('report_current_status', ['Pending', 'Processing'])
            ->count();

        $openReportsPercentage = $totalReports > 0
            ? ($openReports / $totalReports) * 100
            : 0;

        $currentMonthReports = DB::table('reports_table')
            ->whereNotNull('report_equipment_id')
            ->whereBetween('report_submitted_at', [
                now()->copy()->startOfMonth(),
                now()->copy()->endOfMonth(),
            ])
            ->count();

        $previousMonthReports = DB::table('reports_table')
            ->whereNotNull('report_equipment_id')
            ->whereBetween('report_submitted_at', [
                now()->copy()->subMonthNoOverflow()->startOfMonth(),
                now()->copy()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->count();

        if ($previousMonthReports > 0) {
            $reportsMonthlyPercentage =
                (($currentMonthReports - $previousMonthReports) / $previousMonthReports) * 100;
        } elseif ($currentMonthReports > 0) {
            $reportsMonthlyPercentage = null;
        } else {
            $reportsMonthlyPercentage = 0;
        }

        $monthlyReportRows = DB::table('reports_table')
            ->selectRaw('
                YEAR(report_submitted_at) AS report_year,
                MONTH(report_submitted_at) AS report_month,
                COUNT(*) AS report_count
            ')
            ->whereNotNull('report_equipment_id')
            ->whereNotNull('report_submitted_at')
            ->where(
                'report_submitted_at',
                '>=',
                now()->copy()->subMonths(11)->startOfMonth()
            )
            ->groupByRaw('
                YEAR(report_submitted_at),
                MONTH(report_submitted_at)
            ')
            ->orderByRaw('
                YEAR(report_submitted_at),
                MONTH(report_submitted_at)
            ')
            ->get()
            ->keyBy(function ($row) {
                return $row->report_year.'-'.str_pad((string) $row->report_month, 2, '0', STR_PAD_LEFT);
            });

        $reportsMonthlyTrend = collect();

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');

            $reportsMonthlyTrend->push([
                'month' => $month->format('M Y'),
                'count' => (int) ($monthlyReportRows[$key]->report_count ?? 0),
            ]);
        }

        $eventTypes = EquipmentTimeline::eventTypes();
        $historyUrl = $request->is('admin/*', 'school-admin/*')
            ? AdminPortal::route('equipment-history.index')
            : url('/maintenance/equipment/history');
        // School Administrators open equipment through their own monitor page.
        $profileUrlBase = AdminPortal::isSchoolAdmin() ? AdminPortal::url('operations/equipment') : null;

        return view(
            'maintenance-personnel.equipment.history',
            compact(
                'historyUrl',
                'profileUrlBase',
                'equipment',
                'totalEquipment',
                'equipmentWithReports',
                'equipmentWithReportsPercentage',
                'totalReports',
                'openReports',
                'openReportsPercentage',
                'reportsMonthlyPercentage',
                'reportsMonthlyTrend',
                'selectedTypes',
                'eventTypes'
            )
        );
    }

    public function equipmentTimeline(Request $request, $id)
    {
        $payload = EquipmentTimeline::forEquipment((int) $id, [
            'from' => $request->input('from', $request->input('date_from')),
            'to' => $request->input('to', $request->input('date_to')),
            'types' => $request->input('types'),
        ]);

        if (! $payload['equipment']) {
            return response()->json(['message' => 'Equipment not found.'], 404);
        }

        return response()->json($payload);
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

        $equipment->equipment_department_name = ! empty($equipment->equipment_department_id)
            ? (Departments::find((int) $equipment->equipment_department_id)->department_name ?? null)
            : null;

        $equipmentBack = EquipmentViewReturn::resolve(request());
        $lifecycle = EquipmentTimeline::forEquipment((int) $id);
        $lifecycleProfile = $lifecycle['equipment'] ?? null;
        $lifecycleEvents = collect($lifecycle['events'] ?? []);
        $lifecycleCounts = $lifecycle['counts'] ?? [];
        $conditionHistory = EquipmentConditionHistory::forEquipment((int) $id, 20);
        $repairCostTotal = 0.0;
        if (Schema::hasTable('equipment_maintenance_history_table')
            && Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_repair_cost')) {
            $repairCostTotal = (float) DB::table('equipment_maintenance_history_table')
                ->where('equipment_maintenance_equipment_id', $id)
                ->sum('equipment_maintenance_repair_cost');
        }

        $propertyAssignment = PropertyAssignments::current((int) $id);
        $assignmentHistory = PropertyAssignments::history((int) $id);
        $assignmentBlocker = PropertyAssignments::blocker($equipment);
        $assignablePeople = $assignmentBlocker === null
            ? \App\Support\Custodians::assignable()
            : collect();

        return view(
            'maintenance-personnel.equipment.view',
            compact(
                'equipment',
                'equipmentBack',
                'lifecycleProfile',
                'lifecycleEvents',
                'lifecycleCounts',
                'conditionHistory',
                'repairCostTotal',
                'propertyAssignment',
                'assignmentHistory',
                'assignmentBlocker',
                'assignablePeople'
            ) + ['reportSummary' => $lifecycle['report_summary'] ?? null]
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
    public function receivableStockLines()
    {
        $mapLine = static fn ($line) => [
            'id' => $line->receiving_report_item_id,
            'receiving_report_item_id' => $line->receiving_report_item_id,
            'receiving_report_id' => $line->receiving_report_id,
            'rr_number' => $line->rr_number,
            'po_number' => $line->po_number,
            'atp_number' => $line->atp_number,
            'article' => $line->article,
            'quantity' => $line->quantity,
            'received_qty' => (int) ($line->received_qty ?? $line->quantity),
            'ordered_qty' => $line->ordered_qty,
            'stocked_qty' => (int) ($line->stocked_qty ?? 0),
            'remaining_qty' => (int) ($line->remaining_qty ?? 0),
            'is_selectable' => (bool) ($line->is_selectable ?? false),
            'status_label' => $line->status_label ?? null,
            'unit' => $line->unit,
            'unit_cost' => $line->unit_cost,
            'amount' => $line->amount,
            'supplier_id' => $line->supplier_id,
            'supplier_name' => $line->supplier_name,
            'purchase_date' => $line->purchase_date,
            'rr_date' => $line->rr_date,
            'invoice_no' => $line->invoice_no,
            'dr_no' => $line->dr_no,
            'label' => $line->label,
            'is_replacement' => (bool) ($line->is_replacement ?? false),
            'is_back_order_replacement' => (bool) ($line->is_back_order_replacement ?? false),
            'replaces_label' => $line->replaces_label ?? null,
            'replaces_equipment_id' => $line->replaces_equipment_id ?? null,
            'replacement_request_id' => $line->replacement_request_id ?? null,
        ];

        $guide = ReceivableStockLines::guideLines()->map($mapLine)->values();
        $pending = $guide->filter(fn ($line) => ! empty($line['is_selectable']))->values();
        $replacementCount = $pending->filter(fn ($line) => ! empty($line['is_replacement']))->count();

        return response()->json([
            'ok' => true,
            'lines' => $pending,
            'guide' => $guide,
            'count' => $pending->count(),
            'guide_count' => $guide->count(),
            'replacement_count' => $replacementCount,
        ]);
    }

    public function stockPendingReceivables(Request $request)
    {
        $message = 'Bulk “Add all pending” is disabled. Use Add to inventory for equipment (Individual units), or Replacement arrived → Stock & Link for replacement deliveries.';
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 410);
        }

        return redirect('/admin/inventory')
            ->with('error', $message);
    }

    public function equipmentOpenBalance()
    {
        $summary = EquipmentOpenBalance::summary(100);

        return response()->json([
            'ok' => true,
            'lines' => $summary['lines'],
            'totals' => $summary['totals'],
        ]);
    }

    public function equipmentAuditPack($id)
    {
        return EquipmentAuditPack::download((int) $id);
    }

    public function storeEquipment(Request $request)
    {
        // =====================================================
        // VALIDATE EQUIPMENT
        // =====================================================

        $validated = $request->validate([

            'equipment_name' => 'required|string|'.\App\Support\InputLimits::name(),

            'equipment_category_id' => 'required',

            'equipment_room_id' => 'required',

            'equipment_quantity' => 'required|integer|min:1|max:'.min(200, \App\Support\InputLimits::QTY),

            'equipment_tracking_mode' => 'nullable|in:Bulk,Individual',

            'equipment_condition_status' => 'nullable|'.\App\Support\EquipmentConditions::ruleIn(),

            'equipment_inventory_status' => 'nullable|'.\App\Support\EquipmentInventoryStatuses::ruleIn(),

            'equipment_asset_tag' => 'nullable|string|max:'.\App\Support\InputLimits::ASSET_TAG,

            'equipment_brand_name' => 'nullable|string|'.\App\Support\InputLimits::name(),

            'equipment_model' => 'nullable|string|'.\App\Support\InputLimits::name(),

            'equipment_serial_number' => 'nullable|string|max:'.\App\Support\InputLimits::SERIAL,

            'equipment_purchase_date' => 'nullable|date',

            'equipment_acquired_date' => 'nullable|date',

            'equipment_purchase_cost' => 'nullable|numeric|min:0|max:'.\App\Support\InputLimits::COST,

            'equipment_warranty_expiration' => 'nullable|date',

            'equipment_useful_life_years' => 'nullable|integer|min:1|max:50',

            'receiving_report_item_id' => 'nullable|integer|min:1',

            'intake_basis' => 'nullable|in:receiving,non_procurement',

            'intake_reason' => 'nullable|string|'.\App\Support\InputLimits::notes(),

            'items' => 'nullable|array|max:200',

            'items.*.equipment_asset_tag' => 'nullable|string|max:'.\App\Support\InputLimits::ASSET_TAG,

            'items.*.equipment_serial_number' => 'nullable|string|max:'.\App\Support\InputLimits::SERIAL,

            'items.*.equipment_brand_name' => 'nullable|string|'.\App\Support\InputLimits::name(),

            'items.*.equipment_model' => 'nullable|string|'.\App\Support\InputLimits::name(),

            'items.*.equipment_condition_status' => 'nullable|'.\App\Support\EquipmentConditions::ruleIn(),

            'items.*.equipment_warranty_expiration' => 'nullable|date',

            'equipment_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',

            'items.*.equipment_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',

            'deploy_room_id' => 'nullable|integer|min:1',
            'items.*.deploy_room_id' => 'nullable|integer|min:0',

            'custodian_id' => 'nullable|integer|min:1',

            'replaces_equipment_id' => 'nullable|integer|min:1',

        ] + EquipmentAcquisition::validationRules());

        $trackingMode = $validated['equipment_tracking_mode'] ?? EquipmentTrackingModes::INDIVIDUAL;
        $items = array_values($validated['items'] ?? []);
        $quantity = (int) $validated['equipment_quantity'];
        $intakeBasis = $validated['intake_basis'] ?? null;
        $rrItemId = (int) ($validated['receiving_report_item_id'] ?? 0);
        $rrLine = null;
        $supplierId = null;

        $categoryIdForTracking = (int) ($validated['equipment_category_id'] ?? 0);
        if (
            $rrItemId > 0
            && $trackingMode === EquipmentTrackingModes::BULK
            && EquipmentTrackingModes::requiresIndividualForCategory($categoryIdForTracking)
        ) {
            return back()
                ->withErrors([
                    'equipment_tracking_mode' => 'RR equipment lines must be stocked as Individual units (one identity per piece). Bulk is only for consumables/supplies.',
                ])
                ->withInput();
        }

        // Inventory stocking should come from a completed RR line (what was delivered).
        // Non-procurement intake remains available with a required reason (donation/legacy).
        $isInventoryRedirect = str_contains((string) $request->headers->get('referer', ''), '/admin/inventory')
            || $request->boolean('require_receiving_basis');

        if ($isInventoryRedirect || $intakeBasis === 'receiving' || $rrItemId > 0) {
            if ($intakeBasis === 'non_procurement') {
                $reason = trim((string) ($validated['intake_reason'] ?? ''));
                if ($reason === '') {
                    return back()
                        ->withErrors(['intake_reason' => 'Enter a reason for non-procurement intake (donation, legacy, found, etc.).'])
                        ->withInput();
                }
                if (EquipmentAcquisition::ready() && blank($validated['equipment_acquisition_source'] ?? null)) {
                    return back()
                        ->withErrors(['equipment_acquisition_source' => 'Select how this equipment was acquired.'])
                        ->withInput();
                }
            } else {
                if ($rrItemId < 1) {
                    return back()
                        ->withErrors(['receiving_report_item_id' => 'Select a received RR line as the basis for this equipment.'])
                        ->withInput();
                }

                $rrLine = ReceivableStockLines::findPendingItem($rrItemId);
                if (! $rrLine) {
                    return back()
                        ->withErrors(['receiving_report_item_id' => 'That RR line is unavailable or already in inventory.'])
                        ->withInput();
                }

                if ($quantity > (int) $rrLine->quantity) {
                    return back()
                        ->withErrors([
                            'equipment_quantity' => 'Quantity cannot exceed the quantity still available to add ('.$rrLine->quantity.') on '.$rrLine->rr_number.'.',
                        ])
                        ->withInput();
                }

                $validated['equipment_condition_status'] = 'Good';
                foreach ($items as $index => $item) {
                    $items[$index]['equipment_condition_status'] = 'Good';
                }

                $supplierId = $rrLine->supplier_id ? (int) $rrLine->supplier_id : null;
                if (empty($validated['equipment_purchase_date']) && ! empty($rrLine->purchase_date)) {
                    $validated['equipment_purchase_date'] = $rrLine->purchase_date;
                }
                if (
                    (! array_key_exists('equipment_purchase_cost', $validated) || $validated['equipment_purchase_cost'] === null)
                    && $rrLine->unit_cost !== null
                ) {
                    $validated['equipment_purchase_cost'] = $rrLine->unit_cost;
                }
            }
        }

        $roomId = (int) ($validated['equipment_room_id'] ?? 0);
        $room = $roomId > 0
            ? DB::table('rooms_table')
                ->where('room_id', $roomId)
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($query) => $query->where('room_is_archived', false)
                )
                ->first()
            : null;

        if (! $room) {
            $fallbackStorageRoomId = DB::table('rooms_table')
                ->where('room_type', RoomCategories::STORAGE_TYPE)
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($query) => $query->where('room_is_archived', false)
                )
                ->orderBy('room_name')
                ->value('room_id');

            if ($fallbackStorageRoomId) {
                $validated['equipment_room_id'] = (int) $fallbackStorageRoomId;
                $roomId = (int) $fallbackStorageRoomId;
            } else {
                return back()
                    ->withErrors([
                        'equipment_room_id' => 'Select a room, or create a storage room first.',
                    ])
                    ->withInput();
            }
        } elseif (! RoomCategories::isStorageType($room->room_type ?? null)) {
            // Inventory stocking must land in a storage room.
            $fallbackStorageRoomId = DB::table('rooms_table')
                ->where('room_type', RoomCategories::STORAGE_TYPE)
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($query) => $query->where('room_is_archived', false)
                )
                ->orderBy('room_name')
                ->value('room_id');

            if ($fallbackStorageRoomId) {
                $validated['equipment_room_id'] = (int) $fallbackStorageRoomId;
            } else {
                return back()
                    ->withErrors([
                        'equipment_room_id' => 'New equipment goes into a storage room first. Create one, or use Transfers to deploy it later.',
                    ])
                    ->withInput();
            }
        }

        // Purchased date/cost come from purchaser/receiving when possible.
        // Manual inventory adds only stamp when the asset entered campus stock.
        $acquiredDate = $validated['equipment_acquired_date'] ?? now()->toDateString();
        $purchaseDate = $validated['equipment_purchase_date'] ?? null;
        $purchaseCost = $validated['equipment_purchase_cost'] ?? null;
        $usefulLifeYears = array_key_exists('equipment_useful_life_years', $validated)
            && $validated['equipment_useful_life_years'] !== null
            && $validated['equipment_useful_life_years'] !== ''
                ? (int) $validated['equipment_useful_life_years']
                : null;

        if ($trackingMode === 'Individual' && count($items) > 0 && count($items) !== $quantity) {
            return back()
                ->withErrors(['items' => 'Item count must match the quantity.'])
                ->withInput();
        }

        // Bulk stock keeps unique name-per-room; Individual units may share a display name.
        if ($trackingMode === 'Bulk') {
            $duplicateName = DB::table('equipment_table')
                ->where('equipment_room_id', $request->equipment_room_id)
                ->whereRaw('LOWER(equipment_name) = ?', [mb_strtolower(trim((string) $request->equipment_name))])
                ->whereNotIn('equipment_inventory_status', ['Disposed'])
                ->exists();

            if ($duplicateName) {
                return back()
                    ->withErrors(['equipment_name' => 'That equipment name already exists in this room.'])
                    ->withInput();
            }
        }

        $identifierError = $this->validateEquipmentIdentifierUniqueness(
            $items,
            $validated,
            $trackingMode
        );

        if ($identifierError !== null) {
            return back()
                ->withErrors($identifierError)
                ->withInput();
        }

        $acquisitionColumns = [];
        if (EquipmentAcquisition::ready()) {
            $acquisitionColumns = $rrLine
                ? ['equipment_acquisition_source' => EquipmentAcquisition::PROCUREMENT]
                : EquipmentAcquisition::manualColumns($validated, $validated['intake_reason'] ?? null);
        }

        $deployRoom = null;
        $deployRoomId = (int) ($validated['deploy_room_id'] ?? 0);
        if ($deployRoomId > 0) {
            $deployRoom = DB::table('rooms_table')
                ->where('room_id', $deployRoomId)
                ->when(
                    Schema::hasColumn('rooms_table', 'room_is_archived'),
                    fn ($query) => $query->where('room_is_archived', false)
                )
                ->first();
            if (! $deployRoom || RoomCategories::isStorageType($deployRoom->room_type ?? null)) {
                return back()
                    ->withErrors(['deploy_room_id' => 'Choose a classroom, lab, or office to deploy to.'])
                    ->withInput();
            }
        }

        $itemRooms = collect();
        if ($deployRoom && $trackingMode === 'Individual' && count($items) > 0) {
            $itemRooms = \App\Support\RrLineIntake::deployRooms(array_column($items, 'deploy_room_id'));
        }

        $custodianId = (int) ($validated['custodian_id'] ?? 0);
        if ($custodianId > 0) {
            if (! $deployRoom) {
                return back()
                    ->withErrors(['custodian_id' => 'Deploy the equipment to a room before assigning it to a person.'])
                    ->withInput();
            }
            if ($trackingMode !== 'Individual') {
                return back()
                    ->withErrors(['custodian_id' => 'Only individually tracked equipment can be assigned to a person.'])
                    ->withInput();
            }
        }

        $createdIds = [];
        $createdRooms = [];
        $deployedRoomNames = [];

        // Shared photo applies to Bulk / single Individual units only.
        // Multi Individual units use optional per-row images on step 2.
        $useSharedImage = !($trackingMode === 'Individual' && $quantity > 1);
        $imagePath = $useSharedImage
            ? $this->storeOptionalEquipmentImage($request)
            : null;

        // =====================================================
        // START TRANSACTION
        // =====================================================

        try {
        DB::transaction(function () use (
            $request,
            $validated,
            $trackingMode,
            $items,
            $quantity,
            $imagePath,
            $useSharedImage,
            $acquiredDate,
            $purchaseDate,
            $purchaseCost,
            $usefulLifeYears,
            $rrLine,
            $supplierId,
            $acquisitionColumns,
            $deployRoom,
            $itemRooms,
            $custodianId,
            &$createdIds,
            &$createdRooms,
            &$deployedRoomNames
        ) {

            $borrowable = $request->has('equipment_is_borrowable');
            $hasSupplierColumn = Schema::hasColumn('equipment_table', 'equipment_supplier_id');
            $hasStockedBy = Schema::hasColumn('equipment_table', 'equipment_stocked_by');
            $hasRrItemFk = Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id');
            $hasLotCode = Schema::hasColumn('equipment_table', 'equipment_stock_lot_code');
            $stockedBy = Auth::id();
            $lotCode = $rrLine
                ? ('RR'.$rrLine->receiving_report_id.'-L'.$rrLine->receiving_report_item_id)
                : null;

            $applyLifecycleMeta = function (array $payload) use (
                $hasSupplierColumn,
                $supplierId,
                $hasStockedBy,
                $stockedBy,
                $hasRrItemFk,
                $rrLine,
                $hasLotCode,
                $lotCode,
                $acquisitionColumns
            ): array {
                $payload = array_merge($payload, $acquisitionColumns);
                if ($hasSupplierColumn && $supplierId) {
                    $payload['equipment_supplier_id'] = $supplierId;
                }
                if ($hasStockedBy && $stockedBy) {
                    $payload['equipment_stocked_by'] = $stockedBy;
                }
                if ($hasRrItemFk && $rrLine) {
                    $payload['equipment_receiving_report_item_id'] = (int) $rrLine->receiving_report_item_id;
                }
                if ($hasLotCode && $lotCode) {
                    $payload['equipment_stock_lot_code'] = $lotCode;
                }

                return $payload;
            };

            if ($trackingMode === 'Bulk') {
                $payload = $applyLifecycleMeta([
                    'equipment_category_id' => $validated['equipment_category_id'],
                    'equipment_room_id' => $validated['equipment_room_id'],
                    'equipment_asset_tag' => $validated['equipment_asset_tag'] ?? null,
                    'equipment_name' => $validated['equipment_name'],
                    'equipment_brand_name' => $validated['equipment_brand_name'] ?? null,
                    'equipment_model' => $validated['equipment_model'] ?? null,
                    'equipment_serial_number' => $validated['equipment_serial_number'] ?? null,
                    'equipment_quantity' => $quantity,
                    'equipment_tracking_mode' => 'Bulk',
                    'equipment_condition_status' => $validated['equipment_condition_status'] ?? 'Good',
                    'equipment_inventory_status' => $validated['equipment_inventory_status'] ?? 'Active',
                    'equipment_purchase_date' => $purchaseDate,
                    'equipment_acquired_date' => $acquiredDate,
                    'equipment_purchase_cost' => $purchaseCost,
                    'equipment_warranty_expiration' => $validated['equipment_warranty_expiration'] ?? null,
                    'equipment_useful_life_years' => $usefulLifeYears,
                    'equipment_is_borrowable' => $borrowable,
                    'equipment_image' => $imagePath,
                    'equipment_placement_zone' => 'Holding',
                    'equipment_current_location' => 'Holding',
                    'equipment_position_x' => 50,
                    'equipment_position_y' => 90,
                    'equipment_created_at' => now(),
                ]);

                $equipmentId = DB::table('equipment_table')->insertGetId($payload);

                EquipmentQrCodes::assignIfEligible((int) $equipmentId);
                $createdIds[] = (int) $equipmentId;
            } else {
                $rows = count($items) > 0
                    ? $items
                    : array_fill(0, $quantity, []);

                foreach ($rows as $index => $item) {
                    $rowImagePath = $useSharedImage
                        ? $imagePath
                        : $this->storeUploadedEquipmentImage(
                            $request->file("items.{$index}.equipment_image")
                        );

                    $payload = $applyLifecycleMeta([
                        'equipment_category_id' => $validated['equipment_category_id'],
                        'equipment_room_id' => $validated['equipment_room_id'],
                        'equipment_asset_tag' => $item['equipment_asset_tag']
                            ?? (($quantity === 1) ? ($validated['equipment_asset_tag'] ?? null) : null),
                        'equipment_name' => $validated['equipment_name'],
                        'equipment_brand_name' => $item['equipment_brand_name']
                            ?? ($validated['equipment_brand_name'] ?? null),
                        'equipment_model' => $item['equipment_model']
                            ?? ($validated['equipment_model'] ?? null),
                        'equipment_serial_number' => $item['equipment_serial_number']
                            ?? (($quantity === 1) ? ($validated['equipment_serial_number'] ?? null) : null),
                        'equipment_quantity' => 1,
                        'equipment_tracking_mode' => 'Individual',
                        'equipment_condition_status' => $item['equipment_condition_status']
                            ?? ($validated['equipment_condition_status'] ?? 'Good'),
                        'equipment_inventory_status' => $validated['equipment_inventory_status'] ?? 'Active',
                        'equipment_purchase_date' => $purchaseDate,
                        'equipment_acquired_date' => $acquiredDate,
                        'equipment_purchase_cost' => $purchaseCost,
                        'equipment_warranty_expiration' => $item['equipment_warranty_expiration']
                            ?? ($validated['equipment_warranty_expiration'] ?? null),
                        'equipment_useful_life_years' => $usefulLifeYears,
                        'equipment_is_borrowable' => $borrowable,
                        'equipment_image' => $rowImagePath,
                        'equipment_placement_zone' => 'Holding',
                        'equipment_current_location' => 'Holding',
                        'equipment_position_x' => 50,
                        'equipment_position_y' => 90,
                        'equipment_created_at' => now(),
                    ]);

                    $equipmentId = DB::table('equipment_table')->insertGetId($payload);

                    EquipmentQrCodes::assignIfEligible((int) $equipmentId);
                    $createdIds[] = (int) $equipmentId;
                    $createdRooms[(int) $equipmentId] = $itemRooms->get((int) ($item['deploy_room_id'] ?? 0)) ?? $deployRoom;
                }
            }

            if (
                $rrLine
                && ! empty($createdIds)
                && Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')
            ) {
                DB::table('receiving_report_items_table')
                    ->where('receiving_report_item_id', $rrLine->receiving_report_item_id)
                    ->where(function ($q) {
                        $q->whereNull('receiving_report_item_equipment_id')
                            ->orWhere('receiving_report_item_equipment_id', 0);
                    })
                    ->update([
                        'receiving_report_item_equipment_id' => (int) $createdIds[0],
                    ]);
            }

            $replacesId = (int) $request->input('replaces_equipment_id', 0);
            if ($replacesId < 1 && $rrLine) {
                $replacesId = $this->resolveReplacedEquipmentIdFromRr((int) $rrLine->receiving_report_id);
            }
            if (
                $replacesId > 0
                && ! empty($createdIds)
                && Schema::hasColumn('equipment_table', 'equipment_replaces_id')
            ) {
                foreach ($createdIds as $newId) {
                    DB::table('equipment_table')
                        ->where('equipment_id', $newId)
                        ->update(['equipment_replaces_id' => $replacesId]);
                }
                if (Schema::hasColumn('equipment_table', 'equipment_replaced_by_id')) {
                    DB::table('equipment_table')
                        ->where('equipment_id', $replacesId)
                        ->update(['equipment_replaced_by_id' => (int) $createdIds[0]]);
                }
            }

            if ($deployRoom && ! empty($createdIds)) {
                $storageRoomId = (int) $validated['equipment_room_id'];
                $hasTransferHistory = Schema::hasTable('equipment_transfer_history_table');
                foreach ($createdIds as $newId) {
                    $toRoom = $createdRooms[$newId] ?? $deployRoom;
                    if ($hasTransferHistory) {
                        DB::table('equipment_transfer_history_table')->insert([
                            'equipment_id' => $newId,
                            'from_room_id' => $storageRoomId ?: null,
                            'to_room_id' => (int) $toRoom->room_id,
                            'transferred_by' => Auth::id(),
                            'remarks' => 'Deployed on intake',
                            'created_at' => now(),
                        ]);
                    }
                    DB::table('equipment_table')
                        ->where('equipment_id', $newId)
                        ->update(['equipment_room_id' => (int) $toRoom->room_id]);
                    $deployedRoomNames[] = (string) $toRoom->room_name;
                }
            }

            if ($custodianId > 0 && ! empty($createdIds)) {
                PropertyAssignments::issueMany($createdIds, $custodianId, [
                    'notes' => 'Issued on intake',
                ]);
            }

            $count = count($createdIds);
            $lastId = (int) end($createdIds);

            $basisNote = '';
            if ($rrLine) {
                $basisNote = ' Basis: '.$rrLine->rr_number
                    .($rrLine->po_number ? ' / PO '.$rrLine->po_number : '')
                    .($rrLine->supplier_name ? ' / '.$rrLine->supplier_name : '')
                    .'.';
            } elseif (filled($acquisitionColumns['equipment_acquisition_source'] ?? null)) {
                $basisNote = ' Source: '.EquipmentAcquisition::sourceLabel($acquisitionColumns['equipment_acquisition_source']).'.';
            }
            $basisNote .= \App\Support\RrLineIntake::deployNote($deployedRoomNames);

            $this->logActivity(
                'Added equipment',
                'Equipment',
                'equipment_table',
                $lastId,
                ($count > 1
                    ? 'Added '.$count.' × '.$validated['equipment_name'].' to the equipment inventory.'
                    : 'Added '.$validated['equipment_name'].' to the equipment inventory.')
                .$basisNote
            );

        });
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['custodian_id' => $e->getMessage()])
                ->withInput();
        }

        // =====================================================
        // SUCCESS
        // =====================================================

        $count = count($createdIds);
        $message = $count > 1
            ? "{$count} equipment records added successfully."
            : 'Equipment added successfully.';
        $message .= \App\Support\RrLineIntake::deployNote($deployedRoomNames);
        if ($custodianId > 0) {
            $message .= ' Issued to the accountable person.';
        }

        return redirect(
            '/admin/inventory'
        )->with('success', $message);
    }

    public function storeEquipmentBatch(Request $request)
    {
        $data = EquipmentBatchIntake::validate($request->all());
        $result = EquipmentBatchIntake::store($data, fn ($file) => $this->storeUploadedEquipmentImage($file));

        $roomSummary = collect($result['rooms'])
            ->map(fn ($qty, $room) => $room.' ('.$qty.')')
            ->implode(', ');

        $this->logActivity(
            'Added equipment',
            'Equipment',
            'equipment_table',
            $result['last_id'],
            'Batch intake: '.$result['created'].' record(s) from '.$result['lines'].' item line(s). '
                .($roomSummary !== '' ? 'Placed in '.$roomSummary.'.' : '')
        );

        $message = $result['created'].' equipment record'.($result['created'] === 1 ? '' : 's')
            .' added from '.$result['lines'].' item'.($result['lines'] === 1 ? '' : 's').'.';
        if ($result['assigned'] > 0) {
            $message .= ' '.$result['assigned'].' issued under '.implode(', ', $result['documents']).'.';
        }

        session()->flash('success', $message);

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => url('/admin/inventory'),
            'result' => $result,
        ]);
    }

    public function quickAddSupplier(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255'], [], ['name' => 'supplier name']);
        $supplier = \App\Support\EquipmentAcquisition::quickAddSupplier((string) $request->input('name'));

        if (! $supplier->existing) {
            $this->logActivity(
                'Added supplier',
                'Supplier',
                'suppliers_table',
                $supplier->supplier_id,
                'Added supplier '.$supplier->supplier_name.' from equipment intake.'
            );
        }

        return response()->json([
            'id' => (string) $supplier->supplier_id,
            'name' => $supplier->supplier_name,
            'existing' => $supplier->existing,
        ]);
    }

    public function storeEquipmentFromRrLines(Request $request)
    {
        $data = \App\Support\RrLineIntake::validate($request->all());
        $result = \App\Support\RrLineIntake::store($data, fn ($file) => $this->storeUploadedEquipmentImage($file));

        $message = $result['created'].' equipment record'.($result['created'] === 1 ? '' : 's')
            .' added to inventory from '.$result['lines'].' RR line'.($result['lines'] === 1 ? '' : 's').'.'
            .\App\Support\RrLineIntake::deployNote($result['deploy_rooms']);
        if ($result['assigned'] > 0) {
            $message .= ' '.$result['assigned'].' issued under '.implode(', ', $result['documents']).'.';
        }

        $this->logActivity('Added equipment', 'Equipment', 'equipment_table', $result['last_id'], $message);

        session()->flash('success', $message);

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => url('/admin/inventory'),
        ]);
    }

    /**
     * @return array<string, string>|null
     */
    private function validateEquipmentIdentifierUniqueness(
        array $items,
        array $validated,
        string $trackingMode
    ): ?array {
        $candidates = count($items) > 0
            ? $items
            : [[
                'equipment_asset_tag' => $validated['equipment_asset_tag'] ?? null,
                'equipment_serial_number' => $validated['equipment_serial_number'] ?? null,
            ]];

        $assetTags = [];
        $serials = [];

        foreach ($candidates as $index => $item) {
            $tag = trim((string) ($item['equipment_asset_tag'] ?? ''));
            $serial = trim((string) ($item['equipment_serial_number'] ?? ''));

            if ($tag !== '') {
                $key = mb_strtolower($tag);
                if (isset($assetTags[$key])) {
                    return ['items' => 'Duplicate asset tag in this batch.'];
                }
                $assetTags[$key] = true;

                if (
                    DB::table('equipment_table')
                        ->whereRaw('LOWER(equipment_asset_tag) = ?', [$key])
                        ->whereNotIn('equipment_inventory_status', ['Disposed'])
                        ->exists()
                ) {
                    return ["items.{$index}.equipment_asset_tag" => "Asset tag \"{$tag}\" is already in use."];
                }
            }

            if ($serial !== '') {
                $key = mb_strtolower($serial);
                if (isset($serials[$key])) {
                    return ['items' => 'Duplicate serial number in this batch.'];
                }
                $serials[$key] = true;

                if (
                    DB::table('equipment_table')
                        ->whereRaw('LOWER(equipment_serial_number) = ?', [$key])
                        ->whereNotIn('equipment_inventory_status', ['Disposed'])
                        ->exists()
                ) {
                    return ["items.{$index}.equipment_serial_number" => "Serial number \"{$serial}\" is already in use."];
                }
            }
        }

        return null;
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
        // =====================================================
        // GET CURRENT EQUIPMENT
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $id
            )

            ->first();


        // =====================================================
        // EQUIPMENT NOT FOUND
        // =====================================================

        if (!$equipment) {

            return back()->with(
                'error',
                'Equipment not found.'
            );
        }

        // Bulk stock keeps unique name-per-room; Individual units may share a display name.
        $duplicateName = ($equipment->equipment_tracking_mode ?? 'Individual') === 'Bulk'
            && DB::table('equipment_table')
                ->where('equipment_room_id', $request->equipment_room_id)
                ->where('equipment_id', '!=', $id)
                ->whereRaw('LOWER(equipment_name) = ?', [mb_strtolower(trim((string) $request->equipment_name))])
                ->whereNotIn('equipment_inventory_status', ['Disposed'])
                ->exists();

        if ($duplicateName) {
            return back()
                ->withErrors(['equipment_name' => 'That equipment name already exists in this room.'])
                ->withInput();
        }

        $requestedRoomId = (int) ($request->equipment_room_id ?? 0);
        if (TransferRequests::requiresApproval()
            && $requestedRoomId > 0
            && $requestedRoomId !== (int) ($equipment->equipment_room_id ?? 0)) {
            return back()
                ->with('error', 'Moving equipment to another room needs the Administrator\'s approval. Use Transfer to send a request; the other changes were not saved.')
                ->withInput();
        }

        $request->validate([
            'equipment_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'remove_equipment_image' => 'nullable',
            'equipment_useful_life_years' => 'nullable|integer|min:1|max:50',
            'equipment_purchase_cost' => 'nullable|numeric|min:0',
        ] + EquipmentAcquisition::validationRules());

        $acquisitionColumns = EquipmentAcquisition::ready()
            && (int) ($equipment->equipment_receiving_report_item_id ?? 0) < 1
            && $request->has('equipment_acquisition_source')
                ? EquipmentAcquisition::manualColumns($request->all())
                : [];

        $previousImage = $equipment->equipment_image ?? null;
        $imagePath = $previousImage;

        if ($request->hasFile('equipment_image')) {
            $imagePath = $this->storeOptionalEquipmentImage($request);
        } elseif ($request->boolean('remove_equipment_image')) {
            $imagePath = null;
        }

        // =====================================================
        // START TRANSACTION
        // =====================================================

        DB::transaction(function () use (
            $request,
            $id,
            $equipment,
            $imagePath,
            $acquisitionColumns
        ) {
            $oldRoomId = (int) ($equipment->equipment_room_id ?? 0);
            $newRoomId = (int) ($request->equipment_room_id ?? 0);
            $oldCondition = $equipment->equipment_condition_status ?? null;
            $newCondition = $request->equipment_condition_status;

            // Room changes must go through transfer history (immutability).
            if ($newRoomId > 0 && $oldRoomId !== $newRoomId && Schema::hasTable('equipment_transfer_history_table')) {
                DB::table('equipment_transfer_history_table')->insert([
                    'equipment_id' => $id,
                    'from_room_id' => $oldRoomId ?: null,
                    'to_room_id' => $newRoomId,
                    'transferred_by' => Auth::id(),
                    'remarks' => trim((string) $request->input('transfer_remarks', '')) ?: 'Room change via equipment edit',
                    'created_at' => now(),
                ]);
            }

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

                    'equipment_purchase_date'
                        => $request->filled('equipment_purchase_date')
                            ? $request->equipment_purchase_date
                            : null,

                    'equipment_acquired_date'
                        => $request->filled('equipment_acquired_date')
                            ? $request->equipment_acquired_date
                            : null,

                    'equipment_purchase_cost'
                        => $request->filled('equipment_purchase_cost')
                            ? $request->equipment_purchase_cost
                            : null,

                    'equipment_warranty_expiration'
                        => $request->equipment_warranty_expiration,

                    'equipment_useful_life_years'
                        => $request->filled('equipment_useful_life_years')
                            ? max(1, min(50, (int) $request->equipment_useful_life_years))
                            : null,

                    'equipment_is_borrowable'
                        => $request->has('equipment_is_borrowable'),

                    'equipment_image'
                        => $imagePath,

                ] + $acquisitionColumns);

            EquipmentConditionHistory::record(
                (int) $id,
                $oldCondition,
                $newCondition,
                'equipment_edit',
                'Condition updated via edit form'
            );

            // =================================================
            // RECENT ACTIVITY
            // =================================================

            $this->logActivity(

                'Updated equipment',

                'Equipment',

                'equipment_table',

                (int) $id,

                'Updated equipment information for '
                . ($request->equipment_name
                    ?? $equipment->equipment_name)
                . '.'

            );

        });


        // =====================================================
        // SUCCESS
        // =====================================================

        if ($previousImage && $previousImage !== $imagePath) {
            $this->deleteEquipmentImageIfUnused($previousImage);
        }

        return back()->with(
            'success',
            'Equipment updated successfully.'
        );
    }

    private function resolveReplacedEquipmentIdFromRr(int $receivingReportId): int
    {
        return ReplacementDeliveries::replacedEquipmentIdForRr($receivingReportId);
    }

    private function storeOptionalEquipmentImage(Request $request): ?string
    {
        return $this->storeUploadedEquipmentImage(
            $request->file('equipment_image')
        );
    }

    private function storeUploadedEquipmentImage($file): ?string
    {
        if (!$file) {
            return null;
        }

        return $file->store('equipment-images', 'public');
    }

    private function deleteEquipmentImageIfUnused(?string $path): void
    {
        if (!filled($path)) {
            return;
        }

        $stillUsed = DB::table('equipment_table')
            ->where('equipment_image', $path)
            ->exists();

        if ($stillUsed) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT TRANSFER & HISTORY E - 2.0
    |--------------------------------------------------------------------------
    */

    public function equipmentTransferHistory(Request $request)
    {
        $record = max(0, (int) $request->query('record', 0));

        return redirect('/maintenance/equipment/deployed'.($record > 0 ? '?record='.$record : ''));
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
        $destination = $this->inventoryTransferDestination($request->all());
        if ($destination['error']) {
            return back()->with('error', $destination['error']);
        }

        $plan = $this->planSingleTransfer((int) $request->equipment_id, (int) $request->room_id, $destination);
        if ($plan['error']) {
            return back()->with('error', $plan['error']);
        }

        if (TransferRequests::requiresApproval()) {
            return $this->submitTransferRequest(
                'single',
                [(int) $plan['equipment']->equipment_id],
                $plan['room'],
                $destination,
                $request->remarks
            );
        }

        try {
            DB::transaction(fn () => $this->recordInventoryTransfer(
                $request,
                $plan['equipment'],
                $plan['oldRoom'],
                $plan['room'],
                $destination,
                $plan['sameRoom'],
                $request->remarks,
                Auth::id(),
                Auth::id()
            ));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $this->singleTransferMessage($destination, $plan['room']));
    }

    public function transferEquipmentBatch(Request $request)
    {
        $validated = $request->validate([
            'equipment_ids' => 'required|array|min:1',
            'equipment_ids.*' => 'integer',
            'room_id' => 'required|integer',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $destination = $this->inventoryTransferDestination($request->all());
        if ($destination['error']) {
            return back()->with('error', $destination['error']);
        }

        $destinationRoom = $this->batchTransferRoom((int) $validated['room_id']);
        if (is_string($destinationRoom)) {
            return back()->with('error', $destinationRoom);
        }

        $ids = collect($validated['equipment_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (TransferRequests::requiresApproval()) {
            return $this->submitTransferRequest('batch', $ids, $destinationRoom, $destination, $validated['remarks'] ?? null);
        }

        $result = $this->runBatchTransfer(
            $request,
            $ids,
            $destinationRoom,
            $destination,
            $validated['remarks'] ?? 'Batch transfer from Inventory',
            Auth::id(),
            Auth::id()
        );

        return back()->with($result['transferred'] > 0 ? 'success' : 'error', $result['message']);
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSFER REQUESTS (non-administrators need the Administrator's approval)
    |--------------------------------------------------------------------------
    */

    public function transferRequests(Request $request)
    {
        $requested = (string) $request->query('status', TransferRequests::PENDING);
        $status = in_array($requested, TransferRequests::STATUSES, true) ? $requested : null;

        return view('maintenance-personnel.equipment.transfer-requests', [
            'requests' => TransferRequests::list($status),
            'counts' => TransferRequests::statusCounts(),
            'status' => $status,
            'isAdminPortal' => $request->is('admin/*'),
        ]);
    }

    public function approveTransferRequest(Request $request, $id)
    {
        $outcome = DB::transaction(function () use ($request, $id) {
            $row = TransferRequests::find((int) $id, true);
            if (! $row || $row->transfer_request_status !== TransferRequests::PENDING) {
                return ['ok' => false, 'handled' => true, 'message' => 'This request was already handled.'];
            }

            $destination = $this->inventoryTransferDestination(TransferRequests::destinationInput($row));
            if ($destination['error']) {
                return ['ok' => false, 'message' => $destination['error']];
            }

            $ids = TransferRequests::equipmentIds($row);
            $requestedBy = $row->transfer_request_requested_by ? (int) $row->transfer_request_requested_by : null;
            $remarks = $row->transfer_request_remarks;

            if ($row->transfer_request_kind === 'batch') {
                $room = $this->batchTransferRoom((int) $row->transfer_request_room_id);
                if (is_string($room)) {
                    return ['ok' => false, 'message' => $room];
                }

                $result = $this->runBatchTransfer(
                    $request,
                    $ids,
                    $room,
                    $destination,
                    $remarks ?? 'Batch transfer from Inventory',
                    $requestedBy,
                    Auth::id()
                );
                if ($result['transferred'] === 0) {
                    return ['ok' => false, 'message' => $result['message']];
                }
                $message = $result['message'];
            } else {
                $plan = $this->planSingleTransfer($ids[0] ?? 0, (int) $row->transfer_request_room_id, $destination);
                if ($plan['error']) {
                    return ['ok' => false, 'message' => $plan['error']];
                }

                try {
                    DB::transaction(fn () => $this->recordInventoryTransfer(
                        $request,
                        $plan['equipment'],
                        $plan['oldRoom'],
                        $plan['room'],
                        $destination,
                        $plan['sameRoom'],
                        $remarks,
                        $requestedBy,
                        Auth::id()
                    ));
                } catch (\RuntimeException $e) {
                    return ['ok' => false, 'message' => $e->getMessage()];
                }
                $message = $this->singleTransferMessage($destination, $plan['room']);
            }

            TransferRequests::close((int) $row->transfer_request_id, TransferRequests::APPROVED, $request->input('note'), $message);
            $this->notifyTransferRequester($row, 'Transfer approved', 'Your transfer request was approved. '.$message, 'equipment_transfer_approved');

            return ['ok' => true, 'message' => 'Transfer approved. '.$message];
        });

        if ($outcome['ok'] || ! empty($outcome['handled'])) {
            return back()->with($outcome['ok'] ? 'success' : 'error', $outcome['message']);
        }

        return back()->with('error', 'Could not approve: '.$outcome['message'].' Reject the request if it is no longer valid.');
    }

    public function rejectTransferRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ], [
            'note.required' => 'Give a reason so the requester knows why the transfer was rejected.',
        ]);

        $row = TransferRequests::find((int) $id);
        if (! $row || $row->transfer_request_status !== TransferRequests::PENDING) {
            return back()->with('error', 'This request was already handled.');
        }

        TransferRequests::close((int) $row->transfer_request_id, TransferRequests::REJECTED, $validated['note']);
        $this->notifyTransferRequester(
            $row,
            'Transfer rejected',
            'Your request to transfer '.TransferRequests::itemsLabel(TransferRequests::equipmentIds($row))
                .' was rejected. Reason: '.trim($validated['note']),
            'equipment_transfer_rejected'
        );

        return back()->with('success', 'Transfer request rejected. The equipment was not moved.');
    }

    public function cancelTransferRequest($id)
    {
        $row = TransferRequests::find((int) $id);
        if (! $row || $row->transfer_request_status !== TransferRequests::PENDING) {
            return back()->with('error', 'This request was already handled.');
        }
        if ((int) $row->transfer_request_requested_by !== (int) Auth::id()) {
            return back()->with('error', 'Only the person who sent the request can cancel it.');
        }

        TransferRequests::close((int) $row->transfer_request_id, TransferRequests::CANCELLED);

        return back()->with('success', 'Transfer request cancelled.');
    }

    private function submitTransferRequest(string $kind, array $equipmentIds, object $room, array $destination, ?string $remarks)
    {
        $pending = TransferRequests::pendingEquipmentIds();
        $free = array_values(array_filter($equipmentIds, fn ($id) => ! isset($pending[$id])));
        $waiting = count($equipmentIds) - count($free);

        if ($free === []) {
            return back()->with(
                'error',
                count($equipmentIds) === 1
                    ? 'This equipment already has a transfer waiting for the Administrator\'s approval.'
                    : 'All of these items already have transfers waiting for the Administrator\'s approval.'
            );
        }

        $loans = TransferLocks::openLoans($free);
        $lockReasons = DB::table('equipment_table')
            ->whereIn('equipment_id', $free)
            ->get()
            ->mapWithKeys(fn ($equipment) => [(int) $equipment->equipment_id => TransferLocks::reason($equipment, $loans)])
            ->filter();
        $free = array_values(array_filter($free, fn ($id) => ! $lockReasons->has($id)));

        if ($free === []) {
            return back()->with('error', $lockReasons->first());
        }

        $requestId = TransferRequests::create($kind, $free, (int) $room->room_id, $destination, $remarks);

        $target = $destination['label'] ? $destination['label'].' ('.$room->room_name.')' : $room->room_name;
        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_ADMIN,
            'Equipment transfer needs approval',
            (Auth::user()->user_full_name ?? 'Maintenance').' asked to transfer '
                .TransferRequests::itemsLabel($free).' to '.$target.'.',
            'equipment_transfer_request',
            'equipment_transfer_request',
            $requestId,
            '/admin/inventory/transfer-requests'
        );

        $message = 'Transfer request sent to the Administrator for approval. The equipment stays where it is until it is approved.';
        if ($waiting > 0) {
            $message .= ' '.$waiting.' item'.($waiting === 1 ? ' was' : 's were').' left out because they already have a pending request.';
        }
        if ($lockReasons->isNotEmpty()) {
            $locked = $lockReasons->count();
            $message .= ' '.$locked.' item'.($locked === 1 ? ' was' : 's were').' left out because '
                .($locked === 1 ? 'it is' : 'they are').' on loan or under maintenance.';
        }

        return back()->with('success', $message);
    }

    private function notifyTransferRequester(object $row, string $title, string $message, string $type): void
    {
        if (empty($row->transfer_request_requested_by)) {
            return;
        }

        WorkflowNotifier::toUser(
            (int) $row->transfer_request_requested_by,
            WorkflowNotifier::ROLE_MAINTENANCE,
            $title,
            $message,
            $type,
            'equipment_transfer_request',
            (int) $row->transfer_request_id,
            '/maintenance/equipment/transfer-requests?status=all'
        );
    }

    private function singleTransferMessage(array $destination, object $room): string
    {
        return $destination['label']
            ? 'Equipment transferred to '.$destination['label'].' ('.$room->room_name.').'
            : 'Equipment transferred successfully.';
    }

    /**
     * Checks a one-item transfer and loads what it needs.
     *
     * @return array{error: ?string, equipment: ?object, oldRoom: ?object, room: ?object, sameRoom: bool}
     */
    private function planSingleTransfer(int $equipmentId, int $roomId, array $destination): array
    {
        $fail = fn (string $message) => ['error' => $message, 'equipment' => null, 'oldRoom' => null, 'room' => null, 'sameRoom' => false];

        $equipment = DB::table('equipment_table')->where('equipment_id', $equipmentId)->first();
        if (! $equipment) {
            return $fail('Equipment not found.');
        }

        if ($locked = TransferLocks::reason($equipment)) {
            return $fail($locked);
        }

        $room = DB::table('rooms_table')->where('room_id', $roomId)->first();
        if (! $room) {
            return $fail('Destination room not found.');
        }

        $sameRoom = (int) $equipment->equipment_room_id === $roomId;

        // Handing an item to a person or department may keep it in the room it is already in.
        if ($sameRoom && in_array($destination['type'], [null, 'room'], true)) {
            return $fail('Equipment is already assigned to this room.');
        }

        return [
            'error' => null,
            'equipment' => $equipment,
            'oldRoom' => DB::table('rooms_table')->where('room_id', $equipment->equipment_room_id)->first(),
            'room' => $room,
            'sameRoom' => $sameRoom,
        ];
    }

    /**
     * Active classroom/lab for a batch transfer, or the reason it cannot be used.
     *
     * @return object|string
     */
    private function batchTransferRoom(int $roomId)
    {
        $room = DB::table('rooms_table')
            ->where('room_id', $roomId)
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('room_is_archived', false)
            )
            ->first();

        if (! $room) {
            return 'Destination room not found.';
        }

        if (RoomCategories::isStorageType($room->room_type ?? null)) {
            return 'Choose a classroom or lab for a batch transfer. To move a unit into storage, transfer it on its own.';
        }

        return $room;
    }

    /**
     * @return array{transferred: int, skipped: int, message: string}
     */
    private function runBatchTransfer(
        Request $request,
        array $ids,
        object $destinationRoom,
        array $destination,
        ?string $remarks,
        ?int $transferredBy,
        ?int $approvedBy
    ): array {
        $transferred = 0;
        $skipped = 0;
        $firstSkipReason = null;

        DB::transaction(function () use ($ids, $destinationRoom, $destination, $request, $remarks, $transferredBy, $approvedBy, &$transferred, &$skipped, &$firstSkipReason) {
            foreach ($ids as $equipmentId) {
                $equipment = DB::table('equipment_table')
                    ->where('equipment_id', $equipmentId)
                    ->whereNotIn('equipment_inventory_status', ['Disposed'])
                    ->first();

                if (! $equipment) {
                    $skipped++;
                    continue;
                }

                if ($locked = TransferLocks::reason($equipment)) {
                    $skipped++;
                    $firstSkipReason ??= $locked;
                    continue;
                }

                $sameRoom = (int) $equipment->equipment_room_id === (int) $destinationRoom->room_id;
                if ($sameRoom && in_array($destination['type'], [null, 'room'], true)) {
                    $skipped++;
                    continue;
                }

                $oldRoom = DB::table('rooms_table')
                    ->where('room_id', $equipment->equipment_room_id)
                    ->first();

                try {
                    DB::transaction(fn () => $this->recordInventoryTransfer(
                        $request,
                        $equipment,
                        $oldRoom,
                        $destinationRoom,
                        $destination,
                        $sameRoom,
                        $remarks,
                        $transferredBy,
                        $approvedBy
                    ));
                } catch (\RuntimeException $e) {
                    $skipped++;
                    $firstSkipReason ??= ($equipment->equipment_name ?? 'Item').': '.$e->getMessage();
                    continue;
                }

                $transferred++;
            }
        });

        if ($transferred === 0) {
            return [
                'transferred' => 0,
                'skipped' => $skipped,
                'message' => $firstSkipReason
                    ?? ($skipped > 0
                        ? 'No equipment was transferred. Items may already be in that room.'
                        : 'No equipment was transferred.'),
            ];
        }

        $message = $transferred.' equipment item'.($transferred === 1 ? '' : 's')
            .' transferred to '
            .($destination['label'] ? $destination['label'].' ('.$destinationRoom->room_name.')' : $destinationRoom->room_name)
            .'.';

        if ($skipped > 0) {
            $message .= ' '.$skipped.' skipped.'.($firstSkipReason ? ' '.$firstSkipReason : '');
        }

        return ['transferred' => $transferred, 'skipped' => $skipped, 'message' => $message];
    }

    /**
     * Optional "transfer to" target sent by the Inventory and Deployed Equipment transfer forms.
     * Input without destination_type only moves the room.
     *
     * @return array{type: ?string, department_id: ?int, custodian_id: ?int, label: ?string, error: ?string}
     */
    private function inventoryTransferDestination(array $input): array
    {
        $none = ['type' => null, 'department_id' => null, 'custodian_id' => null, 'label' => null, 'error' => null];

        switch ((string) ($input['destination_type'] ?? '')) {
            case 'room':
                return ['type' => 'room'] + $none;

            case 'department':
                $department = Departments::find((int) ($input['department_id'] ?? 0));
                if (! $department || ! empty($department->department_is_archived)) {
                    return ['error' => 'Choose the department receiving the equipment.'] + $none;
                }

                return [
                    'type' => 'department',
                    'department_id' => (int) $department->department_id,
                    'label' => $department->department_name,
                ] + $none;

            case 'person':
                $person = Custodians::find((int) ($input['custodian_id'] ?? 0));
                if (! $person || ($person->custodian_status ?? null) !== Custodians::STATUS_ACTIVE) {
                    return ['error' => 'Choose an active person to receive the equipment.'] + $none;
                }

                return [
                    'type' => 'person',
                    'department_id' => ! empty($person->custodian_department_id) ? (int) $person->custodian_department_id : null,
                    'custodian_id' => (int) $person->custodian_id,
                    'label' => $person->custodian_full_name,
                ] + $none;
        }

        return $none;
    }

    /**
     * Moves one item, tags its department, issues it to the person when chosen,
     * and writes the transfer history, notification and audit log.
     * Must run inside a transaction; throws RuntimeException when the item cannot go to the person.
     */
    private function recordInventoryTransfer(
        Request $request,
        object $equipment,
        ?object $oldRoom,
        object $newRoom,
        array $destination,
        bool $sameRoom,
        ?string $remarks,
        ?int $transferredBy,
        ?int $approvedBy
    ): void {
        $history = [
            'equipment_id' => $equipment->equipment_id,
            'from_room_id' => $equipment->equipment_room_id,
            'to_room_id' => $newRoom->room_id,
            'transferred_by' => $transferredBy,
            'remarks' => $remarks,
            'created_at' => now(),
        ];
        if (Schema::hasColumn('equipment_transfer_history_table', 'approved_by')) {
            $history['approved_by'] = $approvedBy;
        }
        if (Schema::hasColumn('equipment_transfer_history_table', 'to_department_id')) {
            $history['to_department_id'] = $destination['department_id'];
            $history['to_custodian_id'] = $destination['custodian_id'];
        }

        $transferId = DB::table('equipment_transfer_history_table')->insertGetId($history);

        $equipmentUpdate = $sameRoom ? [] : [
            'equipment_room_id' => $newRoom->room_id,
            'equipment_placement_zone' => 'Holding',
            'equipment_current_location' => 'Holding',
            'equipment_position_x' => 50,
            'equipment_position_y' => 90,
        ];
        if ($destination['type'] !== null && Schema::hasColumn('equipment_table', 'equipment_department_id')) {
            $equipmentUpdate['equipment_department_id'] = $destination['department_id'];
        }
        if ($equipmentUpdate !== []) {
            DB::table('equipment_table')
                ->where('equipment_id', $equipment->equipment_id)
                ->update($equipmentUpdate);
        }

        if ($destination['type'] === 'person') {
            PropertyAssignments::issue((int) $equipment->equipment_id, (int) $destination['custodian_id'], [
                'notes' => $remarks ?: 'Issued through an Inventory transfer.',
            ]);
        }

        $summary = ($equipment->equipment_name ?? 'Equipment')
            .' was transferred from '
            .($oldRoom->room_name ?? 'Unassigned Location')
            .' to '
            .($destination['label'] ? $destination['label'].' ('.($newRoom->room_name ?? 'Unknown Location').')' : ($newRoom->room_name ?? 'Unknown Location'))
            .'.';

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
            'notification_event_key' => 'equipment_transferred_'.$transferId,
            'notification_created_at' => now(),
        ]);

        DB::table('audit_logs_table')->insert([
            'audit_log_user_id' => Auth::id(),
            'audit_log_action' => 'Transferred equipment',
            'audit_log_module' => 'Equipment',
            'audit_log_table_name' => 'equipment_table',
            'audit_log_reference_id' => $equipment->equipment_id,
            'audit_log_description' => $summary,
            'audit_log_ip_address' => $request->ip(),
            'audit_log_created_at' => now(),
        ]);
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
        ->insert(array_filter([

            'equipment_maintenance_equipment_id'
                => $request->equipment_id,

            'equipment_maintenance_status'
                => $request->status,

            'equipment_maintenance_findings'
                => $request->findings,

            'equipment_maintenance_repair_action'
                => $request->repair_action,

            'equipment_maintenance_parts_used'
                => Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_parts_used')
                    ? $request->input('parts_used')
                    : null,

            'equipment_maintenance_repair_cost'
                => Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_repair_cost')
                    ? $request->input('repair_cost')
                    : null,

            'equipment_maintenance_downtime_hours'
                => Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_downtime_hours')
                    ? $request->input('downtime_hours')
                    : null,

            'equipment_maintenance_personnel_id'
                => Auth::id(),

            'equipment_maintenance_completed_at'
                => in_array($request->status, ['Completed', 'Done', 'Fixed'], true) ? now() : null,

            'equipment_maintenance_proof_image'
                => $imagePath,

            'equipment_maintenance_created_at'
                => now()

        ], fn ($value) => $value !== null));

        if ($request->filled('condition_status') && Schema::hasTable('equipment_table')) {
            $equipment = DB::table('equipment_table')->where('equipment_id', $request->equipment_id)->first();
            if ($equipment) {
                $from = $equipment->equipment_condition_status ?? null;
                $to = $request->input('condition_status');
                DB::table('equipment_table')
                    ->where('equipment_id', $request->equipment_id)
                    ->update(['equipment_condition_status' => $to]);
                EquipmentConditionHistory::record(
                    (int) $request->equipment_id,
                    $from,
                    $to,
                    'maintenance',
                    $request->input('findings')
                );
            }
        }

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
        );

        $destinationColumns = TransferDestinations::join($history);

        $history = $history->select(

            'equipment_transfer_history_table.transfer_id',

            'equipment_transfer_history_table.equipment_id',

            'equipment_transfer_history_table.from_room_id',

            'equipment_transfer_history_table.to_room_id',

            'equipment_transfer_history_table.remarks',

            'equipment_transfer_history_table.created_at',

            'from_room.room_name as from_room_name',

            'to_room.room_name as to_room_name',

            ...$destinationColumns

        )

        ->orderBy(
            'equipment_transfer_history_table.created_at',
            'desc'
        )

        ->get()

        ->map(function ($row) {
            $row->to_destination_label = TransferDestinations::label($row);

            return $row;
        });


        // =====================================================
        // RETURN JSON RESPONSE
        // =====================================================

        return response()->json($history);

    }

    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT LIFECYCLE (layout asset details)
    |--------------------------------------------------------------------------
    */
    public function equipmentLifecycle($id)
    {
        $equipment = DB::table('equipment_table')
            ->where('equipment_id', $id)
            ->first();

        if (! $equipment) {
            return response()->json(['message' => 'Equipment not found.'], 404);
        }

        $transfers = collect();
        if (Schema::hasTable('equipment_transfer_history_table')) {
            $transfers = DB::table('equipment_transfer_history_table')
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
                ->where('equipment_transfer_history_table.equipment_id', $id)
                ->select(
                    'equipment_transfer_history_table.transfer_id',
                    'equipment_transfer_history_table.from_room_id',
                    'equipment_transfer_history_table.to_room_id',
                    'equipment_transfer_history_table.remarks',
                    'equipment_transfer_history_table.created_at',
                    'from_room.room_name as from_room_name',
                    'to_room.room_name as to_room_name'
                )
                ->orderByDesc('equipment_transfer_history_table.created_at')
                ->limit(10)
                ->get();
        }

        $maintenance = collect();
        if (Schema::hasTable('equipment_maintenance_history_table')) {
            $maintenance = DB::table('equipment_maintenance_history_table')
                ->where('equipment_maintenance_equipment_id', $id)
                ->orderByDesc('equipment_maintenance_created_at')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    return [
                        'findings' => $row->equipment_maintenance_findings,
                        'repair' => $row->equipment_maintenance_repair_action,
                        'status' => $row->equipment_maintenance_status,
                        'at' => $row->equipment_maintenance_completed_at
                            ?: $row->equipment_maintenance_created_at,
                    ];
                });
        }

        $disposal = null;
        if (Schema::hasTable('disposal_records_table')) {
            $disposal = DB::table('disposal_records_table')
                ->where('disposal_equipment_id', $id)
                ->orderByDesc('disposal_disposed_at')
                ->first();
        }

        $currentRoomId = $equipment->equipment_room_id;
        $deployedAt = $equipment->equipment_created_at;
        $lastMovedAt = null;

        $latestTransfer = $transfers->first();
        if ($latestTransfer) {
            $lastMovedAt = $latestTransfer->created_at;
        }

        $intoCurrent = $transfers->first(function ($row) use ($currentRoomId) {
            return (int) $row->to_room_id === (int) $currentRoomId;
        });

        if ($intoCurrent) {
            $deployedAt = $intoCurrent->created_at;
        } elseif ($equipment->equipment_acquired_date) {
            $deployedAt = $equipment->equipment_acquired_date;
        }

        $timeline = EquipmentTimeline::forEquipment((int) $id);

        return response()->json([
            'equipment_id' => (int) $equipment->equipment_id,
            'deployed_at' => $deployedAt,
            'last_moved_at' => $lastMovedAt,
            'disposed_at' => $disposal->disposal_disposed_at ?? null,
            'disposal_reason' => $disposal->disposal_reason ?? null,
            'disposal_area' => $disposal->disposal_area_location ?? null,
            'last_maintenance_at' => data_get($maintenance->first(), 'at'),
            'transfers' => $transfers->values(),
            'maintenance' => $maintenance->values(),
            'procurement' => $timeline['equipment'] ?? null,
            'events' => $timeline['events'] ?? [],
            'stages' => $timeline['stages'] ?? [],
            'stage_counts' => $timeline['stage_counts'] ?? [],
        ]);
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

            ->leftJoin('rooms_table as borrow_from_room', 'borrow_from_room.room_id', '=', 'borrowing_records_table.borrowing_from_room_id')
            ->leftJoin('rooms_table as borrow_return_room', 'borrow_return_room.room_id', '=', 'borrowing_records_table.borrowing_returned_to_room_id')
            ->leftJoin('users_table as borrow_returned_by', 'borrow_returned_by.user_id', '=', 'borrowing_records_table.borrowing_returned_by')
            ->select(
                'borrowing_records_table.*',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_brand_name',
                'equipment_table.equipment_model',
                'equipment_table.equipment_tracking_mode',
                'borrow_from_room.room_name as borrowed_from_room_name',
                'borrow_return_room.room_name as returned_to_room_name',
                'borrow_returned_by.user_full_name as returned_by_name'
            );

        $recordId = max(0, (int) $request->query('record', 0));
        $attentionFocus = null;

        if ($recordId > 0) {
            $record = (clone $query)->where('borrowing_records_table.borrowing_record_id', $recordId)->first();
            $query->where('borrowing_records_table.borrowing_record_id', $recordId);
            $request->query->remove('search');
            $request->query->remove('status');
            $attentionFocus = \App\Support\RecordFocus::make(
                $record ? trim(($record->equipment_name ?: 'Equipment').' · '.($record->borrowing_borrower_name ?: 'Borrower')) : 'Borrowing #'.$recordId,
                route('admin.borrowing.index'),
                'borrowing record'
            );
        } else {
            $attentionFocus = $this->reminderFocus($request, 'borrowing', ['status']);
        }

        if ($attentionFocus && $recordId === 0) {
            MaintenanceAttentionSummary::scopeOverdueBorrowings($query);
        }


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

        $onLoanByEquipment = DB::table('borrowing_records_table')
            ->select(
                'borrowing_equipment_id',
                DB::raw('COALESCE(SUM(borrowing_quantity), 0) as on_loan_qty')
            )
            ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
            ->groupBy('borrowing_equipment_id')
            ->pluck('on_loan_qty', 'borrowing_equipment_id');

        $equipment = DB::table('equipment_table')
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->where('equipment_is_borrowable', 1)
            ->where('equipment_inventory_status', 'Active')
            ->select(
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_quantity',
                'equipment_table.equipment_tracking_mode',
                'equipment_table.equipment_asset_tag',
                'rooms_table.room_name'
            )
            ->orderBy('equipment_name', 'asc')
            ->get()
            ->map(function ($item) use ($onLoanByEquipment) {
                $stock = max(1, (int) ($item->equipment_quantity ?? 1));
                $onLoan = (int) ($onLoanByEquipment[$item->equipment_id] ?? 0);
                $item->available_quantity = max(0, $stock - $onLoan);
                return $item;
            })
            ->filter(fn ($item) => $item->available_quantity > 0)
            ->values();

        $borrowableEquipmentJson = $equipment->map(fn ($item) => [
            'id' => (int) $item->equipment_id,
            'name' => (string) $item->equipment_name,
            'room' => (string) ($item->room_name ?? ''),
            'assetTag' => (string) ($item->equipment_asset_tag ?? ''),
            'tracking' => (string) ($item->equipment_tracking_mode ?? 'Individual'),
            'stock' => max(1, (int) ($item->equipment_quantity ?? 1)),
            'available' => (int) $item->available_quantity,
        ])->values();


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
            'admin.borrowing.index',

            compact(
                'borrowings',
                'equipment',
                'borrowableEquipmentJson',

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

                'borrowingMonthlyTrend',

                'attentionFocus'
            ) + [
                'returnStorageRooms' => DB::table('rooms_table')
                    ->where('room_type', RoomCategories::STORAGE_TYPE)
                    ->when(
                        Schema::hasColumn('rooms_table', 'room_is_archived'),
                        fn ($query) => $query->where(fn ($q) => $q->whereNull('room_is_archived')->orWhere('room_is_archived', false))
                    )
                    ->orderBy('room_name')
                    ->get(['room_id', 'room_name']),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE BORROWING
    |--------------------------------------------------------------------------
    */

    public function storeBorrowing(Request $request)
    {
        $legacySingle = $request->filled('borrowing_equipment_id')
            && ! $request->filled('items');

        $validated = $request->validate([
            'borrowing_borrower_name' => 'required|string|max:255',
            'borrowing_borrower_department' => 'nullable|string|max:255',
            'borrowing_authorized_by' => 'nullable|string|max:255',
            'borrowing_date' => 'required|date',
            'borrowing_expected_return_date' => 'required|date|after_or_equal:borrowing_date',
            'borrowing_purpose' => 'nullable|string',
            'borrowing_destination_location' => 'nullable|string|max:255',
            'borrowing_remarks' => 'nullable|string',
            'borrowing_equipment_condition' => 'nullable|string|max:255',
            'borrowing_equipment_id' => $legacySingle ? 'required|integer' : 'nullable|integer',
            'borrowing_quantity' => $legacySingle ? 'required|integer|min:1|max:5000' : 'nullable|integer|min:1|max:5000',
            'items' => $legacySingle ? 'nullable|array' : 'required|array|min:1|max:100',
            'items.*.equipment_id' => 'required_with:items|integer',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:5000',
            'items.*.condition' => 'nullable|string|max:255',
        ]);

        $lines = $legacySingle
            ? [[
                'equipment_id' => (int) $validated['borrowing_equipment_id'],
                'quantity' => (int) $validated['borrowing_quantity'],
                'condition' => $validated['borrowing_equipment_condition'] ?? null,
            ]]
            : array_values($validated['items']);

        // Merge duplicate equipment lines
        $merged = [];
        foreach ($lines as $line) {
            $id = (int) $line['equipment_id'];
            if (! isset($merged[$id])) {
                $merged[$id] = [
                    'equipment_id' => $id,
                    'quantity' => 0,
                    'condition' => $line['condition'] ?? null,
                ];
            }
            $merged[$id]['quantity'] += (int) $line['quantity'];
            if (! empty($line['condition'])) {
                $merged[$id]['condition'] = $line['condition'];
            }
        }
        $lines = array_values($merged);

        try {
            DB::transaction(function () use ($request, $validated, $lines) {
                $createdIds = [];
                $names = [];

                foreach ($lines as $line) {
                    $equipment = DB::table('equipment_table')
                        ->where('equipment_id', $line['equipment_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $equipment || ! (int) ($equipment->equipment_is_borrowable ?? 0)) {
                        throw new \RuntimeException('One or more selected equipment items are not borrowable.');
                    }

                    if (($equipment->equipment_inventory_status ?? '') === 'Disposed') {
                        throw new \RuntimeException(($equipment->equipment_name ?? 'Equipment').' is not available.');
                    }

                    $stock = max(1, (int) ($equipment->equipment_quantity ?? 1));
                    $onLoan = (int) DB::table('borrowing_records_table')
                        ->where('borrowing_equipment_id', $equipment->equipment_id)
                        ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
                        ->sum('borrowing_quantity');
                    $available = max(0, $stock - $onLoan);

                    if ((int) $line['quantity'] > $available) {
                        throw new \RuntimeException(
                            ($equipment->equipment_name ?? 'Equipment')
                            .' only has '.$available.' available to borrow.'
                        );
                    }

                    $borrowingId = DB::table('borrowing_records_table')->insertGetId([
                        'borrowing_equipment_id' => $equipment->equipment_id,
                        'borrowing_from_room_id' => $equipment->equipment_room_id,
                        'borrowing_borrower_name' => $validated['borrowing_borrower_name'],
                        'borrowing_borrower_department' => $validated['borrowing_borrower_department'] ?? null,
                        'borrowing_quantity' => (int) $line['quantity'],
                        'borrowing_equipment_condition' => $line['condition']
                            ?? ($validated['borrowing_equipment_condition'] ?? null),
                        'borrowing_date' => $validated['borrowing_date'],
                        'borrowing_expected_return_date' => $validated['borrowing_expected_return_date'],
                        'borrowing_purpose' => $validated['borrowing_purpose'] ?? null,
                        'borrowing_destination_location' => $validated['borrowing_destination_location'] ?? null,
                        'borrowing_authorized_by' => $validated['borrowing_authorized_by'] ?? null,
                        'borrowing_remarks' => $validated['borrowing_remarks'] ?? null,
                        'borrowing_status' => 'Borrowed',
                        'borrowing_created_at' => now(),
                    ]);

                    $remaining = $available - (int) $line['quantity'];
                    $isBulk = ($equipment->equipment_tracking_mode ?? 'Individual') === 'Bulk';

                    // Mark fully out units as Borrowed; keep Active when bulk stock remains.
                    if (! $isBulk || $remaining <= 0) {
                        DB::table('equipment_table')
                            ->where('equipment_id', $equipment->equipment_id)
                            ->update(['equipment_inventory_status' => 'Borrowed']);
                    }

                    $eventKey = 'equipment_borrowed_'.$borrowingId;

                    DB::table('notifications_table')->insertOrIgnore([
                        'notification_user_id' => null,
                        'notification_target_role' => WorkflowNotifier::ROLE_ADMIN,
                        'notification_title' => 'Equipment Borrowed',
                        'notification_message' => ($equipment->equipment_name ?? 'Equipment')
                            .' (x'.((int) $line['quantity']).') was borrowed by '
                            .$validated['borrowing_borrower_name'].'.',
                        'notification_type' => 'equipment_borrowed',
                        'notification_category' => 'Equipment',
                        'notification_reference_type' => 'borrowing_record',
                        'notification_reference_id' => $borrowingId,
                        'notification_url' => '/admin/borrowing',
                        'notification_event_key' => $eventKey,
                        'notification_created_at' => now(),
                    ]);

                    $createdIds[] = $borrowingId;
                    $names[] = ($equipment->equipment_name ?? 'equipment').' × '.$line['quantity'];
                }

                $count = count($createdIds);
                $lastId = (int) end($createdIds);

                DB::table('audit_logs_table')->insert([
                    'audit_log_user_id' => Auth::id(),
                    'audit_log_action' => 'Borrowed equipment',
                    'audit_log_module' => 'Borrowing',
                    'audit_log_table_name' => 'borrowing_records_table',
                    'audit_log_reference_id' => $lastId,
                    'audit_log_description' => $count > 1
                        ? ('Borrowed '.$count.' item lines for '.$validated['borrowing_borrower_name'].': '.implode(', ', $names).'.')
                        : ('Borrowed '.$names[0].' to '.$validated['borrowing_borrower_name'].'.'),
                    'audit_log_ip_address' => $request->ip(),
                    'audit_log_created_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $lineCount = count($lines);

        return back()->with(
            'success',
            $lineCount > 1
                ? "Borrowed {$lineCount} equipment lines successfully."
                : 'Equipment borrowed successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RETURN EQUIPMENT
    |--------------------------------------------------------------------------
    */

    public function returnEquipment(Request $request)
    {
        $request->validate([
            'borrowing_record_id' => 'required|integer',
            'return_condition' => 'required|in:Good,Damaged,For Repair',
            'remarks' => 'nullable|string|max:1000',
            'return_room_id' => 'nullable|integer',
        ]);

        // =====================================================
        // FIND BORROWING RECORD
        // =====================================================

        $record = DB::table('borrowing_records_table')

            ->where(
                'borrowing_record_id',
                $request->borrowing_record_id
            )

            ->first();


        // =====================================================
        // BORROWING RECORD NOT FOUND
        // =====================================================

        if (!$record) {

            return back()->with(
                'error',
                'Borrowing record not found.'
            );
        }


        // =====================================================
        // PREVENT RETURNING SAME RECORD TWICE
        // =====================================================

        if ($record->borrowing_status === 'Returned') {

            return back()->with(
                'error',
                'This equipment has already been returned.'
            );
        }


        // =====================================================
        // GET EQUIPMENT INFORMATION
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $record->borrowing_equipment_id
            )

            ->first();


        $storageRoom = $this->borrowReturnStorageRoom((int) $request->input('return_room_id', 0));
        $returnMessage = 'Equipment returned successfully.';

        // =====================================================
        // START TRANSACTION
        // =====================================================

        DB::transaction(function () use (
            $request,
            $record,
            $equipment,
            $storageRoom,
            &$returnMessage
        ) {

            // =================================================
            // UPDATE EQUIPMENT STATUS
            // =================================================

            $remainingOnLoan = (int) DB::table('borrowing_records_table')
                ->where('borrowing_equipment_id', $record->borrowing_equipment_id)
                ->where('borrowing_record_id', '!=', $record->borrowing_record_id)
                ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
                ->sum('borrowing_quantity');

            $stock = max(1, (int) ($equipment->equipment_quantity ?? 1));

            DB::table('equipment_table')
                ->where('equipment_id', $record->borrowing_equipment_id)
                ->update([
                    'equipment_inventory_status' => $remainingOnLoan >= $stock ? 'Borrowed' : 'Active',
                    'equipment_condition_status' => $request->return_condition,
                ]);

            EquipmentConditionHistory::record(
                (int) $record->borrowing_equipment_id,
                $equipment->equipment_condition_status ?? null,
                $request->return_condition,
                'borrow_return',
                'Condition on return from borrow'
            );

            // =================================================
            // SEND THE RETURNED ITEM TO STORAGE (INVENTORY)
            //
            // A bulk row that still has units in a room stays
            // there: its returned units rejoin the same row.
            // =================================================

            $currentRoom = $equipment
                ? DB::table('rooms_table')->where('room_id', $equipment->equipment_room_id)->first()
                : null;
            $isSingle = $equipment && (($equipment->equipment_tracking_mode ?? 'Individual') !== 'Bulk' || $stock <= 1);
            $wholeRowWasOut = $equipment && ($equipment->equipment_inventory_status ?? '') === 'Borrowed';
            $alreadyInStorage = $currentRoom && RoomCategories::isStorageType($currentRoom->room_type ?? null);

            $returnedToRoomId = $equipment->equipment_room_id ?? null;

            if ($storageRoom && ! $alreadyInStorage && $remainingOnLoan === 0 && ($isSingle || $wholeRowWasOut)) {
                $assignment = PropertyAssignments::current((int) $record->borrowing_equipment_id);
                if ($assignment) {
                    PropertyAssignments::returnAssignment(
                        (int) $assignment->assignment_id,
                        $request->return_condition === 'Good' ? 'Good' : 'Damaged',
                        'Returned to storage after borrowing.'
                    );
                }

                DB::table('equipment_table')
                    ->where('equipment_id', $record->borrowing_equipment_id)
                    ->update([
                        'workstation_slot_id' => null,
                        'workstation_id' => null,
                        'workstation_template_slot_id' => null,
                    ]);

                $this->recordInventoryTransfer(
                    $request,
                    DB::table('equipment_table')->where('equipment_id', $record->borrowing_equipment_id)->first(),
                    $currentRoom,
                    $storageRoom,
                    ['type' => null, 'department_id' => null, 'custodian_id' => null, 'label' => null, 'error' => null],
                    false,
                    'Returned from borrowing by '.$record->borrowing_borrower_name.'.',
                    Auth::id(),
                    Auth::id()
                );

                $returnedToRoomId = $storageRoom->room_id;
                $returnMessage = 'Equipment returned and moved to '.$storageRoom->room_name.' (Inventory).'
                    .($assignment ? ' Its assignment to '.($assignment->custodian_full_name ?? 'a person').' was closed.' : '');
            } elseif ($alreadyInStorage) {
                $returnMessage = 'Equipment returned to '.$currentRoom->room_name.' (Inventory).';
            } elseif ($currentRoom && ! $isSingle) {
                $returnMessage = 'Equipment returned. The units rejoined '.($equipment->equipment_name ?? 'the item')
                    .' in '.$currentRoom->room_name.' because other units of it are still there.';
            }

            // =================================================
            // UPDATE BORROWING RECORD
            // =================================================

            DB::table('borrowing_records_table')
                ->where('borrowing_record_id', $record->borrowing_record_id)
                ->update([
                    'borrowing_status' => 'Returned',
                    'borrowing_actual_return_date' => now(),
                    'borrowing_return_condition' => $request->return_condition,
                    'borrowing_return_remarks' => $request->input('remarks'),
                    'borrowing_returned_by' => Auth::id(),
                    'borrowing_returned_to_room_id' => $returnedToRoomId,
                ]);

            // =================================================
            // CREATE NOTIFICATION
            // =================================================

            $eventKey =
                'equipment_returned_'
                . $record->borrowing_record_id;


            DB::table('notifications_table')

                ->insertOrIgnore([

                    'notification_user_id'
                        => null,

                    'notification_target_role'
                        => WorkflowNotifier::ROLE_ADMIN,

                    'notification_title'
                        => 'Equipment Returned',

                    'notification_message'
                        => ($equipment->equipment_name ?? 'Equipment')
                        . ' was returned by '
                        . $record->borrowing_borrower_name
                        . '.',

                    'notification_type'
                        => 'equipment_returned',

                    'notification_category'
                        => 'Equipment',

                    'notification_reference_type'
                        => 'borrowing_record',

                    'notification_reference_id'
                        => $record->borrowing_record_id,

                    'notification_url'
                        => '/admin/borrowing',

                    'notification_event_key'
                        => $eventKey,

                    'notification_created_at'
                        => now(),

                ]);


            // =================================================
            // RECENT ACTIVITY
            // =================================================

            DB::table('audit_logs_table')->insert([

                'audit_log_user_id'
                    => Auth::id(),

                'audit_log_action'
                    => 'Returned equipment',

                'audit_log_module'
                    => 'Borrowing',

                'audit_log_table_name'
                    => 'borrowing_records_table',

                'audit_log_reference_id'
                    => $record->borrowing_record_id,

                'audit_log_description'
                    => 'Returned '
                    . ($equipment->equipment_name ?? 'equipment')
                    . ' from '
                    . $record->borrowing_borrower_name
                    . '.',

                'audit_log_ip_address'
                    => $request->ip(),

                'audit_log_created_at'
                    => now(),

            ]);

        });


        // =====================================================
        // RETURN SUCCESS
        // =====================================================

        return back()->with(
            'success',
            $returnMessage
        );
    }

    /**
     * Storage room picked on the return form, or the first active storage room.
     */
    private function borrowReturnStorageRoom(int $roomId): ?object
    {
        $rooms = DB::table('rooms_table')
            ->where('room_type', RoomCategories::STORAGE_TYPE)
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where(fn ($q) => $q->whereNull('room_is_archived')->orWhere('room_is_archived', false))
            )
            ->orderBy('room_name')
            ->get();

        return ($roomId > 0 ? $rooms->firstWhere('room_id', $roomId) : null) ?? $rooms->first();
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
            'admin.rooms.room-details',
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
        EquipmentSchedules::healStuckCompletedCycles();

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

            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )

            ->select(
                'maintenance_schedules_table.*',
                'equipment_table.equipment_name',
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_serial_number',
                'equipment_table.equipment_room_id',
                'equipment_table.equipment_brand_name',
                'equipment_table.equipment_model',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'equipment_categories_table.equipment_category_name',
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

        $recordId = max(0, (int) $request->query('record', 0));

        if ($recordId > 0) {
            $record = (clone $schedulesQuery)->where('maintenance_schedules_table.maintenance_schedule_id', $recordId)->first();
            $tableSchedulesQuery->where('maintenance_schedules_table.maintenance_schedule_id', $recordId);
            foreach (['search', 'frequency', 'room', 'status'] as $filter) {
                $request->query->remove($filter);
            }
            $attentionFocus = \App\Support\RecordFocus::make(
                $record ? ($record->maintenance_schedule_title ?: $record->equipment_name ?: 'Schedule #'.$recordId) : 'Schedule #'.$recordId,
                \App\Support\SchedulePortal::schedulesUrl(),
                'schedule'
            );
        } else {
            $attentionFocus = $this->reminderFocus($request, 'schedules', ['status']);

            if ($attentionFocus) {
                MaintenanceAttentionSummary::scopeOverdueSchedules($tableSchedulesQuery);
            }
        }


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
        // ROOM FILTER
        // =====================================================

        if ($request->filled('room')) {

            $tableSchedulesQuery->where(
                'equipment_table.equipment_room_id',
                $request->room
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

            ->paginate(12)

            ->withQueryString();


        // =====================================================
        // GET ACTIVE EQUIPMENT FOR CREATE SCHEDULE MODAL
        // ONLY EQUIPMENT WITH A GENERATED QR CODE
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

            ->whereNotNull('equipment_table.equipment_qr_code')

            ->where('equipment_table.equipment_qr_code', '!=', '')

            ->select(
                'equipment_table.*',
                'rooms_table.room_name'
            )

            ->orderBy(
                'equipment_name'
            )

            ->get();

        $scheduleEquipmentJson = $equipment->map(fn ($item) => [
            'id' => (int) $item->equipment_id,
            'name' => (string) $item->equipment_name,
            'room' => (string) ($item->room_name ?? ''),
            'qr' => (string) ($item->equipment_qr_code ?? ''),
            'assetTag' => (string) ($item->equipment_asset_tag ?? ''),
            'serialNumber' => (string) ($item->equipment_serial_number ?? ''),
        ])->values();


        $rooms = DB::table('rooms_table')
            ->orderBy('room_name')
            ->get(['room_id', 'room_name']);


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
                'scheduleEquipmentJson',
                'rooms',

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

                'scheduleMonthlyTrend',

                'attentionFocus'
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
        $legacySingle = $request->filled('equipment_id') && ! $request->filled('equipment_ids');

        $validated = $request->validate([
            'equipment_id' => $legacySingle
                ? ['required', 'integer', 'exists:equipment_table,equipment_id']
                : ['nullable', 'integer', 'exists:equipment_table,equipment_id'],
            'equipment_ids' => $legacySingle ? ['nullable', 'array'] : ['required', 'array', 'min:1', 'max:100'],
            'equipment_ids.*' => ['required', 'integer', 'exists:equipment_table,equipment_id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frequency' => ['required', 'string', 'max:100'],
            'next_date' => ['required', 'date'],
        ]);

        $equipmentIds = $legacySingle
            ? [(int) $validated['equipment_id']]
            : array_values(array_unique(array_map('intval', $validated['equipment_ids'])));

        $equipmentRows = DB::table('equipment_table')
            ->whereIn('equipment_id', $equipmentIds)
            ->get()
            ->keyBy('equipment_id');

        foreach ($equipmentIds as $equipmentId) {
            $equipment = $equipmentRows->get($equipmentId);
            if (! $equipment || ! filled($equipment->equipment_qr_code)) {
                return back()
                    ->withErrors([
                        'equipment_ids' => 'Only equipment with a generated QR code can be scheduled for maintenance.',
                    ])
                    ->withInput();
            }
        }

        $createdIds = [];
        $names = [];

        DB::transaction(function () use (
            $request,
            $validated,
            $equipmentIds,
            $equipmentRows,
            &$createdIds,
            &$names
        ) {
            foreach ($equipmentIds as $equipmentId) {
                $equipment = $equipmentRows->get($equipmentId);

                $scheduleId = DB::table('maintenance_schedules_table')->insertGetId([
                    'maintenance_schedule_equipment_id' => $equipmentId,
                    'maintenance_schedule_title' => $validated['title'],
                    'maintenance_schedule_description' => $validated['description'] ?? null,
                    'maintenance_schedule_frequency' => $validated['frequency'],
                    'maintenance_schedule_next_date' => $validated['next_date'],
                    'maintenance_schedule_status' => 'Active',
                ]);

                $createdIds[] = $scheduleId;
                $names[] = $equipment->equipment_name ?? 'equipment';
            }

            $count = count($createdIds);
            $lastId = (int) end($createdIds);

            DB::table('audit_logs_table')->insert([
                'audit_log_user_id' => Auth::id(),
                'audit_log_action' => 'Created maintenance schedule',
                'audit_log_module' => 'Schedules',
                'audit_log_table_name' => 'maintenance_schedules_table',
                'audit_log_reference_id' => $lastId,
                'audit_log_description' => $count > 1
                    ? ('Created "'.$validated['title'].'" schedule for '.$count.' equipment: '.implode(', ', $names).'.')
                    : ('Created maintenance schedule "'.$validated['title'].'" for '.$names[0].'.'),
                'audit_log_ip_address' => $request->ip(),
                'audit_log_created_at' => now(),
            ]);
        });

        $count = count($createdIds);

        return redirect()
            ->back()
            ->with(
                'success',
                $count > 1
                    ? "Created {$count} maintenance schedules."
                    : 'Maintenance schedule created.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function completeSchedule(Request $request)
    {
        // =====================================================
        // VALIDATE REQUEST
        // =====================================================

        $request->validate([

            'schedule_id' => [
                'required',
                'integer',
                'exists:maintenance_schedules_table,maintenance_schedule_id'
            ],

            'findings' => [
                'required',
                'string'
            ],

            'repair_action' => [
                'required',
                'string'
            ],

            'maintenance_status' => [
                'required',
                'string',
                'max:100'
            ],

            'proof_image' => [
                'nullable',
                'image',
                'max:4096'
            ],

            'parts_used' => ['nullable', 'string', 'max:500'],
            'repair_cost' => ['nullable', 'numeric', 'min:0'],
            'downtime_hours' => ['nullable', 'numeric', 'min:0'],

        ]);


        // =====================================================
        // GET MAINTENANCE SCHEDULE
        // =====================================================

        $schedule = DB::table('maintenance_schedules_table')

            ->where(
                'maintenance_schedule_id',
                $request->schedule_id
            )

            ->first();

        if (! $schedule) {
            return redirect()
                ->back()
                ->with('error', 'Maintenance schedule not found.');
        }

        // =====================================================
        // ONLY WITHIN 7 DAYS OF NEXT DATE (OR OVERDUE)
        // =====================================================

        $blockedReason = EquipmentSchedules::cannotMarkCompleteReason($schedule);
        if ($blockedReason !== null) {
            return redirect()
                ->back()
                ->with('error', $blockedReason);
        }


        // =====================================================
        // GET EQUIPMENT INFORMATION
        //
        // USED FOR RECENT ACTIVITY DESCRIPTION
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $schedule->maintenance_schedule_equipment_id
            )

            ->first();


        // =====================================================
        // HANDLE PROOF IMAGE
        // =====================================================

        $proofImage = null;

        if ($request->hasFile('proof_image')) {

            $proofImage = $request
                ->file('proof_image')
                ->store(
                    'maintenance-proofs',
                    'public'
                );
        }


        // =====================================================
        // START TRANSACTION
        // =====================================================

        DB::transaction(function () use (
            $request,
            $schedule,
            $equipment,
            $proofImage
        ) {

            // =================================================
            // CREATE MAINTENANCE HISTORY RECORD
            // =================================================

            $maintenanceHistoryId = DB::table(
                'equipment_maintenance_history_table'
            )

            ->insertGetId(array_filter([

                'equipment_maintenance_equipment_id'
                    => $schedule->maintenance_schedule_equipment_id,

                'equipment_maintenance_personnel_id'
                    => Auth::check()
                        ? Auth::id()
                        : null,

                'equipment_maintenance_findings'
                    => $request->findings,

                'equipment_maintenance_repair_action'
                    => $request->repair_action,

                'equipment_maintenance_status'
                    => $request->maintenance_status,

                'equipment_maintenance_parts_used'
                    => Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_parts_used')
                        ? $request->input('parts_used')
                        : null,

                'equipment_maintenance_repair_cost'
                    => Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_repair_cost')
                        ? $request->input('repair_cost')
                        : null,

                'equipment_maintenance_downtime_hours'
                    => Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_downtime_hours')
                        ? $request->input('downtime_hours')
                        : null,

                'equipment_maintenance_completed_at'
                    => now(),

                'equipment_maintenance_created_at'
                    => now(),

                'equipment_maintenance_proof_image'
                    => $proofImage,

            ], fn ($value) => $value !== null));


            // =================================================
            // COMPLETE THIS CYCLE → ADVANCE NEXT DATE (RECURRING)
            // =================================================

            DB::table('maintenance_schedules_table')
                ->where(
                    'maintenance_schedule_id',
                    $schedule->maintenance_schedule_id
                )
                ->update(EquipmentSchedules::completionUpdatePayload($schedule));


            // =================================================
            // RECENT ACTIVITY
            //
            // THIS WILL APPEAR ON THE DASHBOARD
            // =================================================

            DB::table('audit_logs_table')->insert([

                'audit_log_user_id'
                    => Auth::id(),

                'audit_log_action'
                    => 'Completed maintenance',

                'audit_log_module'
                    => 'Maintenance',

                'audit_log_table_name'
                    => 'equipment_maintenance_history_table',

                'audit_log_reference_id'
                    => $maintenanceHistoryId,

                'audit_log_description'
                    => 'Completed maintenance "'
                    . ($schedule->maintenance_schedule_title ?? 'Maintenance')
                    . '" for '
                    . ($equipment->equipment_name ?? 'equipment')
                    . '.',

                'audit_log_ip_address'
                    => $request->ip(),

                'audit_log_created_at'
                    => now(),

            ]);

        });


        // =====================================================
        // SUCCESS
        // =====================================================

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
        ReportGrouping::ensureDisposedAppearInDisposalModule();

        // =====================================================
        // BUILD DISPOSAL RECORDS QUERY
        // SEARCH AND FILTERS APPLY BEFORE PAGINATION
        // =====================================================

        $showArchive = $request->is('admin/*')
            && ($request->query('archive') == 1 || $request->query('view') === 'archive');
        $hasArchiveColumn = Schema::hasColumn('disposal_records_table', 'disposal_is_archived');

        // School Administrator sees finalized disposals only, archived or not
        $disposedOnly = $request->is('school-admin/*');

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

            ->leftJoin('rooms_table', 'equipment_table.equipment_room_id', '=', 'rooms_table.room_id')
            ->leftJoin('departments_table', 'equipment_table.equipment_department_id', '=', 'departments_table.department_id')
            ->leftJoin('users_table as disposal_users', 'disposal_records_table.disposal_approved_by', '=', 'disposal_users.user_id')

            ->select(
                'disposal_records_table.*',
                'equipment_table.equipment_name',
                'equipment_table.equipment_condition_status',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_serial_number',
                'equipment_table.equipment_brand_name',
                'equipment_table.equipment_model',
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_image',
                'equipment_table.equipment_quantity',
                'equipment_table.equipment_purchase_date',
                'equipment_table.equipment_purchase_cost',
                'equipment_table.equipment_acquired_date',
                'equipment_table.equipment_warranty_expiration',
                'equipment_table.equipment_useful_life_years',
                'equipment_table.equipment_acquisition_source',
                'equipment_table.equipment_supplier_name',
                'equipment_table.equipment_reference_number',
                'equipment_table.equipment_current_location',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name',
                'departments_table.department_name',
                'disposal_users.user_full_name as disposed_by_name'
            )

            ->when(
                $hasArchiveColumn && ! $disposedOnly,
                function ($q) use ($showArchive) {
                    $q->where(
                        'disposal_records_table.disposal_is_archived',
                        $showArchive ? 1 : 0
                    );
                }
            )

            ->when(
                $disposedOnly,
                fn ($q) => $q->where('equipment_table.equipment_condition_status', 'Disposed')
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
                )

                ->orWhere('equipment_table.equipment_asset_tag', 'LIKE', '%' . $request->search . '%')
                ->orWhere('equipment_table.equipment_serial_number', 'LIKE', '%' . $request->search . '%')
                ->orWhere('equipment_table.equipment_brand_name', 'LIKE', '%' . $request->search . '%')
                ->orWhere('equipment_table.equipment_model', 'LIKE', '%' . $request->search . '%');

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

            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->where(
                'equipment_inventory_status',
                '!=',
                'Disposed'
            )

            ->select(
                'equipment_table.*',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name as equipment_room_label'
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
                ->when(
                    $hasArchiveColumn,
                    fn ($q) => $q->where('disposal_is_archived', 0)
                )
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

                ->when(
                    $hasArchiveColumn,
                    fn ($q) => $q->where('disposal_records_table.disposal_is_archived', 0)
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


        $disposedOnlyStats = null;

        if ($disposedOnly) {
            $finalized = DB::table('disposal_records_table')
                ->join('equipment_table', 'disposal_records_table.disposal_equipment_id', '=', 'equipment_table.equipment_id')
                ->where('equipment_table.equipment_condition_status', 'Disposed');

            $disposedOnlyStats = [
                'total' => (clone $finalized)->count(),
                'thisMonth' => (clone $finalized)
                    ->whereBetween('disposal_records_table.disposal_disposed_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'writtenOff' => (float) (clone $finalized)->sum('equipment_table.equipment_purchase_cost'),
            ];
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
                'showArchive',
                'disposedOnly',
                'disposedOnlyStats',

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
        $validated = $request->validate([
            'equipment_id' => 'nullable|integer|required_without:equipment_ids',
            'equipment_ids' => 'nullable|array|min:1|max:200|required_without:equipment_id',
            'equipment_ids.*' => 'integer',
            'reason' => 'required|string|max:1000',
            'location' => 'nullable|string|max:255',
            'method' => 'nullable|string|max:80',
            'residual_value' => 'nullable|numeric|min:0',
        ]);

        $ids = collect($validated['equipment_ids'] ?? [])
            ->push($validated['equipment_id'] ?? null)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $details = [
            'reason' => $validated['reason'],
            'method' => $validated['method'] ?? null,
            'location' => $validated['location'] ?? null,
            'residual_value' => $validated['residual_value'] ?? null,
        ];

        if (DisposalRequests::requiresApproval()) {
            return $this->submitDisposalRequest($ids, $details);
        }

        $result = $this->runDisposals($ids, $details);

        return back()->with($result['disposed'] > 0 ? 'success' : 'error', $result['message']);
    }

    /**
     * Why this equipment cannot be disposed right now, or null when it can.
     */
    private function disposalBlocker(?object $equipment, $openLoans): ?string
    {
        if (! $equipment) {
            return 'Equipment not found.';
        }

        if (($equipment->equipment_inventory_status ?? '') === 'Disposed') {
            return ($equipment->equipment_name ?? 'This equipment').' is already disposed.';
        }

        if ($openLoans->has((int) $equipment->equipment_id)) {
            return ($equipment->equipment_name ?? 'This equipment').' is still on loan. Record the return before disposing it.';
        }

        return null;
    }

    /**
     * Move each piece of equipment into Disposal. Items that cannot be disposed are skipped.
     *
     * @return array{disposed: int, message: string}
     */
    private function runDisposals(array $equipmentIds, array $details, ?int $requestedBy = null): array
    {
        $rows = DB::table('equipment_table')->whereIn('equipment_id', $equipmentIds)->get()->keyBy('equipment_id');
        $openLoans = TransferLocks::openLoans($equipmentIds);

        $disposed = [];
        $skipped = [];

        foreach ($equipmentIds as $equipmentId) {
            $equipment = $rows->get($equipmentId);
            $blocker = $this->disposalBlocker($equipment, $openLoans);

            if ($blocker) {
                $skipped[] = $blocker;
                continue;
            }

            $this->disposeEquipmentRecord($equipment, $details, $requestedBy);
            $disposed[] = $equipment->equipment_name ?? 'Equipment';
        }

        if ($disposed === []) {
            return [
                'disposed' => 0,
                'message' => count($skipped) === 1 ? $skipped[0] : 'None of the selected equipment could be disposed. '.($skipped[0] ?? ''),
            ];
        }

        $message = count($disposed) === 1
            ? 'Equipment moved to Disposal. It can still be restored until you finalize with Dispose.'
            : count($disposed).' items moved to Disposal. They can still be restored until you finalize with Dispose.';

        if ($skipped !== []) {
            $message .= ' '.count($skipped).' skipped: '.implode(' ', array_slice($skipped, 0, 3))
                .(count($skipped) > 3 ? ' …' : '');
        }

        return ['disposed' => count($disposed), 'message' => $message];
    }

    private function submitDisposalRequest(array $equipmentIds, array $details)
    {
        $pending = DisposalRequests::pendingEquipmentIds();
        $pendingTransfers = TransferRequests::pendingEquipmentIds();
        $rows = DB::table('equipment_table')->whereIn('equipment_id', $equipmentIds)->get()->keyBy('equipment_id');
        $openLoans = TransferLocks::openLoans($equipmentIds);

        $free = [];
        $skipped = [];
        foreach ($equipmentIds as $equipmentId) {
            $equipment = $rows->get($equipmentId);
            if (isset($pending[$equipmentId])) {
                $skipped[] = ($equipment->equipment_name ?? 'This equipment').' already has a disposal waiting for approval.';
                continue;
            }
            if (isset($pendingTransfers[$equipmentId])) {
                $skipped[] = ($equipment->equipment_name ?? 'This equipment').' has a transfer waiting for approval.';
                continue;
            }
            $blocker = $this->disposalBlocker($equipment, $openLoans);
            if ($blocker) {
                $skipped[] = $blocker;
                continue;
            }
            $free[] = $equipmentId;
        }

        if ($free === []) {
            return back()->with('error', count($skipped) === 1 ? $skipped[0] : 'None of the selected equipment can be sent for disposal. '.($skipped[0] ?? ''));
        }

        $requestId = DisposalRequests::create($free, $details);

        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_ADMIN,
            'Equipment disposal needs approval',
            (Auth::user()->user_full_name ?? 'Maintenance').' asked to dispose '
                .DisposalRequests::itemsLabel($free).'. Reason: '.trim((string) $details['reason']),
            'equipment_disposal_request',
            'equipment_disposal_request',
            $requestId,
            '/admin/disposal/requests'
        );

        $message = 'Disposal request sent to the Administrator for approval. The equipment stays where it is until it is approved.';
        if ($skipped !== []) {
            $message .= ' '.count($skipped).' item'.(count($skipped) === 1 ? ' was' : 's were').' left out: '
                .implode(' ', array_slice($skipped, 0, 3)).(count($skipped) > 3 ? ' …' : '');
        }

        return back()->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | DISPOSAL REQUESTS (non-administrators need the Administrator's approval)
    |--------------------------------------------------------------------------
    */

    public function disposalRequests(Request $request)
    {
        $requested = (string) $request->query('status', DisposalRequests::PENDING);
        $status = in_array($requested, DisposalRequests::STATUSES, true) ? $requested : null;

        return view('maintenance-personnel.disposal.requests', [
            'requests' => DisposalRequests::list($status),
            'counts' => DisposalRequests::statusCounts(),
            'status' => $status,
            'isAdminPortal' => $request->is('admin/*'),
        ]);
    }

    public function approveDisposalRequest(Request $request, $id)
    {
        $outcome = DB::transaction(function () use ($request, $id) {
            $row = DisposalRequests::find((int) $id, true);
            if (! $row || $row->disposal_request_status !== DisposalRequests::PENDING) {
                return ['ok' => false, 'handled' => true, 'message' => 'This request was already handled.'];
            }

            $requestedBy = $row->disposal_request_requested_by ? (int) $row->disposal_request_requested_by : null;
            $result = $this->runDisposals(DisposalRequests::equipmentIds($row), DisposalRequests::details($row), $requestedBy);

            if ($result['disposed'] === 0) {
                return ['ok' => false, 'message' => $result['message']];
            }

            DisposalRequests::close((int) $row->disposal_request_id, DisposalRequests::APPROVED, $request->input('note'), $result['message']);
            $this->notifyDisposalRequester($row, 'Disposal approved', 'Your disposal request was approved. '.$result['message'], 'equipment_disposal_approved');

            return ['ok' => true, 'message' => 'Disposal approved. '.$result['message']];
        });

        if ($outcome['ok'] || ! empty($outcome['handled'])) {
            return back()->with($outcome['ok'] ? 'success' : 'error', $outcome['message']);
        }

        return back()->with('error', 'Could not approve: '.$outcome['message'].' Reject the request if it is no longer valid.');
    }

    public function rejectDisposalRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
        ], [
            'note.required' => 'Give a reason so the requester knows why the disposal was rejected.',
        ]);

        $row = DisposalRequests::find((int) $id);
        if (! $row || $row->disposal_request_status !== DisposalRequests::PENDING) {
            return back()->with('error', 'This request was already handled.');
        }

        DisposalRequests::close((int) $row->disposal_request_id, DisposalRequests::REJECTED, $validated['note']);
        $this->notifyDisposalRequester(
            $row,
            'Disposal rejected',
            'Your request to dispose '.DisposalRequests::itemsLabel(DisposalRequests::equipmentIds($row))
                .' was rejected. Reason: '.trim($validated['note']),
            'equipment_disposal_rejected'
        );

        return back()->with('success', 'Disposal request rejected. The equipment was not disposed.');
    }

    public function cancelDisposalRequest($id)
    {
        $row = DisposalRequests::find((int) $id);
        if (! $row || $row->disposal_request_status !== DisposalRequests::PENDING) {
            return back()->with('error', 'This request was already handled.');
        }
        if ((int) $row->disposal_request_requested_by !== (int) Auth::id()) {
            return back()->with('error', 'Only the person who sent the request can cancel it.');
        }

        DisposalRequests::close((int) $row->disposal_request_id, DisposalRequests::CANCELLED);

        return back()->with('success', 'Disposal request cancelled.');
    }

    private function notifyDisposalRequester(object $row, string $title, string $message, string $type): void
    {
        if (empty($row->disposal_request_requested_by)) {
            return;
        }

        WorkflowNotifier::toUser(
            (int) $row->disposal_request_requested_by,
            WorkflowNotifier::ROLE_MAINTENANCE,
            $title,
            $message,
            $type,
            'equipment_disposal_request',
            (int) $row->disposal_request_id,
            '/maintenance/disposal/requests?status=all'
        );
    }

    private function disposeEquipmentRecord(object $equipment, array $details, ?int $requestedBy = null): void
    {
        $method = filled($details['method'] ?? null) ? $details['method'] : null;
        $residual = ($details['residual_value'] ?? null) !== null && $details['residual_value'] !== ''
            ? $details['residual_value']
            : null;

        DB::transaction(function () use (
            $equipment,
            $details,
            $method,
            $residual,
            $requestedBy
        ) {

            $existingDisposal = DB::table('disposal_records_table')
                ->where('disposal_equipment_id', $equipment->equipment_id)
                ->when(
                    Schema::hasColumn('disposal_records_table', 'disposal_is_archived'),
                    fn ($q) => $q->where('disposal_is_archived', 0)
                )
                ->orderByDesc('disposal_record_id')
                ->first();

            if ($existingDisposal) {
                $disposalId = (int) $existingDisposal->disposal_record_id;

                $update = [
                    'disposal_reason' => $details['reason'],
                    'disposal_area_location' => $details['location'] ?? null,
                    'disposal_approved_by' => Auth::id(),
                    'disposal_disposed_at' => now(),
                ];
                if (Schema::hasColumn('disposal_records_table', 'disposal_method')) {
                    $update['disposal_method'] = $method;
                }
                if (Schema::hasColumn('disposal_records_table', 'disposal_residual_value')) {
                    $update['disposal_residual_value'] = $residual;
                }

                DB::table('disposal_records_table')
                    ->where('disposal_record_id', $disposalId)
                    ->update($update);
            } else {
                $insert = [
                    'disposal_equipment_id' => $equipment->equipment_id,
                    'disposal_reason' => $details['reason'],
                    'disposal_area_location' => $details['location'] ?? null,
                    'disposal_approved_by' => Auth::id(),
                    'disposal_disposed_at' => now(),
                ];
                if (Schema::hasColumn('disposal_records_table', 'disposal_method')) {
                    $insert['disposal_method'] = $method;
                }
                if (Schema::hasColumn('disposal_records_table', 'disposal_residual_value')) {
                    $insert['disposal_residual_value'] = $residual;
                }

                $disposalId = DB::table('disposal_records_table')->insertGetId($insert);
            }


            // =================================================
            // UPDATE EQUIPMENT STATUS
            // =================================================

            DB::table('equipment_table')

                ->where(
                    'equipment_id',
                    $equipment->equipment_id
                )

                ->update([

                    'equipment_inventory_status'
                        => 'Disposed',

                    // Keep Damaged so it remains restorable until finalized via Dispose.
                    'equipment_condition_status'
                        => 'Damaged',

                ]);


            // =================================================
            // RECENT ACTIVITY
            // =================================================

            $this->logActivity(

                'Disposed equipment',

                'Equipment',

                'disposal_records_table',

                $disposalId,

                'Disposed '
                . ($equipment->equipment_name ?? 'equipment')
                . '. Reason: '
                . ($details['reason'] ?: 'No reason provided')
                . '.'
                . ($requestedBy ? ' Requested by '.WorkflowNotifier::recipientName($requestedBy, 'Maintenance').'.' : '')

            );

        });
    }

    public function restoreDisposal(Request $request)
    {
        $record = DB::table('disposal_records_table')
            ->where('disposal_record_id', $request->disposal_id)
            ->first();

        if (! $record) {
            return back()->with('error', 'Disposal record not found.');
        }

        $equipment = DB::table('equipment_table')
            ->where('equipment_id', $record->disposal_equipment_id)
            ->first();

        if (
            $equipment
            && ($equipment->equipment_condition_status ?? '') === 'Disposed'
        ) {
            return back()->with(
                'error',
                'This equipment has been finally disposed and cannot be restored.'
            );
        }

        DB::table('equipment_table')
            ->where('equipment_id', $record->disposal_equipment_id)
            ->update([
                'equipment_inventory_status' => 'Active',
                'equipment_condition_status' => 'Good',
            ]);

        DB::table('disposal_records_table')
            ->where('disposal_record_id', $request->disposal_id)
            ->delete();

        return back()->with('success', 'Equipment restored to Inventory.');
    }

    public function confirmDisposal(Request $request)
    {
        $record = DB::table('disposal_records_table')
            ->where('disposal_record_id', $request->disposal_id)
            ->first();

        if (! $record) {
            return back()->with('error', 'Disposal record not found.');
        }

        DB::table('equipment_table')
            ->where('equipment_id', $record->disposal_equipment_id)
            ->update([
                'equipment_inventory_status' => 'Disposed',
                'equipment_condition_status' => 'Disposed',
            ]);

        DB::table('disposal_records_table')
            ->where('disposal_record_id', $request->disposal_id)
            ->update([
                'disposal_disposed_at' => now(),
            ]);

        return back()->with(
            'success',
            'Equipment marked as finally disposed. It will remain in Disposal and cannot be restored.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ARCHIVE / UNARCHIVE DISPOSAL RECORD
    | Finalized disposals only — keeps audit trail, hides from active list.
    |--------------------------------------------------------------------------
    */

    public function archiveDisposal(Request $request)
    {
        if (! Schema::hasColumn('disposal_records_table', 'disposal_is_archived')) {
            return back()->with('error', 'Archive is not available yet.');
        }

        $record = DB::table('disposal_records_table')
            ->where('disposal_record_id', $request->disposal_id)
            ->first();

        if (! $record) {
            return back()->with('error', 'Disposal record not found.');
        }

        $equipment = DB::table('equipment_table')
            ->where('equipment_id', $record->disposal_equipment_id)
            ->first();

        if (($equipment->equipment_condition_status ?? '') !== 'Disposed') {
            return back()->with(
                'error',
                'Only finalized disposals can be archived. Restore or finalize the item first.'
            );
        }

        DB::table('disposal_records_table')
            ->where('disposal_record_id', $request->disposal_id)
            ->update(['disposal_is_archived' => 1]);

        return back()->with('success', 'Disposal record archived.');
    }

    public function unarchiveDisposal(Request $request)
    {
        if (! Schema::hasColumn('disposal_records_table', 'disposal_is_archived')) {
            return back()->with('error', 'Archive is not available yet.');
        }

        $updated = DB::table('disposal_records_table')
            ->where('disposal_record_id', $request->disposal_id)
            ->update(['disposal_is_archived' => 0]);

        if (! $updated) {
            return back()->with('error', 'Disposal record not found.');
        }

        return back()->with('success', 'Disposal record restored from archive.');
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

        $pinsReady = ReporterPins::ready();
        $activeReportersWithoutPin = $pinsReady
            ? DB::table('reporters_table')
                ->where('reporter_status', 'Active')
                ->where(fn ($q) => $q->whereNull('reporter_pin_hash')->orWhere('reporter_pin_hash', ''))
                ->count()
            : 0;

        return compact(
            'totalReporters',
            'reportersWithEmail',
            'reportersWithContact',
            'currentMonthReporters',
            'previousMonthReporters',
            'reporterMonthlyPercentage',
            'emailCoveragePercentage',
            'contactCoveragePercentage',
            'reporterMonthlyTrend',
            'pinsReady',
            'activeReportersWithoutPin'
        );
    }

    // =====================================================
    // REPORTER PIN
    // GENERATE OR SET A 6-DIGIT PIN; THE PLAIN PIN IS SHOWN ONCE
    // =====================================================

    public function setReporterPin(Request $request, $id)
    {
        $request->validate([
            'mode' => 'required|in:generate,manual',
            'pin' => 'nullable|required_if:mode,manual|digits:'.ReporterPins::LENGTH,
            'send_email' => 'nullable|boolean',
        ], [
            'pin.required_if' => 'Enter the 6-digit PIN you want to set.',
            'pin.digits' => 'PIN must be exactly '.ReporterPins::LENGTH.' digits.',
        ]);

        if (! ReporterPins::ready()) {
            return back()->with('error', 'Reporter PINs are not set up yet. Run the database migration first.');
        }

        $reporter = ReporterPins::findReporterById((int) $id);

        if (! $reporter) {
            return back()->with('error', 'Reporter not found.');
        }

        if ($request->mode === 'manual' && ($reason = ReporterPins::weaknessReason((string) $request->pin))) {
            return back()->with('error', $reason);
        }

        $hadPin = ReporterPins::hasPin($reporter);
        $pin = ReporterPins::assign((int) $reporter->reporter_id, $request->mode === 'manual' ? (string) $request->pin : null);
        $emailed = $request->boolean('send_email') && ReporterPins::emailPin($reporter, $pin, ! $hadPin);

        return back()
            ->with('success', $hadPin ? 'Reporter PIN changed.' : 'Reporter PIN created.')
            ->with('reporter_pin_result', $this->reporterPinResult($reporter, $pin, $emailed));
    }

    public function generateMissingReporterPins()
    {
        if (! ReporterPins::ready()) {
            return back()->with('error', 'Reporter PINs are not set up yet. Run the database migration first.');
        }

        set_time_limit(0);

        $reporters = DB::table('reporters_table')
            ->where('reporter_status', 'Active')
            ->where(fn ($q) => $q->whereNull('reporter_pin_hash')->orWhere('reporter_pin_hash', ''))
            ->orderBy('reporter_full_name')
            ->get();

        if ($reporters->isEmpty()) {
            return back()->with('success', 'Every active reporter already has a PIN.');
        }

        $emailed = 0;
        $handOver = [];

        foreach ($reporters as $reporter) {
            $pin = ReporterPins::assign((int) $reporter->reporter_id);

            if (ReporterPins::emailPin($reporter, $pin)) {
                $emailed++;
            } else {
                $handOver[] = $this->reporterPinResult($reporter, $pin, false);
            }
        }

        return back()
            ->with('success', 'Created PINs for '.$reporters->count().' '.\Illuminate\Support\Str::plural('reporter', $reporters->count()).'.')
            ->with('reporter_pin_bulk_result', [
                'total' => $reporters->count(),
                'emailed' => $emailed,
                'hand_over' => $handOver,
            ]);
    }

    private function reporterPinResult(object $reporter, string $pin, bool $emailed): array
    {
        return [
            'name' => $reporter->reporter_full_name,
            'employee_id' => $reporter->reporter_employee_id,
            'email' => $reporter->reporter_email_address,
            'pin' => $pin,
            'emailed' => $emailed,
        ];
    }

    /**
     * Gives a newly added reporter a PIN and emails it; the result is flashed so it can be shown once.
     */
    private function issuePinForNewReporter(int $reporterId): ?array
    {
        if (! ReporterPins::ready()) {
            return null;
        }

        $reporter = ReporterPins::findReporterById($reporterId);

        if (! $reporter) {
            return null;
        }

        $pin = ReporterPins::assign($reporterId);

        return $this->reporterPinResult($reporter, $pin, ReporterPins::emailPin($reporter, $pin));
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

            $historyReporter = DB::table('reporters_table as r')
                ->when(ReporterImport::hasDepartmentColumn() && Departments::tableReady(), function ($query) {
                    $query->leftJoin('departments_table as d', 'd.department_id', '=', 'r.reporter_department_id');
                })
                ->where('r.reporter_id', $request->history)
                ->select('r.*')
                ->when(ReporterImport::hasDepartmentColumn() && Departments::tableReady(), fn ($query) => $query->addSelect('d.department_name as reporter_department_name'))
                ->first();

            if ($historyReporter && empty($historyReporter->reporter_department_name) && empty($historyReporter->department_name)) {
                $historyReporter->reporter_department_name = $this->resolveReporterDepartmentName($historyReporter);
                $historyReporter->department_name = $historyReporter->reporter_department_name;
            }


            // =================================================
            // REPORTER NOT FOUND
            // =================================================

            if (!$historyReporter) {

                return redirect()

                    ->route('admin.reporters.index')

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

            if (ReporterImport::hasDepartmentColumn() && Departments::tableReady()) {
                $query
                    ->leftJoin('departments_table as reporter_dept', 'reporter_dept.department_id', '=', 'reporters_table.reporter_department_id')
                    ->select('reporters_table.*', 'reporter_dept.department_name as reporter_department_name');
            }


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

            // add a subquery that counts total reports per reporter (by employee id)
            $reportCountSub = DB::table('reports_table')
                ->selectRaw('COUNT(*)')
                ->whereColumn('reports_table.report_reporter_employee_id', 'reporters_table.reporter_employee_id');

            $query->selectSub($reportCountSub, 'report_count');

            $reporters = $query
                ->orderBy('reporter_id', 'desc')
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

            'admin.reporters.index',

            array_merge(

                [
                    'reporters' => $reporters,

                    'historyReporter' => $historyReporter,

                    'reportHistory' => $reportHistory,

                    'departments' => $this->reporterDepartmentOptions(),
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
    | LOG WALK-IN REPORT
    |--------------------------------------------------------------------------
    */

    public function createWalkInReport()
    {
        $walkInReports = ReportGrouping::hasLoggedByColumn()
            ? $this->walkInReportsQuery()->paginate(10)->withQueryString()
            : collect();

        if ($walkInReports instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            ReportItems::attachToReports($walkInReports->getCollection());
        }

        return view('maintenance-personnel.reports.log-report', [
            'rooms' => $this->roomsForWalkInReport(),
            'walkInReports' => $walkInReports,
        ]);
    }

    private function walkInReportsQuery()
    {
        return DB::table('reports_table')
            ->whereNotNull('reports_table.report_logged_by')
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
            ->leftJoin(
                'users_table as logged_by_user',
                'reports_table.report_logged_by',
                '=',
                'logged_by_user.user_id'
            )
            ->select(
                'reports_table.*',
                'rooms_table.room_name',
                'floors_table.floor_level',
                'equipment_table.equipment_name',
                'reporters_table.reporter_full_name',
                'logged_by_user.user_full_name as report_logged_by_name'
            )
            ->orderByDesc('reports_table.report_submitted_at');
    }

    private function roomsForWalkInReport()
    {
        $equipmentCountSub = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
                ->select('equipment_room_id', DB::raw('COUNT(*) as equipment_count'))
                ->whereNotNull('equipment_room_id')
                ->groupBy('equipment_room_id')
        );

        return DB::table('rooms_table')
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
            ->leftJoinSub(
                $equipmentCountSub,
                'room_equipment_counts',
                'rooms_table.room_id',
                '=',
                'room_equipment_counts.equipment_room_id'
            )
            ->select(
                'rooms_table.*',
                'floors_table.floor_level',
                'buildings_table.building_name',
                DB::raw('COALESCE(room_equipment_counts.equipment_count, 0) as equipment_count')
            )
            ->orderByRaw("
                CASE
                    WHEN floors_table.floor_level LIKE '2nd%' THEN 1
                    WHEN floors_table.floor_level LIKE '3rd%' THEN 2
                    ELSE 99
                END ASC
            ")
            ->orderByRaw(
                "CASE WHEN floors_table.floor_level LIKE '3rd%' AND rooms_table.room_type = ? THEN 0 ELSE 1 END ASC",
                ['Lecture Room']
            )
            ->orderBy('rooms_table.room_name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | STORE WALK-IN REPORT
    |--------------------------------------------------------------------------
    */

    public function storeReport(Request $request, ReportSubmissionService $reports)
    {
        $result = $reports->submit($request, (int) Auth::id());

        if (! ($result['success'] ?? false)) {
            return back()
                ->withErrors($result['errors'] ?? ['general' => $result['message'] ?? 'Unable to log report.'])
                ->withInput();
        }

        $reportId = (int) ($result['report_id'] ?? 0);

        if ($reportId > 0) {
            $this->logActivity(
                'Logged walk-in report',
                'Reports',
                'reports_table',
                $reportId,
                'Logged walk-in Report #' . $reportId . ' on behalf of ' . $request->report_reporter_employee_id . '.'
            );
        }

        return redirect()
            ->route('maintenance.reports.log')
            ->with('success', (string) ($result['message'] ?? 'Report logged successfully.')
                .(!empty($result['severity']) ? ' Priority: '.$result['severity'].'.' : ''));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE REPORTER
    |--------------------------------------------------------------------------
    */

    public function updateReporter(Request $request)
    {
        PersonNames::cleanRequest($request, ['first_name', 'middle_name', 'last_name']);

        $request->validate([
            'reporter_id' => 'required',
            'employee_id' => ['required', 'string', 'regex:/^OMC[0-9]{5}[FS]$/'],
            'first_name' => PersonNames::rules(),
            'middle_name' => PersonNames::rules(false),
            'last_name' => PersonNames::rules(),
            'type' => 'required|in:Faculty,Staff',
            'employment_basis' => ['nullable', Rule::in(['Full time', 'Part time'])],
            'department_id' => $this->reporterDepartmentRules(),
            'email' => 'required|email|max:255',
            'contact' => \App\Support\PhoneNumber::rules(),
        ], [
            'employee_id.regex' => 'Employee ID must look like OMC00127F (Faculty) or OMC00127S (Staff).',
        ]);

        $employeeId = strtoupper(trim($request->employee_id));
        $expectedSuffix = $request->type === 'Staff' ? 'S' : 'F';

        if (! str_ends_with($employeeId, $expectedSuffix)) {
            return back()->withErrors([
                'employee_id' => $request->type === 'Staff'
                    ? 'Staff employee IDs must end with S (example: OMC00127S).'
                    : 'Faculty employee IDs must end with F (example: OMC00127F).',
            ])->withInput();
        }

        $taken = ReporterApprovals::registeredByEmployeeNumber($employeeId);

        if ($taken && (string) $taken->reporter_id !== (string) $request->reporter_id) {
            return back()->with(
                'error',
                'That employee number is already used by another reporter (Faculty or Staff).'
            );
        }

        if ($emailTaken = $this->holdReporterForTakenEmail($request, (int) $request->reporter_id)) {
            return $emailTaken;
        }

        $first = trim($request->first_name);
        $middle = trim((string) $request->middle_name);
        $last = trim($request->last_name);

        if ($duplicate = $this->holdReporterForDuplicateName($request, $first, $last, (int) $request->reporter_id)) {
            return $duplicate;
        }

        $payload = [
            'reporter_employee_id' => $employeeId,
            'reporter_full_name' => ReporterImport::composeFullName($first, $middle, $last),
            'reporter_email_address' => $request->email ? trim($request->email) : null,
            'reporter_contact_number' => \App\Support\PhoneNumber::normalizeForStorage($request->contact),
        ];

        if (ReporterImport::hasNameColumns()) {
            $payload['reporter_first_name'] = $first;
            $payload['reporter_middle_name'] = $middle !== '' ? $middle : null;
            $payload['reporter_last_name'] = $last;
        }

        if (ReporterImport::hasTypeColumn()) {
            $payload['reporter_employment_type'] = $request->filled('type') ? trim($request->type) : null;
        }

        if (ReporterImport::hasBasisColumn()) {
            $payload['reporter_employment_basis'] = $request->filled('employment_basis') ? trim((string) $request->employment_basis) : null;
        }

        if (ReporterImport::hasDepartmentColumn()) {
            $payload['reporter_department_id'] = $request->filled('department_id') ? (int) $request->department_id : null;
        }

        DB::table('reporters_table')
            ->where('reporter_id', $request->reporter_id)
            ->update($payload);

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
        // CUSTOM CALENDAR FILTER
        // =====================================================

        $date = $request->get('date');

        $weekDate = $request->get('week_date');

        $month = $request->get('month');

        $year = $request->get('year');


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
            'Inspection',
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
        // DATE / PERIOD FILTER
        // =====================================================


        // =====================================================
        // SPECIFIC DATE
        // Example: July 24, 2026
        // =====================================================

        if ($date) {

            $query->whereDate(
                'notifications_table.notification_created_at',
                $date
            );
        }


        // =====================================================
        // SPECIFIC WEEK
        // Uses the selected date and gets its entire week
        // =====================================================

        elseif ($weekDate) {

            $selectedWeek =
                \Carbon\Carbon::parse($weekDate);

            $query->whereBetween(
                'notifications_table.notification_created_at',
                [
                    $selectedWeek->copy()->startOfWeek(),
                    $selectedWeek->copy()->endOfWeek(),
                ]
            );
        }


        // =====================================================
        // SPECIFIC MONTH
        // Example: July 2026
        // =====================================================

        elseif ($month) {

            $selectedMonth =
                \Carbon\Carbon::createFromFormat(
                    'Y-m',
                    $month
                );

            $query
                ->whereYear(
                    'notifications_table.notification_created_at',
                    $selectedMonth->year
                )

                ->whereMonth(
                    'notifications_table.notification_created_at',
                    $selectedMonth->month
                );
        }


        // =====================================================
        // SPECIFIC YEAR
        // Example: 2025
        // =====================================================

        elseif ($year) {

            $query->whereYear(
                'notifications_table.notification_created_at',
                $year
            );
        }


        // =====================================================
        // NO CUSTOM DATE SELECTED
        // USE QUICK FILTERS
        // =====================================================

        else {

            switch ($period) {

                // =================================================
                // CURRENT WEEK
                // =================================================

                case 'week':

                    $query->whereBetween(
                        'notifications_table.notification_created_at',
                        [
                            now()->startOfWeek(),
                            now()->endOfWeek(),
                        ]
                    );

                    break;


                // =================================================
                // CURRENT MONTH
                // =================================================

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


                // =================================================
                // CURRENT YEAR
                // =================================================

                case 'year':

                    $query->whereYear(
                        'notifications_table.notification_created_at',
                        now()->year
                    );

                    break;


                // =================================================
                // TODAY
                // =================================================

                default:

                    $query->whereDate(
                        'notifications_table.notification_created_at',
                        today()
                    );

                    break;
            }
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

            ->paginate(10)

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
        // DASHBOARD SUMMARY CARDS
        // =====================================================


        // =====================================================
        // URGENT REPORTS
        // REPORTS THAT STILL REQUIRE ATTENTION
        // =====================================================

        $urgentReports = DB::table('reports_table')

            ->where(
                'report_urgency_level',
                'Urgent'
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

            ->count();


        // =====================================================
        // MAINTENANCE DUE TODAY
        // =====================================================

        $dueToday = DB::table('maintenance_schedules_table')

            ->whereDate(
                'maintenance_schedule_next_date',
                today()
            )

            ->where(
                'maintenance_schedule_status',
                'Active'
            )

            ->count();


        // =====================================================
        // OVERDUE MAINTENANCE
        // DATE HAS ALREADY PASSED BUT SCHEDULE IS STILL ACTIVE
        // =====================================================

        $overdueMaintenance = DB::table('maintenance_schedules_table')

            ->whereDate(
                'maintenance_schedule_next_date',
                '<',
                today()
            )

            ->where(
                'maintenance_schedule_status',
                'Active'
            )

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

                // =============================================
                // CUSTOM DATE FILTERS
                // =============================================

                'date',
                'weekDate',
                'month',
                'year',

                // =============================================
                // PERIOD COUNTS
                // =============================================

                'todayCount',
                'weekCount',
                'monthCount',
                'yearCount',
                'unreadCount',

                // =============================================
                // SUMMARY CARDS
                // =============================================

                'urgentReports',
                'dueToday',
                'overdueMaintenance'
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
            \App\Support\NotificationLinks::resolve($notification);


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

    // =====================================================
    // RECORD MAINTENANCE ACTIVITY
    // =====================================================

    private function logActivity(
        string $action,
        string $module,
        string $tableName,
        ?int $referenceId,
        string $description
    ): void
    {
        // =====================================================
        // GET CURRENT LOGGED IN USER
        // =====================================================

        $user = Auth::user();

        // =====================================================
        // CREATE AUDIT / ACTIVITY LOG
        // =====================================================

        DB::table('audit_logs_table')->insert([
            'audit_log_user_id' => $user?->user_id,

            'audit_log_action' => $action,

            'audit_log_module' => $module,

            'audit_log_table_name' => $tableName,

            'audit_log_reference_id' => $referenceId,

            'audit_log_description' => $description,

            'audit_log_ip_address' => request()->ip(),

            'audit_log_created_at' => now(),
        ]);
    }

    // =====================================================
    // MAINTENANCE ACTIVITY HISTORY
    // =====================================================

    public function activities(Request $request)
    {
        // =====================================================
        // START ACTIVITY QUERY
        // ONLY SHOW THE LOGGED IN USER'S ACTIVITIES
        // =====================================================

        $query = DB::table('audit_logs_table')

            ->where(
                'audit_log_user_id',
                Auth::id()
            )

            ->whereNotNull(
                'audit_log_module'
            );


        // =====================================================
        // SEARCH
        //
        // SEARCHES ACTION AND DESCRIPTION
        // =====================================================

        if ($request->filled('search')) {

            $search = trim(
                $request->input('search')
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'audit_log_action',
                    'like',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'audit_log_description',
                    'like',
                    '%' . $search . '%'
                );

            });
        }


        // =====================================================
        // MODULE FILTER
        //
        // EG: REPORTS, EQUIPMENT, SCHEDULES
        // =====================================================

        if ($request->filled('module')) {

            $query->where(
                'audit_log_module',
                $request->input('module')
            );
        }


        // =====================================================
        // DATE FROM
        // =====================================================

        if ($request->filled('date_from')) {

            $query->whereDate(
                'audit_log_created_at',
                '>=',
                $request->input('date_from')
            );
        }


        // =====================================================
        // DATE TO
        // =====================================================

        if ($request->filled('date_to')) {

            $query->whereDate(
                'audit_log_created_at',
                '<=',
                $request->input('date_to')
            );
        }


        // =====================================================
        // GET AVAILABLE MODULES
        //
        // THIS MAKES THE FILTER AUTOMATIC.
        // IF WE ADD A NEW MODULE LATER, IT CAN APPEAR HERE.
        // =====================================================

        $activityModules = DB::table('audit_logs_table')

            ->where(
                'audit_log_user_id',
                Auth::id()
            )

            ->whereNotNull(
                'audit_log_module'
            )

            ->distinct()

            ->orderBy(
                'audit_log_module'
            )

            ->pluck(
                'audit_log_module'
            );


        // =====================================================
        // GET PAGINATED ACTIVITIES
        // =====================================================

        $activities = $query

            ->orderByDesc(
                'audit_log_created_at'
            )

            ->paginate(15)

            ->withQueryString();


        // =====================================================
        // ADD DISPLAY INFORMATION
        // =====================================================

        $activities->getCollection()
            ->transform(function ($activity) {

                // =================================================
                // ICON
                // =================================================

                $activity->icon = match (
                    $activity->audit_log_module
                ) {

                    'Reports' =>
                        'clipboard-list',

                    'Equipment' =>
                        'monitor',

                    'Schedules' =>
                        'calendar',

                    'Borrowing' =>
                        'package',

                    'Infrastructure' =>
                        'building-2',

                    default =>
                        'activity',
                };


                // =================================================
                // DESTINATION
                // =================================================

                $activity->url = match (
                    $activity->audit_log_table_name
                ) {

                    'reports_table' =>
                        $activity->audit_log_reference_id
                            ? url(
                                '/maintenance/reports/details/'
                                . $activity->audit_log_reference_id
                            )
                            : null,

                    'equipment_table' =>
                        $activity->audit_log_reference_id
                            ? url(
                                '/maintenance/equipment/view/'
                                . $activity->audit_log_reference_id
                            )
                            : null,

                    'maintenance_schedules_table' =>
                        url('/maintenance/schedules'),

                    'borrowing_records_table' =>
                        RoleAccess::isAdmin(auth()->user()) ? url('/admin/borrowing') : null,

                    'rooms_table' =>
                        $activity->audit_log_reference_id
                            ? url('/maintenance/property-assignments/rooms/'.$activity->audit_log_reference_id)
                            : null,

                    default =>
                        null,
                };


                return $activity;
            });


        // =====================================================
        // RETURN PAGE
        // =====================================================

        return view(
            'maintenance-personnel.activities.index',
            compact(
                'activities',
                'activityModules'
            )
        );
    }


    // =====================================================
    // PROCESS MAINTENANCE SCHEDULE ALERTS
    //
    // CALLED AUTOMATICALLY BY LARAVEL SCHEDULER
    // =====================================================

    public function processMaintenanceScheduleAlerts(): void
    {
        // KEEP ALL THE MAINTENANCE ALERT LOGIC
        // WE JUST CREATED INSIDE HERE.

        $today = now()->startOfDay();

        $dueSoonLimit = now()
            ->startOfDay()
            ->addDays(3);

        $schedules = DB::table('maintenance_schedules_table')

            ->leftJoin(
                'equipment_table',
                'maintenance_schedules_table.maintenance_schedule_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->whereNotNull(
                'maintenance_schedules_table.maintenance_schedule_next_date'
            )

            ->where(
                'maintenance_schedules_table.maintenance_schedule_status',
                '!=',
                'Completed'
            )

            ->select(
                'maintenance_schedules_table.*',
                'equipment_table.equipment_name'
            )

            ->get();


        foreach ($schedules as $schedule) {

            $scheduleDate = \Carbon\Carbon::parse(
                $schedule->maintenance_schedule_next_date
            )->startOfDay();

            $equipmentName =
                $schedule->equipment_name
                ?? 'Equipment';


            // =================================================
            // OVERDUE
            // =================================================

            if ($scheduleDate->lt($today)) {

                if (
                    $schedule->maintenance_schedule_status
                    !== 'Overdue'
                ) {

                    DB::table('maintenance_schedules_table')

                        ->where(
                            'maintenance_schedule_id',
                            $schedule->maintenance_schedule_id
                        )

                        ->update([
                            'maintenance_schedule_status'
                                => 'Overdue',
                        ]);
                }


                DB::table('notifications_table')
                    ->insertOrIgnore([

                        'notification_user_id'
                            => null,

                        'notification_target_role'
                            => 'Maintenance Personnel',

                        'notification_title'
                            => 'Maintenance Overdue',

                        'notification_message'
                            => $equipmentName
                            . ' has an overdue maintenance schedule.',

                        'notification_type'
                            => 'maintenance_overdue',

                        'notification_category'
                            => 'Maintenance',

                        'notification_reference_type'
                            => 'maintenance_schedule',

                        'notification_reference_id'
                            => $schedule->maintenance_schedule_id,

                        'notification_url'
                            => '/maintenance/schedules',

                        'notification_event_key'
                            => 'maintenance_overdue_'
                            . $schedule->maintenance_schedule_id,

                        'notification_created_at'
                            => now(),

                    ]);

                continue;
            }


            // =================================================
            // DUE TODAY
            // =================================================

            if (
                $scheduleDate->isSameDay($today)
                &&
                $schedule->maintenance_schedule_status === 'Active'
            ) {

                DB::table('notifications_table')
                    ->insertOrIgnore([

                        'notification_user_id'
                            => null,

                        'notification_target_role'
                            => 'Maintenance Personnel',

                        'notification_title'
                            => 'Maintenance Due Today',

                        'notification_message'
                            => $equipmentName
                            . ' is scheduled for maintenance today.',

                        'notification_type'
                            => 'maintenance_due_today',

                        'notification_category'
                            => 'Maintenance',

                        'notification_reference_type'
                            => 'maintenance_schedule',

                        'notification_reference_id'
                            => $schedule->maintenance_schedule_id,

                        'notification_url'
                            => '/maintenance/schedules',

                        'notification_event_key'
                            => 'maintenance_due_today_'
                            . $schedule->maintenance_schedule_id,

                        'notification_created_at'
                            => now(),

                    ]);

                continue;
            }


            // =================================================
            // DUE SOON
            // =================================================

            if (
                $scheduleDate->gt($today)
                &&
                $scheduleDate->lte($dueSoonLimit)
                &&
                $schedule->maintenance_schedule_status === 'Active'
            ) {

                DB::table('notifications_table')
                    ->insertOrIgnore([

                        'notification_user_id'
                            => null,

                        'notification_target_role'
                            => 'Maintenance Personnel',

                        'notification_title'
                            => 'Maintenance Due Soon',

                        'notification_message'
                            => $equipmentName
                            . ' is scheduled for maintenance on '
                            . $scheduleDate->format('M d, Y')
                            . '.',

                        'notification_type'
                            => 'maintenance_upcoming',

                        'notification_category'
                            => 'Maintenance',

                        'notification_reference_type'
                            => 'maintenance_schedule',

                        'notification_reference_id'
                            => $schedule->maintenance_schedule_id,

                        'notification_url'
                            => '/maintenance/schedules',

                        'notification_event_key'
                            => 'maintenance_upcoming_'
                            . $schedule->maintenance_schedule_id,

                        'notification_created_at'
                            => now(),

                    ]);
            }
        }
    }

    private function resolveReporterDepartmentName(object $reporter): ?string
    {
        if (! ReporterImport::hasDepartmentColumn() || ! Departments::tableReady()) {
            return null;
        }

        $employeeId = trim((string) ($reporter->reporter_employee_id ?? ''));
        $reporterId = (int) ($reporter->reporter_id ?? 0);

        if ($reporterId <= 0 && $employeeId === '') {
            return null;
        }

        $linked = DB::table('custodians_table as person')
            ->leftJoin('departments_table as dept', 'dept.department_id', '=', 'person.custodian_department_id')
            ->where(function ($query) use ($reporterId, $employeeId) {
                if ($reporterId > 0) {
                    $query->orWhere('person.custodian_reporter_id', $reporterId);
                }
                if ($employeeId !== '') {
                    $query->orWhere('person.custodian_employee_id', $employeeId);
                }
            })
            ->orderByRaw('person.custodian_reporter_id = ? DESC', [$reporterId])
            ->value('dept.department_name');

        return $linked !== null && $linked !== '' ? (string) $linked : null;
    }

    private function reporterDepartmentRules(): array
    {
        return Departments::tableReady()
            ? ['nullable', 'integer', Rule::exists('departments_table', 'department_id')]
            : ['nullable'];
    }

    private function reporterDepartmentOptions()
    {
        return Departments::tableReady()
            ? DB::table('departments_table')->orderBy('department_name')->get(['department_id', 'department_name', 'department_is_archived'])
            : collect();
    }

    /**
     * Same first + last name as another reporter: send the form back with the matches until confirmed.
     */
    private function holdReporterForDuplicateName(Request $request, string $first, string $last, ?int $reporterId = null)
    {
        if ($request->boolean('confirm_duplicate') || ! ReporterImport::hasNameColumns()) {
            return null;
        }

        $key = fn ($f, $l) => PersonNames::matchKey($f).'|'.PersonNames::matchKey($l);
        if ($reporterId) {
            $current = DB::table('reporters_table')->where('reporter_id', $reporterId)->first(['reporter_first_name', 'reporter_last_name']);
            if ($current && $key($current->reporter_first_name, $current->reporter_last_name) === $key($first, $last)) {
                return null;
            }
        }

        $matches = PersonNames::duplicates(
            'reporters_table', 'reporter_first_name', 'reporter_last_name',
            $first, $last, 'reporter_id', $reporterId ?: null
        )->map(fn ($reporter) => [
            'name' => $reporter->reporter_full_name,
            'employee_id' => $reporter->reporter_employee_id,
            'type' => $reporter->reporter_employment_type ?? null,
            'status' => $reporter->reporter_status,
            'url' => route('admin.reporters.index', ['search' => $reporter->reporter_employee_id]),
        ])->all();

        return $matches === []
            ? null
            : back()->withInput()->with('reporter_duplicates', $matches)->with('reporter_duplicate_mode', $reporterId ? 'edit' : 'create');
    }

    /**
     * Send the form back when another reporter already uses this email (case-insensitive).
     */
    private function holdReporterForTakenEmail(Request $request, ?int $reporterId = null)
    {
        $email = strtolower(trim((string) $request->email));
        if ($email === '') {
            return null;
        }

        if ($reporterId) {
            $current = strtolower(trim((string) DB::table('reporters_table')->where('reporter_id', $reporterId)->value('reporter_email_address')));
            if ($current === $email) {
                return null;
            }
        }

        $owner = DB::table('reporters_table')
            ->whereRaw('LOWER(TRIM(reporter_email_address)) = ?', [$email])
            ->when($reporterId, fn ($q) => $q->where('reporter_id', '!=', $reporterId))
            ->first(['reporter_full_name', 'reporter_employee_id']);

        if (! $owner) {
            return null;
        }

        return back()
            ->withInput()
            ->withErrors([
                'email' => 'This email is already used by '.($owner->reporter_full_name ?: 'another reporter')
                    .($owner->reporter_employee_id ? ' ('.$owner->reporter_employee_id.')' : '')
                    .'. Every reporter needs a different email.',
            ])
            ->with('reporter_form_mode', $reporterId ? 'edit' : 'create');
    }

    public function storeReporter(Request $request)
    {
        PersonNames::cleanRequest($request, ['first_name', 'middle_name', 'last_name']);

        // Prefer campus Persons master when provided.
        if ($request->filled('person_id') && \App\Support\Persons::tableReady()) {
            $person = \App\Support\Persons::find((int) $request->input('person_id'));
            if ($person) {
                $request->merge([
                    'employee_id' => $person->person_employee_id,
                    'first_name' => $person->person_first_name ?: $request->input('first_name'),
                    'middle_name' => $person->person_middle_name,
                    'last_name' => $person->person_last_name ?: $request->input('last_name'),
                    'type' => $person->person_employment_type ?: $request->input('type'),
                    'employment_basis' => $person->person_employment_basis ?: $request->input('employment_basis'),
                    'department_id' => $person->person_department_id ?: $request->input('department_id'),
                    'email' => $person->person_email_address ?: $request->input('email'),
                    'contact' => $person->person_contact_number ?: $request->input('contact'),
                ]);
            }
        }

        $request->validate([
            'person_id' => ['nullable', 'integer', 'min:1'],
            // Employee ID
            // Required: OMC + 5 digits + F/S; unique by 5-digit number (F/S variants share one number)
            'employee_id' => [
                'required',
                'string',
                'regex:/^OMC[0-9]{5}[FS]$/',
            ],

            // First Name
            // Required
            'first_name' => PersonNames::rules(),

            // Middle Name
            // Optional
            'middle_name' => PersonNames::rules(false),

            // Last Name
            // Required
            'last_name' => PersonNames::rules(),

            // Employment Type
            // Required
            'type' => [
                'required',
                'in:Faculty,Staff',
            ],

            // Employment Basis
            // Optional, if present must be Full time or Part time
            'employment_basis' => ['nullable', Rule::in(['Full time', 'Part time'])],

            // Department
            // Optional
            'department_id' => $this->reporterDepartmentRules(),

            // Email
            // Required; the reporter's PIN is sent here
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            // Contact Number — country-aware (PH mobile or international E.164)
            'contact' => \App\Support\PhoneNumber::rules(),
        ], [
            'employee_id.regex' => 'Employee ID must look like OMC00127F (Faculty) or OMC00127S (Staff).',
        ]);

        $employeeId = strtoupper(trim($request->employee_id));
        $expectedSuffix = $request->type === 'Staff' ? 'S' : 'F';

        if (! str_ends_with($employeeId, $expectedSuffix)) {
            return back()->withErrors([
                'employee_id' => $request->type === 'Staff'
                    ? 'Staff employee IDs must end with S (example: OMC00127S).'
                    : 'Faculty employee IDs must end with F (example: OMC00127F).',
            ])->withInput();
        }

        if (ReporterApprovals::registeredByEmployeeNumber($employeeId)) {
            return back()->with(
                'error',
                'That employee number is already in the reporters list (Faculty or Staff).'
            );
        }

        if ($emailTaken = $this->holdReporterForTakenEmail($request)) {
            return $emailTaken;
        }

        $first = trim($request->first_name);
        $middle = trim((string) $request->middle_name);
        $last = trim($request->last_name);

        if ($duplicate = $this->holdReporterForDuplicateName($request, $first, $last)) {
            return $duplicate;
        }

        $fullName = ReporterImport::composeFullName($first, $middle, $last);

        $payload = [
            'reporter_employee_id' => $employeeId,
            'reporter_full_name' => $fullName,
            'reporter_email_address' => $request->email ? trim($request->email) : null,
            'reporter_contact_number' => \App\Support\PhoneNumber::normalizeForStorage($request->contact),
            'reporter_status' => 'Active',
            'reporter_created_at' => now(),
        ];

        if (ReporterImport::hasNameColumns()) {
            $payload['reporter_first_name'] = $first;
            $payload['reporter_middle_name'] = $middle !== '' ? $middle : null;
            $payload['reporter_last_name'] = $last;
        }

        if (ReporterImport::hasTypeColumn() && $request->filled('type')) {
            $payload['reporter_employment_type'] = trim($request->type);
        }

        if (ReporterImport::hasBasisColumn() && $request->filled('employment_basis')) {
            $payload['reporter_employment_basis'] = trim((string) $request->employment_basis);
        }

        if (ReporterImport::hasDepartmentColumn()) {
            $payload['reporter_department_id'] = $request->filled('department_id') ? (int) $request->department_id : null;
        }

        // If a custodian exists with this employee ID, enforce exact match
        // rather than silently altering data. This prevents divergent
        // records for the same employee ID across modules.
        if (Schema::hasTable('custodians_table')) {
            $custodian = DB::table('custodians_table')
                ->where('custodian_employee_id', $employeeId)
                ->first();

            if ($custodian) {
                $errors = [];
                $custName = trim((string) $custodian->custodian_full_name);
                if ($custName !== '' && $custName !== $fullName) {
                    $errors['employee_id'] = 'Employee ID already exists in the people directory with a different name ('.$custName.'). Reconcile details first.';
                }

                $custEmail = trim((string) ($custodian->custodian_email_address ?? ''));
                if ($custEmail !== '' && $custEmail !== ($payload['reporter_email_address'] ?? '')) {
                    $errors['email'] = 'Email differs from the people directory ('.$custEmail.'). Reconcile details first.';
                }

                $custContact = trim((string) ($custodian->custodian_contact_number ?? ''));
                if ($custContact !== '' && $custContact !== ($payload['reporter_contact_number'] ?? '')) {
                    $errors['contact'] = 'Contact number differs from the people directory ('.$custContact.'). Reconcile details first.';
                }

                $custDept = ! empty($custodian->custodian_department_id) ? (int) $custodian->custodian_department_id : null;
                $reqDept = ReporterImport::hasDepartmentColumn() ? ($request->filled('department_id') ? (int) $request->department_id : null) : null;
                if ($custDept !== null && $reqDept !== null && $custDept !== $reqDept) {
                    $errors['department_id'] = 'Department differs from the people directory. Reconcile details first.';
                }

                if ($errors !== []) {
                    // Offer a force-sync option in the UI: include custodian data in session
                    session()->flash('mismatch_force_data', [
                        'type' => 'custodian',
                        'custodian' => [
                            'custodian_full_name' => $custodian->custodian_full_name ?? null,
                            'custodian_first_name' => $custodian->custodian_first_name ?? null,
                            'custodian_middle_name' => $custodian->custodian_middle_name ?? null,
                            'custodian_last_name' => $custodian->custodian_last_name ?? null,
                            'custodian_email_address' => $custodian->custodian_email_address ?? null,
                            'custodian_contact_number' => $custodian->custodian_contact_number ?? null,
                            'custodian_department_id' => $custodian->custodian_department_id ?? null,
                        ],
                    ]);

                    // If the user explicitly asked to force sync, perform the auto-sync instead
                    if ($request->filled('force_sync')) {

                        // Overwrite payload fields with custodian's authoritative values
                        $payload['reporter_full_name'] = $custodian->custodian_full_name ?? $payload['reporter_full_name'];
                        if (ReporterImport::hasNameColumns()) {
                            $payload['reporter_first_name'] = $custodian->custodian_first_name ?? $payload['reporter_first_name'] ?? null;
                            $payload['reporter_middle_name'] = $custodian->custodian_middle_name ?? $payload['reporter_middle_name'] ?? null;
                            $payload['reporter_last_name'] = $custodian->custodian_last_name ?? $payload['reporter_last_name'] ?? null;
                        }
                        $payload['reporter_email_address'] = $custodian->custodian_email_address ?: $payload['reporter_email_address'] ?? null;
                        $payload['reporter_contact_number'] = $custodian->custodian_contact_number ?: $payload['reporter_contact_number'] ?? null;
                        if (ReporterImport::hasDepartmentColumn() && ! empty($custodian->custodian_department_id)) {
                            $payload['reporter_department_id'] = (int) $custodian->custodian_department_id;
                        }

                        // Employment type/status mapping where applicable
                        if (ReporterImport::hasTypeColumn() && ! empty($custodian->custodian_position)) {
                            $pos = trim((string) $custodian->custodian_position);
                            if (stripos($pos, 'staff') !== false) {
                                $payload['reporter_employment_type'] = 'Staff';
                            } elseif (stripos($pos, 'faculty') !== false) {
                                $payload['reporter_employment_type'] = 'Faculty';
                            }
                        }

                        // Map custodian status to reporter_status when available
                        if (! empty($custodian->custodian_status)) {
                            $payload['reporter_status'] = $custodian->custodian_status === 'Inactive' ? 'Inactive' : 'Active';
                        }

                        $reporterId = DB::table('reporters_table')->insertGetId($payload, 'reporter_id');

                        // Link the custodian to the newly created reporter
                        DB::table('custodians_table')
                            ->where('custodian_id', $custodian->custodian_id)
                            ->update(['custodian_reporter_id' => (int) $reporterId]);

                        return back()
                            ->with('success', 'Reporter added successfully (forced sync).')
                            ->with('reporter_pin_result', $this->issuePinForNewReporter((int) $reporterId));
                    }

                    return back()->withInput()->withErrors($errors)->with('reporter_form_mode', 'create');
                }
            }
        }

        if (\App\Support\Persons::tableReady() && Schema::hasColumn('reporters_table', 'person_id')) {
            try {
                $personId = $request->filled('person_id')
                    ? (int) $request->input('person_id')
                    : \App\Support\Persons::ensureFromIdentity([
                        'employee_id' => $employeeId,
                        'type' => $request->type,
                        'first_name' => $first,
                        'middle_name' => $middle,
                        'last_name' => $last,
                        'employment_basis' => $request->input('employment_basis'),
                        'department_id' => $request->input('department_id'),
                        'email' => $request->input('email'),
                        'contact' => $request->input('contact'),
                    ]);
                $payload['person_id'] = $personId;
            } catch (\Throwable $e) {
                // Keep reporter create resilient if person sync fails.
            }
        }

        $reporterId = DB::table('reporters_table')->insertGetId($payload, 'reporter_id');

        // If reporter is created and a custodian exists, link the two records
        // so future lookups are consistent.
        if (isset($custodian) && $custodian && Schema::hasTable('custodians_table')) {
            DB::table('custodians_table')
                ->where('custodian_id', $custodian->custodian_id)
                ->update(['custodian_reporter_id' => (int) $reporterId]);
        }

        return back()
            ->with('success', 'Reporter added successfully.')
            ->with('reporter_pin_result', $this->issuePinForNewReporter((int) $reporterId));
    }

    public function downloadReporterTemplate()
    {
        $filename = 'reporter-import-template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_values(ReporterImport::FIELDS));
            fputcsv($handle, ['Faculty', 'OMC00130F', 'John', 'Michael', 'Smith', 'Academic Affairs', 'john.smith@ormoc.sti.edu.ph', "\t09171234567"]);
            fputcsv($handle, ['Staff', 'OMC00131S', 'Sarah', '', 'Connor', 'Registrar', 'sarah.connor@ormoc.sti.edu.ph', "\t09179876543"]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function previewReporterImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
        ]);

        $file = $request->file('file');
        $rows = ReporterImport::parseFile($file->getRealPath(), $file->getClientOriginalName());

        if (count($rows) < 2) {
            return response()->json([
                'ok' => false,
                'message' => 'The file needs a header row and at least one reporter.',
            ], 422);
        }

        $mapping = ReporterImport::suggestMapping($rows[0]);
        $missing = collect(['employee_id', 'first_name', 'last_name'])
            ->filter(fn ($field) => ($mapping[$field] ?? '') === '')
            ->map(fn ($field) => ReporterImport::FIELDS[$field])
            ->values();

        if ($missing->isNotEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Missing column(s): '.$missing->implode(', ').'. Download the sample file to see the expected headers.',
            ], 422);
        }

        $plan = ReporterImport::plan($rows, $mapping);

        return response()->json([
            'ok' => true,
            'mapping' => $mapping,
            'people' => collect($plan['rows'])->sortBy(fn ($row) => $row['error'] === null ? 1 : 0)->take(50)->values()->all(),
            'total' => count($plan['rows']),
            'ready' => $plan['ready'],
            'skipped' => $plan['skipped'],
            'unknown_departments' => $plan['unknown_departments'],
        ]);
    }

    public function importReporters(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
            'mapping' => 'required|string',
        ]);

        $mapping = json_decode($request->mapping, true);

        if (! is_array($mapping)) {
            return back()->with('error', 'Column mapping is invalid.');
        }

        $file = $request->file('file');
        $rows = ReporterImport::parseFile($file->getRealPath(), $file->getClientOriginalName());

        if (count($rows) < 2) {
            return back()->with('error', 'The file needs a header row and at least one reporter.');
        }

        $plan = ReporterImport::plan($rows, $mapping);
        $hasType = ReporterImport::hasTypeColumn();
        $hasNames = ReporterImport::hasNameColumns();
        $hasDepartment = ReporterImport::hasDepartmentColumn();
        $now = now();

        $autoLinked = 0;

        $payloads = collect($plan['rows'])
            ->whereNull('error')
            ->map(function (array $row) use ($hasType, $hasNames, $hasDepartment, $now, &$autoLinked) {
                $payload = [
                    'reporter_employee_id' => $row['employee_id'],
                    'reporter_full_name' => ReporterImport::composeFullName($row['first_name'], $row['middle_name'], $row['last_name']),
                    'reporter_email_address' => $row['email_address'] !== '' ? $row['email_address'] : null,
                    'reporter_contact_number' => $row['contact_number'] !== '' ? $row['contact_number'] : null,
                    'reporter_status' => 'Active',
                    'reporter_created_at' => $now,
                ];

                if ($hasType) {
                    $payload['reporter_employment_type'] = $row['type'];
                }

                if ($hasNames) {
                    $payload['reporter_first_name'] = $row['first_name'];
                    $payload['reporter_middle_name'] = $row['middle_name'] !== '' ? $row['middle_name'] : null;
                    $payload['reporter_last_name'] = $row['last_name'];
                }

                if ($hasDepartment) {
                    $deptId = $row['department_id'] ?? null;

                    // If department is not provided in the CSV, attempt to infer it from existing custodian
                    // linked to this employee ID. This helps auto-link reporters when the person already
                    // exists in the directory with a department assignment.
                    if ($deptId === null && trim((string) $row['employee_id']) !== '') {
                        try {
                            $custodianId = Custodians::idForEmployee($row['employee_id']);
                        } catch (\Throwable $e) {
                            $custodianId = null;
                        }

                        if ($custodianId) {
                            $cust = Custodians::find((int) $custodianId);
                            if ($cust && ! empty($cust->custodian_department_id)) {
                                $deptId = (int) $cust->custodian_department_id;
                                $autoLinked++;
                            }
                        }
                    }

                    $payload['reporter_department_id'] = $deptId;
                }

                return $payload;
            })
            ->values();

        DB::transaction(function () use ($payloads) {
            foreach ($payloads->chunk(200) as $chunk) {
                DB::table('reporters_table')->insert($chunk->values()->all());
            }
        });

        $errors = collect($plan['rows'])
            ->whereNotNull('error')
            ->map(fn ($row) => 'Row '.$row['line'].': '.$row['error'])
            ->values()
            ->all();

        return back()->with('import_result', [
            'created' => $payloads->count(),
            'skipped' => $plan['skipped'],
            'errors' => array_slice($errors, 0, 20),
            'unknown_departments' => $plan['unknown_departments'],
            'auto_linked' => $autoLinked,
        ]);
    }

    /**
     * Admin report listing reporter ↔ custodian conflicts by employee ID.
     */
    public function reporterCustodianConflicts()
    {
        if (! Schema::hasTable('reporters_table') || ! Schema::hasTable('custodians_table')) {
            $conflicts = collect();
        } else {
            $conflicts = DB::table('reporters_table as r')
                ->join('custodians_table as c', function ($join) {
                    $join->on('r.reporter_employee_id', '=', 'c.custodian_employee_id');
                })
                ->select(
                    'r.reporter_employee_id as employee_id',
                    'r.reporter_full_name as reporter_name',
                    'c.custodian_full_name as custodian_name',
                    'r.reporter_email_address as reporter_email',
                    'c.custodian_email_address as custodian_email',
                    'r.reporter_contact_number as reporter_contact',
                    'c.custodian_contact_number as custodian_contact'
                )
                ->where(function ($q) {
                    $q->whereRaw('COALESCE(NULLIF(TRIM(r.reporter_full_name), \'\'), \'__NULL__\') != COALESCE(NULLIF(TRIM(c.custodian_full_name), \'\'), \'__NULL__\')')
                      ->orWhereRaw('COALESCE(NULLIF(TRIM(r.reporter_email_address), \'\'), \'__NULL__\') != COALESCE(NULLIF(TRIM(c.custodian_email_address), \'\'), \'__NULL__\')')
                      ->orWhereRaw('COALESCE(NULLIF(TRIM(r.reporter_contact_number), \'\'), \'__NULL__\') != COALESCE(NULLIF(TRIM(c.custodian_contact_number), \'\'), \'__NULL__\')');
                })
                ->orderBy('r.reporter_employee_id')
                ->get();
        }

        return view('admin.reporters.conflicts', ['conflicts' => $conflicts]);
    }
}
