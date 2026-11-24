@php
    $modalId = $modalId ?? 'risPreviewModal';
    $iframeId = $iframeId ?? 'risPreviewIframe';
    $closeFn = $closeFn ?? 'closeRisPreviewModal';
    $printFn = $printFn ?? 'printRisPreview';
    $zIndex = $zIndex ?? '50';
    $footerView = $footerView ?? null;
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
            class="ris-preview-modal-panel flex flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
            style="width: min(1024px, 96vw); height: auto; max-width: 98vw; max-height: 96vh;"
            onclick="event.stopPropagation()"
            data-ris-preview-panel
        >
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-2.5" data-ris-preview-header>
                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    <h3 class="text-base font-semibold text-gray-900">RIS Form</h3>
                    <span
                        data-ris-preview-number
                        class="hidden rounded-md px-2 py-0.5 text-xs font-semibold"
                        style="background:#eff6ff;color:#1d4ed8;"
                    ></span>
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
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white hover:bg-blue-800"
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

            {{-- Single scrollable body so RIS form and Canvass attachments scroll naturally (no pinned footer, no horizontal scrollbar) --}}
            <div class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div
                    class="ris-preview-stage relative w-full overflow-hidden bg-gray-100"
                    data-iframe-id="{{ $iframeId }}"
                    style="min-height: min(72vh, 620px); width: 100%;"
                >
                    <div
                        class="ris-preview-scale-wrap"
                        data-scale-wrap-for="{{ $iframeId }}"
                        style="overflow: hidden; line-height: 0; height: 0; min-height: 0;"
                    >
                        <iframe
                            id="{{ $iframeId }}"
                            class="block bg-white"
                            scrolling="no"
                            style="width: 11in; height: 0; border: 0; overflow: hidden; transform-origin: top left;"
                            src="about:blank"
                            title="RIS Form Preview"
                        ></iframe>
                    </div>
                    <div class="hidden bg-gray-100 p-4 md:p-6" data-ris-preview-inline></div>
                    <div
                        class="flex flex-col items-center justify-center gap-2 bg-white text-xs font-medium text-slate-500"
                        style="min-height: min(72vh, 620px);"
                        data-ris-preview-skeleton
                    >
                        <svg class="h-6 w-6 animate-spin" style="color:#2563eb;" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span>Loading RIS form...</span>
                    </div>
                </div>

                <div
                    id="{{ $modalId }}-attachments"
                    class="hidden border-t border-gray-200 bg-white px-5 py-3"
                >
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Canvass</p>
                    <div class="mt-1 space-y-0.5" data-attachment-list></div>
                </div>
            </div>

            @if($footerView)
                <div class="shrink-0 border-t border-gray-200 bg-white px-4 py-3" data-ris-preview-footer>
                    @include($footerView, ['modalId' => $modalId, 'closeFn' => $closeFn])
                </div>
            @endif
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

    function collapsePreviewFrame(iframe) {
        var stage = iframe.closest('.ris-preview-stage');
        var wrap = document.querySelector('[data-scale-wrap-for="' + iframe.id + '"]');
        var panel = stage ? stage.closest('[data-ris-preview-panel]') : null;
        var modal = panel ? panel.closest('[data-ris-preview-root]') : null;
        iframe.style.height = '0px';
        if (wrap) {
            wrap.style.height = '0px';
            wrap.style.minHeight = '0';
        }
        if (stage) {
            stage.style.height = 'auto';
            stage.style.minHeight = 'min(72vh, 620px)';
            stage.style.width = 'min(1112px, 96vw)';
            stage.style.background = '#f1f5f9';
        }
        if (panel && !(modal && modal.dataset.fullscreen === '1')) {
            panel.style.width = 'min(1112px, 96vw)';
            panel.style.height = 'auto';
        }
    }

    function watchRisPreviewSkeleton(iframe) {
        var stage = iframe.closest('.ris-preview-stage');
        var skeleton = stage ? stage.querySelector('[data-ris-preview-skeleton]') : null;
        if (!skeleton || iframe.dataset.skeletonWatched === '1') return;
        iframe.dataset.skeletonWatched = '1';

        var isBlank = function () {
            var src = iframe.getAttribute('src') || '';
            return !src || src === 'about:blank';
        };

        new MutationObserver(function () {
            if (isBlank()) {
                skeleton.classList.add('hidden');
                return;
            }
            var doc = iframe.contentDocument;
            if (doc && doc.readyState === 'complete' && doc.URL && doc.URL.indexOf('about:blank') === -1) {
                skeleton.classList.add('hidden');
                window.scaleRisPreviewIframe(iframe.id);
                return;
            }
            collapsePreviewFrame(iframe);
            skeleton.classList.remove('hidden');
        }).observe(iframe, { attributes: true, attributeFilter: ['src'] });

        iframe.addEventListener('load', function () {
            if (isBlank()) return;
            skeleton.classList.add('hidden');
            window.scaleRisPreviewIframe(iframe.id);
            setTimeout(function () { window.scaleRisPreviewIframe(iframe.id); }, 80);
        });
    }

    function warmVisibleRisForms() {
        var base = @js(\App\Support\AdminPortal::url('procurement-review/ris'));
        var seen = {};
        var warmed = 0;
        function warm(id) {
            if (!id || seen[id] || warmed >= 15) return;
            seen[id] = true;
            warmed += 1;
            fetch(base + '/' + id + '/print', { credentials: 'same-origin' }).catch(function () {});
        }
        document.querySelectorAll('[data-ris-id]').forEach(function (el) {
            warm(el.getAttribute('data-ris-id'));
        });
        document.querySelectorAll('[onclick*="openRisPreviewModal"]').forEach(function (el) {
            var match = (el.getAttribute('onclick') || '').match(/openRisPreviewModal\('(\d+)'\)/);
            if (match) warm(match[1]);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-ris-preview-root] .ris-preview-stage iframe').forEach(watchRisPreviewSkeleton);
        document.querySelectorAll('[id$="PreviewModal"], #risPreviewModal').forEach(mountRisPreviewModal);
        warmVisibleRisForms();
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
        var footerEl = panel ? panel.querySelector('[data-ris-preview-footer]') : null;
        var headerH = headerEl ? headerEl.offsetHeight : 52;
        var attachH = (attachEl && !attachEl.classList.contains('hidden')) ? attachEl.offsetHeight : 0;
        attachH += footerEl ? footerEl.offsetHeight : 0;
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

        var docW = 1095;
        var docH = 845;
        var purchaserSheet = false;

        try {
            var idoc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
            if (!idoc || idoc.readyState !== 'complete' || !idoc.body) {
                return;
            }
            var sheetRoot = idoc.querySelector('.ris-original-form, .ris-document, .ris-print-sheet, .rfc-print-sheet, .atp-print-sheet, .dv-print-sheet, .rr-print-sheet');
            if (!sheetRoot && !String(idoc.body.innerText || '').trim()) {
                return;
            }
            if (idoc && idoc.body) {
                iframe.style.width = docW + 'px';
                iframe.style.height = '2400px';
                var root = sheetRoot || idoc.body;
                purchaserSheet = !!(root.classList && root.classList.contains('ris-original-form'));
                var prevMin = root.style.minHeight;
                var prevHeight = root.style.height;
                if (!purchaserSheet) {
                    root.style.minHeight = '0px';
                    root.style.height = 'auto';
                }
                var view = idoc.defaultView || window;
                var top = root.getBoundingClientRect().top;
                var bottom = top;
                Array.prototype.forEach.call(root.children, function (child) {
                    var cs = view.getComputedStyle(child);
                    if (cs.display === 'none' || cs.position === 'absolute' || cs.position === 'fixed') return;
                    bottom = Math.max(bottom, child.getBoundingClientRect().bottom + (parseFloat(cs.marginBottom) || 0));
                });
                var sheetStyle = view.getComputedStyle(root);
                var measuredH = Math.ceil(bottom - top + (parseFloat(sheetStyle.paddingBottom) || 0) + (parseFloat(sheetStyle.borderBottomWidth) || 0));
                if (measuredH < 80) {
                    measuredH = Math.ceil(root.scrollHeight || 0);
                }
                if (purchaserSheet) {
                    measuredH = Math.max(
                        measuredH,
                        Math.ceil(root.scrollHeight || 0),
                        Math.ceil(root.getBoundingClientRect().height || 0)
                    );
                }
                var measuredW = Math.ceil(root.getBoundingClientRect().width || root.scrollWidth || 0);
                if (!purchaserSheet) {
                    root.style.minHeight = prevMin;
                    root.style.height = prevHeight;
                }
                if (measuredW >= 280 && measuredW <= 1600) docW = measuredW;
                if (measuredH >= 80 && measuredH <= 2400) docH = measuredH;
            }
        } catch (e) { /* ignore */ }

        var isFs = modal && modal.dataset.fullscreen === '1';
        var chrome = chromeHeights(panel);
        var pad = isFs ? 0 : 24;

        var maxW = Math.max(320, window.innerWidth - pad * 2);
        var maxH = Math.max(240, window.innerHeight - pad * 2 - chrome.headerH - chrome.attachH);

        var scale = purchaserSheet
            ? Math.min(maxW / docW, isFs ? 1.5 : 1)
            : Math.min(maxW / docW, maxH / docH, isFs ? 1.5 : 1);
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
            var finalPanelW = Math.min(maxW, Math.max(scaledW, Math.min(1024, maxW)));
            wrap.style.margin = finalPanelW > scaledW ? '0 auto' : '0';
            wrap.style.boxShadow = 'none';

            var stageH = scaledH;
            if (purchaserSheet) stageH = Math.min(scaledH, maxH);
            stage.style.flex = 'none';
            stage.style.width = finalPanelW + 'px';
            stage.style.height = stageH + 'px';
            stage.style.minHeight = purchaserSheet ? '0' : (scaledH + 'px');
            stage.style.overflowX = 'hidden';
            stage.style.overflowY = purchaserSheet && scaledH > stageH + 1 ? 'auto' : 'hidden';
            stage.style.background = '#fff';
            stage.style.display = 'block';
            stage.style.padding = '0';
            stage.style.justifyContent = '';
            stage.style.alignItems = '';

            panel.style.width = finalPanelW + 'px';
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
        fetch(@js(\App\Support\AdminPortal::url('ris')) + '/' + risId + '/details', {
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
                    link.className = 'block max-w-full truncate text-[15px] font-semibold text-[#0025cc] hover:underline';
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

    document.addEventListener('click', function (event) {
        var tabBtn = event.target.closest('[data-ris-modal-tab]');
        if (tabBtn) {
            var root = tabBtn.closest('[data-ris-modal-doc-root]');
            if (!root) return;
            var target = tabBtn.getAttribute('data-ris-modal-tab');
            root.querySelectorAll('[data-ris-modal-pane]').forEach(function (pane) {
                pane.classList.toggle('hidden', pane.getAttribute('data-ris-modal-pane') !== target);
            });
            root.querySelectorAll('[data-ris-modal-tab]').forEach(function (btn) {
                var active = btn.getAttribute('data-ris-modal-tab') === target;
                btn.style.borderColor = active ? '#2563eb' : 'transparent';
                btn.style.color = active ? '#1d4ed8' : '#64748b';
            });
            return;
        }

        var stepBtn = event.target.closest('[data-ris-atp-prev], [data-ris-atp-next]');
        if (stepBtn) {
            var root = stepBtn.closest('[data-ris-modal-doc-root]');
            if (!root) return;
            var sheets = Array.from(root.querySelectorAll('[data-ris-atp-sheet]'));
            if (!sheets.length) return;
            var cur = sheets.findIndex(function (el) { return !el.classList.contains('hidden'); });
            if (cur < 0) cur = 0;
            var next = stepBtn.hasAttribute('data-ris-atp-next') ? cur + 1 : cur - 1;
            next = Math.max(0, Math.min(sheets.length - 1, next));
            sheets.forEach(function (el, idx) { el.classList.toggle('hidden', idx !== next); });
            var labelEl = root.querySelector('[data-ris-atp-label]');
            if (labelEl && sheets[next]) {
                labelEl.textContent = 'ATP ' + (next + 1) + ' of ' + sheets.length + ' · ' + (sheets[next].getAttribute('data-ris-atp-title') || '');
            }
            var prevBtn = root.querySelector('[data-ris-atp-prev]');
            var nextBtn = root.querySelector('[data-ris-atp-next]');
            if (prevBtn) prevBtn.disabled = next === 0;
            if (nextBtn) nextBtn.disabled = next >= sheets.length - 1;
        }
    }, true);

    window.printAdminRis = function (risId) {
        if (!risId) return;
        var url = @js(\App\Support\AdminPortal::url('procurement-review/ris')) + '/' + encodeURIComponent(risId) + '/print';
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
