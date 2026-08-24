<script>
    (function () {
        function fitAccountingDocuments() {
            document.querySelectorAll('.acc-viewer').forEach(function (viewer) {
                var stage = viewer.querySelector('.acc-viewer-stage');
                var fit = viewer.querySelector('.acc-viewer-fit');
                if (!stage || !fit) return;

                fit.style.transform = 'none';
                fit.style.width = 'auto';
                fit.style.height = 'auto';
                fit.style.margin = '0';

                var sheet = fit.querySelector('.rfc-print-sheet, .liq-print-sheet, .acc-paper') || fit.firstElementChild;
                if (!sheet) return;

                var pad = 16;
                var availW = Math.max(140, stage.clientWidth - pad);
                var availH = Math.max(140, stage.clientHeight - pad);
                var docW = Math.max(sheet.scrollWidth, sheet.offsetWidth);
                var docH = Math.max(sheet.scrollHeight, sheet.offsetHeight);
                if (!docW || !docH) return;

                var scale = Math.min(availW / docW, availH / docH, 1);
                scale = Math.max(0.28, Math.round(scale * 1000) / 1000);

                fit.style.width = docW + 'px';
                fit.style.height = docH + 'px';
                fit.style.transform = 'scale(' + scale + ')';
                fit.style.margin = ((docH * (scale - 1)) / 2) + 'px ' + ((docW * (scale - 1)) / 2) + 'px';
            });
        }

        window.fitAccountingDocuments = fitAccountingDocuments;

        function scheduleFit() {
            requestAnimationFrame(function () {
                fitAccountingDocuments();
                setTimeout(fitAccountingDocuments, 80);
                setTimeout(fitAccountingDocuments, 320);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', scheduleFit);
        } else {
            scheduleFit();
        }

        window.addEventListener('resize', function () {
            clearTimeout(window.__accFitTimer);
            window.__accFitTimer = setTimeout(fitAccountingDocuments, 80);
        });

        if (window.lucide) {
            try { lucide.createIcons(); } catch (e) {}
        }
    })();
</script>
