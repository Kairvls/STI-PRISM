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
            Review President-approved RIS and sign Issued by to return them to the Purchaser.
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
    {{-- SIGN RIS CONTENT (STATS + FILTERS + TABLE + PAGINATION) --}}
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

</div>


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
    let currentFilter = '{{ $filter ?? 'for_cosign' }}';
    let currentSearch = '{{ $search ?? '' }}';


    // =====================================================
    // FETCH SIGN RIS DATA VIA AJAX
    // =====================================================

    function fetchSignRisData(filter, search, page) {

        const params = new URLSearchParams();

        params.set('filter', filter || 'for_cosign');

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
                contentContainer.innerHTML = html;
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

        modal.classList.remove('hidden');
        modal.style.display = 'block';

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

    };


    window.scaleSignRisPreviewToFit = function () {

        const iframe =
            document.getElementById('signRisPreviewIframe');

        if (!iframe) {
            return;
        }

        const docWidthInches = 11;
        const docHeightInches = 8.5;

        const docWidthPx = docWidthInches * 96;
        const docHeightPx = docHeightInches * 96;

        const viewportWidth = window.innerWidth - 64;
        const viewportHeight = window.innerHeight - 64;

        const scaleX = viewportWidth / docWidthPx;
        const scaleY = viewportHeight / docHeightPx;

        const scale = Math.min(scaleX, scaleY, 1) * 0.9;

        iframe.style.transform = `scale(${scale})`;
        iframe.style.width = docWidthPx + 'px';
        iframe.style.height = docHeightPx + 'px';

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


    window.openCoSignModal = function(risId) {
        if (typeof window.openDirectApproveModal === 'function') {
            window.openDirectApproveModal(risId, 'cosign');
        }
    };

    document.addEventListener(
        'keydown',
        function (event) {
            if (event.key === 'Escape') {
                closeSignRisPreviewModal();
                if (typeof window.closeDirectApproveModal === 'function') {
                    window.closeDirectApproveModal();
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
