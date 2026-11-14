@extends('layouts.purchaser-layout')

@section('page-title', 'Brand Insights')

@section('content')
@php
    $riskTone = [
        'high' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'elevated' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'watch' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $monthLabel = match ((int) $months) {
        6 => 'Last 6 months',
        12 => 'Last 12 months',
        0 => 'All time',
        default => 'Last 24 months',
    };
@endphp

<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="pur-card overflow-hidden">
        <div class="border-b border-gray-100 bg-gradient-to-br from-slate-50 via-white to-amber-50/40 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0 max-w-2xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Predictive procurement</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Brands most reported as defective</h2>
                    <p class="mt-1.5 text-sm leading-6 text-slate-600">
                        Ranked from maintenance defect reports. Use this to spot failure-prone brands before you order again, and flag ones to avoid on future RIS.
                    </p>
                </div>
                <form method="GET" action="{{ route('purchaser.brand-insights.index') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="brandInsightsMonths" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Period</label>
                        <select id="brandInsightsMonths" name="months" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none focus:border-[#0025cc]">
                            <option value="6" @selected($months === 6)>Last 6 months</option>
                            <option value="12" @selected($months === 12)>Last 12 months</option>
                            <option value="24" @selected($months === 24)>Last 24 months</option>
                            <option value="0" @selected($months === 0)>All time</option>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">
                        Apply
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Tracked brands</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-950">{{ number_format($brands->count()) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $monthLabel }}</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Defect report links</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-950">{{ number_format($totalReports) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Across ranked brands</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Flagged to avoid</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ number_format($flagged->count()) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Shown as caution on RIS brands</p>
            </div>
        </div>
    </section>

    @if($highRisk->isNotEmpty())
        <section class="rounded-xl border border-amber-200 bg-amber-50/70 px-5 py-4">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                    <i data-lucide="megaphone" class="h-4 w-4"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-950">Broadcast notice</p>
                    <p class="mt-0.5 text-sm leading-6 text-amber-900/80">
                        Prefer other brands when ordering —
                        {{ $highRisk->take(3)->map(fn ($b) => $b->brand.' ('.$b->report_count.')')->implode(', ') }}
                        @if($highRisk->count() > 3)
                            , and {{ $highRisk->count() - 3 }} more with elevated report volume.
                        @else
                            .
                        @endif
                    </p>
                </div>
            </div>
        </section>
    @endif

    <section class="pur-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold tracking-tight text-gray-950">Brand defect ranking</h2>
                <p class="mt-0.5 text-xs text-gray-500">Highest report counts first · {{ $monthLabel }}</p>
            </div>
            <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'brands']) }}" class="text-xs font-semibold text-[#0025cc] hover:underline">
                Manage brand master
            </a>
        </div>

        @if($brands->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <i data-lucide="shield-check" class="h-5 w-5"></i>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-800">No brand defect pattern yet</p>
                <p class="mt-1 max-w-sm text-xs text-slate-500">When equipment with brand names is reported defective, rankings will appear here.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                            <th class="px-5 py-3">Brand</th>
                            <th class="px-5 py-3">Reports</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Last reported</th>
                            <th class="px-5 py-3">Risk</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($brands as $row)
                            <tr class="{{ $row->avoid_ordering ? 'bg-rose-50/40' : '' }}">
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-900">{{ $row->brand }}</div>
                                    @if(!empty($row->sample_equipment))
                                        <div class="mt-0.5 truncate text-xs text-slate-500" title="{{ implode(', ', $row->sample_equipment) }}">
                                            {{ implode(' · ', $row->sample_equipment) }}
                                        </div>
                                    @endif
                                    @if($row->avoid_ordering)
                                        <span class="mt-1 inline-flex items-center gap-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-rose-700">
                                            Avoid ordering
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 tabular-nums font-semibold text-slate-900">{{ number_format($row->report_count) }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-slate-600">{{ number_format($row->equipment_count) }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $row->last_reported }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $riskTone[$row->risk] ?? $riskTone['watch'] }}">
                                        {{ $row->risk_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if($row->brand_id)
                                        <form method="POST" action="{{ route('purchaser.brand-insights.avoid', $row->brand_id) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="months" value="{{ $months }}">
                                            <input type="hidden" name="avoid" value="{{ $row->avoid_ordering ? 0 : 1 }}">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $row->avoid_ordering ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' }}"
                                            >
                                                <i data-lucide="{{ $row->avoid_ordering ? 'circle-check' : 'ban' }}" class="h-3.5 w-3.5"></i>
                                                {{ $row->avoid_ordering ? 'Clear flag' : 'Flag avoid' }}
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('purchaser.brand-insights.flag') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="brand_name" value="{{ $row->brand }}">
                                            <input type="hidden" name="avoid" value="1">
                                            <input type="hidden" name="months" value="{{ $months }}">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                            >
                                                <i data-lucide="ban" class="h-3.5 w-3.5"></i>
                                                Flag avoid
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
