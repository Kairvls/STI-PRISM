@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', 'Request Checks')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
{{-- accounting CSS loaded via admin-layout for school-admin ATP/PO/RFC --}}
<style>
    .acc-rfc-page,
    .acc-rfc-page * {
        font-family: "Inter", sans-serif;
    }
    .acc-rfc-page .pur-card .acc-pagination {
        padding: 0.75rem 1.25rem 1rem;
        border-top: 1px solid #f1f5f9;
    }
    .acc-rfc-page #rfcTableBody.is-loading {
        opacity: .45;
        filter: saturate(.6);
        pointer-events: none;
        transition: opacity .18s ease, filter .18s ease;
    }
    .acc-rfc-page #rfcTableBody.acc-animate tr {
        animation: accFadeSwap .35s ease both;
    }
    .acc-rfc-page #rfcTableBody.acc-animate tr:nth-child(1)  { animation-delay: .02s; }
    .acc-rfc-page #rfcTableBody.acc-animate tr:nth-child(2)  { animation-delay: .05s; }
    .acc-rfc-page #rfcTableBody.acc-animate tr:nth-child(3)  { animation-delay: .08s; }
    .acc-rfc-page #rfcTableBody.acc-animate tr:nth-child(4)  { animation-delay: .11s; }
    .acc-rfc-page #rfcTableBody.acc-animate tr:nth-child(5)  { animation-delay: .14s; }
    .acc-rfc-page #rfcTableBody.acc-animate tr:nth-child(6)  { animation-delay: .17s; }
    @media (prefers-reduced-motion: reduce) {
        .acc-rfc-page #rfcTableBody.acc-animate tr { animation: none; }
    }
</style>
@endpush

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Needs review',
        'funds' => 'Funds',
        'released' => 'Released',
        'revision' => 'Revision',
        'approved' => 'Approved',
    ];
@endphp

<div class="acc-page acc-rfc-page acc-content-fill space-y-6 fade-in">

    @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $records->total()])

    <div class="pur-card">
        @include('accounting.partials.status-filter-bar', [
            'filters' => $filters,
            'activeFilter' => $filter,
            'baseUrl' => '/school-admin/request-check',
            'counts' => $counts,
            'searchPlaceholder' => 'Search RFC, Request No., ATP, RIS, payee...',
            'formId' => 'rfcSearchForm',
            'searchId' => 'rfcSearch',
            'dateName' => 'submitted',
            'dateValue' => $submittedDate ?? '',
            'dateInputId' => 'rfcSubmittedDate',
            'dateBtnId' => 'rfcSubmittedDateBtn',
            'dateClearId' => 'rfcSubmittedDateClear',
            'dateTitle' => 'Filter by submitted date',
            'exportUrl' => route('school-admin.rfc.export-pdf', array_filter([
                'status' => $filter,
                'search' => request('search'),
                'submitted' => $submittedDate ?? '',
                'focus' => request('focus'),
            ])),
        ])

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1060px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Check Number</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RIS</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Payee</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="rfcTableBody" class="divide-y divide-gray-100 bg-white acc-animate">
                    @include('accounting.request-check._rows', ['records' => $records, 'filter' => $filter])
                </tbody>
            </table>
        </div>

        <div id="rfcPreviewStore" hidden>
            @foreach (($formPreviews ?? []) as $url => $html)
                <template data-preview-store data-url="{{ $url }}">{!! $html !!}</template>
            @endforeach
        </div>

        <div id="rfcPagination">
            @if ($records->hasPages())
                <div class="acc-pagination">{{ $records->links('pagination.president') }}</div>
            @endif
        </div>
    </div>
</div>

{{-- RFC form preview (stay on list; Review still opens the full decision page) --}}
<div
    id="rfcFormPreviewModal"
    class="sa-form-preview-modal fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="rfcFormPreviewTitle"
    data-fullscreen="0"
>
    <div id="rfcFormPreviewPanel" class="sa-form-preview-panel my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-5 py-3.5">
            <div class="flex min-w-0 items-center gap-2.5">
                <h2 id="rfcFormPreviewTitle" class="truncate text-lg font-semibold text-slate-950">RFC Form</h2>
                <span id="rfcFormPreviewPaymentBadge" class="hidden inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"></span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <div id="rfcFormPreviewCarouselNav" class="hidden inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-1.5 py-1">
                    <button type="button" id="rfcFormPreviewPrev" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-white text-slate-700 shadow-xs transition hover:bg-slate-100 disabled:opacity-40" title="Previous RFC">‹</button>
                    <span id="rfcFormPreviewCounter" class="min-w-[56px] text-center text-xs font-semibold tabular-nums text-slate-700">1 / 1</span>
                    <button type="button" id="rfcFormPreviewNext" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-white text-slate-700 shadow-xs transition hover:bg-slate-100 disabled:opacity-40" title="Next RFC">›</button>
                </div>
                <button
                    type="button"
                    id="rfcFormPreviewFullscreen"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                    title="Full screen"
                    aria-label="Full screen"
                    data-fs-btn
                >
                    <svg data-fs-icon="expand" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"></path>
                    </svg>
                    <svg data-fs-icon="collapse" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5v4M15 4h4v4M9 20H5v-4M15 20h4v-4"></path>
                    </svg>
                </button>
                <button
                    type="button"
                    id="rfcFormPreviewPrint"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    title="Print"
                >
                    <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                    Print
                </button>
                <button
                    type="button"
                    id="rfcFormPreviewClose"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
        <div class="sa-form-preview-body max-h-[75vh] flex-1 overflow-auto bg-slate-100">
            <div id="rfcFormPreviewBody"></div>
            <div id="rfcFormPreviewSignWrap" class="hidden px-3 pb-4 md:px-5">
                <form id="rfcListDecisionForm" method="POST" action="" class="mx-auto w-full max-w-[1095px]">
                    @csrf
                    <input type="hidden" name="signature_data" id="signatureData" value="">
                    <input type="hidden" name="signature_used" id="signatureUsed" value="0">
                    @include('accounting.partials.decision-signature-panel', [
                        'savedSignatures' => \App\Support\UserSignatureLibrary::forUser((int) auth()->id()),
                        'signTitle' => 'School Administrator signature',
                        'signHint' => 'Pick a saved signature, draw one, or upload. It authorizes this Request for Check.',
                        'padTitle' => 'Sign the Request for Check',
                        'padHint' => 'Sign clearly. This overlays your printed name on the approval.',
                        'hideInlinePreview' => true,
                    ])
                </form>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-3.5">
            <button
                type="button"
                id="rfcFormPreviewDismiss"
                class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
            >
                Close
            </button>
            <a
                id="rfcFormPreviewReview"
                href="#"
                class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Open review
            </a>
            <button
                type="button"
                id="rfcFormPreviewApprove"
                class="hidden inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001db3]"
            >
                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                Approve RFC
            </button>
        </div>
    </div>
</div>

<div id="rfcListFinalConfirmModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 p-4" style="z-index: 99999;" onclick="closeRfcListFinalConfirm()">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
        <h3 class="text-base font-bold text-slate-900">Confirm RFC Approval?</h3>
        <p class="mt-2 text-sm text-slate-600">
            Are you sure you want to approve <strong id="rfcListFinalConfirmLabel" class="text-slate-900">this RFC</strong>? This will update the status for the Purchaser and send it to Accounting so they can release the check.
        </p>
        <div class="mt-4 space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Payment Method</span>
                <span id="rfcListFinalConfirmPayment" class="font-semibold text-slate-900">—</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Payee</span>
                <span id="rfcListFinalConfirmPayee" class="truncate font-semibold text-slate-900">—</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Amount</span>
                <span id="rfcListFinalConfirmAmount" class="font-semibold tabular-nums text-slate-900">—</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Send to Purchaser</span>
                <span id="rfcListFinalConfirmPurchaser" class="font-semibold text-[#0025cc]">—</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Send to Accounting</span>
                <span class="font-semibold text-emerald-700">For check release</span>
            </div>
        </div>
        <div class="mt-5 flex items-center justify-end gap-2">
            <button type="button" onclick="closeRfcListFinalConfirm()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="rfcListFinalConfirmBtn" onclick="executeRfcListDecision()" class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001db3]">
                Confirm &amp; Send
            </button>
        </div>
    </div>
</div>

<script>
    let rfcListDecideInFlight = false;
    let rfcListDecisionTitle = 'this RFC';
    let rfcListDecisionMeta = { payment: '—', payee: '—', amount: '—', purchaser: 'Purchaser' };

    window.syncRfcPreviewPaperSignature = function () {
        const sigInput = document.getElementById('signatureData');
        const sigVal = sigInput ? String(sigInput.value || '') : '';
        const paperOverlay = document.getElementById('accPaperSigOverlay');
        if (paperOverlay) {
            if (sigVal.indexOf('data:image/') === 0) {
                paperOverlay.src = sigVal;
                paperOverlay.style.display = '';
            } else {
                paperOverlay.style.display = 'none';
            }
        }
        const paperName = document.getElementById('accPaperSigPrintedName');
        const panelName = document.getElementById('accSigPrintedName');
        if (paperName && panelName && panelName.value) {
            paperName.value = panelName.value;
        }
    };

    window.closeRfcListDecision = function () {
        window.closeRfcListFinalConfirm();
    };

    window.closeRfcListFinalConfirm = function () {
        const confirmModal = document.getElementById('rfcListFinalConfirmModal');
        if (!confirmModal) return;
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
    };

    window.submitRfcListDecision = function () {
        if (rfcListDecideInFlight) return;
        const form = document.getElementById('rfcListDecisionForm');
        if (!form) return;
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
        const confirmModal = document.getElementById('rfcListFinalConfirmModal');
        if (confirmModal) {
            const labelEl = document.getElementById('rfcListFinalConfirmLabel');
            if (labelEl) labelEl.textContent = rfcListDecisionTitle || 'this RFC';
            const payEl = document.getElementById('rfcListFinalConfirmPayment');
            if (payEl) payEl.textContent = rfcListDecisionMeta.payment || '—';
            const payeeEl = document.getElementById('rfcListFinalConfirmPayee');
            if (payeeEl) payeeEl.textContent = rfcListDecisionMeta.payee || '—';
            const amtEl = document.getElementById('rfcListFinalConfirmAmount');
            if (amtEl) amtEl.textContent = rfcListDecisionMeta.amount || '—';
            const purEl = document.getElementById('rfcListFinalConfirmPurchaser');
            if (purEl) purEl.textContent = rfcListDecisionMeta.purchaser || 'Purchaser';
            if (confirmModal.parentElement !== document.body) document.body.appendChild(confirmModal);
            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
            return;
        }
        window.executeRfcListDecision();
    };

    let rfcGroupBatchQueue = null;

    window.executeRfcListDecision = function () {
        if (rfcListDecideInFlight) return;
        const form = document.getElementById('rfcListDecisionForm');
        if (!form) return;
        const used = document.getElementById('signatureUsed');
        if (used) used.value = '1';
        rfcListDecideInFlight = true;
        const confirmBtn = document.getElementById('rfcListFinalConfirmBtn');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Sending...';
        }
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const targets = Array.isArray(rfcGroupBatchQueue) && rfcGroupBatchQueue.length > 0
            ? rfcGroupBatchQueue.slice()
            : [{ url: form.action }];

        (async () => {
            for (let i = 0; i < targets.length; i++) {
                if (confirmBtn && targets.length > 1) {
                    confirmBtn.textContent = 'Approving ' + (i + 1) + ' of ' + targets.length + '...';
                }
                const response = await fetch(targets[i].url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: new FormData(form)
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'Unable to save decision.');
                }
            }
            window.closeRfcListDecision();
            if (typeof window.showMpToast === 'function') {
                const msg = targets.length > 1
                    ? targets.length + ' RFCs approved. Purchaser notified and sent to Accounting for check release.'
                    : 'RFC approved. Purchaser notified and sent to Accounting for check release.';
                showMpToast(msg, { title: 'Approved', type: 'success' });
            }
            setTimeout(() => window.location.reload(), 900);
        })()
        .catch(error => {
            window.closeRfcListFinalConfirm();
            if (typeof window.showMpToast === 'function') {
                showMpToast(error.message || 'Unable to save decision.', { title: 'Unable to complete', type: 'error', timer: 4200 });
            } else {
                alert(error.message);
            }
        })
        .finally(() => {
            rfcListDecideInFlight = false;
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm & Send';
            }
        });
    };

    (function () {
        const tbody = document.getElementById('rfcTableBody');
        const pagination = document.getElementById('rfcPagination');
        const searchInput = document.getElementById('rfcSearch');
        const searchForm = document.getElementById('rfcSearchForm');
        const dateInput = document.getElementById('rfcSubmittedDate');
        const filterCards = document.querySelectorAll('.status-filter-card');
        const filterTabs = document.querySelectorAll('.status-filter-tab');
        const previewModal = document.getElementById('rfcFormPreviewModal');
        const previewTitle = document.getElementById('rfcFormPreviewTitle');
        const previewPaymentBadge = document.getElementById('rfcFormPreviewPaymentBadge');
        const previewBody = document.getElementById('rfcFormPreviewBody');
        const previewReview = document.getElementById('rfcFormPreviewReview');
        const previewApprove = document.getElementById('rfcFormPreviewApprove');
        let previewRfcId = '';
        let currentFilter = '{{ $filter }}';
        let currentFocus = @json($attentionFocus['key'] ?? null);
        let searchTimeout = null;
        let fetching = false;

        function currentSubmittedDate() {
            return dateInput ? String(dateInput.value || '').trim() : '';
        }

        @include('accounting.partials.form-preview-loader')
        const previewSkeleton = window.saFormPreview.skeleton;

        function setRfcPreviewFullscreen(isFs) {
            if (!previewModal) return;
            previewModal.dataset.fullscreen = isFs ? '1' : '0';
            const btn = document.getElementById('rfcFormPreviewFullscreen');
            if (!btn) return;
            const expand = btn.querySelector('[data-fs-icon="expand"]');
            const collapse = btn.querySelector('[data-fs-icon="collapse"]');
            if (expand) expand.classList.toggle('hidden', isFs);
            if (collapse) collapse.classList.toggle('hidden', !isFs);
            btn.title = isFs ? 'Exit full screen' : 'Full screen';
            btn.setAttribute('aria-label', isFs ? 'Exit full screen' : 'Full screen');
        }

        function closeRfcPreview() {
            if (!previewModal) return;
            setRfcPreviewFullscreen(false);
            previewModal.classList.add('hidden');
            previewModal.classList.remove('flex');
            if (previewBody) previewBody.innerHTML = '';
            document.body.style.overflow = '';
        }

        async function openRfcPreview(trigger) {
            if (!previewModal || !previewBody) return;

            const previewUrl = trigger.getAttribute('data-preview-url') || '';
            const reviewUrl = trigger.getAttribute('data-review-url') || '#';
            const title = trigger.getAttribute('data-preview-title') || 'RFC Form';
            const paymentMethod = trigger.getAttribute('data-payment-method') || '';
            const paymentLabel = trigger.getAttribute('data-payment-label') || '';
            const purchaserName = trigger.getAttribute('data-purchaser-name') || '';
            const payeeName = trigger.getAttribute('data-payee-name') || '';
            const amountLabel = trigger.getAttribute('data-amount-label') || '—';
            const canApprove = trigger.getAttribute('data-can-approve') === '1';
            previewRfcId = trigger.getAttribute('data-rfc-id') || '';
            rfcListDecisionMeta = {
                payment: paymentLabel || '—',
                payee: payeeName || '—',
                amount: amountLabel || '—',
                purchaser: purchaserName || 'Purchaser',
            };

            if (!previewUrl) {
                if (reviewUrl && reviewUrl !== '#') window.location = reviewUrl;
                return;
            }

            if (previewModal.parentElement !== document.body) {
                document.body.appendChild(previewModal);
            }

            if (previewTitle) previewTitle.textContent = title;
            if (previewPaymentBadge) {
                if (paymentLabel) {
                    const paymentStyles = {
                        direct_payment: 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;',
                        cash_installment: 'background:#fffbeb;color:#b45309;border:1px solid #fde68a;',
                        cash_advance: 'background:#f3e8ff;color:#7e22ce;border:1px solid #d8b4fe;',
                    };
                    previewPaymentBadge.textContent = paymentLabel;
                    previewPaymentBadge.setAttribute('style', paymentStyles[paymentMethod] || 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;');
                    previewPaymentBadge.setAttribute('title', 'Payment method: ' + paymentLabel);
                    previewPaymentBadge.classList.remove('hidden');
                } else {
                    previewPaymentBadge.textContent = '';
                    previewPaymentBadge.removeAttribute('style');
                    previewPaymentBadge.classList.add('hidden');
                }
            }
            if (previewReview) {
                previewReview.href = reviewUrl;
                previewReview.classList.toggle('hidden', !reviewUrl || reviewUrl === '#');
            }
            const previewSignWrap = document.getElementById('rfcFormPreviewSignWrap');
            const decisionForm = document.getElementById('rfcListDecisionForm');
            if (previewApprove) {
                previewApprove.classList.toggle('hidden', !canApprove || !previewRfcId);
            }
            if (previewSignWrap) {
                previewSignWrap.classList.toggle('hidden', !canApprove || !previewRfcId);
            }
            if (decisionForm && canApprove && previewRfcId) {
                decisionForm.action = '/school-admin/request-check/' + previewRfcId + '/approve';
                rfcListDecisionTitle = title || 'Request Check';
            }
            setRfcPreviewFullscreen(false);
            previewBody.dataset.previewUrl = previewUrl;
            previewBody.innerHTML = previewSkeleton;
            previewModal.classList.remove('hidden');
            previewModal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            try {
                const html = await window.saFormPreview.load(previewUrl);
                if (previewBody.dataset.previewUrl !== previewUrl) return;
                previewBody.innerHTML = html;
                if (canApprove && previewRfcId) {
                    const sigInput = document.getElementById('signatureData');
                    if (sigInput && String(sigInput.value || '').indexOf('data:image/') !== 0) {
                        const firstSaved = document.querySelector('#accSavedList .acc-use-saved');
                        if (firstSaved) firstSaved.click();
                    }
                    if (typeof window.syncRfcPreviewPaperSignature === 'function') {
                        window.syncRfcPreviewPaperSignature();
                    }
                }
                if (window.lucide) lucide.createIcons();
            } catch (error) {
                if (previewBody.dataset.previewUrl !== previewUrl) return;
                previewBody.innerHTML = '<p class="px-5 py-10 text-center text-sm text-red-600">Could not load this RFC form.</p>';
            }
        }

        const carouselNav = document.getElementById('rfcFormPreviewCarouselNav');
        const carouselCounter = document.getElementById('rfcFormPreviewCounter');
        const carouselPrev = document.getElementById('rfcFormPreviewPrev');
        const carouselNext = document.getElementById('rfcFormPreviewNext');
        let groupCarouselPayload = null;
        let groupCarouselIdx = 0;

        async function renderGroupCarouselSlide() {
            if (!groupCarouselPayload || !Array.isArray(groupCarouselPayload.slides) || !groupCarouselPayload.slides.length) return;
            const slide = groupCarouselPayload.slides[groupCarouselIdx];
            if (!slide) return;
            const total = groupCarouselPayload.slides.length;
            const canApproveAll = !!groupCarouselPayload.can_approve_all;

            if (carouselNav) carouselNav.classList.remove('hidden');
            if (carouselCounter) carouselCounter.textContent = (groupCarouselIdx + 1) + ' / ' + total;
            if (carouselPrev) carouselPrev.disabled = groupCarouselIdx <= 0;
            if (carouselNext) carouselNext.disabled = groupCarouselIdx >= total - 1;

            previewRfcId = String(slide.id);
            if (previewTitle) previewTitle.textContent = slide.title + ' (' + groupCarouselPayload.request_no + ')';
            if (previewPaymentBadge) {
                if (slide.payment_label) {
                    const paymentStyles = {
                        direct_payment: 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;',
                        cash_installment: 'background:#fffbeb;color:#b45309;border:1px solid #fde68a;',
                        cash_advance: 'background:#f3e8ff;color:#7e22ce;border:1px solid #d8b4fe;',
                    };
                    previewPaymentBadge.textContent = slide.payment_label;
                    previewPaymentBadge.setAttribute('style', paymentStyles[slide.payment_method] || 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;');
                    previewPaymentBadge.classList.remove('hidden');
                } else {
                    previewPaymentBadge.classList.add('hidden');
                }
            }
            if (previewReview) {
                previewReview.href = slide.review_url || '#';
                previewReview.classList.toggle('hidden', !slide.review_url);
            }
            const previewSignWrap = document.getElementById('rfcFormPreviewSignWrap');
            const decisionForm = document.getElementById('rfcListDecisionForm');
            if (previewApprove) {
                previewApprove.classList.toggle('hidden', !canApproveAll);
                previewApprove.innerHTML = '<i data-lucide="check-check" class="h-3.5 w-3.5"></i> Approve All (' + total + ')';
            }
            if (previewSignWrap) {
                previewSignWrap.classList.toggle('hidden', !canApproveAll);
            }
            if (canApproveAll) {
                rfcGroupBatchQueue = groupCarouselPayload.slides.map(s => ({ url: '/school-admin/request-check/' + s.id + '/approve' }));
                if (decisionForm) decisionForm.action = rfcGroupBatchQueue[0].url;
                rfcListDecisionTitle = total + ' RFCs (' + groupCarouselPayload.request_no + ')';
                const uniquePayees = Array.from(new Set(groupCarouselPayload.slides.map(s => s.payee).filter(Boolean)));
                rfcListDecisionMeta = {
                    payment: slide.payment_label || '—',
                    payee: uniquePayees.length === 1 ? uniquePayees[0] : (uniquePayees.length + ' payees'),
                    amount: '₱' + Number(groupCarouselPayload.total_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                    purchaser: slide.purchaser || 'Purchaser',
                };
            } else {
                rfcGroupBatchQueue = null;
            }

            previewBody.dataset.previewUrl = slide.preview_url;
            previewBody.innerHTML = previewSkeleton;
            try {
                const html = await window.saFormPreview.load(slide.preview_url);
                if (previewBody.dataset.previewUrl !== slide.preview_url) return;
                previewBody.innerHTML = html;
                if (canApproveAll) {
                    const sigInput = document.getElementById('signatureData');
                    if (sigInput && String(sigInput.value || '').indexOf('data:image/') !== 0) {
                        const firstSaved = document.querySelector('#accSavedList .acc-use-saved');
                        if (firstSaved) firstSaved.click();
                    }
                    if (typeof window.syncRfcPreviewPaperSignature === 'function') {
                        window.syncRfcPreviewPaperSignature();
                    }
                }
                if (window.lucide) lucide.createIcons();
            } catch (_) {
                previewBody.innerHTML = '<p class="px-5 py-10 text-center text-sm text-red-600">Could not load this RFC form.</p>';
            }
        }

        carouselPrev?.addEventListener('click', function () {
            if (groupCarouselIdx > 0) {
                groupCarouselIdx--;
                renderGroupCarouselSlide();
            }
        });
        carouselNext?.addEventListener('click', function () {
            if (groupCarouselPayload && groupCarouselIdx < groupCarouselPayload.slides.length - 1) {
                groupCarouselIdx++;
                renderGroupCarouselSlide();
            }
        });

        document.addEventListener('click', function (event) {
            const carouselBtn = event.target.closest('[data-sa-rfc-group-carousel]');
            if (carouselBtn) {
                event.preventDefault();
                event.stopPropagation();
                try {
                    groupCarouselPayload = JSON.parse(carouselBtn.getAttribute('data-group-payload') || '{}');
                } catch (_) {
                    return;
                }
                if (!groupCarouselPayload.slides || !groupCarouselPayload.slides.length) return;
                groupCarouselIdx = 0;
                if (previewModal.parentElement !== document.body) document.body.appendChild(previewModal);
                setRfcPreviewFullscreen(false);
                previewModal.classList.remove('hidden');
                previewModal.classList.add('flex');
                document.body.style.overflow = 'hidden';
                renderGroupCarouselSlide();
                return;
            }

            const toggleBtn = event.target.closest('[data-sa-rfc-toggle]');
            const groupRow = !toggleBtn ? event.target.closest('tr[data-sa-rfc-group]') : null;
            const gKey = toggleBtn
                ? toggleBtn.getAttribute('data-sa-rfc-toggle')
                : (groupRow ? groupRow.getAttribute('data-sa-rfc-group') : null);
            if (gKey) {
                event.preventDefault();
                const row = document.querySelector('tr[data-sa-rfc-group="' + gKey + '"]');
                const isOpen = row && row.dataset.open === '1';
                const nextOpen = !isOpen;
                if (row) row.dataset.open = nextOpen ? '1' : '0';
                document.querySelectorAll('tr[data-sa-rfc-child="' + gKey + '"]').forEach(function (tr) {
                    tr.style.display = nextOpen ? '' : 'none';
                });
                const chev = row ? row.querySelector('[data-sa-rfc-chevron]') : null;
                if (chev) chev.classList.toggle('rotate-90', nextOpen);
                return;
            }

            const trigger = event.target.closest('[data-rfc-preview]');
            if (!trigger) return;
            event.preventDefault();
            groupCarouselPayload = null;
            rfcGroupBatchQueue = null;
            if (carouselNav) carouselNav.classList.add('hidden');
            if (previewApprove) {
                previewApprove.innerHTML = '<i data-lucide="check" class="h-3.5 w-3.5"></i> Approve RFC';
            }
            openRfcPreview(trigger);
        });

        document.getElementById('rfcFormPreviewClose')?.addEventListener('click', closeRfcPreview);
        document.getElementById('rfcFormPreviewDismiss')?.addEventListener('click', closeRfcPreview);
        document.getElementById('rfcFormPreviewFullscreen')?.addEventListener('click', function () {
            setRfcPreviewFullscreen(previewModal.dataset.fullscreen !== '1');
        });
        previewModal?.addEventListener('click', function (event) {
            if (event.target === previewModal && previewModal.dataset.fullscreen !== '1') {
                closeRfcPreview();
            }
        });
        document.getElementById('rfcFormPreviewApprove')?.addEventListener('click', function () {
            if (!previewRfcId || typeof window.submitRfcListDecision !== 'function') return;
            const paperName = document.getElementById('accPaperSigPrintedName');
            const panelName = document.getElementById('accSigPrintedName');
            if (paperName && panelName) {
                panelName.value = paperName.value;
            }
            if (!rfcGroupBatchQueue) {
                rfcListDecisionTitle = previewTitle ? previewTitle.textContent : 'Request Check';
            }
            window.submitRfcListDecision();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            const finalConfirm = document.getElementById('rfcListFinalConfirmModal');
            if (finalConfirm && !finalConfirm.classList.contains('hidden')) {
                window.closeRfcListFinalConfirm();
                return;
            }
            const decision = document.getElementById('rfcListDecisionModal');
            if (decision && !decision.classList.contains('hidden')) {
                window.closeRfcListDecision();
                return;
            }
            if (!previewModal || previewModal.classList.contains('hidden')) return;
            if (previewModal.dataset.fullscreen === '1') {
                setRfcPreviewFullscreen(false);
                return;
            }
            closeRfcPreview();
        });
        document.getElementById('rfcFormPreviewPrint')?.addEventListener('click', function () {
            if (!previewBody) return;
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            printWindow.document.write(
                '<!DOCTYPE html><html><head><title>'
                + (previewTitle ? previewTitle.textContent : 'RFC')
                + '</title><link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}"></head><body>'
                + previewBody.innerHTML
                + '</body></html>'
            );
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function () {
                printWindow.print();
            }, 250);
        });

        function syncFocusChip(total) {
            const chip = document.querySelector('[data-attention-focus]');
            if (!chip) return;
            if (!currentFocus) {
                chip.remove();
                return;
            }
            const badge = chip.querySelector('p.text-sm span');
            if (badge && total !== undefined) badge.textContent = Number(total).toLocaleString();
        }

        function updateFilterButtons(activeFilter) {
            filterCards.forEach(card => {
                card.classList.toggle('is-active', card.getAttribute('data-filter') === activeFilter);
            });
            filterTabs.forEach(tab => {
                const active = tab.getAttribute('data-filter') === activeFilter;
                tab.classList.toggle('bg-gray-100/80', active);
                tab.classList.toggle('font-medium', active);
                tab.classList.toggle('text-black', active);
                tab.classList.toggle('text-slate-500', !active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            if (searchForm) {
                const hidden = searchForm.querySelector('input[name="status"]');
                if (hidden) hidden.value = activeFilter;
            }
        }

        function buildUrl(page, filter, search, submitted) {
            const params = new URLSearchParams();
            params.set('status', filter);
            if (currentFocus) params.set('focus', currentFocus);
            if (search) params.set('search', search);
            if (submitted) params.set('submitted', submitted);
            if (page && page > 1) params.set('page', page);
            return '/school-admin/request-check?' + params.toString();
        }

        function applyRfcPayload(data) {
            if (data.previews && window.saFormPreview) window.saFormPreview.prime(data.previews);
            if (tbody) {
                tbody.innerHTML = data.table_html;
                tbody.classList.remove('is-loading');
                tbody.classList.remove('acc-animate');
                void tbody.offsetWidth;
                tbody.classList.add('acc-animate');
            }
            if (pagination) {
                pagination.innerHTML = data.pagination_html
                    ? '<div class="acc-pagination">' + data.pagination_html + '</div>'
                    : '';
                if (typeof window.bindPageCarousels === 'function') window.bindPageCarousels();
            }
            if (data.counts) {
                Object.keys(data.counts).forEach(key => {
                    document.querySelectorAll('[data-count="' + key + '"]').forEach(el => {
                        el.textContent = Number(data.counts[key]).toLocaleString();
                    });
                });
            }
            currentFocus = data.focus || null;
            syncFocusChip(data.total);
            if (window.lucide) lucide.createIcons();
        }

        function fetchData(page, filter) {
            if (fetching) return;
            fetching = true;
            page = page || 1;
            filter = filter || currentFilter;
            const search = searchInput ? searchInput.value.trim() : '';
            const submitted = currentSubmittedDate();
            const requestUrl = new URL(buildUrl(page, filter, search, submitted), window.location.origin).href;
            const cachedRaw = window.PrismListCache?.get?.(requestUrl) || null;

            if (!cachedRaw && tbody) tbody.classList.add('is-loading');

            const finish = () => { fetching = false; };

            if (cachedRaw) {
                try {
                    applyRfcPayload(JSON.parse(cachedRaw));
                } catch (e) {}
                finish();
                fetch(requestUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then(res => res.text())
                    .then(raw => {
                        window.PrismListCache?.set?.(requestUrl, raw);
                        if (raw !== cachedRaw) applyRfcPayload(JSON.parse(raw));
                    })
                    .catch(() => {});
                return;
            }

            fetch(requestUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
            .then(res => res.text())
            .then(raw => {
                window.PrismListCache?.set?.(requestUrl, raw);
                applyRfcPayload(JSON.parse(raw));
                finish();
            })
            .catch(err => {
                console.error(err);
                if (tbody) tbody.classList.remove('is-loading');
                finish();
            });
        }

        function pushUrl(page, filter, search, submitted) {
            window.history.replaceState({}, '', buildUrl(page, filter, search, submitted));
        }

        function refreshList(page) {
            page = page || 1;
            const search = searchInput ? searchInput.value.trim() : '';
            const submitted = currentSubmittedDate();
            fetchData(page, currentFilter);
            pushUrl(page, currentFilter, search, submitted);
        }

        function applyFilter(newFilter) {
            if (newFilter === currentFilter && !currentFocus) return;
            currentFilter = newFilter;
            currentFocus = null;
            syncFocusChip();
            updateFilterButtons(newFilter);
            refreshList(1);
        }

        filterCards.forEach(card => {
            card.addEventListener('click', function (e) {
                e.preventDefault();
                applyFilter(this.getAttribute('data-filter'));
            });
        });

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                applyFilter(this.getAttribute('data-filter'));
            });
        });

        if (searchForm) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                refreshList(1);
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => refreshList(1), 300);
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', function () {
                refreshList(1);
            });
        }

        if (pagination) {
            pagination.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') return;
                e.preventDefault();
                const url = new URL(link.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                refreshList(page);
            });
        }

        window.addEventListener('popstate', function () {
            const params = new URLSearchParams(window.location.search);
            const filter = params.get('status') || 'incoming';
            const search = params.get('search') || '';
            const submitted = params.get('submitted') || '';
            currentFilter = filter;
            currentFocus = params.get('focus') || null;
            if (searchInput) searchInput.value = search;
            if (dateInput) dateInput.value = submitted;
            updateFilterButtons(filter);
            fetchData(1, filter);
        });
    })();
</script>
@endsection
