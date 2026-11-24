<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authority to Purchase &amp; RIS — {{ \App\Support\CanvassWorkflow::displayNumber($canvass) }}</title>
    @include('partials.ris-purchaser-sheet-styles')
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 24px; background: #f8fafc; }
        .no-print { margin-bottom: 18px; display: flex; justify-content: flex-end; gap: 8px; }
        .btn { padding: 8px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer; font-weight: 600; font-size: 13px; }
        .btn-primary { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .sheet { background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 28px; margin-bottom: 24px; page-break-after: always; }
        .sheet:last-of-type { page-break-after: auto; }
        .hdr { text-align: center; }
        .hdr h1 { margin: 0; font-size: 17px; }
        .hdr h2 { margin: 4px 0 0; font-size: 12px; color: #475569; letter-spacing: .04em; text-transform: uppercase; }
        .hdr .sup { margin-top: 6px; font-size: 13px; font-weight: 700; }
        .meta { display: flex; justify-content: space-between; margin-top: 16px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 9px; text-align: left; }
        th { background: #f1f5f9; font-weight: 700; }
        .sigs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; margin-top: 32px; font-size: 12px; }
        .sig-line { height: 50px; border-bottom: 1px solid #334155; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 4px; }
        .sig-line img { max-height: 44px; max-width: 100%; object-fit: contain; }
        .sig-lbl { margin-top: 4px; font-weight: 600; color: #334155; }
        .sig-date { margin-top: 6px; color: #64748b; }
        .atp-sheet { background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 34px 40px; margin-bottom: 24px; page-break-after: always; color: #000; font-size: 13px; line-height: 1.3; }
        .atp-sheet:last-of-type { page-break-after: auto; }
        .atp-hdr { position: relative; text-align: center; }
        .atp-hdr .school { font-size: 18px; font-weight: 700; }
        .atp-hdr .addr { font-size: 12px; margin-top: 2px; }
        .atp-hdr .title { font-size: 20px; font-weight: 700; letter-spacing: .02em; margin-top: 8px; }
        .atp-hdr .corner { position: absolute; right: 0; top: 0; text-align: left; font-size: 13px; }
        .atp-hdr .corner-no { color: #dc2626; font-weight: 700; }
        .atp-hdr .corner-date { margin-top: 8px; }
        .atp-hdr .corner-date span { display: inline-block; min-width: 110px; border-bottom: 1px solid #000; text-align: center; padding: 0 4px; }
        .atp-to { margin-top: 34px; }
        .atp-intro { margin-top: 20px; }
        .atp-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 16px; font-size: 13px; }
        .atp-table th, .atp-table td { border: 1px solid #000; padding: 6px 8px; height: 28px; }
        .atp-table th { background: #fff; font-weight: 700; text-align: center; }
        .atp-sigs { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 36px; align-items: start; }
        .atp-sig-box { max-width: 340px; }
        .atp-sig-box.right { margin-left: auto; width: 100%; max-width: 300px; }
        .atp-sig-stack { position: relative; margin-top: 24px; min-height: 28px; border-bottom: 1px solid #000; text-align: center; padding-bottom: 4px; display: flex; align-items: center; justify-content: center; }
        .atp-sig-stack img { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); max-height: 38px; max-width: 92%; object-fit: contain; pointer-events: none; }
        .atp-sig-stack span { position: relative; z-index: 1; font-size: 13px; }
        .atp-po-row { margin-top: 24px; display: flex; align-items: flex-end; gap: 8px; font-size: 12px; }
        .atp-po-row .line { flex: 1; border-bottom: 1px solid #000; padding-bottom: 2px; }
        @media print {
            body { margin: 0; background: #fff; }
            .no-print { display: none; }
            .sheet, .atp-sheet { border: 0; border-radius: 0; padding: 16px; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.close()">Close</button>
        <button class="btn btn-primary" onclick="window.print()">Print</button>
    </div>

    @php
        $displayNo = \App\Support\CanvassWorkflow::displayNumber($canvass);
        $submittedAt = $canvass->canvass_submitted_at ? \Carbon\Carbon::parse($canvass->canvass_submitted_at)->format('Y-m-d H:i:s') : '—';
        $atpDateShort = $canvass->canvass_submitted_at ? \Carbon\Carbon::parse($canvass->canvass_submitted_at)->format('m/d/Y') : now()->format('m/d/Y');
        $ownerName = trim((string) ($canvass->owner_name ?? '')) ?: 'Purchaser';
        $adminName = trim((string) (auth()->user()->user_full_name ?? '')) ?: \App\Support\AccountingSigner::SIGNATURE_TITLE;
        $bySupplier = [];
        foreach ($items as $it) {
            if ((string) ($it->canvass_item_status ?? '') === \App\Support\CanvassWorkflow::ITEM_REJECTED) {
                continue;
            }
            foreach ($it->comparison_offers as $o) {
                if (! $o->is_selected) continue;
                $sid = (int) $o->supplier_id;
                $supName = trim((string) ($o->supplier_display_name ?? ''));
                $groupKey = $supName !== '' ? mb_strtolower($supName) : ('id:'.$sid);
                $lineAmount = (float) ($o->approved_amount ?? $o->computed_total);
                if (! isset($bySupplier[$groupKey])) {
                    $bySupplier[$groupKey] = [
                        'name' => $supName !== '' ? $supName : 'Supplier',
                        'lines' => [],
                        'total' => 0.0,
                    ];
                }
                $itemLabel = (string) ($it->canvass_item_name ?: $it->catalog_item_name);
                $brandLabel = trim((string) ($o->brand_name ?? ''));
                $bySupplier[$groupKey]['lines'][] = [
                    'qty' => (int) ($o->approved_offer_units ?? $o->computed_offer_units),
                    'unit' => (string) ($o->offer_uom_name ?: ($it->uom_name ?: 'pcs')),
                    'description' => ($brandLabel !== '' && $brandLabel !== '—') ? ($itemLabel.' ('.$brandLabel.')') : $itemLabel,
                    'unitCost' => (float) $o->offer_unit_price,
                    'amount' => $lineAmount,
                ];
                $bySupplier[$groupKey]['total'] += $lineAmount;
            }
        }
    @endphp

    @foreach ($bySupplier as $supGroup)
        @php
            $atpLines = $supGroup['lines'];
            $blankCount = max(0, 8 - count($atpLines));
        @endphp
        <div class="atp-sheet">
            <div class="atp-hdr">
                <div class="school">STI COLLEGE ORMOC, INC.</div>
                <div class="addr">Centrum Mall, Aviles Street, Ormoc City</div>
                <div class="title">AUTHORITY TO PURCHASE</div>
                <div class="corner">
                    <div class="corner-no">No.&nbsp;&nbsp;{{ $supGroup['atpNumber'] ?? '' }}</div>
                    <div class="corner-date"><strong>Date</strong>&nbsp;&nbsp;<span>{{ $atpDateShort }}</span></div>
                </div>
            </div>
            <div class="atp-to"><strong>To:</strong> {{ $supGroup['name'] }}</div>
            <div class="atp-intro">Please deliver to bearer the following items chargeable to our account.</div>
            <table class="atp-table">
                <colgroup>
                    <col style="width:10%">
                    <col style="width:10%">
                    <col style="width:52%">
                    <col style="width:14%">
                    <col style="width:14%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th style="text-align:left;">Description</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($atpLines as $line)
                        <tr>
                            <td style="text-align:center;">{{ $line['qty'] }}</td>
                            <td style="text-align:center;">{{ $line['unit'] }}</td>
                            <td>{{ $line['description'] }}</td>
                            <td style="text-align:right;">₱{{ number_format($line['unitCost'], 2) }}</td>
                            <td style="text-align:right;">₱{{ number_format($line['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="3">&nbsp;</td>
                        <td style="text-align:right;font-weight:700;">TOTAL</td>
                        <td style="text-align:right;font-weight:700;">₱{{ number_format((float) ($supGroup['total'] ?? 0), 2) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="atp-sigs">
                <div class="atp-sig-box">
                    <div style="font-weight:600;">RECEIVED BY:</div>
                    <div class="atp-sig-stack">
                        @if (! empty($canvass->canvass_purchaser_signature))
                            <img src="{{ $canvass->canvass_purchaser_signature }}" alt="Received by">
                        @endif
                        <span>{{ $ownerName }}</span>
                    </div>
                    <div class="atp-po-row">
                        <span>Reference P.O. No.</span>
                        <div class="line">&nbsp;</div>
                    </div>
                </div>
                <div class="atp-sig-box right">
                    <div>Authorized by</div>
                    <div class="atp-sig-stack">
                        @if (! empty($canvass->canvass_admin_signature))
                            <img src="{{ $canvass->canvass_admin_signature }}" alt="Authorized by">
                        @endif
                        <span>{{ $adminName }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Shared Requisition Slip (Official Format, Actual Rows Only) --}}
    @php
        $risPrintRows = [];
        foreach ($items as $it) {
            if ((string) ($it->canvass_item_status ?? '') === \App\Support\CanvassWorkflow::ITEM_REJECTED) {
                continue;
            }
            $itemName = trim((string) ($it->canvass_item_name ?: $it->catalog_item_name));
            $nameKey = mb_strtolower($itemName);
            $reqQty = (float) ($it->canvass_item_quantity ?? 0);
            $selectedOffers = collect($it->comparison_offers ?? [])->filter(fn ($o) => ! empty($o->is_selected))->values();
            if ($selectedOffers->isEmpty()) {
                $risPrintRows[] = [
                    'itemName' => $itemName,
                    'nameKey' => $nameKey,
                    'brandLabel' => '',
                    'unit' => (string) ($it->uom_name ?: 'pcs'),
                    'supplierRaw' => '',
                    'supplierBase' => '',
                    'supplierKey' => '',
                    'requestedQty' => $reqQty,
                    'issuedQty' => 0.0,
                    'unitCost' => null,
                    'amount' => null,
                ];
                continue;
            }
            $totalIssued = $selectedOffers->sum(function ($o) {
                $q = (int) ($o->approved_offer_units ?? $o->computed_offer_units ?? 0);
                return $q * (float) ($o->offer_units_per_pack ?? 1);
            });
            foreach ($selectedOffers as $o) {
                $q = (int) ($o->approved_offer_units ?? $o->computed_offer_units ?? 0);
                $brandBase = trim((string) ($o->brand_name ?? ''));
                $brandLabel = ($brandBase !== '' && $brandBase !== '—')
                    ? ($q > 1 ? $brandBase.' ×'.$q : $brandBase)
                    : ($q > 1 ? '×'.$q : '');
                $supRaw = trim((string) ($o->supplier_display_name ?? ''));
                $supBase = trim(explode('·', $supRaw)[0]);
                $supKey = mb_strtolower($supBase !== '' ? $supBase : $supRaw);
                $unitCost = (float) ($o->offer_unit_price ?? 0);
                $amount = (float) ($o->approved_amount ?? ($q * $unitCost));
                $risPrintRows[] = [
                    'itemName' => $itemName,
                    'nameKey' => $nameKey,
                    'brandLabel' => $brandLabel,
                    'unit' => (string) ($o->offer_uom_name ?: ($it->uom_name ?: 'pcs')),
                    'supplierRaw' => $supRaw,
                    'supplierBase' => $supBase,
                    'supplierKey' => $supKey,
                    'requestedQty' => $reqQty,
                    'issuedQty' => $totalIssued,
                    'unitCost' => $unitCost,
                    'amount' => $amount,
                ];
            }
        }
        $fmtRisQty = static function (float $val): string {
            return abs($val - round($val)) < 0.0001
                ? (string) (int) round($val)
                : rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
        };
    @endphp

    <div class="atp-sheet" style="padding: 28px 34px;">
        <div class="ris-original-form" style="min-height: 0; border: 0; padding: 0; box-shadow: none;">
            <div class="ris-document-header">
                <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                <div class="ris-document-title">REQUISITION SLIP</div>
                <div class="ris-number-area">
                    <span class="ris-number-label">No.</span>
                    <span class="ris-number-line ris-value-line">{{ $canvass->linked_ris_number ?? '' }}</span>
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
                    @foreach ($risPrintRows as $rIdx => $r)
                        @php
                            $prev = $rIdx > 0 ? $risPrintRows[$rIdx - 1] : null;
                            $sameItem = $r['nameKey'] !== '' && $prev && $prev['nameKey'] === $r['nameKey'];
                            $sameSupplier = $sameItem && $r['supplierKey'] !== '' && $prev && $prev['supplierKey'] === $r['supplierKey'];
                            $itemSpan = 1;
                            if ($r['nameKey'] !== '' && ! $sameItem) {
                                for ($i = $rIdx + 1; $i < count($risPrintRows); $i++) {
                                    if ($risPrintRows[$i]['nameKey'] !== $r['nameKey']) break;
                                    $itemSpan++;
                                }
                            }
                            $supplierSpan = 1;
                            if ($r['supplierKey'] !== '' && ! $sameSupplier) {
                                for ($i = $rIdx + 1; $i < count($risPrintRows); $i++) {
                                    if ($risPrintRows[$i]['nameKey'] !== $r['nameKey'] || $risPrintRows[$i]['supplierKey'] !== $r['supplierKey']) break;
                                    $supplierSpan++;
                                }
                            }
                            $supDisplay = $supplierSpan > 1 && $r['supplierBase'] !== '' ? $r['supplierBase'] : $r['supplierRaw'];
                        @endphp
                        <tr>
                            @if (! $sameItem)
                                <td @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif>{{ $r['itemName'] }}</td>
                            @endif
                            <td class="text-center">{{ $r['brandLabel'] ?: ' ' }}</td>
                            <td class="text-center">{{ $r['unit'] }}</td>
                            @if (! $sameSupplier)
                                <td @if ($supplierSpan > 1) rowspan="{{ $supplierSpan }}" @endif>{{ $supDisplay ?: ' ' }}</td>
                            @endif
                            @if (! $sameItem)
                                <td @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif class="text-center">{{ $fmtRisQty($r['requestedQty']) }}</td>
                                <td @if ($itemSpan > 1) rowspan="{{ $itemSpan }}" @endif class="text-center">{{ $fmtRisQty($r['issuedQty']) }}</td>
                            @endif
                            <td class="text-right">{{ $r['unitCost'] !== null ? '₱'.number_format($r['unitCost'], 2) : ' ' }}</td>
                            <td class="text-right">{{ $r['amount'] !== null ? '₱'.number_format($r['amount'], 2) : ' ' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="ris-purpose-area">
                <div class="ris-purpose-lined-wrap">
                    <div class="ris-purpose-label">PURPOSE</div>
                    <div class="ris-purpose-input">{{ trim((string) ($canvass->canvass_purpose ?? '')) ?: ' ' }}</div>
                </div>
            </div>

            <div class="ris-signatures">
                <div class="ris-signature-column">
                    <div class="ris-signature-label">Requested by:</div>
                    <div class="ris-signature-line ris-value-line" style="position:relative;display:flex;align-items:center;justify-content:center;">
                        @if (! empty($canvass->canvass_purchaser_signature))
                            <img src="{{ $canvass->canvass_purchaser_signature }}" alt="Requested by" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);max-height:34px;max-width:90%;object-fit:contain;pointer-events:none;">
                        @endif
                        <span style="position:relative;z-index:1;">{{ $ownerName }}</span>
                    </div>
                    <div class="ris-date-label">Date:</div>
                    <div class="ris-date-line ris-value-line">{{ $atpDateShort }}</div>
                </div>
                <div class="ris-signature-column">
                    <div class="ris-signature-label">Approved by:</div>
                    <div class="ris-signature-line ris-value-line">&nbsp;</div>
                    <div class="ris-date-label">Date:</div>
                    <div class="ris-date-line ris-value-line">&nbsp;</div>
                </div>
                <div class="ris-signature-column">
                    <div class="ris-signature-label">Issued by:</div>
                    <div class="ris-signature-line ris-value-line">&nbsp;</div>
                    <div class="ris-date-label">Date:</div>
                    <div class="ris-date-line ris-value-line">&nbsp;</div>
                </div>
                <div class="ris-signature-column">
                    <div class="ris-signature-label">Received by:</div>
                    <div class="ris-signature-line ris-value-line">&nbsp;</div>
                    <div class="ris-date-label">Date:</div>
                    <div class="ris-date-line ris-value-line">&nbsp;</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
