<?php

namespace App\Http\Controllers;

use App\Support\DocumentUrgency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\BackOrders;
use App\Support\ProcurementPaymentPath;
use App\Support\PurchaserAttentionSummary;
use App\Support\PurchaserDocumentAccess;
use App\Support\ReviewerAssignment;
use App\Support\RfcAtpLinks;
use App\Support\RisWorkflow;
use App\Support\RrFormNumber;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;
use App\Services\DocumentWorkflowService;
use App\Services\ReceivingReportFormExporter;
use App\Support\ProcurementPortal;

class ReceivingReportController extends Controller
{
    private const ACTIVE_STATUSES = [
        'Draft',
        'Submitted',
        'Under Review',
        'Minor Revision',
        'Resubmitted',
    ];

    // Missing or damaged items are delivered as replacement rows on the same RR, never on a second RR.
    private const TAKEN_STATUSES = [
        ...self::ACTIVE_STATUSES,
        'Completed',
        'Accepted',
        'Incomplete',
    ];

    public function index(Request $request)
    {
        $archiveView = $request->query('view') === 'archive';
        $query = $this->rrBaseQuery();
        PurchaserDocumentAccess::scopeOwned($query, 'rr', 'receiving_reports_table');

        DocumentWorkflowService::applyArchiveFilter(
            $query,
            'receiving_reports_table.receiving_report_is_archived',
            $archiveView
        );

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($sub) use ($search) {
                $sub->where('receiving_reports_table.receiving_report_form_number', 'LIKE', $search)
                    ->orWhere('receiving_reports_table.receiving_report_received_from', 'LIKE', $search)
                    ->orWhere('request_check_table.request_check_form_number', 'LIKE', $search)
                    ->orWhere('receiving_reports_table.receiving_report_invoice_no', 'LIKE', $search);
            });
        }

        $attentionFocus = $archiveView
            ? null
            : PurchaserAttentionSummary::focusFor($request, PurchaserAttentionSummary::FOCUS_RR_READY_FOR_LIQ);
        if ($attentionFocus) {
            PurchaserAttentionSummary::scopeRrReadyForLiq($query);
        } elseif ($request->filled('status')) {
            $this->applyStatusFilter($query, $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('receiving_reports_table.receiving_report_date', $request->date);
        }

        $completeness = in_array($request->query('completeness'), ['complete', 'incomplete'], true) ? $request->query('completeness') : null;
        if (!$attentionFocus) {
            $this->applyBackOrderFilters($query, $completeness, $request->boolean('replacement'), $request->boolean('new_supplier'));
        }

        DocumentUrgency::select($query, 'RR');
        $spotlightQuery = clone $query;
        $reports = $query
            ->orderByDesc('receiving_reports_table.receiving_report_created_at')
            ->paginate(10)
            ->withQueryString();

        $viewRrId = (int) ($request->query('view_rr') ?: 0);
        $editRrId = (int) ($request->query('edit_rr') ?: 0);
        $spotlightId = $editRrId ?: $viewRrId;
        if (
            $spotlightId
            && !$reports->getCollection()->contains(fn ($row) => (int) $row->receiving_report_id === $spotlightId)
        ) {
            $spotlight = $spotlightQuery
                ->where('receiving_reports_table.receiving_report_id', $spotlightId)
                ->first();
            if ($spotlight) {
                $reports->setCollection($reports->getCollection()->prepend($spotlight));
            }
        }

        $summary = $this->rrStatusSummary();

        $eligibleRfcs = $this->eligibleFundingTargets();
        $rfcPrefill = $this->buildRfcPrefill($eligibleRfcs);
        $selectedRfcId = (int) $request->query('selected_rfc', 0);
        $selectedFundingKey = trim((string) $request->query('selected_key', ''));
        if ($selectedFundingKey === '' && $selectedRfcId > 0) {
            $selectedFundingKey = (string) ($eligibleRfcs->firstWhere('request_check_id', $selectedRfcId)->funding_key ?? '');
        }
        $suppliers = $this->activeSuppliersForRr();
        $rrIds = $reports->getCollection()->pluck('receiving_report_id');
        $items = $this->itemsFor($rrIds);

        $rrHasLiq = [];
        if ($rrIds->isNotEmpty() && Schema::hasTable('liquidation_reports_table')) {
            $rrHasLiq = DB::table('liquidation_reports_table')
                ->whereIn('liquidation_report_receiving_report_id', $rrIds)
                ->where(function ($q) {
                    $q->whereNull('liquidation_report_is_archived')
                        ->orWhere('liquidation_report_is_archived', 0);
                })
                ->where('liquidation_report_status', '!=', 'Rejected')
                ->pluck('liquidation_report_receiving_report_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $unresolvedBackOrders = BackOrders::unresolvedCounts($rrIds->all());
        foreach ($reports as $rr) {
            $rr->has_liq = in_array((int) $rr->receiving_report_id, $rrHasLiq, true);
            $path = $rr->authority_purchase_payment_path
                ?? ($rr->request_check_funding_type ?? null);
            $rr->requires_liquidation = ProcurementPaymentPath::requiresLiquidation($path);
            $rr->open_back_orders = (int) ($unresolvedBackOrders[(int) $rr->receiving_report_id] ?? 0);
            $rrRows = $items->get($rr->receiving_report_id, collect());
            $rr->replacement_rows = $rrRows->filter(fn ($row) => !empty($row->receiving_report_item_back_order_id))->count();
            $rr->is_incomplete = $rr->receiving_report_status === 'Incomplete' || $rr->open_back_orders > 0;
        }

        return view('purchaser.receiving-reports.index', [
            'attentionFocus' => $attentionFocus,
            'reports' => $reports,
            'archiveView' => $archiveView,
            'summary' => $summary,
            'eligibleRfcs' => $eligibleRfcs,
            'rfcPrefill' => $rfcPrefill,
            'suppliers' => $suppliers,
            'items' => $items,
            'selectedRfcId' => $selectedRfcId ?: null,
            'selectedFundingKey' => $selectedFundingKey !== '' ? $selectedFundingKey : null,
            'viewRrId' => $viewRrId ?: null,
            'editRrId' => $editRrId ?: null,
            'completeness' => $completeness,
            'savedSignatures' => UserSignatureLibrary::forUser((int) auth()->id()),
            'suggestedRrFormNumber' => RrFormNumber::next(),
        ]);
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('save_action', 'draft') === 'draft';
        $validated = $this->validateRr($request, $isDraft);
        $receivedName = trim((string) ($validated['receiving_report_received_by_name'] ?? ''));
        $receivedSig = RisWorkflow::normalizeDrawnSignature($validated['receiving_report_received_by_signature'] ?? null);
        if (!$isDraft && ($receivedName === '' || !$receivedSig)) {
            throw ValidationException::withMessages([
                'receiving_report_received_by_signature' => 'Printed name and a drawn/uploaded signature are required before submitting.',
            ]);
        }

        [$rfcId, $atpId] = $this->resolveRrTarget($validated);

        if ($error = $this->rrEligibilityError($rfcId, $atpId, null, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        if (!$isDraft && ($itemError = $this->rrItemRowsError($validated['items'] ?? [], $atpId))) {
            return back()->withInput()->with('error', $itemError);
        }

        if (!$isDraft && ($itemError = $this->validateRrItemsForFunding($rfcId, $atpId, $validated['items'] ?? []))) {
            return back()->withInput()->with('error', $itemError);
        }

        return DB::transaction(function () use ($validated, $isDraft, $receivedName, $receivedSig, $rfcId, $atpId) {
            $now = now();
            $user = auth()->user();
            $reviewerId = $isDraft
                ? null
                : ReviewerAssignment::resolve(request(), WorkflowNotifier::ROLE_RECEIVING);
            $formNumber = $isDraft
                ? null
                : (filled($validated['receiving_report_form_number'] ?? null)
                    ? (string) $validated['receiving_report_form_number']
                    : RrFormNumber::next());

            $payload = [
                'receiving_report_request_check_id' => $rfcId,
                'receiving_report_form_number' => $formNumber,
                'receiving_report_date' => $validated['receiving_report_date'] ?? null,
                'receiving_report_received_from' => $validated['receiving_report_received_from'] ?? null,
                'receiving_report_supplier_address_override' => $validated['receiving_report_supplier_address_override'] ?? null,
                'receiving_report_invoice_no' => $validated['receiving_report_invoice_no'] ?? null,
                'receiving_report_dr_no' => $validated['receiving_report_dr_no'] ?? null,
                'receiving_report_delivery_date' => $validated['receiving_report_delivery_date'] ?? null,
                'receiving_report_received_by_signature' => $receivedSig ?: ($receivedName !== '' ? $receivedName : ($user->user_full_name ?? null)),
                'receiving_report_status' => $isDraft ? 'Draft' : 'Submitted',
                'receiving_report_created_by' => auth()->id(),
                'receiving_report_submitted_by' => $isDraft ? null : auth()->id(),
                'receiving_report_submitted_at' => $isDraft ? null : $now,
                'receiving_report_is_archived' => 0,
                'receiving_report_created_at' => $now,
                'receiving_report_updated_at' => $now,
            ];
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_name')) {
                $payload['receiving_report_received_by_name'] = $receivedName !== '' ? $receivedName : null;
            }
            if (! $isDraft && Schema::hasColumn('receiving_reports_table', 'receiving_report_assigned_reviewer_id')) {
                $payload['receiving_report_assigned_reviewer_id'] = $reviewerId;
            }

            $id = DB::table('receiving_reports_table')->insertGetId($payload);

            $this->replaceItems($id, $validated['items'] ?? []);
            if (!$isDraft && !$this->hasCompleteRrItem($id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Add at least one item with quantity of 1 or more before submitting.',
                ]);
            }
            $this->linkRfc($rfcId, $id);
            $this->attachRelatedDocuments($id, $rfcId, $atpId);

            $backOrders = 0;
            if (!$isDraft) {
                $backOrders = $this->syncBackOrders($id);
                $this->notifyReceiving($id, $reviewerId);
            }

            return ProcurementPortal::redirect('rr.index')->with(
                'success',
                $isDraft ? 'Receiving Report draft saved.' : $this->submittedMessage($backOrders)
            );
        });
    }

    public function update(Request $request, $id)
    {
        $rr = $this->findRr($id);
        if (!$rr || !$this->isEditable($rr)) {
            return back()->with('error', 'Only draft or revision Receiving Reports can be edited.');
        }

        $isDraft = $request->input('save_action', 'draft') === 'draft';
        $validated = $this->validateRr($request, $isDraft, $id);
        [$rfcId, $atpId] = $this->resolveRrTarget($validated, $rr);
        $receivedName = trim((string) ($validated['receiving_report_received_by_name'] ?? ($rr->receiving_report_received_by_name ?? '')));
        $receivedSig = RisWorkflow::normalizeDrawnSignature($validated['receiving_report_received_by_signature'] ?? null)
            ?? (RisWorkflow::isDrawnSignature((string) ($rr->receiving_report_received_by_signature ?? ''))
                ? (string) $rr->receiving_report_received_by_signature
                : null);
        if (!$isDraft && ($receivedName === '' || !$receivedSig)) {
            throw ValidationException::withMessages([
                'receiving_report_received_by_signature' => 'Printed name and a drawn/uploaded signature are required before submitting.',
            ]);
        }

        if ($error = $this->rrEligibilityError($rfcId, $atpId, $id, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        if (!$isDraft && ($itemError = $this->rrItemRowsError($validated['items'] ?? [], $atpId, (int) $id))) {
            return back()->withInput()->with('error', $itemError);
        }

        if (!$isDraft && ($itemError = $this->validateRrItemsForFunding($rfcId, $atpId, $validated['items'] ?? [], (int) $id))) {
            return back()->withInput()->with('error', $itemError);
        }

        return DB::transaction(function () use ($validated, $rr, $isDraft, $id, $rfcId, $atpId, $receivedName, $receivedSig) {
            $now = now();
            $reviewerId = $isDraft
                ? null
                : ReviewerAssignment::resolve(request(), WorkflowNotifier::ROLE_RECEIVING);
            $wasRevision = $rr->receiving_report_status === 'Minor Revision';
            $status = $isDraft
                ? ($wasRevision ? 'Minor Revision' : 'Draft')
                : ($wasRevision ? 'Resubmitted' : 'Submitted');

            $formNumber = $isDraft
                ? ($wasRevision ? ($rr->receiving_report_form_number ?? null) : null)
                : (filled($validated['receiving_report_form_number'] ?? null)
                    ? (string) $validated['receiving_report_form_number']
                    : RrFormNumber::allocateOnSubmit($rr->receiving_report_form_number ?? null));

            $payload = [
                'receiving_report_request_check_id' => $rfcId,
                'receiving_report_form_number' => $formNumber,
                'receiving_report_date' => $validated['receiving_report_date'] ?? null,
                'receiving_report_received_from' => $validated['receiving_report_received_from'] ?? null,
                'receiving_report_supplier_address_override' => $validated['receiving_report_supplier_address_override'] ?? null,
                'receiving_report_invoice_no' => $validated['receiving_report_invoice_no'] ?? null,
                'receiving_report_dr_no' => $validated['receiving_report_dr_no'] ?? null,
                'receiving_report_delivery_date' => $validated['receiving_report_delivery_date'] ?? null,
                'receiving_report_received_by_signature' => $receivedSig ?: ($receivedName !== '' ? $receivedName : $rr->receiving_report_received_by_signature),
                'receiving_report_status' => $status,
                'receiving_report_submitted_by' => $isDraft ? $rr->receiving_report_submitted_by : auth()->id(),
                'receiving_report_submitted_at' => $isDraft ? $rr->receiving_report_submitted_at : $now,
                'receiving_report_updated_at' => $now,
            ];
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_name')) {
                $payload['receiving_report_received_by_name'] = $receivedName !== '' ? $receivedName : null;
            }
            if (! $isDraft && Schema::hasColumn('receiving_reports_table', 'receiving_report_assigned_reviewer_id')) {
                $payload['receiving_report_assigned_reviewer_id'] = $reviewerId;
            }

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update($payload);

            $this->replaceItems($id, $validated['items'] ?? []);
            if (!$isDraft && !$this->hasCompleteRrItem($id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Add at least one item with quantity of 1 or more before submitting.',
                ]);
            }
            $this->linkRfc($rfcId, $id);
            $this->attachRelatedDocuments($id, $rfcId, $atpId);

            $backOrders = 0;
            if (!$isDraft) {
                $backOrders = $this->syncBackOrders($id);
                $this->notifyReceiving($id, $reviewerId);
            }

            return ProcurementPortal::redirect('rr.index')->with(
                'success',
                $isDraft ? 'Receiving Report updated.' : $this->submittedMessage($backOrders)
            );
        });
    }

    public function submit($id)
    {
        $reviewerId = ReviewerAssignment::resolve(request(), WorkflowNotifier::ROLE_RECEIVING);

        return DB::transaction(function () use ($id, $reviewerId) {
            $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $id)->lockForUpdate()->first();
            if (!$rr || !$this->isEditable($rr)) {
                return back()->with('error', 'This Receiving Report cannot be submitted.');
            }
            PurchaserDocumentAccess::assertOwns($rr, 'rr');

            [$rfcId, $atpId] = $this->resolveRrTarget([], $rr);
            if ($error = $this->rrEligibilityError($rfcId, $atpId, $id, true)) {
                return back()->with('error', $error);
            }

            if (!$this->hasCompleteRrItem($id)) {
                return back()->with('error', 'Add at least one item with quantity of 1 or more before submitting.');
            }

            if ($itemError = $this->rrItemRowsError($this->storedRrItemRows((int) $id), $atpId, (int) $id)) {
                return back()->with('error', $itemError);
            }

            $receivedName = trim((string) ($rr->receiving_report_received_by_name ?? ''));
            if ($receivedName === '' && !RisWorkflow::isDrawnSignature((string) ($rr->receiving_report_received_by_signature ?? ''))) {
                $receivedName = trim((string) ($rr->receiving_report_received_by_signature ?? ''));
            }
            if ($receivedName === '' || !RisWorkflow::isDrawnSignature((string) ($rr->receiving_report_received_by_signature ?? ''))) {
                return back()->with('error', 'Printed name and a drawn/uploaded signature are required before submitting.');
            }

            if (blank($rr->receiving_report_date)) {
                return back()->with('error', 'Date is required before submitting.');
            }

            $wasRevision = $rr->receiving_report_status === 'Minor Revision';
            $update = [
                'receiving_report_status' => $wasRevision ? 'Resubmitted' : 'Submitted',
                'receiving_report_form_number' => RrFormNumber::allocateOnSubmit($rr->receiving_report_form_number ?? null),
                'receiving_report_submitted_by' => auth()->id(),
                'receiving_report_submitted_at' => now(),
                'receiving_report_updated_at' => now(),
            ];
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_assigned_reviewer_id')) {
                $update['receiving_report_assigned_reviewer_id'] = $reviewerId;
            }
            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update($update);
            $this->linkRfc($rfcId, $id);
            $this->attachRelatedDocuments($id, $rfcId, $atpId);
            $backOrders = $this->syncBackOrders($id);
            $this->notifyReceiving($id, $reviewerId);

            return back()->with('success', $this->submittedMessage($backOrders));
        });
    }

    public function archive($id)
    {
        $rr = $this->findRr($id);
        if (!$rr || !in_array($rr->receiving_report_status, ['Completed', 'Returned'], true)) {
            return back()->with('error', 'Only completed or returned Receiving Reports can be archived.');
        }

        DocumentWorkflowService::setArchived(
            'receiving_reports_table',
            'receiving_report_id',
            $id,
            'receiving_report_is_archived',
            'receiving_report_updated_at',
            true
        );

        return back()->with('success', 'Receiving Report archived.');
    }

    public function restore($id)
    {
        $rr = $this->findRr($id);
        if (!$rr) {
            return back()->with('error', 'Receiving Report not found.');
        }

        DocumentWorkflowService::setArchived(
            'receiving_reports_table',
            'receiving_report_id',
            $id,
            'receiving_report_is_archived',
            'receiving_report_updated_at',
            false
        );

        return back()->with('success', 'Receiving Report restored.');
    }

    public static function reviewBaseQuery()
    {
        return DB::table('receiving_reports_table')
            ->leftJoin(
                'request_check_table',
                'receiving_reports_table.receiving_report_request_check_id',
                '=',
                'request_check_table.request_check_id'
            )
            ->select(
                'receiving_reports_table.*',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_payee'
            );
    }

    private function validateRr(Request $request, bool $isDraft, $ignoreRrId = null): array
    {
        return $request->validate([
            'save_action' => ['required', 'in:draft,submit'],
            'receiving_report_request_check_id' => [
                'nullable',
                'integer',
                'exists:request_check_table,request_check_id',
            ],
            'receiving_report_funding_key' => ['nullable', 'string', 'regex:/^\d+:\d+$/'],
            'receiving_report_form_number' => $this->rrFormNumberRules(false, $ignoreRrId),
            'receiving_report_date' => [$isDraft ? 'nullable' : 'required', 'date'],
            'receiving_report_received_from' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'receiving_report_supplier_address_override' => ['nullable', 'string', 'max:2000'],
            'receiving_report_invoice_no' => ['nullable', 'string', 'max:100'],
            'receiving_report_dr_no' => ['nullable', 'string', 'max:100'],
            'receiving_report_delivery_date' => ['nullable', 'date'],
            'receiving_report_received_by_name' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'receiving_report_received_by_signature' => [$isDraft ? 'nullable' : 'required', 'string', 'max:2000000'],
            'items' => ['nullable', 'array', 'max:9'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'items.*.ordered_qty' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'items.*.condition' => ['nullable', 'in:ok,short,bad_order'],
            'items.*.condition_remarks' => ['nullable', 'string', 'max:500'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.article' => ['nullable', 'string', 'max:2000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.supplier_id' => ['nullable', 'integer', 'exists:suppliers_table,supplier_id'],
            'items.*.supplier_name' => ['nullable', 'string', 'max:255'],
            'items.*.back_order_id' => ['nullable', 'integer'],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.damaged_qty' => ['nullable', 'integer', 'min:0', 'max:9999999', 'lte:items.*.quantity'],
            'items.*.damage_remarks' => ['nullable', 'string', 'max:500'],
        ], [
            'items.*.damaged_qty.lte' => 'Damaged units cannot be more than the quantity received.',
            'receiving_report_form_number.required' => 'Receiving Report number is required before submitting.',
            'receiving_report_form_number.regex' => 'Receiving Report number must follow the format RR-YYYYMM-0000001.',
            'receiving_report_form_number.unique' => 'This Receiving Report number is already in use.',
        ]);
    }

    private function rrFormNumberRules(bool $required, $ignoreRrId = null): array
    {
        $unique = Rule::unique('receiving_reports_table', 'receiving_report_form_number');
        if ($ignoreRrId) {
            $unique->ignore($ignoreRrId, 'receiving_report_id');
        }

        return [
            $required ? 'required' : 'nullable',
            'regex:'.RrFormNumber::FORM_NUMBER_REGEX,
            $unique,
        ];
    }

    /**
     * Rows already verified at second count are kept untouched; the rest are re-inserted in form order
     * and their back order links move to the new row ids.
     */
    private function replaceItems($rrId, array $items): void
    {
        $table = 'receiving_report_items_table';
        $hasVerified = Schema::hasColumn($table, 'receiving_report_item_verified');
        $existing = DB::table($table)->where('receiving_report_id', $rrId)->orderBy('receiving_report_item_id')->get();
        [$verifiedRows, $oldRows] = $existing->partition(fn ($row) => $hasVerified && !empty($row->receiving_report_item_verified));
        $verifiedIds = $verifiedRows->pluck('receiving_report_item_id')->map(fn ($itemId) => (int) $itemId)->all();
        $oldIds = $oldRows->pluck('receiving_report_item_id')->map(fn ($itemId) => (int) $itemId)->values()->all();

        DB::table($table)->where('receiving_report_id', $rrId)
            ->when($verifiedIds !== [], fn ($q) => $q->whereNotIn('receiving_report_item_id', $verifiedIds))
            ->delete();

        $allowedBackOrderIds = BackOrders::supported()
            ? DB::table(BackOrders::TABLE)->where('back_order_root_receiving_report_id', $rrId)->pluck('back_order_id')->map(fn ($boId) => (int) $boId)->all()
            : [];
        $hasBackOrderColumn = Schema::hasColumn($table, 'receiving_report_item_back_order_id');
        $hasDamagedColumn = Schema::hasColumn($table, 'receiving_report_item_damaged_qty');

        $newIds = [];
        foreach (array_slice($items, 0, 9) as $row) {
            if (in_array((int) ($row['item_id'] ?? 0), $verifiedIds, true)) {
                continue;
            }
            $qty = $row['quantity'] ?? null;
            $unit = $row['unit'] ?? null;
            $article = $row['article'] ?? null;
            if ($qty === null && blank($unit) && blank($article)) {
                continue;
            }

            $itemRow = [
                'receiving_report_id' => $rrId,
                'receiving_report_item_quantity' => $qty ?: null,
                'receiving_report_item_unit' => $unit,
                'receiving_report_item_article' => $article,
            ];

            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_unit_price')) {
                $itemRow['receiving_report_item_unit_price'] = isset($row['unit_price']) && $row['unit_price'] !== ''
                    ? (float) $row['unit_price']
                    : null;
            }
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_supplier_id')) {
                $itemRow['receiving_report_item_supplier_id'] = $row['supplier_id'] ?? null;
            }
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_supplier_name')) {
                $itemRow['receiving_report_item_supplier_name'] = $row['supplier_name'] ?? null;
            }
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_ordered_qty')) {
                $ordered = $row['ordered_qty'] ?? null;
                $itemRow['receiving_report_item_ordered_qty'] = ($ordered !== null && $ordered !== '')
                    ? (int) $ordered
                    : null;
            }
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_condition')) {
                $condition = strtolower(trim((string) ($row['condition'] ?? 'ok')));
                if (! in_array($condition, ['ok', 'short', 'bad_order'], true)) {
                    $condition = 'ok';
                }
                $orderedQty = (int) ($itemRow['receiving_report_item_ordered_qty'] ?? 0);
                $receivedQty = (int) ($qty ?: 0);
                if ($condition === 'ok' && $orderedQty > 0 && $receivedQty < $orderedQty) {
                    $condition = 'short';
                }
                $itemRow['receiving_report_item_condition'] = $condition;
            }
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_condition_remarks')) {
                $itemRow['receiving_report_item_condition_remarks'] = filled($row['condition_remarks'] ?? null)
                    ? trim((string) $row['condition_remarks'])
                    : null;
            }
            if ($hasBackOrderColumn) {
                $backOrderId = (int) ($row['back_order_id'] ?? 0);
                $itemRow['receiving_report_item_back_order_id'] = in_array($backOrderId, $allowedBackOrderIds, true) ? $backOrderId : null;
            }
            if ($hasDamagedColumn) {
                $damaged = min((int) ($qty ?: 0), max(0, (int) ($row['damaged_qty'] ?? 0)));
                $itemRow['receiving_report_item_damaged_qty'] = $damaged;
                $damageRemarks = $row['damage_remarks'] ?? $row['condition_remarks'] ?? null;
                $itemRow['receiving_report_item_damage_remarks'] = $damaged > 0 && filled($damageRemarks)
                    ? mb_substr(trim((string) $damageRemarks), 0, 500)
                    : null;
            }

            $newId = (int) DB::table($table)->insertGetId($itemRow, 'receiving_report_item_id');
            $postedId = (int) ($row['item_id'] ?? 0);
            if (in_array($postedId, $oldIds, true)) {
                $newIds[$postedId] = $newId;
            }
        }

        $this->relinkBackOrders($rrId, $oldIds, $newIds);
    }

    /**
     * Point back orders at the re-inserted rows; back orders of removed rows are dropped
     * (or, for a removed replacement row, reopened).
     *
     * @param  array<int, int>  $oldIds
     * @param  array<int, int>  $newIds  old row id => new row id
     */
    private function relinkBackOrders($rrId, array $oldIds, array $newIds): void
    {
        if ($oldIds === [] || !BackOrders::supported()) {
            return;
        }

        $table = BackOrders::TABLE;
        foreach ($newIds as $oldId => $newId) {
            DB::table($table)->where('back_order_receiving_report_item_id', $oldId)->update(['back_order_receiving_report_item_id' => $newId]);
            DB::table($table)->where('back_order_replacement_item_id', $oldId)->update(['back_order_replacement_item_id' => $newId]);
        }

        $removedIds = array_values(array_diff($oldIds, array_keys($newIds)));
        if ($removedIds === []) {
            return;
        }

        foreach (DB::table($table)->whereIn('back_order_replacement_item_id', $removedIds)->get() as $bo) {
            BackOrders::clearReplacement($bo);
        }
        BackOrders::deleteBackOrders(DB::table($table)->whereIn('back_order_receiving_report_item_id', $removedIds)->get());
    }

    /**
     * Row checks applied on submit: ordered and received are both required, received cannot exceed
     * ordered, article and unit are required, and ordered must match the ATP line (or back order).
     */
    private function rrItemRowsError(array $items, ?int $atpId, ?int $rrId = null): ?string
    {
        $filled = fn ($value) => $value !== null && trim((string) $value) !== '';
        $normalize = fn ($text) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $text)));

        $atpLines = $atpId
            ? DB::table('authority_to_purchase_items_table')
                ->where('authority_purchase_id', $atpId)
                ->orderBy('atp_item_id')
                ->get(['atp_description', 'atp_quantity'])
                ->map(fn ($line) => ['description' => $normalize($line->atp_description), 'quantity' => (int) $line->atp_quantity])
                ->all()
            : [];
        $backOrders = $rrId && BackOrders::supported()
            ? BackOrders::forRoot($rrId)->keyBy('back_order_id')
            : collect();

        foreach (array_values(array_slice($items, 0, 9)) as $index => $row) {
            $line = 'Item '.($index + 1);
            $ordered = $row['ordered_qty'] ?? null;
            $received = $row['quantity'] ?? null;
            $article = $row['article'] ?? null;
            $unit = $row['unit'] ?? null;
            $hasData = $filled($ordered) || $filled($received) || (int) ($row['damaged_qty'] ?? 0) > 0
                || $filled($article) || $filled($unit);
            if (!$hasData) {
                continue;
            }

            if (!$filled($ordered)) {
                return $line.': enter the ordered quantity.';
            }
            if (!$filled($received)) {
                return $line.': enter the received quantity (0 if nothing arrived).';
            }
            if ((int) $received > (int) $ordered) {
                return $line.': received ('.(int) $received.') cannot be more than ordered ('.(int) $ordered.').';
            }
            if (!$filled($article) || !$filled($unit)) {
                return $line.': article and unit are required.';
            }

            $backOrder = $backOrders->get((int) ($row['back_order_id'] ?? 0));
            if ($backOrder) {
                $expected = (int) $backOrder->back_order_quantity;
                if ((int) $ordered !== $expected) {
                    return $line.': ordered must match the back order quantity ('.$expected.').';
                }
                continue;
            }

            $key = $normalize($article);
            foreach ($atpLines as $atpIndex => $atpLine) {
                if ($atpLine['description'] !== $key) {
                    continue;
                }
                unset($atpLines[$atpIndex]);
                if ((int) $ordered !== $atpLine['quantity']) {
                    return $line.': ordered must match the ATP quantity ('.$atpLine['quantity'].').';
                }
                break;
            }
        }

        return null;
    }

    /**
     * Stored rows in the same shape as the submitted form, skipping rows already verified at second count.
     */
    private function storedRrItemRows(int $rrId): array
    {
        $table = 'receiving_report_items_table';
        $hasVerified = Schema::hasColumn($table, 'receiving_report_item_verified');

        return DB::table($table)
            ->where('receiving_report_id', $rrId)
            ->orderBy('receiving_report_item_id')
            ->get()
            ->map(fn ($item) => ($hasVerified && !empty($item->receiving_report_item_verified)) ? [] : [
                'ordered_qty' => $item->receiving_report_item_ordered_qty ?? null,
                'quantity' => $item->receiving_report_item_quantity ?? null,
                'damaged_qty' => $item->receiving_report_item_damaged_qty ?? 0,
                'article' => $item->receiving_report_item_article ?? null,
                'unit' => $item->receiving_report_item_unit ?? null,
                'back_order_id' => $item->receiving_report_item_back_order_id ?? null,
            ])
            ->all();
    }

    private function validateRrItemsForFunding($rfcId, $atpId, array $items, ?int $rrId = null): ?string
    {
        if (!$rfcId) {
            return null;
        }

        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        if (!$rfc) {
            return 'Selected funding request was not found.';
        }

        $atpId = (int) ($atpId ?: $rfc->request_check_authority_purchase_id);
        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        $rfc->authority_purchase_payment_path = $atp->authority_purchase_payment_path ?? null;
        $rfc->authority_purchase_supplier_id = $atp->authority_purchase_supplier_id ?? null;

        $path = $rfc->authority_purchase_payment_path
            ?? ($rfc->request_check_funding_type ?? ProcurementPaymentPath::REQUEST_FOR_CHECK);

        $atpItems = DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $atpId)
            ->orderBy('atp_item_id')
            ->get();

        if ($path === ProcurementPaymentPath::REQUEST_FOR_CHECK) {
            foreach ($items as $row) {
                if (!empty($row['supplier_id']) && (int) $row['supplier_id'] !== (int) $rfc->authority_purchase_supplier_id) {
                    return 'Request for Check requires all items from the ATP supplier only.';
                }
            }
        }

        $backOrders = $rrId && BackOrders::supported()
            ? BackOrders::forRoot((int) $rrId)->keyBy('back_order_id')
            : collect();
        $rows = array_values(array_slice($items, 0, 9));
        foreach ($rows as $index => $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            $backOrder = $backOrders->get((int) ($row['back_order_id'] ?? 0));
            if ($backOrder && $qty > (int) $backOrder->back_order_quantity) {
                return 'Item ' . ($index + 1) . ' cannot receive more than the ' . (int) $backOrder->back_order_quantity . ' on back order.';
            }
        }

        if ($path === ProcurementPaymentPath::CASH_ADVANCE) {
            $budgetByIndex = $atpItems->values();
            $originalIndex = 0;
            foreach ($rows as $index => $row) {
                if ($backOrders->has((int) ($row['back_order_id'] ?? 0))) {
                    continue;
                }
                $budgetItem = $budgetByIndex[$originalIndex++] ?? null;
                if (!$budgetItem) {
                    continue;
                }
                $unitPrice = isset($row['unit_price']) && $row['unit_price'] !== '' ? (float) $row['unit_price'] : null;
                $maxPrice = (float) ($budgetItem->atp_unit_price ?? 0);
                if ($unitPrice !== null && $maxPrice > 0 && $unitPrice > $maxPrice) {
                    return 'Item ' . ($index + 1) . ' unit price cannot exceed the approved budget of ₱' . number_format($maxPrice, 2) . '.';
                }
            }
        }

        return null;
    }

    private function activeSuppliersForRr()
    {
        return DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('suppliers_table.supplier_is_active', 1)
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->orderBy('suppliers_table.supplier_id')
            ->get();
    }

    private function linkRfc($rfcId, $rrId): void
    {
        if (!$rfcId || !Schema::hasColumn('request_check_table', 'request_check_receiving_report_id')) {
            return;
        }

        DB::table('request_check_table')
            ->where('request_check_id', $rfcId)
            ->update(['request_check_receiving_report_id' => $rrId]);
    }

    private function rrBaseQuery()
    {
        $query = DB::table('receiving_reports_table')
            ->leftJoin(
                'request_check_table',
                'receiving_reports_table.receiving_report_request_check_id',
                '=',
                'request_check_table.request_check_id'
            );

        if ($this->rrHasAtpColumn()) {
            $query->leftJoin('authority_to_purchase_table', function ($join) {
                $join->on(
                    'authority_to_purchase_table.authority_purchase_id',
                    '=',
                    DB::raw('COALESCE(receiving_reports_table.receiving_report_atp_id, request_check_table.request_check_authority_purchase_id)')
                );
            });
        } else {
            $query->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
            );
        }

        return $query->select(
            'receiving_reports_table.*',
            'request_check_table.request_check_form_number',
            'request_check_table.request_check_payee',
            'request_check_table.request_check_funding_type',
            'authority_to_purchase_table.authority_purchase_form_number',
            'authority_to_purchase_table.authority_purchase_payment_path'
        );
    }

    private function rrHasAtpColumn(): bool
    {
        return Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id');
    }

    /**
     * One row per (funding request, ATP) pair that still needs a Receiving Report.
     * A funding request covering several ATPs (Purchase Order) yields one row per ATP.
     */
    private function eligibleFundingTargets()
    {
        $query = DB::table('request_check_table')
            ->where('request_check_table.request_check_status', 'Approved')
            ->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });

        if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            $query->whereNotNull('request_check_table.request_check_funds_released_at');
        }

        $rfcs = $query
            ->select(
                'request_check_table.request_check_id',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_payee',
                'request_check_table.request_check_funding_type',
                'request_check_table.request_check_authority_purchase_id'
            )
            ->orderByDesc('request_check_table.request_check_id')
            ->limit(50)
            ->get();

        if ($rfcs->isEmpty()) {
            return collect();
        }

        $primaryByRfc = $rfcs->mapWithKeys(fn ($rfc) => [
            (int) $rfc->request_check_id => (int) ($rfc->request_check_authority_purchase_id ?? 0) ?: null,
        ])->all();
        $linkMap = RfcAtpLinks::atpIdsForMany($primaryByRfc);
        $atpIds = collect($linkMap)->flatten()->unique()->values();
        if ($atpIds->isEmpty()) {
            return collect();
        }

        $atps = DB::table('authority_to_purchase_table')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->whereIn('authority_to_purchase_table.authority_purchase_id', $atpIds)
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_payment_path',
                'authority_to_purchase_table.authority_purchase_supplier_id',
                'physical_suppliers_table.company_name',
                'physical_suppliers_table.company_address',
                'online_suppliers_table.shop_name',
                'suppliers_table.supplier_store_type'
            )
            ->get()
            ->keyBy('authority_purchase_id');

        $taken = [];
        $activeRrs = DB::table('receiving_reports_table')
            ->whereIn('receiving_report_request_check_id', $rfcs->pluck('request_check_id'))
            ->where(function ($inner) {
                $inner->whereNull('receiving_report_is_archived')
                    ->orWhere('receiving_report_is_archived', 0);
            })
            ->whereIn('receiving_report_status', self::TAKEN_STATUSES)
            ->get($this->rrHasAtpColumn()
                ? ['receiving_report_request_check_id', 'receiving_report_atp_id']
                : ['receiving_report_request_check_id']);
        foreach ($activeRrs as $row) {
            $rfcId = (int) $row->receiving_report_request_check_id;
            $atpId = (int) ($row->receiving_report_atp_id ?? 0) ?: (int) ($primaryByRfc[$rfcId] ?? 0);
            $taken[$rfcId.':'.$atpId] = true;
        }

        $targets = collect();
        foreach ($rfcs as $rfc) {
            $rfcAtpIds = $linkMap[(int) $rfc->request_check_id] ?? [];
            foreach ($rfcAtpIds as $atpId) {
                $key = $rfc->request_check_id.':'.$atpId;
                $atp = $atps->get($atpId);
                if (isset($taken[$key]) || !$atp) {
                    continue;
                }
                $targets->push((object) array_merge((array) $atp, [
                    'funding_key' => $key,
                    'request_check_id' => (int) $rfc->request_check_id,
                    'request_check_form_number' => $rfc->request_check_form_number,
                    'request_check_payee' => $rfc->request_check_payee,
                    'request_check_funding_type' => $rfc->request_check_funding_type,
                    'is_multi_atp' => count($rfcAtpIds) > 1,
                ]));
            }
        }

        return $targets;
    }

    /**
     * @return array{0: ?int, 1: ?int} [rfcId, atpId]
     */
    private function resolveRrTarget(array $validated, ?object $rr = null): array
    {
        $key = trim((string) ($validated['receiving_report_funding_key'] ?? ''));
        if (preg_match('/^(\d+):(\d+)$/', $key, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        $rfcId = (int) ($validated['receiving_report_request_check_id'] ?? ($rr->receiving_report_request_check_id ?? 0));
        if ($rfcId < 1) {
            return [null, null];
        }

        if ($rr && (int) $rr->receiving_report_request_check_id === $rfcId && !empty($rr->receiving_report_atp_id)) {
            return [$rfcId, (int) $rr->receiving_report_atp_id];
        }

        $primary = DB::table('request_check_table')->where('request_check_id', $rfcId)->value('request_check_authority_purchase_id');
        $atpIds = RfcAtpLinks::atpIdsFor($rfcId, $primary ? (int) $primary : null);

        return [$rfcId, count($atpIds) === 1 ? $atpIds[0] : null];
    }

    private function buildRfcPrefill($eligibleRfcs): array
    {
        $prefill = [];
        $atpIds = collect($eligibleRfcs)->pluck('authority_purchase_id')->filter();

        $atpItems = DB::table('authority_to_purchase_items_table')
            ->whereIn('authority_purchase_id', $atpIds)
            ->orderBy('atp_item_id')
            ->get()
            ->groupBy('authority_purchase_id');

        foreach ($eligibleRfcs as $rfc) {
            $from = $rfc->supplier_store_type === 'Physical Store'
                ? ($rfc->company_name ?? $rfc->request_check_payee)
                : ($rfc->shop_name ?? $rfc->request_check_payee);
            $path = $rfc->authority_purchase_payment_path
                ?? ($rfc->request_check_funding_type ?? ProcurementPaymentPath::REQUEST_FOR_CHECK);
            $rows = [];
            foreach (($atpItems[$rfc->authority_purchase_id] ?? collect())->take(10) as $index => $item) {
                $rows[] = [
                    'quantity' => $item->atp_quantity,
                    'ordered_qty' => $item->atp_quantity,
                    'condition' => 'ok',
                    'condition_remarks' => '',
                    'unit' => $item->atp_unit,
                    'article' => $item->atp_description,
                    'unit_price' => $item->atp_unit_price,
                    'supplier_id' => $path === ProcurementPaymentPath::CASH_ADVANCE ? null : $rfc->authority_purchase_supplier_id,
                    'supplier_name' => $path === ProcurementPaymentPath::CASH_ADVANCE ? '' : $from,
                ];
            }
            $prefill[(string) $rfc->funding_key] = [
                'received_from' => $from,
                'address' => $rfc->company_address ?? '',
                'payment_path' => $path,
                'items' => $rows,
            ];
        }

        return $prefill;
    }

    private function rrStatusSummary(): array
    {
        $counts = DB::table('receiving_reports_table')
            ->select('receiving_report_status', 'receiving_report_is_archived', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('receiving_report_status', 'receiving_report_is_archived')
            ->get();

        $summary = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'completed' => 0,
            'incomplete' => 0,
            'returned' => 0,
            'archived' => 0,
        ];
        $submitted = ['Submitted', 'Under Review', 'Resubmitted'];

        foreach ($counts as $row) {
            $count = (int) $row->aggregate;
            if ((int) $row->receiving_report_is_archived === 1) {
                $summary['archived'] += $count;
                continue;
            }
            $summary['total'] += $count;
            if ($row->receiving_report_status === 'Draft') {
                $summary['draft'] += $count;
            } elseif (in_array($row->receiving_report_status, $submitted, true)) {
                $summary['submitted'] += $count;
            } elseif ($row->receiving_report_status === 'Completed') {
                $summary['completed'] += $count;
            } elseif ($row->receiving_report_status === 'Incomplete') {
                $summary['incomplete'] += $count;
            } elseif ($row->receiving_report_status === 'Returned') {
                $summary['returned'] += $count;
            }
        }

        return $summary;
    }

    private function rrEligibilityError($rfcId, $atpId, $ignoreId, bool $required): ?string
    {
        if (!$rfcId) {
            return $required ? 'Select an approved Request for Check with released funds before submitting.' : null;
        }

        $primary = DB::table('request_check_table')->where('request_check_id', $rfcId)->value('request_check_authority_purchase_id');
        $coveredAtpIds = RfcAtpLinks::atpIdsFor((int) $rfcId, $primary ? (int) $primary : null);

        if (!$atpId) {
            return $required && count($coveredAtpIds) > 1
                ? 'This funding request covers several ATPs. Choose which ATP this Receiving Report is for.'
                : null;
        }

        if ($coveredAtpIds !== [] && !in_array((int) $atpId, $coveredAtpIds, true)) {
            return 'That ATP is not covered by the selected funding request.';
        }

        if ($this->hasBlockingRr($rfcId, $atpId, $ignoreId)) {
            return count($coveredAtpIds) > 1
                ? 'A Receiving Report already exists for this ATP on the selected funding request.'
                : 'A Receiving Report already exists for the selected Request for Check.';
        }

        if (!$this->rfcReadyForReceivingReport($rfcId)) {
            return 'Funds must be released by Accounting before a Receiving Report can be created.';
        }

        return null;
    }

    private function hasCompleteRrItem($rrId): bool
    {
        return DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $rrId)
            ->where('receiving_report_item_quantity', '>=', 1)
            ->exists();
    }

    private function hasBlockingRr($rfcId, $atpId = null, $ignoreId = null): bool
    {
        if (!$rfcId) {
            return false;
        }

        $query = DB::table('receiving_reports_table')
            ->where('receiving_report_request_check_id', $rfcId)
            ->whereIn('receiving_report_status', self::TAKEN_STATUSES)
            ->where(function ($q) {
                $q->whereNull('receiving_report_is_archived')
                    ->orWhere('receiving_report_is_archived', 0);
            });

        if ($atpId && $this->rrHasAtpColumn()) {
            $primary = (int) DB::table('request_check_table')->where('request_check_id', $rfcId)->value('request_check_authority_purchase_id');
            $query->where(function ($q) use ($atpId, $primary) {
                $q->where('receiving_report_atp_id', $atpId);
                if ($primary === (int) $atpId) {
                    $q->orWhereNull('receiving_report_atp_id');
                }
            });
        }

        if ($ignoreId) {
            $query->where('receiving_report_id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Purchaser's verdict at submission (first check) creates the back orders.
     */
    private function syncBackOrders($rrId): int
    {
        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->first();

        return $rr ? BackOrders::syncForRr($rr) : 0;
    }

    private function submittedMessage(int $newBackOrders): string
    {
        return $newBackOrders > 0
            ? 'Receiving Report submitted to Receiving as Incomplete. ' . $newBackOrders . ' missing/damaged line' . ($newBackOrders === 1 ? ' was' : 's were') . ' added to Back Orders.'
            : 'Receiving Report submitted to Receiving.';
    }

    private function applyBackOrderFilters($query, ?string $completeness, bool $replacementOnly, bool $newSupplierOnly): void
    {
        $unresolved = function ($q) {
            $q->select(DB::raw(1))
                ->from(BackOrders::TABLE)
                ->whereColumn('back_order_root_receiving_report_id', 'receiving_reports_table.receiving_report_id')
                ->whereIn('back_order_status', BackOrders::UNRESOLVED);
        };
        $supported = BackOrders::supported();

        if ($completeness === 'incomplete') {
            $query->where(function ($q) use ($unresolved, $supported) {
                $q->where('receiving_reports_table.receiving_report_status', 'Incomplete');
                if ($supported) {
                    $q->orWhereExists($unresolved);
                }
            });
        } elseif ($completeness === 'complete') {
            $query->whereIn('receiving_reports_table.receiving_report_status', ['Completed', 'Accepted']);
            if ($supported) {
                $query->whereNotExists($unresolved);
            }
        }

        if (($replacementOnly || $newSupplierOnly) && $supported) {
            $query->whereExists(function ($q) use ($newSupplierOnly) {
                $q->select(DB::raw(1))
                    ->from(BackOrders::TABLE)
                    ->whereColumn('back_order_root_receiving_report_id', 'receiving_reports_table.receiving_report_id')
                    ->whereNotNull('back_order_replacement_item_id');
                if ($newSupplierOnly) {
                    $q->whereNotNull('back_order_replacement_supplier_name');
                }
            });
        }
    }

    private function rfcReadyForReceivingReport($rfcId): bool
    {
        if (!$rfcId) {
            return false;
        }

        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        if (!$rfc || $rfc->request_check_status !== 'Approved') {
            return false;
        }

        if (
            Schema::hasColumn('request_check_table', 'request_check_funds_released_at')
            && empty($rfc->request_check_funds_released_at)
        ) {
            return false;
        }

        return true;
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'Submitted') {
            $query->whereIn('receiving_reports_table.receiving_report_status', ['Submitted', 'Under Review', 'Resubmitted']);
            return;
        }
        $query->where('receiving_reports_table.receiving_report_status', $status);
    }

    private function itemsFor($ids)
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('receiving_report_items_table')
            ->whereIn('receiving_report_id', $ids)
            ->orderBy('receiving_report_item_id')
            ->get()
            ->groupBy('receiving_report_id');
    }

    private function findRr($id)
    {
        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $id)->first();
        if ($rr) {
            PurchaserDocumentAccess::assertOwns($rr, 'rr');
        }

        return $rr;
    }

    public function exportBlankExcel(ReceivingReportFormExporter $exporter)
    {
        return $exporter->downloadExcel();
    }

    public function exportBlankWord(ReceivingReportFormExporter $exporter)
    {
        return $exporter->downloadWord();
    }

    public function exportExcel($id, ReceivingReportFormExporter $exporter)
    {
        $rr = $this->findRr($id);
        abort_if(!$rr, 404);
        $items = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $id)
            ->orderBy('receiving_report_item_id')
            ->get();

        return $exporter->downloadExcel($rr, $items);
    }

    public function exportWord($id, ReceivingReportFormExporter $exporter)
    {
        $rr = $this->findRr($id);
        abort_if(!$rr, 404);
        $items = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $id)
            ->orderBy('receiving_report_item_id')
            ->get();

        return $exporter->downloadWord($rr, $items);
    }

    private function notifyReceiving($id, ?int $reviewerId = null): void
    {
        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $id)->first();
        $ref = $rr->receiving_report_form_number ?? ('RR #' . $id);
        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_RECEIVING,
            'Receiving Report submitted',
            $ref . ' is waiting for inspection.',
            'rr_submitted',
            'RR',
            (int) $id,
            '/receiving/reports',
            $reviewerId
        );
    }

    private function attachRelatedDocuments($rrId, $rfcId, $atpId = null): void
    {
        if (!$rfcId) {
            return;
        }

        $atpId = (int) ($atpId ?: DB::table('request_check_table')
            ->where('request_check_id', $rfcId)
            ->value('request_check_authority_purchase_id'));
        if ($atpId < 1) {
            return;
        }

        $risId = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $atpId)
            ->value('authority_purchase_ris_id');

        $payload = [];
        if ($this->rrHasAtpColumn()) {
            $payload['receiving_report_atp_id'] = $atpId;
        }
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_ris_id') && !empty($risId)) {
            $payload['receiving_report_ris_id'] = $risId;
        }

        if ($payload !== []) {
            DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->update($payload);
        }
    }

    private function isEditable($rr): bool
    {
        return DocumentWorkflowService::isEditable(
            $rr,
            'receiving_report_status',
            ['Draft', 'Minor Revision'],
            'receiving_report_is_archived'
        );
    }
}
