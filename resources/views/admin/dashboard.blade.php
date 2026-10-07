@extends('layouts.admin-layout')

@section('title', 'Administrator Dashboard')

@section('content')
@php
    $stages = $overview['stage_counts'] ?? [];

    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
    $plural = fn (int $n, string $word, ?string $many = null) => $n.' '.($n === 1 ? $word : ($many ?? $word.'s'));
    $tagAlert = 'bg-amber-50 text-amber-700';
    $tagNeutral = 'bg-slate-100 text-slate-600';

    $urgentReports = (int) ($overview['urgent_reports'] ?? 0);
    $overdueSchedules = (int) ($overview['overdue_schedules'] ?? 0);
    $overdueBorrows = (int) ($overview['overdue_borrows'] ?? 0);
    $openBackOrderRrs = (int) ($receivingSummary['open'] ?? 0);

    $attentionCount = $urgentReports + $overdueSchedules + $overdueBorrows + $openBackOrderRrs;
    $headline = $attentionCount > 0
        ? $plural($attentionCount, 'item').' across campus and procurement need follow-up'
        : 'Campus and procurement are on track';

    $pipeline = [
        'ris' => 'RIS',
        'atp' => 'ATP',
        'rfc' => 'RFC / CA',
        'receiving' => 'RR',
        'liquidation' => 'Liquidation',
    ];
    $pipelineMax = max(1, collect($pipeline)->keys()->map(fn ($key) => (int) ($stages[$key] ?? 0))->max());

    $stats = [
        ['label' => 'Urgent reports', 'value' => $urgentReports, 'note' => ($overview['open_reports'] ?? 0).' open in total', 'href' => route('admin.operations.reports', ['filter' => 'urgent'])],
        ['label' => 'Overdue schedules', 'value' => $overdueSchedules, 'note' => 'Maintenance past due', 'href' => route('admin.operations.schedules', ['filter' => 'overdue'])],
        ['label' => 'Overdue borrows', 'value' => $overdueBorrows, 'note' => 'Equipment not returned on time', 'href' => route('admin.operations.borrowing', ['filter' => 'Overdue'])],
        ['label' => 'Open back orders', 'value' => $openBackOrderRrs, 'note' => 'Deliveries with missing or damaged items', 'href' => route('admin.back-orders.index')],
    ];

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
                <p class="text-xs text-slate-500">{{ now()->format('l, F j, Y') }}</p>
                <h1 class="mt-3 max-w-2xl text-2xl font-semibold tracking-tight text-slate-900 sm:text-[28px] sm:leading-tight">{{ $headline }}</h1>
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

    {{-- Budget + back orders --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @php
            $selectedYear = (int) ($budgetProposalYear ?? now()->year);
            $usage = ($budgetUsage ?? [])[$selectedYear] ?? null;
        @endphp
        <section class="ad-panel p-6 sm:p-8">
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
            <p class="mt-1 text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $peso($budgetProposalTotal ?? 0) }}</p>
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
                        <span title="Paid counts Request for Check and Cash Advance funds released that year.">Budget used</span>
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
        </section>

        <section class="ad-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">RR with back orders</h2>
                <a href="{{ route('admin.back-orders.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">All {{ $receivingSummary['total'] ?? 0 }} →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Latest receiving reports with missing or damaged items</p>

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
                                · <span class="text-amber-700">{{ $plural($rr->openQty, 'item') }} ({{ $peso($rr->openValue) }}) not delivered</span>
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

    {{-- Suppliers --}}
    @php
        $supplierComparison = $supplierComparison ?? collect();
        $supplierComparisonMax = (float) ($supplierComparisonMax ?? 0);
        $typeCompare = $supplierTypeComparison ?? ['physical_count' => 0, 'online_count' => 0, 'physical_amount' => 0, 'online_amount' => 0];
        $typeTotalAmount = (float) $typeCompare['physical_amount'] + (float) $typeCompare['online_amount'];
        $physicalShare = $typeTotalAmount > 0 ? round((float) $typeCompare['physical_amount'] / $typeTotalAmount * 100) : 0;
    @endphp
    <div class="grid gap-6 lg:grid-cols-2">
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
                <h2 class="text-base font-semibold text-slate-900">Equipment movements</h2>
                <p class="mt-0.5 text-xs text-slate-500">Transfers, borrowing and disposal across campus</p>
            </div>
            <div class="flex flex-wrap items-center gap-1 text-sm">
                @foreach([
                    ['Equipment', $overview['equipment_total'] ?? 0, route('admin.operations.equipment')],
                    ['Under maint.', $overview['needs_maintenance'] ?? 0, route('admin.operations.equipment', ['filter' => 'maintenance'])],
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
            <h2 class="text-base font-semibold text-slate-900">Who is using PaAyo</h2>
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

<style>
.ad-panel { border-radius: 1rem; border: 1px solid #e2e8f0; background: #fff; }
.ad-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
</style>
@endsection
