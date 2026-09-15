<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\ProcurementPortal;

class BrandController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.brand_name' => ['required', 'string', 'max:150'],
            'items.*.brand_status' => ['required', 'in:Active,Inactive'],
        ]);

        $names = collect($validated['items'])
            ->map(fn ($row) => mb_strtolower(trim((string) ($row['brand_name'] ?? ''))))
            ->filter()
            ->values();

        if ($names->count() !== $names->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate brand names in this form. Each name must be unique.',
            ]);
        }

        $existing = Brand::query()
            ->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhereRaw('LOWER(brand_name) = ?', [$name]);
                }
            })
            ->pluck('brand_name');

        if ($existing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'These brands already exist: '.$existing->implode(', '),
            ]);
        }

        $now = now();
        $created = 0;

        DB::transaction(function () use ($validated, $now, &$created) {
            foreach ($validated['items'] as $row) {
                $name = trim((string) $row['brand_name']);
                if ($name === '') {
                    continue;
                }

                Brand::create([
                    'brand_name' => $name,
                    'brand_status' => $row['brand_status'],
                    'brand_created_at' => $now,
                    'brand_updated_at' => $now,
                ]);
                $created++;
            }
        });

        $message = $created === 1
            ? 'Brand created successfully.'
            : $created.' brands created successfully.';

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'brands'])->with('success', $message);
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

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'brands'])->with('success', 'Brand updated successfully.');
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
                ->route(ProcurementPortal::routeName('file-maintenance.index'), ['tab' => 'brands'])
                ->with('error', 'Cannot delete this brand because it is used on one or more RIS items.');
        }

        $brand->delete();

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'brands'])->with('success', 'Brand deleted successfully.');
    }
}
