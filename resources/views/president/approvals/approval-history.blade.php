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
                                $type = $row->type ?? $row->reference_type ?? '—';
                                $reference = $row->reference ?? ($row->ris_id ? 'RIS#'.$row->ris_id : ($row->procurement_request_id ? 'PR#'.$row->procurement_request_id : '—'));
                                $supplier = $row->supplier_name ?? $row->party_name ?? '—';
                                $decision = $row->decision ?? $row->approval_status ?? '—';
                                $remarks = $row->remarks ?? $row->approval_remarks ?? null;
                                $decidedAt = $row->decided_at ?? $row->approved_at ?? $row->approval_date ?? null;
                                $decisionLower = is_string($decision) ? strtolower($decision) : '';
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
                                            ? $decidedAt->format('Y-m-d H:i')
                                            : date('Y-m-d H:i', strtotime((string)$decidedAt))
                                        ) : '—'
                                    }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-2 py-12 text-center">
                                    <p class="text-sm font-semibold text-gray-800">No approval records found.</p>
                                    <p class="mt-1 text-xs text-gray-500">When backend data is wired, the table will automatically populate here.</p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="6" class="px-2 py-12 text-center">
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

<script>
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

