@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', 'Review Request Check')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page acc-page--review fade-in">
    <div class="acc-review-head">
        <div>
            <a href="/school-admin/request-check?status={{ urlencode($returnStatus ?? 'incoming') }}" class="acc-back" data-tip="Back to Request Check queue" aria-label="Back to Request Check queue">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $rfc->request_check_form_number ?? ('RFC-'.$rfc->request_check_id) }}</h1>
                @include('accounting.partials.status-badge', ['status' => !empty($rfc->request_check_funds_collected_at) ? 'Check collected' : (!empty($rfc->request_check_funds_released_at) ? 'Released' : $rfc->request_check_status), 'doc' => 'rfc'])
                @include('partials.ris-urgency-badge', [
                    'urgent' => \App\Support\DocumentUrgency::isUrgent('RFC', $rfc->request_check_id),
                    'title' => 'Inherited from linked RIS',
                ])
                @include('partials.payment-method-badge', [
                    'rfc' => $rfc,
                ])
            </div>
            @if(!blank($rfc->request_check_request_no ?? null))
                <div class="mt-2">
                    @include('partials.procurement-request-no', ['requestNo' => $rfc->request_check_request_no, 'inline' => true, 'size' => 'md'])
                </div>
            @endif
            <p class="acc-page-subtitle">Collect funds in person.</p>
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="openApproveRfcConfirm()" class="acc-action-btn is-approve" data-tip="Approve RFC and send to Purchaser (status update) and Accounting to release the check">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    <span>Approve RFC</span>
                </button>
            @endif
            @if ($releasable && ! \App\Support\AdminPortal::isSchoolAdmin())
                <button type="button" onclick="document.getElementById('releaseFundsModal').classList.remove('hidden')" class="acc-action-btn is-approve" data-tip="Release funds">
                    <i data-lucide="banknote" class="h-4 w-4"></i>
                    <span>Release Funds</span>
                </button>
            @endif
            <button type="button" class="acc-action-btn" onclick="window.accountingPrintForm({ page: 'landscape', sheetId: 'acc-print-sheet' })" data-tip="Print request check">
                <i data-lucide="printer" class="h-4 w-4"></i>
                <span>Print</span>
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
                <p class="text-xs text-slate-500">
                    Payee: {{ $rfc->request_check_payee ?: '—' }}
                    @if (!empty($purchaseOrderLabel))
                        · {{ $purchaseOrderLabel }}
                        · ATPs {{ ($linkedAtps ?? collect())->map(fn ($atp) => \App\Support\PurchaseOrderBasket::atpListLabel($atp))->implode(', ') }}
                    @elseif (!empty($rfc->authority_purchase_form_number))
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
                                        <td>
                                            {{ $item->atp_description ?: '—' }}
                                            @if (($linkedAtps ?? collect())->count() > 1 && !empty($item->atp_label))
                                                <span class="block text-[11px] text-slate-400">{{ $item->atp_label }}</span>
                                            @endif
                                        </td>
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

            <form method="POST" action="/school-admin/request-check/{{ $rfc->request_check_id }}/release-funds" class="mt-4">
                @csrf
                <div class="confirm-actions">
                    <button type="button" class="btn-ghost" data-tip="Cancel" onclick="closeReleaseFundsModal()">Cancel</button>
                    <button type="submit" class="btn-send bg-[#0025cc] hover:bg-blue-800" data-tip="Mark funds as ready" onclick="this.disabled = true; this.form.submit();">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <div class="acc-review-body">
        @if (!empty($rfc->request_check_funds_collected_at))
            <div class="acc-note acc-note-ok mb-3">
                Check collected / handed over since {{ \Carbon\Carbon::parse($rfc->request_check_funds_collected_at)->format('M d, Y g:i A') }}.
            </div>
        @elseif (!empty($rfc->request_check_funds_released_at))
            <div class="acc-note acc-note-ok mb-3">
                Funds ready for personal collection since {{ \Carbon\Carbon::parse($rfc->request_check_funds_released_at)->format('M d, Y g:i A') }}.
            </div>
        @endif

        <div class="acc-review-grid">
            <div>
                <div class="rounded-2xl bg-slate-100 p-3 md:p-4">
                    @include('partials.request-check-paper', [
                        'editable' => false,
                        'rfc' => $rfc,
                        'printId' => 'acc-print-sheet',
                        'accLiveSign' => !empty($reviewable),
                    ])
                </div>
                @if ($reviewable)
                    <form id="inlineApproveRfcForm" method="POST" action="/school-admin/request-check/{{ $rfc->request_check_id }}/approve" class="mt-4">
                        @csrf
                        <input type="hidden" name="target_type" value="rfc" />
                        <input type="hidden" name="target_id" value="{{ $rfc->request_check_id }}" />
                        <input type="hidden" name="decision" value="Approved" />
                        <input type="hidden" name="route_to" value="{{ !empty($dualRouteApprove) ? 'purchaser_and_accounting' : 'purchaser' }}" />
                        <input type="hidden" name="signature_data" id="signatureData" value="" />
                        <input type="hidden" name="signature_used" id="signatureUsed" value="0" />
                        @include('accounting.partials.decision-signature-panel', [
                            'savedSignatures' => $savedSignatures ?? collect(),
                            'signTitle' => 'School Administrator signature',
                            'signHint' => 'Pick a saved signature, draw one, or upload. It approves this Request for Check.',
                            'padTitle' => 'Sign the Request for Check',
                            'padHint' => 'Sign clearly. This overlays your printed name on the approval.',
                            'hideInlinePreview' => true,
                        ])
                    </form>
                @endif
            </div>
            <div class="acc-side-stack">
                @include('accounting.partials.related-docs', [
                    'chain' => $chain,
                    'current' => request('view') === 'funds' ? 'funds' : 'rfc',
                ])
                @include('accounting.partials.history', ['history' => $history])
                @if (!empty($rfc->request_check_revision_notes))
                    <div class="acc-note acc-note-info">
                        Last revision note: {{ $rfc->request_check_revision_notes }}
                        @include('partials.ris-revision-images', [
                            'revision' => \App\Support\DocumentRevisionNotes::latest('RFC', $rfc->request_check_id),
                            'routeName' => 'school-admin.document-revision-image',
                            'size' => 'sm',
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Final Confirmation Modal before Approving RFC --}}
@php
    $showRawPath = $rfc->request_check_funding_type ?? ($chain['atp']->authority_purchase_payment_path ?? null);
    $showNormPath = \App\Support\ProcurementPaymentPath::normalize(is_string($showRawPath) ? $showRawPath : null);
    $showPaymentLabel = $showNormPath ? \App\Support\ProcurementPaymentPath::label($showNormPath) : '—';
    $showAmountLabel = isset($rfc->request_check_amount_figures) && $rfc->request_check_amount_figures !== ''
        ? '₱'.number_format((float) $rfc->request_check_amount_figures, 2)
        : '—';
    $showPurchaserName = trim((string) ($rfc->request_check_requested_by ?? '')) ?: 'Purchaser';
@endphp
<div id="rfcFinalConfirmModal" class="fixed inset-0 hidden" style="z-index: 99999;">
    <div class="absolute inset-0 flex items-center justify-center bg-black/50 p-4" onclick="if (event.target === this) closeRfcFinalConfirm()">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" onclick="event.stopPropagation()" role="dialog" aria-modal="true">
            <div class="px-6 pb-4 pt-6">
                <h3 class="text-base font-bold text-slate-900">Confirm RFC Approval?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to approve <strong id="rfcFinalConfirmLabel" class="text-slate-900">{{ $rfc->request_check_form_number ?? ('RFC-'.$rfc->request_check_id) }}</strong>? This will update the status for the Purchaser and send it to Accounting so they can release the check.
                </p>
                <div class="mt-4 space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Payment Method</span>
                        <span class="font-semibold text-slate-900">{{ $showPaymentLabel }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Payee</span>
                        <span class="truncate font-semibold text-slate-900">{{ $rfc->request_check_payee ?: '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Amount</span>
                        <span class="font-semibold tabular-nums text-slate-900">{{ $showAmountLabel }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Send to Purchaser</span>
                        <span class="font-semibold text-[#0025cc]">{{ $showPurchaserName }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Send to Accounting</span>
                        <span class="font-semibold text-emerald-700">For check release</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                <button type="button" onclick="closeRfcFinalConfirm()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                <button type="button" id="rfcFinalConfirmSubmitBtn" onclick="executeInlineApproveRfc()" class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-95" style="background:#0025cc;color:#ffffff;">Confirm &amp; Send</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        @if (request('view') === 'funds' && !empty($releasable))
        var releaseModal = document.getElementById('releaseFundsModal');
        if (releaseModal) releaseModal.classList.remove('hidden');
        @endif
        @if ($reviewable)
        setTimeout(function () {
            const sigInput = document.getElementById('signatureData');
            if (sigInput && String(sigInput.value || '').indexOf('data:image/') !== 0) {
                const firstSaved = document.querySelector('#accSavedList .acc-use-saved');
                if (firstSaved) firstSaved.click();
            }
        }, 60);
        @endif
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let decideInFlight = false;

    function openApproveRfcConfirm() {
        if (decideInFlight) return;
        const paperName = document.getElementById('accPaperSigPrintedName');
        const panelName = document.getElementById('accSigPrintedName');
        if (paperName && panelName) {
            panelName.value = paperName.value;
        }
        const hasSig = window.accountingSignaturePanel
            ? window.accountingSignaturePanel.hasSignature()
            : String(document.getElementById('signatureData')?.value || '').indexOf('data:image/') === 0;
        if (!hasSig) {
            if (typeof window.showMpToast === 'function') {
                showMpToast('Please add a signature before approving.', { title: 'Signature required', type: 'warning', timer: 3600 });
            } else {
                alert('Please add a signature before approving.');
            }
            return;
        }
        const confirmBox = document.getElementById('rfcFinalConfirmModal');
        if (confirmBox) confirmBox.classList.remove('hidden');
    }

    function closeRfcFinalConfirm() {
        var confirmBox = document.getElementById('rfcFinalConfirmModal');
        if (confirmBox) confirmBox.classList.add('hidden');
    }

    function closeReleaseFundsModal() {
        document.getElementById('releaseFundsModal')?.classList.add('hidden');
    }

    function executeInlineApproveRfc() {
        if (decideInFlight) return;
        const form = document.getElementById('inlineApproveRfcForm');
        if (!form) return;
        const paperName = document.getElementById('accPaperSigPrintedName');
        const panelName = document.getElementById('accSigPrintedName');
        if (paperName && panelName) {
            panelName.value = paperName.value;
        }
        const used = document.getElementById('signatureUsed');
        if (used) used.value = '1';
        decideInFlight = true;
        const btn = document.getElementById('rfcFinalConfirmSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Sending...';
        }
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: new FormData(form)
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to save decision.');
            closeRfcFinalConfirm();
            if (typeof window.showMpToast === 'function') {
                showMpToast('RFC approved. Purchaser notified and sent to Accounting for check release.', { title: 'Approved', type: 'success' });
            }
            setTimeout(() => window.location.reload(), 900);
        })
        .catch(error => {
            closeRfcFinalConfirm();
            if (typeof window.showMpToast === 'function') {
                showMpToast(error.message || 'Unable to save decision.', { title: 'Unable to complete', type: 'error', timer: 4200 });
            } else {
                alert(error.message);
            }
        })
        .finally(() => {
            decideInFlight = false;
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Confirm & Send';
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeRfcFinalConfirm();
        closeReleaseFundsModal();
    });
</script>

@include('partials.ris-revision-image-viewer')
@endsection