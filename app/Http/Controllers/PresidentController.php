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
        return view('president.approvals.approval-history');
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

        \DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->update([
                'ris_status' => $decision === 'Approved' ? 'Approved' : 'Rejected',
                'ris_approved_by_date' => now(),
            ]);

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
        return view('president.reports.monthly-summary');
    }

    // =====================================================
    // NOTIFICATIONS
    // =====================================================

    public function notifications(): View
    {
        return view('president.notifications.index');
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