@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', 'ATP')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/president-modern.css') }}">
<link rel="stylesheet" href="{{ asset('css/accounting-modern.css') }}">
@include('accounting.partials.ui')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
<style>
    .acc-atp-page,
    .acc-atp-page * {
        font-family: "Inter", sans-serif;
    }
    .acc-atp-page .pur-card .acc-pagination {
        padding: 0.75rem 1.25rem 1rem;
        border-top: 1px solid #f1f5f9;
    }
    .acc-atp-page #atpTableBody.is-loading {
        opacity: .45;
        filter: saturate(.6);
        pointer-events: none;
        transition: opacity .18s ease, filter .18s ease;
    }
    .acc-atp-page #atpTableBody.acc-animate tr {
        animation: accFadeSwap .35s ease both;
    }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(1)  { animation-delay: .02s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(2)  { animation-delay: .05s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(3)  { animation-delay: .08s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(4)  { animation-delay: .11s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(5)  { animation-delay: .14s; }
    .acc-atp-page #atpTableBody.acc-animate tr:nth-child(6)  { animation-delay: .17s; }
    @media (prefers-reduced-motion: reduce) {
        .acc-atp-page #atpTableBody.acc-animate tr { animation: none; }
    }
</style>
@endpush

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Needs review',
        'revision' => 'Revision',
        'approved' => 'Approved',
    ];
@endphp

<div class="acc-page acc-atp-page acc-content-fill space-y-6 fade-in">

    @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $records->total()])

    <div class="pur-card">
        @include('accounting.partials.status-filter-bar', [
            'filters' => $filters,
            'activeFilter' => $filter,
            'baseUrl' => '/school-admin/authority-to-purchase',
            'counts' => $counts,
            'searchPlaceholder' => 'Search ATP, Request No., RIS, supplier...',
            'formId' => 'atpSearchForm',
            'searchId' => 'atpSearch',
            'dateName' => 'submitted',
            'dateValue' => $submittedDate ?? '',
            'dateInputId' => 'atpSubmittedDate',
            'dateBtnId' => 'atpSubmittedDateBtn',
            'dateClearId' => 'atpSubmittedDateClear',
            'dateTitle' => 'Filter by submitted date',
            'exportUrl' => route('school-admin.atp.export-pdf', array_filter([
                'status' => $filter,
                'search' => request('search'),
                'submitted' => $submittedDate ?? '',
                'focus' => request('focus'),
            ])),
        ])

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP Number</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Related RIS</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="atpTableBody" class="divide-y divide-gray-100 bg-white acc-animate">
                    @include('accounting.authority-to-purchase._rows', ['records' => $records, 'filter' => $filter])
                </tbody>
            </table>
        </div>

        <div id="atpPreviewStore" hidden>
            @foreach (($formPreviews ?? []) as $url => $html)
                <template data-preview-store data-url="{{ $url }}">{!! $html !!}</template>
            @endforeach
        </div>

        <div id="atpPagination">
            @if ($records->hasPages())
                <div class="acc-pagination">{{ $records->links('pagination.president') }}</div>
            @endif
        </div>
    </div>
</div>

{{-- ATP form preview (stay on list; Review still opens the full decision page) --}}
<div
    id="atpFormPreviewModal"
    class="sa-form-preview-modal fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="atpFormPreviewTitle"
    data-fullscreen="0"
>
    <div id="atpFormPreviewPanel" class="sa-form-preview-panel my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-5 py-3.5">
            <h2 id="atpFormPreviewTitle" class="truncate text-lg font-semibold text-slate-950">ATP Form</h2>
            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    id="atpFormPreviewFullscreen"
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
                    id="atpFormPreviewPrint"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    title="Print"
                >
                    <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                    Print
                </button>
                <button
                    type="button"
                    id="atpFormPreviewClose"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
        <div id="atpFormPreviewBody" class="sa-form-preview-body max-h-[72vh] flex-1 overflow-auto bg-slate-50"></div>
        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-3.5">
            <button
                type="button"
                id="atpFormPreviewDismiss"
                class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
            >
                Close
            </button>
            <a
                id="atpFormPreviewReview"
                href="#"
                class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Open review
            </a>
            <button
                type="button"
                id="atpFormPreviewApprove"
                class="hidden inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001db3]"
            >
                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                Approve
            </button>
        </div>
    </div>
</div>

<div id="atpListDecisionModal" class="confirm-modal hidden" style="z-index: 12050;">
    <div class="confirm-backdrop" onclick="closeAtpListDecision()"></div>
    <div class="confirm-card wide" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3>Sign to approve</h3>
                <p id="atpListDecisionSubtitle">Authority to Purchase</p>
            </div>
            <button type="button" class="icon-close" onclick="closeAtpListDecision()" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <form id="atpListDecisionForm" method="POST" action="">
            @csrf
            <input type="hidden" name="signature_data" id="signatureData" value="">
            <input type="hidden" name="signature_used" id="signatureUsed" value="0">
            <div class="mt-4">
                @include('accounting.partials.decision-signature-panel', [
                    'savedSignatures' => \App\Support\UserSignatureLibrary::forUser((int) auth()->id()),
                    'signTitle' => 'Accounting signature',
                    'signHint' => 'Pick a saved signature, draw one, or upload. It authorizes this Authority to Purchase.',
                    'padTitle' => 'Sign the ATP',
                    'padHint' => 'Sign clearly. This overlays your printed name on the approval.',
                ])
            </div>
            <div class="mt-4">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" placeholder="Optional for approve."></textarea>
            </div>
            <div class="confirm-actions mt-5">
                <button type="button" class="btn-ghost" onclick="closeAtpListDecision()">Cancel</button>
                <button type="button" class="btn-approve" onclick="submitAtpListDecision()">Approve</button>
            </div>
        </form>
    </div>
</div>

<script>
    let atpListDecideInFlight = false;

    window.openAtpListDecision = function (id, title) {
        const form = document.getElementById('atpListDecisionForm');
        const modal = document.getElementById('atpListDecisionModal');
        if (!form || !modal || !id) return;
        form.action = '/school-admin/authority-to-purchase/' + id + '/approve';
        const subtitle = document.getElementById('atpListDecisionSubtitle');
        if (subtitle) subtitle.textContent = title || 'Authority to Purchase';
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

    window.closeAtpListDecision = function () {
        document.getElementById('atpListDecisionModal')?.classList.add('hidden');
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
    };

    window.submitAtpListDecision = function () {
        if (atpListDecideInFlight) return;
        const form = document.getElementById('atpListDecisionForm');
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
        atpListDecideInFlight = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: new FormData(form)
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to save decision.');
            window.closeAtpListDecision();
            if (typeof window.showMpToast === 'function') {
                showMpToast('ATP approved successfully.', { title: 'Approved', type: 'success' });
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
        .finally(() => { atpListDecideInFlight = false; });
    };

    (function () {
        const tbody = document.getElementById('atpTableBody');
        const pagination = document.getElementById('atpPagination');
        const searchInput = document.getElementById('atpSearch');
        const searchForm = document.getElementById('atpSearchForm');
        const dateInput = document.getElementById('atpSubmittedDate');
        const filterCards = document.querySelectorAll('.status-filter-card');
        const filterTabs = document.querySelectorAll('.status-filter-tab');
        const previewModal = document.getElementById('atpFormPreviewModal');
        const previewTitle = document.getElementById('atpFormPreviewTitle');
        const previewBody = document.getElementById('atpFormPreviewBody');
        const previewReview = document.getElementById('atpFormPreviewReview');
        const previewApprove = document.getElementById('atpFormPreviewApprove');
        let previewAtpId = '';
        let currentFilter = '{{ $filter }}';
        let currentFocus = @json($attentionFocus['key'] ?? null);
        let searchTimeout = null;
        let fetching = false;

        function currentSubmittedDate() {
            return dateInput ? String(dateInput.value || '').trim() : '';
        }

        @include('accounting.partials.form-preview-loader')
        const previewSkeleton = window.saFormPreview.skeleton;

        function setAtpPreviewFullscreen(isFs) {
            if (!previewModal) return;
            previewModal.dataset.fullscreen = isFs ? '1' : '0';
            const btn = document.getElementById('atpFormPreviewFullscreen');
            if (!btn) return;
            const expand = btn.querySelector('[data-fs-icon="expand"]');
            const collapse = btn.querySelector('[data-fs-icon="collapse"]');
            if (expand) expand.classList.toggle('hidden', isFs);
            if (collapse) collapse.classList.toggle('hidden', !isFs);
            btn.title = isFs ? 'Exit full screen' : 'Full screen';
            btn.setAttribute('aria-label', isFs ? 'Exit full screen' : 'Full screen');
        }

        function closeAtpPreview() {
            if (!previewModal) return;
            setAtpPreviewFullscreen(false);
            previewModal.classList.add('hidden');
            previewModal.classList.remove('flex');
            if (previewBody) previewBody.innerHTML = '';
            document.body.style.overflow = '';
        }

        async function openAtpPreview(trigger) {
            if (!previewModal || !previewBody) return;

            const previewUrl = trigger.getAttribute('data-preview-url') || '';
            const reviewUrl = trigger.getAttribute('data-review-url') || '#';
            const title = trigger.getAttribute('data-preview-title') || 'ATP Form';
            const canApprove = trigger.getAttribute('data-can-approve') === '1';
            previewAtpId = trigger.getAttribute('data-atp-id') || '';

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
                previewApprove.classList.toggle('hidden', !canApprove || !previewAtpId);
            }
            setAtpPreviewFullscreen(false);
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
                previewBody.innerHTML = '<p class="px-5 py-10 text-center text-sm text-red-600">Could not load this ATP form.</p>';
            }
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-atp-preview]');
            if (!trigger) return;
            event.preventDefault();
            openAtpPreview(trigger);
        });

        document.getElementById('atpFormPreviewClose')?.addEventListener('click', closeAtpPreview);
        document.getElementById('atpFormPreviewDismiss')?.addEventListener('click', closeAtpPreview);
        document.getElementById('atpFormPreviewFullscreen')?.addEventListener('click', function () {
            setAtpPreviewFullscreen(previewModal.dataset.fullscreen !== '1');
        });
        previewModal?.addEventListener('click', function (event) {
            if (event.target === previewModal && previewModal.dataset.fullscreen !== '1') {
                closeAtpPreview();
            }
        });
        document.getElementById('atpFormPreviewApprove')?.addEventListener('click', function () {
            if (!previewAtpId || typeof window.openAtpListDecision !== 'function') return;
            window.openAtpListDecision(previewAtpId, previewTitle ? previewTitle.textContent : 'Authority to Purchase');
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            const decision = document.getElementById('atpListDecisionModal');
            if (decision && !decision.classList.contains('hidden')) {
                window.closeAtpListDecision();
                return;
            }
            if (!previewModal || previewModal.classList.contains('hidden')) return;
            if (previewModal.dataset.fullscreen === '1') {
                setAtpPreviewFullscreen(false);
                return;
            }
            closeAtpPreview();
        });
        document.getElementById('atpFormPreviewPrint')?.addEventListener('click', function () {
            if (!previewBody) return;
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            printWindow.document.write(
                '<!DOCTYPE html><html><head><title>'
                + (previewTitle ? previewTitle.textContent : 'ATP')
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
            return '/school-admin/authority-to-purchase?' + params.toString();
        }

        function applyAtpPayload(data) {
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
                    applyAtpPayload(JSON.parse(cachedRaw));
                } catch (e) {}
                finish();
                fetch(requestUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                })
                    .then(res => res.text())
                    .then(raw => {
                        window.PrismListCache?.set?.(requestUrl, raw);
                        if (raw !== cachedRaw) applyAtpPayload(JSON.parse(raw));
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
                applyAtpPayload(JSON.parse(raw));
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
