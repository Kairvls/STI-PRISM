{{-- ===================================================== --}}
{{-- SIGN RIS TABLE PARTIAL --}}
{{-- ===================================================== --}}

<table class="w-full table-fixed">


    {{-- ================================================= --}}
    {{-- TABLE HEADER --}}
    {{-- ================================================= --}}

    <thead class="border-b border-gray-200 bg-gray-50">

        <tr>

            <th class="w-[13%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Reference No.
            </th>

            <th class="w-[26%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Equipment
            </th>

            <th class="w-[15%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Requested By
            </th>

            <th class="w-[18%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Status
            </th>

            <th class="w-[12%] px-3 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Amount
            </th>

            <th class="w-[16%] px-3 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">
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

            @php
                $awaitingSign = \App\Support\RisWorkflow::needsAdminIssuedBy($ris);
                $needsDecision = \App\Support\RisWorkflow::needsSignDecision($ris);
                $isPresidentRejected = \App\Support\RisWorkflow::canReturnForRevision($ris);
                $rowDimmed = !$needsDecision && !$awaitingSign && !$isPresidentRejected;
            @endphp

            <tr
                class="
                    transition hover:bg-gray-50/70
                    {{ $rowDimmed ? 'bg-gray-50 text-gray-500' : '' }}
                "
            >


                {{-- ================================================= --}}
                {{-- RIS NUMBER --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div
                        class="truncate text-sm font-semibold {{ $rowDimmed ? 'text-gray-500' : 'text-gray-900' }}"
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


                {{-- ================================================= --}}
                {{-- REQUESTED BY --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div
                        class="truncate text-sm font-medium {{ $rowDimmed ? 'text-gray-500' : 'text-gray-700' }}"
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
                    class="px-3 py-2.5 text-right text-sm font-semibold whitespace-nowrap {{ $rowDimmed ? 'text-gray-500' : 'text-gray-900' }}"
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
                            onclick="window.openSignRisPreviewModal('{{ $ris->ris_id }}')"
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

                        @if($needsDecision)
                            @include('admin.procurement-review._ris-action-menu', [
                                'risId' => $ris->ris_id,
                                'btnSizeClass' => 'h-8 w-8',
                            ])
                        @endif

                        @if($awaitingSign)

                            <button
                                type="button"
                                onclick="window.openCoSignModal('{{ $ris->ris_id }}')"
                                title="Sign Issued by on this President-approved RIS"
                                aria-label="Sign Issued by"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600 text-white transition hover:bg-sky-700"
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
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
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


            {{-- ================================================= --}}
            {{-- EMPTY TABLE --}}
            {{-- ================================================= --}}

            <tr>

                <td
                    colspan="6"
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
                            No RIS records found
                        </h3>

                        <p class="mt-1 text-xs text-gray-400">

                            No accepted or President-returned RIS records match the selected filter or search.

                        </p>

                    </div>

                </td>

            </tr>

        @endforelse

    </tbody>

</table>

@include('layouts.partials.table-showing-pager', [
    'pager' => $signableRisRecords,
    'linkClass' => 'sign-ris-pagination-link',
    'noun' => 'records',
])
