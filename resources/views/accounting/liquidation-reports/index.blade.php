@extends('layouts.accounting-layout')

@section('title', 'Liquidation Reports')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'incoming' => 'Needs review ('.$counts['incoming'].')',
        'revision' => 'Revision ('.$counts['revision'].')',
        'approved' => 'Approved ('.$counts['approved'].')',
        'all' => 'All',
    ];
@endphp

<div class="acc-page fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Review expenses, receipts, and close the transaction.</p>
        </div>
        <form method="GET" class="acc-toolbar" id="liqSearchForm">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" id="liqSearch" value="{{ request('search') }}" placeholder="Search liquidation or employee" class="acc-search">
            <button class="acc-btn acc-btn-funds" type="submit">Search</button>
        </form>
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
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

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
                    <th></th>
                </tr>
            </thead>
            <tbody id="liqTableBody" class="acc-animate">
                @include('accounting.liquidation-reports._rows', ['records' => $records])
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
            return '/accounting/liquidation-reports?' + params.toString();
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
                    const map = { incoming: 'incoming', revision: 'revision', approved: 'approved' };
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