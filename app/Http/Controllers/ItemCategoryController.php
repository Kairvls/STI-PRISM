<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\ProcurementPortal;

class ItemCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_category_name' => ['required', 'string', 'max:150', 'unique:item_categories_table,item_category_name'],
            'item_category_description' => ['nullable', 'string', 'max:255'],
            'item_category_status' => ['required', 'in:Active,Inactive'],
        ]);

        ItemCategory::create([
            'item_category_name' => trim($validated['item_category_name']),
            'item_category_description' => filled($validated['item_category_description'] ?? null) ? trim($validated['item_category_description']) : null,
            'item_category_status' => $validated['item_category_status'],
            'item_category_created_at' => now(),
            'item_category_updated_at' => now(),
        ]);

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'categories'])->with('success', 'Category created successfully.');
    }

    public function update(Request $request, $categoryId)
    {
        $category = ItemCategory::findOrFail($categoryId);

        $validated = $request->validate([
            'item_category_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('item_categories_table', 'item_category_name')->ignore($category->item_category_id, 'item_category_id'),
            ],
            'item_category_description' => ['nullable', 'string', 'max:255'],
            'item_category_status' => ['required', 'in:Active,Inactive'],
        ]);

        $category->update([
            'item_category_name' => trim($validated['item_category_name']),
            'item_category_description' => filled($validated['item_category_description'] ?? null) ? trim($validated['item_category_description']) : null,
            'item_category_status' => $validated['item_category_status'],
            'item_category_updated_at' => now(),
        ]);

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'categories'])->with('success', 'Category updated successfully.');
    }

    public function destroy($categoryId)
    {
        $category = ItemCategory::withCount('subcategories')->findOrFail($categoryId);

        if ($category->subcategories_count > 0) {
            return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'categories'])->with('error', 'This category has sub categories and cannot be deleted.');
        }

        $category->delete();

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'categories'])->with('success', 'Category deleted successfully.');
    }
}
