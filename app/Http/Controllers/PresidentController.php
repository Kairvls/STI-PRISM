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
        // Procurement decision counts
        // ================================
        // Read-only queries (no DB writes)
        $pendingApprovalsCount =
            \DB::table('procurement_requests_table')
                ->where('procurement_request_status', 'Pending')
                ->count();

        $approvedDecisionsCount =
            \DB::table('procurement_requests_table')
                ->where('procurement_request_status', 'Approved')
                ->count();

        $rejectedDecisionsCount =
            \DB::table('procurement_requests_table')
                ->where('procurement_request_status', 'Rejected')
                ->count();

        // ================================
        // Notifications count
        // ================================
        // Prefer role-targeted notifications if available,
        // otherwise show notifications assigned to the current user.
        $notificationsCount = 0;

        try {
            $user = \Auth::user();

            if ($user) {
                $notificationsCount =
                    \DB::table('notifications_table')
                        ->when(
                            !empty($user->user_role_id),
                            function ($q) use ($user) {
                                // notifications_table.notification_target_role stores role name (string)
                                // in your current SQL dump it's NULL, so this may be 0.
                                // We still keep it as a useful default.
                                return $q->where('notification_target_role', function ($sub) use ($user) {
                                    $sub->select('role_name')
                                        ->from('roles_table')
                                        ->whereColumn('roles_table.role_id', 'users_table.user_role_id')
                                        ->limit(1);
                                });
                            }
                        )
                        ->when(empty($user->user_role_id), function ($q) {
                            return $q;
                        })
                        ->where('notification_user_id', $user->user_id)
                        ->count();
            }
        } catch (\Throwable $e) {
            $notificationsCount = 0;
        }

        return view('president.dashboard', [
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'approvedDecisionsCount' => $approvedDecisionsCount,
            'rejectedDecisionsCount' => $rejectedDecisionsCount,
            'notificationsCount' => $notificationsCount,
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
        // Fetch RIS records that have been approved by the President
        $approvalHistoryRecords = \DB::table('requisition_issue_slip_table')
            ->select(
                'requisition_issue_slip_table.ris_id',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_status',
                'requisition_issue_slip_table.ris_approved_by_date'
            )
            ->where('requisition_issue_slip_table.ris_status', 'Approved')
            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_signature')
            ->orderByDesc('requisition_issue_slip_table.ris_approved_by_date')
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
        return view('president.reports.approved');
    }

    public function rejectedReports(): View
    {
        return view('president.reports.rejected');
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
        // Monthly breakdown — predefined months
        // (August 2025 to July 2026)
        // ================================

        $monthlyStats = [];

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        // Generate August 2025 to July 2026
        $periods = [
            ['year' => 2025, 'month' => 8],
            ['year' => 2025, 'month' => 9],
            ['year' => 2025, 'month' => 10],
            ['year' => 2025, 'month' => 11],
            ['year' => 2025, 'month' => 12],
            ['year' => 2026, 'month' => 1],
            ['year' => 2026, 'month' => 2],
            ['year' => 2026, 'month' => 3],
            ['year' => 2026, 'month' => 4],
            ['year' => 2026, 'month' => 5],
            ['year' => 2026, 'month' => 6],
            ['year' => 2026, 'month' => 7],
        ];

        foreach ($periods as $period) {
            $y = $period['year'];
            $m = $period['month'];

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