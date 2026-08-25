@extends('layouts.accounting-layout')

@section('title', 'Liquidation Reports')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Needs review',
        'revision' => 'Revision',
        'approved' => 'Approved',
    ];
    $filterCounts = [
        'incoming' => $counts['incoming'],
        'revision' => $counts['revision'],
        'approved' => $counts['approved'],
    ];
    $statCards = [
        ['key' => 'incoming', 'label' => 'Needs review', 'value' => $counts['incoming'], 'hint' => 'Pending review', 'icon' => 'receipt', 'tone' => 'blue'],
        ['key' => 'revision', 'label' => 'Revision', 'value' => $counts['revision'], 'hint' => 'Sent back', 'icon' => 'pencil', 'tone' => 'slate'],
        ['key' => 'approved', 'label' => 'Approved', 'value' => $counts['approved'], 'hint' => 'Completed', 'icon' => 'badge-check', 'tone' => 'blue'],
        ['key' => 'all', 'label' => 'All liquidations', 'value' => $counts['all'], 'hint' => 'In queue', 'icon' => 'folder', 'tone' => 'slate'],
    ];
@endphp

<div class="acc-page acc-content-fill fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Review expenses, receipts, and close the transaction.</p>
        </div>
        <form method="GET" class="acc-toolbar" id="liqSearchForm">
            <input type="hidden" name="status" value="{{ $filter }}">
            @if (!empty($deadlineFilter))
                <input type="hidden" name="deadline" value="{{ $deadlineFilter }}">
            @endif
            <input type="search" name="search" id="liqSearch" value="{{ request('search') }}" placeholder="Search liquidation or employee..." class="acc-search">
        </form>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $i => $card)
            <a
                href="/accounting/liquidation-reports?status={{ $card['key'] }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                class="pm-stat-card relative slide-up status-filter-card {{ $filter === $card['key'] ? 'ring-2 ring-blue-200 border-blue-200' : '' }}"
                style="animation-delay:{{ 0.04 + ($i * 0.04) }}s"
                data-filter="{{ $card['key'] }}"
            >
                <div class="pm-stat-icon {{ $card['tone'] === 'blue' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-600' }}">
                    <i data-lucide="{{ $card['icon'] }}"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="pm-stat-label">{{ $card['label'] }}</p>
                    <p class="pm-stat-value {{ $card['tone'] === 'blue' ? 'is-blue' : '' }}">
                        <span data-count="{{ $card['key'] }}">{{ $card['value'] }}</span> {{ $card['hint'] }}
                    </p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 slide-up">
        <div class="pm-seg" id="liqFilterSlider" role="tablist" aria-label="Liquidation status filters" data-active="{{ $filter }}">
            <span class="pm-seg-thumb" aria-hidden="true"></span>
            @foreach ($filters as $key => $label)
                <a
                    href="/accounting/liquidation-reports?status={{ $key }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                    role="tab"
                    class="pm-seg-btn status-filter-btn {{ $filter === $key ? 'is-active' : '' }}"
                    data-filter="{{ $key }}"
                    aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                >{{ $label }}@if (isset($filterCounts[$key]))<span class="acc-count-badge">{{ $filterCounts[$key] }}</span>@endif</a>
            @endforeach
        </div>
    </div>

    @if (!empty($deadlineFilter))
        @php
            $deadlineBanner = [
                'overdue' => ['label' => 'Overdue', 'hint' => 'Past submission deadline', 'tone' => 'rose'],
                'due_today' => ['label' => 'Due today', 'hint' => 'Deadline is today', 'tone' => 'amber'],
                'this_week' => ['label' => 'Due this week', 'hint' => 'Deadline within 7 days', 'tone' => 'blue'],
            ][$deadlineFilter] ?? null;
        @endphp
        @if ($deadlineBanner)
            <div class="acc-deadline-banner is-{{ $deadlineBanner['tone'] }} mt-4 slide-up" id="liqDeadlineBanner">
                <div class="min-w-0">
                    <p class="acc-deadline-banner-title">{{ $deadlineBanner['label'] }}</p>
                    <p class="acc-deadline-banner-hint">{{ $deadlineBanner['hint'] }} · showing matching liquidations only</p>
                </div>
                <a
                    href="/accounting/liquidation-reports?status=incoming{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                    class="acc-btn acc-btn-ghost acc-deadline-banner-clear"
                >Clear filter</a>
            </div>
        @endif
    @endif

    <div class="acc-table-wrap mt-4 slide-up">
        <table class="acc-table min-w-[820px]">
            <thead>
                <tr>
                    <th>Liquidation</th>
                    <th>Receiving Report</th>
                    <th>Employee</th>
                    <th class="!text-right">Amount</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="liqTableBody" class="acc-animate">
                @include('accounting.liquidation-reports._rows', ['records' => $records, 'filter' => $filter, 'deadlineFilter' => $deadlineFilter ?? null])
            </tbody>
        </table>
    </div>
    <div id="liqPagination">
        @if ($records->hasPages())
            <div class="acc-pagination mt-3">{{ $records->links('pagination.president') }}</div>
        @endif
    </div>
</div>

<script>
    (function () {
        const tbody = document.getElementById('liqTableBody');
        const pagination = document.getElementById('liqPagination');
        const searchInput = document.getElementById('liqSearch');
        const searchForm = document.getElementById('liqSearchForm');
        const filterSlider = document.getElementById('liqFilterSlider');
        const filterButtons = document.querySelectorAll('#liqFilterSlider .status-filter-btn');
        const filterCards = document.querySelectorAll('.status-filter-card');
        let currentFilter = '{{ $filter }}';
        let currentDeadline = @json($deadlineFilter ?? null);
        let searchTimeout = null;
        let fetching = false;

        function updateFilterButtons(activeFilter) {
            if (filterSlider) {
                filterSlider.setAttribute('data-active', activeFilter);
                if (typeof window.pmUpdateSegControl === 'function') {
                    window.pmUpdateSegControl(filterSlider, activeFilter, true);
                }
            }
            filterButtons.forEach(btn => {
                const active = btn.getAttribute('data-filter') === activeFilter;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            filterCards.forEach(card => {
                const active = card.getAttribute('data-filter') === activeFilter;
                card.classList.toggle('ring-2', active);
                card.classList.toggle('ring-blue-200', active);
                card.classList.toggle('border-blue-200', active);
            });
            if (searchForm) {
                const hidden = searchForm.querySelector('input[name="status"]');
                if (hidden) hidden.value = activeFilter;
            }
        }

        function buildUrl(page, filter, search, deadline) {
            const params = new URLSearchParams();
            params.set('status', filter);
            if (search) params.set('search', search);
            if (deadline) params.set('deadline', deadline);
            if (page && page > 1) params.set('page', page);
            return '/accounting/liquidation-reports?' + params.toString();
        }

        function fetchData(page, filter) {
            if (fetching) return;
            fetching = true;
            page = page || 1;
            filter = filter || currentFilter;
            const search = searchInput ? searchInput.value.trim() : '';
            const deadline = currentDeadline;

            if (tbody) tbody.classList.add('is-loading');

            fetch(buildUrl(page, filter, search, deadline), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (tbody) {
                    tbody.innerHTML = data.table_html;
                    tbody.classList.remove('is-loading');
                    tbody.classList.remove('acc-animate');
                    void tbody.offsetWidth;
                    tbody.classList.add('acc-animate');
                }
                if (pagination) {
                    pagination.innerHTML = data.pagination_html
                        ? '<div class="acc-pagination mt-3">' + data.pagination_html + '</div>'
                        : '';
                }
                if (data.counts) {
                    Object.keys(data.counts).forEach(key => {
                        document.querySelectorAll('[data-count="' + key + '"]').forEach(el => {
                            el.textContent = data.counts[key];
                        });
                    });
                    const map = { incoming: 'incoming', revision: 'revision', approved: 'approved' };
                    filterButtons.forEach(btn => {
                        const key = btn.getAttribute('data-filter');
                        const badge = btn.querySelector('.acc-count-badge');
                        if (map[key] && badge && typeof data.counts[map[key]] !== 'undefined') {
                            badge.textContent = data.counts[map[key]];
                        }
                    });
                }
                currentDeadline = data.deadline_filter || null;
                const banner = document.getElementById('liqDeadlineBanner');
                if (banner && !currentDeadline) banner.remove();
                if (searchForm) {
                    let deadlineInput = searchForm.querySelector('input[name="deadline"]');
                    if (currentDeadline) {
                        if (!deadlineInput) {
                            deadlineInput = document.createElement('input');
                            deadlineInput.type = 'hidden';
                            deadlineInput.name = 'deadline';
                            searchForm.appendChild(deadlineInput);
                        }
                        deadlineInput.value = currentDeadline;
                    } else if (deadlineInput) {
                        deadlineInput.remove();
                    }
                }
                if (window.lucide) lucide.createIcons();
                fetching = false;
            })
            .catch(err => {
                console.error(err);
                if (tbody) tbody.classList.remove('is-loading');
                fetching = false;
            });
        }

        function pushUrl(page, filter, search) {
            window.history.replaceState({}, '', buildUrl(page, filter, search, currentDeadline));
        }

        function applyFilter(newFilter) {
            if (newFilter === currentFilter && !currentDeadline) return;
            currentFilter = newFilter;
            currentDeadline = null;
            updateFilterButtons(newFilter);
            fetchData(1, newFilter);
            pushUrl(1, newFilter, searchInput ? searchInput.value.trim() : '');
        }

        filterButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                applyFilter(this.getAttribute('data-filter'));
            });
        });

        filterCards.forEach(card => {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                applyFilter(this.getAttribute('data-filter'));
            });
        });

        if (searchForm) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                fetchData(1, currentFilter);
                pushUrl(1, currentFilter, searchInput ? searchInput.value.trim() : '');
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchData(1, currentFilter);
                    pushUrl(1, currentFilter, searchInput.value.trim());
                }, 300);
            });
        }

        if (pagination) {
            pagination.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') return;
                e.preventDefault();
                const url = new URL(link.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                fetchData(page, currentFilter);
                pushUrl(page, currentFilter, searchInput ? searchInput.value.trim() : '');
            });
        }

        window.addEventListener('popstate', function () {
            const params = new URLSearchParams(window.location.search);
            const filter = params.get('status') || 'incoming';
            const search = params.get('search') || '';
            currentFilter = filter;
            if (searchInput) searchInput.value = search;
            updateFilterButtons(filter);
            fetchData(1, filter);
        });
    })();
</script>
@endsection
