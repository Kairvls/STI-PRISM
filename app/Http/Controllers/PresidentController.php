<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Support\RisWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use App\Support\WorkflowNotifier;

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
                ->where(function ($q) {
                    $this->scopeAwaitingPresident($q);
                })
                ->count();

        $approvedDecisionsCount =
            DB::table('requisition_issue_slip_table')
                ->where(function ($q) {
                    $this->scopePresidentApproved($q);
                })
                ->count();

        $rejectedDecisionsCount =
            DB::table('requisition_issue_slip_table')
                ->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
                ->count();

        // ================================
        // Monthly stats (last 6 months)
        // ================================
        $monthlyStats = [];
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        for ($i = 5; $i >= 0; $i--) {
            $y = (int) date('Y', strtotime("-$i months"));
            $m = (int) date('m', strtotime("-$i months"));

            $approved = DB::table('requisition_issue_slip_table')
                ->where(function ($q) {
                    $this->scopePresidentApproved($q);
                })
                ->where(function ($q) use ($y, $m) {
                    $q->where(function ($byApprovedDate) use ($y, $m) {
                        $byApprovedDate->whereNotNull('ris_approved_by_date')
                            ->whereYear('ris_approved_by_date', $y)
                            ->whereMonth('ris_approved_by_date', $m);
                    })->orWhere(function ($byCreated) use ($y, $m) {
                        $byCreated->whereNull('ris_approved_by_date')
                            ->whereYear('ris_created_at', $y)
                            ->whereMonth('ris_created_at', $m);
                    });
                })
                ->count();

            $rejected = DB::table('requisition_issue_slip_table')
                ->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
                ->whereYear('ris_created_at', $y)
                ->whereMonth('ris_created_at', $m)
                ->count();

            $pending = DB::table('requisition_issue_slip_table')
                ->where(function ($q) {
                    $this->scopeAwaitingPresident($q);
                })
                ->whereYear('ris_created_at', $y)
                ->whereMonth('ris_created_at', $m)
                ->count();

            $monthlyStats[] = [
                'year_month' => sprintf('%04d-%02d', $y, $m),
                'month_label' => $monthNames[$m],
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
            ];
        }

        // ================================
        // Top 3 most recent RIS awaiting decision
        // ================================
        $recentRis = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_requested_by_signature',
                'ris.ris_approved_by_signature',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->where(function ($q) {
                $this->scopeAwaitingPresident($q, 'ris.');
            })
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_requested_by_signature',
                'ris.ris_approved_by_signature'
            )
            ->orderByDesc('ris.ris_created_at')
            ->orderByDesc('ris.ris_id')
            ->limit(3)
            ->get();

        // ================================
        // Recently approved RIS (for dashboard panel)
        // ================================
        $recentlyApprovedRis = DB::table('requisition_issue_slip_table')
            ->select(
                'ris_id',
                'ris_form_number',
                'ris_status',
                'ris_approved_by_date',
                'ris_approved_by_signature',
                'ris_purpose_description',
                'ris_issued_by_signature'
            )
            ->where(function ($q) {
                $this->scopePresidentApproved($q);
            })
            ->orderByDesc('ris_approved_by_date')
            ->orderByDesc('ris_id')
            ->limit(5)
            ->get()
            ->map(function ($ris) {
                $ris->admin_notified = $this->presidentHasNotifiedAdmin((int) $ris->ris_id);
                $ris->awaiting_notify = !$ris->admin_notified
                    && trim((string) ($ris->ris_issued_by_signature ?? '')) === '';

                return $ris;
            });

        $awaitingNotifyCount = DB::table('requisition_issue_slip_table')
            ->where(function ($q) {
                $this->scopePresidentApproved($q);
            })
            ->where(function ($q) {
                $q->whereNull('ris_issued_by_signature')
                    ->orWhere('ris_issued_by_signature', '');
            })
            ->get()
            ->filter(fn ($ris) => !$this->presidentHasNotifiedAdmin((int) $ris->ris_id))
            ->count();

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
            'awaitingNotifyCount' => $awaitingNotifyCount,
            'notificationsCount' => $notificationsCount,
            'monthlyStats' => $monthlyStats,
            'recentRis' => $recentRis,
            'recentlyApprovedRis' => $recentlyApprovedRis,
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
            ->where('ris_status', '!=', 'Directly Approved');

        $totalRisCount = (clone $forwardedBase)->count();

        $totalPendingRis = (clone $forwardedBase)
            ->where(function ($q) {
                $this->scopeAwaitingPresident($q);
            })
            ->count();

        $totalApprovedRis = (clone $forwardedBase)
            ->where(function ($q) {
                $this->scopePresidentApproved($q);
            })
            ->count();

        $totalRejectedRis = (clone $forwardedBase)
            ->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
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
                'ris.ris_requested_by_signature',
                'ris.ris_created_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->whereNotNull('ris.ris_requested_by_date')
            ->where('ris.ris_status', '!=', 'Directly Approved')
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_purpose_description',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_approved_by_signature',
                'ris.ris_requested_by_signature',
                'ris.ris_created_at'
            );

        $query->where(function ($q) {
            $this->scopeAwaitingPresident($q, 'ris.');
        });

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
        // Order & paginate (oldest / first-in first)
        // ================================
        $pendingRis = $query
            ->orderBy('ris.ris_created_at')
            ->orderBy('ris.ris_id')
            ->paginate(10)
            ->withQueryString();

        // ================================
        // Approved but not yet notified (eligible to pin / stay in queue)
        // ================================
        $awaitingNotifyRis = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_purpose_description',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_approved_by_signature',
                'ris.ris_requested_by_signature',
                'ris.ris_issued_by_signature',
                'ris.ris_created_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->where(function ($q) {
                $this->scopePresidentApproved($q, 'ris.');
            })
            ->where(function ($q) {
                $q->whereNull('ris.ris_issued_by_signature')
                    ->orWhere('ris.ris_issued_by_signature', '');
            })
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_purpose_description',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_approved_by_signature',
                'ris.ris_requested_by_signature',
                'ris.ris_issued_by_signature',
                'ris.ris_created_at'
            )
            ->orderBy('ris.ris_approved_by_date')
            ->orderBy('ris.ris_id')
            ->get()
            ->filter(fn ($ris) => !$this->presidentHasNotifiedAdmin((int) $ris->ris_id))
            ->values()
            ->map(function ($ris) {
                $ris->is_president_approved = true;
                $ris->awaiting_notify = true;
                $ris->queue_kind = 'awaiting_notify';

                return $ris;
            });

        // ================================
        // Recent decisions (oldest first in the stack)
        // ================================
        $recentRis = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_approved_by_date',
                'ris.ris_approved_by_signature',
                'ris.ris_issued_by_signature',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->whereIn('ris.ris_status', [
                'Approved',
                'Approved by the President',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
            ])
            ->whereNotNull('ris.ris_requested_by_date')
            ->where('ris.ris_status', '!=', 'Directly Approved')
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_approved_by_date',
                'ris.ris_approved_by_signature',
                'ris.ris_issued_by_signature'
            )
            ->orderBy('ris.ris_approved_by_date')
            ->orderBy('ris.ris_created_at')
            ->orderBy('ris.ris_id')
            ->limit(10)
            ->get()
            ->map(function ($ris) {
                $isApproved = RisWorkflow::isPresidentApproved($ris);
                $ris->is_president_approved = $isApproved;
                $ris->admin_notified = $isApproved && $this->presidentHasNotifiedAdmin((int) $ris->ris_id);
                $ris->awaiting_notify = $isApproved && !$ris->admin_notified
                    && trim((string) ($ris->ris_issued_by_signature ?? '')) === '';

                return $ris;
            });

        // ================================
        // AJAX response: return JSON with rendered partial
        // ================================
        if ($request->ajax()) {
            $tableHtml = view('president.approvals._table', [
                'pendingRis' => $pendingRis,
                'awaitingNotifyRis' => $awaitingNotifyRis,
            ])->render();

            return response()->json([
                'table_html' => $tableHtml,
                'total' => $pendingRis->total(),
                'awaiting_review' => $totalPendingRis,
                'from' => $pendingRis->firstItem(),
                'to' => $pendingRis->lastItem(),
                'current_page' => $pendingRis->currentPage(),
                'last_page' => $pendingRis->lastPage(),
            ]);
        }

        $pendingValue = (float) DB::table('requisition_issue_slip_items_table as items')
            ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
            ->where(function ($q) {
                $this->scopeAwaitingPresident($q, 'ris.');
            })
            ->sum('items.ris_total_amount');

        $latestSubmitted = $pendingRis->first();

        return view('president.approvals.index', [
            'pendingRis' => $pendingRis,
            'awaitingNotifyRis' => $awaitingNotifyRis,
            'totalRisCount' => $totalRisCount,
            'totalPendingRis' => $totalPendingRis,
            'totalApprovedRis' => $totalApprovedRis,
            'totalRejectedRis' => $totalRejectedRis,
            'recentRis' => $recentRis,
            'pendingValue' => $pendingValue,
            'latestSubmitted' => $latestSubmitted,
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
            ->whereIn('ris.ris_status', [
                'Approved',
                'Approved by the President',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
            ])
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

        $fail = function (string $message, int $code = 422) use ($request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], $code);
            }
            return back()->with('error', $message);
        };

        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return $fail('Invalid RIS decision payload.');
        }

        $target = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->first();

        if (!$target) {
            return $fail('RIS not found.', 404);
        }

        if (!$this->risIsAwaitingPresident($target)) {
            return $fail('Only RIS records forwarded by Admin can be decided by President.');
        }

        if ($decision === 'Rejected' && trim((string) $remarks) === '') {
            return $fail('Please provide a rejection reason.');
        }

        $updateValues = [
            'ris_status' => $decision === 'Approved' ? 'Approved by the President' : 'Rejected by the President',
        ];

        if ($decision === 'Approved') {
            $presidentName = Auth::user()->user_full_name ?? 'President';
            $signature = trim((string) $request->input('signature_data', ''));
            $updateValues['ris_approved_by_signature'] = $signature !== '' && str_starts_with($signature, 'data:image')
                ? $signature
                : $presidentName;
            $updateValues['ris_approved_by_date'] = now()->toDateString();
        }

        try {
            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $targetId)
                ->update($updateValues);
        } catch (\Throwable $e) {
            return $fail(
                'Could not save the President decision. Run database migrations so presidential RIS statuses are valid.'
            );
        }

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

        // Reject still notifies Admin immediately. Approve only persists the
        // decision — Admin is notified when President clicks "Notify Admin".
        if ($decision === 'Rejected') {
            $form = $target->ris_form_number ?: ('RIS #' . $targetId);
            WorkflowNotifier::toRole(
                WorkflowNotifier::ROLE_ADMIN,
                'President rejected an RIS',
                $form . (trim((string) $remarks) !== '' ? (': ' . $remarks) : ''),
                'ris_president_rejected',
                'RIS',
                (int) $targetId,
                '/admin/digital-signatures/sign-ris'
            );
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'decision' => $decision,
                'ris_id' => (int) $targetId,
                'approved_by_date' => $decision === 'Approved'
                    ? ($updateValues['ris_approved_by_date'] ?? null)
                    : null,
                'admin_notified' => false,
                'message' => $decision === 'Approved'
                    ? 'RIS approved successfully. Notify Admin when ready.'
                    : 'RIS rejected successfully.',
            ]);
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
                'ris.ris_approved_by_signature',
                'ris.ris_issued_by_signature',
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
                'ris.ris_approved_by_signature',
                'ris.ris_issued_by_signature',
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
        $totalRejected = DB::table('requisition_issue_slip_table')->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])->count();
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
        $risRejected = (clone $risQuery)->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])->count();
        $risPending = (clone $risQuery)->where('ris_status', 'Pending')->count();
        $totalRis = $risApproved + $risRejected + $risPending;

        // Total amount (filtered)
        $totalAmountQuery = DB::table('requisition_issue_slip_items_table as items')
            ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
            ->whereIn('ris.ris_status', ['Approved', 'Approved by the President', 'Rejected', 'Rejected by President', 'Rejected by the President', 'Pending']);

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
            ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President']);

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
                ->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
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
                ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
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
                ->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
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
                ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
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
                    ->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
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
                    ->whereIn('ris.ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
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

    public function notifications(Request $request): View
    {
        $userId = Auth::id();
        $category = $request->get('category', 'all');
        $allowedCategories = ['all', 'Approvals', 'Rejections', 'workflow'];

        if (!in_array($category, $allowedCategories, true)) {
            $category = 'all';
        }

        $notifications = collect();
        $unreadCount = 0;

        if ($userId && Schema::hasTable('notifications_table')) {
            $query = DB::table('notifications_table');

            if (Schema::hasTable('notification_reads_table')) {
                $query->leftJoin('notification_reads_table', function ($join) use ($userId) {
                    $join->on(
                        'notifications_table.notification_id',
                        '=',
                        'notification_reads_table.notification_id'
                    )->where('notification_reads_table.user_id', '=', $userId);
                })
                    ->select('notifications_table.*')
                    ->selectRaw(
                        'CASE
                            WHEN notification_reads_table.notification_read_id IS NULL
                            THEN 0
                            ELSE 1
                        END AS is_read'
                    );
            } else {
                $query->select('notifications_table.*')
                    ->selectRaw('0 AS is_read');
            }

            $query = $this->applyPresidentNotificationAccess($query, $userId);

            if ($category !== 'all') {
                $query->where('notifications_table.notification_category', $category);
            }

            $notifications = $query
                ->orderByDesc('notifications_table.notification_created_at')
                ->limit(100)
                ->get();

            $unreadQuery = $this->applyPresidentNotificationAccess(
                DB::table('notifications_table'),
                $userId
            );

            if (Schema::hasTable('notification_reads_table')) {
                $unreadCount = $unreadQuery
                    ->whereNotExists(function ($sub) use ($userId) {
                        $sub->select(DB::raw(1))
                            ->from('notification_reads_table')
                            ->whereColumn(
                                'notification_reads_table.notification_id',
                                'notifications_table.notification_id'
                            )
                            ->where('notification_reads_table.user_id', $userId);
                    })
                    ->count();
            } else {
                $unreadCount = $unreadQuery->count();
            }
        }

        return view('president.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'activeCategory' => $category,
        ]);
    }

    public function openNotification($id)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(401);
        }

        $notification = $this->applyPresidentNotificationAccess(
            DB::table('notifications_table')->where('notifications_table.notification_id', $id),
            $userId
        )->first();

        if (!$notification) {
            abort(404);
        }

        if (Schema::hasTable('notification_reads_table')) {
            DB::table('notification_reads_table')->insertOrIgnore([
                'notification_id' => $notification->notification_id,
                'user_id' => $userId,
                'notification_read_at' => now(),
            ]);
        }

        $destination = $notification->notification_url;
        if (!$destination || !str_starts_with($destination, '/president/')) {
            return redirect('/president/notifications');
        }

        return redirect($destination);
    }

    public function markAllNotificationsAsRead()
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(401);
        }

        if (!Schema::hasTable('notifications_table') || !Schema::hasTable('notification_reads_table')) {
            return back()->with('success', 'All notifications are already read.');
        }

        $notificationIds = $this->applyPresidentNotificationAccess(
            DB::table('notifications_table'),
            $userId
        )
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('notification_reads_table')
                    ->whereColumn(
                        'notification_reads_table.notification_id',
                        'notifications_table.notification_id'
                    )
                    ->where('notification_reads_table.user_id', $userId);
            })
            ->pluck('notifications_table.notification_id');

        if ($notificationIds->isEmpty()) {
            return back()->with('success', 'All notifications are already read.');
        }

        $now = now();
        $readRows = $notificationIds->map(fn ($notificationId) => [
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'notification_read_at' => $now,
        ])->all();

        DB::table('notification_reads_table')->insertOrIgnore($readRows);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function rejectionHistory(): View
    {
        return view('president.notifications.rejection-history');
    }

    private function applyPresidentNotificationAccess($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('notifications_table.notification_user_id', $userId)
                ->orWhere(function ($query) {
                    $query->whereNull('notifications_table.notification_user_id')
                        ->where('notifications_table.notification_target_role', 'President');
                });
        });
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
    public function viewRis($ris, Request $request)
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

        if ($request->boolean('preview')) {
            return view('president.ris.viewer', [
                'ris' => $ris,
                'risItems' => $risItems,
                'presidentName' => Auth::user()->user_full_name ?? 'President',
                'isScreenPreview' => true,
            ]);
        }

        return view('admin.ris.print', [
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => Auth::user()->user_full_name ?? 'President',
        ]);
    }

    public function risDetails($ris)
    {
        $record = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->first();

        abort_if(!$record, 404);

        $items = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $record->ris_id)
            ->orderBy('ris_item_id')
            ->get();

        $attachments = collect();
        if (Schema::hasTable('ris_attachments_table')) {
            $attachments = DB::table('ris_attachments_table')
                ->where('ris_id', $record->ris_id)
                ->orderBy('ris_attachment_original_name')
                ->get();
        }

        $isPresidentApproved = RisWorkflow::isPresidentApproved($record);
        $adminNotified = $isPresidentApproved && $this->presidentHasNotifiedAdmin((int) $record->ris_id);

        return response()->json([
            'ris_id' => $record->ris_id,
            'form_number' => $record->ris_form_number,
            'purpose' => $record->ris_purpose_description,
            'requester_name' => $record->ris_requested_by_signature,
            'status' => $record->ris_status,
            'created_at' => $record->ris_created_at,
            'approved_by_date' => $record->ris_approved_by_date,
            'requested_by_date' => $record->ris_requested_by_date,
            'total_amount' => $items->sum(fn ($item) => (float) $item->ris_total_amount),
            'attachments' => $attachments->map(fn ($file) => [
                'id' => $file->ris_attachment_id,
                'name' => $file->ris_attachment_original_name,
                'size' => $file->ris_attachment_size ?? null,
                'url' => route('president.ris.attachments.download', $file->ris_attachment_id),
            ]),
            'has_president_signature' => trim((string) ($record->ris_approved_by_signature ?? '')) !== '',
            'is_president_approved' => $isPresidentApproved,
            'admin_notified' => $adminNotified,
            'awaiting_notify' => $isPresidentApproved && !$adminNotified
                && trim((string) ($record->ris_issued_by_signature ?? '')) === '',
        ]);
    }

    public function sendToAdmin($ris)
    {
        $record = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->first();

        abort_if(!$record, 404);

        if (!RisWorkflow::isPresidentApproved($record)) {
            return response()->json([
                'ok' => false,
                'message' => 'Only an approved RIS can notify Admin.',
            ], 422);
        }

        if ($this->presidentHasNotifiedAdmin((int) $record->ris_id)) {
            return response()->json([
                'ok' => true,
                'ris_id' => (int) $record->ris_id,
                'already_notified' => true,
                'message' => 'Admin was already notified for this RIS.',
            ]);
        }

        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $record->ris_id,
                'approval_log_level' => 'President',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => 'Approved',
                'approval_log_approval_remarks' => 'Notified Admin for co-sign',
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }

        $form = $record->ris_form_number ?: ('RIS #' . $record->ris_id);
        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_ADMIN,
            'President approved an RIS',
            $form . ' was approved by the President and is ready for Admin co-sign.',
            'ris_president_approved',
            'RIS',
            (int) $record->ris_id,
            '/admin/digital-signatures/sign-ris'
        );

        return response()->json([
            'ok' => true,
            'ris_id' => (int) $record->ris_id,
            'already_notified' => false,
            'message' => 'Admin has been notified.',
        ]);
    }

    public function approveForm($risId)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        if (!$this->risIsAwaitingPresident($ris)) {
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

    private function presidentHasNotifiedAdmin(int $risId): bool
    {
        try {
            return DB::table('approval_logs_table')
                ->where('approval_log_reference_type', 'RIS')
                ->where('approval_log_reference_id', $risId)
                ->where('approval_log_level', 'President')
                ->where(function ($q) {
                    $q->where('approval_log_approval_remarks', 'Notified Admin for co-sign')
                        ->orWhere('approval_log_approval_remarks', 'Forwarded to Admin for co-sign');
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function risIsAwaitingPresident(object $ris): bool
    {
        return RisWorkflow::isAwaitingPresident($ris);
    }

    private function scopeAwaitingPresident($query, string $prefix = '')
    {
        $status = $prefix . 'ris_status';
        $sig = $prefix . 'ris_approved_by_signature';
        $approvedDate = $prefix . 'ris_approved_by_date';

        return $query->where(function ($q) use ($status, $sig, $approvedDate) {
            $q->where($status, 'Forwarded to President')
                ->orWhere(function ($legacy) use ($status, $sig) {
                    $legacy->where($status, 'Approved')
                        ->where(function ($empty) use ($sig) {
                            $empty->whereNull($sig)->orWhere($sig, '');
                        });
                })
                ->orWhere(function ($queuedPending) use ($status, $sig, $approvedDate) {
                    $queuedPending->where($status, 'Pending')
                        ->whereNotNull($approvedDate)
                        ->where(function ($empty) use ($sig) {
                            $empty->whereNull($sig)->orWhere($sig, '');
                        });
                });
        });
    }

    private function scopePresidentApproved($query, string $prefix = '')
    {
        $status = $prefix . 'ris_status';
        $sig = $prefix . 'ris_approved_by_signature';

        return $query->where(function ($q) use ($status, $sig) {
            $q->where($status, 'Approved by the President')
                ->orWhere(function ($legacy) use ($status, $sig) {
                    // Legacy Approved counts only with a digital signature image.
                    $legacy->where($status, 'Approved')
                        ->whereNotNull($sig)
                        ->where($sig, '!=', '')
                        ->where($sig, 'like', 'data:image%');
                });
        });
    }

    private function parseFlexibleDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'j/n/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
