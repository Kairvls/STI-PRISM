@forelse ($records as $row)
    <tr class="transition hover:bg-gray-50/70">
        <td class="px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                    <i data-lucide="file-check-2" class="h-4 w-4"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="font-semibold text-gray-900 transition hover:text-[#0025cc] hover:underline"
                            data-atp-preview
                            data-preview-url="{{ route('school-admin.atp.preview', $row->authority_purchase_id) }}"
                            data-review-url="/school-admin/authority-to-purchase/{{ $row->authority_purchase_id }}?return_status={{ urlencode($filter ?? 'incoming') }}"
                            data-preview-title="{{ $row->authority_purchase_form_number ?? ('ATP-'.$row->authority_purchase_id) }}"
                            data-atp-id="{{ $row->authority_purchase_id }}"
                            data-can-approve="{{ ($row->authority_purchase_status ?? '') === 'Pending' && ! empty($row->authority_purchase_submitted_at) ? '1' : '0' }}"
                            title="View ATP form"
                        >
                            {{ $row->authority_purchase_form_number ?? 'ATP-' . $row->authority_purchase_id }}
                        </button>
                        @if (\App\Support\DocumentUrgency::isUrgent('ATP', $row))
                            @include('partials.ris-urgency-badge', ['urgent' => true, 'size' => 'sm', 'title' => 'Urgent RIS'])
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-gray-400">Record #{{ $row->authority_purchase_id }}</p>
                </div>
            </div>
        </td>
        <td class="px-5 py-4">
            @include('partials.procurement-request-no', ['requestNo' => $row->authority_purchase_request_no ?? null, 'inline' => true, 'showLabel' => false])
            @if(blank($row->authority_purchase_request_no ?? null))
                <span class="text-xs text-gray-400">—</span>
            @endif
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
            @if($row->authority_purchase_submitted_at)
                {{ \Carbon\Carbon::parse($row->authority_purchase_submitted_at)->format('M j, Y') }}
                <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($row->authority_purchase_submitted_at)->format('g:i A') }}</span>
            @else
                <span class="text-gray-400">—</span>
            @endif
        </td>
        <td class="px-5 py-4">
            @include('accounting.partials.status-badge', [
                'status' => $row->authority_purchase_status,
                'submitted' => $row->authority_purchase_submitted_at,
                'revision' => $row->authority_purchase_rejection_reason,
            ])
        </td>
        <td class="px-5 py-4 text-center">
            <div class="inline-flex items-center justify-center gap-1.5">
                <button
                    type="button"
                    data-atp-preview
                    data-preview-url="{{ route('school-admin.atp.preview', $row->authority_purchase_id) }}"
                    data-review-url="/school-admin/authority-to-purchase/{{ $row->authority_purchase_id }}?return_status={{ urlencode($filter ?? 'incoming') }}"
                    data-preview-title="{{ $row->authority_purchase_form_number ?? ('ATP-'.$row->authority_purchase_id) }}"
                    data-atp-id="{{ $row->authority_purchase_id }}"
                    data-can-approve="{{ ($row->authority_purchase_status ?? '') === 'Pending' && ! empty($row->authority_purchase_submitted_at) ? '1' : '0' }}"
                    title="View ATP form"
                    aria-label="View ATP form"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
                @include('partials.share-document-button', [
                    'type' => 'atp',
                    'id' => $row->authority_purchase_id,
                    'label' => \App\Support\RequestExplorer::shareUiLabel('atp', $row->authority_purchase_form_number ?? null),
                ])
                <x-view-action-button
                    :href="'/school-admin/authority-to-purchase/'.$row->authority_purchase_id.'?return_status='.urlencode($filter ?? 'incoming')"
                    label="Review"
                    title="Review ATP — form and pipeline"
                    aria-label="Review ATP"
                    data-tip="Review ATP — form and pipeline"
                />
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8">
            <div class="pur-empty my-2">No ATP records in this queue.</div>
        </td>
    </tr>
@endforelse
