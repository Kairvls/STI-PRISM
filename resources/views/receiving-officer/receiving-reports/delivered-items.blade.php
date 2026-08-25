@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Delivered Items</h1>
        <p class="admin-page-subtitle">Delivered items that were added to inventory.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div data-ro-table data-ro-default-filter="all" class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Delivered items</h2>
                        <p class="mt-1 text-xs text-gray-500">Items already received into inventory.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'roDeliveredViewSwitcher',
                            'btnClass' => 'ro-delivered-view-btn',
                        ])
                        @include('layouts.partials.receiving-export-pdf', ['exportSection' => 'delivered'])
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $rows->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
                    @include('layouts.partials.receiving-filters', ['searchId' => 'receivingDeliveredSearch', 'placeholder' => 'Search RIS, ATP, items, supplier...'])
                </div>
            </div>
        </div>

        <div id="roDeliveredTable" class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RR / RIS</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Qty</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier / Location</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Received</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Photos</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null;
                            $rrItems = ($lineItems ?? collect())->get($row->receiving_report_id, collect());
                            $photos = [];
                            if (!empty($row->receiving_report_verification_photos)) {
                                $decoded = json_decode((string) $row->receiving_report_verification_photos, true);
                                $photos = is_array($decoded) ? $decoded : [];
                            }
                            $storeLocation = trim((string) ($row->store_location ?? ''));
                            $statusLabel = $storeLocation !== '' ? 'Delivered · '.$storeLocation : 'Delivered';
                            $itemSearch = $rrItems->map(fn ($item) => ($item->receiving_report_item_article ?? '').' '.($item->receiving_report_item_quantity ?? ''))->implode(' ');
                            $rowSearch = trim(implode(' ', [
                                $row->receiving_report_form_number ?? '',
                                $row->ris_form_number ?? '',
                                $row->authority_purchase_form_number ?? '',
                                $row->item_names ?? '',
                                $itemSearch,
                                $row->supplier_name ?? '',
                                $storeLocation,
                                $row->official_receipt ?? '',
                                $row->officer_name ?? '',
                                $statusLabel,
                            ]));
                            $ref = $row->receiving_report_form_number ?: ($row->ris_form_number ?: $row->authority_purchase_form_number);
                        @endphp
                        <tr data-ro-status="all" data-ro-search="{{ $rowSearch }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                {{ $ref }}
                                <div class="mt-1">
                                    <span class="rounded-lg border border-sky-200 bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700">{{ $statusLabel }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                @if($rrItems->isNotEmpty())
                                    <ul class="space-y-1">
                                        @foreach($rrItems as $item)
                                            <li>
                                                <span class="font-medium text-gray-900">{{ $item->receiving_report_item_article ?: '—' }}</span>
                                                <span class="text-gray-500"> · qty {{ (int) ($item->receiving_report_item_quantity ?? 0) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{ $row->item_names ?: '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ (int) ($row->total_qty ?? $rrItems->sum(fn ($i) => (int) ($i->receiving_report_item_quantity ?? 0))) }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                <div>{{ $row->supplier_name }}</div>
                                @if($storeLocation !== '')
                                    <div class="mt-0.5 text-xs text-gray-500">{{ $storeLocation }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y g:i A') : '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->officer_name ?: 'Receiving Officer' }}</td>
                            <td class="px-5 py-4">
                                @if(count($photos))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach(array_slice($photos, 0, 4) as $photo)
                                            <a href="{{ asset('storage/'.$photo) }}" target="_blank" rel="noopener" class="block h-10 w-10 overflow-hidden rounded-md border border-gray-200 bg-gray-50">
                                                <img src="{{ asset('storage/'.$photo) }}" alt="Verification photo" class="h-full w-full object-cover">
                                            </a>
                                        @endforeach
                                        @if(count($photos) > 4)
                                            <span class="text-xs text-gray-500 self-center">+{{ count($photos) - 4 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    @include('layouts.partials.receiving-ris-eye', ['risId' => $previewRisId])
                                    @if(!empty($row->receiving_report_id))
                                        <a href="/receiving/reports/{{ $row->receiving_report_id }}/print" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if($rows->count()) style="display:none" @endif>
                        <td colspan="8" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for a delivered item. Complete second count on a Receiving Report first.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="roDeliveredCards" class="hidden space-y-3 px-5 py-4">
            @forelse($rows as $row)
                @php
                    $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null;
                    $rrItems = ($lineItems ?? collect())->get($row->receiving_report_id, collect());
                    $storeLocation = trim((string) ($row->store_location ?? ''));
                    $statusLabel = $storeLocation !== '' ? 'Delivered · '.$storeLocation : 'Delivered';
                    $itemSearch = $rrItems->map(fn ($item) => ($item->receiving_report_item_article ?? '').' '.($item->receiving_report_item_quantity ?? ''))->implode(' ');
                    $rowSearch = trim(implode(' ', [
                        $row->receiving_report_form_number ?? '',
                        $row->ris_form_number ?? '',
                        $row->authority_purchase_form_number ?? '',
                        $row->item_names ?? '',
                        $itemSearch,
                        $row->supplier_name ?? '',
                        $storeLocation,
                        $row->officer_name ?? '',
                    ]));
                    $ref = $row->receiving_report_form_number ?: ($row->ris_form_number ?: $row->authority_purchase_form_number);
                    $itemsLabel = $rrItems->isNotEmpty()
                        ? $rrItems->take(3)->map(fn ($i) => ($i->receiving_report_item_article ?: '—').' ×'.(int) ($i->receiving_report_item_quantity ?? 0))->implode(', ')
                        : ($row->item_names ?: '—');
                    $actionsHtml = view('layouts.partials.receiving-ris-eye', ['risId' => $previewRisId])->render();
                    if (!empty($row->receiving_report_id)) {
                        $actionsHtml .= '<a href="/receiving/reports/'.$row->receiving_report_id.'/print" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></a>';
                    }
                @endphp
                @include('receiving-officer.partials.list-info-card', [
                    'title' => $ref,
                    'status' => $statusLabel,
                    'statusClass' => 'border-sky-200 bg-sky-50 text-sky-700',
                    'roStatus' => 'all',
                    'roSearch' => $rowSearch,
                    'fields' => [
                        ['label' => 'Items', 'value' => \Illuminate\Support\Str::limit($itemsLabel, 80), 'full' => true],
                        ['label' => 'Supplier', 'value' => $row->supplier_name ?: '—'],
                        ['label' => 'Officer', 'value' => $row->officer_name ?: 'Receiving Officer'],
                        ['label' => 'Received', 'value' => $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y g:i A') : '—'],
                        ['label' => 'Qty', 'value' => (string) ((int) ($row->total_qty ?? $rrItems->sum(fn ($i) => (int) ($i->receiving_report_item_quantity ?? 0))))],
                    ],
                    'actionsHtml' => $actionsHtml,
                ])
            @empty
            @endforelse
            <div class="receiving-empty-cards px-2 py-10 text-center text-sm text-gray-400" @if($rows->count()) style="display:none" @endif>
                Waiting for a delivered item. Complete second count on a Receiving Report first.
            </div>
        </div>

        @include('layouts.partials.receiving-table-pager')
    </div>
</div>

@include('admin.partials.view-mode-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.bindAdminViewMode === 'function') {
        window.bindAdminViewMode({
            tableId: 'roDeliveredTable',
            cardsId: 'roDeliveredCards',
            buttonSelector: '.ro-delivered-view-btn',
            storageKey: 'ro_delivered_view',
        });
    }
    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
});
</script>

@endsection
