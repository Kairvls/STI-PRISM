<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\ProcurementPortal;

class ItemCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.item_category_name' => ['required', 'string', 'max:150'],
            'items.*.item_category_description' => ['nullable', 'string', 'max:255'],
            'items.*.item_category_status' => ['required', 'in:Active,Inactive'],
        ]);

        $names = collect($validated['items'])
            ->map(fn ($row) => mb_strtolower(trim((string) ($row['item_category_name'] ?? ''))))
            ->filter()
            ->values();

        if ($names->count() !== $names->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate category names in this form. Each name must be unique.',
            ]);
        }

        $existing = ItemCategory::query()
            ->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhereRaw('LOWER(item_category_name) = ?', [$name]);
                }
            })
            ->pluck('item_category_name');

        if ($existing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'These categories already exist: '.$existing->implode(', '),
            ]);
        }

        $now = now();
        $created = 0;

        DB::transaction(function () use ($validated, $now, &$created) {
            foreach ($validated['items'] as $row) {
                $name = trim((string) $row['item_category_name']);
                if ($name === '') {
                    continue;
                }

                ItemCategory::create([
                    'item_category_name' => $name,
                    'item_category_description' => filled($row['item_category_description'] ?? null)
                        ? trim((string) $row['item_category_description'])
                        : null,
                    'item_category_status' => $row['item_category_status'],
                    'item_category_created_at' => $now,
                    'item_category_updated_at' => $now,
                ]);
                $created++;
            }
        });

        $message = $created === 1
            ? 'Category created successfully.'
            : $created.' categories created successfully.';

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'categories'])->with('success', $message);
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
