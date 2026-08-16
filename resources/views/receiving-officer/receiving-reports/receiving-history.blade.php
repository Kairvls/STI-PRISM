@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Delivery History</h1>
        <p class="admin-page-subtitle">Accepted and returned receiving reports.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    @php
        $filter = $filter ?? 'all';
        $historyCards = [
            ['filter' => 'all', 'label' => 'All', 'count' => $allCount ?? $rows->count(), 'color' => 'text-slate-900', 'title' => 'Show all history'],
            ['filter' => 'accepted', 'label' => 'Accepted', 'count' => $acceptedCount ?? 0, 'color' => 'text-emerald-600', 'title' => 'Show accepted deliveries'],
            ['filter' => 'returned', 'label' => 'Returned', 'count' => $returnedCount ?? 0, 'color' => 'text-rose-600', 'title' => 'Show returned deliveries'],
        ];
        $sliderOptions = [
            ['filter' => 'all', 'label' => 'All'],
            ['filter' => 'accepted', 'label' => 'Accepted'],
            ['filter' => 'returned', 'label' => 'Returned'],
        ];
    @endphp

    <div data-ro-table data-ro-default-filter="all" class="space-y-6">
    @include('layouts.partials.receiving-stat-cards', ['cards' => $historyCards, 'current' => $filter])

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Delivery History</h2>
                        <p class="mt-1 text-xs text-gray-500">Accepted and returned receiving reports.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @include('layouts.partials.receiving-export-pdf', ['exportSection' => 'history'])
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
                    @include('layouts.partials.receiving-filters', ['searchId' => 'receivingHistorySearch', 'placeholder' => 'Search RIS, ATP, supplier, OR...'])
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS / ATP</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">OR / PO</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Result</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null;
                            $rowStatus = in_array($row->receiving_report_status, ['Accepted', 'Completed'], true) ? 'accepted' : 'returned';
                            $rowSearch = trim(implode(' ', [
                                $row->ris_form_number ?? '',
                                $row->authority_purchase_form_number ?? '',
                                $row->item_names ?? '',
                                $row->supplier_name ?? '',
                                $row->official_receipt ?? '',
                                $row->officer_name ?? '',
                            ]));
                        @endphp
                        <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}">
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->item_names ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->official_receipt ?: '—' }} / {{ $row->authority_purchase_reference_po_no ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @if($rowStatus === 'accepted')
                                    <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Accepted</span>
                                @else
                                    <span class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Returned</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->officer_name ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    @include('layouts.partials.receiving-ris-eye', ['risId' => $previewRisId])
                                    @if(!empty($row->receiving_report_id))
                                        <a href="/receiving/reports/{{ $row->receiving_report_id }}/print" class="text-sm font-semibold text-[#0037c7]">Print</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if($rows->count()) style="display:none" @endif>
                        <td colspan="8" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for Purchaser ATP to be approved and inspected. History appears after you accept or return a delivery.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
    </div>
</div>

@endsection
