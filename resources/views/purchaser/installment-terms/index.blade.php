@extends('layouts.purchaser-layout')

@section('page-title', 'Installment Terms')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="pur-card overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">File maintenance</p>
            <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Cash installment terms</h2>
            <p class="mt-1 text-sm text-slate-600">Used when planning Cash Installment on an ATP (e.g. every 3 or 4 months).</p>
        </div>

        <form method="POST" action="{{ route('purchaser.installment-terms.store') }}" class="grid gap-3 border-b border-gray-100 px-5 py-4 sm:grid-cols-[1fr_8rem_auto] sm:items-end sm:px-6">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Term name</label>
                <input type="text" name="term_name" value="{{ old('term_name') }}" required maxlength="120" placeholder="e.g. Every 3 months" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-[#0025cc]">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Months / RFCs</label>
                <input type="number" name="term_months" value="{{ old('term_months', 3) }}" min="1" max="12" inputmode="numeric" required class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-[#0025cc]" oninput="this.value = String(this.value || '').replace(/\\D/g, '').slice(0, 2); if (Number(this.value) > 12) this.value = '12';">
            </div>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">
                Add term
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                        <th class="px-5 py-3">Term</th>
                        <th class="px-5 py-3">Months</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($terms as $term)
                        <tr>
                            <td class="px-5 py-3.5">
                                <form method="POST" action="{{ route('purchaser.installment-terms.update', $term->term_id) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="term_name" value="{{ $term->term_name }}" required maxlength="120" class="h-9 min-w-[12rem] flex-1 rounded-lg border border-slate-200 bg-white px-2.5 text-sm outline-none focus:border-[#0025cc]">
                                    <input type="number" name="term_months" value="{{ $term->term_months }}" min="1" max="12" inputmode="numeric" required class="h-9 w-20 rounded-lg border border-slate-200 bg-white px-2.5 text-sm outline-none focus:border-[#0025cc]" oninput="this.value = String(this.value || '').replace(/\\D/g, '').slice(0, 2); if (Number(this.value) > 12) this.value = '12';">
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                        <input type="checkbox" name="term_is_active" value="1" @checked((int) $term->term_is_active === 1) class="rounded border-slate-300 text-[#0025cc]">
                                        Active
                                    </label>
                                    <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Save</button>
                                </form>
                            </td>
                            <td class="px-5 py-3.5 tabular-nums text-slate-700">{{ $term->term_months }}</td>
                            <td class="px-5 py-3.5">
                                @if((int) $term->term_is_active === 1)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <form method="POST" action="{{ route('purchaser.installment-terms.destroy', $term->term_id) }}" onsubmit="return confirm('Delete or deactivate this term?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-700 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">No terms yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="pur-card overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">File maintenance</p>
            <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Day-count intervals</h2>
            <p class="mt-1 text-sm text-slate-600">
                Used to auto-date installment RFCs from the downpayment date (e.g. every 15 or 30 days).
            </p>
        </div>

        <form method="POST" action="{{ route('purchaser.installment-terms.intervals.store') }}" class="grid gap-3 border-b border-gray-100 px-5 py-4 sm:grid-cols-[1fr_8rem_auto] sm:items-end sm:px-6">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Interval name</label>
                <input type="text" name="interval_name" value="{{ old('interval_name') }}" required maxlength="120" placeholder="e.g. Every 15 days" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-[#0025cc]">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Days</label>
                <input type="number" name="interval_days" value="{{ old('interval_days', 15) }}" min="1" max="365" inputmode="numeric" required class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-[#0025cc]" oninput="this.value = String(this.value || '').replace(/\D/g, '').slice(0, 3); if (Number(this.value) > 365) this.value = '365';">
            </div>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">
                Add interval
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                        <th class="px-5 py-3">Interval</th>
                        <th class="px-5 py-3">Days</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dayIntervals as $interval)
                        <tr>
                            <td class="px-5 py-3.5">
                                <form method="POST" action="{{ route('purchaser.installment-terms.intervals.update', $interval->interval_id) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="interval_name" value="{{ $interval->interval_name }}" required maxlength="120" class="h-9 min-w-[12rem] flex-1 rounded-lg border border-slate-200 bg-white px-2.5 text-sm outline-none focus:border-[#0025cc]">
                                    <input type="number" name="interval_days" value="{{ $interval->interval_days }}" min="1" max="365" inputmode="numeric" required class="h-9 w-20 rounded-lg border border-slate-200 bg-white px-2.5 text-sm outline-none focus:border-[#0025cc]" oninput="this.value = String(this.value || '').replace(/\D/g, '').slice(0, 3); if (Number(this.value) > 365) this.value = '365';">
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                        <input type="checkbox" name="interval_is_active" value="1" @checked((int) $interval->interval_is_active === 1) class="rounded border-slate-300 text-[#0025cc]">
                                        Active
                                    </label>
                                    <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Save</button>
                                </form>
                            </td>
                            <td class="px-5 py-3.5 tabular-nums text-slate-700">{{ $interval->interval_days }}</td>
                            <td class="px-5 py-3.5">
                                @if((int) $interval->interval_is_active === 1)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <form method="POST" action="{{ route('purchaser.installment-terms.intervals.destroy', $interval->interval_id) }}" onsubmit="return confirm('Delete or deactivate this interval?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-700 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">No day-count intervals yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
