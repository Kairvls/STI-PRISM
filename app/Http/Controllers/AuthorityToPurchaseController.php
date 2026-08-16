<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
                'requisition_issue_slip_table.ris_request_type',
                'requisition_issue_slip_table.ris_manual_title',
                'requisition_issue_slip_table.ris_purpose_description',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            );

        if ($archiveView) {
            $query->where('authority_to_purchase_table.authority_purchase_is_archived', 1);
        } else {
            $query->where(function ($q) {
                $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                    ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
            });
        }

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
            $query->where(
                'requisition_issue_slip_table.ris_request_type',
                $request->request_type
            );
        }

        $atps = $query
            ->orderByDesc('authority_to_purchase_table.authority_purchase_created_at')
            ->paginate(10)
            ->withQueryString();

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
            $atpHasRfc = DB::table('request_check_table')
                ->whereIn('request_check_authority_purchase_id', $atpIds)
                ->where(function ($q) {
                    $q->whereNull('request_check_is_archived')
                        ->orWhere('request_check_is_archived', 0);
                })
                ->where('request_check_status', '!=', 'Rejected')
                ->pluck('request_check_authority_purchase_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        foreach ($atps as $atp) {
            $atp->has_rfc = in_array((int) $atp->authority_purchase_id, $atpHasRfc, true);
        }

        $selectedRisId = $request->query('selected_ris');
        $viewAtpId = $request->query('view_atp');
        $editAtpId = $request->query('edit_atp');
        $risPrefill = $this->buildRisPrefill($eligibleRis);

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
                'risPrefill'
            )
        );
    }

    public function create(Request $request)
    {
        return redirect()->route('purchaser.atp.index', array_filter([
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
                'required',
                'integer',
                'exists:requisition_issue_slip_table,ris_id',
            ],
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
            'authority_purchase_reference_po_no' => ['nullable', 'string', 'max:100'],
            'items' => [$isDraft ? 'nullable' : 'required', 'array', 'max:50'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $validated['authority_purchase_ris_id'])
            ->first();

        if (!$ris || !$this->risIsEligibleForAtp($ris)) {
            return back()->withInput()->with('error', 'Only approved RIS records may be used to create ATP.');
        }

        if ($this->hasBlockingAtpForRis($validated['authority_purchase_ris_id'])) {
            return back()->withInput()->with('error', 'An Authority to Purchase already exists for the selected RIS.');
        }

        $items = $this->filterItemRows($validated['items'] ?? []);

        if (!$isDraft) {
            $this->assertSubmitReadyItems($items);
        }

        return DB::transaction(function () use ($validated, $items, $isDraft) {
            $now = now();

            $authorityPurchaseId = DB::table('authority_to_purchase_table')->insertGetId([
                'authority_purchase_ris_id' => $validated['authority_purchase_ris_id'],
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
            ]);

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $authorityPurchaseId)
                ->update([
                    'authority_purchase_form_number' => 'ATP-' . $now->format('Y') . '-' . str_pad((string) $authorityPurchaseId, 5, '0', STR_PAD_LEFT),
                ]);

            $this->replaceAtpItems($authorityPurchaseId, $items);

            $message = $isDraft
                ? 'Authority to Purchase draft created successfully.'
                : 'Authority to Purchase submitted successfully.';

            return redirect()->route('purchaser.atp.index')->with('success', $message);
        });
    }

    public function show($id)
    {
        return redirect()->route('purchaser.atp.index', ['view_atp' => $id]);
    }

    public function edit($id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return redirect()->route('purchaser.atp.index')->with('error', 'Authority to Purchase record not found.');
        }

        if (!$this->isSoftDraft($atp)) {
            return redirect()->route('purchaser.atp.index', ['view_atp' => $id])
                ->with('error', 'Only draft ATP records can be edited.');
        }

        return redirect()->route('purchaser.atp.index', ['edit_atp' => $id]);
    }

    public function update(Request $request, $id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase record not found.');
        }

        if (!$this->isSoftDraft($atp)) {
            return back()->with('error', 'Only draft ATP records can be updated.');
        }

        $saveAction = $request->input('save_action', 'save');
        $isDraft = in_array($saveAction, ['save', 'draft'], true);

        $validated = $request->validate([
            'save_action' => ['required', 'in:save,draft,submit'],
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
            'authority_purchase_reference_po_no' => ['nullable', 'string', 'max:100'],
            'items' => [$isDraft ? 'nullable' : 'required', 'array', 'max:50'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $items = $this->filterItemRows($validated['items'] ?? []);

        if (!$isDraft) {
            $this->assertSubmitReadyItems($items);
        }

        return DB::transaction(function () use ($validated, $id, $items, $isDraft) {
            $now = now();

            $payload = [
                'authority_purchase_supplier_id' => $validated['authority_purchase_supplier_id'] ?? null,
                'authority_purchase_date' => $validated['authority_purchase_date'] ?? null,
                'authority_purchase_received_by_name' => $validated['authority_purchase_received_by_name'] ?? null,
                'authority_purchase_reference_po_no' => $validated['authority_purchase_reference_po_no'] ?? null,
                'authority_purchase_updated_at' => $now,
            ];

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

            $message = $isDraft
                ? 'Authority to Purchase draft updated successfully.'
                : 'Authority to Purchase submitted successfully.';

            return redirect()
                ->route('purchaser.atp.index')
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

            if (!$this->isSoftDraft($atp)) {
                return back()->with('error', 'Only draft ATP records can be submitted.');
            }

            if (blank($atp->authority_purchase_supplier_id)) {
                return back()->with('error', 'Supplier is required before submitting.');
            }

            if (blank($atp->authority_purchase_date)) {
                return back()->with('error', 'Date is required before submitting.');
            }

            if (blank($atp->authority_purchase_received_by_name)) {
                return back()->with('error', 'Received By is required before submitting.');
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

            return redirect()
                ->route('purchaser.atp.index')
                ->with('success', 'Authority to Purchase submitted successfully.');
        });
    }

    public function approve($id)
    {
        return back()->with(
            'error',
            'ATP approval is handled by Accounting and is not available on the purchaser module.'
        );
    }

    public function reject(Request $request, $id)
    {
        return back()->with(
            'error',
            'ATP rejection is handled by Accounting and is not available on the purchaser module.'
        );
    }

    public function archive($id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->update([
                'authority_purchase_is_archived' => 1,
                'authority_purchase_updated_at' => now(),
            ]);

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

        DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->update([
                'authority_purchase_is_archived' => 0,
                'authority_purchase_updated_at' => now(),
            ]);

        return back()->with('success', 'ATP restored from archive.');
    }

    private function isSoftDraft(object $atp): bool
    {
        return $atp->authority_purchase_status === 'Pending'
            && blank($atp->authority_purchase_submitted_at)
            && (int) ($atp->authority_purchase_is_archived ?? 0) === 0;
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'Draft') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNull('authority_to_purchase_table.authority_purchase_submitted_at');
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
                'requisition_issue_slip_table.ris_request_type',
                'requisition_issue_slip_table.ris_manual_title',
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

            $rows[] = [
                'authority_purchase_id' => $authorityPurchaseId,
                'atp_description' => $item['description'],
                'atp_quantity' => $quantity,
                'atp_unit' => $item['unit'],
                'atp_unit_price' => $unitPrice,
                'atp_amount' => ($quantity !== null && $unitPrice !== null)
                    ? $quantity * $unitPrice
                    : null,
            ];
        }
        if ($rows !== []) {
            DB::table('authority_to_purchase_items_table')->insert($rows);
        }
    }

    /**
     * RIS statuses that may start an ATP after the current Admin/President workflow.
     * Legacy `Approved` stays eligible so existing Purchaser records are unchanged.
     */
    private function applyAtpEligibleRisScope($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('requisition_issue_slip_table.ris_status', [
                'Approved',
                'Directly Approved',
            ])->orWhere(function ($president) {
                $president->where('requisition_issue_slip_table.ris_status', 'Approved by the President')
                    ->whereNotNull('requisition_issue_slip_table.ris_issued_by_signature')
                    ->whereRaw('TRIM(requisition_issue_slip_table.ris_issued_by_signature) != ""');
            });
        });
    }

    private function risIsEligibleForAtp(object $ris): bool
    {
        $status = (string) ($ris->ris_status ?? '');

        if (in_array($status, ['Approved', 'Directly Approved'], true)) {
            return true;
        }

        if ($status === 'Approved by the President') {
            return trim((string) ($ris->ris_issued_by_signature ?? '')) !== '';
        }

        return false;
    }
}
