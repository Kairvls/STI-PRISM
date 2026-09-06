<?php

namespace App\Http\Controllers;

use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Support\ProcurementPortal;

class UomController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'uom_name' => ['required', 'string', 'max:50', 'unique:uom_table,uom_name'],
            'uom_description' => ['nullable', 'string', 'max:255'],
        ]);

        Uom::create([
            'uom_name' => trim($validated['uom_name']),
            'uom_description' => filled($validated['uom_description'] ?? null) ? trim($validated['uom_description']) : null,
            'uom_created_at' => now(),
            'uom_updated_at' => now(),
        ]);

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'uom'])->with('success', 'UOM created successfully.');
    }

    public function update(Request $request, $uomId)
    {
        $uom = Uom::findOrFail($uomId);

        $validated = $request->validate([
            'uom_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('uom_table', 'uom_name')->ignore($uom->uom_id, 'uom_id'),
            ],
            'uom_description' => ['nullable', 'string', 'max:255'],
        ]);

        $uom->update([
            'uom_name' => trim($validated['uom_name']),
            'uom_description' => filled($validated['uom_description'] ?? null) ? trim($validated['uom_description']) : null,
            'uom_updated_at' => now(),
        ]);

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'uom'])->with('success', 'UOM updated successfully.');
    }

    public function destroy($uomId)
    {
        $uom = Uom::findOrFail($uomId);

        if (
            Schema::hasTable('requisition_issue_slip_items_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')
            && DB::table('requisition_issue_slip_items_table')->where('ris_item_uom_id', $uom->uom_id)->exists()
        ) {
            return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'uom'])->with('error', 'This UOM is used on RIS items and cannot be deleted.');
        }

        $uom->delete();

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'uom'])->with('success', 'UOM deleted successfully.');
    }
}
