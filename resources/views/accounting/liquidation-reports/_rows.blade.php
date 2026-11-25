@forelse ($records as $row)
    @php
        $when = $row->liquidation_report_submitted_at ?? $row->liquidation_report_date_submitted ?? $row->liquidation_report_created_at;
        $deadline = $row->liquidation_report_submission_deadline ?? null;
        $deadlineQs = !empty($deadlineFilter ?? null) ? '&deadline=' . urlencode($deadlineFilter) : '';
    @endphp
    <tr class="transition hover:bg-gray-50/70">
        <td class="px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                    <i data-lucide="receipt" class="h-4 w-4"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-gray-900">
                            {{ $row->liquidation_report_form_number ?? ('LIQ-'.$row->liquidation_report_id) }}
                        </p>
                        @include('accounting.partials.deadline-badge', ['deadline' => $deadline])
                        @include('partials.ris-urgency-badge', [
                            'urgent' => \App\Support\DocumentUrgency::isUrgent('LIQ', $row),
                            'size' => 'sm',
                            'title' => 'Inherited from linked RIS',
                        ])
                    </div>
                </div>
            </div>
        </td>
        <td class="px-5 py-4">
            @include('partials.procurement-request-no', ['requestNo' => $row->liquidation_report_request_no ?? null, 'inline' => true, 'showLabel' => false])
            @if(blank($row->liquidation_report_request_no ?? null))
                <span class="text-xs text-gray-400">—</span>
            @endif
        </td>
        <td class="px-5 py-4 text-gray-700">{{ $row->receiving_report_form_number ?? '—' }}</td>
        <td class="px-5 py-4 text-gray-600">{{ $row->liquidation_report_employee_name ?? '—' }}</td>
        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
            <div>{{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}</div>
            @if ($deadline)
                <div class="mt-0.5 text-[10px] font-medium text-gray-400">
                    Deadline {{ \Carbon\Carbon::parse($deadline)->format('M d, Y') }}
                </div>
            @endif
        </td>
        <td class="px-5 py-4">
            @php
                $accLiqStatus = trim((string) ($row->liquidation_report_status ?? ''));
                $accLiqReviewable = in_array($accLiqStatus, ['Submitted', 'Under Review', 'Resubmitted'], true);
                $accLiqBadge = match (true) {
                    $accLiqReviewable => [
                        'pill' => 'Needs Verification',
                        'sub' => 'Submitted by Purchaser',
                        'bg' => '#eef2ff', 'fg' => '#4338ca', 'border' => '#c7d2fe', 'dot' => '#4f46e5',
                    ],
                    in_array($accLiqStatus, ['Minor Revision', 'Returned', 'For revision'], true) => [
                        'pill' => 'For Revision',
                        'sub' => 'Sent back to Purchaser',
                        'bg' => '#fff7ed', 'fg' => '#c2410c', 'border' => '#fed7aa', 'dot' => '#ea580c',
                    ],
                    $accLiqStatus === 'Approved' => [
                        'pill' => 'Verified',
                        'sub' => 'Transaction completed',
                        'bg' => '#ecfdf5', 'fg' => '#047857', 'border' => '#a7f3d0', 'dot' => '#059669',
                    ],
                    $accLiqStatus === 'Rejected' => [
                        'pill' => 'Rejected',
                        'sub' => 'Not approved',
                        'bg' => '#fef2f2', 'fg' => '#b91c1c', 'border' => '#fecaca', 'dot' => '#dc2626',
                    ],
                    default => [
                        'pill' => \App\Support\ProcurementStatus::label($accLiqStatus, 'liq'),
                        'sub' => '',
                        'bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#cbd5e1', 'dot' => '#64748b',
                    ],
                };
            @endphp
            <div class="inline-flex flex-col items-start">
                <span
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                    style="background:{{ $accLiqBadge['bg'] }};color:{{ $accLiqBadge['fg'] }};border-color:{{ $accLiqBadge['border'] }};"
                >
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $accLiqBadge['dot'] }};"></span>
                    <span>{{ $accLiqBadge['pill'] }}</span>
                </span>
                @if($accLiqBadge['sub'] !== '')
                    <span class="mt-1 whitespace-nowrap text-[11px] font-medium leading-tight text-slate-500">
                        {{ $accLiqBadge['sub'] }}
                    </span>
                @endif
            </div>
        </td>
        <td class="px-5 py-4 text-right">
            <div class="inline-flex items-center justify-end gap-1.5">
                @if($accLiqReviewable)
                    <button
                        type="button"
                        onclick="window.openLiqVerifyModal({{ (int) $row->liquidation_report_id }})"
                        class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition hover:opacity-85"
                        style="background:#eff6ff;color:#0025cc;"
                        data-tip="Verify liquidation"
                        data-tip-place="above"
                        aria-label="Verify liquidation"
                    >
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                        <span class="pointer-events-none absolute -right-1 -top-1 flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white"></span>
                        </span>
                    </button>
                    <button
                        type="button"
                        onclick="window.openLiqReviseModal({{ (int) $row->liquidation_report_id }}, @js($row->liquidation_report_form_number ?? ('LIQ-'.$row->liquidation_report_id)), @js($row->liquidation_report_employee_name ?: 'Purchaser'))"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border transition"
                        style="background:#fff7ed;border-color:#fdba74;color:#c2410c;"
                        onmouseenter="this.style.background='#ffedd5'"
                        onmouseleave="this.style.background='#fff7ed'"
                        data-tip="Return for revision"
                        data-tip-place="above"
                        aria-label="Return for revision"
                    >
                        <i data-lucide="undo-2" class="h-4 w-4"></i>
                    </button>
                    <template
                        id="acc-liq-paper-{{ (int) $row->liquidation_report_id }}"
                        data-form-number="{{ $row->liquidation_report_form_number ?? ('LIQ-'.$row->liquidation_report_id) }}"
                        data-rr-number="{{ $row->receiving_report_form_number ?? '—' }}"
                        data-employee="{{ $row->liquidation_report_employee_name ?? '—' }}"
                        data-attachments='@json($row->preloaded_attachments ?? [])'
                    >@include('partials.liquidation-report-paper', [
                        'editable' => false,
                        'liq' => $row,
                        'rows' => $row->preloaded_rows ?? collect(),
                        'accLiveSign' => true,
                    ])</template>
                @endif
                <x-view-action-button
                    :href="'/accounting/liquidation-reports/'.$row->liquidation_report_id.'?return_status='.urlencode($filter ?? 'incoming').$deadlineQs"
                    label="View"
                    title="Review liquidation"
                    aria-label="Review liquidation"
                    data-tip="Review liquidation"
                />
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8">
            <div class="pur-empty my-2">No liquidation reports in this queue.</div>
        </td>
    </tr>
@endforelse
