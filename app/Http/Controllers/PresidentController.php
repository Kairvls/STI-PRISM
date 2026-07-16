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
        return view('president.dashboard');
    }

    // =====================================================
    // APPROVALS
    // =====================================================

    public function approvals(): View
    {
        return view('president.approvals.index');
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