@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', 'Disbursement Vouchers')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Needs review',
        'revision' => 'Returned',
        'approved' => 'Approved',
    ];
    $search = $search ?? '';
    $submittedDate = $submittedDate ?? '';
@endphp

<div class="acc-page acc-content-fill space-y-6 fade-in">
    <div class="pur-card">
        @include('accounting.partials.status-filter-bar', [
            'filters' => $filters,
            'activeFilter' => $filter,
            'baseUrl' => route('school-admin.dv.index'),
            'counts' => $counts,
            'search' => $search,
            'searchValue' => $search,
            'searchPlaceholder' => 'Search DV, Request No., RFC...',
            'formId' => 'dvSearchForm',
            'searchId' => 'dvSearch',
            'listPanel' => '#sa-dv-list-panel',
            'dateName' => 'submitted',
            'dateValue' => $submittedDate,
            'dateInputId' => 'dvSubmittedDate',
            'dateBtnId' => 'dvSubmittedDateBtn',
            'dateClearId' => 'dvSubmittedDateClear',
            'dateTitle' => 'Filter by submitted date',
            'exportUrl' => route('school-admin.dv.export-pdf', array_filter([
                'status' => $filter,
                'search' => $search,
                'submitted' => $submittedDate,
            ])),
        ])

        <div id="sa-dv-list-panel" data-list-panel-root class="relative">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-gray-50/70">
                        <tr class="border-b border-gray-100">
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">DV Number</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($records as $row)
                            @php
                                $dvRequestNo = trim((string) ($row->dv_request_no ?? ($row->request_check_request_no ?? '')));
                                $dvLabel = $row->dv_form_number ?: ('DV-'.$row->dv_id);
                                $dvReviewUrl = route('school-admin.dv.show', $row->dv_id);
                                $dvPreviewUrl = route('school-admin.dv.preview', $row->dv_id);
                                $submittedAt = $row->dv_submitted_at ?? null;
                            @endphp
                            <tr class="transition hover:bg-gray-50/70">
                                <td class="px-5 py-4">
                                    <button
                                        type="button"
                                        class="font-semibold text-gray-900 transition hover:text-[#0025cc] hover:underline"
                                        data-dv-preview
                                        data-preview-url="{{ $dvPreviewUrl }}"
                                        data-review-url="{{ $dvReviewUrl }}"
                                        data-preview-title="{{ $dvLabel }}"
                                        data-dv-id="{{ $row->dv_id }}"
                                        data-can-approve="{{ ($row->dv_status ?? '') === 'Pending' && ! empty($row->dv_submitted_at) ? '1' : '0' }}"
                                        title="View DV form"
                                    >
                                        {{ $dvLabel }}
                                    </button>
                                </td>
                                <td class="px-5 py-4">
                                    @include('partials.procurement-request-no', ['requestNo' => $dvRequestNo, 'inline' => true, 'showLabel' => false])
                                    @if($dvRequestNo === '')
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $row->request_check_form_number ?: 'Request for Check' }}</td>
                                <td class="px-5 py-4 text-right tabular-nums text-gray-900">{{ $row->dv_amount !== null ? '₱'.number_format((float) $row->dv_amount, 2) : '—' }}</td>
                                <td class="px-5 py-4 text-gray-600 whitespace-nowrap">
                                    @if($submittedAt)
                                        {{ \Illuminate\Support\Carbon::parse($submittedAt)->format('M j, Y') }}
                                        <span class="block text-xs text-gray-400">{{ \Illuminate\Support\Carbon::parse($submittedAt)->format('g:i A') }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @include('accounting.partials.status-badge', [
                                        'status' => $row->dv_status,
                                        'submitted' => $row->dv_submitted_at ?? null,
                                    ])
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            data-dv-preview
                                            data-preview-url="{{ $dvPreviewUrl }}"
                                            data-review-url="{{ $dvReviewUrl }}"
                                            data-preview-title="{{ $dvLabel }}"
                                            data-dv-id="{{ $row->dv_id }}"
                                            data-can-approve="{{ ($row->dv_status ?? '') === 'Pending' && ! empty($row->dv_submitted_at) ? '1' : '0' }}"
                                            title="View DV form"
                                            aria-label="View DV form"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        @include('partials.share-document-button', [
                                            'type' => 'dv',
                                            'id' => $row->dv_id,
                                            'label' => \App\Support\RequestExplorer::shareUiLabel('dv', $row->dv_form_number ?? null),
                                            'variant' => 'button',
                                        ])
                                        <x-view-action-button
                                            :href="$dvReviewUrl"
                                            label="Review"
                                            title="Review DV — form and pipeline"
                                            aria-label="Review DV"
                                            data-tip="Review DV — form and pipeline"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-500">No Disbursement Vouchers in this view.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="dvPreviewStore" hidden>
                @foreach (($formPreviews ?? []) as $url => $html)
                    <template data-preview-store data-url="{{ $url }}">{!! $html !!}</template>
                @endforeach
            </div>
            @if($records->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">{{ $records->links('pagination.president') }}</div>
            @endif
        </div>
    </div>
</div>

{{-- DV form preview (stay on list; Review still opens the full decision page) --}}
<div
    id="dvFormPreviewModal"
    class="sa-form-preview-modal fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dvFormPreviewTitle"
    data-fullscreen="0"
>
    <div id="dvFormPreviewPanel" class="sa-form-preview-panel my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-5 py-3.5">
            <h2 id="dvFormPreviewTitle" class="truncate text-lg font-semibold text-slate-950">DV Form</h2>
            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    id="dvFormPreviewFullscreen"
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
                    id="dvFormPreviewPrint"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    title="Print"
                >
                    <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                    Print
                </button>
                <button
                    type="button"
                    id="dvFormPreviewClose"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
        <div id="dvFormPreviewBody" class="sa-form-preview-body max-h-[72vh] flex-1 overflow-auto bg-slate-50"></div>
        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-3.5">
            <button
                type="button"
                id="dvFormPreviewDismiss"
                class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
            >
                Close
            </button>
            <a
                id="dvFormPreviewReview"
                href="#"
                class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Open review
            </a>
            <button
                type="button"
                id="dvFormPreviewApprove"
                class="hidden inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001db3]"
            >
                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                Approve
            </button>
        </div>
    </div>
</div>

<div id="dvListDecisionModal" class="confirm-modal hidden" style="z-index: 12050;">
    <div class="confirm-backdrop" onclick="closeDvListDecision()"></div>
    <div class="confirm-card wide" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3>Sign to approve</h3>
                <p id="dvListDecisionSubtitle">Disbursement Voucher</p>
            </div>
            <button type="button" class="icon-close" onclick="closeDvListDecision()" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <form id="dvListDecisionForm" method="POST" action="">
            @csrf
            <input type="hidden" name="signature_data" id="signatureData" value="">
            <input type="hidden" name="signature_used" id="signatureUsed" value="0">
            <div class="mt-4">
                @include('accounting.partials.decision-signature-panel', [
                    'savedSignatures' => \App\Support\UserSignatureLibrary::forUser((int) auth()->id()),
                    'signTitle' => 'School Administrator signature',
                    'signHint' => 'Pick a saved signature, draw one, or upload. It authorizes this Disbursement Voucher.',
                    'padTitle' => 'Sign the DV',
                    'padHint' => 'Sign clearly. This overlays your printed name on the approval.',
                ])
            </div>
            <div class="mt-4">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" placeholder="Optional for approve."></textarea>
            </div>
            <div class="confirm-actions mt-5">
                <button type="button" class="btn-ghost" onclick="closeDvListDecision()">Cancel</button>
                <button type="button" class="btn-approve" onclick="submitDvListDecision()">Approve</button>
            </div>
        </form>
    </div>
</div>

<script>
    let dvListDecideInFlight = false;

    window.openDvListDecision = function (id, title) {
        const form = document.getElementById('dvListDecisionForm');
        const modal = document.getElementById('dvListDecisionModal');
        if (!form || !modal || !id) return;
        form.action = '/school-admin/disbursement-vouchers/' + id + '/approve';
        const subtitle = document.getElementById('dvListDecisionSubtitle');
        if (subtitle) subtitle.textContent = title || 'Disbursement Voucher';
        const remarks = form.querySelector('textarea[name="remarks"]');
        if (remarks) remarks.value = '';
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
        else {
            const data = document.getElementById('signatureData');
            const used = document.getElementById('signatureUsed');
            if (data) data.value = '';
            if (used) used.value = '0';
        }
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    };

    window.closeDvListDecision = function () {
        document.getElementById('dvListDecisionModal')?.classList.add('hidden');
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
    };

    window.submitDvListDecision = function () {
        if (dvListDecideInFlight) return;
        const form = document.getElementById('dvListDecisionForm');
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
        const used = document.getElementById('signatureUsed');
        if (used) used.value = '1';
        dvListDecideInFlight = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: new FormData(form)
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to save decision.');
            window.closeDvListDecision();
            if (typeof window.showMpToast === 'function') {
                showMpToast('Disbursement Voucher approved successfully.', { title: 'Approved', type: 'success' });
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
        .finally(() => { dvListDecideInFlight = false; });
    };

    (function () {
        let searchTimer = null;
        document.addEventListener('input', function (event) {
            const input = event.target;
            if (!input || input.id !== 'dvSearch') return;
            const form = input.form;
            if (!form) return;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                if (typeof form.requestSubmit === 'function') form.requestSubmit();
                else form.submit();
            }, 350);
        });

        function syncDvStatusTabs(active) {
            document.querySelectorAll('.status-filter-tab').forEach(function (tab) {
                const on = tab.getAttribute('data-filter') === active;
                tab.classList.toggle('bg-gray-100/80', on);
                tab.classList.toggle('font-medium', on);
                tab.classList.toggle('text-black', on);
                tab.classList.toggle('text-slate-500', !on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            const hidden = document.querySelector('#dvSearchForm input[name="status"]');
            if (hidden) hidden.value = active;
        }

        document.addEventListener('click', function (event) {
            const tab = event.target.closest('.status-filter-tab[data-list-panel="#sa-dv-list-panel"]');
            if (!tab) return;
            syncDvStatusTabs(tab.getAttribute('data-filter') || 'incoming');
        });

        const previewModal = document.getElementById('dvFormPreviewModal');
        const previewTitle = document.getElementById('dvFormPreviewTitle');
        const previewBody = document.getElementById('dvFormPreviewBody');
        const previewReview = document.getElementById('dvFormPreviewReview');
        const previewApprove = document.getElementById('dvFormPreviewApprove');
        let previewDvId = '';
        if (!previewModal || !previewBody) return;

        @include('accounting.partials.form-preview-loader')
        const previewSkeleton = window.saFormPreview.skeleton;

        function setDvPreviewFullscreen(isFs) {
            previewModal.dataset.fullscreen = isFs ? '1' : '0';
            const btn = document.getElementById('dvFormPreviewFullscreen');
            if (!btn) return;
            const expand = btn.querySelector('[data-fs-icon="expand"]');
            const collapse = btn.querySelector('[data-fs-icon="collapse"]');
            if (expand) expand.classList.toggle('hidden', isFs);
            if (collapse) collapse.classList.toggle('hidden', !isFs);
            btn.title = isFs ? 'Exit full screen' : 'Full screen';
            btn.setAttribute('aria-label', isFs ? 'Exit full screen' : 'Full screen');
        }

        function closeDvPreview() {
            setDvPreviewFullscreen(false);
            previewModal.classList.add('hidden');
            previewModal.classList.remove('flex');
            previewBody.innerHTML = '';
            document.body.style.overflow = '';
        }

        async function openDvPreview(trigger) {
            const previewUrl = trigger.getAttribute('data-preview-url') || '';
            const reviewUrl = trigger.getAttribute('data-review-url') || '#';
            const title = trigger.getAttribute('data-preview-title') || 'DV Form';
            const canApprove = trigger.getAttribute('data-can-approve') === '1';
            previewDvId = trigger.getAttribute('data-dv-id') || '';

            if (!previewUrl) {
                if (reviewUrl && reviewUrl !== '#') window.location = reviewUrl;
                return;
            }

            if (previewModal.parentElement !== document.body) {
                document.body.appendChild(previewModal);
            }

            if (previewTitle) previewTitle.textContent = title;
            if (previewReview) {
                previewReview.href = reviewUrl;
                previewReview.classList.toggle('hidden', !reviewUrl || reviewUrl === '#');
            }
            if (previewApprove) {
                previewApprove.classList.toggle('hidden', !canApprove || !previewDvId);
            }
            setDvPreviewFullscreen(false);
            previewBody.dataset.previewUrl = previewUrl;
            previewBody.innerHTML = previewSkeleton;
            previewModal.classList.remove('hidden');
            previewModal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            try {
                const html = await window.saFormPreview.load(previewUrl);
                if (previewBody.dataset.previewUrl !== previewUrl) return;
                previewBody.innerHTML = html;
                if (window.lucide) lucide.createIcons();
            } catch (error) {
                if (previewBody.dataset.previewUrl !== previewUrl) return;
                previewBody.innerHTML = '<p class="px-5 py-10 text-center text-sm text-red-600">Could not load this DV form.</p>';
            }
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dv-preview]');
            if (!trigger) return;
            event.preventDefault();
            openDvPreview(trigger);
        });

        document.getElementById('dvFormPreviewClose')?.addEventListener('click', closeDvPreview);
        document.getElementById('dvFormPreviewDismiss')?.addEventListener('click', closeDvPreview);
        document.getElementById('dvFormPreviewFullscreen')?.addEventListener('click', function () {
            setDvPreviewFullscreen(previewModal.dataset.fullscreen !== '1');
        });
        previewModal.addEventListener('click', function (event) {
            if (event.target === previewModal && previewModal.dataset.fullscreen !== '1') {
                closeDvPreview();
            }
        });
        document.getElementById('dvFormPreviewApprove')?.addEventListener('click', function () {
            if (!previewDvId || typeof window.openDvListDecision !== 'function') return;
            window.openDvListDecision(previewDvId, previewTitle ? previewTitle.textContent : 'Disbursement Voucher');
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            const decision = document.getElementById('dvListDecisionModal');
            if (decision && !decision.classList.contains('hidden')) {
                window.closeDvListDecision();
                return;
            }
            if (previewModal.classList.contains('hidden')) return;
            if (previewModal.dataset.fullscreen === '1') {
                setDvPreviewFullscreen(false);
                return;
            }
            closeDvPreview();
        });
        document.getElementById('dvFormPreviewPrint')?.addEventListener('click', function () {
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            printWindow.document.write(
                '<!DOCTYPE html><html><head><title>'
                + (previewTitle ? previewTitle.textContent : 'DV')
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
    })();
</script>
@endsection
