@extends('layouts.president-layout')

@section('title', 'Decisions')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Decisions</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">View all approved and rejected RIS decisions.</p>
    </div>
</div>

{{-- Summary Cards --}}
<div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
    <div class="rounded-xl border border-gray-200 bg-white p-4 card-hover slide-up" style="animation-delay: 0.05s">
        <p class="text-xs font-medium text-gray-500">Approved Today</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $approvedToday ?? 0 }}">{{ $approvedToday ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 card-hover slide-up" style="animation-delay: 0.1s">
        <p class="text-xs font-medium text-gray-500">Rejected Today</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $rejectedToday ?? 0 }}">{{ $rejectedToday ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 card-hover slide-up" style="animation-delay: 0.15s">
        <p class="text-xs font-medium text-gray-500">Archived Today</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ ($approvedToday ?? 0) + ($rejectedToday ?? 0) }}">{{ ($approvedToday ?? 0) + ($rejectedToday ?? 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 card-hover slide-up" style="animation-delay: 0.2s">
        <p class="text-xs font-medium text-gray-500">Total Approved</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalApproved ?? 0 }}">{{ $totalApproved ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 card-hover slide-up" style="animation-delay: 0.25s">
        <p class="text-xs font-medium text-gray-500">Total Rejected</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalRejected ?? 0 }}">{{ $totalRejected ?? 0 }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 card-hover slide-up" style="animation-delay: 0.3s">
        <p class="text-xs font-medium text-gray-500">Total Decisions</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 count-up" data-target="{{ $totalDecisions ?? 0 }}">{{ $totalDecisions ?? 0 }}</p>
    </div>
</div>

{{-- Search & Filters --}}
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.35s">
    <form method="GET" action="/president/reports/decisions" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input type="search" name="search" value="{{ $search ?? '' }}" placeholder="Search by reference, description..." class="w-64 rounded-lg border border-gray-200 bg-white pl-9 pr-4 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200" />
            </div>
            @php
                $decisionFilters = [
                    'all' => 'All',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                ];
                $activeDecisionFilter = $filter ?? 'all';
            @endphp
            <div
                id="decisionsFilterSlider"
                class="pm-seg"
                role="tablist"
                aria-label="Decision filters"
                data-active="{{ $activeDecisionFilter }}"
            >
                <span class="pm-seg-thumb" aria-hidden="true"></span>
                @foreach ($decisionFilters as $key => $label)
                    <button
                        type="submit"
                        name="filter"
                        value="{{ $key }}"
                        role="tab"
                        class="pm-seg-btn {{ $activeDecisionFilter === $key ? 'is-active' : '' }}"
                        data-filter="{{ $key }}"
                        aria-selected="{{ $activeDecisionFilter === $key ? 'true' : 'false' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @if ($search || ($filter ?? 'all') !== 'all')
                <a href="/president/reports/decisions" class="action-btn inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 active:scale-95">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Decisions Table --}}
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.4s">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Decision List</h2>
            <p class="mt-1 text-xs text-gray-500">A timeline of approved and rejected RIS decisions.</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-semibold text-gray-600">Showing</p>
            <p id="decisionCount" class="text-sm font-bold text-gray-900">{{ $records->total() }}</p>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table id="decisionTable" class="min-w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference No.</th>
                    <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Status</th>
                    <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Amount</th>
                    <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Decision Date</th>
                    <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Actions</th>
                </tr>
            </thead>
            <tbody id="decisionTableBody">
                @forelse ($records as $row)
                    @php
                        $reference = $row->ris_form_number ?? ('RIS-' . date('Y') . '-' . str_pad($row->ris_id, 5, '0', STR_PAD_LEFT));
                        $totalAmount = number_format((float)($row->total_amount ?? 0), 2);
                        $decisionDate = $row->decided_at ?? $row->ris_created_at ?? null;
                        $formattedDate = $decisionDate ? date('F d, Y', strtotime($decisionDate)) : '—';
                        $statusLower = strtolower($row->ris_status ?? '');
                        $statusBadge = $statusLower === 'approved'
                            ? 'bg-blue-50 text-blue-800 border-blue-200'
                            : 'bg-slate-100 text-slate-800 border-slate-200';
                        $remarks = $row->remarks ?? null;
                    @endphp
                    <tr class="border-b border-gray-100 decision-row transition-all duration-200 slide-up" style="animation-delay: {{ $loop->index * 0.03 }}s">
                        <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $reference }}</td>
                        <td class="px-3 py-4 text-sm">
                            <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-semibold border {{ $statusBadge }}">{{ $row->ris_status ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-700">₱{{ $totalAmount }}</td>
                        <td class="px-3 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $formattedDate }}</td>
                        <td class="px-3 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-xl bg-white px-3 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95" title="View RIS form" onclick="openRisViewModal({{ $row->ris_id }})">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                    <span class="ml-1.5">View</span>
                                </button>
                                @if ($remarks)
                                    <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-xl bg-white px-3 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95" title="View remarks" onclick="openRemarksModal('{{ addslashes($remarks) }}')">
                                        <i data-lucide="message-square" class="h-4 w-4"></i>
                                        <span class="ml-1.5">Remarks</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-2 py-12 text-center fade-in">
                            <p class="text-sm font-semibold text-gray-800">No decision records found.</p>
                            <p class="mt-1 text-xs text-gray-500">Approved and rejected RIS records will appear here.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('president.partials.table-word-export', [
        'target' => '#decisionTable',
        'filename' => 'president-decisions',
    ])

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $records->links('pagination.president') }}
    </div>
</div>

{{-- ============================== --}}
{{-- RIS VIEW MODAL --}}
{{-- ============================== --}}
<div id="risViewModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center bg-black/70 backdrop-blur-sm p-4 sm:p-8 modal-overlay" onclick="closeRisViewModal()">
        <div class="relative w-full max-w-5xl max-h-[90vh] bg-white shadow-2xl modal-content" onclick="event.stopPropagation()">
            <div class="absolute top-4 right-4 z-10 flex items-center gap-2">
                <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-xl bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="window.print()" title="Print RIS">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span class="ml-1.5">Print</span>
                </button>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisViewModal()" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <div class="overflow-auto p-4" style="max-height: 90vh;">
                <iframe id="risViewIframe" class="w-full bg-white" style="height: 75vh; min-height: 600px;" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- REMARKS MODAL --}}
{{-- ============================== --}}
<div id="remarksModal" class="fixed inset-0 z-50 hidden">
    <div class="flex h-screen items-center justify-center bg-black/40 backdrop-blur-sm p-4 sm:p-8 modal-overlay" onclick="closeRemarksModal()">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] modal-content" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Remarks</h3>
                        <p class="mt-1 text-sm text-slate-600">Decision remarks for this RIS.</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRemarksModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>
            <div class="px-6 py-5">
                <p id="remarksText" class="text-sm text-gray-700 whitespace-pre-wrap"></p>
            </div>
            <div class="border-t border-gray-100 px-6 py-4">
                <button type="button" class="action-btn h-10 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-gray-800 active:scale-95" onclick="closeRemarksModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    @keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }
    .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    .slide-up { opacity: 0; animation: slideUp 0.5s ease-out forwards; }
    .modal-overlay { animation: overlayIn 0.2s ease-out forwards; }
    .modal-content { animation: modalIn 0.25s ease-out forwards; }
    .decision-row { transition: background-color 0.2s ease, transform 0.2s ease; }
    .decision-row:hover { background-color: rgba(254, 252, 232, 0.4); transform: translateX(2px); }
    .action-btn { transition: all 0.2s ease; }
    .action-btn:active { transform: scale(0.95); }
    .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .card-hover:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06); }
    .count-up { display: inline-block; }
</style>

<script>
    function openRisViewModal(risId) {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (!modal || !iframe) return;
        iframe.src = `/president/ris/${risId}/print?ts=${Date.now()}`;
        modal.classList.remove('hidden');
    }

    function closeRisViewModal() {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }

    function openRemarksModal(remarks) {
        const modal = document.getElementById('remarksModal');
        const text = document.getElementById('remarksText');
        if (text) text.textContent = remarks;
        if (modal) modal.classList.remove('hidden');
    }

    function closeRemarksModal() {
        const modal = document.getElementById('remarksModal');
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) { lucide.createIcons(); }
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
