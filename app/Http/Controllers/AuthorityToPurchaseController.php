<?php

namespace App\Http\Controllers;

use App\Support\DocumentUrgency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\AtpFormNumber;
use App\Support\AtpRisSuppliers;
use App\Support\DocumentLineage;
use App\Support\DocumentRevisionNotes;
use App\Support\ProcurementDraftLabel;
use App\Support\ProcurementPaymentPath;
use App\Support\PurchaseOrderBasket;
use App\Support\PurchaseOrderFunding;
use App\Support\PurchaserAttentionSummary;
use App\Support\PurchaserDocumentAccess;
use App\Support\RfcAtpLinks;
use App\Support\ReviewerAssignment;
use App\Support\RisWorkflow;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;
use App\Services\AtpFormExporter;
use App\Services\DocumentWorkflowService;
use App\Support\AccountingHandoff;
use App\Support\CashInstallmentPlanner;
use App\Support\ProcurementPortal;
use App\Support\RfcFormNumber;
use App\Support\SupplyHandoff;

class AuthorityToPurchaseController extends Controller
{
    /**
     * Active path: Purchaser may mutate ATP when PURCHASER_OWNS_ATP.
     * Supply Officer URL context remains allowed so that path can be re-enabled later.
     */
    private function assertAtpMutator(): void
    {
        if (ProcurementPortal::userCanMutateAtp()) {
            return;
        }

        abort(403, ProcurementPortal::PURCHASER_OWNS_ATP
            ? 'Only the Purchaser (or School Supply / Material Officer) may create or submit Authority to Purchase records.'
            : 'Only the School Supply / Material Officer may create or submit Authority to Purchase records.');
    }

    /** @deprecated Use assertAtpMutator(); kept for Supply Officer path compatibility. */
    private function assertSupplyOfficerMutatesAtp(): void
    {
        $this->assertAtpMutator();
    }

    public function index(Request $request)
    {
        $archiveView = $request->query('view') === 'archive';

        $query = DB::table('authority_to_purchase_table')
            ->leftJoin(
                'requisition_issue_slip_table',
                'authority_to_purchase_table.authority_purchase_ris_id',
                '=',
                'requisition_issue_slip_table.ris_id'
            )
            ->leftJoin(
                'procurement_requests_table',
                'requisition_issue_slip_table.ris_procurement_request_id',
                '=',
                'procurement_requests_table.procurement_request_id'
            )
            ->leftJoin(
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
                'suppliers_table',
                'authority_to_purchase_table.authority_purchase_supplier_id',
                '=',
                'suppliers_table.supplier_id'
            )
            ->leftJoin(
                'physical_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'physical_suppliers_table.supplier_id'
            )
            ->leftJoin(
                'online_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'online_suppliers_table.supplier_id'
            )
            ->select(
                'authority_to_purchase_table.*',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_purpose_description',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            );

        PurchaserDocumentAccess::scopeOwned($query, 'atp', 'authority_to_purchase_table');

        DocumentWorkflowService::applyArchiveFilter(
            $query,
            'authority_to_purchase_table.authority_purchase_is_archived',
            $archiveView
        );

        if ($request->filled('search')) {
            $query->where(function ($subQuery) use ($request) {
                $search = '%' . $request->search . '%';

                $subQuery
                    ->where('authority_to_purchase_table.authority_purchase_form_number', 'LIKE', $search)
                    ->orWhere('requisition_issue_slip_table.ris_form_number', 'LIKE', $search)
                    ->orWhere('physical_suppliers_table.company_name', 'LIKE', $search)
                    ->orWhere('online_suppliers_table.shop_name', 'LIKE', $search)
                    ->orWhere('equipment_table.equipment_name', 'LIKE', $search)
                    ->orWhere('reports_table.report_unlisted_equipment_name', 'LIKE', $search);
                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no')) {
                    $subQuery->orWhere('authority_to_purchase_table.authority_purchase_request_no', 'LIKE', $search);
                }
            });
        }

        $attentionFocus = $archiveView
            ? null
            : PurchaserAttentionSummary::focusFor($request, PurchaserAttentionSummary::FOCUS_ATP_READY_FOR_RFC);
        if ($attentionFocus) {
            PurchaserAttentionSummary::scopeAtpReadyForRfc($query);
        } elseif ($request->filled('status')) {
            $this->applyStatusFilter($query, $request->status);
        }

        if ($request->filled('request_type') && Schema::hasColumn('requisition_issue_slip_table', 'ris_request_type')) {
            $requestType = (string) $request->request_type;
            if ($requestType === 'Replacement') {
                $requestType = RisWorkflow::REQUEST_TYPE_REPLACEMENT;
            } elseif (in_array($requestType, ['manual', 'Manual Procurement'], true)) {
                $requestType = RisWorkflow::REQUEST_TYPE_NEW;
            }
            $query->where(
                'requisition_issue_slip_table.ris_request_type',
                $requestType
            );
        }

        DocumentUrgency::select($query, 'ATP');
        $spotlightQuery = clone $query;
        $atps = DocumentWorkflowService::orderSoftDraftFirst(
            $query,
            'authority_to_purchase_table.authority_purchase_status',
            'authority_to_purchase_table.authority_purchase_submitted_at'
        )
            ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
            ->paginate(10)
            ->withQueryString();

        $selectedRisId = $request->query('selected_ris');
        $selectedSupplierId = $request->query('selected_supplier');
        $viewAtpId = $request->query('view_atp');
        $editAtpId = $request->query('edit_atp');
        $spotlightId = (int) ($editAtpId ?: $viewAtpId);
        if (
            $spotlightId
            && !$atps->getCollection()->contains(fn ($row) => (int) $row->authority_purchase_id === $spotlightId)
        ) {
            $spotlight = $spotlightQuery
                ->where('authority_to_purchase_table.authority_purchase_id', $spotlightId)
                ->first();
            if ($spotlight) {
                $atps->setCollection($atps->getCollection()->prepend($spotlight));
            }
        }

        $atpSummary = $this->atpStatusSummary();

        // Always load create-form datasets on the active list so "+ Create ATP"
        // can open instantly without a ?create=1 round-trip reload.
        $loadFormDatasets = ! $archiveView
            || filled($selectedRisId)
            || filled($editAtpId)
            || $request->boolean('create')
            || ($request->session()->getOldInput('authority_purchase_ris_id') !== null);

        $risOptions = [];
        $risPrefill = [];
        $suppliers = collect();
        $uomNames = collect();

        if ($loadFormDatasets) {
            $risChoices = $this->buildRisOptions($this->eligibleRisQuery()->limit(200)->get());
            $risOptions = $risChoices['options'];
            $risPrefill = $risChoices['prefill'];
            $suppliers = AtpRisSuppliers::withDisplayNames($this->activeSuppliersQuery()->get());
            $uomNames = Schema::hasTable('uom_table')
                ? DB::table('uom_table')
                    ->orderByRaw('LOWER(uom_name) ASC')
                    ->orderBy('uom_name')
                    ->pluck('uom_name')
                    ->map(fn ($name) => (string) $name)
                    ->values()
                : collect();
        }

        // Create ATP from Incoming RIS, but that RIS already has a live ATP → open it instead.
        if (
            filled($selectedRisId)
            && empty($risOptions)
            && ! $request->filled('view_atp')
            && ! $request->filled('edit_atp')
        ) {
            $existingAtpId = $this->liveAtpIdForRis((int) $selectedRisId);
            if ($existingAtpId) {
                $isDraft = DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $existingAtpId)
                    ->where('authority_purchase_status', 'Pending')
                    ->whereNull('authority_purchase_submitted_at')
                    ->exists();

                return redirect()
                    ->route(ProcurementPortal::routeName('atp.index'), array_filter([
                        $isDraft ? 'edit_atp' : 'view_atp' => $existingAtpId,
                    ]))
                    ->with(
                        'success',
                        'This Canvass + RIS already has an Authority to Purchase. Opening that record instead of creating a duplicate.'
                    );
            }
        }

        // Keep split-RIS siblings together on this page so the list can group by RIS.
        $pageCollection = $atps->getCollection();
        $risIdsOnPage = $pageCollection
            ->pluck('authority_purchase_ris_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        if ($risIdsOnPage->isNotEmpty()) {
            $onPageIds = $pageCollection
                ->pluck('authority_purchase_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $siblings = (clone $spotlightQuery)
                ->whereIn('authority_to_purchase_table.authority_purchase_ris_id', $risIdsOnPage->all())
                ->whereNotIn('authority_to_purchase_table.authority_purchase_id', $onPageIds)
                ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
                ->get();
            if ($siblings->isNotEmpty()) {
                $pageCollection = $pageCollection->concat($siblings)->unique('authority_purchase_id')->values();
                $atps->setCollection($pageCollection);
            }
        }

        $atpIds = $atps->getCollection()->pluck('authority_purchase_id');

        $atpItems = $atpIds->isEmpty()
            ? collect()
            : DB::table('authority_to_purchase_items_table')
                ->whereIn('authority_purchase_id', $atpIds)
                ->orderBy('atp_item_id')
                ->get()
                ->groupBy('authority_purchase_id');

        $supplierBreakdown = AtpRisSuppliers::breakdown(
            $atps->getCollection()
                ->filter(fn ($row) => (int) ($row->authority_purchase_ris_supplier_split ?? 0) === 1)
                ->pluck('authority_purchase_ris_id')
        );

        $atpHasRfc = RfcAtpLinks::fundedAtpIds($atpIds->all());
        $poFundedAtpIds = PurchaseOrderFunding::poFundedAtpIds($atpIds->all());
        $poMap = PurchaseOrderBasket::mapForAtps($atpIds->all());

        $revisionAtpIds = $atps->getCollection()
            ->filter(fn ($row) => filled($row->authority_purchase_rejection_reason ?? null))
            ->pluck('authority_purchase_id')
            ->all();
        DocumentRevisionNotes::warmLatest('ATP', $revisionAtpIds);

        $lineageAtpIds = array_values(array_unique(array_filter([
            (int) $viewAtpId,
            (int) $editAtpId,
        ])));
        $atpLineages = [];
        foreach ($lineageAtpIds as $lineageId) {
            $atpLineages[$lineageId] = DocumentLineage::forAtp($lineageId);
        }

        foreach ($atps as $atp) {
            $atpId = (int) $atp->authority_purchase_id;
            $atp->ris_supplier_position = null;
            $atp->ris_supplier_total = 0;
            if ((int) ($atp->authority_purchase_ris_supplier_split ?? 0) === 1) {
                $groups = $supplierBreakdown[(int) $atp->authority_purchase_ris_id] ?? [];
                $group = collect($groups)->firstWhere('supplier_id', (int) $atp->authority_purchase_supplier_id);
                $atp->ris_supplier_position = $group['position'] ?? null;
                $atp->ris_supplier_total = count($groups);
            }
            $atp->has_rfc = in_array($atpId, $atpHasRfc, true)
                || CashInstallmentPlanner::planExistsForAtp($atpId);
            $atp->funds_via_po = in_array($atpId, $poFundedAtpIds, true);
            $atp->atp_total_amount = CashInstallmentPlanner::atpTotal($atpId);
            $poMeta = $poMap[$atpId] ?? null;
            $atp->purchase_order_id = $poMeta['purchase_order_id'] ?? null;
            $atp->purchase_order_label = $poMeta['purchase_order_label'] ?? null;
            $atp->purchase_order_status = $poMeta['purchase_order_status'] ?? null;
            $atp->purchase_order_atp_count = (int) ($poMeta['purchase_order_atp_count'] ?? 0);
            $atp->submits_via_po = false; // PO workflow retired
            $atp->released_to_purchaser = SupplyHandoff::atpIsReleasedToPurchaser($atp);
            $atp->can_release_to_purchaser = ($atp->authority_purchase_status ?? null) === 'Approved'
                && ! $atp->released_to_purchaser
                && ProcurementPortal::isSupplyOfficerContext();
            $latestRfc = RfcAtpLinks::latestRfcForAtp($atpId);
            $atp->linked_rfc_sent_to_accounting = $latestRfc
                ? AccountingHandoff::rfcIsSentToAccounting($latestRfc)
                : false;
            $atp->can_joint_submit = ProcurementPortal::PURCHASER_OWNS_ATP
                && ! ProcurementPortal::isSupplyOfficerContext()
                && empty($atp->authority_purchase_submitted_at)
                && in_array($atp->authority_purchase_status, ['Draft', 'Pending'], true)
                && filled($atp->authority_purchase_payment_path ?? null)
                && ! empty($atp->has_rfc);
        }

        $savedSignatures = $loadFormDatasets || filled($viewAtpId) || filled($editAtpId)
            ? UserSignatureLibrary::forUser((int) auth()->id())
            : collect();
        $suggestedAtpFormNumber = $loadFormDatasets ? AtpFormNumber::next() : null;
        $installmentTerms = CashInstallmentPlanner::activeTerms();
        $installmentDayIntervals = CashInstallmentPlanner::activeDayIntervals();

        $atpGroups = $this->buildAtpListGroups($atps->getCollection(), $atpItems);

        return view(
            'purchaser.authority-to-purchase.index',
            compact(
                'attentionFocus',
                'atps',
                'atpGroups',
                'archiveView',
                'atpSummary',
                'risOptions',
                'suppliers',
                'uomNames',
                'atpItems',
                'selectedRisId',
                'selectedSupplierId',
                'viewAtpId',
                'editAtpId',
                'risPrefill',
                'savedSignatures',
                'suggestedAtpFormNumber',
                'atpLineages',
                'loadFormDatasets',
                'installmentTerms',
                'installmentDayIntervals'
            )
        );
    }

    /**
     * Collapse same-RIS ATPs into one expandable list group.
     * ATPs without an RIS stay as single-row groups.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, object>>  $atpItems
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildAtpListGroups($rows, $atpItems = null)
    {
        $atpItems = $atpItems ?? collect();

        $grouped = $rows->groupBy(function ($atp) {
            $risId = (int) ($atp->authority_purchase_ris_id ?? 0);

            return $risId > 0 ? 'ris-'.$risId : 'atp-'.(int) $atp->authority_purchase_id;
        });

        return $grouped->map(function ($items, $key) use ($atpItems) {
            $items = $items->values();
            $first = $items->first();
            $risId = (int) ($first->authority_purchase_ris_id ?? 0);
            $labels = $items->map(function ($atp) {
                return \App\Support\RisWorkflow::atpStatusLabel($atp);
            })->unique()->values();
            $urgent = $items->contains(fn ($atp) => DocumentUrgency::isUrgent('ATP', $atp));
            $dates = $items
                ->pluck('authority_purchase_date')
                ->filter()
                ->map(fn ($d) => (string) $d)
                ->sort()
                ->values();

            return [
                'key' => (string) $key,
                'ris_id' => $risId > 0 ? $risId : null,
                'count' => $items->count(),
                'atps' => $items,
                'first' => $first,
                'request_no' => $first->authority_purchase_request_no ?? null,
                'ris_label' => $risId > 0
                    ? RisWorkflow::formNumber($first, $risId)
                    : '—',
                'equipment' => $this->atpGroupItemSummary($items, $atpItems, $first),
                'equipment_items' => $this->atpGroupItemNames($items, $atpItems, $first),
                'status_labels' => $labels,
                'status_mixed' => $labels->count() > 1,
                'urgent' => $urgent,
                'date' => $dates->last() ?: null,
                'supplier_total' => (int) ($first->ris_supplier_total ?? 0),
            ];
        })->values();
    }

    /**
     * Item labels for a RIS/ATP group with quantities summed by description
     * (e.g. "2 Desktop Computer", "4 Door Knob").
     *
     * @param  \Illuminate\Support\Collection<int, object>  $atps
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, object>>  $atpItems
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function atpGroupItemNames($atps, $atpItems, object $first)
    {
        $fromEquipment = trim((string) (
            $first->equipment_name
            ?? $first->report_unlisted_equipment_name
            ?? ''
        ));
        if ($fromEquipment !== '') {
            return collect([$fromEquipment]);
        }

        $totals = [];
        foreach ($atps as $atp) {
            $lines = $atpItems->get((int) $atp->authority_purchase_id, collect());
            foreach ($lines as $line) {
                $desc = trim((string) ($line->atp_description ?? ''));
                if ($desc === '') {
                    continue;
                }
                $qty = (float) ($line->atp_quantity ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $key = mb_strtolower($desc);
                if (! isset($totals[$key])) {
                    $totals[$key] = ['name' => $desc, 'qty' => 0.0];
                }
                $totals[$key]['qty'] += $qty;
            }
        }

        $labels = collect($totals)->map(function (array $row) {
            return $this->formatAtpItemQtyLabel($row['qty'], $row['name']);
        })->values();

        if ($labels->isNotEmpty()) {
            return $labels;
        }

        $purpose = trim((string) ($first->ris_purpose_description ?? ''));

        return $purpose !== ''
            ? collect([\Illuminate\Support\Str::limit($purpose, 48)])
            : collect();
    }

    private function formatAtpItemQtyLabel(float $qty, string $name): string
    {
        $qtyLabel = fmod($qty, 1.0) === 0.0
            ? (string) (int) $qty
            : rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');

        return $qtyLabel.' '.$name;
    }

    /**
     * Subtitle under RIS: first item, with "+N more" when there are others.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $atps
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, object>>  $atpItems
     */
    private function atpGroupItemSummary($atps, $atpItems, object $first): string
    {
        $unique = $this->atpGroupItemNames($atps, $atpItems, $first);
        if ($unique->isEmpty()) {
            return 'No line items';
        }

        if ($unique->count() === 1) {
            return $unique->first();
        }

        return $unique->first().' +'.($unique->count() - 1).' more';
    }

    public function create(Request $request)
    {
        return ProcurementPortal::redirect('atp.index', array_filter([
            'selected_ris' => $request->query('selected_ris'),
            'selected_supplier' => $request->query('selected_supplier'),
        ]));
    }

    public function store(Request $request)
    {
        $this->assertSupplyOfficerMutatesAtp();

        $saveAction = $request->input('save_action', 'draft');
        // Purchaser path: create always soft-saves first; joint submit happens after payment path + RFC.
        $purchaserOwnsPath = ProcurementPortal::PURCHASER_OWNS_ATP && ! ProcurementPortal::isSupplyOfficerContext();
        if ($purchaserOwnsPath && $saveAction === 'submit') {
            $saveAction = 'draft';
            $request->merge(['save_action' => 'draft']);
        }
        $isDraft = $saveAction === 'draft';

        $validated = $request->validate([
            'save_action' => ['required', 'in:draft,submit'],
            // Real ATP records always link an approved RIS — blank paper is Print Empty only.
            'authority_purchase_ris_id' => [
                'required',
                'integer',
                'exists:requisition_issue_slip_table,ris_id',
            ],
            'authority_purchase_form_number' => $this->atpFormNumberRules(false),
            'authority_purchase_supplier_id' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                Rule::exists('suppliers_table', 'supplier_id')->where(fn ($q) => $q->where('supplier_is_active', 1)),
            ],
            'authority_purchase_date' => [
                $isDraft ? 'nullable' : 'required',
                'date',
                'after_or_equal:today',
            ],
            'authority_purchase_received_by_name' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:255',
            ],
            'authority_purchase_received_by_signature' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:2000000',
            ],
            'authority_purchase_reference_po_no' => ['nullable', 'string', 'max:100'],
            'items' => [$isDraft ? 'nullable' : 'required', 'array', 'max:50'],
            'items.*.description' => ['nullable', 'string', 'max:'.\App\Support\InputLimits::DESCRIPTION],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:'.\App\Support\InputLimits::QTY],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:'.\App\Support\InputLimits::COST],
        ], [
            'authority_purchase_form_number.required' => 'ATP number is required before submitting.',
            'authority_purchase_form_number.regex' => 'ATP number must follow the format ATP-YYYYMMDD-0001.',
            'authority_purchase_form_number.unique' => 'This ATP number is already in use.',
            'authority_purchase_ris_id.required' => 'Select an approved RIS before creating an ATP.',
            'authority_purchase_date.after_or_equal' => 'The date cannot be before today.',
        ]);

        $receivedSig = RisWorkflow::normalizeDrawnSignature($validated['authority_purchase_received_by_signature'] ?? null);
        if (!$isDraft && !$receivedSig) {
            throw ValidationException::withMessages([
                'authority_purchase_received_by_signature' => 'Draw or upload your signature before submitting.',
            ]);
        }

        $risId = (int) $validated['authority_purchase_ris_id'];

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        if (!$ris || !$this->risIsEligibleForAtp($ris)) {
            $error = 'Only approved RIS records sent by the Purchaser may be used to create ATP.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $error], 422);
            }

            return back()->withInput()->with('error', $error);
        }

        $check = AtpRisSuppliers::checkSupplierForRis(
            $risId,
            isset($validated['authority_purchase_supplier_id']) ? (int) $validated['authority_purchase_supplier_id'] : null
        );
        if ($check['error']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $check['error']], 422);
            }

            return back()->withInput()->with('error', $check['error']);
        }
        $supplierSplit = $check['split'];

        $items = $this->filterItemRows($validated['items'] ?? []);
        $this->assertRisLinesUnchanged(
            $risId,
            isset($validated['authority_purchase_supplier_id']) ? (int) $validated['authority_purchase_supplier_id'] : null,
            $items
        );

        if (!$isDraft) {
            $this->assertSubmitReadyItems($items);
        }

        return DB::transaction(function () use ($request, $validated, $items, $isDraft, $receivedSig, $risId, $supplierSplit) {
            $now = now();
            $reviewerId = $isDraft
                ? null
                : ReviewerAssignment::resolve(request(), \App\Support\ProcurementReviewPortal::ROLE);

            // System-assigned on submit only — ignore client-posted No. (multi-purchaser safe).
            $formNumber = $isDraft ? null : AtpFormNumber::next();

            $payload = [
                'authority_purchase_ris_id' => $risId,
                'authority_purchase_form_number' => $formNumber,
                'authority_purchase_supplier_id' => $validated['authority_purchase_supplier_id'] ?? null,
                'authority_purchase_date' => $validated['authority_purchase_date'] ?? null,
                'authority_purchase_received_by_name' => $validated['authority_purchase_received_by_name'] ?? null,
                'authority_purchase_reference_po_no' => $validated['authority_purchase_reference_po_no'] ?? null,
                'authority_purchase_status' => 'Pending',
                'authority_purchase_created_by' => auth()->id(),
                'authority_purchase_is_archived' => 0,
                'authority_purchase_created_at' => $now,
                'authority_purchase_updated_at' => $now,
                'authority_purchase_submitted_by' => $isDraft ? null : auth()->id(),
                'authority_purchase_submitted_at' => $isDraft ? null : $now,
            ];
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')) {
                $payload['authority_purchase_received_by_signature'] = $receivedSig;
            }
            if (AtpRisSuppliers::hasSplitColumn()) {
                $payload['authority_purchase_ris_supplier_split'] = $supplierSplit ? 1 : 0;
            }
            if (! $isDraft && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_assigned_reviewer_id')) {
                $payload['authority_purchase_assigned_reviewer_id'] = $reviewerId;
            }
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no') && $risId) {
                $requestNo = \App\Support\ProcurementRequestNumber::resolveFromRis((int) $risId);
                if ($requestNo) {
                    $payload['authority_purchase_request_no'] = $requestNo;
                }
            }

            $authorityPurchaseId = DB::table('authority_to_purchase_table')->insertGetId($payload);

            $this->replaceAtpItems($authorityPurchaseId, $items);

            // Purchase Orders removed from active workflow — ATP submits directly to School Administrator.
            if (! $isDraft) {
                $this->notifyAccountingAtp($authorityPurchaseId, $formNumber, $reviewerId);
            }

            $message = $isDraft
                ? ProcurementDraftLabel::flashCreated('Authority to Purchase')
                : 'Authority to Purchase submitted successfully.';

            if ($request->expectsJson() || $request->ajax()) {
                $redirect = null;
                if (
                    $isDraft
                    && ProcurementPortal::PURCHASER_OWNS_ATP
                    && ! ProcurementPortal::isSupplyOfficerContext()
                ) {
                    $redirect = ProcurementPortal::route('atp.index', [
                        'view_atp' => $authorityPurchaseId,
                        'choose_path' => 1,
                    ]);
                    $message .= ' Choose a payment path, prepare the funding request, then submit both for School Administrator review.';
                }

                return response()->json([
                    'ok' => true,
                    'id' => (int) $authorityPurchaseId,
                    'message' => $message,
                    'redirect' => $redirect,
                ]);
            }

            if (
                $isDraft
                && ProcurementPortal::PURCHASER_OWNS_ATP
                && ! ProcurementPortal::isSupplyOfficerContext()
            ) {
                return ProcurementPortal::redirect('atp.index', [
                    'view_atp' => $authorityPurchaseId,
                    'choose_path' => 1,
                ])->with(
                    'success',
                    $message.' Choose a payment path, prepare the funding request, then submit both for School Administrator review.'
                );
            }

            return $this->redirectAfterSplitAtp($risId, $supplierSplit, $message);
        });
    }

    /**
     * A split RIS needs one ATP per supplier. After one is saved, reopen Create
     * on the next supplier that still has no ATP.
     */
    private function redirectAfterSplitAtp(int $risId, bool $supplierSplit, string $message)
    {
        if ($supplierSplit && $risId > 0) {
            $groups = AtpRisSuppliers::groups([$risId]);
            $info = $groups[$risId] ?? null;
            if (is_array($info) && ! empty($info['split'])) {
                $coverage = AtpRisSuppliers::coverage([$risId]);
                $open = AtpRisSuppliers::openGroups($info, $coverage[$risId] ?? null);
                $nextSupplierId = (int) array_key_first($open);
                if ($nextSupplierId > 0) {
                    return ProcurementPortal::redirect('atp.index', [
                        'selected_ris' => $risId,
                        'selected_supplier' => $nextSupplierId,
                    ])->with('success', $message.' The next supplier on this RIS still needs its own ATP.');
                }
            }
        }

        return ProcurementPortal::redirect('atp.index')->with('success', $message);
    }

    public function show($id)
    {
        return ProcurementPortal::redirect('atp.index', ['view_atp' => $id]);
    }

    public function edit($id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return ProcurementPortal::redirect('atp.index')->with('error', 'Authority to Purchase record not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if (!$this->isSoftDraft($atp)) {
            return ProcurementPortal::redirect('atp.index', ['view_atp' => $id])
                ->with('error', ProcurementDraftLabel::onlyEditableError('ATP records'));
        }

        return ProcurementPortal::redirect('atp.index', ['edit_atp' => $id]);
    }

    public function choosePaymentPath(Request $request, $id)
    {
        $validated = $request->validate([
            'authority_purchase_payment_path' => [
                'required',
                Rule::in(ProcurementPaymentPath::choices()),
            ],
        ]);

        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->first();
        if (!$atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if ($error = $this->paymentPathGateError($atp)) {
            return back()->with('error', $error);
        }

        if (
            filled($atp->authority_purchase_payment_path ?? null)
            && RfcAtpLinks::fundedAtpIds([(int) $id]) !== []
        ) {
            return back()->with('error', 'Payment path cannot be changed after a funding request has been created.');
        }

        $path = $validated['authority_purchase_payment_path'];

        DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->update([
                'authority_purchase_payment_path' => $path,
                'authority_purchase_payment_path_chosen_at' => now(),
                'authority_purchase_updated_at' => now(),
            ]);

        $label = ProcurementPaymentPath::label($path);

        return redirect(ProcurementPortal::route('rfc.index', [
            'selected_atp' => $id,
            'funding_type' => $path,
            'fund' => $path,
        ]))->with('success', "Payment path set to {$label}. You may now create the funding request.");
    }

    public function updateReceivedByName(Request $request, $id)
    {
        $validated = $request->validate([
            'authority_purchase_received_by_name' => ['nullable', 'string', 'max:255'],
            'authority_purchase_received_by_signature' => ['nullable', 'string'],
            'authority_purchase_reference_po_no' => ['nullable', 'string', 'max:'.\App\Support\InputLimits::SHORT],
        ]);

        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->first();
        if (! $atp) {
            return response()->json(['ok' => false, 'message' => 'Authority to Purchase not found.'], 404);
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        $update = ['authority_purchase_updated_at' => now()];

        if ($request->has('authority_purchase_received_by_name')) {
            $update['authority_purchase_received_by_name'] = trim((string) ($validated['authority_purchase_received_by_name'] ?? ''));
        }

        if ($request->has('authority_purchase_received_by_signature')) {
            $sig = trim((string) ($validated['authority_purchase_received_by_signature'] ?? ''));
            $update['authority_purchase_received_by_signature'] = ($sig !== '' && \App\Support\RisWorkflow::isDrawnSignature($sig)) ? $sig : null;
        }

        if ($request->has('authority_purchase_reference_po_no')) {
            $po = trim((string) ($validated['authority_purchase_reference_po_no'] ?? ''));
            $update['authority_purchase_reference_po_no'] = $po !== '' ? $po : null;
        }

        DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->update($update);

        return response()->json([
            'ok' => true,
            'name' => $update['authority_purchase_received_by_name'] ?? $atp->authority_purchase_received_by_name,
            'signature' => $update['authority_purchase_received_by_signature'] ?? $atp->authority_purchase_received_by_signature,
            'reference_po_no' => $update['authority_purchase_reference_po_no'] ?? $atp->authority_purchase_reference_po_no,
        ]);
    }

    /**
     * Cash Installment: installment count + payment interval + downpayment → draft RFCs.
     */
    public function planCashInstallment(Request $request, $id)
    {
        abort_unless(CashInstallmentPlanner::supported(), 404);

        $validated = $request->validate([
            'installment_count' => ['required', 'integer', 'min:1', 'max:36'],
            'day_interval_id' => ['nullable', 'integer', 'exists:installment_day_intervals_table,interval_id'],
            'downpayment' => ['nullable', 'numeric', 'min:0', 'max:'.\App\Support\InputLimits::COST],
            'dates' => ['nullable', 'array'],
            'dates.*' => ['nullable', 'date'],
        ]);

        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->first();
        if (! $atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if ($error = $this->paymentPathGateError($atp)) {
            return back()->with('error', $error);
        }

        if (
            filled($atp->authority_purchase_payment_path ?? null)
            && $atp->authority_purchase_payment_path !== ProcurementPaymentPath::CASH_INSTALLMENT
        ) {
            return back()->with('error', 'This ATP already uses a different payment path.');
        }

        try {
            $result = CashInstallmentPlanner::create(
                (int) $id,
                (int) $validated['installment_count'],
                (float) ($validated['downpayment'] ?? 0),
                array_values($validated['dates'] ?? []),
                isset($validated['day_interval_id']) ? (int) $validated['day_interval_id'] : null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $count = count($result['rfc_ids']);

        return redirect(ProcurementPortal::route('rfc.index', [
            'fund' => ProcurementPaymentPath::CASH_INSTALLMENT,
            'search' => $atp->authority_purchase_form_number ?? null,
        ]))->with(
            'success',
            "Cash installment planned: {$count} funding request".($count === 1 ? '' : 's').' created in preparation. Open each RFC to sign and submit — each gets its own DV / check number.'
        );
    }

    public function update(Request $request, $id)
    {
        $this->assertSupplyOfficerMutatesAtp();

        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase record not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if (!$this->isSoftDraft($atp)) {
            return back()->with('error', ProcurementDraftLabel::onlyEditableError('ATP records'));
        }

        $saveAction = $request->input('save_action', 'save');
        $purchaserOwnsPath = ProcurementPortal::PURCHASER_OWNS_ATP && ! ProcurementPortal::isSupplyOfficerContext();
        // Purchaser cannot submit ATP alone — joint submit via submit() after path + RFC.
        if ($purchaserOwnsPath && $saveAction === 'submit') {
            $saveAction = 'save';
            $request->merge(['save_action' => 'save']);
        }
        $isDraft = in_array($saveAction, ['save', 'draft'], true);

        $validated = $request->validate([
            'save_action' => ['required', 'in:save,draft,submit'],
            'authority_purchase_form_number' => $this->atpFormNumberRules(false, $id),
            'authority_purchase_supplier_id' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                Rule::exists('suppliers_table', 'supplier_id')->where(fn ($q) => $q->where('supplier_is_active', 1)),
            ],
            'authority_purchase_date' => [
                $isDraft ? 'nullable' : 'required',
                'date',
                'after_or_equal:today',
            ],
            'authority_purchase_received_by_name' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:255',
            ],
            'authority_purchase_received_by_signature' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:2000000',
            ],
            'authority_purchase_reference_po_no' => ['nullable', 'string', 'max:100'],
            'items' => [$isDraft ? 'nullable' : 'required', 'array', 'max:50'],
            'items.*.description' => ['nullable', 'string', 'max:'.\App\Support\InputLimits::DESCRIPTION],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:'.\App\Support\InputLimits::QTY],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:'.\App\Support\InputLimits::COST],
        ], [
            'authority_purchase_form_number.required' => 'ATP number is required before submitting.',
            'authority_purchase_form_number.regex' => 'ATP number must follow the format ATP-YYYYMMDD-0001.',
            'authority_purchase_form_number.unique' => 'This ATP number is already in use.',
            'authority_purchase_date.after_or_equal' => 'The date cannot be before today.',
        ]);

        $receivedSig = RisWorkflow::normalizeDrawnSignature($validated['authority_purchase_received_by_signature'] ?? null);
        if (!$isDraft && !$receivedSig) {
            throw ValidationException::withMessages([
                'authority_purchase_received_by_signature' => 'Draw or upload your signature before submitting.',
            ]);
        }

        if (
            !empty($atp->authority_purchase_ris_id)
            && (int) ($atp->authority_purchase_ris_supplier_split ?? 0) === 1
        ) {
            $check = AtpRisSuppliers::checkSupplierForRis(
                (int) $atp->authority_purchase_ris_id,
                isset($validated['authority_purchase_supplier_id']) ? (int) $validated['authority_purchase_supplier_id'] : null,
                (int) $id
            );
            if ($check['error']) {
                return back()->withInput()->with('error', $check['error']);
            }
        }

        $items = $this->filterItemRows($validated['items'] ?? []);
        if (!empty($atp->authority_purchase_ris_id)) {
            $this->assertRisLinesUnchanged(
                (int) $atp->authority_purchase_ris_id,
                isset($validated['authority_purchase_supplier_id']) ? (int) $validated['authority_purchase_supplier_id'] : null,
                $items
            );
        }

        if (!$isDraft) {
            $this->assertSubmitReadyItems($items);
        }

        return DB::transaction(function () use ($validated, $id, $items, $isDraft, $atp, $receivedSig) {
            // PO workflow retired — always allow direct ATP submit to School Administrator.
            if (! $isDraft) {
                $this->linkedPoIdAfterReleasingSolo((int) $id);
            }

            $now = now();
            $reviewerId = $isDraft
                ? null
                : ReviewerAssignment::resolve(request(), \App\Support\ProcurementReviewPortal::ROLE);

            // System-assigned on submit only — drafts stay null; keep existing if already assigned.
            $formNumber = $isDraft
                ? null
                : AtpFormNumber::allocateOnSubmit($atp->authority_purchase_form_number ?? null);

            $payload = [
                'authority_purchase_form_number' => $formNumber,
                'authority_purchase_supplier_id' => $validated['authority_purchase_supplier_id'] ?? null,
                'authority_purchase_date' => $validated['authority_purchase_date'] ?? null,
                'authority_purchase_received_by_name' => $validated['authority_purchase_received_by_name'] ?? null,
                'authority_purchase_reference_po_no' => $validated['authority_purchase_reference_po_no'] ?? null,
                'authority_purchase_updated_at' => $now,
            ];
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')) {
                $payload['authority_purchase_received_by_signature'] = $receivedSig
                    ?? ($atp->authority_purchase_received_by_signature ?? null);
            }

            if (!$isDraft) {
                $payload['authority_purchase_submitted_by'] = auth()->id();
                $payload['authority_purchase_submitted_at'] = $now;
                $payload['authority_purchase_status'] = 'Pending';
                $payload['authority_purchase_rejection_reason'] = null;
                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_assigned_reviewer_id')) {
                    $payload['authority_purchase_assigned_reviewer_id'] = $reviewerId;
                }
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update($payload);

            $this->replaceAtpItems($id, $items);

            if (!$isDraft) {
                $this->notifyAccountingAtp($id, $formNumber, $reviewerId);
            }

            $message = $isDraft
                ? ProcurementDraftLabel::flashUpdated('Authority to Purchase')
                : 'Authority to Purchase submitted successfully.';

            if ($isDraft) {
                return redirect()
                    ->route(ProcurementPortal::routeName('atp.index'))
                    ->with('success', $message);
            }

            return $this->redirectAfterSplitAtp(
                (int) ($atp->authority_purchase_ris_id ?? 0),
                (int) ($atp->authority_purchase_ris_supplier_split ?? 0) === 1,
                $message
            );
        });
    }

    public function submit($id)
    {
        $this->assertSupplyOfficerMutatesAtp();

        $reviewerId = ReviewerAssignment::resolve(request(), \App\Support\ProcurementReviewPortal::ROLE);

        return DB::transaction(function () use ($id, $reviewerId) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$atp) {
                return back()->with('error', 'Authority to Purchase not found.');
            }

            PurchaserDocumentAccess::assertOwns($atp, 'atp');

            if (!$this->isSoftDraft($atp)) {
                return back()->with('error', 'Only ATP records in preparation can be submitted.');
            }

            if (blank($atp->authority_purchase_ris_id)) {
                return back()->with('error', 'Link an approved RIS before submitting this ATP.');
            }

            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', (int) $atp->authority_purchase_ris_id)
                ->first();
            if (!$ris || !$this->risIsEligibleForAtp($ris)) {
                return back()->with('error', 'Only approved RIS records sent by the Purchaser may be used to submit ATP.');
            }

            $linkedPoId = null; // PO workflow retired — always submit ATP directly
            if ($linkedPoId) {
                return redirect()
                    ->route(ProcurementPortal::routeName('purchase-orders.index'), ['edit_po' => $linkedPoId])
                    ->with('error', 'This ATP is on a Purchase Order. Submit the Purchase Order to Accounting instead.');
            }

            if ($error = $this->purchaserJointSubmitGateError($atp, (int) $id)) {
                return back()->with('error', $error);
            }

            if (blank($atp->authority_purchase_supplier_id)) {
                return back()->with('error', 'Supplier is required before submitting.');
            }

            if (blank($atp->authority_purchase_date)) {
                return back()->with('error', 'Date is required before submitting.');
            }

            $formNumber = AtpFormNumber::isValid($atp->authority_purchase_form_number ?? null)
                ? trim((string) $atp->authority_purchase_form_number)
                : AtpFormNumber::next();

            if (blank($atp->authority_purchase_received_by_name)) {
                return back()->with('error', 'Received By is required before submitting.');
            }

            $postedSignature = RisWorkflow::normalizeDrawnSignature(request()->input('signature_data'));
            if (
                $postedSignature
                && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')
            ) {
                DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $id)
                    ->update([
                        'authority_purchase_received_by_signature' => $postedSignature,
                        'authority_purchase_updated_at' => now(),
                    ]);
                $atp->authority_purchase_received_by_signature = $postedSignature;
            }

            if (
                Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')
                && !RisWorkflow::isDrawnSignature((string) ($atp->authority_purchase_received_by_signature ?? ''))
            ) {
                return back()->with('error', 'Draw or upload your signature before submitting.');
            }

            $items = DB::table('authority_to_purchase_items_table')
                ->where('authority_purchase_id', $id)
                ->orderBy('atp_item_id')
                ->get();

            if ($items->isEmpty()) {
                return back()->with('error', 'Please add at least one ATP item before submitting.');
            }

            foreach ($items as $index => $item) {
                $rowNumber = $index + 1;

                if (blank($item->atp_description)) {
                    return back()->with('error', "Item {$rowNumber} is missing its description.");
                }

                if ((int) $item->atp_quantity < 1) {
                    return back()->with('error', "Item {$rowNumber} requires a quantity of at least 1.");
                }

                if (blank($item->atp_unit)) {
                    return back()->with('error', "Item {$rowNumber} is missing its unit.");
                }

                if ($item->atp_unit_price === null || $item->atp_unit_price === '') {
                    return back()->with('error', "Item {$rowNumber} is missing its unit price.");
                }

                DB::table('authority_to_purchase_items_table')
                    ->where('atp_item_id', $item->atp_item_id)
                    ->update([
                        'atp_amount' => ((int) $item->atp_quantity) * ((float) $item->atp_unit_price),
                    ]);
            }

            $draftRfcs = RfcAtpLinks::draftRfcsForAtp((int) $id);
            if (
                ProcurementPortal::PURCHASER_OWNS_ATP
                && ! ProcurementPortal::isSupplyOfficerContext()
                && $draftRfcs->isEmpty()
            ) {
                // Cash installment may already have RFCs stamped as Draft with null submitted_at —
                // also accept any linked non-rejected RFC that is still editable-ish.
                $latest = RfcAtpLinks::latestRfcForAtp((int) $id);
                if (! $latest || ! in_array($latest->request_check_status ?? '', ['Draft', 'Pending', 'Minor Revision'], true)) {
                    return back()->with('error', 'Create and complete the funding request (RFC) before submitting ATP + RFC together.');
                }
                $draftRfcs = collect([$latest]);
            }

            foreach ($draftRfcs as $rfc) {
                if (blank($rfc->request_check_payee ?? null)) {
                    return back()->with('error', 'Complete payee on the linked funding request before submitting.');
                }
                if ((float) ($rfc->request_check_amount_figures ?? 0) <= 0) {
                    return back()->with('error', 'Complete the amount on the linked funding request before submitting.');
                }
                if (blank($rfc->request_check_particulars_purpose ?? null)) {
                    return back()->with('error', 'Complete the purpose on the linked funding request before submitting.');
                }
                if (
                    Schema::hasColumn('request_check_table', 'request_check_requested_by_signature')
                    && ! RisWorkflow::isDrawnSignature((string) ($rfc->request_check_requested_by_signature ?? ''))
                ) {
                    return back()->with('error', 'Sign the linked funding request before submitting ATP + RFC together.');
                }
            }

            $now = now();
            $update = [
                'authority_purchase_status' => 'Pending',
                'authority_purchase_form_number' => $formNumber,
                'authority_purchase_submitted_by' => auth()->id(),
                'authority_purchase_submitted_at' => $now,
                'authority_purchase_rejection_reason' => null,
                'authority_purchase_updated_at' => $now,
            ];
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_assigned_reviewer_id')) {
                $update['authority_purchase_assigned_reviewer_id'] = $reviewerId;
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update($update);

            $jointRfcCount = 0;
            foreach ($draftRfcs as $rfc) {
                $rfcId = (int) $rfc->request_check_id;
                $wasRevision = ($rfc->request_check_status ?? '') === 'Minor Revision';
                $rfcPayload = [
                    'request_check_status' => $wasRevision ? 'Resubmitted' : 'Submitted',
                    'request_check_submitted_by' => auth()->id(),
                    'request_check_submitted_at' => $now,
                    'request_check_updated_at' => $now,
                ];
                if (Schema::hasColumn('request_check_table', 'request_check_review_stage')) {
                    $rfcPayload['request_check_review_stage'] = 'accounting';
                }
                if (Schema::hasColumn('request_check_table', 'request_check_form_number')) {
                    $rfcPayload['request_check_form_number'] = RfcFormNumber::allocateOnSubmit($rfc->request_check_form_number ?? null);
                }
                if (Schema::hasColumn('request_check_table', 'request_check_assigned_reviewer_id')) {
                    $rfcPayload['request_check_assigned_reviewer_id'] = $reviewerId;
                }
                // Persist only statuses the enum allows.
                if (! $this->rfcStatusAllowed($rfcPayload['request_check_status'])) {
                    $rfcPayload['request_check_status'] = $this->rfcStatusAllowed('Pending')
                        ? 'Pending'
                        : ($rfc->request_check_status ?? 'Submitted');
                }

                DB::table('request_check_table')->where('request_check_id', $rfcId)->update($rfcPayload);
                $jointRfcCount++;
            }

            if ($jointRfcCount > 0) {
                $this->notifyAccountingAtpPair($id, $formNumber, $jointRfcCount, $reviewerId);
            } else {
                $this->notifyAccountingAtp($id, $formNumber, $reviewerId);
            }

            $success = $jointRfcCount > 0
                ? 'Authority to Purchase and funding request'.($jointRfcCount === 1 ? '' : 's').' submitted to the School Administrator.'
                : 'Authority to Purchase submitted successfully.';

            return $this->redirectAfterSplitAtp(
                (int) ($atp->authority_purchase_ris_id ?? 0),
                (int) ($atp->authority_purchase_ris_supplier_split ?? 0) === 1,
                $success
            );
        });
    }

    public function releaseToPurchaser(Request $request, $id)
    {
        $this->assertSupplyOfficerMutatesAtp();

        $validated = $request->validate([
            'purchaser_user_id' => ['nullable', 'integer'],
        ]);

        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (! $atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        $purchaserId = isset($validated['purchaser_user_id']) && (int) $validated['purchaser_user_id'] > 0
            ? (int) $validated['purchaser_user_id']
            : SupplyHandoff::defaultPurchaserForAtp($atp);

        if (! $purchaserId) {
            return back()->with('error', 'Could not determine which Purchaser should receive this ATP.');
        }

        try {
            SupplyHandoff::releaseAtpToPurchaser($atp, $purchaserId, (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $form = $atp->authority_purchase_form_number ?: ('ATP #'.$id);

        return back()->with(
            'success',
            $form.' (with approved Canvass + RIS) was released to the Purchaser for Request for Check.'
        );
    }

    public function archive($id)
    {
        $this->assertSupplyOfficerMutatesAtp();

        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if (!in_array($atp->authority_purchase_status, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Only approved or rejected ATP records can be archived.');
        }

        if ($atp->authority_purchase_status === 'Approved') {
            if (
                SupplyHandoff::atpReleaseColumnsReady()
                && ! SupplyHandoff::atpIsReleasedToPurchaser($atp)
            ) {
                return back()->with('error', 'Release this ATP to the Purchaser before archiving.');
            }
            $hasRfc = RfcAtpLinks::fundedAtpIds([(int) $id]) !== [];
            if (! $hasRfc) {
                return back()->with('error', 'Create the funding request (RFC) before archiving this ATP.');
            }
        }

        DocumentWorkflowService::setArchived(
            'authority_to_purchase_table',
            'authority_purchase_id',
            $id,
            'authority_purchase_is_archived',
            'authority_purchase_updated_at',
            true
        );

        return back()->with('success', 'ATP archived.');
    }

    public function restore($id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        DocumentWorkflowService::setArchived(
            'authority_to_purchase_table',
            'authority_purchase_id',
            $id,
            'authority_purchase_is_archived',
            'authority_purchase_updated_at',
            false
        );

        return back()->with('success', 'ATP restored from archive.');
    }

    private function notifyAccountingAtp($id, ?string $formNumber, ?int $reviewerId = null): void
    {
        DocumentWorkflowService::notifySubmitted(
            \App\Support\ProcurementReviewPortal::ROLE,
            'ATP submitted for review',
            ($formNumber ?: ('ATP #' . $id)) . ' is waiting for School Administrator review.',
            'atp_submitted',
            'ATP',
            (int) $id,
            \App\Support\ProcurementReviewPortal::atp((int) $id),
            $reviewerId
        );
    }

    private function notifyAccountingAtpPair($id, ?string $formNumber, int $rfcCount, ?int $reviewerId = null): void
    {
        $atpLabel = $formNumber ?: ('ATP #'.$id);
        $rfcBit = $rfcCount === 1 ? 'linked funding request' : "{$rfcCount} linked funding requests";

        DocumentWorkflowService::notifySubmitted(
            \App\Support\ProcurementReviewPortal::ROLE,
            'ATP + funding request submitted',
            "{$atpLabel} and {$rfcBit} are waiting for School Administrator review.",
            'atp_rfc_submitted',
            'ATP',
            (int) $id,
            \App\Support\ProcurementReviewPortal::atp((int) $id),
            $reviewerId
        );
    }

    private function rfcStatusAllowed(string $status): bool
    {
        static $values = null;
        if ($values === null) {
            $values = [];
            try {
                $col = DB::select("SHOW COLUMNS FROM request_check_table LIKE 'request_check_status'");
                $type = $col[0]->Type ?? '';
                if (preg_match_all("/'([^']+)'/", $type, $matches)) {
                    $values = $matches[1];
                }
            } catch (\Throwable $e) {
                $values = [];
            }
        }

        return $values === [] || in_array($status, $values, true);
    }

    /**
     * PO id that still blocks a direct submit, or null. An ATP that is alone in
     * a draft PO is released from it first so it can go straight to the School Administrator.
     */
    private function linkedPoIdAfterReleasingSolo(int $atpId): ?int
    {
        $poId = PurchaseOrderBasket::poIdForAtp($atpId);
        if (! $poId) {
            return null;
        }

        PurchaseOrderBasket::releaseIfUndersized($poId);

        return PurchaseOrderBasket::poIdForAtp($atpId);
    }

    private function isSoftDraft(object $atp): bool
    {
        return DocumentWorkflowService::isSoftDraft(
            $atp,
            'authority_purchase_status',
            'authority_purchase_submitted_at',
            'authority_purchase_is_archived'
        );
    }

    /**
     * Purchaser-owned path: payment path + RFC while ATP is still Draft/Pending.
     * Legacy Supply path: only after Approved + released to Purchaser.
     */
    private function paymentPathGateError(object $atp): ?string
    {
        if (ProcurementPortal::PURCHASER_OWNS_ATP && ! ProcurementPortal::isSupplyOfficerContext()) {
            $status = (string) ($atp->authority_purchase_status ?? '');
            if (! in_array($status, ['Draft', 'Pending', 'Approved'], true)) {
                return 'Payment path cannot be chosen for this ATP status.';
            }

            // Once submitted to SA, path is locked until approved (or revision returns it to soft draft).
            if (
                $status === 'Pending'
                && filled($atp->authority_purchase_submitted_at ?? null)
                && ! $this->isSoftDraft($atp)
            ) {
                return 'This ATP is already with the School Administrator. Wait for approval before changing the payment path.';
            }

            return null;
        }

        if ($atp->authority_purchase_status !== 'Approved') {
            return 'Payment path can only be chosen after ATP is approved.';
        }

        if (SupplyHandoff::atpReleaseColumnsReady() && ! SupplyHandoff::atpIsReleasedToPurchaser($atp)) {
            return 'Wait until the School Supply / Material Officer releases this ATP before choosing a payment path.';
        }

        return null;
    }

    /**
     * When Purchaser owns ATP, joint submit requires a linked funding request first.
     */
    private function purchaserJointSubmitGateError(object $atp, int $atpId): ?string
    {
        if (! ProcurementPortal::PURCHASER_OWNS_ATP || ProcurementPortal::isSupplyOfficerContext()) {
            return null;
        }

        if (
            ! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_payment_path')
            || blank($atp->authority_purchase_payment_path ?? null)
        ) {
            return 'Choose a payment path (Direct Payment, Cash Installment, or Cash Advance) before submitting to the School Administrator.';
        }

        $funded = RfcAtpLinks::fundedAtpIds([$atpId]);
        if ($funded === [] && ! CashInstallmentPlanner::planExistsForAtp($atpId)) {
            return 'Create the funding request (RFC) for this ATP before submitting to the School Administrator.';
        }

        return null;
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'Draft') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNull('authority_to_purchase_table.authority_purchase_submitted_at')
                ->where(function ($q) {
                    $q->whereNull('authority_to_purchase_table.authority_purchase_rejection_reason')
                        ->orWhere('authority_to_purchase_table.authority_purchase_rejection_reason', '');
                });
            return;
        }

        if ($status === 'Minor Revision') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNull('authority_to_purchase_table.authority_purchase_submitted_at')
                ->whereNotNull('authority_to_purchase_table.authority_purchase_rejection_reason')
                ->where('authority_to_purchase_table.authority_purchase_rejection_reason', '!=', '');
            return;
        }

        if ($status === 'Submitted') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNotNull('authority_to_purchase_table.authority_purchase_submitted_at');
            return;
        }

        $query->where('authority_to_purchase_table.authority_purchase_status', $status);
    }

    private function eligibleRisQuery()
    {
        $query = DB::table('requisition_issue_slip_table')
            ->leftJoin(
                'procurement_requests_table',
                'requisition_issue_slip_table.ris_procurement_request_id',
                '=',
                'procurement_requests_table.procurement_request_id'
            )
            ->leftJoin(
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
            );

        $this->applyAtpEligibleRisScope($query);

        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('requisition_issue_slip_table.ris_is_archived')
                    ->orWhere('requisition_issue_slip_table.ris_is_archived', 0);
            });
        }

        // A whole-RIS ATP closes the RIS here; per-supplier coverage is checked in buildRisOptions().
        return $query
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('authority_to_purchase_table')
                    ->whereColumn(
                        'authority_to_purchase_table.authority_purchase_ris_id',
                        'requisition_issue_slip_table.ris_id'
                    )
                    ->where(function ($q) {
                        $q->whereNull('authority_purchase_is_archived')
                            ->orWhere('authority_purchase_is_archived', 0);
                    })
                    ->where('authority_purchase_status', '!=', 'Rejected')
                    ->when(AtpRisSuppliers::hasSplitColumn(), function ($q) {
                        $q->where('authority_purchase_ris_supplier_split', 0);
                    });
            })
            ->select(
                'requisition_issue_slip_table.ris_id',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_purpose_description',
                'requisition_issue_slip_table.ris_supplier_id',
                'procurement_requests_table.procurement_request_id',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name'
            )
            ->orderByDesc('requisition_issue_slip_table.ris_created_at');
    }

    private function atpStatusSummary(): array
    {
        $query = DB::table('authority_to_purchase_table')
            ->select(
                'authority_purchase_status',
                'authority_purchase_is_archived',
                DB::raw('CASE WHEN authority_purchase_submitted_at IS NULL THEN 0 ELSE 1 END as is_submitted'),
                DB::raw('COUNT(*) as aggregate')
            );

        PurchaserDocumentAccess::scopeOwned($query, 'atp', 'authority_to_purchase_table');

        $rows = $query
            ->groupBy(
                'authority_purchase_status',
                'authority_purchase_is_archived',
                DB::raw('CASE WHEN authority_purchase_submitted_at IS NULL THEN 0 ELSE 1 END')
            )
            ->get();

        $summary = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0,
            'archived' => 0,
        ];

        foreach ($rows as $row) {
            $count = (int) $row->aggregate;
            if ((int) $row->authority_purchase_is_archived === 1) {
                $summary['archived'] += $count;
                continue;
            }
            $summary['total'] += $count;
            if ($row->authority_purchase_status === 'Pending' && (int) $row->is_submitted === 0) {
                $summary['draft'] += $count;
            } elseif ($row->authority_purchase_status === 'Pending') {
                $summary['submitted'] += $count;
            } elseif ($row->authority_purchase_status === 'Approved') {
                $summary['approved'] += $count;
            } elseif ($row->authority_purchase_status === 'Rejected') {
                $summary['rejected'] += $count;
            }
        }

        return $summary;
    }

    /**
     * One dropdown option per RIS supplier group that still needs an ATP.
     *
     * @return array{options: array<int, array<string, mixed>>, prefill: array<string, array<string, mixed>>}
     */
    private function buildRisOptions($eligibleRis, int $limit = 50): array
    {
        $eligibleRis = collect($eligibleRis);
        $groups = AtpRisSuppliers::groups($eligibleRis->pluck('ris_id'));
        $coverage = AtpRisSuppliers::coverage(array_keys($groups));
        $supplierNames = AtpRisSuppliers::supplierNames(
            collect($groups)->flatMap(fn ($info) => array_keys($info['groups']))
        );

        $options = [];
        $prefill = [];
        $risCount = 0;

        foreach ($eligibleRis as $ris) {
            $risId = (int) $ris->ris_id;
            $info = $groups[$risId] ?? ['split' => false, 'groups' => [0 => collect()]];
            $open = AtpRisSuppliers::openGroups($info, $coverage[$risId] ?? null);
            if ($open === []) {
                continue;
            }
            if (++$risCount > $limit) {
                break;
            }

            $label = RisWorkflow::formNumber($ris);
            if ($ris->equipment_name || $ris->report_unlisted_equipment_name) {
                $label .= ' · '.($ris->equipment_name ?? $ris->report_unlisted_equipment_name);
            } elseif (!empty($ris->ris_purpose_description)) {
                $label .= ' · '.\Illuminate\Support\Str::limit($ris->ris_purpose_description, 40);
            }

            foreach ($open as $supplierId => $items) {
                $key = $risId.':'.$supplierId;
                $count = $items->count();
                $itemNames = $items
                    ->map(fn ($item) => trim((string) ($item->ris_item_name_description ?? '')))
                    ->filter()
                    ->values();
                $itemLabel = $itemNames->take(3)->implode(', ');
                if ($itemNames->count() > 3) {
                    $itemLabel .= ' +'.($itemNames->count() - 3);
                }
                $supplierLabel = $supplierNames[$supplierId] ?? ('Supplier #'.$supplierId);
                $optionLabel = $label;
                $chip = $label;
                if ($info['split']) {
                    $optionLabel = RisWorkflow::formNumber($ris);
                    if ($itemLabel !== '') {
                        $optionLabel .= ' · '.$itemLabel;
                    }
                    $optionLabel .= ' — '.$supplierLabel
                        .' ('.$count.' '.\Illuminate\Support\Str::plural('item', $count).')';
                    $chip = trim(($itemLabel !== '' ? $itemLabel.' — ' : '').$supplierLabel);
                }

                $options[] = [
                    'key' => $key,
                    'ris_id' => $risId,
                    'supplier_id' => $supplierId ?: null,
                    'split' => $info['split'],
                    'label' => $optionLabel,
                    'chip' => $chip,
                ];

                $prefill[$key] = [
                    'supplier_id' => $supplierId ?: ($ris->ris_supplier_id ?? null),
                    'items' => $items->map(function ($item) {
                        $issued = (int) ($item->ris_quantity_issued ?? 0);
                        $requested = (int) ($item->ris_quantity_requested ?? 0);
                        $quantity = $issued > 0 ? $issued : $requested;

                        return [
                            'description' => $item->ris_item_name_description,
                            'quantity' => $quantity > 0 ? $quantity : null,
                            'unit' => $item->uom_name ?? '',
                            'unit_price' => $item->ris_unit_cost !== null ? (float) $item->ris_unit_cost : null,
                        ];
                    })->values()->all(),
                ];
            }
        }

        return ['options' => $options, 'prefill' => $prefill];
    }

    private function activeSuppliersQuery()
    {
        return DB::table('suppliers_table')
            ->leftJoin(
                'physical_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'physical_suppliers_table.supplier_id'
            )
            ->leftJoin(
                'online_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'online_suppliers_table.supplier_id'
            )
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_store_type',
                'suppliers_table.supplier_is_active',
                'suppliers_table.supplier_is_blacklisted',
                'suppliers_table.supplier_blacklist_reason',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->where('suppliers_table.supplier_is_active', 1)
            ->orderBy('suppliers_table.supplier_id');
    }

    private function findAtpWithRelations($id)
    {
        return DB::table('authority_to_purchase_table')
            ->leftJoin('requisition_issue_slip_table', 'authority_to_purchase_table.authority_purchase_ris_id', '=', 'requisition_issue_slip_table.ris_id')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'authority_to_purchase_table.*',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_purpose_description',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->where('authority_to_purchase_table.authority_purchase_id', $id)
            ->first();
    }

    private function assertRisLinesUnchanged(int $risId, ?int $supplierId, $items): void
    {
        $info = AtpRisSuppliers::groups([$risId])[$risId] ?? null;
        if (!$info) {
            return;
        }

        if (!empty($info['split'])) {
            $expectedSupplier = (int) $supplierId;
            $lines = collect($info['groups'][$expectedSupplier] ?? []);
        } else {
            $expectedSupplier = (int) (array_key_first($info['groups']) ?? 0);
            $lines = collect($info['groups'][$expectedSupplier] ?? []);
        }

        if ($expectedSupplier > 0 && (int) $supplierId !== $expectedSupplier) {
            throw ValidationException::withMessages([
                'authority_purchase_supplier_id' => 'The supplier is taken from the RIS and cannot be changed.',
            ]);
        }

        $posted = collect($items)->values();
        $lines = $lines->values();
        if ($posted->count() < $lines->count()) {
            throw ValidationException::withMessages([
                'items' => 'Keep every RIS item on this ATP. Extra rows can still be added.',
            ]);
        }

        foreach ($lines as $index => $line) {
            $row = $posted[$index] ?? [];
            $issued = (int) ($line->ris_quantity_issued ?? 0);
            $requested = (int) ($line->ris_quantity_requested ?? 0);
            $quantity = $issued > 0 ? $issued : $requested;
            $expectedPrice = $line->ris_unit_cost !== null ? round((float) $line->ris_unit_cost, 2) : null;
            $postedPrice = isset($row['unit_price']) && $row['unit_price'] !== null
                ? round((float) $row['unit_price'], 2)
                : null;
            $same = trim((string) ($row['description'] ?? '')) === trim((string) ($line->ris_item_name_description ?? ''))
                && (int) ($row['quantity'] ?? 0) === (int) $quantity
                && trim((string) ($row['unit'] ?? '')) === trim((string) ($line->uom_name ?? ''))
                && $postedPrice === $expectedPrice;

            if (!$same) {
                throw ValidationException::withMessages([
                    'items' => 'Quantity, unit, description, and unit price for RIS items cannot be changed.',
                ]);
            }
        }
    }

    private function filterItemRows(array $items)
    {
        return collect($items)
            ->filter(function ($item) {
                return filled($item['description'] ?? null);
            })
            ->map(function ($item) {
                return [
                    'description' => $item['description'] ?? null,
                    'quantity' => isset($item['quantity']) && $item['quantity'] !== ''
                        ? (int) $item['quantity']
                        : null,
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => isset($item['unit_price']) && $item['unit_price'] !== ''
                        ? (float) $item['unit_price']
                        : null,
                ];
            })
            ->values();
    }

    private function assertSubmitReadyItems($items): void
    {
        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Please add at least one ATP item before submitting.',
            ]);
        }

        foreach ($items as $index => $item) {
            $rowNumber = $index + 1;

            if (blank($item['description'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.description" => "Item {$rowNumber} requires a description.",
                ]);
            }

            if (($item['quantity'] ?? 0) < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => "Item {$rowNumber} requires a quantity of at least 1.",
                ]);
            }

            if (blank($item['unit'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit" => "Item {$rowNumber} requires a unit.",
                ]);
            }

            if ($item['unit_price'] === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => "Item {$rowNumber} requires a unit price.",
                ]);
            }
        }
    }

    private function replaceAtpItems($authorityPurchaseId, $items): void
    {
        DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $authorityPurchaseId)
            ->delete();

        $rows = [];
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? null;
            $unitPrice = $item['unit_price'] ?? null;

            $row = [
                'authority_purchase_id' => $authorityPurchaseId,
                'atp_description' => $item['description'],
                'atp_quantity' => $quantity,
                'atp_unit' => $item['unit'],
                'atp_unit_price' => $unitPrice,
                'atp_amount' => ($quantity !== null && $unitPrice !== null)
                    ? $quantity * $unitPrice
                    : null,
            ];
            $rows[] = $row;
        }
        if ($rows !== []) {
            DB::table('authority_to_purchase_items_table')->insert($rows);
        }
    }

    /**
     * RIS statuses that may start an ATP after Admin/President release.
     */
    private function applyAtpEligibleRisScope($query)
    {
        RisWorkflow::applyEligibleForAtpScope($query);

        // Purchaser-owned ATP path: any eligible approved RIS (no Supply handoff required).
        if (ProcurementPortal::PURCHASER_OWNS_ATP && ! ProcurementPortal::isSupplyOfficerContext()) {
            return $query;
        }

        // Supply Officer path: only RIS the Purchaser explicitly sent.
        if (SupplyHandoff::risHandoffColumnsReady()) {
            $query->whereNotNull('requisition_issue_slip_table.ris_sent_to_supply_at');
            $officerId = (int) auth()->id();
            $query->where(function ($q) use ($officerId) {
                $q->whereNull('requisition_issue_slip_table.ris_supply_officer_user_id')
                    ->orWhere('requisition_issue_slip_table.ris_supply_officer_user_id', $officerId);
            });
        }

        return $query;
    }

    private function atpFormNumberRules(bool $required, $ignoreAtpId = null): array
    {
        $unique = Rule::unique('authority_to_purchase_table', 'authority_purchase_form_number');
        if ($ignoreAtpId) {
            $unique->ignore($ignoreAtpId, 'authority_purchase_id');
        }

        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:30',
            'regex:'.AtpFormNumber::FORM_NUMBER_REGEX,
            $unique,
        ];
    }

    private function risIsEligibleForAtp(object $ris): bool
    {
        if (! RisWorkflow::isEligibleForAtp($ris)) {
            return false;
        }

        // Purchaser-owned ATP path: skip Supply handoff requirement.
        if (ProcurementPortal::PURCHASER_OWNS_ATP && ! ProcurementPortal::isSupplyOfficerContext()) {
            return true;
        }

        if (! SupplyHandoff::risHandoffColumnsReady()) {
            return true;
        }

        if (! SupplyHandoff::risIsSentToSupply($ris)) {
            return false;
        }

        $assigned = (int) ($ris->ris_supply_officer_user_id ?? 0);

        return $assigned === 0 || $assigned === (int) auth()->id();
    }

    /**
     * Latest live (non-rejected, non-archived) ATP linked to an RIS, if any.
     */
    private function liveAtpIdForRis(int $risId): ?int
    {
        if ($risId <= 0 || ! Schema::hasTable('authority_to_purchase_table')) {
            return null;
        }

        $id = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_ris_id', $risId)
            ->where(function ($q) {
                $q->whereNull('authority_purchase_is_archived')
                    ->orWhere('authority_purchase_is_archived', 0);
            })
            ->where('authority_purchase_status', '!=', 'Rejected')
            ->orderByDesc('authority_purchase_id')
            ->value('authority_purchase_id');

        return $id ? (int) $id : null;
    }

    public function exportBlankExcel(AtpFormExporter $exporter)
    {
        return $exporter->downloadExcel();
    }

    public function exportBlankWord(AtpFormExporter $exporter)
    {
        return $exporter->downloadWord();
    }

    public function exportExcel($id, AtpFormExporter $exporter)
    {
        [$atp, $items] = $this->loadAtpForExport($id);

        return $exporter->downloadExcel($atp, $items);
    }

    public function exportWord($id, AtpFormExporter $exporter)
    {
        [$atp, $items] = $this->loadAtpForExport($id);

        return $exporter->downloadWord($atp, $items);
    }

    private function loadAtpForExport($id): array
    {
        $atp = DB::table('authority_to_purchase_table')
            ->leftJoin(
                'suppliers_table',
                'authority_to_purchase_table.authority_purchase_supplier_id',
                '=',
                'suppliers_table.supplier_id'
            )
            ->leftJoin(
                'physical_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'physical_suppliers_table.supplier_id'
            )
            ->leftJoin(
                'online_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'online_suppliers_table.supplier_id'
            )
            ->where('authority_to_purchase_table.authority_purchase_id', $id)
            ->select(
                'authority_to_purchase_table.*',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->first();

        abort_if(!$atp, 404);
        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        $items = DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $id)
            ->orderBy('atp_item_id')
            ->get();

        return [$atp, $items];
    }
}
