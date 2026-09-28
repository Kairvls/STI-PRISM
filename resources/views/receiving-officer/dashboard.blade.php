@extends('layouts.receiving-layout')

@section('title', 'Dashboard')

@section('content')
@php
    $d = $dashboard;
    $counts = $d['counts'];
    $queue = $d['queue'];
    $board = $d['board'];
    $snap = $d['snapshot'];
    $week = $d['week'];
    $replacementCount = $board['receiving']['count'] ?? 0;
    $firstName = trim(explode(' ', (string) ($user->user_full_name ?? $user->name ?? ''))[0] ?? '');

    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
    $plural = fn (int $n, string $word, ?string $many = null) => $n.' '.($n === 1 ? $word : ($many ?? $word.'s'));
    $ageText = function (?int $days) {
        if ($days === null) return '—';
        if ($days === 0) return 'today';
        return $days === 1 ? '1 day' : $days.' days';
    };

    // Neutral scale; amber is reserved for things Receiving has to act on.
    $tones = [
        'ink' => ['dot' => 'bg-[#0025cc]', 'hex' => '#0025cc'],
        'mid' => ['dot' => 'bg-slate-500', 'hex' => '#64748b'],
        'soft' => ['dot' => 'bg-slate-300', 'hex' => '#cbd5e1'],
        'accent' => ['dot' => 'bg-amber-500', 'hex' => '#f59e0b'],
    ];
    $boardFilter = [
        'open' => 'unresolved',
        'waiting_restock' => 'waiting_restock',
        'refunded' => 'refunded',
        'receiving' => 'receiving',
    ];

    if ($counts['pendingCount'] > 0) {
        $headline = $plural($counts['pendingCount'], 'delivery', 'deliveries').' waiting for your second count';
    } elseif ($replacementCount > 0) {
        $headline = $plural($replacementCount, 'replacement').' arrived and need counting';
    } else {
        $headline = 'Nothing waiting to be counted';
    }
    $subline = $snap['total'] > 0
        ? $plural($snap['total'], 'back-order line').' still open ('.$plural($snap['units'], 'unit').') across '.$plural($d['incomplete']->count(), 'incomplete report').'.'
        : 'No open back orders. Every counted delivery is complete.';

    $ring = [];
    $cursor = 0;
    foreach ($snap['byStatus'] as $row) {
        if ($snap['total'] <= 0 || $row['count'] <= 0) continue;
        $end = $cursor + ($row['count'] / $snap['total']) * 360;
        $ring[] = $tones[$row['tone']]['hex'].' '.round($cursor, 2).'deg '.round($end, 2).'deg';
        $cursor = $end;
    }
    $ringCss = $ring ? 'conic-gradient('.implode(', ', $ring).')' : 'conic-gradient(#f1f5f9 0deg 360deg)';

    $weekMax = max(1, collect($week)->max(fn ($day) => max($day['arrived'], $day['counted'])));
    $missingShare = $snap['units'] > 0 ? round(($snap['missing']['units'] / $snap['units']) * 100) : 0;
@endphp

<style>
    .rod-panel { border-radius: 1rem; border: 1px solid #e2e8f0; background: #fff; }
    .rod-perf { border-top: 1px dashed #e2e8f0; }
    .rod-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
</style>

<span class="admin-keep-colors hidden" aria-hidden="true"></span>
<div class="mx-auto max-w-[1400px] space-y-6">

    {{-- Overview --}}
    <section class="rod-panel overflow-hidden">
        <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_300px]">
            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                    <i data-lucide="warehouse" class="h-3.5 w-3.5 text-slate-400"></i>
                    <span class="font-medium text-slate-700">Receiving</span>
                    <span class="text-slate-300">/</span>
                    <span>{{ now()->format('l, F j, Y') }}</span>
                    @if($firstName !== '')
                        <span class="text-slate-300">/</span>
                        <span>On duty: {{ $firstName }}</span>
                    @endif
                </p>
                <h1 class="mt-4 max-w-2xl text-2xl font-semibold tracking-tight text-slate-900 sm:text-[28px] sm:leading-tight">{{ $headline }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ $subline }}</p>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <a href="{{ route('receiving.rr.index', ['focus' => 'queue']) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001ea3]">
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i> Start second count
                    </a>
                    <a href="{{ route('receiving.back-orders.index') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="package-x" class="h-4 w-4 text-slate-400"></i> Back orders
                    </a>
                    @if($counts['returnedCount'] > 0)
                        <a href="{{ route('receiving.rr.index', ['focus' => 'returned']) }}"
                           class="ml-1 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> {{ $counts['returnedCount'] }} returned to Purchaser
                        </a>
                    @endif
                </div>
            </div>

            {{-- Last 7 days --}}
            <div class="lg:border-l lg:border-slate-100 lg:pl-8">
                <div class="flex items-center justify-between">
                    <p class="rod-label">Last 7 days</p>
                    <div class="flex items-center gap-3 text-[11px] text-slate-500">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-slate-300"></span>Arrived</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[#0025cc]"></span>Counted</span>
                    </div>
                </div>
                <div class="mt-4 flex h-28 items-end justify-between gap-2">
                    @foreach($week as $day)
                        @php $isToday = $day['date']->isToday(); @endphp
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-2"
                             title="{{ $day['date']->format('D, M j') }} · {{ $day['arrived'] }} arrived · {{ $day['counted'] }} counted">
                            <div class="flex h-full w-full items-end justify-center gap-[3px]">
                                <span class="w-2 rounded-sm bg-slate-300" style="height: {{ $day['arrived'] ? max(8, ($day['arrived'] / $weekMax) * 100) : 2 }}%"></span>
                                <span class="w-2 rounded-sm {{ $day['counted'] ? 'bg-[#0025cc]' : 'bg-slate-200' }}" style="height: {{ $day['counted'] ? max(8, ($day['counted'] / $weekMax) * 100) : 2 }}%"></span>
                            </div>
                            <span class="text-[10px] {{ $isToday ? 'font-semibold text-[#0025cc]' : 'text-slate-400' }}">{{ $isToday ? 'Today' : $day['date']->format('D') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @php
            $stats = [
                ['label' => 'To count', 'value' => $counts['pendingCount'], 'note' => $counts['leftoverCount'] > 0 ? $counts['leftoverCount'].' from before today' : 'All arrived today', 'href' => route('receiving.rr.index', ['focus' => 'queue']), 'alert' => $counts['leftoverCount'] > 0 || $d['urgentCount'] > 0],
                ['label' => 'Replacements', 'value' => $replacementCount, 'note' => 'Waiting at the dock', 'href' => route('receiving.back-orders.index', ['status' => 'receiving']), 'alert' => $replacementCount > 0],
                ['label' => 'Open back orders', 'value' => $snap['total'], 'note' => $snap['units'] > 0 ? $plural($snap['units'], 'unit').' · '.$peso($snap['value']) : 'Nothing outstanding', 'href' => route('receiving.back-orders.index'), 'alert' => false],
                ['label' => 'Counted this month', 'value' => $d['countedThisMonth'], 'note' => 'Last month: '.$d['countedLastMonth'], 'href' => route('receiving.rr.index', ['status' => 'completed']), 'alert' => false],
            ];
        @endphp
        <div class="grid grid-cols-2 border-t border-slate-100 lg:grid-cols-4">
            @foreach($stats as $i => $stat)
                <a href="{{ $stat['href'] }}"
                   class="group px-6 py-5 transition hover:bg-slate-50 sm:px-8 {{ $i % 2 === 1 ? 'border-l border-slate-100' : '' }} {{ $i === 2 ? 'max-lg:border-t lg:border-l' : '' }} {{ $i === 3 ? 'max-lg:border-t' : '' }}">
                    <span class="flex items-center gap-1.5 text-xs text-slate-500">
                        {{ $stat['label'] }}
                        @if($stat['alert'])<span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>@endif
                    </span>
                    <span class="mt-1 block text-3xl font-semibold tabular-nums tracking-tight text-slate-900">{{ $stat['value'] }}</span>
                    <span class="mt-0.5 block truncate text-xs text-slate-400 group-hover:text-slate-500">{{ $stat['note'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Back-order board --}}
    <section class="rod-panel p-6 sm:p-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="rod-label">Back-order board</p>
                <h2 class="mt-1.5 text-lg font-semibold text-slate-900">Where every missing or damaged item stands</h2>
                <p class="mt-0.5 text-sm text-slate-500">Lines move left to right until the replacement passes your second count.</p>
            </div>
            <div class="flex flex-wrap items-center gap-1 text-sm">
                <a href="{{ route('receiving.back-orders.index', ['type' => 'short']) }}" class="rounded-md px-2.5 py-1.5 text-slate-600 hover:bg-slate-100">
                    Missing <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ $snap['missing']['lines'] }}</span>
                </a>
                <a href="{{ route('receiving.back-orders.index', ['type' => 'damaged']) }}" class="rounded-md px-2.5 py-1.5 text-slate-600 hover:bg-slate-100">
                    Damaged <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ $snap['damaged']['lines'] }}</span>
                </a>
                <span class="mx-1 h-4 w-px bg-slate-200"></span>
                <a href="{{ route('receiving.back-orders.index', ['status' => 'all']) }}" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 font-medium text-[#0025cc] hover:bg-blue-50">
                    All back orders <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($board as $status => $col)
                @php $isMine = $status === 'receiving'; $highlight = $isMine && $col['count'] > 0; @endphp
                <div class="flex flex-col rounded-xl p-3 {{ $highlight ? 'bg-amber-50/60 ring-1 ring-amber-200' : 'bg-slate-50' }}">
                    <div class="flex items-start justify-between gap-2 px-1 pb-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ $tones[$col['tone']]['dot'] }}"></span>
                                <h3 class="text-sm font-medium text-slate-900">{{ $col['label'] }}</h3>
                            </div>
                            <p class="mt-0.5 pl-4 text-[11px] text-slate-400">{{ $col['hint'] }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold tabular-nums {{ $col['count'] ? 'text-slate-900' : 'text-slate-300' }}">{{ $col['count'] }}</span>
                    </div>

                    <div class="flex flex-1 flex-col gap-2">
                        @forelse($col['tickets'] as $ticket)
                            <a href="{{ route('receiving.back-orders.index', array_filter(['status' => $boardFilter[$status], 'rr' => $ticket->root_id ?: null])) }}"
                               class="block rounded-lg border border-slate-200 bg-white px-3.5 py-3 transition hover:border-slate-300 hover:shadow-sm">
                                <p class="mb-1 font-mono text-[10px] font-medium tracking-tight text-[#0025cc]">{{ $ticket->number }}</p>
                                <div class="flex items-start justify-between gap-2">
                                    <p class="min-w-0 truncate text-sm font-medium text-slate-900" title="{{ $ticket->article }}">{{ $ticket->article }}</p>
                                    <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium {{ $ticket->type === 'damaged' ? 'border border-slate-200 text-slate-500' : 'bg-slate-100 text-slate-700' }}">{{ $ticket->type_label }}</span>
                                </div>
                                <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900">{{ $ticket->qty }} <span class="text-xs font-normal text-slate-400">{{ $ticket->unit }}</span></p>
                                <div class="rod-perf mt-2.5 space-y-0.5 pt-2 text-[11px] text-slate-500">
                                    <p class="truncate" title="{{ $ticket->supplier }}">{{ $ticket->supplier }}</p>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="truncate text-slate-400">{{ $ticket->rr_number ?? 'RR not linked' }}</span>
                                        <span class="shrink-0 {{ ($ticket->age ?? 0) >= 7 ? 'font-medium text-amber-600' : 'text-slate-400' }}">{{ $ticket->age === 0 ? 'Today' : $ageText($ticket->age) }}</span>
                                    </div>
                                    @if($ticket->reason)
                                        <p class="text-slate-400">{{ $ticket->reason }}</p>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 px-3 py-8 text-center">
                                <p class="text-xs text-slate-400">{{ $isMine ? 'No replacements to count' : 'Empty' }}</p>
                            </div>
                        @endforelse

                        @if($col['count'] > count($col['tickets']))
                            <a href="{{ route('receiving.back-orders.index', ['status' => $boardFilter[$status]]) }}" class="rounded-md py-1.5 text-center text-xs font-medium text-slate-500 hover:bg-white hover:text-slate-900">
                                +{{ $col['count'] - count($col['tickets']) }} more
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Queue + snapshot --}}
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="rod-panel flex flex-col lg:col-span-3">
            <div class="flex items-center justify-between gap-3 px-6 pb-4 pt-6 sm:px-8">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Second-count line</h2>
                    <p class="text-xs text-slate-500">Oldest delivery first · urgent ones jump the line</p>
                </div>
                <a href="{{ route('receiving.rr.index', ['focus' => 'queue']) }}" class="shrink-0 text-xs font-medium text-slate-500 hover:text-slate-900">
                    {{ $queue['total'] > count($queue['rows']) ? 'See all '.$queue['total'] : 'Open queue' }} →
                </a>
            </div>

            <div class="flex-1 divide-y divide-slate-100 border-t border-slate-100">
                @forelse($queue['rows'] as $rr)
                    <a href="{{ route('receiving.rr.index', ['focus' => 'queue', 'search' => $rr->number]) }}" class="group flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50 sm:px-8">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg border border-slate-200">
                            <span class="text-[10px] uppercase tracking-wide text-slate-400">{{ $rr->submitted_at?->format('M') ?? '—' }}</span>
                            <span class="text-lg font-semibold leading-none tabular-nums text-slate-900">{{ $rr->submitted_at?->format('d') ?? '--' }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-slate-900">{{ $rr->number }}</span>
                                <span class="text-[11px] text-slate-400">{{ $rr->status }}</span>
                                @if($rr->is_urgent)
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700"><i data-lucide="zap" class="h-3 w-3"></i>Urgent</span>
                                @endif
                                @if($rr->has_replacement)
                                    <span class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">Replacement</span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-600">{{ $rr->supplier }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-400">
                                {{ collect($rr->articles)->take(3)->implode(', ') }}{{ count($rr->articles) > 3 ? ' +'.(count($rr->articles) - 3).' more' : '' }}
                                · {{ $plural($rr->qty, 'unit') }} · {{ $plural($rr->lines, 'line') }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-xs {{ $rr->is_leftover && $rr->age >= 3 ? 'font-medium text-amber-600' : 'text-slate-500' }}">
                                {{ $rr->age === null ? 'Date unknown' : ($rr->age === 0 ? 'Arrived today' : 'Waiting '.$ageText($rr->age)) }}
                            </p>
                            @if($rr->amount > 0)
                                <p class="mt-0.5 text-xs tabular-nums text-slate-400">{{ $peso($rr->amount) }}</p>
                            @endif
                            <span class="mt-1 inline-flex items-center gap-0.5 text-xs font-medium text-slate-400 group-hover:text-slate-900">Count <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></span>
                        </div>
                    </a>
                @empty
                    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
                        <i data-lucide="check" class="h-5 w-5 text-slate-400"></i>
                        <p class="mt-2 text-sm font-medium text-slate-800">No deliveries waiting</p>
                        <p class="text-xs text-slate-500">New receiving reports from the Purchaser will line up here.</p>
                    </div>
                @endforelse

                @if($d['recentlyCounted']->isNotEmpty() && count($queue['rows']) < 4)
                    <div class="px-6 py-5 sm:px-8">
                        <p class="rod-label">Recently counted</p>
                        <ul class="mt-3 space-y-2">
                            @foreach($d['recentlyCounted'] as $done)
                                @php $isIncomplete = $done->status === 'Incomplete'; @endphp
                                <li class="flex items-center gap-3 text-sm">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $isIncomplete ? 'bg-amber-500' : 'bg-slate-300' }}"></span>
                                    <span class="shrink-0 text-slate-700">{{ $done->number }}</span>
                                    <span class="min-w-0 flex-1 truncate text-xs text-slate-400">{{ $done->supplier }}{{ $done->qty ? ' · '.$plural($done->qty, 'unit') : '' }}</span>
                                    <span class="shrink-0 text-xs {{ $isIncomplete ? 'text-amber-600' : 'text-slate-400' }}">{{ $isIncomplete ? 'Incomplete' : $done->counted_at->format('M j') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if($counts['returnedCount'] > 0 || $counts['leftoverCount'] > 0)
                <div class="flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 px-6 py-3 text-xs sm:px-8">
                    @if($counts['leftoverCount'] > 0)
                        <a href="{{ route('receiving.rr.index', ['focus' => 'leftover']) }}" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-slate-900">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> {{ $counts['leftoverCount'] }} left over from before today
                        </a>
                    @endif
                    @if($counts['returnedCount'] > 0)
                        <a href="{{ route('receiving.rr.index', ['focus' => 'returned']) }}" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-slate-900">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> {{ $counts['returnedCount'] }} returned to Purchaser
                        </a>
                    @endif
                </div>
            @endif
        </section>

        <section class="rod-panel p-6 sm:p-8 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Back-order snapshot</h2>
                <span class="text-[11px] text-slate-400">{{ $snap['fulfilledThisMonth'] }} delivered this month</span>
            </div>

            <div class="mt-6 flex items-center gap-6">
                <div class="relative h-32 w-32 shrink-0 rounded-full" style="background: {{ $ringCss }}">
                    <div class="absolute inset-[10px] flex flex-col items-center justify-center rounded-full bg-white">
                        <span class="text-3xl font-semibold tabular-nums text-slate-900">{{ $snap['total'] }}</span>
                        <span class="text-[11px] text-slate-400">open {{ $snap['total'] === 1 ? 'line' : 'lines' }}</span>
                    </div>
                </div>
                <ul class="min-w-0 flex-1 space-y-2">
                    @foreach($snap['byStatus'] as $status => $row)
                        <li>
                            <a href="{{ route('receiving.back-orders.index', ['status' => $boardFilter[$status]]) }}" class="flex items-center justify-between gap-2 rounded-md px-1 py-0.5 text-sm hover:bg-slate-50">
                                <span class="flex min-w-0 items-center gap-2 text-slate-600">
                                    <span class="h-2 w-2 shrink-0 rounded-full {{ $tones[$row['tone']]['dot'] }}"></span>
                                    <span class="truncate">{{ $row['label'] }}</span>
                                </span>
                                <span class="tabular-nums {{ $row['count'] ? 'font-semibold text-slate-900' : 'text-slate-300' }}">{{ $row['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <dl class="mt-6 grid grid-cols-3 divide-x divide-slate-100 border-y border-slate-100 py-4 text-center">
                <div>
                    <dt class="text-[11px] text-slate-400">Units owed</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums text-slate-900">{{ number_format($snap['units']) }}</dd>
                </div>
                <div class="px-1">
                    <dt class="text-[11px] text-slate-400">Value on hold</dt>
                    <dd class="mt-0.5 truncate text-lg font-semibold tabular-nums text-slate-900" title="{{ $peso($snap['value']) }}">{{ $snap['value'] >= 100000 ? '₱'.number_format($snap['value'] / 1000, 1).'k' : '₱'.number_format($snap['value']) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400">Oldest open</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums {{ ($snap['oldestDays'] ?? 0) >= 7 ? 'text-amber-600' : 'text-slate-900' }}">{{ $snap['oldestDays'] === null ? '—' : ($snap['oldestDays'] === 0 ? 'Today' : $snap['oldestDays'].'d') }}</dd>
                </div>
            </dl>

            <div class="mt-5">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-[#0025cc]"></span>Missing · {{ $plural($snap['missing']['units'], 'unit') }}</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-slate-300"></span>Damaged · {{ $plural($snap['damaged']['units'], 'unit') }}</span>
                </div>
                <div class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                    @if($snap['units'] > 0)
                        <span class="bg-[#0025cc]" style="width: {{ $missingShare }}%"></span>
                        <span class="bg-slate-300" style="width: {{ 100 - $missingShare }}%"></span>
                    @endif
                </div>
            </div>

            @if(!empty($snap['reasons']))
                <div class="mt-5">
                    <p class="rod-label">Why items were held back</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach($snap['reasons'] as $reason)
                            <li class="flex items-center justify-between text-slate-600">
                                <span>{{ $reason['label'] }}</span>
                                <span class="tabular-nums text-slate-900">{{ $reason['count'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    </div>

    {{-- Incomplete reports · suppliers · activity --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rod-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Incomplete reports</h2>
                <a href="{{ route('receiving.rr.index', ['status' => 'completed']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">View →</a>
            </div>
            <p class="mt-0.5 text-xs text-slate-500">Counted, but still waiting on back-order lines</p>

            <div class="mt-4 space-y-2">
                @forelse($d['incomplete'] as $rr)
                    <a href="{{ route('receiving.back-orders.index', ['rr' => $rr->id, 'status' => 'all']) }}" class="block rounded-lg border border-slate-200 p-3 transition hover:border-slate-300 hover:bg-slate-50">
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium text-slate-900">{{ $rr->number }}</span>
                            <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $rr->settled }}/{{ $rr->total }} settled</span>
                        </div>
                        <p class="mt-0.5 truncate text-xs text-slate-400" title="{{ $rr->supplier }}">{{ $rr->supplier }}</p>
                        <div class="mt-2.5 flex h-1 overflow-hidden rounded-full bg-slate-100">
                            <span class="bg-[#0025cc]" style="width: {{ $rr->percent }}%"></span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                            <span class="truncate">
                                {{ collect([
                                    $rr->missing_units ? $rr->missing_units.' missing' : null,
                                    $rr->damaged_units ? $rr->damaged_units.' damaged' : null,
                                ])->filter()->implode(' · ') }}
                                @if($rr->awaiting_count)<span class="text-amber-600"> · {{ $rr->awaiting_count }} to count</span>@endif
                            </span>
                            @if($rr->counted_at)
                                <span class="shrink-0 text-slate-400">Counted {{ $rr->counted_at->format('M j') }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center">
                        <p class="text-xs text-slate-400">Every counted report is complete.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rod-panel p-6">
            <h2 class="text-base font-semibold text-slate-900">Supplier issues</h2>
            <p class="mt-0.5 text-xs text-slate-500">Units held back per supplier, all time</p>

            <div class="mt-4 space-y-4">
                @forelse($d['suppliers']['rows'] as $sup)
                    @php $w = ($sup['units'] / $d['suppliers']['maxUnits']) * 100; @endphp
                    <a href="{{ route('receiving.back-orders.index', ['status' => 'all', 'search' => $sup['name']]) }}" class="group block">
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="truncate text-slate-700 group-hover:text-slate-900" title="{{ $sup['name'] }}">{{ $sup['name'] }}</span>
                            <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $plural($sup['units'], 'unit') }}</span>
                        </div>
                        <div class="mt-1.5 flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="flex h-full" style="width: {{ max(8, $w) }}%">
                                @if($sup['units'] > 0)
                                    <span class="bg-[#0025cc]" style="width: {{ ($sup['missing'] / $sup['units']) * 100 }}%"></span>
                                    <span class="bg-slate-300" style="width: {{ ($sup['damaged'] / $sup['units']) * 100 }}%"></span>
                                @endif
                            </div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            {{ $plural($sup['lines'], 'line') }} · {{ $sup['missing'] }} missing · {{ $sup['damaged'] }} damaged
                            @if($sup['open'])<span class="text-slate-600"> · {{ $sup['open'] }} open</span>@endif
                        </p>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center">
                        <p class="text-xs text-slate-400">No supplier has short or damaged deliveries.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rod-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Dock activity</h2>
                <a href="{{ url('/receiving/logs') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">Logs →</a>
            </div>

            <ol class="relative mt-4 space-y-4 border-l border-slate-200 pl-5">
                @forelse($d['activity'] as $log)
                    <li class="relative">
                        <span class="absolute -left-[24.5px] top-1.5 h-2 w-2 rounded-full ring-4 ring-white {{ $tones[$log->tone]['dot'] ?? 'bg-slate-300' }}"></span>
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm font-medium text-slate-800">{{ $log->action }}</p>
                            <span class="shrink-0 text-[11px] text-slate-400" title="{{ $log->at?->format('M j, Y g:i A') }}">{{ $log->at?->diffForHumans(null, true, true) }}</span>
                        </div>
                        <p class="text-xs text-slate-500">
                            {{ $log->rr_number ?? 'General' }}@if($log->officer) · {{ $log->officer }}@endif
                        </p>
                        @if($log->remarks)
                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-400">{{ $log->remarks }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-xs text-slate-400">No receiving activity yet.</li>
                @endforelse
            </ol>
        </section>
    </div>
</div>
@endsection
