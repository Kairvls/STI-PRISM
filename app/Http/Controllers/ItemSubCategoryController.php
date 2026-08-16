<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\ItemSubCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemSubCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_category_id' => ['required', 'integer', 'exists:item_categories_table,item_category_id'],
            'item_subcategory_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('item_subcategories_table', 'item_subcategory_name')
                    ->where('item_category_id', $request->item_category_id),
            ],
            'item_subcategory_description' => ['nullable', 'string', 'max:255'],
            'item_subcategory_status' => ['required', 'in:Active,Inactive'],
        ]);

        ItemSubCategory::create([
            'item_category_id' => $validated['item_category_id'],
            'item_subcategory_name' => trim($validated['item_subcategory_name']),
            'item_subcategory_description' => filled($validated['item_subcategory_description'] ?? null) ? trim($validated['item_subcategory_description']) : null,
            'item_subcategory_status' => $validated['item_subcategory_status'],
            'item_subcategory_created_at' => now(),
            'item_subcategory_updated_at' => now(),
        ]);

        return redirect()->route('purchaser.file-maintenance.index', ['tab' => 'subcategories'])->with('success', 'Sub category created successfully.');
    }

    public function update(Request $request, $subcategoryId)
    {
        $subcategory = ItemSubCategory::findOrFail($subcategoryId);

        $validated = $request->validate([
            'item_category_id' => ['required', 'integer', 'exists:item_categories_table,item_category_id'],
            'item_subcategory_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('item_subcategories_table', 'item_subcategory_name')
                    ->where('item_category_id', $request->item_category_id)
                    ->ignore($subcategory->item_subcategory_id, 'item_subcategory_id'),
            ],
            'item_subcategory_description' => ['nullable', 'string', 'max:255'],
            'item_subcategory_status' => ['required', 'in:Active,Inactive'],
        ]);

        $subcategory->update([
            'item_category_id' => $validated['item_category_id'],
            'item_subcategory_name' => trim($validated['item_subcategory_name']),
            'item_subcategory_description' => filled($validated['item_subcategory_description'] ?? null) ? trim($validated['item_subcategory_description']) : null,
            'item_subcategory_status' => $validated['item_subcategory_status'],
            'item_subcategory_updated_at' => now(),
        ]);

        return redirect()->route('purchaser.file-maintenance.index', ['tab' => 'subcategories'])->with('success', 'Sub category updated successfully.');
    }

    public function destroy($subcategoryId)
    {
        $subcategory = ItemSubCategory::findOrFail($subcategoryId);
        $subcategory->delete();

        return redirect()->route('purchaser.file-maintenance.index', ['tab' => 'subcategories'])->with('success', 'Sub category deleted successfully.');
    }
}
