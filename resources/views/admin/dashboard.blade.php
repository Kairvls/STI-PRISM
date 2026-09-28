@extends('layouts.admin-layout')

@section('title', 'Administrator Dashboard')

@section('content')
@php
    $canPurchaser = (bool) ($overview['can_purchaser'] ?? false);
    $stages = $overview['stage_counts'] ?? [];

    $adminFullName = trim((string) (auth()->user()->user_full_name ?? ''));
    $firstName = $adminFullName !== '' ? strtok($adminFullName, ' ') : '';

    $peso = fn ($v, int $decimals = 0) => '₱'.number_format((float) $v, $decimals);
    $plural = fn (int $n, string $word, ?string $many = null) => $n.' '.($n === 1 ? $word : ($many ?? $word.'s'));
    $tagAlert = 'bg-amber-50 text-amber-700';
    $tagNeutral = 'bg-slate-100 text-slate-600';

    $urgentReports = (int) ($overview['urgent_reports'] ?? 0);
    $overdueSchedules = (int) ($overview['overdue_schedules'] ?? 0);
    $overdueBorrows = (int) ($overview['overdue_borrows'] ?? 0);

    if ($pendingRis > 0) {
        $headline = $plural((int) $pendingRis, 'RIS', 'RIS').' waiting for your acceptance';
    } elseif ($forCosigningCount > 0) {
        $headline = $plural((int) $forCosigningCount, 'RIS', 'RIS').' waiting for your signature';
    } elseif ($amendRis > 0) {
        $headline = $plural((int) $amendRis, 'RIS', 'RIS').' sent back for amendment';
    } else {
        $headline = 'Nothing is waiting on you';
    }

    $campusParts = array_filter([
        $urgentReports > 0 ? $plural($urgentReports, 'urgent report') : null,
        $overdueSchedules > 0 ? $plural($overdueSchedules, 'overdue schedule') : null,
        $overdueBorrows > 0 ? $plural($overdueBorrows, 'overdue borrow') : null,
    ]);
    $campusText = $campusParts
        ? (count($campusParts) > 1 ? implode(', ', array_slice($campusParts, 0, -1)).' and '.end($campusParts) : reset($campusParts)).' need follow-up on campus.'
        : 'Campus operations are clear.';
    $subline = ($pendingRis > 0 && $forCosigningCount > 0 ? $plural((int) $forCosigningCount, 'RIS', 'RIS').' also waiting for your signature. ' : '').$campusText;

    $pipeline = [
        'ris' => 'RIS',
        'atp' => 'ATP',
        'rfc' => 'RFC / CA',
        'receiving' => 'RR',
        'liquidation' => 'Liquidation',
    ];
    $pipelineMax = max(1, collect($pipeline)->keys()->map(fn ($key) => (int) ($stages[$key] ?? 0))->max());

    $stats = [
        ['label' => 'To accept', 'value' => (int) $pendingRis, 'note' => $peso($pendingRisAmount).' waiting', 'href' => route('admin.procurement-review.ris', ['filter' => 'pending'])],
        ['label' => 'To sign', 'value' => (int) $forCosigningCount, 'note' => 'Issued-by signature', 'href' => route('admin.digital-signatures.sign-ris', ['filter' => 'pending'])],
        ['label' => 'Urgent reports', 'value' => $urgentReports, 'note' => ($overview['open_reports'] ?? 0).' open in total', 'href' => route('admin.operations.reports', ['filter' => 'urgent'])],
        ['label' => 'Overdue schedules', 'value' => $overdueSchedules, 'note' => 'Maintenance', 'href' => route('admin.operations.schedules', ['filter' => 'overdue'])],
    ];

    $risDate = function ($ris) {
        $raw = data_get($ris, 'ris_submitted_at') ?: data_get($ris, 'ris_requested_by_date') ?: data_get($ris, 'ris_created_at');

        return $raw ? \Carbon\Carbon::parse($raw) : null;
    };
    $daysTag = function (?\Carbon\Carbon $date) {
        if (! $date) {
            return null;
        }
        $days = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

        return $days < 0 ? [abs($days).'d overdue', true] : ($days === 0 ? ['Due today', true] : ['In '.$days.'d', false]);
    };
@endphp

<span class="admin-keep-colors hidden" aria-hidden="true"></span>
<div class="ad-dash mx-auto max-w-[1400px] space-y-6">

    {{-- Overview --}}
    <section class="ad-panel overflow-hidden">
        <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                    <i data-lucide="shield-check" class="h-3.5 w-3.5 text-slate-400"></i>
                    <span class="font-medium text-slate-700">Administrator</span>
                    <span class="text-slate-300">/</span>
                    <span>{{ now()->format('l, F j, Y') }}</span>
                    @if($firstName !== '')
                        <span class="text-slate-300">/</span>
                        <span>{{ $firstName }}</span>
                    @endif
                </p>
                <h1 class="mt-4 max-w-2xl text-2xl font-semibold tracking-tight text-slate-900 sm:text-[28px] sm:leading-tight">{{ $headline }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ $subline }}</p>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.procurement-review.ris', ['filter' => 'pending']) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001ea3]">
                        <i data-lucide="inbox" class="h-4 w-4"></i> Review RIS
                    </a>
                    <a href="{{ route('admin.digital-signatures.sign-ris', ['filter' => 'pending']) }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="pen-tool" class="h-4 w-4 text-slate-400"></i> Sign RIS
                    </a>
                    <a href="{{ route('admin.operations.overview') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="layout-grid" class="h-4 w-4 text-slate-400"></i> Command Center
                    </a>
                    @if($canPurchaser)
                        <a href="{{ url('/purchaser/ris') }}" class="ml-1 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i> New RIS
                        </a>
                    @endif
                </div>
            </div>

            {{-- Procurement pipeline --}}
            <div class="lg:border-l lg:border-slate-100 lg:pl-8">
                <div class="flex items-center justify-between">
                    <p class="ad-label">Procurement pipeline</p>
                    <a href="{{ route('admin.operations.procurement') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Monitor →</a>
                </div>
                <ul class="mt-4 space-y-3">
                    @foreach($pipeline as $key => $label)
                        @php $count = (int) ($stages[$key] ?? 0); @endphp
                        <li class="grid grid-cols-[76px_minmax(0,1fr)_32px] items-center gap-3 text-xs">
                            <span class="text-slate-500">{{ $label }}</span>
                            <span class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <span class="block h-full rounded-full bg-[#0025cc]" style="width: {{ $count > 0 ? max(4, round($count / $pipelineMax * 100)) : 0 }}%"></span>
                            </span>
                            <span class="text-right font-semibold tabular-nums {{ $count ? 'text-slate-900' : 'text-slate-300' }}">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 text-[11px] text-slate-400">{{ $plural((int) ($overview['open_ris'] ?? 0), 'RIS', 'RIS') }} still open</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px border-t border-slate-100 bg-slate-100 lg:grid-cols-4">
            @foreach($stats as $stat)
                <a href="{{ $stat['href'] }}" class="group bg-white px-6 py-5 transition hover:bg-slate-50">
                    <span class="flex items-center gap-1.5 text-xs text-slate-500">
                        {{ $stat['label'] }}
                        @if($stat['value'] > 0)<span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>@endif
                    </span>
                    <span class="mt-1 block text-3xl font-semibold tabular-nums tracking-tight {{ $stat['value'] > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ $stat['value'] }}</span>
                    <span class="mt-0.5 block truncate text-xs text-slate-400 group-hover:text-slate-500">{{ $stat['note'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Queue + budget --}}
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="ad-panel flex flex-col lg:col-span-3">
            <div class="flex items-center justify-between gap-3 px-6 pb-4 pt-6 sm:px-8">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Your queue</h2>
                    <p class="text-xs text-slate-500">RIS waiting on the Administrator</p>
                </div>
                @if($canPurchaser)
                    <a href="{{ url('/purchaser/dashboard') }}" class="shrink-0 text-xs font-medium text-slate-500 hover:text-slate-900">Purchaser portal →</a>
                @endif
            </div>

            @foreach([
                ['label' => 'To accept', 'rows' => $actionPendingRis, 'href' => route('admin.procurement-review.ris', ['filter' => 'pending']), 'empty' => 'Nothing waiting for acceptance.', 'meta' => fn ($ris) => \Illuminate\Support\Str::limit($ris->ris_purpose_description, 60) ?: $ris->ris_status],
                ['label' => 'To sign', 'rows' => $actionSignRis, 'href' => route('admin.digital-signatures.sign-ris', ['filter' => 'pending']), 'empty' => 'Nothing waiting for your signature.', 'meta' => fn ($ris) => $ris->ris_status],
            ] as $block)
                <div class="border-t border-slate-100">
                    <div class="flex items-center justify-between px-6 pt-4 sm:px-8">
                        <p class="ad-label">{{ $block['label'] }} · {{ count($block['rows']) }}</p>
                        <a href="{{ $block['href'] }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">View all →</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($block['rows'] as $ris)
                            @php $date = $risDate($ris); @endphp
                            <div class="flex items-center gap-4 px-6 py-4 sm:px-8">
                                <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg border border-slate-200">
                                    <span class="text-[10px] uppercase tracking-wide text-slate-400">{{ $date?->format('M') ?? '—' }}</span>
                                    <span class="text-lg font-semibold leading-none tabular-nums text-slate-900">{{ $date?->format('d') ?? '--' }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-900">{{ \App\Support\RisWorkflow::formNumber($ris) }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $block['meta']($ris) }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-medium tabular-nums text-slate-900">{{ $peso($ris->ris_calculated_total ?? 0) }}</p>
                                    <button type="button" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')"
                                            class="mt-0.5 inline-flex items-center gap-0.5 text-xs font-medium text-slate-400 hover:text-slate-900">
                                        View <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-6 text-center text-xs text-slate-400 sm:px-8">{{ $block['empty'] }}</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </section>

        {{-- Budget --}}
        @php
            $selectedYear = (int) ($budgetProposalYear ?? now()->year);
            $usage = ($budgetUsage ?? [])[$selectedYear] ?? null;
            $otherUsage = collect($budgetUsage ?? [])->except($selectedYear)->values();
        @endphp
        <section class="ad-panel p-6 sm:p-8 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-slate-900">Budget</h2>
                <form method="GET" action="{{ route('admin.dashboard') }}">
                    <label for="budget_year" class="sr-only">Budget year</label>
                    <select id="budget_year" name="budget_year" onchange="this.form.submit()"
                            class="rounded-md border border-slate-200 bg-white py-1 pl-2.5 pr-7 text-xs font-medium text-slate-700 focus:border-slate-300 focus:outline-none focus:ring-0">
                        @foreach(($budgetProposalYears ?? collect([(int) now()->year])) as $yearOption)
                            <option value="{{ $yearOption }}" @selected($selectedYear === (int) $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <p class="ad-label mt-6">Proposed {{ $selectedYear }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $peso($budgetProposalTotal ?? 0, 2) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ $plural((int) ($budgetProposalRisCount ?? 0), 'RIS record') }}</p>

            @if($usage)
                @php
                    $scale = max($usage['proposed'], $usage['approved'], $usage['released'], 1);
                    $paidWidth = min(100, round($usage['released'] / $scale * 100, 1));
                    $approvedWidth = max(0, min(100, round($usage['approved'] / $scale * 100, 1)) - $paidWidth);
                    $paidOfApproved = $usage['approved'] > 0 ? (int) round($usage['released'] / $usage['approved'] * 100) : 0;
                @endphp
                <div class="mt-6">
                    <div class="flex items-baseline justify-between text-xs text-slate-500">
                        <span>Budget used</span>
                        <span><span class="text-base font-semibold tabular-nums text-[#0025cc]">{{ $paidOfApproved }}%</span> of approved paid out</span>
                    </div>
                    <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-slate-100"
                         title="Paid out {{ $peso($usage['released']) }} · Approved {{ $peso($usage['approved']) }} · Proposed {{ $peso($usage['proposed']) }}">
                        <span class="bg-[#0025cc]" style="width: {{ $paidWidth }}%"></span>
                        <span class="bg-[#b9c4f6]" style="width: {{ $approvedWidth }}%"></span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-500">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[#0025cc]"></span>Paid {{ $peso($usage['released']) }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[#b9c4f6]"></span>Approved {{ $peso($usage['approved']) }}</span>
                    </div>
                </div>
            @endif

            <dl class="mt-6 grid grid-cols-3 divide-x divide-slate-100 border-y border-slate-100 py-4 text-center">
                <div>
                    <dt class="text-[11px] text-slate-400">Pending</dt>
                    <dd class="mt-0.5 truncate text-base font-semibold tabular-nums text-slate-900">{{ $peso($budgetPendingAmount ?? 0) }}</dd>
                </div>
                <div class="px-1">
                    <dt class="text-[11px] text-slate-400">Administrator OK</dt>
                    <dd class="mt-0.5 truncate text-base font-semibold tabular-nums text-slate-900">{{ $peso($budgetAdminApprovedAmount ?? 0) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400">President</dt>
                    <dd class="mt-0.5 truncate text-base font-semibold tabular-nums text-slate-900">{{ $peso($budgetPresidentApprovedAmount ?? 0) }}</dd>
                </div>
            </dl>

            @if((float) ($budgetPresidentRejectedAmount ?? 0) > 0)
                <p class="mt-3 text-xs text-slate-500">Rejected this year: <span class="font-medium text-slate-700">{{ $peso($budgetPresidentRejectedAmount, 2) }}</span></p>
            @endif

            @if($otherUsage->isNotEmpty())
                <ul class="mt-5 space-y-2">
                    @foreach($otherUsage as $row)
                        @php $rowPaid = $row['approved'] > 0 ? (int) round($row['released'] / $row['approved'] * 100) : 0; @endphp
                        <li class="grid grid-cols-[40px_minmax(0,1fr)_auto] items-center gap-3 text-[11px] text-slate-500">
                            <span class="font-medium text-slate-700">{{ $row['year'] }}</span>
                            <span class="h-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full bg-[#0025cc]" style="width: {{ min(100, $rowPaid) }}%"></span></span>
                            <span class="tabular-nums">{{ $peso($row['released']) }} of {{ $peso($row['approved']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-5 text-[11px] text-slate-400">Paid counts Request for Check and Cash Advance funds released that year.</p>
        </section>
    </div>

    {{-- RIS trend + RR with back orders --}}
    @php
        $trendSeries = [
            ['label' => 'Admin approved', 'data' => array_map('intval', $risTrendApproved ?? []), 'color' => '#0025cc'],
            ['label' => 'President approved', 'data' => array_map('intval', $risTrendForwarded ?? []), 'color' => '#8a9bf0'],
            ['label' => 'Amend', 'data' => array_map('intval', $risTrendAmend ?? []), 'color' => '#f59e0b'],
            ['label' => 'Rejected', 'data' => array_map('intval', $risTrendRejected ?? []), 'color' => '#cbd5e1'],
        ];
        $trendLabels = collect($risTrendLabels ?? [])->map(fn ($label) => mb_substr((string) $label, 0, 3))->values()->all();
        $trendTotal = collect($trendSeries)->sum(fn ($series) => array_sum($series['data']));
    @endphp
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="ad-panel p-6 sm:p-8 lg:col-span-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">RIS trend</h2>
                    <p class="text-xs text-slate-500">Outcomes of RIS created in the last 6 months</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                    @foreach($trendSeries as $series)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-sm" style="background: {{ $series['color'] }}"></span>
                            {{ $series['label'] }} <span class="font-semibold tabular-nums text-slate-900">{{ array_sum($series['data']) }}</span>
                        </span>
                    @endforeach
                </div>
            </div>

            @if($trendTotal > 0)
                <div class="relative mt-6 h-60">
                    <canvas id="adminRisTrendChart"
                            aria-label="RIS outcomes per month"
                            data-labels='@json($trendLabels)'
                            data-series='@json($trendSeries)'></canvas>
                </div>
            @else
                <div class="mt-6 rounded-lg border border-dashed border-slate-200 px-4 py-12 text-center">
                    <p class="text-xs text-slate-400">No RIS decisions in the last 6 months.</p>
                </div>
            @endif
        </section>

        <section class="ad-panel p-6 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">RR with back orders</h2>
                <a href="{{ route('admin.back-orders.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">All {{ $receivingSummary['total'] ?? 0 }} →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">
                Latest receiving reports with missing or damaged items
                @if(($receivingSummary['open'] ?? 0) > 0)
                    · <span class="font-medium text-amber-600">{{ $receivingSummary['open'] }} still open</span>
                @endif
            </p>

            <div class="mt-4 space-y-2">
                @forelse(($receivingSummary['rows'] ?? collect()) as $rr)
                    <div class="rounded-lg border p-3 {{ $rr->open > 0 ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('admin.operations.document', ['type' => 'rr', 'id' => $rr->id]) }}" class="text-sm font-medium text-slate-900 hover:text-[#0025cc]">{{ $rr->number }}</a>
                                <p class="truncate text-xs text-slate-500" title="{{ $rr->supplier }}">{{ $rr->supplier ?: 'Supplier not set' }}</p>
                            </div>
                            <a href="{{ route('admin.back-orders.index', ['rr' => $rr->id, 'status' => $rr->open > 0 ? 'unresolved' : 'resolved']) }}"
                               class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $rr->open > 0 ? $tagAlert : $tagNeutral }}">
                                {{ $rr->open > 0 ? $rr->open.' of '.$rr->total.' open' : 'All delivered' }}
                            </a>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            {{ $rr->purchaser ?: 'Purchaser' }}@if($rr->status) · {{ $rr->status }}@endif @if($rr->latestAt) · {{ $rr->latestAt->format('M j') }}@endif
                            @if($rr->open > 0)
                                · <span class="text-amber-700">{{ $plural($rr->openQty, 'item') }} ({{ $peso($rr->openValue, 2) }}) not delivered</span>
                            @endif
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($rr->backOrders->take(3) as $bo)
                                <span class="inline-flex max-w-full items-center gap-1.5 truncate rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[11px] text-slate-500" title="{{ $bo->status }}">
                                    <span class="font-mono text-[10px] font-medium {{ $bo->open ? 'text-amber-700' : 'text-[#0025cc]' }}">{{ $bo->number }}</span>
                                    {{ $bo->qty }} {{ $bo->article }} · {{ strtolower($bo->type) }}
                                </span>
                            @endforeach
                            @if($rr->backOrders->count() > 3)
                                <span class="rounded border border-slate-200 px-1.5 py-0.5 text-[11px] text-slate-500">+{{ $rr->backOrders->count() - 3 }} more</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center">
                        <p class="text-xs text-slate-400">No receiving reports with back orders yet.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Suppliers + approvals --}}
    @php
        $supplierComparison = $supplierComparison ?? collect();
        $supplierComparisonMax = (float) ($supplierComparisonMax ?? 0);
        $typeCompare = $supplierTypeComparison ?? ['physical_count' => 0, 'online_count' => 0, 'physical_amount' => 0, 'online_amount' => 0];
        $typeTotalAmount = (float) $typeCompare['physical_amount'] + (float) $typeCompare['online_amount'];
        $physicalShare = $typeTotalAmount > 0 ? round((float) $typeCompare['physical_amount'] / $typeTotalAmount * 100) : 0;
    @endphp
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="ad-panel p-6">
            <h2 class="text-base font-semibold text-slate-900">Supplier spend</h2>
            <p class="mt-0.5 text-xs text-slate-500">ATP amounts by store type and supplier</p>

            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[#0025cc]"></span>Physical · {{ (int) $typeCompare['physical_count'] }} ATP · {{ $peso($typeCompare['physical_amount']) }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-slate-300"></span>Online · {{ (int) $typeCompare['online_count'] }}</span>
            </div>
            <div class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                @if($typeTotalAmount > 0)
                    <span class="bg-[#0025cc]" style="width: {{ $physicalShare }}%"></span>
                    <span class="bg-slate-300" style="width: {{ 100 - $physicalShare }}%"></span>
                @endif
            </div>
            <p class="mt-1 text-right text-[11px] text-slate-400">Online {{ $peso($typeCompare['online_amount']) }}</p>

            <div class="mt-4 space-y-4">
                @forelse($supplierComparison as $supplier)
                    @php $barPct = $supplierComparisonMax > 0 ? max(4, round((float) $supplier->total_amount / $supplierComparisonMax * 100)) : 4; @endphp
                    <div>
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="truncate text-slate-700" title="{{ $supplier->supplier_name }}">{{ $supplier->supplier_name }}</span>
                            <span class="shrink-0 text-xs tabular-nums text-slate-900">{{ $peso($supplier->total_amount) }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <span class="block h-full rounded-full bg-[#0025cc]" style="width: {{ $barPct }}%"></span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">{{ $plural((int) $supplier->atp_count, 'ATP') }}@if(!empty($supplier->supplier_type)) · {{ $supplier->supplier_type }}@endif</p>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center">
                        <p class="text-xs text-slate-400">No supplier ATP records yet.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="ad-panel p-6">
            @php
                $reliableDeliveries = (int) ($supplierReliability['deliveries'] ?? 0);
                $incompleteDeliveries = (int) ($supplierReliability['incomplete'] ?? 0);
                $completeRate = $reliableDeliveries > 0 ? (int) round(($reliableDeliveries - $incompleteDeliveries) / $reliableDeliveries * 100) : null;
            @endphp
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Supplier reliability</h2>
                <a href="{{ route('admin.back-orders.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Back orders →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Deliveries that arrived with missing or damaged items</p>

            <div class="mt-4 flex items-end gap-3">
                <span class="text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $completeRate !== null ? $completeRate.'%' : '—' }}</span>
                <span class="pb-1 text-xs text-slate-500">complete · {{ $incompleteDeliveries }} of {{ $plural($reliableDeliveries, 'delivery', 'deliveries') }} short</span>
            </div>

            <div class="mt-5 space-y-4">
                @forelse(($supplierReliability['rows'] ?? collect()) as $row)
                    <div>
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="truncate text-slate-700" title="{{ $row->supplier }}">{{ $row->supplier }}</span>
                            <span class="shrink-0 text-xs font-medium tabular-nums {{ $row->rate > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $row->rate }}%</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <span class="block h-full rounded-full bg-amber-500" style="width: {{ $row->rate > 0 ? max(4, $row->rate) : 0 }}%"></span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            {{ $row->incomplete }} of {{ $plural($row->deliveries, 'delivery', 'deliveries') }} incomplete
                            @if($row->missingQty > 0) · {{ $row->missingQty }} missing @endif
                            @if($row->damagedQty > 0) · {{ $row->damagedQty }} damaged @endif
                        </p>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center">
                        <p class="text-xs text-slate-400">No submitted receiving reports yet.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="ad-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Approvals</h2>
                <a href="{{ route('admin.reports.approval-logs') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Logs →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Latest decisions across documents</p>

            <ol class="relative mt-4 space-y-4 border-l border-slate-200 pl-5">
                @forelse($recentApprovals as $log)
                    @php
                        $status = (string) $log->approval_log_approval_status;
                        $dot = str_contains(strtolower($status), 'reject') ? 'bg-amber-500' : (in_array($status, ['Approved', 'Admin Approved', 'Directly Approved', 'Co-signed', 'Accepted'], true) ? 'bg-[#0025cc]' : 'bg-slate-300');
                        $at = !empty($log->approval_log_approved_at) ? \Carbon\Carbon::parse($log->approval_log_approved_at) : null;
                    @endphp
                    <li class="relative">
                        <span class="absolute -left-[24.5px] top-1.5 h-2 w-2 rounded-full ring-4 ring-white {{ $dot }}"></span>
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm font-medium text-slate-800">{{ $log->approval_log_reference_type }} #{{ $log->approval_log_reference_id }}</p>
                            <span class="shrink-0 text-[11px] text-slate-400" title="{{ $at?->format('M j, Y g:i A') }}">{{ $at?->diffForHumans(null, true, true) }}</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ $status }} · {{ $log->actor_name ?: 'System' }}</p>
                    </li>
                @empty
                    <li class="text-xs text-slate-400">No approval activity.</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- Recent RIS + calendar --}}
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="ad-panel flex flex-col lg:col-span-3">
            <div class="flex items-center justify-between gap-3 px-6 pb-4 pt-6 sm:px-8">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Recent RIS</h2>
                    <p class="text-xs text-slate-500">Latest requisition activity</p>
                </div>
                <a href="{{ route('admin.operations.procurement') }}" class="shrink-0 text-xs font-medium text-slate-500 hover:text-slate-900">Pipeline →</a>
            </div>
            <div class="border-t border-slate-100">
                <table class="w-full table-fixed text-[13px]">
                    <thead>
                        <tr class="text-left text-[11px] text-slate-400">
                            <th class="w-[36%] py-3 pl-6 pr-2 font-medium sm:pl-8 2xl:w-[29%]">RIS number</th>
                            <th class="hidden px-2 py-3 font-medium 2xl:table-cell">Items</th>
                            <th class="px-2 py-3 font-medium">Status</th>
                            <th class="w-[24%] px-2 py-3 text-right font-medium 2xl:w-[17%]">Amount</th>
                            <th class="w-[64px] py-3 pl-2 pr-6 sm:pr-8"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 border-t border-slate-100">
                        @forelse($recentRisRecords as $ris)
                            @php $risNumber = \App\Support\RisWorkflow::formNumber($ris) ?: '—'; $risItems = \App\Support\RisWorkflow::sourceLabel($ris); @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="truncate py-2.5 pl-6 pr-2 font-medium text-slate-900 sm:pl-8" title="{{ $risNumber }}">{{ $risNumber }}</td>
                                <td class="hidden truncate px-2 py-2.5 text-slate-500 2xl:table-cell" title="{{ $risItems }}">{{ $risItems }}</td>
                                <td class="ad-status-cell px-2 py-2.5">@include('admin.partials.ris-status-badge', ['ris' => $ris])</td>
                                <td class="truncate px-2 py-2.5 text-right tabular-nums text-slate-900">{{ $peso($ris->ris_calculated_total ?? 0, 2) }}</td>
                                <td class="py-2.5 pl-2 pr-6 text-right sm:pr-8">
                                    <button type="button" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')" class="text-slate-400 hover:text-slate-900" title="View RIS" aria-label="View RIS">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-xs text-slate-400">No RIS records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="ad-panel p-6 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Calendar</h2>
                <a href="{{ url('/admin/procurement-review') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Review →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">RIS submitted, forwarded, approved and issued</p>

            <div class="mt-5 flex items-center justify-between">
                <button type="button" id="calPrevBtn" class="admin-dash-cal-nav" title="Previous month">
                    <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                </button>
                <span id="calMonthLabel" class="text-sm font-medium text-slate-900">{{ now()->format('F Y') }}</span>
                <button type="button" id="calNextBtn" class="admin-dash-cal-nav" title="Next month">
                    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                </button>
            </div>
            <div id="adminCalendarGrid" class="admin-dash-cal-grid mt-3"></div>
            <div id="adminCalendarUpcoming" class="admin-dash-cal-upcoming"></div>
        </section>
    </div>

    {{-- Campus follow-up --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="ad-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Urgent reports</h2>
                <a href="{{ route('admin.operations.reports', ['filter' => 'urgent']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">All →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Open high-priority tickets</p>

            <ul class="mt-3 divide-y divide-slate-100">
                @forelse($urgentReportsList as $report)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $report->equipment_name ?: 'Unlisted equipment' }}</p>
                            <p class="truncate text-xs text-slate-500">#{{ $report->report_id }} · {{ $report->room_name ?: 'No room' }}</p>
                        </div>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $tagNeutral }}">{{ $report->report_current_status }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-xs text-slate-400">No urgent reports.</li>
                @endforelse
            </ul>
        </section>

        <section class="ad-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Maintenance</h2>
                <a href="{{ route('admin.operations.schedules') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Schedules →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Schedules due in 14 days · semester inspections due in 7</p>

            <p class="ad-label mt-5">Schedules</p>
            <ul class="mt-1 divide-y divide-slate-100">
                @forelse(($upcomingMaintenanceSchedules ?? collect()) as $schedule)
                    @php
                        $nextDate = !empty($schedule->maintenance_schedule_next_date) ? \Carbon\Carbon::parse($schedule->maintenance_schedule_next_date) : null;
                        $tag = $daysTag($nextDate);
                        if (strcasecmp((string) ($schedule->maintenance_schedule_status ?? ''), 'Overdue') === 0 && (! $tag || ! $tag[1])) {
                            $tag = ['Overdue', true];
                        }
                        $tag = $tag ?: [$schedule->maintenance_schedule_status ?: 'Scheduled', false];
                    @endphp
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $schedule->equipment_name ?: ($schedule->maintenance_schedule_title ?: 'Equipment') }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ $schedule->room_name ?: 'No room' }}
                                @if(!empty($schedule->maintenance_schedule_title) && $schedule->equipment_name) · {{ $schedule->maintenance_schedule_title }}@endif
                                @if(!empty($schedule->maintenance_schedule_frequency)) · {{ $schedule->maintenance_schedule_frequency }}@endif
                            </p>
                        </div>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $tag[1] ? $tagAlert : $tagNeutral }}">{{ $tag[0] }}</span>
                    </li>
                @empty
                    <li class="py-4 text-xs text-slate-400">No schedules due soon.</li>
                @endforelse
            </ul>

            <p class="ad-label mt-5">Semester inspections</p>
            <ul class="mt-1 divide-y divide-slate-100">
                @forelse(($semesterInspectionDue ?? collect()) as $campaign)
                    @php $tag = $daysTag(\Carbon\Carbon::parse($campaign->campaign_due_date)); @endphp
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $campaign->campaign_title }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ $campaign->campaign_semester }}@if(!empty($campaign->campaign_academic_year)) · {{ $campaign->campaign_academic_year }}@endif · {{ $campaign->campaign_status }}
                            </p>
                        </div>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $tag[1] ? $tagAlert : $tagNeutral }}">{{ $tag[0] }}</span>
                    </li>
                @empty
                    <li class="py-4 text-xs text-slate-400">No semester inspections due soon.</li>
                @endforelse
            </ul>
        </section>

        <section class="ad-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Equipment watch</h2>
                <a href="{{ route('admin.operations.equipment') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Equipment →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Warranties, disposal and aging assets</p>

            <p class="ad-label mt-5">Warranty ending · {{ (int) ($warrantyWatch['total'] ?? 0) }}</p>
            <ul class="mt-1 divide-y divide-slate-100">
                @forelse(($warrantyWatch['rows'] ?? collect()) as $item)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $item->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $item->meta ?: 'No details' }} · Ends {{ $item->endsAt->format('M j') }}</p>
                        </div>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $item->daysLeft <= 7 ? $tagAlert : $tagNeutral }}">{{ $item->daysLeft === 0 ? 'Today' : $item->daysLeft.'d left' }}</span>
                    </li>
                @empty
                    <li class="py-4 text-xs text-slate-400">No warranties ending in the next 30 days.</li>
                @endforelse
            </ul>

            <div class="mt-5 flex items-center justify-between">
                <p class="ad-label">Awaiting disposal · {{ (int) ($disposalWatch['total'] ?? 0) }}</p>
                <a href="{{ route('admin.operations.equipment', ['filter' => 'replacement']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">View all →</a>
            </div>
            <ul class="mt-1 divide-y divide-slate-100">
                @forelse(($disposalWatch['rows'] ?? collect()) as $item)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $item->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $item->meta }}</p>
                        </div>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $tagAlert }}">{{ $item->flaggedAt ? $item->flaggedAt->diffForHumans(null, true, true) : 'For replacement' }}</span>
                    </li>
                @empty
                    <li class="py-4 text-xs text-slate-400">No equipment waiting for disposal.</li>
                @endforelse
            </ul>

            <div class="mt-5 flex items-center justify-between">
                <p class="ad-label">Aging assets</p>
                <a href="{{ route('admin.operations.equipment', ['filter' => 'lifecycle']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Lifecycle →</a>
            </div>
            <ul class="mt-1 divide-y divide-slate-100">
                @forelse(($lifecycleAlerts ?? collect()) as $alert)
                    @php
                        $yearsLeft = (int) ($alert->years_remaining ?? 0);
                        $lifeYears = (int) ($alert->useful_life_years ?? ($usefulLifeYears ?? 5));
                        $alreadyMarked = strcasecmp((string) ($alert->equipment_inventory_status ?? ''), 'For Replacement') === 0;
                        if ($alreadyMarked) {
                            $hint = 'Marked';
                        } elseif ($yearsLeft < 0) {
                            $hint = abs($yearsLeft).'y past life';
                        } elseif ($yearsLeft === 0) {
                            $hint = 'Replace this year';
                        } else {
                            $hint = '~'.$yearsLeft.'y left';
                        }
                        $isAlert = $alreadyMarked || $yearsLeft <= 0;
                    @endphp
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $alert->equipment_name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $alert->room_name ?: 'No room' }} · Age {{ (int) ($alert->age_years ?? 0) }}y of {{ $lifeYears }}y</p>
                        </div>
                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $isAlert ? $tagAlert : $tagNeutral }}">{{ $hint }}</span>
                    </li>
                @empty
                    <li class="py-4 text-xs text-slate-400">No aging equipment needing attention.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- Movements --}}
    <section class="ad-panel p-6 sm:p-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="ad-label">Movements</p>
                <h2 class="mt-1.5 text-lg font-semibold text-slate-900">Where equipment is moving</h2>
                <p class="mt-0.5 text-sm text-slate-500">Transfers, borrowing and disposal across campus.</p>
            </div>
            <div class="flex flex-wrap items-center gap-1 text-sm">
                @foreach([
                    ['Equipment', $overview['equipment_total'] ?? 0, route('admin.operations.equipment')],
                    ['Under maint.', $overview['needs_maintenance'] ?? 0, route('admin.operations.equipment', ['filter' => 'maintenance'])],
                    ['Replace', $overview['for_replacement'] ?? 0, route('admin.operations.equipment', ['filter' => 'replacement'])],
                    ['Transfers 30d', $overview['transfers_30d'] ?? 0, route('admin.operations.movements', ['tab' => 'transfers', 'filter' => 'recent'])],
                    ['Disposals', $overview['disposals_total'] ?? 0, route('admin.operations.movements', ['tab' => 'disposal'])],
                ] as [$label, $value, $href])
                    <a href="{{ $href }}" class="rounded-md px-2.5 py-1.5 text-slate-600 hover:bg-slate-100">
                        {{ $label }} <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ $value }}</span>
                    </a>
                @endforeach
                <span class="mx-1 h-4 w-px bg-slate-200"></span>
                <a href="{{ route('admin.operations.movements') }}" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 font-medium text-[#0025cc] hover:bg-blue-50">
                    All movements <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach([
                ['label' => 'Transfers', 'rows' => $movementTransfers, 'empty' => 'No recent transfers'],
                ['label' => 'Borrowing', 'rows' => $movementBorrows, 'empty' => 'No active borrows'],
                ['label' => 'Disposal', 'rows' => $movementDisposals, 'empty' => 'No disposals yet'],
            ] as $col)
                <div class="flex flex-col rounded-xl bg-slate-50 p-3">
                    <div class="flex items-center justify-between px-1 pb-3">
                        <h3 class="text-sm font-medium text-slate-900">{{ $col['label'] }}</h3>
                        <span class="text-sm font-semibold tabular-nums {{ count($col['rows']) ? 'text-slate-900' : 'text-slate-300' }}">{{ count($col['rows']) }}</span>
                    </div>
                    <div class="flex flex-1 flex-col gap-2">
                        @forelse($col['rows'] as $row)
                            <div class="rounded-lg border border-slate-200 bg-white px-3.5 py-3">
                                @if($col['label'] === 'Transfers')
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $row->equipment_name ?: ('#'.$row->equipment_id) }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $row->from_room_name ?: '—' }} → {{ $row->to_room_name ?: '—' }}</p>
                                @elseif($col['label'] === 'Borrowing')
                                    @php $isOverdue = $row->borrowing_status === 'Overdue'; @endphp
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="min-w-0 truncate text-sm font-medium text-slate-900">{{ $row->equipment_name ?: '—' }}</p>
                                        <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $isOverdue ? $tagAlert : $tagNeutral }}">{{ $row->borrowing_status }}</span>
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $row->borrowing_borrower_name ?: '—' }}
                                        @if($row->borrowing_expected_return_date) · Due {{ \Carbon\Carbon::parse($row->borrowing_expected_return_date)->format('M j') }}@endif
                                    </p>
                                @else
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $row->equipment_name ?: '—' }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $row->disposal_reason ?: '—' }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 px-3 py-8 text-center">
                                <p class="text-xs text-slate-400">{{ $col['empty'] }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- People --}}
    @php
        $peopleTotal = (int) $totalUsers;
        $peopleActive = (int) $activeUsers;
        $peopleActivePct = $peopleTotal > 0 ? min(100, (int) round($peopleActive / $peopleTotal * 100)) : 0;
        $peopleRoles = [
            ['label' => 'Maintenance', 'count' => (int) $maintenancePersonnel, 'color' => '#0025cc'],
            ['label' => 'Purchaser', 'count' => (int) $purchasers, 'color' => '#4a63e0'],
            ['label' => 'Accounting', 'count' => (int) $accounting, 'color' => '#8a9cf0'],
            ['label' => 'Receiving', 'count' => (int) $receivingOfficers, 'color' => '#c3cdfa'],
        ];
        $peopleOther = max(0, $peopleTotal - collect($peopleRoles)->sum('count'));
        if ($peopleOther > 0) {
            $peopleRoles[] = ['label' => 'Admin & others', 'count' => $peopleOther, 'color' => '#e2e8f0'];
        }
        $peopleRoleTotal = max(1, collect($peopleRoles)->sum('count'));
    @endphp
    <section class="ad-panel p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="ad-label">People &amp; access</p>
                <h2 class="mt-1 text-base font-semibold text-slate-900">Who is using PRISM</h2>
            </div>
            <a href="{{ url('/admin/users') }}" class="shrink-0 text-xs font-medium text-slate-500 hover:text-slate-900">Manage users →</a>
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,15rem)_minmax(0,1fr)] lg:items-end">
            <div>
                <p class="flex items-baseline gap-2">
                    <span class="text-4xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $peopleTotal }}</span>
                    <span class="text-sm text-slate-500">{{ $peopleTotal === 1 ? 'account' : 'accounts' }}</span>
                </p>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Active this week</span>
                        <span class="tabular-nums font-medium text-slate-900">{{ $peopleActive }} <span class="font-normal text-slate-400">· {{ $peopleActivePct }}%</span></span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full" style="width: {{ $peopleActivePct }}%; background: #0025cc;"></div>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex h-2 gap-1">
                    @foreach($peopleRoles as $role)
                        @if($role['count'] > 0)
                            <div class="h-full rounded-full" style="flex: {{ $role['count'] }} 1 0%; background: {{ $role['color'] }};" title="{{ $role['label'] }}: {{ $role['count'] }}"></div>
                        @endif
                    @endforeach
                    @if(collect($peopleRoles)->sum('count') === 0)
                        <div class="h-full flex-1 rounded-full bg-slate-100"></div>
                    @endif
                </div>
                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3 xl:grid-cols-5">
                    @foreach($peopleRoles as $role)
                        <div class="min-w-0">
                            <dt class="flex items-center gap-1.5 truncate text-xs text-slate-500">
                                <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $role['color'] }};"></span>
                                {{ $role['label'] }}
                            </dt>
                            <dd class="mt-1 flex items-baseline gap-1.5">
                                <span class="text-lg font-semibold tabular-nums {{ $role['count'] > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ $role['count'] }}</span>
                                <span class="text-[11px] tabular-nums text-slate-400">{{ (int) round($role['count'] / $peopleRoleTotal * 100) }}%</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>
</div>

@include('admin.partials.ris-preview-modal', ['zIndex' => '11000'])

<style>
.ad-panel { border-radius: 1rem; border: 1px solid #e2e8f0; background: #fff; }
.ad-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
.ad-dash .ad-status-cell > span { display: block; width: fit-content; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Calendar (grid and list are rendered by script) */
.ad-dash .admin-dash-cal-nav {
    width: 28px; height: 28px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
    display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer;
}
.ad-dash .admin-dash-cal-nav:hover { color: #0f172a; background: #f8fafc; }
.ad-dash .admin-dash-cal-nav:disabled { opacity: .4; cursor: not-allowed; }
.ad-dash .admin-dash-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.ad-dash .admin-dash-cal-dow { text-align: center; font-size: 10px; color: #94a3b8; padding: 2px 0 6px; }
.ad-dash .admin-dash-cal-day {
    min-height: 32px; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 2px; font-size: 12px; color: #64748b;
}
.ad-dash .admin-dash-cal-day.is-empty { visibility: hidden; }
.ad-dash .admin-dash-cal-day.has-event { color: #0f172a; font-weight: 600; cursor: pointer; }
.ad-dash .admin-dash-cal-day.has-event:hover { background: #f1f5f9; }
.ad-dash .admin-dash-cal-day.is-today { background: #0025cc; color: #fff; font-weight: 600; }
.ad-dash .admin-dash-cal-day.is-today .admin-dash-cal-dot { background: #fff; }
.ad-dash .admin-dash-cal-day.is-selected { box-shadow: inset 0 0 0 1.5px #0025cc; }
.ad-dash .admin-dash-cal-dot { width: 4px; height: 4px; border-radius: 50%; background: #0025cc; display: block; }
.ad-dash .admin-dash-cal-upcoming { margin-top: 16px; border-top: 1px dashed #e2e8f0; padding-top: 14px; }
.ad-dash .admin-dash-cal-upcoming-title { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; margin-bottom: 6px; }
.ad-dash .admin-dash-cal-item { display: flex; align-items: flex-start; gap: 10px; padding: 6px 0; }
.ad-dash .admin-dash-cal-item.is-highlighted { background: #f8fafc; border-radius: 8px; padding: 6px 8px; margin: 0 -8px; }
.ad-dash .admin-dash-cal-item-dot { width: 6px; height: 6px; border-radius: 50%; background: #0025cc; margin-top: 6px; flex-shrink: 0; display: block; }
.ad-dash .admin-dash-cal-item a { color: inherit; text-decoration: none; }
.ad-dash .admin-dash-cal-item a:hover .admin-dash-list-title { color: #0025cc; }
.ad-dash .admin-dash-list-title { font-size: 13px; font-weight: 500; color: #0f172a; }
.ad-dash .admin-dash-list-meta { font-size: 11px; color: #94a3b8; }
.ad-dash .admin-dash-empty { font-size: 12px; color: #94a3b8; text-align: center; }
.ad-dash .admin-dash-cal-all { display: inline-block; margin-top: 8px; font-size: 12px; font-weight: 500; color: #64748b; text-decoration: none; }
.ad-dash .admin-dash-cal-all:hover { color: #0f172a; }
.ad-dash .admin-dash-cal-all::after { content: " →"; }
.ad-dash .admin-dash-cal-hint { margin-top: 2px; font-size: 11px; color: #94a3b8; }
</style>

@push('scripts')
<script>
    window.openRisPreviewModal = function (risId) {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');
        if (!modal || !iframe) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.classList.remove('hidden');
        iframe.src = '/admin/procurement-review/ris/' + risId + '/print?ts=' + Date.now();
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.closeRisPreviewModal = function () {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    };

    (function () {
        var prevBtn = document.getElementById('calPrevBtn');
        var nextBtn = document.getElementById('calNextBtn');
        var monthLabel = document.getElementById('calMonthLabel');
        var grid = document.getElementById('adminCalendarGrid');
        var upcoming = document.getElementById('adminCalendarUpcoming');
        if (!grid || !monthLabel) return;

        var events = {!! json_encode(
            collect($calendarEvents ?? [])->map(function ($event) {
                return [
                    'date' => $event->event_date ?? null,
                    'name' => $event->event_name ?? 'RIS',
                    'id' => $event->ris_id ?? null,
                    'url' => $event->url ?? '/admin/procurement-review',
                ];
            })->filter(fn ($e) => !empty($e['date']))->values()
        ) !!};
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var view = new Date();
        view.setDate(1);
        var now = new Date();
        var minMonthIndex = now.getFullYear() * 12 + now.getMonth() - 1;
        var selectedDate = null;

        function pad(n) { return n < 10 ? '0' + n : String(n); }
        function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function monthIndex(d) { return d.getFullYear() * 12 + d.getMonth(); }
        function escapeHtml(str) {
            return String(str || '').replace(/[&<>"']/g, function (c) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
            });
        }
        function canGoPrev() { return monthIndex(view) > minMonthIndex; }
        function updateNavButtons() {
            if (!prevBtn) return;
            var allowed = canGoPrev();
            prevBtn.disabled = !allowed;
            prevBtn.title = allowed ? 'Previous month' : 'Cannot go back more than one month';
        }
        function eventsOn(dateKey) {
            return events.filter(function (e) { return e.date === dateKey; });
        }

        function renderUpcoming(dateKey) {
            if (!upcoming) return;
            var year = view.getFullYear();
            var month = view.getMonth();
            var listEvents;
            var title;
            var totalCount = 0;
            var viewAllHref = '/admin/procurement-review';

            if (dateKey) {
                listEvents = eventsOn(dateKey).slice().reverse();
                totalCount = listEvents.length;
                listEvents = listEvents.slice(0, 3);
                var parts = dateKey.split('-');
                title = monthNames[parseInt(parts[1], 10) - 1] + ' ' + parseInt(parts[2], 10);
            } else {
                var monthPrefix = year + '-' + pad(month + 1);
                listEvents = events.filter(function (e) { return e.date.indexOf(monthPrefix) === 0; })
                    .sort(function (a, b) { return b.date.localeCompare(a.date); });
                totalCount = listEvents.length;
                listEvents = listEvents.slice(0, 3);
                title = 'Latest activity';
            }

            var list = '<h3 class="admin-dash-cal-upcoming-title">' + escapeHtml(title) + '</h3>';
            if (!listEvents.length) {
                list += '<p class="admin-dash-empty" style="padding:12px 0 !important;">'
                    + (dateKey ? 'No events on this day' : 'No procurement dates this month')
                    + '</p>';
            } else {
                listEvents.forEach(function (e) {
                    var p = e.date.split('-');
                    var label = monthNames[parseInt(p[1], 10) - 1] + ' ' + parseInt(p[2], 10) + ', ' + p[0];
                    var href = e.url || '/admin/procurement-review';
                    var highlight = dateKey ? ' is-highlighted' : '';
                    list += '<div class="admin-dash-cal-item' + highlight + '">';
                    list += '<i class="admin-dash-cal-item-dot"></i><div>';
                    list += '<a href="' + escapeHtml(href) + '"><p class="admin-dash-list-title">' + escapeHtml(e.name) + '</p></a>';
                    list += '<p class="admin-dash-list-meta">' + escapeHtml(label) + '</p></div></div>';
                });
            }
            if (totalCount > 0) {
                list += '<a class="admin-dash-cal-all" href="' + escapeHtml(viewAllHref) + '">View all</a>';
            }
            if (totalCount > 3) {
                list += '<p class="admin-dash-cal-hint">Showing 3 of ' + totalCount + (dateKey ? ' on this day' : '') + '</p>';
            }
            upcoming.innerHTML = list;
        }

        function render() {
            var year = view.getFullYear();
            var month = view.getMonth();
            monthLabel.textContent = monthNames[month] + ' ' + year;

            var first = new Date(year, month, 1);
            var lastDate = new Date(year, month + 1, 0).getDate();
            var startPad = first.getDay();
            var todayKey = ymd(new Date());
            var html = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function (d) {
                html += '<div class="admin-dash-cal-dow">' + d + '</div>';
            });
            var totalSlots = Math.ceil((startPad + lastDate) / 7) * 7;
            for (var i = 0; i < totalSlots; i++) {
                var dayNum = i - startPad + 1;
                if (dayNum < 1 || dayNum > lastDate) {
                    html += '<div class="admin-dash-cal-day is-empty"></div>';
                    continue;
                }
                var dateKey = year + '-' + pad(month + 1) + '-' + pad(dayNum);
                var dayEvents = eventsOn(dateKey);
                var cls = 'admin-dash-cal-day';
                if (dateKey === todayKey) cls += ' is-today';
                if (dayEvents.length) cls += ' has-event';
                if (selectedDate === dateKey) cls += ' is-selected';
                html += '<div class="' + cls + '" data-date="' + dateKey + '" title="'
                    + (dayEvents.length ? dayEvents.length + ' event(s)' : '') + '">';
                html += '<span>' + dayNum + '</span>';
                if (dayEvents.length) html += '<i class="admin-dash-cal-dot"></i>';
                html += '</div>';
            }
            grid.innerHTML = html;
            renderUpcoming(selectedDate);
            updateNavButtons();
        }

        grid.addEventListener('click', function (e) {
            var dayEl = e.target.closest('.admin-dash-cal-day[data-date]');
            if (!dayEl || dayEl.classList.contains('is-empty')) return;
            var dateKey = dayEl.getAttribute('data-date');
            if (!dateKey) return;
            if (!eventsOn(dateKey).length) {
                selectedDate = null;
                render();
                return;
            }
            selectedDate = dateKey;
            render();
        });

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', function () {
                if (!canGoPrev()) return;
                view.setMonth(view.getMonth() - 1);
                selectedDate = null;
                render();
            });
            nextBtn.addEventListener('click', function () {
                view.setMonth(view.getMonth() + 1);
                selectedDate = null;
                render();
            });
        }
        render();
    })();

</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var canvas = document.getElementById('adminRisTrendChart');
        if (!canvas || typeof window.Chart === 'undefined') return;

        var labels = JSON.parse(canvas.dataset.labels || '[]');
        var series = JSON.parse(canvas.dataset.series || '[]');

        new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: series.map(function (s) {
                    return {
                        label: s.label,
                        data: s.data,
                        backgroundColor: s.color,
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 28,
                    };
                }),
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
                        titleFont: { family: 'Outfit', weight: '600' },
                        bodyFont: { size: 12 },
                        filter: function (item) { return item.raw > 0; },
                    },
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11 } },
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#f1f3f7' },
                        ticks: { color: '#94a3b8', font: { size: 11 }, precision: 0 },
                    },
                },
            },
        });
    })();
</script>
@endpush
@endsection
