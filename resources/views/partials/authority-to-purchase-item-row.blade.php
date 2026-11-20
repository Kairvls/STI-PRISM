@php
    $i = $i ?? '__INDEX__';
    $qty = $qty ?? '';
    $unit = $unit ?? '';
    $desc = $desc ?? '';
    $price = $price ?? '';
    $amount = $amount ?? '';
@endphp
<tr data-doc-row @if(!empty($lockRow)) data-doc-row-locked @endif>
    <td class="border border-black p-1">
        <input
            type="number"
            name="items[{{ $i }}][quantity]"
            value="{{ $qty }}"
            min="1"
            max="9999999"
            step="1"
            inputmode="numeric"
            onkeydown="if (['e', 'E', '+', '-', '.'].includes(event.key)) event.preventDefault();"
            oninput="if (this.value.length > 7) this.value = this.value.replace(/\D/g, '').slice(0, 7);"
            @if(!empty($lockRow)) readonly @endif
            title="{{ !empty($lockRow) ? 'Taken from the RIS' : '' }}"
            class="{{ $atpCellClass }} text-center {{ !empty($lockRow) ? 'cursor-not-allowed !bg-gray-50 text-gray-600' : '' }}"
        >
    </td>
    <td class="border border-black p-1">
        @include('partials.procurement-uom-select', [
            'unit' => $unit,
            'inputName' => 'items['.$i.'][unit]',
            'lockRow' => ! empty($lockRow),
            'uomNames' => $uomNames,
            'pp' => $pp ?? null,
            'buttonClass' => 'flex w-full min-w-0 items-center justify-between gap-0.5 border-0 bg-transparent px-1 py-1.5 text-center text-sm outline-none focus:ring-0',
        ])
    </td>
    <td class="border border-black p-1">
        <input type="text" name="items[{{ $i }}][description]" value="{{ $desc }}" maxlength="{{ \App\Support\InputLimits::NOTES }}" @if(!empty($lockRow)) readonly @endif title="{{ !empty($lockRow) ? 'Taken from the RIS' : '' }}" class="{{ $atpCellClass }} {{ !empty($lockRow) ? 'cursor-not-allowed !bg-gray-50 text-gray-600' : '' }}">
    </td>
    <td class="border border-black p-1">
        <input
            type="number"
            step="0.01"
            name="items[{{ $i }}][unit_price]"
            value="{{ $price }}"
            min="0"
            max="{{ \App\Support\InputLimits::COST }}"
            inputmode="decimal"
            data-money
            onkeydown="if (['e', 'E', '+', '-'].includes(event.key)) event.preventDefault();"
            oninput="const [whole, decimals] = this.value.split('.'); if (whole.length > 7 || (decimals ?? '').length > 2) this.value = whole.slice(0, 7) + (decimals !== undefined ? '.' + decimals.slice(0, 2) : '');"
            @if(!empty($lockRow)) readonly @endif
            title="{{ !empty($lockRow) ? 'Taken from the RIS' : '' }}"
            class="{{ $atpCellClass }} text-right {{ !empty($lockRow) ? 'cursor-not-allowed !bg-gray-50 text-gray-600' : '' }}"
        >
    </td>
    <td class="border border-black p-1">
        <input
            readonly
            name="items[{{ $i }}][amount_display]"
            value="{{ $amount }}"
            class="{{ $atpCellClass }} cursor-not-allowed bg-gray-50 text-right text-gray-500"
            placeholder="0.00"
        >
    </td>
</tr>
