{{-- Shrinks the printed page height to the form so short forms don't print with a blank lower half --}}
<script>
(function () {
    if (window.fitPrintPage) return;

    var MM_PER_PX = 25.4 / 96;
    var IN = 25.4;

    // Page width/height/margin in mm; padding must match each form's @media print padding.
    // ATP and RR stay on full A4 portrait: printer drivers center a shorter page on the sheet.
    var FORMS = [
        { selector: '.ris-document', width: 11 * IN, height: 8.5 * IN, margin: 0.25 * IN, padding: '0.2in' },
        { selector: '.ris-print-sheet', width: 11 * IN, height: 8.5 * IN, margin: 0.25 * IN, padding: null },
        { selector: '.rfc-print-sheet', width: 297, height: 210, margin: 8, padding: '10mm 14mm' }
    ];

    function formFor(sheet) {
        for (var i = 0; i < FORMS.length; i++) {
            if (sheet.matches(FORMS[i].selector)) return FORMS[i];
        }
        return null;
    }

    function applyImportant(el, styles) {
        var previous = {};
        Object.keys(styles).forEach(function (prop) {
            if (styles[prop] === null) return;
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

    // Measures in-flow children instead of the sheet so min-height and absolute watermarks are ignored.
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
        return bottom - top + (parseFloat(sheetStyle.paddingBottom) || 0) + (parseFloat(sheetStyle.borderBottomWidth) || 0);
    }

    window.fitPrintPage = function (sheet, options) {
        options = options || {};
        if (!sheet) return null;
        var form = formFor(sheet);
        if (!form) return null;

        var doc = sheet.ownerDocument;
        var root = doc.documentElement;

        root.classList.add('print-measuring');
        var restore = applyImportant(sheet, {
            'box-sizing': 'border-box',
            'width': (form.width - form.margin * 2) + 'mm',
            'max-width': 'none',
            'min-width': '0',
            'min-height': '0',
            'height': 'auto',
            'transform': 'none',
            'padding': form.padding
        });
        var heightPx = contentHeightPx(sheet);
        restore();
        root.classList.remove('print-measuring');

        var buffer = options.bufferMm != null ? options.bufferMm : 4;
        var pageHeight = Math.ceil(heightPx * MM_PER_PX + form.margin * 2 + buffer);
        if (!isFinite(pageHeight) || heightPx < 40) return null;
        pageHeight = Math.min(pageHeight, Math.ceil(form.height));

        var style = doc.getElementById('print-fit-page-style');
        if (!style) {
            style = doc.createElement('style');
            style.id = 'print-fit-page-style';
        }
        style.textContent = '@media print { @page { size: ' + form.width.toFixed(1) + 'mm ' + pageHeight + 'mm; margin: ' + form.margin.toFixed(2) + 'mm; } }';
        // Appended last so it overrides any earlier @page rule on the page.
        (options.host || doc.body).appendChild(style);
        return style;
    };

    window.installPrintFit = function (selector) {
        window.addEventListener('beforeprint', function () {
            var sheets = document.querySelectorAll(selector);
            if (sheets.length !== 1) return;
            window.fitPrintPage(sheets[0]);
        });
    };
})();
</script>
