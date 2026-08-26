<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Support\PurchaserDocumentAccess;
use App\Support\RisWorkflow;
use App\Support\WorkflowNotifier;
use App\Services\DocumentWorkflowService;
use App\Services\RisFormExporter;

/**
 * Requisition & Issue Slip (RIS) workflow for the purchaser role.
 */
class RisController extends Controller
{
    private ?bool $risItemsHaveUomColumn = null;

    private ?bool $risItemsHaveSupplierColumn = null;

    private $validUomIds = null;

    private $validSupplierIds = null;

    public function index(Request $request)
    {
        $risQuery = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->select(
                'requisition_issue_slip_table.ris_id',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_procurement_request_id',
                'requisition_issue_slip_table.ris_status',
                'requisition_issue_slip_table.ris_request_type',
                'requisition_issue_slip_table.ris_manual_title',
                'requisition_issue_slip_table.ris_purpose_description',
                'requisition_issue_slip_table.ris_supplier_id',
                'requisition_issue_slip_table.ris_requested_by_signature',
                'requisition_issue_slip_table.ris_requested_by_date',
                'requisition_issue_slip_table.ris_approved_by_signature',
                'requisition_issue_slip_table.ris_issued_by_signature',
                'requisition_issue_slip_table.ris_received_by_signature',
                'requisition_issue_slip_table.ris_approved_by_date',
                'requisition_issue_slip_table.ris_issued_by_date',
                'requisition_issue_slip_table.ris_received_by_date',
                'requisition_issue_slip_table.ris_submitted_at',
                'requisition_issue_slip_table.ris_submitted_by',
                'requisition_issue_slip_table.ris_created_by',
                'requisition_issue_slip_table.ris_created_at',
                'requisition_issue_slip_table.ris_updated_at',
                DB::raw('CASE WHEN requisition_issue_slip_table.ris_issued_by_signature IS NOT NULL AND TRIM(requisition_issue_slip_table.ris_issued_by_signature) != "" THEN 1 ELSE 0 END as has_issued_by_signature'),
                DB::raw('CASE WHEN requisition_issue_slip_table.ris_approved_by_signature IS NOT NULL AND TRIM(requisition_issue_slip_table.ris_approved_by_signature) != "" THEN 1 ELSE 0 END as has_approved_by_signature'),
                DB::raw('CASE WHEN requisition_issue_slip_table.ris_received_by_signature IS NOT NULL AND TRIM(requisition_issue_slip_table.ris_received_by_signature) != "" THEN 1 ELSE 0 END as has_received_by_signature'),
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

        PurchaserDocumentAccess::scopeOwned($risQuery, 'ris', 'requisition_issue_slip_table');

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

        // Status filter (Approved / Rejected / In Review match risStatusSummary groups)
        if ($request->filled('status')) {
            $statusGroups = $this->risStatusGroups();
            $groupKey = match ($request->status) {
                'In Review' => 'submitted',
                'Approved' => 'approved',
                'Rejected' => 'rejected',
                default => null,
            };

            if ($groupKey !== null) {
                $risQuery->whereIn(
                    'requisition_issue_slip_table.ris_status',
                    $statusGroups[$groupKey]
                );
            } else {
                $risQuery->where(
                    'requisition_issue_slip_table.ris_status',
                    $request->status
                );
            }
        }

        // Date filters
        if ($request->filled('date_from')) {
            $risQuery->whereDate('requisition_issue_slip_table.ris_created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $risQuery->whereDate('requisition_issue_slip_table.ris_created_at', '<=', $request->date_to);
        }

        // Paginated RIS list
        $spotlightQuery = clone $risQuery;
        $risRecords = $risQuery
            ->orderByDesc('requisition_issue_slip_table.ris_created_at')
            ->paginate(10)
            ->withQueryString();

        $viewRisId = (int) ($request->query('view_ris') ?: $request->query('ris_id') ?: 0);
        if (
            $viewRisId
            && !$risRecords->getCollection()->contains(fn ($row) => (int) $row->ris_id === $viewRisId)
        ) {
            $spotlight = $spotlightQuery
                ->where('requisition_issue_slip_table.ris_id', $viewRisId)
                ->first();
            if ($spotlight) {
                $risRecords->setCollection($risRecords->getCollection()->prepend($spotlight));
            }
        }

        // Supporting documents for list downloads
        $risIds = $risRecords->getCollection()->pluck('ris_id');

        $attachmentsByRis = DB::table('ris_attachments_table')
            ->whereIn('ris_id', $risIds)
            ->orderBy('ris_attachment_original_name')
            ->get()
            ->groupBy('ris_id');

        $itemsByRis = $this->risItemsWithLookups($risIds)
            ->groupBy('ris_id');

        $risHasAtp = DB::table('authority_to_purchase_table')
            ->whereIn('authority_purchase_ris_id', $risIds)
            ->pluck('authority_purchase_ris_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $releasedRisIds = DB::table('approval_logs_table')
            ->where('approval_log_reference_type', 'RIS')
            ->whereIn('approval_log_reference_id', $risIds)
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
            ->whereIn('revisions.ris_id', $risIds)
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
            $ris->can_create_atp = RisWorkflow::isEligibleForAtp($ris);
        }

        // Dashboard counts
        $risSummary = $this->risStatusSummary();
        $availableReplacementRequests = collect();
        $replacementSourceError = null;
        if (!$request->ajax()) {
            $availableReplacementRequests = $this->availableReplacementRequests();
            $requestedReplacementId = (int) $request->query('replacement_request', 0);
            if ($requestedReplacementId > 0) {
                $alreadyListed = $availableReplacementRequests->contains(
                    'procurement_request_id',
                    $requestedReplacementId
                );
                if (!$alreadyListed) {
                    $requested = $this->replacementSourceQuery()
                        ->where('procurement_requests_table.procurement_request_id', $requestedReplacementId)
                        ->where('procurement_requests_table.procurement_request_status', 'Approved')
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('requisition_issue_slip_table')
                                ->whereColumn(
                                    'requisition_issue_slip_table.ris_procurement_request_id',
                                    'procurement_requests_table.procurement_request_id'
                                );
                        })
                        ->first();

                    if ($requested) {
                        $availableReplacementRequests = $availableReplacementRequests->prepend($requested);
                    } else {
                        $replacementSourceError = $this->replacementRequestUnavailableMessage($requestedReplacementId);
                    }
                }
            }
        }

        $isAjax = $request->ajax();
        $activeSuppliers = $isAjax ? collect() : $this->activeSuppliersForRis();
        $uoms = ($isAjax || !$this->uomTableExists())
            ? collect()
            : DB::table('uom_table')->orderBy('uom_name')->get();

        $supplierIdsOnPage = $risRecords->getCollection()->pluck('ris_supplier_id')->filter()->unique()->values();
        $supplierNames = $this->supplierOptionsForRis(false, $supplierIdsOnPage)->keyBy('supplier_id');
        foreach ($risRecords as $ris) {
            $ris->supplier_display_name = optional($supplierNames->get($ris->ris_supplier_id ?? null))->display_name;
        }

        return view('purchaser.ris.index', compact(
            'risRecords',
            'risSummary',
            'attachmentsByRis',
            'itemsByRis',
            'risHasAtp',
            'availableReplacementRequests',
            'replacementSourceError',
            'activeSuppliers',
            'uoms'
        ));
    }

// =====================================================
// RIS MODULE: CREATE RIS AS DRAFT OR SUBMITTED
// =====================================================
    public function store(Request $request)
    {
        // =====================================================
        // 1. DETERMINE ACTION
        // draft = save without sending to Admin
        // submit = validate everything and send to Admin
        // =====================================================
        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';

        $this->prefillRisFromReplacement($request);


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

            'ris_form_number' => $this->risFormNumberRules(!$isDraft),

            'ris_supplier_id' => $this->activeSupplierRule(),

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

            'ris_items.*.supplier_id' => $this->activeSupplierRule(),

            'ris_items.*.uom_id' => array_values(array_filter([
                'nullable',
                'integer',
                Schema::hasTable('uom_table') ? 'exists:uom_table,uom_id' : null,
            ])),

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
                'max:1',
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

            'ris_form_number.digits' =>
                'RIS number must be exactly 8 digits.',

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
                'You may upload only 1 supporting document at a time.',

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
                    || filled($item['unit_cost'] ?? null)
                    || filled($item['uom_id'] ?? null)
                    || filled($item['supplier_id'] ?? null);

            })
            ->values();

        $splitOverflow = $this->risItemSplitOverflowMessage($items);
        if ($splitOverflow) {
            return back()->withInput()->with('error', $splitOverflow);
        }


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

            $submitError = $this->validateRisItemsForSubmit($items);
            if ($submitError) {
                return back()->withInput()->with('error', $submitError);
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

            $source = $procurementRequestId
                ? $this->replacementSourceForRis($procurementRequestId)
                : null;

            $firstItem = $items->first();
            $manualTitle = $source
                ? RisWorkflow::equipmentLabel($source)
                : trim((string) (($firstItem['name_description'] ?? '') ?: ($validated['ris_purpose_description'] ?? '')));
            if ($manualTitle !== '') {
                $manualTitle = mb_substr($manualTitle, 0, 255);
            } else {
                $manualTitle = null;
            }

            $risPayload = [
                'ris_procurement_request_id' => $procurementRequestId,
                'ris_form_number' => $validated['ris_form_number'] ?? null,
                'ris_supplier_id' => null,
                'ris_purpose_description' => $validated['ris_purpose_description'] ?? null,
                'ris_status' => $isDraft ? 'Draft' : 'Submitted',
                'ris_requested_by_signature' => $validated['ris_requested_by'] ?? null,
                'ris_requested_by_date' => $requestedByDate,
                'ris_approved_by_signature' => null,
                'ris_approved_by_date' => null,
                'ris_issued_by_signature' => null,
                'ris_issued_by_date' => null,
                'ris_received_by_signature' => null,
                'ris_received_by_date' => null,
                'ris_submitted_by' => $isDraft ? null : Auth::id(),
                'ris_submitted_at' => $isDraft ? null : now(),
                'ris_created_at' => now(),
                'ris_updated_at' => now(),
            ];

            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_type')) {
                $risPayload['ris_request_type'] = RisWorkflow::requestType(
                    $procurementRequestId ? (int) $procurementRequestId : null
                );
            }

            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_manual_title')) {
                $risPayload['ris_manual_title'] = $manualTitle;
            }

            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_created_by')) {
                $risPayload['ris_created_by'] = Auth::id();
            }

            $risId = DB::table('requisition_issue_slip_table')->insertGetId($risPayload);


            // =====================================================
            // 8. SAVE RIS ITEMS
            // =====================================================
            $itemRows = [];
            foreach ($items as $item) {
                $itemRows[] = $this->risItemPayload($risId, $item);
            }
            if ($itemRows !== []) {
                DB::table('requisition_issue_slip_items_table')->insert($itemRows);
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


            if (!$isDraft) {
                DocumentWorkflowService::notifySubmitted(
                    WorkflowNotifier::ROLE_ADMIN,
                    'New RIS submitted',
                    (($validated['ris_form_number'] ?? null) ?: ('RIS #' . $risId)) . ' was submitted for Admin review.',
                    'ris_submitted',
                    'RIS',
                    (int) $risId,
                    '/admin/procurement-review'
                );
            }

            $success = $isDraft
                ? 'RIS saved as draft.'
                : 'RIS submitted to Admin successfully.';
            if ($procurementRequestId) {
                $success = $isDraft
                    ? 'RIS draft created from replacement request #' . $procurementRequestId . '.'
                    : 'RIS from replacement request #' . $procurementRequestId . ' was submitted to Admin.';
            }

            return redirect()
                ->route('purchaser.ris.index')
                ->with('success', $success);
        });
    }

// =====================================================
    // RIS PRINT / PREVIEW
    // Used by Purchaser, Admin, and President
    // =====================================================

    public function print($risId)
    {
        // =====================================================
        // GET RIS RECORD
        // =====================================================

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404, 'RIS not found.');
        PurchaserDocumentAccess::assertOwns($ris, 'ris');

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

        $risItems = $this->risItemsWithLookups([$risId])->values();

        return view('purchaser.ris.print', [
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => $presidentName,
        ]);
    }

    public function exportBlankExcel(RisFormExporter $exporter)
    {
        return $exporter->downloadExcel();
    }

    public function exportBlankWord(RisFormExporter $exporter)
    {
        return $exporter->downloadWord();
    }

    public function exportExcel($risId, RisFormExporter $exporter)
    {
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        abort_if(!$ris, 404);
        PurchaserDocumentAccess::assertOwns($ris, 'ris');
        $items = $this->risItemsWithLookups([$risId])->values();

        return $exporter->downloadExcel($ris, $items);
    }

    public function exportWord($risId, RisFormExporter $exporter)
    {
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        abort_if(!$ris, 404);
        PurchaserDocumentAccess::assertOwns($ris, 'ris');
        $items = $this->risItemsWithLookups([$risId])->values();

        return $exporter->downloadWord($ris, $items);
    }
// =====================================================
// RIS MODULE: UPDATE DRAFT OR MINOR REVISION RIS
// =====================================================
public function update(Request $request, $risId)
{
    $ris = DB::table('requisition_issue_slip_table')
        ->where('ris_id', $risId)
        ->first();

    abort_if(!$ris, 404);
    PurchaserDocumentAccess::assertOwns($ris, 'ris');


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

        'ris_form_number' => $this->risFormNumberRules(!$isSaveOnly, $risId),

        'ris_supplier_id' => $this->activeSupplierRule(),

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

        'ris_items.*.supplier_id' => $this->activeSupplierRule(),

        'ris_items.*.uom_id' => array_values(array_filter([
            'nullable',
            'integer',
            Schema::hasTable('uom_table') ? 'exists:uom_table,uom_id' : null,
        ])),

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
            'max:1',
        ],

        'ris_attachments.*' => [
            'file',
            'mimes:doc,docx,xls,xlsx',
            'max:10240',
        ],

    ], [

        'ris_form_number.required' =>
            'RIS number is required before submitting.',

        'ris_form_number.digits' =>
            'RIS number must be exactly 8 digits.',

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
                || filled($item['unit_cost'] ?? null)
                || filled($item['uom_id'] ?? null)
                || filled($item['supplier_id'] ?? null);

        })
        ->values();

    $splitOverflow = $this->risItemSplitOverflowMessage($items);
    if ($splitOverflow) {
        return back()->withInput()->with('error', $splitOverflow);
    }


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

        $submitError = $this->validateRisItemsForSubmit($items);
        if ($submitError) {
            return back()->withInput()->with('error', $submitError);
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

            'ris_supplier_id' => null,

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


        $itemRows = [];
        foreach ($items as $item) {
            $itemRows[] = $this->risItemPayload($risId, $item);
        }
        if ($itemRows !== []) {
            DB::table('requisition_issue_slip_items_table')->insert($itemRows);
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

        if (in_array($saveAction, ['submit', 'resubmit'], true)) {
            DocumentWorkflowService::notifySubmitted(
                WorkflowNotifier::ROLE_ADMIN,
                $saveAction === 'resubmit' ? 'RIS resubmitted' : 'New RIS submitted',
                ($ris->ris_form_number ?: ('RIS #' . $risId)) . ' was submitted for Admin review.',
                'ris_submitted',
                'RIS',
                (int) $risId,
                '/admin/procurement-review'
            );
        }

        return redirect()
            ->route('purchaser.ris.index')
            ->with('success', $message);
    });
}

// =====================================================
// RIS MODULE: SUBMIT DRAFT RIS TO ADMIN
// =====================================================
public function submit($risId)
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
        PurchaserDocumentAccess::assertOwns($ris, 'ris');


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

        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_ADMIN,
            'New RIS submitted',
            ($ris->ris_form_number ?: ('RIS #' . $risId)) . ' was submitted for Admin review.',
            'ris_submitted',
            'RIS',
            (int) $risId,
            '/admin/procurement-review'
        );

        return redirect()
            ->route('purchaser.ris.index')
            ->with(
                'success',
                'RIS submitted to Admin successfully.'
            );
    });
}

    // RIS MODULE: DOWNLOAD SUPPORTING DOCUMENT
    public function downloadAttachment($attachmentId)
    {
        $attachment = DB::table('ris_attachments_table')
            ->where('ris_attachment_id', $attachmentId)
            ->first();

        abort_if(!$attachment, 404);

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $attachment->ris_id)
            ->first();
        abort_if(!$ris, 404);
        PurchaserDocumentAccess::assertOwns($ris, 'ris');

        abort_if(!Storage::disk('public')->exists($attachment->ris_attachment_path), 404);

        return Storage::disk('public')->download(
            $attachment->ris_attachment_path,
            $attachment->ris_attachment_original_name
        );
    }

    private function activeSuppliersForRis()
    {
        return $this->supplierOptionsForRis(true);
    }

    private function supplierOptionsForRis(bool $activeOnly = false, $limitIds = null)
    {
        $query = DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_store_type',
                'suppliers_table.supplier_is_active',
                'suppliers_table.supplier_is_blacklisted',
                'suppliers_table.supplier_blacklist_reason',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            );

        if ($activeOnly) {
            $query->where('suppliers_table.supplier_is_active', 1);
        }

        if ($limitIds !== null) {
            $ids = collect($limitIds)->filter()->values();
            if ($ids->isEmpty()) {
                return collect();
            }
            $query->whereIn('suppliers_table.supplier_id', $ids);
        }

        return $query
            ->orderBy('suppliers_table.supplier_id')
            ->get()
            ->map(function ($supplier) {
                $baseName = $supplier->supplier_store_type === 'Online Store'
                    ? ($supplier->shop_name ?: 'Online supplier #' . $supplier->supplier_id)
                    : ($supplier->company_name ?: 'Physical supplier #' . $supplier->supplier_id);

                $supplier->is_blacklisted = (int) ($supplier->supplier_is_blacklisted ?? 0) === 1;
                $supplier->display_name = $supplier->is_blacklisted
                    ? $baseName . ' (Blacklisted)'
                    : $baseName;

                return $supplier;
            });
    }

    private function risItemPayload($risId, array $item): array
    {
        $quantityIssued = (int) ($item['quantity_issued'] ?? 0);
        $unitCost = (float) ($item['unit_cost'] ?? 0);

        $payload = [
            'ris_id' => $risId,
            'ris_item_name_description' => $item['name_description'] ?? null,
            'ris_quantity_requested' => $item['quantity_requested'] ?? null,
            'ris_quantity_issued' => $quantityIssued,
            'ris_unit_cost' => $item['unit_cost'] ?? null,
            'ris_total_amount' => round($quantityIssued * $unitCost, 2),
        ];

        if ($this->risItemsHaveUomColumn()) {
            $payload['ris_item_uom_id'] = $this->resolveRisUomId($item['uom_id'] ?? null);
        }

        if ($this->risItemsHaveSupplierColumn()) {
            $payload['ris_item_supplier_id'] = $this->resolveRisSupplierId($item['supplier_id'] ?? null);
        }

        return $payload;
    }

    private function resolveRisUomId($uomId): ?int
    {
        if (!filled($uomId) || !$this->uomTableExists()) {
            return null;
        }

        if ($this->validUomIds === null) {
            $this->validUomIds = DB::table('uom_table')->pluck('uom_id')->flip();
        }

        return isset($this->validUomIds[(int) $uomId]) ? (int) $uomId : null;
    }

    private function resolveRisSupplierId($supplierId): ?int
    {
        if (!filled($supplierId)) {
            return null;
        }

        if ($this->validSupplierIds === null) {
            $this->validSupplierIds = DB::table('suppliers_table')
                ->where('supplier_is_active', 1)
                ->pluck('supplier_id')
                ->flip();
        }

        return isset($this->validSupplierIds[(int) $supplierId]) ? (int) $supplierId : null;
    }

    private function activeSupplierRule(): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('suppliers_table', 'supplier_id')->where(fn ($q) => $q->where('supplier_is_active', 1)),
        ];
    }

    private function risFormNumberRules(bool $required, $ignoreRisId = null): array
    {
        $unique = Rule::unique('requisition_issue_slip_table', 'ris_form_number');
        if ($ignoreRisId) {
            $unique->ignore($ignoreRisId, 'ris_id');
        }

        return [
            $required ? 'required' : 'nullable',
            'digits:8',
            $unique,
        ];
    }

    /**
     * Shared status groups for RIS summary cards and list filters.
     *
     * @return array{submitted: list<string>, approved: list<string>, rejected: list<string>}
     */
    private function risStatusGroups(): array
    {
        return [
            'submitted' => ['Submitted', 'Under Review', 'Resubmitted'],
            'approved' => ['Approved', 'Approved by the President', 'Directly Approved'],
            'rejected' => ['Rejected', 'Rejected by President', 'Rejected by the President'],
        ];
    }

    private function risStatusSummary(): array
    {
        $counts = DB::table('requisition_issue_slip_table')
            ->select('ris_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('ris_status')
            ->pluck('aggregate', 'ris_status');

        $groups = $this->risStatusGroups();

        return [
            'total' => (int) $counts->sum(),
            'draft' => (int) ($counts['Draft'] ?? 0),
            'submitted' => (int) $counts->only($groups['submitted'])->sum(),
            'approved' => (int) $counts->only($groups['approved'])->sum(),
            'rejected' => (int) $counts->only($groups['rejected'])->sum(),
        ];
    }

    private function uomTableExists(): bool
    {
        return Schema::hasTable('uom_table');
    }

    private function risItemsHaveUomColumn(): bool
    {
        return $this->risItemsHaveUomColumn ??= Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id');
    }

    private function risItemsHaveSupplierColumn(): bool
    {
        return $this->risItemsHaveSupplierColumn ??= Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id');
    }

    private function risItemsWithLookups($risIds)
    {
        $query = DB::table('requisition_issue_slip_items_table')
            ->whereIn('requisition_issue_slip_items_table.ris_id', $risIds)
            ->orderBy('ris_item_id');

        $select = ['requisition_issue_slip_items_table.*'];

        if (
            Schema::hasTable('uom_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')
        ) {
            $query->leftJoin(
                'uom_table',
                'uom_table.uom_id',
                '=',
                'requisition_issue_slip_items_table.ris_item_uom_id'
            );
            $select[] = 'uom_table.uom_name';
        }

        if (
            Schema::hasTable('suppliers_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')
        ) {
            $query
                ->leftJoin(
                    'suppliers_table',
                    'suppliers_table.supplier_id',
                    '=',
                    'requisition_issue_slip_items_table.ris_item_supplier_id'
                )
                ->leftJoin(
                    'physical_suppliers_table',
                    'physical_suppliers_table.supplier_id',
                    '=',
                    'suppliers_table.supplier_id'
                )
                ->leftJoin(
                    'online_suppliers_table',
                    'online_suppliers_table.supplier_id',
                    '=',
                    'suppliers_table.supplier_id'
                );

            $select[] = DB::raw(
                "CASE
                    WHEN suppliers_table.supplier_store_type = 'Online Store'
                        THEN COALESCE(online_suppliers_table.shop_name, CONCAT('Online supplier #', suppliers_table.supplier_id))
                    ELSE COALESCE(physical_suppliers_table.company_name, CONCAT('Physical supplier #', suppliers_table.supplier_id))
                END as supplier_display_name"
            );
        }

        return $query->select($select)->get();
    }

    private function risNormalizedItemName($name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    private function risItemSplitOverflowMessage($items): ?string
    {
        $groups = [];

        foreach ($items as $item) {
            $key = $this->risNormalizedItemName($item['name_description'] ?? '');
            if ($key === '') {
                continue;
            }
            $groups[$key][] = $item;
        }

        foreach ($groups as $rows) {
            $asked = (int) ($rows[0]['quantity_requested'] ?? 0);
            if ($asked < 1) {
                continue;
            }

            $allocated = 0;
            foreach ($rows as $row) {
                $allocated += (int) ($row['quantity_issued'] ?? 0);
            }

            if ($allocated > $asked) {
                $label = trim((string) ($rows[0]['name_description'] ?? 'Item'));

                return "\"{$label}\" is over the requested amount ({$allocated} issued of {$asked} requested).";
            }
        }

        return null;
    }

    private function validateRisItemsForSubmit($items): ?string
    {
        $seenNames = [];

        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;
            $name = trim((string) ($item['name_description'] ?? ''));

            if ($name === '') {
                return "Item {$rowNumber} needs an item description.";
            }

            $key = $this->risNormalizedItemName($name);
            $isFirstOfGroup = !isset($seenNames[$key]);
            $seenNames[$key] = true;

            if ($isFirstOfGroup && blank($item['quantity_requested'] ?? null)) {
                return "Item {$rowNumber} needs a Quantity Requested.";
            }

            if ($isFirstOfGroup && (int) $item['quantity_requested'] < 1) {
                return "Item {$rowNumber} Quantity Requested must be at least 1.";
            }

            if ($isFirstOfGroup && blank($item['uom_id'] ?? null)) {
                return "Item {$rowNumber} needs a unit of measure.";
            }

            if ($isFirstOfGroup && (!isset($item['unit_cost']) || $item['unit_cost'] === '' || (float) $item['unit_cost'] <= 0)) {
                return "Item {$rowNumber} needs a unit cost greater than 0.";
            }
        }

        return null;
    }

    private function availableReplacementRequests()
    {
        return $this->replacementSourceQuery()
            ->where('procurement_requests_table.procurement_request_status', 'Approved')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('requisition_issue_slip_table')
                    ->whereColumn(
                        'requisition_issue_slip_table.ris_procurement_request_id',
                        'procurement_requests_table.procurement_request_id'
                    );
            })
            ->orderByDesc('procurement_requests_table.procurement_request_created_at')
            ->limit(50)
            ->get();
    }

    private function replacementSourceQuery()
    {
        return DB::table('procurement_requests_table')
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
            );
    }

    private function replacementSourceForRis($procurementRequestId): ?object
    {
        if (!$procurementRequestId) {
            return null;
        }

        return $this->replacementSourceQuery()
            ->where('procurement_requests_table.procurement_request_id', $procurementRequestId)
            ->first();
    }

    private function prefillRisFromReplacement(Request $request): void
    {
        $source = $this->replacementSourceForRis($request->input('ris_procurement_request_id'));
        if (!$source) {
            return;
        }

        if (!$request->filled('ris_purpose_description')) {
            $request->merge([
                'ris_purpose_description' => RisWorkflow::replacementPurpose($source),
            ]);
        }

        $items = $request->input('ris_items', []);
        $hasNamedItem = collect($items)->contains(
            fn ($item) => is_array($item) && filled($item['name_description'] ?? null)
        );
        if ($hasNamedItem) {
            return;
        }

        $first = is_array($items[0] ?? null) ? $items[0] : [];
        $first['name_description'] = RisWorkflow::equipmentLabel($source);
        if (!filled($first['quantity_requested'] ?? null)) {
            $first['quantity_requested'] = 1;
        }
        $items[0] = $first;
        $request->merge(['ris_items' => $items]);
    }

    private function replacementRequestUnavailableMessage(int $requestId): string
    {
        $row = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $requestId)
            ->first();

        if (!$row) {
            return 'That replacement request was not found.';
        }

        if (($row->procurement_request_status ?? '') !== 'Approved') {
            return 'Only approved replacement requests can start an RIS.';
        }

        $hasRis = DB::table('requisition_issue_slip_table')
            ->where('ris_procurement_request_id', $requestId)
            ->exists();
        if ($hasRis) {
            return 'This replacement request already has an RIS.';
        }

        return 'This replacement request is missing its maintenance report, so it cannot prefill an RIS.';
    }

}
