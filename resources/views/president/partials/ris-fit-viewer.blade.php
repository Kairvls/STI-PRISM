<style>
    .eyebrow { margin: 0; font-size: 0.75rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }
    .hidden { display: none !important; }
    .doc-modal { position: fixed; inset: 0; z-index: 80; }
    .doc-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .55); backdrop-filter: blur(6px); }
    .doc-shell {
        position: relative; z-index: 1;
        width: min(1240px, calc(100vw - 24px));
        height: calc(100vh - 24px);
        margin: 12px auto;
        display: flex; flex-direction: column; overflow: hidden;
        background: #f8fafc; border-radius: 18px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, .28);
        animation: risModalIn .22s ease;
    }
    .doc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
    .doc-head-actions { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .doc-head h2 { margin: 0; font-size: 1.125rem; line-height: 1.75rem; font-weight: 700; color: #0f172a; }
    .doc-meta { margin: 3px 0 0; font-size: 0.875rem; line-height: 1.25rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 6px; }
    .doc-stage { flex: 1 1 auto; min-height: 0; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 8px 12px; }
    .doc-fit { transform-origin: center center; background: #fff; box-shadow: 0 10px 40px rgba(15, 23, 42, .12); border-radius: 4px; overflow: hidden; }
    .doc-fit iframe { display: block; width: var(--ris-doc-w); height: var(--ris-doc-h); border: 0; background: #fff; overflow: hidden; pointer-events: none; }
    .doc-actions {
        flex-shrink: 0;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 10px 16px 12px;
        background: rgba(255,255,255,.92);
        border-top: 1px solid #e2e8f0;
    }
    .icon-close { width: 32px; height: 32px; border: 0; border-radius: 8px; background: transparent; color: #64748b; display: grid; place-items: center; cursor: pointer; }
    .icon-close:hover { background: #f1f5f9; color: #0f172a; }
    .btn-ghost, .btn-reject, .btn-approve, .btn-send { border: 0; border-radius: 12px; padding: 8px 16px; font-size: 0.875rem; line-height: 1.25rem; font-weight: 500; cursor: pointer; }
    .btn-ghost { background: transparent; color: #475569; }
    .btn-reject { background: #334155; color: #fff; }
    .btn-approve { background: #0f172a; color: #fff; }
    .btn-send { background: #0f172a; color: #fff; }
    .btn-ghost:hover { background: #f1f5f9; color: #0f172a; }
    .btn-reject:hover { background: #1e293b; }
    .btn-approve:hover { background: #1e293b; }
    .btn-send:hover { background: #1e293b; }
    @media (max-width: 1024px) {
        .doc-shell {
            width: min(1240px, calc(100vw - 16px));
            height: calc(100vh - 16px);
            margin: 8px auto;
            border-radius: 14px;
        }
    }
    @keyframes risModalIn { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }
</style>
<script>
    window.RIS_DOC_W = 1056;
    window.RIS_DOC_H = 700;
    window.fitRisDocument = function (iframeId, stageId, fitId) {
        const iframe = document.getElementById(iframeId);
        const stage = document.getElementById(stageId);
        const fit = document.getElementById(fitId);
        if (!iframe || !stage || !fit) return;
        iframe.style.width = window.RIS_DOC_W + 'px';
        iframe.style.height = window.RIS_DOC_H + 'px';
        const scale = Math.min(
            Math.max(0.35, (stage.clientWidth - 8) / window.RIS_DOC_W),
            Math.max(0.35, (stage.clientHeight - 8) / window.RIS_DOC_H)
        );
        fit.style.width = window.RIS_DOC_W + 'px';
        fit.style.height = window.RIS_DOC_H + 'px';
        fit.style.transform = 'scale(' + scale + ')';
    };
    window.addEventListener('resize', function () {
        window.fitRisDocument('risReviewIframe', 'reviewStage', 'reviewFit');
        window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit');
        window.fitRisDocument('historyRisIframe', 'historyStage', 'historyFit');
    });

    window.printRisDocument = window.printRisDocument || function (risId) {
        if (!risId) return;
        const win = window.open('/president/ris/' + risId + '/print', '_blank', 'noopener,noreferrer,width=1200,height=860');
        if (!win) return;
        const triggerPrint = function () {
            try { win.focus(); win.print(); } catch (e) {}
        };
        win.onload = triggerPrint;
        setTimeout(triggerPrint, 1200);
    };

    window.openRisViewModal = function (risId) {
        const modal = document.getElementById('historyRisModal');
        const iframe = document.getElementById('historyRisIframe');
        const title = document.getElementById('historyRisTitle');
        if (!modal || !iframe) return;
        window.currentHistoryRisId = risId;
        if (title) title.textContent = 'RIS #' + risId;
        iframe.src = '/president/ris/' + risId + '/view?preview=1&ts=' + Date.now();
        modal.classList.remove('hidden');
        requestAnimationFrame(() => window.fitRisDocument('historyRisIframe', 'historyStage', 'historyFit'));
        iframe.onload = () => window.fitRisDocument('historyRisIframe', 'historyStage', 'historyFit');
        if (window.lucide) lucide.createIcons();
    };

    window.closeRisViewModal = function () {
        const modal = document.getElementById('historyRisModal');
        const iframe = document.getElementById('historyRisIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    };
</script>
