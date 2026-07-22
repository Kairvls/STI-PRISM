@extends('layouts.admin-layout')

{{-- ===================================================== --}}
{{-- PROCUREMENT REQUEST / RIS APPROVAL PAGE --}}
{{-- ===================================================== --}}

@section('content')

<div class="space-y-6">


    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div>

        <h1 class="text-2xl font-bold text-gray-900">
            RIS Approval
        </h1>

        <p class="mt-1 text-sm text-gray-600">
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
    {{-- RIS PREVIEW MODAL --}}
    {{-- ===================================================== --}}

    <div
        id="risPreviewModal"
        class="fixed inset-0 z-50 hidden"
    >

        <div
            class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px]"
            onclick="closeRisPreviewModal()"
        >

            <div
                class="h-[calc(100vh-1rem)] w-full max-w-6xl overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
                onclick="event.stopPropagation()"
            >


                {{-- ===================================================== --}}
                {{-- MODAL HEADER --}}
                {{-- ===================================================== --}}

                <div class="border-b border-gray-100 px-6 py-5">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <h3 class="text-lg font-bold text-slate-950">
                                RIS Form Preview
                            </h3>

                            <p
                                id="risPreviewModalSubtitle"
                                class="mt-1 text-sm text-slate-600"
                            >
                                Requisition and Issue Slip
                            </p>

                        </div>


                        {{-- ===================================================== --}}
                        {{-- CLOSE BUTTON --}}
                        {{-- ===================================================== --}}

                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                            onclick="closeRisPreviewModal()"
                            title="Close RIS preview"
                            aria-label="Close"
                        >

                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                ></path>

                            </svg>

                        </button>

                    </div>

                </div>


                {{-- ===================================================== --}}
                {{-- RIS PREVIEW IFRAME --}}
                {{-- ===================================================== --}}

                <div class="h-full overflow-auto bg-gray-50">

                    <iframe
                        id="risPreviewIframe"
                        class="h-full w-full"
                        style="min-height: calc(100vh - 140px);"
                        src="about:blank"
                        title="RIS Form Preview"
                    ></iframe>

                </div>

            </div>

        </div>

    </div>


</div>


{{-- ===================================================== --}}
{{-- RIS PAGE JAVASCRIPT --}}
{{-- (SEARCH, FILTERS, PAGINATION & PREVIEW) --}}
{{-- ===================================================== --}}

<script>


    // =====================================================
    // RIS AJAX STATE
    // =====================================================

    let risSearchTimer = null;
    let currentFilter = '{{ $filter ?? 'all' }}';
    let currentSearch = '{{ $search ?? '' }}';


    // =====================================================
    // FETCH RIS DATA VIA AJAX
    // =====================================================

    function fetchRisData(filter, search, page) {

        // Build query parameters.
        const params = new URLSearchParams();

        params.set('filter', filter || 'all');

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

            // Replace the entire content area.
            const contentContainer =
                document.getElementById('risContentContainer');

            if (contentContainer) {

                contentContainer.innerHTML = html;

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

        const filterButtons =
            document.querySelectorAll('.ris-filter-btn');

        filterButtons.forEach(function (btn) {

            btn.addEventListener(
                'click',
                function () {

                    const filter =
                        this.getAttribute('data-filter');

                    if (filter === currentFilter) {

                        return;

                    }

                    currentFilter = filter;

                    fetchRisData(
                        currentFilter,
                        currentSearch,
                        null
                    );

                }
            );

        });


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

    function openRisPreviewModal(risId) {

        const modal =
            document.getElementById('risPreviewModal');

        const iframe =
            document.getElementById('risPreviewIframe');

        const subtitle =
            document.getElementById('risPreviewModalSubtitle');


        if (!modal || !iframe) {

            return;

        }


        // =====================================================
        // UPDATE MODAL TITLE
        // =====================================================

        if (subtitle) {

            subtitle.textContent =
                `RIS #${risId}`;

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


        // =====================================================
        // SHOW MODAL
        // =====================================================

        modal.classList.remove('hidden');

    }


    // =====================================================
    // CLOSE RIS PREVIEW
    // =====================================================

    function closeRisPreviewModal() {

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

        }

    }


    // =====================================================
    // CLOSE PREVIEW WITH ESCAPE KEY
    // =====================================================

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                closeRisPreviewModal();

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

