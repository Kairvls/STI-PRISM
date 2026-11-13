@extends('layouts.admin-layout')

@section('title', 'Administrator Dashboard')

@section('content')
@php
    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
    $selectedYear = (int) ($budgetProposalYear ?? now()->year);
    $usage = ($budgetUsage ?? [])[$selectedYear] ?? null;

    $urgentTotal = (int) ($overview['urgent_reports'] ?? 0);
    $overdueSchedules = (int) ($overview['overdue_schedules'] ?? 0);
    $overdueBorrows = (int) ($overview['overdue_borrows'] ?? 0);
    $openBackOrders = (int) ($overview['open_back_orders'] ?? 0);
    $attentionTotal = $urgentTotal + $overdueSchedules + $overdueBorrows + $openBackOrders;

    $sys = $system ?? [];
    $equipmentTotal = (int) ($sys['equipment_total'] ?? 0);
    $storage = (int) ($sys['equipment_storage'] ?? 0);
    $deployed = (int) ($sys['equipment_deployed'] ?? 0);
    $forReplacement = (int) ($sys['for_replacement'] ?? 0);
    $replaced = (int) ($sys['replaced'] ?? 0);
    $disposed = (int) ($sys['disposed'] ?? 0);
    $computerSets = (int) ($sys['computer_sets'] ?? 0);
    $rooms = (int) ($sys['rooms'] ?? 0);
    $departments = (int) ($sys['departments'] ?? 0);
    $users = (int) ($sys['users'] ?? 0);
    $stockSwaps = (int) ($sys['stock_swaps'] ?? 0);
    $procurementReplacements = (int) ($sys['procurement_replacements'] ?? 0);

    $inventoryHref = function (string $path = '') {
        return url('/admin/inventory'.($path !== '' ? '/'.$path : ''));
    };
@endphp

<span class="admin-keep-colors hidden" aria-hidden="true"></span>

<div class="ad-dash mx-3 space-y-5 sm:mx-1 lg:-mx-1">
    {{-- Attention --}}
    <section class="ad-panel overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-slate-950">Needs attention</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Open items that may need Admin follow-up.</p>
                </div>
                <p class="text-xs font-semibold tabular-nums text-slate-500">{{ number_format($attentionTotal) }} total</p>
            </div>
        </div>
        <div class="grid grid-cols-2 divide-y divide-slate-100 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
            @foreach ([
                ['Urgent reports', $urgentTotal, route('admin.operations.reports', ['filter' => 'urgent']), 'file-warning'],
                ['Overdue schedules', $overdueSchedules, route('admin.operations.schedules', ['filter' => 'overdue']), 'calendar-clock'],
                ['Overdue borrows', $overdueBorrows, route('admin.borrowing.index', ['status' => 'Overdue']), 'handshake'],
                ['Open back orders', $openBackOrders, route('admin.back-orders.index'), 'package-x'],
            ] as [$label, $value, $href, $icon])
                <a href="{{ $href }}" class="flex items-start gap-3 px-5 py-4 transition hover:bg-slate-50">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 ring-1 ring-slate-200">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-medium text-slate-500">{{ $label }}</span>
                        <span class="mt-0.5 block text-2xl font-semibold tabular-nums tracking-tight {{ $value > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ number_format($value) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- Equipment --}}
        <section class="ad-panel p-5 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-950">Equipment overview</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Where assets sit across storage and campus rooms.</p>
                </div>
                <a href="{{ route('admin.equipment-all.index') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">All equipment</a>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ([
                    ['Total assets', $equipmentTotal, route('admin.equipment-all.index'), null],
                    ['In storage', $storage, $inventoryHref(), null],
                    ['Deployed', $deployed, route('admin.inventory.deployed'), null],
                    ['For replacement', $forReplacement, route('admin.equipment.replacements'), 'amber'],
                    ['Replaced', $replaced, route('admin.equipment.replacements', ['type' => \App\Support\ReplacementStock::STOCK_SWAP]), 'blue'],
                    ['Disposed', $disposed, route('admin.disposal.index'), 'rose'],
                ] as [$label, $value, $href, $tone])
                    <a href="{{ $href }}" class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3 transition hover:border-slate-300 hover:bg-white">
                        <span class="block text-[11px] font-medium uppercase tracking-wide text-slate-500">{{ $label }}</span>
                        <span @class([
                            'mt-1 block text-xl font-semibold tabular-nums tracking-tight',
                            'text-amber-700' => $tone === 'amber' && $value > 0,
                            'text-[#0025cc]' => $tone === 'blue' && $value > 0,
                            'text-rose-700' => $tone === 'rose' && $value > 0,
                            'text-slate-900' => $tone === null || $value === 0,
                            'text-slate-300' => $value === 0 && $tone !== null,
                        ])>{{ number_format($value) }}</span>
                    </a>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <a href="{{ \App\Support\ComputerSets::url() }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50">
                    <span>
                        <span class="block text-xs font-semibold text-slate-900">Computer sets</span>
                        <span class="mt-0.5 block text-[11px] text-slate-500">Active COMPSET groupings in labs</span>
                    </span>
                    <span class="text-lg font-semibold tabular-nums text-slate-900">{{ number_format($computerSets) }}</span>
                </a>
                <a href="{{ route('admin.equipment.replacements') }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50">
                    <span>
                        <span class="block text-xs font-semibold text-slate-900">Replacement log</span>
                        <span class="mt-0.5 block text-[11px] text-slate-500">{{ number_format($stockSwaps) }} stock · {{ number_format($procurementReplacements) }} procurement</span>
                    </span>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-slate-400"></i>
                </a>
            </div>
        </section>

        {{-- Campus + budget --}}
        <div class="space-y-5">
            <section class="ad-panel p-5">
                <h2 class="text-sm font-semibold text-slate-950">Campus</h2>
                <p class="mt-0.5 text-xs text-slate-500">Core directory counts.</p>
                <dl class="mt-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-xs text-slate-500">Rooms</dt>
                        <dd>
                            <a href="{{ route('admin.rooms.index') }}" class="text-sm font-semibold tabular-nums text-slate-900 hover:text-[#0025cc]">{{ number_format($rooms) }}</a>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-xs text-slate-500">Departments</dt>
                        <dd>
                            <a href="{{ route('admin.departments.index') }}" class="text-sm font-semibold tabular-nums text-slate-900 hover:text-[#0025cc]">{{ number_format($departments) }}</a>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-xs text-slate-500">Users</dt>
                        <dd>
                            <a href="{{ route('admin.users') }}" class="text-sm font-semibold tabular-nums text-slate-900 hover:text-[#0025cc]">{{ number_format($users) }}</a>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="ad-panel p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-950">Budget</h2>
                        <p class="mt-0.5 text-xs text-slate-500">RIS totals for the selected year.</p>
                    </div>
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
                <dl class="mt-4 divide-y divide-slate-100">
                    <div class="flex items-center justify-between gap-3 py-3">
                        <dt class="text-xs text-slate-500">Proposed</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900">{{ $peso($budgetProposalTotal ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-3">
                        <dt class="text-xs text-slate-500">Approved</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900">{{ $peso($usage['approved'] ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-3">
                        <dt class="text-xs text-slate-500">Paid</dt>
                        <dd class="text-sm font-semibold tabular-nums text-slate-900">{{ $peso($usage['released'] ?? 0) }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>

    @php
        $switchCards = [
            ['key' => 'borrows', 'switch' => 'Borrows', 'title' => '3 latest overdue borrows', 'href' => route('admin.borrowing.index', ['status' => 'Overdue']), 'empty' => 'No overdue borrows'],
            ['key' => 'schedules', 'switch' => 'Schedules', 'title' => 'Overdue schedules', 'href' => route('admin.operations.schedules', ['filter' => 'overdue']), 'empty' => 'No overdue schedules'],
            ['key' => 'disposed', 'switch' => 'Disposed', 'title' => '3 latest disposed equipment', 'href' => route('admin.disposal.index'), 'empty' => 'No disposed equipment'],
        ];
        $requestCards = [
            ['key' => 'rooms', 'switch' => 'Rooms', 'title' => 'Rooms reported today', 'href' => route('admin.operations.reports'), 'empty' => 'No reports today'],
            ['key' => 'transfers', 'switch' => 'Transfers', 'title' => 'Transfer requests', 'href' => route('admin.inventory.transfer-requests'), 'empty' => 'No transfer requests'],
            ['key' => 'disposals', 'switch' => 'Disposals', 'title' => 'Disposal requests', 'href' => route('admin.disposal.requests'), 'empty' => 'No disposal requests'],
        ];
        $cards = [
            ['key' => 'prs', 'title' => '3 latest procurement', 'href' => route('admin.procurement-review'), 'empty' => 'No procurement requests'],
        ];
        $switchGroups = [$switchCards, $requestCards];
    @endphp
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
        @foreach ($switchGroups as $group)
            <section class="ad-panel p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="admin-list-switch inline-flex rounded-full bg-slate-100 p-0.5" role="group" aria-label="Dashboard lists">
                        @foreach ($group as $index => $card)
                            <button type="button" class="ad-switch {{ $index === 0 ? 'is-on' : '' }}" data-admin-list="{{ $card['key'] }}" aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">{{ $card['switch'] }}</button>
                        @endforeach
                    </div>
                </div>
                @foreach ($group as $index => $card)
                    @php
                        $block = $lists[$card['key']] ?? ['total' => 0, 'rows' => []];
                        $rows = collect($block['rows'] ?? []);
                        $total = (int) ($block['total'] ?? 0);
                    @endphp
                    <div data-admin-panel="{{ $card['key'] }}" class="{{ $index === 0 ? '' : 'hidden' }}">
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-2">
                                <h2 class="truncate text-sm font-semibold text-slate-950">{{ $card['title'] }}</h2>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($total) }}</span>
                            </div>
                            <a href="{{ $card['href'] }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                        </div>
                        <div class="mt-3 divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                <a href="{{ $row['href'] }}" class="flex items-center justify-between gap-3 py-3 transition hover:bg-slate-50">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                                        @if (!empty($row['meta']))
                                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['meta'] }}</span>
                                        @endif
                                    </span>
                                </a>
                            @empty
                                <p class="py-8 text-center text-xs font-medium text-slate-400">{{ $card['empty'] }}</p>
                            @endforelse
                        </div>
                        @if ($total > $rows->count())
                            <p class="mt-2 text-xs text-slate-500">{{ $rows->count() }} latest of {{ number_format($total) }}</p>
                        @endif
                    </div>
                @endforeach
            </section>
        @endforeach
        @foreach ($cards as $card)
            @php
                $block = $lists[$card['key']] ?? ['total' => 0, 'rows' => []];
                $rows = collect($block['rows'] ?? []);
                $total = (int) ($block['total'] ?? 0);
            @endphp
            <section class="ad-panel p-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <h2 class="truncate text-sm font-semibold text-slate-950">{{ $card['title'] }}</h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($total) }}</span>
                    </div>
                    <a href="{{ $card['href'] }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                </div>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <a href="{{ $row['href'] }}" class="flex items-center justify-between gap-3 py-3 transition hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                                @if (!empty($row['meta']))
                                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['meta'] }}</span>
                                @endif
                            </span>
                            @if (array_key_exists('amount', $row))
                                <span class="shrink-0 text-xs font-semibold tabular-nums text-blue-700">{{ $peso($row['amount']) }}</span>
                            @endif
                        </a>
                    @empty
                        <p class="py-8 text-center text-xs font-medium text-slate-400">{{ $card['empty'] }}</p>
                    @endforelse
                </div>
                @if ($total > $rows->count())
                    <p class="mt-2 text-xs text-slate-500">{{ $rows->count() }} latest of {{ number_format($total) }}</p>
                @endif
            </section>
        @endforeach
    </div>
</div>

<style>
.ad-panel { border-radius: 1rem; border: 1px solid #e2e8f0; background: #fff; }
.ad-switch {
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    padding: 6px 10px;
    cursor: pointer;
}
.ad-switch.is-on {
    background: #fff;
    color: #0f172a;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}
</style>
<script>
    document.querySelectorAll('.admin-list-switch').forEach(function (switcher) {
        var card = switcher.closest('section');
        switcher.addEventListener('click', function (event) {
            var button = event.target.closest('[data-admin-list]');
            if (!button || !card) return;
            var key = button.getAttribute('data-admin-list');
            switcher.querySelectorAll('[data-admin-list]').forEach(function (item) {
                var on = item === button;
                item.classList.toggle('is-on', on);
                item.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            card.querySelectorAll('[data-admin-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-admin-panel') !== key);
            });
        });
    });
</script>
@endsection
