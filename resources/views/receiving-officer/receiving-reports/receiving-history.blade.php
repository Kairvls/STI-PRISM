@extends('layouts.receiving-layout')

@section('content')

<div class="space-y-6">
    @include('layouts.partials.receiving-query-error')

    @php
        $filter = $filter ?? 'all';
        $historyCards = [
            ['filter' => 'all', 'label' => 'All', 'count' => $allCount ?? $rows->count(), 'color' => 'text-slate-950', 'title' => 'Show all history'],
            ['filter' => 'accepted', 'label' => 'Delivered', 'count' => $acceptedCount ?? 0, 'color' => 'text-[#0025cc]', 'title' => 'Show delivered items'],
            ['filter' => 'returned', 'label' => 'Returned', 'count' => $returnedCount ?? 0, 'color' => 'text-slate-700', 'title' => 'Show returned deliveries'],
        ];
        $sliderOptions = [
            ['filter' => 'all', 'label' => 'All'],
            ['filter' => 'accepted', 'label' => 'Delivered'],
            ['filter' => 'returned', 'label' => 'Returned'],
        ];
    @endphp

    <div data-ro-table data-ro-default-filter="all" class="space-y-6">
        @include('layouts.partials.receiving-stat-cards', ['cards' => $historyCards, 'current' => $filter])

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-950">Delivery History</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Delivered and returned receiving reports.</p>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                            @include('admin.partials.view-mode-switcher', [
                                'switcherId' => 'roHistoryViewSwitcher',
                                'btnClass' => 'ro-history-view-btn',
                            ])
                            <a
                                href="/receiving/export-pdf?section=history"
                                title="Export this table to PDF"
                                class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                <i data-lucide="file-down" class="h-3.5 w-3.5"></i>
                                Export PDF
                            </a>
                            <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                                {{ $allCount ?? $rows->count() }} total
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        @include('layouts.partials.receiving-filter-slider', [
                            'sliderId' => 'receivingHistoryFilterSlider',
                            'current' => $filter,
                            'ariaLabel' => 'History filters',
                            'options' => $sliderOptions,
                        ])
                        <div class="relative w-full max-w-md lg:ml-auto">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                            </div>
                            <input
                                id="receivingHistorySearch"
                                type="search"
                                class="receiving-live-search h-10 w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                                placeholder="Search RIS, ATP, supplier, OR..."
                                autocomplete="off"
                                title="Search RIS, ATP, supplier, OR..."
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div id="roHistoryTable" class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/80">
                        <tr>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">RR / RIS</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Items</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Supplier</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">OR / PO</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Result</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Officer</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Preview</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            @php
                                $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null;
                                $rowStatus = in_array($row->receiving_report_status, ['Accepted', 'Completed'], true) ? 'accepted' : 'returned';
                                $storeLocation = trim((string) ($row->store_location ?? ''));
                                $deliveredLabel = $storeLocation !== '' ? 'Delivered · '.$storeLocation : 'Delivered';
                                $rowSearch = trim(implode(' ', [
                                    $row->ris_form_number ?? '',
                                    $row->authority_purchase_form_number ?? '',
                                    $row->item_names ?? '',
                                    $row->supplier_name ?? '',
                                    $storeLocation,
                                    $row->official_receipt ?? '',
                                    $row->officer_name ?? '',
                                ]));
                                $ref = $row->receiving_report_form_number ?: ($row->ris_form_number ?: $row->authority_purchase_form_number);
                            @endphp
                            <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}" class="transition hover:bg-slate-50/70">
                                <td class="px-5 py-4 whitespace-nowrap text-slate-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-950">{{ $ref }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $row->item_names ?: '—' }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $row->supplier_name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $row->official_receipt ?? '—' }} / {{ $row->authority_purchase_reference_po_no ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @if($rowStatus === 'accepted')
                                        <span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700" title="{{ $storeLocation !== '' ? $storeLocation : 'Delivered' }}">{{ $deliveredLabel }}</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-700">Returned</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ $row->officer_name ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        @include('layouts.partials.receiving-ris-eye', ['risId' => $previewRisId])
                                        @if(!empty($row->receiving_report_id))
                                            <a
                                                href="/receiving/reports/{{ $row->receiving_report_id }}/print"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                                title="Print"
                                                aria-label="Print"
                                            >
                                                <i data-lucide="printer" class="h-4 w-4"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                        <tr class="receiving-empty-row" @if($rows->count()) style="display:none" @endif>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <p class="text-sm font-medium text-slate-700">No history yet</p>
                                <p class="mt-1 text-xs text-slate-400">History appears after second count or a return.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="roHistoryCards" class="hidden space-y-3 px-5 py-5 sm:px-6">
                @forelse($rows as $row)
                    @php
                        $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null;
                        $rowStatus = in_array($row->receiving_report_status, ['Accepted', 'Completed'], true) ? 'accepted' : 'returned';
                        $storeLocation = trim((string) ($row->store_location ?? ''));
                        $deliveredLabel = $storeLocation !== '' ? 'Delivered · '.$storeLocation : 'Delivered';
                        $rowSearch = trim(implode(' ', [
                            $row->ris_form_number ?? '',
                            $row->authority_purchase_form_number ?? '',
                            $row->item_names ?? '',
                            $row->supplier_name ?? '',
                            $storeLocation,
                            $row->official_receipt ?? '',
                            $row->officer_name ?? '',
                        ]));
                        $ref = $row->receiving_report_form_number ?: ($row->ris_form_number ?: $row->authority_purchase_form_number);
                        $actionsHtml = view('layouts.partials.receiving-ris-eye', ['risId' => $previewRisId])->render();
                        if (!empty($row->receiving_report_id)) {
                            $actionsHtml .= '<a href="/receiving/reports/'.$row->receiving_report_id.'/print" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></a>';
                        }
                    @endphp
                    @include('receiving-officer.partials.list-info-card', [
                        'title' => $ref,
                        'status' => $rowStatus === 'accepted' ? $deliveredLabel : 'Returned',
                        'statusClass' => $rowStatus === 'accepted' ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-700',
                        'roStatus' => $rowStatus,
                        'roSearch' => $rowSearch,
                        'fields' => [
                            ['label' => 'Items', 'value' => $row->item_names ?: '—', 'full' => true],
                            ['label' => 'Supplier', 'value' => $row->supplier_name ?: '—'],
                            ['label' => 'OR / PO', 'value' => ($row->official_receipt ?? '—').' / '.($row->authority_purchase_reference_po_no ?? '—')],
                            ['label' => 'Date', 'value' => $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—'],
                            ['label' => 'Officer', 'value' => $row->officer_name ?: '—'],
                        ],
                        'actionsHtml' => $actionsHtml,
                    ])
                @empty
                @endforelse
                <div class="receiving-empty-cards rounded-xl border border-dashed border-slate-200 px-4 py-12 text-center" @if($rows->count()) style="display:none" @endif>
                    <p class="text-sm font-medium text-slate-700">No history yet</p>
                    <p class="mt-1 text-xs text-slate-400">History appears after second count or a return.</p>
                </div>
            </div>

            @include('layouts.partials.receiving-table-pager')
        </div>
    </div>
</div>

@include('admin.partials.view-mode-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.bindAdminViewMode === 'function') {
        window.bindAdminViewMode({
            tableId: 'roHistoryTable',
            cardsId: 'roHistoryCards',
            buttonSelector: '.ro-history-view-btn',
            storageKey: 'ro_history_view',
        });
    }
    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
});
</script>

@endsection
