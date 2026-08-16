<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($request->filled('search')) {
            $query->where('brand_name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('brand_status', $request->status);
        }

        $brands = $query->orderBy('brand_name')->paginate(10)->withQueryString();

        $summary = [
            'total' => Brand::count(),
            'active' => Brand::where('brand_status', 'Active')->count(),
            'inactive' => Brand::where('brand_status', 'Inactive')->count(),
        ];

        return view('purchaser.file-maintenance.brands.index', compact('brands', 'summary'));
    }

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

        return redirect()->route('purchaser.brands.index')->with('success', 'Brand created successfully.');
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

        return redirect()->route('purchaser.brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy($brandId)
    {
        $brand = Brand::findOrFail($brandId);
        $brand->delete();

        return redirect()->route('purchaser.brands.index')->with('success', 'Brand deleted successfully.');
    }
}
