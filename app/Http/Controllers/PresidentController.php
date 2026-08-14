<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

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
                ->where('ris_status', 'Approved')
                ->whereNotNull('ris_approved_by_date')
                ->where(function ($q) {
                    $q->whereNull('ris_approved_by_signature')
                        ->orWhere('ris_approved_by_signature', '');
                })
                ->count();

        $approvedDecisionsCount =
            DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereNotNull('ris_approved_by_signature')
                ->where('ris_approved_by_signature', '!=', '')
                ->count();

        $rejectedDecisionsCount =
            DB::table('requisition_issue_slip_table')
                ->whereIn('ris_status', ['Rejected', 'Rejected by President'])
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
                ->whereIn('ris_status', ['Rejected', 'Rejected by President'])
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
            $user = Auth::user();
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
        $forwardedBase = DB::table('requisition_issue_slip_table')
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->where('ris_status', '!=', 'Directly Approved');

        $totalRisCount = (clone $forwardedBase)->count();

        $totalPendingRis = (clone $forwardedBase)
            ->where('ris_status', 'Approved')
            ->where(function ($q) {
                $q->whereNull('ris_approved_by_signature')
                    ->orWhere('ris_approved_by_signature', '');
            })
            ->count();

        $totalApprovedRis = (clone $forwardedBase)
            ->where('ris_status', 'Approved')
            ->whereNotNull('ris_approved_by_signature')
            ->where('ris_approved_by_signature', '!=', '')
            ->count();

        $totalRejectedRis = (clone $forwardedBase)
            ->whereIn('ris_status', ['Rejected', 'Rejected by President'])
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
                'ris.ris_approved_by_signature',
                'ris.ris_created_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->whereNotNull('ris.ris_requested_by_date')
            ->whereNotNull('ris.ris_approved_by_date')
            ->where('ris.ris_status', '!=', 'Directly Approved')
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_purpose_description',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_approved_by_signature',
                'ris.ris_created_at'
            );

        $status = $request->query('status', 'Pending');
        if (!in_array($status, ['', 'Pending', 'Approved', 'Rejected'], true)) {
            $status = 'Pending';
        }

        if ($status === 'Pending') {
            $query->where('ris.ris_status', 'Approved')
                ->where(function ($q) {
                    $q->whereNull('ris.ris_approved_by_signature')
                        ->orWhere('ris.ris_approved_by_signature', '');
                });
        } elseif ($status === 'Approved') {
            $query->where('ris.ris_status', 'Approved')
                ->whereNotNull('ris.ris_approved_by_signature')
                ->where('ris.ris_approved_by_signature', '!=', '');
        } elseif ($status === 'Rejected') {
            $query->whereIn('ris.ris_status', ['Rejected', 'Rejected by President']);
        }

        // ================================
        // Search filter
        // ================================
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_requested_by_signature', 'LIKE', "%{$search}%");

                $this->addDateSearch($q, 'ris.ris_created_at', $search);
            });
        }

        // ================================
        // Order & paginate
        // ================================
        $pendingRis = $query
            ->orderByDesc('ris.ris_id')
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
            'status' => $status,
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
            ->whereIn('ris.ris_status', ['Approved', 'Rejected', 'Rejected by President'])
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

        if (!in_array($target->ris_status, ['Approved', 'Pending'], true) || empty($target->ris_approved_by_date)) {
            return back()->with('error', 'Only RIS records forwarded by Admin can be decided by President.');
        }

        if ($target->ris_status === 'Directly Approved') {
            return back()->with('error', 'Admin Approved RIS records are not sent to the President.');
        }

        $updateValues = [
            'ris_status' => $decision === 'Approved' ? 'Approved' : 'Rejected by President',
        ];

        if ($decision === 'Approved') {
            $validated = $request->validate([
                'ris_approved_by' => ['required', 'string', 'max:255'],
                'ris_approved_by_date' => ['required', 'string', 'max:20'],
            ]);

            try {
                $approvedDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($validated['ris_approved_by_date']))
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
                return back()->with('error', 'Approved by date must be in dd/mm/yyyy format.');
            }

            $updateValues['ris_approved_by_signature'] = trim($validated['ris_approved_by']);
            $updateValues['ris_approved_by_date'] = $approvedDate;
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
                'approval_log_approved_by' => Auth::id(),
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
                'approval_log_approved_by' => Auth::id(),
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
        $filter = $request->filled('filter') ? $request->filter : 'all';
        
        if (!in_array($filter, ['all', 'approved', 'rejected', 'pending'], true)) {
            $filter = 'all';
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
                'ris.ris_requested_by_signature',
                'log.approval_log_approval_remarks as remarks',
                'log.approval_log_approved_at as decided_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_requested_by_signature',
                'log.approval_log_approval_remarks',
                'log.approval_log_approved_at'
            );

        if ($filter !== 'all') {
            $status = $filter === 'approved' ? 'Approved' : ($filter === 'rejected' ? 'Rejected' : 'Pending');
            $query->where('ris.ris_status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_requested_by_signature', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_status', 'LIKE', "%{$search}%");

                $this->addDateSearch($q, 'ris.ris_created_at', $search);
            });
        }

        $outcomeRecords = $query
            ->orderByDesc('ris.ris_created_at')
            ->paginate(10)
            ->withQueryString();

        $totalApproved = DB::table('requisition_issue_slip_table')->where('ris_status', 'Approved')->count();
        $totalRejected = DB::table('requisition_issue_slip_table')->whereIn('ris_status', ['Rejected', 'Rejected by President'])->count();
        $totalPending = DB::table('requisition_issue_slip_table')->where('ris_status', 'Pending')->count();
        $totalDecisions = $totalApproved + $totalRejected + $totalPending;

        if ($request->ajax()) {
            $tableHtml = view('president.reports._approved-table', ['approvedOutcomeRecords' => $outcomeRecords, 'type' => $filter])->render();
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
            'totalApproved' => $totalApproved,
            'totalRejected' => $totalRejected,
            'totalPending' => $totalPending,
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
        $risRejected = (clone $risQuery)->whereIn('ris_status', ['Rejected', 'Rejected by President'])->count();
        $risPending = (clone $risQuery)->where('ris_status', 'Pending')->count();
        $totalRis = $risApproved + $risRejected + $risPending;

        // Total amount (filtered)
        $totalAmountQuery = DB::table('requisition_issue_slip_items_table as items')
            ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
            ->whereIn('ris.ris_status', ['Approved', 'Rejected', 'Rejected by President', 'Pending']);

        if ($filterMonth && $filterYear) {
            $totalAmountQuery->whereYear('ris.ris_created_at', $filterYear)
                             ->whereMonth('ris.ris_created_at', $filterMonth);
        }

        $totalAmount = $totalAmountQuery->sum('items.ris_total_amount');

        // Per-status amounts
        $approvedAmount = DB::table('requisition_issue_slip_items_table as items')
            ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
            ->where('ris.ris_status', 'Approved');

        $rejectedAmount = DB::table('requisition_issue_slip_items_table as items')
            ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
            ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President']);

        if ($filterMonth && $filterYear) {
            $approvedAmount->whereYear('ris.ris_created_at', $filterYear)
                           ->whereMonth('ris.ris_created_at', $filterMonth);
            $rejectedAmount->whereYear('ris.ris_created_at', $filterYear)
                           ->whereMonth('ris.ris_created_at', $filterMonth);
        }

        $approvedAmount = $approvedAmount->sum('items.ris_total_amount');
        $rejectedAmount = $rejectedAmount->sum('items.ris_total_amount');

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
                ->whereIn('ris_status', ['Rejected', 'Rejected by President'])
                ->whereBetween('ris_created_at', [$weekStart, $weekEnd])
                ->count();

            $pending = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Pending')
                ->whereBetween('ris_created_at', [$weekStart, $weekEnd])
                ->count();

            $total = $approved + $rejected + $pending;
            $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
            $rejectionRate = $total > 0 ? round(($rejected / $total) * 100, 1) : 0;

            $weekApprovedAmount = DB::table('requisition_issue_slip_items_table as items')
                ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
                ->where('ris.ris_status', 'Approved')
                ->whereBetween('ris.ris_created_at', [$weekStart, $weekEnd])
                ->sum('items.ris_total_amount');

            $weekRejectedAmount = DB::table('requisition_issue_slip_items_table as items')
                ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
                ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President'])
                ->whereBetween('ris.ris_created_at', [$weekStart, $weekEnd])
                ->sum('items.ris_total_amount');

            $avgProcessingTime = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereBetween('ris_created_at', [$weekStart, $weekEnd])
                ->whereNotNull('ris_approved_by_date')
                ->selectRaw('AVG(DATEDIFF(ris_approved_by_date, ris_created_at)) as avg_days')
                ->value('avg_days');

            $avgProcessingTime = $avgProcessingTime ? round($avgProcessingTime, 1) : null;

            // Trend: compare with previous week
            $prevWeekEnd = now()->subWeeks($i + 1)->endOfWeek(Carbon::SUNDAY);
            $prevWeekStart = $prevWeekEnd->copy()->subDays(6);
            $prevTotal = DB::table('requisition_issue_slip_table')
                ->whereBetween('ris_created_at', [$prevWeekStart, $prevWeekEnd])
                ->count();

            $trend = '➜ No Change';
            if ($prevTotal > 0 && $total > $prevTotal) {
                $trend = '▲ Improved';
            } elseif ($prevTotal > 0 && $total < $prevTotal) {
                $trend = '▼ Declined';
            }

            $weeklyStats[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'label' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d, Y'),
                'total' => $total,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
                'approval_rate' => $approvalRate,
                'rejection_rate' => $rejectionRate,
                'approved_amount' => $weekApprovedAmount ?? 0,
                'rejected_amount' => $weekRejectedAmount ?? 0,
                'avg_processing_time' => $avgProcessingTime,
                'trend' => $trend,
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
                ->whereIn('ris_status', ['Rejected', 'Rejected by President'])
                ->whereYear('ris_created_at', $filterYear)
                ->whereMonth('ris_created_at', $filterMonth)
                ->count();

            $pending = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Pending')
                ->whereYear('ris_created_at', $filterYear)
                ->whereMonth('ris_created_at', $filterMonth)
                ->count();

            $total = $approved + $rejected + $pending;
            $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
            $rejectionRate = $total > 0 ? round(($rejected / $total) * 100, 1) : 0;

            $monthApprovedAmount = DB::table('requisition_issue_slip_items_table as items')
                ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
                ->where('ris.ris_status', 'Approved')
                ->whereYear('ris.ris_created_at', $filterYear)
                ->whereMonth('ris.ris_created_at', $filterMonth)
                ->sum('items.ris_total_amount');

            $monthRejectedAmount = DB::table('requisition_issue_slip_items_table as items')
                ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
                ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President'])
                ->whereYear('ris.ris_created_at', $filterYear)
                ->whereMonth('ris.ris_created_at', $filterMonth)
                ->sum('items.ris_total_amount');

            $avgProcessingTime = DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereYear('ris_created_at', $filterYear)
                ->whereMonth('ris_created_at', $filterMonth)
                ->whereNotNull('ris_approved_by_date')
                ->selectRaw('AVG(DATEDIFF(ris_approved_by_date, ris_created_at)) as avg_days')
                ->value('avg_days');

            $avgProcessingTime = $avgProcessingTime ? round($avgProcessingTime, 1) : null;

            // Trend: compare with previous month
            $prevMonth = $filterMonth == 1 ? 12 : $filterMonth - 1;
            $prevYear = $filterMonth == 1 ? $filterYear - 1 : $filterYear;
            $prevTotal = DB::table('requisition_issue_slip_table')
                ->whereYear('ris_created_at', $prevYear)
                ->whereMonth('ris_created_at', $prevMonth)
                ->count();

            $trend = '➜ No Change';
            if ($prevTotal > 0 && $total > $prevTotal) {
                $trend = '▲ Improved';
            } elseif ($prevTotal > 0 && $total < $prevTotal) {
                $trend = '▼ Declined';
            }

            $monthlyStats[] = [
                'year_month' => sprintf('%04d-%02d', $filterYear, $filterMonth),
                'month_label' => $monthNames[$filterMonth] . ' ' . $filterYear,
                'total' => $total,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
                'approval_rate' => $approvalRate,
                'rejection_rate' => $rejectionRate,
                'approved_amount' => $monthApprovedAmount ?? 0,
                'rejected_amount' => $monthRejectedAmount ?? 0,
                'avg_processing_time' => $avgProcessingTime,
                'trend' => $trend,
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
                    ->whereIn('ris_status', ['Rejected', 'Rejected by President'])
                    ->whereYear('ris_created_at', $y)
                    ->whereMonth('ris_created_at', $m)
                    ->count();

                $pending = DB::table('requisition_issue_slip_table')
                    ->where('ris_status', 'Pending')
                    ->whereYear('ris_created_at', $y)
                    ->whereMonth('ris_created_at', $m)
                    ->count();

                $total = $approved + $rejected + $pending;
                $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
                $rejectionRate = $total > 0 ? round(($rejected / $total) * 100, 1) : 0;

                $monthApprovedAmount = DB::table('requisition_issue_slip_items_table as items')
                    ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
                    ->where('ris.ris_status', 'Approved')
                    ->whereYear('ris.ris_created_at', $y)
                    ->whereMonth('ris.ris_created_at', $m)
                    ->sum('items.ris_total_amount');

                $monthRejectedAmount = DB::table('requisition_issue_slip_items_table as items')
                    ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
                    ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President'])
                    ->whereYear('ris.ris_created_at', $y)
                    ->whereMonth('ris.ris_created_at', $m)
                    ->sum('items.ris_total_amount');

                $avgProcessingTime = DB::table('requisition_issue_slip_table')
                    ->where('ris_status', 'Approved')
                    ->whereYear('ris_created_at', $y)
                    ->whereMonth('ris_created_at', $m)
                    ->whereNotNull('ris_approved_by_date')
                    ->selectRaw('AVG(DATEDIFF(ris_approved_by_date, ris_created_at)) as avg_days')
                    ->value('avg_days');

                $avgProcessingTime = $avgProcessingTime ? round($avgProcessingTime, 1) : null;

                // Trend: compare with previous month
                $prevMonth = $m == 1 ? 12 : $m - 1;
                $prevYear = $m == 1 ? $y - 1 : $y;
                $prevTotal = DB::table('requisition_issue_slip_table')
                    ->whereYear('ris_created_at', $prevYear)
                    ->whereMonth('ris_created_at', $prevMonth)
                    ->count();

                $trend = '➜ No Change';
                if ($prevTotal > 0 && $total > $prevTotal) {
                    $trend = '▲ Improved';
                } elseif ($prevTotal > 0 && $total < $prevTotal) {
                    $trend = '▼ Declined';
                }

                $monthlyStats[] = [
                    'year_month' => sprintf('%04d-%02d', $y, $m),
                    'month_label' => $monthNames[$m] . ' ' . $y,
                    'total' => $total,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'pending' => $pending,
                    'approval_rate' => $approvalRate,
                    'rejection_rate' => $rejectionRate,
                    'approved_amount' => $monthApprovedAmount ?? 0,
                    'rejected_amount' => $monthRejectedAmount ?? 0,
                    'avg_processing_time' => $avgProcessingTime,
                    'trend' => $trend,
                ];
            }
        }

        // ================================
        // Executive insights
        // ================================

        $insights = [
            'approval_rate' => $totalRis > 0 ? round(($risApproved / $totalRis) * 100, 1) : 0,
            'rejection_rate' => $totalRis > 0 ? round(($risRejected / $totalRis) * 100, 1) : 0,
            'approved_amount' => $approvedAmount,
            'rejected_amount' => $rejectedAmount,
        ];

        // Find highest approval month (from monthly stats)
        $highestApprovalMonth = null;
        $highestApprovalCount = 0;
        foreach ($monthlyStats as $stat) {
            if ($stat['approved'] > $highestApprovalCount) {
                $highestApprovalCount = $stat['approved'];
                $highestApprovalMonth = $stat['month_label'];
            }
        }
        $insights['highest_approval_month'] = $highestApprovalMonth;

        // Find highest rejection month
        $highestRejectionMonth = null;
        $highestRejectionCount = 0;
        foreach ($monthlyStats as $stat) {
            if ($stat['rejected'] > $highestRejectionCount) {
                $highestRejectionCount = $stat['rejected'];
                $highestRejectionMonth = $stat['month_label'];
            }
        }
        $insights['highest_rejection_month'] = $highestRejectionMonth;

        return view('president.reports.monthly-summary', [
            'approvedDecisionsCount' => $risApproved,
            'rejectedDecisionsCount' => $risRejected,
            'pendingApprovalsCount' => $risPending,
            'totalRis' => $totalRis,
            'totalAmount' => $totalAmount,
            'approvedAmount' => $approvedAmount,
            'rejectedAmount' => $rejectedAmount,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
            'insights' => $insights,
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
            ->orderBy('ris_item_id')
            ->get()
            ->pad(8, null);

        return view('admin.ris.print', [
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => Auth::user()->user_full_name ?? 'President',
        ]);
    }

    public function approveForm($risId)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        if (
            $ris->ris_status !== 'Approved'
            || empty($ris->ris_approved_by_date)
            || !empty($ris->ris_approved_by_signature)
        ) {
            abort(403, 'Only RIS records forwarded by Admin can be signed by the President.');
        }

        $risItems = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get();

        return view('president.approvals._approve-form', [
            'ris' => $ris,
            'risItems' => $risItems,
        ]);
    }

    // =====================================================
    // SEARCH HELPER: DATE SEARCH
    // =====================================================

    private function addDateSearch($query, string $dateColumn, string $search): void
    {
        $timestamp = strtotime($search);
        $searchLower = strtolower($search);

        $query->orWhere(function ($dq) use ($dateColumn, $search, $timestamp, $searchLower) {
            // If strtotime succeeded, add exact date component matches
            if ($timestamp !== false) {
                $year = (int) date('Y', $timestamp);
                $monthNum = (int) date('m', $timestamp);
                $day = (int) date('d', $timestamp);

                $dq->whereDate($dateColumn, date('Y-m-d', $timestamp))
                   ->whereYear($dateColumn, $year)
                   ->whereMonth($dateColumn, $monthNum)
                   ->whereDay($dateColumn, $day);

                if (preg_match('/^\d{4}$/', $search)) {
                    $dq->orWhereYear($dateColumn, (int) $search);
                }

                if (preg_match('/^(0?[1-9]|[12]\d|3[01])$/', $search)) {
                    $dq->orWhereDay($dateColumn, (int) $search);
                }
            }

            // Always add month name matching (works even if strtotime failed)
            $monthNames = [
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ];
            $monthAbbreviations = [
                'jan', 'feb', 'mar', 'apr', 'may', 'jun',
                'jul', 'aug', 'sep', 'oct', 'nov', 'dec'
            ];

            foreach ($monthNames as $month) {
                if (str_contains($searchLower, $month)) {
                    $dq->orWhere($dateColumn, 'LIKE', "%{$month}%");
                    break;
                }
            }

            foreach ($monthAbbreviations as $abbr) {
                if (str_contains($searchLower, $abbr)) {
                    $dq->orWhere($dateColumn, 'LIKE', "%{$abbr}%");
                    break;
                }
            }
        });
    }
}
