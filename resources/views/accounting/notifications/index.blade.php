@extends('layouts.accounting-layout')

@section('title', 'Notifications')

@section('content')
@php
    $periods = [
        'today' => 'Today',
        'week' => 'Weekly',
        'month' => 'Monthly',
        'year' => 'Yearly',
    ];
    $activePeriod = $period ?? 'today';
    $periodLabels = [
        'today' => 'today',
        'week' => 'this week',
        'month' => 'this month',
        'year' => 'this year',
    ];
@endphp

<div class="acc-page fade-in space-y-6">
    <div>
        <p class="text-sm leading-6 text-gray-500">Accounting alerts from the topbar bell.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="pm-card p-5 slide-up">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Inbox</p>
                    <p id="notifInboxCount" class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ ($items->total() ?? 0) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="bell" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <p id="notifInboxHint" class="text-xs text-slate-400">Alerts {{ $periodLabels[$activePeriod] ?? 'today' }}</p>
            </div>
        </div>
        <a href="/accounting/dashboard" class="pm-card p-5 slide-up" style="animation-delay:.06s">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Queues</p>
                    <p class="mt-2 text-base font-semibold tracking-tight text-slate-950">Open dashboard</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-400">ATP, Request Checks, and liquidations</p>
            </div>
        </a>
    </div>

    <section class="pm-card overflow-hidden slide-up" style="animation-delay:.1s">
        <div class="border-b border-slate-100 px-5 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">All alerts</h2>
                    <p id="notifSectionHint" class="mt-0.5 text-xs text-gray-400">Showing alerts {{ $periodLabels[$activePeriod] ?? 'today' }}, newest first</p>
                </div>
                <div
                    id="notifPeriodSlider"
                    class="pm-seg shrink-0"
                    role="tablist"
                    aria-label="Notification period filters"
                    data-active="{{ $activePeriod }}"
                >
                    <span class="pm-seg-thumb" aria-hidden="true"></span>
                    @foreach ($periods as $key => $label)
                        <a
                            href="/accounting/notifications?period={{ $key }}"
                            role="tab"
                            class="pm-seg-btn period-filter-btn {{ $activePeriod === $key ? 'is-active' : '' }}"
                            data-filter="{{ $key }}"
                            aria-selected="{{ $activePeriod === $key ? 'true' : 'false' }}"
                        >{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </div>
        <div id="notifItems" class="acc-list-fill-lg">
            @include('accounting._notif-items', ['items' => $items, 'period' => $activePeriod])
        </div>
        <div id="notifPagination">
            @if ($items->hasPages())
                <div class="acc-pagination acc-pagination--flush border-t border-slate-100">{{ $items->links('pagination.president') }}</div>
            @endif
        </div>
    </section>
</div>

<script>
    (function () {
        const itemsEl = document.getElementById('notifItems');
        const paginationEl = document.getElementById('notifPagination');
        const inboxCountEl = document.getElementById('notifInboxCount');
        const inboxHintEl = document.getElementById('notifInboxHint');
        const sectionHintEl = document.getElementById('notifSectionHint');
        const filterSlider = document.getElementById('notifPeriodSlider');
        const filterButtons = document.querySelectorAll('#notifPeriodSlider .period-filter-btn');

        let currentPeriod = @json($activePeriod);
        let fetching = false;

        const periodHints = {
            today: 'today',
            week: 'this week',
            month: 'this month',
            year: 'this year',
        };

        function buildUrl(page, period) {
            const params = new URLSearchParams();
            params.set('period', period || currentPeriod);
            if (page && page > 1) params.set('page', page);
            return '/accounting/notifications?' + params.toString();
        }

        function updatePeriodButtons(activePeriod) {
            if (filterSlider) {
                filterSlider.setAttribute('data-active', activePeriod);
                if (typeof window.pmUpdateSegControl === 'function') {
                    window.pmUpdateSegControl(filterSlider, activePeriod, true);
                }
            }
            filterButtons.forEach(function (btn) {
                const active = btn.getAttribute('data-filter') === activePeriod;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function updateHints(period) {
            const hint = periodHints[period] || 'today';
            if (inboxHintEl) inboxHintEl.textContent = 'Alerts ' + hint;
            if (sectionHintEl) sectionHintEl.textContent = 'Showing alerts ' + hint + ', newest first';
        }

        function fetchNotifications(page, period) {
            if (fetching) return;
            fetching = true;
            page = page || 1;
            period = period || currentPeriod;

            fetch(buildUrl(page, period), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (itemsEl && data.table_html !== undefined) itemsEl.innerHTML = data.table_html;
                if (paginationEl) {
                    paginationEl.innerHTML = data.pagination_html
                        ? '<div class="acc-pagination acc-pagination--flush border-t border-slate-100">' + data.pagination_html + '</div>'
                        : '';
                }
                if (inboxCountEl && typeof data.total !== 'undefined') {
                    inboxCountEl.textContent = data.total;
                }
                if (window.lucide) lucide.createIcons();
                fetching = false;
            })
            .catch(function (err) {
                console.error(err);
                fetching = false;
            });
        }

        filterButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const newPeriod = this.getAttribute('data-filter');
                if (newPeriod === currentPeriod) return;
                currentPeriod = newPeriod;
                updatePeriodButtons(newPeriod);
                updateHints(newPeriod);
                fetchNotifications(1, newPeriod);
                window.history.replaceState({}, '', buildUrl(1, newPeriod));
            });
        });

        if (paginationEl) {
            paginationEl.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') return;
                e.preventDefault();
                const url = new URL(link.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                const period = url.searchParams.get('period') || currentPeriod;
                if (period !== currentPeriod) {
                    currentPeriod = period;
                    updatePeriodButtons(period);
                    updateHints(period);
                }
                fetchNotifications(page, period);
                window.history.replaceState({}, '', url.pathname + url.search);
            });
        }
    })();
</script>
@endsection
