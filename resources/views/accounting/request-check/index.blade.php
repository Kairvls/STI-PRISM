@extends('layouts.accounting-layout')

@section('title', 'Request Checks')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Needs review',
        'funds' => 'Funds',
        'released' => 'Released',
        'revision' => 'Revision',
        'approved' => 'Approved',
    ];
    $filterCounts = [
        'incoming' => $counts['incoming'],
        'funds' => $counts['funds'],
        'released' => $counts['released'],
        'revision' => $counts['revision'],
    ];
    $statCards = [
        ['key' => 'incoming', 'label' => 'Needs review', 'value' => $counts['incoming'], 'hint' => 'Pending review', 'icon' => 'clipboard-list', 'tone' => 'blue'],
        ['key' => 'funds', 'label' => 'Funds to release', 'value' => $counts['funds'], 'hint' => 'Ready', 'icon' => 'banknote', 'tone' => 'blue'],
        ['key' => 'released', 'label' => 'Released', 'value' => $counts['released'], 'hint' => 'Collected', 'icon' => 'circle-check', 'tone' => 'slate'],
        ['key' => 'revision', 'label' => 'Revision', 'value' => $counts['revision'], 'hint' => 'Sent back', 'icon' => 'pencil', 'tone' => 'slate'],
    ];
@endphp

<div class="acc-page acc-content-fill fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Review submitted checks and mark funds ready for personal collection.</p>
        </div>
        <form method="GET" class="acc-toolbar" id="rfcSearchForm">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" id="rfcSearch" value="{{ request('search') }}" placeholder="Search RFC, ATP, RIS, payee..." class="acc-search">
        </form>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $i => $card)
            <a
                href="/accounting/request-check?status={{ $card['key'] }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
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
        <div class="pm-seg" id="rfcFilterSlider" role="tablist" aria-label="Request Check status filters" data-active="{{ $filter }}">
            <span class="pm-seg-thumb" aria-hidden="true"></span>
            @foreach ($filters as $key => $label)
                <a
                    href="/accounting/request-check?status={{ $key }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                    role="tab"
                    class="pm-seg-btn status-filter-btn {{ $filter === $key ? 'is-active' : '' }}"
                    data-filter="{{ $key }}"
                    aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                >{{ $label }}@if (isset($filterCounts[$key]))<span class="acc-count-badge">{{ $filterCounts[$key] }}</span>@endif</a>
            @endforeach
        </div>
    </div>

    <div class="acc-table-wrap mt-4 slide-up">
        <table class="acc-table min-w-[900px]">
            <thead>
                <tr>
                    <th>Request Check</th>
                    <th>ATP</th>
                    <th>RIS</th>
                    <th>Payee</th>
                    <th class="!text-right">Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="rfcTableBody" class="acc-animate">
                @include('accounting.request-check._rows', ['records' => $records, 'filter' => $filter])
            </tbody>
        </table>
    </div>
    <div id="rfcPagination">
        @if ($records->hasPages())
            <div class="acc-pagination mt-3">{{ $records->links('pagination.president') }}</div>
        @endif
    </div>
</div>

<script>
    (function () {
        const tbody = document.getElementById('rfcTableBody');
        const pagination = document.getElementById('rfcPagination');
        const searchInput = document.getElementById('rfcSearch');
        const searchForm = document.getElementById('rfcSearchForm');
        const filterSlider = document.getElementById('rfcFilterSlider');
        const filterButtons = document.querySelectorAll('#rfcFilterSlider .status-filter-btn');
        const filterCards = document.querySelectorAll('.status-filter-card');
        let currentFilter = '{{ $filter }}';
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

        function buildUrl(page, filter, search) {
            const params = new URLSearchParams();
            params.set('status', filter);
            if (search) params.set('search', search);
            if (page && page > 1) params.set('page', page);
            return '/accounting/request-check?' + params.toString();
        }

        function fetchData(page, filter) {
            if (fetching) return;
            fetching = true;
            page = page || 1;
            filter = filter || currentFilter;
            const search = searchInput ? searchInput.value.trim() : '';

            if (tbody) tbody.classList.add('is-loading');

            fetch(buildUrl(page, filter, search), {
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
                    const map = { incoming: 'incoming', funds: 'funds', released: 'released', revision: 'revision' };
                    filterButtons.forEach(btn => {
                        const key = btn.getAttribute('data-filter');
                        const badge = btn.querySelector('.acc-count-badge');
                        if (map[key] && badge && typeof data.counts[map[key]] !== 'undefined') {
                            badge.textContent = data.counts[map[key]];
                        }
                    });
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
            window.history.replaceState({}, '', buildUrl(page, filter, search));
        }

        function applyFilter(newFilter) {
            if (newFilter === currentFilter) return;
            currentFilter = newFilter;
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
