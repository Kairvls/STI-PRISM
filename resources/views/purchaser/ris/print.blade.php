<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ris->ris_form_number ?? 'RIS Form' }}</title>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: PRINTABLE RIS FORM STYLES --}}
    {{-- ===================================================== --}}

    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; background: #f3f4f6; color: #111827; }
        .toolbar { padding: 16px; text-align: center; }
        .toolbar button { border: 0; border-radius: 6px; background: #111827; color: white; cursor: pointer; font-size: 14px; padding: 10px 18px; }
        .sheet { width: 11in; min-height: 8.5in; margin: 0 auto 24px; background: white; padding: 0.35in; position: relative; }
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
        .signature-box p { margin: 0 0 38px; }
        .signature-line { border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
        .signature-image { display: block; max-height: 72px; margin: 0 auto 4px; }
        .signature-name { margin-top: 6px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .date-row { margin-top: 16px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }

        .approval-stamp {
            position: absolute;
            top: 0.5in;
            left: 0.35in;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 100px;
            height: 100px;
            border: 3px solid rgba(5, 150, 105, 0.55);
            border-radius: 50%;
            color: rgba(5, 150, 105, 0.7);
            font-weight: 900;
            font-size: 12px;
            line-height: 1.2;
            letter-spacing: 1.5px;
            pointer-events: none;
            opacity: 0.75;
        }

        .approval-stamp .stamp-title {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 2px;
        }

        .approval-stamp .stamp-sub {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { width: 100%; min-height: auto; margin: 0; padding: 0.2in; position: relative; }
            .header { margin-top: 140px; }
            .approval-stamp { top: 0.35in; left: 0.2in; }
            .approval-stamp {
                opacity: 0.7;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page { size: landscape; margin: 0.25in; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print RIS Form</button>
    </div>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: PRINTABLE RIS FORM MATCHING PAPER FORMAT --}}
    {{-- ===================================================== --}}

    <main class="sheet">
        @if ($ris->ris_status === 'Approved')
            <div class="approval-stamp">
                <span class="stamp-title">APPROVED</span>
                <span class="stamp-sub">President</span>
                <span class="stamp-sub">{{ \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('Y-m-d') }}</span>
            </div>
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
                <div class="signature-line">
                    @if (!empty($ris->ris_approved_by_signature) && strpos($ris->ris_approved_by_signature, 'data:image/png;base64,') === 0)
                        <img src="{{ $ris->ris_approved_by_signature }}" alt="Approved by signature" class="signature-image" />
                    @else
                        {{ $ris->ris_approved_by_signature }}
                    @endif
                </div>
                <div class="date-row"><span>Date:</span><div class="signature-line">{{ $ris->ris_approved_by_date }}</div></div>
            </div>
            <div class="signature-box">
                <p>Issued by:</p>
                <div class="signature-line">
                    @if (!empty($ris->ris_issued_by_signature) && strpos($ris->ris_issued_by_signature, 'data:image/png;base64,') === 0)
                        <img src="{{ $ris->ris_issued_by_signature }}" alt="Issued by signature" class="signature-image" />
                    @else
                        {{ $ris->ris_issued_by_signature }}
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
