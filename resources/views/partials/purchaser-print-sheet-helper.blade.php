{{-- Clone a form sheet out of hidden modals so browser print is not blank --}}
<script>
    window.purchaserPrintSheet = function (sheetId, activeClass) {
        var sheet = document.getElementById(sheetId);
        if (!sheet) return;

        document.querySelectorAll('.' + activeClass).forEach(function (el) {
            el.classList.remove(activeClass);
        });

        var existing = document.getElementById('purchaser-print-mount');
        if (existing) existing.remove();

        var mount = document.createElement('div');
        mount.id = 'purchaser-print-mount';
        mount.setAttribute('aria-hidden', 'true');
        mount.className = 'purchaser-print-mount';

        var clone = sheet.cloneNode(true);
        clone.removeAttribute('id');
        clone.classList.add(activeClass);
        clone.classList.add('purchaser-print-sheet');
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
    /* Keep full layout size off-screen so print preview measures correctly */
    #purchaser-print-mount {
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

    #purchaser-print-mount .rfc-print-sheet,
    #purchaser-print-mount .liq-print-sheet {
        width: 297mm !important;
    }

    @media print {
        html, body {
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        /* Hide app chrome; keep only the cloned form (preserves flex/grid) */
        body > *:not(#purchaser-print-mount) {
            display: none !important;
        }

        #purchaser-print-mount {
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

        #purchaser-print-mount .purchaser-print-sheet,
        #purchaser-print-mount .atp-print-sheet,
        #purchaser-print-mount .rfc-print-sheet,
        #purchaser-print-mount .rr-print-sheet,
        #purchaser-print-mount .liq-print-sheet,
        #purchaser-print-mount .ris-print-sheet {
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
        }

        #purchaser-print-mount .atp-print-sheet,
        #purchaser-print-mount .rr-print-sheet {
            padding: 12mm !important;
            background: #fff !important;
        }

        #purchaser-print-mount .rfc-print-sheet {
            padding: 10mm 14mm !important;
        }

        #purchaser-print-mount .liq-print-sheet {
            padding: 8mm 10mm !important;
            background: #fff !important;
        }

        .print-hidden {
            display: none !important;
        }
    }
</style>
