<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\ProcurementPaymentPath;
use App\Support\PurchaserDocumentAccess;
use App\Support\RisWorkflow;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;
use App\Services\AtpFormExporter;
use App\Services\DocumentWorkflowService;
use App\Support\ProcurementPortal;

class AuthorityToPurchaseController extends Controller
{
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
            });
        }

        if ($request->filled('status')) {
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

        $spotlightQuery = clone $query;
        $atps = $query
            ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
            ->paginate(10)
            ->withQueryString();

        $selectedRisId = $request->query('selected_ris');
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

        $eligibleRis = $this->eligibleRisQuery()->limit(50)->get();
        $suppliers = $this->activeSuppliersQuery()->get();

        $atpIds = $atps->getCollection()->pluck('authority_purchase_id');

        $atpItems = DB::table('authority_to_purchase_items_table')
            ->whereIn('authority_purchase_id', $atpIds)
            ->orderBy('atp_item_id')
            ->get()
            ->groupBy('authority_purchase_id');

        $atpHasRfc = [];
        if ($atpIds->isNotEmpty() && Schema::hasTable('request_check_table')) {
            $rfcLinkQuery = DB::table('request_check_table')
                ->whereIn('request_check_authority_purchase_id', $atpIds)
                ->where('request_check_status', '!=', 'Rejected');

            if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
                $rfcLinkQuery->where(function ($q) {
                    $q->whereNull('request_check_is_archived')
                        ->orWhere('request_check_is_archived', 0);
                });
            }

            $atpHasRfc = $rfcLinkQuery
                ->pluck('request_check_authority_purchase_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        foreach ($atps as $atp) {
            $atp->has_rfc = in_array((int) $atp->authority_purchase_id, $atpHasRfc, true);
        }

        $risPrefill = $this->buildRisPrefill($eligibleRis);
        $savedSignatures = UserSignatureLibrary::forUser((int) auth()->id());
        $suggestedAtpFormNumber = $this->nextSuggestedAtpFormNumber();

        return view(
            'purchaser.authority-to-purchase.index',
            compact(
                'atps',
                'archiveView',
                'atpSummary',
                'eligibleRis',
                'suppliers',
                'atpItems',
                'selectedRisId',
                'viewAtpId',
                'editAtpId',
                'risPrefill',
                'savedSignatures',
                'suggestedAtpFormNumber'
            )
        );
    }

    public function create(Request $request)
    {
        return ProcurementPortal::redirect('atp.index', array_filter([
            'selected_ris' => $request->query('selected_ris'),
        ]));
    }

    public function store(Request $request)
    {
        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';

        $validated = $request->validate([
            'save_action' => ['required', 'in:draft,submit'],
            'authority_purchase_ris_id' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                'exists:requisition_issue_slip_table,ris_id',
            ],
            'authority_purchase_form_number' => $this->atpFormNumberRules(!$isDraft),
            'authority_purchase_supplier_id' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                Rule::exists('suppliers_table', 'supplier_id')->where(fn ($q) => $q->where('supplier_is_active', 1)),
            ],
            'authority_purchase_date' => [
                $isDraft ? 'nullable' : 'required',
                'date',
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
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.supplier_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ], [
            'authority_purchase_form_number.required' => 'ATP number is required before submitting.',
            'authority_purchase_form_number.digits' => 'ATP number must be exactly 4 digits.',
            'authority_purchase_form_number.unique' => 'This ATP number is already in use.',
            'authority_purchase_ris_id.required' => 'Select an approved RIS before submitting.',
        ]);

        $receivedSig = RisWorkflow::normalizeDrawnSignature($validated['authority_purchase_received_by_signature'] ?? null);
        if (!$isDraft && !$receivedSig) {
            throw ValidationException::withMessages([
                'authority_purchase_received_by_signature' => 'Draw or upload your signature before submitting.',
            ]);
        }

        $risId = isset($validated['authority_purchase_ris_id'])
            ? (int) $validated['authority_purchase_ris_id']
            : null;

        if ($risId) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->first();

            if (!$ris || !$this->risIsEligibleForAtp($ris)) {
                return back()->withInput()->with('error', 'Only approved RIS records may be used to create ATP.');
            }

            if ($this->hasBlockingAtpForRis($risId)) {
                return back()->withInput()->with('error', 'An Authority to Purchase already exists for the selected RIS.');
            }
        } elseif (!$isDraft) {
            return back()->withInput()->with('error', 'Select an approved RIS before submitting.');
        }

        $items = $this->filterItemRows($validated['items'] ?? []);

        if (!$isDraft) {
            $this->assertSubmitReadyItems($items);
        }

        return DB::transaction(function () use ($validated, $items, $isDraft, $receivedSig, $risId) {
            $now = now();

            $formNumber = filled($validated['authority_purchase_form_number'] ?? null)
                ? (string) $validated['authority_purchase_form_number']
                : $this->nextSuggestedAtpFormNumber();

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

            $authorityPurchaseId = DB::table('authority_to_purchase_table')->insertGetId($payload);

            $this->replaceAtpItems($authorityPurchaseId, $items);

            if (!$isDraft) {
                $this->notifyAccountingAtp($authorityPurchaseId, $formNumber);
            }

            $message = $isDraft
                ? 'Authority to Purchase draft created successfully.'
                : 'Authority to Purchase submitted successfully.';

            return ProcurementPortal::redirect('atp.index')->with('success', $message);
        });
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
                ->with('error', 'Only draft ATP records can be edited.');
        }

        return ProcurementPortal::redirect('atp.index', ['edit_atp' => $id]);
    }

    public function choosePaymentPath(Request $request, $id)
    {
        $validated = $request->validate([
            'authority_purchase_payment_path' => [
                'required',
                Rule::in([ProcurementPaymentPath::REQUEST_FOR_CHECK, ProcurementPaymentPath::CASH_ADVANCE]),
            ],
        ]);

        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->first();
        if (!$atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if ($atp->authority_purchase_status !== 'Approved') {
            return back()->with('error', 'Payment path can only be chosen after ATP is approved.');
        }

        if (
            filled($atp->authority_purchase_payment_path ?? null)
            && Schema::hasTable('request_check_table')
            && DB::table('request_check_table')
                ->where('request_check_authority_purchase_id', $id)
                ->where('request_check_status', '!=', 'Rejected')
                ->exists()
        ) {
            return back()->with('error', 'Payment path cannot be changed after a funding request has been created.');
        }

        DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->update([
                'authority_purchase_payment_path' => $validated['authority_purchase_payment_path'],
                'authority_purchase_payment_path_chosen_at' => now(),
                'authority_purchase_updated_at' => now(),
            ]);

        $label = ProcurementPaymentPath::label($validated['authority_purchase_payment_path']);
        $route = $validated['authority_purchase_payment_path'] === ProcurementPaymentPath::CASH_ADVANCE
            ? ProcurementPortal::route('rfc.index', ['selected_atp' => $id, 'funding_type' => ProcurementPaymentPath::CASH_ADVANCE])
            : ProcurementPortal::route('rfc.index', ['selected_atp' => $id, 'funding_type' => ProcurementPaymentPath::REQUEST_FOR_CHECK]);

        return redirect($route)->with('success', "Payment path set to {$label}. You may now create the funding request.");
    }

    public function update(Request $request, $id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase record not found.');
        }

        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if (!$this->isSoftDraft($atp)) {
            return back()->with('error', 'Only draft ATP records can be updated.');
        }

        $saveAction = $request->input('save_action', 'save');
        $isDraft = in_array($saveAction, ['save', 'draft'], true);

        $validated = $request->validate([
            'save_action' => ['required', 'in:save,draft,submit'],
            'authority_purchase_form_number' => $this->atpFormNumberRules(!$isDraft, $id),
            'authority_purchase_supplier_id' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                Rule::exists('suppliers_table', 'supplier_id')->where(fn ($q) => $q->where('supplier_is_active', 1)),
            ],
            'authority_purchase_date' => [
                $isDraft ? 'nullable' : 'required',
                'date',
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
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.supplier_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ], [
            'authority_purchase_form_number.required' => 'ATP number is required before submitting.',
            'authority_purchase_form_number.digits' => 'ATP number must be exactly 4 digits.',
            'authority_purchase_form_number.unique' => 'This ATP number is already in use.',
        ]);

        $receivedSig = RisWorkflow::normalizeDrawnSignature($validated['authority_purchase_received_by_signature'] ?? null);
        if (!$isDraft && !$receivedSig) {
            throw ValidationException::withMessages([
                'authority_purchase_received_by_signature' => 'Draw or upload your signature before submitting.',
            ]);
        }

        $items = $this->filterItemRows($validated['items'] ?? []);

        if (!$isDraft) {
            $this->assertSubmitReadyItems($items);
        }

        return DB::transaction(function () use ($validated, $id, $items, $isDraft, $atp, $receivedSig) {
            $now = now();

            $formNumber = filled($validated['authority_purchase_form_number'] ?? null)
                ? (string) $validated['authority_purchase_form_number']
                : ($this->normalizeAtpFormNumberForEdit($atp->authority_purchase_form_number ?? null)
                    ?: $this->nextSuggestedAtpFormNumber());

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
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update($payload);

            $this->replaceAtpItems($id, $items);

            if (!$isDraft) {
                $this->notifyAccountingAtp($id, $formNumber);
            }

            $message = $isDraft
                ? 'Authority to Purchase draft updated successfully.'
                : 'Authority to Purchase submitted successfully.';

            return redirect()
                ->route(ProcurementPortal::routeName('atp.index'))
                ->with('success', $message);
        });
    }

    public function submit($id)
    {
        return DB::transaction(function () use ($id) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->lockForUpdate()
                ->first();

            if (!$atp) {
                return back()->with('error', 'Authority to Purchase not found.');
            }

            PurchaserDocumentAccess::assertOwns($atp, 'atp');

            if (!$this->isSoftDraft($atp)) {
                return back()->with('error', 'Only draft ATP records can be submitted.');
            }

            if (blank($atp->authority_purchase_supplier_id)) {
                return back()->with('error', 'Supplier is required before submitting.');
            }

            if (blank($atp->authority_purchase_date)) {
                return back()->with('error', 'Date is required before submitting.');
            }

            if (blank($atp->authority_purchase_form_number) || !preg_match('/^\d{4}$/', (string) $atp->authority_purchase_form_number)) {
                return back()->with('error', 'ATP number must be exactly 4 digits before submitting.');
            }

            if (blank($atp->authority_purchase_received_by_name)) {
                return back()->with('error', 'Received By is required before submitting.');
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

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_status' => 'Pending',
                    'authority_purchase_submitted_by' => auth()->id(),
                    'authority_purchase_submitted_at' => now(),
                    'authority_purchase_rejection_reason' => null,
                    'authority_purchase_updated_at' => now(),
                ]);

            $this->notifyAccountingAtp($id, $atp->authority_purchase_form_number);

            return redirect()
                ->route(ProcurementPortal::routeName('atp.index'))
                ->with('success', 'Authority to Purchase submitted successfully.');
        });
    }

    public function archive($id)
    {
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

    private function notifyAccountingAtp($id, ?string $formNumber): void
    {
        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_ACCOUNTING,
            'ATP submitted for review',
            ($formNumber ?: ('ATP #' . $id)) . ' is waiting for Accounting review.',
            'atp_submitted',
            'ATP',
            (int) $id,
            '/accounting/authority-to-purchase/' . $id
        );
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

    private function hasBlockingAtpForRis(int $risId): bool
    {
        return DB::table('authority_to_purchase_table')
            ->where('authority_purchase_ris_id', $risId)
            ->where(function ($q) {
                $q->whereNull('authority_purchase_is_archived')
                    ->orWhere('authority_purchase_is_archived', 0);
            })
            ->where('authority_purchase_status', '!=', 'Rejected')
            ->exists();
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
                    ->where('authority_purchase_status', '!=', 'Rejected');
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
        $rows = DB::table('authority_to_purchase_table')
            ->select(
                'authority_purchase_status',
                'authority_purchase_is_archived',
                DB::raw('CASE WHEN authority_purchase_submitted_at IS NULL THEN 0 ELSE 1 END as is_submitted'),
                DB::raw('COUNT(*) as aggregate')
            )
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

    private function buildRisPrefill($eligibleRis): array
    {
        $risIds = collect($eligibleRis)->pluck('ris_id')->filter()->values();
        $prefill = [];

        foreach ($eligibleRis as $ris) {
            $prefill[(string) $ris->ris_id] = [
                'supplier_id' => $ris->ris_supplier_id ?? null,
                'items' => [],
            ];
        }

        if ($risIds->isEmpty()) {
            return $prefill;
        }

        $itemsQuery = DB::table('requisition_issue_slip_items_table')
            ->whereIn('requisition_issue_slip_items_table.ris_id', $risIds)
            ->orderBy('ris_item_id');

        $select = ['requisition_issue_slip_items_table.*'];

        if (
            Schema::hasTable('uom_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')
        ) {
            $itemsQuery->leftJoin(
                'uom_table',
                'uom_table.uom_id',
                '=',
                'requisition_issue_slip_items_table.ris_item_uom_id'
            );
            $select[] = 'uom_table.uom_name';
        }

        $items = $itemsQuery->select($select)->get()->groupBy('ris_id');

        foreach ($items as $risId => $risItems) {
            $firstSupplier = null;
            $prefill[(string) $risId]['items'] = $risItems->map(function ($item) use (&$firstSupplier) {
                $issued = (int) ($item->ris_quantity_issued ?? 0);
                $requested = (int) ($item->ris_quantity_requested ?? 0);
                $quantity = $issued > 0 ? $issued : $requested;
                $lineSupplier = $item->ris_item_supplier_id ?? null;
                if ($firstSupplier === null && $lineSupplier) {
                    $firstSupplier = $lineSupplier;
                }

                return [
                    'description' => $item->ris_item_name_description,
                    'quantity' => $quantity > 0 ? $quantity : null,
                    'unit' => $item->uom_name ?? '',
                    'unit_price' => $item->ris_unit_cost !== null ? (float) $item->ris_unit_cost : null,
                    'supplier_id' => $lineSupplier,
                ];
            })->values()->all();

            $prefill[(string) $risId]['supplier_id'] = $firstSupplier ?: ($prefill[(string) $risId]['supplier_id'] ?? null);
        }

        return $prefill;
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
                    'supplier_stock' => isset($item['supplier_stock']) && $item['supplier_stock'] !== ''
                        ? (int) $item['supplier_stock']
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

            $supplierStock = $item['supplier_stock'] ?? null;
            $backOrderQty = null;
            if ($quantity !== null && $supplierStock !== null && $quantity > $supplierStock) {
                $backOrderQty = $quantity - $supplierStock;
            }

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

            if (Schema::hasColumn('authority_to_purchase_items_table', 'atp_supplier_stock')) {
                $row['atp_supplier_stock'] = $supplierStock;
            }
            if (Schema::hasColumn('authority_to_purchase_items_table', 'atp_back_order_qty')) {
                $row['atp_back_order_qty'] = $backOrderQty;
            }

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
        return RisWorkflow::applyEligibleForAtpScope($query);
    }

    private function atpFormNumberRules(bool $required, $ignoreAtpId = null): array
    {
        $unique = Rule::unique('authority_to_purchase_table', 'authority_purchase_form_number');
        if ($ignoreAtpId) {
            $unique->ignore($ignoreAtpId, 'authority_purchase_id');
        }

        return [
            $required ? 'required' : 'nullable',
            'digits:4',
            $unique,
        ];
    }

    /**
     * Next editable default for the ATP "No." field (4 zero-padded digits).
     * Considers both new 4-digit values and legacy ATP-YYYY-##### numbers.
     */
    private function nextSuggestedAtpFormNumber(): string
    {
        $max = 0;

        foreach (
            DB::table('authority_to_purchase_table')
                ->whereNotNull('authority_purchase_form_number')
                ->pluck('authority_purchase_form_number') as $formNumber
        ) {
            $parsed = $this->parseAtpFormNumberSequence((string) $formNumber);
            if ($parsed !== null) {
                $max = max($max, $parsed);
            }
        }

        $next = min($max + 1, 9999);

        return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function parseAtpFormNumberSequence(string $formNumber): ?int
    {
        $formNumber = trim($formNumber);
        if ($formNumber === '') {
            return null;
        }

        if (preg_match('/^\d{1,4}$/', $formNumber)) {
            return (int) $formNumber;
        }

        if (preg_match('/(\d+)$/', $formNumber, $matches)) {
            $n = (int) $matches[1];

            return $n > 9999 ? (int) substr((string) $n, -4) : $n;
        }

        return null;
    }

    private function normalizeAtpFormNumberForEdit(?string $formNumber): string
    {
        if ($formNumber === null || trim($formNumber) === '') {
            return '';
        }

        if (preg_match('/^\d{4}$/', trim($formNumber))) {
            return trim($formNumber);
        }

        $parsed = $this->parseAtpFormNumberSequence($formNumber);

        return $parsed === null
            ? ''
            : str_pad((string) $parsed, 4, '0', STR_PAD_LEFT);
    }

    private function risIsEligibleForAtp(object $ris): bool
    {
        return RisWorkflow::isEligibleForAtp($ris);
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
