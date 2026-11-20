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
            width: 210mm;
            max-width: 210mm;
            min-height: 0;
            padding: 8mm 10mm;
            background: white;
            position: relative;
        }
        .header { position: relative; min-height: 78px; margin-top: 8px; margin-bottom: 12px; text-align: center; }
        .school { font-size: 18px; font-weight: 700; letter-spacing: 0.4px; padding: 0 230px; }
        .title { margin-top: 6px; font-family: Georgia, 'Times New Roman', serif; font-size: 18px; font-weight: 800; letter-spacing: 0.6px; padding: 0 230px; }
        .number { position: absolute; right: 0; bottom: 0; font-size: 13px; white-space: nowrap; }
        .line { display: inline-block; min-width: 190px; border-bottom: 1px solid #111827; text-align: center; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ris-table th, .ris-table td {
            border: 1px solid #1f2937;
            height: 36px;
            padding: 6px 5px;
            font-size: 12px;
            vertical-align: middle;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .ris-table th { text-align: center; font-weight: 700; line-height: 1.15; }
        .item-col { width: 18%; }
        .brand-col { width: 12%; }
        .unit-col { width: 8%; }
        .supplier-col { width: 16%; }
        .qty-group { width: 20%; }
        .qty-col { width: 10%; font-size: 11px; white-space: normal; }
        .cost-col { width: 13%; }
        .amount-col { width: 13%; }
        .purpose { margin-top: 8px; font-size: 15px; font-weight: 700; }
        .purpose-row-1 { display: flex; align-items: flex-end; gap: 12px; }
        .purpose-label { flex-shrink: 0; line-height: 28px; }
        .purpose-line {
            flex: 1;
            min-height: 28px;
            border-bottom: 1px solid #6b7280;
            font-weight: 400;
            line-height: 28px;
            white-space: nowrap;
            overflow: hidden;
        }
        .purpose-line-2 { display: block; width: 100%; margin-top: 8px; flex: none; }
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
        .signature-name { font-size: 11px; text-transform: none; letter-spacing: 0; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-image {
            max-height: 38px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translate(-50%, -50%);
            top: 50%; bottom: auto;
            z-index: 10;
            pointer-events: none;
            object-fit: contain;
            object-position: center center;
        }
        .signature-line .signature-name { display: block; position: relative; z-index: 1; line-height: 1.35; }
        .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }

        /* Modal / screen preview */
        @media screen {
            html, body {
                width: auto;
                min-height: 0;
                height: auto;
                overflow: visible;
                background: #fff;
            }
            html:not(.print-measuring) .ris-document {
                width: 210mm;
                height: auto;
                min-height: 0;
                max-height: none;
                padding: 0.2in 0.28in 0.18in;
                overflow: visible;
                background: #fff;
            }
            html:not(.print-measuring) .header {
                margin-top: 4px;
                margin-bottom: 10px;
                min-height: 72px;
            }
            html:not(.print-measuring) .school { font-size: 17px; }
            html:not(.print-measuring) .title { font-size: 17px; margin-top: 4px; }
            html:not(.print-measuring) .ris-table th,
            html:not(.print-measuring) .ris-table td {
                height: auto;
                min-height: 32px;
                padding: 6px 4px;
                font-size: 12px;
            }
            html:not(.print-measuring) .qty-col { font-size: 10px; }
            html:not(.print-measuring) .purpose { margin-top: 6px; font-size: 13px; }
            html:not(.print-measuring) .purpose-line { min-height: 20px; line-height: 20px; }
            html:not(.print-measuring) .purpose-line-2 { margin-top: 6px; }
            html:not(.print-measuring) .signatures { margin-top: 12px; gap: 14px; font-size: 12px; }
            html:not(.print-measuring) .signature-line { min-height: 26px; }
            html:not(.print-measuring) .date-row { margin-top: 6px; }
        }

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                background: white !important;
            }
            body { background: white; }
            .ris-document {
                width: 100% !important;
                max-width: 100% !important;
                min-height: 0 !important;
                height: auto !important;
                max-height: none;
                margin: 0;
                padding: 6mm 8mm;
                position: relative;
                overflow: visible !important;
            }
            .header { margin-top: 10px; margin-bottom: 6px; }
            .ris-table { width: 100% !important; }
            .ris-table th, .ris-table td {
                height: auto !important;
                min-height: 0 !important;
                padding: 3px 4px !important;
                font-size: 11px !important;
            }
            .signatures {
                margin-top: 10px;
                gap: 12px;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            @page { size: A4 landscape; margin: 8mm; }
        }
    </style>
    @include('partials.ris-signature-overlay-styles')
    @include('partials.print-fit-page')
    <script>window.installPrintFit('.ris-original-form');</script>
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
        ? ($adminIssuedName ?: 'Administrator')
        : ($hasCheckedSign && !$hasCheckedImage ? $rawApproved : '');

    $approvedDate = $hasPresidentSign ? $ris->ris_approved_by_date : null;
    $approvedName = $hasPresidentImage
        ? ($presidentName ?? 'President')
        : ($hasPresidentText ? $rawApproved : '');
@endphp

    <main class="ris-sheet-host">
        @include('partials.ris-purchaser-sheet', ['ris' => $ris, 'risItems' => $risItems])
    </main>
</body>
</html>
