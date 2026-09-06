<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Support\PhoneNumber;
use App\Support\SupplierCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\ProcurementPortal;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->supplierListQuery();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('suppliers_table.supplier_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('suppliers_table.supplier_code', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('suppliers_table.supplier_store_type', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('physical_suppliers_table.company_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('physical_suppliers_table.contact_person', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('online_suppliers_table.shop_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('online_suppliers_table.contact_person', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('online_suppliers_table.contact_number', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('online_suppliers_table.app_used', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('online_suppliers_table.seller_id', 'LIKE', '%' . $request->search . '%');
            });
        }

        $allowedTypes = ['Physical Store', 'Online Store'];
        if ($request->filled('type') && in_array($request->type, $allowedTypes, true)) {
            $query->where('suppliers_table.supplier_store_type', $request->type);
        }

        if ($request->filled('status') && in_array($request->status, ['Active', 'Inactive'], true)) {
            $query->where('suppliers_table.supplier_is_active', $request->status === 'Inactive' ? 0 : 1);
        }

        if ($request->filled('blacklisted') && in_array($request->blacklisted, ['Yes', 'No'], true)) {
            $query->where('suppliers_table.supplier_is_blacklisted', $request->blacklisted === 'Yes' ? 1 : 0);
        }

        $suppliers = $query->orderByDesc('suppliers_table.supplier_id')->paginate(10)->withQueryString();

        $supplierSummary = [
            'total' => (int) DB::table('suppliers_table')->count(),
            'active' => (int) DB::table('suppliers_table')->where('supplier_is_active', 1)->count(),
            'inactive' => (int) DB::table('suppliers_table')->where('supplier_is_active', 0)->count(),
            'blacklisted' => (int) DB::table('suppliers_table')->where('supplier_is_blacklisted', 1)->count(),
        ];

        return view('purchaser.suppliers.index', compact('suppliers', 'supplierSummary'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'Physical');
        if ($type === 'Online') {
            return view('purchaser.suppliers.create-online');
        }
        return view('purchaser.suppliers.create-physical');
    }

    public function store(SupplierRequest $request)
    {
        $supplierId = DB::transaction(function () use ($request) {
            return $this->createSupplier($request);
        });

        return ProcurementPortal::redirect('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    // ADDED SUPPLIERS MODULE: reuse supplier creation from the RIS modal without duplicating database logic.
    public function quickStore(SupplierRequest $request)
    {
        $supplierId = DB::transaction(function () use ($request) {
            return $this->createSupplier($request);
        });

        return redirect()
            ->route(ProcurementPortal::routeName('ris.index'), ['selected_supplier' => $supplierId])
            ->with('success', 'Supplier created successfully. You can now select it for this RIS.');
    }

    public function edit($id)
    {
        $supplier = DB::table('suppliers_table')->where('supplier_id', $id)->first();
        if (!$supplier) {
            return back()->with('error', 'Supplier not found.');
        }

        if ($supplier->supplier_store_type === 'Physical Store') {
            $physical = DB::table('physical_suppliers_table')->where('supplier_id', $id)->first();
            return view('purchaser.suppliers.edit', compact('supplier', 'physical'));
        }

        $online = DB::table('online_suppliers_table')->where('supplier_id', $id)->first();
        return view('purchaser.suppliers.edit', compact('supplier', 'online'));
    }

    public function show($id)
    {
        $supplier = $this->supplierListQuery()
            ->where('suppliers_table.supplier_id', $id)
            ->first();

        if (!$supplier) {
            return back()->with('error', 'Supplier not found.');
        }

        $notes = DB::table('supplier_notes_table')
            ->leftJoin('users_table', 'supplier_notes_table.supplier_note_user_id', '=', 'users_table.user_id')
            ->where('supplier_notes_table.supplier_id', $id)
            ->select(
                'supplier_notes_table.*',
                'users_table.user_full_name as author_name'
            )
            ->orderByDesc('supplier_notes_table.created_at')
            ->orderByDesc('supplier_notes_table.supplier_note_id')
            ->get();

        $documentTrail = $this->buildDocumentTrail((int) $id);

        return view('purchaser.suppliers.show', compact('supplier', 'notes', 'documentTrail'));
    }

    public function storeNote(Request $request, $id)
    {
        $supplier = DB::table('suppliers_table')->where('supplier_id', $id)->first();
        if (!$supplier) {
            return back()->with('error', 'Supplier not found.');
        }

        $validated = $request->validate([
            'supplier_note_body' => ['required', 'string', 'max:2000'],
        ]);

        $this->insertSupplierNote((int) $id, 'note', trim($validated['supplier_note_body']));
        $this->writeSupplierAudit('Added supplier note', (int) $id, 'Note added to supplier #' . $id . '.');

        return redirect()
            ->route(ProcurementPortal::routeName('suppliers.show'), $id)
            ->with('success', 'Note added.');
    }

    public function blacklist(Request $request, $id)
    {
        $supplier = DB::table('suppliers_table')->where('supplier_id', $id)->first();
        if (!$supplier) {
            return back()->with('error', 'Supplier not found.');
        }

        $validated = $request->validate([
            'supplier_note_body' => ['required', 'string', 'max:2000'],
        ], [
            'supplier_note_body.required' => 'A reason is required to blacklist this supplier.',
        ]);

        $reason = trim($validated['supplier_note_body']);

        DB::transaction(function () use ($id, $reason) {
            DB::table('suppliers_table')->where('supplier_id', $id)->update([
                'supplier_is_blacklisted' => 1,
                'supplier_blacklist_reason' => $reason,
                'supplier_blacklisted_at' => now(),
                'supplier_blacklisted_by' => Auth::id(),
            ]);

            $this->insertSupplierNote((int) $id, 'blacklist', $reason);
        });

        $this->writeSupplierAudit('Blacklisted supplier', (int) $id, 'Supplier #' . $id . ' blacklisted.');

        return redirect()
            ->route(ProcurementPortal::routeName('suppliers.show'), $id)
            ->with('success', 'Supplier marked as blacklisted. They can still be selected with a warning.');
    }

    public function unblacklist(Request $request, $id)
    {
        $supplier = DB::table('suppliers_table')->where('supplier_id', $id)->first();
        if (!$supplier) {
            return back()->with('error', 'Supplier not found.');
        }

        $validated = $request->validate([
            'supplier_note_body' => ['nullable', 'string', 'max:2000'],
        ]);

        $body = trim((string) ($validated['supplier_note_body'] ?? ''));
        if ($body === '') {
            $body = 'Blacklist cleared.';
        }

        DB::transaction(function () use ($id, $body) {
            DB::table('suppliers_table')->where('supplier_id', $id)->update([
                'supplier_is_blacklisted' => 0,
                'supplier_blacklist_reason' => null,
                'supplier_blacklisted_at' => null,
                'supplier_blacklisted_by' => null,
            ]);

            $this->insertSupplierNote((int) $id, 'unblacklist', $body);
        });

        $this->writeSupplierAudit('Cleared supplier blacklist', (int) $id, 'Supplier #' . $id . ' blacklist cleared.');

        return redirect()
            ->route(ProcurementPortal::routeName('suppliers.show'), $id)
            ->with('success', 'Blacklist cleared.');
    }

    public function update(SupplierRequest $request, $id)
    {
        $supplier = DB::table('suppliers_table')->where('supplier_id', $id)->first();
        if (!$supplier) {
            return back()->with('error', 'Supplier not found.');
        }

        return DB::transaction(function () use ($request, $id, $supplier) {
            if (Schema::hasColumn('suppliers_table', 'operating_hours')) {
                DB::table('suppliers_table')->where('supplier_id', $id)->update([
                    'operating_hours' => $request->operating_hours ? trim($request->operating_hours) : null,
                ]);
            }

            // ADDED SUPPLIERS MODULE: keep existing parent supplier type, update only type-specific details.
            if ($supplier->supplier_store_type === 'Physical Store') {
                DB::table('physical_suppliers_table')->updateOrInsert(
                    ['supplier_id' => $id],
                    [
                        'company_name' => trim($request->company_name),
                        'contact_person' => $request->contact_person ? trim($request->contact_person) : null,
                        'email_address' => $request->email_address ? trim($request->email_address) : null,
                        'contact_number' => PhoneNumber::normalizeForStorage($request->contact_number),
                        'landline_number' => PhoneNumber::normalizeLandlineForStorage($request->landline_number),
                        'company_address' => $request->company_address ? trim($request->company_address) : null,
                    ]
                );
            } else {
                DB::table('online_suppliers_table')->updateOrInsert(
                    ['supplier_id' => $id],
                    [
                        'app_used' => trim($request->app_used),
                        'shop_name' => trim($request->shop_name),
                        'contact_person' => $request->contact_person ? trim($request->contact_person) : null,
                        'email_address' => $request->email_address ? trim($request->email_address) : null,
                        'contact_number' => PhoneNumber::normalizeForStorage($request->contact_number),
                        'store_url' => $request->store_url ? trim($request->store_url) : null,
                        'seller_id' => $request->seller_id ? trim($request->seller_id) : null,
                        'order_id' => $request->order_id ? trim($request->order_id) : null,
                    ]
                );
            }

            $this->writeSupplierAudit('Updated supplier', $id, 'Supplier #' . $id . ' updated.');

            return ProcurementPortal::redirect('suppliers.index')->with('success', 'Supplier updated successfully.');
        });
    }

    public function deactivate($id)
    {
        $exists = DB::table('suppliers_table')->where('supplier_id', $id)->exists();
        if (!$exists) {
            return back()->with('error', 'Supplier not found.');
        }

        DB::table('suppliers_table')->where('supplier_id', $id)->update(['supplier_is_active' => 0]);
        $this->writeSupplierAudit('Deactivated supplier', $id, 'Supplier #' . $id . ' deactivated.');

        return back()->with('success', 'Supplier deactivated.');
    }

    public function activate($id)
    {
        $exists = DB::table('suppliers_table')->where('supplier_id', $id)->exists();
        if (!$exists) {
            return back()->with('error', 'Supplier not found.');
        }

        DB::table('suppliers_table')->where('supplier_id', $id)->update(['supplier_is_active' => 1]);
        $this->writeSupplierAudit('Activated supplier', $id, 'Supplier #' . $id . ' activated.');

        return back()->with('success', 'Supplier activated.');
    }

    private function supplierListQuery()
    {
        return DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'suppliers_table.*',
                'physical_suppliers_table.company_name',
                'physical_suppliers_table.landline_number',
                'physical_suppliers_table.company_address',
                'online_suppliers_table.app_used',
                'online_suppliers_table.shop_name',
                'online_suppliers_table.store_url',
                'online_suppliers_table.seller_id',
                'online_suppliers_table.order_id',
                DB::raw('COALESCE(physical_suppliers_table.contact_person, online_suppliers_table.contact_person) as contact_person'),
                DB::raw('COALESCE(physical_suppliers_table.email_address, online_suppliers_table.email_address) as email_address'),
                DB::raw('COALESCE(physical_suppliers_table.contact_number, online_suppliers_table.contact_number) as contact_number')
            );
    }

    private function createSupplier(SupplierRequest $request): int
    {
        $createdAt = now();
        $name = $request->supplier_store_type === 'Physical Store'
            ? trim((string) $request->company_name)
            : trim((string) $request->shop_name);

        $payload = [
            'supplier_store_type' => $request->supplier_store_type,
            'supplier_is_active' => 1,
            'supplier_created_at' => $createdAt,
        ];

        if (Schema::hasColumn('suppliers_table', 'operating_hours')) {
            $payload['operating_hours'] = $request->operating_hours ? trim($request->operating_hours) : null;
        }

        if (Schema::hasColumn('suppliers_table', 'supplier_is_blacklisted')) {
            $payload['supplier_is_blacklisted'] = 0;
        }

        if (Schema::hasColumn('suppliers_table', 'supplier_code')) {
            $payload['supplier_code'] = $this->makeUniqueSupplierCode(
                (string) $request->supplier_store_type,
                $name,
                $createdAt
            );
        }

        $supplierId = DB::table('suppliers_table')->insertGetId($payload);

        if ($request->supplier_store_type === 'Physical Store') {
            DB::table('physical_suppliers_table')->insert([
                'supplier_id' => $supplierId,
                'company_name' => trim($request->company_name),
                'contact_person' => $request->contact_person ? trim($request->contact_person) : null,
                'email_address' => $request->email_address ? trim($request->email_address) : null,
                'contact_number' => PhoneNumber::normalizeForStorage($request->contact_number),
                'landline_number' => PhoneNumber::normalizeLandlineForStorage($request->landline_number),
                'company_address' => $request->company_address ? trim($request->company_address) : null,
            ]);
        } else {
            DB::table('online_suppliers_table')->insert([
                'supplier_id' => $supplierId,
                'app_used' => trim($request->app_used),
                'shop_name' => trim($request->shop_name),
                'contact_person' => $request->contact_person ? trim($request->contact_person) : null,
                'email_address' => $request->email_address ? trim($request->email_address) : null,
                'contact_number' => PhoneNumber::normalizeForStorage($request->contact_number),
                'store_url' => $request->store_url ? trim($request->store_url) : null,
                'seller_id' => $request->seller_id ? trim($request->seller_id) : null,
                'order_id' => $request->order_id ? trim($request->order_id) : null,
            ]);
        }

        $code = $payload['supplier_code'] ?? ('#' . $supplierId);
        $this->writeSupplierAudit('Created supplier', $supplierId, 'Supplier ' . $code . ' created.');

        return $supplierId;
    }

    private function makeUniqueSupplierCode(string $storeType, ?string $name, $createdAt): string
    {
        $base = SupplierCode::generate($storeType, $name, $createdAt);
        $code = $base;
        $n = 2;

        while (DB::table('suppliers_table')->where('supplier_code', $code)->exists()) {
            $code = $base . '-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            $n++;
        }

        return $code;
    }

    private function insertSupplierNote(int $supplierId, string $type, string $body): void
    {
        DB::table('supplier_notes_table')->insert([
            'supplier_id' => $supplierId,
            'supplier_note_user_id' => Auth::id(),
            'supplier_note_type' => $type,
            'supplier_note_body' => $body,
            'created_at' => now(),
        ]);
    }

    private function buildDocumentTrail(int $supplierId)
    {
        $rows = collect();

        $risHeader = DB::table('requisition_issue_slip_table')
            ->where('ris_supplier_id', $supplierId)
            ->select(
                'ris_id as doc_id',
                'ris_form_number as doc_number',
                'ris_status as doc_status',
                'ris_created_at as doc_date',
                DB::raw("'RIS' as doc_type")
            )
            ->get();

        $risItemIds = [];
        if (Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')) {
            $risItemIds = DB::table('requisition_issue_slip_items_table')
                ->where('ris_item_supplier_id', $supplierId)
                ->pluck('ris_id')
                ->unique()
                ->filter()
                ->values()
                ->all();
        }

        $risFromItems = collect();
        if (!empty($risItemIds)) {
            $existingHeaderIds = $risHeader->pluck('doc_id')->all();
            $missingIds = array_values(array_diff($risItemIds, $existingHeaderIds));
            if (!empty($missingIds)) {
                $risFromItems = DB::table('requisition_issue_slip_table')
                    ->whereIn('ris_id', $missingIds)
                    ->select(
                        'ris_id as doc_id',
                        'ris_form_number as doc_number',
                        'ris_status as doc_status',
                        'ris_created_at as doc_date',
                        DB::raw("'RIS' as doc_type")
                    )
                    ->get();
            }
        }

        $rows = $rows->merge($risHeader)->merge($risFromItems);

        $atpRows = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_supplier_id', $supplierId)
            ->select(
                'authority_purchase_id as doc_id',
                'authority_purchase_form_number as doc_number',
                'authority_purchase_status as doc_status',
                'authority_purchase_created_at as doc_date',
                DB::raw("'ATP' as doc_type")
            )
            ->get();
        $rows = $rows->merge($atpRows);

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_supplier_id')) {
            $rrRows = DB::table('receiving_reports_table')
                ->where('receiving_report_supplier_id', $supplierId)
                ->select(
                    'receiving_report_id as doc_id',
                    'receiving_report_form_number as doc_number',
                    'receiving_report_status as doc_status',
                    'receiving_report_created_at as doc_date',
                    DB::raw("'RR' as doc_type")
                )
                ->get();
            $rows = $rows->merge($rrRows);
        }

        $procurementRows = DB::table('procurement_requests_table')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->where('procurement_requests_table.procurement_request_supplier_id', $supplierId)
            ->select(
                'procurement_requests_table.procurement_request_id as doc_id',
                DB::raw("CONCAT('PR #', procurement_requests_table.procurement_request_id) as doc_number"),
                'procurement_requests_table.procurement_request_status as doc_status',
                'procurement_requests_table.procurement_request_created_at as doc_date',
                DB::raw("'Procurement' as doc_type"),
                'reports_table.report_problem_description as doc_detail'
            )
            ->get();
        $rows = $rows->merge($procurementRows);

        return $rows
            ->map(function ($row) {
                $row->doc_url = $this->documentTrailUrl($row->doc_type, (int) $row->doc_id);
                return $row;
            })
            ->sortByDesc(function ($row) {
                return $row->doc_date ? strtotime((string) $row->doc_date) : 0;
            })
            ->values();
    }

    private function documentTrailUrl(string $type, int $id): ?string
    {
        return match ($type) {
            'RIS' => ProcurementPortal::route('ris.index', ['search' => $id]),
            'ATP' => ProcurementPortal::route('atp.index', ['search' => $id]),
            'RR' => ProcurementPortal::route('rr.index', ['search' => $id]),
            default => null,
        };
    }

    private function writeSupplierAudit(string $action, int $supplierId, string $description): void
    {
        DB::table('audit_logs_table')->insert([
            'audit_log_user_id' => Auth::id(),
            'audit_log_action' => $action,
            'audit_log_table_name' => 'suppliers_table',
            'audit_log_reference_id' => $supplierId,
            'audit_log_description' => $description,
            'audit_log_ip_address' => request()->ip(),
            'audit_log_created_at' => now(),
        ]);
    }
}
