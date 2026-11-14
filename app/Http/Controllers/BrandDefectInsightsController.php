<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Support\BrandDefectInsights;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BrandDefectInsightsController extends Controller
{
    public function index(Request $request): View
    {
        $months = (int) $request->input('months', 24);
        if (! in_array($months, [6, 12, 24, 0], true)) {
            $months = 24;
        }

        $brands = BrandDefectInsights::ranked(40, $months > 0 ? $months : null);
        $flagged = $brands->where('avoid_ordering', true)->values();
        $highRisk = $brands->whereIn('risk', [
            BrandDefectInsights::RISK_HIGH,
            BrandDefectInsights::RISK_ELEVATED,
        ])->values();

        return view('purchaser.brand-insights.index', [
            'brands' => $brands,
            'flagged' => $flagged,
            'highRisk' => $highRisk,
            'months' => $months,
            'totalReports' => $brands->sum('report_count'),
        ]);
    }

    public function toggleAvoid(Request $request, int $brand): RedirectResponse
    {
        abort_unless(
            Schema::hasTable('brands_table') && Schema::hasColumn('brands_table', 'brand_avoid_ordering'),
            404
        );

        $record = Brand::findOrFail($brand);
        $avoid = $request->boolean('avoid');
        $note = trim((string) $request->input('note', ''));

        $payload = [
            'brand_avoid_ordering' => $avoid,
            'brand_updated_at' => now(),
        ];
        if (Schema::hasColumn('brands_table', 'brand_caution_note')) {
            $payload['brand_caution_note'] = $avoid
                ? ($note !== '' ? mb_substr($note, 0, 255) : 'Frequently reported defective — prefer other brands.')
                : null;
        }

        $record->update($payload);

        $message = $avoid
            ? $record->brand_name.' flagged — avoid ordering this brand when possible.'
            : $record->brand_name.' removed from avoid-ordering list.';

        return redirect()
            ->route('purchaser.brand-insights.index', $request->only('months'))
            ->with('success', $message);
    }

    /**
     * Create a brand master record (if missing) then flag avoid-ordering.
     */
    public function flagByName(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('brands_table'), 404);

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:150'],
            'avoid' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
            'months' => ['nullable', 'integer'],
        ]);

        $name = trim($validated['brand_name']);
        $record = Brand::query()
            ->whereRaw('LOWER(TRIM(brand_name)) = ?', [mb_strtolower($name)])
            ->first();

        if (! $record) {
            $now = now();
            $record = Brand::create([
                'brand_name' => $name,
                'brand_status' => 'Active',
                'brand_created_at' => $now,
                'brand_updated_at' => $now,
            ]);
        }

        $avoid = (bool) $validated['avoid'];
        $payload = ['brand_updated_at' => now()];
        if (Schema::hasColumn('brands_table', 'brand_avoid_ordering')) {
            $payload['brand_avoid_ordering'] = $avoid;
        }
        if (Schema::hasColumn('brands_table', 'brand_caution_note')) {
            $payload['brand_caution_note'] = $avoid
                ? (trim((string) ($validated['note'] ?? '')) ?: 'Frequently reported defective — prefer other brands.')
                : null;
        }
        $record->update($payload);

        return redirect()
            ->route('purchaser.brand-insights.index', array_filter([
                'months' => $validated['months'] ?? null,
            ]))
            ->with('success', $avoid
                ? $name.' flagged — avoid ordering this brand when possible.'
                : $name.' removed from avoid-ordering list.');
    }
}
