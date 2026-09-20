{{-- Browser-print Receiving Reports from any Receiving Officer page --}}
<script>
    window.receivingBrowserPrintRr = function (reportId) {
        if (!reportId) return;

        var sheetId = 'rr-print-' + reportId;
        if (document.getElementById(sheetId) && typeof window.purchaserPrintSheet === 'function') {
            window.purchaserPrintSheet(sheetId, 'rr-print-active');
            return;
        }

        var existing = document.getElementById('receiving-browser-print-frame');
        if (existing) existing.remove();

        var iframe = document.createElement('iframe');
        iframe.id = 'receiving-browser-print-frame';
        iframe.setAttribute('aria-hidden', 'true');
        iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
        iframe.src = '/receiving/reports/' + reportId + '/print?embed=1&ts=' + Date.now();

        var cleaned = false;
        var cleanup = function () {
            if (cleaned) return;
            cleaned = true;
            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
            window.removeEventListener('focus', cleanup);
        };

        iframe.onload = function () {
            try {
                var win = iframe.contentWindow;
                if (!win) {
                    cleanup();
                    return;
                }
                // Allow Tailwind CDN / form layout to settle before opening the dialog.
                window.setTimeout(function () {
                    try {
                        win.focus();
                        win.print();
                    } catch (e) {
                        window.open('/receiving/reports/' + reportId + '/print', '_blank');
                    }
                    window.setTimeout(cleanup, 2000);
                    window.addEventListener('focus', cleanup);
                }, 350);
            } catch (e) {
                window.open('/receiving/reports/' + reportId + '/print', '_blank');
                cleanup();
            }
        };

        document.body.appendChild(iframe);
    };
</script>
