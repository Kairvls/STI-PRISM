{{-- Same physical RIS sheet the purchaser sees. --}}
@php
    $sheetItems = collect($risItems ?? ($ris->risItems ?? []))->values();
    $sheetRows = max(8, $sheetItems->filter(fn ($item) => filled($item?->ris_item_name_description ?? null))->count());
@endphp
@include('partials.ris-purchaser-sheet-styles')
<div @if(!empty($printId)) id="{{ $printId }}" @endif class="ris-original-form">
    <div class="ris-document-header">
        <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
        <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
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
                    $supplierKey = mb_strtolower($supplier);
                    $prev = $row > 0 ? $sheetItems->get($row - 1) : null;
                    $prevNameKey = mb_strtolower(trim((string) ($prev?->ris_item_name_description ?? '')));
                    $prevSupplierKey = mb_strtolower(trim((string) ($prev?->supplier_display_name ?? '')));
                    $sameItem = $nameKey !== '' && $nameKey === $prevNameKey;
                    $sameSupplier = $sameItem && $supplierKey !== '' && $supplierKey === $prevSupplierKey;
                    $itemSpan = 1;
                    if ($nameKey !== '' && ! $sameItem) {
                        for ($i = $row + 1; $i < $sheetItems->count(); $i++) {
                            $nextName = mb_strtolower(trim((string) ($sheetItems->get($i)?->ris_item_name_description ?? '')));
                            if ($nextName === '' || $nextName !== $nameKey) {
                                break;
                            }
                            $itemSpan++;
                        }
                    }
                    $supplierSpan = 1;
                    if ($supplierKey !== '' && ! $sameSupplier) {
                        for ($i = $row + 1; $i < $sheetItems->count(); $i++) {
                            $next = $sheetItems->get($i);
                            $nextName = mb_strtolower(trim((string) ($next?->ris_item_name_description ?? '')));
                            $nextSupplier = mb_strtolower(trim((string) ($next?->supplier_display_name ?? '')));
                            if ($nextName !== $nameKey || $nextSupplier !== $supplierKey) {
                                break;
                            }
                            $supplierSpan++;
                        }
                    }
                @endphp
                <tr>
                    @if (! $sameItem)
                        <td @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif>{{ $name !== '' ? $name : ' ' }}</td>
                    @endif
                    <td class="text-center">{{ $item ? (\App\Support\RisWorkflow::brandPaperLabel($item) ?: ' ') : ' ' }}</td>
                    <td class="text-center">{{ $item?->uom_name ?: ' ' }}</td>
                    @if (! $sameSupplier)
                        <td @if ($supplierSpan > 1) rowspan="{{ $supplierSpan }}" @endif>{{ $supplier !== '' ? $supplier : ' ' }}</td>
                    @endif
                    <td class="text-center">{{ $item?->ris_quantity_requested ?? ' ' }}</td>
                    <td class="text-center">{{ $item?->ris_quantity_issued ?? ' ' }}</td>
                    <td class="text-right">{{ $item && $item->ris_unit_cost !== null ? \App\Support\Money::format($item->ris_unit_cost) : ' ' }}</td>
                    <td class="text-right">{{ $item ? \App\Support\Money::format(((float) ($item->ris_quantity_requested ?? 0)) * ((float) ($item->ris_unit_cost ?? 0))) : ' ' }}</td>
                </tr>
            @endfor
        </tbody>
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
