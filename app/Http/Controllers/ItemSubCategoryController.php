<?php

namespace App\Http\Controllers;

use App\Models\ItemSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\ProcurementPortal;

class ItemSubCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.item_category_id' => ['required', 'integer', 'exists:item_categories_table,item_category_id'],
            'items.*.item_subcategory_name' => ['required', 'string', 'max:150'],
            'items.*.item_subcategory_description' => ['nullable', 'string', 'max:255'],
            'items.*.item_subcategory_status' => ['required', 'in:Active,Inactive'],
        ]);

        $keys = collect($validated['items'])->map(function ($row) {
            return ((int) $row['item_category_id']).'|'.mb_strtolower(trim((string) $row['item_subcategory_name']));
        });

        if ($keys->count() !== $keys->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate sub category names under the same category in this form.',
            ]);
        }

        foreach ($validated['items'] as $index => $row) {
            $name = trim((string) $row['item_subcategory_name']);
            $exists = ItemSubCategory::query()
                ->where('item_category_id', (int) $row['item_category_id'])
                ->whereRaw('LOWER(item_subcategory_name) = ?', [mb_strtolower($name)])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_subcategory_name" => "\"{$name}\" already exists for the selected category.",
                ]);
            }
        }

        $now = now();
        $created = 0;

        DB::transaction(function () use ($validated, $now, &$created) {
            foreach ($validated['items'] as $row) {
                $name = trim((string) $row['item_subcategory_name']);
                if ($name === '') {
                    continue;
                }

                ItemSubCategory::create([
                    'item_category_id' => (int) $row['item_category_id'],
                    'item_subcategory_name' => $name,
                    'item_subcategory_description' => filled($row['item_subcategory_description'] ?? null)
                        ? trim((string) $row['item_subcategory_description'])
                        : null,
                    'item_subcategory_status' => $row['item_subcategory_status'],
                    'item_subcategory_created_at' => $now,
                    'item_subcategory_updated_at' => $now,
                ]);
                $created++;
            }
        });

        $message = $created === 1
            ? 'Sub category created successfully.'
            : $created.' sub categories created successfully.';

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'subcategories'])->with('success', $message);
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

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'subcategories'])->with('success', 'Sub category updated successfully.');
    }

    public function destroy($subcategoryId)
    {
        $subcategory = ItemSubCategory::findOrFail($subcategoryId);
        $subcategory->delete();

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'subcategories'])->with('success', 'Sub category deleted successfully.');
    }
}
