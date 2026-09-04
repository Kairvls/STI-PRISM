{{-- Admin Checked by / Issued by: Direct Approve, Forward to President, or Co-sign --}}
@php
    $adminName = Auth::user()->user_full_name ?? 'Admin';
    $todayDisplay = now()->format('d/m/Y');
    $items = $risItems ?? collect();
    $mode = in_array(($mode ?? 'direct'), ['direct', 'forward', 'cosign'], true) ? $mode : 'direct';
    $isForward = $mode === 'forward';
    $isCosign = $mode === 'cosign';
    $isDirect = $mode === 'direct';
    $formAction = $isCosign
        ? route('admin.digital-signatures.ris.decide')
        : ($isForward
            ? route('admin.procurement-review.ris.approve', $ris->ris_id)
            : route('admin.procurement-review.ris.direct-approve', $ris->ris_id));
    $approvedRaw = trim((string) ($ris->ris_approved_by_signature ?? ''));
    $approvedIsImage = $approvedRaw !== '' && str_starts_with($approvedRaw, 'data:image');
    $approvedPrintedName = \App\Support\RisWorkflow::approvedByPrintedName($ris);
    $secondColumnLabel = $isDirect ? 'Checked by:' : 'Approved by:';
@endphp

<style>
    .admin-da-ris-form {
        width: 100%;
        max-width: 1095px;
        min-height: 845px;
        border: 2px solid #1f2937;
        padding: 26px 24px 24px;
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        background: #fff;
    }
    .admin-da-ris-form .ris-document-header { position: relative; height: 120px; text-align: center; }
    .admin-da-ris-form .ris-school-name { font-size: 19px; line-height: 1.2; font-weight: 700; }
    .admin-da-ris-form .ris-document-title { margin-top: 9px; font-size: 15px; line-height: 1.2; font-weight: 700; }
    .admin-da-ris-form .ris-number-area { position: absolute; right: 0; bottom: 18px; display: flex; align-items: flex-end; gap: 10px; }
    .admin-da-ris-form .ris-number-label { font-size: 15px; font-weight: 600; }
    .admin-da-ris-form .ris-number-line {
        display: flex; align-items: flex-end; justify-content: center;
        width: 160px; min-height: 24px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px;
    }
    .admin-da-ris-form .ris-items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .admin-da-ris-form .ris-items-table th,
    .admin-da-ris-form .ris-items-table td { border: 1px solid #1f2937; }
    .admin-da-ris-form .ris-items-table th {
        padding: 9px 5px; vertical-align: middle; text-align: center;
        font-size: 12px; line-height: 1.2; font-weight: 700;
    }
    .admin-da-ris-form .ris-items-table tbody td {
        height: 45px; padding: 4px 6px; font-size: 12px; vertical-align: middle;
    }
    .admin-da-ris-form .ris-item-column { width: 20%; }
    .admin-da-ris-form .ris-brand-column { width: 10%; }
    .admin-da-ris-form .ris-unit-column { width: 7%; font-size: 11px !important; }
    .admin-da-ris-form .ris-supplier-column { width: 14%; font-size: 11px !important; }
    .admin-da-ris-form .ris-quantity-header { width: 20%; }
    .admin-da-ris-form .ris-requested-column { width: 10%; font-size: 11px !important; }
    .admin-da-ris-form .ris-issued-column { width: 10%; font-size: 11px !important; }
    .admin-da-ris-form .ris-unit-cost-column { width: 14%; }
    .admin-da-ris-form .ris-amount-column { width: 16%; }
    .admin-da-ris-form .ris-purpose-area { margin-top: 31px; }
    .admin-da-ris-form .ris-purpose-label { font-size: 13px; font-weight: 700; }
    .admin-da-ris-form .ris-purpose-line-row { display: flex; margin-top: 29px; }
    .admin-da-ris-form .ris-purpose-spacer { width: 80px; flex-shrink: 0; }
    .admin-da-ris-form .ris-purpose-line {
        flex: 1; min-height: 40px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; font-weight: 400;
    }
    .admin-da-ris-form .ris-signatures {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        column-gap: 32px; margin-top: 40px;
    }
    .admin-da-ris-form .ris-signature-column { min-width: 0; }
    .admin-da-ris-form .ris-signature-label { font-size: 12px; color: #374151; }
    .admin-da-ris-form .ris-signature-line {
        display: flex; align-items: flex-end; justify-content: center;
        position: relative;
        height: 49px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
        overflow: visible;
    }
    .admin-da-ris-form .ris-signature-line .signature-image {
        position: absolute;
        left: 50%;
        bottom: 6px;
        z-index: 2;
        max-height: 42px;
        max-width: 92%;
        width: auto;
        transform: translateX(-50%);
        pointer-events: none;
    }
    .admin-da-ris-form .ris-signature-line .signature-name {
        font-size: 11px;
        letter-spacing: 0;
        text-transform: none;
    }
    .admin-da-ris-form .ris-signature-input-wrap {
        position: relative;
        overflow: visible;
    }
    .admin-da-ris-form .ris-signature-input-wrap .signature-image {
        position: absolute;
        left: 50%;
        bottom: 6px;
        z-index: 10;
        max-height: 42px;
        max-width: 92%;
        width: auto;
        height: auto;
        transform: translateX(-50%);
        pointer-events: none;
        object-fit: contain;
    }
    .admin-da-ris-form .ris-signature-input {
        position: relative;
        z-index: 1;
    }
    .admin-da-ris-form .ris-date-label { margin-top: 16px; font-size: 12px; color: #374151; }
    .admin-da-ris-form .ris-date-line {
        display: flex; align-items: flex-end; justify-content: center;
        height: 31px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .admin-da-ris-form .ris-date-placeholder {
        color: #9ca3af;
    }
    .admin-da-ris-form .ris-signature-line--input,
    .admin-da-ris-form .ris-date-line--input {
        padding: 0 6px 4px;
    }
    .admin-da-ris-form .ris-signature-input,
    .admin-da-ris-form .ris-date-input {
        width: 100%;
        height: auto;
        margin: 0;
        padding: 0;
        border: 0 !important;
        border-radius: 0;
        background: transparent !important;
        background-color: transparent !important;
        outline: none !important;
        box-shadow: none !important;
        text-align: center;
        font-size: 12px;
        line-height: 1.2;
        color: #000;
        -webkit-appearance: none;
        appearance: none;
    }
    .admin-da-ris-form .ris-signature-input:focus,
    .admin-da-ris-form .ris-date-input:focus,
    .admin-da-ris-form .ris-signature-input:-webkit-autofill,
    .admin-da-ris-form .ris-signature-input:-webkit-autofill:hover,
    .admin-da-ris-form .ris-signature-input:-webkit-autofill:focus,
    .admin-da-ris-form .ris-date-input:-webkit-autofill,
    .admin-da-ris-form .ris-date-input:-webkit-autofill:hover,
    .admin-da-ris-form .ris-date-input:-webkit-autofill:focus {
        border: 0 !important;
        outline: none !important;
        box-shadow: 0 0 0 1000px #fff inset !important;
        -webkit-text-fill-color: #000;
        transition: background-color 99999s ease-out;
    }
    .admin-da-locked {
        pointer-events: none;
        user-select: none;
    }
</style>
@include('partials.ris-signature-overlay-styles')

<form
    id="directApproveForm"
    method="POST"
    action="{{ $formAction }}"
    enctype="multipart/form-data"
    class="flex min-h-0 flex-1 flex-col"
    data-mode="{{ $mode }}"
>
    @csrf
    @if ($isCosign)
        <input type="hidden" name="target_id" value="{{ $ris->ris_id }}">
        <input type="hidden" name="decision" value="Approved">
    @endif
    <input type="hidden" name="ris_issued_by_signature_image" id="ris_issued_by_signature_image" value="">
    @if ($isDirect)
        <input type="hidden" name="ris_checked_by_signature_image" id="ris_checked_by_signature_image" value="">
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="space-y-4 p-4 md:px-5 md:pt-3 md:pb-4">
            <div class="flex gap-3 rounded-xl border px-4 py-3 {{ $isForward ? 'border-sky-100 bg-sky-50/90' : ($isCosign ? 'border-slate-200 bg-slate-50' : 'border-emerald-100 bg-emerald-50/80') }}">
                <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $isForward ? 'text-sky-600' : ($isCosign ? 'text-slate-500' : 'text-emerald-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"></path>
                </svg>
                <p class="text-xs leading-relaxed {{ $isForward ? 'text-sky-950/80' : ($isCosign ? 'text-slate-700' : 'text-emerald-950/80') }}">
                    @if ($isForward)
                        All RIS details are locked. You may add optional <strong>supporting details</strong> and an attachment for the President. Confirming forwards this RIS without an Issued by signature. After the President approves, sign Issued by here on Sign RIS.
                    @elseif ($isCosign)
                        The President has already signed <strong>Approved by</strong>. You can only fill <strong>Issued by</strong> and its <strong>Date</strong>. Confirming returns this RIS to the Purchaser.
                    @else
                        Direct approval bypasses presidential signing. Fill <strong>Checked by</strong> and <strong>Issued by</strong>, provide a <strong>reason</strong> (and optional proof), then confirm. This returns the RIS to the Purchaser, keeps a copy in <strong>Signature History</strong>, and records it for the President.
                    @endif
                </p>
            </div>

            @if ($isDirect)
            <div class="rounded-xl border border-amber-100 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            Reason for direct approval
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Required for the President’s record. Explain why this RIS was approved without presidential signing.
                        </p>
                    </div>
                </div>

                <label for="direct_approval_reason" class="mt-3 block text-xs font-medium text-slate-700">
                    Reason <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="direct_approval_reason"
                    name="direct_approval_reason"
                    rows="3"
                    required
                    maxlength="2000"
                    placeholder="e.g. Urgent classroom equipment replacement; amount within admin authority; emergency purchase needed before class resumes."
                    class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                >{{ old('direct_approval_reason') }}</textarea>

                <label class="relative mt-4 flex cursor-pointer items-center gap-3 overflow-hidden rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 transition hover:border-slate-300 hover:bg-slate-50">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-700">Proof attachment</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Optional · Image, PDF, or Word · max 10 MB</span>
                        <span id="direct_approval_proofName" class="mt-1 hidden block truncate text-[11px] font-medium text-slate-600"></span>
                    </span>
                    <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
                        Choose file
                    </span>
                    <input
                        type="file"
                        id="direct_approval_proof"
                        name="direct_approval_proof"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,image/*"
                        class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                    >
                </label>
            </div>
            @endif

            @if ($isForward)
            <div class="rounded-xl border border-sky-100 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            Admin supporting details
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Optional note and attachment for the President explaining why this RIS should be approved. Separate from Purchaser supporting documents below.
                        </p>
                    </div>
                </div>

                <label for="forward_details" class="mt-3 block text-xs font-medium text-slate-700">
                    Supporting details
                </label>
                <textarea
                    id="forward_details"
                    name="forward_details"
                    rows="3"
                    maxlength="2000"
                    placeholder="e.g. Priority replacement for Room 204; budget already verified; recommended for presidential approval."
                    class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-300 focus:ring-2 focus:ring-sky-100"
                >{{ old('forward_details') }}</textarea>

                <label for="forward_attachment" class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-sky-200 bg-sky-50/50 px-3.5 py-3 transition hover:border-sky-300 hover:bg-sky-50">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-sky-500 ring-1 ring-sky-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-700">Supporting attachment</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Optional · Image, PDF, or Word · max 10 MB</span>
                        <span id="forward_attachmentName" class="mt-1 hidden block truncate text-[11px] font-medium text-sky-700"></span>
                    </span>
                    <span class="rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-sky-700">
                        Choose file
                    </span>
                    <input
                        type="file"
                        id="forward_attachment"
                        name="forward_attachment"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,image/*"
                        class="sr-only"
                    >
                </label>
            </div>
            <script>
            (function () {
                var input = document.getElementById('forward_attachment');
                var nameOut = document.getElementById('forward_attachmentName');
                if (!input) return;
                input.addEventListener('change', function () {
                    var file = input.files && input.files[0];
                    if (!nameOut) return;
                    if (file) {
                        nameOut.textContent = file.name;
                        nameOut.classList.remove('hidden');
                    } else {
                        nameOut.textContent = '';
                        nameOut.classList.add('hidden');
                    }
                });
            })();
            </script>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100/80 shadow-inner">
                <div class="overflow-x-auto p-3 md:p-5">
                    <div class="admin-da-ris-form mx-auto">
                <div class="ris-document-header admin-da-locked">
                    <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                    <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
                    <div class="ris-number-area">
                        <span class="ris-number-label">No.</span>
                        <div class="ris-number-line">{{ $ris->ris_form_number ?: '—' }}</div>
                    </div>
                </div>

                <table class="ris-items-table admin-da-locked">
                    <thead>
                        <tr>
                            <th rowspan="2" class="ris-item-column">ITEM</th>
                            <th rowspan="2" class="ris-brand-column">BRAND</th>
                            <th rowspan="2" class="ris-unit-column">UNIT</th>
                            <th rowspan="2" class="ris-supplier-column">SUPPLIER</th>
                            <th colspan="2" class="ris-quantity-header">QUANTITY</th>
                            <th rowspan="2" class="ris-unit-cost-column">UNIT COST</th>
                            <th rowspan="2" class="ris-amount-column">AMOUNT</th>
                        </tr>
                        <tr>
                            <th class="ris-requested-column">REQUESTED</th>
                            <th class="ris-issued-column">ISSUED</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowCount = max(10, $items->count()); @endphp
                        @for($i = 0; $i < $rowCount; $i++)
                            @php $item = $items->get($i); @endphp
                            <tr>
                                <td>{{ $item->ris_item_name_description ?? '' }}</td>
                                <td class="text-center">{{ $item?->brand_name ?? '' }}</td>
                                <td class="text-center">{{ $item?->uom_name ?? '' }}</td>
                                <td>{{ $item->supplier_display_name ?? '' }}</td>
                                <td class="text-center">{{ $item->ris_quantity_requested ?? '' }}</td>
                                <td class="text-center">{{ $item->ris_quantity_issued ?? '' }}</td>
                                <td class="text-right">{{ isset($item->ris_unit_cost) ? number_format((float) $item->ris_unit_cost, 2) : '' }}</td>
                                <td class="text-right">{{ isset($item->ris_total_amount) ? number_format((float) $item->ris_total_amount, 2) : (isset($item) ? number_format(((float)($item->ris_quantity_issued ?? 0)) * ((float)($item->ris_unit_cost ?? 0)), 2) : '') }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                <div class="ris-purpose-area admin-da-locked">
                    <div class="ris-purpose-label">PURPOSE</div>
                    <div class="ris-purpose-line-row">
                        <div class="ris-purpose-spacer"></div>
                        <div class="ris-purpose-line">{{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? '') }}</div>
                    </div>
                </div>

                <div class="ris-signatures">
                    <div class="ris-signature-column admin-da-locked">
                        <div class="ris-signature-label">Requested by:</div>
                        @include('partials.ris-requested-by-signatory', [
                            'ris' => $ris,
                            'lineClass' => 'ris-signature-line',
                        ])
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>

                    <div class="ris-signature-column {{ $isDirect ? '' : 'admin-da-locked' }}">
                        <div class="ris-signature-label">{{ $secondColumnLabel }}</div>
                        @if ($isDirect)
                            <div class="ris-signature-line ris-signature-line--input ris-signature-input-wrap">
                                <img
                                    id="daCheckedSigOverlay"
                                    class="signature-image"
                                    alt="Checked by signature"
                                    style="display:none;"
                                >
                                <input
                                    type="text"
                                    name="ris_checked_by"
                                    id="da_checked_by"
                                    value="{{ old('ris_checked_by', $adminName) }}"
                                    required
                                    maxlength="255"
                                    autocomplete="off"
                                    class="ris-signature-input"
                                    title="Admin name for Checked by"
                                >
                            </div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line ris-date-line--input">
                                <input
                                    type="text"
                                    name="ris_checked_by_date"
                                    id="da_checked_by_date"
                                    value="{{ old('ris_checked_by_date', $todayDisplay) }}"
                                    required
                                    placeholder="dd/mm/yyyy"
                                    inputmode="numeric"
                                    maxlength="10"
                                    autocomplete="off"
                                    class="ris-date-input"
                                    title="Checked by date (dd/mm/yyyy)"
                                >
                            </div>
                        @else
                            <div class="ris-signature-line" id="sigLineApproved">
                                @if ($isCosign && $approvedIsImage)
                                    <img src="{{ $approvedRaw }}" alt="Approved by signature" class="signature-image">
                                    @if ($approvedPrintedName !== '')
                                        <span class="signature-name">{{ $approvedPrintedName }}</span>
                                    @endif
                                @elseif ($isCosign && $approvedRaw !== '')
                                    <span class="signature-name">{{ $approvedRaw }}</span>
                                @else
                                    {{ ' ' }}
                                @endif
                            </div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line{{ ($isCosign && !empty($ris->ris_approved_by_date)) ? '' : ' ris-date-placeholder' }}">
                                @if ($isCosign && !empty($ris->ris_approved_by_date))
                                    {{ \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('d/m/Y') }}
                                @else
                                    dd/mm/yyyy
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="ris-signature-column {{ $isForward ? 'admin-da-locked' : '' }}">
                        <div class="ris-signature-label">Issued by:</div>
                        @if ($isForward)
                            <div class="ris-signature-line"> </div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line ris-date-placeholder">dd/mm/yyyy</div>
                        @else
                        <div class="ris-signature-line ris-signature-line--input ris-signature-input-wrap">
                            <img
                                id="daIssuedSigOverlay"
                                class="signature-image"
                                alt="Issued by signature"
                                style="display:none;"
                            >
                            <input
                                type="text"
                                name="ris_issued_by"
                                id="da_issued_by"
                                value="{{ old('ris_issued_by', $adminName) }}"
                                required
                                maxlength="255"
                                autocomplete="off"
                                class="ris-signature-input"
                                title="Admin name for Issued by"
                            >
                        </div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line ris-date-line--input">
                            <input
                                type="text"
                                name="ris_issued_by_date"
                                id="da_issued_by_date"
                                value="{{ old('ris_issued_by_date', $todayDisplay) }}"
                                required
                                placeholder="dd/mm/yyyy"
                                inputmode="numeric"
                                maxlength="10"
                                autocomplete="off"
                                class="ris-date-input"
                                title="Issued by date (dd/mm/yyyy)"
                            >
                        </div>
                        @endif
                    </div>

                    <div class="ris-signature-column admin-da-locked">
                        <div class="ris-signature-label">Received by:</div>
                        <div class="ris-signature-line">{{ $ris->ris_received_by_signature ?: ' ' }}</div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line{{ empty($ris->ris_received_by_date) ? ' ris-date-placeholder' : '' }}">
                            {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>

            {{-- Supporting documents already attached to this RIS --}}
            @php $risSupportingDocs = $supportingDocuments ?? collect(); @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            Supporting documents
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            Files the Purchaser attached to this RIS. Open any file to review before you confirm.
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                        {{ $risSupportingDocs->count() }} file{{ $risSupportingDocs->count() === 1 ? '' : 's' }}
                    </span>
                </div>

                @if ($risSupportingDocs->isNotEmpty())
                    <ul class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                        @foreach ($risSupportingDocs as $file)
                            <li>
                                <a
                                    href="{{ route('admin.ris.attachments.download', $file->ris_attachment_id) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center gap-3 px-3.5 py-2.5 transition hover:bg-slate-50"
                                    title="{{ $file->ris_attachment_original_name }}"
                                >
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-medium text-slate-800">
                                            {{ $file->ris_attachment_original_name }}
                                        </span>
                                        @if (!empty($file->ris_attachment_size))
                                            <span class="mt-0.5 block text-[11px] text-slate-400">
                                                {{ number_format(((int) $file->ris_attachment_size) / 1024, 1) }} KB
                                            </span>
                                        @endif
                                    </span>
                                    <span class="text-[11px] font-medium text-slate-500">Open</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 text-xs text-slate-500">
                        No supporting documents were attached to this RIS.
                    </div>
                @endif
            </div>

            @if (!$isForward)
            @php $savedSignatures = $savedSignatures ?? collect(); @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">
                            {{ $isDirect ? 'Admin signature' : 'Issued by signature' }}
                        </h4>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                            @if ($isDirect)
                                Pick a saved signature, draw one, or upload. It overlays <strong>Checked by</strong> and <strong>Issued by</strong> on the form above.
                            @else
                                Pick a saved signature, draw one, or upload. It overlays <strong>Issued by</strong> on the form above.
                            @endif
                        </p>
                    </div>
                    <div id="adminDaSignBadge" class="hidden inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                        Signature added
                    </div>
                </div>

                <div class="mt-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-medium text-slate-700">My saved signatures</p>
                        <span id="adminDaSavedCount" class="text-[11px] text-slate-400">{{ $savedSignatures->count() }} / 4 saved</span>
                    </div>
                    <div id="adminDaSavedList" class="mt-2 grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4">
                        @forelse ($savedSignatures as $saved)
                            <div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/90 p-2.5 shadow-sm shadow-slate-900/[0.03] transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md hover:shadow-slate-900/5" data-saved-sig-id="{{ $saved->user_signature_id }}">
                                <button
                                    type="button"
                                    class="admin-da-use-saved flex w-full flex-col items-center gap-2 rounded-xl px-1 py-1 text-left"
                                    title="Use this signature"
                                >
                                    <span class="flex h-14 w-full items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">
                                        <img src="{{ $saved->preview_url }}" alt="" class="admin-da-saved-preview max-h-10 w-auto max-w-[90%] object-contain">
                                    </span>
                                    <span class="w-full truncate text-center text-[11px] font-medium text-slate-600">{{ $saved->user_signature_label ?: 'Signature' }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="admin-da-delete-saved absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/95 text-slate-400 opacity-0 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100"
                                    data-id="{{ $saved->user_signature_id }}"
                                    data-label="{{ $saved->user_signature_label ?: 'Signature' }}"
                                    title="Remove from list"
                                    aria-label="Remove saved signature"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <div id="adminDaSavedEmpty" class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-4 text-center text-xs text-slate-500">
                                No saved signatures yet. Upload or draw one below, then save it for next time.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        id="adminDaOpenSignPad"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#0025cc] px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4 16.464V20z"></path>
                        </svg>
                        <span id="adminDaOpenSignPadLabel">Draw signature</span>
                    </button>
                    <button
                        type="button"
                        id="adminDaClearSign"
                        class="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Clear signature
                    </button>
                    <button
                        type="button"
                        id="adminDaSaveCurrentSign"
                        class="hidden rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                        @if ($savedSignatures->count() >= 4) disabled title="Maximum of 4 saved signatures reached" @endif
                    >
                        Save to my list
                    </button>
                </div>

                <label class="relative mt-3 flex cursor-pointer items-center gap-3 overflow-hidden rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-3 transition hover:border-slate-300 hover:bg-slate-50">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-slate-700">Or upload a signature image</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Optional · PNG, JPG, or similar · can be saved to your list</span>
                        <span id="adminDaSigUploadName" class="mt-1 hidden block truncate text-[11px] font-medium text-slate-600"></span>
                    </span>
                    <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
                        Choose file
                    </span>
                    <input
                        type="file"
                        id="adminDaSigUpload"
                        accept="image/*"
                        class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                    >
                </label>

                <label class="mt-3 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
                    <input
                        type="checkbox"
                        id="adminDaSaveOnUpload"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                        @checked($savedSignatures->count() < 4)
                        @disabled($savedSignatures->count() >= 4)
                    >
                    <span class="min-w-0">
                        <span class="block text-xs font-medium text-slate-700">Save uploaded signature to my list</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400">Keeps up to 4 signatures for the next Approve Directly.</span>
                    </span>
                </label>
            </div>

            <div id="adminDaSignPadModal" class="fixed inset-0 z-[12100] hidden">
                <div class="absolute inset-0 flex items-center justify-center bg-slate-900/45 p-4" data-admin-da-pad-dismiss>
                    <div class="relative w-full max-w-[560px] rounded-2xl bg-white p-5 shadow-[0_20px_60px_rgba(15,23,42,0.2)]" onclick="event.stopPropagation()">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Sign the RIS</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-500">
                                    @if ($isDirect)
                                        Sign over your printed name. This overlays <strong>Checked by</strong> and <strong>Issued by</strong>.
                                    @else
                                        Sign over your printed name. This overlays <strong>Issued by</strong>.
                                    @endif
                                </p>
                            </div>
                            <button type="button" id="adminDaCloseSignPad" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-4">
                            @include('partials.signature-pad', [
                                'canvasId' => 'adminDaSignatureCanvas',
                                'hiddenName' => 'admin_da_pad_scratch',
                                'hiddenId' => 'adminDaPadScratch',
                                'label' => 'Digital signature',
                                'hint' => $isDirect
                                    ? 'Sign to overlay Checked by and Issued by.'
                                    : 'Sign to overlay Issued by.',
                                'requiredMessage' => 'Please sign before applying.',
                            ])
                        </div>
                        <label class="mt-4 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
                            <input
                                type="checkbox"
                                id="adminDaSaveOnDraw"
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                                @checked($savedSignatures->count() < 4)
                                @disabled($savedSignatures->count() >= 4)
                            >
                            <span class="min-w-0">
                                <span class="block text-xs font-medium text-slate-700">Also save this drawing to my list</span>
                                <span class="mt-0.5 block text-[11px] text-slate-400">Maximum 4 saved signatures.</span>
                            </span>
                        </label>
                        <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
                            <button type="button" id="adminDaCancelSignPad" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-950">Cancel</button>
                            <button type="button" id="adminDaApplySignPad" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                                Apply signature
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modern name / delete dialogs (replace browser prompt & confirm) --}}
            <div id="adminDaNameSigModal" class="fixed inset-0 z-[12200] hidden">
                <div class="absolute inset-0 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]" data-admin-da-name-dismiss>
                    <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_24px_60px_rgba(15,23,42,0.22)] ring-1 ring-slate-200/80" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="adminDaNameSigTitle">
                        <div class="border-b border-slate-100 px-5 pb-3 pt-4">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 id="adminDaNameSigTitle" class="text-base font-semibold tracking-tight text-slate-900">Save to my list</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-500">Give this signature a short name so it’s easy to reuse later.</p>
                                </div>
                                <button type="button" id="adminDaNameSigClose" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-3 px-5 py-4">
                            <div class="flex items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5">
                                <img id="adminDaNameSigPreview" src="" alt="Signature preview" class="max-h-14 w-auto max-w-full object-contain">
                            </div>
                            <div>
                                <label for="adminDaNameSigInput" class="block text-xs font-medium text-slate-700">Signature name <span class="font-normal text-slate-400">(optional)</span></label>
                                <input
                                    type="text"
                                    id="adminDaNameSigInput"
                                    maxlength="120"
                                    placeholder="e.g. My official signature"
                                    class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                                >
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                            <button type="button" id="adminDaNameSigCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                            <button type="button" id="adminDaNameSigConfirm" class="rounded-xl bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Save signature</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="adminDaDeleteSigModal" class="fixed inset-0 z-[12200] hidden">
                <div class="absolute inset-0 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]" data-admin-da-delete-dismiss>
                    <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_24px_60px_rgba(15,23,42,0.22)] ring-1 ring-slate-200/80" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="adminDaDeleteSigTitle">
                        <div class="px-5 pb-2 pt-5">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m5 0H4"></path>
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 id="adminDaDeleteSigTitle" class="text-base font-semibold tracking-tight text-slate-900">Remove signature?</h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                                        <span id="adminDaDeleteSigLabel" class="font-medium text-slate-700">This signature</span> will be removed from your saved list. You can always upload or draw it again later.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                            <button type="button" id="adminDaDeleteSigCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Keep it</button>
                            <button type="button" id="adminDaDeleteSigConfirm" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="adminDaNotice" class="pointer-events-none fixed inset-x-0 bottom-6 z-[12300] flex justify-center px-4 opacity-0 transition duration-200">
                <div class="pointer-events-auto max-w-sm rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-lg shadow-slate-900/10 ring-1 ring-slate-100">
                    <span id="adminDaNoticeText"></span>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-5 py-4 md:px-6">
        <button
            type="button"
            onclick="if (!window._adminDaFileDialogOpen) closeDirectApproveModal()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            Cancel
        </button>
        <button
            type="submit"
            class="rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-sm transition {{ $isForward ? 'bg-[#0025cc] hover:bg-blue-800' : 'bg-[#0025cc] hover:bg-blue-800' }}"
            title="{{ $isForward ? 'Forward this RIS to the President' : ($isCosign ? 'Sign Issued by and return to Purchaser' : 'Confirm direct approval, notify President for record, and return to Purchaser') }}"
        >
            {{ $isForward ? 'Forward to President' : ($isCosign ? 'Confirm Issued by' : 'Confirm Admin Approval') }}
        </button>
    </div>
</form>

@if (!$isForward)
<script>
(function () {
    var form = document.getElementById('directApproveForm');
    var issuedHidden = document.getElementById('ris_issued_by_signature_image');
    var checkedHidden = document.getElementById('ris_checked_by_signature_image');
    var issuedOverlay = document.getElementById('daIssuedSigOverlay');
    var checkedOverlay = document.getElementById('daCheckedSigOverlay');
    var proofInput = document.getElementById('direct_approval_proof');
    var proofNameOut = document.getElementById('direct_approval_proofName');
    var padModal = document.getElementById('adminDaSignPadModal');
    var openPadBtn = document.getElementById('adminDaOpenSignPad');
    var openPadLabel = document.getElementById('adminDaOpenSignPadLabel');
    var clearSignBtn = document.getElementById('adminDaClearSign');
    var applyPadBtn = document.getElementById('adminDaApplySignPad');
    var closePadBtn = document.getElementById('adminDaCloseSignPad');
    var cancelPadBtn = document.getElementById('adminDaCancelSignPad');
    var signBadge = document.getElementById('adminDaSignBadge');
    var uploadInput = document.getElementById('adminDaSigUpload');
    var uploadNameOut = document.getElementById('adminDaSigUploadName');
    var saveCurrentBtn = document.getElementById('adminDaSaveCurrentSign');
    var saveOnUpload = document.getElementById('adminDaSaveOnUpload');
    var saveOnDraw = document.getElementById('adminDaSaveOnDraw');
    var savedList = document.getElementById('adminDaSavedList');
    var savedCount = document.getElementById('adminDaSavedCount');
    var nameModal = document.getElementById('adminDaNameSigModal');
    var nameInput = document.getElementById('adminDaNameSigInput');
    var namePreview = document.getElementById('adminDaNameSigPreview');
    var nameConfirmBtn = document.getElementById('adminDaNameSigConfirm');
    var nameCancelBtn = document.getElementById('adminDaNameSigCancel');
    var nameCloseBtn = document.getElementById('adminDaNameSigClose');
    var deleteModal = document.getElementById('adminDaDeleteSigModal');
    var deleteLabel = document.getElementById('adminDaDeleteSigLabel');
    var deleteConfirmBtn = document.getElementById('adminDaDeleteSigConfirm');
    var deleteCancelBtn = document.getElementById('adminDaDeleteSigCancel');
    var noticeEl = document.getElementById('adminDaNotice');
    var noticeText = document.getElementById('adminDaNoticeText');
    var pendingSaveDataUrl = '';
    var pendingDeleteId = '';
    var noticeTimer = null;
    var maxSavedSignatures = 4;
    var dateInputs = [
        document.getElementById('da_issued_by_date'),
        document.getElementById('da_checked_by_date'),
    ];

    if (proofInput) {
        proofInput.addEventListener('click', function () {
            if (window.markAdminDaFileDialog) window.markAdminDaFileDialog(true);
        });
        proofInput.addEventListener('change', function () {
            if (window.markAdminDaFileDialog) window.markAdminDaFileDialog(false);
            var file = proofInput.files && proofInput.files[0];
            if (!proofNameOut) return;
            if (file) {
                proofNameOut.textContent = file.name;
                proofNameOut.classList.remove('hidden');
            } else {
                proofNameOut.textContent = '';
                proofNameOut.classList.add('hidden');
            }
        });
    }

    function canvasHasDrawing(canvas) {
        if (!canvas) return false;
        var pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
        for (var i = 3; i < pixels.length; i += 4) {
            if (pixels[i] > 0) return true;
        }
        return false;
    }

    function setOverlay(img, dataUrl) {
        if (!img) return;
        if (dataUrl && String(dataUrl).indexOf('data:image/') === 0) {
            img.src = dataUrl;
            img.style.display = '';
        } else {
            img.removeAttribute('src');
            img.style.display = 'none';
        }
    }

    function applySignature(dataUrl) {
        var url = (dataUrl && String(dataUrl).indexOf('data:image/') === 0) ? dataUrl : '';
        if (issuedHidden) issuedHidden.value = url;
        if (checkedHidden) checkedHidden.value = url;
        setOverlay(issuedOverlay, url);
        setOverlay(checkedOverlay, url);
        var hasSig = url !== '';
        if (signBadge) signBadge.classList.toggle('hidden', !hasSig);
        if (clearSignBtn) clearSignBtn.classList.toggle('hidden', !hasSig);
        if (saveCurrentBtn) {
            saveCurrentBtn.classList.toggle('hidden', !hasSig);
            var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
            updateSaveAvailability(currentCount);
        }
        if (openPadLabel) openPadLabel.textContent = hasSig ? 'Redraw signature' : 'Draw signature';
    }

    function clearSignature() {
        var canvas = document.getElementById('adminDaSignatureCanvas');
        if (window.clearSignaturePad) {
            window.clearSignaturePad('adminDaSignatureCanvas', 'adminDaPadScratch');
        } else if (canvas) {
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        }
        if (uploadInput) uploadInput.value = '';
        if (uploadNameOut) {
            uploadNameOut.textContent = '';
            uploadNameOut.classList.add('hidden');
        }
        applySignature('');
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        var input = form && form.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function updateSaveAvailability(count) {
        var full = count >= maxSavedSignatures;
        if (savedCount) {
            savedCount.textContent = count + ' / ' + maxSavedSignatures + ' saved';
            savedCount.classList.toggle('text-amber-600', full);
            savedCount.classList.toggle('font-semibold', full);
            savedCount.classList.toggle('text-slate-400', !full);
        }
        if (saveCurrentBtn && !saveCurrentBtn.classList.contains('hidden')) {
            saveCurrentBtn.disabled = full;
            saveCurrentBtn.title = full ? 'Maximum of 4 saved signatures reached' : '';
        }
        if (saveOnUpload) {
            saveOnUpload.disabled = full;
            if (full) saveOnUpload.checked = false;
        }
        if (saveOnDraw) {
            saveOnDraw.disabled = full;
            if (full) saveOnDraw.checked = false;
        }
    }

    function renderSavedList(items) {
        if (!savedList) return;
        var list = Array.isArray(items) ? items : [];
        updateSaveAvailability(list.length);
        if (!list.length) {
            savedList.innerHTML = '<div id="adminDaSavedEmpty" class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-4 text-center text-xs text-slate-500">No saved signatures yet. Upload or draw one below, then save it for next time.</div>';
            return;
        }
        savedList.innerHTML = list.map(function (item) {
            var id = item.id;
            var label = escapeHtml(item.label || 'Signature');
            var preview = String(item.preview_url || '').replace(/"/g, '&quot;');
            return ''
                + '<div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/90 p-2.5 shadow-sm shadow-slate-900/[0.03] transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md hover:shadow-slate-900/5" data-saved-sig-id="' + id + '">'
                +   '<button type="button" class="admin-da-use-saved flex w-full flex-col items-center gap-2 rounded-xl px-1 py-1 text-left" title="Use this signature">'
                +     '<span class="flex h-14 w-full items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">'
                +       '<img src="' + preview + '" alt="" class="admin-da-saved-preview max-h-10 w-auto max-w-[90%] object-contain">'
                +     '</span>'
                +     '<span class="w-full truncate text-center text-[11px] font-medium text-slate-600">' + label + '</span>'
                +   '</button>'
                +   '<button type="button" class="admin-da-delete-saved absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/95 text-slate-400 opacity-0 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100" data-id="' + id + '" data-label="' + label + '" title="Remove from list" aria-label="Remove saved signature">'
                +     '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'
                +   '</button>'
                + '</div>';
        }).join('');
    }

    function saveSignatureToLibrary(options) {
        options = options || {};
        var dataUrl = options.dataUrl || (issuedHidden ? String(issuedHidden.value || '') : '');
        var file = options.file || null;
        var label = options.label || '';
        var body;
        var headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken()
        };

        if (file) {
            body = new FormData();
            body.append('signature_file', file);
            if (label) body.append('signature_label', label);
            body.append('_token', csrfToken());
        } else {
            if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
                return Promise.reject(new Error('No signature to save.'));
            }
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify({
                signature_image: dataUrl,
                signature_label: label || null,
                _token: csrfToken()
            });
        }

        return fetch(@json(route('admin.digital-signatures.saved-signatures.store')), {
            method: 'POST',
            headers: headers,
            body: body,
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.ok) {
                    throw new Error((payload && payload.message) || 'Could not save signature.');
                }
                return payload;
            });
        }).then(function (payload) {
            renderSavedList(payload.signatures || []);
            return payload;
        });
    }

    function deleteSavedSignature(id) {
        return fetch(@json(url('/admin/digital-signatures/saved-signatures')) + '/' + id, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ _token: csrfToken() }),
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.ok) {
                    throw new Error((payload && payload.message) || 'Could not delete signature.');
                }
                return payload;
            });
        }).then(function (payload) {
            renderSavedList(payload.signatures || []);
            return payload;
        });
    }

    function mountOverlay(el) {
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    }

    function showNotice(message) {
        if (!noticeEl || !noticeText) {
            window.alert(message);
            return;
        }
        mountOverlay(noticeEl);
        noticeText.textContent = message;
        noticeEl.classList.remove('opacity-0');
        noticeEl.classList.add('opacity-100');
        if (noticeTimer) window.clearTimeout(noticeTimer);
        noticeTimer = window.setTimeout(function () {
            noticeEl.classList.remove('opacity-100');
            noticeEl.classList.add('opacity-0');
        }, 2800);
    }

    function openNameModal(dataUrl) {
        pendingSaveDataUrl = dataUrl || '';
        if (!nameModal || !pendingSaveDataUrl) return;
        mountOverlay(nameModal);
        if (namePreview) namePreview.src = pendingSaveDataUrl;
        if (nameInput) {
            nameInput.value = 'My signature';
            nameInput.focus();
            nameInput.select();
        }
        nameModal.classList.remove('hidden');
    }

    function closeNameModal() {
        if (nameModal) nameModal.classList.add('hidden');
        pendingSaveDataUrl = '';
        if (nameConfirmBtn) {
            nameConfirmBtn.disabled = false;
            nameConfirmBtn.textContent = 'Save signature';
        }
    }

    function openDeleteModal(id, label) {
        pendingDeleteId = String(id || '');
        if (!deleteModal || !pendingDeleteId) return;
        mountOverlay(deleteModal);
        if (deleteLabel) deleteLabel.textContent = label || 'This signature';
        deleteModal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        if (deleteModal) deleteModal.classList.add('hidden');
        pendingDeleteId = '';
        if (deleteConfirmBtn) {
            deleteConfirmBtn.disabled = false;
            deleteConfirmBtn.textContent = 'Remove';
        }
    }

    function openSignPad() {
        if (!padModal) return;
        mountOverlay(padModal);
        padModal.classList.remove('hidden');
        requestAnimationFrame(function () {
            if (window.initSignaturePad) {
                window.initSignaturePad('adminDaSignatureCanvas');
            }
        });
    }

    function closeSignPad() {
        if (padModal) padModal.classList.add('hidden');
    }

    function applyPadDrawing() {
        var canvas = document.getElementById('adminDaSignatureCanvas');
        if (!canvasHasDrawing(canvas)) {
            showNotice('Please sign before applying.');
            return;
        }
        var dataUrl = canvas.toDataURL('image/png');
        applySignature(dataUrl);
        if (uploadInput) uploadInput.value = '';
        if (uploadNameOut) {
            uploadNameOut.textContent = '';
            uploadNameOut.classList.add('hidden');
        }
        closeSignPad();
        var sigs = form && form.querySelector('.ris-signatures');
        if (sigs && typeof sigs.scrollIntoView === 'function') {
            sigs.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        if (saveOnDraw && saveOnDraw.checked) {
            var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
            if (currentCount >= maxSavedSignatures) {
                showNotice('Signature applied. List is full (4 max) — remove one to save this drawing.');
            } else {
                saveSignatureToLibrary({ dataUrl: dataUrl, label: 'Drawn signature' }).catch(function (err) {
                    showNotice(err.message || 'Could not save signature to your list.');
                });
            }
        }
    }

    function readFileAsDataUrl(file) {
        return new Promise(function (resolve, reject) {
            if (!file) return resolve(null);
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    if (openPadBtn) openPadBtn.addEventListener('click', openSignPad);
    if (applyPadBtn) applyPadBtn.addEventListener('click', applyPadDrawing);
    if (closePadBtn) closePadBtn.addEventListener('click', closeSignPad);
    if (cancelPadBtn) cancelPadBtn.addEventListener('click', closeSignPad);
    if (clearSignBtn) clearSignBtn.addEventListener('click', clearSignature);

    if (saveCurrentBtn) {
        saveCurrentBtn.addEventListener('click', function () {
            var dataUrl = issuedHidden ? String(issuedHidden.value || '') : '';
            if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
                showNotice('Apply or upload a signature first.');
                return;
            }
            var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
            if (currentCount >= maxSavedSignatures) {
                showNotice('You can save up to 4 signatures only. Remove one first.');
                return;
            }
            openNameModal(dataUrl);
        });
    }

    if (nameConfirmBtn) {
        nameConfirmBtn.addEventListener('click', function () {
            if (!pendingSaveDataUrl) return;
            var label = nameInput ? String(nameInput.value || '').trim() : '';
            nameConfirmBtn.disabled = true;
            nameConfirmBtn.textContent = 'Saving...';
            saveSignatureToLibrary({ dataUrl: pendingSaveDataUrl, label: label || 'My signature' }).then(function () {
                closeNameModal();
                if (saveCurrentBtn) {
                    saveCurrentBtn.textContent = 'Saved';
                    window.setTimeout(function () {
                        saveCurrentBtn.textContent = 'Save to my list';
                    }, 1200);
                }
                showNotice('Signature saved to your list.');
            }).catch(function (err) {
                nameConfirmBtn.disabled = false;
                nameConfirmBtn.textContent = 'Save signature';
                showNotice(err.message || 'Could not save signature.');
            });
        });
    }

    if (nameCancelBtn) nameCancelBtn.addEventListener('click', closeNameModal);
    if (nameCloseBtn) nameCloseBtn.addEventListener('click', closeNameModal);
    if (nameModal) {
        var nameDismiss = nameModal.querySelector('[data-admin-da-name-dismiss]');
        if (nameDismiss) {
            nameDismiss.addEventListener('click', function (event) {
                if (event.target === nameDismiss) closeNameModal();
            });
        }
    }
    if (nameInput) {
        nameInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (nameConfirmBtn) nameConfirmBtn.click();
            }
            if (event.key === 'Escape') closeNameModal();
        });
    }

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function () {
            if (!pendingDeleteId) return;
            deleteConfirmBtn.disabled = true;
            deleteConfirmBtn.textContent = 'Removing...';
            deleteSavedSignature(pendingDeleteId).then(function () {
                closeDeleteModal();
                showNotice('Signature removed from your list.');
            }).catch(function (err) {
                deleteConfirmBtn.disabled = false;
                deleteConfirmBtn.textContent = 'Remove';
                showNotice(err.message || 'Could not delete signature.');
            });
        });
    }
    if (deleteCancelBtn) deleteCancelBtn.addEventListener('click', closeDeleteModal);
    if (deleteModal) {
        var deleteDismiss = deleteModal.querySelector('[data-admin-da-delete-dismiss]');
        if (deleteDismiss) {
            deleteDismiss.addEventListener('click', function (event) {
                if (event.target === deleteDismiss) closeDeleteModal();
            });
        }
    }

    if (savedList) {
        savedList.addEventListener('click', function (event) {
            var useBtn = event.target.closest('.admin-da-use-saved');
            if (useBtn) {
                var previewImg = useBtn.querySelector('.admin-da-saved-preview');
                applySignature(previewImg ? previewImg.getAttribute('src') : '');
                if (uploadInput) uploadInput.value = '';
                if (uploadNameOut) {
                    uploadNameOut.textContent = '';
                    uploadNameOut.classList.add('hidden');
                }
                var sigs = form && form.querySelector('.ris-signatures');
                if (sigs && typeof sigs.scrollIntoView === 'function') {
                    sigs.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            var deleteBtn = event.target.closest('.admin-da-delete-saved');
            if (deleteBtn) {
                var id = deleteBtn.getAttribute('data-id');
                if (!id) return;
                openDeleteModal(id, deleteBtn.getAttribute('data-label') || 'This signature');
            }
        });
    }

    if (padModal) {
        var dismiss = padModal.querySelector('[data-admin-da-pad-dismiss]');
        if (dismiss) {
            dismiss.addEventListener('click', function (event) {
                if (event.target === dismiss) closeSignPad();
            });
        }
    }

    if (uploadInput) {
        uploadInput.addEventListener('click', function () {
            if (window.markAdminDaFileDialog) window.markAdminDaFileDialog(true);
        });
        uploadInput.addEventListener('change', function () {
            if (window.markAdminDaFileDialog) window.markAdminDaFileDialog(false);
            var file = uploadInput.files && uploadInput.files[0];
            if (uploadNameOut) {
                if (file) {
                    uploadNameOut.textContent = file.name;
                    uploadNameOut.classList.remove('hidden');
                } else {
                    uploadNameOut.textContent = '';
                    uploadNameOut.classList.add('hidden');
                }
            }
            if (!file) return;
            readFileAsDataUrl(file).then(function (url) {
                applySignature(url || '');
                if (saveOnUpload && saveOnUpload.checked) {
                    var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
                    if (currentCount >= maxSavedSignatures) {
                        showNotice('Signature applied. List is full (4 max) — remove one to save this upload.');
                    } else {
                        var label = file.name ? String(file.name).replace(/\.[^.]+$/, '') : 'Uploaded signature';
                        saveSignatureToLibrary({ file: file, label: label }).then(function () {
                            showNotice('Signature saved to your list.');
                        }).catch(function (err) {
                            showNotice(err.message || 'Signature applied, but could not save it to your list.');
                        });
                    }
                }
            }).catch(function () {});
        });
    }

    dateInputs.forEach(function (input) {
        if (!input) return;
        input.addEventListener('input', function () {
            var digits = String(this.value || '').replace(/\D/g, '').slice(0, 8);
            var parts = [];
            if (digits.length > 0) parts.push(digits.slice(0, 2));
            if (digits.length > 2) parts.push(digits.slice(2, 4));
            if (digits.length > 4) parts.push(digits.slice(4, 8));
            this.value = parts.join('/');
        });
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            var canvas = document.getElementById('adminDaSignatureCanvas');
            if (canvasHasDrawing(canvas)) {
                applySignature(canvas.toDataURL('image/png'));
                return;
            }
            var file = uploadInput && uploadInput.files && uploadInput.files[0];
            var issuedVal = issuedHidden ? String(issuedHidden.value || '') : '';
            var checkedVal = checkedHidden ? String(checkedHidden.value || '') : '';
            if (file && issuedVal.indexOf('data:image/') !== 0 && checkedVal.indexOf('data:image/') !== 0) {
                event.preventDefault();
                readFileAsDataUrl(file).then(function (url) {
                    applySignature(url || '');
                    form.submit();
                }).catch(function () {
                    form.submit();
                });
            }
        });
    }

    updateSaveAvailability(savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0);
})();
</script>
@endif
