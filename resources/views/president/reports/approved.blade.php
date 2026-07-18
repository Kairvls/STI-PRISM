@extends('layouts.president-layout')

@section('title', 'Approved Outcomes')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Approved Outcomes</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">
            View all procurement decisions marked as Approved by the President.
        </p>
    </div>

    <div class="flex items-center gap-2">
            <a href="/president/reports/rejected" class="inline-flex h-10 items-center justify-center rounded-lg border border-rose-200 bg-white px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 active:scale-95">
                <i data-lucide="x-circle" class="h-4 w-4"></i>
                Rejected
            </a>

            <a href="/president/reports/approved" class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700 active:scale-95">
                <i data-lucide="badge-check" class="h-4 w-4"></i>
                Approved
            </a>

            <a href="/president/reports/monthly-summary" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-900 transition hover:bg-gray-50 active:scale-95">
                <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                Monthly Summary
            </a>
        </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Filters/Stats --}}
    <section class="lg:col-span-1 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.05s">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Refine results</h2>
                <p class="mt-1 text-xs text-gray-500">Use filters to find a specific approval.</p>
            </div>
            <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">
                Approved
            </span>
        </div>

        <div class="mt-4 flex flex-col gap-2">
            <button type="button" class="outcome-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50 active:scale-[0.98]" data-filter="all">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="layers" class="h-4 w-4 text-gray-600"></i>
                    All outcomes
                </span>
            </button>

            <button type="button" class="outcome-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50 active:scale-[0.98]" data-filter="ris">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="h-4 w-4 text-emerald-600"></i>
                    RIS approvals
                </span>
            </button>

            <button type="button" class="outcome-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50 active:scale-[0.98]" data-filter="procurement">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="file-text" class="h-4 w-4 text-emerald-600"></i>
                    Procurement approvals
                </span>
            </button>
        </div>
    </section>

    {{-- Table --}}
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.1s">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Approved decision list</h2>
                <p class="mt-1 text-xs text-gray-500">A timeline of approvals (RIS / Procurement).</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-semibold text-gray-600">Showing</p>
                <p id="outcomeCount" class="text-sm font-bold text-gray-900">0</p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-3 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Remarks</th>
                        <th class="px-3 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Action</th>
                    </tr>
                </thead>
                <tbody id="outcomeTableBody">

                    @isset($approvedOutcomeRecords)
                        @forelse($approvedOutcomeRecords as $row)
                            @php
                                $reference = $row->ris_form_number ?? ('RIS-' . date('Y') . '-' . str_pad($row->ris_id, 5, '0', STR_PAD_LEFT));
                                $approvedAt = $row->decided_at ?? ($row->ris_created_at ?? null);
                                $remarks = $row->remarks ?? null;
                                $searchBlob = strtolower((string)($reference.' '.($remarks ?? '')));
                                $dataTypeFilter = 'ris';
                            @endphp

                            <tr class="border-b border-gray-100 outcome-row slide-up"
                                data-type="{{ $dataTypeFilter }}"
                                data-search="{{ $searchBlob }}"
                                style="animation-delay: {{ $loop->index * 0.03 }}s"
                            >
                                <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $reference }}</td>
                                <td class="px-3 py-4 text-sm text-gray-700">
                                    {{ $approvedAt ? (is_object($approvedAt) ? $approvedAt->format('Y-m-d') : date('Y-m-d', strtotime((string)$approvedAt))) : '—' }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700">{{ $remarks ?: '—' }}</td>
                                <td class="px-3 py-4 text-center">
                                    <button type="button" class="action-btn inline-flex h-8 items-center justify-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95" title="View approved RIS form" onclick="openRisViewModal({{ $row->ris_id }})">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                        <span class="ml-1.5">View</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-2 py-12 text-center">
                                    <p class="text-sm font-semibold text-gray-800">No approved outcomes found.</p>
                                    <p class="mt-1 text-xs text-gray-500">Approved RIS records will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="4" class="px-2 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-800">No data available.</p>
                                <p class="mt-1 text-xs text-gray-500">Approved outcomes will appear here once decisions are made.</p>
                            </td>
                        </tr>
                    @endisset

                </tbody>
            </table>
        </div>
    </section>

</div>

{{-- ============================== --}}
{{-- RIS VIEW MODAL --}}
{{-- ============================== --}}
<div id="risViewModal" class="fixed inset-0 z-50 hidden">
    <div class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px] modal-overlay" onclick="closeRisViewModal()">
        <div class="w-full max-w-6xl h-[calc(100vh-1rem)] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] modal-content" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">RIS Form</h3>
                        <p id="risViewTitle" class="mt-1 text-sm text-slate-600">Approved RIS</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="window.print()" title="Print RIS">
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            <span class="ml-1.5">Print</span>
                        </button>
                        <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisViewModal()" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="h-full">
                <div class="h-full w-full overflow-hidden rounded-b-2xl border border-gray-200 bg-gray-50">
                    <iframe id="risViewIframe" class="w-full h-full" style="min-height: calc(100vh - 140px);" src="about:blank"></iframe>
                </div>
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

    .outcome-filter-btn {
        transition: all 0.2s ease;
    }

    .outcome-filter-btn:active {
        transform: scale(0.98);
    }
</style>

<script>
    function openRisViewModal(risId) {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        const title = document.getElementById('risViewTitle');
        if (!modal || !iframe) return;

        iframe.src = `/president/ris/${risId}/print?ts=${Date.now()}`;
        if (title) title.textContent = `RIS #${risId}`;
        modal.classList.remove('hidden');
    }

    function closeRisViewModal() {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }

    function setOutcomeCount() {
        const body = document.getElementById('outcomeTableBody');
        const rows = body ? body.querySelectorAll('.outcome-row') : [];
        const visible = Array.from(rows).filter(r => r.style.display !== 'none');
        document.getElementById('outcomeCount').textContent = visible.length;
    }

    function applyOutcomeFilters() {
        const activeBtn = document.querySelector('.outcome-filter-btn.bg-gray-900');
        const activeType = activeBtn?.dataset?.filter || 'all';

        const body = document.getElementById('outcomeTableBody');
        if (!body) return;

        const rows = body.querySelectorAll('.outcome-row');
        rows.forEach(row => {
            const type = (row.dataset.type || '').toLowerCase();

            const matchesType = (activeType === 'all') || (type === activeType);
            row.style.display = matchesType ? '' : 'none';
        });

        setOutcomeCount();
    }

    (function initOutcomeUI() {
        const filterBtns = document.querySelectorAll('.outcome-filter-btn');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.classList.remove('bg-gray-900', 'text-white', 'hover:text-white');
                    b.classList.add('text-gray-700');
                });

                btn.classList.add('bg-gray-900', 'text-white');
                btn.classList.add('hover:text-white');
                btn.classList.remove('text-gray-700');

                applyOutcomeFilters();
            });
        });

        const defaultBtn = document.querySelector('.outcome-filter-btn[data-filter="all"]');
        if (defaultBtn) defaultBtn.click();

        setOutcomeCount();
    })();

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

@endsection
