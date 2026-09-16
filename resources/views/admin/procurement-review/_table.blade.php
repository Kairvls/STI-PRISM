{{-- ===================================================== --}}
{{-- RIS TABLE PARTIAL --}}
{{-- ===================================================== --}}

<table class="pur-table w-full min-w-[1020px] table-fixed">

    <thead>
        <tr>
            <th class="w-[4%] text-center">
                <input
                    type="checkbox"
                    id="risSelectAllPage"
                    class="h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                    title="Select all acceptable requests on this page"
                    aria-label="Select all acceptable requests on this page"
                    onclick="typeof window.toggleRisSelectAllPage === 'function' && window.toggleRisSelectAllPage(this)"
                >
            </th>
            <th class="w-[12%]">RIS Number</th>
            <th class="w-[24%]">Equipment</th>
            <th class="w-[14%]">Requested By</th>
            <th class="w-[17%]">Status</th>
            <th class="w-[12%] text-right">Amount</th>
            <th class="w-[17%] text-center">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($risRecords as $ris)
            @php
                $isAcceptable = in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true);
            @endphp

            <tr class="transition hover:bg-gray-50/70 {{ !$isAcceptable ? 'bg-gray-50/50 text-gray-500' : '' }}">
                <td class="text-center">
                    @if ($isAcceptable)
                        <input
                            type="checkbox"
                            class="ris-accept-checkbox h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                            value="{{ $ris->ris_id }}"
                            data-ref="{{ \App\Support\RisWorkflow::formNumber($ris) }}"
                            title="Select {{ \App\Support\RisWorkflow::formNumber($ris) }}"
                            aria-label="Select {{ \App\Support\RisWorkflow::formNumber($ris) }}"
                            onchange="typeof window.updateRisAcceptSelection === 'function' && window.updateRisAcceptSelection()"
                        >
                    @else
                        <span class="inline-block h-4 w-4" aria-hidden="true"></span>
                    @endif
                </td>

                <td>
                    <div
                        class="truncate text-sm font-semibold {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-900' }}"
                        title="{{ \App\Support\RisWorkflow::formNumber($ris) }}"
                    >
                        {{ \App\Support\RisWorkflow::formNumber($ris) }}
                        @if(\App\Support\RisWorkflow::isUrgent($ris))
                            <div class="mt-0.5">
                                <span class="inline-flex items-center rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">Urgent</span>
                            </div>
                        @endif
                    </div>
                </td>

                <td>
                    <div
                        class="truncate text-sm {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ \App\Support\RisWorkflow::sourceLabel($ris) }}"
                    >
                        {{ \App\Support\RisWorkflow::sourceLabel($ris) }}
                        @if(!empty($ris->ris_request_type))
                            <div class="mt-0.5 truncate text-[11px] text-gray-400">{{ \App\Support\RisWorkflow::requestTypeLabel($ris) }}</div>
                        @endif
                    </div>
                    @include('admin.partials.ris-attachments', ['ris' => $ris])
                </td>

                <td>
                    <div
                        class="truncate text-sm font-medium {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ $ris->ris_requested_by_signature ?? 'Purchaser' }}"
                    >
                        {{ $ris->ris_requested_by_signature ?? 'Purchaser' }}
                    </div>
                    <div class="mt-0.5 truncate text-[11px] text-gray-400" title="Date the RIS was submitted">
                        {{ $ris->ris_requested_by_date ?? 'N/A' }}
                    </div>
                </td>

                <td>
                    {{-- Keep existing status badge design --}}
                    @include('admin.partials.ris-status-badge', ['ris' => $ris])
                </td>

                <td
                    class="text-right text-sm font-semibold whitespace-nowrap tabular-nums {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-900' }}"
                    title="Total computed amount of this RIS"
                >
                    ₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 2) }}
                </td>

                <td class="text-center">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button
                            type="button"
                            onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')"
                            title="Preview this RIS form"
                            aria-label="Preview RIS"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>

                        @include('admin.partials.ris-print-icon-button', [
                            'risId' => $ris->ris_id,
                            'btnClass' => 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900',
                        ])

                        @if($isAcceptable)
                            @php
                                $acceptRef = \App\Support\RisWorkflow::formNumber($ris);
                                $acceptDetail = \App\Support\RisWorkflow::sourceLabel($ris);
                            @endphp
                            <button
                                type="button"
                                onclick="openAcceptRisModal('{{ $ris->ris_id }}', @js($acceptRef), @js($acceptDetail))"
                                title="Accept and send to Sign RIS"
                                aria-label="Accept procurement request"
                                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-2.5 text-xs font-semibold text-white transition hover:bg-[#001fa8]"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="openAmendModal('{{ $ris->ris_id }}')"
                                title="Return this RIS to the Purchaser for revision"
                                aria-label="Return for revision"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="pur-empty">
                    @if (($filter ?? 'pending') === 'pending')
                        No pending procurement requests
                    @elseif (($filter ?? '') === 'accepted')
                        No accepted requests waiting on Sign RIS
                    @else
                        No RIS records found
                    @endif
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@include('layouts.partials.table-showing-pager', [
    'pager' => $risRecords,
    'linkClass' => 'ris-pagination-link',
    'noun' => 'records',
])
