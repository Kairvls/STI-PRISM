@extends('layouts.president-layout')

@section('title', 'Decision Reports')

@section('content')

@php
    $period = $period ?? 'all';
    $status = $status ?? 'all';
    $dateFilter = $dateFilter ?? null;
    $summary = $summary ?? [
        'approved_count' => 0,
        'rejected_count' => 0,
        'approved_amount' => 0,
        'rejected_amount' => 0,
        'period_label' => 'All time',
    ];
    $periods = [
        'all' => 'All time',
        'today' => 'Today',
        'week' => 'This week',
        'month' => 'This month',
        'year' => 'This year',
    ];
    $statuses = [
        'all' => 'All',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];
@endphp

<div class="grid grid-cols-1 gap-4">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="pm-kpi-card">
            <p class="text-xs font-medium text-slate-500">Approved · <span id="summaryPeriodLabel">{{ $summary['period_label'] }}</span></p>
            <p class="mt-2 text-2xl font-semibold text-slate-900" id="summaryApprovedCount">{{ number_format($summary['approved_count']) }}</p>
        </div>
        <div class="pm-kpi-card">
            <p class="text-xs font-medium text-slate-500">Rejected</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900" id="summaryRejectedCount">{{ number_format($summary['rejected_count']) }}</p>
        </div>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.05s">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900">Decision Records</h2>
                <p class="mt-1 text-xs text-gray-500">Monitor RIS decisions for the selected period.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3 flex-1 justify-end min-w-0">
                <div class="pm-seg" role="tablist" aria-label="Period filters" id="periodFilter" data-active="{{ $period }}">
                    <span class="pm-seg-thumb" aria-hidden="true"></span>
                    @foreach ($periods as $key => $label)
                        <button
                            type="button"
                            role="tab"
                            class="pm-seg-btn period-filter-btn {{ $period === $key && empty($dateFilter) ? 'is-active' : '' }}"
                            data-period="{{ $key }}"
                            aria-selected="{{ $period === $key && empty($dateFilter) ? 'true' : 'false' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <div class="pm-seg" role="tablist" aria-label="Status filters" id="statusFilter" data-active="{{ $status }}">
                    <span class="pm-seg-thumb" aria-hidden="true"></span>
                    @foreach ($statuses as $key => $label)
                        <button
                            type="button"
                            role="tab"
                            class="pm-seg-btn status-filter-btn {{ $status === $key ? 'is-active' : '' }}"
                            data-status="{{ $key }}"
                            aria-selected="{{ $status === $key ? 'true' : 'false' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <div class="date-filter-wrap {{ !empty($dateFilter) ? 'is-active' : '' }}">
                    <button
                        type="button"
                        id="historyDateBtn"
                        class="date-filter-btn"
                        data-tip="{{ !empty($dateFilter) ? 'Date '.$dateFilter.' — click to change' : 'Filter by date' }}"
                        aria-label="Filter decision records by date"
                    >
                        <i data-lucide="calendar" class="h-4 w-4"></i>
                    </button>
                    <input
                        type="date"
                        id="historyDateInput"
                        class="date-filter-input"
                        value="{{ $dateFilter ?? '' }}"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                    @if(!empty($dateFilter))
                        <button
                            type="button"
                            id="historyDateClear"
                            class="date-filter-clear"
                            data-tip="Clear date filter"
                            aria-label="Clear date filter"
                        >×</button>
                    @endif
                </div>

                <div class="relative w-full min-w-[220px] max-w-md">
                    <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="text"
                        id="historySearch"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by RIS Number, Request No., or Purpose..."
                        class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200"
                        autocomplete="off"
                    />
                </div>

                <span id="historyCount" class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800 border border-slate-200 shrink-0">
                    {{ $approvalHistoryRecords->total() }} total
                </span>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table id="historyTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS Number</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Request No.</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Decision</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Actions</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    @include('president.approvals._history-table', ['approvalHistoryRecords' => $approvalHistoryRecords])
                </tbody>
            </table>
        </div>
        @include('president.partials.table-word-export', [
            'target' => '#historyTable',
            'filename' => 'president-decision-reports',
        ])

        @if ($approvalHistoryRecords->hasPages())
            <div id="historyPagination" class="mt-4 border-t border-gray-100 pt-4">
                {{ $approvalHistoryRecords->links('pagination.president') }}
            </div>
        @else
            <div id="historyPagination" class="mt-4 border-t border-gray-100 pt-4 hidden"></div>
        @endif
    </section>
</div>

@include('president.partials.ris-readonly-modal')
@include('president.partials.ris-fit-viewer')

<style>
    .slide-up { opacity: 0; animation: slideUp 0.5s ease-out forwards; }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .history-row { transition: background-color 0.2s ease; }
    .history-row:hover { background-color: rgba(254, 252, 232, 0.4); }
    #historyTableBody.updating { opacity: 0.5; }
</style>

<script>
(function () {
    let searchTimeout = null;
    let currentPeriod = @json($period);
    let currentStatus = @json($status);
    let currentDate = @json($dateFilter ?? '');
    const searchInput = document.getElementById('historySearch');
    const dateInput = document.getElementById('historyDateInput');
    const dateBtn = document.getElementById('historyDateBtn');

    function money(value) {
        return '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function openNativeDatePicker(input) {
        if (!input) return;
        try {
            if (typeof input.showPicker === 'function') input.showPicker();
            else { input.focus(); input.click(); }
        } catch (e) {
            input.focus();
        }
    }

    function syncDateFilterChrome(wrapEl, value) {
        if (!wrapEl) return;
        const btn = wrapEl.querySelector('.date-filter-btn');
        const hasValue = !!value;
        wrapEl.classList.toggle('is-active', hasValue);
        if (btn) {
            btn.setAttribute('data-tip', hasValue ? ('Date ' + value + ' — click to change') : 'Filter by date');
        }
        let clearBtn = wrapEl.querySelector('.date-filter-clear');
        if (hasValue && !clearBtn) {
            clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'date-filter-clear';
            clearBtn.id = 'historyDateClear';
            clearBtn.textContent = '×';
            clearBtn.setAttribute('data-tip', 'Clear date filter');
            clearBtn.setAttribute('aria-label', 'Clear date filter');
            wrapEl.appendChild(clearBtn);
        } else if (!hasValue && clearBtn) {
            clearBtn.remove();
        }
    }

    function syncSeg(rootId, activeValue, attr) {
        const root = document.getElementById(rootId);
        if (!root) return;
        root.setAttribute('data-active', activeValue || '');
        root.querySelectorAll('.pm-seg-btn').forEach(function (btn) {
            const on = !!activeValue && btn.getAttribute(attr) === activeValue;
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }

    function updateSummary(summary) {
        if (!summary) return;
        const label = document.getElementById('summaryPeriodLabel');
        const approvedCount = document.getElementById('summaryApprovedCount');
        const rejectedCount = document.getElementById('summaryRejectedCount');
        const approvedAmount = document.getElementById('summaryApprovedAmount');
        const rejectedAmount = document.getElementById('summaryRejectedAmount');
        if (label) label.textContent = summary.period_label || '';
        if (approvedCount) approvedCount.textContent = Number(summary.approved_count || 0).toLocaleString();
        if (rejectedCount) rejectedCount.textContent = Number(summary.rejected_count || 0).toLocaleString();
        if (approvedAmount) approvedAmount.textContent = money(summary.approved_amount);
        if (rejectedAmount) rejectedAmount.textContent = money(summary.rejected_amount);
    }

    function fetchHistoryData(page) {
        const search = searchInput ? String(searchInput.value || '').trim() : '';
        page = page || 1;

        const tbody = document.getElementById('historyTableBody');
        const pagination = document.getElementById('historyPagination');
        const totalSpan = document.getElementById('historyCount');

        if (tbody) tbody.classList.add('updating');

        const params = new URLSearchParams();
        params.set('period', currentPeriod || 'all');
        params.set('status', currentStatus);
        if (currentDate) params.set('date', currentDate);
        if (search) params.set('search', search);
        params.set('page', page);

        fetch(`{{ route('president.approvals.history') }}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (tbody) {
                tbody.innerHTML = data.table_html;
                tbody.classList.remove('updating');
            }
            if (totalSpan) totalSpan.textContent = data.total + ' total';
            updateSummary(data.summary);
            if (pagination) {
                if (data.last_page > 1) {
                    pagination.innerHTML = window.buildPageCarouselHtml
                        ? window.buildPageCarouselHtml(data, 'goToPage')
                        : '';
                    pagination.classList.remove('hidden');
                    if (typeof window.bindPageCarousels === 'function') window.bindPageCarousels();
                } else {
                    pagination.innerHTML = '';
                    pagination.classList.add('hidden');
                }
            }
            const url = new URL(window.location.href);
            url.searchParams.set('period', currentPeriod || 'all');
            url.searchParams.set('status', currentStatus);
            if (currentDate) url.searchParams.set('date', currentDate);
            else url.searchParams.delete('date');
            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');
            if (Number(data.current_page) > 1) url.searchParams.set('page', String(data.current_page));
            else url.searchParams.delete('page');
            window.history.replaceState({}, '', url);
            if (window.lucide) lucide.createIcons();
        })
        .catch(() => {
            if (tbody) tbody.classList.remove('updating');
        });
    }

    window.goToPage = function (page) {
        fetchHistoryData(page);
    };

    document.querySelectorAll('.period-filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentPeriod = btn.getAttribute('data-period') || 'all';
            currentDate = '';
            if (dateInput) dateInput.value = '';
            syncDateFilterChrome(document.querySelector('.date-filter-wrap'), '');
            syncSeg('periodFilter', currentPeriod, 'data-period');
            fetchHistoryData(1);
        });
    });

    document.querySelectorAll('.status-filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentStatus = btn.getAttribute('data-status') || 'all';
            syncSeg('statusFilter', currentStatus, 'data-status');
            fetchHistoryData(1);
        });
    });

    if (dateBtn && dateInput) {
        dateBtn.addEventListener('click', function () {
            openNativeDatePicker(dateInput);
        });
        dateInput.addEventListener('change', function () {
            currentDate = String(dateInput.value || '');
            syncDateFilterChrome(dateInput.closest('.date-filter-wrap'), currentDate);
            if (currentDate) {
                syncSeg('periodFilter', '', 'data-period');
            } else {
                syncSeg('periodFilter', currentPeriod || 'all', 'data-period');
            }
            fetchHistoryData(1);
        });
    }

    document.addEventListener('click', function (event) {
        const clearBtn = event.target.closest('#historyDateClear, .date-filter-clear');
        if (!clearBtn || !clearBtn.closest('.date-filter-wrap')) return;
        currentDate = '';
        if (dateInput) dateInput.value = '';
        syncDateFilterChrome(document.querySelector('.date-filter-wrap'), '');
        syncSeg('periodFilter', currentPeriod || 'all', 'data-period');
        fetchHistoryData(1);
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                fetchHistoryData(1);
            }, 300);
        });
        searchInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            clearTimeout(searchTimeout);
            fetchHistoryData(1);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        const risModal = document.getElementById('historyRisModal');
        if (risModal && !risModal.classList.contains('hidden')) {
            if (risModal.dataset.fullscreen === '1') return;
            if (typeof closeRisViewModal === 'function') closeRisViewModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        if (currentDate) syncSeg('periodFilter', '', 'data-period');
    });
})();
</script>

@endsection
