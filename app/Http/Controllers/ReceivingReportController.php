<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\PurchaserDocumentAccess;
use App\Support\WorkflowNotifier;
use App\Services\DocumentWorkflowService;
use App\Services\ReceivingReportFormExporter;

class ReceivingReportController extends Controller
{
    private const ACTIVE_STATUSES = [
        'Draft',
        'Submitted',
        'Under Review',
        'Minor Revision',
        'Resubmitted',
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

        if ($request->filled('status')) {
            $this->applyStatusFilter($query, $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('receiving_reports_table.receiving_report_date', $request->date);
        }

        $spotlightQuery = clone $query;
        $reports = $query
            ->orderByDesc('receiving_reports_table.receiving_report_created_at')
            ->paginate(10)
            ->withQueryString();

        $viewRrId = (int) ($request->query('view_rr') ?: 0);
        if (
            $viewRrId
            && !$reports->getCollection()->contains(fn ($row) => (int) $row->receiving_report_id === $viewRrId)
        ) {
            $spotlight = $spotlightQuery
                ->where('receiving_reports_table.receiving_report_id', $viewRrId)
                ->first();
            if ($spotlight) {
                $reports->setCollection($reports->getCollection()->prepend($spotlight));
            }
        }

        $summary = $this->rrStatusSummary();

        $eligibleRfcs = $this->eligibleRfcQuery()->get();
        $rfcPrefill = $this->buildRfcPrefill($eligibleRfcs);
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

        foreach ($reports as $rr) {
            $rr->has_liq = in_array((int) $rr->receiving_report_id, $rrHasLiq, true);
        }

        return view('purchaser.receiving-reports.index', [
            'reports' => $reports,
            'archiveView' => $archiveView,
            'summary' => $summary,
            'eligibleRfcs' => $eligibleRfcs,
            'rfcPrefill' => $rfcPrefill,
            'items' => $items,
            'selectedRfcId' => $request->query('selected_rfc'),
            'viewRrId' => $viewRrId ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('save_action', 'draft') === 'draft';
        $validated = $this->validateRr($request, $isDraft);

        if ($error = $this->rrEligibilityError($validated['receiving_report_request_check_id'] ?? null, null, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        return DB::transaction(function () use ($validated, $isDraft) {
            $now = now();
            $user = auth()->user();

            $id = DB::table('receiving_reports_table')->insertGetId([
                'receiving_report_request_check_id' => $validated['receiving_report_request_check_id'] ?? null,
                'receiving_report_date' => $validated['receiving_report_date'] ?? null,
                'receiving_report_received_from' => $validated['receiving_report_received_from'] ?? null,
                'receiving_report_supplier_address_override' => $validated['receiving_report_supplier_address_override'] ?? null,
                'receiving_report_invoice_no' => $validated['receiving_report_invoice_no'] ?? null,
                'receiving_report_dr_no' => $validated['receiving_report_dr_no'] ?? null,
                'receiving_report_delivery_date' => $validated['receiving_report_delivery_date'] ?? null,
                'receiving_report_received_by_signature' => $validated['receiving_report_received_by_signature'] ?? ($user->user_full_name ?? null),
                'receiving_report_status' => $isDraft ? 'Draft' : 'Submitted',
                'receiving_report_created_by' => auth()->id(),
                'receiving_report_submitted_by' => $isDraft ? null : auth()->id(),
                'receiving_report_submitted_at' => $isDraft ? null : $now,
                'receiving_report_is_archived' => 0,
                'receiving_report_created_at' => $now,
                'receiving_report_updated_at' => $now,
            ]);

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update([
                'receiving_report_form_number' => 'RR-' . $now->format('Y') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT),
            ]);

            $this->replaceItems($id, $validated['items'] ?? []);
            if (!$isDraft && !$this->hasCompleteRrItem($id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Add at least one item with quantity of 1 or more before submitting.',
                ]);
            }
            $this->linkRfc($validated['receiving_report_request_check_id'] ?? null, $id);
            $this->attachRelatedDocuments($id, $validated['receiving_report_request_check_id'] ?? null);

            if (!$isDraft) {
                $this->notifyReceiving($id);
            }

            return redirect()->route('purchaser.rr.index')->with(
                'success',
                $isDraft ? 'Receiving Report draft saved.' : 'Receiving Report submitted to Receiving.'
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
        $validated = $this->validateRr($request, $isDraft);
        $rfcId = $validated['receiving_report_request_check_id'] ?? $rr->receiving_report_request_check_id;

        if ($error = $this->rrEligibilityError($rfcId, $id, !$isDraft)) {
            return back()->withInput()->with('error', $error);
        }

        return DB::transaction(function () use ($validated, $rr, $isDraft, $id, $rfcId) {
            $now = now();
            $wasRevision = $rr->receiving_report_status === 'Minor Revision';
            $status = $isDraft
                ? ($wasRevision ? 'Minor Revision' : 'Draft')
                : ($wasRevision ? 'Resubmitted' : 'Submitted');

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update([
                'receiving_report_request_check_id' => $rfcId,
                'receiving_report_date' => $validated['receiving_report_date'] ?? null,
                'receiving_report_received_from' => $validated['receiving_report_received_from'] ?? null,
                'receiving_report_supplier_address_override' => $validated['receiving_report_supplier_address_override'] ?? null,
                'receiving_report_invoice_no' => $validated['receiving_report_invoice_no'] ?? null,
                'receiving_report_dr_no' => $validated['receiving_report_dr_no'] ?? null,
                'receiving_report_delivery_date' => $validated['receiving_report_delivery_date'] ?? null,
                'receiving_report_received_by_signature' => $validated['receiving_report_received_by_signature'] ?? $rr->receiving_report_received_by_signature,
                'receiving_report_status' => $status,
                'receiving_report_submitted_by' => $isDraft ? $rr->receiving_report_submitted_by : auth()->id(),
                'receiving_report_submitted_at' => $isDraft ? $rr->receiving_report_submitted_at : $now,
                'receiving_report_updated_at' => $now,
            ]);

            $this->replaceItems($id, $validated['items'] ?? []);
            if (!$isDraft && !$this->hasCompleteRrItem($id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => 'Add at least one item with quantity of 1 or more before submitting.',
                ]);
            }
            $this->linkRfc($rfcId, $id);
            $this->attachRelatedDocuments($id, $rfcId);

            if (!$isDraft) {
                $this->notifyReceiving($id);
            }

            return redirect()->route('purchaser.rr.index')->with(
                'success',
                $isDraft ? 'Receiving Report updated.' : 'Receiving Report submitted to Receiving.'
            );
        });
    }

    public function submit($id)
    {
        return DB::transaction(function () use ($id) {
            $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $id)->lockForUpdate()->first();
            if (!$rr || !$this->isEditable($rr)) {
                return back()->with('error', 'This Receiving Report cannot be submitted.');
            }
            PurchaserDocumentAccess::assertOwns($rr, 'rr');

            if ($error = $this->rrEligibilityError($rr->receiving_report_request_check_id, $id, true)) {
                return back()->with('error', $error);
            }

            if (!$this->hasCompleteRrItem($id)) {
                return back()->with('error', 'Add at least one item with quantity of 1 or more before submitting.');
            }

            $wasRevision = $rr->receiving_report_status === 'Minor Revision';
            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update([
                'receiving_report_status' => $wasRevision ? 'Resubmitted' : 'Submitted',
                'receiving_report_submitted_by' => auth()->id(),
                'receiving_report_submitted_at' => now(),
                'receiving_report_updated_at' => now(),
            ]);
            $this->linkRfc($rr->receiving_report_request_check_id, $id);
            $this->attachRelatedDocuments($id, $rr->receiving_report_request_check_id);
            $this->notifyReceiving($id);

            return back()->with('success', 'Receiving Report submitted to Receiving.');
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

    private function validateRr(Request $request, bool $isDraft): array
    {
        return $request->validate([
            'save_action' => ['required', 'in:draft,submit'],
            'receiving_report_request_check_id' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                'exists:request_check_table,request_check_id',
            ],
            'receiving_report_date' => [$isDraft ? 'nullable' : 'required', 'date'],
            'receiving_report_received_from' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'receiving_report_supplier_address_override' => ['nullable', 'string', 'max:2000'],
            'receiving_report_invoice_no' => ['nullable', 'string', 'max:100'],
            'receiving_report_dr_no' => ['nullable', 'string', 'max:100'],
            'receiving_report_delivery_date' => ['nullable', 'date'],
            'receiving_report_received_by_signature' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'max:10'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.article' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function replaceItems($rrId, array $items): void
    {
        DB::table('receiving_report_items_table')->where('receiving_report_id', $rrId)->delete();

        $rows = [];
        foreach (array_slice($items, 0, 10) as $row) {
            $qty = $row['quantity'] ?? null;
            $unit = $row['unit'] ?? null;
            $article = $row['article'] ?? null;
            if ($qty === null && blank($unit) && blank($article)) {
                continue;
            }
            $rows[] = [
                'receiving_report_id' => $rrId,
                'receiving_report_item_quantity' => $qty ?: null,
                'receiving_report_item_unit' => $unit,
                'receiving_report_item_article' => $article,
            ];
        }
        if ($rows !== []) {
            DB::table('receiving_report_items_table')->insert($rows);
        }
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

    private function eligibleRfcQuery()
    {
        $query = DB::table('request_check_table')
            ->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
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
            ->where('request_check_table.request_check_status', 'Approved')
            ->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });

        if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            $query->whereNotNull('request_check_table.request_check_funds_released_at');
        }

        return $query
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('receiving_reports_table')
                    ->whereColumn(
                        'receiving_reports_table.receiving_report_request_check_id',
                        'request_check_table.request_check_id'
                    )
                    ->where(function ($inner) {
                        $inner->whereNull('receiving_report_is_archived')
                            ->orWhere('receiving_report_is_archived', 0);
                    })
                    ->whereIn('receiving_report_status', self::ACTIVE_STATUSES);
            })
            ->select(
                'request_check_table.request_check_id',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_payee',
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'physical_suppliers_table.company_name',
                'physical_suppliers_table.company_address',
                'online_suppliers_table.shop_name',
                'suppliers_table.supplier_store_type'
            )
            ->orderByDesc('request_check_table.request_check_id')
            ->limit(50);
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
            $rows = [];
            foreach (($atpItems[$rfc->authority_purchase_id] ?? collect())->take(10) as $item) {
                $rows[] = [
                    'quantity' => $item->atp_quantity,
                    'unit' => $item->atp_unit,
                    'article' => $item->atp_description,
                ];
            }
            $prefill[(string) $rfc->request_check_id] = [
                'received_from' => $from,
                'address' => $rfc->company_address ?? '',
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
            } elseif ($row->receiving_report_status === 'Returned') {
                $summary['returned'] += $count;
            }
        }

        return $summary;
    }

    private function rrEligibilityError($rfcId, $ignoreId, bool $required): ?string
    {
        if (!$rfcId) {
            return $required ? 'Select an approved Request for Check with released funds before submitting.' : null;
        }

        if ($this->hasBlockingRr($rfcId, $ignoreId)) {
            return 'A Receiving Report already exists for the selected Request for Check.';
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

    private function hasBlockingRr($rfcId, $ignoreId = null): bool
    {
        if (!$rfcId) {
            return false;
        }

        $query = DB::table('receiving_reports_table')
            ->where('receiving_report_request_check_id', $rfcId)
            ->whereIn('receiving_report_status', self::ACTIVE_STATUSES)
            ->where(function ($q) {
                $q->whereNull('receiving_report_is_archived')
                    ->orWhere('receiving_report_is_archived', 0);
            });

        if ($ignoreId) {
            $query->where('receiving_report_id', '!=', $ignoreId);
        }

        return $query->exists();
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

    private function notifyReceiving($id): void
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
            '/receiving/reports'
        );
    }

    private function attachRelatedDocuments($rrId, $rfcId): void
    {
        if (!$rfcId) {
            return;
        }

        $rfc = DB::table('request_check_table')
            ->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
            )
            ->where('request_check_table.request_check_id', $rfcId)
            ->select(
                'request_check_table.request_check_authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_ris_id'
            )
            ->first();

        if (!$rfc) {
            return;
        }

        $payload = [];
        if (
            Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')
            && !empty($rfc->request_check_authority_purchase_id)
        ) {
            $payload['receiving_report_atp_id'] = $rfc->request_check_authority_purchase_id;
        }
        if (
            Schema::hasColumn('receiving_reports_table', 'receiving_report_ris_id')
            && !empty($rfc->authority_purchase_ris_id)
        ) {
            $payload['receiving_report_ris_id'] = $rfc->authority_purchase_ris_id;
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
