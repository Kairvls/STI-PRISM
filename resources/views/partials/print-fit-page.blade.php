{{--
  Procurement browser print: always A4 portrait, then shrink the sheet so
  signatures stay on page 1 whenever the form can fit (avoids a nearly-blank
  first page with the signature block alone on page 2).
--}}
<script>
(function () {
    if (window.fitPrintPage) return;

    var MM_PER_PX = 25.4 / 96;
    var A4_W = 210;
    var A4_H = 297;
    var DEFAULT_MARGIN = 8;
    var MIN_SCALE = 0.62;

    // All procurement papers print portrait A4. padding matches each form's print CSS.
    var FORMS = [
        { selector: '.ris-document', margin: 8, padding: '6mm 8mm', compact: true },
        { selector: '.ris-print-sheet', margin: 8, padding: '6mm 8mm', compact: true },
        { selector: '.rr-print-sheet', margin: 8, padding: '6mm 10mm', compact: true },
        { selector: '.liq-print-sheet', margin: 8, padding: '6mm 8mm', compact: true },
        { selector: '.atp-print-sheet', margin: 8, padding: '6mm 10mm', compact: true },
        { selector: '.rfc-print-sheet', margin: 8, padding: '8mm 10mm', compact: true },
        { selector: '.dv-print-sheet', margin: 8, padding: '8mm 10mm', compact: false },
        { selector: '.acc-paper', margin: 8, padding: '8mm 10mm', compact: false }
    ];

    function formFor(sheet) {
        for (var i = 0; i < FORMS.length; i++) {
            if (sheet.matches(FORMS[i].selector)) return FORMS[i];
        }
        return { selector: '*', margin: DEFAULT_MARGIN, padding: '8mm', compact: true };
    }

    function applyImportant(el, styles) {
        var previous = {};
        Object.keys(styles).forEach(function (prop) {
            if (styles[prop] === null || styles[prop] === undefined) return;
            previous[prop] = [el.style.getPropertyValue(prop), el.style.getPropertyPriority(prop)];
            el.style.setProperty(prop, styles[prop], 'important');
        });
        return function () {
            Object.keys(previous).forEach(function (prop) {
                if (previous[prop][0]) {
                    el.style.setProperty(prop, previous[prop][0], previous[prop][1]);
                } else {
                    el.style.removeProperty(prop);
                }
            });
        };
    }

    // Measures in-flow children so min-height / absolute watermarks are ignored.
    function contentHeightPx(sheet) {
        var win = sheet.ownerDocument.defaultView;
        var top = sheet.getBoundingClientRect().top;
        var bottom = top;
        Array.prototype.forEach.call(sheet.children, function (child) {
            var cs = win.getComputedStyle(child);
            if (cs.display === 'none' || cs.position === 'absolute' || cs.position === 'fixed') return;
            bottom = Math.max(bottom, child.getBoundingClientRect().bottom + (parseFloat(cs.marginBottom) || 0));
        });
        var sheetStyle = win.getComputedStyle(sheet);
        return bottom - top
            + (parseFloat(sheetStyle.paddingBottom) || 0)
            + (parseFloat(sheetStyle.borderBottomWidth) || 0);
    }

    function ensureStyle(doc, id) {
        var style = doc.getElementById(id);
        if (!style) {
            style = doc.createElement('style');
            style.id = id;
        }
        return style;
    }

    window.fitPrintPage = function (sheet, options) {
        options = options || {};
        if (!sheet) return null;

        var form = formFor(sheet);
        var doc = sheet.ownerDocument;
        var root = doc.documentElement;
        var margin = options.marginMm != null ? options.marginMm : (form.margin || DEFAULT_MARGIN);
        var usableW = A4_W - margin * 2;
        var usableH = A4_H - margin * 2;
        var host = options.host || doc.body;

        root.classList.add('print-measuring');
        var restore = applyImportant(sheet, {
            'box-sizing': 'border-box',
            'width': usableW + 'mm',
            'max-width': 'none',
            'min-width': '0',
            'min-height': '0',
            'height': 'auto',
            'transform': 'none',
            'zoom': '1',
            'padding': form.padding || null
        });
        var heightPx = contentHeightPx(sheet);
        restore();
        root.classList.remove('print-measuring');

        if (!isFinite(heightPx) || heightPx < 40) {
            return null;
        }

        var contentMm = heightPx * MM_PER_PX;
        var buffer = options.bufferMm != null ? options.bufferMm : 3;
        var scale = 1;
        if (contentMm + buffer > usableH) {
            scale = Math.max(MIN_SCALE, usableH / (contentMm + buffer));
        }

        var pageStyle = ensureStyle(doc, 'print-fit-page-style');
        var compactCss = form.compact
            ? (
                sheet.matches('.ris-document, .ris-print-sheet')
                    ? [
                        '.ris-document .ris-table th, .ris-document .ris-table td,',
                        '.ris-print-sheet table th, .ris-print-sheet table td,',
                        '.ris-document table th, .ris-document table td {',
                        '  height: auto !important; min-height: 0 !important;',
                        '  padding: 3px 4px !important; font-size: 11px !important;',
                        '}',
                        '.ris-document .signatures, .ris-print-sheet .ris-signatures, .ris-document .ris-signatures {',
                        '  margin-top: 10px !important; gap: 12px !important;',
                        '  page-break-inside: avoid !important; break-inside: avoid !important;',
                        '}',
                        '.ris-document .header, .ris-print-sheet .header { margin-top: 8px !important; margin-bottom: 6px !important; }',
                        '.ris-document .purpose, .ris-print-sheet .ris-purpose-area { margin-top: 8px !important; }'
                    ].join('')
                    : [
                        '.rr-print-sheet, .liq-print-sheet, .atp-print-sheet, .rfc-print-sheet {',
                        '  page-break-inside: auto !important;',
                        '}',
                        '.rr-print-sheet .signature-name-stack,',
                        '.liq-print-sheet [class*="signature"],',
                        '.atp-print-sheet [class*="signature"],',
                        '.rfc-print-sheet [class*="signature"] {',
                        '  page-break-inside: avoid !important; break-inside: avoid !important;',
                        '}'
                    ].join('')
            )
            : '';

        // Portrait A4 always. Scale the sheet when content would otherwise spill
        // the signature block onto a nearly empty second page.
        pageStyle.textContent = [
            '@media print {',
            '  @page { size: A4 portrait; margin: ' + margin + 'mm; }',
            '  html, body { width: auto !important; height: auto !important; }',
            compactCss,
            '}'
        ].join('\n');

        if (scale < 0.999) {
            pageStyle.textContent += [
                '@media print {',
                '  ' + form.selector + ', .purchaser-print-sheet, .accounting-print-sheet {',
                '    zoom: ' + scale.toFixed(4) + ' !important;',
                '    transform-origin: top left !important;',
                '  }',
                '}'
            ].join('\n');
        }

        host.appendChild(pageStyle);
        return pageStyle;
    };

    window.installPrintFit = function (selector) {
        window.addEventListener('beforeprint', function () {
            var sheets = document.querySelectorAll(selector);
            if (!sheets.length) return;
            // Prefer a single active sheet; otherwise fit the first match.
            var sheet = sheets.length === 1
                ? sheets[0]
                : (document.querySelector(selector + '.ris-print-active, ' + selector + '.rr-print-active, ' + selector + '.liq-print-active') || sheets[0]);
            window.fitPrintPage(sheet);
        });
    };
})();
</script>
