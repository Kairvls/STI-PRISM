<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIS Preview</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; padding: 0; background: transparent; }
        .ris-document {
            width: 11in;
            min-height: 8.5in;
            padding: 0.35in;
            background: white;
            position: relative;
        }
        .header { position: relative; margin-top: 160px; margin-bottom: 10px; text-align: center; }
        .school { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .title { margin-top: 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: 800; letter-spacing: 1px; }
        .number { position: absolute; right: 0; bottom: -4px; font-size: 14px; }
        .line { display: inline-block; min-width: 130px; border-bottom: 1px solid #111827; text-align: center; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ris-table th, .ris-table td { border: 2px solid #374151; height: 28px; padding: 3px 6px; font-size: 13px; vertical-align: top; }
        .ris-table th { text-align: center; font-weight: 700; }
        .item-col { width: 40%; }
        .qty-col { width: 10%; }
        .cost-col { width: 10%; }
        .amount-col { width: 30%; }
        .purpose { margin-top: 8px; display: grid; grid-template-columns: 130px 1fr; gap: 8px; font-size: 15px; font-weight: 700; }
        .purpose-lines { min-height: 58px; border-bottom: 1px solid #6b7280; line-height: 28px; font-weight: 400; }
        .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; }
        .signature-box { position: relative; }
        .signature-box p { margin: 0 0 6px; }
        .signature-line { border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
        .signature-name-wrapper { position: relative; display: inline-block; width: 100%; text-align: center; }
        .signature-name { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-image { max-height: 36px; width: auto; position: absolute; left: 50%; transform: translateX(-50%); bottom: 100%; margin-bottom: -8px; z-index: 10; }
        .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }

        .approval-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.08);
            letter-spacing: 8px;
            text-transform: uppercase;
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
            user-select: none;
        }

@media print {
            body { background: white; }
            .ris-document {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0.2in;
                position: relative;
            }
            .header { margin-top: 140px; }
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

{{-- Toolbar removed --}}

    <main class="ris-document">
        @if ($ris->ris_status === 'Approved')
            <div class="approval-watermark">APPROVED</div>
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
                        <td>{{ $item->ris_item_name_description ?? '' }}</td>
                        <td style="text-align: center;">{{ $item->ris_quantity_requested ?? '' }}</td>
                        <td style="text-align: center;">{{ $item->ris_quantity_issued ?? '' }}</td>
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
                <div class="signature-line">{{ $ris->ris_requested_by_signature }}</div>
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_requested_by_date }}</div></div>
            </div>
            <div class="signature-box">
                <p>Approved by:</p>
                <div class="signature-line"></div>
                <div class="signature-name-wrapper">
                    <div class="signature-name">{{ $ris->ris_approved_by_name ?? '' }}</div>
                    <div class="signature-position">{{ $ris->ris_approved_by_position ?? '' }}</div>
                    @if (!empty($ris->ris_approved_by_signature) && strpos($ris->ris_approved_by_signature, 'data:image/png;base64,') === 0)
                        <img src="{{ $ris->ris_approved_by_signature }}" alt="Approved by signature" class="signature-image" />
                    @endif
                </div>
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_approved_by_date }}</div></div>
            </div>
            <div class="signature-box">
                <p>Issued by:</p>
                <div class="signature-line"></div>
                <div class="signature-name-wrapper">
                    <div class="signature-name">{{ $ris->ris_issued_by_name ?? '' }}</div>
                    <div class="signature-position">{{ $ris->ris_issued_by_position ?? '' }}</div>
                    @if (!empty($ris->ris_issued_by_signature) && strpos($ris->ris_issued_by_signature, 'data:image/png;base64,') === 0)
                        <img src="{{ $ris->ris_issued_by_signature }}" alt="Issued by signature" class="signature-image" />
                    @endif
                </div>
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_issued_by_date }}</div></div>
            </div>
            <div class="signature-box">
                <p>Received by:</p>
                <div class="signature-line">{{ $ris->ris_received_by_signature }}</div>
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_received_by_date }}</div></div>
            </div>
        </section>
    </main>
</body>
</html>

