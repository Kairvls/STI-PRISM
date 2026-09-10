@php
    $canvasId = $canvasId ?? 'signatureCanvas';
    $hiddenName = $hiddenName ?? 'signature_data';
    $hiddenId = $hiddenId ?? ($canvasId.'Data');
    $label = $label ?? 'Digital signature';
    $hint = $hint ?? 'Sign in the box. This is required.';
    $requiredMessage = $requiredMessage ?? 'Please sign before continuing.';
    $renderPad = $renderPad ?? true;
@endphp
@if ($renderPad)
<div class="signature-pad" data-required-message="{{ $requiredMessage }}">
    <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    <input type="hidden" name="{{ $hiddenName }}" id="{{ $hiddenId }}" value="">
    <canvas
        id="{{ $canvasId }}"
        class="signature-pad-canvas mt-2 w-full rounded-lg border border-slate-200 bg-white"
        width="520"
        height="160"
        tabindex="0"
        aria-label="Signature drawing area"
    ></canvas>
    <div class="mt-2 flex flex-wrap items-center gap-2">
        <button
            type="button"
            id="{{ $canvasId }}UndoBtn"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            onclick="window.undoSignaturePad('{{ $canvasId }}', '{{ $hiddenId }}')"
            disabled
        >Undo</button>
        <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700"
            onclick="window.clearSignaturePad('{{ $canvasId }}', '{{ $hiddenId }}')"
        >Clear</button>
    </div>
</div>
@endif
@once
<style>
    .signature-pad-canvas { height: 160px; touch-action: none; max-width: 100%; }
    @media print { .signature-pad { display: none !important; } }
</style>
<script>
    /**
     * Crop transparent padding so ink sits centered when the PNG overlays a name.
     */
    window.exportTrimmedSignatureDataUrl = function (canvas, pad) {
        if (!canvas) return '';
        pad = typeof pad === 'number' ? pad : 10;
        try {
            var ctx = canvas.getContext('2d', { willReadFrequently: true });
            var width = canvas.width;
            var height = canvas.height;
            var pixels = ctx.getImageData(0, 0, width, height).data;
            var minX = width;
            var minY = height;
            var maxX = -1;
            var maxY = -1;
            for (var y = 0; y < height; y++) {
                for (var x = 0; x < width; x++) {
                    if (pixels[(y * width + x) * 4 + 3] > 8) {
                        if (x < minX) minX = x;
                        if (y < minY) minY = y;
                        if (x > maxX) maxX = x;
                        if (y > maxY) maxY = y;
                    }
                }
            }
            if (maxX < 0) return '';
            minX = Math.max(0, minX - pad);
            minY = Math.max(0, minY - pad);
            maxX = Math.min(width - 1, maxX + pad);
            maxY = Math.min(height - 1, maxY + pad);
            var tw = maxX - minX + 1;
            var th = maxY - minY + 1;
            var out = document.createElement('canvas');
            out.width = tw;
            out.height = th;
            out.getContext('2d').drawImage(canvas, minX, minY, tw, th, 0, 0, tw, th);
            return out.toDataURL('image/png');
        } catch (e) {
            return canvas.toDataURL('image/png');
        }
    };

    window.trimSignatureDataUrl = function (dataUrl, pad) {
        return new Promise(function (resolve) {
            if (!dataUrl || String(dataUrl).indexOf('data:image/') !== 0) {
                resolve(dataUrl || '');
                return;
            }
            var img = new Image();
            img.onload = function () {
                try {
                    var canvas = document.createElement('canvas');
                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    canvas.getContext('2d').drawImage(img, 0, 0);
                    var trimmed = window.exportTrimmedSignatureDataUrl(canvas, pad);
                    resolve(trimmed || dataUrl);
                } catch (e) {
                    resolve(dataUrl);
                }
            };
            img.onerror = function () { resolve(dataUrl); };
            img.src = dataUrl;
        });
    };

    function signaturePadHistory(canvas) {
        if (!canvas._sigStrokeHistory) canvas._sigStrokeHistory = [];
        return canvas._sigStrokeHistory;
    }

    function syncSignaturePadUndoButton(canvasId) {
        var canvas = document.getElementById(canvasId);
        var btn = document.getElementById(canvasId + 'UndoBtn');
        if (!btn) return;
        var history = canvas ? signaturePadHistory(canvas) : [];
        btn.disabled = !history.length;
        btn.setAttribute('aria-disabled', history.length ? 'false' : 'true');
    }

    window.initSignaturePad = function (canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        if (canvas.dataset.padReady === '1') {
            syncSignaturePadUndoButton(canvasId);
            return;
        }
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1f2937';
        signaturePadHistory(canvas);
        let drawing = false, lastX = 0, lastY = 0, strokeStarted = false;

        function getPos(evt) {
            const rect = canvas.getBoundingClientRect();
            const clientX = evt.touches ? evt.touches[0].clientX : evt.clientX;
            const clientY = evt.touches ? evt.touches[0].clientY : evt.clientY;
            const width = rect.width || canvas.width;
            const height = rect.height || canvas.height;
            return {
                x: (clientX - rect.left) * (canvas.width / width),
                y: (clientY - rect.top) * (canvas.height / height)
            };
        }

        function pushStrokeSnapshot() {
            try {
                signaturePadHistory(canvas).push(ctx.getImageData(0, 0, canvas.width, canvas.height));
                // Cap history so memory stays reasonable.
                if (canvas._sigStrokeHistory.length > 40) {
                    canvas._sigStrokeHistory.shift();
                }
                syncSignaturePadUndoButton(canvasId);
            } catch (e) {
                // ignore snapshot failures
            }
        }

        function start(evt) {
            drawing = true;
            strokeStarted = false;
            const p = getPos(evt);
            lastX = p.x;
            lastY = p.y;
        }

        function move(evt) {
            if (!drawing) return;
            if (!strokeStarted) {
                pushStrokeSnapshot();
                strokeStarted = true;
            }
            const p = getPos(evt);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            lastX = p.x;
            lastY = p.y;
        }

        function end() {
            drawing = false;
            strokeStarted = false;
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', function (e) { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', function (e) { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', end);
        canvas.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && String(e.key).toLowerCase() === 'z') {
                e.preventDefault();
                window.undoSignaturePad(canvasId);
            }
        });

        canvas.dataset.padReady = '1';
        syncSignaturePadUndoButton(canvasId);
    };

    window.undoSignaturePad = function (canvasId, hiddenId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return false;
        const history = signaturePadHistory(canvas);
        if (!history.length) {
            syncSignaturePadUndoButton(canvasId);
            return false;
        }
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const snapshot = history.pop();
        ctx.putImageData(snapshot, 0, 0);
        const hidden = hiddenId ? document.getElementById(hiddenId) : null;
        if (hidden) hidden.value = '';
        syncSignaturePadUndoButton(canvasId);
        return true;
    };

    window.clearSignaturePad = function (canvasId, hiddenId) {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            canvas._sigStrokeHistory = [];
        }
        const hidden = document.getElementById(hiddenId);
        if (hidden) hidden.value = '';
        syncSignaturePadUndoButton(canvasId);
    };

    window.requireSignaturePad = function (canvasId, hiddenId, message) {
        const canvas = document.getElementById(canvasId);
        const hidden = document.getElementById(hiddenId);
        if (!canvas || !hidden) return true;
        const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
        let hasDrawing = false;
        for (let i = 3; i < pixels.length; i += 4) {
            if (pixels[i] > 0) { hasDrawing = true; break; }
        }
        if (!hasDrawing) {
            alert(message || 'Please sign before continuing.');
            return false;
        }
        hidden.value = window.exportTrimmedSignatureDataUrl(canvas) || canvas.toDataURL('image/png');
        return true;
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.signature-pad-canvas').forEach(function (canvas) {
            window.initSignaturePad(canvas.id);
        });
    });
</script>
@endonce
