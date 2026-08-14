<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIS Preview</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
        }
        .ris-original-form {
            width: 100%;
            max-width: 1095px;
            min-height: 845px;
            margin: 0 auto;
            border: 2px solid #1f2937;
            padding: 26px 24px 24px;
            background: #fff;
            color: #000;
            position: relative;
        }
        .ris-document-header { position: relative; height: 120px; text-align: center; }
        .ris-school-name { font-size: 19px; line-height: 1.2; font-weight: 700; }
        .ris-document-title { margin-top: 9px; font-size: 15px; line-height: 1.2; font-weight: 700; }
        .ris-number-area {
            position: absolute; right: 0; bottom: 18px;
            display: flex; align-items: flex-end; gap: 10px;
        }
        .ris-number-label { font-size: 15px; font-weight: 600; }
        .ris-number-line {
            display: block;
            width: 160px;
            border-bottom: 1px solid #1f2937;
        }
        .ris-items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ris-items-table th, .ris-items-table td { border: 1px solid #1f2937; }
        .ris-items-table th {
            padding: 9px 5px; vertical-align: middle; text-align: center;
            font-size: 12px; line-height: 1.2; font-weight: 700;
        }
        .ris-items-table tbody td {
            height: 45px; padding: 4px 6px; font-size: 12px; vertical-align: middle;
        }
        .ris-item-column { width: 40%; }
        .ris-quantity-header { width: 23%; }
        .ris-requested-column { width: 11%; font-size: 11px !important; }
        .ris-issued-column { width: 12%; font-size: 11px !important; }
        .ris-unit-cost-column { width: 17%; }
        .ris-amount-column { width: 20%; }
        .ris-purpose-area { margin-top: 31px; }
        .ris-purpose-label { font-size: 13px; font-weight: 700; }
        .ris-purpose-line-row { display: flex; margin-top: 29px; }
        .ris-purpose-spacer { width: 80px; flex-shrink: 0; }
        .ris-purpose-line {
            flex: 1;
            min-height: 40px;
            border-bottom: 1px solid #1f2937;
        }
        .ris-signatures {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 32px;
            margin-top: 40px;
        }
        .ris-signature-column { min-width: 0; }
        .ris-signature-label { font-size: 12px; color: #374151; }
        .ris-signature-line {
            height: 49px;
            border-bottom: 1px solid #1f2937;
            position: relative;
        }
        .ris-date-label { margin-top: 16px; font-size: 12px; color: #374151; }
        .ris-date-line {
            height: 31px;
            border-bottom: 1px solid #1f2937;
        }
        .ris-value-line {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            min-height: 31px;
            padding: 0 6px 4px;
            font-size: 12px;
            line-height: 1.35;
            text-align: center;
        }
        .ris-number-line.ris-value-line { min-height: 24px; }
        .ris-signature-line.ris-value-line { min-height: 49px; }
        .ris-signature-image {
            max-height: 36px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: -6px;
        }
        .approval-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.07);
            letter-spacing: 6px;
            text-transform: uppercase;
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
            user-select: none;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .ris-original-form { border-width: 1px; max-width: none; }
            .approval-watermark {
                opacity: 0.12;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page { size: landscape; margin: 0.25in; }
        }
    </style>
</head>
<body>
@php
    $isDirectlyApproved = ($ris->ris_status ?? '') === 'Directly Approved';
    $rawApproved = (string) ($ris->ris_approved_by_signature ?? '');
    $rawIssued = (string) ($ris->ris_issued_by_signature ?? '');
    $hasPresidentImage = $rawApproved !== '' && str_starts_with($rawApproved, 'data:image');
    $legacyAdminInApproved = $rawApproved !== '' && !$hasPresidentImage && $rawIssued === '';
    $presidentText = ($rawApproved !== '' && !$hasPresidentImage && $rawIssued !== '') ? $rawApproved : '';
    $hasPresidentSign = $hasPresidentImage || $presidentText !== '';

    $issuedDisplay = $rawIssued !== '' ? $rawIssued : ($legacyAdminInApproved ? $rawApproved : '');
    $issuedDate = $ris->ris_issued_by_date
        ?: (($legacyAdminInApproved && empty($rawIssued)) ? $ris->ris_approved_by_date : null);

    $approvedDate = $hasPresidentSign ? $ris->ris_approved_by_date : null;
    $approvedName = $hasPresidentImage ? ($presidentName ?? 'President') : $presidentText;
@endphp

    <div class="ris-original-form">
        @if ($isDirectlyApproved)
            <div class="approval-watermark">ADMIN APPROVED</div>
        @elseif (($ris->ris_status ?? '') === 'Approved' && $hasPresidentSign)
            <div class="approval-watermark">APPROVED</div>
        @endif

        <div class="ris-document-header">
            <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
            <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
            <div class="ris-number-area">
                <span class="ris-number-label">No.</span>
                <span class="ris-number-line ris-value-line">{{ $ris->ris_form_number ?: ' ' }}</span>
            </div>
        </div>

        <table class="ris-items-table">
            <thead>
                <tr>
                    <th rowspan="2" class="ris-item-column">ITEM</th>
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
                @for ($row = 0; $row < 8; $row++)
                    @php $item = $risItems[$row] ?? null; @endphp
                    <tr>
                        <td>{{ $item->ris_item_name_description ?? ' ' }}</td>
                        <td style="text-align:center;">{{ $item->ris_quantity_requested ?? ' ' }}</td>
                        <td style="text-align:center;">{{ $item->ris_quantity_issued ?? ' ' }}</td>
                        <td style="text-align:right;">{{ $item && $item->ris_unit_cost !== null ? number_format((float) $item->ris_unit_cost, 2) : ' ' }}</td>
                        <td style="text-align:right;">{{ $item && $item->ris_total_amount !== null ? number_format((float) $item->ris_total_amount, 2) : ' ' }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="ris-purpose-area">
            <div class="ris-purpose-label">PURPOSE</div>
            <div class="ris-purpose-line-row">
                <div class="ris-purpose-spacer"></div>
                <div class="ris-purpose-line ris-value-line" style="justify-content:flex-start;">
                    {{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? ' ') }}
                </div>
            </div>
        </div>

        <div class="ris-signatures">
            <div class="ris-signature-column">
                <div class="ris-signature-label">Requested by:</div>
                <div class="ris-signature-line ris-value-line">{{ $ris->ris_requested_by_signature ?: ' ' }}</div>
                <div class="ris-date-label">Date:</div>
                <div class="ris-date-line ris-value-line">
                    {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('M d, Y') : ' ' }}
                </div>
            </div>

            <div class="ris-signature-column">
                <div class="ris-signature-label">Approved by:</div>
                <div class="ris-signature-line ris-value-line">
                    @if ($hasPresidentImage)
                        <img src="{{ $rawApproved }}" alt="Approved by signature" class="ris-signature-image" />
                        <span>{{ $approvedName }}</span>
                    @elseif ($hasPresidentSign)
                        {{ $approvedName }}
                    @else
                        {{ ' ' }}
                    @endif
                </div>
                <div class="ris-date-label">Date:</div>
                <div class="ris-date-line ris-value-line">
                    {{ $approvedDate ? \Carbon\Carbon::parse($approvedDate)->format('M d, Y') : ' ' }}
                </div>
            </div>

            <div class="ris-signature-column">
                <div class="ris-signature-label">Issued by:</div>
                <div class="ris-signature-line ris-value-line">{{ $issuedDisplay !== '' ? $issuedDisplay : ' ' }}</div>
                <div class="ris-date-label">Date:</div>
                <div class="ris-date-line ris-value-line">
                    {{ $issuedDate ? \Carbon\Carbon::parse($issuedDate)->format('M d, Y') : ' ' }}
                </div>
            </div>

            <div class="ris-signature-column">
                <div class="ris-signature-label">Received by:</div>
                <div class="ris-signature-line ris-value-line">{{ $ris->ris_received_by_signature ?: ' ' }}</div>
                <div class="ris-date-label">Date:</div>
                <div class="ris-date-line ris-value-line">
                    {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('M d, Y') : ' ' }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
