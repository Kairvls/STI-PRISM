{{-- ===================================================== --}}
{{-- RIS TABLE PARTIAL --}}
{{-- ===================================================== --}}

<table class="w-full min-w-[1250px]">


    {{-- ================================================= --}}
    {{-- TABLE HEADER --}}
    {{-- ================================================= --}}

    <thead class="border-b border-gray-200 bg-gray-50">

        <tr>

            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                Reference No.
            </th>

            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                Purpose
            </th>

            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                Equipment
            </th>

            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                Requested By
            </th>

            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                Status
            </th>

            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                Amount
            </th>

            <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
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

            <tr
                class="
                    transition hover:bg-gray-50/70
                    {{ !in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)
                        ? 'bg-gray-50 text-gray-500'
                        : ''
                    }}
                "
            >


                {{-- ================================================= --}}
                {{-- RIS NUMBER --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="text-sm font-semibold {{ !in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) ? 'text-gray-500' : 'text-gray-900' }}"
                        title="RIS Number"
                    >

                        {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- PURPOSE --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="max-w-[220px] truncate text-sm font-medium {{ !in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? 'N/A') }}"
                    >

                        {{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? 'N/A') }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- EQUIPMENT --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="max-w-[220px] truncate text-sm {{ !in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="Items / Equipment included in this RIS"
                    >

                        {{ \App\Support\RisWorkflow::sourceLabel($ris) }}
                        @if(!empty($ris->ris_request_type))
                            <div class="mt-1 text-xs text-gray-400">{{ \App\Support\RisWorkflow::requestTypeLabel($ris) }}</div>
                        @endif
                    </div>
                    @include('admin.partials.ris-attachments', ['ris' => $ris])

                </td>


                {{-- ================================================= --}}
                {{-- REQUESTED BY --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="text-sm font-medium {{ !in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="Person who sent this RIS request"
                    >

                        {{ $ris->ris_requested_by_signature ?? 'Purchaser' }}

                    </div>


                    {{-- Submitted date --}}

                    <div
                        class="mt-1 text-xs text-gray-400"
                        title="Date the RIS was submitted"
                    >

                        {{ $ris->ris_requested_by_date ?? 'N/A' }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- STATUS --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">
                    @include('admin.partials.ris-status-badge', ['ris' => $ris])
                </td>


                {{-- ================================================= --}}
                {{-- AMOUNT (COMPUTED FROM RIS ITEMS) --}}
                {{-- ================================================= --}}

                <td
                    class="px-5 py-4 text-right text-sm font-semibold {{ !in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) ? 'text-gray-500' : 'text-gray-900' }}"
                    title="Total computed amount of this RIS"
                >

                    ₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 2) }}

                </td>


                {{-- ================================================= --}}
                {{-- ACTIONS --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div class="flex items-center justify-center gap-1.5">

                        <button
                            type="button"
                            onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')"
                            title="Preview this RIS form"
                            aria-label="Preview RIS"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>

                        @include('admin.partials.ris-print-icon-button', [
                            'risId' => $ris->ris_id,
                            'btnClass' => 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900',
                        ])

                        @if(in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true))

                            <button
                                type="button"
                                title="Forward this RIS to the President (no Issued by signature required)"
                                aria-label="Forward to President"
                                onclick="openDirectApproveModal('{{ $ris->ris_id }}', 'forward')"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </button>

                            <button
                                type="button"
                                title="Sign Issued by and mark this RIS as Admin Approved for the Purchaser"
                                aria-label="Admin Approve"
                                onclick="openDirectApproveModal('{{ $ris->ris_id }}', 'direct')"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600 text-white transition hover:bg-sky-700"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>

                            <button
                                type="button"
                                title="Return this RIS to the Purchaser for revision (no signature)"
                                aria-label="Amend"
                                onclick="openAmendModal('{{ $ris->ris_id }}')"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="px-5 py-16 text-center"
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
                                No pending RIS forms
                            @elseif (($filter ?? '') === 'forwarded')
                                No forwarded RIS forms
                            @else
                                No RIS records found
                            @endif
                        </h3>

                        <p class="mt-1 text-xs text-gray-400">
                            @if (($filter ?? 'pending') === 'pending')
                                There are no RIS forms waiting for admin review.
                            @elseif (($filter ?? '') === 'forwarded')
                                No RIS forms are currently with the President.
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
