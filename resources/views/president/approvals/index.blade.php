@extends('layouts.president-layout')

@section('title', 'RIS Approvals')

@section('content')

<div class="ris-workspace">
    <header class="ris-page-header">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">RIS Approvals</h1>
            <p class="mt-1 text-sm leading-6 text-gray-500">Review forwarded RIS documents and send approved records to Admin.</p>
        </div>
        <div class="metric-row">
            <div class="metric metric-pending">
                <span>Pending</span>
                <strong>{{ number_format($totalPendingRis ?? 0) }}</strong>
            </div>
            <div class="metric metric-approved">
                <span>Approved</span>
                <strong>{{ number_format($totalApprovedRis ?? 0) }}</strong>
            </div>
            <div class="metric metric-rejected">
                <span>Rejected</span>
                <strong>{{ number_format($totalRejectedRis ?? 0) }}</strong>
            </div>
            <div class="metric metric-value">
                <span>Awaiting value</span>
                <strong>₱{{ number_format($pendingValue ?? 0, 2) }}</strong>
            </div>
        </div>
    </header>

    <div class="ris-layout">
        <section class="queue-panel">
            <div class="panel-head">
                <div>
                    <h2>Queue</h2>
                    <p>{{ $pendingRis->total() }} awaiting review</p>
                </div>
                <div class="search-wrap">
                    <input type="text" id="queueSearch" value="{{ request('search') }}" placeholder="Search RIS, requester, purpose" autocomplete="off" />
                </div>
            </div>
            <div id="queueList">
                @include('president.approvals._table')
            </div>
            <div id="risPagination" class="{{ $pendingRis->hasPages() ? '' : 'hidden' }}">
                @if ($pendingRis->hasPages())
                    {{ $pendingRis->links() }}
                @endif
            </div>
        </section>

        <aside class="recent-panel">
            <div class="panel-head compact">
                <div>
                    <h2>Recent</h2>
                    <p>
                        @if (!empty($latestSubmitted))
                            Latest in queue: {{ $latestSubmitted->ris_form_number ?? ('RIS #' . $latestSubmitted->ris_id) }}
                        @else
                            Latest decisions
                        @endif
                    </p>
                </div>
            </div>
            <div class="recent-list">
                @forelse ($recentRis as $ris)
                    @php
                        $rawStatus = (string) ($ris->ris_status ?? '');
                        $isApproved = in_array($rawStatus, ['Approved', 'Approved by the President'], true);
                        $isRejected = in_array($rawStatus, ['Rejected', 'Rejected by President', 'Rejected by the President'], true);
                        $displayStatus = $isApproved ? 'Approved' : ($isRejected ? 'Rejected' : 'Pending');
                    @endphp
                    <div class="recent-item">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $ris->ris_form_number ?? 'RIS #' . $ris->ris_id }}</p>
                            <p class="truncate text-xs text-gray-500">{{ Str::limit($ris->ris_purpose_description ?? '—', 42) }}</p>
                        </div>
                        <span class="status-pill status-{{ strtolower($displayStatus) }}">{{ $displayStatus }}</span>
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
            <button type="button" class="icon-close" onclick="closeRisReviewModal()" aria-label="Close"><i data-lucide="x"></i></button>
        </header>
        <div class="doc-stage" id="reviewStage">
            <div class="doc-fit" id="reviewFit">
                <iframe id="risReviewIframe" title="RIS document" scrolling="no" src="about:blank"></iframe>
            </div>
        </div>
        <div class="doc-actions">
            <button type="button" class="btn-ghost" onclick="closeRisReviewModal()">Close</button>
            <button type="button" class="btn-reject" onclick="openDecisionModal('ris', window.currentRisId, 'Rejected')">Reject</button>
            <button type="button" class="btn-approve" onclick="submitRisApproval()">Approve</button>
        </div>
    </div>
</div>

<div id="approvedRisPreviewModal" class="doc-modal hidden">
    <div class="doc-backdrop" onclick="closeApprovedRisPreviewModal()"></div>
    <div class="doc-shell" onclick="event.stopPropagation()">
        <header class="doc-head">
            <div class="min-w-0">
                <p class="eyebrow" style="color:#059669">Approved</p>
                <h2>Final RIS</h2>
                <p class="doc-meta">
                    <span id="previewPresidentName">—</span><span>·</span>
                    <span id="previewApprovedDate">—</span>
                </p>
            </div>
            <button type="button" class="icon-close" onclick="closeApprovedRisPreviewModal()" aria-label="Close"><i data-lucide="x"></i></button>
        </header>
        <div class="doc-stage" id="previewStage">
            <div class="doc-fit" id="previewFit">
                <iframe id="approvedRisIframe" title="Approved RIS" scrolling="no" src="about:blank"></iframe>
            </div>
        </div>
        <div class="doc-actions">
            <button type="button" class="btn-ghost" onclick="closeApprovedRisPreviewModal()">Close</button>
            <button type="button" class="btn-send" id="sendApprovedRisBtn" onclick="sendApprovedRisToAdmin()">Send Back to Admin</button>
        </div>
    </div>
</div>

<div id="sendConfirmationModal" class="confirm-modal hidden">
    <div class="confirm-backdrop" onclick="closeSendConfirmationModal()"></div>
    <div class="confirm-card" onclick="event.stopPropagation()">
        <h3>Send back to Admin?</h3>
        <p>This forwards the approved RIS for Admin co-sign. The document is not duplicated.</p>
        <div class="confirm-actions">
            <button type="button" class="btn-ghost" onclick="closeSendConfirmationModal()">Cancel</button>
            <button type="button" class="btn-send" id="confirmSendActionBtn" onclick="executeSendRis()">Confirm</button>
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
            <button type="button" class="icon-close" onclick="closeDecisionModal()" aria-label="Close"><i data-lucide="x"></i></button>
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
                <button type="button" class="btn-ghost sm" onclick="clearSignature()">Clear</button>
            </div>
            <div class="mt-4">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" placeholder="Optional for approve. Required for reject."></textarea>
            </div>
            <div class="confirm-actions mt-5">
                <button type="button" class="btn-ghost" onclick="closeDecisionModal()">Cancel</button>
                <button type="button" id="approveBtn" class="btn-approve" onclick="submitDecision('Approved')">Approve</button>
                <button type="button" id="rejectBtn" class="btn-reject" onclick="submitDecision('Rejected')">Reject</button>
            </div>
        </form>
    </div>
</div>

<style>
    .ris-workspace { animation: fadeIn .35s ease; }
    .eyebrow { margin: 0; font-size: 0.75rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; }
    .ris-page-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
    .metric-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .metric { min-width: 92px; padding: 8px 12px; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; }
    .metric span { display: block; font-size: 0.75rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
    .metric strong { display: block; margin-top: 2px; font-size: 1.5rem; line-height: 2rem; font-weight: 700; }
    .metric.metric-value strong { font-size: 1.125rem; line-height: 1.75rem; color: #111827; }
    .metric-pending strong { color: #d97706; }
    .metric-approved strong { color: #059669; }
    .metric-rejected strong { color: #e11d48; }
    .ris-layout { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(260px, .8fr); gap: 16px; }
    .queue-panel, .recent-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 8px 24px rgba(15, 23, 42, .04); overflow: hidden; }
    .panel-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
    .panel-head h2 { margin: 0; font-size: 0.875rem; line-height: 1.25rem; font-weight: 600; color: #111827; }
    .panel-head p { margin: 2px 0 0; font-size: 0.75rem; line-height: 1rem; color: #6b7280; }
    .search-wrap { min-width: 220px; flex: 1; max-width: 320px; }
    .search-wrap input { width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; font-size: 0.875rem; line-height: 1.25rem; outline: none; background: #f8fafc; }
    .search-wrap input:focus { border-color: #fbbf24; background: #fff; box-shadow: 0 0 0 3px rgba(251, 191, 36, .15); }
    #queueList { padding: 8px; }
    #queueList.updating { opacity: .55; }
    .queue-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; cursor: pointer; }
    .queue-item:hover { background: #f8fafc; }
    .review-btn { flex-shrink: 0; border: 0; border-radius: 9px; padding: 6px 12px; font-size: 0.75rem; line-height: 1rem; font-weight: 600; color: #fff; background: #0f172a; cursor: pointer; }
    .empty-queue, .empty-note { padding: 36px 16px; text-align: center; color: #6b7280; font-size: 0.875rem; line-height: 1.25rem; }
    .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 8px; font-size: 0.75rem; line-height: 1rem; font-weight: 600; }
    .status-pending { background: #fffbeb; color: #b45309; }
    .status-approved { background: #ecfdf5; color: #047857; }
    .status-rejected { background: #fff1f2; color: #be123c; }
    .recent-list { padding: 8px; }
    .recent-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 9px 8px; border-radius: 10px; }
    #risPagination { padding: 8px 16px 12px; border-top: 1px solid #f1f5f9; font-size: 0.875rem; }
    .confirm-modal { position: fixed; inset: 0; z-index: 90; display: grid; place-items: center; }
    .confirm-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .4); }
    .confirm-card { position: relative; width: min(420px, calc(100vw - 32px)); background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 20px 60px rgba(15, 23, 42, .2); }
    .confirm-card.wide { width: min(560px, calc(100vw - 32px)); }
    .confirm-card h3 { margin: 0; font-size: 1.125rem; line-height: 1.75rem; font-weight: 700; color: #0f172a; }
    .confirm-card p { margin: 6px 0 0; font-size: 0.875rem; line-height: 1.5rem; color: #64748b; }
    .confirm-card label { display: block; font-size: 0.875rem; line-height: 1.25rem; font-weight: 500; color: #334155; }
    .confirm-card .hint { margin: 4px 0 8px; font-size: 0.75rem; line-height: 1rem; }
    .confirm-card textarea, #signatureCanvas { width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; margin-top: 6px; }
    .confirm-card textarea { padding: 8px 10px; font-size: 0.875rem; line-height: 1.25rem; resize: none; }
    #signatureCanvas { height: 160px; touch-action: none; }
    .confirm-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
    .btn-ghost.sm { padding: 6px 10px; font-size: 0.75rem; line-height: 1rem; font-weight: 600; margin-top: 8px; }
    .hidden { display: none !important; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @media (max-width: 1024px) { .ris-layout { grid-template-columns: 1fr; } }
</style>

@include('president.partials.ris-fit-viewer')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        initSignatureCanvas();
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const presidentDisplayName = @json(Auth::user()->user_full_name ?? 'President');
    const searchInput = document.getElementById('queueSearch');
    let searchTimeout = null, sendInFlight = false, decideInFlight = false;

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
            if (pagination) pagination.classList.toggle('hidden', !(data.last_page > 1));
            if (window.lucide) lucide.createIcons();
        })
        .catch(() => { if (queueList) queueList.classList.remove('updating'); });
    }
    window.goToPage = fetchTableData;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchTableData(1), 300);
        });
    }

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
    window.openApprovedRisPreviewModal = function (risId) {
        const modal = document.getElementById('approvedRisPreviewModal');
        const iframe = document.getElementById('approvedRisIframe');
        window.currentRisId = risId;
        iframe.src = '/president/ris/' + risId + '/print?preview=1&ts=' + Date.now();
        document.getElementById('previewPresidentName').textContent = presidentDisplayName;
        document.getElementById('previewApprovedDate').textContent = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
        modal.classList.remove('hidden');
        requestAnimationFrame(() => window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit'));
        iframe.onload = () => window.fitRisDocument('approvedRisIframe', 'previewStage', 'previewFit');
        if (window.lucide) lucide.createIcons();
    };
    window.closeApprovedRisPreviewModal = function () {
        const iframe = document.getElementById('approvedRisIframe');
        if (iframe) iframe.src = 'about:blank';
        document.getElementById('approvedRisPreviewModal').classList.add('hidden');
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
        confirmBtn.textContent = 'Sending...';
        confirmBtn.disabled = true;
        fetch('/president/ris/' + risId + '/send-to-admin', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({})
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to send RIS to Admin.');
            closeSendConfirmationModal();
            closeApprovedRisPreviewModal();
            showToast('RIS sent back to Admin successfully.');
            setTimeout(() => window.location.reload(), 900);
        })
        .catch(error => {
            sendInFlight = false;
            confirmBtn.textContent = original;
            confirmBtn.disabled = false;
            alert(error.message);
        });
    }
    function showToast(message) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#059669;color:#fff;padding:10px 16px;border-radius:10px;font-size:13px;z-index:100';
        toast.textContent = message;
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
            if (!hasDrawing) { alert('Please sign the RIS before approving.'); return; }
            document.getElementById('signatureData').value = canvas.toDataURL('image/png');
            document.getElementById('signatureUsed').value = '1';
        } else {
            const remarksField = form.querySelector('textarea[name="remarks"]');
            if (!remarksField || !remarksField.value.trim()) { alert('Please provide a rejection reason.'); return; }
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
            if (decision === 'Approved') openApprovedRisPreviewModal(risId);
            else { showToast('RIS rejected successfully.'); setTimeout(() => window.location.reload(), 900); }
        })
        .catch(error => alert(error.message))
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
        closeApprovedRisPreviewModal();
        closeDecisionModal();
        closeSendConfirmationModal();
    });
</script>

@endsection
