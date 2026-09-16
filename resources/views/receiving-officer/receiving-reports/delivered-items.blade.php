@extends('layouts.receiving-layout')

@section('content')

<div class="space-y-6">
    @include('layouts.partials.receiving-query-error')

    <div data-ro-table data-ro-default-filter="all" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-950">Delivered items</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Items already received into inventory.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'roDeliveredViewSwitcher',
                            'btnClass' => 'ro-delivered-view-btn',
                        ])
                        <a
                            href="/receiving/export-pdf?section=delivered"
                            title="Export this table to PDF"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            <i data-lucide="file-down" class="h-3.5 w-3.5"></i>
                            Export PDF
                        </a>
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $rows->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative w-full max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                        </div>
                        <input
                            id="receivingDeliveredSearch"
                            type="search"
                            class="receiving-live-search h-10 w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                            placeholder="Search RIS, ATP, items, supplier..."
                            autocomplete="off"
                            title="Search RIS, ATP, items, supplier..."
                        >
                    </div>
                </div>
            </div>
        </div>

        <div id="roDeliveredTable" class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">RR / RIS</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Items</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Qty</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Supplier / Location</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Received</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Officer</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Photos</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
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
                        <tr data-ro-status="all" data-ro-search="{{ $rowSearch }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950">{{ $ref }}</p>
                                <div class="mt-1.5">
                                    <span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 text-[11px] font-semibold text-sky-700">{{ $statusLabel }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if($rrItems->isNotEmpty())
                                    <ul class="space-y-1">
                                        @foreach($rrItems as $item)
                                            <li>
                                                <span class="font-medium text-slate-900">{{ $item->receiving_report_item_article ?: '—' }}</span>
                                                <span class="text-slate-500"> · qty {{ (int) ($item->receiving_report_item_quantity ?? 0) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{ $row->item_names ?: '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ (int) ($row->total_qty ?? $rrItems->sum(fn ($i) => (int) ($i->receiving_report_item_quantity ?? 0))) }}</td>
                            <td class="px-5 py-4 text-slate-700">
                                <div class="font-medium text-slate-900">{{ $row->supplier_name }}</div>
                                @if($storeLocation !== '')
                                    <div class="mt-0.5 text-xs text-slate-500">{{ $storeLocation }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-slate-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y g:i A') : '—' }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $row->officer_name ?: 'Receiving Officer' }}</td>
                            <td class="px-5 py-4">
                                @if(count($photos))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach(array_slice($photos, 0, 4) as $photo)
                                            <a href="{{ asset('storage/'.$photo) }}" target="_blank" rel="noopener" class="block h-10 w-10 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 transition hover:border-slate-300">
                                                <img src="{{ asset('storage/'.$photo) }}" alt="Verification photo" class="h-full w-full object-cover">
                                            </a>
                                        @endforeach
                                        @if(count($photos) > 4)
                                            <span class="self-center text-xs font-medium text-slate-500">+{{ count($photos) - 4 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
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
                            <p class="text-sm font-medium text-slate-700">No delivered items yet</p>
                            <p class="mt-1 text-xs text-slate-400">Complete second count on a Receiving Report first.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="roDeliveredCards" class="hidden space-y-3 px-5 py-5 sm:px-6">
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
                        $actionsHtml .= '<a href="/receiving/reports/'.$row->receiving_report_id.'/print" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></a>';
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
            <div class="receiving-empty-cards rounded-xl border border-dashed border-slate-200 px-4 py-12 text-center" @if($rows->count()) style="display:none" @endif>
                <p class="text-sm font-medium text-slate-700">No delivered items yet</p>
                <p class="mt-1 text-xs text-slate-400">Complete second count on a Receiving Report first.</p>
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
