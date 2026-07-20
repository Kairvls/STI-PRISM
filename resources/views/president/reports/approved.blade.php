@extends('layouts.president-layout')

@section('title', 'RIS Status')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">RIS Status</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">
            View all RIS records marked as Approved, Rejected, or Pending.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="/president/reports/monthly-summary" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-900 transition hover:bg-gray-50 active:scale-95">
            <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
            Monthly Summary
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7 slide-up" style="animation-delay: 0.05s">
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs font-semibold text-emerald-700">Approved Today</p>
        <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $approvedToday ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
        <p class="text-xs font-semibold text-rose-700">Rejected Today</p>
        <p class="mt-1 text-2xl font-bold text-rose-900">{{ $rejectedToday ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-xs font-semibold text-amber-700">Archived Today</p>
        <p class="mt-1 text-2xl font-bold text-amber-900">{{ $archivedToday ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs font-semibold text-emerald-700">Total Approved</p>
        <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $totalApproved ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
        <p class="text-xs font-semibold text-rose-700">Total Rejected</p>
        <p class="mt-1 text-2xl font-bold text-rose-900">{{ $totalRejected ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-xs font-semibold text-amber-700">Pending Today</p>
        <p class="mt-1 text-2xl font-bold text-amber-900">{{ $pendingToday ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-semibold text-gray-700">Total Decisions</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalDecisions ?? 0 }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="mt-6 slide-up" style="animation-delay: 0.08s">
    <div class="flex flex-wrap items-center gap-3">
        <a href="/president/reports/approved?filter=approved" class="inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition active:scale-95 {{ ($filter ?? 'approved') === 'approved' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50' }}">
            <i data-lucide="badge-check" class="h-4 w-4"></i>
            RIS Approvals
        </a>
        <a href="/president/reports/approved?filter=rejected" class="inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition active:scale-95 {{ ($filter ?? 'approved') === 'rejected' ? 'border-rose-600 bg-rose-600 text-white' : 'border-rose-200 bg-white text-rose-700 hover:bg-rose-50' }}">
            <i data-lucide="x-circle" class="h-4 w-4"></i>
            RIS Rejections
        </a>
        <a href="/president/reports/approved?filter=pending" class="inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition active:scale-95 {{ ($filter ?? 'approved') === 'pending' ? 'border-amber-600 bg-amber-600 text-white' : 'border-amber-200 bg-white text-amber-700 hover:bg-amber-50' }}">
            <i data-lucide="clock" class="h-4 w-4"></i>
            Pending RIS
        </a>
    </div>
</div>

{{-- Search --}}
<div class="mt-4 slide-up" style="animation-delay: 0.1s">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[220px]">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                id="approvedSearch"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by Reference No. or Purpose..."
                class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-amber-100 transition-all duration-200"
                autocomplete="off"
            />
        </div>
    </div>
</div>

{{-- Table --}}
<div class="mt-4 grid grid-cols-1 gap-4">
    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.15s">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">{{ ($filter ?? 'approved') === 'approved' ? 'Approved decision list' : (($filter ?? 'approved') === 'rejected' ? 'Rejected decision list' : 'Pending decision list') }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ ($filter ?? 'approved') === 'approved' ? 'RIS records approved by the President.' : (($filter ?? 'approved') === 'rejected' ? 'RIS records rejected by the President.' : 'RIS records pending President\'s decision.') }}</p>
            </div>
            <span id="approvedCount" class="inline-flex items-center rounded-lg {{ ($filter ?? 'approved') === 'approved' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : (($filter ?? 'approved') === 'rejected' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-amber-50 text-amber-800 border-amber-200') }} px-3 py-1 text-xs font-semibold border">
                {{ $outcomeRecords->total() }} total
            </span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference No.</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Amount</th>
                        <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Remarks</th>
                        <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Action</th>
                    </tr>
                </thead>
                <tbody id="approvedTableBody">
                    @include('president.reports._approved-table', ['approvedOutcomeRecords' => $outcomeRecords])
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($outcomeRecords->hasPages())
            <div id="approvedPagination" class="mt-4 border-t border-gray-100 pt-4">
                {{ $outcomeRecords->links() }}
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
                <button type="button" class="action-btn rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-slate-100 hover:text-slate-950 active:scale-95" onclick="closeRemarksModal()">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- RIS VIEW MODAL --}}
{{-- ============================== --}}
<div id="risViewModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 backdroop-overlay" onclick="closeRisViewModal()">
        <div class="relative flex items-center justify-center" onclick="event.stopPropagation()">
            <div id="risViewContainer" class="relative">
                <iframe id="risViewIframe" class="bg-white shadow-2xl" style="width: 11in; height: 8.5in; border: 1px solid #e5e7eb; transform-origin: center center;" src="about:blank"></iframe>
            </div>
            <div class="fixed top-4 right-4 z-10 flex items-center gap-2">
                <button type="button" class="print-btn inline-flex h-9 items-center justify-center rounded-lg bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="printRis()" title="Print RIS">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span class="ml-1.5">Print</span>
                </button>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisViewModal()" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>

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
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .outcome-row:hover {
        background-color: rgba(254, 252, 232, 0.4);
        transform: translateX(2px);
    }

    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:active {
        transform: scale(0.95);
    }

    #approvedTableBody {
        transition: opacity 0.2s ease;
    }

    #approvedTableBody.updating {
        opacity: 0.5;
    }

    .backdroop-overlay {
        animation: overlayIn 0.2s ease-out forwards;
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
    function openRisViewModal(risId) {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (!modal || !iframe) return;
        iframe.src = `/president/ris/${risId}/view?ts=${Date.now()}`;
        modal.classList.remove('hidden');
        scaleRisToFit();
    }

    function closeRisViewModal() {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }

    function scaleRisToFit() {
        const iframe = document.getElementById('risViewIframe');
        if (!iframe) return;

        // Document dimensions in inches (landscape)
        const docWidthInches = 11;
        const docHeightInches = 8.5;
        
        // Convert to pixels (96 DPI)
        const docWidthPx = docWidthInches * 96;
        const docHeightPx = docHeightInches * 96;

        // Calculate available viewport (with margins)
        const viewportWidth = window.innerWidth - 64;
        const viewportHeight = window.innerHeight - 64;

        // Calculate scale to fit
        const scaleX = viewportWidth / docWidthPx;
        const scaleY = viewportHeight / docHeightPx;
        const scale = Math.min(scaleX, scaleY, 1);

        // Apply CSS transform to the iframe
        iframe.style.transform = `scale(${scale})`;
        iframe.style.width = docWidthPx + 'px';
        iframe.style.height = docHeightPx + 'px';
    }

    window.addEventListener('resize', function() {
        const modal = document.getElementById('risViewModal');
        if (modal && !modal.classList.contains('hidden')) {
            scaleRisToFit();
        }
    });

    function printRis() {
        const iframe = document.getElementById('risViewIframe');
        if (!iframe || !iframe.contentWindow) return;
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const risModal = document.getElementById('risViewModal');
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

    // Live search
    let searchTimeout = null;
    const searchInput = document.getElementById('approvedSearch');

    function fetchApprovedData(page) {
        const search = searchInput ? searchInput.value : '';
        page = page || 1;

        const tbody = document.getElementById('approvedTableBody');
        const pagination = document.getElementById('approvedPagination');
        const totalSpan = document.getElementById('approvedCount');

        if (tbody) tbody.classList.add('updating');

        const params = new URLSearchParams();
        if (search) params.set('search', search);
        params.set('page', page);
        const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'approved';
        params.set('filter', currentFilter);

        fetch(`/president/reports/approved?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (tbody) { tbody.innerHTML = data.table_html; tbody.classList.remove('updating'); }
            if (totalSpan) totalSpan.textContent = data.total + ' total';
            if (pagination) {
                if (data.last_page > 1) {
                    let html = buildPagination(data, 'goToApprovedPage');
                    pagination.innerHTML = html;
                    pagination.classList.remove('hidden');
                } else { pagination.innerHTML = ''; }
            }
            if (window.lucide) lucide.createIcons();
        })
        .catch(err => { console.error(err); if (tbody) tbody.classList.remove('updating'); });
    }

    function buildPagination(data, fnName) {
        let html = '<nav class="flex items-center justify-between"><div class="text-sm text-gray-500">Showing ' + data.from + ' to ' + data.to + ' of ' + data.total + ' results</div><ul class="flex items-center gap-1">';
        const prevDisabled = data.current_page <= 1;
        html += '<li class="' + (prevDisabled ? 'opacity-50 pointer-events-none' : '') + '"><button onclick="' + fnName + '(' + (data.current_page - 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50">&laquo;</button></li>';
        for (let i = 1; i <= data.last_page; i++) {
            if (i === data.current_page) {
                html += '<li><span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-sm font-semibold text-white">' + i + '</span></li>';
            } else {
                html += '<li><button onclick="' + fnName + '(' + i + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50">' + i + '</button></li>';
            }
        }
        const nextDisabled = data.current_page >= data.last_page;
        html += '<li class="' + (nextDisabled ? 'opacity-50 pointer-events-none' : '') + '"><button onclick="' + fnName + '(' + (data.current_page + 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50">&raquo;</button></li>';
        html += '</ul></nav>';
        return html;
    }

    function goToApprovedPage(page) { fetchApprovedData(page); }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchApprovedData(1), 300);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();
    });
</script>

@endsection