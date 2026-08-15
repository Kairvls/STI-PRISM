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
            View all finished / completed RIS records (Admin Approved, Co-signed, Amended). Active/pending RIS forms are not shown here.
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
    let currentSearch = '{{ $search ?? '' }}';


    // =====================================================
    // FETCH SIGNATURE HISTORY DATA VIA AJAX
    // =====================================================

    function fetchSignatureHistoryData(search, page) {

        const params = new URLSearchParams();

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
                { search: search, page: page },
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

    function bindSignatureHistoryEventListeners() {

        // =====================================================
        // SEARCH INPUT
        // =====================================================

        const searchInput =
            document.getElementById('signatureHistoryLiveSearch');

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function () {

                    clearTimeout(signatureHistorySearchTimer);

                    const value = this.value;

                    signatureHistorySearchTimer =
                        setTimeout(
                            function () {
                                currentSearch = value;
                                fetchSignatureHistoryData(
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
        // PAGINATION LINKS
        // =====================================================

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
                        page
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
