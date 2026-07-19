<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->supplierListQuery();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('suppliers_table.supplier_id', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('suppliers_table.supplier_store_type', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('physical_suppliers_table.company_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('physical_suppliers_table.contact_person', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('online_suppliers_table.shop_name', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('online_suppliers_table.app_used', 'LIKE', '%' . $request->search . '%');
            });
        }

        // ADDED SUPPLIERS MODULE: supplier type filter.
        if ($request->filled('type')) {
            $query->where('suppliers_table.supplier_store_type', $request->type);
        }

        // ADDED SUPPLIERS MODULE: active/inactive filter.
        if ($request->filled('status')) {
            $query->where('suppliers_table.supplier_is_active', $request->status === 'Inactive' ? 0 : 1);
        }

        $suppliers = $query->orderByDesc('suppliers_table.supplier_id')->paginate(10)->withQueryString();

        return view('purchaser.suppliers.index', compact('suppliers'));
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

        return redirect()->route('purchaser.suppliers.index')->with('success', 'Supplier created successfully.');
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

        $procurementHistory = DB::table('procurement_requests_table')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->where('procurement_requests_table.procurement_request_supplier_id', $id)
            ->select(
                'procurement_requests_table.*',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'reports_table.report_problem_description'
            )
            ->orderByDesc('procurement_requests_table.procurement_request_created_at')
            ->get();

        return view('purchaser.suppliers.show', compact('supplier', 'procurementHistory'));
    }

    public function update(SupplierRequest $request, $id)
    {
        $supplier = DB::table('suppliers_table')->where('supplier_id', $id)->first();
        if (!$supplier) {
            return back()->with('error', 'Supplier not found.');
        }

        return DB::transaction(function () use ($request, $id, $supplier) {
            // ADDED SUPPLIERS MODULE: keep existing parent supplier type, update only type-specific details.
            if ($supplier->supplier_store_type === 'Physical Store') {
                DB::table('physical_suppliers_table')->updateOrInsert(
                    ['supplier_id' => $id],
                    [
                        'company_name' => trim($request->company_name),
                        'contact_person' => $request->contact_person ? trim($request->contact_person) : null,
                        'email_address' => $request->email_address ? trim($request->email_address) : null,
                        'contact_number' => $request->contact_number ? trim($request->contact_number) : null,
                        'company_address' => $request->company_address ? trim($request->company_address) : null,
                    ]
                );
            } else {
                DB::table('online_suppliers_table')->updateOrInsert(
                    ['supplier_id' => $id],
                    [
                        'app_used' => trim($request->app_used),
                        'shop_name' => trim($request->shop_name),
                        'order_id' => $request->order_id ? trim($request->order_id) : null,
                    ]
                );
            }

            $this->writeSupplierAudit('Updated supplier', $id, 'Supplier #' . $id . ' updated.');

            return redirect()->route('purchaser.suppliers.index')->with('success', 'Supplier updated successfully.');
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
                'physical_suppliers_table.contact_person',
                'physical_suppliers_table.email_address',
                'physical_suppliers_table.contact_number',
                'physical_suppliers_table.company_address',
                'online_suppliers_table.app_used',
                'online_suppliers_table.shop_name',
                'online_suppliers_table.order_id'
            );
    }

    private function createSupplier(SupplierRequest $request): int
    {
        $supplierId = DB::table('suppliers_table')->insertGetId([
            'supplier_store_type' => $request->supplier_store_type,
            'supplier_is_active' => 1,
            'supplier_created_at' => now(),
        ]);

        if ($request->supplier_store_type === 'Physical Store') {
            DB::table('physical_suppliers_table')->insert([
                'supplier_id' => $supplierId,
                'company_name' => trim($request->company_name),
                'contact_person' => $request->contact_person ? trim($request->contact_person) : null,
                'email_address' => $request->email_address ? trim($request->email_address) : null,
                'contact_number' => $request->contact_number ? trim($request->contact_number) : null,
                'company_address' => $request->company_address ? trim($request->company_address) : null,
            ]);
        } else {
            DB::table('online_suppliers_table')->insert([
                'supplier_id' => $supplierId,
                'app_used' => trim($request->app_used),
                'shop_name' => trim($request->shop_name),
                'order_id' => $request->order_id ? trim($request->order_id) : null,
            ]);
        }

        $this->writeSupplierAudit('Created supplier', $supplierId, 'Supplier #' . $supplierId . ' created.');

        return $supplierId;
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
