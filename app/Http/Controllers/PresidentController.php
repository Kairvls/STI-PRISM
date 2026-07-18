<?php

namespace App\Http\Controllers;

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
        $totalRisCount = \DB::table('requisition_issue_slip_table')->count();

        $pendingApprovalsCount =
            \DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Pending')
                ->count();

        $approvedDecisionsCount =
            \DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->count();

        $rejectedDecisionsCount =
            \DB::table('requisition_issue_slip_table')
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

            $approved = \DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereYear('ris_created_at', $y)
                ->whereMonth('ris_created_at', $m)
                ->count();

            $rejected = \DB::table('requisition_issue_slip_table')
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
        $recentRis = \DB::table('requisition_issue_slip_table')
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
                $notificationsCount = \DB::table('notifications_table')
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

    public function approvals(): View
    {
        // President approvals page: include admin-forwarded RIS records that still need the President's final decision.
        $pendingRis = \DB::table('requisition_issue_slip_table')
            ->whereNotNull('ris_requested_by_date')
            ->whereNotNull('ris_approved_by_date')
            ->where(function ($query) {
                $query->where(function ($pendingQuery) {
                    $pendingQuery->where('ris_status', 'Pending');
                })
                ->orWhere(function ($approvedQuery) {
                    $approvedQuery->where('ris_status', 'Approved')
                        ->whereNotExists(function ($sub) {
                            $sub->select(\DB::raw(1))
                                ->from('approval_logs_table')
                                ->whereColumn('approval_logs_table.approval_log_reference_id', 'requisition_issue_slip_table.ris_id')
                                ->where('approval_logs_table.approval_log_reference_type', 'RIS')
                                ->where('approval_logs_table.approval_log_level', 'President');
                        });
                });
            })
            ->orderByDesc('ris_created_at')
            ->get();

        return view('president.approvals.index', [
            'pendingRis' => $pendingRis,
        ]);
    }


    public function approvalHistory(): View
    {
        $approvalHistoryRecords = \DB::table('requisition_issue_slip_table as ris')
            ->join('approval_logs_table as log', function ($join) {
                $join->on('log.approval_log_reference_id', '=', 'ris.ris_id')
                    ->where('log.approval_log_reference_type', '=', 'RIS')
                    ->where('log.approval_log_level', '=', 'President');
            })
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_purpose_description',
                'log.approval_log_approval_status as decision',
                'log.approval_log_approval_remarks as remarks',
                'log.approval_log_approved_at as decided_at'
            )
            ->whereIn('ris.ris_status', ['Approved', 'Rejected'])
            ->orderByDesc('log.approval_log_approved_at')
            ->get();

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

    public function decideRis(\Illuminate\Http\Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $remarks = $request->input('remarks');

        // Basic validation
        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Invalid RIS decision payload.');
        }

        $target = \DB::table('requisition_issue_slip_table')
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

        \DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->update($updateValues);

        // approval logs (optional but useful)
        try {
            \DB::table('approval_logs_table')->insert([
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

    public function decideProcurement(\Illuminate\Http\Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $remarks = $request->input('remarks');

        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Invalid procurement decision payload.');
        }

        $target = \DB::table('procurement_requests_table')
            ->where('procurement_request_id', $targetId)
            ->first();

        if (!$target) {
            return back()->with('error', 'Procurement request not found.');
        }

        \DB::table('procurement_requests_table')
            ->where('procurement_request_id', $targetId)
            ->update([
                'procurement_request_status' => $decision,
            ]);

        try {
            \DB::table('approval_logs_table')->insert([
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


    public function approvedReports(): View
    {
        $approvedRecords = \DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('approval_logs_table as log', function ($join) {
                $join->on('log.approval_log_reference_id', '=', 'ris.ris_id')
                    ->where('log.approval_log_reference_type', '=', 'RIS')
                    ->where('log.approval_log_level', '=', 'President');
            })
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'log.approval_log_approval_remarks as remarks',
                'log.approval_log_approved_at as decided_at'
            )
            ->where('ris.ris_status', 'Approved')
            ->orderByDesc('ris.ris_created_at')
            ->get();

        return view('president.reports.approved', [
            'approvedOutcomeRecords' => $approvedRecords,
        ]);
    }

    public function rejectedReports(): View
    {
        $rejectedRecords = \DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('approval_logs_table as log', function ($join) {
                $join->on('log.approval_log_reference_id', '=', 'ris.ris_id')
                    ->where('log.approval_log_reference_type', '=', 'RIS')
                    ->where('log.approval_log_level', '=', 'President');
            })
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'log.approval_log_approval_remarks as remarks',
                'log.approval_log_approved_at as decided_at'
            )
            ->where('ris.ris_status', 'Rejected')
            ->orderByDesc('ris.ris_created_at')
            ->get();

        return view('president.reports.rejected', [
            'rejectedOutcomeRecords' => $rejectedRecords,
        ]);
    }

    public function monthlySummary(): View
    {
        // ================================
        // All-time totals (RIS only)
        // ================================

        $risApproved = \DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->count();

        $risRejected = \DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Rejected')
            ->count();

        $risPending = \DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Pending')
            ->count();

        // ================================
        // Monthly breakdown — last 6 months
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

            $approved = \DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->whereYear('ris_created_at', $y)
                ->whereMonth('ris_created_at', $m)
                ->count();

            $rejected = \DB::table('requisition_issue_slip_table')
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

        return view('president.reports.monthly-summary', [
            'approvedDecisionsCount' => $risApproved,
            'rejectedDecisionsCount' => $risRejected,
            'pendingApprovalsCount' => $risPending,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    // =====================================================
    // NOTIFICATIONS
    // =====================================================

    public function notifications(): View
    {
        // Fetch the 3 most recent RIS records forwarded to the President
        // (records that have been approved by Admin but need President's decision)
        $recentRis = \DB::table('requisition_issue_slip_table')
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

        // Fetch all RIS records for the "View All" modal
        $allRis = \DB::table('requisition_issue_slip_table')
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
}