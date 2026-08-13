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

                        {{ $ris->ris_item_names
                            ?: ($ris->ris_manual_title
                                ?: ($ris->equipment_name
                                    ?? $ris->report_unlisted_equipment_name
                                    ?? ($ris->ris_request_type === 'manual' ? 'Manual Procurement' : 'Unknown Equipment'))) }}

                    </div>

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


                    {{-- PENDING (new workflow + legacy) --}}

                    @if(in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true))

                        <span
                            class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            title="This RIS is waiting for review"
                        >
                            Pending
                        </span>


                    {{-- DIRECTLY APPROVED --}}

                    @elseif(
                        $ris->ris_status === 'Directly Approved'
                        || (
                            $ris->ris_status === 'Approved'
                            && !empty($ris->ris_approved_by_date)
                            && !empty($ris->ris_approved_by_signature)
                            && !str_starts_with($ris->ris_approved_by_signature, 'data:image')
                            && empty($ris->ris_issued_by_date)
                        )
                    )

                        <span
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-900"
                            title="This RIS has been directly approved by Admin and returned to Purchaser"
                        >
                            Directly Approved
                        </span>


                    {{-- FORWARDED TO PRESIDENT --}}
                    {{-- (Approved AND has approved_by_date, but no signature or base64 sig) --}}

                    @elseif($ris->ris_status === 'Approved' && !empty($ris->ris_approved_by_date))

                        <span
                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            title="This RIS has been forwarded to the President"
                        >
                            Forwarded to President
                        </span>


                    {{-- AMEND --}}
                    {{-- New workflow uses Minor Revision; legacy uses Rejected --}}

                    @elseif(in_array($ris->ris_status, ['Minor Revision', 'Rejected'], true))

                        <span
                            class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700"
                            title="This RIS was returned for amendment"
                        >
                            Amend
                        </span>


                    {{-- OTHER STATUS --}}

                    @else

                        <span
                            class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-600"
                            title="Current RIS status"
                        >
                            {{ $ris->ris_status }}
                        </span>

                    @endif

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

                        @if(in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true))

                            <button
                                type="button"
                                title="Sign Issued by, then forward this RIS to the President"
                                aria-label="Forward to President"
                                onclick="openDirectApproveModal('{{ $ris->ris_id }}', 'forward')"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </button>

                            <button
                                type="button"
                                title="Sign Issued by and directly approve this RIS for the Purchaser"
                                aria-label="Direct Approval"
                                onclick="openDirectApproveModal('{{ $ris->ris_id }}', 'direct')"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white transition hover:bg-slate-800"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>

                            <button
                                type="button"
                                title="Return this RIS to the Purchaser for amendment with revision notes"
                                aria-label="Amend"
                                onclick="openAmendModal('{{ $ris->ris_id }}')"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
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
                            No RIS records found
                        </h3>

                        <p class="mt-1 text-xs text-gray-400">

                            No RIS records match the selected filter or search.

                        </p>

                    </div>

                </td>

            </tr>

        @endforelse

    </tbody>

</table>


{{-- ===================================================== --}}
{{-- CUSTOM PAGINATION --}}
{{-- ===================================================== --}}

@if($risRecords->hasPages())

    <div class="flex items-center justify-end border-t border-gray-100 px-5 py-4">

        <div class="flex items-center gap-1">


            {{-- ================================================= --}}
            {{-- PREVIOUS PAGE --}}
            {{-- ================================================= --}}

            @if($risRecords->onFirstPage())

                <span
                    class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-300"
                    title="No previous page"
                >
                    <
                </span>

            @else

                <a
                    href="{{ $risRecords->previousPageUrl() }}"
                    data-page="{{ $risRecords->currentPage() - 1 }}"
                    title="Previous page"
                    class="ris-pagination-link flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                    <
                </a>

            @endif


            {{-- ================================================= --}}
            {{-- CURRENT PAGE --}}
            {{-- ================================================= --}}

            <span
                class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white"
                title="Current page {{ $risRecords->currentPage() }}"
            >

                {{ $risRecords->currentPage() }}

            </span>


            {{-- ================================================= --}}
            {{-- NEXT PAGE --}}
            {{-- ================================================= --}}

            @if($risRecords->hasMorePages())

                <a
                    href="{{ $risRecords->nextPageUrl() }}"
                    data-page="{{ $risRecords->currentPage() + 1 }}"
                    title="Next page"
                    class="ris-pagination-link flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                    >
                </a>

            @else

                <span
                    class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-300"
                    title="No next page"
                >
                    >
                </span>

            @endif

        </div>

    </div>

@endif
