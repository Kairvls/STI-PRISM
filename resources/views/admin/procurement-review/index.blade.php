@extends('layouts.admin-layout')

@section('title', 'RIS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

{{-- ===================================================== --}}
{{-- RIS — PENDING ACCEPT --}}
{{-- ===================================================== --}}

@section('content')

<div class="admin-page">
    @include('admin.partials.ris-phase-tabs', [
        'phase' => 'accept',
        'pendingAcceptCount' => (int) ($pendingRis ?? 0),
    ])
    <div id="risContentContainer">
        @include('admin.procurement-review._content')
    </div>

    @include('admin.partials.ris-preview-modal', ['zIndex' => '11000'])
    @if (\App\Support\AdminPortal::canActOnRis())
        @include('admin.procurement-review._direct-approve-modal')
        @include('admin.procurement-review._accept-modal')
    @endif
</div>

@include('admin.partials.view-mode-script')

{{-- ===================================================== --}}
{{-- RIS PAGE JAVASCRIPT --}}
{{-- (SEARCH, FILTERS, PAGINATION & PREVIEW) --}}
{{-- ===================================================== --}}

<script>


    // =====================================================
    // RIS AJAX STATE
    // =====================================================

    let risSearchTimer = null;
    let risFilterFetchTimer = null;
    let risFetchSeq = 0;
    let currentFilter = '{{ $filter ?? 'pending' }}';
    let currentSearch = '{{ $search ?? '' }}';
    let currentSubmittedDate = @json($submittedDate ?? '');
    let currentFocus = @json($attentionFocus['key'] ?? '');

    function syncRisDateFilterUi() {
        const btn = document.getElementById('risSubmittedDateBtn');
        const input = document.getElementById('risSubmittedDate');
        const clear = document.getElementById('risSubmittedDateClear');
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

    function snapshotRisSearch() {
        const el = document.getElementById('risLiveSearch');
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

    function restoreRisSearch(snap, requestedSearch) {
        const el = document.getElementById('risLiveSearch');
        if (!el) {
            return;
        }

        // Keep whatever the user was typing — never clobber with a stale server value.
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
            clearTimeout(risSearchTimer);
            risSearchTimer = setTimeout(function () {
                currentSearch = snap.value;
                fetchRisData(currentFilter, currentSearch, null);
            }, 180);
        }
    }


    // =====================================================
    // FETCH RIS DATA VIA AJAX
    // =====================================================

    function fetchRisData(filter, search, page) {

        // Build query parameters.
        const params = new URLSearchParams();
        const requestedSearch = search || '';
        const seq = ++risFetchSeq;

        if (currentFocus) {
            params.set('focus', currentFocus);
        } else {
            params.set('filter', filter || 'pending');
        }

        if (requestedSearch) {
            params.set('search', requestedSearch);
        }

        if (currentSubmittedDate) {
            params.set('submitted', currentSubmittedDate);
        }

        if (page) {
            params.set('page', page);
        }

        const tableContainer =
            document.getElementById('risTableContainer');
        const searchFocused = (function () {
            const el = document.getElementById('risLiveSearch');
            return !!(el && document.activeElement === el);
        })();
        const requestUrl = new URL(
            '{{ \App\Support\AdminPortal::route('procurement-review.ris') }}?' + params.toString(),
            window.location.origin
        ).href;
        const cachedHtml = window.PrismListCache?.get?.(requestUrl) || null;

        // Don't wipe the toolbar/search while typing — only skeleton the table body.
        if (!cachedHtml && tableContainer && !searchFocused) {
            tableContainer.innerHTML = window.prismSkeleton('table', { rows: 6, label: 'Loading procurement requests' });
        }

        const applyRisHtml = function (html) {
            if (seq !== risFetchSeq) {
                return;
            }

            const contentContainer =
                document.getElementById('risContentContainer');
            const searchSnap = snapshotRisSearch();

            if (contentContainer) {
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const partial = parsed.querySelector('#risContent')
                    || parsed.querySelector('#risContentContainer');
                contentContainer.innerHTML = partial
                    ? (partial.id === 'risContent' ? partial.outerHTML : partial.innerHTML)
                    : html;

                if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                    window.Alpine.initTree(contentContainer);
                }
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            }

            restoreRisSearch(searchSnap, requestedSearch);
            syncRisDateFilterUi();
            bindRisEventListeners();
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
            applyRisHtml(cachedHtml);
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
                    if (seq !== risFetchSeq) {
                        return;
                    }
                    window.PrismListCache?.set?.(requestUrl, html);
                    if (html !== cachedHtml) applyRisHtml(html);
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
            if (seq !== risFetchSeq) {
                return;
            }
            window.PrismListCache?.set?.(requestUrl, html);
            applyRisHtml(html);
        })
        .catch(function (error) {
            if (seq !== risFetchSeq) {
                return;
            }
            console.error('RIS fetch error:', error);
            if (tableContainer) {
                tableContainer.innerHTML =
                    '<div class="px-5 py-16 text-center text-sm text-red-600">Failed to load RIS data. Please try again.</div>';
            }
        });

    }


    // =====================================================
    // BIND RIS EVENT LISTENERS
    // =====================================================

    function updateRisFilterSlider(activeFilter) {
        const track = document.getElementById('risFilterSlider');
        if (!track) return;

        const buttons = track.querySelectorAll('.ris-filter-btn');
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


    function applyRisFilter(filter) {
        if (!filter || filter === currentFilter) {
            return;
        }

        currentFocus = '';
        currentFilter = filter;
        updateRisFilterSlider(currentFilter);

        clearTimeout(risFilterFetchTimer);
        risFilterFetchTimer = setTimeout(function () {
            fetchRisData(
                currentFilter,
                currentSearch,
                null
            );
        }, 230);
    }

    function applyAdminPrView(mode, animate) {
        if (typeof window.applyAdminViewMode === 'function') {
            window.applyAdminViewMode({
                mode: mode,
                tableId: 'risTableContainer',
                cardsId: 'risCardsContainer',
                buttonSelector: '.admin-pr-view-btn',
                storageKey: 'admin_pr_view',
                animate: animate !== false,
            });
            return;
        }
        var table = document.getElementById('risTableContainer');
        var cards = document.getElementById('risCardsContainer');
        var buttons = document.querySelectorAll('.admin-pr-view-btn');
        if (!table || !cards) return;
        var useCards = mode === 'cards';
        table.classList.toggle('hidden', useCards);
        cards.classList.toggle('hidden', !useCards);
        buttons.forEach(function (btn) {
            var active = (btn.getAttribute('data-view-mode') || btn.getAttribute('data-pr-view')) === mode;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        try { /* do not persist cards/table across navigation */ } catch (e) {}
    }


    function bindRisEventListeners() {


        // =====================================================
        // SEARCH INPUT
        // =====================================================

        const searchInput =
            document.getElementById('risLiveSearch');

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function () {

                    clearTimeout(risSearchTimer);
                    clearTimeout(risFilterFetchTimer);

                    const value = this.value;

                    risSearchTimer =
                        setTimeout(
                            function () {

                                currentSearch = value;
                                fetchRisData(
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

        const dateBtn = document.getElementById('risSubmittedDateBtn');
        const dateInput = document.getElementById('risSubmittedDate');
        const dateClear = document.getElementById('risSubmittedDateClear');

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
                syncRisDateFilterUi();
                fetchRisData(currentFilter, currentSearch, null);
            });
        }

        if (dateClear) {
            dateClear.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                currentSubmittedDate = '';
                syncRisDateFilterUi();
                fetchRisData(currentFilter, currentSearch, null);
            });
        }

        syncRisDateFilterUi();


        // =====================================================
        // FILTER BUTTONS
        // =====================================================

        document.querySelectorAll('.ris-filter-btn, .ris-filter-card').forEach(function (el) {
            el.addEventListener('click', function () {
                applyRisFilter(this.getAttribute('data-filter'));
            });
        });

        const filterButtons = document.querySelectorAll('.ris-filter-btn');
        filterButtons.forEach(function (btn, index) {
            btn.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                event.preventDefault();
                const next = event.key === 'ArrowRight'
                    ? filterButtons[(index + 1) % filterButtons.length]
                    : filterButtons[(index - 1 + filterButtons.length) % filterButtons.length];
                next.focus();
                applyRisFilter(next.getAttribute('data-filter'));
            });
        });

        updateRisFilterSlider(currentFilter);


        // =====================================================
        // CARDS / TABLE VIEW SWITCHER
        // =====================================================

        document.querySelectorAll('.admin-pr-view-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyAdminPrView(btn.getAttribute('data-view-mode') || btn.getAttribute('data-pr-view') || 'table', true);
                if (typeof window.clearRisAcceptSelection === 'function') {
                    window.clearRisAcceptSelection();
                }
            });
        });

        // Always reopen in table view when navigating / reloading content.
        applyAdminPrView('table', false);


        // =====================================================
        // PAGINATION LINKS
        // =====================================================

        const paginationLinks =
            document.querySelectorAll(
                '.ris-pagination-link'
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

                    clearTimeout(risFilterFetchTimer);
                    fetchRisData(
                        currentFilter,
                        currentSearch,
                        page
                    );

                }
            );

        });

        // Keep multi-select accept state in sync after AJAX refresh.
        if (typeof window.updateRisAcceptSelection === 'function') {
            window.updateRisAcceptSelection();
        }

    }


    // =====================================================
    // MULTI-SELECT ACCEPT
    // =====================================================

    function getVisibleRisAcceptRoot() {
        var table = document.getElementById('risTableContainer');
        var cards = document.getElementById('risCardsContainer');
        if (cards && !cards.classList.contains('hidden')) {
            return cards;
        }
        return table || document;
    }

    function getRisAcceptCheckboxes() {
        var root = getVisibleRisAcceptRoot();
        return Array.from(root.querySelectorAll('.ris-accept-checkbox'));
    }

    function getSelectedRisAcceptIds() {
        var seen = {};
        return getRisAcceptCheckboxes()
            .filter(function (input) { return input.checked; })
            .map(function (input) { return String(input.value); })
            .filter(function (id) {
                if (!id || seen[id]) return false;
                seen[id] = true;
                return true;
            });
    }

    window.updateRisAcceptSelection = function () {
        var boxes = getRisAcceptCheckboxes();
        var selected = getSelectedRisAcceptIds();
        var selectAll = document.getElementById('risSelectAllPage');
        var bar = document.getElementById('risBulkAcceptBar');
        var countEl = document.getElementById('risBulkAcceptCount');

        if (selectAll) {
            if (!boxes.length) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                selectAll.disabled = true;
            } else {
                selectAll.disabled = false;
                selectAll.checked = selected.length === boxes.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
            }
        }

        if (countEl) {
            countEl.textContent = selected.length === 1
                ? '1 selected'
                : (selected.length + ' selected');
        }

        if (bar) {
            if (selected.length > 0) {
                bar.classList.remove('hidden');
                bar.classList.add('inline-flex');
            } else {
                bar.classList.add('hidden');
                bar.classList.remove('inline-flex');
            }
        }
    };

    window.toggleRisSelectAllPage = function (source) {
        var checked = !!(source && source.checked);
        getRisAcceptCheckboxes().forEach(function (input) {
            input.checked = checked;
        });
        window.updateRisAcceptSelection();
    };

    window.clearRisAcceptSelection = function () {
        getRisAcceptCheckboxes().forEach(function (input) {
            input.checked = false;
        });
        window.updateRisAcceptSelection();
    };

    window.openSelectedRisAcceptModal = function () {
        var ids = getSelectedRisAcceptIds();
        if (!ids.length) {
            return;
        }
        if (typeof window.openBulkAcceptRisModal === 'function') {
            window.openBulkAcceptRisModal(ids);
        }
    };


    // =====================================================
    // OPEN RIS PREVIEW
    // =====================================================

    window.openRisPreviewModal = function (risId) {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('risPreviewIframe');


        if (!modal || !iframe) {

            return;

        }

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }


        // =====================================================
        // LOAD RIS FORM
        //
        // The timestamp is a cache buster.
        // eg. it prevents the browser from showing an older
        // cached version of the RIS form.
        // =====================================================

        iframe.src =
            `{{ \App\Support\AdminPortal::url('procurement-review/ris') }}/${risId}/print?ts=${Date.now()}`;

        if (window.fillRisPreviewAttachments) {
            window.fillRisPreviewAttachments(risId);
        }

        const csrfToken = document.querySelector('input[name="_token"]')?.value;
        if (csrfToken && @json(\App\Support\AdminPortal::canActOnRis())) {
            fetch(`{{ \App\Support\AdminPortal::url('procurement-review/ris') }}/${risId}/review`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).catch(function () {});
        }


        // =====================================================
        // SHOW MODAL
        // =====================================================

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const rescheduleScale = function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('risPreviewIframe');
            } else if (typeof window.scaleRisPreviewToFit === 'function') {
                window.scaleRisPreviewToFit();
            }
        };

        // Scale only after the print view has loaded (avoids blank/collapsed preview).
        iframe.onload = function () {
            rescheduleScale();
            setTimeout(rescheduleScale, 60);
            setTimeout(rescheduleScale, 250);
        };

    };


    // =====================================================
    // CLOSE RIS PREVIEW
    // =====================================================

    window.closeRisPreviewModal = function () {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('risPreviewIframe');


        // =====================================================
        // CLEAR PREVIEW
        // =====================================================

        if (iframe) {

            iframe.src = 'about:blank';

        }


        // =====================================================
        // HIDE MODAL
        // =====================================================

        if (typeof window.exitRisPreviewFullscreen === 'function') {
            window.exitRisPreviewFullscreen('risPreviewModal', 'risPreviewIframe');
        }

        if (modal) {

            modal.classList.add('hidden');
            modal.style.display = '';
        }

        document.body.style.overflow = '';

    };


    // =====================================================
    // SCALE RIS IFRAME TO FIT VIEWPORT
    // =====================================================

    window.scaleRisPreviewToFit = function () {
        if (typeof window.scaleRisPreviewIframe === 'function') {
            window.scaleRisPreviewIframe('risPreviewIframe');
            return;
        }
    };


    // =====================================================
    // PRINT RIS FORM
    // =====================================================

    window.printRisPreview = function () {

        const iframe =
            document.getElementById('risPreviewIframe');

        if (!iframe || !iframe.contentWindow) {

            return;

        }

        iframe.contentWindow.focus();
        iframe.contentWindow.print();

    };


    // =====================================================
    // RIS ROW ACTION MENU — teleport to body, anchor to ⋯ button
    // =====================================================

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

                // Reset so measuring is accurate after teleport.
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


    // Modal open/close for Direct Approve / Forward / Amend live in
    // admin.procurement-review._direct-approve-modal


    // =====================================================
    // RESCALE ON WINDOW RESIZE
    // =====================================================

    window.addEventListener(
        'resize',
        function () {

            const modal =
                document.getElementById('risPreviewModal');

            if (
                modal &&
                !modal.classList.contains('hidden')
            ) {

                window.scaleRisPreviewToFit();

            }

            updateRisFilterSlider(currentFilter);

        }
    );


    // =====================================================
    // CLOSE PREVIEW WITH ESCAPE KEY
    // =====================================================

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                closeRisPreviewModal();
                closeDirectApproveModal();
                closeAmendModal();

            }

        }
    );


    // =====================================================
    // INITIALISE EVENT LISTENERS ON PAGE LOAD
    // =====================================================

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            bindRisEventListeners();

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

        }
    );


</script>

@endsection

