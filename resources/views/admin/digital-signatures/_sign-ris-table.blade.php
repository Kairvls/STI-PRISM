{{-- ===================================================== --}}
{{-- SIGN RIS TABLE PARTIAL --}}
{{-- ===================================================== --}}

<table class="pur-table w-full min-w-[980px] table-fixed">

    <thead>
        <tr>
            <th class="w-[13%]">RIS Number</th>
            <th class="w-[26%]">Equipment</th>
            <th class="w-[15%]">Requested By</th>
            <th class="w-[18%]">Status</th>
            <th class="w-[12%] text-right">Amount</th>
            <th class="w-[16%] text-center">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($signableRisRecords as $ris)
            @php
                $awaitingSign = \App\Support\RisWorkflow::needsAdminIssuedBy($ris);
                $needsDecision = \App\Support\RisWorkflow::needsSignDecision($ris);
                $isPresidentRejected = \App\Support\RisWorkflow::canReturnForRevision($ris);
                $rowDimmed = !$needsDecision && !$awaitingSign && !$isPresidentRejected;
            @endphp

            <tr class="transition hover:bg-gray-50/70 {{ $rowDimmed ? 'bg-gray-50/50 text-gray-500' : '' }}">
                <td>
                    <div
                        class="truncate text-sm font-semibold {{ $rowDimmed ? 'text-gray-500' : 'text-gray-900' }}"
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
                        class="truncate text-sm {{ $rowDimmed ? 'text-gray-500' : 'text-gray-700' }}"
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
                        class="truncate text-sm font-medium {{ $rowDimmed ? 'text-gray-500' : 'text-gray-700' }}"
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
                    class="text-right text-sm font-semibold whitespace-nowrap tabular-nums {{ $rowDimmed ? 'text-gray-500' : 'text-gray-900' }}"
                    title="Total computed amount of this RIS"
                >
                    ₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 2) }}
                </td>

                <td class="text-center">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        <button
                            type="button"
                            onclick="window.openSignRisPreviewModal('{{ $ris->ris_id }}')"
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

                        @if($needsDecision)
                            @include('admin.procurement-review._ris-action-menu', [
                                'risId' => $ris->ris_id,
                                'btnSizeClass' => 'h-9 w-9',
                            ])
                        @endif

                        @if($awaitingSign)
                            <button
                                type="button"
                                onclick="window.openCoSignModal('{{ $ris->ris_id }}')"
                                title="Sign Issued by on this President-approved RIS"
                                aria-label="Sign Issued by"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-3.172-3.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"></path>
                                </svg>
                            </button>
                        @endif

                        @if($isPresidentRejected)
                            <button
                                type="button"
                                onclick="window.openReturnRevisionModal('{{ $ris->ris_id }}')"
                                title="Return this President-rejected RIS to Purchaser for Minor Revision"
                                aria-label="Return for revision"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="pur-empty">No RIS records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@include('layouts.partials.table-showing-pager', [
    'pager' => $signableRisRecords,
    'linkClass' => 'sign-ris-pagination-link',
    'noun' => 'records',
])
