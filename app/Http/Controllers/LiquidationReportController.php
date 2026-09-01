<?php

namespace App\Http\Controllers;

use App\Services\DocumentWorkflowService;
use App\Services\LiquidationReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Support\ProcurementPaymentPath;
use App\Support\PurchaserDocumentAccess;
use App\Support\WorkflowNotifier;

class LiquidationReportController extends Controller
{
    private const ACTIVE_STATUSES = [
        'Draft', 'Submitted', 'Under Review', 'Minor Revision', 'Resubmitted', 'Pending Admin Approval',
    ];

    public function index(Request $request)
    {
        $archiveView = $request->query('view') === 'archive';
        $query = $this->liqBaseQuery();
        PurchaserDocumentAccess::scopeOwned($query, 'liq', 'liquidation_reports_table');

        DocumentWorkflowService::applyArchiveFilter(
            $query,
            'liquidation_reports_table.liquidation_report_is_archived',
            $archiveView
        );

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($sub) use ($search) {
                $sub->where('liquidation_reports_table.liquidation_report_form_number', 'LIKE', $search)
                    ->orWhere('liquidation_reports_table.liquidation_report_employee_name', 'LIKE', $search)
                    ->orWhere('liquidation_reports_table.liquidation_report_purpose', 'LIKE', $search)
                    ->orWhere('receiving_reports_table.receiving_report_form_number', 'LIKE', $search);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'Submitted') {
                $query->whereIn('liquidation_reports_table.liquidation_report_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending Admin Approval']);
            } else {
                $query->where('liquidation_reports_table.liquidation_report_status', $request->status);
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('liquidation_reports_table.liquidation_report_date_submitted', $request->date);
        }

        $spotlightQuery = clone $query;
        $reports = $query->orderByDesc('liquidation_reports_table.liquidation_report_created_at')->paginate(10)->withQueryString();

        $viewLiqId = (int) ($request->query('view_liq') ?: 0);
        if (
            $viewLiqId
            && !$reports->getCollection()->contains(fn ($row) => (int) $row->liquidation_report_id === $viewLiqId)
        ) {
            $spotlight = $spotlightQuery
                ->where('liquidation_reports_table.liquidation_report_id', $viewLiqId)
                ->first();
            if ($spotlight) {
                $reports->setCollection($reports->getCollection()->prepend($spotlight));
            }
        }

        $summary = $this->liqStatusSummary();

        $eligibleRrs = $this->eligibleRrQuery()->get();
        $rrPrefill = $this->buildRrPrefill($eligibleRrs);
        $items = $this->itemsFor($reports->getCollection()->pluck('liquidation_report_id'));
        $attachments = $this->attachmentsFor($reports->getCollection()->pluck('liquidation_report_id'));

        return view('purchaser.liquidation-reports.index', compact(
            'reports', 'archiveView', 'summary', 'eligibleRrs', 'rrPrefill', 'items', 'attachments'
        ) + [
            'selectedRrId' => $request->query('selected_rr'),
            'viewLiqId' => $viewLiqId ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('save_action', 'draft') === 'draft';
        $validated = $this->validateLiq($request, $isDraft);

        if ($error = $this->rrEligibilityError($validated['liquidation_report_receiving_report_id'] ?? null, null, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        return DB::transaction(function () use ($request, $validated, $isDraft) {
            $payload = $this->payloadFromValidated($validated, $isDraft, null);
            $id = DB::table('liquidation_reports_table')->insertGetId($payload);
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_form_number' => 'LIQ-' . now()->format('Y') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT),
            ]);
            $this->replaceItems($id, $validated['items'] ?? []);
            if (!$isDraft && !$this->hasCompleteLiqItem($id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Add at least one expense line before submitting.',
                ]);
            }
            $this->recalcTotals($id, $validated['items'] ?? [], $validated['liquidation_report_amount_advance'] ?? null);
            $this->storeAttachments($request, $id);

            if (!$isDraft) {
                $this->notifyAccountingLiq($id);
            }

            return redirect()->route('purchaser.liq.index')->with(
                'success',
                $isDraft ? 'Liquidation Report draft saved.' : 'Liquidation Report submitted to Accounting.'
            );
        });
    }

    public function update(Request $request, $id)
    {
        $liq = $this->find($id);
        if (!$liq || !$this->isEditable($liq)) {
            return back()->with('error', 'Only draft or revision Liquidation Reports can be edited.');
        }

        $isDraft = $request->input('save_action', 'draft') === 'draft';
        $validated = $this->validateLiq($request, $isDraft);
        $rrId = $validated['liquidation_report_receiving_report_id'] ?? $liq->liquidation_report_receiving_report_id;

        if ($error = $this->rrEligibilityError($rrId, $id, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        return DB::transaction(function () use ($request, $validated, $liq, $isDraft, $id) {
            $payload = $this->payloadFromValidated($validated, $isDraft, $liq);
            unset($payload['liquidation_report_created_at']);
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update($payload);
            $this->replaceItems($id, $validated['items'] ?? []);
            if (!$isDraft && !$this->hasCompleteLiqItem($id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Add at least one expense line before submitting.',
                ]);
            }
            $this->recalcTotals($id, $validated['items'] ?? [], $validated['liquidation_report_amount_advance'] ?? null);
            $this->deleteRequestedAttachments($request, $id);
            $this->storeAttachments($request, $id);

            if (!$isDraft) {
                $this->notifyAccountingLiq($id);
            }

            return redirect()->route('purchaser.liq.index')->with(
                'success',
                $isDraft ? 'Liquidation Report updated.' : 'Liquidation Report submitted to Accounting.'
            );
        });
    }

    public function submit($id)
    {
        return DB::transaction(function () use ($id) {
            $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->lockForUpdate()->first();
            if (!$liq || !$this->isEditable($liq)) {
                return back()->with('error', 'This Liquidation Report cannot be submitted.');
            }
            PurchaserDocumentAccess::assertOwns($liq, 'liq');

            if (
                blank($liq->liquidation_report_employee_name)
                || blank($liq->liquidation_report_purpose)
                || $liq->liquidation_report_amount_advance === null
            ) {
                return back()->with('error', 'Complete employee, purpose, and amount advanced before submitting.');
            }

            if ($error = $this->rrEligibilityError($liq->liquidation_report_receiving_report_id, $id, true)) {
                return back()->with('error', $error);
            }

            if (!$this->hasCompleteLiqItem($id)) {
                return back()->with('error', 'Add at least one expense line before submitting.');
            }

            $wasRevision = $liq->liquidation_report_status === 'Minor Revision';
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => $wasRevision ? 'Resubmitted' : 'Submitted',
                'liquidation_report_review_stage' => 'accounting',
                'liquidation_report_submitted_by' => auth()->id(),
                'liquidation_report_submitted_at' => now(),
                'liquidation_report_date_submitted' => now()->toDateString(),
                'liquidation_report_submitted_by_date' => now()->toDateString(),
                'liquidation_report_days_lapse' => $this->daysLapsed($liq->liquidation_report_submission_deadline, now()->toDateString()),
                'liquidation_report_updated_at' => now(),
            ]);

            $this->notifyAccountingLiq($id);

            return back()->with('success', 'Liquidation Report submitted to Accounting.');
        });
    }

    public function archive($id)
    {
        $liq = $this->find($id);
        if (!$liq || !in_array($liq->liquidation_report_status, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Only approved or rejected Liquidation Reports can be archived.');
        }
        DocumentWorkflowService::setArchived(
            'liquidation_reports_table',
            'liquidation_report_id',
            $id,
            'liquidation_report_is_archived',
            'liquidation_report_updated_at',
            true
        );
        return back()->with('success', 'Liquidation Report archived.');
    }

    public function restore($id)
    {
        $liq = $this->find($id);
        if (!$liq) {
            return back()->with('error', 'Liquidation Report not found.');
        }
        DocumentWorkflowService::setArchived(
            'liquidation_reports_table',
            'liquidation_report_id',
            $id,
            'liquidation_report_is_archived',
            'liquidation_report_updated_at',
            false
        );
        return back()->with('success', 'Liquidation Report restored.');
    }

    public function downloadAttachment($id, $attachmentId)
    {
        $liq = $this->find($id);
        abort_if(!$liq, 404);

        $attachment = DB::table('liquidation_report_attachments_table')
            ->where('liquidation_attachment_id', $attachmentId)
            ->where('liquidation_report_id', $id)
            ->first();
        abort_if(!$attachment, 404);
        $path = storage_path('app/public/' . $attachment->liquidation_attachment_path);
        abort_if(!is_file($path), 404);
        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $attachment->liquidation_attachment_original_name . '"',
        ]);
    }

    public function exportExcel($id, LiquidationReportExporter $exporter)
    {
        return $exporter->downloadExcel($this->loadForExport($id));
    }

    public function exportWord($id, LiquidationReportExporter $exporter)
    {
        return $exporter->downloadWord($this->loadForExport($id));
    }

    public function exportBlankExcel(LiquidationReportExporter $exporter)
    {
        return $exporter->downloadExcel();
    }

    public function exportBlankWord(LiquidationReportExporter $exporter)
    {
        return $exporter->downloadWord();
    }

    public static function reviewBaseQuery()
    {
        return DB::table('liquidation_reports_table')
            ->leftJoin(
                'receiving_reports_table',
                'liquidation_reports_table.liquidation_report_receiving_report_id',
                '=',
                'receiving_reports_table.receiving_report_id'
            )
            ->select(
                'liquidation_reports_table.*',
                'receiving_reports_table.receiving_report_form_number'
            );
    }

    private function loadForExport($id): array
    {
        $liq = $this->liqBaseQuery()->where('liquidation_reports_table.liquidation_report_id', $id)->first();
        abort_if(!$liq, 404);
        PurchaserDocumentAccess::assertOwns($liq, 'liq');
        $items = DB::table('liquidation_report_items_table')
            ->where('liquidation_report_id', $id)
            ->orderBy('liquidation_item_id')
            ->get();
        return compact('liq', 'items');
    }

    private function validateLiq(Request $request, bool $isDraft): array
    {
        return $request->validate([
            'save_action' => ['required', 'in:draft,submit'],
            'liquidation_report_receiving_report_id' => [$isDraft ? 'nullable' : 'required', 'integer', 'exists:receiving_reports_table,receiving_report_id'],
            'liquidation_report_employee_name' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'liquidation_report_cheque_number' => ['nullable', 'string', 'max:100'],
            'liquidation_report_purpose' => [$isDraft ? 'nullable' : 'required', 'string', 'max:5000'],
            'liquidation_report_amount_advance' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'liquidation_report_date_released' => ['nullable', 'date'],
            'liquidation_report_charge_to_account' => ['nullable', 'string', 'max:255'],
            'liquidation_report_activity_end_date' => ['nullable', 'date'],
            'liquidation_report_submission_deadline' => ['nullable', 'date'],
            'liquidation_report_date_submitted' => ['nullable', 'date'],
            'liquidation_report_other_income' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'liquidation_report_cash_returned_or_no' => ['nullable', 'string', 'max:100'],
            'liquidation_report_submitted_by_signature' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'max:20'],
            'items.*.particulars' => ['nullable', 'string', 'max:2000'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.actual_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.actual_total' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.ref_no' => ['nullable', 'string', 'max:100'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'delete_attachments' => ['nullable', 'array'],
            'delete_attachments.*' => ['integer'],
        ]);
    }

    private function payloadFromValidated(array $validated, bool $isDraft, $existing): array
    {
        $now = now();
        $wasRevision = $existing && $existing->liquidation_report_status === 'Minor Revision';
        $status = $isDraft
            ? ($wasRevision ? 'Minor Revision' : 'Draft')
            : ($wasRevision ? 'Resubmitted' : 'Submitted');
        $submittedDate = $validated['liquidation_report_date_submitted'] ?? ($isDraft ? null : $now->toDateString());

        $row = [
            'liquidation_report_receiving_report_id' => $validated['liquidation_report_receiving_report_id'] ?? ($existing->liquidation_report_receiving_report_id ?? null),
            'liquidation_report_employee_name' => $validated['liquidation_report_employee_name'] ?? null,
            'liquidation_report_cheque_number' => $validated['liquidation_report_cheque_number'] ?? null,
            'liquidation_report_purpose' => $validated['liquidation_report_purpose'] ?? null,
            'liquidation_report_amount_advance' => $validated['liquidation_report_amount_advance'] ?? null,
            'liquidation_report_date_released' => $validated['liquidation_report_date_released'] ?? null,
            'liquidation_report_charge_to_account' => $validated['liquidation_report_charge_to_account'] ?? null,
            'liquidation_report_activity_end_date' => $validated['liquidation_report_activity_end_date'] ?? null,
            'liquidation_report_submission_deadline' => $validated['liquidation_report_submission_deadline'] ?? null,
            'liquidation_report_date_submitted' => $submittedDate,
            'liquidation_report_days_lapse' => $this->daysLapsed($validated['liquidation_report_submission_deadline'] ?? null, $submittedDate),
            'liquidation_report_other_income' => $validated['liquidation_report_other_income'] ?? null,
            'liquidation_report_cash_returned_or_no' => $validated['liquidation_report_cash_returned_or_no'] ?? null,
            'liquidation_report_submitted_by_signature' => $validated['liquidation_report_submitted_by_signature'] ?? (auth()->user()->user_full_name ?? null),
            'liquidation_report_submitted_by_date' => $submittedDate,
            'liquidation_report_status' => $status,
            'liquidation_report_review_stage' => $isDraft ? ($existing->liquidation_report_review_stage ?? null) : 'accounting',
            'liquidation_report_submitted_by' => $isDraft ? ($existing->liquidation_report_submitted_by ?? null) : auth()->id(),
            'liquidation_report_submitted_at' => $isDraft ? ($existing->liquidation_report_submitted_at ?? null) : $now,
            'liquidation_report_is_archived' => 0,
            'liquidation_report_updated_at' => $now,
        ];

        if (!$existing) {
            $row['liquidation_report_created_by'] = auth()->id();
            $row['liquidation_report_created_at'] = $now;
        }

        return $row;
    }

    private function replaceItems($id, array $items): void
    {
        DB::table('liquidation_report_items_table')->where('liquidation_report_id', $id)->delete();
        $rows = [];
        foreach ($items as $row) {
            $particulars = $row['particulars'] ?? null;
            $amount = $row['amount'] ?? null;
            $actual = $row['actual_amount'] ?? null;
            $total = $row['actual_total'] ?? $actual;
            if (blank($particulars) && $amount === null && $actual === null) {
                continue;
            }
            $variance = ((float) ($total ?? 0)) - ((float) ($amount ?? 0));
            $rows[] = [
                'liquidation_report_id' => $id,
                'liquidation_item_particulars' => $particulars,
                'liquidation_item_particulars_amount' => $amount,
                'liquidation_item_actual_breakdown_amount' => $actual,
                'liquidation_item_actual_total_amount' => $total,
                'liquidation_item_variance' => $variance,
                'liquidation_item_ref_no' => $row['ref_no'] ?? null,
            ];
        }
        if ($rows !== []) {
            DB::table('liquidation_report_items_table')->insert($rows);
        }
    }

    private function recalcTotals($id, array $items = [], $headerAdvance = null): void
    {
        $advance = 0.0;
        $actual = 0.0;
        foreach ($items as $row) {
            $advance += (float) ($row['amount'] ?? 0);
            $actual += (float) ($row['actual_total'] ?? $row['actual_amount'] ?? 0);
        }
        $amtAdvanced = $headerAdvance !== null && $headerAdvance !== '' ? (float) $headerAdvance : $advance;

        DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
            'liquidation_report_summary_amt_advanced' => $amtAdvanced,
            'liquidation_report_summary_actual_expense' => $actual,
            'liquidation_report_summary_balance' => $amtAdvanced - $actual,
        ]);
    }

    private function daysLapsed($deadline, $submitted): ?int
    {
        if (!$deadline || !$submitted) {
            return null;
        }
        $submittedAt = \Carbon\Carbon::parse($submitted);
        $deadlineAt = \Carbon\Carbon::parse($deadline);
        return $submittedAt->greaterThan($deadlineAt) ? $submittedAt->diffInDays($deadlineAt) : 0;
    }

    private function storeAttachments(Request $request, $id): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }
        foreach ($request->file('attachments') as $file) {
            if (!$file) {
                continue;
            }
            $path = $file->store('liquidation/' . $id, 'public');
            DB::table('liquidation_report_attachments_table')->insert([
                'liquidation_report_id' => $id,
                'liquidation_attachment_original_name' => $file->getClientOriginalName(),
                'liquidation_attachment_path' => $path,
                'liquidation_attachment_mime_type' => $file->getClientMimeType(),
                'liquidation_attachment_size' => $file->getSize(),
                'liquidation_attachment_uploaded_by' => auth()->id(),
                'liquidation_attachment_created_at' => now(),
            ]);
        }
    }

    private function deleteRequestedAttachments(Request $request, $id): void
    {
        $ids = collect($request->input('delete_attachments', []))->filter();
        if ($ids->isEmpty()) {
            return;
        }
        $rows = DB::table('liquidation_report_attachments_table')->where('liquidation_report_id', $id)->whereIn('liquidation_attachment_id', $ids)->get();
        foreach ($rows as $row) {
            Storage::disk('public')->delete($row->liquidation_attachment_path);
        }
        DB::table('liquidation_report_attachments_table')->where('liquidation_report_id', $id)->whereIn('liquidation_attachment_id', $ids)->delete();
    }

    private function liqBaseQuery()
    {
        return DB::table('liquidation_reports_table')
            ->leftJoin(
                'receiving_reports_table',
                'liquidation_reports_table.liquidation_report_receiving_report_id',
                '=',
                'receiving_reports_table.receiving_report_id'
            )
            ->select(
                'liquidation_reports_table.*',
                'receiving_reports_table.receiving_report_form_number'
            );
    }

    private function eligibleRrQuery()
    {
        return DB::table('receiving_reports_table')
            ->leftJoin(
                'request_check_table',
                'receiving_reports_table.receiving_report_request_check_id',
                '=',
                'request_check_table.request_check_id'
            )
            ->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
            )
            ->where('receiving_reports_table.receiving_report_status', 'Completed')
            ->where(function ($q) {
                $q->where('authority_to_purchase_table.authority_purchase_payment_path', ProcurementPaymentPath::CASH_ADVANCE)
                    ->orWhere('request_check_table.request_check_funding_type', ProcurementPaymentPath::CASH_ADVANCE);
            })
            ->where(function ($q) {
                $q->whereNull('receiving_reports_table.receiving_report_is_archived')
                    ->orWhere('receiving_reports_table.receiving_report_is_archived', 0);
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('liquidation_reports_table')
                    ->whereColumn(
                        'liquidation_reports_table.liquidation_report_receiving_report_id',
                        'receiving_reports_table.receiving_report_id'
                    )
                    ->where(function ($inner) {
                        $inner->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
                    })
                    ->whereIn('liquidation_report_status', self::ACTIVE_STATUSES);
            })
            ->select(
                'receiving_reports_table.receiving_report_id',
                'receiving_reports_table.receiving_report_form_number',
                'request_check_table.request_check_id',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_particulars_purpose',
                'request_check_table.request_check_amount_figures',
                'authority_to_purchase_table.authority_purchase_id'
            )
            ->orderByDesc('receiving_reports_table.receiving_report_id')
            ->limit(50);
    }

    private function buildRrPrefill($eligibleRrs): array
    {
        $prefill = [];
        $rrIds = collect($eligibleRrs)->pluck('receiving_report_id');
        $atpIds = collect($eligibleRrs)->pluck('authority_purchase_id')->filter();

        $rrItems = DB::table('receiving_report_items_table')
            ->whereIn('receiving_report_id', $rrIds)
            ->orderBy('receiving_report_item_id')
            ->get()
            ->groupBy('receiving_report_id');

        $atpItems = DB::table('authority_to_purchase_items_table')
            ->whereIn('authority_purchase_id', $atpIds)
            ->orderBy('atp_item_id')
            ->get()
            ->groupBy('authority_purchase_id');

        foreach ($eligibleRrs as $rr) {
            $rows = [];
            $source = $rrItems[$rr->receiving_report_id] ?? collect();
            $atp = $atpItems[$rr->authority_purchase_id] ?? collect();
            foreach ($source->values() as $i => $item) {
                $atpRow = $atp[$i] ?? null;
                $amount = $atpRow->atp_amount ?? null;
                $article = $item->receiving_report_item_article;
                $rows[] = [
                    'particulars' => $article,
                    'amount' => $amount,
                    'actual_amount' => $amount,
                    'actual_total' => $amount,
                    'ref_no' => '',
                ];
            }
            $prefill[(string) $rr->receiving_report_id] = [
                'purpose' => $rr->request_check_particulars_purpose ?? '',
                'amount' => $rr->request_check_amount_figures ?? '',
                'items' => $rows,
            ];
        }

        return $prefill;
    }

    private function liqStatusSummary(): array
    {
        $counts = DB::table('liquidation_reports_table')
            ->select('liquidation_report_status', 'liquidation_report_is_archived', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('liquidation_report_status', 'liquidation_report_is_archived')
            ->get();

        $summary = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0,
            'archived' => 0,
        ];
        $submitted = ['Submitted', 'Under Review', 'Resubmitted', 'Pending Admin Approval'];

        foreach ($counts as $row) {
            $count = (int) $row->aggregate;
            if ((int) $row->liquidation_report_is_archived === 1) {
                $summary['archived'] += $count;
                continue;
            }
            $summary['total'] += $count;
            if ($row->liquidation_report_status === 'Draft') {
                $summary['draft'] += $count;
            } elseif (in_array($row->liquidation_report_status, $submitted, true)) {
                $summary['submitted'] += $count;
            } elseif ($row->liquidation_report_status === 'Approved') {
                $summary['approved'] += $count;
            } elseif ($row->liquidation_report_status === 'Rejected') {
                $summary['rejected'] += $count;
            }
        }

        return $summary;
    }

    private function rrEligibilityError($rrId, $ignoreId, bool $required): ?string
    {
        if (!$rrId) {
            return $required ? 'Select a completed Receiving Report before submitting.' : null;
        }

        $rr = DB::table('receiving_reports_table')
            ->leftJoin(
                'request_check_table',
                'receiving_reports_table.receiving_report_request_check_id',
                '=',
                'request_check_table.request_check_id'
            )
            ->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
            )
            ->where('receiving_reports_table.receiving_report_id', $rrId)
            ->select(
                'receiving_reports_table.*',
                'request_check_table.request_check_funding_type',
                'authority_to_purchase_table.authority_purchase_payment_path'
            )
            ->first();
        if (
            !$rr
            || !in_array($rr->receiving_report_status, ['Completed', 'Accepted'], true)
            || !empty($rr->receiving_report_is_archived)
        ) {
            return 'Only a completed Receiving Report can be used for a Liquidation Report.';
        }

        $path = $rr->authority_purchase_payment_path
            ?? ($rr->request_check_funding_type ?? ProcurementPaymentPath::REQUEST_FOR_CHECK);
        if ($path !== ProcurementPaymentPath::CASH_ADVANCE) {
            return 'Liquidation Reports apply only to Cash Advance workflows. Request for Check ends after the Receiving Report.';
        }

        if ($this->hasBlocking($rrId, $ignoreId)) {
            return 'A Liquidation Report already exists for the selected Receiving Report.';
        }

        return null;
    }

    private function hasCompleteLiqItem($id): bool
    {
        return DB::table('liquidation_report_items_table')
            ->where('liquidation_report_id', $id)
            ->where(function ($q) {
                $q->whereNotNull('liquidation_item_particulars')
                    ->where('liquidation_item_particulars', '!=', '');
            })
            ->exists();
    }

    private function hasBlocking($rrId, $ignoreId = null): bool
    {
        if (!$rrId) {
            return false;
        }
        $query = DB::table('liquidation_reports_table')
            ->where('liquidation_report_receiving_report_id', $rrId)
            ->whereIn('liquidation_report_status', self::ACTIVE_STATUSES)
            ->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            });
        if ($ignoreId) {
            $query->where('liquidation_report_id', '!=', $ignoreId);
        }
        return $query->exists();
    }

    private function itemsFor($ids)
    {
        if ($ids->isEmpty()) {
            return collect();
        }
        return DB::table('liquidation_report_items_table')->whereIn('liquidation_report_id', $ids)->orderBy('liquidation_item_id')->get()->groupBy('liquidation_report_id');
    }

    private function attachmentsFor($ids)
    {
        if ($ids->isEmpty() || !Schema::hasTable('liquidation_report_attachments_table')) {
            return collect();
        }
        return DB::table('liquidation_report_attachments_table')->whereIn('liquidation_report_id', $ids)->get()->groupBy('liquidation_report_id');
    }

    private function find($id)
    {
        $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->first();
        if ($liq) {
            PurchaserDocumentAccess::assertOwns($liq, 'liq');
        }

        return $liq;
    }

    private function notifyAccountingLiq($id): void
    {
        $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->first();
        $ref = $liq->liquidation_report_form_number ?? ('LIQ #' . $id);
        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_ACCOUNTING,
            'Liquidation Report submitted',
            $ref . ' is waiting for Accounting review.',
            'liq_submitted',
            'LIQ',
            (int) $id,
            '/accounting/liquidation-reports/' . $id
        );
    }

    private function isEditable($liq): bool
    {
        return DocumentWorkflowService::isEditable(
            $liq,
            'liquidation_report_status',
            ['Draft', 'Minor Revision'],
            'liquidation_report_is_archived'
        );
    }
}
