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
@endphp
<tr
    class="h-8 {{ $replacement ? 'bg-sky-50/60' : '' }}"
    data-rr-row
    @if($editable) data-doc-row @endif
    @if($editable && $isVerified) data-doc-row-locked title="Verified at second count — this row cannot be deleted" @endif
>
    <td class="border border-black">
        @if($editable && $itemId > 0)
            <input type="hidden" name="items[{{ $i }}][item_id]" value="{{ $itemId }}">
        @endif
        @if($cellsEditable)
            <input type="number" {!! $rrQtyAttrs !!} name="items[{{ $i }}][ordered_qty]" value="{{ $orderedQty }}" class="h-7 w-full border-0 bg-transparent text-center outline-none text-[11px]">
        @else
            {{ $orderedQty !== '' && $orderedQty !== null ? $orderedQty : '—' }}
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
            <div
                class="relative min-w-0"
                x-data="{
                    value: @js((string) $unit),
                    options: @js($uomNames->all()),
                    open: false,
                    query: '',
                    get filtered() {
                        const list = this.value && !this.options.includes(this.value)
                            ? [this.value, ...this.options]
                            : this.options;
                        const q = this.query.trim().toLowerCase();
                        return q ? list.filter((opt) => opt.toLowerCase().includes(q)) : list;
                    },
                    toggle() {
                        this.open = !this.open;
                        this.query = '';
                        if (this.open) this.$nextTick(() => this.$refs.search.focus({ preventScroll: true }));
                    },
                    pick(opt) {
                        this.value = opt;
                        this.open = false;
                        this.query = '';
                    }
                }"
                x-on:keydown.escape.stop="open = false"
            >
                <input
                    type="hidden"
                    name="items[{{ $i }}][unit]"
                    value="{{ $unit }}"
                    x-bind:value="value"
                    x-on:input="value = $event.target.value"
                >
                <button
                    type="button"
                    x-on:click.stop="toggle()"
                    class="flex h-7 w-full min-w-0 items-center justify-between gap-0.5 border-0 bg-transparent px-1 text-center text-[12px] outline-none focus:ring-0"
                    x-bind:aria-expanded="open.toString()"
                >
                    <span class="min-w-0 flex-1 truncate" x-bind:class="value ? '' : 'text-gray-400'" x-text="value || 'Unit'">{{ $unit }}</span>
                    <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-cloak
                    x-transition
                    x-on:click.outside="open = false"
                    class="absolute left-0 top-full z-[80] mt-0.5 w-max min-w-[7rem] max-w-[16rem] overflow-hidden rounded-lg border border-gray-200 bg-white text-left shadow-lg"
                >
                    <div class="border-b border-gray-100 p-1.5">
                        <input
                            type="text"
                            x-ref="search"
                            x-model="query"
                            x-on:click.stop
                            x-on:keydown.enter.prevent="filtered.length && pick(filtered[0])"
                            placeholder="Search..."
                            class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300 focus:bg-white"
                        >
                    </div>
                    <ul class="max-h-[10.5rem] overflow-y-auto py-1">
                        <li>
                            <button
                                type="button"
                                x-on:click="pick('')"
                                class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-500 hover:bg-gray-50"
                                x-bind:class="!value ? 'bg-slate-50 font-medium text-slate-800' : ''"
                            >
                                Select unit
                            </button>
                        </li>
                        <template x-for="opt in filtered" :key="opt">
                            <li>
                                <button
                                    type="button"
                                    x-on:click="pick(opt)"
                                    class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                                    x-bind:class="value === opt ? 'bg-slate-50 font-medium' : ''"
                                    x-text="opt"
                                ></button>
                            </li>
                        </template>
                        <li x-show="filtered.length === 0" class="px-2.5 py-2 text-[11px] text-gray-400">
                            No matches
                        </li>
                    </ul>
                </div>
            </div>
        @else
            {{ $unit }}
        @endif
    </td>
    <td class="border border-black text-left px-2">
        @if($cellsEditable)
            <input type="text" name="items[{{ $i }}][article]" value="{{ $article }}" class="h-7 w-full border-0 bg-transparent outline-none">
            <input type="text" name="items[{{ $i }}][condition_remarks]" value="{{ $conditionRemarks }}" placeholder="Remarks for missing / damaged units" class="mt-0.5 h-5 w-full border-0 bg-transparent text-[10px] text-amber-800 outline-none">
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
            <input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $unitPrice }}" class="h-7 w-full border-0 bg-transparent text-center outline-none text-[11px]">
        </td>
    @elseif($allowMultiSupplier && !$editable)
        <td class="border border-black text-xs">{{ $supplierName ?: '—' }}</td>
        <td class="border border-black text-xs">{{ $unitPrice !== '' && $unitPrice !== null ? number_format((float) $unitPrice, 2) : '—' }}</td>
    @endif
</tr>
