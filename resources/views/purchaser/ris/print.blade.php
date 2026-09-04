<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIS Preview</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        html, body { margin: 0; padding: 0; background: transparent; }
        .ris-document {
            width: 11in;
            min-height: 8.5in;
            padding: 0.35in;
            background: white;
            position: relative;
        }
        .header { position: relative; margin-top: 24px; margin-bottom: 10px; text-align: center; }
        .school { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .title { margin-top: 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: 800; letter-spacing: 1px; }
        .number { position: absolute; right: 0; bottom: -4px; font-size: 14px; }
        .line { display: inline-block; min-width: 130px; border-bottom: 1px solid #111827; text-align: center; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ris-table th, .ris-table td { border: 2px solid #374151; height: 28px; padding: 3px 6px; font-size: 13px; vertical-align: top; }
        .ris-table th { text-align: center; font-weight: 700; }
        .item-col { width: 20%; }
        .brand-col { width: 10%; }
        .unit-col { width: 7%; }
        .supplier-col { width: 14%; }
        .qty-col { width: 9%; }
        .cost-col { width: 12%; }
        .amount-col { width: 14%; }
        .purpose { margin-top: 8px; display: grid; grid-template-columns: 130px 1fr; gap: 8px; font-size: 15px; font-weight: 700; }
        .purpose-lines { min-height: 58px; border-bottom: 1px solid #6b7280; line-height: 28px; font-weight: 400; }
        .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; position: relative; z-index: 50; page-break-inside: avoid; break-inside: avoid; }
        .signature-box { position: relative; z-index: 50; }
        .signature-box p { margin: 0 0 6px; }
        .signature-line {
            border-bottom: 1px solid #111827;
            min-height: 36px;
            text-align: center;
            font-size: 12px;
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 2px;
        }
        .signature-name-wrapper { position: relative; display: inline-block; width: 100%; text-align: center; }
        .signature-name { font-size: 11px; text-transform: none; letter-spacing: 0; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-image {
            max-height: 36px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 12px;
            z-index: 10;
            pointer-events: none;
        }
        .signature-line .signature-name { display: block; line-height: 20px; }
        .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                background: white !important;
            }
            .ris-document {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto;
                height: auto;
                max-height: none;
                margin: 0;
                padding: 0.2in;
                position: relative;
                overflow: visible !important;
            }
            .header { margin-top: 24px; margin-bottom: 8px; }
            .ris-table { width: 100% !important; }
            .signatures { margin-top: 16px; }
            @page { size: landscape; margin: 0.25in; }
        }
    </style>
    @include('partials.ris-signature-overlay-styles')
</head>
<body>

@php
    $isDirectlyApproved = ($ris->ris_status ?? '') === 'Directly Approved';
    $approvedLabel = \App\Support\RisWorkflow::approvedByColumnLabel($ris);
    $approvedRaw = trim((string) ($ris->ris_approved_by_signature ?? ''));
    $approvedImage = \App\Support\RisWorkflow::isDrawnSignature($approvedRaw) ? $approvedRaw : '';
    $approvedName = \App\Support\RisWorkflow::approvedByPrintedName($ris, $presidentName ?? null);

    $issuedRaw = trim((string) ($ris->ris_issued_by_signature ?? ''));
    $issuedImage = \App\Support\RisWorkflow::isDrawnSignature($issuedRaw) ? $issuedRaw : '';
    $issuedName = \App\Support\RisWorkflow::issuedByPrintedName($ris);
    $hasIssued = $issuedRaw !== '';

    $receivedRaw = trim((string) ($ris->ris_received_by_signature ?? ''));
    $receivedImage = \App\Support\RisWorkflow::isDrawnSignature($receivedRaw) ? $receivedRaw : '';
    $receivedName = ($receivedImage === '' && $receivedRaw !== '') ? $receivedRaw : '';

    $fullyReleased = $hasIssued && ($isDirectlyApproved || $approvedRaw !== '');
@endphp

    <main class="ris-document">
        @if ($isDirectlyApproved)
            @include('partials.ris-approval-watermark', ['watermarkLabel' => 'ADMIN APPROVED'])
        @elseif ($fullyReleased)
            @include('partials.ris-approval-watermark', ['watermarkLabel' => 'APPROVED'])
        @endif

        <section class="header">
            <div class="school">STI COLLEGE- ORMOC, INC.</div>
            <div class="title">REQUISITION AND ISSUE SLIP</div>
            <div class="number">
                No.
                <span class="line">{{ $ris->ris_form_number ?? $ris->ris_id }}</span>
            </div>
        </section>

        <table class="ris-table">
            <thead>
                <tr>
                    <th rowspan="2" class="item-col">ITEM</th>
                    <th rowspan="2" class="brand-col">BRAND</th>
                    <th rowspan="2" class="unit-col">UNIT</th>
                    <th rowspan="2">SUPPLIER</th>
                    <th colspan="2">QUANTITY</th>
                    <th rowspan="2" class="cost-col">UNIT COST</th>
                    <th rowspan="2" class="amount-col">AMOUNT</th>
                </tr>
                <tr>
                    <th class="qty-col">REQUESTED</th>
                    <th class="qty-col">ISSUED</th>
                </tr>
            </thead>
            <tbody>
                @for($row = 0; $row < 10; $row++)
                    @php($item = $risItems[$row] ?? null)
                    <tr>
                        <td>{{ $item?->ris_item_name_description ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->brand_name ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->uom_name ?? '' }}</td>
                        <td>{{ $item?->supplier_display_name ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->ris_quantity_requested ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->ris_quantity_issued ?? '' }}</td>
                        <td style="text-align: right;">{{ $item && $item->ris_unit_cost !== null ? number_format($item->ris_unit_cost, 2) : '' }}</td>
                        <td style="text-align: right;">{{ $item && $item->ris_total_amount !== null ? number_format($item->ris_total_amount, 2) : '' }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <section class="purpose">
            <div>PURPOSE</div>
            <div class="purpose-lines">{{ $ris->ris_purpose_description }}</div>
        </section>

        <section class="signatures">
            <div class="signature-box">
                <p>Requested by:</p>
                @include('partials.ris-requested-by-signatory', ['ris' => $ris])
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('d/m/Y') : '' }}</div></div>
            </div>
            <div class="signature-box">
                <p>{{ $approvedLabel }}</p>
                <div class="signature-line">
                    @if ($approvedImage !== '')
                        <img src="{{ $approvedImage }}" alt="{{ rtrim($approvedLabel, ':') }} signature" class="signature-image" />
                    @endif
                    @if ($approvedName !== '')
                        <span class="signature-name">{{ $approvedName }}</span>
                    @endif
                </div>
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_approved_by_date ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('d/m/Y') : '' }}</div></div>
            </div>
            <div class="signature-box">
                <p>Issued by:</p>
                <div class="signature-line">
                    @if ($issuedImage !== '')
                        <img src="{{ $issuedImage }}" alt="Issued by signature" class="signature-image" />
                    @endif
                    @if ($issuedName !== '' || ($hasIssued && !$issuedImage))
                        <span class="signature-name">{{ $issuedName !== '' ? $issuedName : $issuedRaw }}</span>
                    @endif
                </div>
                <div class="date-row">
                    <span>Date:</span>
                    <div class="signature-line">
                        {{ $hasIssued && $ris->ris_issued_by_date ? \Carbon\Carbon::parse($ris->ris_issued_by_date)->format('d/m/Y') : '' }}
                    </div>
                </div>
            </div>
            <div class="signature-box">
                <p>Received by:</p>
                <div class="signature-line">
                    @if ($receivedImage !== '')
                        <img src="{{ $receivedImage }}" alt="Received by signature" class="signature-image" />
                    @endif
                    @if ($receivedName !== '')
                        <span class="signature-name">{{ $receivedName }}</span>
                    @endif
                </div>
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : '' }}</div></div>
            </div>
        </section>
    </main>
</body>
</html>

