{{-- President signs Approved by only --}}
@php
    $presidentName = Auth::user()->user_full_name ?? 'President';
    $todayDisplay = now()->format('d/m/Y');
    $items = $risItems ?? collect();
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
        height: 49px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .president-ris-form .ris-date-label { margin-top: 16px; font-size: 12px; color: #374151; }
    .president-ris-form .ris-date-line {
        display: flex; align-items: flex-end; justify-content: center;
        height: 31px; border-bottom: 1px solid #1f2937;
        padding: 0 6px 4px; font-size: 12px; text-align: center;
    }
    .president-ris-form .ris-signature-input,
    .president-ris-form .ris-date-input {
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
    .president-ris-form .ris-signature-input { height: 49px; padding: 0 6px 4px; }
    .president-ris-form .ris-date-input { height: 31px; padding: 0 6px 4px; }
    .president-ris-form .ris-editable-hint {
        margin-top: 4px; font-size: 10px; color: #64748B; text-align: center; font-weight: 600;
    }
    .president-ris-locked { pointer-events: none; user-select: none; }
</style>

<form
    id="presidentApproveForm"
    method="POST"
    action="{{ route('president.approvals.ris.decide') }}"
    class="flex min-h-0 flex-1 flex-col"
>
    @csrf
    <input type="hidden" name="target_id" value="{{ $ris->ris_id }}">
    <input type="hidden" name="decision" value="Approved">

    <div class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6">
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-100 px-4 py-3 text-xs text-slate-900">
            You can only fill <strong>Approved by</strong> and its <strong>Date</strong>. All other RIS details are locked.
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
                        <div class="ris-signature-line">{{ $ris->ris_requested_by_signature ?: ' ' }}</div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>

                    <div class="ris-signature-column">
                        <div class="ris-signature-label">Approved by:</div>
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
                        <div class="ris-editable-hint">Editable</div>
                        <div class="ris-date-label">Date:</div>
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
                        <div class="ris-editable-hint">Editable</div>
                    </div>

                    <div class="ris-signature-column president-ris-locked">
                        <div class="ris-signature-label">Issued by:</div>
                        <div class="ris-signature-line">{{ $ris->ris_issued_by_signature ?: ' ' }}</div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line">
                            {{ $ris->ris_issued_by_date ? \Carbon\Carbon::parse($ris->ris_issued_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                        </div>
                    </div>

                    <div class="ris-signature-column president-ris-locked">
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
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-white px-6 py-4">
        <button
            type="button"
            onclick="closePresidentApproveModal()"
            class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
        >
            Cancel
        </button>
        <button
            type="submit"
            class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800"
        >
            Confirm Approval
        </button>
    </div>
</form>
