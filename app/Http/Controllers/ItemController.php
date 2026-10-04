<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Support\ProcurementPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.item_status' => ['required', 'in:Active,Inactive'],
        ]);

        $rows = collect($validated['items'])
            ->map(fn ($row) => [
                'name' => self::normalizeName($row['item_name'] ?? ''),
                'status' => $row['item_status'],
            ])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values();

        $keys = $rows->map(fn ($row) => mb_strtolower($row['name']));
        if ($keys->count() !== $keys->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate item names in this form. Each name must be unique.',
            ]);
        }

        $existing = self::findByNames($keys->all())->pluck('item_name');
        if ($existing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'These items already exist: '.$existing->implode(', '),
            ]);
        }

        $now = now();
        DB::transaction(function () use ($rows, $now) {
            foreach ($rows as $row) {
                Item::create([
                    'item_name' => $row['name'],
                    'item_status' => $row['status'],
                    'item_created_at' => $now,
                    'item_updated_at' => $now,
                ]);
            }
        });

        $message = $rows->count() === 1
            ? 'Item created successfully.'
            : $rows->count().' items created successfully.';

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'items'])->with('success', $message);
    }

    /**
     * Inline add from the RIS item dropdown. Reuses (and reactivates) a
     * same-named item instead of failing on duplicates.
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
        ]);

        $name = self::normalizeName($validated['item_name']);
        if ($name === '') {
            throw ValidationException::withMessages(['item_name' => 'Enter an item name.']);
        }

        $item = self::findByNames([mb_strtolower($name)])->first();
        $created = false;

        if ($item) {
            if ($item->item_status !== 'Active') {
                $item->update(['item_status' => 'Active', 'item_updated_at' => now()]);
            }
        } else {
            $item = Item::create([
                'item_name' => $name,
                'item_status' => 'Active',
                'item_created_at' => now(),
                'item_updated_at' => now(),
            ]);
            $created = true;
        }

        return response()->json([
            'id' => (string) $item->item_id,
            'label' => (string) $item->item_name,
            'created' => $created,
        ], $created ? 201 : 200);
    }

    public function update(Request $request, $itemId)
    {
        $item = Item::findOrFail($itemId);

        $validated = $request->validate([
            'item_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('items_table', 'item_name')->ignore($item->item_id, 'item_id'),
            ],
            'item_status' => ['required', 'in:Active,Inactive'],
        ]);

        $item->update([
            'item_name' => self::normalizeName($validated['item_name']),
            'item_status' => $validated['item_status'],
            'item_updated_at' => now(),
        ]);

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'items'])->with('success', 'Item updated successfully.');
    }

    public function destroy($itemId)
    {
        Item::findOrFail($itemId)->delete();

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'items'])->with('success', 'Item deleted successfully.');
    }

    private static function normalizeName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * @param  array<int, string>  $lowerNames
     */
    private static function findByNames(array $lowerNames)
    {
        if ($lowerNames === []) {
            return collect();
        }

        return Item::query()
            ->where(function ($q) use ($lowerNames) {
                foreach ($lowerNames as $name) {
                    $q->orWhereRaw('LOWER(item_name) = ?', [$name]);
                }
            })
            ->get();
    }
}
