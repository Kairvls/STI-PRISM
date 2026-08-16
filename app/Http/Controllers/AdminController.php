<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CampusSetupSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $presidentRejectedRis = 0;
        $totalRisAmount = 0;
        $pendingRisAmount = 0;
        $adminApprovedAmount = 0;
        $presidentApprovedAmount = 0;
        $presidentRejectedAmount = 0;
        $budgetProposalYear = (int) now()->format('Y');
        $budgetProposalTotal = 0;
        $budgetPendingAmount = 0;
        $budgetAdminApprovedAmount = 0;
        $budgetPresidentApprovedAmount = 0;
        $budgetPresidentRejectedAmount = 0;

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

            $approvedRis = $this->applyRisPresidentApprovedScope(clone $baseRIS)->count();

            $directApprovedRis = $this->applyRisAdminApprovedScope(clone $baseRIS)->count();

            $cosignedRis = $this->applyRisCosignedScope(clone $baseRIS)->count();

            $presidentRejectedRis = $this->applyRisPresidentRejectedScope(clone $baseRIS)->count();
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

        $itemsJoin = DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum');

        try {
            $adminApprovedAmount = $this->applyRisAdminApprovedScope(
                DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->whereNotNull('ris_requested_by_date')
            )->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $adminApprovedAmount = 0; }

        try {
            $presidentApprovedAmount = $this->applyRisPresidentApprovedScope(
                DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->whereNotNull('ris_requested_by_date')
            )->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $presidentApprovedAmount = 0; }

        try {
            $presidentRejectedAmount = $this->applyRisPresidentRejectedScope(
                DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->whereNotNull('ris_requested_by_date')
            )->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $presidentRejectedAmount = 0; }

        try {
            $budgetProposalTotal = DB::table('requisition_issue_slip_table')
                ->leftJoin($itemsJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                ->whereNotNull('ris_requested_by_date')
                ->whereYear('ris_requested_by_date', $budgetProposalYear)
                ->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $budgetProposalTotal = 0; }

        $yearAmountBase = function () use ($itemsJoin, $budgetProposalYear) {
            return DB::table('requisition_issue_slip_table')
                ->leftJoin($itemsJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                ->whereNotNull('ris_requested_by_date')
                ->whereYear('ris_requested_by_date', $budgetProposalYear);
        };

        try {
            $budgetPendingAmount = (clone $yearAmountBase())
                ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending'])
                ->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $budgetPendingAmount = 0; }

        try {
            $budgetAdminApprovedAmount = $this->applyRisAdminApprovedScope($yearAmountBase())
                ->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $budgetAdminApprovedAmount = 0; }

        try {
            $budgetPresidentApprovedAmount = $this->applyRisPresidentApprovedScope($yearAmountBase())
                ->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $budgetPresidentApprovedAmount = 0; }

        try {
            $budgetPresidentRejectedAmount = $this->applyRisPresidentRejectedScope($yearAmountBase())
                ->sum('ris_items_sum.ris_calculated_total');
        } catch (\Throwable $e) { $budgetPresidentRejectedAmount = 0; }


        // =====================================================
        // DIGITAL SIGNATURE STATS
        // =====================================================

        $forCosigningCount = 0;
        $cosignedCount = 0;

        try {
            $forCosigningCount = $this->applyRisAwaitingAdminActionScope(
                DB::table('requisition_issue_slip_table as ris'),
                'ris'
            )->count();
        } catch (\Throwable $e) { $forCosigningCount = 0; }

        try {
            $cosignedCount = $this->applyRisCosignedScope(
                DB::table('requisition_issue_slip_table')
            )->count();
        } catch (\Throwable $e) { $cosignedCount = 0; }


        // =====================================================
        // CALENDAR OF EVENTS — PROCUREMENT / RIS DATES
        // =====================================================

        $calendarEvents = $this->adminProcurementCalendarEvents();
        $calendarEventsByDate = [];
        foreach ($calendarEvents as $evt) {
            $dateKey = $evt->event_date ?? null;
            if ($dateKey) {
                if (!isset($calendarEventsByDate[$dateKey])) {
                    $calendarEventsByDate[$dateKey] = [];
                }
                $calendarEventsByDate[$dateKey][] = $evt;
            }
        }


        // =====================================================
        // SUPPLIER COMPARISON — ATP spend by supplier
        // =====================================================

        $supplierComparison = collect();
        $supplierTypeComparison = [
            'physical_count' => 0,
            'online_count' => 0,
            'physical_amount' => 0,
            'online_amount' => 0,
        ];
        $supplierComparisonMax = 0;

        try {
            if (Schema::hasTable('authority_to_purchase_table')) {
                $query = DB::table('authority_to_purchase_table');

                if (Schema::hasTable('physical_suppliers_table')) {
                    $query->leftJoin(
                        'physical_suppliers_table',
                        'physical_suppliers_table.supplier_id',
                        '=',
                        'authority_to_purchase_table.authority_purchase_supplier_id'
                    );
                }
                if (Schema::hasTable('online_suppliers_table')) {
                    $query->leftJoin(
                        'online_suppliers_table',
                        'online_suppliers_table.supplier_id',
                        '=',
                        'authority_to_purchase_table.authority_purchase_supplier_id'
                    );
                }
                if (Schema::hasTable('suppliers_table')) {
                    $query->leftJoin(
                        'suppliers_table',
                        'suppliers_table.supplier_id',
                        '=',
                        'authority_to_purchase_table.authority_purchase_supplier_id'
                    );
                }
                if (Schema::hasTable('authority_to_purchase_items_table')) {
                    $query->leftJoin(
                        'authority_to_purchase_items_table',
                        'authority_to_purchase_items_table.authority_purchase_id',
                        '=',
                        'authority_to_purchase_table.authority_purchase_id'
                    );
                }

                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_is_archived')) {
                    $query->where(function ($q) {
                        $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                            ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
                    });
                }

                $nameParts = ["'Unnamed supplier'"];
                if (Schema::hasTable('online_suppliers_table')) {
                    array_unshift($nameParts, 'online_suppliers_table.shop_name');
                }
                if (Schema::hasTable('physical_suppliers_table')) {
                    array_unshift($nameParts, 'physical_suppliers_table.company_name');
                }
                $nameExpr = 'COALESCE(' . implode(', ', $nameParts) . ')';
                $typeExpr = Schema::hasTable('suppliers_table')
                    ? "COALESCE(suppliers_table.supplier_store_type, 'Unknown')"
                    : "'Unknown'";

                $rows = $query
                    ->select(
                        DB::raw($nameExpr . ' as supplier_name'),
                        DB::raw($typeExpr . ' as supplier_type'),
                        DB::raw('COUNT(DISTINCT authority_to_purchase_table.authority_purchase_id) as atp_count'),
                        DB::raw(Schema::hasTable('authority_to_purchase_items_table')
                            ? 'SUM(COALESCE(authority_to_purchase_items_table.atp_amount, 0)) as total_amount'
                            : '0 as total_amount')
                    )
                    ->groupBy(DB::raw($nameExpr), DB::raw($typeExpr))
                    ->orderByDesc('total_amount')
                    ->limit(6)
                    ->get();

                $supplierComparison = $rows;
                $supplierComparisonMax = (float) ($rows->max('total_amount') ?: 0);

                foreach ($rows as $row) {
                    $type = strtolower((string) ($row->supplier_type ?? ''));
                    $amount = (float) ($row->total_amount ?? 0);
                    $count = (int) ($row->atp_count ?? 0);
                    if (str_contains($type, 'online')) {
                        $supplierTypeComparison['online_count'] += $count;
                        $supplierTypeComparison['online_amount'] += $amount;
                    } else {
                        $supplierTypeComparison['physical_count'] += $count;
                        $supplierTypeComparison['physical_amount'] += $amount;
                    }
                }
            }
        } catch (\Throwable $e) {
            $supplierComparison = collect();
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
                ->whereIn('approval_logs_table.approval_log_approval_status', [
                    'Approved',
                    'Co-signed',
                    'Rejected',
                    'Directly Approved',
                    'Admin Approved',
                    'Forwarded to President',
                ])
                ->orderByDesc('approval_logs_table.approval_log_approved_at')
                ->limit(2)
                ->get()
                ->map(function ($log) {
                    $log->is_pending = false;
                    $statusLabel = match ((string) $log->status) {
                        'Directly Approved' => 'Admin Approved',
                        default => $log->status,
                    };
                    $log->title = $statusLabel . ' — ' . ($log->ref_type ?? 'RIS');
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
        // Admin Approved, Approved by the President, Amend
        // =====================================================

        $risTrendLabels = [];
        $risTrendApproved = [];
        $risTrendForwarded = [];
        $risTrendAmend = [];
        $risTrendRejected = [];

        try {
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ];

            for ($i = 5; $i >= 0; $i--) {
                $month = now()->copy()->subMonths($i)->startOfMonth();
                $year = (int) $month->format('Y');
                $monthNum = (int) $month->format('n');
                $monthStart = $month->copy()->startOfMonth();
                $monthEnd = $month->copy()->endOfMonth();

                $monthBase = DB::table('requisition_issue_slip_table')
                    ->whereNotNull('ris_created_at')
                    ->whereBetween('ris_created_at', [$monthStart, $monthEnd]);

                $risTrendLabels[] = ($monthNames[$monthNum] ?? $month->format('F')) . ' ' . $year;
                $risTrendApproved[] = $this->applyRisAdminApprovedScope(clone $monthBase)->count();
                $risTrendForwarded[] = $this->applyRisPresidentApprovedScope(clone $monthBase)->count();
                $risTrendAmend[] = (clone $monthBase)
                    ->whereIn('ris_status', ['Minor Revision', 'Rejected'])
                    ->count();
                $risTrendRejected[] = $this->applyRisPresidentRejectedScope(clone $monthBase)->count();
            }
        } catch (\Throwable $e) {
            $risTrendLabels = [];
            $risTrendApproved = [];
            $risTrendForwarded = [];
            $risTrendAmend = [];
            $risTrendRejected = [];
        }


        // =====================================================
        // RIS STATUS DISTRIBUTION FOR CHART
        // =====================================================

        $risStatusChart = [
            'labels' => [
                'Pending',
                'Approved by the President',
                'Admin Approved',
                'Amend',
                'Rejected by the President',
            ],
            'data' => [
                $pendingRis,
                $approvedRis,
                $directApprovedRis,
                $amendRis,
                $presidentRejectedRis,
            ],
            'colors' => [
                '#d97706',
                '#059669',
                '#38bdf8',
                '#f59e0b',
                '#e11d48',
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
                ->leftJoin($this->risReleasedJoin(), 'requisition_issue_slip_table.ris_id', '=', 'ris_released.released_ris_id')
                ->select(
                    'requisition_issue_slip_table.*',
                    'equipment_table.equipment_name',
                    'reports_table.report_unlisted_equipment_name',
                    'ris_items_sum.ris_calculated_total',
                    'ris_items_names.ris_item_names',
                    'ris_released.released_ris_id'
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
            'adminApprovedAmount',
            'presidentApprovedAmount',
            'presidentRejectedAmount',
            'budgetProposalYear',
            'budgetProposalTotal',
            'budgetPendingAmount',
            'budgetAdminApprovedAmount',
            'budgetPresidentApprovedAmount',
            'budgetPresidentRejectedAmount',

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
            'risTrendRejected',
            'risStatusChart',

            // Recent records
            'recentRisRecords',

            // Calendar events
            'calendarEvents',
            'calendarEventsByDate',
            'supplierComparison',
            'supplierComparisonMax',
            'supplierTypeComparison',

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

    $filter = strtolower($request->query('filter', 'pending'));
    $search = trim($request->query('search', ''));

    if (!in_array($filter, ['pending', 'forwarded', 'all'], true)) {
        $filter = 'pending';
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
        ->leftJoin($this->risReleasedJoin(), 'requisition_issue_slip_table.ris_id', '=', 'ris_released.released_ris_id')

        ->select(
            'requisition_issue_slip_table.*',
            'procurement_requests_table.procurement_request_id',
            'reports_table.report_id',
            'reports_table.report_unlisted_equipment_name',
            'equipment_table.equipment_name',
            'ris_items_sum.ris_calculated_total',
            'ris_items_names.ris_item_names',
            'ris_released.released_ris_id'
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
    $pendingRis = $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'pending')
        ->distinct()
        ->count('requisition_issue_slip_table.ris_id');

<<<<<<< Updated upstream
    $forwardedRis = $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'forwarded')
        ->distinct()
        ->count('requisition_issue_slip_table.ris_id');

    $allRis = $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'all')
        ->distinct()
        ->count('requisition_issue_slip_table.ris_id');
=======
    $pendingRis = (clone $baseQuery)
        ->whereIn(
            'requisition_issue_slip_table.ris_status',
            $this->adminReviewableStatuses()
        )
        ->count();

    $amendRis = (clone $baseQuery)
        ->where(
            'requisition_issue_slip_table.ris_status',
            'Minor Revision'
        )
        ->count();
>>>>>>> Stashed changes

    $pendingRisAmount = (float) $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'pending')
        ->sum('ris_items_sum.ris_calculated_total');
    $forwardedRisAmount = (float) $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'forwarded')
        ->sum('ris_items_sum.ris_calculated_total');
    $allRisAmount = (float) $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'all')
        ->sum('ris_items_sum.ris_calculated_total');


    // =====================================================
    // TABLE QUERY
    // =====================================================

    $query = clone $baseQuery;


    // =====================================================
    // STATUS FILTER
    // =====================================================

<<<<<<< Updated upstream
    $this->applyProcurementReviewStatusFilter($query, $filter);
=======
    if ($filter === 'pending') {

        $query->whereIn(
            'requisition_issue_slip_table.ris_status',
            $this->adminReviewableStatuses()
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

        $query->where(
            'requisition_issue_slip_table.ris_status',
            'Approved'
        )
        ->whereNotNull(
            'requisition_issue_slip_table.ris_approved_by_date'
        )
        ->whereNotNull(
            'requisition_issue_slip_table.ris_approved_by_signature'
        )
        ->where(
            'requisition_issue_slip_table.ris_approved_by_signature',
            'not like',
            'data:image%'
        );

    } elseif ($filter === 'rejected') {

        $query->where(
            'requisition_issue_slip_table.ris_status',
            'Minor Revision'
        );

    } else {

        $query->whereIn(
            'requisition_issue_slip_table.ris_status',
            [
                'Submitted',
                'Under Review',
                'Resubmitted',
                'Minor Revision',
                'Approved',
                'Rejected',
            ]
        );
    }
>>>>>>> Stashed changes


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

<<<<<<< Updated upstream
        ->orderByDesc(DB::raw('COALESCE(requisition_issue_slip_table.ris_submitted_at, requisition_issue_slip_table.ris_requested_by_date, requisition_issue_slip_table.ris_created_at)'))
=======
        ->orderByRaw("
            CASE
                WHEN requisition_issue_slip_table.ris_status IN ('Submitted', 'Resubmitted', 'Under Review') THEN 0
                WHEN requisition_issue_slip_table.ris_status = 'Minor Revision' THEN 1
                WHEN requisition_issue_slip_table.ris_status = 'Approved' THEN 2
                WHEN requisition_issue_slip_table.ris_status = 'Rejected' THEN 3
                ELSE 4
            END
        ")

        ->orderByDesc(
            'requisition_issue_slip_table.ris_requested_by_date'
        )
>>>>>>> Stashed changes

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
                'forwardedRis',
                'allRis',
                'pendingRisAmount',
                'forwardedRisAmount',
                'allRisAmount'
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
            'forwardedRis',
            'allRis',
            'pendingRisAmount',
            'forwardedRisAmount',
            'allRisAmount'
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
    // President-approved RIS (admin signs Issued by and returns to Purchaser)
    // and President-rejected RIS (logged only).

    $filter = strtolower($request->query('filter', 'for_cosign'));
    $search = trim($request->query('search', ''));

    if (!in_array($filter, ['all', 'for_cosign', 'cosigned', 'president_rejected'], true)) {
        $filter = 'for_cosign';
    }

        $releasedJoin = DB::raw('(
        SELECT DISTINCT approval_log_reference_id AS released_ris_id
        FROM approval_logs_table
        WHERE approval_log_reference_type = "RIS"
          AND (
                approval_log_approval_remarks LIKE "%returned to Purchaser%"
             OR approval_log_approval_status = "Co-signed"
             OR approval_log_level IN ("Admin Return", "Admin Co-sign")
          )
    ) as ris_released');

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
        ->leftJoin($releasedJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_released.released_ris_id')
        ->select(
            'requisition_issue_slip_table.*',
            'procurement_requests_table.procurement_request_id',
            'reports_table.report_id',
            'reports_table.report_unlisted_equipment_name',
            'equipment_table.equipment_name',
            'ris_items_sum.ris_calculated_total',
            'ris_items_names.ris_item_names',
            'ris_released.released_ris_id'
        )
        ->where(function ($q) {
            $q->where(function ($approved) {
                $approved->whereIn('requisition_issue_slip_table.ris_status', ['Approved', 'Approved by the President'])
                    ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                    ->whereRaw('TRIM(requisition_issue_slip_table.ris_approved_by_signature) != ""');
            })
            ->orWhereIn('requisition_issue_slip_table.ris_status', [
                'Rejected by President',
                'Rejected by the President',
                'Rejected',
            ]);
        });

    $presidentApprovedQuery = function ($query) {
        $query->whereIn('requisition_issue_slip_table.ris_status', ['Approved', 'Approved by the President'])
            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
            ->whereRaw('TRIM(requisition_issue_slip_table.ris_approved_by_signature) != ""');
    };

    $awaitingQuery = function ($query) use ($presidentApprovedQuery) {
        $query->where($presidentApprovedQuery)
            ->where(function ($unsigned) {
                $unsigned->whereNull('requisition_issue_slip_table.ris_issued_by_signature')
                    ->orWhereRaw('TRIM(requisition_issue_slip_table.ris_issued_by_signature) = ""');
            })
            ->whereNull('ris_released.released_ris_id');
    };

    $returnedQuery = function ($query) use ($presidentApprovedQuery) {
        $query->where($presidentApprovedQuery)
            ->where(function ($signed) {
                $signed->where(function ($issued) {
                    $issued->whereNotNull('requisition_issue_slip_table.ris_issued_by_signature')
                        ->whereRaw('TRIM(requisition_issue_slip_table.ris_issued_by_signature) != ""');
                })->orWhereNotNull('ris_released.released_ris_id');
            });
    };

    $presidentRejectedQuery = function ($query) {
        $query->whereIn('requisition_issue_slip_table.ris_status', [
            'Rejected by President',
            'Rejected by the President',
            'Rejected',
        ]);
    };

    $forCosignCount = (clone $baseQuery)->where($awaitingQuery)->count();
    $cosignedCount = (clone $baseQuery)->where($returnedQuery)->count();
    $presidentRejectedCount = (clone $baseQuery)->where($presidentRejectedQuery)->count();
    $allCount = (clone $baseQuery)->count();

    $forCosignAmount = (clone $baseQuery)->where($awaitingQuery)->sum('ris_items_sum.ris_calculated_total');
    $cosignedAmount = (clone $baseQuery)->where($returnedQuery)->sum('ris_items_sum.ris_calculated_total');
    $presidentRejectedAmount = (clone $baseQuery)->where($presidentRejectedQuery)->sum('ris_items_sum.ris_calculated_total');
    $allAmount = (clone $baseQuery)->sum('ris_items_sum.ris_calculated_total');

    $query = clone $baseQuery;

    if ($filter === 'for_cosign') {
        $query->where($awaitingQuery);
    } elseif ($filter === 'cosigned') {
        $query->where($returnedQuery);
    } elseif ($filter === 'president_rejected') {
        $query->where($presidentRejectedQuery);
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

        ->orderByDesc('requisition_issue_slip_table.ris_id')

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
                'presidentRejectedCount',
                'allCount',
                'forCosignAmount',
                'cosignedAmount',
                'presidentRejectedAmount',
                'allAmount'
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
            'presidentRejectedCount',
            'allCount',
            'forCosignAmount',
            'cosignedAmount',
            'presidentRejectedAmount',
            'allAmount'
        )
    );
}

    public function signatureHistory(Request $request): View
    {
        // =====================================================
        // SIGNATURE HISTORY
        // Log of identifiable RIS forms, including incomplete
        // and still-in-progress records. Use the filter chips
        // to narrow by outcome.
        // =====================================================

        // Get search value (default empty).
        $search = trim($request->query('search', ''));
        $filter = strtolower($request->query('filter', 'all'));

        if (!in_array($filter, ['all', 'direct_approved', 'president_approved', 'president_rejected', 'amend'], true)) {
            $filter = 'all';
        }


        // =====================================================
        // BASE QUERY - Identifiable RIS forms
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
            ->leftJoin($this->risReleasedJoin(), 'requisition_issue_slip_table.ris_id', '=', 'ris_released.released_ris_id')

            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'ris_items_sum.ris_calculated_total',
                'ris_items_names.ris_item_names',
                'ris_released.released_ris_id'
            )

<<<<<<< Updated upstream
            // Keep every identifiable RIS visible for logging,
            // including incomplete, forwarded, and already-decided forms.
            ->where(function ($q) {
                $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_issued_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_created_at');
            });
=======
            // Only RIS forms that have been submitted
            ->whereNotNull(
                'requisition_issue_slip_table.ris_requested_by_date'
            )

            ->whereNotIn(
                'requisition_issue_slip_table.ris_status',
                array_merge(['Draft'], $this->adminReviewableStatuses())
            );
>>>>>>> Stashed changes


        // Card counts use the same scopes as the filter chips.
        $allCount = (clone $baseQuery)->count();
        $directApprovedCount = $this->applyRisAdminApprovedScope(clone $baseQuery)->count();
        $presidentApprovedCount = $this->applyRisPresidentApprovedScope(clone $baseQuery)->count();
        $presidentRejectedCount = $this->applyRisPresidentRejectedScope(clone $baseQuery)->count();
        $amendedCount = (clone $baseQuery)
            ->whereIn('requisition_issue_slip_table.ris_status', ['Minor Revision', 'Rejected'])
            ->count();


        // =====================================================
        // TABLE QUERY
        // =====================================================

        $query = clone $baseQuery;

        if ($filter === 'direct_approved') {
            $this->applyRisAdminApprovedScope($query);
        } elseif ($filter === 'president_approved') {
            $this->applyRisPresidentApprovedScope($query);
        } elseif ($filter === 'president_rejected') {
            $this->applyRisPresidentRejectedScope($query);
        } elseif ($filter === 'amend') {
            $query->whereIn('requisition_issue_slip_table.ris_status', ['Minor Revision', 'Rejected']);
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
                'filter' => $filter,
            ]);


        // =====================================================
        // RETURN VIEW
        // =====================================================

        // AJAX requests return only the content partial
        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {

            if ($request->boolean('table_only')) {
                return view(
                    'admin.digital-signatures._signature-history-quick-access',
                    compact('signatureHistory', 'search', 'filter')
                );
            }

            return view(
                'admin.digital-signatures._signature-history-content',
                compact(
                    'signatureHistory',
                    'search',
                    'filter',
                    'allCount',
                    'directApprovedCount',
                    'presidentApprovedCount',
                    'presidentRejectedCount',
                    'amendedCount'
                )
            );

        }

        return view(
            'admin.digital-signatures.signature-history',
            compact(
                'signatureHistory',
                'search',
                'filter',
                'allCount',
                'directApprovedCount',
                'presidentApprovedCount',
                'presidentRejectedCount',
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
        $decision = $request->input('decision', 'Approved');
        $adminName = $request->input('admin_name', $request->input('ris_issued_by'));
        $adminDate = $request->input('admin_date', $request->input('ris_issued_by_date'));

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

        // President approved (plain name or image). Admin signs Issued by.
        $presidentApproved = in_array($target->ris_status, ['Approved', 'Approved by the President'], true)
            && trim((string) ($target->ris_approved_by_signature ?? '')) !== '';

        if (!$presidentApproved) {
            return back()->with('error', 'Only RIS records approved by the President can be signed by Admin.');
        }

        $alreadyIssued = trim((string) ($target->ris_issued_by_signature ?? '')) !== ''
            || !empty($target->ris_issued_by_date);

        if ($alreadyIssued) {
            return back()->with('error', 'This RIS has already been signed by Admin.');
        }

        $issuedDate = $this->parseFlexibleDate((string) $adminDate) ?: $adminDate;

        $updateValues = [
            'ris_status' => 'Approved by the President',
        ];

        if ($decision === 'Approved') {
            $updateValues['ris_issued_by_signature'] = trim($adminName);
            $updateValues['ris_issued_by_date'] = $issuedDate;
        } else {
            return back()->with('error', 'Only signing Issued by is supported for President-approved RIS.');
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
                'approval_log_approval_remarks' => 'RIS signed (Issued by) by ' . trim($adminName) . ' after President approval and returned to Purchaser.',
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        return redirect()
            ->route('admin.digital-signatures.sign-ris')
            ->with('success', 'RIS signed (Issued by) and returned to the Purchaser.');
    }

    public function returnRisToPurchaser($risId)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        if (
            !in_array($ris->ris_status, ['Approved', 'Approved by the President'], true)
            || empty($ris->ris_approved_by_signature)
        ) {
            return back()->with(
                'error',
                'Only President-approved RIS records can be returned to the Purchaser. Current status: ' . ($ris->ris_status ?: 'unknown')
            );
        }

        $alreadyReturned = DB::table('approval_logs_table')
            ->where('approval_log_reference_type', 'RIS')
            ->where('approval_log_reference_id', (int) $risId)
            ->where(function ($q) {
                $q->where('approval_log_approval_remarks', 'like', '%returned to Purchaser%')
                    ->orWhere('approval_log_approval_status', 'Co-signed')
                    ->orWhereIn('approval_log_level', ['Admin Return', 'Admin Co-sign']);
            })
            ->exists();

        if ($alreadyReturned) {
            return back()->with('error', 'This RIS has already been returned to the Purchaser.');
        }

        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $risId,
                'approval_log_level' => 'Admin',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => 'Approved',
                'approval_log_approval_remarks' => 'President-approved RIS returned to Purchaser.',
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            try {
                DB::table('approval_logs_table')->insert([
                    'approval_log_reference_type' => 'RIS',
                    'approval_log_reference_id' => (int) $risId,
                    'approval_log_approved_by' => Auth::id(),
                    'approval_log_approval_status' => 'Approved',
                    'approval_log_approval_remarks' => 'President-approved RIS returned to Purchaser.',
                    'approval_log_approved_at' => now(),
                ]);
            } catch (\Throwable $inner) {
                return back()->with('error', 'Could not return this RIS to the Purchaser.');
            }
        }

        return back()->with('success', 'RIS returned to the Purchaser. They can now create an ATP.');
    }

    public function returnRisForRevision(Request $request, $risId)
    {
        $remarks = trim((string) $request->input('remarks', ''));
        if ($remarks === '') {
            return back()->with('error', 'Please provide remarks so the Purchaser knows what to revise.');
        }

        return DB::transaction(function () use ($risId, $remarks) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            if ($ris->ris_status !== 'Rejected') {
                return back()->with('error', 'Only President-rejected RIS records can be returned for revision.');
            }

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_status' => 'Minor Revision',
                    'ris_rejection_reason' => $remarks,
                    'ris_approved_by_signature' => null,
                    'ris_approved_by_date' => null,
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
                // ignore
            }

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
                // ignore
            }

            return back()->with('success', 'RIS returned to the Purchaser for revision.');
        });
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
        $query = DB::table('users_table')
            ->leftJoin('roles_table', 'users_table.user_role_id', '=', 'roles_table.role_id')
            ->select(
                'users_table.user_id',
                'users_table.user_employee_id',
                'users_table.user_username',
                'users_table.user_full_name',
                'users_table.user_email_address',
                'users_table.user_contact_number',
                'users_table.user_role_id',
                'roles_table.role_name'
            )
            ->orderBy('users_table.user_full_name');

        if (Schema::hasColumn('users_table', 'last_active_at')) {
            $query->addSelect('users_table.last_active_at');
        }

        $users = $query->get();
        $roles = Schema::hasTable('roles_table')
            ? DB::table('roles_table')->orderBy('role_id')->get()
            : collect();

        $activeCount = 0;
        if (Schema::hasColumn('users_table', 'last_active_at')) {
            $activeCount = $users->filter(function ($user) {
                return !empty($user->last_active_at)
                    && \Carbon\Carbon::parse($user->last_active_at)->gte(now()->subDays(30));
            })->count();
        } elseif (Schema::hasTable('sessions')) {
            $activeIds = DB::table('sessions')->whereNotNull('user_id')->pluck('user_id')->unique();
            $activeCount = $users->whereIn('user_id', $activeIds)->count();
        }

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'totalUsers' => $users->count(),
            'activeUsers' => $activeCount,
            'roleCount' => $users->pluck('role_name')->filter()->unique()->count(),
        ]);
    }

    public function createUser()
    {
        return redirect('/admin/users');
    }

    public function editUser()
    {
        return redirect('/admin/users');
    }

    public function viewUser()
    {
        return redirect('/admin/users');
    }

    public function resetPassword()
    {
        return redirect('/admin/users');
    }

    public function userActivityLogs()
    {
        return redirect('/admin/reports/user-login-logs');
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

    public function systemReports()
    {
        return redirect('/admin/reports/maintenance-history');
    }

    public function approvalLogs(Request $request): View
    {
        $filters = $this->systemReportFilters($request);
        $rows = $this->emptyReportPager($request);

        if (Schema::hasTable('approval_logs_table')) {
            $query = DB::table('approval_logs_table')
                ->leftJoin('users_table', 'users_table.user_id', '=', 'approval_logs_table.approval_log_approved_by');

            $select = [
                'approval_logs_table.approval_log_id',
                'approval_logs_table.approval_log_reference_type',
                'approval_logs_table.approval_log_reference_id',
                'approval_logs_table.approval_log_approval_status',
                'approval_logs_table.approval_log_approval_remarks',
                'approval_logs_table.approval_log_approved_at',
                'users_table.user_full_name as officer_name',
            ];
            if (Schema::hasColumn('approval_logs_table', 'approval_log_level')) {
                $select[] = 'approval_logs_table.approval_log_level';
            }
            $query->select($select);
            $this->applyDateFilter($query, 'approval_logs_table.approval_log_approved_at', $filters);

            if ($filters['q'] !== '') {
                $needle = '%'.$filters['q'].'%';
                $query->where(function ($q) use ($needle) {
                    $q->where('users_table.user_full_name', 'like', $needle)
                        ->orWhere('approval_logs_table.approval_log_approval_status', 'like', $needle)
                        ->orWhere('approval_logs_table.approval_log_approval_remarks', 'like', $needle)
                        ->orWhere('approval_logs_table.approval_log_reference_type', 'like', $needle);
                });
            }

            $rows = $query->orderByDesc('approval_logs_table.approval_log_approved_at')->paginate(10)->withQueryString();
        }

        return view('admin.reports.approval-logs', [
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function auditLogs()
    {
        return redirect('/admin/reports/approval-logs');
    }

    public function maintenanceHistory(Request $request): View
    {
        $filters = $this->systemReportFilters($request);
        $rows = new LengthAwarePaginator([], 0, 10, max(1, (int) $request->query('page', 1)), [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        $byStatus = collect();
        $repeatEquipment = collect();
        $avgCloseHours = null;

        if (Schema::hasTable('reports_table')) {
            $base = DB::table('reports_table')
                ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'reports_table.report_room_id')
                ->leftJoin('floors_table', 'rooms_table.room_floor_id', '=', 'floors_table.floor_id')
                ->leftJoin('buildings_table', 'floors_table.floor_building_id', '=', 'buildings_table.building_id')
                ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'reports_table.report_equipment_id')
                ->leftJoin('users_table as technicians', 'technicians.user_id', '=', 'reports_table.report_assigned_personnel_id')
                ->leftJoin('reporters_table', 'reporters_table.reporter_employee_id', '=', 'reports_table.report_reporter_employee_id');

            $this->applyDateFilter($base, 'reports_table.report_submitted_at', $filters);

            if ($filters['q'] !== '') {
                $needle = '%'.$filters['q'].'%';
                $base->where(function ($q) use ($needle) {
                    $q->where('equipment_table.equipment_name', 'like', $needle)
                        ->orWhere('reports_table.report_unlisted_equipment_name', 'like', $needle)
                        ->orWhere('reports_table.report_suggested_issue', 'like', $needle)
                        ->orWhere('reports_table.report_current_status', 'like', $needle)
                        ->orWhere('rooms_table.room_name', 'like', $needle)
                        ->orWhere('buildings_table.building_name', 'like', $needle)
                        ->orWhere('technicians.user_full_name', 'like', $needle);
                });
            }

            $byStatus = (clone $base)
                ->select('reports_table.report_current_status', DB::raw('COUNT(*) as total'))
                ->groupBy('reports_table.report_current_status')
                ->pluck('total', 'report_current_status');

            $closed = (clone $base)->whereIn('reports_table.report_current_status', ['Resolved', 'Rejected', 'For Replacement']);
            $avgCloseHours = (clone $closed)->avg(DB::raw('TIMESTAMPDIFF(HOUR, reports_table.report_submitted_at, reports_table.report_updated_at)'));

            $repeatEquipment = (clone $base)
                ->select(
                    DB::raw("COALESCE(equipment_table.equipment_name, reports_table.report_unlisted_equipment_name, 'Unlisted') as equipment_label"),
                    DB::raw('COUNT(*) as report_count')
                )
                ->groupBy('equipment_label')
                ->having('report_count', '>=', 2)
                ->orderByDesc('report_count')
                ->limit(8)
                ->get();

            $rows = (clone $base)
                ->select(
                    'reports_table.report_id',
                    'reports_table.report_current_status',
                    'reports_table.report_urgency_level',
                    'reports_table.report_submitted_at',
                    'reports_table.report_updated_at',
                    'reports_table.report_suggested_issue',
                    'reports_table.report_unlisted_equipment_name',
                    'equipment_table.equipment_name',
                    'rooms_table.room_name',
                    'buildings_table.building_name',
                    'technicians.user_full_name as technician_name'
                )
                ->orderByDesc('reports_table.report_submitted_at')
                ->paginate(10)
                ->withQueryString();
        }

        return view('admin.reports.maintenance-history', [
            'rows' => $rows,
            'filters' => $filters,
            'filed' => (int) $byStatus->sum(),
            'resolved' => (int) ($byStatus['Resolved'] ?? 0),
            'rejected' => (int) ($byStatus['Rejected'] ?? 0),
            'replacement' => (int) ($byStatus['For Replacement'] ?? 0),
            'pending' => (int) ($byStatus['Pending'] ?? 0),
            'processing' => (int) ($byStatus['Processing'] ?? 0),
            'avgCloseHours' => $avgCloseHours,
            'repeatEquipment' => $repeatEquipment,
        ]);
    }

    public function procurementHistory()
    {
        return redirect('/admin/reports/maintenance-history');
    }

    public function receivingSummary(Request $request): View
    {
        $filters = $this->systemReportFilters($request);
        $rows = $this->emptyReportPager($request);
        $accepted = 0;
        $returned = 0;
        $withOr = 0;
        $inventoryLines = 0;

        if (Schema::hasTable('receiving_reports_table')) {
            $query = DB::table('receiving_reports_table');
            $dateCol = Schema::hasColumn('receiving_reports_table', 'receiving_report_date')
                ? 'receiving_reports_table.receiving_report_date'
                : 'receiving_reports_table.receiving_report_created_at';
            $this->applyDateFilter($query, $dateCol, $filters);

            if (Schema::hasTable('physical_suppliers_table')) {
                $query->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'receiving_reports_table.receiving_report_supplier_id');
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $query->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'receiving_reports_table.receiving_report_supplier_id');
            }

            $select = [
                'receiving_reports_table.receiving_report_id',
                'receiving_reports_table.receiving_report_status',
                'receiving_reports_table.receiving_report_invoice_no',
                'receiving_reports_table.receiving_report_date',
                'receiving_reports_table.receiving_report_created_at',
                'receiving_reports_table.receiving_report_received_by_signature',
            ];
            $supplierParts = [];
            if (Schema::hasTable('physical_suppliers_table')) {
                $supplierParts[] = 'physical_suppliers_table.company_name';
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $supplierParts[] = 'online_suppliers_table.shop_name';
            }
            $select[] = DB::raw(($supplierParts ? 'COALESCE('.implode(', ', $supplierParts).', ' : '')."'—'".($supplierParts ? ')' : '').' as supplier_name');

            $query->select($select);

            if ($filters['q'] !== '') {
                $needle = '%'.$filters['q'].'%';
                $query->where(function ($q) use ($needle) {
                    $q->where('receiving_reports_table.receiving_report_invoice_no', 'like', $needle)
                        ->orWhere('receiving_reports_table.receiving_report_status', 'like', $needle)
                        ->orWhere('receiving_reports_table.receiving_report_received_by_signature', 'like', $needle);
                });
            }

            $accepted = (clone $query)->whereIn('receiving_reports_table.receiving_report_status', ['Completed', 'Accepted'])->count();
            $returned = (clone $query)->where('receiving_reports_table.receiving_report_status', 'Returned')->count();
            $withOr = (clone $query)->whereNotNull('receiving_reports_table.receiving_report_invoice_no')
                ->where('receiving_reports_table.receiving_report_invoice_no', '!=', '')
                ->count();
            $rows = $query->orderByDesc('receiving_reports_table.receiving_report_id')->paginate(10)->withQueryString();
        }

        if (Schema::hasTable('receiving_report_items_table') && Schema::hasTable('receiving_reports_table')) {
            $itemQuery = DB::table('receiving_report_items_table')
                ->join('receiving_reports_table', 'receiving_reports_table.receiving_report_id', '=', 'receiving_report_items_table.receiving_report_id')
                ->whereIn('receiving_reports_table.receiving_report_status', ['Completed', 'Accepted']);
            $dateCol = Schema::hasColumn('receiving_reports_table', 'receiving_report_date')
                ? 'receiving_reports_table.receiving_report_date'
                : 'receiving_reports_table.receiving_report_created_at';
            $this->applyDateFilter($itemQuery, $dateCol, $filters);
            $inventoryLines = $itemQuery->count();
        }

        return view('admin.reports.receiving-summary', [
            'rows' => $rows,
            'filters' => $filters,
            'accepted' => $accepted,
            'returned' => $returned,
            'withOr' => $withOr,
            'inventoryLines' => $inventoryLines,
        ]);
    }

    public function userLoginLogs(Request $request): View
    {
        $filters = $this->systemReportFilters($request);
        $users = $this->emptyReportPager($request);
        $sessions = $this->emptyReportPager($request, 'sessions_page');

        if (Schema::hasTable('users_table')) {
            $query = DB::table('users_table')
                ->leftJoin('roles_table', 'roles_table.role_id', '=', 'users_table.user_role_id');

            if (Schema::hasTable('sessions')) {
                $query->leftJoin('sessions', 'sessions.user_id', '=', 'users_table.user_id');
            }

            $select = [
                'users_table.user_id',
                'users_table.user_full_name',
                'users_table.user_username',
                'users_table.user_employee_id',
                'roles_table.role_name',
            ];
            if (Schema::hasTable('sessions')) {
                $select[] = DB::raw('MAX(sessions.last_activity) as last_activity');
                $select[] = DB::raw('MAX(sessions.ip_address) as last_ip');
            } else {
                $select[] = DB::raw('NULL as last_activity');
                $select[] = DB::raw('NULL as last_ip');
            }

            $query->select($select)->groupBy(
                'users_table.user_id',
                'users_table.user_full_name',
                'users_table.user_username',
                'users_table.user_employee_id',
                'roles_table.role_name'
            );

            if ($filters['q'] !== '') {
                $needle = '%'.$filters['q'].'%';
                $query->where(function ($q) use ($needle) {
                    $q->where('users_table.user_full_name', 'like', $needle)
                        ->orWhere('users_table.user_username', 'like', $needle)
                        ->orWhere('roles_table.role_name', 'like', $needle);
                });
            }

            $users = $query->orderBy('users_table.user_full_name')->paginate(10)->withQueryString();
        }

        if (Schema::hasTable('sessions')) {
            $sessionQuery = DB::table('sessions')
                ->leftJoin('users_table', 'users_table.user_id', '=', 'sessions.user_id')
                ->leftJoin('roles_table', 'roles_table.role_id', '=', 'users_table.user_role_id')
                ->select(
                    'sessions.id',
                    'sessions.ip_address',
                    'sessions.user_agent',
                    'sessions.last_activity',
                    'users_table.user_full_name',
                    'users_table.user_username',
                    'roles_table.role_name'
                )
                ->whereNotNull('sessions.user_id')
                ->orderByDesc('sessions.last_activity');

            if ($filters['from'] !== '') {
                $sessionQuery->where('sessions.last_activity', '>=', \Carbon\Carbon::parse($filters['from'])->startOfDay()->timestamp);
            }
            if ($filters['to'] !== '') {
                $sessionQuery->where('sessions.last_activity', '<=', \Carbon\Carbon::parse($filters['to'])->endOfDay()->timestamp);
            }

            $sessions = $sessionQuery->paginate(10, ['*'], 'sessions_page')->withQueryString();
        }

        return view('admin.reports.user-login-logs', [
            'users' => $users,
            'sessions' => $sessions,
            'filters' => $filters,
        ]);
    }

    private function systemReportFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];
    }

    private function emptyReportPager(Request $request, string $pageName = 'page'): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 10, max(1, (int) $request->query($pageName, 1)), [
            'path' => $request->url(),
            'query' => $request->query(),
            'pageName' => $pageName,
        ]);
    }

    private function applyDateFilter($query, string $column, array $filters): void
    {
        if ($filters['from'] !== '') {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if ($filters['to'] !== '') {
            $query->whereDate($column, '<=', $filters['to']);
        }
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

    public function maintenanceSettings()
    {
        return redirect('/admin/settings/campus-setup-pin');
    }

    public function notificationSettings()
    {
        return redirect('/admin/settings/campus-setup-pin');
    }

    public function systemSettings()
    {
        return redirect('/admin/settings/campus-setup-pin');
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
<<<<<<< Updated upstream
        // =====================================================
        // RIS APPROVALS - Full implementation matching
        // procurementReview() to provide all required variables
        // for the admin.procurement-review views.
        // =====================================================

        $filter = strtolower($request->query('filter', 'pending'));
        $search = trim($request->query('search', ''));

        if (!in_array($filter, ['pending', 'forwarded', 'all'], true)) {
            $filter = 'pending';
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

=======
        $risRecords = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
>>>>>>> Stashed changes
            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'ris_items_sum.ris_calculated_total',
                'ris_items_names.ris_item_names'
            )
<<<<<<< Updated upstream
=======
            ->whereIn('requisition_issue_slip_table.ris_status', $this->adminReviewableStatuses())
            ->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
            ->whereNull('requisition_issue_slip_table.ris_approved_by_date')
            ->orderByDesc('requisition_issue_slip_table.ris_requested_by_date')
            ->paginate(10);
>>>>>>> Stashed changes

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
        $pendingRis = $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'pending')
            ->distinct()
            ->count('requisition_issue_slip_table.ris_id');

        $forwardedRis = $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'forwarded')
            ->distinct()
            ->count('requisition_issue_slip_table.ris_id');

        $allRis = $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'all')
            ->distinct()
            ->count('requisition_issue_slip_table.ris_id');

        $pendingRisAmount = (float) $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'pending')
            ->sum('ris_items_sum.ris_calculated_total');
        $forwardedRisAmount = (float) $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'forwarded')
            ->sum('ris_items_sum.ris_calculated_total');
        $allRisAmount = (float) $this->applyProcurementReviewStatusFilter(clone $baseQuery, 'all')
            ->sum('ris_items_sum.ris_calculated_total');


        // =====================================================
        // TABLE QUERY
        // =====================================================

        $query = clone $baseQuery;


        // =====================================================
        // STATUS FILTER
        // =====================================================

        $this->applyProcurementReviewStatusFilter($query, $filter);


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
                    'forwardedRis',
                    'allRis',
                    'pendingRisAmount',
                    'forwardedRisAmount',
                    'allRisAmount'
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
                'forwardedRis',
                'allRis',
                'pendingRisAmount',
                'forwardedRisAmount',
                'allRisAmount'
            )
        );
    }

    public function startRisReview($risId)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        $this->markRisUnderReview($ris);

<<<<<<< Updated upstream
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
        $filter = strtolower($request->query('filter', 'pending'));
        $search = trim($request->query('search', ''));

        if (!in_array($filter, ['pending', 'forwarded', 'all'], true)) {
            $filter = 'pending';
        }

        $query = $this->adminRisJoinQuery()
            ->where(function ($q) {
                $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date');
            });

        $this->applyProcurementReviewStatusFilter($query, $filter);

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

        if (!in_array($filter, ['all', 'for_cosign', 'cosigned', 'president_rejected'], true)) {
            $filter = 'all';
        }

        $query = $this->adminRisJoinQuery()
            ->where(function ($q) {
                $q->where(function ($approved) {
                    $approved->whereIn('requisition_issue_slip_table.ris_status', ['Approved', 'Approved by the President'])
                        ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
                        ->whereRaw('TRIM(requisition_issue_slip_table.ris_approved_by_signature) != ""');
                })
                ->orWhereIn('requisition_issue_slip_table.ris_status', [
                    'Rejected by President',
                    'Rejected by the President',
                    'Rejected',
                ]);
            });

        if ($filter === 'for_cosign') {
            $query->whereIn('requisition_issue_slip_table.ris_status', ['Approved', 'Approved by the President'])
                ->whereNull('ris_released.released_ris_id');
        } elseif ($filter === 'cosigned') {
            $query->whereIn('requisition_issue_slip_table.ris_status', ['Approved', 'Approved by the President'])
                ->whereNotNull('ris_released.released_ris_id');
        } elseif ($filter === 'president_rejected') {
            $query->whereIn('requisition_issue_slip_table.ris_status', [
                'Rejected by President',
                'Rejected by the President',
                'Rejected',
            ]);
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
        $filter = strtolower($request->query('filter', 'all'));

        if (!in_array($filter, ['all', 'direct_approved', 'president_approved', 'president_rejected', 'amend'], true)) {
            $filter = 'all';
        }

        $query = $this->adminRisJoinQuery()
            ->where(function ($q) {
                $q->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_form_number')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_approved_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_issued_by_date')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_submitted_at')
                    ->orWhereNotNull('requisition_issue_slip_table.ris_created_at');
            });

        if ($filter === 'direct_approved') {
            $this->applyRisAdminApprovedScope($query);
        } elseif ($filter === 'president_approved') {
            $this->applyRisPresidentApprovedScope($query);
        } elseif ($filter === 'president_rejected') {
            $this->applyRisPresidentRejectedScope($query);
        } elseif ($filter === 'amend') {
            $query->whereIn('requisition_issue_slip_table.ris_status', ['Minor Revision', 'Rejected']);
        }

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
            ->leftJoin($this->risReleasedJoin(), 'requisition_issue_slip_table.ris_id', '=', 'ris_released.released_ris_id')
            ->select(
                'requisition_issue_slip_table.*',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'ris_items_sum.ris_calculated_total',
                'ris_items_names.ris_item_names',
                'ris_released.released_ris_id'
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

    private function risColumn(string $column, string $table = 'requisition_issue_slip_table'): string
    {
        return $table === '' ? $column : $table . '.' . $column;
    }

    private function risReleasedJoin()
    {
        return DB::raw('(
            SELECT DISTINCT approval_log_reference_id AS released_ris_id
            FROM approval_logs_table
            WHERE approval_log_reference_type = "RIS"
              AND (
                    approval_log_approval_remarks LIKE "%returned to Purchaser%"
                 OR approval_log_approval_status = "Co-signed"
                 OR approval_log_level IN ("Admin Return", "Admin Co-sign")
              )
        ) as ris_released');
    }

    private function risReleasedExistsCallback(string $risIdColumn)
    {
        return function ($sub) use ($risIdColumn) {
            $sub->select(DB::raw(1))
                ->from('approval_logs_table as log')
                ->whereColumn('log.approval_log_reference_id', $risIdColumn)
                ->where('log.approval_log_reference_type', 'RIS')
                ->where(function ($released) {
                    $released->where('log.approval_log_approval_remarks', 'like', '%returned to Purchaser%')
                        ->orWhere('log.approval_log_approval_status', 'Co-signed')
                        ->orWhereIn('log.approval_log_level', ['Admin Return', 'Admin Co-sign']);
                });
        };
    }

    private function applyProcurementReviewStatusFilter($query, string $filter)
    {
        if ($filter === 'pending') {
            return $query->whereIn(
                'requisition_issue_slip_table.ris_status',
                ['Submitted', 'Under Review', 'Resubmitted', 'Pending']
            );
        }

        if ($filter === 'forwarded') {
            return $this->applyRisForwardedScope($query);
        }

        return $query->whereIn(
            'requisition_issue_slip_table.ris_status',
            [
                'Draft',
                'Submitted',
                'Under Review',
                'Resubmitted',
                'Pending',
                'Approved',
                'Forwarded to President',
                'Approved by the President',
                'Directly Approved',
                'Minor Revision',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
                'Archived',
            ]
        );
    }

    private function countRisCurrentlyForwardedToPresident(): int
    {
        try {
            return (int) DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Forwarded to President')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function applyRisForwardedScope($query, string $table = 'requisition_issue_slip_table')
    {
        $status = $this->risColumn('ris_status', $table);

        return $query->where($status, 'Forwarded to President');
    }

    private function applyRisAdminApprovedScope($query, string $table = 'requisition_issue_slip_table')
    {
        return $query->where($this->risColumn('ris_status', $table), 'Directly Approved');
    }

    private function applyRisPresidentApprovedScope($query, string $table = 'requisition_issue_slip_table')
    {
        $sig = $this->risColumn('ris_approved_by_signature', $table);

        return $query->where(function ($q) use ($table, $sig) {
            $status = $this->risColumn('ris_status', $table);
            $q->where($status, 'Approved by the President')
                ->orWhere(function ($legacy) use ($status, $sig) {
                    $legacy->where($status, 'Approved')
                        ->whereNotNull($sig)
                        ->where($sig, '!=', '');
                });
        });
    }

    private function applyRisPresidentRejectedScope($query, string $table = 'requisition_issue_slip_table')
    {
        return $query->whereIn($this->risColumn('ris_status', $table), [
            'Rejected by the President',
            'Rejected by President',
        ]);
    }

    private function applyRisCosignedScope($query, string $table = 'requisition_issue_slip_table')
    {
        $id = $this->risColumn('ris_id', $table);
        $issued = $this->risColumn('ris_issued_by_signature', $table);

        return $this->applyRisPresidentApprovedScope($query, $table)
            ->where(function ($signed) use ($issued, $id) {
                $signed->where(function ($admin) use ($issued) {
                    $admin->whereNotNull($issued)->where($issued, '!=', '');
                })->orWhereExists($this->risReleasedExistsCallback($id));
            });
    }

    private function applyRisAwaitingAdminActionScope($query, string $table = 'ris')
    {
        $id = $this->risColumn('ris_id', $table);
        $issued = $this->risColumn('ris_issued_by_signature', $table);

        return $this->applyRisPresidentApprovedScope($query, $table)
            ->where(function ($unsigned) use ($issued) {
                $unsigned->whereNull($issued)->orWhere($issued, '');
            })
            ->whereNotExists($this->risReleasedExistsCallback($id));
    }

    private function formatAdminRisStatusLabel(object $ris): string
    {
        if (in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)) {
            return 'Pending';
        }

        if ($ris->ris_status === 'Directly Approved') {
            return 'Admin Approved';
        }

        if (in_array($ris->ris_status, ['Rejected by President', 'Rejected by the President'], true)) {
            return 'Rejected by the President';
        }

        if ($ris->ris_status === 'Approved by the President') {
            return 'Approved by the President';
        }

        if ($ris->ris_status === 'Forwarded to President') {
            return 'Forwarded to President';
        }

        if ($ris->ris_status === 'Approved') {
            return !empty($ris->ris_approved_by_signature)
                ? 'Approved by the President'
                : 'Forwarded to President';
        }

        if (in_array($ris->ris_status, ['Minor Revision', 'Rejected'], true)) {
            return 'Amend';
        }

        return (string) ($ris->ris_status ?? 'N/A');
    }

    private function parseFlexibleDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'j/n/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'] as $format) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // try next format
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
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

    private function adminProcurementCalendarEvents()
    {
        $events = collect();
        if (!Schema::hasTable('requisition_issue_slip_table')) {
            return $events;
        }

        try {
            $query = DB::table('requisition_issue_slip_table')
                ->select(
                    'ris_id',
                    'ris_form_number',
                    'ris_status',
                    'ris_submitted_at',
                    'ris_requested_by_date',
                    'ris_issued_by_date',
                    'ris_approved_by_date'
                )
                ->orderByDesc('ris_id')
                ->limit(250);

            $rows = $query->get();
        } catch (\Throwable $e) {
            return $events;
        }

        foreach ($rows as $ris) {
            $ref = $ris->ris_form_number ?: ('RIS-'.$ris->ris_id);
            $status = (string) ($ris->ris_status ?? '');
            $this->pushCalendarEvent($events, $ris->ris_submitted_at ?? $ris->ris_requested_by_date, $ref.' · Submitted');
            if (stripos($status, 'Forwarded') !== false) {
                $this->pushCalendarEvent($events, $ris->ris_submitted_at ?? $ris->ris_requested_by_date, $ref.' · Forwarded to President');
            }
            $this->pushCalendarEvent($events, $ris->ris_approved_by_date, $ref.' · Approved by President');
            $this->pushCalendarEvent($events, $ris->ris_issued_by_date, $ref.' · Issued by Admin');
        }

        return $events->sortBy('event_date')->values();
    }

    private function pushCalendarEvent($events, $rawDate, string $name): void
    {
        $date = $this->calendarEventDate($rawDate);
        if (!$date) {
            return;
        }
        $events->push((object) [
            'event_date' => $date,
            'event_name' => $name,
        ]);
    }

    private function calendarEventDate($raw): ?string
    {
        if (empty($raw)) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    // =====================================================
    // ADDED RIS ADMIN APPROVAL: APPROVE RIS
    // =====================================================
=======
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['status' => 'Under Review']);
        }

        return back();
    }
>>>>>>> Stashed changes

    public function approveRis(Request $request, $risId)
    {
        return DB::transaction(function () use ($request, $risId) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

<<<<<<< Updated upstream
            // New workflow: Submitted / Under Review / Resubmitted.
            // Legacy status: Pending.
            if (
                !in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true)
                || !$ris->ris_requested_by_date
            ) {
                return back()->with('error', 'Only submitted pending RIS records can be approved.');
            }

            $adminName = trim((string) (Auth::user()->user_full_name ?? 'Admin'));

            // Forward without Issued by. President signs Approved by.
            // Admin signs Issued by later on Sign RIS.
            // approved_by_date (with empty signature) marks the President queue.
            $forwardUpdate = [
                'ris_status' => 'Forwarded to President',
                'ris_issued_by_signature' => null,
                'ris_issued_by_date' => null,
                'ris_approved_by_signature' => null,
                'ris_approved_by_date' => now()->toDateString(),
            ];

            try {
                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $risId)
                    ->update($forwardUpdate);
            } catch (\Throwable $e) {
                $forwardUpdate['ris_status'] = 'Approved';
                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $risId)
                    ->update($forwardUpdate);
            }
=======
            if (!$this->isAdminReviewable($ris->ris_status) || !$ris->ris_requested_by_date) {
                return back()->with('error', 'Only submitted RIS records can be approved.');
            }

            $this->markRisUnderReview($ris);

            $adminName = Auth::user()->user_full_name ?? 'Admin';

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_status' => 'Approved',
                    'ris_approved_by_signature' => $adminName,
                    'ris_approved_by_date' => now()->toDateString(),
                ]);
>>>>>>> Stashed changes

            $this->completeLinkedReplacementRequest($ris);

            try {
                DB::table('approval_logs_table')->insert([
                    'approval_log_reference_type' => 'RIS',
                    'approval_log_reference_id' => (int) $risId,
                    'approval_log_level' => 'Admin',
                    'approval_log_approved_by' => Auth::id(),
                    'approval_log_approval_status' => 'Forwarded to President',
                    'approval_log_approval_remarks' => 'RIS forwarded to President by ' . $adminName . ' without Issued by signature.',
                    'approval_log_approved_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore logging failures
            }

            return back()->with('success', 'RIS forwarded to the President for final approval.');
        });
    }

    public function directApproveForm(Request $request, $risId)
    {
        $mode = strtolower($request->query('mode', 'direct'));
        if (!in_array($mode, ['direct', 'forward', 'cosign'], true)) {
            $mode = 'direct';
        }

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        if ($mode === 'cosign') {
            $presidentApproved = in_array($ris->ris_status, ['Approved', 'Approved by the President'], true)
                && trim((string) ($ris->ris_approved_by_signature ?? '')) !== '';
            $alreadyIssued = trim((string) ($ris->ris_issued_by_signature ?? '')) !== ''
                || !empty($ris->ris_issued_by_date);

            if (!$presidentApproved || $alreadyIssued) {
                abort(403, 'Only President-approved RIS records awaiting Issued by can be signed here.');
            }
        } elseif (
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

        $issuedDate = $this->parseFlexibleDate($validated['ris_issued_by_date']);
        if (!$issuedDate) {
            return back()->with(
                'error',
                'Issued by date must be a valid date (dd/mm/yyyy).'
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
<<<<<<< Updated upstream
            !in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true) ||
=======
            !$this->isAdminReviewable($ris->ris_status) ||
>>>>>>> Stashed changes
            empty($ris->ris_requested_by_date)
        ) {
            return back()->with(
                'error',
<<<<<<< Updated upstream
                'Only submitted pending RIS records can be admin approved.'
=======
                'Only submitted RIS records can be directly approved.'
>>>>>>> Stashed changes
            );
        }

        $this->markRisUnderReview($ris);

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

        $this->completeLinkedReplacementRequest($ris);

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
                'approval_log_approval_status' => 'Admin Approved',
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
            ->route('admin.procurement-review', [
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

<<<<<<< Updated upstream
            $validated = $request->validate([
                'remarks' => ['required', 'string', 'min:3'],
            ]);

            if (
                !in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true)
                || !$ris->ris_requested_by_date
            ) {
                return back()->with('error', 'Only submitted pending RIS records can be returned for amendment.');
            }

            $remarks = trim($validated['remarks']);

            // Keep the submission so the Purchaser can correct and resubmit.
            // No Issued by signature — amend is not an approval.
            if (in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted'], true)) {

                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $risId)
                    ->update([
                        'ris_status' => 'Minor Revision',
                        'ris_issued_by_signature' => null,
                        'ris_issued_by_date' => null,
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

                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $risId)
                    ->update([
                        'ris_status' => 'Draft',
                        'ris_requested_by_signature' => null,
                        'ris_requested_by_date' => null,
                        'ris_submitted_by' => null,
                        'ris_submitted_at' => null,
                        'ris_issued_by_signature' => null,
                        'ris_issued_by_date' => null,
                        'ris_rejection_reason' => $remarks,
                    ]);
            }

=======
            if (!$this->isAdminReviewable($ris->ris_status) || !$ris->ris_requested_by_date) {
                return back()->with('error', 'Only submitted RIS records can be returned for revision.');
            }

            $remarks = trim((string) $request->input('remarks', ''));
            if ($remarks === '') {
                return back()->with('error', 'Please provide amendment remarks to inform the Purchaser what needs to be revised.');
            }

            $this->markRisUnderReview($ris);

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_status' => 'Minor Revision',
                    'ris_rejection_reason' => $remarks,
                ]);

            DB::table('ris_revision_notes_table')->insert([
                'ris_id' => (int) $risId,
                'ris_revision_requested_by' => Auth::id(),
                'ris_revision_type' => 'Minor Revision',
                'ris_revision_note' => $remarks,
                'ris_revision_created_at' => now(),
            ]);

>>>>>>> Stashed changes
            try {
                DB::table('approval_logs_table')->insert([
                    'approval_log_reference_type' => 'RIS',
                    'approval_log_reference_id' => (int) $risId,
                    'approval_log_level' => 'Admin',
                    'approval_log_approved_by' => Auth::id(),
<<<<<<< Updated upstream
                    'approval_log_approval_status' => 'Rejected',
                    'approval_log_approval_remarks' => 'RIS returned to Purchaser for amendment (no signature): ' . $remarks,
=======
                    'approval_log_approval_status' => 'Minor Revision',
                    'approval_log_approval_remarks' => $remarks,
>>>>>>> Stashed changes
                    'approval_log_approved_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore logging failures
            }

            return back()->with('success', 'RIS returned to Purchaser for amendment. No signature was required.');
        });
    }

<<<<<<< Updated upstream
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
        $select = [
            'users_table.user_id',
            'users_table.user_employee_id',
            'users_table.user_username',
            'users_table.user_full_name',
            'roles_table.role_name',
        ];
        if (Schema::hasColumn('users_table', 'last_active_at')) {
            $select[] = 'users_table.last_active_at';
        } else {
            $select[] = DB::raw('NULL as last_active_at');
        }

        $users = DB::table('users_table')
            ->leftJoin('roles_table', 'users_table.user_role_id', '=', 'roles_table.role_id')
            ->select($select)
            ->orderBy('users_table.user_full_name')
            ->paginate(15);

        return view('admin.users._quick-access', compact('users'));
    }

    public function quickAccessReportsContent(Request $request)
    {
        return view('admin.reports._quick-access', [
            'maintenance' => $this->maintenanceHistory($request)->getData(),
            'receiving' => $this->receivingSummary($request)->getData(),
            'approvals' => $this->approvalLogs($request)->getData(),
            'access' => $this->userLoginLogs($request)->getData(),
        ]);
    }

    public function quickAccessSettingsContent()
    {
        return view('admin.settings._quick-access', [
            'setting' => CampusSetupSetting::query()->first(),
        ]);
=======
    public function finalRejectRis(Request $request, $risId)
    {
        return DB::transaction(function () use ($request, $risId) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            if (!$this->isAdminReviewable($ris->ris_status) || !$ris->ris_requested_by_date) {
                return back()->with('error', 'Only submitted RIS records can be rejected.');
            }

            $remarks = trim((string) $request->input('remarks', $request->input('rejection_reason', '')));

            $this->markRisUnderReview($ris);

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_status' => 'Rejected',
                    'ris_rejection_reason' => $remarks !== '' ? $remarks : 'Rejected by Admin.',
                ]);

            try {
                DB::table('approval_logs_table')->insert([
                    'approval_log_reference_type' => 'RIS',
                    'approval_log_reference_id' => (int) $risId,
                    'approval_log_level' => 'Admin',
                    'approval_log_approved_by' => Auth::id(),
                    'approval_log_approval_status' => 'Rejected',
                    'approval_log_approval_remarks' => $remarks !== '' ? $remarks : 'Rejected by Admin.',
                    'approval_log_approved_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore logging failures
            }

            return back()->with('success', 'RIS rejected.');
        });
    }

    private function adminReviewableStatuses(): array
    {
        return ['Submitted', 'Resubmitted', 'Under Review'];
    }

    private function isAdminReviewable(?string $status): bool
    {
        return in_array($status, $this->adminReviewableStatuses(), true);
    }

    private function markRisUnderReview(object $ris): void
    {
        if (!in_array($ris->ris_status, ['Submitted', 'Resubmitted'], true)) {
            return;
        }

        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris->ris_id)
            ->update([
                'ris_status' => 'Under Review',
            ]);

        $ris->ris_status = 'Under Review';
    }

    private function completeLinkedReplacementRequest(object $ris): void
    {
        if (empty($ris->ris_procurement_request_id)) {
            return;
        }

        DB::table('procurement_requests_table')
            ->where('procurement_request_id', $ris->ris_procurement_request_id)
            ->whereIn('procurement_request_status', ['Approved', 'Pending'])
            ->update([
                'procurement_request_status' => 'Completed',
            ]);
    }

    public function requestCheck(Request $request)
    {
        $filter = $request->query('status', 'pending');

        $query = RequestForCheckController::reviewBaseQuery()
            ->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });

        if ($filter === 'pending') {
            $query->where(function ($q) {
                $q->where('request_check_table.request_check_status', 'Pending Admin Approval')
                    ->orWhere(function ($inner) {
                        $inner->where('request_check_table.request_check_status', 'Under Review')
                            ->where('request_check_table.request_check_review_stage', 'admin');
                    });
            });
        } elseif ($filter === 'approved') {
            $query->where('request_check_table.request_check_status', 'Approved');
        } elseif ($filter === 'rejected') {
            $query->where('request_check_table.request_check_status', 'Rejected');
        }

        $rfcs = $query
            ->orderByDesc('request_check_table.request_check_accounting_verified_at')
            ->orderByDesc('request_check_table.request_check_id')
            ->paginate(10)
            ->withQueryString();

        $rfcIds = $rfcs->getCollection()->pluck('request_check_id');
        $attachments = $rfcIds->isEmpty()
            ? collect()
            : DB::table('request_check_attachments_table')
                ->whereIn('request_check_id', $rfcIds)
                ->get()
                ->groupBy('request_check_id');

        $counts = [
            'pending' => RequestForCheckController::reviewBaseQuery()
                ->where(function ($q) {
                    $q->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
                })
                ->where(function ($q) {
                    $q->where('request_check_status', 'Pending Admin Approval')
                        ->orWhere(function ($inner) {
                            $inner->where('request_check_status', 'Under Review')
                                ->where('request_check_review_stage', 'admin');
                        });
                })
                ->count(),
            'approved' => RequestForCheckController::reviewBaseQuery()
                ->where(function ($q) {
                    $q->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
                })
                ->where('request_check_status', 'Approved')
                ->count(),
            'rejected' => RequestForCheckController::reviewBaseQuery()
                ->where(function ($q) {
                    $q->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
                })
                ->where('request_check_status', 'Rejected')
                ->count(),
        ];

        return view('admin.request-check.index', compact('rfcs', 'attachments', 'filter', 'counts'));
    }

    public function startRfcReview($id)
    {
        return DB::transaction(function () use ($id) {
            $rfc = $this->lockAdminRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            if ($rfc->request_check_status === 'Pending Admin Approval') {
                DB::table('request_check_table')->where('request_check_id', $id)->update([
                    'request_check_status' => 'Under Review',
                    'request_check_review_stage' => 'admin',
                    'request_check_updated_at' => now(),
                ]);
            }

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 'Under Review']);
            }

            return back();
        });
    }

    public function approveRfc($id)
    {
        return DB::transaction(function () use ($id) {
            $rfc = $this->lockAdminRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            $adminName = Auth::user()->user_full_name ?? 'Administrator';

            DB::table('request_check_table')->where('request_check_id', $id)->update([
                'request_check_status' => 'Approved',
                'request_check_review_stage' => 'admin',
                'request_check_approved_by_user_id' => Auth::id(),
                'request_check_approved_at' => now(),
                'request_check_approved_by_signature' => $adminName,
                'request_check_approved_by_admin' => $adminName,
                'request_check_rejection_reason' => null,
                'request_check_updated_at' => now(),
            ]);

            return back()->with('success', 'Request for Check approved.');
        });
    }

    public function rejectRfc(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);

        return DB::transaction(function () use ($request, $id) {
            $rfc = $this->lockAdminRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update([
                'request_check_status' => 'Rejected',
                'request_check_rejection_reason' => $request->input('remarks'),
                'request_check_updated_at' => now(),
            ]);

            return back()->with('success', 'Request for Check rejected.');
        });
    }

    public function reviseRfc(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);

        return DB::transaction(function () use ($request, $id) {
            $rfc = $this->lockAdminRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update([
                'request_check_status' => 'Minor Revision',
                'request_check_review_stage' => null,
                'request_check_revision_notes' => $request->input('remarks'),
                'request_check_updated_at' => now(),
            ]);

            return back()->with('success', 'Request for Check returned to Purchaser for revision.');
        });
    }

    private function lockAdminRfc($id)
    {
        $rfc = DB::table('request_check_table')
            ->where('request_check_id', $id)
            ->lockForUpdate()
            ->first();

        if (!$rfc) {
            return back()->with('error', 'Request for Check not found.');
        }

        if ((int) ($rfc->request_check_is_archived ?? 0) === 1) {
            return back()->with('error', 'Archived Request for Check cannot be reviewed.');
        }

        $reviewable = $rfc->request_check_status === 'Pending Admin Approval'
            || ($rfc->request_check_status === 'Under Review' && $rfc->request_check_review_stage === 'admin');

        if (!$reviewable) {
            return back()->with('error', 'Only Request for Check records pending Administrator approval can be reviewed.');
        }

        return $rfc;
    }

    public function liquidationReports(Request $request)
    {
        $filter = $request->query('status', 'pending');
        $query = \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()
            ->where(function ($q) {
                $q->whereNull('liquidation_reports_table.liquidation_report_is_archived')
                    ->orWhere('liquidation_reports_table.liquidation_report_is_archived', 0);
            });

        if ($filter === 'pending') {
            $query->where(function ($q) {
                $q->where('liquidation_report_status', 'Pending Admin Approval')
                    ->orWhere(function ($inner) {
                        $inner->where('liquidation_report_status', 'Under Review')->where('liquidation_report_review_stage', 'admin');
                    });
            });
        } elseif ($filter === 'approved') {
            $query->where('liquidation_report_status', 'Approved');
        } elseif ($filter === 'rejected') {
            $query->where('liquidation_report_status', 'Rejected');
        }

        $reports = $query->orderByDesc('liquidation_report_id')->paginate(10)->withQueryString();
        $ids = $reports->getCollection()->pluck('liquidation_report_id');
        $items = $ids->isEmpty() ? collect() : DB::table('liquidation_report_items_table')->whereIn('liquidation_report_id', $ids)->orderBy('liquidation_item_id')->get()->groupBy('liquidation_report_id');

        $counts = [
            'pending' => \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            })->where(function ($q) {
                $q->where('liquidation_report_status', 'Pending Admin Approval')
                    ->orWhere(function ($inner) {
                        $inner->where('liquidation_report_status', 'Under Review')->where('liquidation_report_review_stage', 'admin');
                    });
            })->count(),
            'approved' => \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            })->where('liquidation_report_status', 'Approved')->count(),
            'rejected' => \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            })->where('liquidation_report_status', 'Rejected')->count(),
        ];

        return view('admin.liquidation-reports.index', compact('reports', 'items', 'filter', 'counts'));
    }

    public function startLiqReview($id)
    {
        return DB::transaction(function () use ($id) {
            $liq = $this->lockAdminLiq($id);
            if (!is_object($liq)) return $liq;
            if ($liq->liquidation_report_status === 'Pending Admin Approval') {
                DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                    'liquidation_report_status' => 'Under Review',
                    'liquidation_report_review_stage' => 'admin',
                    'liquidation_report_updated_at' => now(),
                ]);
            }
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 'Under Review']);
            }
            return back();
        });
    }

    public function approveLiq($id)
    {
        return DB::transaction(function () use ($id) {
            $liq = $this->lockAdminLiq($id);
            if (!is_object($liq)) return $liq;
            $name = Auth::user()->user_full_name ?? 'Administrator';
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => 'Approved',
                'liquidation_report_review_stage' => 'admin',
                'liquidation_report_indorsed_by_supervisor' => $name,
                'liquidation_report_indorsed_by_date' => now()->toDateString(),
                'liquidation_report_recommending_approval' => $name,
                'liquidation_report_updated_at' => now(),
            ]);
            return back()->with('success', 'Liquidation Report endorsed and approved.');
        });
    }

    public function rejectLiq(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);
        return DB::transaction(function () use ($request, $id) {
            $liq = $this->lockAdminLiq($id);
            if (!is_object($liq)) return $liq;
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => 'Rejected',
                'liquidation_report_rejection_reason' => $request->input('remarks'),
                'liquidation_report_updated_at' => now(),
            ]);
            return back()->with('success', 'Liquidation Report rejected.');
        });
    }

    public function reviseLiq(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);
        return DB::transaction(function () use ($request, $id) {
            $liq = $this->lockAdminLiq($id);
            if (!is_object($liq)) return $liq;
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => 'Minor Revision',
                'liquidation_report_review_stage' => null,
                'liquidation_report_revision_notes' => $request->input('remarks'),
                'liquidation_report_updated_at' => now(),
            ]);
            return back()->with('success', 'Liquidation Report returned to Purchaser for revision.');
        });
    }

    private function lockAdminLiq($id)
    {
        $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->lockForUpdate()->first();
        if (!$liq) {
            return back()->with('error', 'Liquidation Report not found.');
        }
        if ((int) ($liq->liquidation_report_is_archived ?? 0) === 1) {
            return back()->with('error', 'Archived Liquidation Reports cannot be reviewed.');
        }
        $reviewable = $liq->liquidation_report_status === 'Pending Admin Approval'
            || ($liq->liquidation_report_status === 'Under Review' && $liq->liquidation_report_review_stage === 'admin');
        if (!$reviewable) {
            return back()->with('error', 'Only Liquidation Reports pending Administrator approval can be reviewed.');
        }
        return $liq;
>>>>>>> Stashed changes
    }
}

