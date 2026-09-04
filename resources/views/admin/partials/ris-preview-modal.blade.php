@php
    $modalId = $modalId ?? 'risPreviewModal';
    $iframeId = $iframeId ?? 'risPreviewIframe';
    $closeFn = $closeFn ?? 'closeRisPreviewModal';
    $printFn = $printFn ?? 'printRisPreview';
    $zIndex = $zIndex ?? '50';
@endphp

<div
    id="{{ $modalId }}"
    class="fixed inset-0 hidden overflow-hidden bg-black/50 p-3"
    style="z-index: {{ $zIndex }}; margin: 0;"
    data-ris-preview-root
>
    <div
        class="flex h-full w-full items-center justify-center"
        data-ris-preview-backdrop
        onclick="if (event.target === this) window.{{ $closeFn }}()"
    >
        <div
            class="ris-preview-modal-panel flex flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
            style="width: auto; height: auto; max-width: 98vw; max-height: 96vh;"
            onclick="event.stopPropagation()"
            data-ris-preview-panel
        >
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-2.5" data-ris-preview-header>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-gray-900">RIS Form</h3>
                    <p class="mt-0.5 truncate text-sm text-gray-500">Newest Requisition and Issue Slip format (includes supplier).</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        onclick="window.toggleRisPreviewFullscreen('{{ $modalId }}', '{{ $iframeId }}')"
                        class="ris-preview-fs-btn inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50"
                        title="Full screen"
                        aria-label="Full screen"
                        data-fs-btn
                    >
                        <svg data-fs-icon="expand" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"></path>
                        </svg>
                        <svg data-fs-icon="collapse" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5v4M15 4h4v4M9 20H5v-4M15 20h4v-4"></path>
                        </svg>
                    </button>
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

            {{-- No flex-1: stage must hug the scaled form (avoids big empty gaps) --}}
            <div
                class="ris-preview-stage relative overflow-hidden bg-slate-100"
                data-iframe-id="{{ $iframeId }}"
                style="min-height: 280px;"
            >
                <div
                    class="ris-preview-scale-wrap"
                    data-scale-wrap-for="{{ $iframeId }}"
                    style="overflow: hidden; line-height: 0; min-height: 280px;"
                >
                    <iframe
                        id="{{ $iframeId }}"
                        class="block bg-white"
                        scrolling="no"
                        style="width: 11in; height: 8.5in; border: 0; overflow: hidden; transform-origin: top left;"
                        src="about:blank"
                        title="RIS Form Preview"
                    ></iframe>
                </div>
            </div>

            <div
                id="{{ $modalId }}-attachments"
                class="hidden shrink-0 border-t border-gray-100 bg-white px-4 py-2"
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

    function mountRisPreviewModal(modal) {
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[id$="PreviewModal"], #risPreviewModal').forEach(mountRisPreviewModal);
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });

    function syncFullscreenButton(modal, isFs) {
        if (!modal) return;
        var btn = modal.querySelector('[data-fs-btn]');
        if (!btn) return;
        var expand = btn.querySelector('[data-fs-icon="expand"]');
        var collapse = btn.querySelector('[data-fs-icon="collapse"]');
        if (expand) expand.classList.toggle('hidden', !!isFs);
        if (collapse) collapse.classList.toggle('hidden', !isFs);
        btn.title = isFs ? 'Exit full screen' : 'Full screen';
        btn.setAttribute('aria-label', isFs ? 'Exit full screen' : 'Full screen');
    }

    function chromeHeights(panel) {
        var headerEl = panel ? panel.querySelector('[data-ris-preview-header]') : null;
        var attachEl = panel ? panel.querySelector('[id$="-attachments"]') : null;
        var headerH = headerEl ? headerEl.offsetHeight : 52;
        var attachH = (attachEl && !attachEl.classList.contains('hidden')) ? attachEl.offsetHeight : 0;
        return { headerH: headerH, attachH: attachH };
    }

    window.setRisPreviewFullscreen = function (modalId, iframeId, enabled) {
        var modal = document.getElementById(modalId || 'risPreviewModal');
        if (!modal) return;

        var panel = modal.querySelector('[data-ris-preview-panel]');
        var isFs = !!enabled;
        modal.classList.toggle('ris-preview-is-fullscreen', isFs);
        modal.dataset.fullscreen = isFs ? '1' : '0';

        if (isFs) {
            modal.style.padding = '0';
            if (panel) {
                panel.classList.remove('rounded-xl');
                panel.classList.add('rounded-none');
            }
        } else {
            modal.style.padding = '';
            if (panel) {
                panel.classList.add('rounded-xl');
                panel.classList.remove('rounded-none');
            }
        }

        syncFullscreenButton(modal, isFs);
        requestAnimationFrame(function () {
            window.scaleRisPreviewIframe(iframeId || 'risPreviewIframe');
            requestAnimationFrame(function () {
                window.scaleRisPreviewIframe(iframeId || 'risPreviewIframe');
            });
        });
    };

    window.toggleRisPreviewFullscreen = function (modalId, iframeId) {
        var modal = document.getElementById(modalId || 'risPreviewModal');
        if (!modal) return;
        window.setRisPreviewFullscreen(modalId, iframeId, modal.dataset.fullscreen !== '1');
    };

    window.exitRisPreviewFullscreen = function (modalId, iframeId) {
        window.setRisPreviewFullscreen(modalId || 'risPreviewModal', iframeId || 'risPreviewIframe', false);
    };

    window.scaleRisPreviewIframe = function (iframeId) {
        var id = iframeId || 'risPreviewIframe';
        var iframe = document.getElementById(id);
        if (!iframe) return;

        // Don't collapse the preview while the iframe is still blank / loading.
        var src = iframe.getAttribute('src') || '';
        if (!src || src === 'about:blank') return;

        var stage = iframe.closest('.ris-preview-stage');
        var wrap = document.querySelector('[data-scale-wrap-for="' + id + '"]');
        var panel = stage ? stage.closest('[data-ris-preview-panel]') : null;
        var modal = panel ? panel.closest('[data-ris-preview-root]') : null;
        if (!stage || !wrap || !panel) return;

        var docW = 11 * 96;
        var docH = 8.5 * 96;

        try {
            var idoc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
            if (idoc && idoc.body && idoc.readyState === 'complete') {
                var root = idoc.querySelector('.ris-document') || idoc.body;
                // Force layout, then measure content box only.
                var measuredH = Math.ceil(Math.max(
                    root.scrollHeight || 0,
                    root.offsetHeight || 0,
                    root.getBoundingClientRect().height || 0
                ));
                if (measuredH >= 240 && measuredH <= 2000) {
                    docH = measuredH;
                }
            }
        } catch (e) { /* ignore */ }

        var isFs = modal && modal.dataset.fullscreen === '1';
        var chrome = chromeHeights(panel);
        var pad = isFs ? 0 : 24;

        var maxW = Math.max(320, window.innerWidth - pad * 2);
        var maxH = Math.max(240, window.innerHeight - pad * 2 - chrome.headerH - chrome.attachH);

        var scale = Math.min(maxW / docW, maxH / docH, isFs ? 1.5 : 1);
        if (!isFinite(scale) || scale <= 0.05) scale = 0.5;

        var scaledW = Math.max(280, Math.floor(docW * scale));
        var scaledH = Math.max(200, Math.floor(docH * scale));

        iframe.setAttribute('scrolling', 'no');
        iframe.style.width = docW + 'px';
        iframe.style.height = docH + 'px';
        iframe.style.maxWidth = 'none';
        iframe.style.overflow = 'hidden';
        iframe.style.border = '0';
        iframe.style.display = 'block';
        iframe.style.visibility = 'visible';
        iframe.style.opacity = '1';
        iframe.style.transform = 'scale(' + scale + ')';
        iframe.style.transformOrigin = 'top left';

        wrap.style.width = scaledW + 'px';
        wrap.style.height = scaledH + 'px';
        wrap.style.overflow = 'hidden';
        wrap.style.lineHeight = '0';
        wrap.style.visibility = 'visible';

        if (isFs) {
            panel.style.width = '100vw';
            panel.style.height = '100vh';
            panel.style.maxWidth = '100vw';
            panel.style.maxHeight = '100vh';

            stage.style.flex = '1 1 auto';
            stage.style.width = '100%';
            stage.style.height = 'auto';
            stage.style.minHeight = '0';
            stage.style.overflowX = 'hidden';
            stage.style.overflowY = 'auto';
            stage.style.background = '#e5e7eb';
            stage.style.display = 'flex';
            stage.style.justifyContent = 'center';
            stage.style.alignItems = 'flex-start';
            stage.style.padding = '12px 0';

            wrap.style.margin = '0 auto';
            wrap.style.boxShadow = '0 10px 30px rgba(15,23,42,0.12)';

            // Re-fit to the fullscreen stage width once layout is ready.
            var stageW = Math.max(320, stage.clientWidth || maxW);
            var fsScale = Math.min(stageW / docW, 1.5);
            if (isFinite(fsScale) && fsScale > 0.05) {
                scale = fsScale;
                scaledW = Math.max(280, Math.floor(docW * scale));
                scaledH = Math.max(200, Math.floor(docH * scale));
                iframe.style.transform = 'scale(' + scale + ')';
                wrap.style.width = scaledW + 'px';
                wrap.style.height = scaledH + 'px';
            }
        } else {
            wrap.style.margin = '0';
            wrap.style.boxShadow = 'none';

            stage.style.flex = 'none';
            stage.style.width = scaledW + 'px';
            stage.style.height = scaledH + 'px';
            stage.style.minHeight = scaledH + 'px';
            stage.style.overflow = 'hidden';
            stage.style.background = '#fff';
            stage.style.display = 'block';
            stage.style.padding = '0';
            stage.style.justifyContent = '';
            stage.style.alignItems = '';

            panel.style.width = scaledW + 'px';
            panel.style.height = 'auto';
            panel.style.maxWidth = '98vw';
            panel.style.maxHeight = '96vh';
        }
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
                    link.className = 'block max-w-full truncate text-xs text-sky-700 hover:underline';
                    link.title = file.name || 'Attachment';
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
            var modal = iframe.closest('[data-ris-preview-root]');
            if (modal && !modal.classList.contains('hidden')) {
                window.scaleRisPreviewIframe(iframe.id);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-ris-preview-root]').forEach(function (modal) {
            if (modal.classList.contains('hidden')) return;
            if (modal.dataset.fullscreen === '1') {
                event.preventDefault();
                window.setRisPreviewFullscreen(modal.id, modal.querySelector('iframe') && modal.querySelector('iframe').id, false);
            }
        });
    });

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
