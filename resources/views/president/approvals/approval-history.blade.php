@extends('layouts.president-layout')

@section('title', 'Approval History')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <p class="text-sm leading-6 text-gray-500">
            View past RIS decisions — both approved and rejected.
        </p>
    </div>
</div>

{{-- ============================== --}}
{{-- SEARCH --}}
{{-- ============================== --}}
<div class="mt-6 slide-up" style="animation-delay: 0.05s">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[220px]">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                id="historySearch"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by ID, Reference No., or Purpose..."
                class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200"
                autocomplete="off"
            />
        </div>
    </div>
</div>

{{-- History table --}}
<div class="mt-4 grid grid-cols-1 gap-4">
    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.1s">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Decision Records</h2>
                <p class="mt-1 text-xs text-gray-500">A timeline of approval decisions made by the President.</p>
            </div>

            <span id="historyCount" class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800 border border-slate-200">
                {{ $approvalHistoryRecords->total() }} total
            </span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS ID</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference No.</th>
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

        {{-- Pagination --}}
        @if ($approvalHistoryRecords->hasPages())
            <div id="historyPagination" class="mt-4 border-t border-gray-100 pt-4">
                {{ $approvalHistoryRecords->links('pagination.president') }}
            </div>
        @endif
    </section>
</div>

<div id="risViewModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center bg-black/80 backdrop-blur-md p-4 sm:p-8 modal-overlay" onclick="closeRisViewModal()">
        <div class="relative w-full h-full flex items-center justify-center" onclick="event.stopPropagation()">
            <div class="relative" style="transform-origin: center center;">
                <iframe id="risViewIframe" class="bg-white shadow-2xl" style="width: 11in; height: 8.5in; min-width: 800px; max-width: 100%; border: 1px solid #e5e7eb;" src="about:blank"></iframe>
            </div>
            <div class="fixed top-4 right-4 z-10 flex items-center gap-2">
                <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-lg bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="printRis()" data-tip="Print RIS" aria-label="Print RIS">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span class="ml-1.5">Print</span>
                </button>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisViewModal()" data-tip="Close" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #risViewModal .modal-content {
        transform: scale(0.95);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    #risViewModal:not(.hidden) .modal-content {
        transform: scale(1);
        opacity: 1;
    }
</style>

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
    function openRisViewModal(risId) {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (!modal || !iframe) return;
        iframe.src = `/president/ris/${risId}/print?ts=${Date.now()}`;
        modal.classList.remove('hidden');
        scaleRisToFit();
    }

    function closeRisViewModal() {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }

    function printRis() {
        const iframe = document.getElementById('risViewIframe');
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            return;
        }
    }

    window.printRisDocument = function (risId) {
        if (!risId) return;
        const win = window.open('/president/ris/' + risId + '/print', '_blank', 'noopener,noreferrer,width=1200,height=860');
        if (!win) return;
        const triggerPrint = function () {
            try { win.focus(); win.print(); } catch (e) {}
        };
        win.onload = triggerPrint;
        setTimeout(triggerPrint, 1200);
    };

    function scaleRisToFit() {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (!modal || !iframe) return;

        const viewportWidth = window.innerWidth - 80;
        const viewportHeight = window.innerHeight - 80;
        const documentWidth = 11 * 96;
        const documentHeight = 8.5 * 96;

        const scaleX = viewportWidth / documentWidth;
        const scaleY = viewportHeight / documentHeight;
        const scale = Math.min(scaleX, scaleY, 1);

        const scaledWidth = documentWidth * scale;
        const scaledHeight = documentHeight * scale;

        iframe.style.width = scaledWidth + 'px';
        iframe.style.height = scaledHeight + 'px';
        iframe.style.minWidth = 'auto';
        iframe.style.maxWidth = '100%';
    }

    window.addEventListener('resize', () => {
        const modal = document.getElementById('risViewModal');
        if (modal && !modal.classList.contains('hidden')) {
            scaleRisToFit();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const risModal = document.getElementById('risViewModal');
            if (risModal && !risModal.classList.contains('hidden')) {
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
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300">&laquo;</span>'
            : '<button type="button" onclick="goToPage(' + (current - 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&laquo;</button>') + '</li>';

        for (let i = start; i <= end; i++) {
            if (i === current) {
                html += '<li><span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">' + i + '</span></li>';
            } else {
                html += '<li><button type="button" onclick="goToPage(' + i + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">' + i + '</button></li>';
            }
        }

        const nextDisabled = current >= last;
        html += '<li>' + (nextDisabled
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300">&raquo;</span>'
            : '<button type="button" onclick="goToPage(' + (current + 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&raquo;</button>') + '</li>';

        html += '</ul></nav>';
        return html;
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