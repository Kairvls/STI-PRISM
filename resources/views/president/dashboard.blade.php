@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

{{-- ===================================== --}}
{{-- TOP HEADER (page title lives in topbar) --}}
{{-- ===================================== --}}


{{-- ===================================== --}}
{{-- KPI SUMMARY CARDS --}}
{{-- ===================================== --}}
@include('layouts.partials.maintenance-stat-cards', [
    'cards' => [
        [
            'label' => 'Total RIS',
            'hint' => 'All time records',
            'value' => number_format((int) ($totalRisCount ?? 0)),
        ],
        [
            'label' => 'Pending',
            'hint' => 'Awaiting decision',
            'value' => number_format((int) ($pendingApprovalsCount ?? 0)),
            'href' => '/president/approvals',
        ],
        [
            'label' => 'Approved',
            'hint' => 'Successfully approved',
            'value' => number_format((int) ($approvedDecisionsCount ?? 0)),
            'href' => '/president/reports/approved',
        ],
        [
            'label' => 'Rejected',
            'hint' => 'Declined requests',
            'value' => number_format((int) ($rejectedDecisionsCount ?? 0)),
            'href' => '/president/reports/approved?filter=rejected',
        ],
    ],
])

{{-- ===================================== --}}
{{-- CHARTS + TOP 3 RECENT RIS --}}
{{-- ===================================== --}}
@php
    $chartApprovedTotal = collect($monthlyStats ?? [])->sum('approved');
    $chartRejectedTotal = collect($monthlyStats ?? [])->sum('rejected');
    $chartDecisionTotal = $chartApprovedTotal + $chartRejectedTotal;
@endphp
<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="pm-analytics-card lg:col-span-2 slide-up" style="animation-delay: 0.22s">
        <div class="pm-analytics-header">
            <div>
                <h2 class="pm-analytics-title">Decision Trend</h2>
                <p class="pm-analytics-subtitle">Last 6 months · approvals &amp; rejections</p>
            </div>
            <div class="pm-chart-total is-blue">
                {{ number_format($chartDecisionTotal) }}
                <span>decisions</span>
            </div>
        </div>

        <div class="pm-decision-chart-legend">
            <div class="pm-decision-chart-legend-item">
                <span class="pm-decision-chart-swatch is-approved"></span>
                Approved
            </div>
            <div class="pm-decision-chart-legend-item">
                <span class="pm-decision-chart-swatch is-rejected"></span>
                Rejected
            </div>
        </div>

        <div class="pm-decision-chart">
            <canvas id="dashboardChart"></canvas>
        </div>
    </section>

    <aside class="pm-card p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recent RIS</h2>
                <p class="mt-0.5 text-xs text-gray-400">Top 3 awaiting your decision</p>
            </div>
            <a href="/president/approvals" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="Open approval queue">
                View all
            </a>
        </div>
        <div class="mt-4 space-y-2.5">
            @forelse ($recentRis as $ris)
                @php
                    $label = \App\Support\RisWorkflow::formNumber($ris);
                    $date = $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—';
                    $requester = \App\Support\RisWorkflow::requesterName($ris);
                    $amount = number_format((float) ($ris->total_amount ?? 0), 2);
                @endphp
                <div class="rounded-xl border border-blue-100 bg-white px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50/40">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $label }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-gray-500">{{ $requester }} · {{ $date }}</p>
                            <p class="mt-1 text-xs font-semibold text-blue-700">₱{{ $amount }}</p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                            </button>
                            <a
                                href="/president/approvals?approve={{ $ris->ris_id }}"
                                class="inline-flex h-9 items-center rounded-xl bg-blue-600 px-3 text-[11px] font-medium text-white transition hover:bg-blue-700"
                                data-tip="Open and approve this RIS"
                            >
                                Approve
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-200 px-3 py-8 text-center">
                    <p class="text-xs font-medium text-gray-500">No pending RIS right now</p>
                    <p class="mt-1 text-[11px] text-gray-400">New forwarded requests will appear here</p>
                </div>
            @endforelse
        </div>
    </aside>
</div>

{{-- ===================================== --}}
{{-- ATTENTION + RECENTLY APPROVED --}}
{{-- ===================================== --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="lg:col-span-2 pm-card p-5 slide-up" style="animation-delay: 0.28s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Needs Your Attention</h2>
                <p class="mt-0.5 text-xs text-gray-400">Actions that keep the approval workflow moving</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
            <a href="/president/approvals" class="group rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-blue-600 ring-1 ring-blue-100">
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    </div>
                    <span class="text-xl font-bold text-blue-700">{{ $pendingApprovalsCount ?? 0 }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Pending review</p>
                <p class="mt-0.5 text-[11px] text-gray-500">RIS waiting for your decision</p>
            </a>
            <a href="/president/approvals" class="group rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50/40">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                        <i data-lucide="bell" class="h-4 w-4"></i>
                    </div>
                    <span class="text-xl font-bold text-blue-700">{{ $awaitingNotifyCount ?? 0 }}</span>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Ready to notify</p>
                <p class="mt-0.5 text-[11px] text-gray-500">Approved, Administrator not yet notified</p>
            </a>
            <a href="/president/approvals/history" class="group rounded-xl border border-slate-200 bg-white px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50/40">
                <div class="flex items-center justify-between">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                        <i data-lucide="history" class="h-4 w-4"></i>
                    </div>
                    <i data-lucide="chevron-right" class="h-4 w-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-blue-500"></i>
                </div>
                <p class="mt-3 text-sm font-semibold text-gray-900">Approval history</p>
                <p class="mt-0.5 text-[11px] text-gray-500">Review past decisions</p>
            </a>
        </div>
    </section>

    <aside class="pm-card p-5 slide-up" style="animation-delay: 0.3s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Recently Approved RIS</h2>
                <p class="mt-0.5 text-xs text-gray-400">Your latest approvals</p>
            </div>
            <a href="/president/reports/approved" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="View all approved RIS">
                View all
            </a>
        </div>
        <div class="mt-4 space-y-1">
            @forelse ($recentlyApprovedRis ?? [] as $ris)
                @php
                    $label = \App\Support\RisWorkflow::formNumber($ris);
                    $date = $ris->ris_approved_by_date
                        ? date('M d, Y', strtotime($ris->ris_approved_by_date))
                        : '—';
                    $awaiting = !empty($ris->awaiting_notify);
                @endphp
                <div class="flex items-center gap-1">
                    <a
                        href="/president/approvals?preview={{ $ris->ris_id }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-200 hover:bg-blue-50/60 min-w-0 flex-1"
                        data-tip="Open approved RIS"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <i data-lucide="badge-check" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $label }}</p>
                            <p class="text-[11px] text-gray-500">{{ $date }}</p>
                        </div>
                        @if ($awaiting)
                            <span class="inline-flex items-center rounded-xl bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-100">
                                Notify Administrator
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-xl bg-blue-600 px-2 py-0.5 text-[10px] font-semibold text-white">
                                Approved
                            </span>
                        @endif
                    </a>
                    <button type="button" class="icon-btn shrink-0" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                    </button>
                </div>
            @empty
                <p class="px-3 py-4 text-center text-xs text-gray-400">No approved RIS yet</p>
            @endforelse
        </div>
    </aside>
</div>

{{-- ===================================== --}}
{{-- SPENDING + WAITING ON YOU --}}
{{-- ===================================== --}}
@php
    $insights = $insights ?? [];
    $spend = $insights['spend'] ?? [];
    $aging = $insights['aging'] ?? ['buckets' => [], 'oldest' => null, 'total' => 0];
    $pipeline = $insights['pipeline'] ?? ['steps' => [], 'stalled' => 0, 'total' => 0];
    $peso = fn ($v) => '₱'.number_format((float) $v, 2);

    $thisMonth = (float) ($spend['thisMonth'] ?? 0);
    $lastMonth = (float) ($spend['lastMonth'] ?? 0);
    $spendChange = match (true) {
        $lastMonth <= 0 && $thisMonth <= 0 => 'No approvals yet',
        $lastMonth <= 0 => 'None approved last month',
        default => (function () use ($thisMonth, $lastMonth, $peso) {
            $pct = round((($thisMonth - $lastMonth) / $lastMonth) * 100);
            return abs($pct) > 500
                ? $peso($lastMonth).' last month'
                : ($pct >= 0 ? '▲ ' : '▼ ').abs($pct).'% vs last month';
        })(),
    };

    $hours = $insights['decisionHours'] ?? null;
    $decisionText = match (true) {
        $hours === null => '—',
        $hours < 1 => max(1, (int) round($hours * 60)).' min',
        $hours < 48 => rtrim(rtrim(number_format($hours, 1), '0'), '.').' hrs',
        default => round($hours / 24, 1).' days',
    };
    $agingMax = max(1, collect($aging['buckets'])->max('count'));
@endphp
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="lg:col-span-2 pm-card p-5 slide-up" style="animation-delay: 0.32s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Spending Overview</h2>
                <p class="mt-0.5 text-xs text-gray-400">Value of requests you are deciding on and have approved</p>
            </div>
            <a href="/president/reports/monthly-summary" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="Open monthly summary">
                Monthly summary
            </a>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2.5 xl:grid-cols-4">
            <a href="/president/approvals" class="rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3.5 transition hover:border-blue-200 hover:bg-blue-50">
                <p class="text-[11px] font-medium text-gray-500">Awaiting your decision</p>
                <p class="mt-1.5 truncate text-lg font-bold text-blue-700" title="{{ $peso($spend['pendingAmount'] ?? 0) }}">{{ $peso($spend['pendingAmount'] ?? 0) }}</p>
                <p class="mt-0.5 text-[11px] text-gray-400">{{ $pendingApprovalsCount ?? 0 }} pending RIS</p>
            </a>
            <a href="/president/reports/approved" class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-blue-200 hover:bg-blue-50/40">
                <p class="text-[11px] font-medium text-gray-500">Approved this month</p>
                <p class="mt-1.5 truncate text-lg font-bold text-gray-900" title="{{ $peso($thisMonth) }}">{{ $peso($thisMonth) }}</p>
                <p class="mt-0.5 text-[11px] text-gray-400">{{ (int) ($spend['thisMonthCount'] ?? 0) }} RIS · {{ $spendChange }}</p>
            </a>
            <a href="/president/direct-approvals" class="rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-blue-200 hover:bg-blue-50/40">
                <p class="text-[11px] font-medium text-gray-500">Direct approvals this month</p>
                <p class="mt-1.5 truncate text-lg font-bold text-gray-900">{{ (int) ($spend['directCount'] ?? 0) }}</p>
                <p class="mt-0.5 truncate text-[11px] text-gray-400">{{ $peso($spend['directAmount'] ?? 0) }} approved by Administrator</p>
            </a>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5">
                <p class="text-[11px] font-medium text-gray-500">Your avg. decision time</p>
                <p class="mt-1.5 text-lg font-bold text-gray-900">{{ $decisionText }}</p>
                <p class="mt-0.5 text-[11px] text-gray-400">From forwarded to decided · 90 days</p>
            </div>
        </div>

        <div class="mt-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-700">Top requested items</p>
                <p class="text-[11px] text-gray-400">Approved RIS · last 90 days</p>
            </div>
            <div class="mt-3 space-y-2.5">
                @forelse ($insights['topItems'] ?? [] as $item)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="min-w-0 truncate font-medium text-gray-800" title="{{ $item->name }}">{{ $item->name }}</span>
                            <span class="shrink-0 font-semibold text-gray-900">{{ $peso($item->amount) }}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-3">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-blue-50">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ max(2, $item->share) }}%"></div>
                            </div>
                            <span class="w-28 shrink-0 text-right text-[11px] text-gray-400">{{ number_format($item->qty) }} qty · {{ $item->requests }} RIS</span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-200 px-3 py-6 text-center text-xs text-gray-400">No approved requests in the last 90 days</p>
                @endforelse
            </div>
        </div>
    </section>

    <aside class="pm-card p-5 slide-up" style="animation-delay: 0.34s">
        <div>
            <h2 class="text-sm font-bold text-gray-900">Waiting On You</h2>
            <p class="mt-0.5 text-xs text-gray-400">How long pending RIS have been in your queue</p>
        </div>

        <div class="mt-4 space-y-2.5">
            @foreach ($aging['buckets'] as $bucket)
                @php $isLate = $bucket['label'] === 'Over 7 days' && $bucket['count'] > 0; @endphp
                <div class="flex items-center gap-3 text-xs">
                    <span class="w-20 shrink-0 text-gray-500">{{ $bucket['label'] }}</span>
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $isLate ? 'bg-amber-500' : 'bg-blue-600' }}" style="width: {{ $bucket['count'] ? max(6, ($bucket['count'] / $agingMax) * 100) : 0 }}%"></div>
                    </div>
                    <span class="w-5 shrink-0 text-right font-semibold {{ $isLate ? 'text-amber-600' : ($bucket['count'] ? 'text-gray-900' : 'text-gray-300') }}">{{ $bucket['count'] }}</span>
                </div>
            @endforeach
        </div>

        @if ($aging['oldest'])
            @php $oldest = $aging['oldest']; @endphp
            <div class="mt-5 rounded-xl border {{ $oldest->days > 7 ? 'border-amber-200 bg-amber-50/60' : 'border-blue-100 bg-blue-50/50' }} px-3.5 py-3">
                <p class="text-[11px] font-medium {{ $oldest->days > 7 ? 'text-amber-700' : 'text-blue-700' }}">Oldest in your queue</p>
                <div class="mt-1 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold text-gray-900">{{ $oldest->number }}</p>
                        <p class="text-[11px] text-gray-500">
                            {{ $oldest->days === 0 ? 'Forwarded today' : 'Waiting '.$oldest->days.' '.($oldest->days === 1 ? 'day' : 'days') }} · {{ $peso($oldest->amount) }}
                        </p>
                    </div>
                    <a href="/president/approvals?approve={{ $oldest->id }}" class="inline-flex h-8 shrink-0 items-center rounded-xl bg-blue-600 px-3 text-[11px] font-medium text-white transition hover:bg-blue-700">
                        Review
                    </a>
                </div>
            </div>
        @else
            <div class="mt-5 rounded-xl border border-dashed border-gray-200 px-3 py-6 text-center">
                <p class="text-xs font-medium text-gray-500">Your queue is clear</p>
            </div>
        @endif

        @php $decisions = $insights['decisions'] ?? ['approved' => 0, 'rejected' => 0, 'returned' => 0, 'total' => 0, 'days' => 30]; @endphp
        <div class="mt-5 border-t border-gray-100 pt-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-700">Your decisions</p>
                <a href="/president/approvals/history" class="text-[11px] font-medium text-gray-400 transition hover:text-blue-700">Last {{ $decisions['days'] }} days</a>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-blue-50/70 px-2 py-2.5">
                    <p class="text-base font-bold text-blue-700">{{ $decisions['approved'] }}</p>
                    <p class="text-[11px] text-gray-500">Approved</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-2 py-2.5">
                    <p class="text-base font-bold text-gray-900">{{ $decisions['returned'] }}</p>
                    <p class="text-[11px] text-gray-500">Returned</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-2 py-2.5">
                    <p class="text-base font-bold text-gray-900">{{ $decisions['rejected'] }}</p>
                    <p class="text-[11px] text-gray-500">Rejected</p>
                </div>
            </div>
            @if ($decisions['total'] > 0)
                <div class="mt-3 flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="bg-blue-600" style="width: {{ ($decisions['approved'] / $decisions['total']) * 100 }}%"></div>
                    <div class="bg-slate-400" style="width: {{ ($decisions['returned'] / $decisions['total']) * 100 }}%"></div>
                    <div class="bg-slate-300" style="width: {{ ($decisions['rejected'] / $decisions['total']) * 100 }}%"></div>
                </div>
            @endif
        </div>
    </aside>
</div>

{{-- ===================================== --}}
{{-- AFTER APPROVAL + DIRECT APPROVALS --}}
{{-- ===================================== --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="lg:col-span-2 pm-card p-5 slide-up" style="animation-delay: 0.36s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">After Your Approval</h2>
                <p class="mt-0.5 text-xs text-gray-400">Where approved RIS are now in purchasing, funding and delivery</p>
            </div>
            <a href="/president/procurement-records" class="text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="Open procurement records">
                Procurement records
            </a>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-5">
            @foreach ($pipeline['steps'] as $i => $step)
                <div class="rounded-xl border {{ $i === 0 ? 'border-blue-100 bg-blue-50/70' : 'border-slate-200 bg-white' }} px-3.5 py-3.5">
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-xl font-bold {{ $i === 0 ? 'text-blue-700' : 'text-gray-900' }}">{{ $step['count'] }}</span>
                        @if ($i > 0)
                            <span class="text-[11px] font-semibold text-blue-600">{{ $step['percent'] }}%</span>
                        @endif
                    </div>
                    <p class="mt-1.5 text-xs font-semibold text-gray-900">{{ $step['label'] }}</p>
                    <p class="mt-0.5 text-[11px] leading-snug text-gray-500">{{ $step['hint'] }}</p>
                    <div class="mt-2.5 h-1 overflow-hidden rounded-full bg-blue-50">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $step['percent'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (($pipeline['stalled'] ?? 0) > 0)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3">
                <div class="flex items-center gap-3">
                    <i data-lucide="clock-alert" class="h-4 w-4 shrink-0 text-amber-600"></i>
                    <p class="text-xs text-gray-700">
                        <span class="font-semibold text-amber-700">{{ $pipeline['stalled'] }} approved {{ $pipeline['stalled'] === 1 ? 'RIS has' : 'RIS have' }}</span>
                        no Authority to Purchase yet after {{ $pipeline['stalledAfter'] }}+ days. The Purchaser may need a follow-up.
                    </p>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ($pipeline['stalledList'] ?? [] as $stalled)
                        <a href="/president/procurement-records" class="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 ring-1 ring-amber-100 transition hover:ring-amber-200">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-gray-900">{{ $stalled->number }}</p>
                                <p class="text-[11px] text-gray-500">Approved {{ $stalled->days }} days ago</p>
                            </div>
                            <span class="shrink-0 text-xs font-semibold text-gray-700">{{ $peso($stalled->amount) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-4 flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50/50 px-4 py-3">
                <i data-lucide="circle-check" class="h-4 w-4 shrink-0 text-blue-600"></i>
                <p class="text-xs text-gray-700">Every approved RIS older than {{ $pipeline['stalledAfter'] ?? 7 }} days already has an Authority to Purchase.</p>
            </div>
        @endif
    </section>

    <aside class="pm-card p-5 slide-up" style="animation-delay: 0.38s">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-gray-900">Direct Approvals</h2>
                <p class="mt-0.5 text-xs text-gray-400">Approved by Administrator without you</p>
            </div>
            <a href="/president/direct-approvals" class="shrink-0 whitespace-nowrap text-xs font-semibold text-blue-600 transition hover:text-blue-800" data-tip="View all direct approvals">
                View all
            </a>
        </div>
        <div class="mt-4 space-y-2.5">
            @forelse ($insights['directApprovals'] ?? [] as $direct)
                <a href="/president/direct-approvals" class="block rounded-xl border border-blue-100 bg-white px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50/40">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-gray-900">{{ $direct->number }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-gray-500">{{ $direct->approver }} · {{ $direct->at?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold text-blue-700">{{ $peso($direct->amount) }}</span>
                    </div>
                    @if ($direct->reason !== '')
                        <p class="mt-1.5 line-clamp-2 border-l-2 border-blue-100 pl-2 text-[11px] italic text-gray-500">“{{ $direct->reason }}”</p>
                    @endif
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-gray-200 px-3 py-8 text-center">
                    <p class="text-xs font-medium text-gray-500">No direct approvals yet</p>
                </div>
            @endforelse
        </div>
    </aside>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes chartFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .slide-up { opacity: 0; animation: slideUp 0.5s ease-out forwards; }
    .count-up { display: inline-block; }

    /* Maintenance-style analytics chart card */
    .pm-analytics-card {
        min-width: 0;
        overflow: hidden;
        padding: 22px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .pm-analytics-card .pm-stat-value {
        font-size: inherit;
    }
    .pm-analytics-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 12px;
    }
    .pm-analytics-title {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 700;
    }
    .pm-analytics-subtitle {
        margin: 3px 0 0;
        color: #94a3b8;
        font-size: 10px;
    }
    .pm-chart-total {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
    }
    .pm-chart-total.is-blue {
        color: #1d4ed8;
    }
    .pm-chart-total span {
        margin-left: 2px;
        font-size: 9px;
        font-weight: 500;
        color: #94a3b8;
    }
    .pm-decision-chart-legend {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 8px;
    }
    .pm-decision-chart-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
    }
    .pm-decision-chart-swatch {
        width: 10px;
        height: 3px;
        border-radius: 999px;
    }
    .pm-decision-chart-swatch.is-approved { background: #2563EB; }
    .pm-decision-chart-swatch.is-rejected { background: #94a3b8; }
    .pm-decision-chart {
        position: relative;
        width: 100%;
        height: 320px;
        animation: chartFadeIn 0.8s ease-out forwards;
    }
</style>

<script>
    window.printRisDocument = function (risId) {
        if (!risId) return;
        var url = '/president/ris/' + encodeURIComponent(risId) + '/print?ts=' + Date.now();
        var iframe = document.getElementById('presidentRisPrintFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'presidentRisPrintFrame';
            iframe.setAttribute('title', 'Print RIS');
            iframe.setAttribute('aria-hidden', 'true');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
            document.body.appendChild(iframe);
        }

        var printed = false;
        var tryPrint = function () {
            if (printed) return;
            if (!iframe.contentWindow) return;
            printed = true;
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) { /* ignore */ }
        };

        iframe.onload = function () {
            setTimeout(tryPrint, 300);
        };
        iframe.src = url;
    };

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }

        const counters = document.querySelectorAll('.count-up');
        counters.forEach(el => {
            const target = parseInt(el.dataset.target || el.textContent || '0', 10);
            if (target === 0) return;
            let current = 0;
            const step = Math.max(1, Math.floor(target / 30));
            const interval = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(interval);
                }
                el.textContent = current;
            }, 30);
        });

        const chartLabels = @json(array_column($monthlyStats ?? [], 'month_label'));
        const chartApproved = @json(array_column($monthlyStats ?? [], 'approved'));
        const chartRejected = @json(array_column($monthlyStats ?? [], 'rejected'));
        const canvas = document.getElementById('dashboardChart');

        if (!canvas || !chartLabels.length) {
            return;
        }

        const blueShadowPlugin = {
            id: 'presidentBlueShadowPlugin',
            beforeDatasetsDraw(chart) {
                const meta = chart.getDatasetMeta(0);
                if (!meta || meta.hidden || !meta.data.length || !meta.dataset) {
                    return;
                }

                const ctx = chart.ctx;
                const chartArea = chart.chartArea;
                const points = meta.data;
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
                gradient.addColorStop(0.35, 'rgba(37, 99, 235, 0.16)');
                gradient.addColorStop(0.7, 'rgba(37, 99, 235, 0.06)');
                gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

                ctx.save();
                ctx.beginPath();
                ctx.rect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
                ctx.clip();
                ctx.beginPath();
                meta.dataset.path(ctx);

                const lastPoint = points[points.length - 1];
                const firstPoint = points[0];
                const shadowDepth = 75;
                ctx.lineTo(lastPoint.x, Math.min(lastPoint.y + shadowDepth, chartArea.bottom));
                ctx.lineTo(firstPoint.x, Math.min(firstPoint.y + shadowDepth, chartArea.bottom));
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();
                ctx.restore();
            }
        };

        const hoverLinePlugin = {
            id: 'presidentDecisionHoverLine',
            afterDatasetsDraw(chart) {
                const activeElements = chart.tooltip?.getActiveElements();
                if (!activeElements?.length) {
                    return;
                }

                const activeElement = activeElements[0].element;
                const activeIndex = activeElements[0].index;
                const x = activeElement.x;
                const ctx = chart.ctx;
                const chartArea = chart.chartArea;

                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([3, 3]);
                ctx.moveTo(x, chartArea.top);
                ctx.lineTo(x, chartArea.bottom);
                ctx.lineWidth = 1;
                ctx.strokeStyle = '#d7dce5';
                ctx.stroke();
                ctx.restore();

                const xScale = chart.scales.x;
                const labelX = xScale.getPixelForTick(activeIndex);
                const labelY = xScale.bottom + 17;
                const activeLabel = String(chartLabels[activeIndex] || '');

                ctx.save();
                ctx.font = '600 10px Inter, sans-serif';
                const textWidth = ctx.measureText(activeLabel).width;
                const boxWidth = textWidth + 14;
                const boxHeight = 22;
                ctx.fillStyle = '#f1f1f3';
                ctx.beginPath();
                if (typeof ctx.roundRect === 'function') {
                    ctx.roundRect(labelX - boxWidth / 2, labelY - boxHeight / 2, boxWidth, boxHeight, 6);
                } else {
                    ctx.rect(labelX - boxWidth / 2, labelY - boxHeight / 2, boxWidth, boxHeight);
                }
                ctx.fill();
                ctx.fillStyle = '#475569';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(activeLabel, labelX, labelY);
                ctx.restore();
            }
        };

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Approved',
                        data: chartApproved,
                        borderColor: '#2563EB',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        fill: false,
                        tension: 0.42,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHitRadius: 25,
                        pointHoverBackgroundColor: '#2563EB',
                        pointHoverBorderColor: 'white',
                        pointHoverBorderWidth: 2,
                    },
                    {
                        label: 'Rejected',
                        data: chartRejected,
                        borderColor: '#94a3b8',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        fill: false,
                        tension: 0.42,
                        cubicInterpolationMode: 'monotone',
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHitRadius: 25,
                        pointHoverBackgroundColor: '#94a3b8',
                        pointHoverBorderColor: 'white',
                        pointHoverBorderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                normalized: true,
                interaction: { mode: 'index', intersect: false },
                layout: { padding: { top: 10, right: 8, bottom: 18, left: 0 } },
                animation: { duration: 350 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false,
                        position: 'nearest',
                        backgroundColor: '#0f172a',
                        titleColor: 'white',
                        bodyColor: '#94a3b8',
                        borderWidth: 0,
                        padding: { top: 10, right: 12, bottom: 10, left: 12 },
                        cornerRadius: 7,
                        caretSize: 0,
                        displayColors: true,
                        usePointStyle: false,
                        boxWidth: 2,
                        boxHeight: 14,
                        boxPadding: 7,
                        titleSpacing: 4,
                        bodySpacing: 7,
                        titleMarginBottom: 7,
                        titleFont: { family: 'Inter', size: 11, weight: '600' },
                        bodyFont: { family: 'Inter', size: 10, weight: '400' },
                        callbacks: {
                            title(context) {
                                return context[0].label;
                            },
                            label(context) {
                                const value = Math.round(Number(context.raw));
                                return context.dataset.label + '     ' + value;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        offset: false,
                        border: { display: false },
                        grid: { display: false },
                        ticks: {
                            autoSkip: false,
                            color: '#8c929c',
                            padding: 14,
                            maxRotation: 0,
                            minRotation: 0,
                            font: { family: 'Inter', size: 10, weight: '400' },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: {
                            color: '#eef1f5',
                            drawTicks: false,
                        },
                        ticks: {
                            precision: 0,
                            padding: 8,
                            color: '#94a3b8',
                            font: { family: 'Inter', size: 10 },
                        },
                    },
                },
            },
            plugins: [blueShadowPlugin, hoverLinePlugin],
        });
    });
</script>

@endsection
