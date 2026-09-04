{{-- President signs Approved by only --}}
@php
    $presidentName = Auth::user()->user_full_name ?? 'President';
    $todayDisplay = now()->format('d/m/Y');
    $items = $risItems ?? collect();
    $savedSignatures = $savedSignatures ?? collect();
    $issuedSig = trim((string) ($ris->ris_issued_by_signature ?? ''));
    $receivedSig = trim((string) ($ris->ris_received_by_signature ?? ''));
@endphp

<style>
    .president-ris-form {
        width: 100%;
        max-width: 1095px;
        min-height: 845px;
        border: 2px solid #1f2937;
        padding: 26px 24px 24px;
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        background: #fff;
    }
    .president-ris-form .ris-document-header { position: relative; height: 120px; text-align: center; }
    .president-ris-form .ris-school-name { font-size: 19px; line-height: 1.2; font-weight: 700; }
    .president-ris-form .ris-document-title { margin-top: 9px; font-size: 15px; line-height: 1.2; font-weight: 700; }
    .president-ris-form .ris-number-area { position: absolute; right: 0; bottom: 18px; display: flex; align-items: flex-end; gap: 10px; }
    .president-ris-form .ris-number-label { font-size: 15px; font-weight: 600; }
    .president-ris-form .ris-number-line {
        display: flex; align-items: flex-end; justify-content: center;
        width: 160px; min-height: 24px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px;
    }
    .president-ris-form .ris-items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .president-ris-form .ris-items-table th,
    .president-ris-form .ris-items-table td { border: 1px solid #1f2937; }
    .president-ris-form .ris-items-table th {
        padding: 9px 5px; vertical-align: middle; text-align: center;
        font-size: 12px; line-height: 1.2; font-weight: 700;
    }
    .president-ris-form .ris-items-table tbody td {
        height: 45px; padding: 4px 6px; font-size: 12px; vertical-align: middle;
    }
    .president-ris-form .ris-item-column { width: 24%; }
    .president-ris-form .ris-brand-column { width: 12%; }
    .president-ris-form .ris-unit-column { width: 10%; }
    .president-ris-form .ris-quantity-header { width: 23%; }
    .president-ris-form .ris-requested-column { width: 11%; font-size: 11px !important; }
    .president-ris-form .ris-issued-column { width: 12%; font-size: 11px !important; }
    .president-ris-form .ris-unit-cost-column { width: 17%; }
    .president-ris-form .ris-amount-column { width: 20%; }
    .president-ris-form .ris-purpose-area { margin-top: 31px; }
    .president-ris-form .ris-purpose-label { font-size: 13px; font-weight: 700; }
    .president-ris-form .ris-purpose-line-row { display: flex; margin-top: 29px; }
    .president-ris-form .ris-purpose-spacer { width: 80px; flex-shrink: 0; }
    .president-ris-form .ris-purpose-line {
        flex: 1; min-height: 40px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; font-weight: 400;
    }
    .president-ris-form .ris-signatures {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        column-gap: 32px; margin-top: 40px;
    }
    .president-ris-form .ris-signature-column { min-width: 0; }
    .president-ris-form .ris-signature-label { font-size: 12px; color: #374151; }
    .president-ris-form .ris-signature-line {
        display: flex; align-items: flex-end; justify-content: center;
        position: relative;
        height: 49px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
        overflow: visible;
    }
    .president-ris-form .ris-signature-line .signature-image {
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
    .president-ris-form .ris-signature-line .signature-name {
        font-size: 11px;
        letter-spacing: 0;
        text-transform: none;
    }
    .president-ris-form .ris-signature-input-wrap {
        position: relative;
        overflow: visible;
    }
    .president-ris-form .ris-signature-input-wrap .signature-image {
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
    .president-ris-form .ris-signature-input {
        position: relative;
        z-index: 1;
    }
    .president-ris-form .ris-date-label { margin-top: 16px; font-size: 12px; color: #374151; }
    .president-ris-form .ris-date-line {
        display: flex; align-items: flex-end; justify-content: center;
        height: 31px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .president-ris-form .ris-signature-line--input,
    .president-ris-form .ris-date-line--input {
        padding: 0 6px 4px;
    }
    .president-ris-form .ris-signature-input,
    .president-ris-form .ris-date-input {
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
    .president-ris-form .ris-signature-input:focus,
    .president-ris-form .ris-date-input:focus,
    .president-ris-form .ris-signature-input:-webkit-autofill,
    .president-ris-form .ris-signature-input:-webkit-autofill:hover,
    .president-ris-form .ris-signature-input:-webkit-autofill:focus,
    .president-ris-form .ris-date-input:-webkit-autofill,
    .president-ris-form .ris-date-input:-webkit-autofill:hover,
    .president-ris-form .ris-date-input:-webkit-autofill:focus {
        border: 0 !important;
        outline: none !important;
        box-shadow: 0 0 0 1000px #fff inset !important;
        -webkit-text-fill-color: #000;
        transition: background-color 99999s ease-out;
    }
    .president-ris-locked { pointer-events: none; user-select: none; }
</style>
@include('partials.ris-signature-overlay-styles')

<form
    id="presidentApproveForm"
    method="POST"
    action="{{ route('president.approvals.ris.decide') }}"
    class="flex min-h-0 flex-1 flex-col"
>
    @csrf
    <input type="hidden" name="target_id" value="{{ $ris->ris_id }}">
    <input type="hidden" name="decision" value="Approved">
    <input type="hidden" name="signature_data" id="pa_signature_data" value="">

    <div class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6">
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-100 px-4 py-3 text-xs text-slate-900">
            You can only fill <strong>Approved by</strong> and its <strong>Date</strong>. Add a signature below so it overlays the name on the form.
        </div>

        <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
            <div class="president-ris-form mx-auto">
                <div class="ris-document-header president-ris-locked">
                    <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                    <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
                    <div class="ris-number-area">
                        <span class="ris-number-label">No.</span>
                        <div class="ris-number-line">{{ $ris->ris_form_number ?: '—' }}</div>
                    </div>
                </div>

                <table class="ris-items-table president-ris-locked">
                    <thead>
                        <tr>
                            <th rowspan="2" class="ris-item-column">ITEM</th>
                            <th rowspan="2" class="ris-brand-column">BRAND</th>
                            <th rowspan="2" class="ris-unit-column">UNIT</th>
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
                        @php $rowCount = max(8, $items->count()); @endphp
                        @for($i = 0; $i < $rowCount; $i++)
                            @php $item = $items->get($i); @endphp
                            <tr>
                                <td>{{ $item->ris_item_name_description ?? '' }}</td>
                                <td class="text-center">{{ $item?->brand_name ?? '' }}</td>
                                <td class="text-center">{{ $item?->uom_name ?? '' }}</td>
                                <td class="text-center">{{ $item->ris_quantity_requested ?? '' }}</td>
                                <td class="text-center">{{ $item->ris_quantity_issued ?? '' }}</td>
                                <td class="text-right">{{ isset($item->ris_unit_cost) ? number_format((float) $item->ris_unit_cost, 2) : '' }}</td>
                                <td class="text-right">{{ isset($item->ris_total_amount) ? number_format((float) $item->ris_total_amount, 2) : '' }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                <div class="ris-purpose-area president-ris-locked">
                    <div class="ris-purpose-label">PURPOSE</div>
                    <div class="ris-purpose-line-row">
                        <div class="ris-purpose-spacer"></div>
                        <div class="ris-purpose-line">{{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? '') }}</div>
                    </div>
                </div>

                <div class="ris-signatures">
                    <div class="ris-signature-column president-ris-locked">
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

                    <div class="ris-signature-column">
                        <div class="ris-signature-label">Approved by:</div>
                        <div class="ris-signature-line ris-signature-line--input ris-signature-input-wrap">
                            <img
                                id="paApprovedSigOverlay"
                                class="signature-image"
                                alt="Approved by signature"
                                style="display:none;"
                            >
                            <input
                                type="text"
                                name="ris_approved_by"
                                id="pa_approved_by"
                                value="{{ old('ris_approved_by', $presidentName) }}"
                                required
                                maxlength="255"
                                autocomplete="off"
                                class="ris-signature-input"
                                title="President name for Approved by"
                            >
                        </div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line ris-date-line--input">
                            <input
                                type="text"
                                name="ris_approved_by_date"
                                id="pa_approved_by_date"
                                value="{{ old('ris_approved_by_date', $todayDisplay) }}"
                                required
                                placeholder="dd/mm/yyyy"
                                inputmode="numeric"
                                maxlength="10"
                                autocomplete="off"
                                class="ris-date-input"
                                title="Approved by date (dd/mm/yyyy)"
                            >
                        </div>
                    </div>

                    <div class="ris-signature-column president-ris-locked">
                        <div class="ris-signature-label">Issued by:</div>
                        <div class="ris-signature-line">
                            @if ($issuedSig !== '' && str_starts_with($issuedSig, 'data:image'))
                                <img src="{{ $issuedSig }}" alt="Issued by signature" class="signature-image">
                            @else
                                <span class="signature-name">{{ $issuedSig !== '' ? $issuedSig : ' ' }}</span>
                            @endif
                        </div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_issued_by_date ? \Carbon\Carbon::parse($ris->ris_issued_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>

                    <div class="ris-signature-column president-ris-locked">
                        <div class="ris-signature-label">Received by:</div>
                        <div class="ris-signature-line">
                            @if ($receivedSig !== '' && str_starts_with($receivedSig, 'data:image'))
                                <img src="{{ $receivedSig }}" alt="Received by signature" class="signature-image">
                            @else
                                <span class="signature-name">{{ $receivedSig !== '' ? $receivedSig : ' ' }}</span>
                            @endif
                        </div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">President signature</h4>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Pick a saved signature, draw one, or upload. It overlays <strong>Approved by</strong> on the form above.
                    </p>
                </div>
                <div id="paSignBadge" class="hidden inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                    Signature added
                </div>
            </div>

            <div class="mt-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-medium text-slate-700">My saved signatures</p>
                    <span id="paSavedCount" class="text-[11px] text-slate-400">{{ $savedSignatures->count() }} / 4 saved</span>
                </div>
                <div id="paSavedList" class="mt-2 grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4">
                    @forelse ($savedSignatures as $saved)
                        <div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/90 p-2.5 shadow-sm shadow-slate-900/[0.03] transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md hover:shadow-slate-900/5" data-saved-sig-id="{{ $saved->user_signature_id }}">
                            <button
                                type="button"
                                class="pa-use-saved flex w-full flex-col items-center gap-2 rounded-xl px-1 py-1 text-left"
                                title="Use this signature"
                            >
                                <span class="flex h-14 w-full items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">
                                    <img src="{{ $saved->preview_url }}" alt="" class="pa-saved-preview max-h-10 w-auto max-w-[90%] object-contain">
                                </span>
                                <span class="w-full truncate text-center text-[11px] font-medium text-slate-600">{{ $saved->user_signature_label ?: 'Signature' }}</span>
                            </button>
                            <button
                                type="button"
                                class="pa-delete-saved absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/95 text-slate-400 opacity-0 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100"
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
                        <div id="paSavedEmpty" class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-4 text-center text-xs text-slate-500">
                            No saved signatures yet. Upload or draw one below, then save it for next time.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    id="paOpenSignPad"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#0025cc] px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4l10.5-10.5a2.5 2.5 0 00-3.536-3.536L4 16.464V20z"></path>
                    </svg>
                    <span id="paOpenSignPadLabel">Draw signature</span>
                </button>
                <button
                    type="button"
                    id="paClearSign"
                    class="hidden rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Clear signature
                </button>
                <button
                    type="button"
                    id="paSaveCurrentSign"
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
                    <span id="paSigUploadName" class="mt-1 hidden block truncate text-[11px] font-medium text-slate-600"></span>
                </span>
                <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
                    Choose file
                </span>
                <input
                    type="file"
                    id="paSigUpload"
                    accept="image/*"
                    class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                >
            </label>

            <label class="mt-3 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
                <input
                    type="checkbox"
                    id="paSaveOnUpload"
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                    @checked($savedSignatures->count() < 4)
                    @disabled($savedSignatures->count() >= 4)
                >
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-slate-700">Save uploaded signature to my list</span>
                    <span class="mt-0.5 block text-[11px] text-slate-400">Keeps up to 4 signatures for the next approval.</span>
                </span>
            </label>
        </div>

        <div class="mt-4">
            <label for="pa_remarks" class="block text-xs font-medium text-slate-700">Remarks <span class="font-normal text-slate-400">(optional)</span></label>
            <textarea
                id="pa_remarks"
                name="remarks"
                rows="2"
                placeholder="Optional notes for this approval"
                class="mt-1.5 block w-full resize-y rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
            ></textarea>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-5 py-4 md:px-6">
        <button
            type="button"
            onclick="if (!window._presidentPaFileDialogOpen) closePresidentApproveModal()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            Cancel
        </button>
        <button
            type="submit"
            id="paSubmitBtn"
            class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-800"
        >
            Confirm Approval
        </button>
    </div>
</form>

<div id="paSignPadModal" class="fixed inset-0 z-[12100] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/45 p-4" data-pa-pad-dismiss>
        <div class="relative w-full max-w-[560px] rounded-2xl bg-white p-5 shadow-[0_20px_60px_rgba(15,23,42,0.2)]" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sign the RIS</h3>
                    <p class="mt-1.5 text-sm leading-6 text-slate-500">
                        Sign over your printed name. This overlays <strong>Approved by</strong>.
                    </p>
                </div>
                <button type="button" id="paCloseSignPad" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mt-4">
                @include('partials.signature-pad', [
                    'canvasId' => 'paSignatureCanvas',
                    'hiddenName' => 'pa_pad_scratch',
                    'hiddenId' => 'paPadScratch',
                    'label' => 'Digital signature',
                    'hint' => 'Sign to overlay Approved by.',
                    'requiredMessage' => 'Please sign before applying.',
                ])
            </div>
            <label class="mt-4 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
                <input
                    type="checkbox"
                    id="paSaveOnDraw"
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
                <button type="button" id="paCancelSignPad" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:text-slate-950">Cancel</button>
                <button type="button" id="paApplySignPad" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Apply signature
                </button>
            </div>
        </div>
    </div>
</div>

<div id="paNameSigModal" class="fixed inset-0 z-[12200] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]" data-pa-name-dismiss>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_24px_60px_rgba(15,23,42,0.22)] ring-1 ring-slate-200/80" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="paNameSigTitle">
            <div class="border-b border-slate-100 px-5 pb-3 pt-4">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 id="paNameSigTitle" class="text-base font-semibold tracking-tight text-slate-900">Save to my list</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">Give this signature a short name so it’s easy to reuse later.</p>
                    </div>
                    <button type="button" id="paNameSigClose" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="space-y-3 px-5 py-4">
                <div class="flex items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5">
                    <img id="paNameSigPreview" src="" alt="Signature preview" class="max-h-14 w-auto max-w-full object-contain">
                </div>
                <div>
                    <label for="paNameSigInput" class="block text-xs font-medium text-slate-700">Signature name <span class="font-normal text-slate-400">(optional)</span></label>
                    <input
                        type="text"
                        id="paNameSigInput"
                        maxlength="120"
                        placeholder="e.g. My official signature"
                        class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
                    >
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                <button type="button" id="paNameSigCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                <button type="button" id="paNameSigConfirm" class="rounded-xl bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">Save signature</button>
            </div>
        </div>
    </div>
</div>

<div id="paDeleteSigModal" class="fixed inset-0 z-[12200] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]" data-pa-delete-dismiss>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_24px_60px_rgba(15,23,42,0.22)] ring-1 ring-slate-200/80" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="paDeleteSigTitle">
            <div class="px-5 pb-2 pt-5">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m5 0H4"></path>
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h3 id="paDeleteSigTitle" class="text-base font-semibold tracking-tight text-slate-900">Remove signature?</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            <span id="paDeleteSigLabel" class="font-medium text-slate-700">This signature</span> will be removed from your saved list. You can always upload or draw it again later.
                        </p>
                    </div>
                </div>
            </div>
            <div class="mt-2 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
                <button type="button" id="paDeleteSigCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Keep it</button>
                <button type="button" id="paDeleteSigConfirm" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">Remove</button>
            </div>
        </div>
    </div>
</div>

<div id="paNotice" class="pointer-events-none fixed inset-x-0 bottom-6 z-[12300] flex justify-center px-4 opacity-0 transition duration-200">
    <div class="pointer-events-auto max-w-sm rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-lg shadow-slate-900/10 ring-1 ring-slate-100">
        <span id="paNoticeText"></span>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('presidentApproveForm');
    var sigHidden = document.getElementById('pa_signature_data');
    var approvedOverlay = document.getElementById('paApprovedSigOverlay');
    var padModal = document.getElementById('paSignPadModal');
    var openPadBtn = document.getElementById('paOpenSignPad');
    var openPadLabel = document.getElementById('paOpenSignPadLabel');
    var clearSignBtn = document.getElementById('paClearSign');
    var applyPadBtn = document.getElementById('paApplySignPad');
    var closePadBtn = document.getElementById('paCloseSignPad');
    var cancelPadBtn = document.getElementById('paCancelSignPad');
    var signBadge = document.getElementById('paSignBadge');
    var uploadInput = document.getElementById('paSigUpload');
    var uploadNameOut = document.getElementById('paSigUploadName');
    var saveCurrentBtn = document.getElementById('paSaveCurrentSign');
    var saveOnUpload = document.getElementById('paSaveOnUpload');
    var saveOnDraw = document.getElementById('paSaveOnDraw');
    var savedList = document.getElementById('paSavedList');
    var savedCount = document.getElementById('paSavedCount');
    var nameModal = document.getElementById('paNameSigModal');
    var nameInput = document.getElementById('paNameSigInput');
    var namePreview = document.getElementById('paNameSigPreview');
    var nameConfirmBtn = document.getElementById('paNameSigConfirm');
    var nameCancelBtn = document.getElementById('paNameSigCancel');
    var nameCloseBtn = document.getElementById('paNameSigClose');
    var deleteModal = document.getElementById('paDeleteSigModal');
    var deleteLabel = document.getElementById('paDeleteSigLabel');
    var deleteConfirmBtn = document.getElementById('paDeleteSigConfirm');
    var deleteCancelBtn = document.getElementById('paDeleteSigCancel');
    var noticeEl = document.getElementById('paNotice');
    var noticeText = document.getElementById('paNoticeText');
    var submitBtn = document.getElementById('paSubmitBtn');
    var pendingSaveDataUrl = '';
    var pendingDeleteId = '';
    var noticeTimer = null;
    var submitInFlight = false;
    var maxSavedSignatures = 4;
    var dateInput = document.getElementById('pa_approved_by_date');

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
        if (sigHidden) sigHidden.value = url;
        setOverlay(approvedOverlay, url);
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
        var canvas = document.getElementById('paSignatureCanvas');
        if (window.clearSignaturePad) {
            window.clearSignaturePad('paSignatureCanvas', 'paPadScratch');
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
            savedList.innerHTML = '<div id="paSavedEmpty" class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-3.5 py-4 text-center text-xs text-slate-500">No saved signatures yet. Upload or draw one below, then save it for next time.</div>';
            return;
        }
        savedList.innerHTML = list.map(function (item) {
            var id = item.id;
            var label = escapeHtml(item.label || 'Signature');
            var preview = String(item.preview_url || '').replace(/"/g, '&quot;');
            return ''
                + '<div class="group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/90 p-2.5 shadow-sm shadow-slate-900/[0.03] transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md hover:shadow-slate-900/5" data-saved-sig-id="' + id + '">'
                +   '<button type="button" class="pa-use-saved flex w-full flex-col items-center gap-2 rounded-xl px-1 py-1 text-left" title="Use this signature">'
                +     '<span class="flex h-14 w-full items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">'
                +       '<img src="' + preview + '" alt="" class="pa-saved-preview max-h-10 w-auto max-w-[90%] object-contain">'
                +     '</span>'
                +     '<span class="w-full truncate text-center text-[11px] font-medium text-slate-600">' + label + '</span>'
                +   '</button>'
                +   '<button type="button" class="pa-delete-saved absolute right-2 top-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/95 text-slate-400 opacity-0 shadow-sm ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100" data-id="' + id + '" data-label="' + label + '" title="Remove from list" aria-label="Remove saved signature">'
                +     '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'
                +   '</button>'
                + '</div>';
        }).join('');
    }

    function saveSignatureToLibrary(options) {
        options = options || {};
        var dataUrl = options.dataUrl || (sigHidden ? String(sigHidden.value || '') : '');
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

        return fetch(@json(route('president.approvals.saved-signatures.store')), {
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
        return fetch(@json(url('/president/approvals/saved-signatures')) + '/' + id, {
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
                window.initSignaturePad('paSignatureCanvas');
            }
        });
    }

    function closeSignPad() {
        if (padModal) padModal.classList.add('hidden');
    }

    function applyPadDrawing() {
        var canvas = document.getElementById('paSignatureCanvas');
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
            var dataUrl = sigHidden ? String(sigHidden.value || '') : '';
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
        var nameDismiss = nameModal.querySelector('[data-pa-name-dismiss]');
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
        var deleteDismiss = deleteModal.querySelector('[data-pa-delete-dismiss]');
        if (deleteDismiss) {
            deleteDismiss.addEventListener('click', function (event) {
                if (event.target === deleteDismiss) closeDeleteModal();
            });
        }
    }

    if (savedList) {
        savedList.addEventListener('click', function (event) {
            var useBtn = event.target.closest('.pa-use-saved');
            if (useBtn) {
                var previewImg = useBtn.querySelector('.pa-saved-preview');
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
            var deleteBtn = event.target.closest('.pa-delete-saved');
            if (deleteBtn) {
                var id = deleteBtn.getAttribute('data-id');
                if (!id) return;
                openDeleteModal(id, deleteBtn.getAttribute('data-label') || 'This signature');
            }
        });
    }

    if (padModal) {
        var dismiss = padModal.querySelector('[data-pa-pad-dismiss]');
        if (dismiss) {
            dismiss.addEventListener('click', function (event) {
                if (event.target === dismiss) closeSignPad();
            });
        }
    }

    if (uploadInput) {
        uploadInput.addEventListener('click', function () {
            if (window.markPresidentPaFileDialog) window.markPresidentPaFileDialog(true);
        });
        uploadInput.addEventListener('change', function () {
            if (window.markPresidentPaFileDialog) window.markPresidentPaFileDialog(false);
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

    if (dateInput) {
        dateInput.addEventListener('input', function () {
            var digits = String(this.value || '').replace(/\D/g, '').slice(0, 8);
            var parts = [];
            if (digits.length > 0) parts.push(digits.slice(0, 2));
            if (digits.length > 2) parts.push(digits.slice(2, 4));
            if (digits.length > 4) parts.push(digits.slice(4, 8));
            this.value = parts.join('/');
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (submitInFlight) return;

            var canvas = document.getElementById('paSignatureCanvas');
            if (canvasHasDrawing(canvas)) {
                applySignature(canvas.toDataURL('image/png'));
            }

            var sigVal = sigHidden ? String(sigHidden.value || '') : '';
            if (sigVal.indexOf('data:image/') !== 0) {
                showNotice('Please add a signature before approving (draw, upload, or pick a saved one).');
                return;
            }

            submitInFlight = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Approving...';
            }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: new FormData(form),
                credentials: 'same-origin'
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || data.ok === false) {
                        throw new Error((data && data.message) || 'Unable to save decision.');
                    }
                    return data;
                });
            })
            .then(function (data) {
                var risId = data.ris_id || {{ (int) $ris->ris_id }};
                if (typeof window.closePresidentApproveModal === 'function') {
                    closePresidentApproveModal();
                }
                if (typeof window.openApprovedRisPreviewModal === 'function') {
                    openApprovedRisPreviewModal(risId, {
                        afterApprove: true,
                        approvedDate: data.approved_by_date
                            ? new Date(data.approved_by_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
                            : null
                    });
                }
                if (typeof window.showToast === 'function') {
                    showToast('RIS approved. Notify Admin when ready.', { title: 'Approved', type: 'success' });
                } else if (typeof window.showMpToast === 'function') {
                    showMpToast('RIS approved. Notify Admin when ready.', { title: 'Approved', type: 'success', timer: 3600 });
                }
            })
            .catch(function (error) {
                showNotice(error.message || 'Unable to save decision.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirm Approval';
                }
            })
            .finally(function () {
                submitInFlight = false;
            });
        });
    }

    updateSaveAvailability(savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0);
})();
</script>
