@extends('layouts.receiving-layout')

@section('content')

<div class="space-y-6">
    @include('layouts.partials.receiving-query-error')

    <div data-ro-table data-ro-default-filter="all" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-950">Vendor lookup</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Contact details for deliveries you have delivered. Add or edit suppliers as Purchaser.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'roSupplierViewSwitcher',
                            'btnClass' => 'ro-supplier-view-btn',
                        ])
                        <a
                            href="/receiving/export-pdf?section=suppliers"
                            title="Export this table to PDF"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            <i data-lucide="file-down" class="h-3.5 w-3.5"></i>
                            Export PDF
                        </a>
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $suppliers->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative w-full max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                        </div>
                        <input
                            id="receivingSupplierSearch"
                            type="search"
                            class="receiving-live-search h-10 w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                            placeholder="Search supplier, contact, type..."
                            autocomplete="off"
                            title="Search supplier, contact, type..."
                        >
                    </div>
                </div>
            </div>
        </div>

        <div id="roSupplierTable" class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Supplier</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Type</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Telephone number</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Delivered</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Last delivery</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $supplier)
                        @php
                            $rowSearch = trim(implode(' ', [
                                $supplier->supplier_name ?? '',
                                $supplier->supplier_store_type ?? '',
                                $supplier->contact_person ?? '',
                                $supplier->company_address ?? '',
                            ]));
                        @endphp
                        <tr data-ro-status="all" data-ro-search="{{ $rowSearch }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-semibold text-slate-950">{{ $supplier->supplier_name }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                @if(filled($supplier->supplier_store_type))
                                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-700">
                                        {{ $supplier->supplier_store_type }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $supplier->contact_person ?: '—' }}</td>
                            <td class="px-5 py-4 text-slate-400">—</td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $supplier->delivery_count }}</td>
                            <td class="px-5 py-4 whitespace-nowrap text-slate-500">{{ $supplier->last_delivery ? \Carbon\Carbon::parse($supplier->last_delivery)->format('M d, Y') : '—' }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if($suppliers->count()) style="display:none" @endif>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <p class="text-sm font-medium text-slate-700">No suppliers found</p>
                            <p class="mt-1 text-xs text-slate-400">Try another search, or wait until deliveries are recorded.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="roSupplierCards" class="hidden space-y-3 px-5 py-5 sm:px-6">
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
            <div class="receiving-empty-cards rounded-xl border border-dashed border-slate-200 px-4 py-12 text-center" @if($suppliers->count()) style="display:none" @endif>
                <p class="text-sm font-medium text-slate-700">No suppliers found</p>
                <p class="mt-1 text-xs text-slate-400">Try another search, or wait until deliveries are recorded.</p>
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
    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
});
</script>

@endsection
