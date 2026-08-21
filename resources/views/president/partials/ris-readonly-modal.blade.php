<div id="historyRisModal" class="doc-modal hidden">
    <div class="doc-backdrop" onclick="closeRisViewModal()"></div>
    <div class="doc-shell" onclick="event.stopPropagation()">
        <header class="doc-head">
            <div class="min-w-0">
                <p class="eyebrow">View</p>
                <h2 id="historyRisTitle">RIS</h2>
                <p class="doc-meta">Read-only document preview</p>
            </div>
            <div class="doc-head-actions">
                <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument(window.currentHistoryRisId || null)">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                </button>
                <button type="button" class="icon-close" onclick="closeRisViewModal()" data-tip="Close" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </header>
        <div class="doc-stage" id="historyStage">
            <div class="doc-fit" id="historyFit">
                <iframe id="historyRisIframe" title="RIS document" scrolling="no" src="about:blank"></iframe>
            </div>
        </div>
        <div class="doc-actions">
            <button type="button" class="btn-ghost" data-tip="Print RIS form" onclick="printRisDocument(window.currentHistoryRisId || null)">
                <i data-lucide="printer" class="h-4 w-4 inline-block mr-1"></i> Print
            </button>
            <button type="button" class="btn-ghost" data-tip="Close preview" onclick="closeRisViewModal()">Close</button>
        </div>
    </div>
</div>
