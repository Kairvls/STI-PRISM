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
        .qty-col { width: 9%; }
        .cost-col { width: 12%; }
        .amount-col { width: 14%; }
        .purpose { margin-top: 8px; display: grid; grid-template-columns: 130px 1fr; gap: 8px; font-size: 15px; font-weight: 700; }
        .purpose-lines { min-height: 58px; border-bottom: 1px solid #6b7280; line-height: 28px; font-weight: 400; }
        .signatures { margin-top: 28px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; font-size: 14px; }
        .signature-box { position: relative; }
        .signature-box p { margin: 0 0 6px; }
        .signature-line { position: relative; border-bottom: 1px solid #111827; min-height: 20px; text-align: center; font-size: 12px; }
        .signature-name-wrapper { position: relative; display: inline-block; width: 100%; text-align: center; }
        .signature-name { font-size: 11px; text-transform: none; letter-spacing: 0; }
        .signature-position { font-size: 10px; color: #4b5563; margin-top: 1px; }
        .signature-name-wrapper .signature-image,
        .signature-line .signature-image {
            max-height: 36px;
            width: auto;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            margin-bottom: -8px;
            z-index: 10;
            pointer-events: none;
        }
        .signature-line .signature-image { max-height: 32px; max-width: 90%; bottom: 12px; }
        .signature-line .signature-name { display: block; line-height: 20px; }
        .date-row { margin-top: 12px; display: grid; grid-template-columns: 40px 1fr; gap: 6px; align-items: end; }
    </style>
    @endpush
@endonce

@include('partials.ris-signature-overlay-styles')
@include('partials.ris-document-paper', compact('ris', 'risItems', 'presidentName', 'isScreenPreview'))
