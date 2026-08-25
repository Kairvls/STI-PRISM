@php
    $modalId = $modalId ?? 'risPreviewModal';
    $iframeId = $iframeId ?? 'risPreviewIframe';
    $closeFn = $closeFn ?? 'closeRisPreviewModal';
    $printFn = $printFn ?? 'printRisPreview';
    $zIndex = $zIndex ?? '50';
@endphp

<div
    id="{{ $modalId }}"
    class="fixed inset-0 hidden overflow-hidden bg-black/50 p-3 md:p-5"
    style="z-index: {{ $zIndex }}; margin: 0;"
>
    <div
        class="flex h-full w-full items-center justify-center"
        style="margin: 0;"
        onclick="if (event.target === this) window.{{ $closeFn }}()"
    >
        <div
            class="flex w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
            style="height: min(94vh, 980px);"
            onclick="event.stopPropagation()"
        >
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">RIS Form</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Newest Requisition and Issue Slip format (includes supplier).</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="window.{{ $printFn }}()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-white hover:bg-gray-800"
                        title="Print"
                        aria-label="Print"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                    </button>
                    <button
                        type="button"
                        onclick="window.{{ $closeFn }}()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                        title="Close"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            {{-- Fit stage: no scroll; form scales to available space --}}
            <div
                class="ris-preview-stage relative flex min-h-0 flex-1 items-center justify-center overflow-hidden bg-gray-100 px-3 py-3"
                data-iframe-id="{{ $iframeId }}"
            >
                <div class="ris-preview-scale-wrap" data-scale-wrap-for="{{ $iframeId }}" style="overflow:hidden; line-height:0;">
                    <iframe
                        id="{{ $iframeId }}"
                        class="block bg-white"
                        style="width: 11in; height: 8.5in; border: 1px solid #e5e7eb; transform-origin: top left;"
                        src="about:blank"
                        title="RIS Form Preview"
                    ></iframe>
                </div>
            </div>

            <div
                id="{{ $modalId }}-attachments"
                class="mx-4 mb-2 hidden shrink-0 overflow-y-auto rounded-lg border border-gray-200 bg-white px-4 py-2"
                style="max-height: 4.5rem;"
            >
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Supporting documents</p>
                <div class="mt-1 space-y-0.5" data-attachment-list></div>
            </div>

        </div>
    </div>
</div>
<script>
(function () {
    if (window.__risPreviewModalHelpers) return;
    window.__risPreviewModalHelpers = true;

    window.scaleRisPreviewIframe = function (iframeId) {
        var id = iframeId || 'risPreviewIframe';
        var iframe = document.getElementById(id);
        if (!iframe) return;

        var stage = iframe.closest('.ris-preview-stage');
        var wrap = document.querySelector('[data-scale-wrap-for="' + id + '"]');
        if (!stage || !wrap) return;

        var docW = 11 * 96;
        var docH = 8.5 * 96;
        var pad = 8;
        var availW = Math.max(120, stage.clientWidth - pad);
        var availH = Math.max(120, stage.clientHeight - pad);
        var scale = Math.min(availW / docW, availH / docH, 1);

        iframe.style.width = docW + 'px';
        iframe.style.height = docH + 'px';
        iframe.style.maxWidth = 'none';
        iframe.style.transform = 'scale(' + scale + ')';
        iframe.style.transformOrigin = 'top left';

        wrap.style.width = Math.floor(docW * scale) + 'px';
        wrap.style.height = Math.floor(docH * scale) + 'px';
    };

    window.fillRisPreviewAttachments = function (risId, modalId) {
        var box = document.getElementById((modalId || 'risPreviewModal') + '-attachments');
        if (!box) return;
        var list = box.querySelector('[data-attachment-list]');
        box.classList.add('hidden');
        if (list) list.innerHTML = '';
        fetch('/admin/ris/' + risId + '/details', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.ok ? res.json() : { attachments: [] }; })
            .then(function (data) {
                var files = data.attachments || [];
                if (!files.length || !list) return;
                files.forEach(function (file) {
                    var link = document.createElement('a');
                    link.href = file.url;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.className = 'block truncate text-sm text-slate-600 hover:underline';
                    link.textContent = file.name || 'Attachment';
                    list.appendChild(link);
                });
                box.classList.remove('hidden');
                requestAnimationFrame(function () {
                    var iframe = box.parentElement && box.parentElement.querySelector('iframe');
                    if (iframe && iframe.id) window.scaleRisPreviewIframe(iframe.id);
                });
            })
            .catch(function () {});
    };

    window.addEventListener('resize', function () {
        document.querySelectorAll('.ris-preview-stage iframe').forEach(function (iframe) {
            var modal = iframe.closest('[id$="Modal"], [id^="risPreview"]');
            if (!modal) modal = iframe.closest('.fixed');
            if (modal && !modal.classList.contains('hidden')) {
                window.scaleRisPreviewIframe(iframe.id);
            }
        });
    });

    /** Trigger browser print dialog via a hidden iframe (no new tab). */
    window.printAdminRis = function (risId) {
        if (!risId) return;
        var url = '/admin/procurement-review/ris/' + encodeURIComponent(risId) + '/print?ts=' + Date.now();
        var iframe = document.getElementById('adminRisPrintFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'adminRisPrintFrame';
            iframe.setAttribute('title', 'Print RIS');
            iframe.setAttribute('aria-hidden', 'true');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
            document.body.appendChild(iframe);
        }

        var printed = false;
        var tryPrint = function () {
            if (printed) return;
            if (!iframe.contentWindow) return;
            printed = true;
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) { /* ignore */ }
        };

        iframe.onload = function () {
            setTimeout(tryPrint, 300);
        };
        iframe.src = url;
    };
})();
</script>
