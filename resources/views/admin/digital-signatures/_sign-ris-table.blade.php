{{-- ===================================================== --}}
{{-- SIGN RIS TABLE PARTIAL --}}
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

        @forelse($signableRisRecords as $ris)


            {{-- ================================================= --}}
            {{-- RIS ROW --}}
            {{-- ================================================= --}}

            <tr
                class="
                    transition hover:bg-gray-50/70
                    {{ !is_null($ris->ris_issued_by_date)
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
                        class="text-sm font-semibold {{ !is_null($ris->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-900' }}"
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
                        class="max-w-[220px] truncate text-sm font-medium {{ !is_null($ris->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ $ris->ris_purpose_description ?? 'N/A' }}"
                    >

                        {{ $ris->ris_purpose_description ?? 'N/A' }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- EQUIPMENT --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="max-w-[220px] truncate text-sm {{ !is_null($ris->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="Items / Equipment included in this RIS"
                    >

                        {{ $ris->ris_item_names ?? ($ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? 'Unknown Equipment') }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- REQUESTED BY --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="text-sm font-medium {{ !is_null($ris->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
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


                    {{-- FOR CO-SIGN (pending == ris_issued_by_date is null) --}}

                    @if(is_null($ris->ris_issued_by_date))

                        <span
                            class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            title="This RIS is awaiting your co-sign"
                        >
                            For Co-sign
                        </span>


                    {{-- CO-SIGNED (ris_issued_by_date is set) --}}

                    @else

                        <span
                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            title="This RIS has been co-signed"
                        >
                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Co-signed
                        </span>

                    @endif

                </td>


                {{-- ================================================= --}}
                {{-- AMOUNT (COMPUTED FROM RIS ITEMS) --}}
                {{-- ================================================= --}}

                <td
                    class="px-5 py-4 text-right text-sm font-semibold {{ !is_null($ris->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-900' }}"
                    title="Total computed amount of this RIS"
                >

                    ₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 2) }}

                </td>


                {{-- ================================================= --}}
                {{-- ACTIONS --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div class="flex flex-wrap items-center justify-center gap-2">


                        {{-- ================================================= --}}
                        {{-- VIEW / PREVIEW --}}
                        {{-- ================================================= --}}

                        <button
                            type="button"
                            onclick="openSignRisPreviewModal('{{ $ris->ris_id }}')"
                            title="Preview this RIS form"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                        >

                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                ></path>

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                ></path>

                            </svg>

                            View

                        </button>


                        {{-- ================================================= --}}
                        {{-- CO-SIGN BUTTON (only for records not yet co-signed) --}}
                        {{-- ================================================= --}}

                        @if(is_null($ris->ris_issued_by_date))

                            <button
                                type="button"
                                title="Co-sign this President-approved RIS"
                                onclick="openCoSignModal('{{ $ris->ris_id }}')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                            >

                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    ></path>

                                </svg>

                                Co-sign

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

                            No President-approved RIS records match the selected filter or search.

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

@if($signableRisRecords->hasPages())

    <div class="flex items-center justify-end border-t border-gray-100 px-5 py-4">

        <div class="flex items-center gap-1">


            {{-- ================================================= --}}
            {{-- PREVIOUS PAGE --}}
            {{-- ================================================= --}}

            @if($signableRisRecords->onFirstPage())

                <span
                    class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-300"
                    title="No previous page"
                >
                    <
                </span>

            @else

                <a
                    href="{{ $signableRisRecords->previousPageUrl() }}"
                    data-page="{{ $signableRisRecords->currentPage() - 1 }}"
                    title="Previous page"
                    class="sign-ris-pagination-link flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                    <
                </a>

            @endif


            {{-- ================================================= --}}
            {{-- CURRENT PAGE --}}
            {{-- ================================================= --}}

            <span
                class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white"
                title="Current page {{ $signableRisRecords->currentPage() }}"
            >

                {{ $signableRisRecords->currentPage() }}

            </span>


            {{-- ================================================= --}}
            {{-- NEXT PAGE --}}
            {{-- ================================================= --}}

            @if($signableRisRecords->hasMorePages())

                <a
                    href="{{ $signableRisRecords->nextPageUrl() }}"
                    data-page="{{ $signableRisRecords->currentPage() + 1 }}"
                    title="Next page"
                    class="sign-ris-pagination-link flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
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

