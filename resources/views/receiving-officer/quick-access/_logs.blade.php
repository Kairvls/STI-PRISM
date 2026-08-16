<div class="space-y-3 p-1" data-ro-table data-ro-default-filter="all">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm text-gray-500">Inspection, accept, and return audit trail.</p>
            <a href="/receiving/logs" class="text-xs font-semibold text-[#0037c7]">Open full page</a>
        </div>
        <span class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ ($rows ?? collect())->count() }} total</span>
    </div>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        @include('layouts.partials.receiving-filter-slider', [
            'sliderId' => 'qaLogsFilterSlider',
            'current' => 'all',
            'options' => [
                ['filter' => 'all', 'label' => 'All'],
                ['filter' => 'accepted', 'label' => 'Accepted'],
                ['filter' => 'returned', 'label' => 'Returned'],
            ],
        ])
        @include('layouts.partials.receiving-filters', ['searchId' => 'qaLogsSearch', 'placeholder' => 'Search action, reference...'])
    </div>
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Timestamp</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Action</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Reference</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Officer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $log)
                        @php
                            $action = strtolower((string) $log->receiving_log_action);
                            $rowStatus = str_contains($action, 'return') ? 'returned' : (str_contains($action, 'accept') ? 'accepted' : 'all');
                            $rowSearch = trim(implode(' ', [$log->receiving_log_action ?? '', $log->ris_form_number ?? '', $log->authority_purchase_form_number ?? '', $log->officer_name ?? '']));
                        @endphp
                        <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->receiving_log_created_at ? \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A') : '—' }}</td>
                            <td class="px-4 py-3 text-sm font-semibold">{{ $log->receiving_log_action }}</td>
                            <td class="px-4 py-3 text-sm">{{ $log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—') }}</td>
                            <td class="px-4 py-3 text-sm">{{ $log->officer_name ?: 'Receiving Officer' }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if(($rows ?? collect())->count()) style="display:none" @endif>
                        <td colspan="4" class="px-4 py-12 text-center text-sm text-gray-400">No receiving logs yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
</div>
