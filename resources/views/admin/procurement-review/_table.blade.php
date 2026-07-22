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
                RIS No.
            </th>

            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                Request
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
                    {{ $ris->ris_status !== 'Pending'
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
                        class="text-sm font-semibold {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-900' }}"
                        title="RIS Number"
                    >

                        {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- REQUEST --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="text-sm font-medium {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-700' }}"
                        title="Person who requested this RIS"
                    >

                        {{ $ris->ris_requested_by_signature ?? 'Purchaser' }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- EQUIPMENT --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="max-w-[220px] text-sm {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-700' }}"
                        title="Equipment included in this RIS"
                    >

                        {{
                            $ris->equipment_name
                            ?? $ris->report_unlisted_equipment_name
                            ?? 'Unknown Equipment'
                        }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- REQUESTED BY --}}
                {{-- ================================================= --}}

                <td class="px-5 py-4">

                    <div
                        class="text-sm font-medium {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-700' }}"
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


                    {{-- PENDING --}}

                    @if($ris->ris_status === 'Pending')

                        <span
                            class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                            title="This RIS is waiting for review"
                        >
                            Pending
                        </span>


                    {{-- APPROVED --}}

                    @elseif($ris->ris_status === 'Approved')

                        <span
                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                            title="This RIS has been approved"
                        >
                            Approved
                        </span>


                    {{-- AMEND --}}
                    {{-- Database value remains Rejected --}}

                    @elseif($ris->ris_status === 'Rejected')

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
                    class="px-5 py-4 text-right text-sm font-semibold {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-900' }}"
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
                            onclick="openRisPreviewModal('{{ $ris->ris_id }}')"
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
                        {{-- PENDING ACTIONS --}}
                        {{-- ================================================= --}}

                        @if($ris->ris_status === 'Pending')


                            {{-- ================================================= --}}
                            {{-- APPROVED FOR PRESIDENT --}}
                            {{-- ================================================= --}}

                            <form
                                method="POST"
                                action="{{ route('admin.procurement-review.ris.approve', $ris->ris_id) }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    title="Approve this RIS and forward it to the President for final approval"
                                    onclick="return confirm('Approve this RIS and forward it to the President for final approval?')"
                                    class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                >
                                    Approved for President
                                </button>

                            </form>


                            {{-- ================================================= --}}
                            {{-- DIRECT APPROVAL --}}
                            {{-- ================================================= --}}

                            <form
                                method="POST"
                                action="{{ route('admin.procurement-review.ris.direct-approve', $ris->ris_id) }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    title="Immediately approve this RIS and return it to the Purchaser"
                                    onclick="return confirm('Directly approve this RIS and return it to the Purchaser? This will bypass the normal signing stage.')"
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

                                    Direct Approval

                                </button>

                            </form>


                            {{-- ================================================= --}}
                            {{-- AMEND --}}
                            {{-- ================================================= --}}

                            <form
                                method="POST"
                                action="{{ route('admin.procurement-review.ris.reject', $ris->ris_id) }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    title="Return this RIS to the Purchaser for amendment"
                                    onclick="return confirm('Return this RIS to the Purchaser for amendment?')"
                                    class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                >
                                    Amend
                                </button>

                            </form>

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
                    title="Previous page"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
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
                    title="Next page"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
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

@endif
