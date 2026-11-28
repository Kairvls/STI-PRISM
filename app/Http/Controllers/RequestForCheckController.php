<?php

namespace App\Http\Controllers;

use App\Support\DocumentUrgency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
use App\Support\RfcFormNumber;
use App\Support\RisWorkflow;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;
use App\Services\DocumentWorkflowService;
use App\Services\RfcFormExporter;
use Illuminate\Validation\ValidationException;
use App\Support\ProcurementPortal;
use App\Support\AccountingHandoff;
use Illuminate\Support\Facades\Auth;

class RequestForCheckController extends Controller
{
    private const EDITABLE_STATUSES = ['Draft', 'Minor Revision'];

    private const ARCHIVEABLE_STATUSES = ['Approved', 'Rejected'];

    public function index(Request $request)
    {
        $archiveView = $request->query('view') === 'archive';

        $query = $this->rfcBaseQuery();
        $this->applyPurchaserOwnership($query);
        $this->applyArchiveFilter($query, $archiveView);

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($sub) use ($search) {
                if ($this->rfcHas('request_check_form_number')) {
                    $sub->where('request_check_table.request_check_form_number', 'LIKE', $search)
                        ->orWhere('request_check_table.request_check_payee', 'LIKE', $search);
                } else {
                    $sub->where('request_check_table.request_check_payee', 'LIKE', $search);
                }
                $sub->orWhere('authority_to_purchase_table.authority_purchase_form_number', 'LIKE', $search)
                    ->orWhere('request_check_table.request_check_particulars_purpose', 'LIKE', $search);
                if ($this->rfcHas('request_check_request_no')) {
                    $sub->orWhere('request_check_table.request_check_request_no', 'LIKE', $search);
                }
            });
        }

        $fundFilter = ProcurementPaymentPath::isValid($request->query('fund'))
            ? ProcurementPaymentPath::normalize($request->query('fund'))
            : null;
        $this->applyFundFilter($query, $fundFilter);

        $attentionFocus = null;
        if (! $archiveView) {
            $attentionFocus = PurchaserAttentionSummary::focusFor($request, PurchaserAttentionSummary::FOCUS_CHECK_READY)
                ?? PurchaserAttentionSummary::focusFor($request, PurchaserAttentionSummary::FOCUS_RFC_READY_FOR_RR);
        }
        if ($attentionFocus && $request->query('focus') === PurchaserAttentionSummary::FOCUS_CHECK_READY) {
            PurchaserAttentionSummary::scopeCheckReady($query);
        } elseif ($attentionFocus && $request->query('focus') === PurchaserAttentionSummary::FOCUS_RFC_READY_FOR_RR) {
            PurchaserAttentionSummary::scopeRfcReadyForRr($query);
        } elseif ($request->filled('status')) {
            $this->applyStatusFilter($query, $request->status);
        }

        $dateFrom = trim((string) $request->input('date_from', $request->input('date', '')));
        $dateTo = trim((string) $request->input('date_to', ''));

        // Single calendar pick (date_from only) = exact submitted day.
        if ($dateFrom !== '' && $dateTo === '') {
            $query->whereDate('request_check_table.request_check_date', $dateFrom);
        } else {
            if ($dateFrom !== '') {
                $query->whereDate('request_check_table.request_check_date', '>=', $dateFrom);
            }
            if ($dateTo !== '') {
                $query->whereDate('request_check_table.request_check_date', '<=', $dateTo);
            }
        }

        DocumentUrgency::select($query, 'RFC');
        $spotlightQuery = clone $query;

        $hasReadyCol = Schema::hasColumn('request_check_table', 'request_check_check_ready_at');
        $hasReleasedCol = Schema::hasColumn('request_check_table', 'request_check_funds_released_at');
        $hasSentCol = Schema::hasColumn('request_check_table', 'request_check_sent_to_accounting_at');
        $hasRrTable = Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id');
        $rrArchiveClause = $hasRrTable && Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')
            ? ' AND (rr_chk.receiving_report_is_archived IS NULL OR rr_chk.receiving_report_is_archived = 0)'
            : '';

        // Active view: Action-needed first (0), Waiting on others (1), Completed/Rejected (2),
        // maintaining STACK (LIFO by request_check_id DESC) inside each tier.
        if (! $archiveView) {
            $noRrExists = $hasRrTable
                ? "NOT EXISTS (SELECT 1 FROM receiving_reports_table as rr_chk WHERE rr_chk.receiving_report_request_check_id = request_check_table.request_check_id{$rrArchiveClause})"
                : '1 = 1';
            $tier0Parts = ["request_check_table.request_check_status IN ('Minor Revision', 'Draft')"];
            if ($hasReleasedCol) {
                $tier0Parts[] = "(request_check_table.request_check_status = 'Approved' AND request_check_table.request_check_funds_released_at IS NOT NULL AND {$noRrExists})";
            }
            if ($hasSentCol) {
                $notReleasedClause = $hasReleasedCol ? ' AND request_check_table.request_check_funds_released_at IS NULL' : '';
                $tier0Parts[] = "(request_check_table.request_check_status = 'Approved' AND request_check_table.request_check_sent_to_accounting_at IS NULL{$notReleasedClause})";
            }
            $tier1NotReleased = $hasReleasedCol ? ' AND request_check_table.request_check_funds_released_at IS NULL' : '';
            $prioritySql = 'CASE'
                .' WHEN '.implode(' OR ', $tier0Parts).' THEN 0'
                ." WHEN request_check_table.request_check_status IN ('Pending', 'Submitted', 'Under Review', 'Resubmitted')"
                ." OR (request_check_table.request_check_status = 'Approved'{$tier1NotReleased}) THEN 1"
                .' ELSE 2 END';

            $query->orderByRaw($prioritySql);
        }

        $rfcs = $query
            ->orderByDesc('request_check_table.request_check_id')
            ->paginate(10)
            ->withQueryString();

        $viewRfcId = (int) ($request->query('view_rfc') ?: 0);
        if (
            $viewRfcId
            && !$rfcs->getCollection()->contains(fn ($row) => (int) $row->request_check_id === $viewRfcId)
        ) {
            $spotlight = $spotlightQuery
                ->where('request_check_table.request_check_id', $viewRfcId)
                ->first();
            if ($spotlight) {
                $rfcs->setCollection($rfcs->getCollection()->prepend($spotlight));
            }
        }

        $rfcSummary = $this->rfcStatusSummary($fundFilter);
        $eligibleAtps = collect();
        $fundingGroups = [];
        $atpPrefill = [];
        $selectedSource = trim((string) $request->query('selected_source', ''));
        if ($selectedSource === '' && $request->filled('selected_atp')) {
            $selectedSource = 'atp:'.(int) $request->query('selected_atp');
        }
        $loadFormDatasets = ! $request->ajax()
            && (
                $request->boolean('create')
                || $selectedSource !== ''
                || ($request->session()->getOldInput('request_check_funding_source') !== null)
            );
        if ($loadFormDatasets) {
            $fundingType = $this->resolveFundingType($request);
            $eligibleAtps = $this->eligibleAtpQuery($fundingType)->get();
            foreach ($this->buildAtpPrefill($eligibleAtps, $fundingType) as $atpId => $row) {
                $atpPrefill['atp:'.$atpId] = $row;
            }
            $fundingGroups = PurchaseOrderFunding::fundingGroups($fundingType);
            foreach ($fundingGroups as $group) {
                $atpPrefill[$group['key']] = [
                    'payee' => $group['payee'],
                    'amount' => $group['amount'],
                    'purpose' => $group['purpose'],
                ];
            }
        }
        $rfcIds = $rfcs->getCollection()->pluck('request_check_id');
        $attachments = $this->attachmentsFor($rfcIds);

        $rfcHasRr = [];
        if (
            $rfcIds->isNotEmpty()
            && Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
        ) {
            $rrQuery = DB::table('receiving_reports_table')
                ->whereIn('receiving_report_request_check_id', $rfcIds)
                ->where('receiving_report_status', '!=', 'Returned');

            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
                $rrQuery->where(function ($q) {
                    $q->whereNull('receiving_report_is_archived')
                        ->orWhere('receiving_report_is_archived', 0);
                });
            }

            $hasRrAtpColumn = Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id');
            $rfcHasRr = $rrQuery
                ->get($hasRrAtpColumn
                    ? ['receiving_report_request_check_id', 'receiving_report_atp_id']
                    : ['receiving_report_request_check_id'])
                ->groupBy('receiving_report_request_check_id')
                ->map(fn ($rows) => $rows->pluck('receiving_report_atp_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all())
                ->all();
        }

        $this->attachLinkedAtps($rfcs->getCollection());

        $revisionRfcIds = $rfcs->getCollection()
            ->filter(fn ($row) => filled($row->request_check_revision_notes ?? null))
            ->pluck('request_check_id')
            ->all();
        DocumentRevisionNotes::warmLatest('RFC', $revisionRfcIds);

        $rfcLineages = [];
        if ($viewRfcId > 0) {
            $rfcLineages[$viewRfcId] = DocumentLineage::forRfc($viewRfcId);
        }

        foreach ($rfcs as $rfc) {
            $rrAtps = $rfcHasRr[(int) $rfc->request_check_id] ?? null;
            $linked = $rfc->linked_atp_ids ?? [];
            $rfc->has_rr = $rrAtps !== null
                && (count($linked) < 2 || array_diff($linked, $rrAtps) === []);
            $rfc->funds_released = !empty($rfc->request_check_funds_released_at ?? null);
            $rfc->funds_collected = !empty($rfc->request_check_funds_collected_at ?? null);
            $rfc->check_ready = !empty($rfc->request_check_check_ready_at ?? null);
            $rfc->sent_to_accounting = AccountingHandoff::rfcIsSentToAccounting($rfc);
            $rfc->can_send_to_accounting = AccountingHandoff::purchaserCanSendRfc($rfc);
            $rfc->must_joint_submit = $this->mustUseJointAtpSubmit($rfc);
            $rfc->joint_submit_atp_id = $this->jointSubmitAtpId($rfc);
        }

        $batchRisId = (int) $request->query('batch_ris', 0);
        $batchAllModes = $request->boolean('batch_all');
        $batchFundingType = $this->resolveFundingType($request);
        $batchRfcItems = [];
        if ($batchRisId > 0 && ! $request->ajax()) {
            if ($batchAllModes) {
                $todayIso = now()->toDateString();
                $collected = collect();
                foreach (ProcurementPaymentPath::choices() as $modePath) {
                    $modeRows = $this->eligibleAtpQuery($modePath)
                        ->where('authority_to_purchase_table.authority_purchase_ris_id', $batchRisId)
                        ->get();
                    if ($modeRows->isEmpty()) {
                        continue;
                    }
                    $prefills = $this->buildAtpPrefill($modeRows, $modePath);
                    $risNo = \App\Support\RisWorkflow::formNumber($modeRows->first(), $batchRisId);
                    foreach ($modeRows as $bAtp) {
                        $aid = (int) $bAtp->authority_purchase_id;
                        $pf = $prefills[$aid] ?? [];
                        $amt = isset($pf['amount']) && is_numeric($pf['amount']) ? (float) $pf['amount'] : 0.0;
                        $collected->push([
                            'atp_id' => $aid,
                            'atp_number' => (string) ($bAtp->authority_purchase_form_number ?: ('ATP #'.$aid)),
                            'ris_number' => (string) $risNo,
                            'request_no' => (string) ($bAtp->authority_purchase_request_no ?? ''),
                            'funding_type' => $modePath,
                            'funding_label' => ProcurementPaymentPath::label($modePath),
                            'payee' => (string) ($pf['payee'] ?? ''),
                            'amount' => round($amt, 2),
                            'amount_words' => $this->amountInWords($amt),
                            'purpose' => (string) ($pf['purpose'] ?? ''),
                            'date' => $todayIso,
                        ]);
                    }
                }
                $batchRfcItems = $collected->sortByDesc('atp_id')->values()->all();
            } else {
                $batchEligible = $this->eligibleAtpQuery($batchFundingType)
                    ->where('authority_to_purchase_table.authority_purchase_ris_id', $batchRisId)
                    ->get();
                if ($batchEligible->isNotEmpty()) {
                    $prefills = $this->buildAtpPrefill($batchEligible, $batchFundingType);
                    $todayIso = now()->toDateString();
                    $risNo = \App\Support\RisWorkflow::formNumber($batchEligible->first(), $batchRisId);
                    foreach ($batchEligible as $bAtp) {
                        $aid = (int) $bAtp->authority_purchase_id;
                        $pf = $prefills[$aid] ?? [];
                        $amt = isset($pf['amount']) && is_numeric($pf['amount']) ? (float) $pf['amount'] : 0.0;
                        $batchRfcItems[] = [
                            'atp_id' => $aid,
                            'atp_number' => (string) ($bAtp->authority_purchase_form_number ?: ('ATP #'.$aid)),
                            'ris_number' => (string) $risNo,
                            'request_no' => (string) ($bAtp->authority_purchase_request_no ?? ''),
                            'funding_type' => $batchFundingType,
                            'funding_label' => ProcurementPaymentPath::label($batchFundingType),
                            'payee' => (string) ($pf['payee'] ?? ''),
                            'amount' => round($amt, 2),
                            'amount_words' => $this->amountInWords($amt),
                            'purpose' => (string) ($pf['purpose'] ?? ''),
                            'date' => $todayIso,
                        ];
                    }
                }
            }
        }

        // Group current page's RFCs by Request No. (same RIS) for collapsible rows + carousel
        $actionTierFor = function ($r) use ($archiveView): int {
            if ($archiveView) {
                return 0;
            }
            $st = (string) ($r->request_check_status ?? '');
            if (in_array($st, ['Minor Revision', 'Draft'], true)) {
                return 0;
            }
            if ($st === 'Approved') {
                if (! empty($r->can_send_to_accounting)) {
                    return 0;
                }
                if (! empty($r->funds_released) && empty($r->has_rr)) {
                    return 0;
                }
                if (empty($r->funds_released)) {
                    return 1;
                }

                return 2;
            }
            if (in_array($st, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)) {
                return 1;
            }

            return 2;
        };

        $rfcGroups = $rfcs->getCollection()
            ->groupBy(function ($rfc) {
                $reqNo = trim((string) ($rfc->request_check_request_no ?? ''));
                if ($reqNo !== '') {
                    return 'req-'.preg_replace('/[^A-Za-z0-9_-]/', '', $reqNo);
                }
                return 'rfc-'.(int) $rfc->request_check_id;
            })
            ->map(function ($items, $key) use ($actionTierFor) {
                $items = $items->sort(function ($a, $b) use ($actionTierFor) {
                    $ta = $actionTierFor($a);
                    $tb = $actionTierFor($b);
                    if ($ta !== $tb) {
                        return $ta <=> $tb;
                    }

                    return ((int) $b->request_check_id) <=> ((int) $a->request_check_id);
                })->values();
                $first = $items->first();
                $payees = $items->pluck('request_check_payee')->filter()->unique()->values();
                $dates = $items->pluck('request_check_date')->filter()->sort()->values();
                $totalAmount = (float) $items->sum(fn ($r) => (float) ($r->request_check_amount_figures ?? 0));
                $urgent = $items->contains(fn ($r) => \App\Support\DocumentUrgency::isUrgent('RFC', $r));
                $maxId = (int) $items->max('request_check_id');
                $minTier = (int) $items->min(fn ($r) => $actionTierFor($r));

                return [
                    'key' => (string) $key,
                    'count' => $items->count(),
                    'rfcs' => $items,
                    'request_no' => $first->request_check_request_no ?? null,
                    'urgent' => $urgent,
                    'date' => $dates->last() ?: null,
                    'total_amount' => round($totalAmount, 2),
                    'payee_summary' => $payees->count() === 1
                        ? (string) $payees->first()
                        : ($payees->count() > 1 ? $payees->count().' payees' : '—'),
                    'rfc_ids' => $items->pluck('request_check_id')->map(fn ($id) => (int) $id)->values()->all(),
                    'max_rfc_id' => $maxId,
                    'action_tier' => $minTier,
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['action_tier'] !== $b['action_tier']) {
                    return $a['action_tier'] <=> $b['action_tier'];
                }

                return $b['max_rfc_id'] <=> $a['max_rfc_id'];
            })
            ->values();

        return view('purchaser.request-check.index', [
            'attentionFocus' => $attentionFocus,
            'fundFilter' => $fundFilter,
            'rfcs' => $rfcs,
            'rfcGroups' => $rfcGroups,
            'archiveView' => $archiveView,
            'rfcSummary' => $rfcSummary,
            'eligibleAtps' => $eligibleAtps,
            'fundingGroups' => $fundingGroups,
            'atpPrefill' => $atpPrefill,
            'attachments' => $attachments,
            'selectedSource' => $selectedSource !== '' ? $selectedSource : null,
            'selectedFundingType' => $batchFundingType,
            'openCreate' => ($request->boolean('create') || $selectedSource !== '') && $batchRfcItems === [],
            'viewRfcId' => $viewRfcId ?: null,
            'fundingTypeLabels' => ProcurementPaymentPath::labels(),
            'savedSignatures' => ($loadFormDatasets || $viewRfcId > 0 || $batchRfcItems !== [])
                ? UserSignatureLibrary::forUser((int) auth()->id())
                : collect(),
            'rfcLineages' => $rfcLineages,
            'loadFormDatasets' => $loadFormDatasets,
            'batchRisId' => $batchRisId,
            'batchRfcItems' => $batchRfcItems,
            'batchFundingType' => $batchRfcItems !== [] ? $batchFundingType : null,
        ]);
    }

    public function store(Request $request)
    {
        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';
        $validated = $this->validateRfc($request, $isDraft);
        $fundingType = ProcurementPaymentPath::normalize(
            $validated['request_check_funding_type'] ?? null
        ) ?? ProcurementPaymentPath::DIRECT_PAYMENT;
        $requestedSig = RisWorkflow::normalizeDrawnSignature($validated['request_check_requested_by_signature'] ?? null);
        if (!$isDraft && !$requestedSig) {
            throw ValidationException::withMessages([
                'request_check_requested_by_signature' => 'Draw or upload your signature before submitting.',
            ]);
        }

        [$error, $atpIds, $poId] = $this->resolveFundingSelection($validated, $fundingType, null, !$isDraft);
        if ($error) {
            return back()->withInput()->with('error', $error);
        }
        // Soft-save first when ATP is still in preparation; joint submit runs after from this RFC endpoint.
        $pendingJointAtpId = null;
        if (! $isDraft) {
            $pendingJointAtpId = $this->firstUnsubmittedSoftDraftAtpId($atpIds);
            if ($pendingJointAtpId) {
                $isDraft = true;
            }
        }

        $id = DB::transaction(function () use ($request, $validated, $isDraft, $fundingType, $requestedSig, $atpIds, $poId) {
            $now = now();
            $user = auth()->user();
            $reviewerId = $isDraft
                ? null
                : ReviewerAssignment::resolve($request, \App\Support\ProcurementReviewPortal::ROLE);

            $payload = [
                'request_check_authority_purchase_id' => $atpIds[0] ?? null,
                'request_check_purchase_order_id' => $poId,
                'request_check_funding_type' => $fundingType,
                'request_check_date' => $validated['request_check_date'] ?? null,
                'request_check_payee' => $this->payeeForFunding($fundingType, $validated['request_check_payee'] ?? null),
                'request_check_amount_figures' => $validated['request_check_amount_figures'] ?? null,
                'request_check_amount_words' => $this->amountInWords($validated['request_check_amount_figures'] ?? null),
                'request_check_particulars_purpose' => $validated['request_check_particulars_purpose'] ?? null,
                'request_check_requested_by' => $validated['request_check_requested_by'] ?? ($user->user_full_name ?? null),
                'request_check_requested_by_signature' => $requestedSig,
                'request_check_requested_by_user_id' => auth()->id(),
                'request_check_status' => $this->rfcPersistStatus($isDraft ? 'Draft' : 'Submitted'),
                'request_check_review_stage' => $isDraft ? null : 'accounting',
                'request_check_submitted_by' => $isDraft ? null : auth()->id(),
                'request_check_submitted_at' => $isDraft ? null : $now,
                'request_check_is_archived' => 0,
                'request_check_created_at' => $now,
                'request_check_updated_at' => $now,
            ];
            if (! $isDraft && $this->rfcHas('request_check_assigned_reviewer_id')) {
                $payload['request_check_assigned_reviewer_id'] = $reviewerId;
            }
            if ($this->rfcHas('request_check_request_no')) {
                $requestNo = \App\Support\ProcurementRequestNumber::resolveFromAtp($atpIds[0] ?? null);
                if ($requestNo) {
                    $payload['request_check_request_no'] = $requestNo;
                }
            }

            $id = DB::table('request_check_table')->insertGetId($this->rfcPayload($payload));
            RfcAtpLinks::sync((int) $id, $atpIds);
            if ($this->rfcHas('request_check_form_number')) {
                DB::table('request_check_table')->where('request_check_id', $id)->update([
                    'request_check_form_number' => $isDraft ? null : RfcFormNumber::next(),
                ]);
            }

            $this->storeAttachments($request, $id);

            if (!$isDraft) {
                $this->notifyAccountingRfc($id, $reviewerId);
            }

            return (int) $id;
        });

        $fundParams = array_filter(['fund' => ProcurementPaymentPath::normalize($fundingType)]);

        if ($pendingJointAtpId) {
            return $this->delegateJointSubmitIfRequired([$pendingJointAtpId])
                ?? ProcurementPortal::redirect('rfc.index', $fundParams)->with(
                    'success',
                    ProcurementDraftLabel::flashSaved(ProcurementPaymentPath::label($fundingType))
                );
        }

        $fundLabel = ProcurementPaymentPath::label($fundingType);

        return ProcurementPortal::redirect('rfc.index', $fundParams)->with(
            'success',
            $isDraft
                ? ProcurementDraftLabel::flashSaved($fundLabel)
                : "{$fundLabel} submitted to the School Administrator."
        );
    }

    public function batchStore(Request $request)
    {
        $validated = $request->validate([
            'funding_type' => ['required', Rule::in(ProcurementPaymentPath::choices())],
            'request_check_requested_by' => ['nullable', 'string', 'max:255'],
            'request_check_requested_by_signature' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.atp_id' => ['required', 'integer'],
            'items.*.funding_type' => ['nullable', Rule::in(ProcurementPaymentPath::choices())],
            'items.*.payee' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.purpose' => ['required', 'string', 'max:1000'],
            'items.*.date' => ['required', 'date'],
        ]);

        $fundingType = ProcurementPaymentPath::normalize($validated['funding_type']) ?? ProcurementPaymentPath::CASH_ADVANCE;
        $requestedSig = RisWorkflow::normalizeDrawnSignature($validated['request_check_requested_by_signature'] ?? null);
        if (! $requestedSig) {
            return back()->with('error', 'Please draw or select your signature before submitting.');
        }

        $user = auth()->user();
        $requestedBy = trim((string) ($validated['request_check_requested_by'] ?? '')) ?: ($user->user_full_name ?? 'Purchaser');
        $atpIds = array_values(array_unique(array_map(fn ($i) => (int) $i['atp_id'], $validated['items'])));
        $alreadyFunded = RfcAtpLinks::fundedAtpIds($atpIds);

        $createdCount = 0;
        $createdByPath = [];

        DB::transaction(function () use ($request, $validated, $fundingType, $requestedSig, $requestedBy, $alreadyFunded, &$createdCount, &$createdByPath) {
            $now = now();
            $reviewerId = ReviewerAssignment::resolve($request, \App\Support\ProcurementReviewPortal::ROLE);

            foreach ($validated['items'] as $entry) {
                $atpId = (int) $entry['atp_id'];
                if (in_array($atpId, $alreadyFunded, true)) {
                    continue;
                }
                $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
                if (! $atp) {
                    continue;
                }
                PurchaserDocumentAccess::assertOwns($atp, 'atp');

                $itemFunding = ProcurementPaymentPath::normalize($entry['funding_type'] ?? null)
                    ?? ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path ?? null)
                    ?? $fundingType;

                $amt = round((float) $entry['amount'], 2);
                $payload = [
                    'request_check_authority_purchase_id' => $atpId,
                    'request_check_purchase_order_id' => null,
                    'request_check_funding_type' => $itemFunding,
                    'request_check_date' => $entry['date'],
                    'request_check_payee' => $this->payeeForFunding($itemFunding, $entry['payee']),
                    'request_check_amount_figures' => $amt,
                    'request_check_amount_words' => $this->amountInWords($amt),
                    'request_check_particulars_purpose' => trim((string) $entry['purpose']),
                    'request_check_requested_by' => $requestedBy,
                    'request_check_requested_by_signature' => $requestedSig,
                    'request_check_requested_by_user_id' => auth()->id(),
                    'request_check_status' => $this->rfcPersistStatus('Submitted'),
                    'request_check_review_stage' => 'accounting',
                    'request_check_submitted_by' => auth()->id(),
                    'request_check_submitted_at' => $now,
                    'request_check_is_archived' => 0,
                    'request_check_created_at' => $now,
                    'request_check_updated_at' => $now,
                ];
                if ($this->rfcHas('request_check_assigned_reviewer_id')) {
                    $payload['request_check_assigned_reviewer_id'] = $reviewerId;
                }
                if ($this->rfcHas('request_check_request_no')) {
                    $requestNo = \App\Support\ProcurementRequestNumber::resolveFromAtp($atpId);
                    if ($requestNo) {
                        $payload['request_check_request_no'] = $requestNo;
                    }
                }

                $id = (int) DB::table('request_check_table')->insertGetId($this->rfcPayload($payload));
                RfcAtpLinks::sync($id, [$atpId]);
                if ($this->rfcHas('request_check_form_number')) {
                    DB::table('request_check_table')->where('request_check_id', $id)->update([
                        'request_check_form_number' => RfcFormNumber::next(),
                    ]);
                }

                $this->notifyAccountingRfc($id, $reviewerId);
                $createdCount++;
                $createdByPath[$itemFunding] = ($createdByPath[$itemFunding] ?? 0) + 1;
            }
        });

        $primaryFund = array_key_first($createdByPath) ?: $fundingType;
        $fundParams = ['fund' => $primaryFund];

        if ($createdCount === 0) {
            return ProcurementPortal::redirect('rfc.index', $fundParams)
                ->with('error', 'No new Request Checks were created (the selected ATPs may already have funding requests).');
        }

        if (count($createdByPath) > 1) {
            $parts = [];
            foreach ($createdByPath as $p => $cnt) {
                $parts[] = $cnt.' '.ProcurementPaymentPath::label($p);
            }

            return ProcurementPortal::redirect('rfc.index', $fundParams)
                ->with('success', "{$createdCount} Request Checks submitted to the School Administrator (".implode(', ', $parts).').');
        }

        $fundLabel = ProcurementPaymentPath::label($primaryFund);

        return ProcurementPortal::redirect('rfc.index', $fundParams)
            ->with('success', "{$createdCount} {$fundLabel} request(s) submitted to the School Administrator.");
    }

    public function update(Request $request, $id)
    {
        $rfc = $this->findRfc($id);
        if (!$rfc) {
            return back()->with('error', 'Request for Check not found.');
        }

        if (!$this->isEditable($rfc)) {
            return back()->with('error', ProcurementDraftLabel::onlyEditableError('Request for Check records'));
        }

        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';
        // Edit forms may omit funding type; keep the stored value so draft saves (incl. signature) don't fail validation.
        if (!$request->filled('request_check_funding_type') && !empty($rfc->request_check_funding_type)) {
            $request->merge(['request_check_funding_type' => $rfc->request_check_funding_type]);
        }
        if (!$request->filled('request_check_authority_purchase_id') && !empty($rfc->request_check_authority_purchase_id)) {
            $request->merge(['request_check_authority_purchase_id' => $rfc->request_check_authority_purchase_id]);
        }
        try {
            $validated = $this->validateRfc($request, $isDraft);
        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('edit_rfc_id', (int) $id);
        }
        $fundingType = ProcurementPaymentPath::normalize(
            $validated['request_check_funding_type']
                ?? ($rfc->request_check_funding_type ?? null)
        ) ?? ProcurementPaymentPath::DIRECT_PAYMENT;
        $requestedSig = RisWorkflow::normalizeDrawnSignature($validated['request_check_requested_by_signature'] ?? null);
        if (!$isDraft && !$requestedSig && !RisWorkflow::isDrawnSignature((string) ($rfc->request_check_requested_by_signature ?? ''))) {
            throw ValidationException::withMessages([
                'request_check_requested_by_signature' => 'Draw or upload your signature before submitting.',
            ]);
        }

        [$error, $atpIds, $poId] = $this->resolveFundingSelection($validated, $fundingType, $rfc, !$isDraft);
        if ($error) {
            return back()->withInput()->with('error', $error)->with('edit_rfc_id', (int) $id);
        }

        $pendingJointAtpId = null;
        if (! $isDraft) {
            $pendingJointAtpId = $this->firstUnsubmittedSoftDraftAtpId($atpIds);
            if ($pendingJointAtpId) {
                $isDraft = true;
            }
        }

        DB::transaction(function () use ($request, $validated, $rfc, $isDraft, $id, $fundingType, $requestedSig, $atpIds, $poId) {
            $now = now();
            $reviewerId = $isDraft
                ? null
                : ReviewerAssignment::resolve($request, \App\Support\ProcurementReviewPortal::ROLE);
            $wasRevision = in_array($rfc->request_check_status, $this->rfcEditableStatuses(), true)
                && $rfc->request_check_status !== 'Draft'
                && $rfc->request_check_status !== 'Pending';
            $status = $isDraft
                ? $this->rfcPersistStatus($wasRevision ? 'Minor Revision' : 'Draft')
                : $this->rfcPersistStatus($wasRevision ? 'Resubmitted' : 'Submitted');

            $payload = [
                'request_check_authority_purchase_id' => $atpIds[0] ?? null,
                'request_check_purchase_order_id' => $poId,
                'request_check_funding_type' => $fundingType,
                'request_check_date' => $validated['request_check_date'] ?? null,
                'request_check_payee' => $this->payeeForFunding($fundingType, $validated['request_check_payee'] ?? null),
                'request_check_amount_figures' => $validated['request_check_amount_figures'] ?? null,
                'request_check_amount_words' => $this->amountInWords($validated['request_check_amount_figures'] ?? null),
                'request_check_particulars_purpose' => $validated['request_check_particulars_purpose'] ?? null,
                'request_check_requested_by' => $validated['request_check_requested_by'] ?? $rfc->request_check_requested_by,
                'request_check_requested_by_signature' => $requestedSig ?? ($rfc->request_check_requested_by_signature ?? null),
                'request_check_status' => $status,
                'request_check_review_stage' => $isDraft ? ($rfc->request_check_review_stage ?? null) : 'accounting',
                'request_check_submitted_by' => $isDraft ? ($rfc->request_check_submitted_by ?? null) : auth()->id(),
                'request_check_submitted_at' => $isDraft ? ($rfc->request_check_submitted_at ?? null) : $now,
                'request_check_updated_at' => $now,
            ];
            if ($this->rfcHas('request_check_form_number')) {
                $payload['request_check_form_number'] = $isDraft
                    ? ($wasRevision ? ($rfc->request_check_form_number ?? null) : null)
                    : RfcFormNumber::allocateOnSubmit($rfc->request_check_form_number ?? null);
            }
            if (! $isDraft && $this->rfcHas('request_check_assigned_reviewer_id')) {
                $payload['request_check_assigned_reviewer_id'] = $reviewerId;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update($this->rfcPayload($payload));
            RfcAtpLinks::sync((int) $id, $atpIds);

            $this->deleteRequestedAttachments($request, $id);
            $this->storeAttachments($request, $id);

            if (!$isDraft) {
                $this->notifyAccountingRfc($id, $reviewerId);
            }
        });

        $fundParams = array_filter(['fund' => ProcurementPaymentPath::normalize($fundingType)]);

        if ($pendingJointAtpId) {
            return $this->delegateJointSubmitIfRequired([$pendingJointAtpId])
                ?? ProcurementPortal::redirect('rfc.index', $fundParams)->with(
                    'success',
                    ProcurementDraftLabel::flashUpdated(ProcurementPaymentPath::label($fundingType))
                );
        }

        $fundLabel = ProcurementPaymentPath::label($fundingType);

        return ProcurementPortal::redirect('rfc.index', $fundParams)->with(
            'success',
            $isDraft
                ? ProcurementDraftLabel::flashUpdated($fundLabel)
                : "{$fundLabel} submitted to the School Administrator."
        );
    }

    public function submit($id)
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $id)->first();
        if (! $rfc) {
            return back()->with('error', 'Request for Check not found.');
        }
        PurchaserDocumentAccess::assertOwns($rfc, 'rfc');
        if (! $this->isEditable($rfc)) {
            return back()->with('error', 'This Request for Check cannot be submitted.');
        }

        // Purchaser flow: RFC is the submit endpoint — send ATP + funding request together.
        $atpIdsForGate = RfcAtpLinks::atpIdsFor(
            (int) $id,
            (int) ($rfc->request_check_authority_purchase_id ?: 0) ?: null
        );
        if ($joint = $this->delegateJointSubmitIfRequired($atpIdsForGate)) {
            return $joint;
        }

        $reviewerId = ReviewerAssignment::resolve(request(), \App\Support\ProcurementReviewPortal::ROLE);

        return DB::transaction(function () use ($id, $reviewerId) {
            $rfc = DB::table('request_check_table')->where('request_check_id', $id)->lockForUpdate()->first();
            if (!$rfc) {
                return back()->with('error', 'Request for Check not found.');
            }
            PurchaserDocumentAccess::assertOwns($rfc, 'rfc');
            if (!$this->isEditable($rfc)) {
                return back()->with('error', 'This Request for Check cannot be submitted.');
            }
            $missing = [];
            if (!$rfc->request_check_authority_purchase_id) {
                $missing[] = 'link an approved ATP';
            }
            if (blank($rfc->request_check_payee)) {
                $missing[] = 'payee';
            }
            if ((float) $rfc->request_check_amount_figures <= 0) {
                $missing[] = 'amount';
            }
            if (blank($rfc->request_check_particulars_purpose)) {
                $missing[] = 'purpose';
            }
            if (
                $this->rfcHas('request_check_requested_by_signature')
                && !RisWorkflow::isDrawnSignature((string) ($rfc->request_check_requested_by_signature ?? ''))
            ) {
                $missing[] = 'signature (open Edit → add signature → Save Changes)';
            }
            if ($missing !== []) {
                return back()->with(
                    'error',
                    'Cannot submit yet. Please complete: ' . implode(', ', $missing) . '.'
                );
            }
            $fundingType = ProcurementPaymentPath::normalize($rfc->request_check_funding_type ?? null)
                ?? ProcurementPaymentPath::DIRECT_PAYMENT;
            $atpIds = RfcAtpLinks::atpIdsFor((int) $id, (int) $rfc->request_check_authority_purchase_id ?: null);
            if ($error = $this->atpSetEligibilityError($atpIds, (int) $id, true, $fundingType)) {
                return back()->with('error', $error);
            }

            $wasRevision = $rfc->request_check_status === 'Minor Revision';

            $payload = [
                'request_check_status' => $this->rfcPersistStatus($wasRevision ? 'Resubmitted' : 'Submitted'),
                'request_check_review_stage' => 'accounting',
                'request_check_submitted_by' => auth()->id(),
                'request_check_submitted_at' => now(),
                'request_check_updated_at' => now(),
            ];
            if ($this->rfcHas('request_check_form_number')) {
                $payload['request_check_form_number'] = RfcFormNumber::allocateOnSubmit($rfc->request_check_form_number ?? null);
            }
            if ($this->rfcHas('request_check_assigned_reviewer_id')) {
                $payload['request_check_assigned_reviewer_id'] = $reviewerId;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update($this->rfcPayload($payload));

            $this->notifyAccountingRfc($id, $reviewerId);

            return back()->with('success', 'Request for Check submitted to the School Administrator.');
        });
    }

    public function sendToAccounting(Request $request, $id)
    {
        $rfc = $this->findRfc($id);
        if (! $rfc) {
            return back()->with('error', 'Request for Check not found.');
        }

        PurchaserDocumentAccess::assertOwns($rfc, 'rfc');

        try {
            $accountingUserId = ReviewerAssignment::resolve($request, WorkflowNotifier::ROLE_ACCOUNTING);
            AccountingHandoff::sendRfcToAccounting($rfc, (int) Auth::id(), $accountingUserId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = $rfc->request_check_form_number ?: ('RFC #'.$id);
        $toName = WorkflowNotifier::recipientName($accountingUserId, 'Accounting');

        return back()->with(
            'success',
            $label.' (Canvass + RIS + ATP + RFC) was submitted to '.$toName.' for Disbursement Voucher.'
        );
    }

    public function archive($id)
    {
        $rfc = $this->findRfc($id);
        if (!$rfc) {
            return back()->with('error', 'Request for Check not found.');
        }
        if (!$this->rfcHas('request_check_is_archived')) {
            return back()->with('error', 'Archiving is not available for this Request for Check schema.');
        }
        if (!in_array($rfc->request_check_status, self::ARCHIVEABLE_STATUSES, true)) {
            return back()->with('error', 'Only approved or rejected Request for Check records can be archived.');
        }

        if ($rfc->request_check_status === 'Approved') {
            $hasRr = DB::table('receiving_reports_table')
                ->where('receiving_report_request_check_id', $id)
                ->exists();
            if (! $hasRr) {
                return back()->with('error', 'Finish the funding handoff and create the Receiving Report before archiving.');
            }
        }

        DocumentWorkflowService::setArchived(
            'request_check_table',
            'request_check_id',
            $id,
            'request_check_is_archived',
            'request_check_updated_at',
            true
        );

        return back()->with('success', 'Request for Check archived.');
    }

    public function restore($id)
    {
        $rfc = $this->findRfc($id);
        if (!$rfc) {
            return back()->with('error', 'Request for Check not found.');
        }

        if (!$this->rfcHas('request_check_is_archived')) {
            return back()->with('error', 'Archiving is not available for this Request for Check schema.');
        }

        DocumentWorkflowService::setArchived(
            'request_check_table',
            'request_check_id',
            $id,
            'request_check_is_archived',
            'request_check_updated_at',
            false
        );

        return back()->with('success', 'Request for Check restored.');
    }

    public function downloadAttachment($id, $attachmentId)
    {
        $rfc = $this->findRfc($id);
        abort_if(!$rfc, 404);

        $attachment = DB::table('request_check_attachments_table')
            ->where('request_check_attachment_id', $attachmentId)
            ->where('request_check_id', $id)
            ->first();

        abort_if(!$attachment, 404);

        $path = storage_path('app/public/' . $attachment->request_check_attachment_path);
        abort_if(!is_file($path), 404);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $attachment->request_check_attachment_original_name . '"',
        ]);
    }

    private function validateRfc(Request $request, bool $isDraft): array
    {
        return $request->validate([
            'save_action' => ['required', 'in:draft,submit'],
            'request_check_funding_type' => [
                'required',
                Rule::in(ProcurementPaymentPath::choices()),
            ],
            'request_check_authority_purchase_id' => [
                'nullable',
                'integer',
                'exists:authority_to_purchase_table,authority_purchase_id',
            ],
            'request_check_funding_source' => ['nullable', 'string', 'max:60'],
            'request_check_date' => [$isDraft ? 'nullable' : 'required', 'date'],
            'request_check_payee' => [$isDraft ? 'nullable' : 'required', 'string', 'max:'.\App\Support\InputLimits::NAME],
            'request_check_amount_figures' => [$isDraft ? 'nullable' : 'required', 'numeric', $isDraft ? 'min:0' : 'gt:0', 'max:'.\App\Support\InputLimits::COST],
            'request_check_particulars_purpose' => [$isDraft ? 'nullable' : 'required', 'string', 'max:'.\App\Support\InputLimits::PURPOSE],
            'request_check_requested_by' => [$isDraft ? 'nullable' : 'required', 'string', 'max:'.\App\Support\InputLimits::NAME],
            'request_check_requested_by_signature' => [$isDraft ? 'nullable' : 'required', 'string', 'max:2000000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'delete_attachments' => ['nullable', 'array'],
            'delete_attachments.*' => ['integer'],
        ]);
    }

    private function storeAttachments(Request $request, $rfcId): void
    {
        if (!$request->hasFile('attachments') || !Schema::hasTable('request_check_attachments_table')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            if (!$file) {
                continue;
            }
            $path = $file->store('request-check/' . $rfcId, 'public');
            DB::table('request_check_attachments_table')->insert([
                'request_check_id' => $rfcId,
                'request_check_attachment_original_name' => $file->getClientOriginalName(),
                'request_check_attachment_path' => $path,
                'request_check_attachment_mime_type' => $file->getClientMimeType(),
                'request_check_attachment_size' => $file->getSize(),
                'request_check_attachment_uploaded_by' => auth()->id(),
                'request_check_attachment_created_at' => now(),
            ]);
        }
    }

    private function deleteRequestedAttachments(Request $request, $rfcId): void
    {
        $ids = collect($request->input('delete_attachments', []))->filter();
        if ($ids->isEmpty()) {
            return;
        }

        $rows = DB::table('request_check_attachments_table')
            ->where('request_check_id', $rfcId)
            ->whereIn('request_check_attachment_id', $ids)
            ->get();

        foreach ($rows as $row) {
            Storage::disk('public')->delete($row->request_check_attachment_path);
        }

        DB::table('request_check_attachments_table')
            ->where('request_check_id', $rfcId)
            ->whereIn('request_check_attachment_id', $ids)
            ->delete();
    }

    private function rfcBaseQuery()
    {
        $query = DB::table('request_check_table')
            ->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
            );

        $select = [
            'request_check_table.*',
            'authority_to_purchase_table.authority_purchase_id as linked_atp_id',
            'authority_to_purchase_table.authority_purchase_form_number',
            'authority_to_purchase_table.authority_purchase_payment_path',
            'authority_to_purchase_table.authority_purchase_status as atp_status',
            'authority_to_purchase_table.authority_purchase_submitted_at as atp_submitted_at',
        ];

        if ($this->rfcHas('request_check_assigned_reviewer_id') && Schema::hasTable('users_table')) {
            $query->leftJoin(
                'users_table as rfc_reviewers',
                'rfc_reviewers.user_id',
                '=',
                'request_check_table.request_check_assigned_reviewer_id'
            );
            $select[] = 'rfc_reviewers.user_full_name as assigned_reviewer_name';
        }

        if (Schema::hasTable('receiving_reports_table')
            && $this->rfcHas('request_check_receiving_report_id')
        ) {
            $query->leftJoin(
                'receiving_reports_table',
                'request_check_table.request_check_receiving_report_id',
                '=',
                'receiving_reports_table.receiving_report_id'
            );
            $select[] = 'receiving_reports_table.receiving_report_form_number';
            $select[] = 'receiving_reports_table.receiving_report_status';
        }

        return $query->select($select);
    }

    private function applyPurchaserOwnership($query)
    {
        return PurchaserDocumentAccess::scopeOwned($query, 'rfc', 'request_check_table');
    }

    public static function reviewBaseQuery()
    {
        return DB::table('request_check_table')
            ->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
            )
            ->select(
                'request_check_table.*',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_status as atp_status'
            );
    }

    private function eligibleAtpQuery(?string $fundingType = null)
    {
        $fundingType = ProcurementPaymentPath::normalize($fundingType) ?? ProcurementPaymentPath::DIRECT_PAYMENT;

        return DB::table('authority_to_purchase_table')
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
            ->when(
                \App\Support\ProcurementPortal::PURCHASER_OWNS_ATP,
                function ($q) {
                    // Purchaser may draft RFC against owned Draft/Pending ATP (pre-SA) or Approved.
                    $q->where(function ($inner) {
                        $inner->whereIn('authority_to_purchase_table.authority_purchase_status', ['Draft', 'Pending', 'Approved'])
                            ->where(function ($owned) {
                                $uid = (int) (\Illuminate\Support\Facades\Auth::id() ?? 0);
                                $owned->where('authority_to_purchase_table.authority_purchase_created_by', $uid)
                                    ->orWhere(function ($legacy) use ($uid) {
                                        $legacy->whereNull('authority_to_purchase_table.authority_purchase_created_by')
                                            ->where(function ($sub) use ($uid) {
                                                $sub->where('authority_to_purchase_table.authority_purchase_submitted_by', $uid)
                                                    ->orWhereNull('authority_to_purchase_table.authority_purchase_submitted_by');
                                            });
                                    });
                            });
                    });
                },
                function ($q) {
                    $q->where('authority_to_purchase_table.authority_purchase_status', 'Approved')
                        ->when(
                            \App\Support\SupplyHandoff::atpReleaseColumnsReady(),
                            fn ($qq) => \App\Support\SupplyHandoff::scopeReleasedAtpsForPurchaser($qq)
                        );
                }
            )
            ->when(
                Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_payment_path'),
                fn ($q) => $q->whereIn(
                    'authority_to_purchase_table.authority_purchase_payment_path',
                    ProcurementPaymentPath::storedValuesForFilter($fundingType)
                )
            )
            ->where(function ($q) {
                $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                    ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('request_check_table')
                    ->whereColumn(
                        'request_check_table.request_check_authority_purchase_id',
                        'authority_to_purchase_table.authority_purchase_id'
                    )
                    ->where('request_check_status', '!=', 'Rejected');
                $this->applyUnarchivedRfcConstraint($q);
            })
            ->when(RfcAtpLinks::tableExists(), function ($query) {
                $query->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('request_check_atps_table')
                        ->join('request_check_table', 'request_check_table.request_check_id', '=', 'request_check_atps_table.request_check_id')
                        ->whereColumn(
                            'request_check_atps_table.authority_purchase_id',
                            'authority_to_purchase_table.authority_purchase_id'
                        )
                        ->where('request_check_table.request_check_status', '!=', 'Rejected');
                    $this->applyUnarchivedRfcConstraint($q);
                });
            })
            ->when(PurchaseOrderBasket::tablesExist(), function ($query) {
                $query->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('purchase_order_atps_table')
                        ->join('purchase_orders_table', 'purchase_orders_table.purchase_order_id', '=', 'purchase_order_atps_table.purchase_order_id')
                        ->whereColumn(
                            'purchase_order_atps_table.authority_purchase_id',
                            'authority_to_purchase_table.authority_purchase_id'
                        )
                        ->where('purchase_orders_table.purchase_order_status', PurchaseOrderBasket::STATUS_APPROVED);
                });
            })
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_payment_path',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name',
                'suppliers_table.supplier_store_type',
                'suppliers_table.supplier_check_name',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_purpose_description',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name'
            )
            ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
            ->limit(50);
    }

    private function buildAtpPrefill($eligibleAtps, ?string $fundingType = null): array
    {
        $prefill = [];
        $ids = collect($eligibleAtps)->pluck('authority_purchase_id');
        $cashAdvance = ProcurementPaymentPath::requiresLiquidation($fundingType);
        $purchaserName = trim((string) (auth()->user()->user_full_name ?? ''));

        $totals = DB::table('authority_to_purchase_items_table')
            ->whereIn('authority_purchase_id', $ids)
            ->select('authority_purchase_id', DB::raw('SUM(atp_amount) as total'))
            ->groupBy('authority_purchase_id')
            ->pluck('total', 'authority_purchase_id');

        foreach ($eligibleAtps as $atp) {
            $isCashAdvance = $cashAdvance
                || ProcurementPaymentPath::requiresLiquidation($atp->authority_purchase_payment_path ?? null);
            $payee = $isCashAdvance
                ? $purchaserName
                : PurchaseOrderBasket::checkPayee($atp);

            $purpose = $atp->equipment_name
                ?? $atp->report_unlisted_equipment_name
                ?? $atp->ris_purpose_description
                ?? $atp->ris_manual_title
                ?? '';

            $prefill[(string) $atp->authority_purchase_id] = [
                'payee' => $payee,
                'amount' => $totals[$atp->authority_purchase_id] ?? '',
                'purpose' => $purpose,
            ];
        }

        return $prefill;
    }

    private function payeeForFunding(string $fundingType, ?string $posted): ?string
    {
        if (! ProcurementPaymentPath::requiresLiquidation($fundingType)) {
            return $posted;
        }

        $name = trim((string) (auth()->user()->user_full_name ?? ''));

        return $name !== '' ? $name : $posted;
    }

    private function attachLinkedAtps($rfcs): void
    {
        $rfcs = collect($rfcs);
        if ($rfcs->isEmpty()) {
            return;
        }

        $linkMap = RfcAtpLinks::atpIdsForMany(
            $rfcs->mapWithKeys(fn ($rfc) => [(int) $rfc->request_check_id => (int) ($rfc->request_check_authority_purchase_id ?? 0) ?: null])->all()
        );
        $allAtpIds = collect($linkMap)->flatten()->unique()->values();
        $atpNumbers = $allAtpIds->isEmpty()
            ? collect()
            : DB::table('authority_to_purchase_table')
                ->whereIn('authority_purchase_id', $allAtpIds)
                ->pluck('authority_purchase_form_number', 'authority_purchase_id');

        $poIds = $rfcs->pluck('request_check_purchase_order_id')->filter()->unique()->values();
        $orders = $poIds->isEmpty() || !PurchaseOrderBasket::tablesExist()
            ? collect()
            : DB::table('purchase_orders_table')->whereIn('purchase_order_id', $poIds)->get()->keyBy('purchase_order_id');

        foreach ($rfcs as $rfc) {
            $ids = $linkMap[(int) $rfc->request_check_id] ?? [];
            $rfc->linked_atp_ids = $ids;
            $rfc->linked_atp_labels = array_map(
                fn ($atpId) => filled($atpNumbers[$atpId] ?? null) ? (string) $atpNumbers[$atpId] : 'ATP —',
                $ids
            );
            $order = $orders->get((int) ($rfc->request_check_purchase_order_id ?? 0));
            $rfc->purchase_order_label = $order ? PurchaseOrderBasket::displayNumber($order) : null;
        }
    }

    private function resolveFundingType(Request $request): string
    {
        $type = (string) $request->query('funding_type', $request->query('fund', ProcurementPaymentPath::DIRECT_PAYMENT));
        $normalized = ProcurementPaymentPath::normalize($type);

        return $normalized ?? ProcurementPaymentPath::DIRECT_PAYMENT;
    }

    private function applyFundFilter($query, ?string $fund): void
    {
        if (!$fund || !$this->rfcHas('request_check_funding_type')) {
            return;
        }

        $values = ProcurementPaymentPath::storedValuesForFilter($fund);

        $query->where(function ($sub) use ($fund, $values) {
            $sub->whereIn('request_check_table.request_check_funding_type', $values);
            if (ProcurementPaymentPath::normalize($fund) === ProcurementPaymentPath::DIRECT_PAYMENT) {
                $sub->orWhereNull('request_check_table.request_check_funding_type')
                    ->orWhere('request_check_table.request_check_funding_type', '');
            }
        });
    }

    private function rfcStatusSummary(?string $fund = null): array
    {
        $select = ['request_check_status', DB::raw('COUNT(*) as aggregate')];
        $groupBy = ['request_check_status'];
        if ($this->rfcHas('request_check_is_archived')) {
            $select[] = 'request_check_is_archived';
            $groupBy[] = 'request_check_is_archived';
        }

        $countsQuery = DB::table('request_check_table')
            ->select($select)
            ->groupBy($groupBy);
        $this->applyFundFilter($countsQuery, $fund);
        $counts = $countsQuery->get();

        $summary = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0,
            'archived' => 0,
        ];
        $submitted = ['Submitted', 'Under Review', 'Resubmitted', 'Pending Admin Approval', 'Pending'];

        foreach ($counts as $row) {
            $count = (int) $row->aggregate;
            if ((int) ($row->request_check_is_archived ?? 0) === 1) {
                $summary['archived'] += $count;
                continue;
            }
            $summary['total'] += $count;
            if ($row->request_check_status === 'Draft') {
                $summary['draft'] += $count;
            } elseif (in_array($row->request_check_status, $submitted, true)) {
                $summary['submitted'] += $count;
            } elseif ($row->request_check_status === 'Approved') {
                $summary['approved'] += $count;
            } elseif ($row->request_check_status === 'Rejected') {
                $summary['rejected'] += $count;
            }
        }

        return $summary;
    }

    /**
     * Soft-draft ATP must be submitted with its funding request(s).
     * RFC is the Purchaser UX endpoint — delegate to ATP joint submit.
     *
     * @param  array<int, int>  $atpIds
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|null
     */
    private function delegateJointSubmitIfRequired(array $atpIds)
    {
        if (! ProcurementPortal::PURCHASER_OWNS_ATP || ProcurementPortal::isSupplyOfficerContext()) {
            return null;
        }

        $atpId = $this->firstUnsubmittedSoftDraftAtpId($atpIds);
        if (! $atpId) {
            return null;
        }

        $response = app(AuthorityToPurchaseController::class)->submit($atpId);
        if (! $response instanceof \Illuminate\Http\RedirectResponse) {
            return $response;
        }

        $error = $response->getSession()->get('error');
        if ($error) {
            return $response;
        }

        $fund = ProcurementPaymentPath::normalize($this->requestFundingType());

        return ProcurementPortal::redirect('rfc.index', array_filter([
            'fund' => $fund,
        ]))->with(
            'success',
            $response->getSession()->get('success') ?: 'Submitted to the School Administrator.'
        );
    }

    private function requestFundingType(): ?string
    {
        $posted = request()->input('request_check_funding_type');
        if (is_string($posted) && $posted !== '') {
            return $posted;
        }

        $query = request()->query('fund');

        return is_string($query) && $query !== '' ? $query : null;
    }

    /**
     * @param  object|null  $rfc  Row may include atp_submitted_at / linked_atp_id from the list query.
     */
    private function mustUseJointAtpSubmit(?object $rfc): bool
    {
        if (! $rfc || ! ProcurementPortal::PURCHASER_OWNS_ATP || ProcurementPortal::isSupplyOfficerContext()) {
            return false;
        }

        if (! in_array((string) ($rfc->request_check_status ?? ''), $this->rfcEditableStatuses(), true)) {
            return false;
        }

        return $this->jointSubmitAtpId($rfc) !== null;
    }

    private function jointSubmitAtpId(?object $rfc): ?int
    {
        if (! $rfc || ! ProcurementPortal::PURCHASER_OWNS_ATP) {
            return null;
        }

        // Prefer columns from the list join when present.
        $joinedAtpId = (int) ($rfc->linked_atp_id ?? $rfc->request_check_authority_purchase_id ?? 0);
        if (
            $joinedAtpId > 0
            && array_key_exists('atp_submitted_at', (array) $rfc)
            && blank($rfc->atp_submitted_at ?? null)
            && in_array((string) ($rfc->atp_status ?? ''), ['Draft', 'Pending'], true)
        ) {
            return $joinedAtpId;
        }

        $atpIds = RfcAtpLinks::atpIdsFor(
            (int) ($rfc->request_check_id ?? 0),
            (int) ($rfc->request_check_authority_purchase_id ?? 0) ?: null
        );

        return $this->firstUnsubmittedSoftDraftAtpId($atpIds);
    }

    /**
     * @param  array<int, int>  $atpIds
     */
    private function firstUnsubmittedSoftDraftAtpId(array $atpIds): ?int
    {
        $atpIds = array_values(array_unique(array_filter(array_map('intval', $atpIds))));
        if ($atpIds === []) {
            return null;
        }

        $row = DB::table('authority_to_purchase_table')
            ->whereIn('authority_purchase_id', $atpIds)
            ->whereIn('authority_purchase_status', ['Draft', 'Pending'])
            ->whereNull('authority_purchase_submitted_at')
            ->orderBy('authority_purchase_id')
            ->first(['authority_purchase_id']);

        return $row ? (int) $row->authority_purchase_id : null;
    }

    private function atpEligibilityError($atpId, $ignoreRfcId, bool $required, ?string $fundingType = null): ?string
    {
        if (!$atpId) {
            return $required ? 'Complete payee, amount, purpose, and ATP before submitting.' : null;
        }

        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $atpId)
            ->first();

        if (! $atp || ! empty($atp->authority_purchase_is_archived)) {
            return 'Only an unarchived Authority to Purchase can be used for a funding request.';
        }

        $status = (string) ($atp->authority_purchase_status ?? '');
        if (\App\Support\ProcurementPortal::PURCHASER_OWNS_ATP) {
            if (! in_array($status, ['Draft', 'Pending', 'Approved'], true)) {
                return 'Only an in-preparation, pending, or approved Authority to Purchase can be used for a funding request.';
            }
            // Block RFC while ATP is already with SA (submitted Pending) — except Approved.
            if (
                $status === 'Pending'
                && filled($atp->authority_purchase_submitted_at ?? null)
            ) {
                return 'This ATP is already with the School Administrator. Wait for approval before changing the funding request.';
            }
        } elseif ($status !== 'Approved') {
            return 'Only an approved, unarchived Authority to Purchase can be used for a funding request.';
        }

        $fundingType = ProcurementPaymentPath::normalize($fundingType) ?? ProcurementPaymentPath::DIRECT_PAYMENT;

        if (
            Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_payment_path')
            && blank($atp->authority_purchase_payment_path ?? null)
        ) {
            return 'Choose a payment path (Direct Payment, Cash Installment, or Cash Advance) on the ATP before creating a funding request.';
        }

        if (
            Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_payment_path')
            && ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path ?? null) !== $fundingType
        ) {
            return 'Funding type must match the payment path chosen on the ATP (' . ProcurementPaymentPath::label($atp->authority_purchase_payment_path) . ').';
        }

        if ($this->hasBlockingRfcForAtp($atpId, $ignoreRfcId)) {
            return 'A funding request already exists for the selected ATP.';
        }

        return null;
    }

    private function hasBlockingRfcForAtp($atpId, $ignoreId = null): bool
    {
        if (!$atpId) {
            return false;
        }

        return RfcAtpLinks::fundedAtpIds([(int) $atpId], $ignoreId ? (int) $ignoreId : null) !== [];
    }

    /**
     * Resolve which ATPs a funding request covers.
     * Source is "atp:{id}" for a standalone ATP or a Purchase Order group key ("po:{id}" / "po:{id}:s:{supplierId}").
     *
     * @return array{0: ?string, 1: array<int, int>, 2: ?int} [error, atpIds, purchaseOrderId]
     */
    private function resolveFundingSelection(array $validated, string $fundingType, ?object $rfc, bool $required): array
    {
        $ignoreId = $rfc ? (int) $rfc->request_check_id : null;
        $source = trim((string) ($validated['request_check_funding_source'] ?? ''));
        $postedAtpId = (int) ($validated['request_check_authority_purchase_id'] ?? 0);

        if (
            $source === ''
            && $rfc
            && ($postedAtpId === 0 || $postedAtpId === (int) $rfc->request_check_authority_purchase_id)
        ) {
            $atpIds = RfcAtpLinks::atpIdsFor((int) $rfc->request_check_id, (int) $rfc->request_check_authority_purchase_id ?: null);
            $poId = isset($rfc->request_check_purchase_order_id) ? ((int) $rfc->request_check_purchase_order_id ?: null) : null;

            return [$this->atpSetEligibilityError($atpIds, $ignoreId, $required, $fundingType), $atpIds, $poId];
        }

        if ($source === '' && $postedAtpId > 0) {
            $source = 'atp:'.$postedAtpId;
        }

        if ($source === '') {
            return [$required ? 'Complete payee, amount, purpose, and ATP before submitting.' : null, [], null];
        }

        if (PurchaseOrderFunding::isGroupKey($source)) {
            $group = PurchaseOrderFunding::resolveGroup($source, $fundingType, $ignoreId);
            if (!$group) {
                return [
                    'That Purchase Order group is no longer available for a '.ProcurementPaymentPath::label($fundingType).'. Refresh and choose again.',
                    [],
                    null,
                ];
            }

            return [
                $this->atpSetEligibilityError($group['atp_ids'], $ignoreId, $required, $fundingType),
                $group['atp_ids'],
                $group['purchase_order_id'],
            ];
        }

        if (preg_match('/^atp:(\d+)$/', $source, $m)) {
            $atpId = (int) $m[1];
            $unchanged = $rfc && (int) $rfc->request_check_authority_purchase_id === $atpId;
            if (!$unchanged && PurchaseOrderFunding::approvedPoIdForAtp($atpId)) {
                return ['This ATP was approved through a Purchase Order. Choose its Purchase Order group instead.', [], null];
            }

            return [$this->atpEligibilityError($atpId, $ignoreId, $required, $fundingType), [$atpId], null];
        }

        return ['Choose a valid ATP or Purchase Order to fund.', [], null];
    }

    /**
     * @param  array<int, int>  $atpIds
     */
    private function atpSetEligibilityError(array $atpIds, $ignoreRfcId, bool $required, string $fundingType): ?string
    {
        if ($atpIds === []) {
            return $required ? 'Complete payee, amount, purpose, and ATP before submitting.' : null;
        }

        foreach ($atpIds as $atpId) {
            if ($error = $this->atpEligibilityError($atpId, $ignoreRfcId, true, $fundingType)) {
                return count($atpIds) > 1
                    ? PurchaseOrderBasket::atpListLabel(DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first()).': '.$error
                    : $error;
            }
        }

        return PurchaseOrderFunding::groupConsistencyError($atpIds, $fundingType);
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'Draft') {
            $query->where('request_check_table.request_check_status', 'Draft');
            return;
        }
        if ($status === 'Submitted') {
            $query->whereIn('request_check_table.request_check_status', $this->rfcSubmittedFilterStatuses());
            return;
        }
        if ($status === 'Ready for Pickup') {
            $query->where('request_check_table.request_check_status', 'Approved');
            if (Schema::hasColumn('request_check_table', 'request_check_check_ready_at')) {
                $query->whereNotNull('request_check_table.request_check_check_ready_at');
            }
            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $query->whereNull('request_check_table.request_check_funds_released_at');
            }
            return;
        }
        if ($status === 'Released') {
            $query->where('request_check_table.request_check_status', 'Approved');
            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $query->whereNotNull('request_check_table.request_check_funds_released_at');
            }
            return;
        }
        if ($status === 'Approved') {
            $query->where('request_check_table.request_check_status', 'Approved');
            if (Schema::hasColumn('request_check_table', 'request_check_check_ready_at')) {
                $query->whereNull('request_check_table.request_check_check_ready_at');
            }
            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $query->whereNull('request_check_table.request_check_funds_released_at');
            }
            return;
        }
        $query->where('request_check_table.request_check_status', $status);
    }

    private function attachmentsFor($ids)
    {
        if ($ids->isEmpty() || !Schema::hasTable('request_check_attachments_table')) {
            return collect();
        }

        return DB::table('request_check_attachments_table')
            ->whereIn('request_check_id', $ids)
            ->orderBy('request_check_attachment_id')
            ->get()
            ->groupBy('request_check_id');
    }

    private function findRfc($id)
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $id)->first();
        if ($rfc) {
            PurchaserDocumentAccess::assertOwns($rfc, 'rfc');
        }

        return $rfc;
    }

    private function isEditable($rfc): bool
    {
        return DocumentWorkflowService::isEditable(
            $rfc,
            'request_check_status',
            $this->rfcEditableStatuses(),
            'request_check_is_archived'
        );
    }

    private function rfcHas(string $column): bool
    {
        return Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', $column);
    }

    private function rfcPayload(array $payload): array
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if ($this->rfcHas($column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }

    private function rfcSortColumn(): string
    {
        if ($this->rfcHas('request_check_created_at')) {
            return 'request_check_table.request_check_created_at';
        }

        return 'request_check_table.request_check_date';
    }

    private function applyArchiveFilter($query, bool $archiveView): void
    {
        if (!$this->rfcHas('request_check_is_archived')) {
            return;
        }

        DocumentWorkflowService::applyArchiveFilter(
            $query,
            'request_check_table.request_check_is_archived',
            $archiveView
        );
    }

    private function applyUnarchivedRfcConstraint($query): void
    {
        if (!$this->rfcHas('request_check_is_archived')) {
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('request_check_is_archived')
                ->orWhere('request_check_is_archived', 0);
        });
    }

    private function rfcStatusAllowed(string $status): bool
    {
        $values = $this->rfcStatusEnum();

        return $values === [] || in_array($status, $values, true);
    }

    private function rfcStatusEnum(): array
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

        return $values;
    }

    private function rfcPersistStatus(string $intended): string
    {
        if ($this->rfcStatusAllowed($intended)) {
            return $intended;
        }

        if (
            in_array($intended, ['Draft', 'Submitted', 'Under Review', 'Resubmitted', 'Pending Admin Approval'], true)
            && $this->rfcStatusAllowed('Pending')
        ) {
            return 'Pending';
        }

        if ($intended === 'Minor Revision' && $this->rfcStatusAllowed('Rejected')) {
            return 'Rejected';
        }

        return $intended;
    }

    private function rfcEditableStatuses(): array
    {
        $wanted = self::EDITABLE_STATUSES;
        if ($this->rfcStatusAllowed('Pending')) {
            $wanted[] = 'Pending';
        }
        if (!$this->rfcStatusAllowed('Minor Revision') && $this->rfcStatusAllowed('Rejected')) {
            $wanted[] = 'Rejected';
        }
        $allowed = array_values(array_filter($wanted, fn ($status) => $this->rfcStatusAllowed($status)));

        return $allowed !== [] ? $allowed : $wanted;
    }

    private function rfcSubmittedFilterStatuses(): array
    {
        $wanted = ['Submitted', 'Under Review', 'Resubmitted', 'Pending Admin Approval', 'Pending'];
        $allowed = array_values(array_filter($wanted, fn ($status) => $this->rfcStatusAllowed($status)));

        return $allowed !== [] ? $allowed : $wanted;
    }

    private function amountInWords($amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $number = (float) $amount;
        $pesos = (int) floor($number);
        $cents = (int) round(($number - $pesos) * 100);
        $words = $this->convertNumberToWords($pesos) . ' Pesos';
        if ($cents > 0) {
            $words .= ' and ' . $this->convertNumberToWords($cents) . ' Centavos';
        }

        return $words . ' Only';
    }

    private function convertNumberToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $chunk = function (int $n) use ($ones, $tens): string {
            $parts = [];
            if ($n >= 100) {
                $parts[] = $ones[(int) floor($n / 100)] . ' Hundred';
                $n %= 100;
            }
            if ($n >= 20) {
                $parts[] = $tens[(int) floor($n / 10)] . ($n % 10 ? '-' . $ones[$n % 10] : '');
            } elseif ($n > 0) {
                $parts[] = $ones[$n];
            }
            return implode(' ', $parts);
        };

        $scales = ['', 'Thousand', 'Million', 'Billion'];
        $parts = [];
        $scale = 0;
        while ($number > 0) {
            $n = $number % 1000;
            if ($n) {
                $parts[] = trim($chunk($n) . ' ' . $scales[$scale]);
            }
            $number = (int) floor($number / 1000);
            $scale++;
        }

        return implode(' ', array_reverse($parts));
    }

    private function notifyAccountingRfc($id, ?int $reviewerId = null): void
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $id)->first();
        $ref = $rfc->request_check_form_number ?? ('RFC #' . $id);
        DocumentWorkflowService::notifySubmitted(
            \App\Support\ProcurementReviewPortal::ROLE,
            'Request Check submitted',
            $ref . ' is waiting for School Administrator review.',
            'rfc_submitted',
            'RFC',
            (int) $id,
            \App\Support\ProcurementReviewPortal::requestCheck((int) $id),
            $reviewerId
        );
    }

    public function exportBlankExcel(RfcFormExporter $exporter)
    {
        return $exporter->downloadExcel();
    }

    public function exportBlankWord(RfcFormExporter $exporter)
    {
        return $exporter->downloadWord();
    }

    public function exportExcel($id, RfcFormExporter $exporter)
    {
        $rfc = $this->findRfc($id);
        abort_if(!$rfc, 404);

        return $exporter->downloadExcel($rfc);
    }

    public function exportWord($id, RfcFormExporter $exporter)
    {
        $rfc = $this->findRfc($id);
        abort_if(!$rfc, 404);

        return $exporter->downloadWord($rfc);
    }
}
