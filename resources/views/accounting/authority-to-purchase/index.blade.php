@extends('layouts.accounting-layout')

@section('title', 'ATP')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
<style>
    .acc-atp-page,
    .acc-atp-page * {
        font-family: "Inter", sans-serif;
    }
    .acc-atp-page .pur-card .acc-pagination {
        padding: 0.75rem 1.25rem 1rem;
        border-top: 1px solid #f1f5f9;
    }
    .acc-atp-page #atpTableBody.is-loading {
        opacity: .45;
        filter: saturate(.6);
        pointer-events: none;
        transition: opacity .18s ease, filter .18s ease;
    }
    .acc-atp-page #atpTableBody.acc-animate tr {
        animation: accFadeSwap .35s ease both;
    }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(1)  { animation-delay: .02s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(2)  { animation-delay: .05s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(3)  { animation-delay: .08s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(4)  { animation-delay: .11s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(5)  { animation-delay: .14s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(6)  { animation-delay: .17s; }
    @media (prefers-reduced-motion: reduce) {
        .acc-atp-page #atpTableBody.acc-animate tr { animation: none; }
    }
</style>
@endpush

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Needs review',
        'revision' => 'Revision',
        'approved' => 'Approved',
    ];
    $searchQuery = request('search') ? '&search='.urlencode(request('search')) : '';
    $statCards = [
        [
            'label' => 'Needs review',
            'hint' => 'Awaiting Accounting review',
            'value' => number_format($counts['incoming'] ?? 0),
            'href' => '/accounting/authority-to-purchase?status=incoming'.$searchQuery,
            'filterKey' => 'incoming',
            'active' => $filter === 'incoming',
        ],
        [
            'label' => 'Revision',
            'hint' => 'Sent back to Purchaser',
            'value' => number_format($counts['revision'] ?? 0),
            'href' => '/accounting/authority-to-purchase?status=revision'.$searchQuery,
            'filterKey' => 'revision',
            'active' => $filter === 'revision',
        ],
        [
            'label' => 'Approved',
            'hint' => 'Cleared by Accounting',
            'value' => number_format($counts['approved'] ?? 0),
            'href' => '/accounting/authority-to-purchase?status=approved'.$searchQuery,
            'filterKey' => 'approved',
            'active' => $filter === 'approved',
        ],
    ];
    $filterLabels = [
        'all' => 'All ATP records',
        'incoming' => 'ATP awaiting your review',
        'revision' => 'ATP sent back for revision',
        'approved' => 'ATP cleared by Accounting',
    ];
@endphp

<div class="acc-page acc-atp-page acc-content-fill space-y-6 fade-in">
    @include('layouts.partials.maintenance-stat-cards', ['cards' => $statCards])

    <div class="pur-card">
        @include('accounting.partials.status-filter-bar', [
            'filters' => $filters,
            'activeFilter' => $filter,
            'baseUrl' => '/accounting/authority-to-purchase',
            'searchPlaceholder' => 'Search ATP, RIS, supplier...',
            'formId' => 'atpSearchForm',
            'searchId' => 'atpSearch',
        ])

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Related RIS</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody id="atpTableBody" class="divide-y divide-gray-100 bg-white acc-animate">
                    @include('accounting.authority-to-purchase._rows', ['records' => $records, 'filter' => $filter])
                </tbody>
            </table>
        </div>

        <div id="atpPagination">
            @if ($records->hasPages())
                <div class="acc-pagination">{{ $records->links('pagination.president') }}</div>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        const tbody = document.getElementById('atpTableBody');
        const pagination = document.getElementById('atpPagination');
        const searchInput = document.getElementById('atpSearch');
        const searchForm = document.getElementById('atpSearchForm');
        const filterCards = document.querySelectorAll('.status-filter-card');
        const filterTabs = document.querySelectorAll('.status-filter-tab');
        let currentFilter = '{{ $filter }}';
        let searchTimeout = null;
        let fetching = false;

        function updateFilterButtons(activeFilter) {
            filterCards.forEach(card => {
                card.classList.toggle('is-active', card.getAttribute('data-filter') === activeFilter);
            });
            filterTabs.forEach(tab => {
                const active = tab.getAttribute('data-filter') === activeFilter;
                tab.classList.toggle('bg-gray-100/80', active);
                tab.classList.toggle('font-medium', active);
                tab.classList.toggle('text-black', active);
                tab.classList.toggle('text-slate-500', !active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
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
            return '/accounting/authority-to-purchase?' + params.toString();
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
                        ? '<div class="acc-pagination">' + data.pagination_html + '</div>'
                        : '';
                    if (typeof window.bindPageCarousels === 'function') window.bindPageCarousels();
                }
                if (data.counts) {
                    Object.keys(data.counts).forEach(key => {
                        document.querySelectorAll('[data-count="' + key + '"]').forEach(el => {
                            el.textContent = Number(data.counts[key]).toLocaleString();
                        });
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

        filterCards.forEach(card => {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                applyFilter(this.getAttribute('data-filter'));
            });
        });

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function (e) {
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
