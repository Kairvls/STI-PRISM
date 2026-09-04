@extends('layouts.president-layout')

@section('title', 'RIS Approvals')

@section('content')

<div class="ris-workspace pm-page">
    <header class="ris-page-header">
        <div class="min-w-0 hidden sm:block">
            <p class="text-sm leading-6 text-gray-500">Review forwarded RIS documents. Approve to sign, then notify Admin when ready.</p>
        </div>
        <div class="awaiting-indicator" data-tip="RIS currently waiting for your review" id="awaitingIndicator">
            <span class="awaiting-label">Awaiting review</span>
            <strong id="awaitingReviewCount">{{ number_format($totalPendingRis ?? 0) }}</strong>
        </div>
    </header>

    <div class="ris-layout">
        <section class="queue-panel">
            <div class="panel-head">
                <div>
                    <h2>Approval Queue</h2>
                    <p>Oldest RIS first · pin approved items to keep them on top</p>
                </div>
                <div class="search-wrap">
                    <input type="text" id="queueSearch" value="{{ request('search') }}" placeholder="Search RIS, requester, purpose" autocomplete="off" data-tip="Search the approval queue" />
                </div>
            </div>
            <div id="queueList">
                @include('president.approvals._table')
            </div>
            @include('president.partials.table-word-export', [
                'target' => '#queueList .pm-queue-table',
                'filename' => 'president-approval-queue',
            ])
            <div id="risPagination" class="{{ $pendingRis->hasPages() ? '' : 'hidden' }}">
                @if ($pendingRis->hasPages())
                    {{ $pendingRis->links('pagination.president') }}
                @endif
            </div>
        </section>

        <aside class="recent-panel">
            <div class="panel-head compact">
                <div>
                    <h2>Recent RIS</h2>
                    <p>Earliest decisions first</p>
                </div>
            </div>
            <div id="recentList" class="recent-list">
                @include('president.approvals._recent-list', ['recentRis' => $recentRis])
            </div>
            <div id="recentPagination" class="recent-pagination {{ $recentRis->hasPages() ? '' : 'hidden' }}">
                @if ($recentRis->hasPages())
                    {{ $recentRis->links('pagination.president') }}
                @endif
            </div>
        </aside>
    </div>
</div>

<div
    id="risReviewModal"
    class="doc-modal hidden"
    data-iframe-id="risReviewIframe"
    data-stage-id="reviewStage"
    data-fit-id="reviewFit"
>
    <div class="doc-backdrop" onclick="closeRisReviewModal()"></div>
    <div class="doc-shell" onclick="event.stopPropagation()">
        <header class="doc-head">
            <div class="min-w-0">
                <p class="eyebrow">Review</p>
                <h2 id="reviewRisNumber">RIS</h2>
                <p class="doc-meta">
                    <span id="reviewRequester">—</span><span>·</span>
                    <span id="reviewDateSubmitted">—</span><span>·</span>
                    <span id="reviewAmount">—</span>
                </p>
            </div>
            <div class="doc-head-actions">
                <button
                    type="button"
                    class="doc-fs-btn"
                    data-fs-btn
                    data-tip="Full screen"
                    title="Full screen"
                    aria-label="Full screen"
                    onclick="toggleDocFullscreen('risReviewModal')"
                >
                    <svg data-fs-icon="expand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"></path>
                    </svg>
                    <svg data-fs-icon="collapse" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5v4M15 4h4v4M9 20H5v-4M15 20h4v-4"></path>
                    </svg>
                </button>
                <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument(window.currentRisId)">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                </button>
                <button type="button" class="icon-close" onclick="closeRisReviewModal()" data-tip="Close" aria-label="Close"><i data-lucide="x"></i></button>
            </div>
        </header>
        <div class="doc-stage" id="reviewStage">
            <div class="doc-fit" id="reviewFit">
                <iframe id="risReviewIframe" title="RIS document" scrolling="no" src="about:blank"></iframe>
            </div>
        </div>
        <div class="doc-actions doc-actions-split">
            <div class="doc-actions-left">
                <div id="reviewAttachments" class="review-attachments-inline hidden">
                    <p class="review-attachments-label">Supporting documents</p>
                    <div id="reviewAttachmentsList" class="review-attachments-list"></div>
                </div>
            </div>
            <div class="doc-actions-right">
            
                <button type="button" class="btn-ghost" data-tip="Close without deciding" onclick="closeRisReviewModal()">Close</button>
                <button type="button" class="btn-reject" data-tip="Reject this RIS" onclick="openDecisionModal('ris', window.currentRisId, 'Rejected')">
                    <i data-lucide="x" class="h-4 w-4"></i>
                    Reject
                </button>
                <button type="button" class="btn-approve" data-tip="Sign and approve" onclick="submitRisApproval()">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    Approve
                </button>
            </div>
        </div>
    </div>
</div>

<div id="adminForwardDetailsModal" class="confirm-modal hidden">
    <div class="confirm-backdrop" onclick="closeAdminForwardDetails()"></div>
    <div class="confirm-card wide" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="eyebrow" style="margin:0">Admin</p>
                <h3 id="adminForwardRisTitle">Supporting details</h3>
                <p id="adminForwardSubtitle" class="mt-1">Notes and files from Admin when this RIS was forwarded.</p>
            </div>
            <button type="button" class="icon-close" onclick="closeAdminForwardDetails()" data-tip="Close" aria-label="Close">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="mt-4">
            <p id="adminForwardDetailsText" class="review-forward-details hidden"></p>
            <div id="adminForwardAttachmentList" class="review-attachments-list"></div>
            <p id="adminForwardEmpty" class="hidden text-sm text-slate-500">No admin supporting details for this RIS.</p>
        </div>
        <div class="confirm-actions mt-5">
            <button type="button" class="btn-ghost" data-tip="Close" onclick="closeAdminForwardDetails()">Close</button>
        </div>
    </div>
</div>

<div
    id="approvedRisPreviewModal"
    class="doc-modal hidden"
    data-iframe-id="approvedRisIframe"
    data-stage-id="previewStage"
    data-fit-id="previewFit"
>
    <div class="doc-backdrop" onclick="closeApprovedRisPreviewModal(true)"></div>
    <div class="doc-shell" onclick="event.stopPropagation()">
        <header class="doc-head">
            <div class="min-w-0">
                <p class="eyebrow" style="color:#2563EB">Approved</p>
                <h2>Final RIS</h2>
                <p class="doc-meta">
                    <span id="previewPresidentName">—</span><span>·</span>
                    <span id="previewApprovedDate">—</span>
                </p>
            </div>
            <div class="doc-head-actions">
                <button
                    type="button"
                    class="doc-fs-btn"
                    data-fs-btn
                    data-tip="Full screen"
                    title="Full screen"
                    aria-label="Full screen"
                    onclick="toggleDocFullscreen('approvedRisPreviewModal')"
                >
                    <svg data-fs-icon="expand" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"></path>
                    </svg>
                    <svg data-fs-icon="collapse" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5v4M15 4h4v4M9 20H5v-4M15 20h4v-4"></path>
                    </svg>
                </button>
                <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument(window.currentRisId)">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                </button>
                <button type="button" class="icon-close" onclick="closeApprovedRisPreviewModal(true)" data-tip="Close" aria-label="Close"><i data-lucide="x"></i></button>
            </div>
        </header>
        <div class="doc-stage" id="previewStage">
            <div class="doc-fit" id="previewFit">
                <iframe id="approvedRisIframe" title="Approved RIS" scrolling="no" src="about:blank"></iframe>
            </div>
        </div>
        <div class="doc-actions doc-actions-split">
            <div class="doc-actions-left">
                <div id="previewAttachments" class="review-attachments-inline">
                    <p class="review-attachments-label">Supporting documents</p>
                    <div id="previewAttachmentsList" class="review-attachments-list">
                        <span class="text-xs text-slate-400">Loading…</span>
                    </div>
                </div>
            </div>
            <div class="doc-actions-right">
                
                <button type="button" class="btn-ghost" data-tip="Keep approval and close" onclick="closeApprovedRisPreviewModal(true)">Close</button>
                <button type="button" class="btn-send" id="sendApprovedRisBtn" data-tip="Notify Admin for co-sign" onclick="sendApprovedRisToAdmin()">
                    <i data-lucide="bell" class="h-4 w-4"></i>
                    <span data-notify-label>Notify Admin</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="sendConfirmationModal" class="confirm-modal hidden">
    <div class="confirm-backdrop" onclick="closeSendConfirmationModal()"></div>
    <div class="confirm-card" onclick="event.stopPropagation()">
        <h3>Notify Admin?</h3>
        <p>This notifies Admin that the approved RIS is ready for co-sign. Your approval stays saved.</p>
        <div class="confirm-actions">
            <button type="button" class="btn-ghost" data-tip="Cancel" onclick="closeSendConfirmationModal()">Cancel</button>
            <button type="button" class="btn-send" id="confirmSendActionBtn" data-tip="Send notification to Admin" onclick="executeSendRis()">Confirm</button>
        </div>
    </div>
</div>

<div id="decisionModal" class="confirm-modal hidden">
    <div class="confirm-backdrop" onclick="closeDecisionModal()"></div>
    <div class="confirm-card wide" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 id="decisionModalTitle">Decision</h3>
                <p id="decisionModalSubtitle">Approve or reject the selected RIS</p>
            </div>
            <button type="button" class="icon-close" onclick="closeDecisionModal()" data-tip="Close" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <form id="decisionForm" method="POST" action="">
            @csrf
            <input type="hidden" name="target_type" id="targetType" value="" />
            <input type="hidden" name="target_id" id="targetId" value="" />
            <input type="hidden" name="decision" id="targetDecision" value="" />
            <div class="mt-4">
                <label>Rejection remarks</label>
                <textarea name="remarks" rows="3" placeholder="Required for reject."></textarea>
            </div>
            <div class="confirm-actions mt-5">
                <button type="button" class="btn-ghost" data-tip="Cancel decision" onclick="closeDecisionModal()">Cancel</button>
                <button type="button" id="rejectBtn" class="btn-reject" data-tip="Confirm rejection" onclick="submitDecision('Rejected')">
                    <i data-lucide="x" class="h-4 w-4"></i>
                    Reject
                </button>
            </div>
        </form>
    </div>
</div>

@include('president.partials.ris-fit-viewer')
@include('president.approvals._approve-modal')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        applyPinOrder();
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const presidentDisplayName = @json(Auth::user()->user_full_name ?? 'President');
    const searchInput = document.getElementById('queueSearch');
    const PIN_STORAGE_KEY = 'president_ris_pins';
    let searchTimeout = null, sendInFlight = false, decideInFlight = false;
    window.approvedPreviewDirty = false;

    function getPinnedIds() {
        try {
            const raw = localStorage.getItem(PIN_STORAGE_KEY);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch (e) {
            return [];
        }
    }

    function setPinnedIds(ids) {
        localStorage.setItem(PIN_STORAGE_KEY, JSON.stringify(ids.map(String)));
    }

    function unpinRis(risId) {
        setPinnedIds(getPinnedIds().filter(id => id !== String(risId)));
    }

    window.toggleRisPin = function (risId) {
        const id = String(risId);
        let pins = getPinnedIds();
        if (pins.includes(id)) pins = pins.filter(p => p !== id);
        else pins.push(id);
        setPinnedIds(pins);
        applyPinOrder();
    };

    function applyPinOrder() {
        const body = document.getElementById('queueTableBody');
        if (!body) return;
        const pins = getPinnedIds();
        const rows = Array.from(body.querySelectorAll('tr.queue-row'));

        rows.forEach(row => {
            const id = String(row.dataset.risId || '');
            const pinBtn = row.querySelector('.pin-btn');
            const isPinned = pins.includes(id);
            row.classList.toggle('is-pinned', isPinned);
            if (pinBtn) {
                pinBtn.classList.toggle('is-active', isPinned);
                pinBtn.setAttribute('data-tip', isPinned ? 'Unpin' : 'Pin to top');
                pinBtn.setAttribute('aria-label', isPinned ? 'Unpin' : 'Pin to top');
            }
        });

        rows.sort((a, b) => {
            const aPinned = pins.includes(String(a.dataset.risId || ''));
            const bPinned = pins.includes(String(b.dataset.risId || ''));
            if (aPinned !== bPinned) return aPinned ? -1 : 1;

            const aKind = a.dataset.queueKind || '';
            const bKind = b.dataset.queueKind || '';
            if (aKind !== bKind) {
                if (aKind === 'awaiting_notify') return -1;
                if (bKind === 'awaiting_notify') return 1;
            }

            const aDate = Date.parse(a.dataset.sortDate || '') || 0;
            const bDate = Date.parse(b.dataset.sortDate || '') || 0;
            if (aDate !== bDate) return aDate - bDate;
            return Number(a.dataset.risId || 0) - Number(b.dataset.risId || 0);
        });

        rows.forEach(row => body.appendChild(row));
        if (window.lucide) lucide.createIcons();
    }

    function formatMoney(value) {
        return '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function fetchTableData(page) {
        const search = searchInput ? searchInput.value : '';
        const queueList = document.getElementById('queueList');
        const pagination = document.getElementById('risPagination');
        if (queueList) queueList.classList.add('updating');
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        params.set('page', page || 1);
        fetch(`{{ route('president.approvals') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (queueList) { queueList.innerHTML = data.table_html; queueList.classList.remove('updating'); }
            if (pagination) {
                if (data.last_page > 1) {
                    pagination.innerHTML = buildPagination(data, 'goToPage');
                    pagination.classList.remove('hidden');
                } else {
                    pagination.innerHTML = '';
                    pagination.classList.add('hidden');
                }
            }
            if (typeof data.awaiting_review !== 'undefined') {
                const countEl = document.getElementById('awaitingReviewCount');
                if (countEl) countEl.textContent = Number(data.awaiting_review).toLocaleString();
            }
            applyPinOrder();
            if (window.lucide) lucide.createIcons();
        })
        .catch(() => { if (queueList) queueList.classList.remove('updating'); });
    }
    function buildPagination(data, fnName) {
        const current = Number(data.current_page || 1);
        const last = Number(data.last_page || 1);
        const windowSize = 5;
        const half = Math.floor(windowSize / 2);
        let start = Math.max(1, current - half);
        let end = Math.min(last, start + windowSize - 1);
        start = Math.max(1, end - windowSize + 1);

        let html = '<nav class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-slate-600">Showing <span class="font-medium text-slate-900">' + (data.from || 0) + '</span> to <span class="font-medium text-slate-900">' + (data.to || 0) + '</span> of <span class="font-medium text-slate-900">' + (data.total || 0) + '</span> results</p><ul class="inline-flex items-center gap-1">';

        const prevDisabled = current <= 1;
        html += '<li>' + (prevDisabled
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-300">&laquo;</span>'
            : '<button type="button" onclick="' + fnName + '(' + (current - 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&laquo;</button>') + '</li>';

        for (let i = start; i <= end; i++) {
            if (i === current) {
                html += '<li><span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white">' + i + '</span></li>';
            } else {
                html += '<li><button type="button" onclick="' + fnName + '(' + i + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">' + i + '</button></li>';
            }
        }

        const nextDisabled = current >= last;
        html += '<li>' + (nextDisabled
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-300">&raquo;</span>'
            : '<button type="button" onclick="' + fnName + '(' + (current + 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&raquo;</button>') + '</li>';

        html += '</ul></nav>';
        return html;
    }
    window.goToPage = fetchTableData;

    function fetchRecentData(page) {
        const recentList = document.getElementById('recentList');
        const pagination = document.getElementById('recentPagination');
        if (recentList) recentList.classList.add('updating');

        const params = new URLSearchParams(window.location.search);
        params.set('section', 'recent');
        params.set('recent_page', page || 1);

        fetch(`{{ route('president.approvals') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (recentList) {
                recentList.innerHTML = data.list_html;
                recentList.classList.remove('updating');
            }
            if (pagination) {
                if (data.last_page > 1) {
                    pagination.innerHTML = buildPagination(data, 'goToRecentPage');
                    pagination.classList.remove('hidden');
                } else {
                    pagination.innerHTML = '';
                    pagination.classList.add('hidden');
                }
            }
            const url = new URL(window.location.href);
            if (Number(data.current_page) > 1) {
                url.searchParams.set('recent_page', data.current_page);
            } else {
                url.searchParams.delete('recent_page');
            }
            window.history.replaceState({}, '', url);
            if (window.lucide) lucide.createIcons();
        })
        .catch(() => {
            if (recentList) recentList.classList.remove('updating');
        });
    }
    window.goToRecentPage = fetchRecentData;

    (function initRecentPagination() {
        const recentPagination = document.getElementById('recentPagination');
        @if ($recentRis->hasPages())
        if (recentPagination) {
            recentPagination.innerHTML = buildPagination({
                from: {{ $recentRis->firstItem() ?? 0 }},
                to: {{ $recentRis->lastItem() ?? 0 }},
                total: {{ $recentRis->total() }},
                current_page: {{ $recentRis->currentPage() }},
                last_page: {{ $recentRis->lastPage() }},
            }, 'goToRecentPage');
            recentPagination.classList.remove('hidden');
        }
        @endif
    })();

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchTableData(1), 300);
        });
    }

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

    window.openRisReviewModal = function (risId) {
        const modal = document.getElementById('risReviewModal');
        const iframe = document.getElementById('risReviewIframe');
        window.currentRisId = risId;
        window.setDocFullscreen('risReviewModal', false);
        iframe.src = '/president/ris/' + risId + '/view?preview=1&ts=' + Date.now();
        modal.classList.remove('hidden');
        requestAnimationFrame(() => window.fitRisDocument('risReviewIframe', 'reviewStage', 'reviewFit'));
        iframe.onload = () => window.fitRisDocument('risReviewIframe', 'reviewStage', 'reviewFit');
        if (window.lucide) lucide.createIcons();
        fetch('/president/ris/' + risId + '/details', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                document.getElementById('reviewRisNumber').textContent = data.form_number || ('RIS #' + risId);
                document.getElementById('reviewRequester').textContent = data.requester_name || '—';
                document.getElementById('reviewDateSubmitted').textContent = formatDate(data.created_at || data.requested_by_date);
                document.getElementById('reviewAmount').textContent = formatMoney(data.total_amount);
                const box = document.getElementById('reviewAttachments');
                const list = document.getElementById('reviewAttachmentsList');
                if (box && list) {
                    list.innerHTML = '';
                    const files = data.attachments || [];
                    files.forEach(function (file) {
                        const link = document.createElement('a');
                        link.href = file.url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'review-attachment-link';
                        link.textContent = file.name || 'Attachment';
                        link.setAttribute('data-tip', 'Open attachment');
                        list.appendChild(link);
                    });
                    box.classList.toggle('hidden', files.length === 0);
                }

                requestAnimationFrame(() => window.fitRisDocument('risReviewIframe', 'reviewStage', 'reviewFit'));
            }).catch(() => {});
    };

    window.openAdminForwardDetails = function (risId) {
        const modal = document.getElementById('adminForwardDetailsModal');
        const title = document.getElementById('adminForwardRisTitle');
        const detailsEl = document.getElementById('adminForwardDetailsText');
        const list = document.getElementById('adminForwardAttachmentList');
        const empty = document.getElementById('adminForwardEmpty');
        if (!modal || !detailsEl || !list || !empty) return;

        if (title) title.textContent = 'Supporting details';
        detailsEl.textContent = '';
        detailsEl.classList.add('hidden');
        list.innerHTML = '';
        empty.classList.add('hidden');
        modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();

        fetch('/president/ris/' + risId + '/details', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(data => {
                if (title) {
                    title.textContent = (data.form_number || ('RIS #' + risId)) + ' · Admin details';
                }

                const details = (data.forward_details || '').trim();
                const adminFile = data.forward_attachment || null;
                let hasInfo = false;

                if (details) {
                    detailsEl.textContent = details;
                    detailsEl.classList.remove('hidden');
                    hasInfo = true;
                }

                if (adminFile && adminFile.url) {
                    const link = document.createElement('a');
                    link.href = adminFile.url;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.className = 'review-attachment-link';
                    link.textContent = adminFile.name || 'Admin attachment';
                    link.setAttribute('data-tip', 'Open admin attachment');
                    list.appendChild(link);
                    hasInfo = true;
                }

                empty.classList.toggle('hidden', hasInfo);
            })
            .catch(() => {
                empty.textContent = 'Could not load admin supporting details.';
                empty.classList.remove('hidden');
            });
    };

    window.closeAdminForwardDetails = function () {
        const modal = document.getElementById('adminForwardDetailsModal');
        if (modal) modal.classList.add('hidden');
    };

    window.closeRisReviewModal = function () {
        window.setDocFullscreen('risReviewModal', false);
        const iframe = document.getElementById('risReviewIframe');
        if (iframe) iframe.src = 'about:blank';
        document.getElementById('risReviewModal').classList.add('hidden');
    };
    window.submitRisApproval = function () {
        closeRisReviewModal();
        if (typeof window.openPresidentApproveModal === 'function') {
            openPresidentApproveModal(window.currentRisId);
        } else {
            openDecisionModal('ris', window.currentRisId, 'Approved');
        }
    };
    function setNotifyAdminButtonState(notifyBtn, options) {
        if (!notifyBtn) return;
        options = options || {};
        var label = notifyBtn.querySelector('[data-notify-label]');
        var icon = notifyBtn.querySelector('[data-lucide], svg');
        if (!label) {
            notifyBtn.innerHTML = '<i data-lucide="bell" class="h-4 w-4"></i><span data-notify-label></span>';
            label = notifyBtn.querySelector('[data-notify-label]');
        } else if (!icon) {
            notifyBtn.insertAdjacentHTML('afterbegin', '<i data-lucide="bell" class="h-4 w-4"></i>');
        }
        if (label) label.textContent = options.label || 'Notify Admin';

        if (options.notified) {
            notifyBtn.classList.remove('hidden');
            notifyBtn.classList.add('is-notified');
            notifyBtn.disabled = true;
            notifyBtn.setAttribute('data-tip', 'Admin has already been notified');
            notifyBtn.setAttribute('aria-disabled', 'true');
        } else if (options.hidden) {
            notifyBtn.classList.add('hidden');
            notifyBtn.classList.remove('is-notified');
        } else {
            notifyBtn.classList.remove('hidden', 'is-notified');
            notifyBtn.disabled = false;
            notifyBtn.setAttribute('data-tip', 'Notify Admin for co-sign');
            notifyBtn.setAttribute('aria-disabled', 'false');
        }
        if (window.lucide) lucide.createIcons();
    }

    window.openApprovedRisPreviewModal = function (risId, options) {
        options = options || {};
        const modal = document.getElementById('approvedRisPreviewModal');
        const iframe = document.getElementById('approvedRisIframe');
        const notifyBtn = document.getElementById('sendApprovedRisBtn');
        const attachBox = document.getElementById('previewAttachments');
        const attachList = document.getElementById('previewAttachmentsList');
        window.currentRisId = risId;
        window.approvedPreviewDirty = !!options.afterApprove;
        window.setDocFullscreen('approvedRisPreviewModal', false);
        if (attachBox) attachBox.classList.remove('hidden');
        if (attachList) {
            attachList.innerHTML = '<span class="text-xs text-slate-400">Loading…</span>';
        }
        iframe.src = '/president/ris/' + encodeURIComponent(risId) + '/view?preview=1&ts=' + Date.now();
        document.getElementById('previewPresidentName').textContent = presidentDisplayName;
        document.getElementById('previewApprovedDate').textContent = options.approvedDate
            || new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
        setNotifyAdminButtonState(notifyBtn, { label: 'Notify Admin' });
        modal.classList.remove('hidden');
        requestAnimationFrame(() => window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit'));
        iframe.onload = () => window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit');
        if (window.lucide) lucide.createIcons();

        fetch('/president/ris/' + encodeURIComponent(risId) + '/details', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.ok ? res.json() : Promise.reject(new Error('details failed')))
        .then(data => {
            if (data.approved_by_date) {
                document.getElementById('previewApprovedDate').textContent = new Date(data.approved_by_date)
                    .toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
            }
            if (notifyBtn) {
                if (data.admin_notified) {
                    setNotifyAdminButtonState(notifyBtn, { label: 'Admin Notified', notified: true });
                } else if (data.awaiting_notify || data.is_president_approved) {
                    setNotifyAdminButtonState(notifyBtn, { label: 'Notify Admin' });
                } else {
                    setNotifyAdminButtonState(notifyBtn, { hidden: true });
                }
            }
            if (attachBox && attachList) {
                attachList.innerHTML = '';
                const files = Array.isArray(data.attachments) ? data.attachments : [];
                if (!files.length) {
                    attachList.innerHTML = '<span class="text-xs text-slate-400">None attached</span>';
                } else {
                    files.forEach(function (file) {
                        const link = document.createElement('a');
                        link.href = file.url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'review-attachment-link';
                        link.textContent = file.name || 'Attachment';
                        link.setAttribute('data-tip', 'Open attachment');
                        attachList.appendChild(link);
                    });
                }
                attachBox.classList.remove('hidden');
            }
            requestAnimationFrame(() => window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit'));
        }).catch(() => {
            if (attachList) {
                attachList.innerHTML = '<span class="text-xs text-slate-400">None attached</span>';
            }
        });
    };
    window.closeApprovedRisPreviewModal = function (shouldRefresh) {
        window.setDocFullscreen('approvedRisPreviewModal', false);
        const iframe = document.getElementById('approvedRisIframe');
        if (iframe) iframe.src = 'about:blank';
        document.getElementById('approvedRisPreviewModal').classList.add('hidden');
        const attachList = document.getElementById('previewAttachmentsList');
        if (attachList) attachList.innerHTML = '';
        if (shouldRefresh && window.approvedPreviewDirty) {
            window.approvedPreviewDirty = false;
            window.location.reload();
        }
    };
    window.sendApprovedRisToAdmin = function () { confirmSendRis(); };
    function confirmSendRis() {
        const btn = document.getElementById('confirmSendActionBtn');
        if (btn) btn.dataset.risId = window.currentRisId;
        document.getElementById('sendConfirmationModal').classList.remove('hidden');
    }
    function closeSendConfirmationModal() {
        document.getElementById('sendConfirmationModal').classList.add('hidden');
    }
    function executeSendRis() {
        const confirmBtn = document.getElementById('confirmSendActionBtn');
        const risId = (confirmBtn && confirmBtn.dataset.risId) || window.currentRisId;
        if (!risId || sendInFlight) return;
        sendInFlight = true;
        const original = confirmBtn.textContent;
        confirmBtn.textContent = 'Notifying...';
        confirmBtn.disabled = true;
        fetch('/president/ris/' + risId + '/send-to-admin', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({})
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to notify Admin.');
            unpinRis(risId);
            closeSendConfirmationModal();
            window.approvedPreviewDirty = false;
            closeApprovedRisPreviewModal(false);
            showToast(data.message || 'Admin has been notified.', { title: 'Success', type: 'success' });
            setTimeout(() => window.location.reload(), 900);
        })
        .catch(error => {
            sendInFlight = false;
            confirmBtn.textContent = original;
            confirmBtn.disabled = false;
            if (typeof window.showMpToast === 'function') {
                showMpToast(error.message || 'Unable to notify Admin.', { title: 'Unable to complete', type: 'error', timer: 4200 });
            } else {
                alert(error.message);
            }
        });
    }
    function showToast(message, options) {
        if (typeof window.showMpToast === 'function') {
            return window.showMpToast(message, options || { title: 'Success', type: 'success' });
        }
        const toast = document.createElement('div');
        toast.className = 'pm-toast';
        toast.textContent = typeof message === 'string' ? message : (message?.message || 'Done');
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2800);
    }
    function openDecisionModal(type, id, presetDecision) {
        if ((presetDecision || '').toLowerCase() === 'approved') {
            if (typeof window.openPresidentApproveModal === 'function') {
                openPresidentApproveModal(id);
                return;
            }
        }
        const form = document.getElementById('decisionForm');
        document.getElementById('targetType').value = type;
        document.getElementById('targetId').value = id;
        document.getElementById('targetDecision').value = presetDecision || 'Rejected';
        const remarks = form.querySelector('textarea[name="remarks"]');
        if (remarks) remarks.value = '';
        document.getElementById('decisionModalSubtitle').textContent = 'RIS #' + id;
        form.action = '/president/approvals/ris/decide';
        document.getElementById('decisionModalTitle').textContent = 'Reject RIS';
        document.getElementById('decisionModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }
    function closeDecisionModal() {
        document.getElementById('decisionModal').classList.add('hidden');
    }
    function submitDecision(decision) {
        if (decideInFlight) return;
        const form = document.getElementById('decisionForm');
        document.getElementById('targetDecision').value = decision || 'Rejected';
        if ((decision || 'Rejected') !== 'Rejected') {
            if (typeof window.openPresidentApproveModal === 'function') {
                closeDecisionModal();
                openPresidentApproveModal(document.getElementById('targetId').value);
            }
            return;
        }
        const remarksField = form.querySelector('textarea[name="remarks"]');
        if (!remarksField || !remarksField.value.trim()) {
            if (typeof window.showMpToast === 'function') {
                showMpToast('Please provide a rejection reason.', { title: 'Remarks required', type: 'warning', timer: 3600 });
            } else {
                alert('Please provide a rejection reason.');
            }
            return;
        }
        decideInFlight = true;
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: new FormData(form)
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to save decision.');
            closeDecisionModal();
            showToast('RIS rejected successfully.', { title: 'Rejected', type: 'success' });
            setTimeout(() => window.location.reload(), 900);
        })
        .catch(error => {
            if (typeof window.showMpToast === 'function') {
                showMpToast(error.message || 'Unable to save decision.', { title: 'Unable to complete', type: 'error', timer: 4200 });
            } else {
                alert(error.message);
            }
        })
        .finally(() => { decideInFlight = false; });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeRisReviewModal();
        closeApprovedRisPreviewModal(true);
        closeDecisionModal();
        closeSendConfirmationModal();
    });

    (function bootFromQuery() {
        const params = new URLSearchParams(window.location.search);
        const approveId = params.get('approve');
        const previewId = params.get('preview');
        if (approveId) {
            openRisReviewModal(approveId);
            history.replaceState({}, '', window.location.pathname);
        } else if (previewId) {
            openApprovedRisPreviewModal(previewId);
            history.replaceState({}, '', window.location.pathname);
        }
    })();
</script>

@endsection
