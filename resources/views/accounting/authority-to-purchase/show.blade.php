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
        @php
            $pendingLinkedRfc = collect($viewerRfcs ?? [])->first(function ($r) {
                return in_array((string) ($r->request_check_status ?? ''), ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true);
            });
        @endphp
        <div class="acc-actions">
            @if ($reviewable)
                @if(!empty($dualRouteApprove))
                    <button
                        type="button"
                        onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Approved', '{{ $isAtpRfcPackage ? 'purchaser_and_accounting' : 'purchaser' }}')"
                        class="acc-action-btn is-approve"
                        data-tip="{{ $isAtpRfcPackage ? 'Approve the ATP and funding request, return them to the Purchaser, and send the funding request to Accounting' : 'Approve and pass to Purchaser' }}"
                    >
                        <i data-lucide="{{ $isAtpRfcPackage ? 'building-2' : 'user-check' }}" class="h-4 w-4"></i>
                        <span>{{ $isAtpRfcPackage ? 'Approve → Purchaser + Accounting' : 'Approve → Purchaser' }}</span>
                    </button>
                @else
                    <button type="button" onclick="openDecisionModal('atp', {{ $atp->authority_purchase_id }}, 'Approved')" class="acc-action-btn is-approve" data-tip="{{ $packageApproveLabel }}">
                        <i data-lucide="check" class="h-4 w-4"></i>
                        <span>{{ $packageApproveLabel }}</span>
                    </button>
                @endif
            @else
                @if ($pendingLinkedRfc)
                    <button
                        type="button"
                        onclick="openDecisionModal('rfc', {{ (int) $pendingLinkedRfc->request_check_id }}, 'Approved', 'purchaser_and_accounting')"
                        class="acc-action-btn is-approve"
                        data-tip="Approve RFC and send to Purchaser (status update) and Accounting to release the check"
                    >
                        <i data-lucide="check" class="h-4 w-4"></i>
                        <span>Approve RFC</span>
                    </button>
                @endif
            @endif
            <button type="button" class="acc-action-btn" onclick="window.accountingPrintForm({ page: 'portrait', sheetId: 'acc-print-sheet' })" data-tip="Print ATP">
                <i data-lucide="printer" class="h-4 w-4"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div data-doc-switcher>
    <div class="acc-review-body">
        <div class="acc-review-grid">
            <div class="acc-viewer">
                <div class="acc-viewer-stage">
                    <div class="acc-viewer-fit">
                        <div data-doc-panel="atp">
                        @include('partials.authority-to-purchase-paper', [
                            'editable' => false,
                            'atp' => $atp,
                            'items' => $items,
                            'printId' => 'acc-print-sheet',
                            'accLiveSign' => !empty($reviewable),
                        ])
                        </div>
                        @if(!empty($ris))
                            <div data-doc-panel="ris" hidden>
                                @include('partials.ris-purchaser-sheet', [
                                    'ris' => $ris,
                                    'risItems' => $risItems ?? collect(),
                                    'printId' => 'acc-ris-print-sheet',
                                ])
                            </div>
                        @endif
                        @if(($viewerRfcs ?? collect())->isNotEmpty())
                            <div data-doc-panel="rfc" hidden>
                                @foreach($viewerRfcs as $linkedRfc)
                                    <div class="mb-6">
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
                @include('accounting.partials.related-docs', ['chain' => $chain, 'current' => 'atp', 'switchable' => true])
                @include('accounting.partials.history', ['history' => $history])
            </div>
        </div>
    </div>
    </div>
</div>
<script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-switch-doc]');
        if (!button) return;
        var root = button.closest('[data-doc-switcher]');
        if (!root) return;
        var key = button.getAttribute('data-switch-doc');
        var panel = root.querySelector('[data-doc-panel="' + key + '"]');
        if (!panel) return;
        event.preventDefault();
        root.querySelectorAll('[data-doc-panel]').forEach(function (node) {
            node.hidden = node.getAttribute('data-doc-panel') !== key;
        });
        root.querySelectorAll('[data-chain-item]').forEach(function (item) {
            var active = item.getAttribute('data-chain-item') === key;
            item.classList.toggle('acc-chain-item--current', active);
            var curEl = item.querySelector('[data-chain-current]');
            var openEl = item.querySelector('[data-chain-open]');
            if (curEl) curEl.hidden = !active;
            if (openEl) openEl.hidden = active;
        });
        if (window.fitAccountingDocuments) {
            requestAnimationFrame(function () { window.fitAccountingDocuments(); });
        }
        root.querySelectorAll('[data-switch-doc]').forEach(function (node) {
            if (node.classList.contains('acc-link')) return;
            var on = node.getAttribute('data-switch-doc') === key;
            node.classList.toggle('bg-[#0025cc]', on);
            node.classList.toggle('text-white', on);
            node.classList.toggle('ring-[#0025cc]', on);
            node.classList.toggle('bg-white', !on);
            node.classList.toggle('text-slate-700', !on);
            node.classList.toggle('ring-slate-200', !on);
            if (on) node.setAttribute('aria-current', 'page');
            else node.removeAttribute('aria-current');
        });
    });
</script>

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
            <div id="remarksBlock" class="mt-4">
                <label>Remarks <span id="remarksReqStar" class="text-red-500 hidden">*</span></label>
                <textarea name="remarks" rows="3" placeholder="Required for revision."></textarea>
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

{{-- Final Confirmation Modal before Approving RFC --}}
<div id="rfcFinalConfirmModal" class="fixed inset-0 z-[12200] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-black/50 p-4" onclick="if (event.target === this) closeRfcFinalConfirm()">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" onclick="event.stopPropagation()" role="dialog" aria-modal="true">
            <div class="flex items-start gap-3.5 px-6 pb-4 pt-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background:#eff6ff;color:#1d4ed8;">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-bold text-slate-900">Confirm RFC Approval?</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Approving <strong id="rfcFinalConfirmLabel" class="text-slate-800"></strong> will update its status for the Purchaser and send it to Accounting so they can release the check.
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                <button type="button" onclick="closeRfcFinalConfirm()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
                <button type="button" onclick="executeDecisionSubmit('Approved')" class="rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-95" style="background:#0025cc;color:#ffffff;">Confirm &amp; Send</button>
            </div>
        </div>
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
        const isRfcTarget = String(type || '').toLowerCase() === 'rfc';
        const rfcLabel = @json($pendingLinkedRfc?->request_check_form_number ?? ($pendingLinkedRfc ? 'RFC-'.$pendingLinkedRfc->request_check_id : ''));
        document.getElementById('decisionModalSubtitle').textContent = isRfcTarget
            ? (rfcLabel || ('RFC-' + id))
            : '{{ $atp->authority_purchase_form_number }}';
        form.action = (isRfcTarget ? '/school-admin/request-check/' : '/school-admin/authority-to-purchase/') + id + (isApproved ? '/approve' : '/revise');
        document.getElementById('signatureBlock').classList.toggle('hidden', !isApproved);
        document.getElementById('remarksBlock')?.classList.toggle('hidden', isApproved);
        document.getElementById('decisionImagesBlock').classList.toggle('hidden', isApproved);
        document.getElementById('remarksReqStar').classList.toggle('hidden', isApproved);
        if (window.resetRevisionImagePicker) window.resetRevisionImagePicker('decisionRevisionImages');
        document.getElementById('approveBtn').classList.toggle('hidden', !isApproved);
        document.getElementById('rejectBtn').classList.toggle('hidden', isApproved);

        const routeHint = document.getElementById('routeHint');
        if (isApproved && isRfcTarget) {
            routeHint.classList.remove('hidden');
            routeHint.textContent = 'Approves this RFC, updates the status for the Purchaser, and sends it to Accounting so they can release the check.';
            document.getElementById('decisionModalTitle').textContent = 'Approve RFC';
            document.getElementById('approveBtn').textContent = 'Approve & Send to Accounting';
        } else if (isApproved && dualRouteApprove) {
            const both = (routeTo || 'purchaser') === 'purchaser_and_accounting';
            routeHint.classList.remove('hidden');
            routeHint.textContent = both
                ? (linkedRfcCount > 0
                    ? 'Approves the ATP and linked funding request. The Purchaser keeps the signed ATP, and the funding request goes to Accounting.'
                    : 'Sends the approved package to the Purchaser and to Accounting.')
                : 'Approves the ATP and returns it to the Purchaser.';
            document.getElementById('decisionModalTitle').textContent = both
                ? 'Approve → Purchaser + Accounting'
                : 'Approve → Purchaser';
            document.getElementById('approveBtn').textContent = both
                ? 'Confirm approve to both'
                : 'Confirm approve to Purchaser';
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
        var confirmBox = document.getElementById('rfcFinalConfirmModal');
        if (confirmBox) confirmBox.classList.add('hidden');
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
    }

    function openRfcFinalConfirm() {
        var confirmBox = document.getElementById('rfcFinalConfirmModal');
        if (!confirmBox) {
            executeDecisionSubmit('Approved');
            return;
        }
        var labelEl = document.getElementById('rfcFinalConfirmLabel');
        if (labelEl) {
            labelEl.textContent = document.getElementById('decisionModalSubtitle')?.textContent || 'this RFC';
        }
        confirmBox.classList.remove('hidden');
    }

    function closeRfcFinalConfirm() {
        var confirmBox = document.getElementById('rfcFinalConfirmModal');
        if (confirmBox) confirmBox.classList.add('hidden');
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
            openRfcFinalConfirm();
            return;
        }
        executeDecisionSubmit(decision);
    }

    function executeDecisionSubmit(decision) {
        if (decideInFlight) return;
        closeRfcFinalConfirm();
        const form = document.getElementById('decisionForm');
        document.getElementById('targetDecision').value = decision;
        if (decision === 'Approved') {
            document.getElementById('signatureUsed').value = '1';
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