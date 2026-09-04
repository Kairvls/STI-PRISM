<div
    id="historyRisModal"
    class="doc-modal hidden"
    data-iframe-id="historyRisIframe"
    data-stage-id="historyStage"
    data-fit-id="historyFit"
>
    <div class="doc-backdrop" onclick="closeRisViewModal()"></div>
    <div class="doc-shell" onclick="event.stopPropagation()">
        <header class="doc-head">
            <div class="min-w-0">
                <p class="eyebrow">View</p>
                <h2 id="historyRisTitle">RIS</h2>
                <p class="doc-meta">Read-only document preview</p>
            </div>
            <div class="doc-head-actions">
                <button
                    type="button"
                    class="doc-fs-btn"
                    data-fs-btn
                    data-tip="Full screen"
                    title="Full screen"
                    aria-label="Full screen"
                    onclick="toggleDocFullscreen('historyRisModal')"
                >
                    <svg data-fs-icon="expand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"></path>
                    </svg>
                    <svg data-fs-icon="collapse" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5v4M15 4h4v4M9 20H5v-4M15 20h4v-4"></path>
                    </svg>
                </button>
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
        <div class="doc-actions doc-actions-split">
            <div class="doc-actions-left">
                <div id="historyAttachments" class="review-attachments-inline hidden">
                    <p class="review-attachments-label">Supporting documents</p>
                    <div id="historyAttachmentsList" class="review-attachments-list"></div>
                </div>
                <div id="historyAdminAttachments" class="review-attachments-inline hidden">
                    <p class="review-attachments-label">Admin supporting details</p>
                    <p id="historyAdminDetailsText" class="review-forward-details hidden"></p>
                    <div id="historyAdminAttachmentsList" class="review-attachments-list"></div>
                </div>
            </div>
            <button type="button" class="btn-ghost" data-tip="Close preview" onclick="closeRisViewModal()">Close</button>
        </div>
    </div>
</div>
