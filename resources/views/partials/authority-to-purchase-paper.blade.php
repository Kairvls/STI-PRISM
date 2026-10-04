@php
    $editable = $editable ?? false;
    $atp = $atp ?? null;
    $items = collect($items ?? [])->values();
    $suppliers = $suppliers ?? collect();
    $uomNames = collect($uomNames ?? [])->values();
    $printClass = $printClass ?? '';
    $printId = $printId ?? null;
    $atpTotal = $items->sum(fn ($item) => (float) ($item->atp_amount ?? 0));
    $isBlank = !$editable && !$atp;

    $dateValue = old(
        'authority_purchase_date',
        $atp?->authority_purchase_date ?? ($editable && !$atp ? now()->format('Y-m-d') : '')
    );
    $supplierId = old('authority_purchase_supplier_id', $atp?->authority_purchase_supplier_id ?? '');
    $receivedBy = old(
        'authority_purchase_received_by_name',
        $atp?->authority_purchase_received_by_name ?? ($editable ? (auth()->user()->user_full_name ?? '') : '')
    );
    $receivedBySignature = old(
        'authority_purchase_received_by_signature',
        $atp?->authority_purchase_received_by_signature ?? ''
    );
    $signKey = $signKey ?? ($atp?->authority_purchase_id ? 'atp-'.$atp->authority_purchase_id : 'atp-create');
    $poNo = old('authority_purchase_reference_po_no', $atp?->authority_purchase_reference_po_no ?? '');
    $oldItems = old('items');
    if (old('authority_purchase_form_number') !== null) {
        $formNumberValue = (string) old('authority_purchase_form_number');
    } elseif ($atp) {
        $existingNo = trim((string) ($atp->authority_purchase_form_number ?? ''));
        $formNumberValue = \App\Support\AtpFormNumber::isValid($existingNo) ? $existingNo : '';
    } else {
        $formNumberValue = '';
    }

    $supplierLabel = '';
    if ($atp) {
        $supplierLabel = $atp->supplier_store_type === 'Physical Store'
            ? ($atp->company_name ?? '')
            : ($atp->shop_name ?? '');
    }

    $atpDefaultRows = 8;
    $atpMaxRows = 50;
    $oldItems = is_array($oldItems) ? array_values($oldItems) : null;
    if ($editable) {
        $rowCount = $oldItems
            ? min($atpMaxRows, count($oldItems))
            : min($atpMaxRows, max($atpDefaultRows, $items->count()));
    } else {
        $rowCount = max($atpDefaultRows, $items->count());
    }

    // Match RIS line-item inputs: no inner border inside table cells.
    $atpCellClass = 'w-full min-w-0 border-0 bg-transparent px-1 py-1.5 text-sm outline-none ring-0 focus:outline-none focus:ring-0';
@endphp

<div
    @if($printId) id="{{ $printId }}" @endif
    class="atp-print-sheet mx-auto w-full max-w-[1095px] bg-white px-10 pb-[38px] pt-[38px] text-[13px] leading-tight text-black shadow {{ $printClass }}"
>
    {{-- HEADER --}}
    <div class="relative text-center">
        <div class="text-lg font-bold">STI COLLEGE ORMOC, INC.</div>
        <div class="text-xs">Centrum Mall, Aviles Street, Ormoc City</div>
        <div class="mt-2 text-xl font-bold tracking-wide">AUTHORITY TO PURCHASE</div>

        <div class="absolute right-0 top-0 text-left text-sm">
            <div class="flex items-end gap-2 text-red-600">
                <strong class="font-semibold">No.</strong>
                @if($editable)
                    @if(filled($formNumberValue))
                        <input
                            type="text"
                            name="authority_purchase_form_number"
                            value="{{ $formNumberValue }}"
                            readonly
                            class="w-40 border-0 bg-transparent px-1 text-center font-semibold text-red-600 outline-none"
                            aria-label="ATP number"
                        >
                    @else
                        <span class="inline-block min-w-[10rem] px-1 text-center text-xs font-normal text-gray-500">Assigned when submitted</span>
                        <input type="hidden" name="authority_purchase_form_number" value="">
                    @endif
                @elseif($isBlank)
                    <span class="inline-block min-w-[4rem] text-center font-semibold">&nbsp;</span>
                @else
                    <span class="inline-block min-w-[10rem] px-1 text-center font-semibold">{{ $formNumberValue }}</span>
                @endif
            </div>

            <div class="mt-2 flex items-end gap-2">
                <strong>Date @if($editable)<span class="text-red-500">*</span>@endif</strong>
                @if($editable)
                    <input
                        type="date"
                        name="authority_purchase_date"
                        value="{{ $dateValue }}"
                        class="border-0 border-b border-black bg-transparent outline-none"
                        title="Required before submitting to Accounting"
                    >
                @elseif($isBlank)
                    <span class="inline-block min-w-[7rem] border-b border-black text-center">&nbsp;</span>
                @else
                    <span class="inline-block min-w-[7rem] border-b border-black px-1 text-center">
                        {{ $dateValue ? \Carbon\Carbon::parse($dateValue)->format('d/m/Y') : '' }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- SUPPLIER --}}
    <div class="mt-10">
        <strong>To: @if($editable)<span class="text-red-500">*</span>@endif</strong>
        @if($editable)
            @php
                $selectedSupplier = collect($suppliers ?? [])->firstWhere('supplier_id', (int) $supplierId);
                $selectedIsBlacklisted = $selectedSupplier && (int) ($selectedSupplier->supplier_is_blacklisted ?? 0) === 1;
            @endphp
            <select
                name="authority_purchase_supplier_id"
                class="ml-2 w-[420px] border-0 border-b border-black bg-transparent outline-none ring-0 focus:outline-none focus:ring-0"
                title="Required before submitting to Accounting"
                onchange="
                    const opt = this.options[this.selectedIndex];
                    const warn = this.parentElement.querySelector('[data-supplier-blacklist-warn]');
                    if (!warn) return;
                    const flagged = opt && opt.dataset.blacklisted === '1';
                    warn.classList.toggle('hidden', !flagged);
                    warn.querySelector('[data-reason]').textContent = flagged ? (opt.dataset.reason || 'This supplier is marked as not recommended.') : '';
                "
            >
                <option value="">Select Supplier</option>
                @foreach($suppliers as $supplier)
                    @php
                        $label = $supplier->supplier_store_type === 'Physical Store'
                            ? $supplier->company_name
                            : $supplier->shop_name;
                        $flagged = (int) ($supplier->supplier_is_blacklisted ?? 0) === 1;
                    @endphp
                    <option
                        value="{{ $supplier->supplier_id }}"
                        data-blacklisted="{{ $flagged ? '1' : '0' }}"
                        data-reason="{{ e($supplier->supplier_blacklist_reason ?? '') }}"
                        {{ (string) $supplierId === (string) $supplier->supplier_id ? 'selected' : '' }}
                    >
                        {{ $label }}{{ $flagged ? ' (Blacklisted)' : '' }}
                    </option>
                @endforeach
            </select>
            <div
                data-supplier-blacklist-warn
                class="mt-2 rounded border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900 {{ $selectedIsBlacklisted ? '' : 'hidden' }}"
            >
                <strong>Warning:</strong>
                <span data-reason>{{ $selectedIsBlacklisted ? ($selectedSupplier->supplier_blacklist_reason ?: 'This supplier is marked as not recommended.') : '' }}</span>
            </div>
        @elseif($isBlank)
            ________________________________________________
        @else
            {{ $supplierLabel }}
        @endif
    </div>

    <p class="mt-6">
        Please deliver to bearer the following items chargeable to our account{{ $editable ? ':' : '.' }}
    </p>

    {{-- ITEMS TABLE --}}
    @if($editable)
    <div data-doc-rows data-doc-rows-name="items" data-doc-rows-min="1" data-doc-rows-max="{{ $atpMaxRows }}" data-doc-rows-default="{{ $atpDefaultRows }}" class="mt-5">
    @endif
    <table class="{{ $editable ? '' : 'mt-5' }} w-full table-fixed border-collapse border border-black text-sm">
        <colgroup>
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 52%">
            <col style="width: 14%">
            <col style="width: 14%">
        </colgroup>
        <thead>
            <tr>
                <th class="border border-black p-2">Quantity @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="border border-black p-2">Unit @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="border border-black p-2 text-left">Description @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="border border-black p-2">Unit Price @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="border border-black p-2">Amount</th>
            </tr>
        </thead>
        <tbody @if($editable) data-doc-rows-body @endif>
            @if($editable)
                @for($i = 0; $i < $rowCount; $i++)
                    @php
                        $row = is_array($oldItems) ? ($oldItems[$i] ?? null) : null;
                        $item = $items[$i] ?? null;
                        $qty = is_array($row) ? ($row['quantity'] ?? '') : ($item->atp_quantity ?? '');
                        $unit = is_array($row) ? ($row['unit'] ?? '') : ($item->atp_unit ?? '');
                        $desc = is_array($row) ? ($row['description'] ?? '') : ($item->atp_description ?? '');
                        $price = is_array($row) ? ($row['unit_price'] ?? '') : ($item->atp_unit_price ?? '');
                        $amount = is_array($row)
                            ? ($row['amount_display'] ?? '')
                            : ($item ? number_format((float) $item->atp_amount, 2) : '');
                    @endphp
                    @include('partials.authority-to-purchase-item-row', ['i' => $i, 'qty' => $qty, 'unit' => $unit, 'desc' => $desc, 'price' => $price, 'amount' => $amount])
                @endfor
            @elseif($isBlank)
                @for($i = 0; $i < 8; $i++)
                    <tr>
                        <td class="border border-black h-8">&nbsp;</td>
                        <td class="border border-black">&nbsp;</td>
                        <td class="border border-black">&nbsp;</td>
                        <td class="border border-black">&nbsp;</td>
                        <td class="border border-black">&nbsp;</td>
                    </tr>
                @endfor
                <tr>
                    <td colspan="3" class="border border-black">&nbsp;</td>
                    <td class="border border-black px-2 text-right font-bold">TOTAL</td>
                    <td class="border border-black px-2 text-right font-bold">&nbsp;</td>
                </tr>
            @else
                @for($i = 0; $i < $rowCount; $i++)
                    @php $item = $items[$i] ?? null; @endphp
                    @if($item)
                        <tr>
                            <td class="border border-black text-center">{{ $item->atp_quantity }}</td>
                            <td class="border border-black text-center">{{ $item->atp_unit }}</td>
                            <td class="border border-black px-2">{{ $item->atp_description }}</td>
                            <td class="border border-black px-2 text-right">{{ number_format($item->atp_unit_price, 2) }}</td>
                            <td class="border border-black px-2 text-right">{{ number_format($item->atp_amount, 2) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="border border-black h-8">&nbsp;</td>
                            <td class="border border-black">&nbsp;</td>
                            <td class="border border-black">&nbsp;</td>
                            <td class="border border-black">&nbsp;</td>
                            <td class="border border-black">&nbsp;</td>
                        </tr>
                    @endif
                @endfor

                <tr>
                    <td colspan="3" class="border border-black">&nbsp;</td>
                    <td class="border border-black px-2 text-right font-bold">TOTAL</td>
                    <td class="border border-black px-2 text-right font-bold">
                        {{ $items->isNotEmpty() ? number_format($atpTotal, 2) : '' }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    @if($editable)
        <template data-doc-rows-template>
            @include('partials.authority-to-purchase-item-row', ['i' => '__INDEX__', 'qty' => '', 'unit' => '', 'desc' => '', 'price' => '', 'amount' => ''])
        </template>
        @include('partials.doc-rows-editor', ['count' => $rowCount, 'max' => $atpMaxRows, 'min' => 1])
    </div>
    @endif

    {{-- BOTTOM SIGNATURES --}}
    <div class="{{ $editable ? 'mt-8' : 'mt-10' }} grid grid-cols-2 items-start gap-10">
        <div class="w-full max-w-sm">
            <div class="font-semibold leading-6">RECEIVED BY: @if($editable)<span class="text-red-500">*</span>@endif</div>

            @if($editable)
                <div class="relative mt-6 w-full">
                    <span class="signature-name-stack w-full">
                        <img
                            id="purSigOverlay-{{ $signKey }}"
                            alt=""
                            class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                            style="display:none;"
                        >
                        <input
                            type="text"
                            name="authority_purchase_received_by_name"
                            id="purSigName-{{ $signKey }}"
                            value="{{ $receivedBy }}"
                            maxlength="255"
                            autocomplete="off"
                            class="relative z-[1] w-full min-h-[1.5rem] border-0 border-b border-black bg-transparent pb-1 text-center text-sm outline-none ring-0 focus:outline-none focus:ring-0"
                        >
                    </span>
                    <input
                        type="hidden"
                        name="authority_purchase_received_by_signature"
                        id="purSigImage-{{ $signKey }}"
                        value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $receivedBySignature) ? $receivedBySignature : '' }}"
                    >
                </div>

                <div class="mt-6 flex items-end gap-2">
                    <span class="shrink-0 pb-1 text-xs whitespace-nowrap">Reference P.O. No.</span>
                    <input
                        type="text"
                        name="authority_purchase_reference_po_no"
                        value="{{ $poNo }}"
                        class="min-w-0 flex-1 border-0 border-b border-black bg-transparent pb-1 outline-none ring-0 focus:outline-none focus:ring-0"
                    >
                </div>
            @else
                <div class="relative mt-6 flex min-h-[1.5rem] items-center justify-center border-b border-black pb-1 text-center">
                    @include('partials.drawn-signature', [
                        'value' => $receivedBySignature,
                        'printedName' => $receivedBy,
                        'empty' => $receivedBy,
                    ])
                </div>

                <div class="mt-6 flex items-end gap-2">
                    <span class="shrink-0 pb-1 text-xs whitespace-nowrap">Reference P.O. No.</span>
                    <div class="min-w-0 flex-1 border-b border-black pb-1 text-sm">
                        {{ $poNo }}
                    </div>
                </div>
            @endif
        </div>

        <div class="w-full max-w-xs justify-self-end text-left">
            <div class="leading-6">Authorized by</div>
            <div
                class="relative mt-6 flex min-h-[1.5rem] w-full items-center justify-center border-b border-black pb-1"
                @if(!empty($accLiveSign)) id="accPaperSigTarget" @endif
            >
                @if(!empty($accLiveSign) && !\App\Support\RisWorkflow::isDrawnSignature((string) ($atp?->authority_purchase_authorized_by_signature ?? '')))
                    <span class="signature-name-stack w-full">
                        <img
                            id="accPaperSigOverlay"
                            alt=""
                            class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                            style="display:none;"
                        >
                        <input
                            type="text"
                            id="accPaperSigPrintedName"
                            value="{{ \App\Support\AccountingSigner::currentUserName() ?: 'Accountant' }}"
                            maxlength="120"
                            autocomplete="off"
                            aria-label="Authorized by printed name"
                            class="ris-signature-input relative z-[1] w-full border-0 bg-transparent px-1 text-center text-xs font-normal not-italic leading-5 text-slate-900 outline-none ring-0 focus:outline-none focus:ring-0"
                        >
                    </span>
                @elseif(!$editable)
                    @include('partials.drawn-signature', [
                        'value' => $atp?->authority_purchase_authorized_by_signature ?? '',
                        'printedName' => \App\Support\AccountingSigner::forAtp($atp),
                    ])
                @endif
            </div>
            <div class="mt-1 text-center text-[11px] italic">({{ \App\Support\AccountingSigner::SIGNATURE_TITLE }})</div>
        </div>
    </div>
</div>
