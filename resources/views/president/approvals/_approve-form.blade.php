{{-- President signs Approved by only --}}
@php
    $presidentName = Auth::user()->user_full_name ?? 'President';
    $todayDisplay = now()->format('m/d/Y');
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
        width: 220px; min-height: 24px; border-bottom: 1px solid #1f2937;
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
    .president-ris-form .ris-items-table tfoot td {
        height: 38px; padding: 6px 8px; font-size: 12px; font-weight: 700; vertical-align: middle; background: #f8fafc;
    }
    .president-ris-form .ris-item-column { width: 20%; }
    .president-ris-form .ris-brand-column { width: 10%; }
    .president-ris-form .ris-unit-column { width: 7%; }
    .president-ris-form .ris-supplier-column { width: 14%; }
    .president-ris-form .ris-quantity-header { width: 20%; }
    .president-ris-form .ris-requested-column,
    .president-ris-form .ris-issued-column { width: 10%; font-size: 11px !important; white-space: nowrap; }
    .president-ris-form .ris-unit-cost-column,
    .president-ris-form .ris-amount-column { width: 15%; }
    .president-ris-form .ris-purpose-lined-wrap { position: relative; }
    .president-ris-form .ris-purpose-lined-wrap .ris-purpose-label {
        position: absolute; left: 0; top: 0; z-index: 1;
        padding-right: 12px; background: #fff; line-height: 31px;
    }
    .president-ris-form .ris-purpose-input {
        display: block; width: 100%; height: 62px; min-height: 62px; max-height: 62px;
        padding: 0; font-size: 12px; line-height: 31px; text-indent: 5.75rem;
        white-space: pre-wrap; overflow: hidden; border: 0; color: #000;
        background-color: transparent;
        background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 30px, #1f2937 30px, #1f2937 31px);
    }
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
        display: flex; align-items: center; justify-content: center;
        position: relative;
        min-height: 1.75rem; height: auto !important; border-bottom: 1px solid #1f2937;
        padding: 0.35rem 6px; font-size: 12px; text-align: center;
        overflow: visible;
    }
    .president-ris-form .ris-signature-line .signature-image {
        position: absolute;
        left: 50%;
        top: 50%;
        bottom: auto;
        z-index: 10;
        max-height: 38px;
        max-width: 92%;
        width: auto;
        transform: translate(-50%, -50%);
        pointer-events: none;
        object-fit: contain;
        object-position: center center;
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
        top: 50%;
        bottom: auto;
        z-index: 10;
        max-height: 38px;
        max-width: 92%;
        width: auto;
        height: auto;
        transform: translate(-50%, -50%);
        pointer-events: none;
        object-fit: contain;
        object-position: center center;
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
    .president-ris-form .ris-issued-input {
        width: 100%;
        border: 0;
        background: #fff7ed;
        text-align: center;
        font-size: 12px;
        outline: none;
    }
    .president-ris-form .ris-issued-input:focus { background: #ffedd5; }
    .president-ris-form tr.ris-issued-over td {
        background: #fef2f2;
        color: #b91c1c;
    }
    .president-ris-form tr.ris-issued-over .ris-issued-input,
    .president-ris-form tr.ris-issued-over .ris-issued-input:focus {
        background: #fee2e2;
        color: #b91c1c;
    }
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
        @php $paLinkedAtps = collect($linkedAtps ?? [])->values(); @endphp
        @if ($paLinkedAtps->isNotEmpty())
            <div class="mb-3 flex items-center gap-5 border-b border-slate-200 bg-white px-4 pt-2" data-pa-doc-tabs>
                <button
                    type="button"
                    data-pa-tab="ris"
                    class="border-b-2 pb-2 text-xs font-semibold transition"
                    style="border-color:#2563eb;color:#1d4ed8;"
                >
                    Requisition Slip
                </button>
                <button
                    type="button"
                    data-pa-tab="atp"
                    class="inline-flex items-center gap-1.5 border-b-2 pb-2 text-xs font-semibold transition"
                    style="border-color:transparent;color:#64748b;"
                >
                    <span>Authorities to Purchase</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold" style="background:#eff6ff;color:#1d4ed8;">{{ $paLinkedAtps->count() }}</span>
                </button>
            </div>

            <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-slate-100/80 shadow-inner" data-pa-pane="atp">
                <div class="space-y-4 p-3 md:p-5">
                    @if ($paLinkedAtps->count() > 1)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-2.5">
                            <button
                                type="button"
                                data-pa-atp-prev
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
                                title="Previous ATP"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                            </button>
                            <span class="text-xs font-semibold text-slate-700" data-pa-atp-label>
                                ATP 1 of {{ $paLinkedAtps->count() }}
                            </span>
                            <button
                                type="button"
                                data-pa-atp-next
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:opacity-90 disabled:opacity-40"
                                style="background:#2563eb;color:#ffffff;"
                                title="Next ATP"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </button>
                        </div>
                    @endif

                    @foreach ($paLinkedAtps as $atpIdx => $linkedAtp)
                        <div
                            class="{{ $atpIdx === 0 ? '' : 'hidden' }}"
                            data-pa-atp-sheet="{{ $atpIdx }}"
                            data-pa-atp-title="{{ $linkedAtp->authority_purchase_form_number ?: ('ATP #'.$linkedAtp->authority_purchase_id) }} · {{ $linkedAtp->company_name ?: ($linkedAtp->shop_name ?: 'Supplier') }}"
                        >
                            @include('partials.authority-to-purchase-paper', [
                                'editable' => false,
                                'atp' => $linkedAtp,
                                'items' => $linkedAtp->atpItems ?? collect(),
                                'adminLiveSign' => true,
                                'adminSignerName' => $presidentName,
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
            <script>
            (function () {
                var tabs = document.querySelector('[data-pa-doc-tabs]');
                if (!tabs || tabs.dataset.bound === '1') return;
                tabs.dataset.bound = '1';
                var modalBody = tabs.closest('form') || document;
                var risPane = modalBody.querySelector('[data-pa-pane="ris"]');
                var atpPane = modalBody.querySelector('[data-pa-pane="atp"]');
                var btns = tabs.querySelectorAll('[data-pa-tab]');

                function setTab(name) {
                    if (risPane) risPane.classList.toggle('hidden', name !== 'ris');
                    if (atpPane) atpPane.classList.toggle('hidden', name !== 'atp');
                    btns.forEach(function (b) {
                        var active = b.getAttribute('data-pa-tab') === name;
                        b.style.borderColor = active ? '#2563eb' : 'transparent';
                        b.style.color = active ? '#1d4ed8' : '#64748b';
                    });
                }
                btns.forEach(function (b) {
                    b.addEventListener('click', function () { setTab(b.getAttribute('data-pa-tab')); });
                });

                var sheets = Array.from(modalBody.querySelectorAll('[data-pa-atp-sheet]'));
                var prevBtn = modalBody.querySelector('[data-pa-atp-prev]');
                var nextBtn = modalBody.querySelector('[data-pa-atp-next]');
                var labelEl = modalBody.querySelector('[data-pa-atp-label]');
                var cur = 0;
                function renderAtp(idx) {
                    cur = Math.max(0, Math.min(sheets.length - 1, idx));
                    sheets.forEach(function (el, i) { el.classList.toggle('hidden', i !== cur); });
                    if (labelEl && sheets[cur]) {
                        labelEl.textContent = 'ATP ' + (cur + 1) + ' of ' + sheets.length + ' · ' + (sheets[cur].getAttribute('data-pa-atp-title') || '');
                    }
                    if (prevBtn) prevBtn.disabled = cur === 0;
                    if (nextBtn) nextBtn.disabled = cur >= sheets.length - 1;
                }
                if (sheets.length > 0) renderAtp(0);
                if (prevBtn) prevBtn.addEventListener('click', function () { renderAtp(cur - 1); });
                if (nextBtn) nextBtn.addEventListener('click', function () { renderAtp(cur + 1); });

                var atpNameInputs = Array.from(modalBody.querySelectorAll('[data-da-atp-sig-name]'));
                atpNameInputs.forEach(function (inp) {
                    inp.addEventListener('input', function () {
                        var val = inp.value;
                        atpNameInputs.forEach(function (other) {
                            if (other !== inp) other.value = val;
                        });
                    });
                });
            })();
            </script>
        @endif

        <div class="overflow-x-auto bg-gray-100 p-4 md:p-6" data-pa-pane="ris">
            <div class="president-ris-form mx-auto">
                <div class="ris-document-header president-ris-locked">
                    <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                    <div class="ris-document-title">REQUISITION SLIP</div>
                    <div class="ris-number-area">
                        <span class="ris-number-label">No.</span>
                        <div class="ris-number-line">{{ \App\Support\RisWorkflow::formNumber($ris) ?: '—' }}</div>
                    </div>
                </div>

                <table class="ris-items-table">
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
                        @php
                            $sheetItems = collect($items ?? [])->values();
                            $rowCount = max(1, $sheetItems->count());
                        @endphp
                        @for($i = 0; $i < $rowCount; $i++)
                            @php
                                $item = $sheetItems->get($i);
                                $itemName = trim((string) ($item?->ris_item_name_description ?? ''));
                                $nameKey = mb_strtolower($itemName);
                                $supplier = trim((string) ($item?->supplier_display_name ?? ''));
                                $supplierKey = mb_strtolower($supplier);
                                $prev = $i > 0 ? $sheetItems->get($i - 1) : null;
                                $prevNameKey = mb_strtolower(trim((string) ($prev?->ris_item_name_description ?? '')));
                                $prevSupplierKey = mb_strtolower(trim((string) ($prev?->supplier_display_name ?? '')));
                                $sameItem = $nameKey !== '' && $nameKey === $prevNameKey;
                                $sameSupplier = $sameItem && $supplierKey !== '' && $supplierKey === $prevSupplierKey;
                                $itemSpan = 1;
                                $itemRequestedSum = 0.0;
                                $itemHasRequested = false;
                                $itemIssuedSum = 0.0;
                                $itemHasIssued = false;
                                if ($item !== null && $item->ris_quantity_requested !== null && $item->ris_quantity_requested !== '') {
                                    $itemRequestedSum += (float) $item->ris_quantity_requested;
                                    $itemHasRequested = true;
                                }
                                if ($item !== null && $item->ris_quantity_issued !== null && $item->ris_quantity_issued !== '') {
                                    $itemIssuedSum += (float) $item->ris_quantity_issued;
                                    $itemHasIssued = true;
                                }
                                if ($nameKey !== '' && ! $sameItem) {
                                    for ($k = $i + 1; $k < $sheetItems->count(); $k++) {
                                        $nextItem = $sheetItems->get($k);
                                        $nextName = mb_strtolower(trim((string) ($nextItem?->ris_item_name_description ?? '')));
                                        if ($nextName === '' || $nextName !== $nameKey) {
                                            break;
                                        }
                                        $itemSpan++;
                                        if ($nextItem?->ris_quantity_requested !== null && $nextItem->ris_quantity_requested !== '') {
                                            $itemRequestedSum += (float) $nextItem->ris_quantity_requested;
                                            $itemHasRequested = true;
                                        }
                                        if ($nextItem?->ris_quantity_issued !== null && $nextItem->ris_quantity_issued !== '') {
                                            $itemIssuedSum += (float) $nextItem->ris_quantity_issued;
                                            $itemHasIssued = true;
                                        }
                                    }
                                }
                                $formatQty = static function (float $val): string {
                                    return abs($val - round($val)) < 0.0001
                                        ? (string) (int) round($val)
                                        : rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
                                };
                                $itemRequestedDisplay = $itemHasRequested ? $formatQty($itemRequestedSum) : '';
                                $itemIssuedDisplay = $itemHasIssued ? $formatQty($itemIssuedSum) : '';
                                $supplierSpan = 1;
                                if ($nameKey !== '' && $supplierKey !== '' && ! $sameSupplier) {
                                    for ($k = $i + 1; $k < $sheetItems->count(); $k++) {
                                        $next = $sheetItems->get($k);
                                        $nextName = mb_strtolower(trim((string) ($next?->ris_item_name_description ?? '')));
                                        $nextSupplier = mb_strtolower(trim((string) ($next?->supplier_display_name ?? '')));
                                        if ($nextName !== $nameKey || $nextSupplier !== $supplierKey) {
                                            break;
                                        }
                                        $supplierSpan++;
                                    }
                                }
                                $issuedQty = (int) ($item?->ris_quantity_issued ?? 0);
                                $amountBasis = $issuedQty > 0 ? $issuedQty : (float) ($item?->ris_quantity_requested ?? 0);
                                $lineAmount = isset($item->ris_unit_cost)
                                    ? round($amountBasis * (float) $item->ris_unit_cost, 2)
                                    : null;
                            @endphp
                            <tr>
                                @if (! $sameItem)
                                    <td class="president-ris-locked" @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif>{{ $itemName }}</td>
                                @endif
                                <td class="text-center president-ris-locked">{{ $item ? \App\Support\RisWorkflow::brandPaperLabel($item) : '' }}</td>
                                <td class="text-center president-ris-locked">{{ $item?->uom_name ?? '' }}</td>
                                @if (! $sameSupplier)
                                    <td class="president-ris-locked" @if ($supplierSpan > 1) rowspan="{{ $supplierSpan }}" @endif>{{ $supplier }}</td>
                                @endif
                                @if (! $sameItem)
                                    <td class="text-center president-ris-locked" @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif>{{ $itemRequestedDisplay }}</td>
                                    <td class="text-center president-ris-locked" @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif>{{ $itemIssuedDisplay }}</td>
                                @endif
                                <td class="text-right president-ris-locked">{{ isset($item->ris_unit_cost) ? \App\Support\Money::format($item->ris_unit_cost) : '' }}</td>
                                <td class="text-right president-ris-locked">{{ $lineAmount !== null ? \App\Support\Money::format($lineAmount) : '' }}</td>
                            </tr>
                        @endfor
                    </tbody>
                    @php
                        $presGrandTotal = collect($risItems ?? [])->sum(function ($ti) {
                            if (! filled($ti?->ris_item_name_description ?? null)) return 0;
                            return $ti->ris_total_amount !== null
                                ? (float) $ti->ris_total_amount
                                : (((float) ($ti->ris_quantity_requested ?? 0)) * ((float) ($ti->ris_unit_cost ?? 0)));
                        });
                    @endphp
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right" style="letter-spacing:0.03em;">TOTAL AMOUNT</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Money::format($presGrandTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="ris-purpose-area president-ris-locked">
                    <div class="ris-purpose-lined-wrap">
                        <div class="ris-purpose-label">PURPOSE</div>
                        <div class="ris-purpose-input">{{ trim((string) ($ris->ris_purpose_description ?: ($ris->ris_manual_description ?? ''))) !== '' ? ($ris->ris_purpose_description ?: $ris->ris_manual_description) : ' ' }}</div>
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
                            {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('m/d/Y') : 'mm/dd/yyyy' }}
                        </div>
                    </div>

                    <div class="ris-signature-column">
                        <div class="ris-signature-label">Approved by: <span class="text-red-500">*</span></div>
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
                        <div class="ris-date-label">Date: <span class="text-red-500">*</span></div>
                        <div class="ris-date-line ris-date-line--input">
                            <input
                                type="text"
                                name="ris_approved_by_date"
                                id="pa_approved_by_date"
                                value="{{ old('ris_approved_by_date', $todayDisplay) }}"
                                required
                                placeholder="mm/dd/yyyy"
                                inputmode="numeric"
                                maxlength="10"
                                autocomplete="off"
                                class="ris-date-input"
                                title="Approved by date (mm/dd/yyyy)"
                            >
                        </div>
                    </div>

                    <div class="ris-signature-column">
                        <div class="ris-signature-label">Issued by: <span class="text-red-500">*</span></div>
                        <div class="ris-signature-line ris-signature-line--input ris-signature-input-wrap">
                            <img
                                id="paIssuedSigOverlay"
                                class="signature-image"
                                alt="Issued by signature"
                                style="display:none;"
                            >
                            <span id="paIssuedByName" class="signature-name">{{ old('ris_approved_by', $presidentName) }}</span>
                        </div>
                        <div class="ris-date-label">Date:</div>
                        <div class="ris-date-line" id="paIssuedByDate">
                            {{ old('ris_approved_by_date', $todayDisplay) }}
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
                            {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('m/d/Y') : 'mm/dd/yyyy' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm shadow-slate-900/5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-bold text-slate-900">President signature:</span>
                    <div id="paSigReadyPill" class="hidden inline-flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5">
                        <img id="paSigThumb" src="" alt="President signature" class="h-7 w-auto max-w-[96px] object-contain">
                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold" style="background:#ecfdf5;color:#059669;">✓ Ready</span>
                    </div>
                    <span id="paSigRequiredPill" class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold" style="background:#fffbeb;color:#b45309;">Required</span>
                    <div id="paSignBadge" class="hidden"></div>
                </div>

                <button
                    type="button"
                    id="paToggleSigPanelBtn"
                    onclick="(function(){ var body = document.getElementById('paSigPanelBody'); var btn = document.getElementById('paToggleSigPanelBtn'); if (!body || !btn) return; var willShow = body.classList.contains('hidden'); body.classList.toggle('hidden', !willShow); btn.textContent = willShow ? 'Hide signature pad' : 'Change / Draw signature'; })()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                >{{ $savedSignatures->isNotEmpty() ? 'Change / Draw signature' : 'Hide signature pad' }}</button>
            </div>

            <div id="paSigPanelBody" class="{{ $savedSignatures->isNotEmpty() ? 'hidden' : '' }} mt-4 border-t border-slate-100 pt-4">
                <p class="mb-3 text-xs text-slate-500">Applied to <strong>Approved by</strong> &amp; <strong>Issued by</strong> on the Requisition Slip and <strong>Authorized by</strong> on linked Authorities to Purchase, then released directly to the Purchaser.</p>
                <div>
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
                    <span class="mt-0.5 block text-[11px] text-slate-400">Optional · PNG, JPG, or paper photo · dark ink on light / white bond paper · paper cleared automatically</span>
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
        </div>

        @php $paSupportingDocs = collect($supportingDocuments ?? []); @endphp
        @if ($paSupportingDocs->isNotEmpty())
            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 class="text-sm font-bold text-slate-900">Canvass</h4>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">{{ $paSupportingDocs->count() }} {{ $paSupportingDocs->count() === 1 ? 'file' : 'files' }}</span>
                </div>
                <div class="space-y-2">
                    @foreach ($paSupportingDocs as $doc)
                        @php
                            $docUrl = !empty($doc->ris_attachment_path)
                                ? \Illuminate\Support\Facades\Storage::disk('public')->url($doc->ris_attachment_path)
                                : '#';
                        @endphp
                        <a
                            href="{{ $docUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-xs transition hover:border-blue-200 hover:bg-blue-50/40"
                        >
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 ring-1 ring-slate-200">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-blue-700">{{ $doc->ris_attachment_original_name }}</div>
                                    @if (!empty($doc->ris_attachment_size))
                                        <div class="text-[11px] text-slate-400">{{ number_format($doc->ris_attachment_size / 1024, 1) }} KB</div>
                                    @endif
                                </div>
                            </div>
                            <span class="shrink-0 font-semibold text-slate-600">Open</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 bg-white px-5 py-4 md:px-6">
        <input type="hidden" name="return_to[]" value="school_admin">
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
            style="background:#0025cc;color:#ffffff;"
        >
            Approve
        </button>
    </div>
</form>

@php
    $paConfirmPurchaserName = \App\Support\RisWorkflow::requesterName($ris, 'Purchaser');
    $paConfirmAtpNumbers = collect($linkedAtps ?? [])
        ->map(fn ($a) => trim((string) ($a->authority_purchase_form_number ?? '')))
        ->filter()
        ->values()
        ->all();
    $paConfirmTotal = collect($items ?? [])->sum(function ($it) {
        $amt = (float) ($it->ris_total_amount ?? 0);
        if ($amt > 0) {
            return $amt;
        }
        $qty = (float) ($it->ris_quantity_issued ?? $it->ris_quantity_requested ?? 0);
        $uc = (float) ($it->ris_unit_cost ?? 0);
        return $qty * $uc;
    });
@endphp
<div id="paApproveConfirmModal" class="fixed inset-0 hidden" style="z-index: 99999;">
    <div
        class="absolute inset-0 flex items-center justify-center bg-black/50 p-4 backdrop-blur-[1px]"
        data-pa-confirm-dismiss
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            onclick="event.stopPropagation()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="paApproveConfirmTitle"
        >
            <div class="flex items-start gap-3.5 px-6 pb-4 pt-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background:#eff6ff;color:#1d4ed8;">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 id="paApproveConfirmTitle" class="text-base font-bold text-slate-900">Approve &amp; Send RIS &amp; ATP?</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Your signature will be applied to RIS <strong>Approved by</strong>, <strong>Issued by</strong>, and linked ATP <strong>Authorized by</strong>, then released immediately.
                    </p>
                </div>
            </div>

            <div class="space-y-2.5 px-6 pb-5 text-xs">
                <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50/80 p-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500">RIS Number</span>
                        <span class="font-semibold text-slate-900">{{ \App\Support\RisWorkflow::formNumber($ris) ?: '—' }}</span>
                    </div>
                    @if (!empty($paConfirmAtpNumbers))
                        <div class="flex items-start justify-between gap-2">
                            <span class="shrink-0 text-slate-500">Linked ATP(s)</span>
                            <span class="text-right font-semibold text-slate-900">{{ implode(', ', $paConfirmAtpNumbers) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500">Total Amount</span>
                        <span class="font-semibold text-slate-900">₱{{ number_format((float) $paConfirmTotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2 border-t border-slate-200/80 pt-2">
                        <span class="text-slate-500">Will be sent to</span>
                        <span class="text-right font-semibold" style="color:#0025cc;">{{ $paConfirmPurchaserName }} (Purchaser)</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-slate-500">Also notified</span>
                        <span class="font-medium text-slate-700">School Administrator</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                <button
                    type="button"
                    id="paApproveConfirmCancel"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    id="paApproveConfirmProceed"
                    class="rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800"
                    style="background:#0025cc;color:#ffffff;"
                >
                    Confirm &amp; Send
                </button>
            </div>
        </div>
    </div>
</div>

<div id="paSignPadModal" class="fixed inset-0 z-[12100] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/45 p-4" data-pa-pad-dismiss>
        <div class="relative w-full max-w-[560px] rounded-2xl bg-white p-5 shadow-[0_20px_60px_rgba(15,23,42,0.2)]" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sign the RIS</h3>
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
        var issuedOverlay = document.getElementById('paIssuedSigOverlay');
        setOverlay(issuedOverlay, url);
        if (form) {
            form.querySelectorAll('[data-da-atp-sig-img]').forEach(function (img) {
                setOverlay(img, url);
            });
        }
        var hasSig = url !== '';
        var readyPill = document.getElementById('paSigReadyPill');
        var reqPill = document.getElementById('paSigRequiredPill');
        var sigThumb = document.getElementById('paSigThumb');
        if (readyPill) readyPill.classList.toggle('hidden', !hasSig);
        if (reqPill) reqPill.classList.toggle('hidden', hasSig);
        if (sigThumb) {
            if (hasSig) sigThumb.src = url;
            else sigThumb.removeAttribute('src');
        }
        if (signBadge) signBadge.classList.toggle('hidden', !hasSig);
        if (clearSignBtn) clearSignBtn.classList.toggle('hidden', !hasSig);
        if (saveCurrentBtn) {
            saveCurrentBtn.classList.toggle('hidden', !hasSig);
            var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
            updateSaveAvailability(currentCount);
        }
        if (openPadLabel) openPadLabel.textContent = hasSig ? 'Redraw signature' : 'Draw signature';
    }

    var paNameInput = document.getElementById('pa_approved_by');
    if (paNameInput) {
        paNameInput.addEventListener('input', function () {
            var val = paNameInput.value;
            var issuedNameEl = document.getElementById('paIssuedByName');
            if (issuedNameEl) issuedNameEl.textContent = val;
            if (form) {
                form.querySelectorAll('[data-da-atp-sig-name]').forEach(function (inp) {
                    inp.value = val;
                });
            }
        });
    }
    if (dateInput) {
        dateInput.addEventListener('input', function () {
            var issuedDateEl = document.getElementById('paIssuedByDate');
            if (issuedDateEl) issuedDateEl.textContent = dateInput.value || 'mm/dd/yyyy';
        });
    }

    var firstSavedBtn = savedList ? savedList.querySelector('.pa-use-saved') : null;
    if (firstSavedBtn && (!sigHidden || !sigHidden.value)) {
        setTimeout(function () { firstSavedBtn.click(); }, 0);
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
        var dataUrl = (window.exportTrimmedSignatureDataUrl
            ? window.exportTrimmedSignatureDataUrl(canvas)
            : canvas.toDataURL('image/png')) || canvas.toDataURL('image/png');
        applySignature(dataUrl);
        if (uploadInput) uploadInput.value = '';
        if (uploadNameOut) {
            uploadNameOut.textContent = '';
            uploadNameOut.classList.add('hidden');
        }
        closeSignPad();
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
                var savedSrc = previewImg ? previewImg.getAttribute('src') : '';
                var applySaved = function (url) {
                    applySignature(url || '');
                    if (uploadInput) uploadInput.value = '';
                    if (uploadNameOut) {
                        uploadNameOut.textContent = '';
                        uploadNameOut.classList.add('hidden');
                    }
                };
                if (savedSrc && window.PrismSignatureImage && window.PrismSignatureImage.cleanForOverlay) {
                    window.PrismSignatureImage.cleanForOverlay(savedSrc).then(applySaved).catch(function () {
                        applySaved(savedSrc);
                    });
                } else {
                    applySaved(savedSrc);
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
            var load = (window.PrismSignatureImage && window.PrismSignatureImage.fromFile)
                ? window.PrismSignatureImage.fromFile(file)
                : readFileAsDataUrl(file);
            load.then(function (url) {
                applySignature(url || '');
                if (saveOnUpload && saveOnUpload.checked) {
                    var currentCount = savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0;
                    if (currentCount >= maxSavedSignatures) {
                        showNotice('Signature applied. List is full (4 max) — remove one to save this upload.');
                    } else {
                        var label = file.name ? String(file.name).replace(/\.[^.]+$/, '') : 'Uploaded signature';
                        saveSignatureToLibrary({ dataUrl: url || '', label: label }).then(function () {
                            showNotice('Signature saved to your list.');
                        }).catch(function (err) {
                            showNotice(err.message || 'Signature applied, but could not save it to your list.');
                        });
                    }
                }
            }).catch(function (err) {
                applySignature('');
                if (uploadInput) uploadInput.value = '';
                if (uploadNameOut) {
                    uploadNameOut.textContent = '';
                    uploadNameOut.classList.add('hidden');
                }
                showNotice(err && err.message
                    ? err.message
                    : 'Use a clear dark-ink signature on light / white bond paper.');
            });
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

    function clampIssuedInput(input) {
        var digits = String(input.value || '').replace(/\D/g, '').slice(0, 7);
        if (input.value !== digits) input.value = digits;
    }

    function formatIssuedMoney(amount) {
        return Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function issuedQuantityError(scope) {
        var inputs = scope.querySelectorAll('[data-issued-input]');
        // Issued is filled by the Purchaser; skip client check when there are no inputs.
        if (!inputs.length) return '';
        var message = '';
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            clampIssuedInput(input);
            var row = input.closest('tr');
            if (row) row.classList.remove('ris-issued-over');
            var label = input.getAttribute('data-item-label') || 'Item';
            var value = String(input.value || '').trim();
            input.classList.remove('text-red-700');
            var amountCell = scope.querySelector('[data-issued-amount="' + String(input.name || '').replace(/\D/g, '') + '"]');
            if (!/^\d{1,7}$/.test(value) || Number(value) < 1) {
                input.classList.add('text-red-700');
                if (amountCell) amountCell.textContent = amountCell.getAttribute('data-purchaser-amount') || '';
                if (!message) message = '"' + label + '" needs an Issued quantity of at least 1, up to 7 digits.';
                continue;
            }
            if (value.length > 7 || Number(value) > 9999999) {
                input.classList.add('text-red-700');
                if (!message) message = '"' + label + '" Issued quantity can be up to 7 digits only.';
            }
            // Each line is independent (same name / different supplier = separate).
            var asked = Number(input.getAttribute('data-row-asked') || input.getAttribute('data-group-asked') || 0);
            var issued = Number(value);
            if (asked > 0 && issued > asked) {
                if (row) row.classList.add('ris-issued-over');
                if (!message) {
                    message = '"' + label + '" is over the requested amount (' + issued + ' issued of ' + asked + ' requested).';
                }
            }
            if (amountCell) {
                amountCell.textContent = formatIssuedMoney(issued * Number(input.getAttribute('data-unit-cost') || 0));
            }
        }
        return message;
    }

    var confirmModal = document.getElementById('paApproveConfirmModal');
    var confirmCancelBtn = document.getElementById('paApproveConfirmCancel');
    var confirmProceedBtn = document.getElementById('paApproveConfirmProceed');

    if (confirmModal && confirmModal.parentElement !== document.body) {
        document.body.appendChild(confirmModal);
    }

    function closeApproveConfirmModal() {
        if (confirmModal) confirmModal.classList.add('hidden');
    }

    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', closeApproveConfirmModal);
    }
    if (confirmModal) {
        confirmModal.addEventListener('click', function (e) {
            if (e.target && e.target.hasAttribute('data-pa-confirm-dismiss')) {
                closeApproveConfirmModal();
            }
        });
    }

    function executePresidentApprovalSubmit() {
        if (submitInFlight || !form) return;
        submitInFlight = true;
        if (confirmProceedBtn) {
            confirmProceedBtn.disabled = true;
            confirmProceedBtn.textContent = 'Sending...';
        }
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
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
            closeApproveConfirmModal();
            if (typeof window.closePresidentApproveModal === 'function') {
                closePresidentApproveModal();
            }
            if (typeof window.openApprovedRisPreviewModal === 'function') {
                openApprovedRisPreviewModal(risId, {
                    afterApprove: true,
                    adminNotified: data.admin_notified !== false,
                    awaitingNotify: false,
                    approvedDate: data.approved_by_date
                        ? new Date(data.approved_by_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
                        : null
                });
            }
            var approvedMessage = (data && data.message) ? data.message : 'RIS approved.';
            if (typeof window.showToast === 'function') {
                showToast(approvedMessage, { title: 'Approved', type: 'success' });
            } else if (typeof window.showMpToast === 'function') {
                showMpToast(approvedMessage, { title: 'Approved', type: 'success', timer: 3600 });
            }
        })
        .catch(function (error) {
            closeApproveConfirmModal();
            showNotice(error.message || 'Unable to save decision.');
            if (confirmProceedBtn) {
                confirmProceedBtn.disabled = false;
                confirmProceedBtn.textContent = 'Confirm & Send';
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Approve';
            }
        })
        .finally(function () {
            submitInFlight = false;
        });
    }

    if (confirmProceedBtn) {
        confirmProceedBtn.addEventListener('click', executePresidentApprovalSubmit);
    }

    if (form) {
        form.addEventListener('input', function (event) {
            if (event.target && event.target.matches('[data-issued-input]')) {
                issuedQuantityError(form);
            }
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (submitInFlight) return;

            var issuedError = issuedQuantityError(form);
            if (issuedError) {
                showNotice(issuedError);
                return;
            }
            var canvas = document.getElementById('paSignatureCanvas');
            if (canvasHasDrawing(canvas)) {
                applySignature((window.exportTrimmedSignatureDataUrl
                    ? window.exportTrimmedSignatureDataUrl(canvas)
                    : canvas.toDataURL('image/png')) || canvas.toDataURL('image/png'));
            }

            var sigVal = sigHidden ? String(sigHidden.value || '') : '';
            if (sigVal.indexOf('data:image/') !== 0) {
                showNotice('Please add a signature before approving (draw, upload, or pick a saved one).');
                return;
            }

            if (confirmModal) {
                if (confirmModal.parentElement !== document.body) {
                    document.body.appendChild(confirmModal);
                }
                confirmModal.classList.remove('hidden');
            } else {
                executePresidentApprovalSubmit();
            }
        });
    }

    updateSaveAvailability(savedList ? savedList.querySelectorAll('[data-saved-sig-id]').length : 0);
})();
</script>
