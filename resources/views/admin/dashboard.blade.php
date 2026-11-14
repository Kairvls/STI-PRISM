@extends('layouts.admin-layout')

@section('title', 'Administrator Dashboard')

@section('content')
@php
    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
    $selectedYear = (int) ($budgetProposalYear ?? now()->year);
    $usage = ($budgetUsage ?? [])[$selectedYear] ?? null;

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
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- Equipment + maintenance calendar --}}
        <div class="space-y-5 lg:col-span-2">
        @include('partials.nearest-lifespan-broadcasts', [
            'lifespan' => $lists['lifespan'] ?? ['total' => 0, 'rows' => [], 'due' => []],
            'lifespanIndexUrl' => route('admin.lifespan-broadcasts.index'),
            'includeLifespanConfirm' => true,
        ])


        @php $maintenanceCalendar = \App\Support\AdminDashboardLists::maintenanceCalendar(); @endphp
        <section class="ad-panel p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-950">Maintenance calendar</h2>
                <a href="{{ $maintenanceCalendar['href'] }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                </div>
            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <div class="flex items-center gap-2">
                        <label class="sr-only" for="maintenanceCalendarMonth">Month</label>
                        <select id="maintenanceCalendarMonth" class="rounded-md border border-slate-200 bg-white py-1 pl-2 pr-7 text-xs font-semibold text-slate-800 focus:border-slate-300 focus:outline-none focus:ring-0"></select>
                        <label class="sr-only" for="maintenanceCalendarYear">Year</label>
                        <select id="maintenanceCalendarYear" class="rounded-md border border-slate-200 bg-white py-1 pl-2 pr-7 text-xs font-semibold text-slate-800 focus:border-slate-300 focus:outline-none focus:ring-0"></select>
                        <button type="button" id="maintenanceCalendarToday" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-[#0025cc]" title="Back to today" aria-label="Back to today">
                            <i data-lucide="calendar-check" class="h-3.5 w-3.5"></i>
                    </button>
            </div>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-semibold text-slate-500">
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>Equipment</span>
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>Room</span>
                        <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Semestral</span>
                    </div>
                    <div class="mt-3 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-slate-400">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>
                    <div id="maintenanceCalendarDays" class="mt-1 grid grid-cols-7 gap-1"></div>
            </div>
                <div class="min-w-0 border-t border-slate-100 pt-4 sm:border-l sm:border-t-0 sm:pl-5 sm:pt-0">
                    <p id="maintenanceCalendarDate" class="text-sm font-semibold text-slate-900"></p>
                    <div id="maintenanceCalendarList" class="mt-2 max-h-[13.5rem] divide-y divide-slate-100 overflow-y-auto"></div>
            </div>
            </div>
            <script type="application/json" id="maintenanceCalendarData">@json($maintenanceCalendar)</script>
        </section>
        <div id="maintenanceCalendarPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center bg-black/50 px-4 pt-16">
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
                <button type="button" data-calendar-preview-close class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="px-6 pb-2 pr-12 pt-6">
                    <p id="calendarPreviewDate" class="text-sm font-medium text-slate-400"></p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Schedule name</p>
                    <h3 id="calendarPreviewTitle" class="mt-0.5 text-lg font-semibold tracking-tight text-slate-900"></h3>
                    <p id="calendarPreviewMeta" class="mt-1 text-base text-slate-500"></p>
                    <p id="calendarPreviewIdentity" class="mt-1 hidden text-base font-medium text-slate-700"></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span id="calendarPreviewBadge" class="rounded-full px-2.5 py-0.5 text-sm font-semibold ring-1"></span>
                        <span id="calendarPreviewStatus" class="rounded-full px-2.5 py-0.5 text-sm font-semibold"></span>
                </div>
            </div>
                <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
                    <button type="button" data-calendar-preview-close class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">Close</button>
                    <a id="calendarPreviewGo" href="#" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001fad]">Go to page</a>
            </div>
                </div>
            </div>
            </div>

        {{-- Campus + budget --}}
        <div class="space-y-5">
        <section class="ad-panel p-5">
                <h2 class="text-sm font-semibold text-slate-950">Equipment</h2>
                @php
                    $computerSetPreview = \App\Support\ComputerSets::dashboardPreview();
                    $propertyPreview = \App\Support\PropertyAssignments::dashboardPreview();
                @endphp
                <div class="mt-4 grid grid-cols-1 gap-3">
                    <button type="button" class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:bg-slate-50" data-computer-sets-open>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">Computer sets</span>
                            <span class="mt-0.5 block text-xs text-slate-500">Active COMPSET groupings in labs</span>
                        </span>
                        <span class="text-sm font-semibold tabular-nums text-slate-900">{{ number_format($computerSets) }}</span>
                    </button>
                    <button type="button" class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:bg-slate-50" data-property-assignments-open>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">Property assignment</span>
                            <span class="mt-0.5 block text-xs text-slate-500">People holding assigned equipment</span>
                        </span>
                        <span class="text-sm font-semibold tabular-nums text-slate-900">{{ number_format($propertyPreview['total'] ?? 0) }}</span>
                    </button>
                    <button type="button" class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:bg-slate-50" data-replacement-log-open>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">Replacement log</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ number_format($stockSwaps) }} stock · {{ number_format($procurementReplacements) }} procurement</span>
                            </span>
                        <i data-lucide="arrow-right" class="h-4 w-4 text-slate-400"></i>
                        </button>
                </div>
                <script type="application/json" id="computerSetsPreviewData">@json($computerSetPreview)</script>
                <script type="application/json" id="propertyAssignmentsPreviewData">@json($propertyPreview)</script>
                <script type="application/json" id="replacementLogPreviewData">@json(\App\Support\ReplacementStock::dashboardPreview())</script>
            </section>

        <section class="ad-panel p-5">
                <div class="admin-list-switch inline-flex rounded-full bg-slate-100 p-0.5" role="group" aria-label="Budget and procurement">
                    <button type="button" class="ad-switch is-on" data-admin-list="prs" aria-pressed="true">Procurement</button>
                    <button type="button" class="ad-switch" data-admin-list="budget" aria-pressed="false">Budget</button>
                </div>
                <div data-admin-panel="budget" class="hidden">
                    <div class="mt-4 flex items-center justify-between gap-3">
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
                </div>
                @php
                    $procurement = $lists['prs'] ?? ['total' => 0, 'rows' => []];
                    $procurementRows = collect($procurement['rows'] ?? []);
                    $procurementTotal = (int) ($procurement['total'] ?? 0);
                @endphp
                <div data-admin-panel="prs">
                    <div class="mt-4 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <h2 class="truncate text-sm font-semibold text-slate-950">3 latest procurement</h2>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($procurementTotal) }}</span>
                        </div>
                        <a href="{{ route('admin.procurement-review') }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                    </div>
                    <div class="mt-3 divide-y divide-slate-100">
                        @forelse ($procurementRows as $row)
                            @if (!empty($row['preview_url']))
                                <button type="button" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50" data-dashboard-preview data-preview-kind="pipeline" data-preview-url="{{ $row['preview_url'] }}" data-continue-url="{{ $row['href'] }}" data-continue-label="Open" data-preview-title="{{ $row['title'] }}">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-[#0025cc]">{{ $row['title'] }}</span>
                                        @if (!empty($row['meta']))
                                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['meta'] }}</span>
                                        @endif
                                    </span>
                                    @if (array_key_exists('amount', $row))
                                        <span class="shrink-0 text-xs font-semibold tabular-nums text-blue-700">{{ $peso($row['amount']) }}</span>
                                    @endif
                                </button>
                            @else
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
                            @endif
                        @empty
                            <p class="py-8 text-center text-xs font-medium text-slate-400">No procurement requests</p>
                        @endforelse
                    </div>
                    @if ($procurementTotal > $procurementRows->count())
                        <p class="mt-2 text-xs text-slate-500">{{ $procurementRows->count() }} latest of {{ number_format($procurementTotal) }}</p>
                    @endif
                </div>
        </section>
        </div>
    </div>

    @php
        $switchCards = [
            ['key' => 'borrows', 'switch' => 'Borrowed', 'title' => '3 latest overdue borrowed', 'href' => route('admin.borrowing.index', ['status' => 'Overdue']), 'empty' => 'No overdue borrowed'],
            ['key' => 'schedules', 'switch' => 'Schedules', 'title' => 'Overdue schedules', 'href' => route('admin.operations.schedules', ['filter' => 'overdue']), 'empty' => 'No overdue schedules'],
            ['key' => 'disposed', 'switch' => 'Disposed', 'title' => '3 latest disposed equipment', 'href' => route('admin.disposal.index'), 'empty' => 'No disposed equipment'],
        ];
        $requestCards = [
            ['key' => 'rooms', 'switch' => 'Rooms', 'title' => 'Rooms reported today', 'href' => route('admin.operations.reports'), 'empty' => 'No reports today'],
            ['key' => 'transfers', 'switch' => 'Transfers', 'title' => 'Transfer requests for approval', 'href' => route('admin.inventory.transfer-requests'), 'empty' => 'No transfer requests for approval'],
            ['key' => 'disposals', 'switch' => 'Disposals', 'title' => 'Disposal requests for approval', 'href' => route('admin.disposal.requests'), 'empty' => 'No disposal requests for approval'],
        ];
        $switchGroups = [$switchCards, $requestCards];
    @endphp
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
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
                                <h2 class="text-sm font-semibold text-slate-950">{{ $card['title'] }}</h2>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($total) }}</span>
                            </div>
                            <a href="{{ $card['href'] }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                        </div>
                        <div class="mt-3 divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                @if ($card['key'] === 'borrows' && !empty($row['preview']))
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50"
                                        data-borrow-preview="{{ json_encode($row['preview'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-normal text-[#0025cc]">{{ $row['title'] }}</span>
                                            @if (!empty($row['meta']))
                                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['meta'] }}</span>
                                            @endif
                                        </span>
                                        <span class="shrink-0 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ $row['badge'] ?? 'Overdue' }}</span>
                                    </button>
                                @else
                                    <a href="{{ $row['href'] }}" class="flex items-center justify-between gap-3 py-3 transition hover:bg-slate-50">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                                            @if (!empty($row['meta']))
                                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['meta'] }}</span>
                                            @endif
                                        </span>
                                    </a>
                                @endif
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
<div id="adminPrPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="adminPrPreviewTitle">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="adminPrPreviewTitle" class="truncate text-sm font-semibold text-slate-950">Request</h2>
            <button type="button" data-pr-preview-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">&times;</button>
        </div>
        <div id="adminPrPreviewStage" class="relative h-auto bg-white">
            <iframe id="adminPrPreviewFrame" title="Request preview" class="w-full border-0 bg-white" src="about:blank"></iframe>
            <div id="adminPrPreviewSkeleton" class="bg-white p-5" role="status" aria-live="polite" aria-busy="true">
                <span class="sr-only">Loading request…</span>
                <div aria-hidden="true">
                    <div class="prism-skel" style="width:72px;height:9px"></div>
                    <div class="prism-skel mt-2" style="width:220px;height:22px"></div>
                    <div class="mt-5 rounded-xl border border-gray-200 p-5">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <div class="prism-skel" style="width:48px;height:9px"></div>
                                <div class="prism-skel mt-2" style="width:168px;height:16px"></div>
                                <div class="prism-skel mt-2" style="width:72px;height:10px"></div>
                            </div>
                            <div class="shrink-0">
                                <div class="prism-skel ml-auto" style="width:148px;height:10px"></div>
                                <div class="prism-skel ml-auto mt-2" style="width:112px;height:10px"></div>
                            </div>
                        </div>
                        <div class="mt-8 flex items-start">
                            @for ($step = 0; $step < 6; $step++)
                                <div class="flex flex-1 flex-col items-center">
                                    <div class="flex w-full items-center">
                                        <div class="h-0.5 flex-1 {{ $step === 0 ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                        <div class="prism-skel prism-skel-round shrink-0" style="width:36px;height:36px"></div>
                                        <div class="h-0.5 flex-1 {{ $step === 5 ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                    </div>
                                    <div class="prism-skel mt-2" style="width:64px;height:9px"></div>
                                    <div class="prism-skel mt-1.5" style="width:92px;height:10px"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" data-pr-preview-close class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="adminPrPreviewOpen" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>
<div id="equipmentPhotoZoom" class="fixed inset-0 z-[13000] hidden items-center justify-center bg-black/80 p-6" role="dialog" aria-modal="true" aria-label="Equipment photo">
    <button type="button" class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-lg text-2xl leading-none text-white hover:bg-white/10" data-photo-zoom-close aria-label="Close">&times;</button>
    <img id="equipmentPhotoZoomImg" alt="" class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain">
</div>
<div id="replacementLogModal" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="replacementLogHeading">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="replacementLogHeading" class="truncate text-lg font-semibold text-slate-950">Replacement log</h2>
            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" data-replacement-log-close aria-label="Close">&times;</button>
        </div>
        <div id="replacementLogBody" class="max-h-[70vh] divide-y divide-slate-100 overflow-y-auto bg-white px-5"></div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" class="text-sm font-medium text-slate-500 hover:text-slate-900" data-replacement-log-close>Close</button>
            <a id="replacementLogOpen" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>
<div id="propertyAssignmentsModal" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="propertyAssignmentsHeading">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="propertyAssignmentsHeading" class="truncate text-lg font-semibold text-slate-950">Property assignment</h2>
            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" data-property-assignments-close aria-label="Close">&times;</button>
        </div>
        <div id="propertyAssignmentsBody" class="max-h-[70vh] space-y-4 overflow-y-auto bg-white p-5"></div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" class="text-sm font-medium text-slate-500 hover:text-slate-900" data-property-assignments-close>Close</button>
            <a id="propertyAssignmentsOpen" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>
<div id="computerSetsModal" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="computerSetsHeading">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="computerSetsHeading" class="truncate text-sm font-semibold text-slate-950">Computer sets</h2>
            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" data-computer-sets-close aria-label="Close">&times;</button>
        </div>
        <div class="border-b border-slate-200 px-5 py-3">
            <div id="computerSetsFilter" class="relative">
                <button type="button" id="computerSetsFilterButton" class="flex h-10 w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 text-left text-sm text-slate-700 hover:bg-slate-50" aria-expanded="false" aria-controls="computerSetsFilterMenu">
                    <span id="computerSetsFilterLabel" class="truncate">All computer sets</span>
                    <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
            </button>
                <div id="computerSetsFilterMenu" class="absolute left-0 right-0 z-20 mt-1 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                    <div class="border-b border-slate-100 p-2">
                        <input id="computerSetsFilterSearch" type="search" placeholder="Search set, room, held by, or equipment" class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:border-slate-300">
        </div>
                    <div id="computerSetsFilterOptions" class="max-h-56 overflow-y-auto py-1"></div>
            </div>
        </div>
        </div>
        <div id="computerSetsBody" class="max-h-[70vh] space-y-4 overflow-y-auto bg-white p-5"></div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" class="text-sm font-medium text-slate-500 hover:text-slate-900" data-computer-sets-close>Close</button>
            <a id="computerSetsOpen" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>
<div id="borrowSlipModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-900/40 p-4">
    <div class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="borrowSlipTitle">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Borrow slip</p>
                <h3 id="borrowSlipTitle" class="truncate font-mono text-lg font-semibold text-slate-950"></h3>
                <p id="borrowSlipMeta" class="mt-1 text-sm text-slate-500"></p>
            </div>
            <button type="button" class="rounded-lg px-2 py-1 text-lg leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" data-borrow-close aria-label="Close">&times;</button>
        </div>
        <div id="borrowSlipItems" class="min-h-0 flex-1 divide-y divide-slate-100 overflow-y-auto px-5"></div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
            <button type="button" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" data-borrow-close>Close</button>
            <a id="borrowSlipOpen" href="#" class="rounded-lg bg-[#0025cc] px-3 py-2 text-sm font-semibold text-white hover:bg-[#001eaa]">Open slip</a>
        </div>
    </div>
</div>
@include('layouts.partials.equipment-layout-icons')
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

    var borrowModal = document.getElementById('borrowSlipModal');
    var borrowTitle = document.getElementById('borrowSlipTitle');
    var borrowMeta = document.getElementById('borrowSlipMeta');
    var borrowItems = document.getElementById('borrowSlipItems');
    var borrowOpen = document.getElementById('borrowSlipOpen');
    var statusClass = {
        Borrowed: 'bg-sky-50 text-sky-700',
        Returned: 'bg-emerald-50 text-emerald-700',
        Overdue: 'bg-rose-50 text-rose-700'
    };

    function closeBorrowModal() {
        if (!borrowModal) return;
        borrowModal.classList.add('hidden');
        borrowModal.classList.remove('flex');
    }

    function openBorrowModal(data) {
        if (!borrowModal || !data) return;
        borrowTitle.textContent = data.slip || 'Borrow slip';
        var meta = [data.borrower, data.due, data.event].filter(Boolean).join(' · ');
        borrowMeta.textContent = meta;
        borrowMeta.classList.toggle('hidden', meta === '');
        borrowOpen.href = data.href || '#';
        borrowItems.replaceChildren();
        (data.items || []).forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 py-3';
            var text = document.createElement('div');
            text.className = 'min-w-0';
            var name = document.createElement('p');
            name.className = 'truncate text-sm font-semibold text-slate-900';
            name.textContent = item.name || 'Equipment';
            text.appendChild(name);
            var uniqueness = item.identity || 'No asset tag / serial';
            if (item.qty > 1 && uniqueness.indexOf('Qty ') === -1) {
                uniqueness += ' · Qty ' + item.qty;
            }
            var metaLine = document.createElement('p');
            metaLine.className = 'mt-0.5 truncate text-xs font-medium text-slate-600';
            metaLine.textContent = uniqueness;
            text.appendChild(metaLine);
            row.appendChild(text);
            var badgeWrap = document.createElement('div');
            badgeWrap.className = 'flex shrink-0 flex-col items-end gap-1';
            if (item.overdue_days) {
                var latePill = document.createElement('span');
                latePill.className = 'rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700';
                latePill.textContent = item.overdue_days === 1 ? '1d overdue' : item.overdue_days + 'd overdue';
                badgeWrap.appendChild(latePill);
            }
            if (item.status) {
                var pill = document.createElement('span');
                pill.className = 'rounded-full px-2 py-0.5 text-[11px] font-semibold ' + (statusClass[item.status] || 'bg-slate-100 text-slate-600');
                pill.textContent = item.status;
                badgeWrap.appendChild(pill);
            }
            row.appendChild(badgeWrap);
            borrowItems.appendChild(row);
        });
        borrowModal.classList.remove('hidden');
        borrowModal.classList.add('flex');
    }

    var setsModal = document.getElementById('computerSetsModal');
    var setsBody = document.getElementById('computerSetsBody');
    var setsHeading = document.getElementById('computerSetsHeading');
    var setsOpen = document.getElementById('computerSetsOpen');

    var photoZoom = document.getElementById('equipmentPhotoZoom');
    var photoZoomImg = document.getElementById('equipmentPhotoZoomImg');

    function closeEquipmentZoom() {
        if (!photoZoom) return;
        photoZoom.classList.add('hidden');
        photoZoom.classList.remove('flex');
        if (photoZoomImg) photoZoomImg.removeAttribute('src');
    }

    function openEquipmentZoom(src, label) {
        if (!photoZoom || !photoZoomImg || !src) return;
        photoZoomImg.src = src;
        photoZoomImg.alt = label || 'Equipment';
        photoZoom.classList.remove('hidden');
        photoZoom.classList.add('flex');
    }

    var replacementLogModal = document.getElementById('replacementLogModal');
    var replacementLogBody = document.getElementById('replacementLogBody');
    var replacementLogOpen = document.getElementById('replacementLogOpen');

    function closeReplacementLog() {
        if (!replacementLogModal) return;
        replacementLogModal.classList.add('hidden');
        replacementLogModal.classList.remove('flex');
    }

    function openReplacementLog() {
        var payload = { href: '#', total: 0, rows: [] };
        try {
            payload = JSON.parse((document.getElementById('replacementLogPreviewData') || {}).textContent || '{}');
        } catch (error) {
            payload = { href: '#', total: 0, rows: [] };
        }
        var rows = payload.rows || [];
        replacementLogOpen.href = payload.href || '#';
        replacementLogBody.replaceChildren();
        if (!rows.length) {
            var empty = document.createElement('p');
            empty.className = 'py-8 text-center text-sm text-slate-400';
            empty.textContent = 'No replacement records';
            replacementLogBody.appendChild(empty);
        }
        rows.forEach(function (row) {
            var item = document.createElement('div');
            item.className = 'flex items-start justify-between gap-3 py-3';
            var text = document.createElement('div');
            text.className = 'min-w-0';
            var title = document.createElement('p');
            title.className = 'truncate text-sm font-semibold text-slate-900';
            title.textContent = row.replaced || 'Equipment';
            text.appendChild(title);
            var fromTag = row.replaced_tag ? 'Tag ' + row.replaced_tag : '';
            var toName = row.replacement || '';
            var toTag = row.replacement_tag ? 'Tag ' + row.replacement_tag : '';
            var successor = [toName, toTag].filter(Boolean).join(' · ');
            var detail = document.createElement('p');
            detail.className = 'mt-0.5 text-xs font-medium text-slate-600';
            detail.textContent = [fromTag, successor ? 'Replaced by ' + successor : '', row.date].filter(Boolean).join(' · ');
            text.appendChild(detail);
            item.appendChild(text);
            if (row.type) {
                var pill = document.createElement('span');
                var pillTone = {
                    'Swapped from inventory stock': 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                    'Procurement request': 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-300',
                    'Marked Replaced (no successor)': 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'
                };
                pill.className = 'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ' + (pillTone[row.type] || 'bg-slate-100 text-slate-600 ring-1 ring-slate-200');
                pill.textContent = row.type;
                item.appendChild(pill);
            }
            replacementLogBody.appendChild(item);
        });
        if (payload.total > rows.length) {
            var more = document.createElement('p');
            more.className = 'py-3 text-xs text-slate-500';
            more.textContent = rows.length + ' latest of ' + payload.total;
            replacementLogBody.appendChild(more);
        }
        replacementLogModal.classList.remove('hidden');
        replacementLogModal.classList.add('flex');
    }

    var propertyModal = document.getElementById('propertyAssignmentsModal');
    var propertyBody = document.getElementById('propertyAssignmentsBody');
    var propertyOpen = document.getElementById('propertyAssignmentsOpen');

    function closePropertyAssignments() {
        if (!propertyModal) return;
        propertyModal.classList.add('hidden');
        propertyModal.classList.remove('flex');
    }

    function openPropertyAssignments() {
        var payload = { href: '#', people: [] };
        try {
            payload = JSON.parse((document.getElementById('propertyAssignmentsPreviewData') || {}).textContent || '{}');
        } catch (error) {
            payload = { href: '#', people: [] };
        }
        var people = payload.people || [];
        propertyOpen.href = payload.href || '#';
        propertyBody.replaceChildren();
        if (!people.length) {
            var empty = document.createElement('p');
            empty.className = 'py-8 text-center text-sm text-slate-400';
            empty.textContent = 'No property assignments';
            propertyBody.appendChild(empty);
        }
        people.forEach(function (person) {
            var card = document.createElement('article');
            card.className = 'rounded-xl border border-slate-200';
            var head = document.createElement('div');
            head.className = 'flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3';
            var who = document.createElement('div');
            who.className = 'min-w-0';
            var name = document.createElement('p');
            name.className = 'truncate text-sm font-semibold text-slate-950';
            name.textContent = person.name || 'Person';
            who.appendChild(name);
            if (person.meta) {
                var meta = document.createElement('p');
                meta.className = 'mt-0.5 truncate text-xs text-slate-500';
                meta.textContent = person.meta;
                who.appendChild(meta);
            }
            head.appendChild(who);
            var count = document.createElement('span');
            count.className = 'shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-600';
            var totalItems = person.count || (person.items || []).length;
            count.textContent = totalItems === 1 ? '1 equipment' : totalItems + ' equipment';
            head.appendChild(count);
            card.appendChild(head);
            var list = document.createElement('div');
            list.className = 'divide-y divide-slate-100 px-4';
            (person.items || []).forEach(function (item) {
                var row = document.createElement('div');
                row.className = 'py-3';
                var title = document.createElement('p');
                title.className = 'truncate text-sm font-semibold text-slate-900';
                title.textContent = item.name || 'Equipment';
                row.appendChild(title);
                if (item.set) {
                    if (item.identity) {
                        var setName = document.createElement('p');
                        setName.className = 'mt-0.5 text-xs text-slate-500';
                        setName.textContent = item.identity;
                        row.appendChild(setName);
                    }
                    var members = item.members || [];
                    if (members.length) {
                        var tree = document.createElement('div');
                        tree.className = 'mt-2 ml-1 border-l border-slate-300';
                        members.forEach(function (member, index) {
                            var branch = document.createElement('div');
                            branch.className = 'relative pl-4' + (index === members.length - 1 ? '' : ' pb-2');
                            var elbow = document.createElement('span');
                            elbow.className = 'absolute left-0 top-[0.65rem] h-px w-3 bg-slate-300';
                            elbow.setAttribute('aria-hidden', 'true');
                            branch.appendChild(elbow);
                            if (index === members.length - 1) {
                                var cap = document.createElement('span');
                                cap.className = 'absolute -left-px top-[0.65rem] bottom-0 w-px bg-white';
                                cap.setAttribute('aria-hidden', 'true');
                                branch.appendChild(cap);
                            }
                            var memberName = document.createElement('p');
                            memberName.className = 'truncate text-sm text-slate-800';
                            memberName.textContent = member.name || 'Equipment';
                            branch.appendChild(memberName);
                            var memberDetail = document.createElement('p');
                            memberDetail.className = 'mt-0.5 text-xs text-slate-500';
                            memberDetail.textContent = [member.identity, member.room].filter(Boolean).join(' · ');
                            branch.appendChild(memberDetail);
                            tree.appendChild(branch);
                        });
                        row.appendChild(tree);
                    }
                } else {
                    var detail = document.createElement('p');
                    detail.className = 'mt-0.5 text-xs text-slate-500';
                    detail.textContent = [item.identity, item.room].filter(Boolean).join(' · ');
                    row.appendChild(detail);
                }
                list.appendChild(row);
            });
            card.appendChild(list);
            propertyBody.appendChild(card);
        });
        propertyModal.classList.remove('hidden');
        propertyModal.classList.add('flex');
    }

    var computerSetCatalog = [];
    var computerSetFilterKey = '';
    var computerSetFilterQuery = '';
    var setsFilter = document.getElementById('computerSetsFilter');
    var setsFilterButton = document.getElementById('computerSetsFilterButton');
    var setsFilterLabel = document.getElementById('computerSetsFilterLabel');
    var setsFilterMenu = document.getElementById('computerSetsFilterMenu');
    var setsFilterSearch = document.getElementById('computerSetsFilterSearch');
    var setsFilterOptions = document.getElementById('computerSetsFilterOptions');

    function computerSetKey(set) {
        return set.title || set.name || '';
    }

    function computerSetHaystack(set) {
        var parts = [set.title, set.name, set.room, set.zone, set.owner];
        (set.items || []).forEach(function (item) {
            parts.push(item.role, item.name, item.identity);
        });
        return parts.filter(Boolean).join(' ').toLowerCase();
    }

    function visibleComputerSets() {
        var query = computerSetFilterQuery;
        return computerSetCatalog.filter(function (set) {
            if (computerSetFilterKey && computerSetKey(set) !== computerSetFilterKey) return false;
            if (!query) return true;
            return computerSetHaystack(set).indexOf(query) !== -1;
        });
    }

    function closeComputerSetFilter() {
        if (!setsFilterMenu) return;
        setsFilterMenu.classList.add('hidden');
        if (setsFilterButton) setsFilterButton.setAttribute('aria-expanded', 'false');
    }

    function syncComputerSetFilterLabel() {
        if (!setsFilterLabel) return;
        if (!computerSetFilterKey) {
            setsFilterLabel.textContent = 'All computer sets';
            return;
        }
        var match = computerSetCatalog.find(function (set) {
            return computerSetKey(set) === computerSetFilterKey;
        });
        setsFilterLabel.textContent = match ? (match.title || 'Computer set') : 'All computer sets';
    }

    function renderComputerSetFilterOptions() {
        if (!setsFilterOptions) return;
        setsFilterOptions.replaceChildren();
        var query = computerSetFilterQuery;
        var choices = [{ key: '', title: 'All computer sets', hint: '' }].concat(computerSetCatalog.map(function (set) {
            return {
                key: computerSetKey(set),
                title: set.title || 'Computer set',
                hint: [set.name && set.name !== set.title ? set.name : '', set.room, set.owner ? 'Held by ' + set.owner : ''].filter(Boolean).join(' · ')
            };
        })).filter(function (choice) {
            if (!query || choice.key === '') return true;
            var set = computerSetCatalog.find(function (item) { return computerSetKey(item) === choice.key; });
            return set ? computerSetHaystack(set).indexOf(query) !== -1 : false;
        });
        if (choices.length === 1 && query) {
            var none = document.createElement('p');
            none.className = 'px-3 py-3 text-sm text-slate-400';
            none.textContent = 'No computer sets match';
            setsFilterOptions.appendChild(none);
        }
        choices.forEach(function (choice) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'flex w-full flex-col items-start px-3 py-2 text-left hover:bg-slate-50' + (choice.key === computerSetFilterKey ? ' bg-slate-50' : '');
            var title = document.createElement('span');
            title.className = 'text-sm font-medium text-slate-900';
            title.textContent = choice.title;
            option.appendChild(title);
            if (choice.hint) {
                var hint = document.createElement('span');
                hint.className = 'text-xs text-slate-500';
                hint.textContent = choice.hint;
                option.appendChild(hint);
            }
            option.addEventListener('click', function () {
                computerSetFilterKey = choice.key;
                syncComputerSetFilterLabel();
                closeComputerSetFilter();
                renderComputerSetCards();
            });
            setsFilterOptions.appendChild(option);
        });
    }

    function renderComputerSetCards() {
        var sets = visibleComputerSets();
        setsBody.replaceChildren();
        if (!computerSetCatalog.length) {
            var empty = document.createElement('p');
            empty.className = 'py-8 text-center text-sm text-slate-400';
            empty.textContent = 'No active computer sets';
            setsBody.appendChild(empty);
            return;
        }
        if (!sets.length) {
            var missed = document.createElement('p');
            missed.className = 'py-8 text-center text-sm text-slate-400';
            missed.textContent = 'No computer sets match this filter';
            setsBody.appendChild(missed);
            return;
        }
        sets.forEach(function (set) {
            var items = set.items || [];
            var card = document.createElement('section');
            card.className = 'rounded-xl border border-gray-200 bg-white p-5';
            var head = document.createElement('div');
            head.className = 'flex flex-wrap items-start justify-between gap-3';
            var titles = document.createElement('div');
            var kicker = document.createElement('p');
            kicker.className = 'text-xs font-medium uppercase tracking-wide text-slate-400';
            kicker.textContent = 'Computer set';
            titles.appendChild(kicker);
            var title = document.createElement('p');
            title.className = 'mt-1 text-lg font-semibold text-slate-950';
            title.textContent = set.title || 'Computer set';
            titles.appendChild(title);
            if (set.name && set.name !== set.title) {
                var hint = document.createElement('p');
                hint.className = 'mt-1 text-sm text-slate-500';
                hint.textContent = set.name;
                titles.appendChild(hint);
            }
            if (set.owner) {
                var owner = document.createElement('p');
                owner.className = 'mt-1 text-sm text-slate-700';
                owner.textContent = 'Held by · ' + set.owner;
                titles.appendChild(owner);
            }
            head.appendChild(titles);
            var side = document.createElement('div');
            side.className = 'text-right text-sm text-slate-500';
            [set.room, set.zone, items.length === 1 ? '1 equipment' : items.length + ' equipment'].filter(Boolean).forEach(function (line) {
                var meta = document.createElement('p');
                meta.textContent = line;
                side.appendChild(meta);
            });
            head.appendChild(side);
            card.appendChild(head);
            var rail = document.createElement('div');
            rail.className = 'mt-6 flex min-w-[640px] items-start overflow-x-auto';
            items.forEach(function (item, index) {
                var step = document.createElement('div');
                step.className = 'flex min-w-[7.5rem] flex-1 flex-col items-center';
                var line = document.createElement('div');
                line.className = 'flex w-full items-center';
                var left = document.createElement('div');
                left.className = 'h-0.5 flex-1 ' + (index === 0 ? 'bg-transparent' : 'bg-[#0025cc]');
                var right = document.createElement('div');
                right.className = 'h-0.5 flex-1 ' + (index === items.length - 1 ? 'bg-transparent' : 'bg-[#0025cc]');
                var shot = document.createElement(item.image ? 'button' : 'div');
                if (item.image) shot.type = 'button';
                shot.className = 'flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 text-slate-400';
                if (item.image) {
                    var photo = document.createElement('img');
                    photo.src = item.image;
                    photo.alt = item.name || item.role || 'Equipment';
                    photo.className = 'h-full w-full object-cover';
                    shot.appendChild(photo);
                    shot.addEventListener('click', function () {
                        openEquipmentZoom(item.image, item.name || item.role || 'Equipment');
                    });
                } else {
                    var iconWrap = document.createElement('span');
                    iconWrap.className = 'inline-flex h-7 w-7 items-center justify-center [&_svg]:h-full [&_svg]:w-full';
                    var iconLabel = [item.role, item.name].filter(Boolean).join(' ');
                    iconWrap.innerHTML = window.PrismEquipmentIcons
                        ? window.PrismEquipmentIcons.svg(iconLabel)
                        : '';
                    shot.appendChild(iconWrap);
                }
                line.appendChild(left);
                line.appendChild(shot);
                line.appendChild(right);
                step.appendChild(line);
                var role = document.createElement('p');
                role.className = 'mt-2 text-center text-sm font-semibold text-slate-700';
                role.textContent = item.role || 'Equipment';
                step.appendChild(role);
                if (item.name) {
                    var name = document.createElement('p');
                    name.className = 'mt-1 max-w-[9rem] text-center text-xs font-semibold text-slate-900';
                    name.textContent = item.name;
                    step.appendChild(name);
                }
                if (item.identity) {
                    var identity = document.createElement('p');
                    identity.className = 'mt-0.5 max-w-[9rem] text-center text-[11px] font-medium text-slate-500';
                    identity.textContent = item.identity;
                    step.appendChild(identity);
                }
                rail.appendChild(step);
            });
            if (items.length) {
                var scroller = document.createElement('div');
                scroller.className = 'overflow-x-auto';
                scroller.appendChild(rail);
                card.appendChild(scroller);
            }
            setsBody.appendChild(card);
        });
    }

    function closeComputerSets() {
        if (!setsModal) return;
        closeComputerSetFilter();
        setsModal.classList.add('hidden');
        setsModal.classList.remove('flex');
    }

    function openComputerSets() {
        var payload = { href: '#', sets: [] };
        try {
            payload = JSON.parse((document.getElementById('computerSetsPreviewData') || {}).textContent || '{}');
        } catch (error) {
            payload = { href: '#', sets: [] };
        }
        computerSetCatalog = payload.sets || [];
        computerSetFilterKey = '';
        computerSetFilterQuery = '';
        if (setsFilterSearch) setsFilterSearch.value = '';
        setsHeading.textContent = 'Computer sets';
        setsOpen.href = payload.href || '#';
        syncComputerSetFilterLabel();
        renderComputerSetFilterOptions();
        renderComputerSetCards();
        closeComputerSetFilter();
        setsModal.classList.remove('hidden');
        setsModal.classList.add('flex');
        if (window.lucide) window.lucide.createIcons();
    }

    if (setsFilterButton) {
        setsFilterButton.addEventListener('click', function () {
            var open = setsFilterMenu.classList.contains('hidden');
            setsFilterMenu.classList.toggle('hidden', !open);
            setsFilterButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && setsFilterSearch) setsFilterSearch.focus();
        });
    }

    if (setsFilterSearch) {
        setsFilterSearch.addEventListener('input', function () {
            computerSetFilterQuery = setsFilterSearch.value.trim().toLowerCase();
            renderComputerSetFilterOptions();
            renderComputerSetCards();
        });
        setsFilterSearch.addEventListener('keydown', function (event) {
            event.stopPropagation();
        });
    }

    document.addEventListener('click', function (event) {
        if (!setsFilter || setsFilter.contains(event.target)) return;
        closeComputerSetFilter();
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-photo-zoom-close]') || event.target === photoZoom) {
            closeEquipmentZoom();
            return;
        }
        if (event.target.closest('[data-replacement-log-open]')) {
            event.preventDefault();
            openReplacementLog();
            return;
        }
        if (event.target.closest('[data-replacement-log-close]') || event.target === replacementLogModal) {
            closeReplacementLog();
            return;
        }
        if (event.target.closest('[data-property-assignments-open]')) {
            event.preventDefault();
            openPropertyAssignments();
            return;
        }
        if (event.target.closest('[data-property-assignments-close]') || event.target === propertyModal) {
            closePropertyAssignments();
            return;
        }
        if (event.target.closest('[data-computer-sets-open]')) {
            event.preventDefault();
            openComputerSets();
            return;
        }
        if (event.target.closest('[data-computer-sets-close]') || event.target === setsModal) {
            closeComputerSets();
        }
        var opener = event.target.closest('[data-borrow-preview]');
        if (opener) {
            event.preventDefault();
            try {
                openBorrowModal(JSON.parse(opener.getAttribute('data-borrow-preview') || '{}'));
            } catch (error) {
                openBorrowModal(null);
            }
            return;
        }
        if (event.target.closest('[data-borrow-close]') || event.target === borrowModal) {
            closeBorrowModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (photoZoom && !photoZoom.classList.contains('hidden')) {
            closeEquipmentZoom();
            return;
        }
        if (replacementLogModal && !replacementLogModal.classList.contains('hidden')) {
            closeReplacementLog();
            return;
        }
        if (prModal && !prModal.classList.contains('hidden')) {
            closePrPreview();
            return;
        }
        if (propertyModal && !propertyModal.classList.contains('hidden')) {
            closePropertyAssignments();
            return;
        }
        if (setsModal && !setsModal.classList.contains('hidden')) {
            closeComputerSets();
            return;
        }
        closeBorrowModal();
    });

    var prModal = document.getElementById('adminPrPreview');
    var prFrame = document.getElementById('adminPrPreviewFrame');
    var prStage = document.getElementById('adminPrPreviewStage');
    var prSkeleton = document.getElementById('adminPrPreviewSkeleton');
    var prTitle = document.getElementById('adminPrPreviewTitle');
    var prOpen = document.getElementById('adminPrPreviewOpen');
    var prCache = Object.create(null);

    function fitPrFrame() {
        var doc;
        try { doc = prFrame.contentDocument; } catch (error) { return; }
        if (!doc || !doc.body) return;
        prFrame.style.height = '40px';
        var total = 0;
        var children = doc.body.children || [];
        for (var i = 0; i < children.length; i++) total += children[i].offsetHeight;
        if (total < 40) total = doc.body.scrollHeight || 0;
        if (total < 40) return;
        prFrame.style.height = Math.ceil(total) + 'px';
        prSkeleton.classList.add('hidden');
    }

    function closePrPreview() {
        if (!prModal) return;
        prModal.classList.add('hidden');
        prModal.classList.remove('flex');
        prSkeleton.classList.remove('hidden');
        prFrame.srcdoc = '';
        prFrame.src = 'about:blank';
        prFrame.style.height = '';
            document.body.style.overflow = '';
        }

    function openPrPreview(trigger) {
        var previewUrl = trigger.getAttribute('data-preview-url') || '';
        var continueUrl = trigger.getAttribute('data-continue-url') || '';
            if (!previewUrl) {
                if (continueUrl) window.location = continueUrl;
                return;
            }
        prTitle.textContent = trigger.getAttribute('data-preview-title') || 'Request';
        prOpen.href = continueUrl || '#';
        prOpen.textContent = trigger.getAttribute('data-continue-label') || 'Open';
        prSkeleton.classList.remove('hidden');
        prFrame.style.height = '0px';
        var cached = prCache[previewUrl] || '';
        if (cached) {
            prFrame.removeAttribute('src');
            prFrame.srcdoc = cached;
            requestAnimationFrame(fitPrFrame);
        } else {
            prFrame.srcdoc = '';
            prFrame.src = previewUrl;
        }
        prModal.classList.remove('hidden');
        prModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

    if (prFrame) {
        prFrame.addEventListener('load', function () {
            var src = prFrame.getAttribute('src') || '';
            if ((!src || src === 'about:blank') && !prFrame.srcdoc) return;
            fitPrFrame();
            setTimeout(fitPrFrame, 80);
        });
    }

        document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-dashboard-preview]');
        if (trigger) {
            event.preventDefault();
            openPrPreview(trigger);
            return;
        }
        if (!prModal) return;
        if (event.target.closest('[data-pr-preview-close]') || event.target === prModal) closePrPreview();
    });

    document.querySelectorAll('[data-preview-kind="pipeline"][data-preview-url]').forEach(function (el) {
        var url = el.getAttribute('data-preview-url');
        if (!url || Object.prototype.hasOwnProperty.call(prCache, url)) return;
        prCache[url] = '';
        fetch(url, { credentials: 'same-origin' })
            .then(function (response) { return response.ok ? response.text() : ''; })
            .then(function (html) { prCache[url] = html || ''; })
            .catch(function () { delete prCache[url]; });
    });

    (function () {
        var dataEl = document.getElementById('maintenanceCalendarData');
        var daysEl = document.getElementById('maintenanceCalendarDays');
        var listEl = document.getElementById('maintenanceCalendarList');
        var dateEl = document.getElementById('maintenanceCalendarDate');
        if (!dataEl || !daysEl || !listEl || !dateEl) return;
        var monthEl = document.getElementById('maintenanceCalendarMonth');
        var yearEl = document.getElementById('maintenanceCalendarYear');
        if (!monthEl || !yearEl) return;
        var payload = { today: '', years: [], events: [] };
        try { payload = JSON.parse(dataEl.textContent || '{}'); } catch (error) { payload = { today: '', years: [], events: [] }; }
        var today = payload.today || '';
        var parts = today.split('-').map(Number);
        if (parts.length < 3) return;
        var year = parts[0];
        var month = parts[1] - 1;
        var selected = today;
        var events = payload.events || [];
        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        var tones = {
            equipment: 'bg-sky-50 text-sky-700 ring-1 ring-sky-100',
            room: 'bg-violet-50 text-violet-700 ring-1 ring-violet-100',
            semestral: 'bg-amber-50 text-amber-800 ring-1 ring-amber-100'
        };
        var dotTones = {
            equipment: 'bg-sky-500',
            room: 'bg-violet-500',
            semestral: 'bg-amber-500'
        };
        var years = (payload.years || []).map(Number).filter(Boolean);
        if (years.indexOf(year) === -1) years.push(year);
        years.sort(function (a, b) { return a - b; });
        years.forEach(function (value) {
            var option = document.createElement('option');
            option.value = String(value);
            option.textContent = String(value);
            if (value === year) option.selected = true;
            yearEl.appendChild(option);
        });
        monthNames.forEach(function (name, index) {
            var option = document.createElement('option');
            option.value = String(index);
            option.textContent = name;
            if (index === month) option.selected = true;
            monthEl.appendChild(option);
        });
        monthEl.addEventListener('change', function () {
            month = Number(monthEl.value);
            selected = year + '-' + String(month + 1).padStart(2, '0') + '-01';
            if (selected.slice(0, 7) === today.slice(0, 7)) selected = today;
            paint();
        });
        yearEl.addEventListener('change', function () {
            year = Number(yearEl.value);
            var maxDay = new Date(year, month + 1, 0).getDate();
            var day = Math.min(Number(selected.slice(8)) || 1, maxDay);
            selected = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            if (selected.slice(0, 7) === today.slice(0, 7)) selected = today;
            paint();
        });
        var todayButton = document.getElementById('maintenanceCalendarToday');
        if (todayButton) {
            todayButton.addEventListener('click', function () {
                year = parts[0];
                month = parts[1] - 1;
                selected = today;
                monthEl.value = String(month);
                yearEl.value = String(year);
                paint();
            });
        }

        function iso(day) {
            return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }

        function onDate(date) {
            return events.filter(function (event) { return event.date === date; });
        }

        function paint() {
            daysEl.replaceChildren();
            var pad = new Date(year, month, 1).getDay();
            var count = new Date(year, month + 1, 0).getDate();
            for (var blank = 0; blank < pad; blank++) daysEl.appendChild(document.createElement('span'));
            for (var day = 1; day <= count; day++) {
                (function (dayNumber) {
                    var date = iso(dayNumber);
                    var button = document.createElement('button');
                    button.type = 'button';
                    var chosen = date === selected;
                    var isToday = date === today;
                    button.className = 'relative flex h-9 items-center justify-center rounded-lg text-xs font-semibold ' + (chosen ? 'bg-[#0025cc] text-white' : (isToday ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-50'));
                    button.textContent = String(dayNumber);
                    var dayRows = onDate(date);
                    if (dayRows.length) {
                        var dots = document.createElement('span');
                        dots.className = 'absolute bottom-1 left-1/2 flex -translate-x-1/2 items-center gap-0.5';
                        var seen = {};
                        dayRows.forEach(function (row) {
                            var tone = row.tone || 'equipment';
                            if (seen[tone]) return;
                            seen[tone] = true;
                            var dot = document.createElement('span');
                            dot.className = 'h-1 w-1 rounded-full ' + (chosen ? 'bg-white' : (dotTones[tone] || 'bg-red-500'));
                            dots.appendChild(dot);
                        });
                        button.appendChild(dots);
                    }
                    button.addEventListener('click', function () {
                        selected = date;
                        paint();
                    });
                    daysEl.appendChild(button);
                })(day);
            }

            var when = new Date(year, month, Number(selected.slice(8)));
            dateEl.textContent = when.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
            listEl.replaceChildren();
            var rows = onDate(selected);
            if (!rows.length) {
                var empty = document.createElement('p');
                empty.className = 'py-8 text-center text-xs font-medium text-slate-400';
                empty.textContent = 'Nothing scheduled';
                listEl.appendChild(empty);
                return;
            }
            rows.forEach(function (row) {
                var link = document.createElement('button');
                link.type = 'button';
                link.className = 'flex h-[4.5rem] w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50';
                var text = document.createElement('span');
                text.className = 'min-w-0';
                var title = document.createElement('span');
                title.className = 'block truncate text-sm font-semibold text-slate-900';
                title.textContent = row.title || 'Maintenance';
                text.appendChild(title);
                if (row.meta) {
                    var meta = document.createElement('span');
                    meta.className = 'mt-0.5 block truncate text-xs text-slate-500';
                    meta.textContent = row.meta;
                    text.appendChild(meta);
                }
                link.appendChild(text);
                if (row.badge) {
                    var badge = document.createElement('span');
                    badge.className = 'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold ' + (tones[row.tone] || tones.equipment);
                    badge.textContent = row.badge;
                    link.appendChild(badge);
                }
                link.addEventListener('click', function () { openCalendarPreview(row); });
                listEl.appendChild(link);
            });
        }

        var preview = document.getElementById('maintenanceCalendarPreview');
        if (preview && preview.parentElement !== document.body) {
            document.body.appendChild(preview);
        }
        function openCalendarPreview(row) {
            if (!preview) return;
            document.getElementById('calendarPreviewDate').textContent = row.date_label || row.date || '';
            document.getElementById('calendarPreviewTitle').textContent = row.title || 'Maintenance';
            var meta = document.getElementById('calendarPreviewMeta');
            meta.textContent = row.meta || '';
            meta.classList.toggle('hidden', !row.meta);
            var identity = document.getElementById('calendarPreviewIdentity');
            identity.textContent = row.identity || '';
            identity.classList.toggle('hidden', !row.identity);
            var badge = document.getElementById('calendarPreviewBadge');
            badge.textContent = row.badge || '';
            badge.className = 'rounded-full px-2.5 py-0.5 text-sm font-semibold ring-1 ' + (tones[row.tone] || tones.equipment);
            var status = document.getElementById('calendarPreviewStatus');
            status.textContent = row.status || '';
            status.className = 'rounded-full px-2.5 py-0.5 text-sm font-semibold ' + (row.status === 'Overdue' ? 'bg-rose-50 text-rose-700' : (row.status === 'Completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'));
            status.classList.toggle('hidden', !row.status);
            var go = document.getElementById('calendarPreviewGo');
            go.href = row.href || '#';
            preview.classList.remove('hidden');
            preview.classList.add('flex');
        }
        function closeCalendarPreview() {
            if (!preview) return;
            preview.classList.add('hidden');
            preview.classList.remove('flex');
        }
        if (preview) {
            preview.addEventListener('click', function (event) {
                if (event.target === preview || event.target.closest('[data-calendar-preview-close]')) closeCalendarPreview();
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeCalendarPreview();
        });

        paint();
    })();
</script>
@endsection
