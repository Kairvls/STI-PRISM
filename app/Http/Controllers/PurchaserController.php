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
                ->where('ris_status', 'Approved')
                ->count(),

            'rejected' => DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Rejected')
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

    // RIS MODULE: CREATE RIS AS DRAFT OR SUBMITTED
    public function storeRis(Request $request)
    {
        // 1. Determine draft or submit
        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';

        // 2. Validate
        // Draft allows incomplete RIS information; submit requires RIS number, purpose, and items.
        $validated = $request->validate([
            'save_action' => ['required', 'in:draft,submit'],
            // RIS MODULE: optional source replacement request
            'ris_procurement_request_id' => [
                'nullable',
                'integer',
                'exists:procurement_requests_table,procurement_request_id'
            ],
            'ris_form_number' => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
            'ris_purpose_description' => [$isDraft ? 'nullable' : 'required', 'string', 'max:5000'],

            'ris_items' => ['nullable', 'array'],
            'ris_items.*.name_description' => ['nullable', 'string', 'max:2000'],
            'ris_items.*.quantity_requested' => ['nullable', 'integer', 'min:1'],
            'ris_items.*.quantity_issued' => ['nullable', 'integer', 'min:0'],
            'ris_items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'ris_items.*.total_amount' => ['nullable', 'numeric', 'min:0'],

            'ris_requested_by' => ['nullable', 'string', 'max:255'],
            'ris_requested_by_date' => ['nullable', 'date'],

            'ris_approved_by' => ['nullable', 'string', 'max:255'],
            'ris_approved_by_date' => ['nullable', 'date'],

            'ris_issued_by' => ['nullable', 'string', 'max:255'],
            'ris_issued_by_date' => ['nullable', 'date'],

            'ris_received_by' => ['nullable', 'string', 'max:255'],
            'ris_received_by_date' => ['nullable', 'date'],

            'ris_attachments' => ['nullable', 'array'],
            'ris_attachments.*' => ['file', 'mimes:doc,docx,xls,xlsx', 'max:10240'],
        ]);

        // 3. Remove empty item rows
        $items = collect($validated['ris_items'] ?? [])
            ->filter(fn ($item) => filled($item['name_description'] ?? null))
            ->values();

        // 4. Require items only for submission (draft allows zero items)
        if (!$isDraft && $items->isEmpty()) {
            return back()->withInput()->with('error', 'Please add at least one RIS item before submitting.');
        }

        // RIS MODULE: validate replacement request source
        $procurementRequestId = $validated['ris_procurement_request_id'] ?? null;

        if ($procurementRequestId) {
            $replacementRequest = DB::table('procurement_requests_table')
                ->where('procurement_request_id', $procurementRequestId)
                ->first();

            if (!$replacementRequest || $replacementRequest->procurement_request_status !== 'Approved') {
                return back()
                    ->withInput()
                    ->with('error', 'Only approved replacement requests can be used to create an RIS.');
            }

            $existingRis = DB::table('requisition_issue_slip_table')
                ->where('ris_procurement_request_id', $procurementRequestId)
                ->exists();

            if ($existingRis) {
                return back()
                    ->withInput()
                    ->with('error', 'This replacement request already has an RIS.');
            }
        }
        // 5. Save RIS
        return DB::transaction(function () use (
            $request,
            $validated,
            $items,
            $isDraft,
            $procurementRequestId,
        ) {

            $risId = DB::table('requisition_issue_slip_table')->insertGetId([
                // RIS information (drafts may be incomplete)
                // RIS MODULE: source replacement request, null means manual RIS
                'ris_procurement_request_id' => $procurementRequestId,
                'ris_form_number' => $validated['ris_form_number'] ?? null,
                'ris_purpose_description' => $validated['ris_purpose_description'] ?? null,

                // Status: Save as Draft -> Draft, Submit RIS -> Submitted
                'ris_status' => $isDraft ? 'Draft' : 'Submitted',

                // Personnel information
                'ris_requested_by_signature' => $validated['ris_requested_by'] ?? null,
                'ris_requested_by_date' => $validated['ris_requested_by_date'] ?? null,
                'ris_approved_by_signature' => $validated['ris_approved_by'] ?? null,
                'ris_approved_by_date' => $validated['ris_approved_by_date'] ?? null,
                'ris_issued_by_signature' => $validated['ris_issued_by'] ?? null,
                'ris_issued_by_date' => $validated['ris_issued_by_date'] ?? null,
                'ris_received_by_signature' => $validated['ris_received_by'] ?? null,
                'ris_received_by_date' => $validated['ris_received_by_date'] ?? null,

                // Submission tracking (draft has not entered the Admin workflow yet)
                'ris_submitted_by' => $isDraft ? null : Auth::id(),
                'ris_submitted_at' => $isDraft ? null : now(),

                'ris_created_at' => now(),
                'ris_updated_at' => now(),
            ]);

            // Save RIS items
            foreach ($items as $item) {
                $quantityIssued = $item['quantity_issued'] ?? 0;
                $unitCost = $item['unit_cost'] ?? 0;

                DB::table('requisition_issue_slip_items_table')->insert([
                    'ris_id' => $risId,
                    'ris_item_name_description' => $item['name_description'] ?? null,
                    'ris_quantity_requested' => $item['quantity_requested'] ?? null,
                    'ris_quantity_issued' => $quantityIssued,
                    'ris_unit_cost' => $item['unit_cost'] ?? null,
                    // Use manually entered total when available, else quantity issued x unit cost
                    'ris_total_amount' => $item['total_amount'] ?? ($quantityIssued * $unitCost),
                ]);
            }

            // Store supporting documents
            foreach ($request->file('ris_attachments', []) as $document) {
                $storedPath = $document->store('ris-supporting-documents/' . $risId, 'public');

                DB::table('ris_attachments_table')->insert([
                    'ris_id' => $risId,
                    'ris_attachment_original_name' => $document->getClientOriginalName(),
                    'ris_attachment_path' => $storedPath,
                    'ris_attachment_mime_type' => $document->getClientMimeType(),
                    'ris_attachment_size' => $document->getSize(),
                    'ris_attachment_uploaded_by' => Auth::id(),
                    'ris_attachment_created_at' => now(),
                ]);
            }

            return redirect()
                ->route('purchaser.ris.index')
                ->with('success', $isDraft ? 'RIS saved as draft.' : 'RIS submitted to Admin successfully.');
        });
    }

    // RIS MODULE: UPDATE DRAFT OR MINOR REVISION RIS
    public function updateRis(Request $request, $risId)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        // Only Draft (not yet in approval) and Minor Revision (returned by Admin) are editable
        if (!in_array($ris->ris_status, ['Draft', 'Minor Revision'], true)) {
            return back()->with('error', 'This RIS can no longer be edited.');
        }

        // Determine update action: save = save only, submit = submit draft, resubmit = resubmit revision
        $saveAction = $request->input('save_action', 'save');
        $isSaveOnly = $saveAction === 'save';

        // Saving allows incomplete draft info; submitting/resubmitting requires RIS info
        $validated = $request->validate([
            'save_action' => ['required', 'in:save,submit,resubmit'],

            'ris_form_number' => [$isSaveOnly ? 'nullable' : 'required', 'string', 'max:100'],
            'ris_purpose_description' => [$isSaveOnly ? 'nullable' : 'required', 'string', 'max:5000'],

            'ris_items' => ['nullable', 'array'],
            'ris_items.*.name_description' => ['nullable', 'string', 'max:2000'],
            'ris_items.*.quantity_requested' => ['nullable', 'integer', 'min:1'],
            'ris_items.*.quantity_issued' => ['nullable', 'integer', 'min:0'],
            'ris_items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'ris_items.*.total_amount' => ['nullable', 'numeric', 'min:0'],

            'ris_requested_by' => ['nullable', 'string', 'max:255'],
            'ris_requested_by_date' => ['nullable', 'date'],

            'ris_approved_by' => ['nullable', 'string', 'max:255'],
            'ris_approved_by_date' => ['nullable', 'date'],

            'ris_issued_by' => ['nullable', 'string', 'max:255'],
            'ris_issued_by_date' => ['nullable', 'date'],

            'ris_received_by' => ['nullable', 'string', 'max:255'],
            'ris_received_by_date' => ['nullable', 'date'],

            'ris_attachments' => ['nullable', 'array'],
            'ris_attachments.*' => ['file', 'mimes:doc,docx,xls,xlsx', 'max:10240'],
        ]);

        // Remove empty item rows
        $items = collect($validated['ris_items'] ?? [])
            ->filter(fn ($item) => filled($item['name_description'] ?? null))
            ->values();

        // Require items when submitting or resubmitting
        if (!$isSaveOnly && $items->isEmpty()) {
            return back()->withInput()->with('error', 'Please add at least one RIS item before submitting.');
        }

        // Validate action against current status
        if ($saveAction === 'submit' && $ris->ris_status !== 'Draft') {
            return back()->with('error', 'Only a Draft RIS can be submitted.');
        }

        if ($saveAction === 'resubmit' && $ris->ris_status !== 'Minor Revision') {
            return back()->with('error', 'Only an RIS under Minor Revision can be resubmitted.');
        }

        return DB::transaction(function () use ($request, $validated, $items, $ris, $risId, $saveAction) {

            // Determine new status
            $newStatus = $ris->ris_status;

            if ($saveAction === 'submit') {
                $newStatus = 'Submitted';
            } elseif ($saveAction === 'resubmit') {
                $newStatus = 'Resubmitted';
            }

            $updateData = [
                'ris_form_number' => $validated['ris_form_number'] ?? null,
                'ris_purpose_description' => $validated['ris_purpose_description'] ?? null,
                'ris_status' => $newStatus,
                'ris_requested_by_signature' => $validated['ris_requested_by'] ?? null,
                'ris_requested_by_date' => $validated['ris_requested_by_date'] ?? null,
                'ris_approved_by_signature' => $validated['ris_approved_by'] ?? null,
                'ris_approved_by_date' => $validated['ris_approved_by_date'] ?? null,
                'ris_issued_by_signature' => $validated['ris_issued_by'] ?? null,
                'ris_issued_by_date' => $validated['ris_issued_by_date'] ?? null,
                'ris_received_by_signature' => $validated['ris_received_by'] ?? null,
                'ris_received_by_date' => $validated['ris_received_by_date'] ?? null,
                'ris_updated_at' => now(),
            ];

            // Track submission when entering the approval workflow
            if (in_array($saveAction, ['submit', 'resubmit'], true)) {
                $updateData['ris_submitted_by'] = Auth::id();
                $updateData['ris_submitted_at'] = now();
            }

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update($updateData);

            // Replace RIS items: remove old rows, save current form rows again
            DB::table('requisition_issue_slip_items_table')
                ->where('ris_id', $risId)
                ->delete();

            foreach ($items as $item) {
                $quantityIssued = $item['quantity_issued'] ?? 0;
                $unitCost = $item['unit_cost'] ?? 0;

                DB::table('requisition_issue_slip_items_table')->insert([
                    'ris_id' => $risId,
                    'ris_item_name_description' => $item['name_description'] ?? null,
                    'ris_quantity_requested' => $item['quantity_requested'] ?? null,
                    'ris_quantity_issued' => $quantityIssued,
                    'ris_unit_cost' => $item['unit_cost'] ?? null,
                    'ris_total_amount' => $item['total_amount'] ?? ($quantityIssued * $unitCost),
                ]);
            }

            // Add new attachments (existing attachments are kept)
            foreach ($request->file('ris_attachments', []) as $document) {
                $storedPath = $document->store('ris-supporting-documents/' . $risId, 'public');

                DB::table('ris_attachments_table')->insert([
                    'ris_id' => $risId,
                    'ris_attachment_original_name' => $document->getClientOriginalName(),
                    'ris_attachment_path' => $storedPath,
                    'ris_attachment_mime_type' => $document->getClientMimeType(),
                    'ris_attachment_size' => $document->getSize(),
                    'ris_attachment_uploaded_by' => Auth::id(),
                    'ris_attachment_created_at' => now(),
                ]);
            }

            $message = match ($saveAction) {
                'submit' => 'RIS updated and submitted to Admin.',
                'resubmit' => 'RIS corrections saved and resubmitted to Admin.',
                default => 'RIS changes saved successfully.',
            };

            return redirect()->route('purchaser.ris.index')->with('success', $message);
        });
    }

    // RIS MODULE: SUBMIT RIS TO ADMIN USING EXISTING COLUMNS
    public function submitRis($risId)
    {
        return DB::transaction(function () use ($risId) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            if ($ris->ris_status !== 'Pending') {
                return back()->with('error', 'Only pending RIS records can be submitted.');
            }

            if ($ris->ris_requested_by_date !== null) {
                return back()->with('error', 'This RIS has already been submitted to Admin.');
            }

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_requested_by_signature' => Auth::user()->user_full_name ?? 'Purchaser',
                    'ris_requested_by_date' => now()->toDateString(),
                    'ris_submitted_by' => Auth::id(),
                    'ris_submitted_at' => now(),
                    'ris_updated_at' => now(),
                ]);

            return back()->with('success', 'RIS submitted to Admin for approval.');
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
