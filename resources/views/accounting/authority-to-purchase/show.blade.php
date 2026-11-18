@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', !empty($linkedPendingRfcs) && $linkedPendingRfcs->isNotEmpty() ? 'Review ATP + RFC' : 'Review ATP')

@section('content')
@include('accounting.partials.flash')

@php
    $fromPoId = (int) request('from_po', 0);
    $poLabel = !empty($purchaseOrder) ? \App\Support\PurchaseOrderBasket::displayNumber($purchaseOrder) : null;
    $backToPo = !empty($purchaseOrder) && $fromPoId === (int) $purchaseOrder->purchase_order_id;
    $isAtpRfcPackage = !empty($linkedPendingRfcs) && $linkedPendingRfcs->isNotEmpty();
    $packageApproveLabel = $isAtpRfcPackage
        ? ($linkedPendingRfcs->count() === 1 ? 'Approve ATP + RFC' : 'Approve ATP + RFCs')
        : 'Approve';
@endphp
<div class="acc-page acc-page--review fade-in">
    <div class="acc-review-head">
        <div>
            @if ($backToPo)
                <a href="/school-admin/purchase-orders/{{ $purchaseOrder->purchase_order_id }}#po-atp-{{ $atp->authority_purchase_id }}" class="acc-back" data-tip="Back to {{ $poLabel }}" aria-label="Back to {{ $poLabel }}">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
            @else
                <a href="/school-admin/authority-to-purchase?status={{ urlencode($returnStatus ?? 'incoming') }}" class="acc-back" data-tip="Back to ATP + Check Approvals" aria-label="Back to ATP + Check Approvals">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                </a>
            @endif
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">
                    {{ $atp->authority_purchase_form_number }}
                    @if($isAtpRfcPackage)
                        <span class="text-base font-semibold text-slate-500">+ Check</span>
                    @endif
                </h1>
                @include('accounting.partials.status-badge', ['status' => \App\Support\RisWorkflow::atpStatusLabel($atp), 'submitted' => $atp->authority_purchase_submitted_at, 'revision' => $atp->authority_purchase_rejection_reason])
                @include('partials.ris-urgency-badge', [
                    'urgent' => \App\Support\DocumentUrgency::isUrgent('ATP', $atp->authority_purchase_id),
                    'title' => 'Inherited from linked RIS',
                ])
            </div>
            @if(!blank($atp->authority_purchase_request_no ?? null))
                <div class="mt-2">
                    @include('partials.procurement-request-no', ['requestNo' => $atp->authority_purchase_request_no, 'inline' => true, 'size' => 'md'])
                </div>
            @endif
            @if ($poLabel)
                <p class="mt-2 inline-flex flex-wrap items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs text-blue-900">
                    <i data-lucide="shopping-bag" class="h-3.5 w-3.5"></i>
                    Part of
                    <a href="/school-admin/purchase-orders/{{ $purchaseOrder->purchase_order_id }}" class="font-semibold underline underline-offset-2">{{ $poLabel }}</a>
                    @if (($purchaseOrder->purchase_order_status ?? '') === 'Submitted')
                        · approve or send back the whole Purchase Order
                    @endif
                </p>
            @endif
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                @if(!empty($dualRouteApprove))
                    <button
                        type="button"
                        onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Approved', 'purchaser')"
                        class="acc-action-btn is-approve"
                        data-tip="{{ $isAtpRfcPackage ? 'Approve ATP + linked RFC(s) and pass to Purchaser' : 'Approve and pass to Purchaser only' }}"
                    >
                        <i data-lucide="user-check" class="h-4 w-4"></i>
                        <span>{{ $isAtpRfcPackage ? 'Approve → Purchaser' : 'Pass to Purchaser' }}</span>
                    </button>
                    <button
                        type="button"
                        onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Approved', 'purchaser_and_accounting')"
                        class="acc-action-btn is-approve"
                        data-tip="{{ $isAtpRfcPackage ? 'Approve ATP + linked RFC and send package to Accounting' : 'Requires a linked funding request' }}"
                        @if(! $isAtpRfcPackage) disabled @endif
                    >
                        <i data-lucide="building-2" class="h-4 w-4"></i>
                        <span>{{ $isAtpRfcPackage ? 'Approve → Purchaser + Accounting' : 'Pass to Purchaser + Accounting' }}</span>
                    </button>
                @else
                    <button type="button" onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Approved')" class="acc-action-btn is-approve" data-tip="{{ $packageApproveLabel }}">
                        <i data-lucide="check" class="h-4 w-4"></i>
                        <span>{{ $packageApproveLabel }}</span>
                    </button>
                @endif
                <button type="button" onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Rejected')" class="acc-action-btn is-revise" data-tip="Send back to Purchaser for revision">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                    <span>Return for Revision</span>
                </button>
            @endif
            <button type="button" class="acc-action-btn" onclick="window.accountingPrintForm({ page: 'portrait', sheetId: 'acc-print-sheet' })" data-tip="Print ATP">
                <i data-lucide="printer" class="h-4 w-4"></i>
                <span>Print</span>
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
                        @if($isAtpRfcPackage)
                            <div class="mt-6 print-hidden">
                                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Linked funding {{ $linkedPendingRfcs->count() === 1 ? 'request' : 'requests' }} (approved with ATP)
                                </p>
                                @foreach($linkedPendingRfcs as $linkedRfc)
                                    <div class="mb-6">
                                        <p class="mb-2 text-sm font-semibold text-slate-800">
                                            {{ $linkedRfc->request_check_form_number ?: ('RFC #'.$linkedRfc->request_check_id) }}
                                            <span class="ml-2 text-xs font-medium text-slate-500">{{ $linkedRfc->request_check_status }}</span>
                                        </p>
                                        @include('partials.request-check-paper', [
                                            'editable' => false,
                                            'rfc' => $linkedRfc,
                                            'printId' => 'acc-rfc-print-'.$linkedRfc->request_check_id,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($atp->authority_purchase_rejection_reason)
                            <div class="mt-4 rounded-lg bg-sky-50 p-2.5 text-xs text-sky-900 print-hidden">
                                <p>Revision remarks: {{ $atp->authority_purchase_rejection_reason }}</p>
                                @include('partials.ris-revision-images', [
                                    'revision' => \App\Support\DocumentRevisionNotes::latest('ATP', $atp->authority_purchase_id),
                                    'routeName' => 'school-admin.document-revision-image',
                                    'size' => 'sm',
                                ])
                            </div>
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
            <input type="hidden" name="route_to" id="routeTo" value="purchaser" />
            <input type="hidden" name="signature_data" id="signatureData" value="" />
            <input type="hidden" name="signature_used" id="signatureUsed" value="0" />
            <div id="signatureBlock" class="hidden mt-4">
                @include('accounting.partials.decision-signature-panel', [
                    'savedSignatures' => $savedSignatures ?? collect(),
                    'signTitle' => 'School Administrator signature',
                    'signHint' => $isAtpRfcPackage
                        ? 'Pick a saved signature, draw one, or upload. It authorizes this ATP and linked funding request(s).'
                        : 'Pick a saved signature, draw one, or upload. It authorizes this Authority to Purchase.',
                    'padTitle' => $isAtpRfcPackage ? 'Sign the ATP + RFC package' : 'Sign the ATP',
                    'padHint' => 'Sign clearly. This overlays your printed name on the approval.',
                ])
            </div>
            <div id="routeHint" class="mt-3 hidden rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700"></div>
            <div class="mt-4">
                <label>Remarks <span id="remarksReqStar" class="text-red-500 hidden">*</span></label>
                <textarea name="remarks" rows="3" placeholder="Optional for approve. Required for revision."></textarea>
            </div>
            <div id="decisionImagesBlock" class="hidden mt-4">
                @include('admin.partials.revision-image-picker', ['pickerId' => 'decisionRevisionImages'])
            </div>
            <div class="confirm-actions mt-5">
                <button type="button" class="btn-ghost" data-tip="Cancel decision" onclick="closeDecisionModal()">Cancel</button>
                <button type="button" id="approveBtn" class="btn-approve" data-tip="Confirm approval" onclick="submitDecision('Approved')">{{ $packageApproveLabel }}</button>
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
    const dualRouteApprove = @json(!empty($dualRouteApprove));
    const linkedRfcCount = @json(isset($linkedPendingRfcs) ? $linkedPendingRfcs->count() : 0);
    let decideInFlight = false;

    function openDecisionModal(type, id, presetDecision, routeTo) {
        const form = document.getElementById('decisionForm');
        document.getElementById('targetType').value = type;
        document.getElementById('targetId').value = id;
        document.getElementById('targetDecision').value = presetDecision || '';
        document.getElementById('routeTo').value = routeTo || 'purchaser';
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
        else {
            document.getElementById('signatureData').value = '';
            document.getElementById('signatureUsed').value = '0';
        }
        const remarks = form.querySelector('textarea[name="remarks"]');
        if (remarks) remarks.value = '';
        const isApproved = (presetDecision || '').toLowerCase() === 'approved';
        document.getElementById('decisionModalSubtitle').textContent = '{{ $atp->authority_purchase_form_number }}';
        form.action = '/school-admin/authority-to-purchase/' + id + (isApproved ? '/approve' : '/revise');
        document.getElementById('signatureBlock').classList.toggle('hidden', !isApproved);
        document.getElementById('decisionImagesBlock').classList.toggle('hidden', isApproved);
        document.getElementById('remarksReqStar').classList.toggle('hidden', isApproved);
        if (window.resetRevisionImagePicker) window.resetRevisionImagePicker('decisionRevisionImages');
        document.getElementById('approveBtn').classList.toggle('hidden', !isApproved);
        document.getElementById('rejectBtn').classList.toggle('hidden', isApproved);

        const routeHint = document.getElementById('routeHint');
        if (isApproved && dualRouteApprove) {
            const both = (routeTo || 'purchaser') === 'purchaser_and_accounting';
            routeHint.classList.remove('hidden');
            routeHint.textContent = both
                ? (linkedRfcCount > 0
                    ? 'Approves ATP' + (linkedRfcCount === 1 ? ' + linked RFC' : ' + ' + linkedRfcCount + ' linked RFCs') + ' and sends the package to Accounting for DV.'
                    : 'Sends the approved package to Accounting for DV.')
                : (linkedRfcCount > 0
                    ? 'Approves ATP' + (linkedRfcCount === 1 ? ' + linked RFC' : ' + ' + linkedRfcCount + ' linked RFCs') + '. Purchaser keeps the record and can submit to Accounting later.'
                    : 'Passes the approved ATP to the Purchaser only.');
            document.getElementById('decisionModalTitle').textContent = both
                ? (linkedRfcCount > 0 ? 'Approve → Purchaser + Accounting' : 'Pass to Purchaser + Accounting')
                : (linkedRfcCount > 0 ? 'Approve → Purchaser' : 'Pass to Purchaser');
            document.getElementById('approveBtn').textContent = both
                ? (linkedRfcCount > 0 ? 'Confirm approve to both' : 'Confirm pass to both')
                : (linkedRfcCount > 0 ? 'Confirm approve to Purchaser' : 'Confirm pass to Purchaser');
        } else {
            routeHint.classList.add('hidden');
            document.getElementById('decisionModalTitle').textContent = isApproved ? 'Sign to approve' : 'Request revision';
            document.getElementById('approveBtn').textContent = linkedRfcCount > 0
                ? (linkedRfcCount === 1 ? 'Approve ATP + RFC' : 'Approve ATP + RFCs')
                : 'Approve';
        }

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

@include('partials.ris-revision-image-viewer')
@endsection