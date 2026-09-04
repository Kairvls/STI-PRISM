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
    ></canvas>
    <button type="button" class="mt-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700" onclick="window.clearSignaturePad('{{ $canvasId }}', '{{ $hiddenId }}')">Clear</button>
</div>
@endif
@once
<style>
    .signature-pad-canvas { height: 160px; touch-action: none; max-width: 100%; }
    @media print { .signature-pad { display: none !important; } }
</style>
<script>
    window.initSignaturePad = function (canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || canvas.dataset.padReady === '1') return;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1f2937';
        let drawing = false, lastX = 0, lastY = 0;
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
        function start(evt) { drawing = true; const p = getPos(evt); lastX = p.x; lastY = p.y; }
        function move(evt) {
            if (!drawing) return;
            const p = getPos(evt);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            lastX = p.x;
            lastY = p.y;
        }
        function end() { drawing = false; }
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', function (e) { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', function (e) { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', end);
        canvas.dataset.padReady = '1';
    };
    window.clearSignaturePad = function (canvasId, hiddenId) {
        const canvas = document.getElementById(canvasId);
        if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        const hidden = document.getElementById(hiddenId);
        if (hidden) hidden.value = '';
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
        hidden.value = canvas.toDataURL('image/png');
        return true;
    };
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.signature-pad-canvas').forEach(function (canvas) {
            window.initSignaturePad(canvas.id);
        });
    });
</script>
@endonce
