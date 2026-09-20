@extends('layouts.accounting-layout')

@section('title', 'History')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
<style>
    .acc-history-page,
    .acc-history-page * {
        font-family: "Inter", sans-serif;
    }
    .acc-history-page .pur-card .acc-pagination {
        padding: 0.75rem 1.25rem 1rem;
        border-top: 1px solid #f1f5f9;
    }
    .acc-history-page #historyTableBody.is-loading {
        opacity: .45;
        filter: saturate(.6);
        pointer-events: none;
        transition: opacity .18s ease, filter .18s ease;
    }
    .acc-history-page #historyTableBody.acc-animate tr {
        animation: accFadeSwap .35s ease both;
    }
    .acc-history-page #historyTableBody.acc-animate tr:nth-child(1)  { animation-delay: .02s; }
    .acc-history-page #historyTableBody.acc-animate tr:nth-child(2)  { animation-delay: .05s; }
    .acc-history-page #historyTableBody.acc-animate tr:nth-child(3)  { animation-delay: .08s; }
    .acc-history-page #historyTableBody.acc-animate tr:nth-child(4)  { animation-delay: .11s; }
    .acc-history-page #historyTableBody.acc-animate tr:nth-child(5)  { animation-delay: .14s; }
    .acc-history-page #historyTableBody.acc-animate tr:nth-child(6)  { animation-delay: .17s; }
    @media (prefers-reduced-motion: reduce) {
        .acc-history-page #historyTableBody.acc-animate tr { animation: none; }
    }
</style>
@endpush

@section('content')
@include('accounting.partials.flash')

@php
    $historyFilters = [
        'all' => 'All types',
        'atp' => 'ATP',
        'rfc' => 'Request Check',
        'liq' => 'Liquidation',
    ];
@endphp

<div class="acc-page acc-history-page acc-content-fill space-y-6 fade-in">
    <div class="pur-card">
        @include('accounting.partials.status-filter-bar', [
            'filters' => $historyFilters,
            'activeFilter' => $type,
            'queryKey' => 'type',
            'baseUrl' => '/accounting/history',
            'searchValue' => $search,
            'searchPlaceholder' => 'Search reference, related document...',
            'formId' => 'historySearchForm',
            'searchId' => 'historySearch',
        ])

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Reference</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Related</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Updated</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody" class="divide-y divide-gray-100 bg-white acc-animate">
                    @include('accounting._history-rows', ['records' => $records])
                </tbody>
            </table>
        </div>

        <div id="historyPagination">
            @if ($records->hasPages())
                <div class="acc-pagination">{{ $records->links('pagination.president') }}</div>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        const tbody = document.getElementById('historyTableBody');
        const pagination = document.getElementById('historyPagination');
        const searchInput = document.getElementById('historySearch');
        const searchForm = document.getElementById('historySearchForm');
        const filterTabs = document.querySelectorAll('.status-filter-tab');
        let currentType = '{{ $type }}';
        let searchTimeout = null;
        let fetching = false;

        function updateFilterButtons(activeFilter) {
            filterTabs.forEach(tab => {
                const active = tab.getAttribute('data-filter') === activeFilter;
                tab.classList.toggle('bg-gray-100/80', active);
                tab.classList.toggle('font-medium', active);
                tab.classList.toggle('text-black', active);
                tab.classList.toggle('text-slate-500', !active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            if (searchForm) {
                const hidden = searchForm.querySelector('input[name="type"]');
                if (hidden) hidden.value = activeFilter;
            }
        }

        function buildUrl(page, type, search) {
            const params = new URLSearchParams();
            params.set('type', type);
            if (search) params.set('search', search);
            if (page && page > 1) params.set('page', page);
            return '/accounting/history?' + params.toString();
        }

        function fetchData(page, type) {
            if (fetching) return;
            fetching = true;
            page = page || 1;
            type = type || currentType;
            const search = searchInput ? searchInput.value.trim() : '';

            if (tbody) tbody.classList.add('is-loading');

            fetch(buildUrl(page, type, search), {
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
                if (window.lucide) lucide.createIcons();
                fetching = false;
            })
            .catch(err => {
                console.error(err);
                if (tbody) tbody.classList.remove('is-loading');
                fetching = false;
            });
        }

        function pushUrl(page, type, search) {
            window.history.replaceState({}, '', buildUrl(page, type, search));
        }

        function applyFilter(newType) {
            if (newType === currentType) return;
            currentType = newType;
            updateFilterButtons(newType);
            fetchData(1, newType);
            pushUrl(1, newType, searchInput ? searchInput.value.trim() : '');
        }

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                applyFilter(this.getAttribute('data-filter'));
            });
        });

        if (searchForm) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                fetchData(1, currentType);
                pushUrl(1, currentType, searchInput ? searchInput.value.trim() : '');
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchData(1, currentType);
                    pushUrl(1, currentType, searchInput.value.trim());
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
                fetchData(page, currentType);
                pushUrl(page, currentType, searchInput ? searchInput.value.trim() : '');
            });
        }

        window.addEventListener('popstate', function () {
            const params = new URLSearchParams(window.location.search);
            const type = params.get('type') || 'all';
            const search = params.get('search') || '';
            currentType = type;
            if (searchInput) searchInput.value = search;
            updateFilterButtons(type);
            fetchData(1, type);
        });
    })();
</script>
@endsection
