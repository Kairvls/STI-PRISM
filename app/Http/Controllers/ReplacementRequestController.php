<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReplacementRequestController extends Controller
{
    public function index(Request $request)
    {
        $archiveView = $request->query('view') === 'archive';

        $query = DB::table('procurement_requests_table')
            ->join('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
            ->leftJoin('users_table as request_creator', 'procurement_requests_table.procurement_request_created_by', '=', 'request_creator.user_id')
            ->leftJoin(
                'requisition_issue_slip_table',
                'procurement_requests_table.procurement_request_id',
                '=',
                'requisition_issue_slip_table.ris_procurement_request_id'
            )
            ->select(
                'procurement_requests_table.*',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'reports_table.report_problem_description',
                'reports_table.report_suggested_issue',
                'reports_table.report_urgency_level',
                'reports_table.report_replacement_notes',
                'reports_table.report_replacement_image',
                'reports_table.report_submitted_at',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'rooms_table.room_name',
                'reporters_table.reporter_full_name',
                'reporters_table.reporter_employee_id',
                'reporters_table.reporter_contact_number',
                'request_creator.user_full_name as request_creator_name',
                // =====================================================
                // RIS INFORMATION
                // =====================================================
                'requisition_issue_slip_table.ris_id',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_status'
            );

        if (!$archiveView) {
            $query->where('procurement_requests_table.procurement_request_is_archived', false);
        } else {
            $query->where('procurement_requests_table.procurement_request_is_archived', true);
        }

        if ($request->filled('search')) {
            $query->where(function ($subQuery) use ($request) {
                $subQuery
                    ->where('procurement_requests_table.procurement_request_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('reports_table.report_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('equipment_table.equipment_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('rooms_table.room_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('procurement_requests_table.procurement_request_status', $request->status);
        }

        $replacementRequests = $query
            ->orderByDesc('procurement_requests_table.procurement_request_created_at')
            ->paginate(10)
            ->withQueryString();

        return view('purchaser.procurement.replacement-requests', compact('replacementRequests', 'archiveView'));
    }

    public function approve(Request $request, int $requestId)
    {
        $replacementRequest = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $requestId)
            ->first();

        if (!$replacementRequest) {
            return back()->with('error', 'Replacement request not found.');
        }

        if ($replacementRequest->procurement_request_is_archived) {
            return back()->with('error', 'Archived requests cannot be approved.');
        }

        if ($replacementRequest->procurement_request_status === 'Approved') {
            return back()->with('error', 'This request is already approved.');
        }

        DB::transaction(function () use ($requestId) {
            DB::table('procurement_requests_table')
                ->where('procurement_request_id', $requestId)
                ->update([
                    'procurement_request_status' => 'Approved',
                ]);

            DB::table('audit_logs_table')->insert([
                'audit_log_user_id' => Auth::id(),
                'audit_log_action' => 'Approved replacement request',
                'audit_log_table_name' => 'procurement_requests_table',
                'audit_log_reference_id' => $requestId,
                'audit_log_description' => 'Replacement request #' . $requestId . ' was approved by purchaser.',
                'audit_log_ip_address' => request()->ip(),
                'audit_log_created_at' => now(),
            ]);
        });

        return back()->with('success', 'Replacement request approved successfully.');
    }

    public function reject(Request $request, int $requestId)
    {
        $replacementRequest = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $requestId)
            ->first();

        if (!$replacementRequest) {
            return back()->with('error', 'Replacement request not found.');
        }

        if ($replacementRequest->procurement_request_is_archived) {
            return back()->with('error', 'Archived requests cannot be rejected.');
        }

        if ($replacementRequest->procurement_request_status === 'Rejected') {
            return back()->with('error', 'This request is already rejected.');
        }

        DB::transaction(function () use ($requestId) {
            DB::table('procurement_requests_table')
                ->where('procurement_request_id', $requestId)
                ->update([
                    'procurement_request_status' => 'Rejected',
                ]);

            DB::table('audit_logs_table')->insert([
                'audit_log_user_id' => Auth::id(),
                'audit_log_action' => 'Rejected replacement request',
                'audit_log_table_name' => 'procurement_requests_table',
                'audit_log_reference_id' => $requestId,
                'audit_log_description' => 'Replacement request #' . $requestId . ' was rejected by purchaser.',
                'audit_log_ip_address' => request()->ip(),
                'audit_log_created_at' => now(),
            ]);
        });

        return back()->with('success', 'Replacement request rejected successfully.');
    }

    public function archive(Request $request, int $requestId)
    {
        $replacementRequest = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $requestId)
            ->first();

        if (!$replacementRequest) {
            return back()->with('error', 'Replacement request not found.');
        }

        if ($replacementRequest->procurement_request_is_archived) {
            return back()->with('error', 'This request is already archived.');
        }

        if (!in_array($replacementRequest->procurement_request_status, ['Approved', 'Rejected', 'Completed'], true)) {
            return back()->with('error', 'Only approved, rejected, or completed replacement requests can be archived.');
        }

        DB::transaction(function () use ($requestId) {
            DB::table('procurement_requests_table')
                ->where('procurement_request_id', $requestId)
                ->update([
                    'procurement_request_is_archived' => true,
                ]);

            DB::table('audit_logs_table')->insert([
                'audit_log_user_id' => Auth::id(),
                'audit_log_action' => 'Archived replacement request',
                'audit_log_table_name' => 'procurement_requests_table',
                'audit_log_reference_id' => $requestId,
                'audit_log_description' => 'Replacement request #' . $requestId . ' was archived by purchaser.',
                'audit_log_ip_address' => request()->ip(),
                'audit_log_created_at' => now(),
            ]);
        });

        return back()->with('success', 'Replacement request archived successfully.');
    }

    public function restore(Request $request, int $requestId)
    {
        $replacementRequest = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $requestId)
            ->first();

        if (!$replacementRequest) {
            return back()->with('error', 'Replacement request not found.');
        }

        if (!$replacementRequest->procurement_request_is_archived) {
            return back()->with('error', 'This request is not archived.');
        }

        DB::transaction(function () use ($requestId) {
            DB::table('procurement_requests_table')
                ->where('procurement_request_id', $requestId)
                ->update([
                    'procurement_request_is_archived' => false,
                ]);

            DB::table('audit_logs_table')->insert([
                'audit_log_user_id' => Auth::id(),
                'audit_log_action' => 'Restored replacement request',
                'audit_log_table_name' => 'procurement_requests_table',
                'audit_log_reference_id' => $requestId,
                'audit_log_description' => 'Replacement request #' . $requestId . ' was restored from archive by purchaser.',
                'audit_log_ip_address' => request()->ip(),
                'audit_log_created_at' => now(),
            ]);
        });

        return back()->with('success', 'Replacement request restored successfully.');
    }
}