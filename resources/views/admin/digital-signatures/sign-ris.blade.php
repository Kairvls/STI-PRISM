@extends('layouts.admin-layout')

@section('title', 'RIS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')

<div class="admin-page">
    @include('admin.partials.ris-phase-tabs', [
        'phase' => ($filter ?? '') === 'for_cosign' ? 'issued' : 'sign',
        'readyToSignCount' => (int) ($forDecisionCount ?? 0),
        'awaitingIssuedCount' => (int) ($forCosignCount ?? 0),
    ])
    <div id="signRisContentContainer">
        @include('admin.digital-signatures._sign-ris-content')
    </div>


    {{-- ===================================================== --}}
    {{-- RIS PREVIEW MODAL --}}
    {{-- ===================================================== --}}

    @include('admin.partials.ris-preview-modal', [
        'iframeId' => 'signRisPreviewIframe',
        'closeFn' => 'closeSignRisPreviewModal',
        'printFn' => 'printSignRisPreview',
        'zIndex' => '50',
    ])


    @if (\App\Support\AdminPortal::canActOnRis())
        @include('admin.procurement-review._direct-approve-modal')
        @include('admin.digital-signatures._return-revision-modal')
    @endif
    @include('admin.digital-signatures._president-remarks-modal')

</div>

@include('admin.partials.view-mode-script')

{{-- ===================================================== --}}
{{-- SIGN RIS JAVASCRIPT --}}
{{-- (SEARCH, FILTERS, PAGINATION & PREVIEW) --}}
{{-- ===================================================== --}}

<script>


    // =====================================================
    // SIGN RIS AJAX STATE
    // =====================================================

    let signRisSearchTimer = null;
    let signRisFilterFetchTimer = null;
    let signRisFetchSeq = 0;
    let currentFilter = '{{ $filter ?? 'for_decision' }}';
    let currentSearch = '{{ $search ?? '' }}';
    let currentSubmittedDate = @json($submittedDate ?? '');
    let currentUrgency = @json($urgencyFilter ?? '');
    let currentFocus = @json($attentionFocus['key'] ?? '');

    window.applySignRisUrgencyFilter = function (value) {
        currentUrgency = value || '';
        fetchSignRisData(currentFilter, currentSearch, null);
    };

    function syncSignRisDateFilterUi() {
        const btn = document.getElementById('signRisSubmittedDateBtn');
        const input = document.getElementById('signRisSubmittedDate');
        const clear = document.getElementById('signRisSubmittedDateClear');
        const hasDate = !!currentSubmittedDate;

        if (input) {
            input.value = currentSubmittedDate || '';
        }
        if (btn) {
            btn.classList.toggle('border-[#0025cc]', hasDate);
            btn.classList.toggle('bg-blue-50', hasDate);
            btn.classList.toggle('text-[#0025cc]', hasDate);
            btn.classList.toggle('border-gray-200', !hasDate);
            btn.classList.toggle('bg-white', !hasDate);
            btn.classList.toggle('text-gray-600', !hasDate);
            btn.title = hasDate
                ? ('Submitted ' + currentSubmittedDate + ' — click to change')
                : 'Filter by submitted date';
        }
        if (clear) {
            clear.classList.toggle('hidden', !hasDate);
        }
    }

    function snapshotSignRisSearch() {
        const el = document.getElementById('signRisLiveSearch');
        if (!el) {
            return null;
        }

        return {
            focused: document.activeElement === el,
            value: el.value,
            start: el.selectionStart,
            end: el.selectionEnd,
        };
    }

    function restoreSignRisSearch(snap, requestedSearch) {
        const el = document.getElementById('signRisLiveSearch');
        if (!el) {
            return;
        }

        const liveValue = snap ? snap.value : (requestedSearch || currentSearch || '');
        el.value = liveValue;

        if (snap && snap.focused) {
            el.focus();
            try {
                if (typeof snap.start === 'number' && typeof snap.end === 'number') {
                    el.setSelectionRange(snap.start, snap.end);
                }
            } catch (e) {}
        }

        if (snap && snap.value !== (requestedSearch || '')) {
            clearTimeout(signRisSearchTimer);
            signRisSearchTimer = setTimeout(function () {
                currentSearch = snap.value;
                fetchSignRisData(currentFilter, currentSearch, null);
            }, 180);
        }
    }


    // =====================================================
    // FETCH SIGN RIS DATA VIA AJAX
    // =====================================================

    function fetchSignRisData(filter, search, page) {

        const params = new URLSearchParams();
        const requestedSearch = search || '';
        const seq = ++signRisFetchSeq;

        params.set('filter', filter || 'for_decision');

        if (currentFocus) {
            params.set('focus', currentFocus);
        }

        if (requestedSearch) {
            params.set('search', requestedSearch);
        }

        if (currentSubmittedDate) {
            params.set('submitted', currentSubmittedDate);
        }

        if (currentUrgency) {
            params.set('urgency', currentUrgency);
        }

        if (page) {
            params.set('page', page);
        }

        const tableContainer =
            document.getElementById('signRisTableContainer');
        const searchFocused = (function () {
            const el = document.getElementById('signRisLiveSearch');
            return !!(el && document.activeElement === el);
        })();
        const requestUrl = new URL(
            '{{ \App\Support\AdminPortal::route('digital-signatures.sign-ris') }}?' + params.toString(),
            window.location.origin
        ).href;
        const cachedHtml = window.PrismListCache?.get?.(requestUrl) || null;

        if (!cachedHtml && tableContainer && !searchFocused) {
            tableContainer.innerHTML = window.prismSkeleton('table', { rows: 6, label: 'Loading RIS to sign' });
        }

        const applySignRisHtml = function (html) {
            if (seq !== signRisFetchSeq) {
                return;
            }

            const contentContainer =
                document.getElementById('signRisContentContainer');
            const searchSnap = snapshotSignRisSearch();

            if (contentContainer) {
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const partial = parsed.querySelector('#signRisContent')
                    || parsed.querySelector('#signRisContentContainer');
                contentContainer.innerHTML = partial
                    ? (partial.id === 'signRisContent' ? partial.outerHTML : partial.innerHTML)
                    : html;

                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            }

            restoreSignRisSearch(searchSnap, requestedSearch);
            syncSignRisDateFilterUi();
            bindSignRisEventListeners();
            if (typeof window.bindPageCarousels === 'function') {
                window.bindPageCarousels();
            }

            window.history.replaceState(
                { filter: filter, search: requestedSearch, submitted: currentSubmittedDate, page: page },
                '',
                requestUrl
            );
        };

        if (cachedHtml) {
            applySignRisHtml(cachedHtml);
            fetch(requestUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Server responded with ' + response.status);
                    return response.text();
                })
                .then(function (html) {
                    if (seq !== signRisFetchSeq) {
                        return;
                    }
                    window.PrismListCache?.set?.(requestUrl, html);
                    if (html !== cachedHtml) applySignRisHtml(html);
                })
                .catch(function () {});
            return;
        }

        fetch(requestUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Server responded with ' + response.status);
            }
            return response.text();
        })
        .then(function (html) {
            if (seq !== signRisFetchSeq) {
                return;
            }
            window.PrismListCache?.set?.(requestUrl, html);
            applySignRisHtml(html);
        })
        .catch(function (error) {
            if (seq !== signRisFetchSeq) {
                return;
            }
            console.error('Sign RIS fetch error:', error);
            if (tableContainer) {
                tableContainer.innerHTML =
                    '<div class="px-5 py-16 text-center text-sm text-red-600">Failed to load data. Please try again.</div>';
            }

        });

    }


    // =====================================================
    // BIND SIGN RIS EVENT LISTENERS
    // =====================================================

    function updateSignRisFilterSlider(activeFilter) {
        const track = document.getElementById('signRisFilterSlider');
        if (!track) return;

        const buttons = track.querySelectorAll('.sign-ris-filter-btn');
        let activeBtn = null;

        buttons.forEach(function (btn) {
            const isActive = btn.getAttribute('data-filter') === activeFilter;
            btn.classList.toggle('bg-gray-100/80', isActive);
            btn.classList.toggle('font-medium', isActive);
            btn.classList.toggle('text-black', isActive);
            btn.classList.toggle('text-slate-500', !isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.setAttribute('tabindex', isActive ? '0' : '-1');
            if (isActive) activeBtn = btn;
        });

        if (activeBtn && typeof activeBtn.scrollIntoView === 'function') {
            activeBtn.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
        }
    }


    function applySignRisFilter(filter) {
        if (!filter || filter === currentFilter) {
            return;
        }

        currentFocus = '';
        currentFilter = filter;
        updateSignRisFilterSlider(currentFilter);

        clearTimeout(signRisFilterFetchTimer);
        signRisFilterFetchTimer = setTimeout(function () {
            fetchSignRisData(
                currentFilter,
                currentSearch,
                null
            );
        }, 230);
    }


    function applySignRisView() {
        var table = document.getElementById('signRisTableContainer');
        if (table) table.classList.remove('hidden');
    }


    function bindSignRisEventListeners() {

        const searchInput =
            document.getElementById('signRisLiveSearch');

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function () {

                    clearTimeout(signRisSearchTimer);
                    clearTimeout(signRisFilterFetchTimer);

                    const value = this.value;

                    signRisSearchTimer =
                        setTimeout(
                            function () {
                                currentSearch = value;
                                fetchSignRisData(
                                    currentFilter,
                                    currentSearch,
                                    null
                                );
                            },
                            400
                        );

                }
            );

        }

        const dateBtn = document.getElementById('signRisSubmittedDateBtn');
        const dateInput = document.getElementById('signRisSubmittedDate');
        const dateClear = document.getElementById('signRisSubmittedDateClear');

        if (dateBtn && dateInput) {
            dateBtn.addEventListener('click', function () {
                try {
                    if (typeof dateInput.showPicker === 'function') {
                        dateInput.showPicker();
                    } else {
                        dateInput.focus();
                        dateInput.click();
                    }
                } catch (e) {
                    dateInput.focus();
                }
            });
            dateInput.addEventListener('change', function () {
                currentSubmittedDate = this.value || '';
                syncSignRisDateFilterUi();
                fetchSignRisData(currentFilter, currentSearch, null);
            });
        }

        if (dateClear) {
            dateClear.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                currentSubmittedDate = '';
                syncSignRisDateFilterUi();
                fetchSignRisData(currentFilter, currentSearch, null);
            });
        }

        syncSignRisDateFilterUi();

        document.querySelectorAll('.sign-ris-filter-btn, .sign-ris-filter-card').forEach(function (el) {
            el.addEventListener('click', function () {
                applySignRisFilter(this.getAttribute('data-filter'));
            });
        });

        const filterButtons = document.querySelectorAll('.sign-ris-filter-btn');
        filterButtons.forEach(function (btn, index) {
            btn.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                event.preventDefault();
                const next = event.key === 'ArrowRight'
                    ? filterButtons[(index + 1) % filterButtons.length]
                    : filterButtons[(index - 1 + filterButtons.length) % filterButtons.length];
                next.focus();
                applySignRisFilter(next.getAttribute('data-filter'));
            });
        });

        updateSignRisFilterSlider(currentFilter);

        document.querySelectorAll('.admin-sign-view-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                applySignRisView(btn.getAttribute('data-view-mode') || 'table', true);
            });
        });
        // Always reopen in table view when navigating / reloading content.
        applySignRisView('table', false);

        const paginationLinks =
            document.querySelectorAll(
                '.sign-ris-pagination-link'
            );

        paginationLinks.forEach(function (link) {

            link.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();

                    const page =
                        this.getAttribute('data-page');

                    if (!page) {
                        return;
                    }

                    clearTimeout(signRisFilterFetchTimer);
                    fetchSignRisData(
                        currentFilter,
                        currentSearch,
                        page
                    );

                }
            );

        });

    }


    window.__signRisInlinePrintUrl = '';

    window.openSignRisPreviewModal = function (risId) {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('signRisPreviewIframe');
        if (!modal || !iframe) return;

        const printUrl = `{{ \App\Support\AdminPortal::url('procurement-review/ris') }}/${risId}/print`;
        const tpl = document.getElementById('sign-ris-sheet-' + risId);
        const stage = modal.querySelector('.ris-preview-stage');
        const inlineHost = modal.querySelector('[data-ris-preview-inline]');
        const skeleton = modal.querySelector('[data-ris-preview-skeleton]');
        const scaleWrap = modal.querySelector('[data-scale-wrap-for="signRisPreviewIframe"]');
        const panel = modal.querySelector('[data-ris-preview-panel]');
        const numBadge = modal.querySelector('[data-ris-preview-number]');

        if (numBadge) {
            const risNo = tpl && tpl.dataset ? (tpl.dataset.risNumber || '') : '';
            if (risNo) {
                numBadge.textContent = risNo;
                numBadge.classList.remove('hidden');
            } else {
                numBadge.textContent = '';
                numBadge.classList.add('hidden');
            }
        }

        if (window.fillRisPreviewAttachments) {
            window.fillRisPreviewAttachments(risId);
        }

        if (tpl && inlineHost) {
            window.__signRisInlinePrintUrl = printUrl;
            iframe.src = 'about:blank';
            inlineHost.innerHTML = tpl.innerHTML;
            inlineHost.classList.remove('hidden');
            if (skeleton) skeleton.classList.add('hidden');
            if (scaleWrap) scaleWrap.classList.add('hidden');
            if (stage) {
                stage.style.minHeight = '0';
                stage.style.height = 'auto';
                stage.style.width = '100%';
                stage.style.background = '#f3f4f6';
            }
            if (panel) {
                panel.style.width = 'min(1024px, 96vw)';
                panel.style.height = 'auto';
            }
            modal.classList.remove('hidden');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            return;
        }

        window.__signRisInlinePrintUrl = '';
        if (inlineHost) {
            inlineHost.innerHTML = '';
            inlineHost.classList.add('hidden');
        }
        if (scaleWrap) scaleWrap.classList.remove('hidden');
        if (skeleton) skeleton.classList.remove('hidden');

        iframe.src = printUrl;
        modal.classList.remove('hidden');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('signRisPreviewIframe');
            }
        });
        iframe.onload = function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('signRisPreviewIframe');
            }
        };
    };

    window.closeSignRisPreviewModal = function () {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('signRisPreviewIframe');
        const inlineHost = modal ? modal.querySelector('[data-ris-preview-inline]') : null;
        const scaleWrap = modal ? modal.querySelector('[data-scale-wrap-for="signRisPreviewIframe"]') : null;

        window.__signRisInlinePrintUrl = '';
        if (iframe) iframe.src = 'about:blank';
        if (inlineHost) {
            inlineHost.innerHTML = '';
            inlineHost.classList.add('hidden');
        }
        if (scaleWrap) scaleWrap.classList.remove('hidden');

        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = '';
        }

        document.body.style.overflow = '';
    };


    window.scaleSignRisPreviewToFit = function () {
        if (window.__signRisInlinePrintUrl) return;
        if (typeof window.scaleRisPreviewIframe === 'function') {
            window.scaleRisPreviewIframe('signRisPreviewIframe');
        }
    };


    window.printSignRisPreview = function () {
        if (window.__signRisInlinePrintUrl) {
            const sep = window.__signRisInlinePrintUrl.includes('?') ? '&' : '?';
            window.open(window.__signRisInlinePrintUrl + sep + 'auto=1', '_blank', 'noopener');
            return;
        }

        const iframe = document.getElementById('signRisPreviewIframe');
        if (!iframe || !iframe.contentWindow) return;
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };


    window.addEventListener(
        'resize',
        function () {

            const modal =
                document.getElementById('risPreviewModal');

            if (
                modal &&
                !modal.classList.contains('hidden')
            ) {
                window.scaleSignRisPreviewToFit();
            }

            updateSignRisFilterSlider(currentFilter);

        }
    );


    window.risActionMenu = function () {
        return {
            open: false,
            _onScroll: null,
            init() {
                this._onScroll = () => {
                    if (this.open) {
                        this.open = false;
                    }
                };
                window.addEventListener('scroll', this._onScroll, true);
                window.addEventListener('resize', this._onScroll);
            },
            destroy() {
                if (this._onScroll) {
                    window.removeEventListener('scroll', this._onScroll, true);
                    window.removeEventListener('resize', this._onScroll);
                }
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => {
                        this.place();
                        requestAnimationFrame(() => this.place());
                    });
                }
            },
            runAction(fn) {
                this.open = false;
                if (typeof fn === 'function') {
                    fn();
                }
            },
            onOutside(event) {
                if (this.$refs.trigger && this.$refs.trigger.contains(event.target)) {
                    return;
                }
                this.open = false;
            },
            place() {
                const trigger = this.$refs.trigger;
                const menu = this.$refs.menu;
                if (!trigger || !menu) {
                    return;
                }

                menu.style.top = '0px';
                menu.style.left = '0px';

                const rect = trigger.getBoundingClientRect();
                const menuHeight = menu.offsetHeight || 148;
                const menuWidth = menu.offsetWidth || 224;
                const gap = 6;

                const spaceBelow = window.innerHeight - rect.bottom - 8;
                const spaceAbove = rect.top - 8;
                const openUp = spaceBelow < menuHeight && spaceAbove > spaceBelow;

                let top = openUp
                    ? rect.top - menuHeight - gap
                    : rect.bottom + gap;
                let left = rect.right - menuWidth;

                top = Math.min(Math.max(8, top), window.innerHeight - menuHeight - 8);
                left = Math.min(Math.max(8, left), window.innerWidth - menuWidth - 8);

                menu.style.top = top + 'px';
                menu.style.left = left + 'px';
                menu.style.right = 'auto';
                menu.style.bottom = 'auto';
            },
        };
    };


    window.openCoSignModal = function(risId) {
        if (typeof window.openDirectApproveModal === 'function') {
            window.openDirectApproveModal(risId, 'cosign');
            return;
        }
        console.error('Sign Issued by modal is not available on this page.');
    };

    document.addEventListener(
        'keydown',
        function (event) {
            if (event.key === 'Escape') {
                closeSignRisPreviewModal();
                if (typeof window.closeRisCostSummaryModal === 'function') {
                    window.closeRisCostSummaryModal();
                }
                if (typeof window.closeDirectApproveModal === 'function') {
                    window.closeDirectApproveModal();
                }
                if (typeof window.closeReturnRevisionModal === 'function') {
                    window.closeReturnRevisionModal();
                }
            }
        }
    );

    window.openRisCostSummaryModal = function (btn) {
        const modal = document.getElementById('risCostSummaryModal');
        if (!modal || !btn) return;
        let data = null;
        try {
            data = JSON.parse(btn.getAttribute('data-summary') || '{}');
        } catch (e) {
            return;
        }
        const fmtPeso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fmtQty = (q) => Number.isInteger(Number(q)) ? String(Number(q)) : Number(q || 0).toFixed(2).replace(/\.?0+$/, '');
        const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        document.getElementById('risCostSummaryTitle').textContent = data.ris_number || 'Cost Summary';
        const reqBadge = document.getElementById('risCostSummaryReqNo');
        if (data.request_no) {
            reqBadge.textContent = data.request_no;
            reqBadge.classList.remove('hidden');
        } else {
            reqBadge.classList.add('hidden');
        }

        const banner = document.getElementById('risCostSummaryBanner');
        if (data.requires_president) {
            banner.style.background = '#f0f9ff';
            banner.style.borderColor = '#bae6fd';
            banner.style.color = '#0369a1';
            banner.textContent = `Exceeds ${fmtPeso(data.budget_limit)} direct-approval limit — requires President approval.`;
        } else {
            banner.style.background = '#ecfdf5';
            banner.style.borderColor = '#a7f3d0';
            banner.style.color = '#047857';
            banner.textContent = `Within ${fmtPeso(data.budget_limit)} direct-approval limit — eligible for Direct Approval.`;
        }

        const tbody = document.getElementById('risCostSummaryBody');
        const items = Array.isArray(data.items) ? data.items : [];
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No itemized line items available.</td></tr>';
        } else {
            tbody.innerHTML = items.map((it) => {
                const sub = [it.brand, it.supplier].filter(Boolean).join(' · ');
                return `
                    <tr>
                        <td class="px-4 py-2.5 align-top">
                            <div class="font-medium text-slate-900">${esc(it.name)}</div>
                            ${sub ? `<div class="mt-0.5 text-[11px] text-slate-500">${esc(sub)}</div>` : ''}
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-right align-top tabular-nums text-slate-700">${fmtQty(it.qty)} ${esc(it.unit)}</td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-right align-top tabular-nums text-slate-700">${fmtPeso(it.unit_cost)}</td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-right align-top font-semibold tabular-nums text-slate-900">${fmtPeso(it.line_total)}</td>
                    </tr>
                `;
            }).join('');
        }

        document.getElementById('risCostSummaryGrandTotal').textContent = fmtPeso(data.total);
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    };

    window.closeRisCostSummaryModal = function () {
        const modal = document.getElementById('risCostSummaryModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.style.display = 'none';
    };

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            bindSignRisEventListeners();
        }
    );


</script>

{{-- COST SUMMARY & COMPUTATION MODAL --}}
<div
    id="risCostSummaryModal"
    onclick="if (event.target === this) window.closeRisCostSummaryModal()"
    class="fixed inset-0 hidden items-center justify-center bg-black/50 p-4"
    style="z-index: 99998;"
>
    <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-base font-bold text-slate-900">Cost Summary</h3>
                    <span id="risCostSummaryTitle" class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"></span>
                    <span id="risCostSummaryReqNo" class="hidden rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700"></span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">Itemized computation and budget limit check.</p>
            </div>
            <button
                type="button"
                onclick="window.closeRisCostSummaryModal()"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                aria-label="Close cost summary"
            >✕</button>
        </div>

        <div class="space-y-4 p-5">
            <div id="risCostSummaryBanner" class="rounded-xl border px-3.5 py-2.5 text-xs font-semibold"></div>

            <div class="max-h-[50vh] overflow-y-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Item</th>
                            <th class="px-3 py-2.5 text-right">Qty</th>
                            <th class="px-3 py-2.5 text-right">Unit Cost</th>
                            <th class="px-4 py-2.5 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="risCostSummaryBody" class="divide-y divide-slate-100 bg-white"></tbody>
                    <tfoot class="border-t border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-700">Total Amount</td>
                            <td id="risCostSummaryGrandTotal" class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold tabular-nums text-slate-900">₱0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50 px-5 py-3">
            <button
                type="button"
                onclick="window.closeRisCostSummaryModal()"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
            >Close</button>
        </div>
    </div>
</div>

@endsection
