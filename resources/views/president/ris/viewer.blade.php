<!DOCTYPE html>
<html lang="en" class="{{ !empty($isScreenPreview) ? 'screen-preview' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RIS Preview</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; padding: 0; background: transparent; }
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
        .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; }
        .signature-box { position: relative; }
        .signature-box p { margin: 0 0 6px; }
        .signature-line { position: relative; border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
        .signature-name-wrapper { position: relative; display: inline-block; width: 100%; text-align: center; }
        .signature-name { font-size: 11px; text-transform: none; letter-spacing: 0; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-name-wrapper .signature-image {
            max-height: 38px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translate(-50%, -50%);
            top: 50%; bottom: auto;
            margin-bottom: 0;
            z-index: 10;
            object-fit: contain;
            object-position: center center;
        }
        .signature-line .signature-image {
            max-height: 38px;
            max-width: 90%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center center;
            position: absolute;
            left: 50%;
            transform: translate(-50%, -50%);
            top: 50%; bottom: auto;
            z-index: 10;
            pointer-events: none;
        }
        .signature-line .signature-name {
            display: block;
            position: relative;
            z-index: 1;
            line-height: 20px;
        }
        .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }

        html.screen-preview,
        html.screen-preview body,
        html.screen-preview main {
            margin: 0;
            padding: 0;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible;
            background: #fff;
        }
        html.screen-preview .ris-document {
            width: 11in;
            min-height: 0 !important;
            height: auto !important;
            padding: 0.12in 0.28in 0.22in;
        }
        html.screen-preview:not(.print-measuring) .header {
            margin-top: 6px;
            margin-bottom: 8px;
        }
        html.screen-preview:not(.print-measuring) .signatures {
            margin-top: 12px;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        html.screen-preview:not(.print-measuring) .date-row {
            margin-top: 8px;
            margin-bottom: 0;
        }
        html.print-measuring .header { margin-top: 24px; margin-bottom: 10px; }
        html.print-measuring .signatures { margin-top: 16px; }

        @media print {
            html, body, main {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                background: white !important;
            }
            html.screen-preview,
            html.screen-preview body,
            html.screen-preview main {
                overflow: visible !important;
                height: auto !important;
                min-height: 0 !important;
            }
            .ris-document {
                width: 100% !important;
                max-width: 100% !important;
                min-height: 0 !important;
                height: auto !important;
                margin: 0 !important;
                padding: 6mm 8mm !important;
                position: relative;
                overflow: visible !important;
            }
            .header { margin-top: 10px !important; margin-bottom: 6px !important; }
            .ris-table { width: 100% !important; }
            .ris-table th, .ris-table td {
                height: auto !important;
                min-height: 0 !important;
                padding: 3px 4px !important;
                font-size: 11px !important;
            }
            .signatures {
                margin-top: 10px !important;
                gap: 12px !important;
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
    <main>
        @include('partials.ris-purchaser-sheet', ['ris' => $ris, 'risItems' => $risItems])
    </main>
</body>
</html>
