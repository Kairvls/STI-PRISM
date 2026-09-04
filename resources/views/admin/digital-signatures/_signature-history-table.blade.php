{{-- ===================================================== --}}
{{-- SIGNATURE HISTORY TABLE PARTIAL --}}
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

            <th class="w-[24%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Equipment
            </th>

            <th class="w-[15%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Requested By
            </th>

            <th class="w-[26%] px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Status
            </th>

            <th class="w-[12%] px-3 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                Amount
            </th>

            <th class="w-[10%] px-3 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">
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

                <td class="px-3 py-2.5">

                    <div
                        class="truncate text-sm font-semibold {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-900' }}"
                        title="{{ $history->ris_form_number ?? 'RIS-' . $history->ris_id }}"
                    >

                        {{ $history->ris_form_number ?? 'RIS-' . $history->ris_id }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- EQUIPMENT --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div
                        class="truncate text-sm {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ \App\Support\RisWorkflow::sourceLabel($history) }}"
                    >

                        {{ \App\Support\RisWorkflow::sourceLabel($history) }}
                        @if(!empty($history->ris_request_type))
                            <div class="mt-0.5 truncate text-[11px] text-gray-400">{{ \App\Support\RisWorkflow::requestTypeLabel($history) }}</div>
                        @endif
                    </div>
                    @include('admin.partials.ris-attachments', ['ris' => $history])

                </td>


                {{-- ================================================= --}}
                {{-- REQUESTED BY --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div
                        class="truncate text-sm font-medium {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-700' }}"
                        title="{{ $history->ris_requested_by_signature ?? 'Purchaser' }}"
                    >

                        {{ $history->ris_requested_by_signature ?? 'Purchaser' }}

                    </div>

                    <div
                        class="mt-0.5 truncate text-[11px] text-gray-400"
                        title="Date the RIS was submitted"
                    >

                        {{ $history->ris_requested_by_date ?? 'N/A' }}

                    </div>

                </td>


                {{-- ================================================= --}}
                {{-- STATUS --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">
                    <div class="min-w-0">
                        @include('admin.partials.ris-status-badge', ['ris' => $history])
                        @if (($history->ris_status ?? '') === 'Directly Approved')
                            @php
                                $directReason = trim((string) ($history->ris_direct_approval_reason ?? ''));
                                $directProofPath = trim((string) ($history->ris_direct_approval_proof_path ?? ''));
                                $directProofName = trim((string) ($history->ris_direct_approval_proof_name ?? ''));
                            @endphp
                            @if ($directReason !== '')
                                <p
                                    class="mt-1 truncate text-[11px] leading-snug text-slate-600"
                                    title="{{ $directReason }}"
                                >
                                    <span class="font-semibold text-slate-500">Reason:</span>
                                    {{ \Illuminate\Support\Str::limit($directReason, 48) }}
                                </p>
                            @endif
                            @if ($directProofPath !== '')
                                <a
                                    href="{{ route('admin.procurement-review.ris.direct-approval-proof', $history->ris_id) }}"
                                    class="mt-1 inline-flex max-w-full items-center gap-1 truncate text-[11px] font-medium text-sky-700 hover:underline"
                                    title="{{ $directProofName !== '' ? $directProofName : 'Download proof' }}"
                                >
                                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    <span class="truncate">{{ $directProofName !== '' ? \Illuminate\Support\Str::limit($directProofName, 22) : 'Proof file' }}</span>
                                </a>
                            @endif
                        @endif
                    </div>
                </td>


                {{-- ================================================= --}}
                {{-- AMOUNT (COMPUTED FROM RIS ITEMS) --}}
                {{-- ================================================= --}}

                <td
                    class="px-3 py-2.5 text-right text-sm font-semibold whitespace-nowrap {{ !is_null($history->ris_issued_by_date) ? 'text-gray-500' : 'text-gray-900' }}"
                    title="Total computed amount of this RIS"
                >

                    ₱{{ number_format((float) ($history->ris_calculated_total ?? 0), 2) }}

                </td>


                {{-- ================================================= --}}
                {{-- ACTIONS --}}
                {{-- ================================================= --}}

                <td class="px-3 py-2.5">

                    <div class="flex items-center justify-center gap-1">

                        <button
                            type="button"
                            onclick="window.openSignatureHistoryPreviewModal('{{ $history->ris_id }}')"
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
                            'risId' => $history->ris_id,
                            'btnClass' => 'inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900',
                        ])

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
