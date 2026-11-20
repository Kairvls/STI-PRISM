@php
    $itemId = (int) (is_array($row) ? ($row['item_id'] ?? 0) : ($row->receiving_report_item_id ?? 0));
    $isVerified = $itemId > 0 && in_array($itemId, $verifiedItemIds, true);
    if ($isVerified && is_array($row)) {
        $row = collect($rows)->firstWhere('receiving_report_item_id', $itemId) ?? $row;
    }
    $cellsEditable = $editable && !$isVerified;
    $qty = is_array($row) ? ($row['quantity'] ?? '') : ($row->receiving_report_item_quantity ?? '');
    $orderedQty = is_array($row) ? ($row['ordered_qty'] ?? '') : ($row->receiving_report_item_ordered_qty ?? '');
    $condition = is_array($row) ? ($row['condition'] ?? 'ok') : ($row->receiving_report_item_condition ?? 'ok');
    $conditionRemarks = is_array($row) ? ($row['condition_remarks'] ?? '') : ($row->receiving_report_item_condition_remarks ?? '');
    $unit = is_array($row) ? ($row['unit'] ?? '') : ($row->receiving_report_item_unit ?? '');
    $article = is_array($row) ? ($row['article'] ?? '') : ($row->receiving_report_item_article ?? '');
    $unitPrice = is_array($row) ? ($row['unit_price'] ?? '') : ($row->receiving_report_item_unit_price ?? '');
    $supplierId = is_array($row) ? ($row['supplier_id'] ?? '') : ($row->receiving_report_item_supplier_id ?? '');
    $supplierName = is_array($row) ? ($row['supplier_name'] ?? '') : ($row->receiving_report_item_supplier_name ?? '');
    $backOrderId = is_array($row) ? ($row['back_order_id'] ?? '') : ($row->receiving_report_item_back_order_id ?? '');
    $damagedQty = (int) (is_array($row) ? ($row['damaged_qty'] ?? 0) : ($row->receiving_report_item_damaged_qty ?? 0));
    $damageRemarks = is_array($row) ? '' : (string) ($row->receiving_report_item_damage_remarks ?? '');
    $replacement = $backOrderId !== '' && $backOrderId !== null ? ($replacementLabels[(int) $backOrderId] ?? null) : null;
    $orderedHasValue = $orderedQty !== '' && $orderedQty !== null;
    $orderedLocked = $editable && ($orderedHasValue || !$cellsEditable);
@endphp
<tr
    class="h-8 {{ $replacement ? 'bg-sky-50/60' : '' }}"
    data-rr-row
    @if($editable) data-doc-row @endif
    @if($editable && $isVerified) data-doc-row-locked title="Verified at second count — this row cannot be deleted" @endif
>
    <td
        class="border border-black {{ $orderedLocked ? 'bg-slate-50/80' : '' }}"
        @if($orderedLocked) title="Ordered qty is locked from the purchase authority" @endif
        data-rr-ordered-cell
    >
        @if($editable && $itemId > 0)
            <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $itemId }}">
        @endif
        @if($cellsEditable && !$orderedHasValue)
            <input type="number" {!! $rrQtyAttrs !!} name="items[{{ $i }}][ordered_qty]" value="" data-rr-ordered-input class="h-7 w-full border-0 bg-transparent text-center outline-none text-[11px]">
        @elseif($editable)
            <input type="hidden" name="items[{{ $i }}][ordered_qty]" value="{{ $orderedQty }}" data-rr-ordered-input>
            <span data-rr-ordered-display class="block text-center text-[11px] text-slate-700">{{ $orderedHasValue ? $orderedQty : '—' }}</span>
        @else
            {{ $orderedHasValue ? $orderedQty : '—' }}
        @endif
    </td>
    <td class="border border-black">
        @if($cellsEditable)
            <input type="number" {!! $rrQtyAttrs !!} name="items[{{ $i }}][quantity]" value="{{ $qty }}" class="h-7 w-full border-0 bg-transparent text-center outline-none text-[11px]">
        @else
            {{ $qty }}
        @endif
    </td>
    <td class="border border-black">
        @if($cellsEditable)
            <input type="number" {!! $rrQtyAttrs !!} name="items[{{ $i }}][damaged_qty]" value="{{ $damagedQty ?: '' }}" placeholder="0" title="Units received but damaged" class="h-7 w-full border-0 bg-transparent text-center outline-none text-[11px] text-red-700">
        @else
            <span class="{{ $damagedQty > 0 ? 'font-semibold text-red-700' : '' }}">{{ $damagedQty > 0 ? $damagedQty : ($article !== '' ? '0' : '') }}</span>
        @endif
    </td>
    <td class="border border-black">
        @if($cellsEditable)
            @include('partials.procurement-uom-select', [
                'unit' => $unit,
                'inputName' => 'items['.$i.'][unit]',
                'uomNames' => $uomNames,
                'pp' => $pp ?? null,
                'buttonClass' => 'flex h-7 w-full min-w-0 items-center justify-between gap-0.5 border-0 bg-transparent px-1 text-center text-[12px] outline-none focus:ring-0',
            ])
        @else
            {{ $unit }}
        @endif
    </td>
    <td class="border border-black text-left px-2">
        @if($cellsEditable)
            <input type="text" name="items[{{ $i }}][article]" value="{{ $article }}" maxlength="{{ \App\Support\InputLimits::NAME }}" class="h-7 w-full border-0 bg-transparent outline-none">
            <input type="hidden" name="items[{{ $i }}][equipment_id]" value="" data-rr-equipment-id>
            <input type="text" name="items[{{ $i }}][condition_remarks]" value="{{ $conditionRemarks }}" maxlength="{{ \App\Support\InputLimits::NOTES }}" placeholder="Remarks for missing / damaged units" class="mt-0.5 h-5 w-full border-0 bg-transparent text-[10px] text-amber-800 outline-none">
            @if($backOrderId !== '' && $backOrderId !== null)
                <input type="hidden" name="items[{{ $i }}][back_order_id]" value="{{ $backOrderId }}">
            @endif
        @else
            {{ $article }}
            @if($conditionRemarks)
                <span class="block text-[10px] text-amber-700">{{ $conditionRemarks }}</span>
            @endif
            @if($damagedQty > 0 && $damageRemarks !== '' && $damageRemarks !== $conditionRemarks)
                <span class="block text-[10px] text-red-700">{{ $damageRemarks }}</span>
            @endif
        @endif
        @if($replacement)
            <span class="block text-[10px] font-semibold {{ $replacement['new'] ? 'text-violet-700' : 'text-sky-700' }}">↳ {{ $replacement['label'] }}</span>
        @endif
        @if($editable && $isVerified)
            <span class="block text-[10px] font-medium text-emerald-700">Verified at second count · locked</span>
        @endif
    </td>
    @php
        $missingQty = ($orderedQty !== '' && $orderedQty !== null && $qty !== '' && $qty !== null)
            ? max(0, (int) $orderedQty - (int) $qty)
            : 0;
        $isMissing = $condition === 'short' || $missingQty > 0;
        $conditionParts = [];
        if ($condition === 'bad_order' && $damagedQty === 0) {
            $conditionParts[] = 'Bad Order';
        }
        if ($isMissing) {
            $conditionParts[] = $missingQty > 0 ? 'Missing '.$missingQty : 'Missing';
        }
        if ($damagedQty > 0) {
            $conditionParts[] = 'Damaged '.$damagedQty;
        }
        $hasRowData = $article !== '' || ($qty !== '' && $qty !== null);
        $conditionLabel = $conditionParts !== [] ? implode(' · ', $conditionParts) : ($hasRowData ? 'OK' : '');
    @endphp
    <td class="border border-black px-1">
        @if($cellsEditable)
            <input type="hidden" name="items[{{ $i }}][condition]" value="{{ $condition }}" data-rr-condition-input data-rr-condition-original="{{ $condition }}">
            <span
                data-rr-condition-label
                class="text-[10px] {{ $conditionLabel === 'OK' || $conditionLabel === '' ? 'text-slate-500' : 'font-semibold text-amber-700' }}"
                title="Worked out from Ordered, Received and Damaged"
            >{{ $conditionLabel !== '' ? $conditionLabel : '—' }}</span>
        @else
            <span class="text-[10px] {{ $conditionLabel === 'OK' || $conditionLabel === '' ? '' : 'font-semibold text-amber-700' }}">{{ $conditionLabel }}</span>
        @endif
    </td>
    @if($allowMultiSupplier && $editable && !$cellsEditable)
        <td class="border border-black text-xs">{{ $supplierName ?: '—' }}</td>
        <td class="border border-black text-xs">{{ $unitPrice !== '' && $unitPrice !== null ? number_format((float) $unitPrice, 2) : '—' }}</td>
    @elseif($allowMultiSupplier && $editable)
        <td class="border border-black px-1">
            <select name="items[{{ $i }}][supplier_id]" class="h-7 w-full border-0 bg-transparent text-xs outline-none" onchange="const o=this.options[this.selectedIndex]; const n=this.parentElement.querySelector('[name$=\'[supplier_name]\']'); if(n) n.value=o.dataset.name||'';">
                <option value="">—</option>
                @foreach($suppliers as $supplier)
                    @php
                        $sName = $supplier->supplier_store_type === 'Physical Store'
                            ? ($supplier->company_name ?? '')
                            : ($supplier->shop_name ?? '');
                    @endphp
                    <option value="{{ $supplier->supplier_id }}" data-name="{{ $sName }}" {{ (string) $supplierId === (string) $supplier->supplier_id ? 'selected' : '' }}>{{ $sName }}</option>
                @endforeach
            </select>
            <input type="hidden" name="items[{{ $i }}][supplier_name]" value="{{ $supplierName }}">
        </td>
        <td class="border border-black">
            <input type="number" step="0.01" min="0" max="{{ \App\Support\InputLimits::COST }}" inputmode="decimal" data-money name="items[{{ $i }}][unit_price]" value="{{ $unitPrice }}" class="h-7 w-full border-0 bg-transparent text-center outline-none text-[11px]">
        </td>
    @elseif($allowMultiSupplier && !$editable)
        <td class="border border-black text-xs">{{ $supplierName ?: '—' }}</td>
        <td class="border border-black text-xs">{{ $unitPrice !== '' && $unitPrice !== null ? number_format((float) $unitPrice, 2) : '—' }}</td>
    @endif
</tr>
