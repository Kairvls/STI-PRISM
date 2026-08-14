<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CampusSetupSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function dashboard(Request $request): View
    {
        // =====================================================
        // ADMIN DASHBOARD
        // All queries are wrapped in try-catch so the dashboard
        // still renders even if tables/columns are missing.
        // =====================================================

        // =====================================================
        // USER STATISTICS
        // =====================================================

        try {
            $totalUsers = DB::table('users_table')->count();
        } catch (\Throwable $e) { $totalUsers = 0; }

        try {
            $maintenancePersonnel = DB::table('users_table')
                ->where('user_role_id', 2)->count();
        } catch (\Throwable $e) { $maintenancePersonnel = 0; }

        try {
            $purchasers = DB::table('users_table')
                ->where('user_role_id', 3)->count();
        } catch (\Throwable $e) { $purchasers = 0; }

        try {
            $presidents = DB::table('users_table')
                ->where('user_role_id', 4)->count();
        } catch (\Throwable $e) { $presidents = 0; }

        try {
            $accounting = DB::table('users_table')
                ->where('user_role_id', 5)->count();
        } catch (\Throwable $e) { $accounting = 0; }

        try {
            $receivingOfficers = DB::table('users_table')
                ->where('user_role_id', 6)->count();
        } catch (\Throwable $e) { $receivingOfficers = 0; }

        try {
            $activeUsers = DB::table('users_table')
                ->whereNotNull('last_active_at')
                ->where('last_active_at', '>=', now()->subDays(7))
                ->count();
        } catch (\Throwable $e) { $activeUsers = 0; }


        // =====================================================
        // RIS STATISTICS
        // =====================================================

        $totalRis = 0;
        $pendingRis = 0;
        $amendRis = 0;
        $approvedRis = 0;
        $directApprovedRis = 0;
        $cosignedRis = 0;
        $totalRisAmount = 0;
        $pendingRisAmount = 0;
        $forCosigningCount = 0;
        $cosignedCount = 0;

        try {
            $baseRIS = DB::table('requisition_issue_slip_table')
                ->whereNotNull('ris_requested_by_date');

            $totalRis = (clone $baseRIS)->count();

            $pendingRis = (clone $baseRIS)
                ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending'])
                ->count();

            $amendRis = (clone $baseRIS)
                ->whereIn('ris_status', ['Minor Revision', 'Rejected'])
                ->count();

            $approvedRis = (clone $baseRIS)
                ->where('ris_status', 'Approved')
                ->whereNotNull('ris_approved_by_date')
                ->where(function ($q) {
                    $q->whereNull('ris_approved_by_signature')
                      ->orWhere('ris_approved_by_signature', 'like', 'data:image%');
                })
                ->count();

            $directApprovedRis = (clone $baseRIS)
                ->where(function ($q) {
                    $q->where('ris_status', 'Directly Approved')
                        ->orWhere(function ($legacy) {
                            $legacy->where('ris_status', 'Approved')
                                ->whereNotNull('ris_approved_by_date')
                                ->whereNotNull('ris_approved_by_signature')
                                ->where('ris_approved_by_signature', 'not like', 'data:image%')
                                ->whereNull('ris_issued_by_date');
                        });
                })
                ->count();

            $cosignedRis = (clone $baseRIS)
                ->where('ris_status', 'Approved')
                ->whereNotNull('ris_issued_by_date')
                ->where('ris_approved_by_signature', 'like', 'data:image%')
                ->count();
        } catch (\Throwable $e) { /* defaults stay 0 */ }

        try {
            $totalRisAmount = DB::table('requisition_issue_slip_table')
                ->leftJoin(
                    DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                    'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id'
                )
                ->whereNotNull('ris_requested_by_date')
                ->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $totalRisAmount = 0; }

        try {
            $pendingRisAmount = DB::table('requisition_issue_slip_table')
                ->leftJoin(
                    DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                    'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id'
                )
                ->whereNotNull('ris_requested_by_date')
                ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending'])
                ->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $pendingRisAmount = 0; }


        // =====================================================
        // DIGITAL SIGNATURE STATS
        // =====================================================

        try {
            $forCosigningCount = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereNotNull('ris_approved_by_date')
                ->where('ris_approved_by_signature', 'like', 'data:image%')
                ->whereNull('ris_issued_by_date')
                ->count();
        } catch (\Throwable $e) { $forCosigningCount = 0; }

        try {
            $cosignedCount = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereNotNull('ris_issued_by_date')
                ->where('ris_approved_by_signature', 'like', 'data:image%')
                ->count();
        } catch (\Throwable $e) { $cosignedCount = 0; }


        // =====================================================
        // CALENDAR OF EVENTS — UPCOMING MAINTENANCE SCHEDULES
        // =====================================================

        try {
            $calendarEvents = DB::table('maintenance_schedules_table')
                ->leftJoin('equipment_table', 'maintenance_schedules_table.maintenance_schedule_equipment_id', '=', 'equipment_table.equipment_id')
                ->select(
                    'maintenance_schedules_table.*',
                    'equipment_table.equipment_name'
                )
                ->where(function ($q) {
                    $q->where('maintenance_schedules_table.maintenance_schedule_status', 'Active')
                      ->orWhere('maintenance_schedules_table.maintenance_schedule_status', 'Overdue');
                })
                ->orderBy('maintenance_schedules_table.maintenance_schedule_next_date')
                ->limit(20)
                ->get();
        } catch (\Throwable $e) { $calendarEvents = collect(); }

        // Group events by date for the calendar
        $calendarEventsByDate = [];
        foreach ($calendarEvents as $evt) {
            $dateKey = $evt->maintenance_schedule_next_date ? \Carbon\Carbon::parse($evt->maintenance_schedule_next_date)->format('Y-m-d') : null;
            if ($dateKey) {
                if (!isset($calendarEventsByDate[$dateKey])) {
                    $calendarEventsByDate[$dateKey] = [];
                }
                $calendarEventsByDate[$dateKey][] = $evt;
            }
        }

        // =====================================================
        // ACTIVITY LIST — Separated into pending (3) + completed (2)
        // =====================================================

        // Pending items come from live RIS forms (not approval_logs),
        // because submitted RIS may not have an approval log yet.
        try {
            $pendingActivityLogs = DB::table('requisition_issue_slip_table')
                ->select(
                    'ris_id as id',
                    'ris_status as status',
                    'ris_purpose_description as description',
                    'ris_form_number',
                    DB::raw("'RIS' as ref_type"),
                    'ris_id as ref_id',
                    DB::raw('COALESCE(ris_submitted_at, ris_requested_by_date, ris_created_at) as created_at'),
                    'ris_requested_by_signature as actor_name',
                    DB::raw("'ris' as log_source")
                )
                ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending'])
                ->orderByDesc(DB::raw('COALESCE(ris_submitted_at, ris_requested_by_date, ris_created_at)'))
                ->orderByDesc('ris_id')
                ->limit(3)
                ->get()
                ->map(function ($log) {
                    $log->is_pending = true;
                    $formNo = $log->ris_form_number ? 'RIS #' . $log->ris_form_number : 'RIS';
                    $log->title = $log->status . ' — ' . $formNo;
                    if (empty($log->description)) {
                        $log->description = 'Awaiting admin review';
                    }
                    return $log;
                });
        } catch (\Throwable $e) { $pendingActivityLogs = collect(); }

        try {
            $completedActivityLogs = DB::table('approval_logs_table')
                ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                ->select(
                    'approval_logs_table.approval_log_id as id',
                    'approval_logs_table.approval_log_approval_status as status',
                    'approval_logs_table.approval_log_approval_remarks as description',
                    'approval_logs_table.approval_log_reference_type as ref_type',
                    'approval_logs_table.approval_log_reference_id as ref_id',
                    'approval_logs_table.approval_log_approved_at as created_at',
                    'users_table.user_full_name as actor_name',
                    DB::raw("'approval' as log_source")
                )
                ->whereIn('approval_logs_table.approval_log_approval_status', ['Approved', 'Co-signed', 'Rejected', 'Directly Approved'])
                ->orderByDesc('approval_logs_table.approval_log_approved_at')
                ->limit(2)
                ->get()
                ->map(function ($log) {
                    $log->is_pending = false;
                    $log->title = $log->status . ' — ' . ($log->ref_type ?? 'RIS');
                    return $log;
                });
        } catch (\Throwable $e) { $completedActivityLogs = collect(); }


        // =====================================================
        // RECENT APPROVAL LOGS (ACTIVITY) — kept for backward compat
        // =====================================================

        try {
            $recentActivities = DB::table('approval_logs_table')
                ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                ->select(
                    'approval_logs_table.*',
                    'users_table.user_full_name as approver_name'
                )
                ->orderByDesc('approval_logs_table.approval_log_approved_at')
                ->limit(8)
                ->get()
                ->map(function ($activity) {
                    $activity->title = $activity->approval_log_approval_status . ' - ' . ($activity->approval_log_reference_type ?? 'RIS');
                    $activity->description = $activity->approval_log_approval_remarks ?? 'No remarks.';
                    $activity->created_at = $activity->approval_log_approved_at;
                    $activity->icon = match ($activity->approval_log_approval_status) {
                        'Approved', 'Co-signed' => 'check-circle',
                        'Rejected' => 'x-circle',
                        default => 'activity',
                    };
                    $activity->background = match ($activity->approval_log_approval_status) {
                        'Approved', 'Co-signed' => '#dcfce7',
                        'Rejected' => '#fee2e2',
                        default => '#f3f4f6',
                    };
                    $activity->color = match ($activity->approval_log_approval_status) {
                        'Approved', 'Co-signed' => '#16a34a',
                        'Rejected' => '#dc2626',
                        default => '#374151',
                    };
                    return $activity;
                });
        } catch (\Throwable $e) { $recentActivities = collect(); }


        // =====================================================
        // RIS MONTHLY TREND (LAST 6 MONTHS)
        // Admin Approved, Forwarded to President, Amend
        // =====================================================

        $risTrendLabels = [];
        $risTrendApproved = [];
        $risTrendForwarded = [];
        $risTrendAmend = [];

        try {
            $risMonthlyStartDate = now()->copy()->subMonths(5)->startOfMonth();

            $risMonthlyRows = DB::table('requisition_issue_slip_table')
                ->selectRaw('
                    YEAR(ris_created_at) AS ris_year,
                    MONTH(ris_created_at) AS ris_month,
                    ris_status,
                    COUNT(*) AS ris_count
                ')
                ->whereNotNull('ris_created_at')
                ->where('ris_created_at', '>=', $risMonthlyStartDate)
                ->groupByRaw('YEAR(ris_created_at), MONTH(ris_created_at), ris_status')
                ->get();

            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ];

            for ($i = 5; $i >= 0; $i--) {
                $month = now()->copy()->subMonths($i)->startOfMonth();
                $year = (int) $month->format('Y');
                $monthNum = (int) $month->format('n');

                $monthRows = $risMonthlyRows->filter(function ($row) use ($year, $monthNum) {
                    return (int) $row->ris_year === $year && (int) $row->ris_month === $monthNum;
                });

                $adminApprovedCount = $monthRows
                    ->where('ris_status', 'Directly Approved')
                    ->sum('ris_count');

                $forwardedCount = $monthRows
                    ->where('ris_status', 'Approved')
                    ->sum('ris_count');

                $amendCount = $monthRows
                    ->whereIn('ris_status', ['Minor Revision', 'Rejected'])
                    ->sum('ris_count');

                $risTrendLabels[] = ($monthNames[$monthNum] ?? $month->format('F')) . ' ' . $year;
                $risTrendApproved[] = (int) $adminApprovedCount;
                $risTrendForwarded[] = (int) $forwardedCount;
                $risTrendAmend[] = (int) $amendCount;
            }
        } catch (\Throwable $e) {
            $risTrendLabels = [];
            $risTrendApproved = [];
            $risTrendForwarded = [];
            $risTrendAmend = [];
        }


        // =====================================================
        // RIS STATUS DISTRIBUTION FOR CHART
        // =====================================================

        $risStatusChart = [
            'labels' => ['Pending', 'Forwarded to President', 'Admin Approved', 'Amend', 'Co-signed'],
            'data' => [
                $pendingRis,
                $approvedRis,
                $directApprovedRis,
                $amendRis,
                $cosignedRis,
            ],
        ];


        // =====================================================
        // RECENT RIS RECORDS (TABLE)
        // =====================================================

        try {
            $recentRisRecords = DB::table('requisition_issue_slip_table')
                ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
                ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
                ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
                ->leftJoin(
                    DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                    'requisition_issue_slip_table.ris_id',
                    '=',
                    'ris_items_sum.ris_id'
                )
                ->leftJoin(
                    DB::raw('(SELECT ris_id, GROUP_CONCAT(COALESCE(ris_item_name_description, "N/A") SEPARATOR ", ") as ris_item_names FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_names'),
                    'requisition_issue_slip_table.ris_id',
                    '=',
                    'ris_items_names.ris_id'
                )
                ->select(
                    'requisition_issue_slip_table.*',
                    'equipment_table.equipment_name',
                    'reports_table.report_unlisted_equipment_name',
                    'ris_items_sum.ris_calculated_total',
                    'ris_items_names.ris_item_names'
                )
                ->where(function ($q) {
                    $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                        ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                        ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at')
                        ->orWhereNotNull('requisition_issue_slip_table.ris_created_at');
                })
                ->orderByDesc(DB::raw('COALESCE(requisition_issue_slip_table.ris_submitted_at, requisition_issue_slip_table.ris_requested_by_date, requisition_issue_slip_table.ris_created_at)'))
                ->orderByDesc('requisition_issue_slip_table.ris_id')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) { $recentRisRecords = collect(); }


        // =====================================================
        // RETURN VIEW
        // =====================================================

        return view('admin.dashboard', compact(
            // User stats
            'totalUsers',
            'maintenancePersonnel',
            'purchasers',
            'presidents',
            'accounting',
            'receivingOfficers',
            'activeUsers',

            // RIS stats
            'totalRis',
            'pendingRis',
            'amendRis',
            'approvedRis',
            'directApprovedRis',
            'cosignedRis',
            'totalRisAmount',
            'pendingRisAmount',

            // Digital signature stats
            'forCosigningCount',
            'cosignedCount',

            // Activity
            'recentActivities',

            // Charts
            'risTrendLabels',
            'risTrendApproved',
            'risTrendForwarded',
            'risTrendAmend',
            'risStatusChart',

            // Recent records
            'recentRisRecords',

            // Calendar events
            'calendarEvents',
            'calendarEventsByDate',

// Activity list (pending 3 + completed 2)
            'pendingActivityLogs',
            'completedActivityLogs',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Procurement Review
    |--------------------------------------------------------------------------
    */

    public function procurementReview(Request $request): View
{
    // =====================================================
    // PROCUREMENT REVIEW
    // =====================================================

    // Get selected status filter.
    // Default is "all".
    $filter = strtolower($request->query('filter', 'all'));

    // Get live search value.
    $search = trim($request->query('search', ''));

    // Only allow these filter values.
    if (!in_array($filter, ['all', 'pending', 'approved', 'rejected', 'direct_approved'], true)) {
        $filter = 'all';
    }


    // =====================================================
    // BASE RIS QUERY
    // =====================================================

    $baseQuery = DB::table('requisition_issue_slip_table')

        ->leftJoin(
            'procurement_requests_table',
            'requisition_issue_slip_table.ris_procurement_request_id',
            '=',
            'procurement_requests_table.procurement_request_id'
        )

        ->leftJoin(
            'reports_table',
            'procurement_requests_table.procurement_request_report_id',
            '=',
            'reports_table.report_id'
        )

        ->leftJoin(
            'equipment_table',
            'reports_table.report_equipment_id',
            '=',
            'equipment_table.equipment_id'
        )

        // =====================================================
        // LEFT JOIN RIS ITEMS SUBQUERY
        // Computes the total amount from RIS items
        // (SUM of ris_total_amount per ris_id)
        // =====================================================

        ->leftJoin(
            DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
            'requisition_issue_slip_table.ris_id',
            '=',
            'ris_items_sum.ris_id'
        )

        // =====================================================
        // LEFT JOIN RIS ITEMS SUBQUERY
        // Gets the concatenated item names/descriptions per RIS
        // =====================================================

        ->leftJoin(
            DB::raw('(SELECT ris_id, GROUP_CONCAT(COALESCE(ris_item_name_description, "N/A") SEPARATOR ", ") as ris_item_names FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_names'),
            'requisition_issue_slip_table.ris_id',
            '=',
            'ris_items_names.ris_id'
        )

        ->select(
            'requisition_issue_slip_table.*',
            'procurement_requests_table.procurement_request_id',
            'reports_table.report_id',
            'reports_table.report_unlisted_equipment_name',
            'equipment_table.equipment_name',
            'ris_items_sum.ris_calculated_total',
            'ris_items_names.ris_item_names'
        )

        // Include submitted + legacy/incomplete forms so old records stay visible for logging.
        ->where(function ($q) {
            $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at')
                ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date');
        });


    // =====================================================
    // DASHBOARD CARD COUNTS
    // These counts are NOT affected by the selected filter.
    // =====================================================

    // New workflow statuses: Submitted / Under Review / Resubmitted.
    // Legacy status: Pending.
    $pendingRis = (clone $baseQuery)
        ->whereIn(
            'requisition_issue_slip_table.ris_status',
            ['Submitted', 'Under Review', 'Resubmitted', 'Pending']
        )
        ->count();

    // "Amend" is only the UI name.
    // New workflow uses "Minor Revision", legacy uses "Rejected".
    $amendRis = (clone $baseQuery)
        ->whereIn(
            'requisition_issue_slip_table.ris_status',
            ['Minor Revision', 'Rejected']
        )
        ->count();

    // "Forwarded to President" = Approved AND has approved_by_date AND (no signature OR base64 signature from President)
    $approvedRis = (clone $baseQuery)
        ->where(
            'requisition_issue_slip_table.ris_status',
            'Approved'
        )
        ->whereNotNull(
            'requisition_issue_slip_table.ris_approved_by_date'
        )
        ->where(function ($q) {
            $q->whereNull('requisition_issue_slip_table.ris_approved_by_signature')
              ->orWhere('requisition_issue_slip_table.ris_approved_by_signature', 'like', 'data:image%');
        })
        ->count();

    // "Directly Approved" = dedicated status, or older Approved+plain-text admin records
    $directApprovedRis = (clone $baseQuery)
        ->where(function ($q) {
            $q->where('requisition_issue_slip_table.ris_status', 'Directly Approved')
                ->orWhere(function ($legacy) {
                    $legacy->where('requisition_issue_slip_table.ris_status', 'Approved')
                        ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                        ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                        ->where('requisition_issue_slip_table.ris_approved_by_signature', 'not like', 'data:image%')
                        ->whereNull('requisition_issue_slip_table.ris_issued_by_date');
                });
        })
        ->count();


    // =====================================================
    // TABLE QUERY
    // =====================================================

    $query = clone $baseQuery;


    // =====================================================
    // STATUS FILTER
    // =====================================================

    if ($filter === 'pending') {

        $query->whereIn(
            'requisition_issue_slip_table.ris_status',
            ['Submitted', 'Under Review', 'Resubmitted', 'Pending']
        );

    } elseif ($filter === 'approved') {

        $query->where(
            'requisition_issue_slip_table.ris_status',
            'Approved'
        )
        ->whereNotNull(
            'requisition_issue_slip_table.ris_approved_by_date'
        )
        ->where(function ($q) {
            $q->whereNull('requisition_issue_slip_table.ris_approved_by_signature')
              ->orWhere('requisition_issue_slip_table.ris_approved_by_signature', 'like', 'data:image%');
        });

    } elseif ($filter === 'direct_approved') {

        $query->where(function ($q) {
            $q->where('requisition_issue_slip_table.ris_status', 'Directly Approved')
                ->orWhere(function ($legacy) {
                    $legacy->where('requisition_issue_slip_table.ris_status', 'Approved')
                        ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                        ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                        ->where('requisition_issue_slip_table.ris_approved_by_signature', 'not like', 'data:image%')
                        ->whereNull('requisition_issue_slip_table.ris_issued_by_date');
                });
        });

    } elseif ($filter === 'rejected') {

        // New workflow uses Minor Revision; legacy uses Rejected.
        // The Blade page displays this as Amend.
        $query->whereIn(
            'requisition_issue_slip_table.ris_status',
            ['Minor Revision', 'Rejected']
        );

    } else {

        // All statuses shown by this Procurement Review page.
        $query->whereIn(
            'requisition_issue_slip_table.ris_status',
            [
                'Draft',
                'Submitted',
                'Under Review',
                'Resubmitted',
                'Pending',
                'Approved',
                'Directly Approved',
                'Minor Revision',
                'Rejected',
                'Archived',
            ]
        );
    }


    // =====================================================
    // SEARCH
    // =====================================================

    if ($search !== '') {

        $query->where(function ($searchQuery) use ($search) {

            $searchQuery

                // Search RIS number.
                ->where(
                    'requisition_issue_slip_table.ris_form_number',
                    'like',
                    '%' . $search . '%'
                )

                // Search person who sent/requested the RIS.
                ->orWhere(
                    'requisition_issue_slip_table.ris_requested_by_signature',
                    'like',
                    '%' . $search . '%'
                )

                // Search equipment from equipment table.
                ->orWhere(
                    'equipment_table.equipment_name',
                    'like',
                    '%' . $search . '%'
                )

                // Search manually entered/unlisted equipment.
                ->orWhere(
                    'reports_table.report_unlisted_equipment_name',
                    'like',
                    '%' . $search . '%'
                )

                // Search status.
                ->orWhere(
                    'requisition_issue_slip_table.ris_status',
                    'like',
                    '%' . $search . '%'
                );

        });
    }


    // =====================================================
    // SORTING — latest first
    // =====================================================

    $risRecords = $query

        ->orderByDesc(DB::raw('COALESCE(requisition_issue_slip_table.ris_submitted_at, requisition_issue_slip_table.ris_requested_by_date, requisition_issue_slip_table.ris_created_at)'))

        ->orderByDesc(
            'requisition_issue_slip_table.ris_id'
        )

        // Exactly 10 RIS requests per page.
        ->paginate(10)

        // Preserve filter and search when changing pages.
        ->appends([
            'filter' => $filter,
            'search' => $search,
        ]);


    // =====================================================
    // RETURN VIEW
    // =====================================================

    // AJAX requests return only the content partial
    // so the page does not fully reload.
    if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {

        if ($request->boolean('table_only')) {
            return view(
                'admin.procurement-review._quick-access',
                compact('risRecords', 'filter', 'search')
            );
        }

        return view(
            'admin.procurement-review._content',
            compact(
                'risRecords',
                'filter',
                'search',
                'pendingRis',
                'amendRis',
                'approvedRis',
                'directApprovedRis'
            )

        );

    }

    return view(
        'admin.procurement-review.index',
        compact(
            'risRecords',
            'filter',
            'search',
            'pendingRis',
            'amendRis',
            'approvedRis',
            'directApprovedRis'
        )
    );
}

    /*
    |--------------------------------------------------------------------------
    | Digital Signatures
    |--------------------------------------------------------------------------
    */

    public function signRis(Request $request)
{
    // =====================================================
    // SIGN RIS
    // Only President-returned signed RIS (base64 Approved by)
    // for Admin co-sign. Directly Approved forms are excluded.
    // =====================================================

    // Get selected status filter.
    $filter = strtolower($request->query('filter', 'all'));

    // Get live search value.
    $search = trim($request->query('search', ''));

    // Only allow these filter values.
    if (!in_array($filter, ['all', 'for_cosign', 'cosigned'], true)) {
        $filter = 'all';
    }


    // =====================================================
    // BASE QUERY - President-signed Approved RIS only
    // =====================================================

    $baseQuery = DB::table('requisition_issue_slip_table')

        ->leftJoin(
            'procurement_requests_table',
            'requisition_issue_slip_table.ris_procurement_request_id',
            '=',
            'procurement_requests_table.procurement_request_id'
        )

        ->leftJoin(
            'reports_table',
            'procurement_requests_table.procurement_request_report_id',
            '=',
            'reports_table.report_id'
        )

        ->leftJoin(
            'equipment_table',
            'reports_table.report_equipment_id',
            '=',
            'equipment_table.equipment_id'
        )

        // LEFT JOIN RIS ITEMS SUBQUERY - computed total
        ->leftJoin(
            DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
            'requisition_issue_slip_table.ris_id',
            '=',
            'ris_items_sum.ris_id'
        )

        // LEFT JOIN RIS ITEMS SUBQUERY - concatenated item names
        ->leftJoin(
            DB::raw('(SELECT ris_id, GROUP_CONCAT(COALESCE(ris_item_name_description, "N/A") SEPARATOR ", ") as ris_item_names FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_names'),
            'requisition_issue_slip_table.ris_id',
            '=',
            'ris_items_names.ris_id'
        )

        ->select(
            'requisition_issue_slip_table.*',
            'procurement_requests_table.procurement_request_id',
            'reports_table.report_id',
            'reports_table.report_unlisted_equipment_name',
            'equipment_table.equipment_name',
            'ris_items_sum.ris_calculated_total',
            'ris_items_names.ris_item_names'
        )

        ->where('requisition_issue_slip_table.ris_status', 'Approved')
        ->where(
            'requisition_issue_slip_table.ris_approved_by_signature',
            'like',
            'data:image%'
        );


// =====================================================
    // DASHBOARD CARD COUNTS - NOT affected by filter
    // =====================================================

    $forCosignCount = (clone $baseQuery)
        ->whereNull(
            'requisition_issue_slip_table.ris_issued_by_date'
        )
        ->where(
            'requisition_issue_slip_table.ris_approved_by_signature',
            'like',
            'data:image%'
        )
        ->count();

    $cosignedCount = (clone $baseQuery)
        ->whereNotNull(
            'requisition_issue_slip_table.ris_issued_by_date'
        )
        ->count();

    // Total amount for For Co-sign (pending)
    $forCosignAmount = (clone $baseQuery)
        ->whereNull('requisition_issue_slip_table.ris_issued_by_date')
        ->where(
            'requisition_issue_slip_table.ris_approved_by_signature',
            'like',
            'data:image%'
        )
        ->sum('ris_items_sum.ris_calculated_total');

    // Total amount for Co-signed
    $cosignedAmount = (clone $baseQuery)
        ->whereNotNull('requisition_issue_slip_table.ris_issued_by_date')
        ->sum('ris_items_sum.ris_calculated_total');


    // =====================================================
    // TABLE QUERY
    // =====================================================

    $query = clone $baseQuery;


    // =====================================================
    // STATUS FILTER
    // =====================================================

    if ($filter === 'for_cosign') {

        $query->whereNull(
            'requisition_issue_slip_table.ris_issued_by_date'
        )
        ->where(
            'requisition_issue_slip_table.ris_approved_by_signature',
            'like',
            'data:image%'
        );

    } elseif ($filter === 'cosigned') {

        $query->whereNotNull(
            'requisition_issue_slip_table.ris_issued_by_date'
        );

    }
    // 'all' shows for-cosign + co-signed President-signed RIS only


    // =====================================================
    // SEARCH
    // =====================================================

    if ($search !== '') {

        $query->where(function ($searchQuery) use ($search) {

            $searchQuery

                ->where(
                    'requisition_issue_slip_table.ris_form_number',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'requisition_issue_slip_table.ris_requested_by_signature',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'requisition_issue_slip_table.ris_manual_title',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'equipment_table.equipment_name',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'reports_table.report_unlisted_equipment_name',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'requisition_issue_slip_table.ris_purpose_description',
                    'like',
                    '%' . $search . '%'
                );

        });
    }


    // =====================================================
    // SORTING
    //
    // For Co-sign (pending) appear first.
    // Newest For Co-sign appear at the very top.
    // =====================================================

    $signableRisRecords = $query

        ->orderByRaw("
            CASE
                WHEN requisition_issue_slip_table.ris_issued_by_date IS NULL
                    AND requisition_issue_slip_table.ris_approved_by_signature LIKE 'data:image%' THEN 0
                WHEN requisition_issue_slip_table.ris_issued_by_date IS NULL THEN 1
                ELSE 2
            END
        ")

        ->orderByDesc(
            'requisition_issue_slip_table.ris_approved_by_date'
        )

        ->orderByDesc(
            'requisition_issue_slip_table.ris_id'
        )

        ->paginate(10)

        ->appends([
            'filter' => $filter,
            'search' => $search,
        ]);


    // =====================================================
    // RETURN VIEW
    // =====================================================

    if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {

        if ($request->boolean('table_only')) {
            return view(
                'admin.digital-signatures._sign-ris-quick-access',
                compact('signableRisRecords', 'filter', 'search')
            );
        }

        return view(
            'admin.digital-signatures._sign-ris-content',
            compact(
                'signableRisRecords',
                'filter',
                'search',
                'forCosignCount',
                'cosignedCount',
                'forCosignAmount',
                'cosignedAmount'
            )

        );

    }

    return view(
        'admin.digital-signatures.sign-ris',
        compact(
            'signableRisRecords',
            'filter',
            'search',
            'forCosignCount',
            'cosignedCount',
            'forCosignAmount',
            'cosignedAmount'
        )
    );
}

    public function signatureHistory(Request $request): View
    {
        // =====================================================
        // SIGNATURE HISTORY
        // Shows history of all FINISHED/COMPLETED RIS forms.
        // Active (Pending) RIS forms are excluded — only those
        // that have reached a final state are shown.
        //
        // Finished states:
        //   - Directly Approved (Issued by Admin)
        //   - Signed / Forwarded to President (Approved + President base64 sig)
        //   - Co-signed (has issued_by_date after President signature)
        //   - Amended (Rejected / Minor Revision)
        // =====================================================

        // Get search value (default empty).
        $search = trim($request->query('search', ''));


        // =====================================================
        // BASE QUERY - Only FINISHED RIS forms
        // Excludes Pending (active) forms.
        // =====================================================

        $baseQuery = DB::table('requisition_issue_slip_table')

            ->leftJoin(
                'procurement_requests_table',
                'requisition_issue_slip_table.ris_procurement_request_id',
                '=',
                'procurement_requests_table.procurement_request_id'
            )

            ->leftJoin(
                'reports_table',
                'procurement_requests_table.procurement_request_report_id',
                '=',
                'reports_table.report_id'
            )

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            // LEFT JOIN RIS ITEMS SUBQUERY - computed total
            ->leftJoin(
                DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                'requisition_issue_slip_table.ris_id',
                '=',
                'ris_items_sum.ris_id'
            )

            // LEFT JOIN RIS ITEMS SUBQUERY - concatenated item names
            ->leftJoin(
                DB::raw('(SELECT ris_id, GROUP_CONCAT(COALESCE(ris_item_name_description, "N/A") SEPARATOR ", ") as ris_item_names FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_names'),
                'requisition_issue_slip_table.ris_id',
                '=',
                'ris_items_names.ris_id'
            )

            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'ris_items_sum.ris_calculated_total',
                'ris_items_names.ris_item_names'
            )

            // Keep finished + legacy records visible for logging,
            // even when dates/signatures are incomplete.
            ->where(function ($q) {
                $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_issued_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at');
            })

            // EXCLUDE only active in-progress forms.
            // Archived and incomplete Approved/Rejected remain visible.
            ->whereNotIn(
                'requisition_issue_slip_table.ris_status',
                [
                    'Draft',
                    'Submitted',
                    'Under Review',
                    'Resubmitted',
                    'Pending',
                ]
            );


        // =====================================================
        // DASHBOARD CARD COUNTS
        // Each card counts a mutually-exclusive finished state.
        // Total = sum of all individual cards (no overlap).
        // =====================================================

        // Directly Approved = dedicated status, or older Approved+plain-text admin records
        $directApprovedCount = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('requisition_issue_slip_table.ris_status', 'Directly Approved')
                    ->orWhere(function ($legacy) {
                        $legacy->where('requisition_issue_slip_table.ris_status', 'Approved')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                            ->where('requisition_issue_slip_table.ris_approved_by_signature', 'not like', 'data:image%')
                            ->whereNull('requisition_issue_slip_table.ris_issued_by_date');
                    });
            })
            ->count();

        // Signed = Approved + base64 President signature, NOT co-signed (forwarded to President)
        $signedCount = (clone $baseQuery)
            ->where('requisition_issue_slip_table.ris_status', 'Approved')
            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
            ->where('requisition_issue_slip_table.ris_approved_by_signature', 'like', 'data:image%')
            ->whereNull('requisition_issue_slip_table.ris_issued_by_date')
            ->count();

        // Co-signed = President signed then Admin issued
        $cosignedCount = (clone $baseQuery)
            ->where('requisition_issue_slip_table.ris_status', 'Approved')
            ->whereNotNull('requisition_issue_slip_table.ris_issued_by_date')
            ->where('requisition_issue_slip_table.ris_approved_by_signature', 'like', 'data:image%')
            ->count();

// Amended = Minor Revision (new workflow) or Rejected (legacy)
        // Counted from the full table because the finished-history query
        // intentionally excludes active Minor Revision records.
        $amendedCount = DB::table('requisition_issue_slip_table')
            ->whereIn('ris_status', ['Minor Revision', 'Rejected'])
            ->whereNotNull('ris_requested_by_date')
            ->count();


        // =====================================================
        // TABLE QUERY
        // =====================================================

        $query = clone $baseQuery;


        // =====================================================
        // SEARCH
        // =====================================================

        if ($search !== '') {

            $query->where(function ($searchQuery) use ($search) {

                $searchQuery
                    ->where(
                        'requisition_issue_slip_table.ris_form_number',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_requested_by_signature',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_manual_title',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'equipment_table.equipment_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'reports_table.report_unlisted_equipment_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_issued_by_signature',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_purpose_description',
                        'like',
                        '%' . $search . '%'
                    );

            });
        }


        // =====================================================
        // SORTING - Most recent / latest first
        // Uses COALESCE to pick the latest relevant date
        // =====================================================

        $signatureHistory = $query

            ->orderByRaw("
                COALESCE(
                    requisition_issue_slip_table.ris_updated_at,
                    requisition_issue_slip_table.ris_issued_by_date,
                    requisition_issue_slip_table.ris_approved_by_date,
                    requisition_issue_slip_table.ris_requested_by_date,
                    requisition_issue_slip_table.ris_created_at
                ) DESC
            ")
            ->orderByDesc(
                'requisition_issue_slip_table.ris_id'
            )

            ->paginate(10)

            ->appends([
                'search' => $search,
            ]);


        // =====================================================
        // RETURN VIEW
        // =====================================================

        // AJAX requests return only the content partial
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {

            if ($request->boolean('table_only')) {
                return view(
                    'admin.digital-signatures._signature-history-quick-access',
                    compact('signatureHistory', 'search')
                );
            }

            return view(
                'admin.digital-signatures._signature-history-content',
                compact(
                    'signatureHistory',
                    'search',
                    'directApprovedCount',
                    'signedCount',
                    'cosignedCount',
                    'amendedCount'
                )
            );

        }

        return view(
            'admin.digital-signatures.signature-history',
            compact(
                'signatureHistory',
                'search',
                'directApprovedCount',
                'signedCount',
                'cosignedCount',
                'amendedCount'
            )
        );
    }

    // =====================================================
    // ADMIN RIS CO-SIGN WITH DIGITAL SIGNATURE
    // =====================================================

    public function decideRis(Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $adminName = $request->input('admin_name');
        $adminDate = $request->input('admin_date');

        // Basic validation
        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Invalid RIS decision payload.');
        }

        // Validate admin name and date
        if (empty(trim($adminName ?? ''))) {
            return back()->with('error', 'Please provide your name to co-sign the RIS.');
        }

        if (empty($adminDate)) {
            return back()->with('error', 'Please provide the date to co-sign the RIS.');
        }

        $target = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->first();

        if (!$target) {
            return back()->with('error', 'RIS not found.');
        }

        // Check if RIS is eligible for co-signing:
        // Must be Approved, have approved_by_date, AND have President's base64 signature
        if (
            $target->ris_status !== 'Approved' ||
            empty($target->ris_approved_by_date) ||
            empty($target->ris_approved_by_signature) ||
            !str_starts_with($target->ris_approved_by_signature, 'data:image')
        ) {
            return back()->with('error', 'Only RIS records approved by the President can be co-signed.');
        }

        // Check if already co-signed
        if (!empty($target->ris_issued_by_date)) {
            return back()->with('error', 'This RIS has already been co-signed.');
        }

        $updateValues = [
            'ris_status' => 'Approved',
        ];

        if ($decision === 'Approved') {
            // Store the admin name as plain text signature
            $updateValues['ris_issued_by_signature'] = trim($adminName);
            $updateValues['ris_issued_by_date'] = $adminDate;
        } else {
            return back()->with('error', 'Only co-signing (approval) is supported for President-approved RIS.');
        }

        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->update($updateValues);

        // Log the activity to approval logs
        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $targetId,
                'approval_log_level' => 'Admin Co-sign',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => 'Co-signed',
                'approval_log_approval_remarks' => 'RIS co-signed by ' . trim($adminName) . ' after President approval.',
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        return redirect()
            ->route('admin.digital-signatures.sign-ris')
            ->with('success', 'RIS co-signed successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    public function notifications(): View
    {
        return view('admin.notifications.index');
    }

    public function createNotification(): View
    {
        return view('admin.notifications.create');
    }

    public function viewNotification(): View
    {
        return view('admin.notifications.view');
    }

    public function sentNotificationHistory(): View
    {
        return view('admin.notifications.sent-history');
    }

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    public function users(): View
    {
        return view('admin.users.index');
    }

    public function createUser(): View
    {
        return view('admin.users.create');
    }

    public function editUser(): View
    {
        return view('admin.users.edit');
    }

    public function viewUser(): View
    {
        return view('admin.users.view');
    }

    public function resetPassword(): View
    {
        return view('admin.users.reset-password');
    }

    public function userActivityLogs(): View
    {
        return view('admin.users.activity-logs');
    }

    /*
    |--------------------------------------------------------------------------
    | Store User
    |--------------------------------------------------------------------------
    */

    public function storeUser(Request $request)
    {
        User::create([

            // users_table.user_role_id
            'user_role_id' => $request->role,

            // users_table.user_employee_id
            'user_employee_id' => $request->employee_id,

            // users_table.user_username
            'user_username' => $request->username,

            // users_table.user_full_name
            'user_full_name' => $request->full_name,

            // users_table.user_email_address
            'user_email_address' => $request->email,

            // users_table.user_contact_number
            'user_contact_number' => $request->contact_number,

            // users_table.user_password
            'user_password' => Hash::make($request->password)

        ]);

        return redirect('/admin/users');
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function approvalLogs(): View
    {
        return view('admin.reports.approval-logs');
    }

    public function auditLogs(): View
    {
        return view('admin.reports.audit-logs');
    }

    public function maintenanceHistory(): View
    {
        return view('admin.reports.maintenance-history');
    }

    public function procurementHistory(): View
    {
        return view('admin.reports.procurement-history');
    }

    public function userLoginLogs(): View
    {
        return view('admin.reports.user-login-logs');
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    public function campusSetupPin(): View
    {
        $setting = CampusSetupSetting::query()->first();

        return view('admin.settings.campus-setup-pin', [
            'setting' => $setting,
        ]);
    }

    public function maintenanceSettings(): View
    {
        return view('admin.settings.maintenance-settings');
    }

    public function notificationSettings(): View
    {
        return view('admin.settings.notification-settings');
    }

    public function systemSettings(): View
    {
        return view('admin.settings.system-settings');
    }

    public function updateCampusSetupPin(Request $request)
    {
        $setting = CampusSetupSetting::query()->first() ?? new CampusSetupSetting();

        $rules = [
            'campus_setup_pin' => ['required', 'string', 'min:4', 'max:20', 'confirmed'],
        ];

        if (!empty($setting->campus_setup_pin_hash)) {
            $rules['current_campus_setup_pin'] = ['required', 'string', 'min:4', 'max:20'];
        }

        $validated = $request->validate($rules, [
            'current_campus_setup_pin.required' => 'Please enter the current PIN before saving a new one.',
            'campus_setup_pin.confirmed' => 'The new PIN confirmation does not match.',
        ]);

        if (!empty($setting->campus_setup_pin_hash)) {
            if (!Hash::check((string) $validated['current_campus_setup_pin'], (string) $setting->campus_setup_pin_hash)) {
                return back()
                    ->withErrors([
                        'current_campus_setup_pin' => 'The current PIN is incorrect.',
                    ])
                    ->withInput();
            }
        }

        $setting->campus_setup_pin_hash = Hash::make($validated['campus_setup_pin']);
        $setting->campus_setup_pin_updated_by = Auth::id();
        $setting->campus_setup_pin_updated_at = now();
        $setting->save();

        return back()->with('success', 'Campus setup PIN updated successfully.');
    }
    // =====================================================
    // ADDED RIS ADMIN APPROVAL: SHOW SUBMITTED RIS RECORDS
    // =====================================================


    public function risApprovals(Request $request)
    {
        // =====================================================
        // RIS APPROVALS - Full implementation matching
        // procurementReview() to provide all required variables
        // for the admin.procurement-review views.
        // =====================================================

        // Get selected status filter.
        $filter = strtolower($request->query('filter', 'all'));

        // Get live search value.
        $search = trim($request->query('search', ''));

        // Only allow these filter values.
        if (!in_array($filter, ['all', 'pending', 'approved', 'rejected', 'direct_approved'], true)) {
            $filter = 'all';
        }


        // =====================================================
        // BASE RIS QUERY
        // =====================================================

        $baseQuery = DB::table('requisition_issue_slip_table')

            ->leftJoin(
                'procurement_requests_table',
                'requisition_issue_slip_table.ris_procurement_request_id',
                '=',
                'procurement_requests_table.procurement_request_id'
            )

            ->leftJoin(
                'reports_table',
                'procurement_requests_table.procurement_request_report_id',
                '=',
                'reports_table.report_id'
            )

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            // LEFT JOIN RIS ITEMS SUBQUERY - computed total
            ->leftJoin(
                DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                'requisition_issue_slip_table.ris_id',
                '=',
                'ris_items_sum.ris_id'
            )

            // LEFT JOIN RIS ITEMS SUBQUERY - concatenated item names
            ->leftJoin(
                DB::raw('(SELECT ris_id, GROUP_CONCAT(COALESCE(ris_item_name_description, "N/A") SEPARATOR ", ") as ris_item_names FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_names'),
                'requisition_issue_slip_table.ris_id',
                '=',
                'ris_items_names.ris_id'
            )

            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'ris_items_sum.ris_calculated_total',
                'ris_items_names.ris_item_names'
            )

            // Include submitted + legacy/incomplete forms so old records stay visible for logging.
            ->where(function ($q) {
                $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date');
            });


        // =====================================================
        // DASHBOARD CARD COUNTS
        // =====================================================

        // New workflow statuses: Submitted / Under Review / Resubmitted.
        // Legacy status: Pending.
        $pendingRis = (clone $baseQuery)
            ->whereIn(
                'requisition_issue_slip_table.ris_status',
                ['Submitted', 'Under Review', 'Resubmitted', 'Pending']
            )
            ->count();

        // New workflow uses Minor Revision; legacy uses Rejected.
        $amendRis = (clone $baseQuery)
            ->whereIn(
                'requisition_issue_slip_table.ris_status',
                ['Minor Revision', 'Rejected']
            )
            ->count();

        $approvedRis = (clone $baseQuery)
            ->where(
                'requisition_issue_slip_table.ris_status',
                'Approved'
            )
            ->whereNotNull(
                'requisition_issue_slip_table.ris_approved_by_date'
            )
            ->where(function ($q) {
                $q->whereNull('requisition_issue_slip_table.ris_approved_by_signature')
                  ->orWhere('requisition_issue_slip_table.ris_approved_by_signature', 'like', 'data:image%');
            })
            ->count();

        $directApprovedRis = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('requisition_issue_slip_table.ris_status', 'Directly Approved')
                    ->orWhere(function ($legacy) {
                        $legacy->where('requisition_issue_slip_table.ris_status', 'Approved')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                            ->where('requisition_issue_slip_table.ris_approved_by_signature', 'not like', 'data:image%')
                            ->whereNull('requisition_issue_slip_table.ris_issued_by_date');
                    });
            })
            ->count();


        // =====================================================
        // TABLE QUERY
        // =====================================================

        $query = clone $baseQuery;


        // =====================================================
        // STATUS FILTER
        // =====================================================

        if ($filter === 'pending') {

            $query->whereIn(
                'requisition_issue_slip_table.ris_status',
                ['Submitted', 'Under Review', 'Resubmitted', 'Pending']
            );

        } elseif ($filter === 'approved') {

            $query->where(
                'requisition_issue_slip_table.ris_status',
                'Approved'
            )
            ->whereNotNull(
                'requisition_issue_slip_table.ris_approved_by_date'
            )
            ->where(function ($q) {
                $q->whereNull('requisition_issue_slip_table.ris_approved_by_signature')
                  ->orWhere('requisition_issue_slip_table.ris_approved_by_signature', 'like', 'data:image%');
            });

        } elseif ($filter === 'direct_approved') {

            $query->where(function ($q) {
                $q->where('requisition_issue_slip_table.ris_status', 'Directly Approved')
                    ->orWhere(function ($legacy) {
                        $legacy->where('requisition_issue_slip_table.ris_status', 'Approved')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                            ->where('requisition_issue_slip_table.ris_approved_by_signature', 'not like', 'data:image%')
                            ->whereNull('requisition_issue_slip_table.ris_issued_by_date');
                    });
            });

        } elseif ($filter === 'rejected') {

            // New workflow uses Minor Revision; legacy uses Rejected.
            $query->whereIn(
                'requisition_issue_slip_table.ris_status',
                ['Minor Revision', 'Rejected']
            );

        } else {

            // Show all RIS forms: current workflow + draft/incomplete + archived.
            $query->whereIn(
                'requisition_issue_slip_table.ris_status',
                [
                    'Draft',
                    'Submitted',
                    'Under Review',
                    'Resubmitted',
                    'Pending',
                    'Approved',
                    'Directly Approved',
                    'Minor Revision',
                    'Rejected',
                    'Archived',
                ]
            );
        }


        // =====================================================
        // SEARCH
        // =====================================================

        if ($search !== '') {

            $query->where(function ($searchQuery) use ($search) {

                $searchQuery
                    ->where(
                        'requisition_issue_slip_table.ris_form_number',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_requested_by_signature',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_manual_title',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_purpose_description',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'equipment_table.equipment_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'reports_table.report_unlisted_equipment_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_status',
                        'like',
                        '%' . $search . '%'
                    );

            });
        }


        // =====================================================
        // SORTING — latest first
        // =====================================================

        $risRecords = $query

            ->orderByDesc(DB::raw('COALESCE(requisition_issue_slip_table.ris_submitted_at, requisition_issue_slip_table.ris_requested_by_date, requisition_issue_slip_table.ris_created_at)'))

            ->orderByDesc(
                'requisition_issue_slip_table.ris_id'
            )

            ->paginate(10)

            ->appends([
                'filter' => $filter,
                'search' => $search,
            ]);


        // =====================================================
        // RETURN VIEW
        // =====================================================

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {

            if ($request->boolean('table_only')) {
                return view(
                    'admin.procurement-review._quick-access',
                    compact('risRecords', 'filter', 'search')
                );
            }

            return view(
                'admin.procurement-review._content',
                compact(
                    'risRecords',
                    'filter',
                    'search',
                    'pendingRis',
                    'amendRis',
                    'approvedRis',
                    'directApprovedRis'
                )
            );

        }

        return view(
            'admin.procurement-review.index',
            compact(
                'risRecords',
                'filter',
                'search',
                'pendingRis',
                'amendRis',
                'approvedRis',
                'directApprovedRis'
            )
        );
    }




    // =====================================================
    // ADMIN RIS PRINT / PREVIEW
    // =====================================================

    public function printRis($risId)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404, 'RIS not found.');

        $risItems = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get()
            ->pad(8, null);

        $presidentName = null;

        if (
            !empty($ris->ris_approved_by_signature) &&
            str_starts_with((string) $ris->ris_approved_by_signature, 'data:image')
        ) {
            try {
                $presidentApproval = DB::table('approval_logs_table')
                    ->leftJoin(
                        'users_table',
                        'approval_logs_table.approval_log_approved_by',
                        '=',
                        'users_table.user_id'
                    )
                    ->where('approval_logs_table.approval_log_reference_type', 'RIS')
                    ->where('approval_logs_table.approval_log_reference_id', (int) $risId)
                    ->where('approval_logs_table.approval_log_level', 'President')
                    ->where('approval_logs_table.approval_log_approval_status', 'Approved')
                    ->select('users_table.user_full_name')
                    ->first();

                if ($presidentApproval && !empty($presidentApproval->user_full_name)) {
                    $presidentName = $presidentApproval->user_full_name;
                }
            } catch (\Throwable $e) {
                $presidentName = null;
            }
        }

        return view('admin.ris.print', [
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => $presidentName,
        ]);
    }


    // =====================================================
    // ADMIN RIS TABLE PDF EXPORTS
    // =====================================================

    public function exportProcurementRisPdf(Request $request)
    {
        $filter = strtolower($request->query('filter', 'all'));
        $search = trim($request->query('search', ''));

        if (!in_array($filter, ['all', 'pending', 'approved', 'rejected', 'direct_approved'], true)) {
            $filter = 'all';
        }

        $query = $this->adminRisJoinQuery()
            ->where(function ($q) {
                $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date');
            });

        if ($filter === 'pending') {
            $query->whereIn('requisition_issue_slip_table.ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending']);
        } elseif ($filter === 'approved') {
            $query->where('requisition_issue_slip_table.ris_status', 'Approved')
                ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                ->where(function ($q) {
                    $q->whereNull('requisition_issue_slip_table.ris_approved_by_signature')
                        ->orWhere('requisition_issue_slip_table.ris_approved_by_signature', 'like', 'data:image%');
                });
        } elseif ($filter === 'direct_approved') {
            $query->where(function ($q) {
                $q->where('requisition_issue_slip_table.ris_status', 'Directly Approved')
                    ->orWhere(function ($legacy) {
                        $legacy->where('requisition_issue_slip_table.ris_status', 'Approved')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                            ->where('requisition_issue_slip_table.ris_approved_by_signature', 'not like', 'data:image%')
                            ->whereNull('requisition_issue_slip_table.ris_issued_by_date');
                    });
            });
        } elseif ($filter === 'rejected') {
            $query->whereIn('requisition_issue_slip_table.ris_status', ['Minor Revision', 'Rejected']);
        } else {
            $query->whereIn('requisition_issue_slip_table.ris_status', [
                'Draft', 'Submitted', 'Under Review', 'Resubmitted', 'Pending',
                'Approved', 'Directly Approved', 'Minor Revision', 'Rejected', 'Archived',
            ]);
        }

        $this->applyAdminRisSearch($query, $search);

        $rows = $query
            ->orderByDesc('requisition_issue_slip_table.ris_id')
            ->get()
            ->map(fn ($ris) => $this->mapAdminRisExportRow($ris));

        return $this->downloadAdminRisTablePdf(
            'Procurement Review — RIS Records',
            $rows,
            'procurement-review-ris.pdf'
        );
    }

    public function exportSignRisPdf(Request $request)
    {
        $filter = strtolower($request->query('filter', 'all'));
        $search = trim($request->query('search', ''));

        if (!in_array($filter, ['all', 'for_cosign', 'cosigned'], true)) {
            $filter = 'all';
        }

        $query = $this->adminRisJoinQuery()
            ->where('requisition_issue_slip_table.ris_status', 'Approved')
            ->where(
                'requisition_issue_slip_table.ris_approved_by_signature',
                'like',
                'data:image%'
            );

        if ($filter === 'for_cosign') {
            $query->whereNull('requisition_issue_slip_table.ris_issued_by_date');
        } elseif ($filter === 'cosigned') {
            $query->whereNotNull('requisition_issue_slip_table.ris_issued_by_date');
        }

        $this->applyAdminRisSearch($query, $search);

        $rows = $query
            ->orderByDesc('requisition_issue_slip_table.ris_id')
            ->get()
            ->map(fn ($ris) => $this->mapAdminRisExportRow($ris));

        return $this->downloadAdminRisTablePdf(
            'Sign RIS — Records',
            $rows,
            'sign-ris-records.pdf'
        );
    }

    public function exportSignatureHistoryPdf(Request $request)
    {
        $search = trim($request->query('search', ''));

        $query = $this->adminRisJoinQuery()
            ->where(function ($q) {
                $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_issued_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at');
            })
            ->whereNotIn('requisition_issue_slip_table.ris_status', [
                'Draft', 'Submitted', 'Under Review', 'Resubmitted', 'Pending',
            ]);

        $this->applyAdminRisSearch($query, $search, true);

        $rows = $query
            ->orderByDesc('requisition_issue_slip_table.ris_id')
            ->get()
            ->map(fn ($ris) => $this->mapAdminRisExportRow($ris));

        return $this->downloadAdminRisTablePdf(
            'Signature History — RIS Records',
            $rows,
            'signature-history-ris.pdf'
        );
    }

    private function adminRisJoinQuery()
    {
        return DB::table('requisition_issue_slip_table')
            ->leftJoin(
                'procurement_requests_table',
                'requisition_issue_slip_table.ris_procurement_request_id',
                '=',
                'procurement_requests_table.procurement_request_id'
            )
            ->leftJoin(
                'reports_table',
                'procurement_requests_table.procurement_request_report_id',
                '=',
                'reports_table.report_id'
            )
            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->leftJoin(
                DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                'requisition_issue_slip_table.ris_id',
                '=',
                'ris_items_sum.ris_id'
            )
            ->leftJoin(
                DB::raw('(SELECT ris_id, GROUP_CONCAT(COALESCE(ris_item_name_description, "N/A") SEPARATOR ", ") as ris_item_names FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_names'),
                'requisition_issue_slip_table.ris_id',
                '=',
                'ris_items_names.ris_id'
            )
            ->select(
                'requisition_issue_slip_table.*',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'ris_items_sum.ris_calculated_total',
                'ris_items_names.ris_item_names'
            );
    }

    private function applyAdminRisSearch($query, string $search, bool $includeIssuedBy = false): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function ($searchQuery) use ($search, $includeIssuedBy) {
            $searchQuery
                ->where('requisition_issue_slip_table.ris_form_number', 'like', '%' . $search . '%')
                ->orWhere('requisition_issue_slip_table.ris_requested_by_signature', 'like', '%' . $search . '%')
                ->orWhere('requisition_issue_slip_table.ris_manual_title', 'like', '%' . $search . '%')
                ->orWhere('equipment_table.equipment_name', 'like', '%' . $search . '%')
                ->orWhere('reports_table.report_unlisted_equipment_name', 'like', '%' . $search . '%')
                ->orWhere('requisition_issue_slip_table.ris_purpose_description', 'like', '%' . $search . '%')
                ->orWhere('requisition_issue_slip_table.ris_status', 'like', '%' . $search . '%');

            if ($includeIssuedBy) {
                $searchQuery->orWhere(
                    'requisition_issue_slip_table.ris_issued_by_signature',
                    'like',
                    '%' . $search . '%'
                );
            }
        });
    }

    private function mapAdminRisExportRow(object $ris): array
    {
        $equipment = $ris->ris_item_names
            ?: ($ris->ris_manual_title
                ?: ($ris->equipment_name
                    ?? $ris->report_unlisted_equipment_name
                    ?? (($ris->ris_request_type ?? null) === 'manual' ? 'Manual Procurement' : 'N/A')));

        return [
            'reference' => $ris->ris_form_number ?? ('RIS-' . $ris->ris_id),
            'purpose' => $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? 'N/A'),
            'equipment' => $equipment,
            'requested_by' => $ris->ris_requested_by_signature ?? 'N/A',
            'date' => $ris->ris_requested_by_date ?? ($ris->ris_approved_by_date ?? 'N/A'),
            'status' => $this->formatAdminRisStatusLabel($ris),
            'amount' => number_format((float) ($ris->ris_calculated_total ?? 0), 2),
        ];
    }

    private function formatAdminRisStatusLabel(object $ris): string
    {
        if (in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)) {
            return 'Pending';
        }

        if ($ris->ris_status === 'Directly Approved') {
            return 'Admin Approved';
        }

        if (
            $ris->ris_status === 'Approved'
            && !empty($ris->ris_approved_by_date)
            && !empty($ris->ris_approved_by_signature)
            && !str_starts_with((string) $ris->ris_approved_by_signature, 'data:image')
            && empty($ris->ris_issued_by_date)
        ) {
            return 'Admin Approved';
        }

        if (
            !empty($ris->ris_issued_by_date)
            && !empty($ris->ris_approved_by_signature)
            && str_starts_with((string) $ris->ris_approved_by_signature, 'data:image')
        ) {
            return 'Co-signed';
        }

        if (
            $ris->ris_status === 'Approved'
            && !empty($ris->ris_approved_by_signature)
            && str_starts_with((string) $ris->ris_approved_by_signature, 'data:image')
        ) {
            return 'Signed';
        }

        if ($ris->ris_status === 'Approved' && !empty($ris->ris_approved_by_date)) {
            return 'Forwarded to President';
        }

        if (in_array($ris->ris_status, ['Minor Revision', 'Rejected'], true)) {
            return 'Amend';
        }

        return (string) ($ris->ris_status ?? 'N/A');
    }

    private function downloadAdminRisTablePdf(string $title, $rows, string $filename)
    {
        $pdf = Pdf::loadView('admin.ris.table-export-pdf', [
            'title' => $title,
            'rows' => $rows,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }


    // =====================================================
    // ADDED RIS ADMIN APPROVAL: APPROVE RIS
    // =====================================================

    public function approveRis(Request $request, $risId)
    {
        return DB::transaction(function () use ($request, $risId) {
            $validated = $request->validate([
                'ris_issued_by' => ['required', 'string', 'max:255'],
                'ris_issued_by_date' => ['required', 'string', 'max:20'],
            ]);

            try {
                $issuedDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($validated['ris_issued_by_date']))
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
                return back()->with(
                    'error',
                    'Issued by date must be in dd/mm/yyyy format.'
                );
            }

            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            // New workflow: Submitted / Under Review / Resubmitted.
            // Legacy status: Pending.
            if (
                !in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true)
                || !$ris->ris_requested_by_date
            ) {
                return back()->with('error', 'Only submitted pending RIS records can be approved.');
            }

            $adminName = trim($validated['ris_issued_by']);

            // Admin signs Issued by only.
            // Approved by stays blank for the President.
            // approved_by_date marks it as forwarded into the President queue.
            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_status' => 'Approved',
                    'ris_issued_by_signature' => $adminName,
                    'ris_issued_by_date' => $issuedDate,
                    'ris_approved_by_signature' => null,
                    'ris_approved_by_date' => now()->toDateString(),
                ]);

            // Log the approval activity
            try {
                DB::table('approval_logs_table')->insert([
                    'approval_log_reference_type' => 'RIS',
                    'approval_log_reference_id' => (int) $risId,
                    'approval_log_level' => 'Admin',
                    'approval_log_approved_by' => Auth::id(),
                    'approval_log_approval_status' => 'Approved',
                    'approval_log_approval_remarks' => 'RIS signed (Issued by) by ' . $adminName . ' and forwarded to President.',
                    'approval_log_approved_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore logging failures
            }

            return back()->with('success', 'RIS signed and forwarded to the President for final approval.');
        });
    }

    public function directApproveForm(Request $request, $risId)
    {
        $mode = strtolower($request->query('mode', 'direct'));
        if (!in_array($mode, ['direct', 'forward'], true)) {
            $mode = 'direct';
        }

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        if (
            !in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true) ||
            empty($ris->ris_requested_by_date)
        ) {
            abort(403, 'Only submitted pending RIS records can be signed by Admin.');
        }

        $risItems = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get();

        return view('admin.procurement-review._direct-approve-form', [
            'ris' => $ris,
            'risItems' => $risItems,
            'mode' => $mode,
        ]);
    }

    public function directApproveRis(Request $request, $risId)
{
    return DB::transaction(function () use ($request, $risId) {

        // =====================================================
        // VALIDATE INPUT — Issued by + date only
        // =====================================================

        $validated = $request->validate([
            'ris_issued_by' => ['required', 'string', 'max:255'],
            'ris_issued_by_date' => ['required', 'string', 'max:20'],
        ]);

        try {
            $issuedDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($validated['ris_issued_by_date']))
                ->format('Y-m-d');
        } catch (\Throwable $e) {
            return back()->with(
                'error',
                'Issued by date must be in dd/mm/yyyy format.'
            );
        }

        // =====================================================
        // GET AND LOCK RIS
        // =====================================================

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->lockForUpdate()
            ->first();

        abort_if(!$ris, 404);

        // =====================================================
        // VALIDATE RIS
        // Only submitted Pending RIS can be directly approved
        // =====================================================

        // New workflow: Submitted / Under Review / Resubmitted.
        // Legacy status: Pending.
        if (
            !in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true) ||
            empty($ris->ris_requested_by_date)
        ) {
            return back()->with(
                'error',
                'Only submitted pending RIS records can be admin approved.'
            );
        }

        // =====================================================
        // DIRECTLY APPROVE RIS
        //
        // Admin signs Issued by only. Approved by stays blank
        // (reserved for President). Status = Directly Approved.
        // Returned to Purchaser — never appears in Sign RIS.
        // =====================================================

        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->update([
                'ris_status' => 'Directly Approved',
                'ris_issued_by_signature' => trim($validated['ris_issued_by']),
                'ris_issued_by_date' => $issuedDate,
                'ris_approved_by_signature' => null,
                'ris_approved_by_date' => null,
            ]);

        // =====================================================
        // APPROVAL LOG
        // Keep a record that this was a Direct Approval
        // =====================================================

        try {

            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $risId,
                'approval_log_level' => 'Admin Approval',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => 'Directly Approved',
                'approval_log_approval_remarks' => 'RIS admin approved by ' . trim($validated['ris_issued_by']) . ' (Issued by) and returned to Purchaser.',
                'approval_log_approved_at' => now(),
            ]);

        } catch (\Throwable $e) {

            // Approval must still succeed even if logging fails.

        }

        // =====================================================
        // RETURN TO PROCUREMENT REQUEST TABLE
        // =====================================================

        return redirect()
            ->route('admin.procurement-review.ris', [
                'filter' => 'all'
            ])
            ->with(
                'success',
                'RIS marked as Admin Approved and returned to the Purchaser.'
            );
    });
}

    // =====================================================
    // ADDED RIS ADMIN APPROVAL: REJECT RIS
    // =====================================================

public function rejectRis(Request $request, $risId)
    {
        return DB::transaction(function () use ($request, $risId) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            // New workflow: Submitted / Under Review / Resubmitted.
            // Legacy status: Pending.
            if (
                !in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true)
                || !$ris->ris_requested_by_date
            ) {
                return back()->with('error', 'Only submitted pending RIS records can be rejected.');
            }

            // Validate amendment remarks
            $remarks = $request->input('remarks', '');
            if (empty(trim($remarks))) {
                return back()->with('error', 'Please provide amendment remarks to inform the Purchaser what needs to be revised.');
            }

            // =====================================================
            // NEW WORKFLOW (Minor Revision)
            //
            // Keep the submission so the Purchaser can correct and
            // resubmit. We set status to Minor Revision and record
            // the revision note in ris_revision_notes_table.
            // =====================================================
            if (in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted'], true)) {

                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $risId)
                    ->update([
                        'ris_status' => 'Minor Revision',
                        'ris_rejection_reason' => $remarks,
                        'ris_updated_at' => now(),
                    ]);

                try {
                    DB::table('ris_revision_notes_table')->insert([
                        'ris_id' => (int) $risId,
                        'ris_revision_requested_by' => Auth::id(),
                        'ris_revision_type' => 'Minor Revision',
                        'ris_revision_note' => $remarks,
                        'ris_revision_created_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    // Ignore revision note failures
                }

            } else {

                // =====================================================
                // LEGACY WORKFLOW (Pending -> Draft)
                //
                // Reset submission fields so the Purchaser can edit
                // and resubmit as a Draft.
                // =====================================================
                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $risId)
                    ->update([
                        'ris_status' => 'Draft',
                        'ris_requested_by_signature' => null,
                        'ris_requested_by_date' => null,
                        'ris_submitted_by' => null,
                        'ris_submitted_at' => null,
                        'ris_rejection_reason' => $remarks,
                    ]);
            }

            // Log the amendment activity
            try {
                DB::table('approval_logs_table')->insert([
                    'approval_log_reference_type' => 'RIS',
                    'approval_log_reference_id' => (int) $risId,
                    'approval_log_level' => 'Admin',
                    'approval_log_approved_by' => Auth::id(),
                    'approval_log_approval_status' => 'Rejected',
                    'approval_log_approval_remarks' => $remarks,
                    'approval_log_approved_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore logging failures
            }

            return back()->with('success', 'RIS returned to Purchaser for amendment with your remarks.');
        });
    }

    // =====================================================
    // QUICK ACCESS MODAL CONTENT METHODS
    // =====================================================

    /**
     * Return the procurement review content partial (all RIS, sorted by latest).
     * Used by the Quick Access modal on the dashboard.
     */
    public function quickAccessProcurementContent(Request $request)
    {
        $request->merge(['table_only' => true]);
        return $this->risApprovals($request);
    }

    /**
     * Return the sign RIS content partial (all President-approved RIS).
     * Used by the Quick Access modal on the dashboard.
     */
    public function quickAccessSignRisContent(Request $request)
    {
        $request->merge(['table_only' => true]);
        return $this->signRis($request);
    }

    /**
     * Return the signature history content partial.
     * Used by the Quick Access modal on the dashboard.
     */
    public function quickAccessHistoryContent(Request $request)
    {
        $request->merge(['table_only' => true]);
        return $this->signatureHistory($request);
    }

    /**
     * Return users table only for Quick Access modal.
     */
    public function quickAccessUsersContent(Request $request)
    {
        $users = DB::table('users_table')
            ->leftJoin('roles_table', 'users_table.user_role_id', '=', 'roles_table.role_id')
            ->select(
                'users_table.user_id',
                'users_table.user_employee_id',
                'users_table.user_username',
                'users_table.user_full_name',
                'users_table.last_active_at',
                'roles_table.role_name'
            )
            ->orderBy('users_table.user_full_name')
            ->paginate(15);

        return view('admin.users._quick-access', compact('users'));
    }
}

