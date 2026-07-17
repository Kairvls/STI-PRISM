@extends('layouts.president-layout')

@section('title', 'Approval History')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Approval History</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">
            View past RIS/procurement decisions made by the President.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="/president/approvals" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-900 transition hover:bg-gray-50">
            <i data-lucide="clipboard-check" class="h-4 w-4"></i>
            Back to Approvals
        </a>
    </div>
</div>

{{-- Filters + Search (client-side only for now) --}}
<div class="mt-6 rounded-xl border border-gray-200 bg-white p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Filters</h2>
            <p class="mt-1 text-xs text-gray-500">Use these controls to narrow down what you want to view.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex rounded-lg border border-gray-200 bg-white overflow-hidden">
                <button type="button" class="history-filter-btn px-4 py-2 text-xs font-semibold bg-gray-900 text-white" data-filter="all">All</button>
                <button type="button" class="history-filter-btn px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50" data-filter="approved">Approved</button>
                <button type="button" class="history-filter-btn px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50" data-filter="rejected">Rejected</button>
            </div>

            <div class="relative">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input id="historySearch" type="search" placeholder="Search by RIS ID / Form # / Reference..." class="w-full sm:w-80 rounded-lg border border-gray-200 bg-white px-9 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-amber-100" />
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1 font-semibold text-emerald-800 border border-emerald-200">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            Approved
        </span>
        <span class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-3 py-1 font-semibold text-rose-800 border border-rose-200">
            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
            Rejected
        </span>
        <span class="ml-auto inline-flex items-center rounded-lg bg-gray-50 px-3 py-1 font-semibold text-gray-600 border border-gray-200">
            Tip: This page is UI-ready. Backend data will populate the table.
        </span>
    </div>
</div>

{{-- History table --}}
<div class="mt-6 grid grid-cols-1 gap-4">
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Decision Records</h2>
                <p class="mt-1 text-xs text-gray-500">A timeline of approval decisions (RIS / Procurement).</p>
            </div>

            <div class="text-right">
                <p class="text-xs font-semibold text-gray-600">Showing</p>
                <p id="historyCount" class="text-sm font-bold text-gray-900">0</p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Type</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Supplier / Party</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Decision</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Remarks</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Actions</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">

                    {{--
                      Expected future variables (optional):
                        - $approvalHistoryRecords = collection/array of records
                      Each record (suggested shape):
                        - type: 'RIS'|'Procurement'
                        - reference: e.g. 'RIS#123' / 'PR#456'
                        - supplier_name: string
                        - decision: 'Approved'|'Rejected'
                        - remarks: string|null
                        - decided_at: datetime|string
                    --}}

                    @isset($approvalHistoryRecords)
                        @forelse($approvalHistoryRecords as $row)
                            @php
                                $type = $row->type ?? $row->reference_type ?? 'RIS';
                                $reference = $row->reference ?? ($row->ris_id ? 'RIS#'.$row->ris_id : ($row->procurement_request_id ? 'PR#'.$row->procurement_request_id : '—'));
                                $supplier = '—';
                                $decision = $row->decision ?? $row->ris_status ?? 'Approved';
                                $remarks = $row->remarks ?? $row->approval_remarks ?? null;
                                $decidedAt = $row->decided_at ?? $row->ris_approved_by_date ?? null;
                                $decisionLower = is_string($decision) ? strtolower($decision) : '';
                                $risId = $row->ris_id ?? null;
                            @endphp

                            <tr class="border-b border-gray-100 history-row" 
                                data-decision="{{ $decisionLower }}"
                                data-search="{{ strtolower((string)($reference.' '.$supplier.' '.$remarks)) }}">
                                <td class="px-2 py-4 text-sm font-semibold text-gray-600">{{ $type }}</td>
                                <td class="px-2 py-4 text-sm text-gray-700">{{ $reference }}</td>
                                <td class="px-2 py-4 text-sm text-gray-700">{{ $supplier }}</td>
                                <td class="px-2 py-4 text-sm">
                                    @if ($decision === 'Approved' || $decisionLower === 'approved')
                                        <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">Approved</span>
                                    @elseif ($decision === 'Rejected' || $decisionLower === 'rejected')
                                        <span class="inline-flex items-center rounded-lg bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-800 border border-rose-200">Rejected</span>
                                    @else
                                        <span class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 border border-gray-200">{{ $decision }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-4 text-sm text-gray-700">{{ $remarks ?: '—' }}</td>
                                <td class="px-2 py-4 text-sm text-gray-700">
                                    {{ $decidedAt ? 
                                        (is_object($decidedAt)
                                            ? $decidedAt->format('Y-m-d')
                                            : date('Y-m-d', strtotime((string)$decidedAt))
                                        ) : '—'
                                    }}
                                </td>
                                <td class="px-2 py-4 text-center">
                                    @if ($type === 'RIS' && $risId)
                                        <button type="button" class="inline-flex h-8 items-center justify-center rounded-lg bg-white px-2.5 text-xs font-semibold text-slate-700 border border-gray-200 transition hover:bg-gray-50" title="View approved RIS form" onclick="openRisViewModal({{ $risId }})">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-2 py-12 text-center">
                                    <p class="text-sm font-semibold text-gray-800">No approval records found.</p>
                                    <p class="mt-1 text-xs text-gray-500">When backend data is wired, the table will automatically populate here.</p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="7" class="px-2 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-800">Approval history UI is ready.</p>
                                <p class="mt-1 text-xs text-gray-500">Backend data will populate this table once the controller is updated.</p>
                            </td>
                        </tr>
                    @endisset

                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="risViewModal" class="fixed inset-0 z-50 hidden">
    <div class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px]" onclick="closeRisViewModal()">
        <div class="w-full max-w-6xl h-[calc(100vh-1rem)] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">RIS Form</h3>
                        <p id="risViewTitle" class="mt-1 text-sm text-slate-600">Approved RIS</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeRisViewModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
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

    function setHistoryCount() {
        const body = document.getElementById('historyTableBody');
        const rows = body ? body.querySelectorAll('.history-row') : [];
        const visible = Array.from(rows).filter(r => r.style.display !== 'none');
        document.getElementById('historyCount').textContent = visible.length;
    }

    function applyHistoryFilters() {
        const searchInput = document.getElementById('historySearch');
        const search = (searchInput?.value || '').trim().toLowerCase();

        const activeBtn = document.querySelector('.history-filter-btn.bg-gray-900');
        const activeFilter = activeBtn?.dataset?.filter || 'all';

        const body = document.getElementById('historyTableBody');
        if (!body) return;

        const rows = body.querySelectorAll('.history-row');

        rows.forEach(row => {
            const decision = (row.dataset.decision || '').toLowerCase();
            const rowSearch = (row.dataset.search || '').toLowerCase();

            const matchesFilter = (activeFilter === 'all') || (decision === activeFilter);
            const matchesSearch = !search || rowSearch.includes(search);

            row.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
        });

        setHistoryCount();
    }

    (function initApprovalHistoryUI() {
        const filterBtns = document.querySelectorAll('.history-filter-btn');
        const searchInput = document.getElementById('historySearch');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.classList.remove('bg-gray-900', 'text-white');
                    b.classList.add('text-gray-700');
                });
                btn.classList.remove('text-gray-700');
                btn.classList.add('bg-gray-900', 'text-white');

                applyHistoryFilters();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                applyHistoryFilters();
            });
        }

        // Initial count
        setHistoryCount();
    })();
</script>

{{-- Ensure lucide icons render on this page. --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

@endsection

