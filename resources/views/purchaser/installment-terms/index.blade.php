@extends('layouts.purchaser-layout')

@section('page-title', 'Payment intervals')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
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
            <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Payment intervals</h2>
            <p class="mt-1 text-sm text-slate-600">
                Day intervals can be edited here. Monthly, semiannually, quarterly, and annually are presets.
                The installment count (1 Installment, 2 Installment, 5 Installment) is typed on the ATP plan.
            </p>
        </div>

        <form method="POST" action="{{ route('purchaser.installment-terms.intervals.store') }}" class="grid gap-3 border-b border-gray-100 px-5 py-4 sm:grid-cols-[1fr_8rem_auto] sm:items-end sm:px-6">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Day interval name</label>
                <input type="text" name="interval_name" value="{{ old('interval_name') }}" required maxlength="120" placeholder="e.g. 15 days" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-[#0025cc]">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Days</label>
                <input type="number" name="interval_days" value="{{ old('interval_days', 15) }}" min="1" max="365" inputmode="numeric" required class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-[#0025cc]" oninput="this.value = String(this.value || '').replace(/\D/g, '').slice(0, 3); if (Number(this.value) > 365) this.value = '365';">
            </div>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">
                Add day interval
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                        <th class="px-5 py-3">Interval</th>
                        <th class="px-5 py-3">Spacing</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dayIntervals as $interval)
                        @php $isPreset = (int) ($interval->interval_is_preset ?? 0) === 1; @endphp
                        <tr>
                            <td class="px-5 py-3.5">
                                @if($isPreset)
                                    <span class="font-medium text-slate-800">{{ $interval->interval_name }}</span>
                                    <span class="ml-2 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700">Preset</span>
                                @else
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
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-700">
                                @if($isPreset)
                                    {{ $interval->interval_name }}
                                @else
                                    <span class="tabular-nums">{{ $interval->interval_days }} days</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if((int) $interval->interval_is_active === 1)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @unless($isPreset)
                                    <form method="POST" action="{{ route('purchaser.installment-terms.intervals.destroy', $interval->interval_id) }}" onsubmit="return confirm('Delete or deactivate this payment interval?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-700 hover:underline">Delete</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-500">No payment intervals yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
