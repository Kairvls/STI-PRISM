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
        .item-col { width: 26%; }
        .unit-col { width: 8%; }
        .qty-col { width: 10%; }
        .cost-col { width: 13%; }
        .amount-col { width: 15%; }
        .purpose { margin-top: 8px; display: grid; grid-template-columns: 130px 1fr; gap: 8px; font-size: 15px; font-weight: 700; }
        .purpose-lines { min-height: 58px; border-bottom: 1px solid #6b7280; line-height: 28px; font-weight: 400; }
        .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; }
        .signature-box { position: relative; }
        .signature-box p { margin: 0 0 6px; }
        .signature-line { position: relative; border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
        .signature-name-wrapper { position: relative; display: inline-block; width: 100%; text-align: center; }
        .signature-name { font-size: 11px; text-transform: none; letter-spacing: 0; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-name-wrapper .signature-image {
            max-height: 36px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: -8px;
            z-index: 10;
        }
        .signature-line .signature-image {
            max-height: 32px;
            max-width: 90%;
            width: auto;
            height: auto;
            object-fit: contain;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 12px;
            z-index: 10;
            pointer-events: none;
        }
        .signature-line .signature-name {
            display: block;
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
            overflow: hidden;
            background: #fff;
        }
        html.screen-preview .ris-document {
            width: 11in;
            min-height: 0 !important;
            height: auto !important;
            padding: 0.12in 0.28in 0.22in;
        }
        html.screen-preview .header {
            margin-top: 6px;
            margin-bottom: 8px;
        }
        html.screen-preview .signatures {
            margin-top: 12px;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        html.screen-preview .date-row {
            margin-top: 8px;
            margin-bottom: 0;
        }

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
                min-height: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0.2in !important;
                position: relative;
                overflow: visible !important;
            }
            .header { margin-top: 24px !important; margin-bottom: 10px !important; }
            .ris-table { width: 100% !important; }
            .signatures { margin-top: 16px !important; }
            @page { size: landscape; margin: 0.25in; }
        }
    </style>
    @include('partials.ris-signature-overlay-styles')
</head>
<body>
    <main>
        @include('partials.ris-document-paper', compact('ris', 'risItems', 'presidentName', 'isScreenPreview'))
    </main>
</body>
</html>
