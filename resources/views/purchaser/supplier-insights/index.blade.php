@extends('layouts.purchaser-layout')

@section('page-title', 'Supplier Insights')

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
        <div class="border-b border-gray-100 bg-gradient-to-br from-slate-50 via-white to-rose-50/40 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0 max-w-2xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Predictive procurement</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Suppliers with the worst delivery records</h2>
                </div>
                <form method="GET" action="{{ route('purchaser.supplier-insights.index') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="supplierInsightsMonths" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Period</label>
                        <select
                            id="supplierInsightsMonths"
                            name="months"
                            onchange="this.form.submit()"
                            class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none focus:border-[#0025cc]"
                        >
                            <option value="6" @selected($months === 6)>Last 6 months</option>
                            <option value="12" @selected($months === 12)>Last 12 months</option>
                            <option value="24" @selected($months === 24)>Last 24 months</option>
                            <option value="0" @selected($months === 0)>All time</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Tracked suppliers</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-950">{{ number_format($suppliers->count()) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $monthLabel }}</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Back-order issues</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-950">{{ number_format($totalIssues) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Missing · damaged · unresolved</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-medium text-slate-500">Flagged caution</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{{ number_format($flagged->count()) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400">Shown as warning on RIS suppliers</p>
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
                        Prefer other suppliers when ordering —
                        {{ $highRisk->take(3)->map(fn ($s) => $s->supplier.' ('.$s->issue_count.')')->implode(', ') }}
                        @if($highRisk->count() > 3)
                            , and {{ $highRisk->count() - 3 }} more with elevated delivery issues.
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
                <h2 class="text-base font-semibold tracking-tight text-gray-950">Supplier bad-record ranking</h2>
                <p class="mt-0.5 text-xs text-gray-500">Most back-order problems first · {{ $monthLabel }}</p>
            </div>
            <a href="{{ route('purchaser.suppliers.index') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">
                Manage suppliers
            </a>
        </div>

        @if($suppliers->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <i data-lucide="shield-check" class="h-5 w-5"></i>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-800">No supplier delivery pattern yet</p>
                <p class="mt-1 max-w-sm text-xs text-slate-500">When receiving reports mark items missing or damaged, supplier rankings will appear here.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                            <th class="px-5 py-3">Supplier</th>
                            <th class="px-5 py-3">Issues</th>
                            <th class="px-5 py-3">Damaged</th>
                            <th class="px-5 py-3">Missing</th>
                            <th class="px-5 py-3">No refund</th>
                            <th class="px-5 py-3">Last issue</th>
                            <th class="px-5 py-3">Risk</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($suppliers as $row)
                            <tr class="{{ $row->avoid_ordering ? 'bg-rose-50/40' : '' }}">
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-900">{{ $row->supplier }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">{{ $row->pattern_summary }}</div>
                                    @if(!empty($row->sample_articles))
                                        <div class="mt-0.5 truncate text-xs text-slate-400" title="{{ implode(', ', $row->sample_articles) }}">
                                            {{ implode(' · ', $row->sample_articles) }}
                                        </div>
                                    @endif
                                    @if($row->avoid_ordering)
                                        <span class="mt-1 inline-flex items-center gap-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-rose-700">
                                            Caution flagged
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 tabular-nums font-semibold text-slate-900">{{ number_format($row->issue_count) }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-slate-600">{{ number_format($row->damaged_count) }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-slate-600">{{ number_format($row->missing_count) }}</td>
                                <td class="px-5 py-3.5 tabular-nums text-slate-600">{{ number_format($row->no_refund_count) }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $row->last_issue }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $riskTone[$row->risk] ?? $riskTone['watch'] }}">
                                        {{ $row->risk_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if($row->supplier_id)
                                        <form method="POST" action="{{ route('purchaser.supplier-insights.caution', $row->supplier_id) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="months" value="{{ $months }}">
                                            <input type="hidden" name="avoid" value="{{ $row->avoid_ordering ? 0 : 1 }}">
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $row->avoid_ordering ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' }}"
                                            >
                                                <i data-lucide="{{ $row->avoid_ordering ? 'circle-check' : 'ban' }}" class="h-3.5 w-3.5"></i>
                                                {{ $row->avoid_ordering ? 'Clear flag' : 'Flag caution' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">No master record</span>
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
