{{-- Admin sign Issued by: Direct Approve, Forward to President, or Amend --}}
@php
    $adminName = Auth::user()->user_full_name ?? 'Admin';
    $todayDisplay = now()->format('d/m/Y');
    $items = $risItems ?? collect();
    $mode = in_array(($mode ?? 'direct'), ['direct', 'forward', 'amend'], true) ? $mode : 'direct';
    $isForward = $mode === 'forward';
    $isAmend = $mode === 'amend';
    $formAction = $isAmend
        ? route('admin.procurement-review.ris.reject', $ris->ris_id)
        : ($isForward
            ? route('admin.procurement-review.ris.approve', $ris->ris_id)
            : route('admin.procurement-review.ris.direct-approve', $ris->ris_id));
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
    .admin-da-ris-form .ris-item-column { width: 40%; }
    .admin-da-ris-form .ris-quantity-header { width: 23%; }
    .admin-da-ris-form .ris-requested-column { width: 11%; font-size: 11px !important; }
    .admin-da-ris-form .ris-issued-column { width: 12%; font-size: 11px !important; }
    .admin-da-ris-form .ris-unit-cost-column { width: 17%; }
    .admin-da-ris-form .ris-amount-column { width: 20%; }
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
        height: 49px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .admin-da-ris-form .ris-date-label { margin-top: 16px; font-size: 12px; color: #374151; }
    .admin-da-ris-form .ris-date-line {
        display: flex; align-items: flex-end; justify-content: center;
        height: 31px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .admin-da-ris-form .ris-signature-input,
    .admin-da-ris-form .ris-date-input {
        width: 100%;
        border: 0;
        border-bottom: 1px solid #1f2937;
        border-radius: 0;
        background: #fffbeb;
        outline: none;
        box-shadow: none;
        text-align: center;
        font-size: 12px;
    }
    .admin-da-ris-form .ris-signature-input {
        height: 49px;
        padding: 0 6px 4px;
    }
    .admin-da-ris-form .ris-date-input {
        height: 31px;
        padding: 0 6px 4px;
    }
    .admin-da-ris-form .ris-editable-hint {
        margin-top: 4px;
        font-size: 10px;
        color: #d97706;
        text-align: center;
        font-weight: 600;
    }
    .admin-da-locked {
        pointer-events: none;
        user-select: none;
    }
</style>

<form
    id="directApproveForm"
    method="POST"
    action="{{ $formAction }}"
    class="flex min-h-0 flex-1 flex-col"
    data-mode="{{ $mode }}"
>
    @csrf

    <div class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6">
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
            @if ($isForward)
                All RIS details are locked. Confirming will forward this RIS to the <strong>President</strong> without an Issued by signature. You sign Issued by later on Sign RIS after the President approves.
            @else
                You can only fill <strong>Issued by</strong> and its <strong>Date</strong>. All other RIS details are locked.
                @if ($isAmend)
                    Sign <strong>Issued by</strong> first, then enter amendment remarks to return this RIS to the Purchaser.
                @else
                    Confirming will mark this RIS as <strong>Admin Approved</strong> and return it to the Purchaser.
                @endif
            @endif
        </div>

        <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
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
                        <div class="ris-signature-line">{{ $ris->ris_requested_by_signature ?: ' ' }}</div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>

                    <div class="ris-signature-column admin-da-locked">
                        <div class="ris-signature-label">Approved by:</div>
                        <div class="ris-signature-line"> </div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">dd/mm/yyyy</div>
                    </div>

                    <div class="ris-signature-column {{ $isForward ? 'admin-da-locked' : '' }}">
                        <div class="ris-signature-label">Issued by:</div>
                        @if ($isForward)
                            <div class="ris-signature-line"> </div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line">dd/mm/yyyy</div>
                        @else
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
                        <div class="ris-editable-hint">Editable</div>
                        <div class="ris-date-label">Date:</div>
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
                        <div class="ris-editable-hint">Editable</div>
                        @endif
                    </div>

                    <div class="ris-signature-column admin-da-locked">
                        <div class="ris-signature-label">Received by:</div>
                        <div class="ris-signature-line">{{ $ris->ris_received_by_signature ?: ' ' }}</div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($isAmend)
            <div class="mt-4 rounded-lg border border-rose-200 bg-white px-4 py-4">
                <label for="amend_remarks" class="block text-sm font-medium text-gray-700">
                    Amendment Remarks <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="amend_remarks"
                    name="remarks"
                    rows="4"
                    required
                    placeholder="Describe in detail what needs to be revised, e.g. incorrect quantities, missing supporting documents, wrong unit cost, etc."
                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                >{{ old('remarks') }}</textarea>
                <p class="mt-1.5 text-xs text-gray-400">These remarks will be visible to the Purchaser when they view this RIS.</p>
            </div>
        @endif
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-white px-6 py-4">
        <button
            type="button"
            onclick="closeDirectApproveModal()"
            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
        >
            Cancel
        </button>
        <button
            type="submit"
            class="rounded-lg {{ $isAmend ? 'bg-rose-600 hover:bg-rose-700' : ($isForward ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-slate-900 hover:bg-slate-800') }} px-4 py-2.5 text-sm font-medium text-white transition"
            title="{{ $isAmend ? 'Sign Issued by and return for amendment' : ($isForward ? 'Forward this RIS to the President' : 'Mark as Admin Approved and return to Purchaser') }}"
        >
            {{ $isAmend ? 'Sign & Confirm Amend' : ($isForward ? 'Forward to President' : 'Confirm Admin Approval') }}
        </button>
    </div>
</form>
