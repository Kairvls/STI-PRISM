@extends('layouts.accounting-layout')

@section('title', 'Review Request Check')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page acc-page--review fade-in">
    <div class="acc-review-head">
        <div>
            <a href="/accounting/request-check?status={{ urlencode($returnStatus ?? 'incoming') }}" class="acc-back" data-tip="Back to Request Check queue" aria-label="Back to Request Check queue">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $rfc->request_check_form_number ?? ('RFC-'.$rfc->request_check_id) }}</h1>
                @include('accounting.partials.status-badge', ['status' => !empty($rfc->request_check_funds_released_at) ? 'Released' : $rfc->request_check_status])
            </div>
            <p class="acc-page-subtitle">Official Request for Check. Funds are collected in person.</p>
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="openDecisionModal('rfc', {{ $rfc->request_check_id }}, 'Approved')" class="icon-btn" data-tip="Approve request check" aria-label="Approve request check">
                    <i data-lucide="check" class="h-4 w-4"></i>
                </button>
                <button type="button" onclick="openDecisionModal('rfc', {{ $rfc->request_check_id }}, 'Rejected')" class="icon-btn" data-tip="Request revision" aria-label="Request revision">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                </button>
            @endif
            @if ($releasable)
                <button type="button" onclick="document.getElementById('releaseFundsModal').classList.remove('hidden')" class="icon-btn" data-tip="Release funds" aria-label="Release funds">
                    <i data-lucide="banknote" class="h-4 w-4"></i>
                </button>
            @endif
            <button type="button" class="icon-btn" onclick="window.accountingPrintForm({ page: 'landscape', sheetId: 'acc-print-sheet' })" data-tip="Print request check" aria-label="Print request check">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <div id="releaseFundsModal" class="confirm-modal hidden">
        <div class="confirm-backdrop" onclick="closeReleaseFundsModal()"></div>
        <div class="confirm-card wide" onclick="event.stopPropagation()">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3>Release funds?</h3>
                    <p>Mark funds as ready for personal collection.</p>
                </div>
                <button type="button" class="icon-close" onclick="closeReleaseFundsModal()" data-tip="Close" aria-label="Close"><i data-lucide="x"></i></button>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Amount to release</p>
                <p class="mt-1 text-2xl font-bold text-blue-700">
                    {{ $rfc->request_check_amount_figures !== null ? '₱'.number_format((float) $rfc->request_check_amount_figures, 2) : '—' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Payee: {{ $rfc->request_check_payee ?: '—' }}
                    @if (!empty($rfc->authority_purchase_form_number))
                        · ATP {{ $rfc->authority_purchase_form_number }}
                    @endif
                </p>
            </div>

            <div class="mt-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Items / products to purchase</p>
                <div class="mt-2 max-h-48 overflow-auto rounded-xl border border-slate-200 bg-white">
                    @if (($atpItems ?? collect())->isNotEmpty())
                        <table class="acc-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="!text-right">Qty</th>
                                    <th class="!text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($atpItems as $item)
                                    <tr>
                                        <td>{{ $item->atp_description ?: '—' }}</td>
                                        <td class="text-right whitespace-nowrap">{{ $item->atp_quantity ?? '—' }}{{ !empty($item->atp_unit) ? ' '.$item->atp_unit : '' }}</td>
                                        <td class="acc-money whitespace-nowrap">{{ $item->atp_amount !== null ? '₱'.number_format((float) $item->atp_amount, 2) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="px-4 py-3 text-sm text-slate-500">No ATP item details found for this Request Check.</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="/accounting/request-check/{{ $rfc->request_check_id }}/release-funds" class="mt-4">
                @csrf
                <div class="confirm-actions">
                    <button type="button" class="btn-ghost" data-tip="Cancel" onclick="closeReleaseFundsModal()">Cancel</button>
                    <button type="submit" class="btn-send" data-tip="Mark funds as ready" onclick="this.disabled = true; this.form.submit();">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <div class="acc-review-body">
        @if (!empty($rfc->request_check_funds_released_at))
            <div class="acc-note acc-note-ok mb-3">
                Funds ready for personal collection since {{ \Carbon\Carbon::parse($rfc->request_check_funds_released_at)->format('M d, Y g:i A') }}.
            </div>
        @endif

        <div class="acc-review-grid">
            <div>
                <div class="acc-viewer">
                    <div class="acc-viewer-stage">
                        <div class="acc-viewer-fit">
                            @include('partials.request-check-paper', [
                                'editable' => false,
                                'rfc' => $rfc,
                                'printId' => 'acc-print-sheet',
                                'accLiveSign' => !empty($reviewable),
                            ])
                        </div>
                    </div>
                </div>
                @if ($attachments->isNotEmpty())
                    <div class="acc-attachments">
                        <h3>Supporting documents</h3>
                        <ul>
                            @foreach ($attachments as $file)
                                <li>
                                    <a href="/accounting/request-check/{{ $rfc->request_check_id }}/attachments/{{ $file->request_check_attachment_id }}">
                                        {{ $file->request_check_attachment_original_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="acc-side-stack">
                @include('accounting.partials.related-docs', [
                    'chain' => $chain,
                    'current' => request('view') === 'funds' ? 'funds' : 'rfc',
                ])
                @include('accounting.partials.history', ['history' => $history])
                @if (!empty($rfc->request_check_revision_notes))
                    <div class="acc-note acc-note-info">Last revision note: {{ $rfc->request_check_revision_notes }}</div>
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
                <p id="decisionModalSubtitle">Approve or request revision for the selected Request Check</p>
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
                    'signHint' => 'Pick a saved signature, draw one, or upload. It approves this Request for Check.',
                    'padTitle' => 'Sign the Request for Check',
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
        @if (request('view') === 'funds' && !empty($releasable))
        var releaseModal = document.getElementById('releaseFundsModal');
        if (releaseModal) releaseModal.classList.remove('hidden');
        @endif
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
        document.getElementById('decisionModalSubtitle').textContent = '{{ $rfc->request_check_form_number ?? ('RFC-'. $rfc->request_check_id) }}';
        form.action = '/accounting/request-check/' + id + (isApproved ? '/approve' : '/revise');
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

    function closeReleaseFundsModal() {
        document.getElementById('releaseFundsModal').classList.add('hidden');
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
                showMpToast(decision === 'Approved' ? 'Request Check approved successfully.' : 'Revision requested.', { title: decision === 'Approved' ? 'Approved' : 'Revision requested', type: 'success' });
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
        closeReleaseFundsModal();
    });
</script>

@endsection