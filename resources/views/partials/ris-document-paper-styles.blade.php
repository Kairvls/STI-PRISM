@once
    @push('head')
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        .ris-document {
            width: 11in;
            min-height: 8.5in;
            padding: 0.35in;
            background: white;
            position: relative;
        }
        @if (!empty($isScreenPreview))
        .ris-document {
            width: 100%;
            max-width: 960px;
            min-height: 0;
            padding: 0.28in 0.24in;
            margin: 0 auto;
        }
        .ris-document .school { font-size: 17px; }
        .ris-document .title { font-size: 18px; }
        .ris-document .number { font-size: 12px; }
        .ris-document .line { min-width: 150px; }
        .ris-document .ris-table th,
        .ris-document .ris-table td {
            height: 36px;
            min-height: 36px;
            padding: 8px 6px;
            font-size: 11px;
            vertical-align: middle;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .ris-document .ris-table tbody td:empty::before {
            content: "\00a0";
        }
        .ris-document .signatures {
            gap: 12px;
            font-size: 12px;
        }
        .ris-document .purpose { font-size: 13px; }
        @endif
        .header { position: relative; margin-top: 24px; margin-bottom: 10px; text-align: center; }
        .school { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .title { margin-top: 8px; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: 800; letter-spacing: 1px; }
        .number { position: absolute; right: 0; bottom: -4px; font-size: 14px; }
        .line { display: inline-block; min-width: 220px; border-bottom: 1px solid #111827; text-align: center; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ris-table th, .ris-table td {
            border: 2px solid #374151;
            height: 36px;
            min-height: 36px;
            padding: 8px 6px;
            font-size: 13px;
            vertical-align: middle;
        }
        .ris-table tbody td:empty::before {
            content: "\00a0";
        }
        .ris-table th { text-align: center; font-weight: 700; }
        .item-col { width: 20%; }
        .brand-col { width: 10%; }
        .unit-col { width: 7%; }
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
        }
        .purpose-line-2 { display: block; width: 100%; margin-top: 8px; flex: none; }
        .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; }
        .signature-box { position: relative; }
        .signature-box p { margin: 0 0 6px; }
        .signature-line { position: relative; border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
        .signature-name-wrapper { position: relative; display: inline-block; width: 100%; text-align: center; }
        .signature-name { font-size: 11px; text-transform: none; letter-spacing: 0; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-name-wrapper .signature-image,
        .signature-line .signature-image {
            max-height: 38px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translate(-50%, -50%);
            top: 50%; bottom: auto;
            margin-bottom: 0;
            z-index: 10;
            pointer-events: none;
            object-fit: contain;
            object-position: center center;
        }
        .signature-line .signature-image { max-height: 38px; max-width: 90%; top: 50%; bottom: auto; }
        .signature-line .signature-name { display: block; position: relative; z-index: 1; line-height: 1.35; }
        .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }

        @media print {
            .ris-document {
                width: 11in !important;
                max-width: none !important;
                min-height: 8.5in !important;
                padding: 0.35in !important;
                margin: 0 !important;
            }
            .ris-document .school { font-size: 20px !important; }
            .ris-document .title { font-size: 22px !important; }
            .ris-document .number { font-size: 14px !important; }
            .ris-document .line { min-width: 220px !important; }
            .ris-document .ris-table th,
            .ris-document .ris-table td {
                height: 36px !important;
                min-height: 36px !important;
                padding: 8px 6px !important;
                font-size: 13px !important;
                vertical-align: middle !important;
            }
            .ris-document .signatures { gap: 24px !important; font-size: 14px !important; }
            .ris-document .purpose { font-size: 15px !important; }
        }
    </style>
    @endpush
@endonce

@include('partials.ris-signature-overlay-styles')
@include('partials.ris-document-paper', compact('ris', 'risItems', 'presidentName', 'isScreenPreview'))
