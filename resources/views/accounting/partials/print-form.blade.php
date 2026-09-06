{{-- Accounting form print: clone only the paper sheet (same approach as purchaser) --}}
<script>
    window.accountingPrintForm = function (options) {
        options = options || {};
        var sheet = null;

        if (options.sheetId) {
            sheet = document.getElementById(options.sheetId);
        }
        if (!sheet) {
            sheet = document.querySelector(
                '.acc-viewer-fit .atp-print-sheet, ' +
                '.acc-viewer-fit .rfc-print-sheet, ' +
                '.acc-viewer-fit .rr-print-sheet, ' +
                '.acc-viewer-fit .liq-print-sheet, ' +
                '.acc-viewer-fit .ris-document, ' +
                '.acc-viewer-fit .ris-print-sheet, ' +
                '.acc-viewer-fit .acc-paper'
            );
        }
        if (!sheet) return;

        var existing = document.getElementById('accounting-print-mount');
        if (existing) existing.remove();

        var mount = document.createElement('div');
        mount.id = 'accounting-print-mount';
        mount.setAttribute('aria-hidden', 'true');
        mount.className = 'accounting-print-mount';
        if (options.page) {
            mount.setAttribute('data-page', options.page);
        }

        // Match purchaser page orientation per form type.
        var pageStyle = document.getElementById('accounting-print-page-style');
        if (!pageStyle) {
            pageStyle = document.createElement('style');
            pageStyle.id = 'accounting-print-page-style';
            document.head.appendChild(pageStyle);
        }
        var pageSize = options.page === 'landscape' ? 'A4 landscape' : 'A4 portrait';
        if (sheet.classList.contains('ris-document') || sheet.classList.contains('ris-print-sheet')) {
            pageSize = 'landscape';
        } else if (sheet.classList.contains('rfc-print-sheet') || sheet.classList.contains('liq-print-sheet')) {
            pageSize = 'A4 landscape';
        } else if (sheet.classList.contains('atp-print-sheet') || sheet.classList.contains('rr-print-sheet')) {
            pageSize = 'A4 portrait';
        }
        pageStyle.textContent = '@media print { @page { size: ' + pageSize + '; margin: 8mm; } }';

        var clone = sheet.cloneNode(true);
        clone.removeAttribute('id');
        clone.classList.add('accounting-print-sheet');
        clone.classList.remove('shadow');
        mount.appendChild(clone);
        document.body.appendChild(mount);

        var cleaned = false;
        var cleanup = function () {
            if (cleaned) return;
            cleaned = true;
            if (mount.parentNode) mount.parentNode.removeChild(mount);
            window.removeEventListener('afterprint', cleanup);
        };

        window.addEventListener('afterprint', cleanup);
        window.print();
        window.setTimeout(cleanup, 1500);
    };
</script>
<style>
    #accounting-print-mount {
        position: fixed !important;
        left: -100vw !important;
        top: 0 !important;
        width: 210mm !important;
        height: auto !important;
        overflow: visible !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }

    #accounting-print-mount[data-page="landscape"],
    #accounting-print-mount:has(.rfc-print-sheet),
    #accounting-print-mount:has(.liq-print-sheet),
    #accounting-print-mount:has(.ris-document),
    #accounting-print-mount:has(.ris-print-sheet) {
        width: 297mm !important;
    }

    #accounting-print-mount .rfc-print-sheet,
    #accounting-print-mount .liq-print-sheet {
        width: 297mm !important;
        min-height: 0 !important;
        height: auto !important;
    }

    #accounting-print-mount .ris-document,
    #accounting-print-mount .ris-print-sheet {
        width: 11in !important;
        min-height: 0 !important;
        height: auto !important;
    }

    @media print {
        @page {
            margin: 8mm;
        }

        html, body {
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        body > *:not(#accounting-print-mount) {
            display: none !important;
        }

        #accounting-print-mount {
            display: block !important;
            position: static !important;
            left: auto !important;
            top: auto !important;
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            opacity: 1 !important;
            z-index: auto !important;
            background: #fff !important;
        }

        #accounting-print-mount[data-page="portrait"] {
            /* ATP / default */
        }

        #accounting-print-mount .accounting-print-sheet,
        #accounting-print-mount .atp-print-sheet,
        #accounting-print-mount .rfc-print-sheet,
        #accounting-print-mount .rr-print-sheet,
        #accounting-print-mount .liq-print-sheet,
        #accounting-print-mount .ris-document,
        #accounting-print-mount .ris-print-sheet,
        #accounting-print-mount .acc-paper {
            display: block !important;
            position: static !important;
            left: auto !important;
            top: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            min-height: 0 !important;
            height: auto !important;
            margin: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
            background: #fff !important;
            transform: none !important;
        }

        #accounting-print-mount .atp-print-sheet,
        #accounting-print-mount .rr-print-sheet {
            padding: 12mm !important;
        }

        #accounting-print-mount .rfc-print-sheet {
            padding: 10mm 14mm !important;
        }

        #accounting-print-mount .liq-print-sheet {
            padding: 8mm 10mm !important;
        }

        #accounting-print-mount .ris-document {
            padding: 0.35in !important;
            width: 100% !important;
        }

        .approval-watermark,
        .print-hidden {
            display: none !important;
        }
    }
</style>
