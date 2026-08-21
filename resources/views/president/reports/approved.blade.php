@extends('layouts.president-layout')

@section('title', 'History')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <p class="text-sm leading-6 text-gray-500">
            View approved, rejected, and pending President RIS decisions.
        </p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4 slide-up" style="animation-delay: 0.05s">
    <div class="pm-kpi-card slide-up" style="animation-delay: 0.05s">
        <p class="text-xs font-semibold text-gray-500">Total Approved</p>
        <p id="cardTotalApproved" class="mt-2 text-3xl font-bold text-blue-600 count-up" data-target="{{ $totalApproved ?? 0 }}">{{ $totalApproved ?? 0 }}</p>
    </div>
    <div class="pm-kpi-card slide-up" style="animation-delay: 0.1s">
        <p class="text-xs font-semibold text-gray-500">Total Rejected</p>
        <p id="cardTotalRejected" class="mt-2 text-3xl font-bold text-slate-600 count-up" data-target="{{ $totalRejected ?? 0 }}">{{ $totalRejected ?? 0 }}</p>
    </div>
    <div class="pm-kpi-card slide-up" style="animation-delay: 0.15s">
        <p class="text-xs font-semibold text-gray-500">Pending RIS</p>
        <p id="cardTotalPending" class="mt-2 text-3xl font-bold text-slate-600 count-up" data-target="{{ $totalPending ?? 0 }}">{{ $totalPending ?? 0 }}</p>
    </div>
    <div class="pm-kpi-card slide-up" style="animation-delay: 0.2s">
        <p class="text-xs font-semibold text-gray-500">Total Decisions</p>
        <p id="cardTotalDecisions" class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $totalDecisions ?? 0 }}">{{ $totalDecisions ?? 0 }}</p>
    </div>
</div>

{{-- Table --}}
<div class="mt-4 grid grid-cols-1 gap-4">
    @php
        $currentFilter = $filter ?? 'all';
        $badgeClasses = match ($currentFilter) {
            'approved' => 'bg-blue-50 text-blue-800 border-blue-200',
            'rejected' => 'bg-slate-100 text-slate-800 border-slate-200',
            'pending' => 'bg-slate-100 text-slate-800 border-slate-200',
            default => 'bg-gray-50 text-gray-800 border-gray-200',
        };
        $listTitle = match ($currentFilter) {
            'approved' => 'Approved decision list',
            'rejected' => 'Rejected decision list',
            'pending' => 'Pending decision list',
            default => 'All decision list',
        };
        $listDescription = match ($currentFilter) {
            'approved' => 'RIS records approved by the President.',
            'rejected' => 'RIS records rejected by the President.',
            'pending' => 'RIS records awaiting the President\'s decision.',
            default => 'Approved, rejected, and pending President decisions.',
        };
    @endphp
    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.15s">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">{{ $listTitle }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $listDescription }}</p>
            </div>
            <span id="approvedCount" class="inline-flex items-center rounded-lg {{ $badgeClasses }} px-3 py-1 text-xs font-semibold border">
                {{ $outcomeRecords->total() }} total
            </span>
        </div>

        {{-- Filters --}}
        <div class="mt-4 flex flex-wrap items-center gap-3">
            {{-- Status Filter Slider --}}
            @php
                $approvedFilters = [
                    'all' => 'All',
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ];
                $activeApprovedFilter = $filter ?? 'all';
            @endphp
            <div
                id="approvedFilterSlider"
                class="pm-seg"
                role="tablist"
                aria-label="Decision status filters"
                data-active="{{ $activeApprovedFilter }}"
            >
                <span class="pm-seg-thumb" aria-hidden="true"></span>
                @foreach ($approvedFilters as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        class="pm-seg-btn status-filter-btn {{ $activeApprovedFilter === $key ? 'is-active' : '' }}"
                        data-filter="{{ $key }}"
                        aria-selected="{{ $activeApprovedFilter === $key ? 'true' : 'false' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Live Search --}}
            <div class="relative flex-1 min-w-[220px]">
                <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input
                    type="text"
                    id="approvedSearch"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by Reference No., Purpose, or Status..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200"
                    autocomplete="off"
                />
            </div>

            {{-- Clear link --}}
            @if (request('search') || request('filter'))
                <button type="button" id="clearFiltersBtn" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-50 active:scale-95">
                    Clear
                </button>
            @endif
        </div>

        <div class="mt-4 overflow-x-auto">
            <table id="approvedTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference No.</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Status</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Amount</th>
                        <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Remarks</th>
                        <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Action</th>
                    </tr>
                </thead>
                <tbody id="approvedTableBody">
                    @include('president.reports._approved-table', ['approvedOutcomeRecords' => $outcomeRecords, 'type' => $filter])
                </tbody>
            </table>
        </div>
        @include('president.partials.table-word-export', [
            'target' => '#approvedTable',
            'filename' => 'president-decision-history',
            'label' => 'Print as Word',
        ])

        {{-- Pagination --}}
        @if ($outcomeRecords->hasPages())
            <div id="approvedPagination" class="mt-4 border-t border-gray-100 pt-4">
                {{ $outcomeRecords->links('pagination.president') }}
            </div>
        @endif
    </section>
</div>

{{-- ============================== --}}
{{-- REMARKS MODAL --}}
{{-- ============================== --}}
<div id="remarksModal" class="fixed inset-0 z-50 hidden">
    <div class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px] modal-overlay" onclick="closeRemarksModal()">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] modal-content" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Remarks</h3>
                        <p class="mt-1 text-sm text-slate-600">Decision remarks from the President.</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRemarksModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>
            <div class="px-6 py-5">
                <p id="remarksContent" class="text-sm text-slate-700 leading-relaxed"></p>
            </div>
            <div class="flex items-center justify-end border-t border-gray-100 px-6 py-4">
                <button type="button" class="action-btn h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-slate-100 hover:text-slate-950 active:scale-95" onclick="closeRemarksModal()">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- RIS VIEW MODAL — same fit-to-screen viewer as RIS Approvals --}}
@include('president.partials.ris-readonly-modal')
@include('president.partials.ris-fit-viewer')

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    @keyframes overlayIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .fade-in {
        animation: fadeIn 0.4s ease-out forwards;
    }

    .slide-up {
        opacity: 0;
        animation: slideUp 0.5s ease-out forwards;
    }

    .modal-overlay {
        animation: overlayIn 0.2s ease-out forwards;
    }

    .modal-content {
        animation: modalIn 0.25s ease-out forwards;
    }

    .outcome-row {
        transition: background-color 0.2s ease;
    }

    .outcome-row:hover {
        background-color: rgba(254, 252, 232, 0.4);
    }

    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:active {
        transform: scale(0.95);
    }

    #approvedTableBody {
        transition: opacity 0.25s ease;
    }

    #approvedTableBody.loading {
        opacity: 0.3;
    }

    .skeleton-row {
        animation: skeletonPulse 1.5s ease-in-out infinite;
    }

    @keyframes skeletonPulse {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 0.8; }
    }

    .backdroop-overlay {
        animation: overlayIn 0.2s ease-out forwards;
    }

    .card-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }

    .count-up {
        display: inline-block;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #risViewModal, #risViewModal * {
            visibility: visible;
        }
        #risViewModal {
            position: static;
            background: white;
            backdrop-filter: none;
        }
        #risViewContainer {
            transform: scale(1) !important;
        }
        .print-btn {
            display: none !important;
        }
    }
</style>

<script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const risModal = document.getElementById('historyRisModal');
            const remarksModal = document.getElementById('remarksModal');
            if (risModal && !risModal.classList.contains('hidden')) {
                closeRisViewModal();
            }
            if (remarksModal && !remarksModal.classList.contains('hidden')) {
                closeRemarksModal();
            }
        }
    });

    function openRemarksModal(remarks) {
        const modal = document.getElementById('remarksModal');
        const content = document.getElementById('remarksContent');
        if (content) content.textContent = remarks;
        if (modal) modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeRemarksModal() {
        document.getElementById('remarksModal').classList.add('hidden');
    }

    // Live search + filter
    let searchTimeout = null;
    const searchInput = document.getElementById('approvedSearch');
    const filterButtons = document.querySelectorAll('.status-filter-btn');
    const clearBtn = document.getElementById('clearFiltersBtn');
    let currentFilter = '{{ $filter ?? 'all' }}';

    function updateFilterButtons(activeFilter) {
        const track = document.getElementById('approvedFilterSlider');
        if (track) {
            track.setAttribute('data-active', activeFilter);
            if (typeof window.pmUpdateSegControl === 'function') {
                window.pmUpdateSegControl(track, activeFilter, true);
            }
        }
    }

    function fetchApprovedData(page, filter) {
        const search = searchInput ? searchInput.value : '';
        page = page || 1;
        filter = filter || currentFilter;

        const tbody = document.getElementById('approvedTableBody');
        const pagination = document.getElementById('approvedPagination');
        const totalSpan = document.getElementById('approvedCount');

        if (tbody) {
            tbody.style.opacity = '0.4';
            tbody.style.transition = 'opacity 0.2s ease';
        }

        const params = new URLSearchParams();
        if (search) params.set('search', search);
        params.set('page', page);
        params.set('filter', filter);

        fetch(`/president/reports/approved?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (tbody) {
                tbody.innerHTML = data.table_html;
                tbody.style.opacity = '1';
            }
            if (totalSpan) totalSpan.textContent = data.total + ' total';
            if (typeof data.total_approved !== 'undefined') {
                const setCard = (id, value) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.textContent = Number(value).toLocaleString();
                    el.setAttribute('data-target', String(value));
                };
                setCard('cardTotalApproved', data.total_approved);
                setCard('cardTotalRejected', data.total_rejected);
                setCard('cardTotalPending', data.total_pending);
                setCard('cardTotalDecisions', data.total_decisions);
            }
            if (pagination) {
                if (data.last_page > 1) {
                    let html = buildPagination(data, 'goToApprovedPage');
                    pagination.innerHTML = html;
                    pagination.classList.remove('hidden');
                } else { pagination.innerHTML = ''; }
            }
            if (window.lucide) lucide.createIcons();
        })
        .catch(err => {
            console.error(err);
            if (tbody) tbody.style.opacity = '1';
        });
    }

    function buildPagination(data, fnName) {
        const current = Number(data.current_page || 1);
        const last = Number(data.last_page || 1);
        const windowSize = 5;
        const half = Math.floor(windowSize / 2);
        let start = Math.max(1, current - half);
        let end = Math.min(last, start + windowSize - 1);
        start = Math.max(1, end - windowSize + 1);

        let html = '<nav class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-slate-600">Showing <span class="font-medium text-slate-900">' + data.from + '</span> to <span class="font-medium text-slate-900">' + data.to + '</span> of <span class="font-medium text-slate-900">' + data.total + '</span> results</p><ul class="inline-flex items-center gap-1">';

        const prevDisabled = current <= 1;
        html += '<li>' + (prevDisabled
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-300">&laquo;</span>'
            : '<button type="button" onclick="' + fnName + '(' + (current - 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&laquo;</button>') + '</li>';

        for (let i = start; i <= end; i++) {
            if (i === current) {
                html += '<li><span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white">' + i + '</span></li>';
            } else {
                html += '<li><button type="button" onclick="' + fnName + '(' + i + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">' + i + '</button></li>';
            }
        }

        const nextDisabled = current >= last;
        html += '<li>' + (nextDisabled
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-300">&raquo;</span>'
            : '<button type="button" onclick="' + fnName + '(' + (current + 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&raquo;</button>') + '</li>';

        html += '</ul></nav>';
        return html;
    }

    function goToApprovedPage(page) { fetchApprovedData(page, currentFilter); }

    // Filter button click handlers
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const newFilter = this.getAttribute('data-filter');
            currentFilter = newFilter;
            updateFilterButtons(newFilter);
            fetchApprovedData(1, newFilter);
            
            // Update URL without reload
            const url = new URL(window.location);
            if (newFilter === 'all') {
                url.searchParams.delete('filter');
            } else {
                url.searchParams.set('filter', newFilter);
            }
            window.history.pushState({}, '', url);
        });
    });

    // Clear button handler
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            currentFilter = 'all';
            updateFilterButtons('all');
            fetchApprovedData(1, 'all');
            
            const url = new URL(window.location);
            url.searchParams.delete('search');
            url.searchParams.delete('filter');
            window.history.pushState({}, '', url);
        });
    }

    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchApprovedData(1, currentFilter), 300);
        });
    }

    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);
        const filter = params.get('filter') || 'all';
        currentFilter = filter;
        updateFilterButtons(filter);
        fetchApprovedData(1, filter);
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
        updateFilterButtons(currentFilter);
        const track = document.getElementById('approvedFilterSlider');
        if (track && typeof window.pmUpdateSegControl === 'function') {
            window.pmUpdateSegControl(track, currentFilter, false);
        }

        const counters = document.querySelectorAll('.count-up');
        counters.forEach(el => {
            const target = parseInt(el.dataset.target || el.textContent || '0', 10);
            if (target === 0) return;
            let current = 0;
            const step = Math.max(1, Math.floor(target / 30));
            const interval = setInterval(() => {
                current += step;
                if (current >= target) { current = target; clearInterval(interval); }
                el.textContent = current;
            }, 30);
        });
    });
</script>

@endsection