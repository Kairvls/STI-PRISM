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
            <div class="recent-list">
                @forelse ($recentRis as $ris)
                    @php
                        $isApproved = !empty($ris->is_president_approved);
                        $isRejected = in_array((string) ($ris->ris_status ?? ''), ['Rejected', 'Rejected by President', 'Rejected by the President'], true);
                        $displayStatus = $isApproved
                            ? (!empty($ris->awaiting_notify) ? 'Notify Admin' : 'Approved')
                            : ($isRejected ? 'Rejected' : 'Pending');
                        $statusClass = $isApproved
                            ? (!empty($ris->awaiting_notify) ? 'status-notify' : 'status-approved')
                            : ($isRejected ? 'status-rejected' : 'status-pending');
                    @endphp
                    <div class="recent-row">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $ris->ris_form_number ?? 'RIS #' . $ris->ris_id }}</p>
                            <p class="truncate text-xs text-gray-500">{{ Str::limit($ris->ris_purpose_description ?? '—', 42) }}</p>
                        </div>
                        <span class="status-pill {{ $statusClass }}">{{ $displayStatus }}</span>
                        <div class="row-actions">
                            @if ($isApproved)
                                <button type="button" class="icon-btn" data-tip="Open approved RIS" aria-label="Open approved RIS" onclick="openApprovedRisPreviewModal({{ $ris->ris_id }})">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                            @endif
                            <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                                <i data-lucide="printer" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="empty-note">No recent decisions</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>

<div id="risReviewModal" class="doc-modal hidden">
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
        <div id="reviewAttachments" class="review-attachments hidden">
            <p class="review-attachments-label">Supporting documents</p>
            <div id="reviewAttachmentsList" class="review-attachments-list"></div>
        </div>
        <div class="doc-actions">
            <button type="button" class="btn-reject" data-tip="Reject this RIS" onclick="openDecisionModal('ris', window.currentRisId, 'Rejected')">Reject</button>
            <button type="button" class="btn-approve" data-tip="Sign and approve" onclick="submitRisApproval()">Approve</button>
            <button type="button" class="btn-ghost" data-tip="Close without deciding" onclick="closeRisReviewModal()">Close</button>
        </div>
    </div>
</div>

<div id="approvedRisPreviewModal" class="doc-modal hidden">
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
        <div class="doc-actions">
            <button type="button" class="btn-send" id="sendApprovedRisBtn" data-tip="Notify Admin for co-sign" onclick="sendApprovedRisToAdmin()">Notify Admin</button>
            <button type="button" class="btn-ghost" data-tip="Keep approval and close" onclick="closeApprovedRisPreviewModal(true)">Close</button>
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
            <input type="hidden" name="signature_data" id="signatureData" value="" />
            <input type="hidden" name="signature_used" id="signatureUsed" value="0" />
            <div id="signatureBlock" class="hidden mt-4">
                <label>Digital signature</label>
                <p class="hint">Sign to approve this RIS.</p>
                <canvas id="signatureCanvas" width="520" height="160"></canvas>
                <button type="button" class="btn-ghost sm" data-tip="Clear signature" onclick="clearSignature()">Clear</button>
            </div>
            <div class="mt-4">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" placeholder="Optional for approve. Required for reject."></textarea>
            </div>
            <div class="confirm-actions mt-5">
                <button type="button" class="btn-ghost" data-tip="Cancel decision" onclick="closeDecisionModal()">Cancel</button>
                <button type="button" id="approveBtn" class="btn-approve" data-tip="Confirm approval" onclick="submitDecision('Approved')">Approve</button>
                <button type="button" id="rejectBtn" class="btn-reject" data-tip="Confirm rejection" onclick="submitDecision('Rejected')">Reject</button>
            </div>
        </form>
    </div>
</div>

@include('president.partials.ris-fit-viewer')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        initSignatureCanvas();
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
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300">&laquo;</span>'
            : '<button type="button" onclick="' + fnName + '(' + (current - 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&laquo;</button>') + '</li>';

        for (let i = start; i <= end; i++) {
            if (i === current) {
                html += '<li><span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-sm font-semibold text-white">' + i + '</span></li>';
            } else {
                html += '<li><button type="button" onclick="' + fnName + '(' + i + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">' + i + '</button></li>';
            }
        }

        const nextDisabled = current >= last;
        html += '<li>' + (nextDisabled
            ? '<span class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300">&raquo;</span>'
            : '<button type="button" onclick="' + fnName + '(' + (current + 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">&raquo;</button>') + '</li>';

        html += '</ul></nav>';
        return html;
    }
    window.goToPage = fetchTableData;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchTableData(1), 300);
        });
    }

    window.printRisDocument = function (risId) {
        if (!risId) return;
        const win = window.open('/president/ris/' + risId + '/print', '_blank', 'noopener,noreferrer,width=1200,height=860');
        if (!win) return;
        const triggerPrint = function () {
            try { win.focus(); win.print(); } catch (e) {}
        };
        win.onload = triggerPrint;
        setTimeout(triggerPrint, 1200);
    };

    window.openRisReviewModal = function (risId) {
        const modal = document.getElementById('risReviewModal');
        const iframe = document.getElementById('risReviewIframe');
        window.currentRisId = risId;
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
            }).catch(() => {});
    };
    window.closeRisReviewModal = function () {
        const iframe = document.getElementById('risReviewIframe');
        if (iframe) iframe.src = 'about:blank';
        document.getElementById('risReviewModal').classList.add('hidden');
    };
    window.submitRisApproval = function () {
        closeRisReviewModal();
        openDecisionModal('ris', window.currentRisId, 'Approved');
    };
    window.openApprovedRisPreviewModal = function (risId, options) {
        options = options || {};
        const modal = document.getElementById('approvedRisPreviewModal');
        const iframe = document.getElementById('approvedRisIframe');
        const notifyBtn = document.getElementById('sendApprovedRisBtn');
        window.currentRisId = risId;
        window.approvedPreviewDirty = !!options.afterApprove;
        iframe.src = '/president/ris/' + risId + '/view?preview=1&ts=' + Date.now();
        document.getElementById('previewPresidentName').textContent = presidentDisplayName;
        document.getElementById('previewApprovedDate').textContent = options.approvedDate
            || new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
        if (notifyBtn) {
            notifyBtn.classList.remove('hidden');
            notifyBtn.disabled = false;
            notifyBtn.textContent = 'Notify Admin';
        }
        modal.classList.remove('hidden');
        requestAnimationFrame(() => window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit'));
        iframe.onload = () => window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit');
        if (window.lucide) lucide.createIcons();

        fetch('/president/ris/' + risId + '/details', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.approved_by_date) {
                document.getElementById('previewApprovedDate').textContent = new Date(data.approved_by_date)
                    .toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
            }
            if (notifyBtn) {
                if (data.admin_notified) {
                    notifyBtn.disabled = true;
                    notifyBtn.textContent = 'Admin Notified';
                } else if (data.awaiting_notify || data.is_president_approved) {
                    notifyBtn.classList.remove('hidden');
                    notifyBtn.disabled = false;
                    notifyBtn.textContent = 'Notify Admin';
                } else {
                    notifyBtn.classList.add('hidden');
                }
            }
        }).catch(() => {});
    };
    window.closeApprovedRisPreviewModal = function (shouldRefresh) {
        const iframe = document.getElementById('approvedRisIframe');
        if (iframe) iframe.src = 'about:blank';
        document.getElementById('approvedRisPreviewModal').classList.add('hidden');
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
        const form = document.getElementById('decisionForm');
        document.getElementById('targetType').value = type;
        document.getElementById('targetId').value = id;
        document.getElementById('targetDecision').value = presetDecision || '';
        document.getElementById('signatureData').value = '';
        document.getElementById('signatureUsed').value = '0';
        const remarks = form.querySelector('textarea[name="remarks"]');
        if (remarks) remarks.value = '';
        const canvas = document.getElementById('signatureCanvas');
        if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('decisionModalSubtitle').textContent = 'RIS #' + id;
        form.action = '/president/approvals/ris/decide';
        const isApproved = (presetDecision || '').toLowerCase() === 'approved';
        document.getElementById('signatureBlock').classList.toggle('hidden', !isApproved);
        document.getElementById('approveBtn').classList.toggle('hidden', !isApproved);
        document.getElementById('rejectBtn').classList.toggle('hidden', isApproved);
        document.getElementById('decisionModalTitle').textContent = isApproved ? 'Sign to approve' : 'Reject RIS';
        document.getElementById('decisionModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }
    function closeDecisionModal() {
        document.getElementById('decisionModal').classList.add('hidden');
    }
    function submitDecision(decision) {
        if (decideInFlight) return;
        const form = document.getElementById('decisionForm');
        document.getElementById('targetDecision').value = decision;
        if (decision === 'Approved') {
            const canvas = document.getElementById('signatureCanvas');
            const pixels = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
            let hasDrawing = false;
            for (let i = 3; i < pixels.length; i += 4) { if (pixels[i] > 0) { hasDrawing = true; break; } }
            if (!hasDrawing) {
                if (typeof window.showMpToast === 'function') {
                    showMpToast('Please sign the RIS before approving.', { title: 'Signature required', type: 'warning', timer: 3600 });
                } else {
                    alert('Please sign the RIS before approving.');
                }
                return;
            }
            document.getElementById('signatureData').value = canvas.toDataURL('image/png');
            document.getElementById('signatureUsed').value = '1';
        } else {
            const remarksField = form.querySelector('textarea[name="remarks"]');
            if (!remarksField || !remarksField.value.trim()) {
                if (typeof window.showMpToast === 'function') {
                    showMpToast('Please provide a rejection reason.', { title: 'Remarks required', type: 'warning', timer: 3600 });
                } else {
                    alert('Please provide a rejection reason.');
                }
                return;
            }
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
            const risId = data.ris_id || document.getElementById('targetId').value;
            closeDecisionModal();
            if (decision === 'Approved') {
                openApprovedRisPreviewModal(risId, {
                    afterApprove: true,
                    approvedDate: data.approved_by_date
                        ? new Date(data.approved_by_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })
                        : null
                });
                showToast('RIS approved. Notify Admin when ready.', { title: 'Approved', type: 'success' });
            } else {
                showToast('RIS rejected successfully.', { title: 'Rejected', type: 'success' });
                setTimeout(() => window.location.reload(), 900);
            }
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
    function initSignatureCanvas() {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.lineWidth = 2.5; ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.strokeStyle = '#1f2937';
        let drawing = false, lastX = 0, lastY = 0;
        function getPos(evt) {
            const rect = canvas.getBoundingClientRect();
            const clientX = evt.touches ? evt.touches[0].clientX : evt.clientX;
            const clientY = evt.touches ? evt.touches[0].clientY : evt.clientY;
            return { x: (clientX - rect.left) * (canvas.width / rect.width), y: (clientY - rect.top) * (canvas.height / rect.height) };
        }
        function start(evt) { drawing = true; const p = getPos(evt); lastX = p.x; lastY = p.y; }
        function move(evt) { if (!drawing) return; const p = getPos(evt); ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(p.x, p.y); ctx.stroke(); lastX = p.x; lastY = p.y; }
        function end() { drawing = false; }
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', (e) => { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', end);
    }
    function clearSignature() {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('signatureData').value = '';
        document.getElementById('signatureUsed').value = '0';
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
