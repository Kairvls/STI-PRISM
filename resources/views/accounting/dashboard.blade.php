@extends('layouts.accounting-layout')

@section('title', 'Accounting Dashboard')

@section('content')
@include('accounting.partials.flash')

@php
    use Illuminate\Support\Str;

    $d = $dashboard;
    $review = $d['review'];
    $funds = $d['funds'];
    $released = $d['releasedThisMonth'];

    $firstName = Str::of(trim((string) ($user->user_full_name ?? '')) ?: 'there')->before(' ');
    $peso = fn ($amount) => '₱'.number_format((float) $amount, 2);
    $plural = fn (int $n, string $word) => $n.' '.($n === 1 ? $word : Str::plural($word));

    $typeLinks = [
        'atp' => route('accounting.atp.index', ['focus' => 'atp-review']),
        'po' => route('accounting.purchase-orders.index', ['status' => 'incoming']),
        'rfc' => route('accounting.rfc.index', ['focus' => 'rfc-review']),
        'funds' => route('accounting.rfc.index', ['focus' => 'funds']),
        'liq' => route('accounting.liq.index', ['focus' => 'liq-review']),
    ];
    $typeShort = ['atp' => 'ATP', 'po' => 'PO', 'rfc' => 'Request Check', 'funds' => 'Release', 'liq' => 'Liquidation'];

    if ($review['count'] > 0 && $funds['count'] > 0) {
        $headline = $plural($review['count'], 'document').' to review and '.$peso($funds['amount']).' ready to release';
    } elseif ($review['count'] > 0) {
        $headline = $plural($review['count'], 'document').' waiting for your review';
    } elseif ($funds['count'] > 0) {
        $headline = $peso($funds['amount']).' approved and ready to release';
    } else {
        $headline = 'You\'re all caught up';
    }

    $releasedNote = match (true) {
        $d['releasedChange'] === null => $plural($released['count'], 'release').' · none last month',
        abs($d['releasedChange']) > 999 => '+'.$peso($released['amount'] - $d['releasedLastMonth']['amount']).' vs last month',
        default => ($d['releasedChange'] >= 0 ? '+' : '−').abs($d['releasedChange']).'% vs last month',
    };

    $overdue = (int) ($deadlines['overdue'] ?? 0);
    $dueToday = (int) ($deadlines['due_today'] ?? 0);
    $dueWeek = (int) ($deadlines['this_week'] ?? 0);

    $received = (float) ($financialSummary['received'] ?? 0);
    $releasedTotal = (float) ($financialSummary['released'] ?? 0);
    $liquidated = (float) ($financialSummary['liquidated'] ?? 0);
    $awaiting = max(0, $received - $releasedTotal);

    $docStatus = [
        ['label' => 'Authority to Purchase', 'review' => $metrics['atp_pending'] ?? 0, 'returned' => $metrics['atp_revision'] ?? 0, 'done' => $metrics['atp_approved'] ?? 0, 'url' => route('accounting.atp.index', ['status' => 'all'])],
        ['label' => 'Purchase Orders', 'review' => $d['poCounts']['pending'], 'returned' => $d['poCounts']['revision'], 'done' => $d['poCounts']['approved'], 'url' => route('accounting.purchase-orders.index', ['status' => 'all'])],
        ['label' => 'Request Checks', 'review' => $metrics['rfc_pending'] ?? 0, 'returned' => $metrics['rfc_revision'] ?? 0, 'done' => $metrics['rfc_approved'] ?? 0, 'url' => route('accounting.rfc.index', ['status' => 'all'])],
        ['label' => 'Liquidations', 'review' => $metrics['liq_pending'] ?? 0, 'returned' => $metrics['liq_revision'] ?? 0, 'done' => $metrics['liq_approved'] ?? 0, 'url' => route('accounting.liq.index', ['status' => 'all'])],
    ];

    $chartMonths = $fundsReleasedChart['months'] ?? [];
@endphp

<div class="acx space-y-6">

    {{-- Header --}}
    <header class="flex flex-col gap-4 pt-1 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs text-slate-500">{{ now()->format('l, F j, Y') }} · Hello, {{ $firstName }}</p>
            <h1 class="acx-h mt-1.5 text-2xl font-semibold tracking-tight sm:text-[28px]">{{ $headline }}</h1>
        </div>
        <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
            @foreach([
                ['Authority to Purchase', route('accounting.atp.index')],
                ['Purchase Orders', route('accounting.purchase-orders.index')],
                ['Request Checks', route('accounting.rfc.index')],
                ['Liquidations', route('accounting.liq.index')],
            ] as [$label, $url])
                <a href="{{ $url }}" class="text-slate-500 underline-offset-4 transition hover:text-[#0025cc] hover:underline">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    {{-- Statement --}}
    @php
        $reviewAlert = $review['slow'] > 0
            ? $review['slow'].' waiting '.\App\Support\AccountingDashboard::SLOW_AFTER_DAYS.'+ days'
            : ($review['urgent'] > 0 ? $review['urgent'].' urgent' : null);
        $fundsFigure = ['label' => 'Ready to release', 'value' => $peso($funds['amount']), 'note' => $funds['count'] > 0 ? $plural($funds['count'], 'approved check').' · oldest '.$plural((int) $funds['oldest_days'], 'day') : 'Nothing to release', 'alert' => null, 'url' => $typeLinks['funds']];
        $reviewFigure = ['label' => 'Waiting for review', 'value' => number_format($review['count']), 'note' => $review['count'] > 0 ? $peso($review['amount']).' in requests' : 'Queue is clear', 'alert' => $reviewAlert, 'url' => '#review-queue'];

        // Lead with money to release; when there is none, lead with the review workload instead.
        $leadFunds = $funds['count'] > 0 || $review['count'] === 0;
    @endphp
    <section class="acx-card grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <div class="flex flex-col justify-between gap-6 p-6 sm:p-8">
            <div>
                @if($leadFunds)
                    <p class="acx-label">Ready to release</p>
                    <p class="mt-3 text-4xl font-semibold tracking-tight tabular-nums sm:text-5xl {{ $funds['count'] > 0 ? 'text-[#0025cc]' : 'text-slate-300' }}">{{ $peso($funds['amount']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $funds['count'] > 0 ? $plural($funds['count'], 'approved check').' · oldest waiting '.$plural((int) $funds['oldest_days'], 'day') : 'Nothing approved is waiting for release.' }}
                    </p>
                @else
                    <p class="acx-label">In your review queue</p>
                    <p class="mt-3 text-4xl font-semibold tracking-tight tabular-nums text-[#0025cc] sm:text-5xl">{{ $peso($review['amount']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $plural($review['count'], 'document') }} to review
                        @if($reviewAlert)<span class="text-amber-600"> · {{ $reviewAlert }}</span>@endif
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($leadFunds)
                    <a href="{{ $typeLinks['funds'] }}" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001ea3]">
                        Release funds <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                    <a href="#review-queue" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Review queue</a>
                @else
                    <a href="#review-queue" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001ea3]">
                        Start reviewing <i data-lucide="arrow-down" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ $typeLinks['funds'] }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Funds to release</a>
                @endif
            </div>
        </div>

        @php
            $figures = [
                $leadFunds ? $reviewFigure : $fundsFigure,
                ['label' => 'Released this month', 'value' => $peso($released['amount']), 'note' => $releasedNote, 'alert' => null, 'url' => route('accounting.rfc.index', ['status' => 'released'])],
                ['label' => 'Cash advances out', 'value' => $peso($d['cashAdvanceAmount']), 'note' => $d['cashAdvanceCount'] > 0 ? $d['cashAdvanceCount'].' not yet liquidated' : 'All liquidated', 'alert' => null, 'url' => '#cash-advances'],
                ['label' => 'Liquidations overdue', 'value' => number_format($overdue), 'note' => $dueToday.' due today · '.$dueWeek.' in 7 days', 'alert' => $overdue > 0 ? 'Past deadline' : null, 'url' => route('accounting.liq.index', ['status' => 'incoming', 'deadline' => 'overdue'])],
            ];
        @endphp
        <div class="grid grid-cols-1 border-t border-slate-100 sm:grid-cols-2 lg:border-l lg:border-t-0">
            @foreach($figures as $i => $fig)
                <a href="{{ $fig['url'] }}"
                   class="group flex flex-col justify-between gap-3 p-6 transition hover:bg-slate-50/70 {{ $i >= 2 ? 'border-t border-slate-100' : '' }} {{ $i === 1 ? 'max-sm:border-t sm:border-l sm:border-slate-100' : '' }} {{ $i === 3 ? 'sm:border-l sm:border-slate-100' : '' }}">
                    <p class="flex items-center justify-between text-xs text-slate-500">
                        {{ $fig['label'] }}
                        <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 text-slate-300 transition group-hover:text-[#0025cc]"></i>
                    </p>
                    <div>
                        <p class="text-2xl font-semibold tracking-tight tabular-nums text-slate-900">{{ $fig['value'] }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ $fig['note'] }}
                            @if($fig['alert'])<span class="text-amber-600"> · {{ $fig['alert'] }}</span>@endif
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Review queue + deadlines --}}
    <section class="grid gap-6 xl:grid-cols-3">
        <div id="review-queue" class="acx-card scroll-mt-24 xl:col-span-2">
            <div class="flex flex-wrap items-end justify-between gap-3 px-6 pt-6">
                <div>
                    <h2 class="acx-h text-base font-semibold">Review queue</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Urgent first, then the ones waiting longest</p>
                </div>
                <span class="text-xs tabular-nums text-slate-400">{{ $d['queueCount'] }} open</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-x-5 border-b border-slate-100 px-6 text-sm">
                @foreach($d['queueByType'] as $type => $group)
                    <a href="{{ $typeLinks[$type] }}"
                       class="-mb-px shrink-0 border-b-2 pb-2.5 transition {{ $group['count'] > 0 ? 'border-[#0025cc] font-medium text-slate-900' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
                        {{ $group['label'] }}
                        <span class="ml-1 tabular-nums {{ $group['count'] > 0 ? 'text-[#0025cc]' : '' }}">{{ $group['count'] }}</span>
                    </a>
                @endforeach
            </div>

            @if($d['queue']->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <th class="px-6 py-3 font-medium">Reference</th>
                            <th class="py-3 pr-4 font-medium max-md:hidden">From</th>
                            <th class="py-3 pr-4 font-medium max-sm:hidden">Waiting</th>
                            <th class="py-3 pr-4 text-right font-medium">Amount</th>
                            <th class="py-3 pr-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 border-t border-slate-100">
                        @foreach($d['queue'] as $item)
                            <tr class="group cursor-pointer transition hover:bg-slate-50/70" onclick="window.location='{{ $item->url }}'">
                                <td class="px-6 py-3.5">
                                    <a href="{{ $item->url }}" class="font-medium text-slate-900 group-hover:text-[#0025cc]">{{ $item->ref }}</a>
                                    <span class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-400">
                                        {{ $typeShort[$item->type] ?? $item->type_label }}
                                        @if($item->urgent)<span class="font-medium text-amber-600">· Urgent</span>@endif
                                    </span>
                                </td>
                                <td class="max-w-[220px] py-3.5 pr-4 max-md:hidden">
                                    <span class="block truncate text-slate-600">{{ $item->who }}</span>
                                    @if($item->related)<span class="block truncate text-xs text-slate-400">{{ $item->related }}</span>@endif
                                </td>
                                <td class="whitespace-nowrap py-3.5 pr-4 text-xs max-sm:hidden {{ $item->slow ? 'font-medium text-amber-600' : 'text-slate-500' }}">{{ $item->waiting ?: '—' }}</td>
                                <td class="whitespace-nowrap py-3.5 pr-4 text-right font-medium tabular-nums text-slate-900">{{ $item->amount !== null ? $peso($item->amount) : '—' }}</td>
                                <td class="whitespace-nowrap py-3.5 pr-6 text-right">
                                    <span class="text-xs font-medium {{ $item->action === 'Release' ? 'text-[#0025cc]' : 'text-slate-500 group-hover:text-slate-900' }}">{{ $item->action }} →</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($d['queueCount'] > $d['queue']->count())
                    <p class="border-t border-slate-100 px-6 py-3 text-xs text-slate-400">Showing {{ $d['queue']->count() }} of {{ $d['queueCount'] }} · open a tab above for the full list</p>
                @endif
            @else
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-medium text-slate-800">Nothing waiting for Accounting</p>
                    <p class="mt-1 text-xs text-slate-500">New ATPs, Purchase Orders, Request Checks and Liquidations will show up here.</p>
                </div>
            @endif
        </div>

        <div class="acx-card flex flex-col">
            <div class="px-6 pt-6">
                <h2 class="acx-h text-base font-semibold">Liquidation deadlines</h2>
                <p class="mt-0.5 text-xs text-slate-500">Reports pending your review</p>
            </div>

            <div class="mt-4 grid grid-cols-3 divide-x divide-slate-100 border-y border-slate-100">
                @foreach([
                    ['Overdue', $overdue, 'overdue', true],
                    ['Due today', $dueToday, 'due_today', true],
                    ['Next 7 days', $dueWeek, 'this_week', false],
                ] as [$label, $count, $key, $warn])
                    <a href="{{ route('accounting.liq.index', ['status' => 'incoming', 'deadline' => $key]) }}" class="px-4 py-4 text-center transition hover:bg-slate-50/70">
                        <span class="block text-2xl font-semibold tabular-nums {{ $count > 0 ? ($warn ? 'text-amber-600' : 'text-slate-900') : 'text-slate-300' }}">{{ $count }}</span>
                        <span class="mt-0.5 block text-[11px] text-slate-500">{{ $label }}</span>
                    </a>
                @endforeach
            </div>

            <div class="flex-1 divide-y divide-slate-100">
                @forelse($d['upcomingDeadlines'] as $due)
                    <a href="{{ $due->url }}" class="group flex items-center gap-4 px-6 py-3 transition hover:bg-slate-50/70">
                        <span class="w-12 shrink-0 text-xs tabular-nums text-slate-400">{{ $due->deadline->format('M j') }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-900 group-hover:text-[#0025cc]">{{ $due->ref }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ $due->who }}</span>
                        </span>
                        <span class="shrink-0 text-xs {{ $due->days <= 1 ? 'font-medium text-amber-600' : 'text-slate-500' }}">{{ $due->label }}</span>
                    </a>
                @empty
                    <div class="flex h-full flex-col items-center justify-center px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-700">No upcoming deadlines</p>
                        <p class="mt-1 text-xs text-slate-500">Pending liquidations with a deadline appear here.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Funds chart + statement --}}
    <section class="grid gap-6 xl:grid-cols-3">
        <div class="acx-card p-6 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="acx-h text-base font-semibold">Funds released</h2>
                    <p class="mt-0.5 text-xs text-slate-500" id="fundsChartSubtitle">Monthly totals for {{ $chartYear }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-lg font-semibold tabular-nums text-slate-900" id="fundsChartTotal">{{ $peso($fundsReleasedChart['total'] ?? 0) }}</p>
                        <p class="text-[11px] text-slate-400" id="fundsChartReleases">{{ $plural((int) ($fundsReleasedChart['releases'] ?? 0), 'release') }}</p>
                    </div>
                    <select id="fundsChartYear" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 outline-none transition focus:border-[#0025cc]" aria-label="Select chart year">
                        @foreach($chartYears as $y)
                            <option value="{{ $y }}" @selected((int) $y === (int) $chartYear)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="relative mt-6 h-[240px]">
                <canvas id="fundsReleasedChart"></canvas>
            </div>
        </div>

        <div class="acx-card flex flex-col p-6">
            <h2 class="acx-h text-base font-semibold">Money flow</h2>
            <p class="mt-0.5 text-xs text-slate-500">Approved Request Checks and Cash Advances</p>

            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex items-baseline gap-2">
                    <dt class="text-slate-600">Approved for payment</dt>
                    <span class="acx-leader"></span>
                    <dd class="tabular-nums text-slate-900">{{ $peso($received) }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-slate-600">Less: released</dt>
                    <span class="acx-leader"></span>
                    <dd class="tabular-nums text-slate-900">({{ $peso($releasedTotal) }})</dd>
                </div>
                <div class="flex items-baseline gap-2 border-t border-slate-900/80 pt-3">
                    <dt class="font-medium text-slate-900">Awaiting release</dt>
                    <span class="acx-leader"></span>
                    <dd class="font-semibold tabular-nums text-[#0025cc]">{{ $peso($awaiting) }}</dd>
                </div>
            </dl>

            <div class="mt-3">
                <div class="flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                    @if($received > 0)
                        <span class="bg-[#0025cc]" style="width: {{ min(100, round(($releasedTotal / $received) * 100)) }}%"></span>
                    @endif
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">{{ $received > 0 ? round(($releasedTotal / $received) * 100).'% of approved funds released' : 'Nothing approved yet' }}</p>
            </div>

            <p class="acx-label mt-6">Cash advances</p>
            <dl class="mt-3 space-y-3 text-sm">
                <div class="flex items-baseline gap-2">
                    <dt class="text-slate-600">Liquidated</dt>
                    <span class="acx-leader"></span>
                    <dd class="tabular-nums text-slate-900">{{ $peso($liquidated) }}</dd>
                </div>
                <div class="flex items-baseline gap-2">
                    <dt class="text-slate-600">Outstanding</dt>
                    <span class="acx-leader"></span>
                    <dd class="tabular-nums {{ $d['cashAdvanceAmount'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $peso($d['cashAdvanceAmount']) }}</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Cash advances · document status · decisions --}}
    <section class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <div id="cash-advances" class="acx-card flex scroll-mt-24 flex-col">
            <div class="flex items-baseline justify-between px-6 pt-6">
                <div>
                    <h2 class="acx-h text-base font-semibold">Cash advances to liquidate</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Released with no approved liquidation</p>
                </div>
                <span class="text-xs tabular-nums text-slate-400">{{ $d['cashAdvanceCount'] }}</span>
            </div>
            <div class="mt-4 flex-1 divide-y divide-slate-100 border-t border-slate-100">
                @forelse($d['cashAdvances'] as $ca)
                    <a href="{{ $ca->url }}" class="group flex items-center gap-3 px-6 py-3 transition hover:bg-slate-50/70">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-900 group-hover:text-[#0025cc]">{{ $ca->ref }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ $ca->payee }}</span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block text-sm tabular-nums text-slate-900">{{ $peso($ca->amount) }}</span>
                            <span class="block text-[11px] {{ $ca->liquidation_status ? 'text-[#0025cc]' : ($ca->days >= 14 ? 'font-medium text-amber-600' : 'text-slate-400') }}">
                                {{ $ca->liquidation_status ? 'Liquidation '.Str::lower($ca->liquidation_status) : ($ca->days === 0 ? 'Released today' : 'Released '.$plural($ca->days, 'day').' ago') }}
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-700">All settled</p>
                        <p class="mt-1 text-xs text-slate-500">Every released cash advance has an approved liquidation.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="acx-card flex flex-col">
            <div class="px-6 pt-6">
                <h2 class="acx-h text-base font-semibold">Document status</h2>
                <p class="mt-0.5 text-xs text-slate-500">Everything Accounting has handled</p>
            </div>
            <table class="mt-4 w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-2.5 text-left font-medium">Document</th>
                        <th class="py-2.5 text-right font-medium">Review</th>
                        <th class="py-2.5 text-right font-medium">Returned</th>
                        <th class="py-2.5 pl-2 pr-6 text-right font-medium">Approved</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($docStatus as $row)
                        <tr class="group cursor-pointer transition hover:bg-slate-50/70" onclick="window.location='{{ $row['url'] }}'">
                            <td class="px-6 py-3.5"><a href="{{ $row['url'] }}" class="text-slate-700 group-hover:text-[#0025cc]">{{ $row['label'] }}</a></td>
                            <td class="py-3.5 text-right tabular-nums {{ $row['review'] > 0 ? 'font-semibold text-[#0025cc]' : 'text-slate-300' }}">{{ $row['review'] }}</td>
                            <td class="py-3.5 text-right tabular-nums {{ $row['returned'] > 0 ? 'text-amber-600' : 'text-slate-300' }}">{{ $row['returned'] }}</td>
                            <td class="py-3.5 pl-2 pr-6 text-right tabular-nums text-slate-600">{{ $row['done'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="acx-card flex flex-col lg:col-span-2 xl:col-span-1">
            <div class="flex items-baseline justify-between px-6 pt-6">
                <div>
                    <h2 class="acx-h text-base font-semibold">Recent decisions</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Latest approvals and releases</p>
                </div>
                <a href="{{ url('/accounting/history') }}" class="text-xs font-medium text-slate-500 hover:text-[#0025cc]">History →</a>
            </div>
            <ol class="mt-4 flex-1 divide-y divide-slate-100 border-t border-slate-100">
                @forelse($d['decisions'] as $decision)
                    @php
                        $dot = match ($decision->tone) {
                            'blue' => 'bg-[#0025cc]',
                            'red', 'amber' => 'bg-amber-500',
                            default => 'bg-slate-300',
                        };
                    @endphp
                    <li>
                        <a href="{{ $decision->url ?? url('/accounting/history') }}" class="group flex items-start gap-3 px-6 py-3 transition hover:bg-slate-50/70">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm text-slate-800">
                                    {{ $decision->action }} <span class="text-slate-400">·</span> <span class="group-hover:text-[#0025cc]">{{ $decision->ref ?: $decision->type_label }}</span>
                                </span>
                                <span class="block truncate text-xs text-slate-400">{{ $decision->by }}</span>
                            </span>
                            <span class="shrink-0 text-[11px] text-slate-400">{{ $decision->at?->diffForHumans(null, true, true) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-700">No decisions yet</p>
                        <p class="mt-1 text-xs text-slate-500">Approvals, returns and releases will be listed here.</p>
                    </li>
                @endforelse
            </ol>
        </div>
    </section>
</div>

<style>
    .acx .acx-card { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 1rem; background: #fff; }
    .acx .acx-h { color: #0f172a; letter-spacing: -0.01em; }
    .acx .acx-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
    .acx .acx-leader { flex: 1 1 auto; min-width: 1rem; border-bottom: 1px dotted #cbd5e1; transform: translateY(-4px); }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('fundsReleasedChart');
        const yearSelect = document.getElementById('fundsChartYear');
        if (!canvas || typeof Chart === 'undefined') return;

        const ACCENT = '#0025cc';
        const MUTED = '#dfe4f5';
        let months = @json($chartMonths);
        let year = Number(@json((int) $chartYear));
        let chart = null;

        function peso(value) {
            return '₱' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function barColors() {
            const now = new Date();
            const current = year === now.getFullYear() ? now.getMonth() : -1;
            return months.map((m, i) => i === current ? ACCENT : MUTED);
        }

        function build() {
            if (chart) chart.destroy();
            chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: months.map(m => m.month_label),
                    datasets: [{
                        label: 'Funds released',
                        data: months.map(m => Number(m.released || 0)),
                        backgroundColor: barColors(),
                        hoverBackgroundColor: ACCENT,
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label(context) {
                                    const m = months[context.dataIndex] || {};
                                    return [
                                        'Released  ' + peso(m.released),
                                        'Releases  ' + (m.count || 0),
                                        'Average   ' + peso(m.average),
                                    ];
                                },
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: '#f1f5f9' },
                            ticks: { color: '#94a3b8', font: { size: 11 }, callback: peso, maxTicksLimit: 5 },
                        },
                    },
                },
            });
        }

        build();

        if (yearSelect) {
            yearSelect.addEventListener('change', async function () {
                const url = new URL(window.location.href);
                url.searchParams.set('year', yearSelect.value);
                url.searchParams.set('partial', 'funds_chart');
                try {
                    const res = await fetch(url.pathname + url.search, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    months = Array.isArray(data.months) ? data.months : [];
                    year = Number(data.year || yearSelect.value);
                    const releases = data.releases || 0;
                    document.getElementById('fundsChartTotal').textContent = peso(data.total);
                    document.getElementById('fundsChartReleases').textContent = releases + (releases === 1 ? ' release' : ' releases');
                    document.getElementById('fundsChartSubtitle').textContent = 'Monthly totals for ' + year;
                    build();
                    url.searchParams.delete('partial');
                    window.history.replaceState({}, '', url.pathname + url.search);
                } catch (err) {
                    console.error(err);
                }
            });
        }
    });
</script>
@endsection
