@forelse ($records as $row)
    <tr class="transition hover:bg-gray-50/70">
        <td class="px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                    <i data-lucide="file-check-2" class="h-4 w-4"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">
                        {{ $row->authority_purchase_form_number ?? 'ATP-' . $row->authority_purchase_id }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">Record #{{ $row->authority_purchase_id }}</p>
                </div>
            </div>
        </td>
        <td class="px-5 py-4">
            <p class="text-sm text-gray-700">{{ $row->ris_form_number ?? '—' }}</p>
        </td>
        <td class="px-5 py-4 text-gray-600">
            {{ $row->company_name ?? $row->shop_name ?? '—' }}
        </td>
        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
            {{ $row->atp_total !== null ? '₱'.number_format((float) $row->atp_total, 2) : '—' }}
        </td>
        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
            {{ $row->authority_purchase_submitted_at ? \Carbon\Carbon::parse($row->authority_purchase_submitted_at)->format('M d, Y') : '—' }}
        </td>
        <td class="px-5 py-4">
            @include('accounting.partials.status-badge', [
                'status' => $row->authority_purchase_status,
                'submitted' => $row->authority_purchase_submitted_at,
                'revision' => $row->authority_purchase_rejection_reason,
            ])
        </td>
        <td class="px-5 py-4 text-right">
            <x-view-action-button
                :href="'/accounting/authority-to-purchase/'.$row->authority_purchase_id.'?return_status='.urlencode($filter ?? 'incoming')"
                label="View"
                title="Review ATP"
                aria-label="Review ATP"
                data-tip="Review ATP"
            />
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7">
            <div class="pur-empty my-2">No ATP records in this queue.</div>
        </td>
    </tr>
@endforelse
