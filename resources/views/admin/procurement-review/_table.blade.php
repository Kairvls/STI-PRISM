{{-- ===================================================== --}}
{{-- RIS TABLE PARTIAL --}}
{{-- ===================================================== --}}

<table class="w-full table-fixed">


    {{-- ================================================= --}}
    {{-- TABLE HEADER --}}
    {{-- ================================================= --}}

    <thead class="border-b border-gray-200 bg-gray-50">

        <tr>

            <th class="w-[4%] px-3 py-2.5 text-center">
                <input
                    type="checkbox"
                    id="risSelectAllPage"
                    class="h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                    title="Select all acceptable requests on this page"
                    aria-label="Select all acceptable requests on this page"
                    onclick="typeof window.toggleRisSelectAllPage === 'function' && window.toggleRisSelectAllPage(this)"
                >
            </th>

            <th class="w-[12%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Reference No.
            </th>

            <th class="w-[24%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Equipment
            </th>

            <th class="w-[14%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Requested By
            </th>

            <th class="w-[17%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Status
            </th>

            <th class="w-[12%] px-3 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Amount
            </th>

            <th class="w-[17%] px-3 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Actions
            </th>

        </tr>

    </thead>


    {{-- ================================================= --}}
    {{-- TABLE BODY --}}
    {{-- ================================================= --}}

    <tbody class="divide-y divide-gray-100">

        @forelse($risRecords as $ris)


            {{-- ================================================= --}}
            {{-- RIS ROW --}}
            {{-- ================================================= --}}

            @php
                $isAcceptable = in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true);
            @endphp

            <tr
                class="
                    transition hover:bg-gray-50/70
                    {{ !$isAcceptable ? 'bg-gray-50 text-gray-500' : '' }}
                "
            >


                {{-- ================================================= --}}
                {{-- SELECT --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5 text-center">
                    @if ($isAcceptable)
                        <input
                            type="checkbox"
                            class="ris-accept-checkbox h-4 w-4 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                            value="{{ $ris->ris_id }}"
                            data-ref="{{ $ris->ris_form_number ?? ('RIS-' . $ris->ris_id) }}"
                            title="Select {{ $ris->ris_form_number ?? ('RIS-' . $ris->ris_id) }}"
                            aria-label="Select {{ $ris->ris_form_number ?? ('RIS-' . $ris->ris_id) }}"
                            onchange="typeof window.updateRisAcceptSelection === 'function' && window.updateRisAcceptSelection()"
                        >
                    @else
                        <span class="inline-block h-4 w-4" aria-hidden="true"></span>
                    @endif
                </td>


                {{-- ================================================= --}}
                {{-- RIS NUMBER --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div
                        class="truncate text-sm font-semibold {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-900' }}"
                        title="{{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}"
                    >

                        {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- EQUIPMENT --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

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


                {{-- ================================================= --}}
                {{-- REQUESTED BY --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div
                        class="truncate text-sm font-medium {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ $ris->ris_requested_by_signature ?? 'Purchaser' }}"
                    >

                        {{ $ris->ris_requested_by_signature ?? 'Purchaser' }}

                    </div>

                    <div
                        class="mt-0.5 truncate text-[11px] text-gray-400"
                        title="Date the RIS was submitted"
                    >

                        {{ $ris->ris_requested_by_date ?? 'N/A' }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- STATUS --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">
                    @include('admin.partials.ris-status-badge', ['ris' => $ris])
                </td>


                {{-- ================================================= --}}
                {{-- AMOUNT (COMPUTED FROM RIS ITEMS) --}}
                {{-- ================================================= --}}

                <td
                    class="px-3 py-2.5 text-right text-sm font-semibold whitespace-nowrap {{ !$isAcceptable ? 'text-gray-500' : 'text-gray-900' }}"
                    title="Total computed amount of this RIS"
                >

                    ₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 2) }}

                </td>


                {{-- ================================================= --}}
                {{-- ACTIONS --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div class="flex items-center justify-center gap-1">

                        <button
                            type="button"
                            onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')"
                            title="Preview this RIS form"
                            aria-label="Preview RIS"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>

                        @include('admin.partials.ris-print-icon-button', [
                            'risId' => $ris->ris_id,
                            'btnClass' => 'inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900',
                        ])

                        @if($isAcceptable)
                            @php
                                $acceptRef = $ris->ris_form_number ?? ('RIS-' . $ris->ris_id);
                                $acceptDetail = \App\Support\RisWorkflow::sourceLabel($ris);
                            @endphp
                            <button
                                type="button"
                                onclick="openAcceptRisModal('{{ $ris->ris_id }}', @js($acceptRef), @js($acceptDetail))"
                                title="Accept and send to Sign RIS"
                                aria-label="Accept procurement request"
                                class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-[#0025cc] px-2.5 text-xs font-semibold text-white transition hover:bg-blue-800"
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
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
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


            {{-- ================================================= --}}
            {{-- EMPTY TABLE --}}
            {{-- ================================================= --}}

            <tr>

                <td
                    colspan="7"
                    class="px-3 py-12 text-center"
                >

                    <div class="mx-auto flex max-w-sm flex-col items-center">

                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400">

                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>

                            </svg>

                        </div>

                        <h3 class="mt-3 text-sm font-semibold text-gray-700">
                            @if (($filter ?? 'pending') === 'pending')
                                No pending procurement requests
                            @elseif (($filter ?? '') === 'accepted')
                                No accepted requests waiting on Sign RIS
                            @else
                                No RIS records found
                            @endif
                        </h3>

                        <p class="mt-1 text-xs text-gray-400">
                            @if (($filter ?? 'pending') === 'pending')
                                There are no purchaser submissions waiting for Admin accept.
                            @elseif (($filter ?? '') === 'accepted')
                                No accepted RIS are waiting for a Sign RIS decision.
                            @else
                                No RIS records match the selected filter or search.
                            @endif
                        </p>

                    </div>

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
