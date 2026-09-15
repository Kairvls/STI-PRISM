<style>
    .signature-box,
    .ris-signature-column {
        overflow: visible !important;
    }

    /* Name line: room for underline, name stays vertically centered in the line */
    .signature-line,
    .ris-signature-line,
    .ris-signature-input-wrap,
    .signature-name-wrapper {
        position: relative !important;
        overflow: visible !important;
        min-height: 1.75rem;
        height: auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /*
     * Stack wraps ONLY the full name (+ overlay). Signature is centered on this box,
     * which is sized by the printed name / name input — not the tall underline row.
     */
    .signature-name-stack {
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        max-width: 100%;
        min-height: 0 !important;
        height: auto !important;
        line-height: 1.35;
        vertical-align: middle;
    }

    .signature-line .signature-image,
    .ris-signature-line .signature-image,
    .ris-signature-input-wrap .signature-image,
    .signature-name-wrapper .signature-image,
    .signature-name-stack .signature-image,
    img.signature-image {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        right: auto !important;
        bottom: auto !important;
        z-index: 10 !important;
        max-height: 38px !important;
        max-width: 92% !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        transform: translate(-50%, -50%) !important;
        pointer-events: none !important;
        object-fit: contain !important;
        object-position: center center !important;
    }

    .signature-line .signature-name,
    .ris-signature-line .signature-name,
    .signature-name-wrapper .signature-name,
    .signature-name-stack .signature-name,
    span.signature-name {
        position: relative;
        z-index: 1;
        display: block;
        line-height: 1.35;
        text-align: center;
        font-size: 11px;
        letter-spacing: 0;
        text-transform: none !important;
    }

    .signature-name-stack > input,
    .signature-name-stack > .ris-signature-input {
        position: relative;
        z-index: 1;
        width: 100%;
        min-width: 8rem;
        background: transparent;
        text-align: center;
    }
</style>
@once
<script>
    (function () {
        function inkBoundsFromImageData(imageData) {
            var data = imageData.data;
            var width = imageData.width;
            var height = imageData.height;
            var minX = width;
            var minY = height;
            var maxX = -1;
            var maxY = -1;
            for (var y = 0; y < height; y++) {
                for (var x = 0; x < width; x++) {
                    if (data[(y * width + x) * 4 + 3] > 8) {
                        if (x < minX) minX = x;
                        if (y < minY) minY = y;
                        if (x > maxX) maxX = x;
                        if (y > maxY) maxY = y;
                    }
                }
            }
            if (maxX < 0) return null;
            return { minX: minX, minY: minY, maxX: maxX, maxY: maxY, width: width, height: height };
        }

        function trimLoadedImage(img, pad) {
            pad = typeof pad === 'number' ? pad : 10;
            try {
                var w = img.naturalWidth || img.width;
                var h = img.naturalHeight || img.height;
                if (!w || !h) return;
                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                var ctx = canvas.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(img, 0, 0);
                var bounds = inkBoundsFromImageData(ctx.getImageData(0, 0, w, h));
                if (!bounds) return;
                var minX = Math.max(0, bounds.minX - pad);
                var minY = Math.max(0, bounds.minY - pad);
                var maxX = Math.min(w - 1, bounds.maxX + pad);
                var maxY = Math.min(h - 1, bounds.maxY + pad);
                var tw = maxX - minX + 1;
                var th = maxY - minY + 1;
                if (minX <= pad + 2 && minY <= pad + 2 && (w - 1 - maxX) <= pad + 2 && (h - 1 - maxY) <= pad + 2) {
                    return;
                }
                var out = document.createElement('canvas');
                out.width = tw;
                out.height = th;
                out.getContext('2d').drawImage(canvas, minX, minY, tw, th, 0, 0, tw, th);
                img.dataset.sigTrimmed = '1';
                img.src = out.toDataURL('image/png');
            } catch (e) {
                // ignore
            }
        }

        function findFullNameTarget(img) {
            if (!img || !img.parentElement) return null;
            var stack = img.closest('.signature-name-stack');
            if (stack) {
                return stack.querySelector(
                    '.signature-name, #accSigPrintedName, #accPaperSigPrintedName, input, .ris-signature-input, [contenteditable="true"]'
                );
            }
            var parent = img.parentElement;
            return parent.querySelector(
                '.signature-name, #accSigPrintedName, #accPaperSigPrintedName, input.ris-signature-input, input[type="text"], input:not([type="hidden"])'
            );
        }

        /**
         * Pin signature image center to the full-name text/input center.
         */
        window.pinSignatureToFullName = function (img) {
            if (!img || img.style.display === 'none') return;
            var nameEl = findFullNameTarget(img);
            var stack = img.closest('.signature-name-stack');
            var parent = stack || img.offsetParent || img.parentElement;
            if (!nameEl || !parent) return;

            var parentRect = parent.getBoundingClientRect();
            var nameRect = nameEl.getBoundingClientRect();
            if (!nameRect.width && !nameRect.height) return;

            var centerX = (nameRect.left + nameRect.width / 2) - parentRect.left;
            var centerY = (nameRect.top + nameRect.height / 2) - parentRect.top;

            img.style.setProperty('position', 'absolute', 'important');
            img.style.setProperty('left', centerX + 'px', 'important');
            img.style.setProperty('top', centerY + 'px', 'important');
            img.style.setProperty('right', 'auto', 'important');
            img.style.setProperty('bottom', 'auto', 'important');
            img.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
            img.style.setProperty('margin', '0', 'important');
        };

        window.centerTrimSignatureOverlayImage = function (img) {
            if (!img || img.dataset.sigTrimmed === '1' || img.dataset.sigTrimPending === '1') {
                window.pinSignatureToFullName(img);
                return;
            }
            var src = img.getAttribute('src') || '';
            if (!src || src.indexOf('data:image/') !== 0) {
                window.pinSignatureToFullName(img);
                return;
            }
            img.dataset.sigTrimPending = '1';
            var after = function () {
                img.dataset.sigTrimPending = '0';
                window.pinSignatureToFullName(img);
            };
            if (img.complete && (img.naturalWidth || img.width)) {
                trimLoadedImage(img, 10);
                after();
                return;
            }
            img.addEventListener('load', function onLoad() {
                img.removeEventListener('load', onLoad);
                trimLoadedImage(img, 10);
                after();
            });
        };

        function scanSignatureOverlays(root) {
            (root || document).querySelectorAll('img.signature-image').forEach(function (img) {
                window.centerTrimSignatureOverlayImage(img);
                window.pinSignatureToFullName(img);
            });
        }

        window.scanSignatureOverlayTrim = scanSignatureOverlays;
        window.scanSignatureNamePins = function () {
            document.querySelectorAll('img.signature-image').forEach(function (img) {
                window.pinSignatureToFullName(img);
            });
        };

        function boot() {
            scanSignatureOverlays(document);
            window.addEventListener('resize', window.scanSignatureNamePins);
            window.addEventListener('load', window.scanSignatureNamePins);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }

        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                var needsScan = false;
                mutations.forEach(function (m) {
                    if (m.type === 'attributes' && m.target && m.target.matches && m.target.matches('img.signature-image')) {
                        if (m.attributeName === 'src' && m.target.dataset.sigTrimmed !== '1') {
                            m.target.dataset.sigTrimmed = '';
                            window.centerTrimSignatureOverlayImage(m.target);
                        } else if (m.attributeName === 'style' || m.attributeName === 'class') {
                            window.pinSignatureToFullName(m.target);
                        }
                    }
                    m.addedNodes && m.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        needsScan = true;
                    });
                });
                if (needsScan) scanSignatureOverlays(document);
            });
            observer.observe(document.documentElement, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['src', 'style', 'class']
            });
        }
    })();
</script>
@endonce
