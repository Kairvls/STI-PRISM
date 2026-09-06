@php
    $editable = $editable ?? false;
    $atp = $atp ?? null;
    $items = collect($items ?? [])->values();
    $suppliers = $suppliers ?? collect();
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

    $supplierLabel = '';
    if ($atp) {
        $supplierLabel = $atp->supplier_store_type === 'Physical Store'
            ? ($atp->company_name ?? '')
            : ($atp->shop_name ?? '');
    }

    $rowCount = $editable
        ? max(8, $items->count() + ($atp ? 1 : 0))
        : max(8, $items->count());
@endphp

<div
    @if($printId) id="{{ $printId }}" @endif
    class="atp-print-sheet mx-auto w-[210mm] max-w-full bg-white px-10 pb-6 pt-10 text-[13px] leading-tight text-black shadow {{ $printClass }}"
>
    {{-- HEADER --}}
    <div class="relative text-center">
        <div class="text-lg font-bold">STI COLLEGE ORMOC, INC.</div>
        <div class="text-xs">Centrum Mall, Aviles Street, Ormoc City</div>
        <div class="mt-4 text-xl font-bold tracking-wide">AUTHORITY TO PURCHASE</div>

        <div class="absolute right-0 top-0 text-left text-sm">
            <div>
                <strong>No.</strong>
                @if($editable && !$atp)
                    <input
                        type="text"
                        readonly
                        placeholder="Auto Generated"
                        class="w-40 border-0 border-b border-black bg-transparent text-center"
                    >
                @elseif($isBlank)
                    ______________
                @else
                    {{ $atp->authority_purchase_form_number ?? '' }}
                @endif
            </div>

            <div class="mt-2">
                <strong>Date</strong>
                @if($editable)
                    <input
                        type="date"
                        name="authority_purchase_date"
                        value="{{ $dateValue }}"
                        class="border-0 border-b border-black bg-transparent"
                    >
                @elseif($isBlank)
                    ______________
                @else
                    {{ $dateValue ? \Carbon\Carbon::parse($dateValue)->format('d/m/Y') : '—' }}
                @endif
            </div>
        </div>
    </div>

    {{-- SUPPLIER --}}
    <div class="mt-10">
        <strong>To:</strong>
        @if($editable)
            @php
                $selectedSupplier = collect($suppliers ?? [])->firstWhere('supplier_id', (int) $supplierId);
                $selectedIsBlacklisted = $selectedSupplier && (int) ($selectedSupplier->supplier_is_blacklisted ?? 0) === 1;
            @endphp
            <select
                name="authority_purchase_supplier_id"
                class="ml-2 w-[420px] border-0 border-b border-black bg-transparent"
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
    <table class="mt-5 w-full border-collapse border border-black text-sm">
        <thead>
            <tr>
                <th class="border border-black p-2">Quantity</th>
                <th class="border border-black p-2">Supplier Stock</th>
                <th class="border border-black p-2">Unit</th>
                <th class="border border-black p-2">Description</th>
                <th class="border border-black p-2">Unit Price</th>
                <th class="border border-black p-2">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if($editable)
                @for($i = 0; $i < $rowCount; $i++)
                    @php
                        $row = is_array($oldItems) ? ($oldItems[$i] ?? null) : null;
                        $item = $items[$i] ?? null;
                        $qty = is_array($row) ? ($row['quantity'] ?? '') : ($item->atp_quantity ?? '');
                        $stock = is_array($row) ? ($row['supplier_stock'] ?? '') : ($item->atp_supplier_stock ?? '');
                        $unit = is_array($row) ? ($row['unit'] ?? '') : ($item->atp_unit ?? '');
                        $desc = is_array($row) ? ($row['description'] ?? '') : ($item->atp_description ?? '');
                        $price = is_array($row) ? ($row['unit_price'] ?? '') : ($item->atp_unit_price ?? '');
                        $amount = is_array($row)
                            ? ($row['amount_display'] ?? '')
                            : ($item ? number_format((float) $item->atp_amount, 2) : '');
                    @endphp
                    <tr>
                        <td class="border border-black p-1">
                            <input type="number" name="items[{{ $i }}][quantity]" value="{{ $qty }}" min="1" class="w-full border-0 text-center">
                        </td>
                        <td class="border border-black p-1">
                            <input type="number" name="items[{{ $i }}][supplier_stock]" value="{{ $stock }}" min="0" class="w-full border-0 text-center" title="Available stock at supplier (manual entry)">
                        </td>
                        <td class="border border-black p-1">
                            <input type="text" name="items[{{ $i }}][unit]" value="{{ $unit }}" class="w-full border-0 text-center">
                        </td>
                        <td class="border border-black p-1">
                            <input type="text" name="items[{{ $i }}][description]" value="{{ $desc }}" class="w-full border-0">
                        </td>
                        <td class="border border-black p-1">
                            <input type="number" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ $price }}" min="0" class="w-full border-0 text-right">
                        </td>
                        <td class="border border-black p-1">
                            <input
                                readonly
                                name="items[{{ $i }}][amount_display]"
                                value="{{ $amount }}"
                                class="w-full border-0 bg-transparent text-right"
                                placeholder="0.00"
                            >
                        </td>
                    </tr>
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
                    <td colspan="4" class="border border-black">&nbsp;</td>
                    <td class="border border-black px-2 text-right font-bold">TOTAL</td>
                    <td class="border border-black px-2 text-right font-bold">&nbsp;</td>
                </tr>
            @else
                @for($i = 0; $i < $rowCount; $i++)
                    @php $item = $items[$i] ?? null; @endphp
                    @if($item)
                        <tr>
                            <td class="border border-black text-center">{{ $item->atp_quantity }}</td>
                            <td class="border border-black text-center">
                                {{ $item->atp_supplier_stock ?? '—' }}
                                @if((int) ($item->atp_back_order_qty ?? 0) > 0)
                                    <span class="block text-[10px] font-semibold text-amber-700">Back order: {{ $item->atp_back_order_qty }}</span>
                                @endif
                            </td>
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
                            <td class="border border-black">&nbsp;</td>
                        </tr>
                    @endif
                @endfor

                <tr>
                    <td colspan="4" class="border border-black">&nbsp;</td>
                    <td class="border border-black px-2 text-right font-bold">TOTAL</td>
                    <td class="border border-black px-2 text-right font-bold">
                        {{ $items->isNotEmpty() ? number_format($atpTotal, 2) : '' }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- BOTTOM SIGNATURES --}}
    <div class="{{ $editable ? 'mt-8' : 'mt-10' }} grid grid-cols-2 items-start gap-10">
        <div class="w-full max-w-sm">
            <div class="font-semibold leading-6">RECEIVED BY:</div>

            @if($editable)
                <div
                    id="purSigPreview-{{ $signKey }}"
                    class="relative mt-6 flex min-h-[3rem] w-full items-end justify-center border-b border-black pb-1"
                    style="display:none;"
                ></div>
                <input
                    type="text"
                    name="authority_purchase_received_by_name"
                    id="purSigName-{{ $signKey }}"
                    value="{{ $receivedBy }}"
                    maxlength="255"
                    autocomplete="off"
                    class="mt-6 w-full min-h-[3rem] border-0 border-b border-black bg-transparent pb-1 text-center outline-none"
                >
                <input
                    type="hidden"
                    name="authority_purchase_received_by_signature"
                    id="purSigImage-{{ $signKey }}"
                    value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $receivedBySignature) ? $receivedBySignature : '' }}"
                >
                <div class="mt-1 text-center text-xs">Signature over Printed Name · use panel below</div>

                <div class="mt-6 flex items-end gap-2">
                    <span class="shrink-0 pb-1 text-xs whitespace-nowrap">Reference P.O. No.</span>
                    <input
                        type="text"
                        name="authority_purchase_reference_po_no"
                        value="{{ $poNo }}"
                        class="min-w-0 flex-1 border-0 border-b border-black bg-transparent pb-1 outline-none"
                    >
                </div>
            @else
                <div class="mt-6 flex min-h-[3rem] items-end justify-center border-b border-black pb-1 text-center">
                    @include('partials.drawn-signature', [
                        'value' => $receivedBySignature,
                        'printedName' => $receivedBy,
                        'empty' => $receivedBy,
                    ])
                </div>
                <div class="mt-1 text-center text-xs">Signature over Printed Name</div>

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
            <div class="mt-6 flex min-h-[3rem] w-full items-end justify-center border-b border-black pb-1">
                @unless($editable)
                    @include('partials.drawn-signature', [
                        'value' => $atp?->authority_purchase_authorized_by_signature ?? '',
                        'printedName' => \App\Support\AccountingSigner::forAtp($atp),
                    ])
                @endunless
            </div>
        </div>
    </div>
</div>
