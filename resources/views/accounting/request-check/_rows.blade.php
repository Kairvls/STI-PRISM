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
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="font-semibold text-gray-900 transition hover:text-[#0025cc] hover:underline"
                            data-rfc-preview
                            data-preview-url="{{ route('school-admin.rfc.preview', $row->request_check_id) }}"
                            data-review-url="/school-admin/request-check/{{ $row->request_check_id }}?return_status={{ urlencode($filter ?? 'incoming') }}"
                            data-preview-title="{{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}"
                            data-rfc-id="{{ $row->request_check_id }}"
                            data-can-approve="{{ in_array($row->request_check_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) && empty($row->request_check_funds_released_at) ? '1' : '0' }}"
                            title="View RFC form"
                        >
                            {{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}
                        </button>
                        @if (\App\Support\DocumentUrgency::isUrgent('RFC', $row))
                            @include('partials.ris-urgency-badge', ['urgent' => true, 'size' => 'sm', 'title' => 'Urgent RIS'])
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-gray-400">Record #{{ $row->request_check_id }}</p>
                </div>
            </div>
        </td>
        <td class="px-5 py-4">
            @include('partials.procurement-request-no', ['requestNo' => $row->request_check_request_no ?? null, 'inline' => true, 'showLabel' => false])
            @if(blank($row->request_check_request_no ?? null))
                <span class="text-xs text-gray-400">—</span>
            @endif
        </td>
        <td class="px-5 py-4 text-gray-700">{{ $row->authority_purchase_form_number ?? '—' }}</td>
        <td class="px-5 py-4 text-gray-700">{{ $row->ris_form_number ?? '—' }}</td>
        <td class="px-5 py-4 text-gray-600">{{ $row->request_check_payee ?? '—' }}</td>
        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
            {{ $row->request_check_amount_figures !== null ? '₱'.number_format((float) $row->request_check_amount_figures, 2) : '—' }}
        </td>
        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
            @if($when)
                {{ \Carbon\Carbon::parse($when)->format('M j, Y') }}
                <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($when)->format('g:i A') }}</span>
            @else
                <span class="text-gray-400">—</span>
            @endif
        </td>
        <td class="px-5 py-4">
            @include('accounting.partials.status-badge', ['status' => $st])
        </td>
        <td class="px-5 py-4 text-center">
            <div class="inline-flex items-center justify-center gap-1.5">
                <button
                    type="button"
                    data-rfc-preview
                    data-preview-url="{{ route('school-admin.rfc.preview', $row->request_check_id) }}"
                    data-review-url="/school-admin/request-check/{{ $row->request_check_id }}?return_status={{ urlencode($filter ?? 'incoming') }}"
                    data-preview-title="{{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}"
                    data-rfc-id="{{ $row->request_check_id }}"
                    data-can-approve="{{ in_array($row->request_check_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) && empty($row->request_check_funds_released_at) ? '1' : '0' }}"
                    title="View RFC form"
                    aria-label="View RFC form"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
                @include('partials.share-document-button', [
                    'type' => 'rfc',
                    'id' => $row->request_check_id,
                    'label' => \App\Support\RequestExplorer::shareUiLabel('rfc', $row->request_check_form_number ?? null),
                ])
                <x-view-action-button
                    :href="'/school-admin/request-check/'.$row->request_check_id.'?return_status='.urlencode($filter ?? 'incoming')"
                    label="Review"
                    :title="$reviewTip"
                    :aria-label="$reviewTip"
                    :data-tip="$reviewTip"
                />
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9">
            <div class="pur-empty my-2">No Request Check records in this queue.</div>
        </td>
    </tr>
@endforelse
