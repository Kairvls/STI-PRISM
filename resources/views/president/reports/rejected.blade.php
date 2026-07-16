@extends('layouts.president-layout')

@section('title', 'Rejected Outcomes')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Rejected Outcomes</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">
            Review procurement decisions marked as Rejected by the President.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="/president/reports/approved" class="inline-flex h-10 items-center justify-center rounded-lg border border-emerald-200 bg-white px-4 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50">
            <i data-lucide="badge-check" class="h-4 w-4"></i>
            Approved
        </a>

        <a href="/president/reports/monthly-summary" class="inline-flex h-10 items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800">
            <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
            Monthly Summary
        </a>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Filters --}}
    <section class="lg:col-span-1 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Refine results</h2>
                <p class="mt-1 text-xs text-gray-500">Filter rejected outcomes and search by details.</p>
            </div>
            <span class="inline-flex items-center rounded-lg bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-800 border border-rose-200">
                Rejected
            </span>
        </div>

        <div class="mt-4 flex flex-col gap-2">
            <button type="button" class="outcome-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50" data-filter="all">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="layers" class="h-4 w-4 text-gray-600"></i>
                    All outcomes
                </span>
            </button>

            <button type="button" class="outcome-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50" data-filter="ris">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="clipboard-x" class="h-4 w-4 text-rose-600"></i>
                    RIS rejections
                </span>
            </button>

            <button type="button" class="outcome-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50" data-filter="procurement">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="file-x" class="h-4 w-4 text-rose-600"></i>
                    Procurement rejections
                </span>
            </button>
        </div>

        <div class="mt-5">
            <h3 class="text-xs font-semibold text-gray-900">Search</h3>
            <div class="relative mt-2">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input id="outcomeSearch" type="search" placeholder="Search by reference, supplier, remarks..." class="w-full rounded-lg border border-gray-200 bg-white px-9 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-amber-100" />
            </div>
        </div>

        <div class="mt-5 rounded-lg border border-dashed border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-semibold text-rose-900">UI note</p>
            <p class="mt-1 text-xs text-rose-900/80">
                Backend data isn’t wired yet, so the table may show the empty state.
            </p>
        </div>
    </section>

    {{-- Table --}}
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Rejected decision list</h2>
                <p class="mt-1 text-xs text-gray-500">Includes remarks to help improve the next submission.</p>
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
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Type</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Supplier / Party</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Decision</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Remarks</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected at</th>
                    </tr>
                </thead>
                <tbody id="outcomeTableBody">

                    {{-- Expected variable (optional): $rejectedOutcomeRecords --}}
                    @isset($rejectedOutcomeRecords)
                        @forelse($rejectedOutcomeRecords as $row)
                            @php
                                $type = $row->type ?? ($row->reference_type ?? '—');
                                $typeLower = is_string($type) ? strtolower($type) : '';
                                $normalizedType = str_contains($typeLower, 'ris') ? 'RIS' : (str_contains($typeLower, 'procurement') ? 'Procurement' : $type);

                                $reference = $row->reference ?? ($row->ris_id ? 'RIS#'.$row->ris_id : ($row->procurement_request_id ? 'PR#'.$row->procurement_request_id : '—'));
                                $party = $row->supplier_name ?? $row->party_name ?? '—';
                                $remarks = $row->remarks ?? $row->approval_remarks ?? null;
                                $rejectedAt = $row->rejected_at ?? $row->decided_at ?? null;

                                $searchBlob = strtolower((string)($reference.' '.$party.' '.($remarks ?? '')));

                                $dataTypeFilter = $normalizedType === 'RIS' ? 'ris' : 'procurement';
                            @endphp

                            <tr class="border-b border-gray-100 outcome-row" 
                                data-type="{{ $dataTypeFilter }}"
                                data-search="{{ $searchBlob }}"
                            >
                                <td class="px-2 py-4 text-sm font-semibold text-gray-600">{{ $normalizedType }}</td>
                                <td class="px-2 py-4 text-sm text-gray-700">{{ $reference }}</td>
                                <td class="px-2 py-4 text-sm text-gray-700">{{ $party }}</td>
                                <td class="px-2 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-lg bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-800 border border-rose-200">Rejected</span>
                                </td>
                                <td class="px-2 py-4 text-sm text-gray-700">{{ $remarks ?: '—' }}</td>
                                <td class="px-2 py-4 text-sm text-gray-700">
                                    {{ $rejectedAt ? (is_object($rejectedAt) ? $rejectedAt->format('Y-m-d H:i') : date('Y-m-d H:i', strtotime((string)$rejectedAt))) : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-2 py-12 text-center">
                                    <p class="text-sm font-semibold text-gray-800">No rejected outcomes found.</p>
                                    <p class="mt-1 text-xs text-gray-500">When backend data is wired, the table will populate here.</p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="6" class="px-2 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-800">Rejected Outcomes UI is ready.</p>
                                <p class="mt-1 text-xs text-gray-500">Backend data will populate the table once the controller is updated.</p>
                            </td>
                        </tr>
                    @endisset

                </tbody>
            </table>
        </div>
    </section>

</div>

<script>
    function setOutcomeCount() {
        const body = document.getElementById('outcomeTableBody');
        const rows = body ? body.querySelectorAll('.outcome-row') : [];
        const visible = Array.from(rows).filter(r => r.style.display !== 'none');
        document.getElementById('outcomeCount').textContent = visible.length;
    }

    function applyOutcomeFilters() {
        const searchInput = document.getElementById('outcomeSearch');
        const search = (searchInput?.value || '').trim().toLowerCase();

        const activeBtn = document.querySelector('.outcome-filter-btn.bg-gray-900');
        const activeType = activeBtn?.dataset?.filter || 'all';

        const body = document.getElementById('outcomeTableBody');
        if (!body) return;

        const rows = body.querySelectorAll('.outcome-row');
        rows.forEach(row => {
            const type = (row.dataset.type || '').toLowerCase();
            const rowSearch = (row.dataset.search || '').toLowerCase();

            const matchesType = (activeType === 'all') || (type === activeType);
            const matchesSearch = !search || rowSearch.includes(search);

            row.style.display = (matchesType && matchesSearch) ? '' : 'none';
        });

        setOutcomeCount();
    }

    (function initOutcomeUI() {
        const filterBtns = document.querySelectorAll('.outcome-filter-btn');
        const searchInput = document.getElementById('outcomeSearch');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.classList.remove('bg-gray-900', 'text-white');
                    b.classList.add('text-gray-700');
                });

                btn.classList.add('bg-gray-900', 'text-white');
                btn.classList.remove('text-gray-700');

                applyOutcomeFilters();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', applyOutcomeFilters);
        }

        const defaultBtn = document.querySelector('.outcome-filter-btn[data-filter="all"]');
        if (defaultBtn) defaultBtn.click();

        setOutcomeCount();
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

@endsection

