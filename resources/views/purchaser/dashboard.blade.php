@extends("layouts.purchaser-layout")

@section("page-title", "Dashboard")
@section("page-subtitle", "Your purchasing work, spend, and next steps at a glance")

@section("content")
@php
    use App\Support\PurchaserAttentionSummary as Focus;
    use App\Support\PurchaserDashboard;
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $d = $dashboard;
    $attention = $d['attention'];
    $thisMonth = $d['thisMonth'];
    $allTime = $d['allTime'];

    $firstName = Str::of(trim((string) ($user->user_full_name ?? '')) ?: 'there')->before(' ');
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    $peso = fn ($amount) => '₱'.number_format((float) $amount, 2);
    $toneClasses = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-100',
        'red' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $docIcons = ['ris' => 'package-open', 'atp' => 'file-check-2', 'po' => 'shopping-bag', 'rfc' => 'wallet', 'rr' => 'package-check', 'liq' => 'receipt-text'];
    $docIndex = ['ris' => 'ris.index', 'atp' => 'atp.index', 'po' => 'purchase-orders.index', 'rfc' => 'rfc.index', 'rr' => 'rr.index', 'liq' => 'liq.index'];

    $nextSteps = collect([
        ['count' => $attention['pendingReplacementRequests'], 'label' => 'Replacement requests to review', 'hint' => 'Sent by Maintenance — approve or reject', 'icon' => 'inbox', 'scope' => 'Shared', 'url' => route('purchaser.procurement.replacement-requests', ['focus' => Focus::FOCUS_REPLACEMENT_PENDING])],
        ['count' => $attention['availableUrgentReports'], 'label' => 'Unclaimed urgent reports', 'hint' => 'Nobody has taken these yet', 'icon' => 'siren', 'scope' => 'Shared', 'url' => route('purchaser.reports.urgent', ['focus' => Focus::FOCUS_URGENT_UNCLAIMED])],
        ['count' => $d['handovers']['total'], 'label' => 'Drafts passed to you', 'hint' => 'A co-worker handed over a draft', 'icon' => 'hand-helping', 'scope' => 'Yours', 'url' => route($d['handovers']['atp'] > 0 ? 'purchaser.atp.index' : ($d['handovers']['po'] > 0 ? 'purchaser.purchase-orders.index' : 'purchaser.ris.index'))],
        ['count' => $attention['risReadyForAtp'], 'label' => 'Approved RIS ready for ATP', 'hint' => 'Create the Authority to Purchase', 'icon' => 'file-plus-2', 'scope' => 'Yours', 'url' => route('purchaser.ris.index', ['focus' => Focus::FOCUS_RIS_READY_FOR_ATP])],
        ['count' => $attention['atpReadyForRfc'], 'label' => 'Approved ATP ready for funding', 'hint' => 'Request for Check or Cash Advance', 'icon' => 'wallet', 'scope' => 'Yours', 'url' => route('purchaser.atp.index', ['focus' => Focus::FOCUS_ATP_READY_FOR_RFC])],
        ['count' => $attention['rfcReadyForRr'], 'label' => 'Funds released — create Receiving Report', 'hint' => 'Collect funds, buy, then record delivery', 'icon' => 'banknote', 'scope' => 'Yours', 'url' => route('purchaser.rfc.index', ['focus' => Focus::FOCUS_RFC_READY_FOR_RR])],
        ['count' => $d['openBackOrders'], 'label' => 'Open back orders', 'hint' => 'Short or damaged lines to replace or refund', 'icon' => 'package-x', 'scope' => 'Yours', 'url' => route('purchaser.bo.index')],
        ['count' => $attention['rrReadyForLiq'], 'label' => 'Receiving Reports ready for liquidation', 'hint' => 'Liquidate your Cash Advance', 'icon' => 'receipt-text', 'scope' => 'Yours', 'url' => route('purchaser.rr.index', ['focus' => Focus::FOCUS_RR_READY_FOR_LIQ])],
    ])->sortBy(fn ($step) => $step['count'] > 0 ? 0 : 1)->values();

    $actionTotal = $nextSteps->sum('count') + $d['needsActionCount'];
    $awaitingDelivery = max(0, $allTime['approved'] - $allTime['delivered']);
    $maxMonthly = max(1, collect($d['monthlySpend'])->max('amount'));
    $sixMonthSpend = collect($d['monthlySpend'])->sum('amount');
    $topSpend = max(1, (float) $d['topSuppliers']->max('spend'));
    $monthStart = now()->startOfMonth()->toDateString();
@endphp

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- WELCOME --}}
    {{-- ===================================================== --}}

    @php
        $actionParts = [
            ['label' => 'Returned to you', 'count' => (int) $d['needsActionCount'], 'color' => '#f59e0b'],
            ['label' => 'Your next steps', 'count' => (int) $nextSteps->where('scope', 'Yours')->sum('count'), 'color' => '#0025cc'],
            ['label' => 'Shared queue', 'count' => (int) $nextSteps->where('scope', 'Shared')->sum('count'), 'color' => '#a5b4fc'],
        ];
    @endphp
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-8 p-6 sm:p-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                    <span class="font-medium text-slate-600">Purchaser</span>
                    <span class="text-slate-300">/</span>
                    <span>{{ now()->format('l, F j, Y') }}</span>
                </p>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{{ $greeting }}, {{ $firstName }}</h1>
                <p class="mt-1.5 max-w-xl text-sm leading-6 text-slate-500">
                    @if($actionTotal > 0)
                        You have <span class="font-medium text-slate-900">{{ $actionTotal }} {{ Str::plural('item', $actionTotal) }}</span> waiting on you across the purchasing workflow.
                    @else
                        You're all caught up — nothing is waiting on you right now.
                    @endif
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    @foreach([
                        ['RIS', 'package-open', route('purchaser.ris.index')],
                        ['Authority to Purchase', 'file-check-2', route('purchaser.atp.index')],
                        ['Request Fund', 'wallet', route('purchaser.rfc.index', ['fund' => 'request_for_check'])],
                        ['Receiving Report', 'package-check', route('purchaser.rr.index')],
                    ] as $i => [$label, $icon, $url])
                        <a href="{{ $url }}" class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-[13px] font-medium transition {{ $i === 0 ? 'bg-[#0025cc] text-white hover:bg-[#001ea8]' : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}">
                            <i data-lucide="{{ $icon }}" class="h-4 w-4 {{ $i === 0 ? '' : 'text-slate-400' }}"></i>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="w-full shrink-0 lg:w-72 lg:border-l lg:border-slate-100 lg:pl-8">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">To act on</p>
                    <a href="#next-steps" class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-900">
                        See list <i data-lucide="arrow-down" class="h-3.5 w-3.5"></i>
                    </a>
                </div>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="text-4xl font-semibold tabular-nums tracking-tight {{ $actionTotal > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ $actionTotal }}</span>
                    <span class="text-sm text-slate-500">{{ Str::plural('item', $actionTotal) }}</span>
                </p>

                <div class="mt-4 flex h-1.5 gap-1">
                    @foreach($actionParts as $part)
                        @if($part['count'] > 0)
                            <div class="h-full rounded-full" style="flex: {{ $part['count'] }} 1 0%; background: {{ $part['color'] }};"></div>
                        @endif
                    @endforeach
                    @if($actionTotal === 0)
                        <div class="h-full flex-1 rounded-full bg-slate-100"></div>
                    @endif
                </div>

                <dl class="mt-4 space-y-2">
                    @foreach($actionParts as $part)
                        <div class="flex items-center justify-between text-xs">
                            <dt class="flex items-center gap-2 text-slate-500">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $part['color'] }};"></span>
                                {{ $part['label'] }}
                            </dt>
                            <dd class="font-medium tabular-nums {{ $part['count'] > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ $part['count'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- KPIs --}}
    {{-- ===================================================== --}}

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $kpis = [
                [
                    'label' => 'Approved this month',
                    'value' => $peso($thisMonth['approved_spend']),
                    'icon' => 'wallet',
                    'url' => route('purchaser.history.index', ['from' => $monthStart]),
                    'foot' => $d['spendChange'] === null
                        ? 'No approved spend last month'
                        : ($d['spendChange'] >= 0 ? '▲ ' : '▼ ').(abs($d['spendChange']) > 999
                            ? $peso(abs($thisMonth['approved_spend'] - $d['lastMonthSpend'])).' more than last month'
                            : abs($d['spendChange']).'% vs last month'),
                    'footTone' => $d['spendChange'] !== null && $d['spendChange'] < 0 ? 'text-amber-700' : 'text-blue-700',
                ],
                [
                    'label' => 'Purchases this month',
                    'value' => number_format($thisMonth['purchases']),
                    'icon' => 'shopping-cart',
                    'url' => route('purchaser.history.index', ['from' => $monthStart]),
                    'foot' => $thisMonth['approved'].' approved · '.$thisMonth['in_review'].' in review',
                    'footTone' => 'text-slate-500',
                ],
                [
                    'label' => 'Awaiting review',
                    'value' => number_format($d['awaitingReview']),
                    'icon' => 'hourglass',
                    'url' => route('purchaser.history.index', ['tab' => 'documents']),
                    'foot' => 'With Admin, Accounting or Receiving',
                    'footTone' => 'text-slate-500',
                ],
                [
                    'label' => 'Awaiting delivery',
                    'value' => number_format($awaitingDelivery),
                    'icon' => 'truck',
                    'url' => route('purchaser.rr.index'),
                    'foot' => $allTime['delivered'].' of '.$allTime['approved'].' approved purchases received',
                    'footTone' => 'text-slate-500',
                ],
            ];
        @endphp

        @foreach($kpis as $kpi)
            <a href="{{ $kpi['url'] }}" class="pur-stat-card group flex flex-col">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                        <i data-lucide="{{ $kpi['icon'] }}" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                <p class="mt-1 flex items-center justify-between gap-2 text-xs font-medium {{ $kpi['footTone'] }}">
                    <span>{{ $kpi['foot'] }}</span>
                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500"></i>
                </p>
            </a>
        @endforeach
    </section>

    {{-- ===================================================== --}}
    {{-- NEXT STEPS + RETURNED --}}
    {{-- ===================================================== --}}

    <section class="grid gap-6 xl:grid-cols-3">
        <div id="next-steps" class="pur-card scroll-mt-24 xl:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-gray-950">Next steps</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Work that can move forward right now.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $nextSteps->sum('count') }} open</span>
            </div>

            <div class="grid grid-cols-1 divide-y divide-gray-100 md:grid-cols-2 md:divide-y-0">
                @foreach($nextSteps as $i => $step)
                    @php $active = $step['count'] > 0; @endphp
                    <a
                        href="{{ $step['url'] }}"
                        class="group flex items-center gap-3.5 px-5 py-3.5 transition hover:bg-gray-50/80 md:border-b md:border-gray-100 {{ $i % 2 === 0 ? 'md:border-r' : '' }} {{ $active ? '' : 'opacity-60' }}"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $active ? 'bg-blue-50 text-blue-700 ring-blue-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }}">
                            <i data-lucide="{{ $step['icon'] }}" class="h-[18px] w-[18px]"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold leading-snug text-gray-900">
                                {{ $step['label'] }}
                                @if($step['scope'] === 'Shared')
                                    <span class="ml-1 inline-block rounded bg-amber-50 px-1.5 py-px align-middle text-[10px] font-semibold uppercase tracking-wide text-amber-700">Shared</span>
                                @endif
                            </span>
                            <span class="mt-0.5 block text-xs leading-snug text-gray-500">{{ $step['hint'] }}</span>
                        </span>
                        @if($active)
                            <span class="flex h-7 min-w-[28px] items-center justify-center rounded-full bg-[#0025cc] px-2 text-xs font-bold text-white">{{ $step['count'] }}</span>
                        @else
                            <i data-lucide="check" class="h-4 w-4 text-slate-400"></i>
                        @endif
                    </a>
                @endforeach
            </div>

            @php $rc = $d['replacementCounts']; @endphp
            <a
                href="{{ route('purchaser.procurement.replacement-requests') }}"
                class="flex flex-wrap items-center justify-between gap-3 bg-gray-50/70 px-5 py-3 text-xs text-gray-500 transition hover:bg-gray-100/70"
            >
                <span class="font-semibold text-gray-700">Replacement requests from Maintenance</span>
                <span class="flex items-center gap-4">
                    <span><b class="text-gray-900">{{ $rc['pending'] }}</b> pending</span>
                    <span><b class="text-gray-900">{{ $rc['approved'] }}</b> approved</span>
                    <span><b class="text-gray-900">{{ $rc['completed'] }}</b> completed</span>
                    <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                </span>
            </a>
        </div>

        @php
            $boReports = $d['backOrderReports'];
            $boRrIds = $boReports->map(fn ($row) => (int) $row->doc->id)->all();
            $returnedDocs = $d['needsAction']->reject(fn ($doc) => $doc->type === 'rr' && in_array((int) $doc->id, $boRrIds, true))->values();
            $panelCount = $returnedDocs->count() + $boReports->count();
        @endphp
        <div class="pur-card flex flex-col">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-gray-950">Returned to you</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Fix, resubmit, or follow up back orders.</p>
                </div>
                @if($panelCount > 0)
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">{{ $panelCount }}</span>
                @endif
            </div>

            @if($panelCount === 0)
                <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                        <i data-lucide="party-popper" class="h-5 w-5"></i>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-gray-800">Nothing returned</p>
                    <p class="mt-1 text-xs text-gray-500">Documents sent back for revision and receiving reports with back orders will show up here.</p>
                </div>
            @else
                <div class="flex flex-1 flex-col">
                    @if($returnedDocs->isNotEmpty())
                        <p class="px-5 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">For revision · {{ $returnedDocs->count() }}</p>
                        @foreach($returnedDocs as $doc)
                            <a href="{{ route('purchaser.'.$doc->route, $doc->params) }}" class="group flex items-center gap-3 px-5 py-2.5 transition hover:bg-gray-50/80">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700">
                                    <i data-lucide="{{ $docIcons[$doc->type] ?? 'file' }}" class="h-4 w-4"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $doc->number }}</span>
                                    <span class="block truncate text-xs text-gray-500">{{ $doc->type_label }} · {{ $doc->status }}</span>
                                </span>
                                <i data-lucide="chevron-right" class="h-4 w-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-gray-500"></i>
                            </a>
                        @endforeach
                    @endif

                    @if($boReports->isNotEmpty())
                        <div class="flex items-center justify-between px-5 pb-2 pt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">RR with back orders · {{ $boReports->count() }}</p>
                            <a href="{{ route('purchaser.bo.index') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">All</a>
                        </div>
                        <div class="pb-2">
                            @foreach($boReports->take(4) as $row)
                                @php
                                    $boItems = implode(', ', array_slice($row->articles, 0, 2)).(count($row->articles) > 2 ? ' +'.(count($row->articles) - 2) : '');
                                @endphp
                                <a href="{{ route('purchaser.bo.index', ['rr' => $row->doc->id]) }}" class="group flex items-center gap-3 px-5 py-2.5 transition hover:bg-gray-50/80">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700">
                                        <i data-lucide="{{ $docIcons['rr'] }}" class="h-4 w-4"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-gray-900">{{ $row->doc->number }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ $row->doc->type_label }} · {{ $row->doc->status }}</span>
                                        <span class="mt-0.5 block truncate text-xs text-gray-500">
                                            @if($row->missing > 0)<span class="font-medium text-amber-700">{{ $row->missing }} missing</span>@endif
                                            @if($row->missing > 0 && $row->damaged > 0)<span class="text-gray-300"> · </span>@endif
                                            @if($row->damaged > 0)<span class="font-medium text-amber-700">{{ $row->damaged }} damaged</span>@endif
                                            <span class="text-gray-300"> · </span>{{ $peso($row->amount) }}@if($boItems !== '')<span class="text-gray-300"> · </span>{{ $boItems }}@endif
                                        </span>
                                    </span>
                                    <i data-lucide="chevron-right" class="h-4 w-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-gray-500"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- WORKFLOW PIPELINE --}}
    {{-- ===================================================== --}}

    <section class="pur-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold tracking-tight text-gray-950">My workflow</h2>
                <p class="mt-0.5 text-sm text-gray-500">Your active documents at each step, from request to liquidation.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[11px] font-medium text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Draft</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#0025cc]"></span>In review</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-400"></span>Needs action</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-200"></span>Completed</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-gray-100 sm:grid-cols-3 xl:grid-cols-6">
            @foreach($d['pipeline'] as $type => $step)
                @php
                    $inProgress = $step['stages']['draft'] + $step['stages']['review'] + $step['stages']['attention'];
                    $segments = [
                        'draft' => 'bg-slate-300',
                        'review' => 'bg-[#0025cc]',
                        'attention' => 'bg-amber-400',
                        'done' => 'bg-blue-200',
                    ];
                @endphp
                <a href="{{ route('purchaser.'.$docIndex[$type]) }}" class="group relative flex flex-col bg-white px-5 py-4 transition hover:bg-gray-50/80">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500 transition group-hover:border-blue-200 group-hover:text-[#0025cc]">
                            <i data-lucide="{{ $docIcons[$type] }}" class="h-4 w-4"></i>
                        </span>
                        <span class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Step {{ $loop->iteration }}</span>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-gray-900">{{ $step['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-gray-950">
                        {{ $inProgress }}
                        <span class="text-xs font-medium text-gray-400">in progress</span>
                    </p>

                    <div class="mt-3 flex h-1.5 overflow-hidden rounded-full bg-gray-100">
                        @if($step['total'] > 0)
                            @foreach($segments as $stage => $color)
                                @if($step['stages'][$stage] > 0)
                                    <span class="{{ $color }}" style="width: {{ ($step['stages'][$stage] / $step['total']) * 100 }}%" title="{{ PurchaserDashboard::STAGES[$stage] }}: {{ $step['stages'][$stage] }}"></span>
                                @endif
                            @endforeach
                        @endif
                    </div>
                    <p class="mt-2 text-[11px] text-gray-500">
                        @if($step['total'] === 0)
                            No documents yet
                        @else
                            {{ $step['stages']['review'] }} in review
                            @if($step['stages']['attention'] > 0)
                                · <span class="font-semibold text-amber-700">{{ $step['stages']['attention'] }} need action</span>
                            @endif
                            · {{ $step['stages']['done'] }} done
                        @endif
                    </p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===================================================== --}}
    {{-- RECENT ACTIVITY + SPEND --}}
    {{-- ===================================================== --}}

    <section class="grid gap-6 xl:grid-cols-3">
        <div class="pur-card xl:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-gray-950">Recent activity</h2>
                    <p class="mt-0.5 text-sm text-gray-500">Your latest documents across the workflow.</p>
                </div>
                <a href="{{ route('purchaser.history.index', ['tab' => 'documents']) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0025cc] hover:underline">
                    View all <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            @forelse($d['recent'] as $doc)
                <a href="{{ route('purchaser.'.$doc->route, $doc->params) }}" class="group flex items-center gap-4 border-b border-gray-100 px-5 py-3.5 transition last:border-b-0 hover:bg-gray-50/80">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-500">
                        <i data-lucide="{{ $docIcons[$doc->type] ?? 'file' }}" class="h-[18px] w-[18px]"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ $doc->number }}</span>
                            <span class="text-xs text-gray-400">{{ $doc->type_label }}</span>
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $doc->title ?: '—' }}</span>
                    </span>
                    <span class="hidden shrink-0 text-right sm:block">
                        <span class="block text-sm font-semibold tabular-nums text-gray-900">{{ $doc->amount !== null ? $peso($doc->amount) : '—' }}</span>
                        <span class="block text-[11px] text-gray-400">{{ $doc->date ? Carbon::parse($doc->date)->diffForHumans() : '' }}</span>
                    </span>
                    <span class="inline-flex w-[132px] shrink-0 justify-end">
                        <span class="inline-block max-w-full truncate whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $toneClasses[$doc->tone] ?? $toneClasses['slate'] }}">{{ $doc->status ?: '—' }}</span>
                    </span>
                </a>
            @empty
                <div class="px-6 py-12 text-center">
                    <i data-lucide="file-plus-2" class="mx-auto h-8 w-8 text-slate-300"></i>
                    <p class="mt-3 text-sm font-semibold text-gray-800">No documents yet</p>
                    <p class="mt-1 text-xs text-gray-500">Start with a RIS — your documents will appear here.</p>
                    <a href="{{ route('purchaser.ris.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-semibold text-white hover:bg-blue-800">
                        <i data-lucide="package-open" class="h-4 w-4"></i> Open RIS
                    </a>
                </div>
            @endforelse
        </div>

        <div class="pur-card flex flex-col">
            <div class="border-b border-gray-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold tracking-tight text-gray-950">Approved spend</h2>
                    <a href="{{ route('purchaser.history.index') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">History</a>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight text-gray-950">{{ $peso($sixMonthSpend) }}</p>
                <p class="text-xs text-gray-500">Last 6 months · {{ $peso($allTime['approved_spend']) }} all time</p>

                <div class="mt-4 flex h-28 items-stretch gap-2">
                    @foreach($d['monthlySpend'] as $month)
                        @php $height = $month['amount'] > 0 ? max(8, round(($month['amount'] / $maxMonthly) * 100)) : 4; @endphp
                        <div class="flex flex-1 flex-col items-center gap-1.5" title="{{ $month['label'] }}: {{ $peso($month['amount']) }}">
                            <div class="relative w-full max-w-[40px] flex-1">
                                <div class="absolute inset-x-0 bottom-0 rounded-md {{ $month['amount'] > 0 ? 'bg-[#0025cc]' : 'bg-slate-100' }}" style="height: {{ $height }}%"></div>
                            </div>
                            <span class="text-[11px] font-medium text-slate-400">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex-1 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Top suppliers</p>
                @forelse($d['topSuppliers'] as $supplier)
                    <div class="mt-3">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate font-medium text-gray-800">{{ $supplier->name }}</span>
                            <span class="shrink-0 font-semibold tabular-nums text-gray-900">{{ $peso($supplier->spend) }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-[#0025cc]" style="width: {{ max(3, round(($supplier->spend / $topSpend) * 100)) }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">{{ $supplier->purchases }} {{ Str::plural('purchase', $supplier->purchases) }} · {{ number_format($supplier->items) }} units</p>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-gray-500">No approved purchases yet.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
