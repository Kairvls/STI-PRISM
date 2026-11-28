@php
    $saRfcItems = $records instanceof \Illuminate\Contracts\Pagination\Paginator
        ? $records->getCollection()
        : collect($records);
    $saRfcGroups = $saRfcItems->groupBy(function ($row) {
        $reqNo = trim((string) ($row->request_check_request_no ?? ''));
        if ($reqNo !== '') {
            return 'sa-req-'.preg_replace('/[^A-Za-z0-9_-]/', '', $reqNo);
        }
        return 'sa-rfc-'.(int) $row->request_check_id;
    });
@endphp
@forelse ($saRfcGroups as $gKey => $gItems)
    @php
        $gItems = $gItems->values();
        $gCount = $gItems->count();
        $gMulti = $gCount > 1;
        $gFirst = $gItems->first();
        $gReqNo = $gFirst->request_check_request_no ?? null;
        $gUrgent = $gItems->contains(fn ($r) => \App\Support\DocumentUrgency::isUrgent('RFC', $r));
        $gPayees = $gItems->pluck('request_check_payee')->filter()->unique()->values();
        $gPayeeSummary = $gPayees->count() === 1 ? (string) $gPayees->first() : ($gPayees->count() > 1 ? $gPayees->count().' payees' : '—');
        $gWhen = $gItems->map(fn ($r) => $r->request_check_submitted_at ?? $r->request_check_date ?? $r->request_check_created_at)->filter()->sort()->last();
        $gPayPaths = $gItems->map(fn ($r) => \App\Support\ProcurementPaymentPath::normalize(is_string($r->request_check_funding_type ?? ($r->authority_purchase_payment_path ?? null)) ? ($r->request_check_funding_type ?? ($r->authority_purchase_payment_path ?? null)) : null))->filter()->unique()->values();
        $gUniformPath = $gPayPaths->count() === 1 ? $gPayPaths->first() : null;

        $gAllSlides = $gItems->map(function ($r) use ($filter) {
            $rawP = $r->request_check_funding_type ?? ($r->authority_purchase_payment_path ?? null);
            $normP = \App\Support\ProcurementPaymentPath::normalize(is_string($rawP) ? $rawP : null);
            $pLabel = $normP ? \App\Support\ProcurementPaymentPath::label($normP) : '';
            $canApp = in_array($r->request_check_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) && empty($r->request_check_funds_released_at);
            return [
                'id' => (int) $r->request_check_id,
                'title' => (string) ($r->request_check_form_number ?? ('RFC-'.$r->request_check_id)),
                'preview_url' => route('school-admin.rfc.preview', $r->request_check_id),
                'review_url' => '/school-admin/request-check/'.$r->request_check_id.'?return_status='.urlencode($filter ?? 'incoming'),
                'payment_method' => (string) ($normP ?? ''),
                'payment_label' => (string) $pLabel,
                'purchaser' => (string) ($r->request_check_requested_by ?? 'Purchaser'),
                'payee' => (string) ($r->request_check_payee ?? '—'),
                'amount' => isset($r->request_check_amount_figures) && $r->request_check_amount_figures !== '' ? (float) $r->request_check_amount_figures : 0.0,
                'amount_label' => isset($r->request_check_amount_figures) && $r->request_check_amount_figures !== '' ? '₱'.number_format((float) $r->request_check_amount_figures, 2) : '—',
                'can_approve' => $canApp,
            ];
        })->values();
        $gPendingSlides = $gAllSlides->filter(fn ($s) => !empty($s['can_approve']))->values();
        $gPendingCount = $gPendingSlides->count();
        $gReleasedCount = $gItems->filter(fn ($r) => !empty($r->request_check_funds_released_at))->count();
        $gCarouselPayload = [
            'request_no' => (string) ($gReqNo ?: 'Grouped RFCs'),
            'can_approve_all' => $gPendingCount >= 2,
            'slides' => ($gPendingCount >= 2 ? $gPendingSlides : $gAllSlides)->all(),
            'total_amount' => round((float) ($gPendingCount >= 2 ? $gPendingSlides : $gAllSlides)->sum('amount'), 2),
        ];
    @endphp
    @if ($gMulti)
        <tr
            class="cursor-pointer bg-slate-50/70 transition hover:bg-blue-50/40"
            data-sa-rfc-group="{{ $gKey }}"
        >
            <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-blue-100 bg-blue-50/70 text-[#0025cc]">
                        <i data-lucide="layers" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900">{{ $gReqNo ?: 'Grouped RFCs' }}</p>
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-bold"
                                style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;"
                            >{{ $gCount }} RFCs</span>
                            @include('partials.ris-urgency-badge', [
                                'urgent' => $gUrgent,
                                'size' => 'sm',
                                'title' => 'Inherited from linked RIS',
                            ])
                            @if ($gUniformPath)
                                @include('partials.payment-method-badge', [
                                    'path' => $gUniformPath,
                                    'size' => 'sm',
                                ])
                            @endif
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-500">Click to expand individual RFCs</p>
                    </div>
                </div>
            </td>
            <td class="px-5 py-4">
                @include('partials.procurement-request-no', ['requestNo' => $gReqNo, 'inline' => true, 'showLabel' => false])
            </td>
            <td class="px-5 py-4 text-xs font-medium text-gray-700">{{ $gCount }} ATPs</td>
            <td class="px-5 py-4 text-gray-700">{{ $gFirst->ris_form_number ?? '—' }}</td>
            <td class="px-5 py-4 text-gray-600">{{ $gPayeeSummary }}</td>
            <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                @if($gWhen)
                    {{ \Carbon\Carbon::parse($gWhen)->format('M j, Y') }}
                    <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($gWhen)->format('g:i A') }}</span>
                @else
                    <span class="text-gray-400">—</span>
                @endif
            </td>
            <td class="px-5 py-4">
                <div class="inline-flex flex-col items-start">
                    @if ($gPendingCount > 0)
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#d97706;"></span>
                            <span>Pending</span>
                        </span>
                        <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">{{ $gPendingCount }} awaiting approval</div>
                    @elseif ($gReleasedCount === $gCount)
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#2563eb;"></span>
                            <span>Released</span>
                        </span>
                        <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">All checks released</div>
                    @else
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background:#ecfdf5;color:#047857;border-color:#a7f3d0;">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#059669;"></span>
                            <span>Approved</span>
                        </span>
                        <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">Sent to Accounting</div>
                    @endif
                </div>
            </td>
            <td class="px-5 py-4 text-center">
                <div class="inline-flex items-center justify-center gap-1.5">
                    @if ($gPendingCount >= 2)
                        <button
                            type="button"
                            data-sa-rfc-group-carousel
                            data-group-payload='@json($gCarouselPayload)'
                            class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-sm transition"
                            style="background:#0025cc;color:#ffffff;"
                            onmouseenter="this.style.background='#001db3'"
                            onmouseleave="this.style.background='#0025cc'"
                            title="Review & Approve all {{ $gPendingCount }} RFCs in carousel"
                            aria-label="Review & Approve all {{ $gPendingCount }} RFCs in carousel"
                        >
                            <i data-lucide="check-check" class="h-4 w-4"></i>
                            <span
                                class="pointer-events-none absolute -right-1.5 -top-1.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white"
                                style="background:#f59e0b;"
                            >{{ $gPendingCount }}</span>
                        </button>
                    @else
                        <button
                            type="button"
                            data-sa-rfc-group-carousel
                            data-group-payload='@json($gCarouselPayload)'
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-[#0025cc] transition hover:bg-blue-100"
                            title="View all {{ $gCount }} RFCs in carousel"
                            aria-label="View all {{ $gCount }} RFCs in carousel"
                        >
                            <i data-lucide="layers" class="h-4 w-4"></i>
                        </button>
                    @endif
                    <button
                        type="button"
                        data-sa-rfc-toggle="{{ $gKey }}"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                        title="Expand RFCs"
                        aria-label="Expand RFCs"
                    >
                        <svg data-sa-rfc-chevron class="h-4 w-4 transition-transform duration-150" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    @endif
    @foreach ($gItems as $row)
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
    <tr
        class="{{ $gMulti ? 'bg-white transition hover:bg-blue-50/20' : 'transition hover:bg-gray-50/70' }}"
        @if ($gMulti) data-sa-rfc-child="{{ $gKey }}" style="display:none;" @endif
    >
        <td class="relative px-5 py-4 {{ $gMulti ? 'pl-14' : '' }}">
            @if ($gMulti)
                <span class="pointer-events-none absolute left-[37px] top-0 w-px bg-slate-200 {{ $loop->last ? 'h-1/2' : 'h-full' }}" aria-hidden="true"></span>
                <span class="pointer-events-none absolute left-[37px] top-1/2 h-px w-4 bg-slate-200" aria-hidden="true"></span>
            @endif
            <div class="flex items-center gap-3">
                @unless ($gMulti)
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                        <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                    </div>
                @endunless
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
    @endforeach
@empty
    <tr>
        <td colspan="9">
            <div class="pur-empty my-2">No Request Check records in this queue.</div>
        </td>
    </tr>
@endforelse
