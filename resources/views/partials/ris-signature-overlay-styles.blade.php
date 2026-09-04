<style>
    .signature-box,
    .ris-signature-column {
        overflow: visible !important;
    }
    .signature-line,
    .ris-signature-line {
        position: relative !important;
        overflow: visible !important;
    }
    .signature-line .signature-image,
    .ris-signature-line .signature-image,
    .ris-signature-input-wrap .signature-image {
        position: absolute;
        left: 50%;
        bottom: 6px;
        z-index: 10;
        max-height: 42px;
        max-width: 92%;
        width: auto;
        height: auto;
        transform: translateX(-50%);
        pointer-events: none;
        object-fit: contain;
    }
    .signature-line .signature-name,
    .ris-signature-line .signature-name {
        display: block;
        line-height: 20px;
        text-align: center;
        font-size: 11px;
        letter-spacing: 0;
        text-transform: none !important;
    }
    .ris-signature-input-wrap {
        position: relative;
    }
</style>
