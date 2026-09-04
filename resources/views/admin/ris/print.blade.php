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
        .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; position: relative; z-index: 50; }
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
        .signature-name { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-image {
            max-height: 36px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: -6px;
            z-index: 10;
        }
        .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }

        /* Modal / screen preview */
        @media screen {
            html, body {
                width: 11in;
                min-height: 0;
                height: auto;
                overflow: hidden;
                background: #fff;
            }
            .ris-document {
                width: 11in;
                height: auto;
                min-height: 0;
                max-height: none;
                padding: 0.2in 0.28in 0.18in;
                overflow: visible;
                background: #fff;
            }
            .header {
                margin-top: 4px;
                margin-bottom: 8px;
            }
            .school { font-size: 17px; }
            .title { font-size: 17px; margin-top: 4px; }
            .ris-table th, .ris-table td { height: 24px; padding: 2px 5px; font-size: 12px; }
            .purpose { margin-top: 6px; font-size: 13px; }
            .purpose-lines { min-height: 36px; line-height: 20px; }
            .signatures { margin-top: 12px; gap: 14px; font-size: 12px; }
            .signature-line { min-height: 26px; }
            .date-row { margin-top: 6px; }
        }

        @media print {
            body { background: white; }
            .ris-document {
                width: 100%;
                min-height: auto;
                height: auto;
                max-height: none;
                margin: 0;
                padding: 0.2in;
                position: relative;
                overflow: visible;
            }
            .header { margin-top: 24px; }
            @page { size: landscape; margin: 0.25in; }
        }
    </style>
</head>
<body>
@php
    $isDirectlyApproved = ($ris->ris_status ?? '') === 'Directly Approved';
    $rawApproved = (string) ($ris->ris_approved_by_signature ?? '');
    $rawIssued = (string) ($ris->ris_issued_by_signature ?? '');
    $hasCheckedSign = $isDirectlyApproved && $rawApproved !== '';
    $hasCheckedImage = $hasCheckedSign && str_starts_with($rawApproved, 'data:image');
    $hasPresidentImage = !$isDirectlyApproved && $rawApproved !== '' && str_starts_with($rawApproved, 'data:image');
    $hasPresidentText = !$isDirectlyApproved && $rawApproved !== '' && !$hasPresidentImage;
    $legacyAdminInApproved = $isDirectlyApproved && $rawApproved !== '' && $rawIssued === '';
    $hasPresidentSign = $hasPresidentImage || $hasPresidentText;

    $issuedDisplay = $rawIssued !== '' ? $rawIssued : ($legacyAdminInApproved ? $rawApproved : '');
    $hasIssuedImage = $issuedDisplay !== '' && str_starts_with($issuedDisplay, 'data:image');
    $adminIssuedName = null;
    if ($hasIssuedImage || ($isDirectlyApproved && $issuedDisplay !== '')) {
        try {
            if (Schema::hasTable('approval_logs_table')) {
                $adminLog = DB::table('approval_logs_table')
                    ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                    ->where('approval_logs_table.approval_log_reference_type', 'RIS')
                    ->where('approval_logs_table.approval_log_reference_id', (int) ($ris->ris_id ?? 0))
                    ->whereIn('approval_logs_table.approval_log_approval_status', ['Admin Approved', 'Co-signed', 'Directly Approved'])
                    ->orderByDesc('approval_logs_table.approval_log_approved_at')
                    ->select('users_table.user_full_name')
                    ->first();
                $adminIssuedName = $adminLog->user_full_name ?? null;
            }
        } catch (\Throwable $e) {
            $adminIssuedName = null;
        }
        if ($adminIssuedName === null && !$hasIssuedImage && $issuedDisplay !== '') {
            $adminIssuedName = $issuedDisplay;
        }
    }
    $issuedDate = $ris->ris_issued_by_date
        ?: (($legacyAdminInApproved && $rawIssued === '') ? $ris->ris_approved_by_date : null);

    $checkedDate = $hasCheckedSign ? $ris->ris_approved_by_date : null;
    $checkedName = $hasCheckedImage
        ? ($adminIssuedName ?: 'Admin')
        : ($hasCheckedSign && !$hasCheckedImage ? $rawApproved : '');

    $approvedDate = $hasPresidentSign ? $ris->ris_approved_by_date : null;
    $approvedName = $hasPresidentImage
        ? ($presidentName ?? 'President')
        : ($hasPresidentText ? $rawApproved : '');
@endphp

    <main class="ris-document">
        @if ($isDirectlyApproved)
            @include('partials.ris-approval-watermark', ['watermarkLabel' => 'ADMIN APPROVED'])
        @elseif ($hasPresidentSign && $issuedDisplay !== '')
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
                    <th rowspan="2" class="supplier-col">SUPPLIER</th>
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
                    @php
                        $item = $risItems[$row] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $item?->ris_item_name_description ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->brand_name ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->uom_name ?? '' }}</td>
                        <td>{{ $item?->supplier_display_name ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->ris_quantity_requested ?? '' }}</td>
                        <td style="text-align: center;">{{ $item?->ris_quantity_issued ?? '' }}</td>
                        <td style="text-align: right;">{{ $item && $item->ris_unit_cost !== null ? number_format((float) $item->ris_unit_cost, 2) : '' }}</td>
                        <td style="text-align: right;">{{ $item && $item->ris_total_amount !== null ? number_format((float) $item->ris_total_amount, 2) : '' }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <section class="purpose">
            <div>PURPOSE</div>
            <div class="purpose-lines">{{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? '') }}</div>
        </section>

        <section class="signatures">
            <div class="signature-box">
                <p>Requested by:</p>
                <div class="signature-line">{{ $ris->ris_requested_by_signature ?: '' }}</div>
                <div class="date-row">
                    <span>Date:</span>
                    <div class="signature-line">
                        {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('M d, Y') : '' }}
                    </div>
                </div>
            </div>

            <div class="signature-box">
                <p>{{ $isDirectlyApproved ? 'Checked by:' : 'Approved by:' }}</p>
                <div class="signature-line">
                    @if ($isDirectlyApproved)
                        @if ($hasCheckedImage)
                            <img src="{{ $rawApproved }}" alt="Checked by signature" class="signature-image" />
                            <span class="signature-name">{{ $checkedName }}</span>
                        @elseif ($hasCheckedSign)
                            <span class="signature-name">{{ $checkedName }}</span>
                        @endif
                    @elseif ($hasPresidentImage)
                        <img src="{{ $rawApproved }}" alt="Approved by signature" class="signature-image" />
                        <span class="signature-name">{{ $approvedName }}</span>
                    @elseif ($hasPresidentSign)
                        <span class="signature-name">{{ $approvedName }}</span>
                    @endif
                </div>
                <div class="date-row">
                    <span>Date:</span>
                    <div class="signature-line">
                        @php
                            $secondColDate = $isDirectlyApproved ? $checkedDate : $approvedDate;
                        @endphp
                        {{ $secondColDate ? \Carbon\Carbon::parse($secondColDate)->format('M d, Y') : '' }}
                    </div>
                </div>
            </div>

            <div class="signature-box">
                <p>Issued by:</p>
                <div class="signature-line" style="flex-direction: column; align-items: center; justify-content: flex-end;">
                    @if ($hasIssuedImage)
                        <img src="{{ $issuedDisplay }}" alt="Issued by signature" class="signature-image" />
                        @if (!empty($adminIssuedName))
                            <span class="signature-name">{{ $adminIssuedName }}</span>
                        @endif
                        <span class="signature-position">Admin</span>
                    @elseif ($issuedDisplay !== '')
                        <span class="signature-name">{{ $issuedDisplay }}</span>
                        <span class="signature-position">Admin</span>
                    @endif
                </div>
                <div class="date-row">
                    <span>Date:</span>
                    <div class="signature-line">
                        {{ $issuedDate ? \Carbon\Carbon::parse($issuedDate)->format('M d, Y') : '' }}
                    </div>
                </div>
            </div>

            <div class="signature-box">
                <p>Received by:</p>
                <div class="signature-line">{{ $ris->ris_received_by_signature ?: '' }}</div>
                <div class="date-row">
                    <span>Date:</span>
                    <div class="signature-line">
                        {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('M d, Y') : '' }}
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
