<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PresidentController extends Controller
{
    // =====================================================
    // DASHBOARD
    // =====================================================

    public function dashboard(): View
    {
        // ================================
        // RIS decision counts
        // ================================
        $totalRisCount = DB::table('requisition_issue_slip_table')->count();

        $pendingApprovalsCount =
            DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Pending')
                ->count();

        $approvedDecisionsCount =
            DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->count();

        $rejectedDecisionsCount =
            DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Rejected')
                ->count();

        // ================================
        // Monthly stats (last 6 months)
        // ================================
        $monthlyStats = [];
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        for ($i = 5; $i >= 0; $i--) {
            $y = (int) date('Y', strtotime("-$i months"));
            $m = (int) date('m', strtotime("-$i months"));

            $approved = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereYear('ris_created_at', $y)
                ->whereMonth('ris_created_at', $m)
                ->count();

            $rejected = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Rejected')
                ->whereYear('ris_created_at', $y)
                ->whereMonth('ris_created_at', $m)
                ->count();

            $monthlyStats[] = [
                'year_month' => sprintf('%04d-%02d', $y, $m),
                'month_label' => $monthNames[$m] . ' ' . $y,
                'approved' => $approved,
                'rejected' => $rejected,
            ];
        }

        // ================================
        // Recent activity (last 5 RIS)
        // ================================
        $recentRis = DB::table('requisition_issue_slip_table')
            ->select(
                'ris_id',
                'ris_form_number',
                'ris_status',
                'ris_created_at',
                'ris_purpose_description'
            )
            ->orderByDesc('ris_created_at')
            ->limit(5)
            ->get();

        // ================================
        // Notifications count (for current user)
        // ================================
        $notificationsCount = 0;
        try {
            $user = \Auth::user();
            if ($user) {
                $notificationsCount = DB::table('notifications_table')
                    ->where('notification_user_id', $user->user_id)
                    ->count();
            }
        } catch (\Throwable $e) {
            $notificationsCount = 0;
        }

        return view('president.dashboard', [
            'totalRisCount' => $totalRisCount,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'approvedDecisionsCount' => $approvedDecisionsCount,
            'rejectedDecisionsCount' => $rejectedDecisionsCount,
            'notificationsCount' => $notificationsCount,
            'monthlyStats' => $monthlyStats,
            'recentRis' => $recentRis,
        ]);
    }

    // =====================================================
    // APPROVALS
    // =====================================================

    public function approvals(Request $request)
    {
        // ================================
        // Dashboard summary counts (ALL forwarded RIS)
        // ================================
        $totalRisCount = DB::table('requisition_issue_slip_table')
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->count();

        $totalPendingRis = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Pending')
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->count();

        $totalApprovedRis = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->count();

        $totalRejectedRis = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected')
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->count();

        // ================================
        // Base query: records forwarded to President
        // ================================
        $query = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_purpose_description',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_created_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->whereNotNull('ris.ris_requested_by_date')
            ->whereNotNull('ris.ris_approved_by_date')
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_purpose_description',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_created_at'
            );

        // ================================
        // Status filter
        // ================================
        if ($request->filled('status')) {
            $status = $request->status;
            if (in_array($status, ['Pending', 'Approved', 'Rejected'], true)) {
                $query->where('ris.ris_status', $status);
            }
        }

        // ================================
        // Search filter
        // ================================
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%");
            });
        }

        // ================================
        // Order & paginate
        // ================================
        $pendingRis = $query
            ->orderByDesc('ris.ris_created_at')
            ->paginate(10)
            ->withQueryString();

        // ================================
        // AJAX response: return JSON with rendered partial
        // ================================
        if ($request->ajax()) {
            $tableHtml = view('president.approvals._table', compact('pendingRis'))->render();
            return response()->json([
                'table_html' => $tableHtml,
                'total' => $pendingRis->total(),
                'from' => $pendingRis->firstItem(),
                'to' => $pendingRis->lastItem(),
                'current_page' => $pendingRis->currentPage(),
                'last_page' => $pendingRis->lastPage(),
            ]);
        }

        return view('president.approvals.index', [
            'pendingRis' => $pendingRis,
            'totalRisCount' => $totalRisCount,
            'totalPendingRis' => $totalPendingRis,
            'totalApprovedRis' => $totalApprovedRis,
            'totalRejectedRis' => $totalRejectedRis,
        ]);
    }


    public function approvalHistory(Request $request)
    {
        $query = DB::table('requisition_issue_slip_table as ris')
            ->join('approval_logs_table as log', function ($join) {
                $join->on('log.approval_log_reference_id', '=', 'ris.ris_id')
                    ->where('log.approval_log_reference_type', '=', 'RIS')
                    ->where('log.approval_log_level', '=', 'President');
            })
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_purpose_description',
                'log.approval_log_approval_status as decision',
                'log.approval_log_approval_remarks as remarks',
                'log.approval_log_approved_at as decided_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->whereIn('ris.ris_status', ['Approved', 'Rejected'])
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_purpose_description',
                'log.approval_log_approval_status',
                'log.approval_log_approval_remarks',
                'log.approval_log_approved_at'
            );

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%");
            });
        }

        // Order & paginate
        $approvalHistoryRecords = $query
            ->orderByDesc('log.approval_log_approved_at')
            ->paginate(10)
            ->withQueryString();

        // AJAX response
        if ($request->ajax()) {
            $tableHtml = view('president.approvals._history-table', compact('approvalHistoryRecords'))->render();
            return response()->json([
                'table_html' => $tableHtml,
                'total' => $approvalHistoryRecords->total(),
                'from' => $approvalHistoryRecords->firstItem(),
                'to' => $approvalHistoryRecords->lastItem(),
                'current_page' => $approvalHistoryRecords->currentPage(),
                'last_page' => $approvalHistoryRecords->lastPage(),
            ]);
        }

        return view('president.approvals.approval-history', [
            'approvalHistoryRecords' => $approvalHistoryRecords,
        ]);
    }

    public function digitalSignature(): View
    {
        return view('president.approvals.digitally-sign');
    }

    // =====================================================
    // APPROVAL DECISIONS (POST)
    // =====================================================

    public function decideRis(Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $remarks = $request->input('remarks');

        // Basic validation
        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Invalid RIS decision payload.');
        }

        $target = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->first();

        if (!$target) {
            return back()->with('error', 'RIS not found.');
        }

        if (!in_array($target->ris_status, ['Pending', 'Approved'], true) || empty($target->ris_approved_by_date)) {
            return back()->with('error', 'Only RIS records approved by Admin can be decided by President.');
        }

        $updateValues = [
            'ris_status' => $decision === 'Approved' ? 'Approved' : 'Rejected',
        ];

        if ($decision === 'Approved') {
            $signatureData = $request->input('signature_data');
            if (empty($signatureData)) {
                return back()->with('error', 'President signature is required to approve the RIS.');
            }
            $updateValues['ris_approved_by_signature'] = $signatureData;
            $updateValues['ris_approved_by_date'] = now()->toDateString();
        }

        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->update($updateValues);

        // approval logs (optional but useful)
        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $targetId,
                'approval_log_level' => 'President',
                'approval_log_approved_by' => \Auth::id(),
                'approval_log_approval_status' => $decision,
                'approval_log_approval_remarks' => $remarks,
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures to not break testing
        }

        return redirect('/president/approvals')->with('success', 'RIS decision saved successfully.');
    }

    public function decideProcurement(Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $remarks = $request->input('remarks');

        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Invalid procurement decision payload.');
        }

        $target = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $targetId)
            ->first();

        if (!$target) {
            return back()->with('error', 'Procurement request not found.');
        }

        DB::table('procurement_requests_table')
            ->where('procurement_request_id', $targetId)
            ->update([
                'procurement_request_status' => $decision,
            ]);

        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'ProcurementRequest',
                'approval_log_reference_id' => (int) $targetId,
                'approval_log_level' => 'President',
                'approval_log_approved_by' => \Auth::id(),
                'approval_log_approval_status' => $decision,
                'approval_log_approval_remarks' => $remarks,
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        return redirect('/president/approvals')->with('success', 'Procurement decision saved successfully.');
    }

    // =====================================================
    // REPORTS
    // =====================================================


    public function approvedReports(Request $request)
    {
        $filter = $request->filled('filter') ? $request->filter : 'approved';
        
        if (!in_array($filter, ['approved', 'rejected'], true)) {
            $filter = 'approved';
        }

        $query = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('approval_logs_table as log', function ($join) {
                $join->on('log.approval_log_reference_id', '=', 'ris.ris_id')
                    ->where('log.approval_log_reference_type', '=', 'RIS')
                    ->where('log.approval_log_level', '=', 'President');
            })
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'log.approval_log_approval_remarks as remarks',
                'log.approval_log_approved_at as decided_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->where('ris.ris_status', $filter === 'approved' ? 'Approved' : 'Rejected')
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'log.approval_log_approval_remarks',
                'log.approval_log_approved_at'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%");
            });
        }

        $outcomeRecords = $query
            ->orderByDesc('ris.ris_created_at')
            ->paginate(10)
            ->withQueryString();

        // Summary counts
        $today = now()->toDateString();
        
        $approvedToday = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->whereDate('ris_approved_by_date', $today)
            ->count();
            
        $rejectedToday = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected')
            ->whereDate('ris_approved_by_date', $today)
            ->count();
            
        $archivedToday = DB::table('requisition_issue_slip_table')
            ->whereDate('ris_created_at', $today)
            ->whereIn('ris_status', ['Approved', 'Rejected'])
            ->count();
            
        $totalApproved = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->count();
            
        $totalRejected = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected')
            ->count();
            
        $totalDecisions = $totalApproved + $totalRejected;

        if ($request->ajax()) {
            $tableHtml = view('president.reports._approved-table', compact('outcomeRecords'))->render();
            return response()->json([
                'table_html' => $tableHtml,
                'total' => $outcomeRecords->total(),
                'from' => $outcomeRecords->firstItem(),
                'to' => $outcomeRecords->lastItem(),
                'current_page' => $outcomeRecords->currentPage(),
                'last_page' => $outcomeRecords->lastPage(),
            ]);
        }

        return view('president.reports.approved', [
            'outcomeRecords' => $outcomeRecords,
            'filter' => $filter,
            'type' => $filter,
            'approvedToday' => $approvedToday,
            'rejectedToday' => $rejectedToday,
            'archivedToday' => $archivedToday,
            'totalApproved' => $totalApproved,
            'totalRejected' => $totalRejected,
            'totalDecisions' => $totalDecisions,
        ]);
    }

    public function monthlySummary(Request $request): View
    {
        // ================================
        // Get filter parameters
        // ================================

        $filterMonth = $request->filled('month') ? (int) $request->month : null;
        $filterYear = $request->filled('year') ? (int) $request->year : null;

        // Base query for RIS
        $risQuery = DB::table('requisition_issue_slip_table');

        // Apply month/year filter if provided
        if ($filterMonth && $filterYear) {
            $risQuery->whereYear('ris_created_at', $filterYear)
                     ->whereMonth('ris_created_at', $filterMonth);
        }

        // ================================
        // Summary cards (filtered)
        // ================================

        $risApproved = (clone $risQuery)->where('ris_status', 'Approved')->count();
        $risRejected = (clone $risQuery)->where('ris_status', 'Rejected')->count();
        $risPending = (clone $risQuery)->where('ris_status', 'Pending')->count();
        $totalRis = $risApproved + $risRejected + $risPending;

        // Total amount (filtered)
        $totalAmountQuery = DB::table('requisition_issue_slip_items_table as items')
            ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
            ->whereIn('ris.ris_status', ['Approved', 'Rejected', 'Pending']);

        if ($filterMonth && $filterYear) {
            $totalAmountQuery->whereYear('ris.ris_created_at', $filterYear)
                             ->whereMonth('ris.ris_created_at', $filterMonth);
        }

        $totalAmount = $totalAmountQuery->sum('items.ris_total_amount');

        // ================================
        // Weekly breakdown — last 4 weeks
        // ================================

        $weeklyStats = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekEnd = now()->subWeeks($i)->endOfWeek(Carbon::SUNDAY);
            $weekStart = $weekEnd->copy()->subDays(6);

            $approved = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereBetween('ris_created_at', [$weekStart, $weekEnd])
                ->count();

            $rejected = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Rejected')
                ->whereBetween('ris_created_at', [$weekStart, $weekEnd])
                ->count();

            $pending = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Pending')
                ->whereBetween('ris_created_at', [$weekStart, $weekEnd])
                ->count();

            $weeklyStats[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'label' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d, Y'),
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
            ];
        }

        // ================================
        // Monthly breakdown
        // ================================

        $monthlyStats = [];
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        if ($filterMonth && $filterYear) {
            // Show only the selected month
            $approved = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereYear('ris_created_at', $filterYear)
                ->whereMonth('ris_created_at', $filterMonth)
                ->count();

            $rejected = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Rejected')
                ->whereYear('ris_created_at', $filterYear)
                ->whereMonth('ris_created_at', $filterMonth)
                ->count();

            $pending = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Pending')
                ->whereYear('ris_created_at', $filterYear)
                ->whereMonth('ris_created_at', $filterMonth)
                ->count();

            $monthlyStats[] = [
                'year_month' => sprintf('%04d-%02d', $filterYear, $filterMonth),
                'month_label' => $monthNames[$filterMonth] . ' ' . $filterYear,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
            ];
        } else {
            // Show last 6 months by default
            for ($i = 5; $i >= 0; $i--) {
                $y = (int) date('Y', strtotime("-$i months"));
                $m = (int) date('m', strtotime("-$i months"));

                $approved = DB::table('requisition_issue_slip_table')
                    ->where('ris_status', 'Approved')
                    ->whereYear('ris_created_at', $y)
                    ->whereMonth('ris_created_at', $m)
                    ->count();

                $rejected = DB::table('requisition_issue_slip_table')
                    ->where('ris_status', 'Rejected')
                    ->whereYear('ris_created_at', $y)
                    ->whereMonth('ris_created_at', $m)
                    ->count();

                $pending = DB::table('requisition_issue_slip_table')
                    ->where('ris_status', 'Pending')
                    ->whereYear('ris_created_at', $y)
                    ->whereMonth('ris_created_at', $m)
                    ->count();

                $monthlyStats[] = [
                    'year_month' => sprintf('%04d-%02d', $y, $m),
                    'month_label' => $monthNames[$m] . ' ' . $y,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'pending' => $pending,
                ];
            }
        }

        return view('president.reports.monthly-summary', [
            'approvedDecisionsCount' => $risApproved,
            'rejectedDecisionsCount' => $risRejected,
            'pendingApprovalsCount' => $risPending,
            'totalRis' => $totalRis,
            'totalAmount' => $totalAmount,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
        ]);
    }

    // =====================================================
    // NOTIFICATIONS
    // =====================================================

    public function notifications(): View
    {
        // Fetch the 3 most recent RIS records forwarded to the President
        $recentRis = DB::table('requisition_issue_slip_table')
            ->select(
                'ris_id',
                'ris_form_number',
                'ris_status',
                'ris_created_at',
                'ris_purpose_description'
            )
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->orderByDesc('ris_created_at')
            ->limit(3)
            ->get();

        $allRis = DB::table('requisition_issue_slip_table')
            ->select(
                'ris_id',
                'ris_form_number',
                'ris_status',
                'ris_created_at',
                'ris_purpose_description'
            )
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->orderByDesc('ris_created_at')
            ->get();

        return view('president.notifications.index', [
            'recentRis' => $recentRis,
            'allRis' => $allRis,
        ]);
    }

    public function rejectionHistory(): View
    {
        return view('president.notifications.rejection-history');
    }

    // =====================================================
    // PROFILE
    // =====================================================

    public function profile(): View
    {
        return view('president.profile.index');
    }

    // =====================================================
    // RIS VIEWER (for preview in modal)
    // =====================================================
    public function viewRis($ris)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->first();

        if (!$ris) {
            abort(404, 'RIS not found');
        }

        $risItems = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $ris->ris_id)
            ->get();

        return view('president.ris.viewer', [
            'ris' => $ris,
            'risItems' => $risItems,
        ]);
    }
}
