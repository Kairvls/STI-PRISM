@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Supplier lookup</h1>
        <p class="admin-page-subtitle">Read-only list of vendors on your deliveries. Purchaser maintains supplier accounts; you cannot add or edit them here.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div data-ro-table data-ro-default-filter="all" class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Vendor lookup</h2>
                        <p class="mt-1 text-xs text-gray-500">Contact details for deliveries you have delivered. Add or edit suppliers as Purchaser.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'roSupplierViewSwitcher',
                            'btnClass' => 'ro-supplier-view-btn',
                        ])
                        @include('layouts.partials.receiving-export-pdf', ['exportSection' => 'suppliers'])
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $suppliers->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
                    @include('layouts.partials.receiving-filters', ['searchId' => 'receivingSupplierSearch', 'placeholder' => 'Search supplier, contact, type...'])
                </div>
            </div>
        </div>

        <div id="roSupplierTable" class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Telephone number</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Delivered</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Last delivery</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($suppliers as $supplier)
                        @php
                            $rowSearch = trim(implode(' ', [
                                $supplier->supplier_name ?? '',
                                $supplier->supplier_store_type ?? '',
                                $supplier->contact_person ?? '',
                                $supplier->company_address ?? '',
                            ]));
                        @endphp
                        <tr data-ro-status="all" data-ro-search="{{ $rowSearch }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $supplier->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $supplier->supplier_store_type ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $supplier->contact_person ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">—</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $supplier->delivery_count }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $supplier->last_delivery ? \Carbon\Carbon::parse($supplier->last_delivery)->format('M d, Y') : '—' }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if($suppliers->count()) style="display:none" @endif>
                        <td colspan="6" class="px-5 py-16 text-center text-sm text-gray-400">No suppliers match this search, or none have been recorded yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="roSupplierCards" class="hidden space-y-3 px-5 py-4">
            @forelse($suppliers as $supplier)
                @php
                    $rowSearch = trim(implode(' ', [
                        $supplier->supplier_name ?? '',
                        $supplier->supplier_store_type ?? '',
                        $supplier->contact_person ?? '',
                        $supplier->company_address ?? '',
                    ]));
                @endphp
                @include('receiving-officer.partials.list-info-card', [
                    'title' => $supplier->supplier_name,
                    'subtitle' => $supplier->supplier_store_type ?: null,
                    'roStatus' => 'all',
                    'roSearch' => $rowSearch,
                    'fields' => [
                        ['label' => 'Contact', 'value' => $supplier->contact_person ?: '—'],
                        ['label' => 'Telephone', 'value' => '—'],
                        ['label' => 'Delivered', 'value' => (string) ($supplier->delivery_count ?? 0)],
                        ['label' => 'Last', 'value' => $supplier->last_delivery ? \Carbon\Carbon::parse($supplier->last_delivery)->format('M d, Y') : '—'],
                    ],
                ])
            @empty
            @endforelse
            <div class="receiving-empty-cards px-2 py-10 text-center text-sm text-gray-400" @if($suppliers->count()) style="display:none" @endif>
                No suppliers match this search, or none have been recorded yet.
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
            tableId: 'roSupplierTable',
            cardsId: 'roSupplierCards',
            buttonSelector: '.ro-supplier-view-btn',
            storageKey: 'ro_supplier_view',
        });
    }
});
</script>

@endsection
