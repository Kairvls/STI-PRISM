<style>
    .eyebrow { margin: 0; font-size: 0.75rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }
    .hidden { display: none !important; }
    .doc-modal { position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; padding: 12px; }
    .doc-backdrop { position: absolute; inset: 0; background: rgba(0, 0, 0, .5); }
    .doc-shell {
        position: relative; z-index: 1;
        width: auto;
        max-width: calc(100vw - 24px);
        height: auto;
        max-height: calc(100vh - 24px);
        margin: 0;
        display: flex; flex-direction: column; overflow: hidden;
        background: #fff; border-radius: 18px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, .28);
        animation: risModalIn .22s ease;
    }
    .doc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
    .doc-head-actions { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .doc-head h2 { margin: 0; font-size: 1.125rem; line-height: 1.75rem; font-weight: 700; color: #0f172a; }
    .doc-meta { margin: 3px 0 0; font-size: 0.875rem; line-height: 1.25rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 6px; }
    .doc-stage {
        flex: none;
        min-height: 0;
        overflow: hidden;
        display: block;
        padding: 0;
        background: #fff;
        line-height: 0;
    }
    .doc-fit {
        transform-origin: top left;
        background: #fff;
        box-shadow: none;
        border-radius: 0;
        overflow: hidden;
        line-height: 0;
    }
    .doc-fit iframe { display: block; width: var(--ris-doc-w); height: var(--ris-doc-h); border: 0; background: #fff; overflow: hidden; pointer-events: none; }
    .doc-actions {
        flex-shrink: 0;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 10px 16px 12px;
        background: #fff;
        border-top: 1px solid #e2e8f0;
    }
    .doc-actions.doc-actions-split {
        justify-content: space-between;
        align-items: flex-end;
        gap: 12px;
    }
    .doc-actions-left {
        min-width: 0;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    .doc-actions-right {
        flex-shrink: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        margin-left: auto;
    }
    .review-attachments-inline {
        min-width: 0;
        max-width: 100%;
    }
    .review-attachments-inline .review-attachments-label {
        margin: 0;
    }
    .review-attachments-inline .review-attachments-list {
        margin-top: 4px;
    }
    .review-attachments-inline .review-forward-details {
        margin: 4px 0 0;
        max-width: 36rem;
    }
    .doc-actions.doc-actions-split > .btn-ghost {
        flex-shrink: 0;
        margin-left: auto;
    }
    .icon-close, .doc-fs-btn {
        width: 32px; height: 32px; border: 0; border-radius: 8px;
        background: transparent; color: #64748b;
        display: grid; place-items: center; cursor: pointer;
    }
    .icon-close:hover, .doc-fs-btn:hover { background: #f1f5f9; color: #0f172a; }
    .doc-fs-btn svg { width: 16px; height: 16px; }
    .btn-ghost, .btn-reject, .btn-approve, .btn-send { border: 0; border-radius: 0.5rem; padding: 8px 16px; font-size: 0.875rem; line-height: 1.25rem; font-weight: 500; cursor: pointer; }
    .btn-ghost { background: transparent; color: #475569; }
    .btn-reject {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #ef0000;
        color: #fff;
    }
    .btn-approve {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #0025cc;
        color: #fff;
    }
    .btn-send {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #0025cc;
        color: #fff;
    }
    .btn-ghost:hover { background: #f1f5f9; color: #0f172a; }
    .btn-reject:hover { background: #c40000; }
    .btn-approve:hover { background: #001db0; }
    .btn-send:hover { background: #001db0; }
    .btn-approve svg,
    .btn-approve i,
    .btn-reject svg,
    .btn-reject i,
    .btn-send svg,
    .btn-send i {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }
    .btn-send:disabled,
    .btn-send.is-notified {
        background: #e2e8f0;
        color: #64748b;
        cursor: default;
        pointer-events: none;
        box-shadow: none;
    }
    .btn-send:disabled:hover,
    .btn-send.is-notified:hover {
        background: #e2e8f0;
        color: #64748b;
    }

    /* Fullscreen: edge-to-edge panel, form scales to width */
    .doc-modal.doc-is-fullscreen {
        padding: 0;
        align-items: stretch;
        justify-content: stretch;
    }
    .doc-modal.doc-is-fullscreen .doc-shell {
        width: 100vw !important;
        max-width: 100vw !important;
        height: 100vh !important;
        max-height: 100vh !important;
        border-radius: 0;
    }
    .doc-modal.doc-is-fullscreen .doc-stage {
        flex: 1 1 auto;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        overflow-x: hidden;
        overflow-y: auto;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        background: #e5e7eb;
        padding: 12px 0;
        line-height: normal;
    }
    .doc-modal.doc-is-fullscreen .doc-fit {
        margin: 0 auto;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
    }

    @media (max-width: 1024px) {
        .doc-modal { padding: 8px; }
        .doc-shell {
            max-width: calc(100vw - 16px);
            max-height: calc(100vh - 16px);
            border-radius: 14px;
        }
        .doc-modal.doc-is-fullscreen { padding: 0; }
        .doc-modal.doc-is-fullscreen .doc-shell { border-radius: 0; }
    }
    @keyframes risModalIn { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }
</style>
<script>
    window.RIS_DOC_W = 1056; // 11in @ 96dpi
    window.RIS_DOC_H = 816;  // 8.5in @ 96dpi fallback

    function syncDocFsButton(modal, isFs) {
        if (!modal) return;
        var btn = modal.querySelector('[data-fs-btn]');
        if (!btn) return;
        var expand = btn.querySelector('[data-fs-icon="expand"]');
        var collapse = btn.querySelector('[data-fs-icon="collapse"]');
        if (expand) expand.classList.toggle('hidden', !!isFs);
        if (collapse) collapse.classList.toggle('hidden', !isFs);
        btn.title = isFs ? 'Exit full screen' : 'Full screen';
        btn.setAttribute('aria-label', isFs ? 'Exit full screen' : 'Full screen');
        btn.setAttribute('data-tip', isFs ? 'Exit full screen' : 'Full screen');
    }

    function docFitTargets(modal) {
        if (!modal) return null;
        return {
            iframeId: modal.dataset.iframeId || '',
            stageId: modal.dataset.stageId || '',
            fitId: modal.dataset.fitId || ''
        };
    }

    window.setDocFullscreen = function (modalId, enabled) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        var isFs = !!enabled;
        modal.classList.toggle('doc-is-fullscreen', isFs);
        modal.dataset.fullscreen = isFs ? '1' : '0';
        syncDocFsButton(modal, isFs);

        var targets = docFitTargets(modal);
        requestAnimationFrame(function () {
            if (targets && targets.iframeId) {
                window.fitRisDocument(targets.iframeId, targets.stageId, targets.fitId);
                requestAnimationFrame(function () {
                    window.fitRisDocument(targets.iframeId, targets.stageId, targets.fitId);
                });
            }
        });
    };

    window.toggleDocFullscreen = function (modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        window.setDocFullscreen(modalId, modal.dataset.fullscreen !== '1');
    };

    window.exitDocFullscreen = function (modalId) {
        window.setDocFullscreen(modalId, false);
    };

    window.fitRisDocument = function (iframeId, stageId, fitId) {
        const iframe = document.getElementById(iframeId);
        const stage = document.getElementById(stageId);
        const fit = document.getElementById(fitId);
        if (!iframe || !stage || !fit) return;

        const src = iframe.getAttribute('src') || '';
        if (!src || src === 'about:blank') return;

        const shell = stage.closest('.doc-shell');
        const modal = shell ? shell.closest('.doc-modal') : null;
        const head = shell ? shell.querySelector('.doc-head') : null;
        const actions = shell ? shell.querySelector('.doc-actions') : null;
        const attachments = shell
            ? Array.from(shell.querySelectorAll('.review-attachments')).filter(function (el) {
                return !el.classList.contains('hidden');
            })
            : [];
        const isFs = modal && modal.dataset.fullscreen === '1';

        let docW = window.RIS_DOC_W;
        let docH = window.RIS_DOC_H;
        try {
            const idoc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
            if (idoc && idoc.body && idoc.readyState === 'complete') {
                const root = idoc.querySelector('.ris-document') || idoc.body;
                const measuredH = Math.ceil(Math.max(
                    root.scrollHeight || 0,
                    root.offsetHeight || 0,
                    root.getBoundingClientRect().height || 0
                ));
                if (measuredH >= 240 && measuredH <= 2000) {
                    docH = measuredH;
                }
            }
        } catch (e) { /* ignore */ }

        const chromeH = (head ? head.offsetHeight : 52)
            + (actions ? actions.offsetHeight : 52)
            + attachments.reduce(function (sum, el) { return sum + (el.offsetHeight || 0); }, 0);
        const pad = isFs ? 0 : (window.innerWidth <= 1024 ? 16 : 24);
        const maxW = Math.max(320, window.innerWidth - pad);
        const maxH = Math.max(240, window.innerHeight - pad - chromeH);

        let scale = Math.min(maxW / docW, maxH / docH, isFs ? 1.5 : 1);
        if (!isFinite(scale) || scale <= 0.05) scale = 0.5;

        let scaledW = Math.max(280, Math.floor(docW * scale));
        let scaledH = Math.max(200, Math.floor(docH * scale));

        iframe.style.width = docW + 'px';
        iframe.style.height = docH + 'px';
        iframe.style.transform = 'scale(' + scale + ')';
        iframe.style.transformOrigin = 'top left';

        fit.style.width = scaledW + 'px';
        fit.style.height = scaledH + 'px';
        fit.style.transform = 'none';

        if (isFs) {
            stage.style.flex = '1 1 auto';
            stage.style.width = '100%';
            stage.style.height = 'auto';
            stage.style.minHeight = '0';
            stage.style.overflowX = 'hidden';
            stage.style.overflowY = 'auto';
            stage.style.display = 'flex';
            stage.style.justifyContent = 'center';
            stage.style.alignItems = 'flex-start';
            stage.style.padding = '12px 0';
            stage.style.background = '#e5e7eb';

            fit.style.margin = '0 auto';
            fit.style.boxShadow = '0 10px 30px rgba(15, 23, 42, 0.12)';

            // Prefer filling stage width in fullscreen
            const stageW = Math.max(320, stage.clientWidth || maxW);
            const fsScale = Math.min(stageW / docW, 1.5);
            if (isFinite(fsScale) && fsScale > 0.05) {
                scale = fsScale;
                scaledW = Math.max(280, Math.floor(docW * scale));
                scaledH = Math.max(200, Math.floor(docH * scale));
                iframe.style.transform = 'scale(' + scale + ')';
                fit.style.width = scaledW + 'px';
                fit.style.height = scaledH + 'px';
            }

            if (shell) {
                shell.style.width = '100vw';
                shell.style.maxWidth = '100vw';
                shell.style.height = '100vh';
            }
        } else {
            fit.style.margin = '0';
            fit.style.boxShadow = 'none';

            stage.style.flex = 'none';
            stage.style.width = scaledW + 'px';
            stage.style.height = scaledH + 'px';
            stage.style.minHeight = scaledH + 'px';
            stage.style.overflow = 'hidden';
            stage.style.display = 'block';
            stage.style.padding = '0';
            stage.style.background = '#fff';
            stage.style.justifyContent = '';
            stage.style.alignItems = '';

            if (shell) {
                shell.style.width = scaledW + 'px';
                shell.style.maxWidth = 'calc(100vw - ' + pad + 'px)';
                shell.style.height = 'auto';
            }
        }
    };

    window.addEventListener('resize', function () {
        window.fitRisDocument('risReviewIframe', 'reviewStage', 'reviewFit');
        window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit');
        window.fitRisDocument('historyRisIframe', 'historyStage', 'historyFit');
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.doc-modal.doc-is-fullscreen').forEach(function (modal) {
            if (modal.classList.contains('hidden')) return;
            event.preventDefault();
            window.setDocFullscreen(modal.id, false);
        });
    });

    window.printRisDocument = function (risId) {
        if (!risId) return;
        var url = '/president/ris/' + encodeURIComponent(risId) + '/print?ts=' + Date.now();
        var iframe = document.getElementById('presidentRisPrintFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'presidentRisPrintFrame';
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

    window.openRisViewModal = function (risId) {
        const modal = document.getElementById('historyRisModal');
        const iframe = document.getElementById('historyRisIframe');
        const title = document.getElementById('historyRisTitle');
        if (!modal || !iframe) return;
        window.currentHistoryRisId = risId;
        if (title) title.textContent = 'RIS #' + risId;
        window.setDocFullscreen('historyRisModal', false);

        var attachBox = document.getElementById('historyAttachments');
        var attachList = document.getElementById('historyAttachmentsList');
        var adminBox = document.getElementById('historyAdminAttachments');
        var adminList = document.getElementById('historyAdminAttachmentsList');
        var adminText = document.getElementById('historyAdminDetailsText');
        if (attachBox) attachBox.classList.add('hidden');
        if (attachList) attachList.innerHTML = '';
        if (adminBox) adminBox.classList.add('hidden');
        if (adminList) adminList.innerHTML = '';
        if (adminText) {
            adminText.textContent = '';
            adminText.classList.add('hidden');
        }

        iframe.src = '/president/ris/' + encodeURIComponent(risId) + '/view?preview=1&ts=' + Date.now();
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        var refit = function () {
            window.fitRisDocument('historyRisIframe', 'historyStage', 'historyFit');
        };
        requestAnimationFrame(refit);
        iframe.onload = function () {
            refit();
            requestAnimationFrame(function () {
                refit();
                setTimeout(refit, 60);
            });
        };
        if (window.lucide) lucide.createIcons();

        fetch('/president/ris/' + encodeURIComponent(risId) + '/details', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.ok ? res.json() : { attachments: [] }; })
            .then(function (data) {
                if (title) {
                    title.textContent = data.form_number || ('RIS #' + risId);
                }

                var files = data.attachments || [];
                if (attachBox && attachList) {
                    attachList.innerHTML = '';
                    files.forEach(function (file) {
                        var link = document.createElement('a');
                        link.href = file.url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'review-attachment-link';
                        link.textContent = file.name || 'Attachment';
                        link.setAttribute('data-tip', 'Open attachment');
                        attachList.appendChild(link);
                    });
                    attachBox.classList.toggle('hidden', files.length === 0);
                }

                var details = (data.forward_details || '').trim();
                var adminFile = data.forward_attachment || null;
                var hasAdmin = false;
                if (adminBox && adminList && adminText) {
                    adminList.innerHTML = '';
                    if (details) {
                        adminText.textContent = details;
                        adminText.classList.remove('hidden');
                        hasAdmin = true;
                    } else {
                        adminText.textContent = '';
                        adminText.classList.add('hidden');
                    }
                    if (adminFile && adminFile.url) {
                        var adminLink = document.createElement('a');
                        adminLink.href = adminFile.url;
                        adminLink.target = '_blank';
                        adminLink.rel = 'noopener';
                        adminLink.className = 'review-attachment-link';
                        adminLink.textContent = adminFile.name || 'Admin attachment';
                        adminLink.setAttribute('data-tip', 'Open admin attachment');
                        adminList.appendChild(adminLink);
                        hasAdmin = true;
                    }
                    adminBox.classList.toggle('hidden', !hasAdmin);
                }

                requestAnimationFrame(refit);
            })
            .catch(function () { /* ignore */ });
    };

    window.closeRisViewModal = function () {
        const modal = document.getElementById('historyRisModal');
        const iframe = document.getElementById('historyRisIframe');
        window.setDocFullscreen('historyRisModal', false);
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';

        var attachBox = document.getElementById('historyAttachments');
        var adminBox = document.getElementById('historyAdminAttachments');
        if (attachBox) attachBox.classList.add('hidden');
        if (adminBox) adminBox.classList.add('hidden');
    };
</script>
