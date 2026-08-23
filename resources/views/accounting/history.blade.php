@extends('layouts.accounting-layout')

@section('title', 'Accounting History')

@section('content')
@php
    $historyFilters = [
        'all' => 'All types',
        'atp' => 'ATP',
        'rfc' => 'Request Check',
        'liq' => 'Liquidation',
    ];
@endphp

<div class="acc-page acc-content-fill fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Processed ATP, Request Checks, fund releases, and liquidations.</p>
        </div>
        <div class="acc-toolbar">
            <input type="search" name="search" id="historySearch" value="{{ $search }}" placeholder="Search reference, related document..." class="acc-search">
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 slide-up">
        <div class="pm-seg" id="historyFilterSlider" role="tablist" aria-label="History type filters" data-active="{{ $type }}">
            <span class="pm-seg-thumb" aria-hidden="true"></span>
            @foreach ($historyFilters as $key => $label)
                <a
                    href="/accounting/history?type={{ $key }}{{ $search ? '&search='.urlencode($search) : '' }}"
                    role="tab"
                    class="pm-seg-btn history-filter-btn {{ $type === $key ? 'is-active' : '' }}"
                    data-filter="{{ $key }}"
                    aria-selected="{{ $type === $key ? 'true' : 'false' }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="acc-table-wrap mt-4 slide-up">
        <table class="acc-table acc-table--spaced min-w-[760px]">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Related</th>
                    <th class="!text-right">Amount</th>
                    <th>Status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody id="historyTableBody" class="acc-animate">
                @include('accounting._history-rows', ['records' => $records])
            </tbody>
        </table>
    </div>
    <div id="historyPagination">
        @if ($records->hasPages())
            <div class="acc-pagination mt-3">{{ $records->links('pagination.president') }}</div>
        @endif
    </div>
</div>

<script>
    (function () {
        const tbody = document.getElementById('historyTableBody');
        const pagination = document.getElementById('historyPagination');
        const searchInput = document.getElementById('historySearch');
        const filterSlider = document.getElementById('historyFilterSlider');
        const filterButtons = document.querySelectorAll('#historyFilterSlider .history-filter-btn');
        let currentType = '{{ $type }}';
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

        filterButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const newType = this.getAttribute('data-filter');
                if (newType === currentType) return;
                currentType = newType;
                updateFilterButtons(newType);
                fetchData(1, newType);
                pushUrl(1, newType, searchInput ? searchInput.value.trim() : '');
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchData(1, currentType);
                    pushUrl(1, currentType, searchInput.value.trim());
                }, 300);
            });
        }

        // Delegate pagination clicks (live, no page reload)
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

        // Handle browser back/forward
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