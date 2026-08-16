{{-- ===================================================== --}}
{{-- SIGNATURE HISTORY TABLE PARTIAL --}}
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

        @forelse($signatureHistory as $history)


            {{-- ================================================= --}}
            {{-- HISTORY ROW --}}
            {{-- ================================================= --}}

            <tr
                class="
                    transition hover:bg-gray-50/70
                    {{ !is_null($history->ris_issued_by_date)
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
                        class="text-sm font-semibold {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-900' }}"
                        title="RIS Number"
                    >

                        {{ $history->ris_form_number ?? 'RIS-' . $history->ris_id }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- PURPOSE --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="max-w-[220px] truncate text-sm font-medium {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ $history->ris_purpose_description ?: ($history->ris_manual_description ?? 'N/A') }}"
                    >

                        {{ $history->ris_purpose_description ?: ($history->ris_manual_description ?? 'N/A') }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- EQUIPMENT --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="max-w-[220px] truncate text-sm {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="Items / Equipment included in this RIS"
                    >

                        {{ $history->ris_item_names
                            ?: ($history->ris_manual_title
                                ?: ($history->equipment_name
                                    ?? $history->report_unlisted_equipment_name
                                    ?? (($history->ris_request_type ?? null) === 'manual' ? 'Manual Procurement' : 'Unknown Equipment'))) }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- REQUESTED BY --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="text-sm font-medium {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="Person who sent this RIS request"
                    >

                        {{ $history->ris_requested_by_signature ?? 'Purchaser' }}

                    </div>


                    {{-- Submitted date --}}

                    <div
                        class="mt-1 text-xs text-gray-400"
                        title="Date the RIS was submitted"
                    >

                        {{ $history->ris_requested_by_date ?? 'N/A' }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- STATUS --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">
                    @include('admin.partials.ris-status-badge', ['ris' => $history])
                </td>


                {{-- ================================================= --}}
                {{-- AMOUNT (COMPUTED FROM RIS ITEMS) --}}
                {{-- ================================================= --}}

                <td
                    class="px-5 py-4 text-right text-sm font-semibold {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-900' }}"
                    title="Total computed amount of this RIS"
                >

                    ₱{{ number_format((float) ($history->ris_calculated_total ?? 0), 2) }}

                </td>


                {{-- ================================================= --}}
                {{-- ACTIONS --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div class="flex items-center justify-center gap-1.5">

                        <button
                            type="button"
                            onclick="window.openSignatureHistoryPreviewModal('{{ $history->ris_id }}')"
                            title="Preview this RIS form"
                            aria-label="Preview RIS"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>

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
                            No history records found
                        </h3>

                        <p class="mt-1 text-xs text-gray-400">

                            No RIS records match the current search.

                        </p>

                    </div>

                </td>

            </tr>

        @endforelse

    </tbody>

</table>

@include('layouts.partials.table-showing-pager', [
    'pager' => $signatureHistory,
    'linkClass' => 'signature-history-pagination-link',
    'noun' => 'records',
])

