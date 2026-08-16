<div class="space-y-3 p-1" data-ro-table data-ro-default-filter="pending">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm text-gray-500">Pending receiving reports waiting for inspection.</p>
            <a href="/receiving/reports" class="text-xs font-semibold text-[#0037c7]">Open full page</a>
        </div>
        <span class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ ($rows ?? collect())->count() }} total</span>
    </div>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        @include('layouts.partials.receiving-filter-slider', [
            'sliderId' => 'qaPendingFilterSlider',
            'current' => 'pending',
            'options' => [
                ['filter' => 'pending', 'label' => 'Pending'],
                ['filter' => 'returned', 'label' => 'For correction'],
                ['filter' => 'all', 'label' => 'All'],
            ],
        ])
        @include('layouts.partials.receiving-filters', ['searchId' => 'qaPendingSearch', 'placeholder' => 'Search RR, RIS, ATP...'])
    </div>
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Receiving Report</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Items</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Value</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $row)
                        @php
                            $rowStatus = ($row->receiving_report_status ?? null) === 'Returned' ? 'returned' : 'pending';
                            $rowSearch = trim(implode(' ', [$row->receiving_report_form_number ?? '', $row->ris_form_number ?? '', $row->authority_purchase_form_number ?? '', $row->item_names ?? '', $row->supplier_name ?? '']));
                        @endphp
                        <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}">
                            <td class="px-4 py-3 text-sm font-semibold">{{ $row->receiving_report_form_number ?: ($row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id)) }}</td>
                            <td class="px-4 py-3 text-sm">{{ \Illuminate\Support\Str::limit($row->item_names ?: '—', 48) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->supplier_name }}</td>
                            <td class="px-4 py-3 text-sm">₱{{ number_format((float) ($row->total_amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $rowStatus === 'returned' ? 'Returned' : 'Pending' }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if(($rows ?? collect())->count()) style="display:none" @endif>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No pending receiving reports.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
</div>
