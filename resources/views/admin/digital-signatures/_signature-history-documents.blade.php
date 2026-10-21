<table class="pur-table w-full min-w-[860px]">
    <thead>
        <tr>
            <th>Number</th>
            <th>Request No.</th>
            <th>Details</th>
            <th>Status</th>
            <th class="text-right">Amount</th>
            <th>Date</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($signatureHistory as $row)
            <tr class="transition hover:bg-gray-50/70">
                <td class="text-sm font-semibold text-gray-900">{{ $row->number }}</td>
                <td>
                    @if (filled($row->request_no))
                        @include('partials.procurement-request-no', ['requestNo' => $row->request_no, 'inline' => true, 'showLabel' => false])
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                <td class="text-sm text-gray-700">{{ $row->detail }}</td>
                <td class="text-sm text-gray-700">{{ $row->status }}</td>
                <td class="text-right text-sm font-semibold tabular-nums text-gray-900">₱{{ number_format((float) $row->amount, 2) }}</td>
                <td class="whitespace-nowrap text-sm text-gray-600">
                    {{ $row->decided_at ? \Carbon\Carbon::parse($row->decided_at)->format('d/m/Y') : '—' }}
                </td>
                <td class="text-center">
                    @if ($row->url)
                        <a
                            href="{{ $row->url }}"
                            class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            View
                        </a>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-16 text-center text-sm text-gray-400">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@include('layouts.partials.table-showing-pager', [
    'pager' => $signatureHistory,
    'linkClass' => 'signature-history-pagination-link',
    'noun' => 'records',
])
