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
        .header { position: relative; margin-top: 24px; margin-bottom: 10px; text-align: center; }
        .school { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; padding-right: 12rem; }
        .title { margin-top: 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: 800; letter-spacing: 1px; padding: 0 0.5rem; }
        .number {
            position: absolute;
            right: 0;
            top: 0;
            bottom: auto;
            font-size: 14px;
            white-space: nowrap;
            max-width: 46%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .line { display: inline-block; min-width: 180px; max-width: 100%; border-bottom: 1px solid #111827; text-align: center; }
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
            text-overflow: ellipsis;
            word-break: break-all;
        }
        .purpose-line-2 { display: block; width: 100%; margin-top: 8px; flex: none; }
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
        html.print-measuring .header { margin-top: 24px; margin-bottom: 8px; }
        html.print-measuring .signatures { margin-top: 16px; }
    </style>
    @include('partials.ris-signature-overlay-styles')
    @include('partials.print-fit-page')
    <script>window.installPrintFit('.ris-original-form');</script>
</head>

<body>
    <main>
        @include('partials.ris-purchaser-sheet', ['ris' => $ris, 'risItems' => $risItems ?? ($ris->risItems ?? collect())])
    </main>
@if (request()->boolean('auto'))
<script>
    window.__sharedPrintStarted = true;
    window.addEventListener('load', function () {
        setTimeout(function () {
            try { window.focus(); window.print(); } catch (e) {}
        }, 280);
    });
</script>
@endif
</body>
</html>
