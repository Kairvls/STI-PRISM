<?php

namespace App\Http\Controllers;

use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\ProcurementPortal;

class UomController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.uom_name' => ['required', 'string', 'max:50'],
            'items.*.uom_description' => ['nullable', 'string', 'max:255'],
        ]);

        $names = collect($validated['items'])
            ->map(fn ($row) => mb_strtolower(trim((string) ($row['uom_name'] ?? ''))))
            ->filter()
            ->values();

        if ($names->count() !== $names->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Duplicate UOM names in this form. Each name must be unique.',
            ]);
        }

        $existing = Uom::query()
            ->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhereRaw('LOWER(uom_name) = ?', [$name]);
                }
            })
            ->pluck('uom_name');

        if ($existing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'These UOMs already exist: '.$existing->implode(', '),
            ]);
        }

        $now = now();
        $created = 0;

        DB::transaction(function () use ($validated, $now, &$created) {
            foreach ($validated['items'] as $row) {
                $name = trim((string) $row['uom_name']);
                if ($name === '') {
                    continue;
                }

                Uom::create([
                    'uom_name' => $name,
                    'uom_description' => filled($row['uom_description'] ?? null) ? trim((string) $row['uom_description']) : null,
                    'uom_created_at' => $now,
                    'uom_updated_at' => $now,
                ]);
                $created++;
            }
        });

        $message = $created === 1
            ? 'UOM created successfully.'
            : $created.' UOMs created successfully.';

        return ProcurementPortal::redirect('file-maintenance.index', ['tab' => 'uom'])->with('success', $message);
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
