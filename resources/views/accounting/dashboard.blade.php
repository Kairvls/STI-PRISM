@extends('layouts.accounting-layout')

@section('title', 'Accounting Dashboard')

@section('content')
@include('accounting.partials.flash')

@php
    use Illuminate\Support\Str;

    $d = $dashboard;

    $peso = fn ($amount) => '₱'.number_format((float) $amount, 2);
    $plural = fn (int $n, string $word) => $n.' '.($n === 1 ? $word : Str::plural($word));

    $typeLinks = [
        'package' => route('accounting.approved-packages.index'),
        'liq' => route('accounting.liq.index', ['focus' => 'liq-review']),
    ];
    $typeShort = [
        'package' => 'Request for Check',
        'liq' => 'Liquidation report',
    ];
    $reviewTabs = $d['queueByType'];
    $activeReview = collect($reviewTabs)->filter(fn ($group) => ($group['count'] ?? 0) > 0)->keys()->first()
        ?? array_key_first($reviewTabs);

    $overdue = (int) ($deadlines['overdue'] ?? 0);
    $dueToday = (int) ($deadlines['due_today'] ?? 0);
    $dueWeek = (int) ($deadlines['this_week'] ?? 0);
@endphp

<div class="acx space-y-6">

    {{-- Review queue + deadlines --}}
    <section class="grid gap-6 xl:grid-cols-3">
        <div id="review-queue" class="acx-card scroll-mt-24 xl:col-span-2">
            <div class="flex flex-wrap items-end justify-between gap-3 px-6 pt-6">
                <h2 class="acx-h text-base font-semibold">To review</h2>
                <span id="reviewWaiting" class="text-xs tabular-nums text-slate-400">{{ (int) ($reviewTabs[$activeReview]['count'] ?? 0) }} waiting</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-x-5 border-b border-slate-100 px-6 text-sm" role="tablist">
                @foreach($reviewTabs as $type => $group)
                    @continue(empty($typeLinks[$type]))
                    <button
                        type="button"
                        role="tab"
                        data-review-tab="{{ $type }}"
                        data-review-count="{{ (int) $group['count'] }}"
                        aria-selected="{{ $type === $activeReview ? 'true' : 'false' }}"
                        class="-mb-px shrink-0 border-b-2 pb-2.5 transition {{ $type === $activeReview ? 'border-[#0025cc] font-medium text-slate-900' : 'border-transparent text-slate-400 hover:text-slate-600' }}"
                    >
                        {{ $group['label'] }}
                        <span class="ml-1 tabular-nums {{ $type === $activeReview ? 'text-[#0025cc]' : '' }}">{{ $group['count'] }}</span>
                    </button>
                @endforeach
            </div>

            @foreach($reviewTabs as $type => $group)
                @continue(empty($typeLinks[$type]))
                <div data-review-panel="{{ $type }}" class="{{ $type === $activeReview ? '' : 'hidden' }}">
                    @if($group['rows']->isNotEmpty())
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                                    <th class="px-6 py-3 font-medium">Number</th>
                                    <th class="py-3 pr-4 font-medium max-md:hidden">From</th>
                                    <th class="py-3 pr-4 font-medium max-sm:hidden">Waiting</th>
                                    <th class="py-3 pr-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 border-t border-slate-100">
                                @foreach($group['rows'] as $item)
                                    <tr
                                        class="group cursor-pointer transition hover:bg-slate-50/70"
                                        data-dashboard-preview
                                        data-preview-url="{{ $item->preview_url ?? '' }}"
                                        data-continue-url="{{ $item->url }}"
                                        data-continue-label="{{ $item->action }}"
                                        data-preview-title="{{ $item->ref }}"
                                    >
                                        <td class="px-6 py-3.5">
                                            <span class="font-medium text-slate-900 group-hover:text-[#0025cc]">{{ $item->ref }}</span>
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
                                        <td class="whitespace-nowrap py-3.5 pr-6 text-right">
                                            <span class="text-xs font-medium text-[#0025cc]">{{ $item->action }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="border-t border-slate-100 px-6 py-3 text-xs text-slate-400">
                            @if($group['count'] > $group['rows']->count())
                                {{ $group['rows']->count() }} latest
                            @else
                                Showing {{ $group['rows']->count() }} of {{ $group['count'] }}
                            @endif
                        </p>
                    @else
                        <div class="px-6 py-14 text-center">
                            <p class="text-sm font-medium text-slate-800">Nothing to review</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="acx-card flex flex-col">
            <div class="px-6 pt-6">
                <h2 class="acx-h text-base font-semibold">Liquidation deadlines</h2>
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
                    <button type="button" class="group flex w-full items-center gap-4 px-6 py-3 text-left transition hover:bg-slate-50/70" data-dashboard-preview data-preview-url="{{ $due->preview_url ?? '' }}" data-continue-url="{{ $due->url }}" data-continue-label="Open" data-preview-title="{{ $due->ref }}">
                        <span class="w-12 shrink-0 text-xs tabular-nums text-slate-400">{{ $due->deadline->format('M j') }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-900 group-hover:text-[#0025cc]">{{ $due->ref }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ $due->who }}</span>
                        </span>
                        <span class="shrink-0 text-xs {{ $due->days <= 1 ? 'font-medium text-amber-600' : 'text-slate-500' }}">{{ $due->label }}</span>
                    </button>
                @empty
                    <div class="flex h-full flex-col items-center justify-center px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-700">No liquidation report is due</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Cash advances · decisions --}}
    <section class="grid gap-6 lg:grid-cols-2">
        <div id="cash-advances" class="acx-card flex scroll-mt-24 flex-col">
            <div class="flex items-start justify-between gap-3 px-6 pt-6">
                <div>
                    <h2 class="acx-h text-base font-semibold">Cash still out</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Released cash advances that still need an approved report.</p>
                </div>
                <span class="text-xs tabular-nums text-slate-400">{{ $d['cashAdvanceCount'] }}</span>
            </div>
            <div class="mt-4 flex-1 divide-y divide-slate-100 border-t border-slate-100">
                @forelse($d['cashAdvances'] as $ca)
                    <button type="button" class="group flex w-full items-center gap-3 px-6 py-3 text-left transition hover:bg-slate-50/70" data-dashboard-preview data-preview-url="{{ $ca->preview_url ?? '' }}" data-continue-url="{{ $ca->url }}" data-continue-label="Open" data-preview-title="{{ $ca->ref }}">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-900 group-hover:text-[#0025cc]">{{ $ca->ref }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ $ca->kind }} · {{ $ca->payee }}</span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block text-[11px] {{ $ca->liquidation_status ? 'text-[#0025cc]' : ($ca->days >= 14 ? 'font-medium text-amber-600' : 'text-slate-400') }}">
                                {{ $ca->liquidation_status ? 'Report '.Str::lower($ca->liquidation_status) : ($ca->days === 0 ? 'Released today' : 'Released '.$plural($ca->days, 'day').' ago') }}
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-700">All settled</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="acx-card flex flex-col">
            <div class="flex items-baseline justify-between px-6 pt-6">
                <div>
                    <h2 class="acx-h text-base font-semibold">Latest actions</h2>
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
                        <button type="button" class="group flex w-full items-start gap-3 px-6 py-3 text-left transition hover:bg-slate-50/70" data-dashboard-preview data-preview-url="{{ $decision->preview_url ?? '' }}" data-continue-url="{{ $decision->url ?? url('/accounting/history') }}" data-continue-label="Open" data-preview-title="{{ $decision->ref ?: $decision->type_label }}">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm text-slate-800">
                                    {{ $decision->action }} <span class="text-slate-400">·</span> <span class="group-hover:text-[#0025cc]">{{ $decision->ref ?: $decision->type_label }}</span>
                                </span>
                                <span class="block truncate text-xs text-slate-400">{{ $decision->kind ? $decision->kind.' · ' : '' }}{{ $decision->by }}</span>
                            </span>
                            <span class="shrink-0 text-[11px] text-slate-400">{{ $decision->at?->diffForHumans(null, true, true) }}</span>
                        </button>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center">
                        <p class="text-sm font-medium text-slate-700">Nothing yet</p>
                    </li>
                @endforelse
            </ol>
        </div>
    </section>
</div>

<div id="accountingDashboardPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="accountingDashboardPreviewTitle">
    <div class="my-auto w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <h2 id="accountingDashboardPreviewTitle" class="truncate text-lg font-semibold text-slate-950"></h2>
            <button type="button" id="accountingDashboardPreviewClose" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <span aria-hidden="true" class="text-lg leading-none">&times;</span>
            </button>
        </div>
        <div id="accountingDashboardPreviewBody" class="max-h-[70vh] overflow-auto bg-slate-50"></div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
            <button type="button" id="accountingDashboardPreviewDismiss" class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="accountingDashboardPreviewContinue" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>

<style>
    .acx .acx-card { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 1rem; background: #fff; }
    .acx .acx-h { color: #0f172a; letter-spacing: -0.01em; }
    .acx .acx-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
    .acx .acx-leader { flex: 1 1 auto; min-width: 1rem; border-bottom: 1px dotted #cbd5e1; transform: translateY(-4px); }
</style>

<script>
    (function () {
        const modal = document.getElementById('accountingDashboardPreview');
        const title = document.getElementById('accountingDashboardPreviewTitle');
        const body = document.getElementById('accountingDashboardPreviewBody');
        const continueLink = document.getElementById('accountingDashboardPreviewContinue');
        if (!modal || !body) return;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        const skeleton = '<div class="space-y-3 p-5" aria-hidden="true">'
            + '<div class="h-8 w-1/3 animate-pulse rounded-md bg-slate-200"></div>'
            + '<div class="h-40 animate-pulse rounded-lg bg-slate-200"></div>'
            + '<div class="h-24 animate-pulse rounded-lg bg-slate-200"></div>'
            + '</div>';

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            body.innerHTML = '';
            document.body.style.overflow = '';
        }

        async function openPreview(trigger) {
            const previewUrl = trigger.dataset.previewUrl || '';
            const continueUrl = trigger.dataset.continueUrl || '';
            if (!previewUrl) {
                if (continueUrl) window.location = continueUrl;
                return;
            }

            title.textContent = trigger.dataset.previewTitle || 'Form';
            continueLink.href = continueUrl || '#';
            continueLink.textContent = trigger.dataset.continueLabel || 'Open';
            body.innerHTML = skeleton;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            try {
                const response = await fetch(previewUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                });
                if (!response.ok) throw new Error('Preview failed');
                body.innerHTML = await response.text();
            } catch (error) {
                body.innerHTML = '<p class="px-5 py-10 text-center text-sm text-red-600">Could not load this form.</p>';
            }
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dashboard-preview]');
            if (!trigger) return;
            openPreview(trigger);
        });

        document.getElementById('accountingDashboardPreviewClose')?.addEventListener('click', closePreview);
        document.getElementById('accountingDashboardPreviewDismiss')?.addEventListener('click', closePreview);
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closePreview();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
<script>
    (function () {
        const waiting = document.getElementById('reviewWaiting');
        const tabs = document.querySelectorAll('[data-review-tab]');
        const panels = document.querySelectorAll('[data-review-panel]');
        if (!tabs.length) return;

        function show(type) {
            tabs.forEach((tab) => {
                const on = tab.dataset.reviewTab === type;
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
                tab.classList.toggle('border-[#0025cc]', on);
                tab.classList.toggle('font-medium', on);
                tab.classList.toggle('text-slate-900', on);
                tab.classList.toggle('border-transparent', !on);
                tab.classList.toggle('text-slate-400', !on);
                const count = tab.querySelector('span');
                if (count) {
                    count.classList.toggle('text-[#0025cc]', on);
                }
            });
            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.reviewPanel !== type);
            });
            const active = document.querySelector('[data-review-tab="' + type + '"]');
            if (waiting && active) {
                waiting.textContent = (active.dataset.reviewCount || '0') + ' waiting';
            }
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', function () {
                show(tab.dataset.reviewTab);
            });
        });
    })();
</script>
@endsection
