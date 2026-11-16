<?php

namespace App\Http\Controllers;

use App\Support\SupplierDeliveryInsights;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SupplierDeliveryInsightsController extends Controller
{
    public function index(Request $request): View
    {
        $months = (int) $request->input('months', 24);
        if (! in_array($months, [6, 12, 24, 0], true)) {
            $months = 24;
        }

        $suppliers = SupplierDeliveryInsights::ranked(40, $months > 0 ? $months : null);
        $flagged = $suppliers->where('avoid_ordering', true)->values();
        $highRisk = $suppliers->whereIn('risk', [
            SupplierDeliveryInsights::RISK_HIGH,
            SupplierDeliveryInsights::RISK_ELEVATED,
        ])->values();

        return view('purchaser.supplier-insights.index', [
            'suppliers' => $suppliers,
            'flagged' => $flagged,
            'highRisk' => $highRisk,
            'months' => $months,
            'totalIssues' => $suppliers->sum('issue_count'),
        ]);
    }

    public function toggleCaution(Request $request, int $supplier): RedirectResponse
    {
        abort_unless(Schema::hasTable('suppliers_table'), 404);

        $record = DB::table('suppliers_table')->where('supplier_id', $supplier)->first();
        abort_unless($record, 404);

        $avoid = $request->boolean('avoid');
        $note = trim((string) $request->input('note', ''));
        $name = $this->supplierDisplayName($supplier);

        if ($avoid) {
            $reason = $note !== ''
                ? mb_substr($note, 0, 2000)
                : 'Flagged from Supplier Insights — repeated delivery issues (missing parts, damage, or no refund).';

            DB::table('suppliers_table')->where('supplier_id', $supplier)->update([
                'supplier_is_blacklisted' => 1,
                'supplier_blacklist_reason' => $reason,
                'supplier_blacklisted_at' => now(),
                'supplier_blacklisted_by' => Auth::id(),
            ]);

            $message = $name.' flagged — prefer other suppliers when ordering.';
        } else {
            DB::table('suppliers_table')->where('supplier_id', $supplier)->update([
                'supplier_is_blacklisted' => 0,
                'supplier_blacklist_reason' => null,
                'supplier_blacklisted_at' => null,
                'supplier_blacklisted_by' => null,
            ]);

            $message = $name.' removed from caution list.';
        }

        return redirect()
            ->route('purchaser.supplier-insights.index', $request->only('months'))
            ->with('success', $message);
    }

    private function supplierDisplayName(int $supplierId): string
    {
        $row = DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('suppliers_table.supplier_id', $supplierId)
            ->selectRaw("COALESCE(physical_suppliers_table.company_name, online_suppliers_table.shop_name, CONCAT('Supplier #', suppliers_table.supplier_id)) as supplier_name")
            ->first();

        return $row ? trim((string) $row->supplier_name) : 'Supplier #'.$supplierId;
    }
}
