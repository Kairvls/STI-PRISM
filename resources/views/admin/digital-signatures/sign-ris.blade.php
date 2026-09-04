@extends('layouts.admin-layout')

@section('title', 'Sign RIS')

@section('content')

<div class="admin-page space-y-6">


    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div>

        <h1 class="admin-page-title">
            Sign RIS
        </h1>

        <p class="admin-page-subtitle">
            Forward to President, approve directly, return for revision, or sign Issued by after President approval.
        </p>

    </div>


    {{-- ===================================================== --}}
    {{-- SIGN RIS CONTENT --}}
    {{-- LOADED VIA AJAX OR INCLUDED DIRECTLY --}}
    {{-- ===================================================== --}}

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


    @include('admin.procurement-review._direct-approve-modal')
    @include('admin.digital-signatures._return-revision-modal')

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
    let currentFilter = '{{ $filter ?? 'pending' }}';
    let currentSearch = '{{ $search ?? '' }}';


    // =====================================================
    // FETCH SIGN RIS DATA VIA AJAX
    // =====================================================

    function fetchSignRisData(filter, search, page) {

        const params = new URLSearchParams();

        params.set('filter', filter || 'pending');

        if (search) {
            params.set('search', search);
        }

        if (page) {
            params.set('page', page);
        }

        // Show loading indicator on the table.
        const tableContainer =
            document.getElementById('signRisTableContainer');

        if (tableContainer) {
            tableContainer.innerHTML =
                '<div class="flex items-center justify-center px-5 py-16"><div class="flex items-center gap-3"><svg class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span class="text-sm text-gray-500">Loading...</span></div></div>';
        }

        // Fetch the updated content.
        fetch(
            '{{ route('admin.digital-signatures.sign-ris') }}?' +
            params.toString(),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            }
        )

        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    'Server responded with ' +
                    response.status
                );
            }

            return response.text();

        })

        .then(function (html) {

            // Replace the entire content area.
            const contentContainer =
                document.getElementById('signRisContentContainer');

            if (contentContainer) {
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const partial = parsed.querySelector('#signRisContent')
                    || parsed.querySelector('#signRisContentContainer');
                contentContainer.innerHTML = partial ? partial.innerHTML : html;
            }

            // Re-bind event listeners after DOM update.
            bindSignRisEventListeners();

            // Update URL without reloading the page.
            const url =
                '{{ route('admin.digital-signatures.sign-ris') }}?' +
                params.toString();

            window.history.replaceState(
                { filter: filter, search: search, page: page },
                '',
                url
            );

        })

        .catch(function (error) {

            console.error(
                'Sign RIS fetch error:',
                error
            );

            if (tableContainer) {
                tableContainer.innerHTML =
                    '<div class="px-5 py-16 text-center text-sm text-red-600">Failed to load data. Please try again.</div>';
            }

        });

    }


    // =====================================================
    // BIND SIGN RIS EVENT LISTENERS
    // =====================================================

    function updateSignRisFilterSlider(activeFilter, animate) {

        const track = document.getElementById('signRisFilterSlider');
        if (!track) {
            return;
        }

        const thumb = track.querySelector('.sign-ris-filter-thumb');
        const buttons = track.querySelectorAll('.sign-ris-filter-btn');
        if (!thumb || !buttons.length) {
            return;
        }

        let activeBtn = null;

        for (let i = 0; i < buttons.length; i++) {
            const btn = buttons[i];
            const isActive = btn.getAttribute('data-filter') === activeFilter;
            btn.style.color = isActive ? '#020617' : '#64748b';
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.setAttribute('tabindex', isActive ? '0' : '-1');
            if (isActive) {
                activeBtn = btn;
            }
        }

        if (!activeBtn) {
            activeBtn = buttons[0];
        }

        const x = activeBtn.offsetLeft;
        const w = activeBtn.offsetWidth;

        if (!animate) {
            const previous = thumb.style.transition;
            thumb.style.transition = 'none';
            thumb.style.width = w + 'px';
            thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
            void thumb.offsetWidth;
            thumb.style.transition = previous || 'transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1)';
        } else {
            thumb.style.width = w + 'px';
            thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
        }

        if (typeof activeBtn.scrollIntoView === 'function') {
            activeBtn.scrollIntoView({ behavior: animate ? 'smooth' : 'auto', inline: 'nearest', block: 'nearest' });
        }
    }


    function applySignRisFilter(filter) {
        if (!filter || filter === currentFilter) {
            return;
        }

        currentFilter = filter;
        updateSignRisFilterSlider(currentFilter, true);

        clearTimeout(signRisFilterFetchTimer);
        signRisFilterFetchTimer = setTimeout(function () {
            fetchSignRisData(
                currentFilter,
                currentSearch,
                null
            );
        }, 230);
    }


    function applySignRisView(mode, animate) {
        if (typeof window.applyAdminViewMode === 'function') {
            window.applyAdminViewMode({
                mode: mode,
                tableId: 'signRisTableContainer',
                cardsId: 'signRisCardsContainer',
                buttonSelector: '.admin-sign-view-btn',
                storageKey: 'admin_sign_view',
                animate: animate !== false,
            });
            return;
        }
        var table = document.getElementById('signRisTableContainer');
        var cards = document.getElementById('signRisCardsContainer');
        var buttons = document.querySelectorAll('.admin-sign-view-btn');
        if (!table || !cards) return;
        var useCards = mode === 'cards';
        table.classList.toggle('hidden', useCards);
        cards.classList.toggle('hidden', !useCards);
        buttons.forEach(function (btn) {
            var active = btn.getAttribute('data-view-mode') === mode;
            btn.classList.toggle('is-active', active);
        });
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

        updateSignRisFilterSlider(currentFilter, false);

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


    window.openSignRisPreviewModal = function (risId) {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('signRisPreviewIframe');

        if (!modal || !iframe) {
            return;
        }

        iframe.src =
            `/admin/procurement-review/ris/${risId}/print?ts=${Date.now()}`;

        if (window.fillRisPreviewAttachments) {
            window.fillRisPreviewAttachments(risId);
        }

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

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('signRisPreviewIframe');

        if (iframe) {
            iframe.src = 'about:blank';
        }

        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = '';
        }

        document.body.style.overflow = '';

    };


    window.scaleSignRisPreviewToFit = function () {
        if (typeof window.scaleRisPreviewIframe === 'function') {
            window.scaleRisPreviewIframe('signRisPreviewIframe');
        }
    };


    window.printSignRisPreview = function () {

        const iframe =
            document.getElementById('signRisPreviewIframe');

        if (!iframe || !iframe.contentWindow) {
            return;
        }

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

            updateSignRisFilterSlider(currentFilter, false);

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
                if (typeof window.closeDirectApproveModal === 'function') {
                    window.closeDirectApproveModal();
                }
                if (typeof window.closeReturnRevisionModal === 'function') {
                    window.closeReturnRevisionModal();
                }
            }
        }
    );

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            bindSignRisEventListeners();
        }
    );


</script>

@endsection
