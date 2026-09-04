<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:150', 'unique:brands_table,brand_name'],
            'brand_status' => ['required', 'in:Active,Inactive'],
        ]);

        Brand::create([
            'brand_name' => trim($validated['brand_name']),
            'brand_status' => $validated['brand_status'],
            'brand_created_at' => now(),
            'brand_updated_at' => now(),
        ]);

        return redirect()->route('purchaser.file-maintenance.index', ['tab' => 'brands'])->with('success', 'Brand created successfully.');
    }

    public function update(Request $request, $brandId)
    {
        $brand = Brand::findOrFail($brandId);

        $validated = $request->validate([
            'brand_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('brands_table', 'brand_name')->ignore($brand->brand_id, 'brand_id'),
            ],
            'brand_status' => ['required', 'in:Active,Inactive'],
        ]);

        $brand->update([
            'brand_name' => trim($validated['brand_name']),
            'brand_status' => $validated['brand_status'],
            'brand_updated_at' => now(),
        ]);

        return redirect()->route('purchaser.file-maintenance.index', ['tab' => 'brands'])->with('success', 'Brand updated successfully.');
    }

    public function destroy($brandId)
    {
        $brand = Brand::findOrFail($brandId);

        if (
            Schema::hasTable('requisition_issue_slip_items_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')
            && DB::table('requisition_issue_slip_items_table')->where('ris_item_brand_id', $brand->brand_id)->exists()
        ) {
            return redirect()
                ->route('purchaser.file-maintenance.index', ['tab' => 'brands'])
                ->with('error', 'Cannot delete this brand because it is used on one or more RIS items.');
        }

        $brand->delete();

        return redirect()->route('purchaser.file-maintenance.index', ['tab' => 'brands'])->with('success', 'Brand deleted successfully.');
    }
}
