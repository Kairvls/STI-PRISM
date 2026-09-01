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
    $receivedBy = old('authority_purchase_received_by_name', $atp?->authority_purchase_received_by_name ?? '');
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
    class="atp-print-sheet mx-auto w-[210mm] max-w-full bg-white p-10 text-[13px] leading-tight text-black shadow {{ $printClass }}"
    style="min-height: 297mm;"
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
                    {{ $dateValue ? \Carbon\Carbon::parse($dateValue)->format('F d, Y') : '—' }}
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
                    <td colspan="4" class="border border-black pr-4 text-right font-bold">TOTAL</td>
                    <td class="border border-black px-2 text-right font-bold">&nbsp;</td>
                </tr>
            @else
                @forelse($items as $item)
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
                @empty
                    <tr>
                        <td colspan="5" class="border border-black p-4 text-center text-gray-500">
                            No ATP line items added.
                        </td>
                    </tr>
                @endforelse

                @if($items->isNotEmpty())
                    <tr>
                        <td colspan="4" class="border border-black pr-4 text-right font-bold">TOTAL</td>
                        <td class="border border-black px-2 text-right font-bold">{{ number_format($atpTotal, 2) }}</td>
                    </tr>
                @endif
            @endif
        </tbody>
    </table>

    {{-- BOTTOM SIGNATURES --}}
    <div class="{{ $editable ? 'mt-10' : 'mt-14' }} flex justify-between">
        <div class="w-72">
            <div class="font-semibold">RECEIVED BY:</div>

            @if($editable)
                <input
                    type="text"
                    name="authority_purchase_received_by_name"
                    value="{{ $receivedBy }}"
                    class="mt-8 w-full border-0 border-b border-black"
                >
                <div class="text-center text-xs">Signature over Printed Name</div>

                <input
                    type="text"
                    name="authority_purchase_reference_po_no"
                    value="{{ $poNo }}"
                    class="mt-6 w-full border-0 border-b border-black"
                >
                <div class="text-center text-xs">Reference P.O. No.</div>
            @else
                <div class="mt-10 border-b border-black text-center min-h-[1.5rem]">
                    {{ $receivedBy }}
                </div>
                <div class="text-center text-xs">Signature over Printed Name</div>

                <div class="mt-8 border-b border-black text-center min-h-[1.5rem]">
                    {{ $poNo }}
                </div>
                <div class="text-center text-xs">Reference P.O. No.</div>
            @endif
        </div>

        <div class="w-56 text-center">
            <div>Authorized By</div>
            <div class="mt-16 border-b border-black min-h-[3rem]">
                @unless($editable)
                    @include('partials.drawn-signature', ['value' => $atp?->authority_purchase_authorized_by_signature ?? ''])
                @endunless
            </div>
        </div>
    </div>
</div>
