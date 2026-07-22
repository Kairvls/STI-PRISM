<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthorityToPurchaseController extends Controller
{
    public function index(Request $request)
    {
        // ============================================================
        // ATP INDEX: Archive / Active View
        // ============================================================

        $archiveView = $request->query('view') === 'archive';


        // ============================================================
        // ATP INDEX: Main ATP Query
        // ============================================================

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

                'procurement_requests_table.procurement_request_id',

                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',

                'equipment_table.equipment_name',

                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            );


        // ============================================================
        // ATP INDEX: Active / Archive Filter
        // ============================================================

        if ($archiveView) {

            $query->where(
                'authority_to_purchase_table.authority_purchase_is_archived',
                1
            );

        } else {

            $query->where(function ($q) {

                $q->whereNull(
                    'authority_to_purchase_table.authority_purchase_is_archived'
                )
                ->orWhere(
                    'authority_to_purchase_table.authority_purchase_is_archived',
                    0
                );

            });
        }


        // ============================================================
        // ATP INDEX: Search
        // ============================================================

        if ($request->filled('search')) {

            $query->where(function ($subQuery) use ($request) {

                $search = '%' . $request->search . '%';

                $subQuery
                    ->where(
                        'authority_to_purchase_table.authority_purchase_form_number',
                        'LIKE',
                        $search
                    )
                    ->orWhere(
                        'requisition_issue_slip_table.ris_form_number',
                        'LIKE',
                        $search
                    )
                    ->orWhere(
                        'physical_suppliers_table.company_name',
                        'LIKE',
                        $search
                    )
                    ->orWhere(
                        'online_suppliers_table.shop_name',
                        'LIKE',
                        $search
                    )
                    ->orWhere(
                        'equipment_table.equipment_name',
                        'LIKE',
                        $search
                    )
                    ->orWhere(
                        'reports_table.report_unlisted_equipment_name',
                        'LIKE',
                        $search
                    );

            });
        }


        // ============================================================
        // ATP INDEX: Status Filter
        // ============================================================

        if ($request->filled('status')) {

            $query->where(
                'authority_to_purchase_table.authority_purchase_status',
                $request->status
            );
        }


        // ============================================================
        // ATP INDEX: RIS Request Type Filter
        // ============================================================

        if ($request->filled('request_type')) {

            $query->where(
                'requisition_issue_slip_table.ris_request_type',
                $request->request_type
            );
        }


        // ============================================================
        // ATP INDEX: Pagination
        // ============================================================

        $atps = $query
            ->orderByDesc(
                'authority_to_purchase_table.authority_purchase_created_at'
            )
            ->paginate(10)
            ->withQueryString();


        // ============================================================
        // ATP INDEX: Summary Cards
        // ============================================================

        $atpSummary = [

            'draft' => DB::table('authority_to_purchase_table')
                ->whereNull('authority_purchase_submitted_at')
                ->where(function ($q) {

                    $q->whereNull('authority_purchase_is_archived')
                        ->orWhere('authority_purchase_is_archived', 0);

                })
                ->count(),


            'submitted' => DB::table('authority_to_purchase_table')
                ->whereNotNull('authority_purchase_submitted_at')
                ->where('authority_purchase_status', 'Pending')
                ->where(function ($q) {

                    $q->whereNull('authority_purchase_is_archived')
                        ->orWhere('authority_purchase_is_archived', 0);

                })
                ->count(),


            'approved' => DB::table('authority_to_purchase_table')
                ->where('authority_purchase_status', 'Approved')
                ->where(function ($q) {

                    $q->whereNull('authority_purchase_is_archived')
                        ->orWhere('authority_purchase_is_archived', 0);

                })
                ->count(),


            'rejected' => DB::table('authority_to_purchase_table')
                ->where('authority_purchase_status', 'Rejected')
                ->where(function ($q) {

                    $q->whereNull('authority_purchase_is_archived')
                        ->orWhere('authority_purchase_is_archived', 0);

                })
                ->count(),
        ];


        // ============================================================
        // CREATE ATP MODAL: Eligible Approved RIS
        // ============================================================

        $eligibleRis = DB::table('requisition_issue_slip_table')

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

            ->where(
                'requisition_issue_slip_table.ris_status',
                'Approved'
            )

            // Prevent creating another ATP for the same RIS
            ->whereNotExists(function ($query) {

                $query
                    ->select(DB::raw(1))
                    ->from('authority_to_purchase_table')
                    ->whereColumn(
                        'authority_to_purchase_table.authority_purchase_ris_id',
                        'requisition_issue_slip_table.ris_id'
                    );

            })

            ->select(
                'requisition_issue_slip_table.ris_id',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_request_type',
                'requisition_issue_slip_table.ris_manual_title',

                'procurement_requests_table.procurement_request_id',

                'equipment_table.equipment_name',

                'reports_table.report_unlisted_equipment_name'
            )

            ->orderByDesc(
                'requisition_issue_slip_table.ris_created_at'
            )

            ->get();


        // ============================================================
        // CREATE / EDIT ATP MODALS: Active Suppliers
        // ============================================================

        $suppliers = DB::table('suppliers_table')

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

            ->where(
                'suppliers_table.supplier_is_active',
                1
            )

            ->orderBy(
                'suppliers_table.supplier_id'
            )

            ->get();


        // ============================================================
        // VIEW / EDIT ATP MODALS: Load Items for Current ATP Page
        // ============================================================

        $atpIds = $atps
            ->getCollection()
            ->pluck('authority_purchase_id');


        $atpItems = DB::table('authority_to_purchase_items_table')

            ->whereIn(
                'authority_purchase_id',
                $atpIds
            )

            ->orderBy('atp_item_id')

            ->get()

            ->groupBy('authority_purchase_id');


        // ============================================================
        // CREATE ATP MODAL: Optional Preselected RIS
        // ============================================================

        $selectedRisId = $request->query('selected_ris');


        // ============================================================
        // RETURN EVERYTHING TO THE SINGLE INDEX.BLADE.PHP
        // ============================================================

        return view(
            'purchaser.authority-to-purchase.index',
            compact(
                'atps',
                'archiveView',
                'atpSummary',
                'eligibleRis',
                'suppliers',
                'atpItems',
                'selectedRisId'
            )
        );
    }

    public function create(Request $request)
    {
        $selectedRisId = $request->query('selected_ris');

        $eligibleRis = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->where('requisition_issue_slip_table.ris_status', 'Approved')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('authority_to_purchase_table')
                    ->whereColumn('authority_to_purchase_table.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id');
            })
            ->select(
                'requisition_issue_slip_table.ris_id',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_request_type',
                'requisition_issue_slip_table.ris_manual_title',
                'procurement_requests_table.procurement_request_id',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name'
            )
            ->orderByDesc('requisition_issue_slip_table.ris_created_at')
            ->get();

        $suppliers = DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_store_type',
                'suppliers_table.supplier_is_active',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->where('suppliers_table.supplier_is_active', 1)
            ->orderBy('suppliers_table.supplier_id')
            ->get();

        return view(
            'purchaser.authority-to-purchase.create',
            compact('eligibleRis', 'suppliers', 'selectedRisId')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'authority_purchase_ris_id' => ['required', 'integer', 'exists:requisition_issue_slip_table,ris_id'],
            'authority_purchase_supplier_id' => ['required', 'integer', 'exists:suppliers_table,supplier_id'],
            'authority_purchase_date' => ['required', 'date'],
            'authority_purchase_received_by_name' => ['required', 'string', 'max:255'],
            'authority_purchase_reference_po_no' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $validated['authority_purchase_ris_id'])
            ->where('ris_status', 'Approved')
            ->first();

        if (!$ris) {
            return back()->withInput()->with('error', 'Only approved RIS records may be used to create ATP.');
        }

        if (DB::table('authority_to_purchase_table')->where('authority_purchase_ris_id', $validated['authority_purchase_ris_id'])->exists()) {
            return back()->withInput()->with('error', 'An Authority to Purchase already exists for the selected RIS.');
        }

        $items = collect($validated['items'])
            ->filter(function ($item) {
                return filled($item['description'] ?? null);
            })
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Please add at least one ATP item.');
        }

        return DB::transaction(function () use ($validated, $items) {
            $authorityPurchaseId = DB::table('authority_to_purchase_table')->insertGetId([
                'authority_purchase_ris_id' => $validated['authority_purchase_ris_id'],
                'authority_purchase_supplier_id' => $validated['authority_purchase_supplier_id'],
                'authority_purchase_date' => $validated['authority_purchase_date'],
                'authority_purchase_received_by_name' => $validated['authority_purchase_received_by_name'],
                'authority_purchase_reference_po_no' => $validated['authority_purchase_reference_po_no'] ?? null,
                'authority_purchase_status' => 'Pending',
                'authority_purchase_created_by' => Auth::id(),
                'authority_purchase_created_at' => now(),
            ]);

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $authorityPurchaseId)
                ->update([
                    'authority_purchase_form_number' => 'ATP-' . now()->format('Y') . '-' . str_pad($authorityPurchaseId, 5, '0', STR_PAD_LEFT),
                ]);

            foreach ($items as $item) {
                DB::table('authority_to_purchase_items_table')->insert([
                    'authority_purchase_id' => $authorityPurchaseId,
                    'atp_description' => $item['description'],
                    'atp_quantity' => $item['quantity'] ?? 1,
                    'atp_unit' => $item['unit'] ?? null,
                    'atp_unit_price' => $item['unit_price'] ?? null,
                    'atp_amount' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                ]);
            }

            return redirect()->route('purchaser.atp.index')->with('success', 'Authority to Purchase draft created successfully.');
        });
    }

    public function show($id)
    {
        $atp = DB::table('authority_to_purchase_table')
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
                'requisition_issue_slip_table.ris_request_type',
                'requisition_issue_slip_table.ris_manual_title',
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

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase record not found.');
        }

        $items = DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $id)
            ->orderBy('atp_item_id')
            ->get();

        return view('purchaser.authority-to-purchase.show', compact('atp', 'items'));
    }

    public function edit($id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase record not found.');
        }

        if ($atp->authority_purchase_submitted_at !== null || $atp->authority_purchase_status !== 'Pending') {
            return back()->with('error', 'Only draft ATP records can be edited.');
        }

        $items = DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $id)
            ->orderBy('atp_item_id')
            ->get();

        $suppliers = DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_store_type',
                'suppliers_table.supplier_is_active',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->where('suppliers_table.supplier_is_active', 1)
            ->orderBy('suppliers_table.supplier_id')
            ->get();

        return view('purchaser.authority-to-purchase.edit', compact('atp', 'items', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase record not found.');
        }

        if ($atp->authority_purchase_submitted_at !== null || $atp->authority_purchase_status !== 'Pending') {
            return back()->with('error', 'Only draft ATP records can be updated.');
        }

        $validated = $request->validate([
            'authority_purchase_supplier_id' => ['required', 'integer', 'exists:suppliers_table,supplier_id'],
            'authority_purchase_date' => ['required', 'date'],
            'authority_purchase_received_by_name' => ['required', 'string', 'max:255'],
            'authority_purchase_reference_po_no' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = collect($validated['items'])
            ->filter(function ($item) {
                return filled($item['description'] ?? null);
            })
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Please keep at least one ATP item.');
        }

        return DB::transaction(function () use ($validated, $id) {
            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_supplier_id' => $validated['authority_purchase_supplier_id'],
                    'authority_purchase_date' => $validated['authority_purchase_date'],
                    'authority_purchase_received_by_name' => $validated['authority_purchase_received_by_name'],
                    'authority_purchase_reference_po_no' => $validated['authority_purchase_reference_po_no'] ?? null,
                    'authority_purchase_updated_at' => now(),
                ]);

            DB::table('authority_to_purchase_items_table')
                ->where('authority_purchase_id', $id)
                ->delete();

            foreach ($items as $item) {
                DB::table('authority_to_purchase_items_table')->insert([
                    'authority_purchase_id' => $id,
                    'atp_description' => $item['description'],
                    'atp_quantity' => $item['quantity'] ?? 1,
                    'atp_unit' => $item['unit'] ?? null,
                    'atp_unit_price' => $item['unit_price'] ?? null,
                    'atp_amount' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                ]);
            }

            return redirect()
                ->route('purchaser.atp.index')
                ->with('success', 'Authority to Purchase draft updated successfully.');
        });
    }

    public function submit($id)
    {
        return DB::transaction(function () use ($id) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(!$atp, 404);

            if ($atp->authority_purchase_status !== 'Pending') {
                return back()->with('error', 'Only pending ATP records can be submitted.');
            }

            if ($atp->authority_purchase_submitted_at !== null) {
                return back()->with('error', 'This ATP has already been submitted.');
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_submitted_by' => Auth::id(),
                    'authority_purchase_submitted_at' => now(),
                    'authority_purchase_updated_at' => now(),
                ]);

            return back()->with('success', 'ATP submitted for approval.');
        });
    }

    public function approve($id)
    {
        return DB::transaction(function () use ($id) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(!$atp, 404);

            if ($atp->authority_purchase_status !== 'Pending' || $atp->authority_purchase_submitted_at === null) {
                return back()->with('error', 'Only submitted ATP records can be approved.');
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_status' => 'Approved',
                    'authority_purchase_authorized_by_signature' => Auth::user()->user_full_name ?? Auth::user()->name,
                    'authority_purchase_updated_at' => now(),
                ]);

            return back()->with('success', 'ATP approved successfully.');
        });
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'authority_purchase_rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(!$atp, 404);

            if ($atp->authority_purchase_status !== 'Pending' || $atp->authority_purchase_submitted_at === null) {
                return back()->with('error', 'Only submitted ATP records can be rejected.');
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_status' => 'Rejected',
                    'authority_purchase_rejection_reason' => $validated['authority_purchase_rejection_reason'],
                    'authority_purchase_updated_at' => now(),
                ]);

            return back()->with('success', 'ATP rejected successfully.');
        });
    }

    public function archive($id)
    {
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
        DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->update([
                'authority_purchase_is_archived' => 0,
                'authority_purchase_updated_at' => now(),
            ]);

        return back()->with('success', 'ATP restored from archive.');
    }
}
