@php
    $editable = $editable ?? false;
    $allowMultiSupplier = $allowMultiSupplier ?? false;
    $suppliers = $suppliers ?? collect();
    $signSecondCount = $signSecondCount ?? false;
    $rr = $rr ?? null;
    $rows = $rows ?? collect();
    $dateValue = old(
        'receiving_report_date',
        $rr?->receiving_report_date ?? ($editable && !$rr ? now()->format('Y-m-d') : '')
    );
    $fromValue = old('receiving_report_received_from', $rr?->receiving_report_received_from ?? '');
    $addressValue = old('receiving_report_supplier_address_override', $rr?->receiving_report_supplier_address_override ?? '');
    $invoiceValue = old('receiving_report_invoice_no', $rr?->receiving_report_invoice_no ?? '');
    $drValue = old('receiving_report_dr_no', $rr?->receiving_report_dr_no ?? '');
    $deliveryValue = old('receiving_report_delivery_date', $rr?->receiving_report_delivery_date ?? '');
    $legacyReceivedSig = (string) ($rr?->receiving_report_received_by_signature ?? '');
    $storedReceivedName = trim((string) ($rr?->receiving_report_received_by_name ?? ''));
    if ($storedReceivedName === '' && !\App\Support\RisWorkflow::isDrawnSignature($legacyReceivedSig)) {
        $storedReceivedName = trim($legacyReceivedSig);
    }
    $receivedByNameDefault = $storedReceivedName;
    if ($editable && $receivedByNameDefault === '') {
        $receivedByNameDefault = (string) (auth()->user()->user_full_name ?? '');
    }
    $receivedByName = old('receiving_report_received_by_name', $receivedByNameDefault);
    $receivedBySignature = old(
        'receiving_report_received_by_signature',
        \App\Support\RisWorkflow::isDrawnSignature($legacyReceivedSig) ? $legacyReceivedSig : ''
    );
    $receivedBy = $receivedByName;
    $signKey = $signKey ?? ($rr?->receiving_report_id ? 'rr-'.$rr->receiving_report_id : 'rr-create');
    $secondCount = $rr?->receiving_report_second_count_signature ?? $rr?->receiving_report_second_count_by ?? '';
    $officerName = $officerName ?? (auth()->user()->user_full_name ?? 'Receiving Officer');
    $signSuffix = $signSuffix ?? (string) ($rr?->receiving_report_id ?? 'sc');
    $suggestedRrFormNumber = $suggestedRrFormNumber ?? \App\Support\RrFormNumber::next();
    if (old('receiving_report_form_number') !== null) {
        $formNo = (string) old('receiving_report_form_number');
    } elseif ($rr) {
        $existingNo = trim((string) ($rr->receiving_report_form_number ?? ''));
        $formNo = \App\Support\RrFormNumber::isValid($existingNo) ? $existingNo : '';
    } else {
        $formNo = '';
    }
    $oldItems = old('items');
    $verifiedItemIds = collect($rows)
        ->filter(fn ($row) => is_object($row) && !empty($row->receiving_report_item_verified))
        ->map(fn ($row) => (int) $row->receiving_report_item_id)
        ->all();
    $replacementLabels = \App\Support\BackOrders::replacementLabels($rows);
    $uomNames = $editable
        ? collect($uomNames ?? once(fn () => \Illuminate\Support\Facades\Schema::hasTable('uom_table')
            ? \Illuminate\Support\Facades\DB::table('uom_table')->orderBy('uom_name')->pluck('uom_name')->map(fn ($name) => (string) $name)->all()
            : []))->values()
        : collect();
    $rrQtyAttrs = 'min="0" max="9999999" step="1" inputmode="numeric"'
        .' onkeydown="if ([\'e\', \'E\', \'+\', \'-\', \'.\'].includes(event.key)) event.preventDefault();"'
        .' oninput="if (this.value.length > 7) this.value = this.value.replace(/\D/g, \'\').slice(0, 7);"';
@endphp

<div
    @if(!empty($printId)) id="{{ $printId }}" @endif
    class="rr-print-sheet mx-auto w-full max-w-[1095px] bg-white px-10 pb-5 pt-8 text-[13px] text-black shadow {{ $printClass ?? '' }}"
    style="min-height: 0; height: auto;"
>
    <div class="relative">
        <div class="absolute left-0 top-0 flex items-end gap-1 text-sm font-semibold text-red-700">
            <span>№</span>
            @if($editable)
                <input
                    type="text"
                    name="receiving_report_form_number"
                    value="{{ $formNo }}"
                    maxlength="17"
                    pattern="RR-\d{6}-\d{7}"
                    title="Assigned on submit (RR-YYYYMM-0000001)"
                    placeholder="{{ $suggestedRrFormNumber }}"
                    class="w-44 border-0 bg-transparent px-0 font-semibold text-red-700 outline-none"
                >
            @else
                <span>{{ $formNo ?: '______' }}</span>
            @endif
        </div>
        <div class="text-center">
            <div class="text-lg font-bold">STI-College - ORMOC, INC.</div>
            <div class="text-sm">Ormoc City</div>
            <div class="mt-2 text-xl font-bold underline">RECEIVING REPORT</div>
        </div>
        <div class="absolute right-0 top-0 flex items-end gap-1 text-sm">
            <span>Date: @if($editable)<span class="text-red-500">*</span>@endif</span>
            @if($editable)
                <input type="date" name="receiving_report_date" value="{{ $dateValue }}" class="h-7 w-36 border-0 border-b border-black bg-transparent px-1 outline-none">
            @else
                <span class="inline-block min-w-[8rem] border-b border-black px-1">{{ $dateValue ? \Carbon\Carbon::parse($dateValue)->format('d/m/Y') : '' }}</span>
            @endif
        </div>
    </div>

    <div class="mt-10 grid grid-cols-2 gap-8">
        <div class="space-y-3">
            <div class="flex items-end gap-2">
                <span class="shrink-0">Received from: @if($editable)<span class="text-red-500">*</span>@endif</span>
                @if($editable)
                    <input type="text" name="receiving_report_received_from" value="{{ $fromValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="flex-1 border-b border-black">{{ $fromValue }}</span>
                @endif
            </div>
            @php
                $addressRaw = trim((string) $addressValue);
                if (preg_match('/\R/', $addressRaw)) {
                    $addressParts = preg_split('/\R/', $addressRaw, 2);
                    $addressLine1 = trim((string) ($addressParts[0] ?? ''));
                    $addressLine2 = trim((string) ($addressParts[1] ?? ''));
                } elseif (mb_strlen($addressRaw) > 48) {
                    $cut = mb_strrpos(mb_substr($addressRaw, 0, 48), ' ');
                    $cut = $cut === false ? 48 : $cut;
                    $addressLine1 = trim(mb_substr($addressRaw, 0, $cut));
                    $addressLine2 = trim(mb_substr($addressRaw, $cut));
                } else {
                    $addressLine1 = $addressRaw;
                    $addressLine2 = '';
                }
            @endphp
            <div class="flex items-start gap-2">
                <span class="shrink-0 leading-[1.75rem]">Address:</span>
                <div class="min-w-0 flex-1">
                    @if($editable)
                        <textarea
                            name="receiving_report_supplier_address_override"
                            rows="2"
                            maxlength="2000"
                            class="rr-address-textarea w-full resize-none border-0 bg-transparent px-0 text-[13px] outline-none"
                        >{{ $addressValue }}</textarea>
                    @else
                        <div class="min-h-[1.75rem] border-b border-black pb-0.5 leading-[1.75rem]">{{ $addressLine1 }}</div>
                        <div class="mt-1 min-h-[1.75rem] border-b border-black pb-0.5 leading-[1.75rem]">{{ $addressLine2 }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex items-end gap-2">
                <span class="shrink-0">Refer Invoice No.:</span>
                @if($editable)
                    <input type="text" name="receiving_report_invoice_no" value="{{ $invoiceValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="flex-1 border-b border-black">{{ $invoiceValue }}</span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <span class="shrink-0">D.R. No.:</span>
                @if($editable)
                    <input type="text" name="receiving_report_dr_no" value="{{ $drValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="flex-1 border-b border-black">{{ $drValue }}</span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <span class="shrink-0">Date:</span>
                @if($editable)
                    <input type="date" name="receiving_report_delivery_date" value="{{ $deliveryValue }}" class="h-7 flex-1 border-0 border-b border-black bg-transparent outline-none">
                @else
                    <span class="flex-1 border-b border-black">{{ $deliveryValue ? \Carbon\Carbon::parse($deliveryValue)->format('d/m/Y') : '' }}</span>
                @endif
            </div>
        </div>
    </div>

    <p class="mt-6">Received the following items:</p>

    <table class="mt-2 w-full border-collapse border border-black text-center">
        <thead>
            <tr>
                <th class="w-16 border border-black py-1 font-semibold text-[10px]">ORDERED @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="w-16 border border-black py-1 font-semibold text-[10px]">RECEIVED @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="w-16 border border-black py-1 font-semibold text-[10px]">DAMAGED</th>
                <th class="w-20 border border-black py-1 font-semibold">UNIT @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="border border-black py-1 font-semibold">ARTICLE @if($editable)<span class="text-red-500">*</span>@endif</th>
                <th class="w-24 border border-black py-1 font-semibold text-[10px]">CONDITION</th>
                @if($allowMultiSupplier && $editable)
                    <th class="w-36 border border-black py-1 font-semibold">SUPPLIER</th>
                    <th class="w-20 border border-black py-1 font-semibold text-[10px]">UNIT PRICE</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < 9; $i++)
                @php
                    $row = $oldItems[$i] ?? $rows[$i] ?? null;
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
                <tr class="h-8 {{ $replacement ? 'bg-sky-50/60' : '' }}" data-rr-row>
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
                            <select name="items[{{ $i }}][supplier_id]" class="h-7 w-full border-0 bg-transparent text-xs outline-none" onchange="const o=this.options[this.selectedIndex]; const n=this.form.querySelector('[name=\'items[{{ $i }}][supplier_name]\']'); if(n) n.value=o.dataset.name||'';">
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
            @endfor
        </tbody>
    </table>

    <div class="mt-12 grid grid-cols-2 gap-16">
        <div class="text-left">
            <div class="font-semibold">Second Count: @if($signSecondCount)<span class="text-red-500">*</span>@endif</div>
            @if($signSecondCount)
                <div class="relative mt-6 w-56">
                    <span class="signature-name-stack w-full">
                        <img
                            id="scSigOverlay-{{ $signSuffix }}"
                            alt=""
                            class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-[10] max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                            style="display:none;"
                        >
                        <input
                            type="text"
                            name="second_count_by"
                            id="scName-{{ $signSuffix }}"
                            value="{{ old('second_count_by', $officerName) }}"
                            required
                            maxlength="255"
                            autocomplete="off"
                            class="relative z-[1] block w-full min-h-[1.5rem] border-0 border-b border-black bg-transparent pb-1 text-center text-sm font-medium outline-none"
                            title="Receiving Officer name for Second Count"
                        >
                    </span>
                </div>
                <div class="mt-3 text-xs font-semibold">Date:</div>
                <div class="mt-1 w-40 border-b border-black pb-0.5 text-center text-sm">
                    {{ now()->format('d/m/Y') }}
                </div>
            @else
                <div class="mt-10 w-56 border-b border-black pb-1 min-h-[1.5rem]">
                    @include('partials.drawn-signature', [
                        'value' => $secondCount,
                        'printedName' => \App\Support\RisWorkflow::isDrawnSignature((string) $secondCount)
                            ? trim((string) ($rr->receiving_report_second_count_by ?? $officerName ?? ''))
                            : '',
                    ])
                </div>
            @endif
        </div>
        <div class="flex justify-end">
            <div class="w-56 text-left">
                <div class="font-semibold">Received by: @if($editable)<span class="text-red-500">*</span>@endif</div>
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
                                name="receiving_report_received_by_name"
                                id="purSigName-{{ $signKey }}"
                                value="{{ $receivedByName }}"
                                maxlength="255"
                                autocomplete="off"
                                class="relative z-[1] block w-full min-h-[1.5rem] border-0 border-b border-black bg-transparent pb-1 text-center text-sm outline-none"
                            >
                        </span>
                        <input
                            type="hidden"
                            name="receiving_report_received_by_signature"
                            id="purSigImage-{{ $signKey }}"
                            value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $receivedBySignature) ? $receivedBySignature : '' }}"
                        >
                    </div>
                @else
                    <div class="relative mt-6 w-full border-b border-black pb-1 min-h-[1.5rem]">
                        @include('partials.drawn-signature', [
                            'value' => $receivedBySignature ?: $legacyReceivedSig,
                            'printedName' => $receivedByName,
                            'empty' => $receivedByName,
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .rr-print-sheet {
        min-height: 0 !important;
        height: auto !important;
    }

    .rr-address-textarea {
        line-height: 1.75rem;
        height: 3.5rem;
        overflow: hidden;
        background-image: repeating-linear-gradient(
            to bottom,
            transparent 0,
            transparent calc(1.75rem - 1px),
            #000 calc(1.75rem - 1px),
            #000 1.75rem
        );
        background-size: 100% 1.75rem;
        background-repeat: repeat-y;
        background-position: left top;
    }

    .rr-print-sheet input.rr-cell-invalid {
        background-color: #fef2f2;
        box-shadow: inset 0 0 0 1px #f87171;
    }

    @media print {
        .rr-print-sheet {
            min-height: 0 !important;
            height: auto !important;
            box-shadow: none !important;
        }
    }
</style>

@pushOnce('scripts')
<script>
(function () {
    function num(row, field) {
        var input = row.querySelector('input[name$="[' + field + ']"]');
        if (!input || String(input.value).trim() === '') return null;
        var value = parseInt(input.value, 10);
        return isNaN(value) ? null : Math.max(0, value);
    }

    function refreshRow(row) {
        var hidden = row.querySelector('[data-rr-condition-input]');
        var label = row.querySelector('[data-rr-condition-label]');
        if (!hidden || !label) return;

        var original = hidden.getAttribute('data-rr-condition-original') || 'ok';
        var ordered = num(row, 'ordered_qty');
        var received = num(row, 'quantity');
        var damaged = num(row, 'damaged_qty') || 0;
        var article = row.querySelector('input[name$="[article]"]');
        var hasData = received !== null || (article && article.value.trim() !== '');

        var missing = 0;
        var isMissing;
        if (ordered !== null && received !== null) {
            missing = Math.max(0, ordered - received);
            isMissing = missing > 0;
        } else {
            isMissing = original === 'short';
        }

        var parts = [];
        if (original === 'bad_order' && damaged === 0) parts.push('Bad Order');
        if (isMissing) parts.push(missing > 0 ? 'Missing ' + missing : 'Missing');
        if (damaged > 0) parts.push('Damaged ' + damaged);

        hidden.value = isMissing ? 'short' : (original === 'bad_order' ? 'bad_order' : 'ok');

        var problem = null;
        var problemField = null;
        var rowHasData = hasData || ordered !== null || damaged > 0;
        if (rowHasData) {
            if (ordered === null) {
                problem = 'Enter ordered';
                problemField = 'ordered_qty';
            } else if (received === null) {
                problem = 'Enter received';
                problemField = 'quantity';
            } else if (received > ordered) {
                problem = 'Over ordered by ' + (received - ordered);
                problemField = 'quantity';
            } else if (damaged > received) {
                problem = 'Damaged > received';
                problemField = 'damaged_qty';
            } else if (!article || article.value.trim() === '') {
                problem = 'Enter article';
                problemField = 'article';
            }
        }

        ['ordered_qty', 'quantity', 'damaged_qty', 'article'].forEach(function (field) {
            var input = row.querySelector('input[name$="[' + field + ']"]');
            if (input) input.classList.toggle('rr-cell-invalid', field === problemField);
        });

        var text = problem || (parts.length ? parts.join(' · ') : (hasData ? 'OK' : '—'));
        label.textContent = text;
        label.title = problem ? 'Fix this before submitting' : 'Worked out from Ordered, Received and Damaged';
        var flagged = parts.length > 0 && !problem;
        label.classList.toggle('font-semibold', flagged || !!problem);
        label.classList.toggle('text-amber-700', flagged);
        label.classList.toggle('text-red-600', !!problem);
        label.classList.toggle('text-slate-500', !flagged && !problem);
    }

    window.rrRefreshConditions = function (root) {
        (root || document).querySelectorAll('[data-rr-row]').forEach(refreshRow);
    };

    document.addEventListener('input', function (event) {
        var row = event.target.closest && event.target.closest('[data-rr-row]');
        if (row) refreshRow(row);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { window.rrRefreshConditions(); });
    } else {
        window.rrRefreshConditions();
    }
})();
</script>
@endPushOnce
