<?php

namespace App\Http\Controllers;

use App\Support\ReportGrouping;
use App\Support\ReporterApprovals;
use App\Support\ReporterImport;
use App\Support\RoomCategories;
use App\Support\RoomName;
use App\Support\SuggestedIssues;
use App\Support\EquipmentQrCodes;
use App\Models\RoomActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

        $overdueBorrowings = DB::table('borrowing_records_table')
            ->where('borrowing_status', 'Overdue')
            ->count();


        $overdueMaintenance = DB::table('maintenance_schedules_table')
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
            })
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
        // MARK OPEN REPORTS AS OVERDUE
        // Submitted before today and still Pending / Processing
        // =====================================================

        $closedReportStatuses = [
            'Resolved',
            'Rejected',
            'For Replacement',
        ];

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
        // Non-urgent: remind from preferred date, or after 5 days if none
        // =====================================================

        $urgentReportsNeedingAction = DB::table('reports_table')
            ->where('report_urgency_level', 'Urgent')
            ->where('report_is_archived', false)
            ->where(function ($query) {
                $query
                    ->where('report_current_status', 'Pending')
                    ->orWhere(function ($overdue) {
                        $overdue
                            ->whereIn(
                                'report_current_status',
                                ['Pending', 'Processing']
                            )
                            ->where(function ($due) {
                                $due
                                    ->where('report_is_overdue', true)
                                    ->orWhereDate(
                                        'report_submitted_at',
                                        '<',
                                        today()
                                    );
                            });
                    });
            })
            ->count();

        $nonUrgentReportsNeedingAction = ReportGrouping::applyNonUrgentReminderWindow(
            DB::table('reports_table')
                ->where('report_urgency_level', 'Non-Urgent')
                ->where('report_is_archived', false)
                ->where('report_current_status', 'Pending')
        )->count();


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

        // Borrow modal: borrowable Active equipment with remaining availability (matches Borrowing module)
        $onLoanByEquipment = DB::table('borrowing_records_table')
            ->select(
                'borrowing_equipment_id',
                DB::raw('COALESCE(SUM(borrowing_quantity), 0) as on_loan_qty')
            )
            ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
            ->groupBy('borrowing_equipment_id')
            ->pluck('on_loan_qty', 'borrowing_equipment_id');

        $borrowableEquipmentJson = DB::table('equipment_table')
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
                $available = max(0, $stock - $onLoan);
                return [
                    'id' => (int) $item->equipment_id,
                    'name' => (string) $item->equipment_name,
                    'room' => (string) ($item->room_name ?? ''),
                    'assetTag' => (string) ($item->equipment_asset_tag ?? ''),
                    'tracking' => (string) ($item->equipment_tracking_mode ?? 'Individual'),
                    'stock' => $stock,
                    'available' => $available,
                ];
            })
            ->filter(fn ($item) => $item['available'] > 0)
            ->values();


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
                        url('/maintenance/borrowing'),

                    'rooms_table' =>
                        url('/maintenance/infrastructure'),

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
                        url('/maintenance/borrowing'),

                    'rooms_table' =>
                        url('/maintenance/infrastructure'),

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

                'urgentReportsNeedingAction',

                'nonUrgentReportsNeedingAction',

                'urgentReports',

                'totalEquipment',

                'underMaintenance',

                'borrowedEquipment',

                'overdueBorrowings',

                'overdueMaintenance',


                // =================================================
                // EXISTING DASHBOARD DATA
                // =================================================

                'urgentReportList',

                'buildings',

                'floors',

                'roomsByFloor',

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

                'borrowableEquipmentJson',

                'usedAssetTags'

            )

        );
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

        ReportGrouping::syncOpenSiblings($report, $updates);
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

                    // One card/row per equipment+room group, including
                    // Resolved and For Replacement — not one card per report.
                    $query->where(function ($groupQuery) {
                        $groupQuery
                            ->whereNull('reports_table.report_equipment_id')
                            ->orWhereNotIn(
                                'reports_table.report_current_status',
                                ReportGrouping::groupedStatuses()
                            )
                            ->orWhereRaw(
                                'reports_table.report_id = (
                                    SELECT MAX(duplicate_reports.report_id)
                                    FROM reports_table AS duplicate_reports
                                    WHERE duplicate_reports.report_equipment_id = reports_table.report_equipment_id
                                      AND duplicate_reports.report_room_id = reports_table.report_room_id
                                      AND duplicate_reports.report_is_archived = 0
                                      AND duplicate_reports.report_current_status IN (?, ?, ?, ?)
                                      AND '.ReportGrouping::groupBucketSql('duplicate_reports').'
                                        = '.ReportGrouping::groupBucketSql('reports_table').'
                                )',
                                ReportGrouping::groupedStatuses()
                            );
                    });
                }
            )

            ->leftJoin(
                DB::raw('(
                    SELECT
                        report_equipment_id,
                        report_room_id,
                        CASE
                            WHEN report_current_status IN (\'Pending\', \'Processing\') THEN \'open\'
                            WHEN report_current_status = \'Resolved\' THEN \'resolved\'
                            WHEN report_current_status = \'For Replacement\' THEN \'replacement\'
                            ELSE report_current_status
                        END AS report_group_bucket,
                        COUNT(*) AS open_count,
                        MAX(CASE WHEN report_urgency_level = \'Urgent\' THEN 1 ELSE 0 END) AS has_urgent
                    FROM reports_table
                    WHERE report_equipment_id IS NOT NULL
                      AND report_is_archived = 0
                      AND report_current_status IN (\'Pending\', \'Processing\', \'Resolved\', \'For Replacement\')
                    GROUP BY
                        report_equipment_id,
                        report_room_id,
                        CASE
                            WHEN report_current_status IN (\'Pending\', \'Processing\') THEN \'open\'
                            WHEN report_current_status = \'Resolved\' THEN \'resolved\'
                            WHEN report_current_status = \'For Replacement\' THEN \'replacement\'
                            ELSE report_current_status
                        END
                ) AS open_report_group'),
                function ($join) {
                    $join
                        ->on(
                            'open_report_group.report_equipment_id',
                            '=',
                            'reports_table.report_equipment_id'
                        )
                        ->on(
                            'open_report_group.report_room_id',
                            '=',
                            'reports_table.report_room_id'
                        )
                        ->whereRaw(
                            'open_report_group.report_group_bucket = '.ReportGrouping::groupBucketSql('reports_table')
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

                DB::raw('COALESCE(open_report_group.open_count, 1) as grouped_report_count'),

                DB::raw("CASE WHEN open_report_group.has_urgent = 1 THEN 'Urgent' ELSE reports_table.report_urgency_level END as grouped_urgency")

            );
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

    private function reportsView($reports, bool $showReportStats = false)
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

        return view(
            'maintenance-personnel.reports.all-reports',

            array_merge(
                [
                    'reports' => $reports,
                    'allReports' => $showReportStats,
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

                'reporters_table.reporter_contact_number'

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


        /*
        |--------------------------------------------------------------------------
        | RETURN REPORT DETAILS VIEW
        |--------------------------------------------------------------------------
        */

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

        $equipmentForReplacement = null;

        $response = DB::transaction(function () use (

            $request,

            $id,

            $personnelId,

            $newStatus,

            $undoRequested,

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

                $this->applyReportStatusUpdate($id, $report, [
                    'report_current_status' => 'Pending',
                    'report_assigned_personnel_id' => null,
                    'report_updated_at' => now(),
                ]);

                    $this->logActivity(
                        'Returned report to pending',
                        'Reports',
                        'reports_table',
                        (int) $id,
                        'Returned Report #' . $id . ' from Processing to Pending.'
                    );


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

                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'For Replacement',
                        'report_replacement_notes' => $request->remarks,
                        'report_replacement_image' => $imagePath,
                        'report_replacement_submitted_to_purchaser' => 1,
                        'report_updated_at' => now(),
                    ]);

                    if (!empty($report->report_equipment_id)) {
                        $equipmentForReplacement = (int) $report->report_equipment_id;
                    }

                        $this->logActivity(
                            'Submitted report for replacement',
                            'Reports',
                            'reports_table',
                            (int) $id,
                            'Submitted Report #' . $id . ' for equipment replacement.'
                        );


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

        if (!empty($equipmentForReplacement)) {
            ReportGrouping::markEquipmentForReplacement(
                (int) $equipmentForReplacement
            );
        }

        return $response;
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

        $equipment = $query

            ->orderBy(
                'equipment_table.equipment_created_at',
                'desc'
            )

            ->paginate(10)

            ->withQueryString();


        $usedAssetTags = DB::table('equipment_table')
            ->whereNotNull('equipment_asset_tag')
            ->where('equipment_asset_tag', '!=', '')
            ->whereNotIn('equipment_inventory_status', ['Disposed'])
            ->pluck('equipment_asset_tag');

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
                'usedAssetTags',

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
            });
        }

        if ($request->filled('building')) {
            $query->where('buildings_table.building_id', $request->building);
        }

        $rooms = $query
            ->orderBy($orderBy, $dir)
            ->paginate(10)
            ->withQueryString();

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
            ->whereIn('room_status', ['Maintenance Needed', 'Critical'])
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
        $roomActivityHistory = collect();
        $roomReportStats = [
            'today' => 0,
            'week' => 0,
            'month' => 0,
            'year' => 0,
            'resolved' => 0,
            'for_replacement' => 0,
            'active' => 0,
        ];
        $roomEquipmentStats = [
            'for_replacement' => 0,
            'for_replacement_qty' => 0,
            'total_qty' => 0,
        ];

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
                    ->route('maintenance.rooms.index')
                    ->with('error', 'Room not found.');
            }

            $roomId = (int) $historyRoom->room_id;

            $historyPeriod = $request->get('history_period', '');
            $periodStarts = [
                'today' => now()->copy()->startOfDay(),
                'week' => now()->copy()->startOfWeek(),
                'month' => now()->copy()->startOfMonth(),
                'year' => now()->copy()->startOfYear(),
            ];
            $periodStart = $periodStarts[$historyPeriod] ?? null;

            $historyFrom = null;
            $historyTo = null;
            $historyMonth = null;

            try {
                // Single calendar (YYYY-MM) — filter that whole month of any year.
                if ($request->filled('history_month')) {
                    $historyMonth = \Carbon\Carbon::createFromFormat('Y-m', $request->history_month)->startOfMonth();
                    $historyFrom = $historyMonth->copy()->startOfMonth();
                    $historyTo = $historyMonth->copy()->endOfMonth();
                    $periodStart = null;
                    $historyPeriod = '';
                } elseif ($request->filled('history_date')) {
                    // Backward-compatible single day if ever passed.
                    $day = \Carbon\Carbon::parse($request->history_date);
                    $historyFrom = $day->copy()->startOfDay();
                    $historyTo = $day->copy()->endOfDay();
                    $periodStart = null;
                    $historyPeriod = '';
                }
            } catch (\Throwable $e) {
                $historyFrom = null;
                $historyTo = null;
                $historyMonth = null;
            }

            $applyHistoryDateFilter = function ($query, string $column) use ($historyFrom, $historyTo, $periodStart) {
                if ($historyFrom) {
                    $query->where($column, '>=', $historyFrom);
                }
                if ($historyTo) {
                    $query->where($column, '<=', $historyTo);
                } elseif ($periodStart) {
                    $query->where($column, '>=', $periodStart);
                }

                return $query;
            };

            $reportsBase = DB::table('reports_table')
                ->where('report_room_id', $roomId)
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($q) => $q->where('report_is_archived', false)
                );

            $roomReportStats['today'] = (clone $reportsBase)
                ->whereDate('report_submitted_at', now()->toDateString())
                ->count();

            $roomReportStats['week'] = (clone $reportsBase)
                ->where('report_submitted_at', '>=', now()->copy()->startOfWeek())
                ->count();

            $roomReportStats['month'] = (clone $reportsBase)
                ->where('report_submitted_at', '>=', now()->copy()->startOfMonth())
                ->count();

            $roomReportStats['year'] = (clone $reportsBase)
                ->where('report_submitted_at', '>=', now()->copy()->startOfYear())
                ->count();

            $periodReportsBase = $applyHistoryDateFilter(clone $reportsBase, 'report_submitted_at');

            $roomReportStats['resolved'] = (clone $periodReportsBase)
                ->where('report_current_status', 'Resolved')
                ->count();

            $roomReportStats['for_replacement'] = (clone $periodReportsBase)
                ->where('report_current_status', 'For Replacement')
                ->count();

            $roomReportStats['active'] = (clone $periodReportsBase)
                ->whereNotIn('report_current_status', ['Resolved', 'Rejected'])
                ->count();

            $equipmentBase = DB::table('equipment_table')
                ->where('equipment_room_id', $roomId);

            $roomEquipmentStats['for_replacement'] = (clone $equipmentBase)
                ->where('equipment_inventory_status', 'For Replacement')
                ->count();

            $roomEquipmentStats['for_replacement_qty'] = (int) (clone $equipmentBase)
                ->where('equipment_inventory_status', 'For Replacement')
                ->sum('equipment_quantity');

            $roomEquipmentStats['total_qty'] = (int) (clone $equipmentBase)
                ->sum('equipment_quantity');

            $showEquipmentForReplacement = $request->get('history_focus') === 'equipment_for_replacement'
                || $request->get('history_status') === 'For Replacement';

            $roomEquipmentList = collect();
            if ($showEquipmentForReplacement || $request->get('history_focus') === 'equipment') {
                $roomEquipmentList = DB::table('equipment_table')
                    ->leftJoin(
                        'equipment_categories_table',
                        'equipment_table.equipment_category_id',
                        '=',
                        'equipment_categories_table.equipment_category_id'
                    )
                    ->where('equipment_table.equipment_room_id', $roomId)
                    ->when(
                        $showEquipmentForReplacement,
                        fn ($q) => $q->where('equipment_table.equipment_inventory_status', 'For Replacement')
                    )
                    ->select(
                        'equipment_table.equipment_id',
                        'equipment_table.equipment_name',
                        'equipment_table.equipment_quantity',
                        'equipment_table.equipment_inventory_status',
                        'equipment_table.equipment_condition_status',
                        'equipment_categories_table.equipment_category_name'
                    )
                    ->orderBy('equipment_table.equipment_name')
                    ->get();
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
                ->where('reports_table.report_room_id', $roomId)
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($q) => $q->where('reports_table.report_is_archived', false)
                )
                ->select(
                    'reports_table.*',
                    'equipment_table.equipment_name',
                    'reporters_table.reporter_full_name'
                );

            $applyHistoryDateFilter($historyQuery, 'reports_table.report_submitted_at');

            if ($request->filled('history_search')) {
                $historySearch = trim($request->history_search);
                $historyQuery->where(function ($query) use ($historySearch) {
                    $query->whereRaw(
                        'CAST(reports_table.report_id AS CHAR) LIKE ?',
                        ['%'.$historySearch.'%']
                    )
                        ->orWhere('equipment_table.equipment_name', 'like', '%'.$historySearch.'%')
                        ->orWhere('reports_table.report_unlisted_equipment_name', 'like', '%'.$historySearch.'%')
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

            if (Schema::hasTable('room_activity_logs_table')) {
                $activityQuery = DB::table('room_activity_logs_table')
                    ->where('room_id', $roomId);
                $applyHistoryDateFilter($activityQuery, 'created_at');
                $roomActivityHistory = $activityQuery
                    ->orderByDesc('created_at')
                    ->paginate(15, ['*'], 'activity_page')
                    ->withQueryString();
            }
        } else {
            $roomEquipmentList = collect();
            $historyPeriod = '';
            $historyFrom = null;
            $historyTo = null;
            $historyMonth = null;
        }

        return view(
            'maintenance-personnel.rooms.index',
            compact(
                'rooms',
                'buildings',
                'floors',
                'roomTypes',
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
                'roomActivityHistory',
                'roomReportStats',
                'roomEquipmentStats',
                'roomEquipmentList',
                'historyPeriod'
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
                ]],
            ]);
        }

        $validated = $request->validate([
            'rooms' => ['required', 'array', 'min:1', 'max:50'],
            'rooms.*.room_name' => ['required', 'string', 'max:255'],
            'rooms.*.room_floor_id' => ['required', 'exists:floors_table,floor_id'],
            'rooms.*.room_type' => ['nullable', 'in:'.implode(',', $roomTypes)],
            'rooms.*.room_status' => ['nullable', 'in:Normal,Maintenance Needed,Critical'],
        ]);

        $rows = collect($validated['rooms'])
            ->map(function ($row) {
                return [
                    'room_name' => trim($row['room_name']),
                    'room_floor_id' => (int) $row['room_floor_id'],
                    'room_type' => ($row['room_type'] ?? null) ?: 'Lecture Room',
                    'room_status' => ($row['room_status'] ?? null) ?: 'Normal',
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

                DB::table('rooms_table')->insert($values);
            }
        });

        $count = $rows->count();

        return redirect()
            ->route('maintenance.rooms.index')
            ->with(
                'success',
                $count === 1 ? 'Room added.' : "{$count} rooms added."
            );
    }

    public function updateDirectoryRoom(Request $request, $id)
    {
        $roomTypes = RoomCategories::values();
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'room_floor_id' => ['required', 'exists:floors_table,floor_id'],
            'room_type' => ['nullable', 'in:'.implode(',', $roomTypes)],
            'room_status' => ['nullable', 'in:Normal,Maintenance Needed,Critical'],
        ]);

        $room = DB::table('rooms_table')->where('room_id', $id)->first();
        if (! $room) {
            return back()->withErrors(['room_name' => 'Room not found.']);
        }

        $matched = $this->matchingRoomName($validated['room_name'], (int) $id);
        if ($matched) {
            return back()
                ->withErrors([
                    'room_name' => '“'.$validated['room_name'].'” is the same room as “'.$matched->room_name.'”.',
                ])
                ->withInput();
        }

        $values = [
            'room_name' => trim($validated['room_name']),
            'room_floor_id' => $validated['room_floor_id'],
            'room_type' => $validated['room_type'] ?: $room->room_type,
            'room_status' => $validated['room_status'] ?: ($room->room_status ?? 'Normal'),
        ];
        if (Schema::hasColumn('rooms_table', 'room_updated_at')) {
            $values['room_updated_at'] = now();
        }
        if (Schema::hasColumn('rooms_table', 'updated_at')) {
            $values['updated_at'] = now();
        }

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

        DB::table('rooms_table')->where('room_id', $id)->update($values);

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

        return redirect()->route('maintenance.rooms.index')->with('success', 'Room updated.');
    }

    public function archiveDirectoryRoom(Request $request, $id)
    {
        return redirect()
            ->route('maintenance.rooms.index')
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
            ->route('maintenance.rooms.index')
            ->with('success', $merged > 0 ? "Merged {$merged} duplicate room(s)." : 'No duplicate rooms found.');
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
            ->select(
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_quantity',
                'equipment_table.equipment_inventory_status',
                'equipment_categories_table.equipment_category_name'
            )
            ->get();

        return response()->json([
            'room' => $room->room_name,
            'count' => $items->count(),
            'items' => $items,
            'inventory_url' => url('/maintenance/equipment/inventory').'?room='.$id,
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

        return view(
            'maintenance-personnel.equipment.suggested-issues',
            compact(
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
        ]);

        $payload = [
            'issue_template_category_id' => $validated['issue_template_category_id'],
            'issue_template_name' => trim($validated['issue_template_name']),
            'issue_template_created_at' => now(),
        ];

        if (Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            $payload['issue_template_component'] = $validated['issue_template_component'] ?: null;
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
        ]);

        $payload = [
            'issue_template_category_id' => $validated['issue_template_category_id'],
            'issue_template_name' => trim($validated['issue_template_name']),
        ];

        if (Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            $payload['issue_template_component'] = $validated['issue_template_component'] ?: null;
        }

        DB::table('issue_templates_table')
            ->where('issue_template_id', $id)
            ->update($payload);

        return back()->with('success', 'Suggested issue updated.');
    }

    public function deleteSuggestedIssue($id)
    {
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
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name',
                DB::raw('COUNT(reports_table.report_id) as report_count'),
                DB::raw('MAX(reports_table.report_submitted_at) as last_reported_at')
            )
            ->groupBy(
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_condition_status',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name'
            );

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('equipment_table.equipment_name', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('rooms_table.room_name', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('equipment_categories_table.equipment_category_name', 'LIKE', '%'.$request->search.'%');
            });
        }

        $equipment = $query
            ->orderByDesc('last_reported_at')
            ->orderBy('equipment_table.equipment_name')
            ->paginate(12)
            ->withQueryString();

        $historyByEquipment = collect();
        $ids = $equipment->getCollection()->pluck('equipment_id')->filter();

        if ($ids->isNotEmpty()) {
            $historyByEquipment = DB::table('reports_table')
                ->leftJoin(
                    'reporters_table',
                    'reports_table.report_reporter_employee_id',
                    '=',
                    'reporters_table.reporter_employee_id'
                )
                ->whereIn('reports_table.report_equipment_id', $ids)
                ->orderByDesc('reports_table.report_submitted_at')
                ->select(
                    'reports_table.report_id',
                    'reports_table.report_equipment_id',
                    'reports_table.report_current_status',
                    'reports_table.report_urgency_level',
                    'reports_table.report_suggested_issue',
                    'reports_table.report_submitted_at',
                    'reporters_table.reporter_full_name'
                )
                ->get()
                ->groupBy('report_equipment_id');
        }

        $equipment->getCollection()->transform(function ($item) use ($historyByEquipment) {
            $item->history = $historyByEquipment->get($item->equipment_id, collect());
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

        return view(
            'maintenance-personnel.equipment.history',
            compact(
                'equipment',
                'totalEquipment',
                'equipmentWithReports',
                'equipmentWithReportsPercentage',
                'totalReports',
                'openReports',
                'openReportsPercentage',
                'reportsMonthlyPercentage',
                'reportsMonthlyTrend'
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
        // =====================================================
        // VALIDATE EQUIPMENT
        // =====================================================

        $validated = $request->validate([

            'equipment_name' => 'required|string|max:255',

            'equipment_category_id' => 'required',

            'equipment_room_id' => 'required',

            'equipment_quantity' => 'required|integer|min:1|max:200',

            'equipment_tracking_mode' => 'nullable|in:Bulk,Individual',

            'equipment_condition_status' => 'nullable|string',

            'equipment_inventory_status' => 'nullable|string',

            'equipment_asset_tag' => 'nullable|string|max:255',

            'equipment_brand_name' => 'nullable|string|max:255',

            'equipment_model' => 'nullable|string|max:255',

            'equipment_serial_number' => 'nullable|string|max:255',

            'equipment_purchase_date' => 'nullable|date',

            'equipment_warranty_expiration' => 'nullable|date',

            'items' => 'nullable|array|max:200',

            'items.*.equipment_asset_tag' => 'nullable|string|max:255',

            'items.*.equipment_serial_number' => 'nullable|string|max:255',

            'items.*.equipment_brand_name' => 'nullable|string|max:255',

            'items.*.equipment_model' => 'nullable|string|max:255',

            'items.*.equipment_condition_status' => 'nullable|string',

            'items.*.equipment_warranty_expiration' => 'nullable|date',

            'equipment_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',

            'items.*.equipment_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',

        ]);

        $trackingMode = $validated['equipment_tracking_mode'] ?? 'Individual';
        $items = array_values($validated['items'] ?? []);
        $quantity = (int) $validated['equipment_quantity'];

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

        $createdIds = [];

        // Shared photo applies to Bulk / single Individual units only.
        // Multi Individual units use optional per-row images on step 2.
        $useSharedImage = !($trackingMode === 'Individual' && $quantity > 1);
        $imagePath = $useSharedImage
            ? $this->storeOptionalEquipmentImage($request)
            : null;

        // =====================================================
        // START TRANSACTION
        // =====================================================

        DB::transaction(function () use (
            $request,
            $validated,
            $trackingMode,
            $items,
            $quantity,
            $imagePath,
            $useSharedImage,
            &$createdIds
        ) {

            $borrowable = $request->has('equipment_is_borrowable');

            if ($trackingMode === 'Bulk') {
                $equipmentId = DB::table('equipment_table')->insertGetId([
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
                    'equipment_purchase_date' => $validated['equipment_purchase_date'] ?? null,
                    'equipment_warranty_expiration' => $validated['equipment_warranty_expiration'] ?? null,
                    'equipment_is_borrowable' => $borrowable,
                    'equipment_image' => $imagePath,
                    'equipment_created_at' => now(),
                ]);

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

                    $equipmentId = DB::table('equipment_table')->insertGetId([
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
                        'equipment_purchase_date' => $validated['equipment_purchase_date'] ?? null,
                        'equipment_warranty_expiration' => $item['equipment_warranty_expiration']
                            ?? ($validated['equipment_warranty_expiration'] ?? null),
                        'equipment_is_borrowable' => $borrowable,
                        'equipment_image' => $rowImagePath,
                        'equipment_created_at' => now(),
                    ]);

                    EquipmentQrCodes::assignIfEligible((int) $equipmentId);
                    $createdIds[] = (int) $equipmentId;
                }
            }

            $count = count($createdIds);
            $lastId = (int) end($createdIds);

            $this->logActivity(
                'Added equipment',
                'Equipment',
                'equipment_table',
                $lastId,
                $count > 1
                    ? 'Added '.$count.' × '.$validated['equipment_name'].' to the equipment inventory.'
                    : 'Added '.$validated['equipment_name'].' to the equipment inventory.'
            );

        });

        // =====================================================
        // SUCCESS
        // =====================================================

        $count = count($createdIds);

        return redirect(
            '/maintenance/equipment/inventory'
        )->with(
            'success',
            $count > 1
                ? "{$count} equipment records added successfully."
                : 'Equipment added successfully.'
        );
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

        $duplicateName = DB::table('equipment_table')
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

        $request->validate([
            'equipment_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'remove_equipment_image' => 'nullable',
        ]);

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
            $imagePath
        ) {

            // =================================================
            // UPDATE EQUIPMENT
            // =================================================

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

                    'equipment_warranty_expiration'
                        => $request->equipment_warranty_expiration,

                    'equipment_is_borrowable'
                        => $request->has('equipment_is_borrowable'),

                    'equipment_image'
                        => $imagePath,

                ]);


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
        // =====================================================
        // GET EQUIPMENT
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $request->equipment_id
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


        // =====================================================
        // GET OLD ROOM
        // =====================================================

        $oldRoom = DB::table('rooms_table')

            ->where(
                'room_id',
                $equipment->equipment_room_id
            )

            ->first();


        // =====================================================
        // GET NEW ROOM
        // =====================================================

        $newRoom = DB::table('rooms_table')

            ->where(
                'room_id',
                $request->room_id
            )

            ->first();


        // =====================================================
        // PREVENT TRANSFER TO SAME ROOM
        // =====================================================

        if (
            (int) $equipment->equipment_room_id
            ===
            (int) $request->room_id
        ) {

            return back()->with(
                'error',
                'Equipment is already assigned to this room.'
            );
        }


        // =====================================================
        // START TRANSACTION
        // =====================================================

        DB::transaction(function () use (
            $request,
            $equipment,
            $oldRoom,
            $newRoom
        ) {

            // =================================================
            // CREATE TRANSFER HISTORY
            // =================================================

            $transferId = DB::table(
                'equipment_transfer_history_table'
            )

            ->insertGetId([

                'equipment_id'
                    => $equipment->equipment_id,

                'from_room_id'
                    => $equipment->equipment_room_id,

                'to_room_id'
                    => $request->room_id,

                'remarks'
                    => $request->remarks,

                'created_at'
                    => now(),

            ]);


            // =================================================
            // UPDATE EQUIPMENT LOCATION
            // =================================================

            DB::table('equipment_table')

                ->where(
                    'equipment_id',
                    $equipment->equipment_id
                )

                ->update([

                    'equipment_room_id'
                        => $request->room_id,

                ]);


            // =================================================
            // CREATE TRANSFER NOTIFICATION
            // =================================================

            DB::table('notifications_table')

                ->insertOrIgnore([

                    'notification_user_id'
                        => null,

                    'notification_target_role'
                        => 'Maintenance Personnel',

                    'notification_title'
                        => 'Equipment Transferred',

                    'notification_message'
                        => ($equipment->equipment_name ?? 'Equipment')
                        . ' was transferred from '
                        . ($oldRoom->room_name ?? 'Unassigned Location')
                        . ' to '
                        . ($newRoom->room_name ?? 'Unknown Location')
                        . '.',

                    'notification_type'
                        => 'equipment_transferred',

                    'notification_category'
                        => 'Equipment',

                    'notification_reference_type'
                        => 'equipment_transfer',

                    'notification_reference_id'
                        => $transferId,

                    'notification_url'
                        => '/maintenance/equipment/transfer',

                    'notification_event_key'
                        => 'equipment_transferred_' . $transferId,

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
                    => 'Transferred equipment',

                'audit_log_module'
                    => 'Equipment',

                'audit_log_table_name'
                    => 'equipment_table',

                'audit_log_reference_id'
                    => $equipment->equipment_id,

                'audit_log_description'
                    => 'Transferred '
                    . ($equipment->equipment_name ?? 'equipment')
                    . ' from '
                    . ($oldRoom->room_name ?? 'Unassigned Location')
                    . ' to '
                    . ($newRoom->room_name ?? 'Unknown Location')
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
            'maintenance-personnel.borrowing.index',

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
                        'notification_target_role' => 'Maintenance Personnel',
                        'notification_title' => 'Equipment Borrowed',
                        'notification_message' => ($equipment->equipment_name ?? 'Equipment')
                            .' (x'.((int) $line['quantity']).') was borrowed by '
                            .$validated['borrowing_borrower_name'].'.',
                        'notification_type' => 'equipment_borrowed',
                        'notification_category' => 'Equipment',
                        'notification_reference_type' => 'borrowing_record',
                        'notification_reference_id' => $borrowingId,
                        'notification_url' => '/maintenance/borrowing',
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


        // =====================================================
        // START TRANSACTION
        // =====================================================

        DB::transaction(function () use (
            $request,
            $record,
            $equipment
        ) {

            // =================================================
            // UPDATE BORROWING RECORD
            // =================================================

            DB::table('borrowing_records_table')

                ->where(
                    'borrowing_record_id',
                    $record->borrowing_record_id
                )

                ->update([

                    'borrowing_status'
                        => 'Returned',

                    'borrowing_actual_return_date'
                        => now(),

                ]);


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
                        => 'Maintenance Personnel',

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
                        => '/maintenance/borrowing',

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
                'equipment_table.equipment_qr_code',
                'equipment_table.equipment_room_id',
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

            ->paginate(10)

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


        // =====================================================
        // PREVENT COMPLETING SAME SCHEDULE TWICE
        // =====================================================

        if ($schedule->maintenance_schedule_status === 'Completed') {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'This maintenance schedule has already been completed.'
                );
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

            ->insertGetId([

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

                'equipment_maintenance_completed_at'
                    => now(),

                'equipment_maintenance_created_at'
                    => now(),

                'equipment_maintenance_proof_image'
                    => $proofImage,

            ]);


            // =================================================
            // MARK SCHEDULE AS COMPLETED
            // =================================================

            DB::table('maintenance_schedules_table')

                ->where(
                    'maintenance_schedule_id',
                    $schedule->maintenance_schedule_id
                )

                ->update([

                    'maintenance_schedule_status'
                        => 'Completed',

                    'maintenance_schedule_last_date'
                        => now(),

                ]);


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
                'equipment_table.equipment_inventory_status',
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
        $request->validate([
            'equipment_id' => 'required|integer',
            'reason' => 'required|string|max:1000',
            'location' => 'nullable|string|max:255',
        ]);

        // =====================================================
        // GET EQUIPMENT INFORMATION
        // USED FOR RECENT ACTIVITY DESCRIPTION
        // =====================================================

        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $request->equipment_id
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

        if (($equipment->equipment_inventory_status ?? '') === 'Disposed') {
            return back()->with(
                'error',
                'This equipment is already disposed.'
            );
        }


        // =====================================================
        // START TRANSACTION
        // =====================================================

        DB::transaction(function () use (
            $request,
            $equipment
        ) {

            $existingDisposal = DB::table('disposal_records_table')
                ->where('disposal_equipment_id', $request->equipment_id)
                ->orderByDesc('disposal_record_id')
                ->first();

            if ($existingDisposal) {
                $disposalId = (int) $existingDisposal->disposal_record_id;

                DB::table('disposal_records_table')
                    ->where('disposal_record_id', $disposalId)
                    ->update([
                        'disposal_reason' => $request->reason,
                        'disposal_area_location' => $request->location,
                        'disposal_approved_by' => Auth::id(),
                        'disposal_disposed_at' => now(),
                    ]);
            } else {
                $disposalId = DB::table(
                    'disposal_records_table'
                )

                ->insertGetId([

                    'disposal_equipment_id'
                        => $request->equipment_id,

                    'disposal_reason'
                        => $request->reason,

                    'disposal_area_location'
                        => $request->location,

                    'disposal_approved_by'
                        => Auth::id(),

                    'disposal_disposed_at'
                        => now(),

                ]);
            }


            // =================================================
            // UPDATE EQUIPMENT STATUS
            // =================================================

            DB::table('equipment_table')

                ->where(
                    'equipment_id',
                    $request->equipment_id
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
                . ($request->reason ?? 'No reason provided')
                . '.'

            );

        });


        // =====================================================
        // SUCCESS
        // =====================================================

        return back()->with(
            'success',
            'Equipment moved to Disposal. It can still be restored until you finalize with Dispose.'
        );
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

        $pendingReporterApprovals = ReporterApprovals::pendingCount();

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
            'pendingReporterApprovals'
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
                'report_preferred_action_date' => ReportGrouping::preferredActionDateRules(),
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

            if (
                !$request->filled('report_suggested_issue')
                && !$request->filled('report_problem_description')
            ) {
                return back()
                    ->withErrors([
                        'report_suggested_issue'
                            => 'Please select a suggested issue or provide additional details.',
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
            // MERGE INTO OPEN REPORT FOR THE SAME EQUIPMENT
            // =====================================================

            if ($request->filled('report_equipment_id')) {
                if (ReportGrouping::equipmentIsForReplacement((int) $request->report_equipment_id)) {
                    return back()
                        ->withErrors([
                            'report_equipment_id' =>
                                'This equipment is already marked for replacement and cannot be reported again.',
                        ])
                        ->withInput();
                }

                $openReport = ReportGrouping::findOpenReport(
                    (int) $request->report_equipment_id,
                    (int) $request->report_room_id
                );

                if ($openReport) {
                    ReportGrouping::mergeIntoOpenReport($openReport, [
                        'reporter_id' => $reporter->reporter_employee_id,
                        'urgency' => $request->report_urgency_level,
                        'preferred_action_date' => ReportGrouping::resolvePreferredActionDate(
                            $request->report_urgency_level,
                            $request->report_preferred_action_date
                        ),
                        'issue' => $request->report_suggested_issue
                            ?: $request->report_problem_description,
                    ]);

                    return back()->with(
                        'success',
                        'This equipment already has an open report. Your report was added to it instead of creating a duplicate.'
                    );
                }
            }

            // =====================================================
            // INSERT REPORT
            // CHANGED FROM insert() TO insertGetId()
            // THIS GIVES US THE NEW REPORT ID
            // =====================================================

            $reportPayload = [

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

            ];

            if (ReportGrouping::hasPreferredActionDateColumn()) {
                $reportPayload['report_preferred_action_date'] = ReportGrouping::resolvePreferredActionDate(
                    $request->report_urgency_level,
                    $request->report_preferred_action_date
                );
            }

            $reportId = DB::table('reports_table')
                ->insertGetId($reportPayload, 'report_id');


            // =====================================================
            // CHECK IF REPORT IS URGENT
            // =====================================================

            $isUrgent =
                $request->report_urgency_level === 'Urgent';


            // =====================================================
            // CREATE MAINTENANCE PERSONNEL ALERT
            // THIS WILL APPEAR ON THE ALERTS PAGE
            // =====================================================

            DB::table('notifications_table')
                ->insertOrIgnore([

                    // NULL means this is not for one specific user
                    'notification_user_id'
                        => null,

                    // Send this to Maintenance Personnel
                    'notification_target_role'
                        => 'Maintenance Personnel',

                    // Different title depending on urgency
                    'notification_title'
                        => $isUrgent
                            ? 'Urgent Report Requires Attention'
                            : 'New Report Submitted',

                    // Notification description
                    'notification_message'
                        => $isUrgent
                            ? 'Urgent Report #' . $reportId . ' requires immediate attention.'
                            : 'A new maintenance Report #' . $reportId . ' has been submitted.',

                    // Used to identify the notification type
                    'notification_type'
                        => $isUrgent
                            ? 'urgent_report'
                            : 'new_report',

                    // Used by your Alerts page filter
                    'notification_category'
                        => 'Reports',

                    // Tells the system what record this notification belongs to
                    'notification_reference_type'
                        => 'report',

                    // Actual report ID
                    'notification_reference_id'
                        => $reportId,

                    // Destination when notification is opened
                    'notification_url'
                        => '/maintenance/reports/details/' . $reportId,

                    // Prevent duplicate notification for the same submission
                    'notification_event_key'
                        => 'report_submitted_' . $reportId,

                    'notification_created_at'
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
        $request->validate([
            'reporter_id' => 'required',
            'employee_id' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'type' => 'nullable|in:Faculty,Staff',
            'email' => 'nullable|email|max:255',
            'contact' => 'nullable|string|max:50',
        ]);

        $first = trim($request->first_name);
        $middle = trim((string) $request->middle_name);
        $last = trim($request->last_name);

        $payload = [
            'reporter_employee_id' => trim($request->employee_id),
            'reporter_full_name' => ReporterImport::composeFullName($first, $middle, $last),
            'reporter_email_address' => $request->email ?: null,
            'reporter_contact_number' => $request->contact ?: null,
        ];

        if (ReporterImport::hasNameColumns()) {
            $payload['reporter_first_name'] = $first;
            $payload['reporter_middle_name'] = $middle !== '' ? $middle : null;
            $payload['reporter_last_name'] = $last;
        }

        if (ReporterImport::hasTypeColumn()) {
            $payload['reporter_employment_type'] = $request->filled('type') ? trim($request->type) : null;
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
    // REPORTER APPROVALS
    // APPLICATIONS WAITING TO BE CONFIRMED AS FACULTY / STAFF
    // =====================================================

    public function reporterApprovals(Request $request)
    {
        $status = $request->get('status', 'pending');
        $allowedStatuses = [
            'pending',
            'approved',
            'rejected',
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $applications = null;
        $pendingCount = 0;
        $approvedThisMonth = 0;
        $rejectedThisMonth = 0;
        $totalApplications = 0;

        if (ReporterApprovals::hasTable()) {
            $query = ReporterApprovals::query()
                ->leftJoin(
                    'users_table',
                    'users_table.user_id',
                    '=',
                    'reporter_approval_requests.reviewed_by'
                )
                ->select(
                    'reporter_approval_requests.*',
                    'users_table.user_full_name as reviewed_by_name'
                );

            if ($request->filled('search')) {
                $search = trim($request->search);

                $query->where(function ($q) use ($search) {
                    $q->where('reporter_approval_requests.employee_id', 'LIKE', '%'.$search.'%')
                        ->orWhere('reporter_approval_requests.full_name', 'LIKE', '%'.$search.'%')
                        ->orWhere('reporter_approval_requests.email', 'LIKE', '%'.$search.'%')
                        ->orWhere('reporter_approval_requests.contact', 'LIKE', '%'.$search.'%');
                });
            }

            $query->where('reporter_approval_requests.status', $status);

            $applications = $query
                ->orderByDesc('reporter_approval_requests.created_at')
                ->paginate(10, ['*'], 'page')
                ->withQueryString();

            $pendingCount = ReporterApprovals::pendingCount();
            $approvedThisMonth = ReporterApprovals::query()
                ->where('status', ReporterApprovals::STATUS_APPROVED)
                ->whereBetween('reviewed_at', [now()->copy()->startOfMonth(), now()->copy()->endOfMonth()])
                ->count();
            $rejectedThisMonth = ReporterApprovals::query()
                ->where('status', ReporterApprovals::STATUS_REJECTED)
                ->whereBetween('reviewed_at', [now()->copy()->startOfMonth(), now()->copy()->endOfMonth()])
                ->count();
            $totalApplications = ReporterApprovals::query()->count();
        } else {
            $applications = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                10,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('maintenance-personnel.reporters.approvals', [
            'applications' => $applications,
            'status' => $status,
            'pendingCount' => $pendingCount,
            'approvedThisMonth' => $approvedThisMonth,
            'rejectedThisMonth' => $rejectedThisMonth,
            'totalApplications' => $totalApplications,
        ]);
    }

    public function approveReporterApplication(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:Faculty,Staff',
        ]);

        if (! ReporterApprovals::hasTable()) {
            return back()->with('error', 'Reporter approval is not available yet.');
        }

        $application = ReporterApprovals::query()
            ->where('id', $id)
            ->first();

        if (! $application) {
            return back()->with('error', 'Application not found.');
        }

        if ($application->status !== ReporterApprovals::STATUS_PENDING) {
            return back()->with('error', 'This application was already reviewed.');
        }

        $employeeId = trim($application->employee_id);

        $idTaken = DB::table('reporters_table')
            ->where('reporter_employee_id', $employeeId)
            ->exists();

        if ($idTaken) {
            return back()->with('error', 'That employee ID is already in the reporters list.');
        }

        $emailTaken = DB::table('reporters_table')
            ->whereRaw('LOWER(reporter_email_address) = ?', [strtolower($application->email)])
            ->exists();

        if ($emailTaken) {
            return back()->with('error', 'That email is already in the reporters list.');
        }

        $type = trim($request->type);
        $first = trim($application->first_name);
        $middle = trim((string) $application->middle_name);
        $last = trim($application->last_name);

        $payload = [
            'reporter_employee_id' => $employeeId,
            'reporter_full_name' => ReporterImport::composeFullName($first, $middle, $last),
            'reporter_email_address' => $application->email,
            'reporter_contact_number' => $application->contact,
            'reporter_status' => 'Active',
            'reporter_created_at' => now(),
        ];

        if (ReporterImport::hasNameColumns()) {
            $payload['reporter_first_name'] = $first;
            $payload['reporter_middle_name'] = $middle !== '' ? $middle : null;
            $payload['reporter_last_name'] = $last;
        }

        if (ReporterImport::hasTypeColumn()) {
            $payload['reporter_employment_type'] = $type;
        }

        DB::transaction(function () use ($payload, $id, $type) {
            DB::table('reporters_table')->insert($payload);

            ReporterApprovals::query()
                ->where('id', $id)
                ->update([
                    'employment_type' => $type,
                    'status' => ReporterApprovals::STATUS_APPROVED,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return redirect('/maintenance/reporters/approvals')
            ->with('success', $payload['reporter_full_name'].' was confirmed and added to the reporters list.');
    }

    public function rejectReporterApplication(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (! ReporterApprovals::hasTable()) {
            return back()->with('error', 'Reporter approval is not available yet.');
        }

        $application = ReporterApprovals::query()
            ->where('id', $id)
            ->first();

        if (! $application) {
            return back()->with('error', 'Application not found.');
        }

        if ($application->status !== ReporterApprovals::STATUS_PENDING) {
            return back()->with('error', 'This application was already reviewed.');
        }

        $reason = trim((string) $request->reason);

        ReporterApprovals::query()
            ->where('id', $id)
            ->update([
                'status' => ReporterApprovals::STATUS_REJECTED,
                'rejection_reason' => $reason !== '' ? $reason : null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect('/maintenance/reporters/approvals?status=rejected')
            ->with('success', $application->full_name.' was declined and was not added to the reporters list.');
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
                        url('/maintenance/borrowing'),

                    'rooms_table' =>
                        url('/maintenance/infrastructure'),

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

    public function storeReporter(Request $request)
    {
        $request->validate([
            // Employee ID
            // Required and must be unique
            'employee_id' => [
                'required',
                'string',
                'max:100',
                'unique:reporters_table,reporter_employee_id',
            ],

            // First Name
            // Required
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            // Middle Name
            // Optional
            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            // Last Name
            // Required
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            // Employment Type
            // Required
            'type' => [
                'required',
                'in:Faculty,Staff',
            ],

            // Email
            // Optional, but must be valid if entered
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            // Contact Number
            // Optional, but must contain exactly 11 digits if entered
            'contact' => [
                'nullable',
                'digits:11',
            ],
        ]);

        $employeeId = trim($request->employee_id);

            if (ReporterApprovals::pendingByEmployeeId($employeeId)) {
                return back()->with(
                    'error',
                    'That employee ID already has an application waiting for approval.'
                );
            }

        if (ReporterApprovals::pendingByEmployeeId($employeeId)) {
            return back()->with('error', 'That employee ID already has an application waiting for approval.');
        }

        $first = trim($request->first_name);
        $middle = trim((string) $request->middle_name);
        $last = trim($request->last_name);
        $fullName = ReporterImport::composeFullName($first, $middle, $last);

        $payload = [
            'reporter_employee_id' => $employeeId,
            'reporter_full_name' => $fullName,
            'reporter_email_address' => $request->email ?: null,
            'reporter_contact_number' => $request->contact ?: null,
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

        DB::table('reporters_table')->insert($payload);

        return back()->with('success', 'Reporter added successfully.');
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
            fputcsv($handle, ['OMC0130F', 'John', 'Michael', 'Smith', 'Faculty', 'john@company.com', "\t09171234567"]);
            fputcsv($handle, ['', 'Sarah', '', 'Connor', 'Staff', 'sarah@company.com', "\t09179876543"]);
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

        $headers = $rows[0];
        $body = array_values(array_filter(array_slice($rows, 1), function ($row) {
            return collect($row)->filter(fn ($cell) => trim((string) $cell) !== '')->isNotEmpty();
        }));

        $mapping = ReporterImport::suggestMapping($headers);
        $cell = function (array $row, string $field) use ($mapping) {
            $index = $mapping[$field] ?? '';
            if ($index === '' || $index === null) {
                return '';
            }

            $value = trim((string) ($row[(int) $index] ?? ''));

            return $field === 'contact_number'
                ? ReporterImport::normalizeContact($value)
                : $value;
        };

        $people = collect($body)->map(function ($row) use ($cell) {
            return [
                'employee_id' => $cell($row, 'employee_id'),
                'first_name' => $cell($row, 'first_name'),
                'middle_name' => $cell($row, 'middle_name'),
                'last_name' => $cell($row, 'last_name'),
                'type' => $cell($row, 'type'),
                'email_address' => $cell($row, 'email_address'),
                'contact_number' => $cell($row, 'contact_number'),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'headers' => $headers,
            'mapping' => $mapping,
            'fields' => ReporterImport::FIELDS,
            'people' => $people->take(20)->all(),
            'total' => $people->count(),
        ]);
    }

    public function importReporters(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
            'auto_assign_ids' => 'nullable',
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

        $body = array_values(array_filter(array_slice($rows, 1), function ($row) {
            return collect($row)->filter(fn ($cell) => trim((string) $cell) !== '')->isNotEmpty();
        }));

        $autoAssign = $request->boolean('auto_assign_ids');
        $cell = function (array $row, string $field) use ($mapping) {
            $index = $mapping[$field] ?? '';
            if ($index === '' || $index === null) {
                return '';
            }

            return trim((string) ($row[(int) $index] ?? ''));
        };

        $existingIds = DB::table('reporters_table')
            ->pluck('reporter_employee_id')
            ->filter()
            ->map(fn ($id) => strtoupper((string) $id))
            ->all();

        $existingEmails = DB::table('reporters_table')
            ->pluck('reporter_email_address')
            ->filter()
            ->map(fn ($email) => strtolower((string) $email))
            ->all();

        if (ReporterApprovals::hasTable()) {
            $pendingIds = ReporterApprovals::query()
                ->where('status', ReporterApprovals::STATUS_PENDING)
                ->pluck('employee_id')
                ->filter()
                ->map(fn ($id) => strtoupper((string) $id))
                ->all();
            $pendingEmails = ReporterApprovals::query()
                ->where('status', ReporterApprovals::STATUS_PENDING)
                ->pluck('email')
                ->filter()
                ->map(fn ($email) => strtolower((string) $email))
                ->all();

            $existingIds = array_values(array_unique(array_merge($existingIds, $pendingIds)));
            $existingEmails = array_values(array_unique(array_merge($existingEmails, $pendingEmails)));
        }

        $generated = ReporterImport::nextEmployeeIds(count($body));
        $created = 0;
        $skipped = 0;
        $errors = [];
        $generatedIndex = 0;
        $hasType = ReporterImport::hasTypeColumn();

        foreach ($body as $offset => $row) {
            $line = $offset + 2;
            $first = $cell($row, 'first_name');
            $middle = $cell($row, 'middle_name');
            $last = $cell($row, 'last_name');
            $fullName = ReporterImport::composeFullName($first, $middle, $last);
            $employeeId = $cell($row, 'employee_id');
            $email = strtolower($cell($row, 'email_address'));
            $contact = ReporterImport::normalizeContact($cell($row, 'contact_number'));
            $type = $cell($row, 'type');

            if ($first === '' || $last === '') {
                $skipped++;
                $errors[] = "Row {$line}: first and last name are required.";
                continue;
            }

            if ($employeeId === '' && $autoAssign) {
                $employeeId = $generated[$generatedIndex] ?? null;
                $generatedIndex++;
            }

            if ($employeeId === '') {
                $skipped++;
                $errors[] = "Row {$line}: employee ID is missing.";
                continue;
            }

            $idKey = strtoupper($employeeId);

            if (in_array($idKey, $existingIds, true)) {
                $skipped++;
                $errors[] = "Row {$line}: employee ID {$employeeId} already exists.";
                continue;
            }

            if ($email !== '' && in_array($email, $existingEmails, true)) {
                $skipped++;
                $errors[] = "Row {$line}: email {$email} already exists.";
                continue;
            }

            $payload = [
                'reporter_employee_id' => $employeeId,
                'reporter_full_name' => $fullName,
                'reporter_email_address' => $email !== '' ? $email : null,
                'reporter_contact_number' => $contact !== '' ? $contact : null,
                'reporter_status' => 'Active',
                'reporter_created_at' => now(),
            ];

            if ($hasType && $type !== '') {
                $payload['reporter_employment_type'] = $type;
            }

            if (ReporterImport::hasNameColumns()) {
                $payload['reporter_first_name'] = $first !== '' ? $first : null;
                $payload['reporter_middle_name'] = $middle !== '' ? $middle : null;
                $payload['reporter_last_name'] = $last !== '' ? $last : null;
            }

            DB::table('reporters_table')->insert($payload);

            $existingIds[] = $idKey;
            if ($email !== '') {
                $existingEmails[] = $email;
            }
            $created++;
        }

        $message = "Imported {$created} reporter".($created === 1 ? '' : 's').'.';
        if ($skipped > 0) {
            $message .= " Skipped {$skipped}.";
        }

        return back()->with('import_result', [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 20),
        ]);
    }
}
