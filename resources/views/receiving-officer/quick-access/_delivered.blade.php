<div class="space-y-3 p-1" data-ro-table data-ro-default-filter="all">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Delivered items added to inventory.</p>
                        <a href="/receiving/delivered-items" class="text-xs font-semibold text-sky-600">Open full page</a>
                    </div>
                    <span class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ ($rows ?? collect())->count() }} total</span>
                </div>
                <div class="flex justify-end">
                    @include('layouts.partials.receiving-filters', ['searchId' => 'qaDeliveredSearch', 'placeholder' => 'Search RIS, ATP, supplier...'])
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">RR / RIS</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Items</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Received</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Officer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $row)
                        @php
                            $rowSearch = trim(implode(' ', [$row->ris_form_number ?? '', $row->authority_purchase_form_number ?? '', $row->item_names ?? '', $row->supplier_name ?? '', $row->officer_name ?? '']));
                        @endphp
                        <tr data-ro-status="all" data-ro-search="{{ $rowSearch }}">
                            <td class="px-4 py-3 text-sm font-semibold">{{ $row->receiving_report_form_number ?: ($row->ris_form_number ?: $row->authority_purchase_form_number) }}</td>
                            <td class="px-4 py-3 text-sm">{{ \Illuminate\Support\Str::limit($row->item_names ?: '—', 48) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->supplier_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->officer_name ?: 'Receiving Officer' }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if(($rows ?? collect())->count()) style="display:none" @endif>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No delivered items yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
</div>
