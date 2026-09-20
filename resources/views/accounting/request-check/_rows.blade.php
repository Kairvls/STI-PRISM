@forelse ($records as $row)
    @php
        $st = $row->request_check_status;
        if (!empty($row->request_check_funds_released_at)) { $st = 'Released'; }
        $when = $row->request_check_submitted_at ?? $row->request_check_date ?? $row->request_check_created_at;
        $reviewTip = ($st === 'Released' || $st === 'Approved') ? 'View request check' : 'Review request check';
    @endphp
    <tr class="transition hover:bg-gray-50/70">
        <td class="px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">
                        {{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">Record #{{ $row->request_check_id }}</p>
                </div>
            </div>
        </td>
        <td class="px-5 py-4 text-gray-700">{{ $row->authority_purchase_form_number ?? '—' }}</td>
        <td class="px-5 py-4 text-gray-700">{{ $row->ris_form_number ?? '—' }}</td>
        <td class="px-5 py-4 text-gray-600">{{ $row->request_check_payee ?? '—' }}</td>
        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
            {{ $row->request_check_amount_figures !== null ? '₱'.number_format((float) $row->request_check_amount_figures, 2) : '—' }}
        </td>
        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
            {{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}
        </td>
        <td class="px-5 py-4">
            @include('accounting.partials.status-badge', ['status' => $st])
        </td>
        <td class="px-5 py-4 text-right">
            <x-view-action-button
                :href="'/accounting/request-check/'.$row->request_check_id.'?return_status='.urlencode($filter ?? 'incoming')"
                label="View"
                :title="$reviewTip"
                :aria-label="$reviewTip"
                :data-tip="$reviewTip"
            />
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8">
            <div class="pur-empty my-2">No Request Check records in this queue.</div>
        </td>
    </tr>
@endforelse
