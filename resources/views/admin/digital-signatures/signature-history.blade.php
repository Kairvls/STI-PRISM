@extends('layouts.admin-layout')

@section('title', 'Signature History')

@section('content')

<div class="admin-page space-y-6">


    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div>

        <h1 class="admin-page-title">
            Signature History
        </h1>

        <p class="admin-page-subtitle">
            View every RIS form in the log, including incomplete or still-in-progress records.
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
    {{-- SIGNATURE HISTORY CONTENT (STATS + TOGGLE + TABLE + PAGINATION) --}}
    {{-- LOADED VIA AJAX OR INCLUDED DIRECTLY --}}
    {{-- ===================================================== --}}

    <div id="signatureHistoryContentContainer">

        @include('admin.digital-signatures._signature-history-content')

    </div>


    {{-- ===================================================== --}}
    {{-- RIS PREVIEW MODAL --}}
    {{-- ===================================================== --}}

    @include('admin.partials.ris-preview-modal', [
        'iframeId' => 'signatureHistoryPreviewIframe',
        'closeFn' => 'closeSignatureHistoryPreviewModal',
        'printFn' => 'printSignatureHistoryPreview',
        'zIndex' => '50',
    ])


</div>


{{-- ===================================================== --}}
{{-- SIGNATURE HISTORY JAVASCRIPT --}}
{{-- (SEARCH, TOGGLE FILTERS, PAGINATION & PREVIEW) --}}
{{-- ===================================================== --}}

<script>


    // =====================================================
    // SIGNATURE HISTORY AJAX STATE
    // =====================================================

    let signatureHistorySearchTimer = null;
    let signatureHistoryFilterFetchTimer = null;
    let currentSearch = '{{ $search ?? '' }}';
    let currentFilter = '{{ $filter ?? 'all' }}';


    function fetchSignatureHistoryData(search, page, filter) {

        const params = new URLSearchParams();

        params.set('filter', filter || currentFilter || 'all');

        if (search) {
            params.set('search', search);
        }

        if (page) {
            params.set('page', page);
        }

        // Show loading indicator on the table.
        const tableContainer =
            document.getElementById('signatureHistoryTableContainer');

        if (tableContainer) {
            tableContainer.innerHTML =
                '<div class="flex items-center justify-center px-5 py-16"><div class="flex items-center gap-3"><svg class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><span class="text-sm text-gray-500">Loading...</span></div></div>';
        }

        // Fetch the updated content.
        fetch(
            '{{ route('admin.digital-signatures.history') }}?' +
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
                document.getElementById('signatureHistoryContentContainer');

            if (contentContainer) {
                contentContainer.innerHTML = html;
            }

            // Re-bind event listeners after DOM update.
            bindSignatureHistoryEventListeners();

            // Update URL without reloading the page.
            const url =
                '{{ route('admin.digital-signatures.history') }}?' +
                params.toString();

            window.history.replaceState(
                { search: search, page: page, filter: currentFilter },
                '',
                url
            );

        })

        .catch(function (error) {

            console.error(
                'Signature History fetch error:',
                error
            );

            if (tableContainer) {
                tableContainer.innerHTML =
                    '<div class="px-5 py-16 text-center text-sm text-red-600">Failed to load data. Please try again.</div>';
            }

        });

    }


    // =====================================================
    // BIND SIGNATURE HISTORY EVENT LISTENERS
    // =====================================================

    function updateSignatureHistoryFilterSlider(activeFilter, animate) {
        const track = document.getElementById('signatureHistoryFilterSlider');
        if (!track) return;

        const thumb = track.querySelector('.signature-history-filter-thumb');
        const buttons = track.querySelectorAll('.signature-history-filter-btn');
        if (!thumb || !buttons.length) return;

        let activeBtn = null;
        for (let i = 0; i < buttons.length; i++) {
            const btn = buttons[i];
            const isActive = btn.getAttribute('data-filter') === activeFilter;
            btn.style.color = isActive ? '#020617' : '#64748b';
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.setAttribute('tabindex', isActive ? '0' : '-1');
            if (isActive) activeBtn = btn;
        }
        if (!activeBtn) activeBtn = buttons[0];

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

    function applySignatureHistoryFilter(filter) {
        if (!filter || filter === currentFilter) return;
        currentFilter = filter;
        updateSignatureHistoryFilterSlider(currentFilter, true);
        clearTimeout(signatureHistoryFilterFetchTimer);
        signatureHistoryFilterFetchTimer = setTimeout(function () {
            fetchSignatureHistoryData(currentSearch, null, currentFilter);
        }, 230);
    }

    function bindSignatureHistoryEventListeners() {

        const searchInput =
            document.getElementById('signatureHistoryLiveSearch');

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function () {

                    clearTimeout(signatureHistorySearchTimer);
                    clearTimeout(signatureHistoryFilterFetchTimer);

                    const value = this.value;

                    signatureHistorySearchTimer =
                        setTimeout(
                            function () {
                                currentSearch = value;
                                fetchSignatureHistoryData(
                                    currentSearch,
                                    null,
                                    currentFilter
                                );
                            },
                            400
                        );

                }
            );

        }

        document.querySelectorAll('.signature-history-filter-btn, .signature-history-filter-card').forEach(function (el) {
            el.addEventListener('click', function () {
                applySignatureHistoryFilter(this.getAttribute('data-filter'));
            });
        });

        const filterButtons = document.querySelectorAll('.signature-history-filter-btn');
        filterButtons.forEach(function (btn, index) {
            btn.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
                event.preventDefault();
                const next = event.key === 'ArrowRight'
                    ? filterButtons[(index + 1) % filterButtons.length]
                    : filterButtons[(index - 1 + filterButtons.length) % filterButtons.length];
                next.focus();
                applySignatureHistoryFilter(next.getAttribute('data-filter'));
            });
        });

        updateSignatureHistoryFilterSlider(currentFilter, false);

        const paginationLinks =
            document.querySelectorAll(
                '.signature-history-pagination-link'
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

                    fetchSignatureHistoryData(
                        currentSearch,
                        page,
                        currentFilter
                    );

                }
            );

        });

    }


    // =====================================================
    // OPEN SIGNATURE HISTORY PREVIEW
    // =====================================================

    window.openSignatureHistoryPreviewModal = function (risId) {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('signatureHistoryPreviewIframe');

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

    };


    // =====================================================
    // CLOSE SIGNATURE HISTORY PREVIEW
    // =====================================================

    window.closeSignatureHistoryPreviewModal = function () {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('signatureHistoryPreviewIframe');

        if (iframe) {
            iframe.src = 'about:blank';
        }

        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = '';
        }

    };


    // =====================================================
    // SCALE RIS IFRAME TO FIT VIEWPORT
    // =====================================================

    window.scaleSignatureHistoryPreviewToFit = function () {

        const iframe =
            document.getElementById('signatureHistoryPreviewIframe');

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

        const scale = Math.min(scaleX, scaleY, 1);

        iframe.style.transform = `scale(${scale})`;
        iframe.style.width = docWidthPx + 'px';
        iframe.style.height = docHeightPx + 'px';

    };


    // =====================================================
    // PRINT RIS FORM
    // =====================================================

    window.printSignatureHistoryPreview = function () {

        const iframe =
            document.getElementById('signatureHistoryPreviewIframe');

        if (!iframe || !iframe.contentWindow) {
            return;
        }

        iframe.contentWindow.focus();
        iframe.contentWindow.print();

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
                window.scaleSignatureHistoryPreviewToFit();
            }

            updateSignatureHistoryFilterSlider(currentFilter, false);

        }
    );


    // =====================================================
    // CLOSE PREVIEW WITH ESCAPE KEY
    // =====================================================

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeSignatureHistoryPreviewModal();
            }

        }
    );


    // =====================================================
    // INITIALISE EVENT LISTENERS ON PAGE LOAD
    // =====================================================

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            bindSignatureHistoryEventListeners();
        }
    );


</script>

@endsection
