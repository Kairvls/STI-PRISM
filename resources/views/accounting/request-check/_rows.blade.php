@forelse ($records as $row)
    @php
        $st = $row->request_check_status;
        if (!empty($row->request_check_funds_collected_at)) { $st = 'Check collected'; }
        elseif (!empty($row->request_check_funds_released_at)) { $st = 'Released'; }
        $when = $row->request_check_submitted_at ?? $row->request_check_date ?? $row->request_check_created_at;
        $reviewTip = ($st === 'Released' || $st === 'Approved') ? 'View request check' : 'Review request check';
        $rawPaymentPath = $row->request_check_funding_type ?? ($row->authority_purchase_payment_path ?? null);
        $paymentPath = \App\Support\ProcurementPaymentPath::normalize(is_string($rawPaymentPath) ? $rawPaymentPath : null);
        $paymentLabel = $paymentPath ? \App\Support\ProcurementPaymentPath::label($paymentPath) : '';
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
                            data-payment-method="{{ $paymentPath ?? '' }}"
                            data-payment-label="{{ $paymentLabel }}"
                            data-purchaser-name="{{ $row->request_check_requested_by ?? '' }}"
                            data-payee-name="{{ $row->request_check_payee ?? '' }}"
                            data-amount-label="{{ isset($row->request_check_amount_figures) && $row->request_check_amount_figures !== '' ? '₱'.number_format((float) $row->request_check_amount_figures, 2) : '—' }}"
                            data-can-approve="{{ in_array($row->request_check_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) && empty($row->request_check_funds_released_at) ? '1' : '0' }}"
                            title="View RFC form"
                        >
                            {{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}
                        </button>
                        @include('partials.ris-urgency-badge', [
                            'urgent' => \App\Support\DocumentUrgency::isUrgent('RFC', $row),
                            'size' => 'sm',
                            'title' => 'Inherited from linked RIS',
                        ])
                        @include('partials.payment-method-badge', [
                            'path' => $paymentPath,
                            'size' => 'sm',
                        ])
                    </div>
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
        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
            @if($when)
                {{ \Carbon\Carbon::parse($when)->format('M j, Y') }}
                <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($when)->format('g:i A') }}</span>
            @else
                <span class="text-gray-400">—</span>
            @endif
        </td>
        <td class="px-5 py-4">
            @php
                $rfcStatusDisplay = match (true) {
                    $st === 'Check collected' => [
                        'pill' => 'Collected',
                        'sub' => 'Check collected',
                        'bg' => '#f0fdfa',
                        'fg' => '#0f766e',
                        'border' => '#99f6e4',
                        'dot' => '#0d9488',
                    ],
                    $st === 'Released' => [
                        'pill' => 'Released',
                        'sub' => 'Check released',
                        'bg' => '#eff6ff',
                        'fg' => '#1d4ed8',
                        'border' => '#bfdbfe',
                        'dot' => '#2563eb',
                    ],
                    $st === 'Approved' => [
                        'pill' => 'Approved',
                        'sub' => 'Sent to Accounting',
                        'bg' => '#ecfdf5',
                        'fg' => '#047857',
                        'border' => '#a7f3d0',
                        'dot' => '#059669',
                    ],
                    in_array($st, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) => [
                        'pill' => 'Pending',
                        'sub' => 'Awaiting approval',
                        'bg' => '#fffbeb',
                        'fg' => '#b45309',
                        'border' => '#fde68a',
                        'dot' => '#d97706',
                    ],
                    in_array($st, ['Returned', 'For revision', 'Minor Revision', 'Revision', 'Revision required'], true) => [
                        'pill' => 'Returned',
                        'sub' => 'For revision',
                        'bg' => '#fff7ed',
                        'fg' => '#c2410c',
                        'border' => '#fed7aa',
                        'dot' => '#ea580c',
                    ],
                    $st === 'Rejected' => [
                        'pill' => 'Rejected',
                        'sub' => 'By School Administrator',
                        'bg' => '#fef2f2',
                        'fg' => '#dc2626',
                        'border' => '#fecaca',
                        'dot' => '#dc2626',
                    ],
                    default => null,
                };
            @endphp
            @if ($rfcStatusDisplay)
                <div class="inline-flex flex-col items-start">
                    <span
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                        style="background:{{ $rfcStatusDisplay['bg'] }};color:{{ $rfcStatusDisplay['fg'] }};border-color:{{ $rfcStatusDisplay['border'] }};"
                    >
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $rfcStatusDisplay['dot'] }};"></span>
                        <span>{{ $rfcStatusDisplay['pill'] }}</span>
                    </span>
                    <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">{{ $rfcStatusDisplay['sub'] }}</div>
                </div>
            @else
                @include('accounting.partials.status-badge', ['status' => $st, 'doc' => 'rfc'])
            @endif
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
                    data-payment-method="{{ $paymentPath ?? '' }}"
                    data-payment-label="{{ $paymentLabel }}"
                    data-purchaser-name="{{ $row->request_check_requested_by ?? '' }}"
                    data-payee-name="{{ $row->request_check_payee ?? '' }}"
                    data-amount-label="{{ isset($row->request_check_amount_figures) && $row->request_check_amount_figures !== '' ? '₱'.number_format((float) $row->request_check_amount_figures, 2) : '—' }}"
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
