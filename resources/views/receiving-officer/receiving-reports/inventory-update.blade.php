@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Inventory Update</h1>
        <p class="admin-page-subtitle">Stock created when a receiving report is delivered.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div data-ro-table data-ro-default-filter="all" class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Inventory lines</h2>
                        <p class="mt-1 text-xs text-gray-500">Quantities added after a delivered receiving report.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'roInventoryViewSwitcher',
                            'btnClass' => 'ro-inventory-view-btn',
                        ])
                        @include('layouts.partials.receiving-export-pdf', ['exportSection' => 'inventory'])
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $items->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
                    @include('layouts.partials.receiving-filters', ['searchId' => 'receivingInventorySearch', 'placeholder' => 'Search item...'])
                </div>
            </div>
        </div>

        <div id="roInventoryTable" class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Qty added</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                        @php
                            $name = $item->receiving_report_item_article ?? $item->receiving_item_description ?? '—';
                        @endphp
                        <tr data-ro-status="all" data-ro-search="{{ $name }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $item->receiving_report_item_quantity ?? $item->receiving_item_quantity ?? '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($item->receiving_report_date ?? $item->receiving_report_created_at)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if($items->count()) style="display:none" @endif>
                        <td colspan="3" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for an accepted delivery. Inventory lines appear after second count on a Receiving Report.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="roInventoryCards" class="hidden space-y-3 px-5 py-4">
            @forelse($items as $item)
                @php
                    $name = $item->receiving_report_item_article ?? $item->receiving_item_description ?? '—';
                    $qty = $item->receiving_report_item_quantity ?? $item->receiving_item_quantity ?? '—';
                    $date = \Carbon\Carbon::parse($item->receiving_report_date ?? $item->receiving_report_created_at)->format('M d, Y');
                @endphp
                @include('receiving-officer.partials.list-info-card', [
                    'title' => $name,
                    'roStatus' => 'all',
                    'roSearch' => $name,
                    'fields' => [
                        ['label' => 'Qty added', 'value' => (string) $qty],
                        ['label' => 'Date', 'value' => $date],
                    ],
                ])
            @empty
            @endforelse
            <div class="receiving-empty-cards px-2 py-10 text-center text-sm text-gray-400" @if($items->count()) style="display:none" @endif>
                Waiting for an accepted delivery. Inventory lines appear after second count on a Receiving Report.
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
            tableId: 'roInventoryTable',
            cardsId: 'roInventoryCards',
            buttonSelector: '.ro-inventory-view-btn',
            storageKey: 'ro_inventory_view',
        });
    }
});
</script>

@endsection
