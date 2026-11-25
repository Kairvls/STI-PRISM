{{-- Same physical RIS sheet the purchaser sees. --}}
@php
    $sheetItems = collect($risItems ?? ($ris->risItems ?? []))->values();
    $sheetRows = max(1, $sheetItems->filter(fn ($item) => filled($item?->ris_item_name_description ?? null))->count());
@endphp
@include('partials.ris-purchaser-sheet-styles')
<div @if(!empty($printId)) id="{{ $printId }}" @endif class="ris-original-form">
    <div class="ris-document-header">
        <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
        <div class="ris-document-title">REQUISITION SLIP</div>
        <div class="ris-number-area">
            <span class="ris-number-label">No.</span>
            <span class="ris-number-line ris-value-line">{{ \App\Support\RisWorkflow::formNumber($ris) ?: ' ' }}</span>
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
            @for ($row = 0; $row < $sheetRows; $row++)
                @php
                    $item = $sheetItems->get($row);
                    $name = trim((string) ($item?->ris_item_name_description ?? ''));
                    $nameKey = mb_strtolower($name);
                    $supplier = trim((string) ($item?->supplier_display_name ?? ''));
                    $supplierBase = trim(explode('·', $supplier)[0]);
                    $supplierKey = mb_strtolower($supplierBase !== '' ? $supplierBase : $supplier);
                    $prev = $row > 0 ? $sheetItems->get($row - 1) : null;
                    $prevNameKey = mb_strtolower(trim((string) ($prev?->ris_item_name_description ?? '')));
                    $prevSupRaw = trim((string) ($prev?->supplier_display_name ?? ''));
                    $prevSupBase = trim(explode('·', $prevSupRaw)[0]);
                    $prevSupplierKey = mb_strtolower($prevSupBase !== '' ? $prevSupBase : $prevSupRaw);
                    $sameItem = $nameKey !== '' && $nameKey === $prevNameKey;
                    $sameSupplier = $sameItem && $supplierKey !== '' && $supplierKey === $prevSupplierKey;
                    $itemSpan = 1;
                    $itemRequestedSum = 0;
                    $itemHasRequested = false;
                    $itemIssuedSum = 0;
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
                        for ($i = $row + 1; $i < $sheetItems->count(); $i++) {
                            $nextItem = $sheetItems->get($i);
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
                    $itemRequestedDisplay = $itemHasRequested ? $formatQty($itemRequestedSum) : ' ';
                    $itemIssuedDisplay = $itemHasIssued ? $formatQty($itemIssuedSum) : ' ';
                    $supplierSpan = 1;
                    if ($supplierKey !== '' && ! $sameSupplier) {
                        for ($i = $row + 1; $i < $sheetItems->count(); $i++) {
                            $next = $sheetItems->get($i);
                            $nextName = mb_strtolower(trim((string) ($next?->ris_item_name_description ?? '')));
                            $nextSupRaw = trim((string) ($next?->supplier_display_name ?? ''));
                            $nextSupBase = trim(explode('·', $nextSupRaw)[0]);
                            $nextSupplier = mb_strtolower($nextSupBase !== '' ? $nextSupBase : $nextSupRaw);
                            if ($nextName !== $nameKey || $nextSupplier !== $supplierKey) {
                                break;
                            }
                            $supplierSpan++;
                        }
                    }
                    $supplierDisplay = $supplierSpan > 1 && $supplierBase !== '' ? $supplierBase : $supplier;
                @endphp
                <tr>
                    @if (! $sameItem)
                        <td @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif>{{ $name !== '' ? $name : ' ' }}</td>
                    @endif
                    <td class="text-center">{{ $item ? (\App\Support\RisWorkflow::brandPaperLabel($item) ?: ' ') : ' ' }}</td>
                    <td class="text-center">{{ $item?->uom_name ?: ' ' }}</td>
                    @if (! $sameSupplier)
                        <td @if ($supplierSpan > 1) rowspan="{{ $supplierSpan }}" @endif>{{ $supplierDisplay !== '' ? $supplierDisplay : ' ' }}</td>
                    @endif
                    @if (! $sameItem)
                        <td @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif class="text-center">{{ $itemRequestedDisplay }}</td>
                        <td @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif class="text-center">{{ $itemIssuedDisplay }}</td>
                    @endif
                    <td class="text-right">{{ $item && $item->ris_unit_cost !== null ? \App\Support\Money::format($item->ris_unit_cost) : ' ' }}</td>
                    <td class="text-right">{{ $item ? \App\Support\Money::format(((float) ($item->ris_quantity_requested ?? 0)) * ((float) ($item->ris_unit_cost ?? 0))) : ' ' }}</td>
                </tr>
            @endfor
        </tbody>
        @php
            $risGrandTotal = 0.0;
            $risHasAnyAmount = false;
            foreach ($sheetItems as $ti) {
                if (filled($ti?->ris_item_name_description ?? null)) {
                    $lineVal = $ti->ris_total_amount !== null
                        ? (float) $ti->ris_total_amount
                        : (((float) ($ti->ris_quantity_requested ?? 0)) * ((float) ($ti->ris_unit_cost ?? 0)));
                    $risGrandTotal += $lineVal;
                    $risHasAnyAmount = true;
                }
            }
        @endphp
        <tfoot>
            <tr>
                <td colspan="7" class="ris-total-label">TOTAL AMOUNT</td>
                <td class="ris-total-value">{{ $risHasAnyAmount ? \App\Support\Money::format($risGrandTotal) : ' ' }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="ris-purpose-area">
        <div class="ris-purpose-lined-wrap">
            <div class="ris-purpose-label">PURPOSE</div>
            <div class="ris-purpose-input">{{ trim((string) ($ris->ris_purpose_description ?: '')) !== '' ? $ris->ris_purpose_description : ' ' }}</div>
        </div>
    </div>

    <div class="ris-signatures">
        <div class="ris-signature-column">
            <div class="ris-signature-label">Requested by:</div>
            @include('partials.ris-requested-by-signatory', [
                'ris' => $ris,
                'lineClass' => 'ris-signature-line ris-value-line',
            ])
            <div class="ris-date-label">Date:</div>
            <div class="ris-date-line ris-value-line">
                {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('m/d/Y') : ' ' }}
            </div>
        </div>
        @include('partials.ris-view-approval-signatories', ['ris' => $ris])
    </div>
</div>
