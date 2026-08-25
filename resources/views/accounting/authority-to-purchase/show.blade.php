@extends('layouts.accounting-layout')

@section('title', 'Review ATP')

@section('content')
@php
    $supplier = ($atp->supplier_store_type ?? '') === 'Physical Store' ? ($atp->company_name ?? '—') : ($atp->shop_name ?? '—');
    $total = $items->sum(fn ($i) => (float) ($i->atp_amount ?? 0));
@endphp
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-review-head">
        <div>
            <a href="/accounting/authority-to-purchase?status={{ urlencode($returnStatus ?? 'incoming') }}" class="acc-back" data-tip="Back to ATP queue" aria-label="Back to ATP queue">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $atp->authority_purchase_form_number }}</h1>
                @include('accounting.partials.status-badge', ['status' => \App\Support\RisWorkflow::atpStatusLabel($atp), 'submitted' => $atp->authority_purchase_submitted_at, 'revision' => $atp->authority_purchase_rejection_reason])
            </div>
            <p class="acc-page-subtitle">Purchaser ATP form. Actions stay outside the document.</p>
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Approved')" class="icon-btn" data-tip="Approve ATP" aria-label="Approve ATP">
                    <i data-lucide="check" class="h-4 w-4"></i>
                </button>
                <button type="button" onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Rejected')" class="icon-btn" data-tip="Request revision" aria-label="Request revision">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                </button>
            @endif
            <button type="button" class="icon-btn" onclick="window.print()" data-tip="Print ATP" aria-label="Print ATP">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <div class="acc-review-grid">
            <div class="acc-viewer">
                <div class="acc-viewer-stage">
                    <div class="acc-viewer-fit">
                        <div class="acc-paper">
                        <div class="acc-paper-title">
                            <p class="org">STI COLLEGE- ORMOC, INC.</p>
                            <p class="doc">AUTHORITY TO PURCHASE</p>
                        </div>
                        <dl>
                            <div><dt>ATP No.</dt><dd>{{ $atp->authority_purchase_form_number }}</dd></div>
                            <div><dt>Date</dt><dd>{{ $atp->authority_purchase_date ? \Carbon\Carbon::parse($atp->authority_purchase_date)->format('F d, Y') : '—' }}</dd></div>
                            <div><dt>RIS</dt><dd>{{ $atp->ris_form_number ?? '—' }}</dd></div>
                            <div><dt>Supplier</dt><dd>{{ $supplier }}</dd></div>
                            <div class="sm:col-span-2"><dt>Purpose</dt><dd>{{ $atp->ris_purpose_description ?? '—' }}</dd></div>
                        </dl>
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="!text-right">Qty</th>
                                    <th class="!text-right">Unit</th>
                                    <th class="!text-right">Unit price</th>
                                    <th class="!text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $item->atp_description }}</td>
                                        <td class="text-right">{{ $item->atp_quantity }}</td>
                                        <td class="text-right">{{ $item->atp_unit }}</td>
                                        <td class="text-right">{{ $item->atp_unit_price !== null ? number_format($item->atp_unit_price, 2) : '—' }}</td>
                                        <td class="text-right">{{ $item->atp_amount !== null ? number_format($item->atp_amount, 2) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-semibold">
                                    <td colspan="4" class="text-right px-2 py-1.5">Total</td>
                                    <td class="text-right px-2 py-1.5">₱{{ number_format($total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="mt-6 grid grid-cols-2 gap-6 text-center text-xs">
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-slate-400">Received by</p>
                                <p class="mt-6 border-b border-slate-800 pb-1">{{ $atp->authority_purchase_received_by_name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-slate-400">Authorized by Accounting</p>
                                <p class="mt-6 border-b border-slate-800 pb-1 min-h-[3rem]">
                                    @include('partials.drawn-signature', ['value' => $atp->authority_purchase_authorized_by_signature ?? ''])
                                </p>
                            </div>
                        </div>
                        @if ($atp->authority_purchase_rejection_reason)
                            <p class="mt-4 rounded-lg bg-sky-50 p-2.5 text-xs text-sky-900">Revision remarks: {{ $atp->authority_purchase_rejection_reason }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="acc-side-stack">
            @include('accounting.partials.related-docs', ['chain' => $chain])
            @include('accounting.partials.history', ['history' => $history])
        </div>
    </div>
</div>

{{-- Decision modal (President-style) --}}
<div id="decisionModal" class="confirm-modal hidden">
    <div class="confirm-backdrop" onclick="closeDecisionModal()"></div>
    <div class="confirm-card wide" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 id="decisionModalTitle">Decision</h3>
                <p id="decisionModalSubtitle">Approve or request revision for the selected ATP</p>
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
                <p class="hint">Sign to authorize this Authority to Purchase.</p>
                <canvas id="signatureCanvas" width="520" height="160"></canvas>
                <button type="button" class="btn-ghost sm" data-tip="Clear signature" onclick="clearSignature()">Clear</button>
            </div>
            <div class="mt-4">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" placeholder="Optional for approve. Required for revision."></textarea>
            </div>
            <div class="confirm-actions mt-5">
                <button type="button" class="btn-ghost" data-tip="Cancel decision" onclick="closeDecisionModal()">Cancel</button>
                <button type="button" id="approveBtn" class="btn-approve" data-tip="Confirm approval" onclick="submitDecision('Approved')">Approve</button>
                <button type="button" id="rejectBtn" class="btn-reject" data-tip="Confirm revision" onclick="submitDecision('Rejected')">Send to Purchaser</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        initSignatureCanvas();
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let decideInFlight = false;

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
        const isApproved = (presetDecision || '').toLowerCase() === 'approved';
        document.getElementById('decisionModalSubtitle').textContent = '{{ $atp->authority_purchase_form_number }}';
        form.action = '/accounting/authority-to-purchase/' + id + (isApproved ? '/approve' : '/revise');
        document.getElementById('signatureBlock').classList.toggle('hidden', !isApproved);
        document.getElementById('approveBtn').classList.toggle('hidden', !isApproved);
        document.getElementById('rejectBtn').classList.toggle('hidden', isApproved);
        document.getElementById('decisionModalTitle').textContent = isApproved ? 'Sign to approve' : 'Request revision';
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
                    showMpToast('Please sign the ATP before approving.', { title: 'Signature required', type: 'warning', timer: 3600 });
                } else {
                    alert('Please sign the ATP before approving.');
                }
                return;
            }
            document.getElementById('signatureData').value = canvas.toDataURL('image/png');
            document.getElementById('signatureUsed').value = '1';
        } else {
            const remarksField = form.querySelector('textarea[name="remarks"]');
            if (!remarksField || !remarksField.value.trim()) {
                if (typeof window.showMpToast === 'function') {
                    showMpToast('Please provide a revision reason.', { title: 'Remarks required', type: 'warning', timer: 3600 });
                } else {
                    alert('Please provide a revision reason.');
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
            closeDecisionModal();
            if (typeof window.showMpToast === 'function') {
                showMpToast(decision === 'Approved' ? 'ATP approved successfully.' : 'Revision requested.', { title: decision === 'Approved' ? 'Approved' : 'Revision requested', type: 'success' });
            }
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
        closeDecisionModal();
    });
</script>

@endsection