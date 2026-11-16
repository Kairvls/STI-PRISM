<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InstallmentTermController extends Controller
{
    public function index(): View
    {
        abort_unless(Schema::hasTable('installment_terms_table'), 404);

        $terms = DB::table('installment_terms_table')
            ->orderByDesc('term_is_active')
            ->orderBy('term_months')
            ->orderBy('term_name')
            ->get();

        $dayIntervals = Schema::hasTable('installment_day_intervals_table')
            ? DB::table('installment_day_intervals_table')
                ->orderByDesc('interval_is_active')
                ->orderBy('interval_days')
                ->orderBy('interval_name')
                ->get()
            : collect();

        return view('purchaser.installment-terms.index', [
            'terms' => $terms,
            'dayIntervals' => $dayIntervals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_terms_table'), 404);

        $validated = $request->validate([
            'term_name' => ['required', 'string', 'max:120'],
            'term_months' => ['required', 'integer', 'min:1', 'max:60'],
            'term_is_active' => ['nullable', 'boolean'],
        ]);

        $now = now();
        DB::table('installment_terms_table')->insert([
            'term_name' => trim($validated['term_name']),
            'term_months' => (int) $validated['term_months'],
            'term_is_active' => $request->boolean('term_is_active', true) ? 1 : 0,
            'term_created_at' => $now,
            'term_updated_at' => $now,
        ]);

        return redirect()
            ->route('purchaser.installment-terms.index')
            ->with('success', 'Installment term added.');
    }

    public function update(Request $request, int $term): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_terms_table'), 404);

        $row = DB::table('installment_terms_table')->where('term_id', $term)->first();
        abort_unless($row, 404);

        $validated = $request->validate([
            'term_name' => ['required', 'string', 'max:120'],
            'term_months' => ['required', 'integer', 'min:1', 'max:60'],
            'term_is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('installment_terms_table')->where('term_id', $term)->update([
            'term_name' => trim($validated['term_name']),
            'term_months' => (int) $validated['term_months'],
            'term_is_active' => $request->boolean('term_is_active') ? 1 : 0,
            'term_updated_at' => now(),
        ]);

        return redirect()
            ->route('purchaser.installment-terms.index')
            ->with('success', 'Installment term updated.');
    }

    public function destroy(int $term): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_terms_table'), 404);

        $row = DB::table('installment_terms_table')->where('term_id', $term)->first();
        abort_unless($row, 404);

        $inUse = Schema::hasTable('cash_installment_plans_table')
            && DB::table('cash_installment_plans_table')->where('term_id', $term)->exists();

        if ($inUse) {
            DB::table('installment_terms_table')->where('term_id', $term)->update([
                'term_is_active' => 0,
                'term_updated_at' => now(),
            ]);

            return redirect()
                ->route('purchaser.installment-terms.index')
                ->with('success', 'Term is used by existing plans, so it was deactivated instead of deleted.');
        }

        DB::table('installment_terms_table')->where('term_id', $term)->delete();

        return redirect()
            ->route('purchaser.installment-terms.index')
            ->with('success', 'Installment term deleted.');
    }

    public function storeInterval(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_day_intervals_table'), 404);

        $validated = $request->validate([
            'interval_name' => ['required', 'string', 'max:120'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'interval_is_active' => ['nullable', 'boolean'],
        ]);

        $now = now();
        DB::table('installment_day_intervals_table')->insert([
            'interval_name' => trim($validated['interval_name']),
            'interval_days' => (int) $validated['interval_days'],
            'interval_is_active' => $request->boolean('interval_is_active', true) ? 1 : 0,
            'interval_created_at' => $now,
            'interval_updated_at' => $now,
        ]);

        return redirect()
            ->route('purchaser.installment-terms.index')
            ->with('success', 'Day-count interval added.');
    }

    public function updateInterval(Request $request, int $interval): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_day_intervals_table'), 404);

        $row = DB::table('installment_day_intervals_table')->where('interval_id', $interval)->first();
        abort_unless($row, 404);

        $validated = $request->validate([
            'interval_name' => ['required', 'string', 'max:120'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'interval_is_active' => ['nullable', 'boolean'],
        ]);

        DB::table('installment_day_intervals_table')->where('interval_id', $interval)->update([
            'interval_name' => trim($validated['interval_name']),
            'interval_days' => (int) $validated['interval_days'],
            'interval_is_active' => $request->boolean('interval_is_active') ? 1 : 0,
            'interval_updated_at' => now(),
        ]);

        return redirect()
            ->route('purchaser.installment-terms.index')
            ->with('success', 'Day-count interval updated.');
    }

    public function destroyInterval(int $interval): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_day_intervals_table'), 404);

        $row = DB::table('installment_day_intervals_table')->where('interval_id', $interval)->first();
        abort_unless($row, 404);

        $inUse = Schema::hasTable('cash_installment_plans_table')
            && Schema::hasColumn('cash_installment_plans_table', 'day_interval_id')
            && DB::table('cash_installment_plans_table')->where('day_interval_id', $interval)->exists();

        if ($inUse) {
            DB::table('installment_day_intervals_table')->where('interval_id', $interval)->update([
                'interval_is_active' => 0,
                'interval_updated_at' => now(),
            ]);

            return redirect()
                ->route('purchaser.installment-terms.index')
                ->with('success', 'Interval is used by existing plans, so it was deactivated instead of deleted.');
        }

        DB::table('installment_day_intervals_table')->where('interval_id', $interval)->delete();

        return redirect()
            ->route('purchaser.installment-terms.index')
            ->with('success', 'Day-count interval deleted.');
    }
}
