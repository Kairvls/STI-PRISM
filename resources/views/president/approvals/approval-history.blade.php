@extends('layouts.president-layout')

@section('title', 'Approval History')

@section('content')

{{-- History table --}}
<div class="grid grid-cols-1 gap-4">
    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.05s">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-gray-900">Decision Records</h2>
                <p class="mt-1 text-xs text-gray-500">A timeline of approval decisions made by the President.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3 flex-1 justify-end min-w-0">
                <div class="relative w-full min-w-[220px] max-w-md">
                    <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="text"
                        id="historySearch"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by ID, RIS Number, or Purpose..."
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
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS ID</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS Number</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Decision</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Amount</th>
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
            'filename' => 'president-approval-history',
        ])

        {{-- Pagination --}}
        @if ($approvalHistoryRecords->hasPages())
            <div id="historyPagination" class="mt-4 border-t border-gray-100 pt-4">
                {{ $approvalHistoryRecords->links('pagination.president') }}
            </div>
        @endif
    </section>
</div>

{{-- Shared fit-to-screen RIS viewer (same as Approved Reports / RIS Approvals) --}}
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

    .fade-in {
        animation: fadeIn 0.4s ease-out forwards;
    }

    .slide-up {
        opacity: 0;
        animation: slideUp 0.5s ease-out forwards;
    }

    .history-row {
        transition: background-color 0.2s ease;
    }

    .history-row:hover {
        background-color: rgba(254, 252, 232, 0.4);
    }

    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:active {
        transform: scale(0.95);
    }

    #historyTableBody {
        transition: opacity 0.2s ease;
    }

    #historyTableBody.updating {
        opacity: 0.5;
    }
</style>

<script>
    // Close modal on Escape key (shared viewer handles fullscreen Escape)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const risModal = document.getElementById('historyRisModal');
            if (risModal && !risModal.classList.contains('hidden')) {
                if (risModal.dataset.fullscreen === '1') return;
                closeRisViewModal();
            }
        }
    });

    // =====================================================
    // LIVE SEARCH (AJAX)
    // =====================================================
    let searchTimeout = null;
    const searchInput = document.getElementById('historySearch');

    function fetchHistoryData(page) {
        const search = searchInput ? searchInput.value : '';
        page = page || 1;

        const tbody = document.getElementById('historyTableBody');
        const pagination = document.getElementById('historyPagination');
        const totalSpan = document.getElementById('historyCount');

        if (tbody) tbody.classList.add('updating');

        const params = new URLSearchParams();
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
            if (totalSpan) {
                totalSpan.textContent = data.total + ' total';
            }
            if (pagination) {
                if (data.last_page > 1) {
                    let html = buildPagination(data);
                    pagination.innerHTML = html;
                    pagination.classList.remove('hidden');
                    if (typeof window.bindPageCarousels === 'function') window.bindPageCarousels();
                } else {
                    pagination.innerHTML = '';
                }
            }
            if (window.lucide) {
                lucide.createIcons();
            }
        })
        .catch(err => {
            console.error('Search error:', err);
            if (tbody) tbody.classList.remove('updating');
        });
    }

    function buildPagination(data) {
        return window.buildPageCarouselHtml
            ? window.buildPageCarouselHtml(data, 'goToPage')
            : '';
    }

    function goToPage(page) {
        fetchHistoryData(page);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                fetchHistoryData(1);
            }, 300);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

@endsection