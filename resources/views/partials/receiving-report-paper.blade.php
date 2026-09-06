@php
    $editable = $editable ?? false;
    $allowMultiSupplier = $allowMultiSupplier ?? false;
    $suppliers = $suppliers ?? collect();
    $signSecondCount = $signSecondCount ?? false;
    $rr = $rr ?? null;
    $rows = $rows ?? collect();
    $dateValue = old('receiving_report_date', $rr?->receiving_report_date ?? '');
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
    $formNo = $rr?->receiving_report_form_number ?? '';
    $oldItems = old('items');
@endphp

<div
    @if(!empty($printId)) id="{{ $printId }}" @endif
    class="rr-print-sheet mx-auto w-[210mm] max-w-full bg-white px-10 pb-5 pt-8 text-[13px] text-black shadow {{ $printClass ?? '' }}"
    style="min-height: 0; height: auto;"
>
    <div class="relative">
        <div class="absolute left-0 top-0 text-sm font-semibold text-red-700">
            № {{ $formNo ?: '______' }}
        </div>
        <div class="text-center">
            <div class="text-lg font-bold">STI-College - ORMOC, INC.</div>
            <div class="text-sm">Ormoc City</div>
            <div class="mt-2 text-xl font-bold underline">RECEIVING REPORT</div>
        </div>
        <div class="absolute right-0 top-0 flex items-end gap-1 text-sm">
            <span>Date:</span>
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
                <span class="shrink-0">Received from:</span>
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
                <th class="w-24 border border-black py-1 font-semibold">QUANTITY</th>
                <th class="w-24 border border-black py-1 font-semibold">UNIT</th>
                <th class="border border-black py-1 font-semibold">ARTICLE</th>
                @if($allowMultiSupplier && $editable)
                    <th class="w-40 border border-black py-1 font-semibold">SUPPLIER</th>
                    <th class="w-24 border border-black py-1 font-semibold">UNIT PRICE</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < 9; $i++)
                @php
                    $row = $oldItems[$i] ?? $rows[$i] ?? null;
                    $qty = is_array($row) ? ($row['quantity'] ?? '') : ($row->receiving_report_item_quantity ?? '');
                    $unit = is_array($row) ? ($row['unit'] ?? '') : ($row->receiving_report_item_unit ?? '');
                    $article = is_array($row) ? ($row['article'] ?? '') : ($row->receiving_report_item_article ?? '');
                    $unitPrice = is_array($row) ? ($row['unit_price'] ?? '') : ($row->receiving_report_item_unit_price ?? '');
                    $supplierId = is_array($row) ? ($row['supplier_id'] ?? '') : ($row->receiving_report_item_supplier_id ?? '');
                    $supplierName = is_array($row) ? ($row['supplier_name'] ?? '') : ($row->receiving_report_item_supplier_name ?? '');
                @endphp
                <tr class="h-8">
                    <td class="border border-black">
                        @if($editable)
                            <input type="number" min="0" name="items[{{ $i }}][quantity]" value="{{ $qty }}" class="h-7 w-full border-0 bg-transparent text-center outline-none">
                        @else
                            {{ $qty }}
                        @endif
                    </td>
                    <td class="border border-black">
                        @if($editable)
                            <input type="text" name="items[{{ $i }}][unit]" value="{{ $unit }}" class="h-7 w-full border-0 bg-transparent text-center outline-none">
                        @else
                            {{ $unit }}
                        @endif
                    </td>
                    <td class="border border-black text-left px-2">
                        @if($editable)
                            <input type="text" name="items[{{ $i }}][article]" value="{{ $article }}" class="h-7 w-full border-0 bg-transparent outline-none">
                        @else
                            {{ $article }}
                        @endif
                    </td>
                    @if($allowMultiSupplier && $editable)
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
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $unitPrice }}" class="h-7 w-full border-0 bg-transparent text-center outline-none">
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
            <div class="font-semibold">Second Count:</div>
            @if($signSecondCount)
                <div
                    id="scSigPreview-{{ $signSuffix }}"
                    class="mt-6 flex min-h-[2.5rem] w-56 flex-col items-center justify-end gap-0.5 border-b border-black pb-1"
                    style="display:none;"
                ></div>
                <input
                    type="text"
                    name="second_count_by"
                    id="scName-{{ $signSuffix }}"
                    value="{{ old('second_count_by', $officerName) }}"
                    required
                    maxlength="255"
                    autocomplete="off"
                    class="mt-6 block w-56 border-0 border-b border-black bg-transparent text-center text-sm font-medium outline-none"
                    title="Receiving Officer name for Second Count"
                >
                <div class="mt-1 w-56 text-center text-[10px] font-semibold text-slate-500">Name is required · add signature above (draw / upload / saved)</div>
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
                <div class="font-semibold">Received by:</div>
                @if($editable)
                    <div
                        id="purSigPreview-{{ $signKey }}"
                        class="relative mt-6 flex min-h-[2.5rem] w-full items-end justify-center border-b border-black pb-1"
                        style="display:none;"
                    ></div>
                    <input
                        type="text"
                        name="receiving_report_received_by_name"
                        id="purSigName-{{ $signKey }}"
                        value="{{ $receivedByName }}"
                        maxlength="255"
                        autocomplete="off"
                        class="mt-10 block w-full border-0 border-b border-black bg-transparent text-center outline-none"
                    >
                    <input
                        type="hidden"
                        name="receiving_report_received_by_signature"
                        id="purSigImage-{{ $signKey }}"
                        value="{{ \App\Support\RisWorkflow::isDrawnSignature((string) $receivedBySignature) ? $receivedBySignature : '' }}"
                    >
                    <div class="mt-1 w-full text-center text-[10px] text-slate-500">Signature overlays printed name · use panel below</div>
                @else
                    <div class="mt-10 w-full border-b border-black pb-1 min-h-[1.5rem]">
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

    @media print {
        .rr-print-sheet {
            min-height: 0 !important;
            height: auto !important;
            box-shadow: none !important;
        }
    }
</style>
