@extends('layouts.admin-layout')

{{-- ===================================================== --}}
{{-- PROCUREMENT REQUEST / RIS APPROVAL PAGE --}}
{{-- ===================================================== --}}

@section('content')

<div class="admin-page space-y-6">


    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div>

        <h1 class="admin-page-title">
            Procurement Request
        </h1>

        <p class="admin-page-subtitle">
            Review and manage Requisition Issue Slips submitted by the Purchaser.
        </p>

    </div>


    {{-- ===================================================== --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ===================================================== --}}

    @if(session('success'))

        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- ERROR MESSAGE --}}
    {{-- ===================================================== --}}

    @if(session('error'))

        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- RIS CONTENT (STATS + FILTERS + TABLE + PAGINATION) --}}
    {{-- LOADED VIA AJAX OR INCLUDED DIRECTLY --}}
    {{-- ===================================================== --}}

    <div id="risContentContainer">

        @include('admin.procurement-review._content')

    </div>


    {{-- ===================================================== --}}
    {{-- RIS PREVIEW MODAL (purchaser print-preview chrome) --}}
    {{-- ===================================================== --}}

    @include('admin.partials.ris-preview-modal', ['zIndex' => '50'])


    {{-- Direct approve / forward modal and remarks-only amend modal --}}
    @include('admin.procurement-review._direct-approve-modal')

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
    let currentFilter = '{{ $filter ?? 'pending' }}';
    let currentSearch = '{{ $search ?? '' }}';


    // =====================================================
    // FETCH RIS DATA VIA AJAX
    // =====================================================

    function fetchRisData(filter, search, page) {

        // Build query parameters.
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
            document.getElementById('risTableContainer');

        if (tableContainer) {
            tableContainer.innerHTML =
                '<div class="flex items-center justify-center px-5 py-16"><div class="flex items-center gap-3"><svg class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span class="text-sm text-gray-500">Loading...</span></div></div>';
        }


        // Fetch the updated content.
        fetch(
            '{{ route('admin.procurement-review.ris') }}?' +
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

            const contentContainer =
                document.getElementById('risContentContainer');

            if (contentContainer) {
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const partial = parsed.querySelector('#risContent')
                    || parsed.querySelector('#risContentContainer');
                contentContainer.innerHTML = partial ? partial.innerHTML : html;
            }


            // Re-bind event listeners after DOM update.
            bindRisEventListeners();


            // Update URL without reloading the page.
            const url =
                '{{ route('admin.procurement-review.ris') }}?' +
                params.toString();

            window.history.replaceState(
                { filter: filter, search: search, page: page },
                '',
                url
            );

        })

        .catch(function (error) {

            console.error(
                'RIS fetch error:',
                error
            );


            // Restore a basic error state.
            if (tableContainer) {

                tableContainer.innerHTML =
                    '<div class="px-5 py-16 text-center text-sm text-red-600">Failed to load RIS data. Please try again.</div>';

            }

        });

    }


    // =====================================================
    // BIND RIS EVENT LISTENERS
    // =====================================================

    function updateRisFilterSlider(activeFilter, animate) {

        const track = document.getElementById('risFilterSlider');
        if (!track) {
            return;
        }

        const thumb = track.querySelector('.ris-filter-thumb');
        const buttons = track.querySelectorAll('.ris-filter-btn');
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


    function applyRisFilter(filter) {
        if (!filter || filter === currentFilter) {
            return;
        }

        currentFilter = filter;
        updateRisFilterSlider(currentFilter, true);

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

        updateRisFilterSlider(currentFilter, false);


        // =====================================================
        // CARDS / TABLE VIEW SWITCHER
        // =====================================================

        document.querySelectorAll('.admin-pr-view-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyAdminPrView(btn.getAttribute('data-view-mode') || btn.getAttribute('data-pr-view') || 'table', true);
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

    }


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


        // =====================================================
        // LOAD RIS FORM
        //
        // The timestamp is a cache buster.
        // eg. it prevents the browser from showing an older
        // cached version of the RIS form.
        // =====================================================

        iframe.src =
            `/admin/procurement-review/ris/${risId}/print?ts=${Date.now()}`;

        if (window.fillRisPreviewAttachments) {
            window.fillRisPreviewAttachments(risId);
        }

        const csrfToken = document.querySelector('input[name="_token"]')?.value;
        if (csrfToken) {
            fetch(`/admin/procurement-review/ris/${risId}/review`, {
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
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('risPreviewIframe');
            } else if (typeof window.scaleRisPreviewToFit === 'function') {
                window.scaleRisPreviewToFit();
            }
        });

        iframe.onload = function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('risPreviewIframe');
            } else if (typeof window.scaleRisPreviewToFit === 'function') {
                window.scaleRisPreviewToFit();
            }
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


    window.openDirectApproveModal = function(risId, mode) {
        var modal = document.getElementById('directApproveModal');
        var body = document.getElementById('directApproveModalBody');
        var title = document.getElementById('directApproveModalTitle');
        var subtitle = document.getElementById('directApproveModalSubtitle');
        if (!modal || !body) return;

        var actionMode = 'direct';
        if (mode === 'forward') actionMode = 'forward';
        if (mode === 'cosign') actionMode = 'cosign';
        if (title) {
            title.textContent = actionMode === 'forward'
                ? 'Forward to President'
                : (actionMode === 'cosign' ? 'Sign Issued by' : 'Admin Approval');
        }
        if (subtitle) {
            subtitle.textContent = actionMode === 'forward'
                ? 'Review the RIS form, then forward it to the President. Issued by is signed later on Sign RIS.'
                : (actionMode === 'cosign'
                    ? 'Sign Issued by on the RIS form. Approved by is already filled by the President.'
                    : 'Sign Issued by on the RIS form, then confirm Admin Approval.');
        }

        body.innerHTML = '<div class="flex flex-1 items-center justify-center gap-3 py-16 text-sm text-gray-500"><div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-slate-800"></div>Loading RIS form...</div>';
        modal.classList.remove('hidden');

        fetch('/admin/procurement-review/ris/' + risId + '/direct-approve-form?mode=' + encodeURIComponent(actionMode), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Failed to load form');
            return response.text();
        })
        .then(function(html) {
            body.innerHTML = html;
            var dateInput = document.getElementById('da_issued_by_date');
            if (dateInput) {
                dateInput.addEventListener('input', function() {
                    var digits = this.value.replace(/\D/g, '').slice(0, 8);
                    var parts = [];
                    if (digits.length > 0) parts.push(digits.slice(0, 2));
                    if (digits.length > 2) parts.push(digits.slice(2, 4));
                    if (digits.length > 4) parts.push(digits.slice(4, 8));
                    this.value = parts.join('/');
                });
            }
        })
        .catch(function() {
            body.innerHTML = '<div class="px-6 py-16 text-center text-sm text-slate-600">Failed to load RIS form. Please try again.</div>';
        });
    };


    // =====================================================
    // CLOSE DIRECT APPROVAL MODAL
    // =====================================================

    window.closeDirectApproveModal = function() {
        var modal = document.getElementById('directApproveModal');
        var body = document.getElementById('directApproveModalBody');
        if (body) {
            body.innerHTML = '';
        }
        if (modal) {
            modal.classList.add('hidden');
        }
    };


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

            updateRisFilterSlider(currentFilter, false);

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

        }
    );


</script>

@endsection

