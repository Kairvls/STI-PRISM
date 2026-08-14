<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PurchaserController extends Controller
{
    // PURCHASER DASHBOARD
    public function dashboard()
    {
        // Count pending replacement requests
        $pendingReplacementRequests = DB::table('procurement_requests_table')
            ->where('procurement_request_status', 'Pending')
            ->count();

        // Count approved replacement requests
        $approvedReplacementRequests = DB::table('procurement_requests_table')
            ->where('procurement_request_status', 'Approved')
            ->count();

        // Count completed replacement requests
        $completedReplacementRequests = DB::table('procurement_requests_table')
            ->where('procurement_request_status', 'Completed')
            ->count();

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
        $risReadyForAtp = DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Approved')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('authority_to_purchase_table')
                    ->whereColumn('authority_to_purchase_table.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id');
            })
            ->count();

        return view('purchaser.dashboard', compact(
            'pendingReplacementRequests',
            'approvedReplacementRequests',
            'completedReplacementRequests',
            'availableUrgentReports',
            'risReadyForAtp'
        ));
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

    // RIS MODULE: SHOW RIS DASHBOARD AND LIST
    public function risIndex(Request $request)
    {
        $risQuery = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'procurement_requests_table.procurement_request_status',
                'reports_table.report_id',
                'reports_table.report_problem_description',
                'reports_table.report_replacement_notes',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'rooms_table.room_name',
            );

        // Search filter
        if ($request->filled('search')) {
            $risQuery->where(function ($query) use ($request) {
                $query
                    ->where('requisition_issue_slip_table.ris_form_number', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('procurement_requests_table.procurement_request_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('equipment_table.equipment_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('reports_table.report_unlisted_equipment_name', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $risQuery->where('requisition_issue_slip_table.ris_status', $request->status);
        }

        // Date filters
        if ($request->filled('date_from')) {
            $risQuery->whereDate('requisition_issue_slip_table.ris_created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $risQuery->whereDate('requisition_issue_slip_table.ris_created_at', '<=', $request->date_to);
        }

        // Paginated RIS list
        $risRecords = $risQuery
            ->orderByDesc('requisition_issue_slip_table.ris_created_at')
            ->paginate(10)
            ->withQueryString();

        // Supporting documents for list downloads
        $attachmentsByRis = DB::table('ris_attachments_table')
            ->whereIn('ris_id', $risRecords->pluck('ris_id'))
            ->orderBy('ris_attachment_original_name')
            ->get()
            ->groupBy('ris_id');

        $itemsByRis = DB::table('requisition_issue_slip_items_table')
            ->whereIn('ris_id', $risRecords->pluck('ris_id'))
            ->orderBy('ris_item_id')
            ->get()
            ->groupBy('ris_id');

        $risHasAtp = DB::table('authority_to_purchase_table')
            ->whereIn('authority_purchase_ris_id', $risRecords->pluck('ris_id'))
            ->pluck('authority_purchase_ris_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $releasedRisIds = DB::table('approval_logs_table')
            ->where('approval_log_reference_type', 'RIS')
            ->whereIn('approval_log_reference_id', $risRecords->pluck('ris_id'))
            ->where(function ($q) {
                $q->where('approval_log_approval_remarks', 'like', '%returned to Purchaser%')
                    ->orWhere('approval_log_approval_status', 'Co-signed')
                    ->orWhereIn('approval_log_level', ['Admin Return', 'Admin Co-sign']);
            })
            ->pluck('approval_log_reference_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Revision history (Minor Revision notes from Admin)
        $risRevisions = DB::table('ris_revision_notes_table as revisions')
            ->leftJoin('users_table as users', 'users.user_id', '=', 'revisions.ris_revision_requested_by')
            ->whereIn('revisions.ris_id', $risRecords->pluck('ris_id'))
            ->select(
                'revisions.*',
                'users.user_full_name as revision_requested_by_name'
            )
            ->orderBy('revisions.ris_revision_created_at', 'desc')
            ->get()
            ->groupBy('ris_id');

        // Attach items, attachments, revisions, and ATP status to each RIS
        foreach ($risRecords as $ris) {
            $ris->risItems = $itemsByRis->get($ris->ris_id, collect());
            $ris->risAttachments = $attachmentsByRis->get($ris->ris_id, collect());
            $ris->risRevisions = $risRevisions->get($ris->ris_id, collect());
            $ris->has_atp = in_array($ris->ris_id, $risHasAtp);
            $ris->released_to_purchaser = in_array((int) $ris->ris_id, $releasedRisIds, true);
            $ris->can_create_atp = $ris->ris_status === 'Directly Approved'
                || ($ris->ris_status === 'Approved' && $ris->released_to_purchaser);
        }

        // Dashboard counts
        $risSummary = [
            'total' => DB::table('requisition_issue_slip_table')->count(),

            'draft' => DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Draft')
                ->count(),

            'submitted' => DB::table('requisition_issue_slip_table')
                ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted'])
                ->count(),

            'approved' => DB::table('requisition_issue_slip_table')
                ->whereIn('ris_status', ['Approved', 'Directly Approved'])
                ->count(),

            'rejected' => DB::table('requisition_issue_slip_table')
                ->whereIn('ris_status', ['Rejected', 'Rejected by President'])
                ->count(),
        ];
        // RIS MODULE: load approved replacement requests without an existing RIS
        $availableReplacementRequests = DB::table('procurement_requests_table')
            ->join(
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
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->where(
                'procurement_requests_table.procurement_request_status',
                'Approved'
            )
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('requisition_issue_slip_table')
                    ->whereColumn(
                        'requisition_issue_slip_table.ris_procurement_request_id',
                        'procurement_requests_table.procurement_request_id'
                    );
            })
            ->select(
                'procurement_requests_table.procurement_request_id',
                'procurement_requests_table.procurement_request_status',
                'reports_table.report_id',
                'reports_table.report_problem_description',
                'reports_table.report_replacement_notes',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'rooms_table.room_name'
            )
            ->orderByDesc('procurement_requests_table.procurement_request_created_at')
            ->get();

        return view('purchaser.ris.index', compact(
            'risRecords',
            'risSummary',
            'attachmentsByRis',
            'itemsByRis',
            'risHasAtp',
            'availableReplacementRequests'
        ));
    }

// =====================================================
// RIS MODULE: CREATE RIS AS DRAFT OR SUBMITTED
// =====================================================
    public function storeRis(Request $request)
    {
        // =====================================================
        // 1. DETERMINE ACTION
        // draft = save without sending to Admin
        // submit = validate everything and send to Admin
        // =====================================================
        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';


        // =====================================================
        // 2. VALIDATE BASIC RIS DATA
        // Drafts are allowed to be incomplete.
        // Submission requires the important RIS information.
        // =====================================================
        $validated = $request->validate([
            'save_action' => [
                'required',
                'in:draft,submit',
            ],

            'ris_procurement_request_id' => [
                'nullable',
                'integer',
                'exists:procurement_requests_table,procurement_request_id',
            ],

            'ris_form_number' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:100',
            ],

            'ris_purpose_description' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:5000',
            ],

            // =============================================
            // RIS ITEMS
            // =============================================
            'ris_items' => [
                'nullable',
                'array',
                'max:50',
            ],

            'ris_items.*.name_description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'ris_items.*.quantity_requested' => [
                'nullable',
                'integer',
                'min:1',
                'max:999999',
            ],

            'ris_items.*.quantity_issued' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],

            'ris_items.*.unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            // We still accept the field from the form,
            // but we DO NOT trust its value.
            // The server calculates the amount itself.
            'ris_items.*.total_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            // =============================================
            // REQUESTED BY
            // =============================================
            'ris_requested_by' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:255',
            ],

            // Browser calendar removed.
            // User enters: dd/mm/yyyy
            'ris_requested_by_date' => [
                $isDraft ? 'nullable' : 'required',
                'date_format:d/m/Y',
            ],

            // =============================================
            // THESE ARE NOT ENTERED BY PURCHASER
            // They are completed later in the workflow.
            // =============================================
            'ris_approved_by' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ris_approved_by_date' => [
                'nullable',
            ],

            'ris_issued_by' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ris_issued_by_date' => [
                'nullable',
            ],

            'ris_received_by' => [
                'nullable',
                'string',
                'max:255',
            ],

            'ris_received_by_date' => [
                'nullable',
            ],

            // =============================================
            // SUPPORTING DOCUMENTS
            // Word and Excel only
            // Maximum 10 MB each
            // =============================================
            'ris_attachments' => [
                'nullable',
                'array',
                'max:10',
            ],

            'ris_attachments.*' => [
                'file',
                'mimes:doc,docx,xls,xlsx',
                'max:10240',
            ],
        ], [
            // =============================================
            // FRIENDLIER VALIDATION MESSAGES
            // =============================================
            'ris_form_number.required' =>
                'RIS number is required before submitting.',

            'ris_purpose_description.required' =>
                'Purpose is required before submitting.',

            'ris_requested_by.required' =>
                'Requested By is required before submitting.',

            'ris_requested_by_date.required' =>
                'Requested By date is required before submitting.',

            'ris_requested_by_date.date_format' =>
                'Requested By date must use dd/mm/yyyy format.',

            'ris_items.*.quantity_requested.integer' =>
                'Quantity Requested must be a whole number.',

            'ris_items.*.quantity_requested.min' =>
                'Quantity Requested must be at least 1.',

            'ris_items.*.quantity_issued.integer' =>
                'Quantity Issued must be a whole number.',

            'ris_items.*.quantity_issued.min' =>
                'Quantity Issued cannot be negative.',

            'ris_items.*.unit_cost.numeric' =>
                'Unit Cost must be a valid number.',

            'ris_items.*.unit_cost.min' =>
                'Unit Cost cannot be negative.',

            'ris_attachments.max' =>
                'You may upload a maximum of 10 supporting documents.',

            'ris_attachments.*.mimes' =>
                'Supporting documents must be Word or Excel files only.',

            'ris_attachments.*.max' =>
                'Each supporting document must not exceed 10 MB.',
        ]);


        // =====================================================
        // 3. REMOVE COMPLETELY EMPTY ITEM ROWS
        // =====================================================
        $items = collect($validated['ris_items'] ?? [])
            ->filter(function ($item) {

                return filled($item['name_description'] ?? null)
                    || filled($item['quantity_requested'] ?? null)
                    || filled($item['quantity_issued'] ?? null)
                    || filled($item['unit_cost'] ?? null);

            })
            ->values();


        // =====================================================
        // 4. STRICT ITEM VALIDATION WHEN SUBMITTING
        // =====================================================
        if (!$isDraft) {

            if ($items->isEmpty()) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Please add at least one RIS item before submitting.'
                    );
            }

            foreach ($items as $index => $item) {

                $rowNumber = $index + 1;

                if (blank($item['name_description'] ?? null)) {
                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            "Item {$rowNumber} needs an item description."
                        );
                }

                if (blank($item['quantity_requested'] ?? null)) {
                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            "Item {$rowNumber} needs a Quantity Requested."
                        );
                }

                if ((int) $item['quantity_requested'] < 1) {
                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            "Item {$rowNumber} Quantity Requested must be at least 1."
                        );
                }
            }
        }


        // =====================================================
        // 5. CONVERT dd/mm/yyyy TO MYSQL DATE
        //
        // Example:
        // 25/07/2026 becomes 2026-07-25
        // =====================================================
        $requestedByDate = null;

        if (!empty($validated['ris_requested_by_date'])) {

            $requestedByDate = \Carbon\Carbon::createFromFormat(
                'd/m/Y',
                $validated['ris_requested_by_date']
            )->format('Y-m-d');
        }


        // =====================================================
        // 6. VALIDATE REPLACEMENT REQUEST SOURCE
        // =====================================================
        $procurementRequestId =
            $validated['ris_procurement_request_id'] ?? null;

        if ($procurementRequestId) {

            $replacementRequest =
                DB::table('procurement_requests_table')
                    ->where(
                        'procurement_request_id',
                        $procurementRequestId
                    )
                    ->first();

            if (
                !$replacementRequest
                || $replacementRequest->procurement_request_status
                    !== 'Approved'
            ) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Only approved replacement requests can be used to create an RIS.'
                    );
            }


            // Prevent duplicate RIS from the same replacement request
            $existingRis =
                DB::table('requisition_issue_slip_table')
                    ->where(
                        'ris_procurement_request_id',
                        $procurementRequestId
                    )
                    ->exists();

            if ($existingRis) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'This replacement request already has an RIS.'
                    );
            }
        }


        // =====================================================
        // 7. SAVE RIS
        // =====================================================
        return DB::transaction(function () use (
            $request,
            $validated,
            $items,
            $isDraft,
            $procurementRequestId,
            $requestedByDate
        ) {

            $risId =
                DB::table('requisition_issue_slip_table')
                    ->insertGetId([

                        'ris_procurement_request_id' =>
                            $procurementRequestId,

                        'ris_form_number' =>
                            $validated['ris_form_number'] ?? null,

                        'ris_purpose_description' =>
                            $validated['ris_purpose_description'] ?? null,

                        'ris_status' =>
                            $isDraft
                                ? 'Draft'
                                : 'Submitted',

                        // =========================================
                        // REQUESTED BY
                        // =========================================
                        'ris_requested_by_signature' =>
                            $validated['ris_requested_by'] ?? null,

                        'ris_requested_by_date' =>
                            $requestedByDate,

                        // =========================================
                        // APPROVED / ISSUED / RECEIVED
                        // Purchaser cannot fill these during creation.
                        // =========================================
                        'ris_approved_by_signature' => null,
                        'ris_approved_by_date' => null,

                        'ris_issued_by_signature' => null,
                        'ris_issued_by_date' => null,

                        'ris_received_by_signature' => null,
                        'ris_received_by_date' => null,

                        // =========================================
                        // SUBMISSION TRACKING
                        // =========================================
                        'ris_submitted_by' =>
                            $isDraft
                                ? null
                                : Auth::id(),

                        'ris_submitted_at' =>
                            $isDraft
                                ? null
                                : now(),

                        'ris_created_at' => now(),
                        'ris_updated_at' => now(),
                    ]);


            // =====================================================
            // 8. SAVE RIS ITEMS
            // =====================================================
            foreach ($items as $item) {

                $quantityIssued =
                    (int) ($item['quantity_issued'] ?? 0);

                $unitCost =
                    (float) ($item['unit_cost'] ?? 0);


                // =============================================
                // IMPORTANT:
                // Never trust total_amount from the browser.
                //
                // Amount = Quantity Issued × Unit Cost
                // =============================================
                $totalAmount =
                    round(
                        $quantityIssued * $unitCost,
                        2
                    );


                DB::table(
                    'requisition_issue_slip_items_table'
                )->insert([

                    'ris_id' => $risId,

                    'ris_item_name_description' =>
                        $item['name_description'] ?? null,

                    'ris_quantity_requested' =>
                        $item['quantity_requested'] ?? null,

                    'ris_quantity_issued' =>
                        $quantityIssued,

                    'ris_unit_cost' =>
                        $item['unit_cost'] ?? null,

                    'ris_total_amount' =>
                        $totalAmount,
                ]);
            }


            // =====================================================
            // 9. SAVE SUPPORTING DOCUMENTS
            // =====================================================
            foreach (
                $request->file('ris_attachments', [])
                as $document
            ) {

                $storedPath =
                    $document->store(
                        'ris-supporting-documents/' . $risId,
                        'public'
                    );

                DB::table('ris_attachments_table')
                    ->insert([

                        'ris_id' => $risId,

                        'ris_attachment_original_name' =>
                            $document->getClientOriginalName(),

                        'ris_attachment_path' =>
                            $storedPath,

                        'ris_attachment_mime_type' =>
                            $document->getClientMimeType(),

                        'ris_attachment_size' =>
                            $document->getSize(),

                        'ris_attachment_uploaded_by' =>
                            Auth::id(),

                        'ris_attachment_created_at' =>
                            now(),
                    ]);
            }


            return redirect()
                ->route('purchaser.ris.index')
                ->with(
                    'success',
                    $isDraft
                        ? 'RIS saved as draft.'
                        : 'RIS submitted to Admin successfully.'
                );
        });
    }

// =====================================================
    // RIS PRINT / PREVIEW
    // Used by Purchaser, Admin, and President
    // =====================================================

    public function printRis($risId)
    {
        // =====================================================
        // GET RIS RECORD
        // =====================================================

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404, 'RIS not found.');


        // =====================================================
        // GET RIS ITEMS
        // =====================================================

        $risItems = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get();

        // Pad with empty items to fill 10 rows
        $risItems = $risItems->pad(10, null);


        // =====================================================
        // GET PRESIDENT NAME (if President has signed)
        //
        // When the President signs (Forwarded to President),
        // ris_approved_by_signature stores the base64 image.
        // The President's name is stored in approval_logs_table.
        // =====================================================

        $presidentName = null;

        if (
            !empty($ris->ris_approved_by_signature) &&
            strpos($ris->ris_approved_by_signature, 'data:image') === 0
        ) {
            // Try to get President name from approval logs
            try {
                $presidentApproval = DB::table('approval_logs_table')
                    ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
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


        // =====================================================
        // RETURN RIS PRINT VIEW
        // =====================================================

        return view('purchaser.ris.print', [
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => $presidentName,
        ]);
    }
// =====================================================
// RIS MODULE: UPDATE DRAFT OR MINOR REVISION RIS
// =====================================================
public function updateRis(Request $request, $risId)
{
    $ris = DB::table('requisition_issue_slip_table')
        ->where('ris_id', $risId)
        ->first();

    abort_if(!$ris, 404);


    // =====================================================
    // ONLY DRAFT AND MINOR REVISION CAN BE EDITED
    // =====================================================
    if (
        !in_array(
            $ris->ris_status,
            ['Draft', 'Minor Revision'],
            true
        )
    ) {
        return back()->with(
            'error',
            'This RIS can no longer be edited.'
        );
    }


    // =====================================================
    // DETERMINE ACTION
    // =====================================================
    $saveAction =
        $request->input('save_action', 'save');

    $isSaveOnly =
        $saveAction === 'save';


    // =====================================================
    // VALIDATE
    // =====================================================
    $validated = $request->validate([

        'save_action' => [
            'required',
            'in:save,submit,resubmit',
        ],

        'ris_form_number' => [
            $isSaveOnly ? 'nullable' : 'required',
            'string',
            'max:100',
        ],

        'ris_purpose_description' => [
            $isSaveOnly ? 'nullable' : 'required',
            'string',
            'max:5000',
        ],


        // =============================================
        // ITEMS
        // =============================================
        'ris_items' => [
            'nullable',
            'array',
            'max:50',
        ],

        'ris_items.*.name_description' => [
            'nullable',
            'string',
            'max:2000',
        ],

        'ris_items.*.quantity_requested' => [
            'nullable',
            'integer',
            'min:1',
            'max:999999',
        ],

        'ris_items.*.quantity_issued' => [
            'nullable',
            'integer',
            'min:0',
            'max:999999',
        ],

        'ris_items.*.unit_cost' => [
            'nullable',
            'numeric',
            'min:0',
            'max:999999999.99',
        ],

        'ris_items.*.total_amount' => [
            'nullable',
            'numeric',
            'min:0',
        ],


        // =============================================
        // REQUESTED BY
        // =============================================
        'ris_requested_by' => [
            $isSaveOnly ? 'nullable' : 'required',
            'string',
            'max:255',
        ],

        'ris_requested_by_date' => [
            $isSaveOnly ? 'nullable' : 'required',
            'date_format:d/m/Y',
        ],


        // =============================================
        // ATTACHMENTS
        // =============================================
        'ris_attachments' => [
            'nullable',
            'array',
            'max:10',
        ],

        'ris_attachments.*' => [
            'file',
            'mimes:doc,docx,xls,xlsx',
            'max:10240',
        ],

    ], [

        'ris_form_number.required' =>
            'RIS number is required before submitting.',

        'ris_purpose_description.required' =>
            'Purpose is required before submitting.',

        'ris_requested_by.required' =>
            'Requested By is required before submitting.',

        'ris_requested_by_date.required' =>
            'Requested By date is required before submitting.',

        'ris_requested_by_date.date_format' =>
            'Requested By date must use dd/mm/yyyy format.',

        'ris_items.*.quantity_requested.min' =>
            'Quantity Requested must be at least 1.',

        'ris_items.*.quantity_issued.min' =>
            'Quantity Issued cannot be negative.',

        'ris_items.*.unit_cost.min' =>
            'Unit Cost cannot be negative.',

        'ris_attachments.*.mimes' =>
            'Supporting documents must be Word or Excel files only.',

        'ris_attachments.*.max' =>
            'Each supporting document must not exceed 10 MB.',
    ]);


    // =====================================================
    // REMOVE COMPLETELY EMPTY ITEM ROWS
    // =====================================================
    $items = collect($validated['ris_items'] ?? [])
        ->filter(function ($item) {

            return filled($item['name_description'] ?? null)
                || filled($item['quantity_requested'] ?? null)
                || filled($item['quantity_issued'] ?? null)
                || filled($item['unit_cost'] ?? null);

        })
        ->values();


    // =====================================================
    // STRICT VALIDATION WHEN SUBMITTING / RESUBMITTING
    // =====================================================
    if (!$isSaveOnly) {

        if ($items->isEmpty()) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Please add at least one RIS item before submitting.'
                );
        }


        foreach ($items as $index => $item) {

            $rowNumber = $index + 1;

            if (blank($item['name_description'] ?? null)) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        "Item {$rowNumber} needs an item description."
                    );
            }

            if (blank($item['quantity_requested'] ?? null)) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        "Item {$rowNumber} needs a Quantity Requested."
                    );
            }
        }
    }


    // =====================================================
    // MAKE SURE ACTION MATCHES CURRENT STATUS
    // =====================================================
    if (
        $saveAction === 'submit'
        && $ris->ris_status !== 'Draft'
    ) {
        return back()->with(
            'error',
            'Only a Draft RIS can be submitted.'
        );
    }


    if (
        $saveAction === 'resubmit'
        && $ris->ris_status !== 'Minor Revision'
    ) {
        return back()->with(
            'error',
            'Only an RIS under Minor Revision can be resubmitted.'
        );
    }


    // =====================================================
    // CONVERT REQUESTED DATE
    // =====================================================
    $requestedByDate = null;

    if (!empty($validated['ris_requested_by_date'])) {

        $requestedByDate =
            \Carbon\Carbon::createFromFormat(
                'd/m/Y',
                $validated['ris_requested_by_date']
            )->format('Y-m-d');
    }


    // =====================================================
    // UPDATE
    // =====================================================
    return DB::transaction(function () use (
        $request,
        $validated,
        $items,
        $ris,
        $risId,
        $saveAction,
        $requestedByDate
    ) {

        $newStatus =
            $ris->ris_status;


        if ($saveAction === 'submit') {

            $newStatus = 'Submitted';

        } elseif ($saveAction === 'resubmit') {

            $newStatus = 'Resubmitted';
        }


        // =================================================
        // IMPORTANT:
        // Approved By, Issued By and Received By are
        // intentionally NOT updated here.
        //
        // Existing values stay untouched.
        // =================================================
        $updateData = [

            'ris_form_number' =>
                $validated['ris_form_number'] ?? null,

            'ris_purpose_description' =>
                $validated['ris_purpose_description'] ?? null,

            'ris_status' =>
                $newStatus,

            'ris_requested_by_signature' =>
                $validated['ris_requested_by'] ?? null,

            'ris_requested_by_date' =>
                $requestedByDate,

            'ris_updated_at' =>
                now(),
        ];


        // =================================================
        // SUBMISSION TRACKING
        // =================================================
        if (
            in_array(
                $saveAction,
                ['submit', 'resubmit'],
                true
            )
        ) {

            $updateData['ris_submitted_by'] =
                Auth::id();

            $updateData['ris_submitted_at'] =
                now();
        }


        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->update($updateData);


        // =================================================
        // REPLACE CURRENT ITEMS
        // =================================================
        DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->delete();


        foreach ($items as $item) {

            $quantityIssued =
                (int) ($item['quantity_issued'] ?? 0);

            $unitCost =
                (float) ($item['unit_cost'] ?? 0);


            // SERVER CALCULATES THE AMOUNT
            $totalAmount =
                round(
                    $quantityIssued * $unitCost,
                    2
                );


            DB::table(
                'requisition_issue_slip_items_table'
            )->insert([

                'ris_id' =>
                    $risId,

                'ris_item_name_description' =>
                    $item['name_description'] ?? null,

                'ris_quantity_requested' =>
                    $item['quantity_requested'] ?? null,

                'ris_quantity_issued' =>
                    $quantityIssued,

                'ris_unit_cost' =>
                    $item['unit_cost'] ?? null,

                'ris_total_amount' =>
                    $totalAmount,
            ]);
        }


        // =================================================
        // ADD NEW ATTACHMENTS
        // Existing attachments remain.
        // =================================================
        foreach (
            $request->file('ris_attachments', [])
            as $document
        ) {

            $storedPath =
                $document->store(
                    'ris-supporting-documents/' . $risId,
                    'public'
                );


            DB::table('ris_attachments_table')
                ->insert([

                    'ris_id' =>
                        $risId,

                    'ris_attachment_original_name' =>
                        $document->getClientOriginalName(),

                    'ris_attachment_path' =>
                        $storedPath,

                    'ris_attachment_mime_type' =>
                        $document->getClientMimeType(),

                    'ris_attachment_size' =>
                        $document->getSize(),

                    'ris_attachment_uploaded_by' =>
                        Auth::id(),

                    'ris_attachment_created_at' =>
                        now(),
                ]);
        }


        $message = match ($saveAction) {

            'submit' =>
                'RIS updated and submitted to Admin.',

            'resubmit' =>
                'RIS corrections saved and resubmitted to Admin.',

            default =>
                'RIS changes saved successfully.',
        };


        return redirect()
            ->route('purchaser.ris.index')
            ->with('success', $message);
    });
}

// =====================================================
// RIS MODULE: SUBMIT DRAFT RIS TO ADMIN
// =====================================================
public function submitRis($risId)
{
    return DB::transaction(function () use ($risId) {

        // =================================================
        // GET AND LOCK RIS
        // =================================================
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->lockForUpdate()
            ->first();

        abort_if(!$ris, 404);


        // =================================================
        // ONLY DRAFT RIS CAN BE SUBMITTED
        // =================================================
        if ($ris->ris_status !== 'Draft') {

            return back()->with(
                'error',
                'Only Draft RIS records can be submitted.'
            );
        }


        // =================================================
        // RIS NUMBER REQUIRED
        // =================================================
        if (blank($ris->ris_form_number)) {

            return back()->with(
                'error',
                'RIS number is required before submitting.'
            );
        }


        // =================================================
        // PURPOSE REQUIRED
        // =================================================
        if (blank($ris->ris_purpose_description)) {

            return back()->with(
                'error',
                'RIS purpose is required before submitting.'
            );
        }


        // =================================================
        // REQUESTED BY REQUIRED
        // =================================================
        if (blank($ris->ris_requested_by_signature)) {

            return back()->with(
                'error',
                'Requested By is required before submitting.'
            );
        }


        // =================================================
        // REQUESTED DATE REQUIRED
        // =================================================
        if (blank($ris->ris_requested_by_date)) {

            return back()->with(
                'error',
                'Requested By date is required before submitting.'
            );
        }


        // =================================================
        // GET ALL RIS ITEMS
        // =================================================
        $items = DB::table(
            'requisition_issue_slip_items_table'
        )
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get();


        // =================================================
        // AT LEAST ONE ITEM REQUIRED
        // =================================================
        if ($items->isEmpty()) {

            return back()->with(
                'error',
                'Please add at least one RIS item before submitting.'
            );
        }


        // =================================================
        // VALIDATE EVERY ITEM
        // =================================================
        foreach ($items as $index => $item) {

            $rowNumber = $index + 1;


            // =============================================
            // ITEM DESCRIPTION
            // =============================================
            if (
                blank(
                    $item->ris_item_name_description
                )
            ) {

                return back()->with(
                    'error',
                    "Item {$rowNumber} is missing its item description."
                );
            }


            // =============================================
            // QUANTITY REQUESTED
            // =============================================
            if (
                $item->ris_quantity_requested === null
                || (int) $item->ris_quantity_requested < 1
            ) {

                return back()->with(
                    'error',
                    "Item {$rowNumber} must have a Quantity Requested of at least 1."
                );
            }


            // =============================================
            // QUANTITY ISSUED CANNOT BE NEGATIVE
            // =============================================
            if (
                $item->ris_quantity_issued !== null
                && (int) $item->ris_quantity_issued < 0
            ) {

                return back()->with(
                    'error',
                    "Item {$rowNumber} has an invalid Quantity Issued."
                );
            }


            // =============================================
            // UNIT COST CANNOT BE NEGATIVE
            // =============================================
            if (
                $item->ris_unit_cost !== null
                && (float) $item->ris_unit_cost < 0
            ) {

                return back()->with(
                    'error',
                    "Item {$rowNumber} has an invalid Unit Cost."
                );
            }


            // =============================================
            // RECALCULATE AMOUNT
            // Never trust a manually supplied amount.
            // =============================================
            $quantityIssued =
                (int) ($item->ris_quantity_issued ?? 0);

            $unitCost =
                (float) ($item->ris_unit_cost ?? 0);

            $correctAmount = round(
                $quantityIssued * $unitCost,
                2
            );


            DB::table(
                'requisition_issue_slip_items_table'
            )
                ->where(
                    'ris_item_id',
                    $item->ris_item_id
                )
                ->update([

                    'ris_total_amount' =>
                        $correctAmount,
                ]);
        }


        // =================================================
        // EVERYTHING IS VALID
        // SEND RIS TO ADMIN
        // =================================================
        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->update([

                'ris_status' =>
                    'Submitted',

                'ris_submitted_by' =>
                    Auth::id(),

                'ris_submitted_at' =>
                    now(),

                'ris_updated_at' =>
                    now(),
            ]);


        return redirect()
            ->route('purchaser.ris.index')
            ->with(
                'success',
                'RIS submitted to Admin successfully.'
            );
    });
}

    // RIS MODULE: DOWNLOAD SUPPORTING DOCUMENT
    public function downloadRisAttachment($attachmentId)
    {
        $attachment = DB::table('ris_attachments_table')
            ->where('ris_attachment_id', $attachmentId)
            ->first();

        abort_if(!$attachment, 404);
        abort_if(!Storage::disk('public')->exists($attachment->ris_attachment_path), 404);

        return Storage::disk('public')->download(
            $attachment->ris_attachment_path,
            $attachment->ris_attachment_original_name
        );
    }
}
