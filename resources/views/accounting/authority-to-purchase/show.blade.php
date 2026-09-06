@extends('layouts.accounting-layout')

@section('title', 'Review ATP')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page acc-page--review fade-in">
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
            <button type="button" class="icon-btn" onclick="window.accountingPrintForm({ page: 'portrait', sheetId: 'acc-print-sheet' })" data-tip="Print ATP" aria-label="Print ATP">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <div class="acc-review-body">
        <div class="acc-review-grid">
            <div class="acc-viewer">
                <div class="acc-viewer-stage">
                    <div class="acc-viewer-fit">
                        @include('partials.authority-to-purchase-paper', [
                            'editable' => false,
                            'atp' => $atp,
                            'items' => $items,
                            'printId' => 'acc-print-sheet',
                            'accLiveSign' => !empty($reviewable),
                        ])
                        @if ($atp->authority_purchase_rejection_reason)
                            <p class="mt-4 rounded-lg bg-sky-50 p-2.5 text-xs text-sky-900 print-hidden">Revision remarks: {{ $atp->authority_purchase_rejection_reason }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="acc-side-stack">
                @include('accounting.partials.related-docs', ['chain' => $chain, 'current' => 'atp'])
                @include('accounting.partials.history', ['history' => $history])
            </div>
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
                @include('accounting.partials.decision-signature-panel', [
                    'savedSignatures' => $savedSignatures ?? collect(),
                    'signTitle' => 'Accounting signature',
                    'signHint' => 'Pick a saved signature, draw one, or upload. It authorizes this Authority to Purchase.',
                    'padTitle' => 'Sign the ATP',
                    'padHint' => 'Sign clearly. This overlays your printed name on the approval.',
                ])
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
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let decideInFlight = false;

    function openDecisionModal(type, id, presetDecision) {
        const form = document.getElementById('decisionForm');
        document.getElementById('targetType').value = type;
        document.getElementById('targetId').value = id;
        document.getElementById('targetDecision').value = presetDecision || '';
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
        else {
            document.getElementById('signatureData').value = '';
            document.getElementById('signatureUsed').value = '0';
        }
        const remarks = form.querySelector('textarea[name="remarks"]');
        if (remarks) remarks.value = '';
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
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
    }

    function submitDecision(decision) {
        if (decideInFlight) return;
        const form = document.getElementById('decisionForm');
        document.getElementById('targetDecision').value = decision;
        if (decision === 'Approved') {
            const hasSig = window.accountingSignaturePanel
                ? window.accountingSignaturePanel.hasSignature()
                : String(document.getElementById('signatureData').value || '').indexOf('data:image/') === 0;
            if (!hasSig) {
                if (typeof window.showMpToast === 'function') {
                    showMpToast('Please add a signature before approving.', { title: 'Signature required', type: 'warning', timer: 3600 });
                } else {
                    alert('Please add a signature before approving.');
                }
                return;
            }
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

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeDecisionModal();
    });
</script>

@endsection