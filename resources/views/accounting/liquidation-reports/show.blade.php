@extends('layouts.accounting-layout')

@section('title', 'Review Liquidation')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page acc-page--review fade-in">
    <div class="acc-review-head">
        <div>
            <a href="/accounting/liquidation-reports?status={{ urlencode($returnStatus ?? 'incoming') }}" class="acc-back" data-tip="Back to liquidation queue" aria-label="Back to liquidation queue">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $liq->liquidation_report_form_number ?? ('LIQ-'.$liq->liquidation_report_id) }}</h1>
                @include('accounting.partials.status-badge', ['status' => $liq->liquidation_report_status])
                @include('accounting.partials.deadline-badge', ['deadline' => $liq->liquidation_report_submission_deadline ?? null])
            </div>
            <p class="acc-page-subtitle">Official liquidation form. Approval completes the transaction.</p>
            @if (!empty($liq->liquidation_report_submission_deadline))
                <p class="mt-1 text-xs font-semibold {{ \Carbon\Carbon::parse($liq->liquidation_report_submission_deadline)->startOfDay()->lt(now()->startOfDay()) ? 'text-rose-600' : (\Carbon\Carbon::parse($liq->liquidation_report_submission_deadline)->startOfDay()->eq(now()->startOfDay()) ? 'text-amber-600' : 'text-slate-500') }}">
                    Submission deadline: {{ \Carbon\Carbon::parse($liq->liquidation_report_submission_deadline)->format('M d, Y') }}
                </p>
            @endif
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="openDecisionModal('liq', {{ $liq->liquidation_report_id }}, 'Approved')" class="icon-btn" data-tip="Approve liquidation" aria-label="Approve liquidation">
                    <i data-lucide="check" class="h-4 w-4"></i>
                </button>
                <button type="button" onclick="openDecisionModal('liq', {{ $liq->liquidation_report_id }}, 'Rejected')" class="icon-btn" data-tip="Request revision" aria-label="Request revision">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                </button>
            @endif
            <button type="button" class="icon-btn" onclick="window.accountingPrintForm({ page: 'landscape', sheetId: 'acc-print-sheet' })" data-tip="Print liquidation" aria-label="Print liquidation">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <div class="acc-review-body">
        <div class="acc-review-grid">
            <div>
                <div class="acc-viewer">
                    <div class="acc-viewer-stage">
                        <div class="acc-viewer-fit">
                            @include('partials.liquidation-report-paper', [
                                'editable' => false,
                                'liq' => $liq,
                                'rows' => $rows,
                                'printId' => 'acc-print-sheet',
                                'accLiveSign' => !empty($reviewable),
                            ])
                        </div>
                    </div>
                </div>
                @if ($attachments->isNotEmpty())
                    <div class="acc-attachments">
                        <h3>Receipts and supporting documents</h3>
                        <ul>
                            @foreach ($attachments as $file)
                                <li>
                                    <a href="/accounting/liquidation-reports/{{ $liq->liquidation_report_id }}/attachments/{{ $file->liquidation_attachment_id }}">
                                        {{ $file->liquidation_attachment_original_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="acc-side-stack">
                @include('accounting.partials.related-docs', ['chain' => $chain, 'current' => 'liq'])
                @include('accounting.partials.history', ['history' => $history])
                @if (!empty($liq->liquidation_report_revision_notes))
                    <div class="acc-note acc-note-info">Last revision note: {{ $liq->liquidation_report_revision_notes }}</div>
                @endif
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
                <p id="decisionModalSubtitle">Approve or request revision for the selected Liquidation</p>
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
                    'signHint' => 'Pick a saved signature, draw one, or upload. It checks and completes this liquidation.',
                    'padTitle' => 'Sign the Liquidation',
                    'padHint' => 'Sign clearly. This overlays your printed name on Checked by.',
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
        document.getElementById('decisionModalSubtitle').textContent = '{{ $liq->liquidation_report_form_number ?? ('LIQ-'. $liq->liquidation_report_id) }}';
        form.action = '/accounting/liquidation-reports/' + id + (isApproved ? '/approve' : '/revise');
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
                showMpToast(decision === 'Approved' ? 'Liquidation approved. Transaction completed.' : 'Revision requested.', { title: decision === 'Approved' ? 'Approved' : 'Revision requested', type: 'success' });
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