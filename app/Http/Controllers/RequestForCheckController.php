<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Support\PurchaserDocumentAccess;
use App\Support\WorkflowNotifier;
use App\Services\DocumentWorkflowService;
use App\Services\RfcFormExporter;

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
            });
        }

        if ($request->filled('status')) {
            $this->applyStatusFilter($query, $request->status);
        }

        $dateFrom = $request->input('date_from', $request->input('date'));
        $dateTo = $request->input('date_to');

        if ($dateFrom) {
            $query->whereDate('request_check_table.request_check_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('request_check_table.request_check_date', '<=', $dateTo);
        }

        $spotlightQuery = clone $query;
        $rfcs = $query
            ->orderByDesc($this->rfcSortColumn())
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

        $rfcSummary = $this->rfcStatusSummary();
        $eligibleAtps = collect();
        $atpPrefill = [];
        if (!$request->ajax()) {
            $eligibleAtps = $this->eligibleAtpQuery()->get();
            $atpPrefill = $this->buildAtpPrefill($eligibleAtps);
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

            $rfcHasRr = $rrQuery
                ->pluck('receiving_report_request_check_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        foreach ($rfcs as $rfc) {
            $rfc->has_rr = in_array((int) $rfc->request_check_id, $rfcHasRr, true);
            $rfc->funds_released = !empty($rfc->request_check_funds_released_at ?? null);
        }

        return view('purchaser.request-check.index', [
            'rfcs' => $rfcs,
            'archiveView' => $archiveView,
            'rfcSummary' => $rfcSummary,
            'eligibleAtps' => $eligibleAtps,
            'atpPrefill' => $atpPrefill,
            'attachments' => $attachments,
            'selectedAtpId' => $request->query('selected_atp'),
            'openCreate' => $request->boolean('create') || $request->filled('selected_atp'),
            'viewRfcId' => $viewRfcId ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';
        $validated = $this->validateRfc($request, $isDraft);

        if ($error = $this->atpEligibilityError($validated['request_check_authority_purchase_id'] ?? null, null, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        return DB::transaction(function () use ($request, $validated, $isDraft) {
            $now = now();
            $user = auth()->user();

            $id = DB::table('request_check_table')->insertGetId($this->rfcPayload([
                'request_check_authority_purchase_id' => $validated['request_check_authority_purchase_id'] ?? null,
                'request_check_date' => $validated['request_check_date'] ?? null,
                'request_check_payee' => $validated['request_check_payee'] ?? null,
                'request_check_amount_figures' => $validated['request_check_amount_figures'] ?? null,
                'request_check_amount_words' => $this->amountInWords($validated['request_check_amount_figures'] ?? null),
                'request_check_particulars_purpose' => $validated['request_check_particulars_purpose'] ?? null,
                'request_check_requested_by' => $validated['request_check_requested_by'] ?? ($user->user_full_name ?? null),
                'request_check_requested_by_user_id' => auth()->id(),
                'request_check_status' => $this->rfcPersistStatus($isDraft ? 'Draft' : 'Submitted'),
                'request_check_review_stage' => $isDraft ? null : 'accounting',
                'request_check_submitted_by' => $isDraft ? null : auth()->id(),
                'request_check_submitted_at' => $isDraft ? null : $now,
                'request_check_is_archived' => 0,
                'request_check_created_at' => $now,
                'request_check_updated_at' => $now,
            ]));
            if ($this->rfcHas('request_check_form_number')) {
                DB::table('request_check_table')->where('request_check_id', $id)->update([
                    'request_check_form_number' => 'RFC-' . $now->format('Y') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT),
                ]);
            }

            $this->storeAttachments($request, $id);

            if (!$isDraft) {
                $this->notifyAccountingRfc($id);
            }

            return redirect()->route('purchaser.rfc.index')->with(
                'success',
                $isDraft ? 'Request for Check draft saved.' : 'Request for Check submitted to Accounting.'
            );
        });
    }

    public function update(Request $request, $id)
    {
        $rfc = $this->findRfc($id);
        if (!$rfc) {
            return back()->with('error', 'Request for Check not found.');
        }

        if (!$this->isEditable($rfc)) {
            return back()->with('error', 'Only draft or revision Request for Check records can be edited.');
        }

        $saveAction = $request->input('save_action', 'draft');
        $isDraft = $saveAction === 'draft';
        $validated = $this->validateRfc($request, $isDraft);

        $atpId = $validated['request_check_authority_purchase_id'] ?? $rfc->request_check_authority_purchase_id;
        if ($error = $this->atpEligibilityError($atpId, $id, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        return DB::transaction(function () use ($request, $validated, $rfc, $isDraft, $id) {
            $now = now();
            $wasRevision = in_array($rfc->request_check_status, $this->rfcEditableStatuses(), true)
                && $rfc->request_check_status !== 'Draft'
                && $rfc->request_check_status !== 'Pending';
            $status = $isDraft
                ? $this->rfcPersistStatus($wasRevision ? 'Minor Revision' : 'Draft')
                : $this->rfcPersistStatus($wasRevision ? 'Resubmitted' : 'Submitted');

            DB::table('request_check_table')->where('request_check_id', $id)->update($this->rfcPayload([
                'request_check_authority_purchase_id' => $validated['request_check_authority_purchase_id'] ?? $rfc->request_check_authority_purchase_id,
                'request_check_date' => $validated['request_check_date'] ?? null,
                'request_check_payee' => $validated['request_check_payee'] ?? null,
                'request_check_amount_figures' => $validated['request_check_amount_figures'] ?? null,
                'request_check_amount_words' => $this->amountInWords($validated['request_check_amount_figures'] ?? null),
                'request_check_particulars_purpose' => $validated['request_check_particulars_purpose'] ?? null,
                'request_check_requested_by' => $validated['request_check_requested_by'] ?? $rfc->request_check_requested_by,
                'request_check_status' => $status,
                'request_check_review_stage' => $isDraft ? ($rfc->request_check_review_stage ?? null) : 'accounting',
                'request_check_submitted_by' => $isDraft ? ($rfc->request_check_submitted_by ?? null) : auth()->id(),
                'request_check_submitted_at' => $isDraft ? ($rfc->request_check_submitted_at ?? null) : $now,
                'request_check_updated_at' => $now,
            ]));

            $this->deleteRequestedAttachments($request, $id);
            $this->storeAttachments($request, $id);

            if (!$isDraft) {
                $this->notifyAccountingRfc($id);
            }

            return redirect()->route('purchaser.rfc.index')->with(
                'success',
                $isDraft ? 'Request for Check draft updated.' : 'Request for Check submitted to Accounting.'
            );
        });
    }

    public function submit($id)
    {
        return DB::transaction(function () use ($id) {
            $rfc = DB::table('request_check_table')->where('request_check_id', $id)->lockForUpdate()->first();
            if (!$rfc) {
                return back()->with('error', 'Request for Check not found.');
            }
            PurchaserDocumentAccess::assertOwns($rfc, 'rfc');
            if (!$this->isEditable($rfc)) {
                return back()->with('error', 'This Request for Check cannot be submitted.');
            }
            if (
                !$rfc->request_check_authority_purchase_id
                || blank($rfc->request_check_payee)
                || (float) $rfc->request_check_amount_figures <= 0
                || blank($rfc->request_check_particulars_purpose)
            ) {
                return back()->with('error', 'Complete payee, amount, purpose, and ATP before submitting.');
            }
            if ($error = $this->atpEligibilityError($rfc->request_check_authority_purchase_id, $id, true)) {
                return back()->with('error', $error);
            }

            $wasRevision = $rfc->request_check_status === 'Minor Revision';

            DB::table('request_check_table')->where('request_check_id', $id)->update($this->rfcPayload([
                'request_check_status' => $this->rfcPersistStatus($wasRevision ? 'Resubmitted' : 'Submitted'),
                'request_check_review_stage' => 'accounting',
                'request_check_submitted_by' => auth()->id(),
                'request_check_submitted_at' => now(),
                'request_check_updated_at' => now(),
            ]));

            $this->notifyAccountingRfc($id);

            return back()->with('success', 'Request for Check submitted to Accounting.');
        });
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
            'request_check_authority_purchase_id' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                'exists:authority_to_purchase_table,authority_purchase_id',
            ],
            'request_check_date' => [$isDraft ? 'nullable' : 'required', 'date'],
            'request_check_payee' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'request_check_amount_figures' => [$isDraft ? 'nullable' : 'required', 'numeric', $isDraft ? 'min:0' : 'gt:0', 'max:999999999.99'],
            'request_check_particulars_purpose' => [$isDraft ? 'nullable' : 'required', 'string', 'max:5000'],
            'request_check_requested_by' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
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
            'authority_to_purchase_table.authority_purchase_form_number',
            'authority_to_purchase_table.authority_purchase_status as atp_status',
        ];

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

    private function eligibleAtpQuery()
    {
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
            ->where('authority_to_purchase_table.authority_purchase_status', 'Approved')
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
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name',
                'suppliers_table.supplier_store_type',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_purpose_description',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name'
            )
            ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
            ->limit(50);
    }

    private function buildAtpPrefill($eligibleAtps): array
    {
        $prefill = [];
        $ids = collect($eligibleAtps)->pluck('authority_purchase_id');

        $totals = DB::table('authority_to_purchase_items_table')
            ->whereIn('authority_purchase_id', $ids)
            ->select('authority_purchase_id', DB::raw('SUM(atp_amount) as total'))
            ->groupBy('authority_purchase_id')
            ->pluck('total', 'authority_purchase_id');

        foreach ($eligibleAtps as $atp) {
            $payee = $atp->supplier_store_type === 'Physical Store'
                ? ($atp->company_name ?? '')
                : ($atp->shop_name ?? $atp->company_name ?? '');

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

    private function rfcStatusSummary(): array
    {
        $select = ['request_check_status', DB::raw('COUNT(*) as aggregate')];
        $groupBy = ['request_check_status'];
        if ($this->rfcHas('request_check_is_archived')) {
            $select[] = 'request_check_is_archived';
            $groupBy[] = 'request_check_is_archived';
        }

        $counts = DB::table('request_check_table')
            ->select($select)
            ->groupBy($groupBy)
            ->get();

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

    private function atpEligibilityError($atpId, $ignoreRfcId, bool $required): ?string
    {
        if (!$atpId) {
            return $required ? 'Complete payee, amount, purpose, and ATP before submitting.' : null;
        }

        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $atpId)
            ->first();

        if (
            !$atp
            || $atp->authority_purchase_status !== 'Approved'
            || !empty($atp->authority_purchase_is_archived)
        ) {
            return 'Only an approved, unarchived Authority to Purchase can be used for a Request for Check.';
        }

        if ($this->hasBlockingRfcForAtp($atpId, $ignoreRfcId)) {
            return 'A Request for Check already exists for the selected ATP.';
        }

        return null;
    }

    private function hasBlockingRfcForAtp($atpId, $ignoreId = null): bool
    {
        if (!$atpId) {
            return false;
        }

        $query = DB::table('request_check_table')
            ->where('request_check_authority_purchase_id', $atpId)
            ->where('request_check_status', '!=', 'Rejected');
        $this->applyUnarchivedRfcConstraint($query);

        if ($ignoreId) {
            $query->where('request_check_id', '!=', $ignoreId);
        }

        return $query->exists();
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

    private function notifyAccountingRfc($id): void
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $id)->first();
        $ref = $rfc->request_check_form_number ?? ('RFC #' . $id);
        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_ACCOUNTING,
            'Request Check submitted',
            $ref . ' is waiting for Accounting review.',
            'rfc_submitted',
            'RFC',
            (int) $id,
            '/accounting/request-check/' . $id
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
