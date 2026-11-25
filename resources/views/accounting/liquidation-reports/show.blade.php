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
                @php
                    $showLiqRaw = trim((string) ($liq->liquidation_report_status ?? ''));
                    $showLiqPill = match (true) {
                        in_array($showLiqRaw, ['Submitted', 'Under Review', 'Resubmitted'], true) => [
                            'label' => 'Needs Verification',
                            'bg' => '#eef2ff', 'fg' => '#4338ca', 'border' => '#c7d2fe', 'dot' => '#4f46e5',
                        ],
                        in_array($showLiqRaw, ['Minor Revision', 'Returned', 'For revision'], true) => [
                            'label' => 'For Revision',
                            'bg' => '#fff7ed', 'fg' => '#c2410c', 'border' => '#fed7aa', 'dot' => '#ea580c',
                        ],
                        $showLiqRaw === 'Approved' => [
                            'label' => 'Verified',
                            'bg' => '#ecfdf5', 'fg' => '#047857', 'border' => '#a7f3d0', 'dot' => '#059669',
                        ],
                        $showLiqRaw === 'Rejected' => [
                            'label' => 'Rejected',
                            'bg' => '#fef2f2', 'fg' => '#b91c1c', 'border' => '#fecaca', 'dot' => '#dc2626',
                        ],
                        default => [
                            'label' => \App\Support\ProcurementStatus::label($showLiqRaw, 'liq'),
                            'bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#cbd5e1', 'dot' => '#64748b',
                        ],
                    };
                @endphp
                <span
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                    style="background:{{ $showLiqPill['bg'] }};color:{{ $showLiqPill['fg'] }};border-color:{{ $showLiqPill['border'] }};"
                >
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $showLiqPill['dot'] }};"></span>
                    <span>{{ $showLiqPill['label'] }}</span>
                </span>
                @include('partials.ris-urgency-badge', [
                    'urgent' => \App\Support\DocumentUrgency::isUrgent('LIQ', $liq->liquidation_report_id),
                    'title' => 'Inherited from linked RIS',
                ])
                @include('accounting.partials.deadline-badge', ['deadline' => $liq->liquidation_report_submission_deadline ?? null])
            </div>
            <p class="acc-page-subtitle">Approval completes the transaction.</p>
            @if (!empty($liq->liquidation_report_submission_deadline))
                <p class="mt-1 text-xs font-semibold {{ \Carbon\Carbon::parse($liq->liquidation_report_submission_deadline)->startOfDay()->lt(now()->startOfDay()) ? 'text-rose-600' : (\Carbon\Carbon::parse($liq->liquidation_report_submission_deadline)->startOfDay()->eq(now()->startOfDay()) ? 'text-amber-600' : 'text-slate-500') }}">
                    Submission deadline: {{ \Carbon\Carbon::parse($liq->liquidation_report_submission_deadline)->format('M d, Y') }}
                </p>
            @endif
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="openVerifyConfirmModal()" class="acc-action-btn is-approve" data-tip="Verify liquidation">
                    <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                    <span>Verify</span>
                </button>
                <button
                    type="button"
                    onclick="openDecisionModal('liq', {{ $liq->liquidation_report_id }}, 'Rejected')"
                    class="acc-action-btn is-revise"
                    style="background:#fff7ed;border:1px solid #fdba74;color:#c2410c;"
                    onmouseenter="this.style.background='#ffedd5'"
                    onmouseleave="this.style.background='#fff7ed'"
                    data-tip="Send back to Purchaser for revision"
                >
                    <i data-lucide="undo-2" class="h-4 w-4"></i>
                    <span>Request Revision</span>
                </button>
            @endif
            <button type="button" class="acc-action-btn" onclick="window.accountingPrintForm({ page: 'landscape', sheetId: 'acc-print-sheet' })" data-tip="Print liquidation">
                <i data-lucide="printer" class="h-4 w-4"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div data-doc-switcher>
    <div class="acc-review-body">
        <div class="acc-review-grid">
            <div>
                <div data-doc-panel="liq">
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
                    @if ($reviewable)
                        <div id="accShowSignatureWrap" class="mt-4">
                            <input type="hidden" id="signatureData" value="">
                            <input type="hidden" id="signatureUsed" value="0">
                            @include('accounting.partials.decision-signature-panel', [
                                'savedSignatures' => $savedSignatures ?? collect(),
                                'signTitle' => 'Accounting signature',
                                'signHint' => 'Pick a saved signature, draw one, or upload. It checks and completes this liquidation.',
                                'padTitle' => 'Sign the Liquidation',
                                'padHint' => 'Sign clearly. This overlays your printed name on Checked by.',
                                'hideInlinePreview' => true,
                            ])
                        </div>
                    @endif
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
                <div data-doc-panel="related" hidden>
                    <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex h-2 w-2 shrink-0 rounded-full" style="background:#0025cc;"></span>
                            <span id="accRelatedDocTitle" class="truncate text-xs font-semibold text-slate-800">Related Document</span>
                        </div>
                        <button
                            type="button"
                            data-switch-doc="liq"
                            data-back-to-liq
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                            <span>Back to Liquidation</span>
                        </button>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-[#eef1f5]">
                        @foreach (['ris', 'atp', 'rfc', 'rr'] as $relKey)
                            @if (!empty($chain[$relKey]['url']))
                                <iframe
                                    data-related-frame="{{ $relKey }}"
                                    src="{{ $chain[$relKey]['url'] }}"
                                    title="{{ strtoupper($relKey) }} preview"
                                    class="w-full border-0 bg-[#eef1f5]"
                                    style="display:none;"
                                    scrolling="no"
                                    hidden
                                ></iframe>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="acc-side-stack">
                @include('accounting.partials.related-docs', [
                    'chain' => $chain,
                    'current' => 'liq',
                    'switchable' => true,
                    'switchableKeys' => ['ris', 'atp', 'rfc', 'rr', 'liq'],
                ])
                @include('accounting.partials.history', ['history' => $history])
                @if (!empty($liq->liquidation_report_revision_notes))
                    <div class="acc-note acc-note-info">
                        Last revision note: {{ $liq->liquidation_report_revision_notes }}
                        @include('partials.ris-revision-images', [
                            'revision' => \App\Support\DocumentRevisionNotes::latest('LIQ', $liq->liquidation_report_id),
                            'routeName' => 'accounting.document-revision-image',
                            'size' => 'sm',
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>
</div>

{{-- Request Revision modal --}}
<div id="decisionModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 99999;" role="dialog" aria-modal="true" aria-labelledby="decisionModalTitle">
    <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-[1px]" onclick="closeDecisionModal()"></div>
    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" style="background:#fff7ed;border-color:#fed7aa;color:#c2410c;">
                    <i data-lucide="undo-2" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 id="decisionModalTitle" class="text-base font-bold text-slate-900">Request Revision</h3>
                    <p id="decisionModalSubtitle" class="mt-0.5 text-xs text-slate-500">{{ $liq->liquidation_report_form_number ?? ('LIQ-'.$liq->liquidation_report_id) }} · Returns to {{ $liq->liquidation_report_employee_name ?: 'Purchaser' }}</p>
                </div>
            </div>
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" onclick="closeDecisionModal()" data-tip="Close" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <form id="decisionForm" method="POST" action="">
            @csrf
            <input type="hidden" name="target_type" id="targetType" value="" />
            <input type="hidden" name="target_id" id="targetId" value="" />
            <input type="hidden" name="decision" id="targetDecision" value="" />
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Remarks <span id="remarksReqStar" class="text-red-500">*</span></label>
                    <textarea
                        name="remarks"
                        rows="3"
                        placeholder="Describe what needs to be revised by the Purchaser..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                    ></textarea>
                </div>
                <div id="decisionImagesBlock">
                    @include('admin.partials.revision-image-picker', ['pickerId' => 'decisionRevisionImages'])
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" data-tip="Cancel decision" onclick="closeDecisionModal()">Cancel</button>
                <button
                    type="button"
                    id="rejectBtn"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition"
                    style="background:#d97706;color:#ffffff;"
                    onmouseenter="this.style.background='#b45309'"
                    onmouseleave="this.style.background='#d97706'"
                    data-tip="Confirm revision"
                    onclick="submitDecision('Rejected')"
                >
                    <i data-lucide="send" class="h-4 w-4"></i>
                    <span>Send to Purchaser</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Verify confirmation modal --}}
<div id="accLiqShowVerifyConfirm" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 99999;" role="dialog" aria-modal="true" aria-labelledby="accShowVerifyTitle">
    <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-[1px]" onclick="closeVerifyConfirmModal()"></div>
    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" style="background:#ecfdf5;border-color:#a7f3d0;color:#047857;">
                <i data-lucide="shield-check" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h4 id="accShowVerifyTitle" class="text-base font-bold text-slate-900">Verify &amp; Complete Transaction?</h4>
                <p class="mt-1 text-sm text-slate-600">
                    Verifying <strong>{{ $liq->liquidation_report_form_number ?? ('LIQ-'.$liq->liquidation_report_id) }}</strong> will mark it as <strong>Approved</strong> and complete this Cash Advance transaction.
                </p>
            </div>
        </div>
        <div class="mt-4 space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Liquidation No.</span>
                <span class="font-semibold text-slate-900">{{ $liq->liquidation_report_form_number ?? ('LIQ-'.$liq->liquidation_report_id) }}</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Receiving Report</span>
                <span class="font-medium text-slate-800">{{ $liq->receiving_report_form_number ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Employee</span>
                <span class="font-medium text-slate-800">{{ $liq->liquidation_report_employee_name ?? '—' }}</span>
            </div>
        </div>
        <div class="mt-5 flex items-center justify-end gap-2">
            <button type="button" onclick="closeVerifyConfirmModal()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="accShowVerifySubmitBtn" onclick="confirmVerifyLiquidation()" class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90" style="background:#059669;">
                <i data-lucide="check" class="h-4 w-4"></i>
                <span>Confirm &amp; Complete</span>
            </button>
        </div>
    </div>
</div>

@php
    $relatedDocMeta = [
        'ris' => [
            'url' => $chain['ris']['url'] ?? null,
            'title' => 'RIS · ' . ($chain['ris']['label'] ?? ''),
        ],
        'atp' => [
            'url' => $chain['atp']['url'] ?? null,
            'title' => 'ATP · ' . ($chain['atp']['label'] ?? ''),
        ],
        'rfc' => [
            'url' => $chain['rfc']['url'] ?? null,
            'title' => 'Request Check · ' . ($chain['rfc']['label'] ?? ''),
        ],
        'rr' => [
            'url' => $chain['rr']['url'] ?? null,
            'title' => 'Receiving Report · ' . ($chain['rr']['label'] ?? ''),
        ],
    ];
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        const modal = document.getElementById('accLiqShowVerifyConfirm');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        const revModal = document.getElementById('decisionModal');
        if (revModal && revModal.parentElement !== document.body) {
            document.body.appendChild(revModal);
        }
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let decideInFlight = false;

    function openVerifyConfirmModal() {
        const hasSig = window.accountingSignaturePanel
            ? window.accountingSignaturePanel.hasSignature()
            : String(document.getElementById('signatureData')?.value || '').indexOf('data:image/') === 0;
        if (!hasSig) {
            const wrap = document.getElementById('accShowSignatureWrap');
            if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof window.showMpToast === 'function') {
                showMpToast('Please add your signature below the liquidation form before verifying.', { title: 'Signature required', type: 'warning', timer: 3600 });
            } else {
                alert('Please add your signature below the liquidation form before verifying.');
            }
            return;
        }
        const modal = document.getElementById('accLiqShowVerifyConfirm');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) lucide.createIcons();
    }

    function closeVerifyConfirmModal() {
        const modal = document.getElementById('accLiqShowVerifyConfirm');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function confirmVerifyLiquidation() {
        if (decideInFlight) return;
        decideInFlight = true;
        const btn = document.getElementById('accShowVerifySubmitBtn');
        if (btn) btn.disabled = true;

        const sigData = document.getElementById('signatureData')?.value || '';
        const printedName = document.getElementById('accSigPrintedName')?.value
            || document.getElementById('accPaperSigPrintedName')?.value
            || '';

        const checkedDate = document.getElementById('accCheckedByDate')?.value || '';

        const fd = new FormData();
        fd.append('_token', csrfToken);
        fd.append('decision', 'Approved');
        fd.append('signature_data', sigData);
        fd.append('signature_used', '1');
        if (printedName) fd.append('signer_name', printedName);
        if (checkedDate) fd.append('checked_by_date', checkedDate);

        fetch('/accounting/liquidation-reports/{{ $liq->liquidation_report_id }}/approve', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: fd
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to verify liquidation.');
            closeVerifyConfirmModal();
            if (typeof window.showMpToast === 'function') {
                showMpToast(data.message || 'Liquidation verified. Transaction completed.', { title: 'Verified', type: 'success' });
            }
            setTimeout(() => window.location.reload(), 900);
        })
        .catch(error => {
            closeVerifyConfirmModal();
            if (typeof window.showMpToast === 'function') {
                showMpToast(error.message || 'Unable to verify liquidation.', { title: 'Unable to complete', type: 'error', timer: 4200 });
            } else {
                alert(error.message);
            }
        })
        .finally(() => {
            decideInFlight = false;
            if (btn) btn.disabled = false;
        });
    }

    function openDecisionModal(type, id, presetDecision) {
        const form = document.getElementById('decisionForm');
        document.getElementById('targetType').value = type;
        document.getElementById('targetId').value = id;
        document.getElementById('targetDecision').value = presetDecision || '';
        const remarks = form.querySelector('textarea[name="remarks"]');
        if (remarks) remarks.value = '';
        document.getElementById('decisionModalSubtitle').textContent = '{{ $liq->liquidation_report_form_number ?? ('LIQ-'. $liq->liquidation_report_id) }} · Returns to {{ $liq->liquidation_report_employee_name ?: 'Purchaser' }}';
        form.action = '/accounting/liquidation-reports/' + id + '/revise';
        if (window.resetRevisionImagePicker) window.resetRevisionImagePicker('decisionRevisionImages');
        document.getElementById('decisionModalTitle').textContent = 'Request Revision';
        const dm = document.getElementById('decisionModal');
        dm.classList.remove('hidden');
        dm.classList.add('flex');
        if (window.lucide) lucide.createIcons();
    }

    function closeDecisionModal() {
        const dm = document.getElementById('decisionModal');
        if (!dm) return;
        dm.classList.add('hidden');
        dm.classList.remove('flex');
    }

    function submitDecision(decision) {
        if (decideInFlight) return;
        const form = document.getElementById('decisionForm');
        document.getElementById('targetDecision').value = decision;
        const remarksField = form.querySelector('textarea[name="remarks"]');
        if (!remarksField || !remarksField.value.trim()) {
            if (typeof window.showMpToast === 'function') {
                showMpToast('Please provide a revision reason.', { title: 'Remarks required', type: 'warning', timer: 3600 });
            } else {
                alert('Please provide a revision reason.');
            }
            return;
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
                showMpToast('Revision requested.', { title: 'Revision requested', type: 'success' });
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
        closeVerifyConfirmModal();
        closeDecisionModal();
    });

    (function () {
        const docMeta = @json($relatedDocMeta);
        const titleEl = document.getElementById('accRelatedDocTitle');
        const frames = Array.from(document.querySelectorAll('iframe[data-related-frame]'));

        function fitFrame(iframe) {
            if (!iframe || iframe.hidden) return;
            try {
                const doc = iframe.contentDocument || iframe.contentWindow?.document;
                if (!doc || !doc.body) return;
                doc.documentElement.style.height = 'auto';
                doc.documentElement.style.minHeight = '0';
                doc.body.style.height = 'auto';
                doc.body.style.minHeight = '0';
                doc.body.style.overflow = 'hidden';
                const h = Math.max(
                    doc.body.scrollHeight,
                    Math.ceil(doc.body.getBoundingClientRect().height)
                );
                if (h > 0) {
                    iframe.style.height = h + 'px';
                }
            } catch (_) {}
        }

        frames.forEach(function (iframe) {
            iframe.addEventListener('load', function () {
                fitFrame(iframe);
            });
        });

        window.addEventListener('resize', function () {
            frames.forEach(fitFrame);
        });

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-switch-doc]');
            if (!button) return;
            const root = button.closest('[data-doc-switcher]');
            if (!root) return;

            const key = button.getAttribute('data-switch-doc');
            const liqPanel = root.querySelector('[data-doc-panel="liq"]');
            const relatedPanel = root.querySelector('[data-doc-panel="related"]');
            if (!liqPanel || !relatedPanel) return;

            if (key === 'liq') {
                event.preventDefault();
                liqPanel.hidden = false;
                relatedPanel.hidden = true;
                frames.forEach(function (f) {
                    f.hidden = true;
                    f.style.display = 'none';
                });
                if (window.fitAccountingDocuments) {
                    requestAnimationFrame(function () { window.fitAccountingDocuments(); });
                }
            } else {
                const targetFrame = root.querySelector('iframe[data-related-frame="' + key + '"]');
                if (!targetFrame) return;
                event.preventDefault();
                const meta = docMeta[key] || {};
                liqPanel.hidden = true;
                relatedPanel.hidden = false;
                if (titleEl) {
                    titleEl.textContent = meta.title || button.getAttribute('data-doc-title') || key.toUpperCase();
                }
                frames.forEach(function (f) {
                    const show = f === targetFrame;
                    f.hidden = !show;
                    f.style.display = show ? 'block' : 'none';
                });
                requestAnimationFrame(function () {
                    fitFrame(targetFrame);
                    setTimeout(function () { fitFrame(targetFrame); }, 120);
                });
            }

            root.querySelectorAll('[data-chain-item]').forEach(function (item) {
                const active = item.getAttribute('data-chain-item') === key;
                item.classList.toggle('acc-chain-item--current', active);
                const curEl = item.querySelector('[data-chain-current]');
                const openEl = item.querySelector('[data-chain-open]');
                if (curEl) curEl.hidden = !active;
                if (openEl) openEl.hidden = active;
            });

            root.querySelectorAll('[role="region"][aria-label="Document pipeline"] [data-switch-doc]').forEach(function (node) {
                const on = node.getAttribute('data-switch-doc') === key;
                node.classList.toggle('bg-[#0025cc]', on);
                node.classList.toggle('text-white', on);
                node.classList.toggle('ring-[#0025cc]', on);
                node.classList.toggle('bg-white', !on);
                node.classList.toggle('text-slate-700', !on);
                node.classList.toggle('ring-slate-200', !on);
                if (on) node.setAttribute('aria-current', 'page');
                else node.removeAttribute('aria-current');
            });

            if (window.lucide) lucide.createIcons();
        });
    })();
</script>

@include('partials.ris-revision-image-viewer')
@endsection