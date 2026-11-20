<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
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

        $dayIntervals = collect();
        if (Schema::hasTable('installment_day_intervals_table')) {
            $intervalQuery = DB::table('installment_day_intervals_table')
                ->orderByDesc('interval_is_active');
            if (Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
                $intervalQuery->orderByRaw("CASE interval_unit WHEN 'days' THEN 0 WHEN 'monthly' THEN 1 WHEN 'quarterly' THEN 2 WHEN 'semiannual' THEN 3 WHEN 'annual' THEN 4 ELSE 9 END");
            }
            $dayIntervals = $intervalQuery
                ->orderBy('interval_days')
                ->orderBy('interval_name')
                ->get();
        }

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
            'term_months' => ['required', 'integer', 'min:1', 'max:12'],
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
            'term_months' => ['required', 'integer', 'min:1', 'max:12'],
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

    public function quickStore(Request $request): JsonResponse
    {
        abort_unless(Schema::hasTable('installment_terms_table'), 404);

        $validated = $request->validate([
            'term_name' => ['nullable', 'string', 'max:120'],
            'term_months' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $months = (int) $validated['term_months'];
        $name = trim((string) ($validated['term_name'] ?? ''));
        if ($name === '') {
            $name = 'Every '.$months.' month'.($months === 1 ? '' : 's');
        }

        $existing = DB::table('installment_terms_table')
            ->where('term_months', $months)
            ->orderByDesc('term_is_active')
            ->orderBy('term_id')
            ->first();

        if ($existing) {
            if ((int) ($existing->term_is_active ?? 0) !== 1) {
                DB::table('installment_terms_table')->where('term_id', $existing->term_id)->update([
                    'term_is_active' => 1,
                    'term_updated_at' => now(),
                ]);
            }

            return response()->json([
                'id' => (int) $existing->term_id,
                'name' => (string) $existing->term_name,
                'months' => (int) $existing->term_months,
                'created' => false,
            ]);
        }

        $now = now();
        $id = (int) DB::table('installment_terms_table')->insertGetId([
            'term_name' => $name,
            'term_months' => $months,
            'term_is_active' => 1,
            'term_created_at' => $now,
            'term_updated_at' => $now,
        ], 'term_id');

        return response()->json([
            'id' => $id,
            'name' => $name,
            'months' => $months,
            'created' => true,
        ], 201);
    }

    public function quickStoreInterval(Request $request): JsonResponse
    {
        abort_unless(Schema::hasTable('installment_day_intervals_table'), 404);

        $validated = $request->validate([
            'interval_name' => ['nullable', 'string', 'max:120'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $days = (int) $validated['interval_days'];
        $name = trim((string) ($validated['interval_name'] ?? ''));
        if ($name === '') {
            $name = 'Every '.$days.' days';
        }

        $existingQuery = DB::table('installment_day_intervals_table')
            ->where('interval_days', $days);
        if (Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
            $existingQuery->where(function ($query) {
                $query->where('interval_unit', 'days')->orWhereNull('interval_unit');
            });
        }
        $existing = $existingQuery
            ->orderByDesc('interval_is_active')
            ->orderBy('interval_id')
            ->first();

        if ($existing) {
            if ((int) ($existing->interval_is_active ?? 0) !== 1) {
                DB::table('installment_day_intervals_table')->where('interval_id', $existing->interval_id)->update([
                    'interval_is_active' => 1,
                    'interval_updated_at' => now(),
                ]);
            }

            return response()->json([
                'id' => (int) $existing->interval_id,
                'name' => (string) $existing->interval_name,
                'days' => (int) $existing->interval_days,
                'created' => false,
            ]);
        }

        $now = now();
        $payload = [
            'interval_name' => $name,
            'interval_days' => $days,
            'interval_is_active' => 1,
            'interval_created_at' => $now,
            'interval_updated_at' => $now,
        ];
        if (Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
            $payload['interval_unit'] = 'days';
        }
        if (Schema::hasColumn('installment_day_intervals_table', 'interval_is_preset')) {
            $payload['interval_is_preset'] = 0;
        }
        $id = (int) DB::table('installment_day_intervals_table')->insertGetId($payload, 'interval_id');

        return response()->json([
            'id' => $id,
            'name' => $name,
            'days' => $days,
            'created' => true,
        ], 201);
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
        $payload = [
            'interval_name' => trim($validated['interval_name']),
            'interval_days' => (int) $validated['interval_days'],
            'interval_is_active' => $request->boolean('interval_is_active', true) ? 1 : 0,
            'interval_created_at' => $now,
            'interval_updated_at' => $now,
        ];
        if (Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
            $payload['interval_unit'] = 'days';
        }
        if (Schema::hasColumn('installment_day_intervals_table', 'interval_is_preset')) {
            $payload['interval_is_preset'] = 0;
        }
        DB::table('installment_day_intervals_table')->insert($payload);

        return redirect()
            ->route('purchaser.installment-terms.index')
            ->with('success', 'Payment interval added.');
    }

    public function updateInterval(Request $request, int $interval): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_day_intervals_table'), 404);

        $row = DB::table('installment_day_intervals_table')->where('interval_id', $interval)->first();
        abort_unless($row, 404);

        if ((int) ($row->interval_is_preset ?? 0) === 1) {
            return redirect()
                ->route('purchaser.installment-terms.index')
                ->with('error', 'Monthly, quarterly, semiannual, and annual intervals are presets.');
        }

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
            ->with('success', 'Payment interval updated.');
    }

    public function destroyInterval(int $interval): RedirectResponse
    {
        abort_unless(Schema::hasTable('installment_day_intervals_table'), 404);

        $row = DB::table('installment_day_intervals_table')->where('interval_id', $interval)->first();
        abort_unless($row, 404);

        if ((int) ($row->interval_is_preset ?? 0) === 1) {
            return redirect()
                ->route('purchaser.installment-terms.index')
                ->with('error', 'Preset payment intervals stay on the list.');
        }

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
            ->with('success', 'Payment interval deleted.');
    }
}
