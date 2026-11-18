{{-- ===================================================== --}}
{{-- RIS TABLE PARTIAL --}}
{{-- ===================================================== --}}

@php
    $risCanAct = \App\Support\AdminPortal::canActOnRis();
    $risAcceptableStatuses = ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];
    $risPageHasAcceptable = $risCanAct
        && $risRecords->contains(fn ($ris) => in_array($ris->ris_status, $risAcceptableStatuses, true));
@endphp

<table class="pur-table w-full table-fixed">

    <thead>
        <tr>
            @if ($risPageHasAcceptable)
            <th class="w-[3%] !px-0 text-center align-middle">
                <input
                    type="checkbox"
                    id="risSelectAllPage"
                    class="block mx-auto h-4 w-4 cursor-pointer rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                    title="Select all acceptable requests on this page"
                    aria-label="Select all acceptable requests on this page"
                    onclick="typeof window.toggleRisSelectAllPage === 'function' && window.toggleRisSelectAllPage(this)"
                >
            </th>
            @endif
            <th class="{{ $risPageHasAcceptable ? 'w-[13%]' : 'w-[14%]' }} text-left">RIS Number</th>
            <th class="w-[11%] text-left">Request No.</th>
            <th class="{{ $risPageHasAcceptable ? 'w-[20%]' : 'w-[24%]' }}">Equipment</th>
            <th class="w-[13%]">Requested By</th>
            <th class="w-[12%]">Status</th>
            <th class="w-[10%] text-right">Amount</th>
            <th class="{{ $risPageHasAcceptable ? 'w-[18%]' : 'w-[16%]' }} text-center">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($risRecords as $ris)
            @php
                $isAcceptable = in_array($ris->ris_status, $risAcceptableStatuses, true);
            @endphp

            <tr class="transition hover:bg-gray-50/70 {{ !$isAcceptable ? 'bg-gray-50/50 text-gray-500' : '' }}">
                @if ($risPageHasAcceptable)
                <td class="!px-0 text-center align-middle">
                    @if ($isAcceptable)
                        <input
                            type="checkbox"
                            class="ris-accept-checkbox block mx-auto h-4 w-4 cursor-pointer rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
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
                @endif

                <td class="text-left">
                    <div
                        class="truncate text-sm font-semibold {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-900' }}"
                        title="{{ \App\Support\RisWorkflow::formNumber($ris) }}"
                    >
                        {{ \App\Support\RisWorkflow::formNumber($ris) }}
                        <div class="mt-0.5">
                            @include('partials.ris-urgency-badge', [
                                'urgent' => \App\Support\RisWorkflow::isUrgent($ris),
                                'size' => 'sm',
                            ])
                        </div>
                    </div>
                </td>

                <td class="text-left">
                    @include('partials.procurement-request-no', ['requestNo' => $ris->ris_request_no ?? null, 'inline' => true, 'showLabel' => false])
                    @if(blank($ris->ris_request_no ?? null))
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>

                <td>
                    @include('admin.partials.ris-equipment-cell', [
                        'ris' => $ris,
                        'muted' => ! $isAcceptable,
                    ])
                    @include('admin.partials.ris-attachments', ['ris' => $ris])
                </td>

                <td>
                    @php
                        $risSubmittedRaw = $ris->ris_submitted_at ?? $ris->ris_requested_by_date ?? null;
                        $risSubmittedLabel = $risSubmittedRaw
                            ? \Carbon\Carbon::parse($risSubmittedRaw)->format('M j, Y')
                            : null;
                    @endphp
                    <div
                        class="truncate text-sm font-medium {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ \App\Support\RisWorkflow::requesterName($ris) }}"
                    >
                        {{ \App\Support\RisWorkflow::requesterName($ris) }}
                    </div>
                    <div
                        class="mt-0.5 whitespace-nowrap text-xs font-medium {{ !$isAcceptable ? 'text-gray-400' : 'text-gray-600' }}"
                        title="{{ $risSubmittedLabel ? 'Submitted '.$risSubmittedLabel : 'Submit date unavailable' }}"
                    >
                        @if ($risSubmittedLabel)
                            Submitted {{ $risSubmittedLabel }}
                        @else
                            Not submitted
                        @endif
                    </div>
                </td>

                <td class="min-w-0 align-middle">
                    @include('admin.partials.ris-status-badge', ['ris' => $ris, 'wrap' => true])
                </td>

                <td
                    class="min-w-0 text-right text-sm font-semibold whitespace-nowrap tabular-nums {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-900' }}"
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

                        @php
                            $passToPurchaser = $risCanAct && \App\Support\RisWorkflow::canReturnForRevision($ris);
                            $ownerPurchaserId = \App\Support\RisWorkflow::ownerPurchaserId($ris);
                            $ownerPurchaserName = \App\Support\RisWorkflow::requesterName($ris, '');
                        @endphp

                        @if($isAcceptable && $risCanAct)
                            <button
                                type="button"
                                onclick="window.openAcceptRisModal('{{ $ris->ris_id }}', @js(\App\Support\RisWorkflow::formNumber($ris)), @js(\App\Support\RisWorkflow::sourceLabel($ris)))"
                                data-tip="Accept"
                                data-tip-place="above"
                                aria-label="Accept"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-[#0025cc] transition hover:border-blue-300 hover:bg-blue-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="window.openAmendModal('{{ $ris->ris_id }}')"
                                data-tip="Return for Revision"
                                data-tip-place="above"
                                aria-label="Return for Revision"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5"></path>
                                </svg>
                            </button>

                            @include('admin.procurement-review._ris-action-menu', [
                                'risId' => $ris->ris_id,
                                'ris' => $ris,
                                'mode' => 'pending',
                                'btnSizeClass' => 'h-9 w-9',
                                'shareLabel' => \App\Support\RequestExplorer::shareUiLabel('ris', $ris->ris_form_number ?? null, $ris->ris_request_no ?? null),
                            ])
                        @elseif($passToPurchaser)
                            <button
                                type="button"
                                onclick="window.openReturnRevisionModal('{{ $ris->ris_id }}', {{ (int) $ownerPurchaserId }}, @js($ownerPurchaserName))"
                                data-tip="Pass to Purchaser"
                                data-tip-place="above"
                                aria-label="Pass to Purchaser"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5"></path>
                                </svg>
                            </button>
                            @include('partials.share-document-button', [
                                'type' => 'ris',
                                'id' => $ris->ris_id,
                                'label' => \App\Support\RequestExplorer::shareUiLabel('ris', $ris->ris_form_number ?? null, $ris->ris_request_no ?? null),
                            ])
                        @else
                            @include('partials.share-document-button', [
                                'type' => 'ris',
                                'id' => $ris->ris_id,
                                'label' => \App\Support\RequestExplorer::shareUiLabel('ris', $ris->ris_form_number ?? null, $ris->ris_request_no ?? null),
                            ])
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $risPageHasAcceptable ? 8 : 7 }}" class="pur-empty">
                    @if (($filter ?? 'pending') === 'pending')
                        No pending procurement requests
                    @elseif (($filter ?? '') === 'revision')
                        No RIS returned for revision
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
