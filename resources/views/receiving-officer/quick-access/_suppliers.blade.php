<div class="space-y-3 p-1" data-ro-table data-ro-default-filter="all">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Read-only vendor list from delivered items. Purchaser owns add/edit.</p>
                        <a href="/receiving/supplier-records" class="text-xs font-semibold text-sky-600">Open full page</a>
                    </div>
                    <span class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ ($rows ?? collect())->count() }} total</span>
                </div>
                <div class="flex justify-end">
                    @include('layouts.partials.receiving-filters', ['searchId' => 'qaSupplierSearch', 'placeholder' => 'Search supplier, contact...'])
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Type</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Contact</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Telephone number</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Delivered</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $supplier)
                        @php
                            $rowSearch = trim(implode(' ', [$supplier->supplier_name ?? '', $supplier->supplier_store_type ?? '', $supplier->contact_person ?? '']));
                        @endphp
                        <tr data-ro-status="all" data-ro-search="{{ $rowSearch }}">
                            <td class="px-4 py-3 text-sm font-semibold">{{ $supplier->supplier_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $supplier->supplier_store_type ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $supplier->contact_person ?: 'No contact' }}</td>
                            <td class="px-4 py-3 text-sm">—</td>
                            <td class="px-4 py-3 text-sm">{{ $supplier->delivery_count }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if(($rows ?? collect())->count()) style="display:none" @endif>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No supplier records yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
</div>
