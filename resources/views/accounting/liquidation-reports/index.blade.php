@extends('layouts.accounting-layout')

@section('title', 'Liquidation Reports')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
<style>
    .acc-liq-page,
    .acc-liq-page * {
        font-family: "Inter", sans-serif;
    }
    .acc-liq-page .pur-card .acc-pagination {
        padding: 0.75rem 1.25rem 1rem;
        border-top: 1px solid #f1f5f9;
    }
    .acc-liq-page #liqTableBody.is-loading {
        opacity: .45;
        filter: saturate(.6);
        pointer-events: none;
        transition: opacity .18s ease, filter .18s ease;
    }
    .acc-liq-page #liqTableBody.acc-animate tr {
        animation: accFadeSwap .35s ease both;
    }
    .acc-liq-page #liqTableBody.acc-animate tr:nth-child(1)  { animation-delay: .02s; }
    .acc-liq-page #liqTableBody.acc-animate tr:nth-child(2)  { animation-delay: .05s; }
    .acc-liq-page #liqTableBody.acc-animate tr:nth-child(3)  { animation-delay: .08s; }
    .acc-liq-page #liqTableBody.acc-animate tr:nth-child(4)  { animation-delay: .11s; }
    .acc-liq-page #liqTableBody.acc-animate tr:nth-child(5)  { animation-delay: .14s; }
    .acc-liq-page #liqTableBody.acc-animate tr:nth-child(6)  { animation-delay: .17s; }
    @media (prefers-reduced-motion: reduce) {
        .acc-liq-page #liqTableBody.acc-animate tr { animation: none; }
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
    // Overdue is a subset of Needs review, so no tab total matches it.
    $activeFilter = ($attentionFocus['key'] ?? null) === 'overdue' ? null : $filter;
@endphp

<div class="acc-page acc-liq-page acc-content-fill space-y-6 fade-in">

    @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $records->total()])

    @if (!empty($deadlineFilter))
        @php
            $deadlineBanner = [
                'overdue' => ['label' => 'Overdue', 'hint' => 'Past submission deadline', 'tone' => 'rose'],
                'due_today' => ['label' => 'Due today', 'hint' => 'Deadline is today', 'tone' => 'amber'],
                'this_week' => ['label' => 'Due this week', 'hint' => 'Deadline within 7 days', 'tone' => 'blue'],
            ][$deadlineFilter] ?? null;
        @endphp
        @if ($deadlineBanner)
            <div class="acc-deadline-banner is-{{ $deadlineBanner['tone'] }}" id="liqDeadlineBanner">
                <div class="min-w-0">
                    <p class="acc-deadline-banner-title">{{ $deadlineBanner['label'] }}</p>
                    <p class="acc-deadline-banner-hint">{{ $deadlineBanner['hint'] }} · showing matching liquidations only</p>
                </div>
                <a
                    href="/accounting/liquidation-reports?status=incoming{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                    class="acc-btn acc-btn-ghost acc-deadline-banner-clear"
                >Clear filter</a>
            </div>
        @endif
    @endif

    <div class="pur-card">
        @include('accounting.partials.status-filter-bar', [
            'filters' => $filters,
            'activeFilter' => $activeFilter,
            'baseUrl' => '/accounting/liquidation-reports',
            'searchPlaceholder' => 'Search liquidation, Request No., or employee...',
            'formId' => 'liqSearchForm',
            'searchId' => 'liqSearch',
            'extraHidden' => !empty($deadlineFilter) ? ['deadline' => $deadlineFilter] : [],
        ])

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">LR No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RR</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Employee</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody id="liqTableBody" class="divide-y divide-gray-100 bg-white acc-animate">
                    @include('accounting.liquidation-reports._rows', ['records' => $records, 'filter' => $filter, 'deadlineFilter' => $deadlineFilter ?? null])
                </tbody>
            </table>
        </div>

        <div id="liqPagination">
            @if ($records->hasPages())
                <div class="acc-pagination">{{ $records->links('pagination.president') }}</div>
            @endif
        </div>
    </div>
</div>

{{-- VERIFY LIQUIDATION MODAL --}}
<div id="accLiqVerifyModal" class="fixed inset-0 hidden overflow-y-auto bg-black/50 p-3 sm:p-6" style="z-index: 1100;" role="dialog" aria-modal="true" aria-labelledby="accLiqVerifyTitle">
    <div class="flex min-h-full items-start justify-center" onclick="if (event.target === this) window.requestCloseLiqVerifyModal()">
        <div class="my-auto w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 id="accLiqVerifyTitle" class="text-base font-bold text-slate-900">Verify Liquidation Report</h3>
                    <p id="accLiqVerifySub" class="text-xs text-slate-500">Review the liquidation report and sign below to complete the transaction.</p>
                </div>
                <button type="button" onclick="window.requestCloseLiqVerifyModal()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="max-h-[75vh] overflow-y-auto bg-slate-100 p-4 sm:p-6">
                <div id="accLiqVerifyPaper" class="mx-auto w-full max-w-[1095px]">
                    <div class="rounded-xl bg-white p-10 text-center text-sm text-slate-500">Loading liquidation report...</div>
                </div>

                <div id="accLiqVerifyAttachments" class="mx-auto mt-4 hidden w-full max-w-[1095px] rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs">
                    <p class="mb-1.5 font-semibold text-slate-800">Supporting Documents</p>
                    <div id="accLiqVerifyAttachmentsList" class="flex flex-wrap gap-2"></div>
                </div>

                {{-- Accounting Signature --}}
                <div class="mx-auto mt-4 w-full max-w-[1095px]">
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
            </div>

            <div class="flex items-center justify-end gap-2.5 border-t border-slate-200 bg-white px-6 py-4">
                <button type="button" onclick="window.requestCloseLiqVerifyModal()" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="button" onclick="window.openLiqVerifyConfirm()" class="inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                    <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                    Verify
                </button>
            </div>
        </div>
    </div>
</div>

{{-- VERIFY CONFIRMATION MODAL --}}
<div id="accLiqVerifyConfirmModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 99999;" role="dialog" aria-modal="true" aria-labelledby="accLiqConfirmTitle">
    <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-[1px]" onclick="window.closeLiqVerifyConfirm()"></div>
    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" style="background:#ecfdf5;border-color:#a7f3d0;color:#047857;">
                <i data-lucide="shield-check" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h4 id="accLiqConfirmTitle" class="text-base font-bold text-slate-900">Verify &amp; Complete Transaction?</h4>
                <p id="accLiqConfirmDesc" class="mt-1 text-sm text-slate-600">
                    Verifying this Liquidation Report will mark it as <strong>Approved</strong> and complete the Cash Advance transaction.
                </p>
            </div>
        </div>
        <div class="mt-4 space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Liquidation No.</span>
                <span id="accLiqConfirmNo" class="font-semibold text-slate-900">—</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Receiving Report</span>
                <span id="accLiqConfirmRr" class="font-medium text-slate-800">—</span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Employee</span>
                <span id="accLiqConfirmEmp" class="font-medium text-slate-800">—</span>
            </div>
        </div>
        <div class="mt-5 flex items-center justify-end gap-2">
            <button type="button" onclick="window.closeLiqVerifyConfirm()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </button>
            <button type="button" id="accLiqConfirmSubmitBtn" onclick="window.submitLiqVerify()" class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90" style="background:#059669;">
                <i data-lucide="check" class="h-4 w-4"></i>
                <span>Confirm &amp; Complete</span>
            </button>
        </div>
    </div>
</div>

{{-- CLOSE VERIFY MODAL CONFIRMATION --}}
<div id="accLiqCloseConfirmModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 99999;" role="dialog" aria-modal="true" aria-labelledby="accLiqCloseConfirmTitle">
    <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-[1px]" onclick="window.cancelCloseLiqVerifyModal()"></div>
    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" style="background:#fff7ed;border-color:#fed7aa;color:#c2410c;">
                <i data-lucide="alert-triangle" class="h-5 w-5"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h4 id="accLiqCloseConfirmTitle" class="text-base font-bold text-slate-900">Close Verification?</h4>
                <p class="mt-1 text-sm text-slate-600">
                    Are you sure you want to close without verifying this Liquidation Report? Any unsaved changes will be discarded.
                </p>
            </div>
        </div>
        <div class="mt-5 flex items-center justify-end gap-2">
            <button type="button" onclick="window.cancelCloseLiqVerifyModal()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Keep reviewing
            </button>
            <button
                type="button"
                onclick="window.closeLiqVerifyModal()"
                class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition"
                style="background:#dc2626;color:#ffffff;"
                onmouseenter="this.style.background='#b91c1c'"
                onmouseleave="this.style.background='#dc2626'"
            >
                Close anyway
            </button>
        </div>
    </div>
</div>

{{-- RETURN FOR REVISION MODAL --}}
<div id="accLiqReviseModal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 99999;" role="dialog" aria-modal="true" aria-labelledby="accLiqReviseTitle">
    <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-[1px]" onclick="window.closeLiqReviseModal()"></div>
    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" style="background:#fff7ed;border-color:#fed7aa;color:#c2410c;">
                    <i data-lucide="undo-2" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 id="accLiqReviseTitle" class="text-base font-bold text-slate-900">Return for Revision</h3>
                    <p id="accLiqReviseSub" class="mt-0.5 text-xs text-slate-500">—</p>
                </div>
            </div>
            <button type="button" onclick="window.closeLiqReviseModal()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <form id="accLiqReviseForm" method="POST" action="" onsubmit="event.preventDefault(); window.submitLiqRevise();">
            @csrf
            <input type="hidden" name="decision" value="Rejected" />
            <div class="space-y-4 px-6 py-5">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Remarks <span class="text-red-500">*</span></label>
                    <textarea
                        id="accLiqReviseRemarks"
                        name="remarks"
                        rows="3"
                        placeholder="Describe what needs to be revised by the Purchaser..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                    ></textarea>
                </div>
                <div>
                    @include('admin.partials.revision-image-picker', ['pickerId' => 'indexLiqRevisionImages'])
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                <button type="button" onclick="window.closeLiqReviseModal()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                <button
                    type="submit"
                    id="accLiqReviseSubmitBtn"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition"
                    style="background:#d97706;color:#ffffff;"
                    onmouseenter="this.style.background='#b45309'"
                    onmouseleave="this.style.background='#d97706'"
                >
                    <i data-lucide="send" class="h-4 w-4"></i>
                    <span>Send to Purchaser</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const tbody = document.getElementById('liqTableBody');
        const pagination = document.getElementById('liqPagination');
        const searchInput = document.getElementById('liqSearch');
        const searchForm = document.getElementById('liqSearchForm');
        const filterCards = document.querySelectorAll('.status-filter-card');
        const filterTabs = document.querySelectorAll('.status-filter-tab');
        let currentFilter = '{{ $filter }}';
        let currentDeadline = @json($deadlineFilter ?? null);
        let currentFocus = @json($attentionFocus['key'] ?? null);
        let searchTimeout = null;
        let fetching = false;

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

        function buildUrl(page, filter, search, deadline) {
            const params = new URLSearchParams();
            params.set('status', filter);
            if (currentFocus) params.set('focus', currentFocus);
            if (search) params.set('search', search);
            if (deadline) params.set('deadline', deadline);
            if (page && page > 1) params.set('page', page);
            return '/accounting/liquidation-reports?' + params.toString();
        }

        function fetchData(page, filter, autoSwitchIfEmpty) {
            if (fetching) return;
            fetching = true;
            page = page || 1;
            filter = filter || currentFilter;
            const search = searchInput ? searchInput.value.trim() : '';
            const deadline = currentDeadline;

            if (tbody) tbody.classList.add('is-loading');

            fetch(buildUrl(page, filter, search, deadline), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (autoSwitchIfEmpty && filter === 'incoming' && Number(data.counts?.incoming ?? 0) === 0 && Number(data.counts?.all ?? 0) > 0) {
                    fetching = false;
                    currentFilter = 'all';
                    updateFilterButtons('all');
                    pushUrl(1, 'all', search);
                    fetchData(1, 'all', false);
                    return;
                }
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
                currentDeadline = data.deadline_filter || null;
                const banner = document.getElementById('liqDeadlineBanner');
                if (banner && !currentDeadline) banner.remove();
                if (searchForm) {
                    let deadlineInput = searchForm.querySelector('input[name="deadline"]');
                    if (currentDeadline) {
                        if (!deadlineInput) {
                            deadlineInput = document.createElement('input');
                            deadlineInput.type = 'hidden';
                            deadlineInput.name = 'deadline';
                            searchForm.appendChild(deadlineInput);
                        }
                        deadlineInput.value = currentDeadline;
                    } else if (deadlineInput) {
                        deadlineInput.remove();
                    }
                }
                if (window.lucide) lucide.createIcons();
                fetching = false;
            })
            .catch(err => {
                console.error(err);
                if (tbody) tbody.classList.remove('is-loading');
                fetching = false;
            });
        }

        function pushUrl(page, filter, search) {
            window.history.replaceState({}, '', buildUrl(page, filter, search, currentDeadline));
        }

        function applyFilter(newFilter) {
            if (newFilter === currentFilter && !currentDeadline && !currentFocus) return;
            currentFilter = newFilter;
            currentDeadline = null;
            currentFocus = null;
            syncFocusChip();
            updateFilterButtons(newFilter);
            fetchData(1, newFilter);
            pushUrl(1, newFilter, searchInput ? searchInput.value.trim() : '');
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
                fetchData(1, currentFilter, true);
                pushUrl(1, currentFilter, searchInput ? searchInput.value.trim() : '');
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchData(1, currentFilter, true);
                    pushUrl(1, currentFilter, searchInput.value.trim());
                }, 300);
            });
        }

        if (pagination) {
            pagination.addEventListener('click', function (e) {
                const link = e.target.closest('a[href]');
                if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') return;
                e.preventDefault();
                const url = new URL(link.href, window.location.origin);
                const page = parseInt(url.searchParams.get('page') || '1', 10);
                fetchData(page, currentFilter);
                pushUrl(page, currentFilter, searchInput ? searchInput.value.trim() : '');
            });
        }

        window.addEventListener('popstate', function () {
            const params = new URLSearchParams(window.location.search);
            const filter = params.get('status') || 'incoming';
            const search = params.get('search') || '';
            currentFilter = filter;
            currentDeadline = params.get('deadline') || null;
            currentFocus = params.get('focus') || null;
            if (searchInput) searchInput.value = search;
            updateFilterButtons(filter);
            fetchData(1, filter);
        });

        // ─── Verify Liquidation Modal ───────────────────────────────
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const verifyModal = document.getElementById('accLiqVerifyModal');
        const confirmModal = document.getElementById('accLiqVerifyConfirmModal');
        let activeVerifyLiq = null;
        let verifyInFlight = false;
        let verifyModalDirty = false;
        let verifyModalInitializing = false;

        if (verifyModal && verifyModal.parentElement !== document.body) {
            document.body.appendChild(verifyModal);
        }
        if (verifyModal) {
            const markVerifyDirty = function (e) {
                if (verifyModalInitializing || (e && e.isTrusted === false)) return;
                verifyModalDirty = true;
            };
            verifyModal.addEventListener('input', markVerifyDirty, true);
            verifyModal.addEventListener('change', markVerifyDirty, true);
            verifyModal.addEventListener('pointerup', function (e) {
                if (e.target && e.target.tagName === 'CANVAS') markVerifyDirty(e);
            }, true);
            verifyModal.addEventListener('click', function (e) {
                if (e.target && e.target.closest && e.target.closest('.acc-use-saved, #accSigClearBtn, #accSigUploadBtn')) {
                    markVerifyDirty(e);
                }
            }, true);
        }
        if (confirmModal && confirmModal.parentElement !== document.body) {
            document.body.appendChild(confirmModal);
        }

        function setSigExpanded(expanded) {
            const body = document.getElementById('accLiqSigBody');
            const chevron = document.getElementById('accLiqSigChevron');
            const label = document.getElementById('accLiqSigToggleLabel');
            const toggle = document.getElementById('accLiqSigToggle');
            if (body) body.classList.toggle('hidden', !expanded);
            if (chevron) chevron.classList.toggle('rotate-90', expanded);
            if (label) label.textContent = expanded ? 'Hide signature' : 'Show signature';
            if (toggle) toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        function syncSigReadyPill() {
            const pill = document.getElementById('accLiqSigReadyPill');
            if (!pill) return;
            const hasSig = window.accountingSignaturePanel
                ? window.accountingSignaturePanel.hasSignature()
                : String(document.getElementById('signatureData')?.value || '').indexOf('data:image/') === 0;
            pill.classList.toggle('hidden', !hasSig);
        }

        window.toggleLiqSigSection = function () {
            const body = document.getElementById('accLiqSigBody');
            const willOpen = body ? body.classList.contains('hidden') : true;
            setSigExpanded(willOpen);
            syncSigReadyPill();
        };

        function renderVerifyAttachments(attWrap, attList, attachments) {
            if (!attWrap || !attList) return;
            if (Array.isArray(attachments) && attachments.length > 0) {
                attList.innerHTML = attachments.map(f =>
                    '<a href="' + f.url + '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 font-medium text-[#0025cc] hover:bg-slate-100">' +
                    '<i data-lucide="file-text" class="h-3.5 w-3.5"></i><span>' + String(f.name || 'Document').replace(/</g, '&lt;') + '</span></a>'
                ).join('');
                attWrap.classList.remove('hidden');
            } else {
                attList.innerHTML = '';
                attWrap.classList.add('hidden');
            }
        }

        function initVerifySignature() {
            verifyModalInitializing = true;
            verifyModalDirty = false;
            if (window.accountingSignaturePanel && typeof window.accountingSignaturePanel.reset === 'function') {
                window.accountingSignaturePanel.reset();
            }
            const sigInput = document.getElementById('signatureData');
            if (sigInput && String(sigInput.value || '').indexOf('data:image/') !== 0) {
                const firstSaved = document.querySelector('#accSavedList .acc-use-saved');
                if (firstSaved) firstSaved.click();
            }
            setTimeout(function () {
                syncSigReadyPill();
                verifyModalInitializing = false;
                verifyModalDirty = false;
            }, 80);
        }

        window.openLiqVerifyModal = function (id) {
            if (!verifyModal) return;
            const paper = document.getElementById('accLiqVerifyPaper');
            const attWrap = document.getElementById('accLiqVerifyAttachments');
            const attList = document.getElementById('accLiqVerifyAttachmentsList');
            const tpl = document.getElementById('acc-liq-paper-' + id);

            setSigExpanded(false);

            if (tpl) {
                const formNo = tpl.getAttribute('data-form-number') || ('LIQ-' + id);
                const rrNo = tpl.getAttribute('data-rr-number') || '—';
                const emp = tpl.getAttribute('data-employee') || '—';
                let atts = [];
                try { atts = JSON.parse(tpl.getAttribute('data-attachments') || '[]'); } catch (_) {}

                activeVerifyLiq = { id: id, form_number: formNo, rr_number: rrNo, employee: emp };
                document.getElementById('accLiqVerifyTitle').textContent = 'Verify ' + formNo;
                document.getElementById('accLiqVerifySub').textContent = 'RR: ' + rrNo + ' · Employee: ' + emp;
                if (paper) paper.innerHTML = tpl.innerHTML;
                renderVerifyAttachments(attWrap, attList, atts);

                verifyModal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                initVerifySignature();
                if (window.lucide) lucide.createIcons();
                return;
            }

            activeVerifyLiq = { id: id, form_number: 'LIQ-' + id, employee: '—', rr_number: '—' };
            if (paper) paper.innerHTML = '';
            renderVerifyAttachments(attWrap, attList, []);
            document.getElementById('accLiqVerifyTitle').textContent = 'Verify Liquidation Report';
            document.getElementById('accLiqVerifySub').textContent = 'Review the liquidation report and sign below to complete the transaction.';

            verifyModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            fetch('/accounting/liquidation-reports/' + id + '/paper', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => {
                if (!r.ok) throw new Error('Failed to load liquidation report');
                return r.json();
            })
            .then(data => {
                activeVerifyLiq = data;
                document.getElementById('accLiqVerifyTitle').textContent = 'Verify ' + (data.form_number || ('LIQ-' + id));
                document.getElementById('accLiqVerifySub').textContent = 'RR: ' + (data.rr_number || '—') + ' · Employee: ' + (data.employee || '—');
                if (paper) paper.innerHTML = data.paper_html || '';
                renderVerifyAttachments(attWrap, attList, data.attachments);
                initVerifySignature();
                if (window.lucide) lucide.createIcons();
            })
            .catch(err => {
                console.error(err);
                if (paper) paper.innerHTML = '<div class="rounded-xl bg-white p-10 text-center text-sm text-rose-600">Could not load liquidation report.</div>';
            });
        };

        const closeConfirmModal = document.getElementById('accLiqCloseConfirmModal');
        if (closeConfirmModal && closeConfirmModal.parentElement !== document.body) {
            document.body.appendChild(closeConfirmModal);
        }

        window.requestCloseLiqVerifyModal = function () {
            if (!verifyModalDirty || !closeConfirmModal) {
                window.closeLiqVerifyModal();
                return;
            }
            closeConfirmModal.classList.remove('hidden');
            closeConfirmModal.classList.add('flex');
            if (window.lucide) lucide.createIcons();
        };

        window.cancelCloseLiqVerifyModal = function () {
            if (!closeConfirmModal) return;
            closeConfirmModal.classList.add('hidden');
            closeConfirmModal.classList.remove('flex');
        };

        window.closeLiqVerifyModal = function () {
            verifyModalDirty = false;
            verifyModalInitializing = false;
            if (closeConfirmModal) {
                closeConfirmModal.classList.add('hidden');
                closeConfirmModal.classList.remove('flex');
            }
            if (confirmModal) {
                confirmModal.classList.add('hidden');
                confirmModal.classList.remove('flex');
            }
            if (verifyModal) verifyModal.classList.add('hidden');
            document.body.style.overflow = '';
            activeVerifyLiq = null;
        };

        window.openLiqVerifyConfirm = function () {
            if (!activeVerifyLiq) return;
            syncSigReadyPill();
            const hasSig = window.accountingSignaturePanel
                ? window.accountingSignaturePanel.hasSignature()
                : String(document.getElementById('signatureData')?.value || '').indexOf('data:image/') === 0;
            if (!hasSig) {
                setSigExpanded(true);
                if (typeof window.showMpToast === 'function') {
                    window.showMpToast('Please add your signature before verifying.', { title: 'Signature required', type: 'warning', timer: 3500 });
                } else {
                    alert('Please add your signature before verifying.');
                }
                return;
            }
            document.getElementById('accLiqConfirmNo').textContent = activeVerifyLiq.form_number || ('LIQ-' + activeVerifyLiq.id);
            document.getElementById('accLiqConfirmRr').textContent = activeVerifyLiq.rr_number || '—';
            document.getElementById('accLiqConfirmEmp').textContent = activeVerifyLiq.employee || '—';
            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
            if (window.lucide) lucide.createIcons();
        };

        window.closeLiqVerifyConfirm = function () {
            if (!confirmModal) return;
            confirmModal.classList.add('hidden');
            confirmModal.classList.remove('flex');
        };

        window.submitLiqVerify = function () {
            if (verifyInFlight || !activeVerifyLiq) return;
            verifyInFlight = true;
            const btn = document.getElementById('accLiqConfirmSubmitBtn');
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

            fetch('/accounting/liquidation-reports/' + activeVerifyLiq.id + '/approve', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.ok === false) {
                    throw new Error(data.message || data.error || 'Could not verify liquidation report.');
                }
                window.closeLiqVerifyModal();
                if (typeof window.showMpToast === 'function') {
                    window.showMpToast(data.message || 'Liquidation verified. Transaction completed.', { title: 'Verified', type: 'success', timer: 4000 });
                }
                fetchData(1, currentFilter, true);
            })
            .catch(err => {
                window.closeLiqVerifyConfirm();
                if (typeof window.showMpToast === 'function') {
                    window.showMpToast(err.message || 'Verification failed.', { title: 'Error', type: 'error', timer: 4500 });
                } else {
                    alert(err.message || 'Verification failed.');
                }
            })
            .finally(() => {
                verifyInFlight = false;
                if (btn) btn.disabled = false;
            });
        };

        const reviseModal = document.getElementById('accLiqReviseModal');
        const reviseForm = document.getElementById('accLiqReviseForm');
        const reviseSub = document.getElementById('accLiqReviseSub');
        const reviseRemarks = document.getElementById('accLiqReviseRemarks');
        const reviseSubmitBtn = document.getElementById('accLiqReviseSubmitBtn');
        let activeReviseLiqId = null;
        let reviseInFlight = false;

        if (reviseModal && reviseModal.parentElement !== document.body) {
            document.body.appendChild(reviseModal);
        }

        window.openLiqReviseModal = function (liqId, formNo, employeeName) {
            if (!reviseModal || !reviseForm) return;
            activeReviseLiqId = liqId;
            reviseForm.action = '/accounting/liquidation-reports/' + liqId + '/revise';
            if (reviseSub) {
                reviseSub.textContent = (formNo || ('LIQ-' + liqId)) + ' · Returns to ' + (employeeName || 'Purchaser');
            }
            if (reviseRemarks) reviseRemarks.value = '';
            if (typeof window.resetRevisionImagePicker === 'function') {
                window.resetRevisionImagePicker('indexLiqRevisionImages');
            }
            reviseModal.classList.remove('hidden');
            reviseModal.classList.add('flex');
            if (window.lucide) lucide.createIcons();
            setTimeout(() => reviseRemarks?.focus(), 40);
        };

        window.closeLiqReviseModal = function () {
            if (!reviseModal) return;
            reviseModal.classList.add('hidden');
            reviseModal.classList.remove('flex');
        };

        window.submitLiqRevise = function () {
            if (reviseInFlight || !activeReviseLiqId || !reviseForm) return;
            const remarks = (reviseRemarks?.value || '').trim();
            if (!remarks) {
                if (typeof window.showMpToast === 'function') {
                    window.showMpToast('Please provide a revision reason.', { title: 'Remarks required', type: 'warning', timer: 3600 });
                } else {
                    alert('Please provide a revision reason.');
                }
                reviseRemarks?.focus();
                return;
            }
            reviseInFlight = true;
            if (reviseSubmitBtn) reviseSubmitBtn.disabled = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const fd = new FormData(reviseForm);
            fd.set('_token', csrfToken);
            fd.set('decision', 'Rejected');
            fd.set('remarks', remarks);

            fetch(reviseForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: fd
            })
            .then(async res => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.ok === false) {
                    throw new Error(data.message || data.error || 'Could not return liquidation for revision.');
                }
                window.closeLiqReviseModal();
                if (typeof window.showMpToast === 'function') {
                    window.showMpToast(data.message || 'Liquidation returned to Purchaser for revision.', { title: 'Returned for Revision', type: 'success', timer: 4000 });
                }
                fetchData(1, currentFilter, true);
            })
            .catch(err => {
                if (typeof window.showMpToast === 'function') {
                    window.showMpToast(err.message || 'Could not return liquidation for revision.', { title: 'Error', type: 'error', timer: 4500 });
                } else {
                    alert(err.message || 'Could not return liquidation for revision.');
                }
            })
            .finally(() => {
                reviseInFlight = false;
                if (reviseSubmitBtn) reviseSubmitBtn.disabled = false;
            });
        };

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (closeConfirmModal && !closeConfirmModal.classList.contains('hidden')) {
                window.cancelCloseLiqVerifyModal();
            } else if (reviseModal && !reviseModal.classList.contains('hidden')) {
                window.closeLiqReviseModal();
            } else if (confirmModal && !confirmModal.classList.contains('hidden')) {
                window.closeLiqVerifyConfirm();
            } else if (verifyModal && !verifyModal.classList.contains('hidden')) {
                window.requestCloseLiqVerifyModal();
            }
        });
    })();
</script>
@endsection
