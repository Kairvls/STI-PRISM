<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Support\RisWorkflow;

class PurchaserController extends Controller
{
    // PURCHASER DASHBOARD
    public function dashboard()
    {
        $replacementCounts = DB::table('procurement_requests_table')
            ->select('procurement_request_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('procurement_request_status')
            ->pluck('aggregate', 'procurement_request_status');

        $pendingReplacementRequests = (int) ($replacementCounts['Pending'] ?? 0);
        $approvedReplacementRequests = (int) ($replacementCounts['Approved'] ?? 0);
        $completedReplacementRequests = (int) ($replacementCounts['Completed'] ?? 0);

        // Count available urgent reports
        // Available = urgent, pending, not archived, unclaimed by maintenance or purchaser
        $availableUrgentReports = DB::table('reports_table')
            ->where('report_urgency_level', 'Urgent')
            ->where('report_current_status', 'Pending')
            ->where('report_is_archived', 0)
            ->whereNull('report_assigned_personnel_id')
            ->whereNull('report_assigned_purchaser_id')
            ->count();

        // Count RIS ready for ATP
        $risReadyForAtp = RisWorkflow::applyEligibleForAtpScope(
            DB::table('requisition_issue_slip_table')
        )
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('authority_to_purchase_table')
                    ->whereColumn('authority_to_purchase_table.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id');
            })
            ->count();

        $atpReadyForRfc = 0;
        $rfcReadyForRr = 0;
        $rrReadyForLiq = 0;

        if (Schema::hasTable('authority_to_purchase_table')) {
            $atpReadyForRfc = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_status', 'Approved')
                ->where(function ($q) {
                    $q->whereNull('authority_purchase_is_archived')
                        ->orWhere('authority_purchase_is_archived', 0);
                })
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('request_check_table')
                        ->whereColumn(
                            'request_check_table.request_check_authority_purchase_id',
                            'authority_to_purchase_table.authority_purchase_id'
                        )
                        ->where('request_check_status', '!=', 'Rejected');

                    if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
                        $query->where(function ($inner) {
                            $inner->whereNull('request_check_is_archived')
                                ->orWhere('request_check_is_archived', 0);
                        });
                    }
                })
                ->count();
        }

        if (Schema::hasTable('request_check_table')) {
            $rfcReadyQuery = DB::table('request_check_table')
                ->where('request_check_status', 'Approved');

            if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
                $rfcReadyQuery->where(function ($q) {
                    $q->whereNull('request_check_is_archived')
                        ->orWhere('request_check_is_archived', 0);
                });
            }

            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $rfcReadyQuery->whereNotNull('request_check_funds_released_at');
            }

            if (
                Schema::hasTable('receiving_reports_table')
                && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
            ) {
                $rfcReadyQuery->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('receiving_reports_table')
                        ->whereColumn(
                            'receiving_reports_table.receiving_report_request_check_id',
                            'request_check_table.request_check_id'
                        )
                        ->where('receiving_report_status', '!=', 'Returned');

                    if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
                        $query->where(function ($inner) {
                            $inner->whereNull('receiving_report_is_archived')
                                ->orWhere('receiving_report_is_archived', 0);
                        });
                    }
                });
            }

            $rfcReadyForRr = $rfcReadyQuery->count();
        }

        if (Schema::hasTable('receiving_reports_table')) {
            $rrReadyQuery = DB::table('receiving_reports_table')
                ->where('receiving_report_status', 'Completed');

            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
                $rrReadyQuery->where(function ($q) {
                    $q->whereNull('receiving_report_is_archived')
                        ->orWhere('receiving_report_is_archived', 0);
                });
            }

            if (
                Schema::hasTable('liquidation_reports_table')
                && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')
            ) {
                $rrReadyQuery->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('liquidation_reports_table')
                        ->whereColumn(
                            'liquidation_reports_table.liquidation_report_receiving_report_id',
                            'receiving_reports_table.receiving_report_id'
                        )
                        ->where('liquidation_report_status', '!=', 'Rejected');

                    if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived')) {
                        $query->where(function ($inner) {
                            $inner->whereNull('liquidation_report_is_archived')
                                ->orWhere('liquidation_report_is_archived', 0);
                        });
                    }
                });
            }

            $rrReadyForLiq = $rrReadyQuery->count();
        }

        return view('purchaser.dashboard', compact(
            'pendingReplacementRequests',
            'approvedReplacementRequests',
            'completedReplacementRequests',
            'availableUrgentReports',
            'risReadyForAtp',
            'atpReadyForRfc',
            'rfcReadyForRr',
            'rrReadyForLiq'
        ));
    }

    public function notifications()
    {
        $items = collect();
        try {
            $items = DB::table('notifications_table')
                ->where(function ($q) {
                    $q->where('notification_user_id', Auth::id())
                        ->orWhere('notification_target_role', 'Purchaser');
                })
                ->orderByDesc('notification_created_at')
                ->limit(80)
                ->get();
        } catch (\Throwable $e) {
        }

        return view('purchaser.notifications.index', compact('items'));
    }

    // SHOW REPLACEMENT REQUESTS FROM MAINTENANCE
    public function replacementRequests(Request $request)
    {
        $query = DB::table('procurement_requests_table')
            ->join('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            // Reporter, used by replacement request cards and view modal
            ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
            // Maintenance personnel who created request (procurement_request_created_by stores user_id)
            ->leftJoin('users_table as request_creator', 'procurement_requests_table.procurement_request_created_by', '=', 'request_creator.user_id')
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
                'reports_table.report_uploaded_image',
            );

        // Search filter
        if ($request->filled('search')) {
            $query->where(function ($subQuery) use ($request) {
                $subQuery
                    ->where('procurement_requests_table.procurement_request_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('reports_table.report_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('equipment_table.equipment_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('rooms_table.room_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('procurement_requests_table.procurement_request_status', $request->status);
        }

        // Newest request first
        $replacementRequests = $query
            ->orderByDesc('procurement_requests_table.procurement_request_created_at')
            ->paginate(10)
            ->withQueryString();

        return view('purchaser.procurement.replacement-requests', compact('replacementRequests'));
    }

    // SHOW URGENT REPORTS
    public function urgentReports(Request $request)
    {
        $purchaserId = Auth::id();

        // Active or archive view (default: active)
        $archiveView = $request->query('view') === 'archive';

        $query = DB::table('reports_table')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
            ->select(
                'reports_table.*',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'rooms_table.room_name',
                'reporters_table.reporter_full_name',
                'reporters_table.reporter_employee_id',
                'reporters_table.reporter_contact_number'
            )
            ->where('reports_table.report_urgency_level', 'Urgent');

        if ($archiveView) {
            $query->where('reports_table.report_is_archived', true);
        } else {
            $query->where('reports_table.report_is_archived', false);
        }

        if ($request->filled('search')) {
            $query->where(function ($subQuery) use ($request) {
                $subQuery
                    ->where('reports_table.report_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('reports_table.report_unlisted_equipment_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('equipment_table.equipment_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('rooms_table.room_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('reporters_table.reporter_full_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('reports_table.report_current_status', $request->status);
        }

        $urgentReports = $query
            ->orderByDesc('reports_table.report_updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('purchaser.reports.urgent-reports', compact('urgentReports', 'archiveView'));
    }

    // PURCHASER ACCEPT URGENT REPORT
    public function acceptUrgentReport($reportId)
    {
        $purchaserId = Auth::id();

        return DB::transaction(function () use ($reportId, $purchaserId) {

            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            abort_if(!$report, 404);

            abort_unless($report->report_urgency_level === 'Urgent', 403);

            if ($report->report_is_archived) {
                return back()->with('error', 'Archived reports cannot be accepted.');
            }

            if ($report->report_current_status !== 'Pending') {
                return back()->with('error', 'This urgent report is no longer available.');
            }

            if ($report->report_assigned_personnel_id !== null) {
                return back()->with('error', 'Maintenance personnel is already handling this report.');
            }

            if ($report->report_assigned_purchaser_id !== null) {
                return back()->with('error', 'Another purchaser is already handling this report.');
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_current_status' => 'Processing',
                    'report_assigned_purchaser_id' => $purchaserId,
                    'report_purchaser_assigned_at' => now(),
                ]);

            return back()->with('success', 'Urgent report accepted successfully.');
        });
    }

    // PURCHASER RESOLVE URGENT REPORT
    public function resolveUrgentReport(Request $request, $reportId)
    {
        $request->validate([
            'resolution_notes' => 'nullable|string|max:5000',
            'resolution_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $purchaserId = Auth::id();

        // Store file before the DB transaction so we don't hold the row lock while uploading
        $resolutionImagePath = null;

        if ($request->hasFile('resolution_image')) {
            $resolutionImagePath = $request->file('resolution_image')->store('report-resolutions', 'public');
        }

        return DB::transaction(function () use ($request, $reportId, $purchaserId, $resolutionImagePath) {

            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            abort_if(!$report, 404);

            if ($report->report_is_archived) {
                return back()->with('error', 'Archived reports cannot be resolved.');
            }

            // Report must be urgent, processing, owned by this purchaser, and not owned by maintenance
            if (
                $report->report_urgency_level !== 'Urgent'
                || $report->report_current_status !== 'Processing'
                || (int) $report->report_assigned_purchaser_id !== (int) $purchaserId
                || $report->report_assigned_personnel_id !== null
            ) {
                return back()->with('error', 'You cannot resolve this urgent report.');
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_current_status' => 'Resolved',
                    'report_resolution_notes' => $request->resolution_notes,
                    'report_resolution_image' => $resolutionImagePath,
                    'report_updated_at' => now(),
                ]);

            return back()->with('success', 'Urgent report resolved successfully.');
        });
    }

    // PURCHASER SEND URGENT REPORT FOR REPLACEMENT
    public function replaceUrgentReport(Request $request, $reportId)
    {
        $request->validate([
            'replacement_notes' => 'required|string|max:5000',
            'replacement_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $purchaserId = Auth::id();

        // Store file before the DB transaction so we don't hold the row lock while uploading
        $replacementImagePath = null;

        if ($request->hasFile('replacement_image')) {
            $replacementImagePath = $request->file('replacement_image')->store('report-replacements', 'public');
        }

        return DB::transaction(function () use ($request, $reportId, $purchaserId, $replacementImagePath) {

            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            abort_if(!$report, 404);

            if ($report->report_is_archived) {
                return back()->with('error', 'Archived reports cannot be sent for replacement.');
            }

            // Report must be urgent, processing, owned by this purchaser, and not owned by maintenance
            if (
                $report->report_urgency_level !== 'Urgent'
                || $report->report_current_status !== 'Processing'
                || (int) $report->report_assigned_purchaser_id !== (int) $purchaserId
                || $report->report_assigned_personnel_id !== null
            ) {
                return back()->with('error', 'You cannot send this urgent report for replacement.');
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_current_status' => 'For Replacement',
                    'report_replacement_notes' => $request->replacement_notes,
                    'report_replacement_image' => $replacementImagePath,
                    'report_replacement_submitted_to_purchaser' => 1,
                    'report_updated_at' => now(),
                ]);

            $procurementRequestExists = DB::table('procurement_requests_table')
                ->where('procurement_request_report_id', $reportId)
                ->exists();

            if (!$procurementRequestExists) {
                DB::table('procurement_requests_table')->insert([
                    'procurement_request_report_id' => $reportId,
                    'procurement_request_status' => 'Pending',
                    'procurement_request_created_by' => $purchaserId,
                    'procurement_request_created_at' => now(),
                ]);
            }

            return back()->with('success', 'Urgent report sent for replacement successfully.');
        });
    }

    // PURCHASER ARCHIVE URGENT REPORT
    public function archiveUrgentReport($reportId)
    {
        $purchaserId = Auth::id();

        return DB::transaction(function () use ($reportId, $purchaserId) {

            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            abort_if(!$report, 404);

            if ($report->report_urgency_level !== 'Urgent') {
                return back()->with('error', 'Only urgent reports can be archived here.');
            }

            if ((int) $report->report_assigned_purchaser_id !== (int) $purchaserId) {
                return back()->with('error', 'You can only archive urgent reports assigned to you.');
            }

            if ($report->report_assigned_personnel_id !== null) {
                return back()->with('error', 'This report belongs to maintenance personnel.');
            }

            // Report must be finished: Resolved or For Replacement
            if (!in_array($report->report_current_status, ['Resolved', 'For Replacement'], true)) {
                return back()->with('error', 'Only completed urgent reports can be archived.');
            }

            if ($report->report_is_archived) {
                return back()->with('error', 'This urgent report is already archived.');
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_is_archived' => 1,
                    'report_updated_at' => now(),
                ]);

            return back()->with('success', 'Urgent report archived successfully.');
        });
    }

    // PURCHASER RESTORE ARCHIVED URGENT REPORT
    public function restoreUrgentReport($reportId)
    {
        $purchaserId = Auth::id();

        return DB::transaction(function () use ($reportId, $purchaserId) {

            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            abort_if(!$report, 404);

            // Must be urgent, belong to this purchaser, not owned by maintenance, and archived
            if (
                $report->report_urgency_level !== 'Urgent'
                || (int) $report->report_assigned_purchaser_id !== (int) $purchaserId
                || $report->report_assigned_personnel_id !== null
                || !$report->report_is_archived
            ) {
                return back()->with('error', 'You cannot restore this urgent report.');
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_is_archived' => 0,
                    'report_updated_at' => now(),
                ]);

            return redirect()
                ->route('purchaser.reports.urgent', ['view' => 'archive'])
                ->with('success', 'Urgent report restored successfully.');
        });
    }
}
