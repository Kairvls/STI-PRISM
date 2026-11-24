<style>
    html, body { margin: 0; padding: 0; background: #fff; }
    @media screen {
        html, body,
        html.screen-preview,
        html.screen-preview body,
        html.screen-preview main {
            width: auto !important;
            max-width: none !important;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
        }
    }
    .ris-original-form {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        min-height: 0;
        box-sizing: border-box;
        border: 2px solid #1f2937;
        padding: 24px 22px 22px;
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        background: #fff;
    }
    .ris-document-header { position: relative; height: 120px; text-align: center; }
    .ris-school-name { font-size: 19px; line-height: 1.2; font-weight: 700; }
    .ris-document-title { margin-top: 9px; font-size: 15px; line-height: 1.2; font-weight: 700; }
    .ris-number-area {
        position: absolute;
        right: 0;
        bottom: 18px;
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }
    .ris-number-label { font-size: 15px; font-weight: 600; }
    .ris-number-line { display: block; width: 220px; border-bottom: 1px solid #1f2937; }
    .ris-items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .ris-items-table th, .ris-items-table td { border: 1px solid #1f2937; }
    .ris-items-table th {
        padding: 9px 5px;
        vertical-align: middle;
        text-align: center;
        font-size: 12px;
        line-height: 1.2;
        font-weight: 700;
    }
    .ris-items-table tbody td { height: 45px; padding: 4px 6px; font-size: 12px; vertical-align: middle; overflow-wrap: anywhere; }
    .ris-item-column { width: 20%; }
    .ris-brand-column { width: 10%; }
    .ris-unit-column { width: 7%; }
    .ris-supplier-column { width: 14%; }
    .ris-quantity-header { width: 20%; }
    .ris-requested-column, .ris-issued-column { width: 10%; font-size: 11px; white-space: nowrap; }
    .ris-unit-cost-column, .ris-amount-column { width: 15%; }
    .ris-purpose-area { margin-top: 31px; }
    .ris-purpose-lined-wrap { position: relative; }
    .ris-purpose-lined-wrap .ris-purpose-label {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 1;
        padding-right: 12px;
        background: #fff;
        font-size: 13px;
        font-weight: 700;
        line-height: 31px;
    }
    .ris-purpose-input {
        display: block;
        width: 100%;
        min-height: 62px;
        max-height: 62px;
        height: 62px;
        padding: 0;
        font-size: 12px;
        line-height: 31px;
        text-indent: 5.75rem;
        text-align: left;
        white-space: pre-wrap;
        overflow: hidden;
        border: 0;
        background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 30px, #1f2937 30px, #1f2937 31px);
        background-color: transparent;
        color: #000;
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
        min-height: 1.75rem;
        border-bottom: 1px solid #1f2937;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .ris-date-label { margin-top: 16px; font-size: 12px; color: #374151; }
    .ris-date-line { height: 31px; border-bottom: 1px solid #1f2937; }
    .ris-value-line {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 1.75rem;
        padding: 0.25rem 6px;
        font-size: 12px;
        line-height: 1.35;
    }
    .ris-number-line.ris-value-line { min-height: 24px; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .ris-signature-line { position: relative; overflow: visible; }
    .ris-signature-line img.signature-image {
        position: absolute;
        left: 50%;
        top: 50%;
        z-index: 10;
        max-height: 38px;
        max-width: 92%;
        width: auto;
        height: auto;
        transform: translate(-50%, -50%);
        pointer-events: none;
        object-fit: contain;
    }
    .ris-signature-line .signature-name {
        display: block;
        position: relative;
        z-index: 1;
        width: 100%;
        line-height: 1.35;
        text-align: center;
        font-size: 11px;
    }
    @media print {
        @page { size: A4 landscape; margin: 6mm; }
        html, body {
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
        }
        /* Keep the on-screen sheet and scale it onto one landscape page. */
        .ris-original-form {
            width: 1095px !important;
            max-width: none !important;
            min-height: 845px !important;
            height: auto !important;
            zoom: 0.85;
            box-shadow: none !important;
            overflow: visible !important;
        }
    }
</style>
