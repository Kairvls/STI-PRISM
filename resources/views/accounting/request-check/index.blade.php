@extends('layouts.accounting-layout')

@section('title', 'Request Checks')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'incoming' => 'Needs review ('.$counts['incoming'].')',
        'funds' => 'Funds ('.$counts['funds'].')',
        'released' => 'Released ('.$counts['released'].')',
        'revision' => 'Revision',
        'approved' => 'Approved',
        'all' => 'All',
    ];
@endphp

<div class="acc-page fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Review submitted checks and mark funds ready for personal collection.</p>
        </div>
        <form method="GET" class="acc-toolbar" id="rfcSearchForm">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" id="rfcSearch" value="{{ request('search') }}" placeholder="Search RFC, ATP, RIS, payee" class="acc-search">
            <button class="acc-btn acc-btn-funds" type="submit">Search</button>
        </form>
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
                >{{ $label }}</a>
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
                    <th></th>
                </tr>
            </thead>
            <tbody id="rfcTableBody" class="acc-animate">
                @include('accounting.request-check._rows', ['records' => $records])
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
                    // Re-trigger the staggered row entrance animation
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
                    const map = { incoming: 'incoming', funds: 'funds', released: 'released' };
                    filterButtons.forEach(btn => {
                        const key = btn.getAttribute('data-filter');
                        if (map[key] && typeof data.counts[map[key]] !== 'undefined') {
                            const base = btn.textContent.replace(/\s*\(\d+\)\s*$/, '');
                            btn.textContent = base + ' (' + data.counts[map[key]] + ')';
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

        filterButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const newFilter = this.getAttribute('data-filter');
                if (newFilter === currentFilter) return;
                currentFilter = newFilter;
                updateFilterButtons(newFilter);
                fetchData(1, newFilter);
                pushUrl(1, newFilter, searchInput ? searchInput.value.trim() : '');
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

        // Delegate pagination clicks
        if (pagination) {
            pagination.addEventListener('click', function (e) {
                const link = e.target.closest('a[href*="page="]');
                if (!link) return;
                e.preventDefault();
                const url = new URL(link.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                fetchData(page, currentFilter);
                pushUrl(page, currentFilter, searchInput ? searchInput.value.trim() : '');
            });
        }

        // Handle browser back/forward
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