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
            class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
            onclick="closeRisPreviewModal()"
        >

            <div
                class="relative flex items-center justify-center"
                onclick="event.stopPropagation()"
            >

                <div id="risViewContainer" class="relative">

                    <iframe
                        id="risPreviewIframe"
                        class="bg-white shadow-2xl"
                        style="width: 11in; height: 8.5in; border: 1px solid #e5e7eb; transform-origin: center center;"
                        src="about:blank"
                        title="RIS Form Preview"
                    ></iframe>

                </div>

                <div class="fixed top-4 right-4 z-10 flex items-center gap-2">

                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95"
                        onclick="exportRisPdf()"
                        title="Download this RIS as PDF"
                    >
                        <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95"
                        onclick="printRisPreview()"
                        title="Print this RIS form"
                    >
                        <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>

                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90"
                        onclick="closeRisPreviewModal()"
                        title="Close RIS preview"
                        aria-label="Close"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- AMEND MODAL --}}
    {{-- ===================================================== --}}

    <div
        id="amendModal"
        class="fixed inset-0 z-50 hidden"
    >

        <div
            class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
            onclick="closeAmendModal()"
        >

            <div
                class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
                onclick="event.stopPropagation()"
            >

                {{-- Modal Header --}}

                <div class="border-b border-gray-100 px-6 py-4">

                    <h3 class="text-lg font-bold text-gray-900">
                        Return for Amendment
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Inform the Purchaser what parts of the form need to be changed and revised.
                    </p>

                </div>

                {{-- Modal Body --}}

                <form id="amendForm" method="POST" action="">
                    @csrf

                    <div class="space-y-5 px-6 py-5">

                        {{-- Remarks Textarea --}}

                        <div>

                            <label for="amend_remarks" class="block text-sm font-medium text-gray-700">
                                Amendment Remarks <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                id="amend_remarks"
                                name="remarks"
                                rows="5"
                                required
                                placeholder="Describe in detail what needs to be revised, e.g. incorrect quantities, missing supporting documents, wrong unit cost, etc."
                                class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                            ></textarea>

                            <p class="mt-1.5 text-xs text-gray-400">
                                These remarks will be visible to the Purchaser when they view this RIS.
                            </p>

                        </div>

                        {{-- Preview Info --}}

                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">

                            <div class="flex gap-2">

                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>

                                <p class="text-xs text-amber-800">
                                    This will immediately return the RIS to the Purchaser as a draft. The Purchaser
                                    must address the remarks above before resubmitting.
                                </p>

                            </div>

                        </div>

                    </div>

                    {{-- Modal Footer --}}

                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">

                        <button
                            type="button"
                            onclick="closeAmendModal()"
                            title="Cancel amendment"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            title="Return this RIS to the Purchaser with the amendment remarks"
                            class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700"
                        >
                            Confirm Amend
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- DIRECT APPROVAL MODAL --}}
    {{-- ===================================================== --}}

    <div
        id="directApproveModal"
        class="fixed inset-0 z-50 hidden"
    >

        <div
            class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
            onclick="closeDirectApproveModal()"
        >

            <div
                class="relative flex w-[95vw] max-w-6xl gap-4"
                onclick="event.stopPropagation()"
            >

                {{-- LEFT: RIS PREVIEW --}}

                <div class="flex-1 overflow-hidden rounded-2xl bg-white shadow-2xl">

                    <div class="border-b border-gray-100 px-5 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">RIS Preview</h3>
                    </div>

                    <div class="overflow-auto bg-gray-50 p-3">
                        <iframe
                            id="directApproveIframe"
                            class="bg-white shadow-md"
                            style="width: 100%; height: 75vh; border: 1px solid #e5e7eb;"
                            src="about:blank"
                            title="RIS Form Preview"
                        ></iframe>
                    </div>

                </div>

                {{-- RIGHT: APPROVAL FORM --}}

                <div class="w-[380px] overflow-hidden rounded-2xl bg-white shadow-2xl">

                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Direct Approval</h3>
                        <p class="mt-1 text-sm text-gray-500">Fill in the details to approve and return to Purchaser.</p>
                    </div>

                    <form id="directApproveForm" method="POST" action="">
                        @csrf

                        <div class="space-y-5 px-5 py-5">

                            {{-- Admin Name --}}

                            <div>
                                <label for="da_admin_name" class="block text-sm font-medium text-gray-700">
                                    Admin Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="da_admin_name"
                                    name="admin_name"
                                    required
                                    placeholder="Enter admin name"
                                    title="Enter the name of the admin approving this RIS"
                                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                                >
                            </div>

                            {{-- Date --}}

                            <div>
                                <label for="da_admin_date" class="block text-sm font-medium text-gray-700">
                                    Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="da_admin_date"
                                    name="admin_date"
                                    required
                                    value="{{ date('Y-m-d') }}"
                                    title="Select the date of approval"
                                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                                >
                            </div>

                            {{-- Signature Display (visual only) --}}

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Signature
                                </label>
                                <div
                                    title="This shows the signature preview (display only)"
                                    class="mt-1.5 flex h-20 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50"
                                >
                                    <span class="text-sm text-gray-400 italic">Admin Signature (display only)</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Signature preview area. The admin name above will be used as the signature.</p>
                            </div>

                            {{-- Preview Info --}}

                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                                <div class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-xs text-amber-800">
                                        This will immediately approve the RIS and return it to the Purchaser, bypassing the President.
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4">
                            <button
                                type="button"
                                onclick="closeDirectApproveModal()"
                                title="Cancel direct approval"
                                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                title="Confirm direct approval and return to Purchaser"
                                class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                            >
                                Confirm Approval
                            </button>
                        </div>

                    </form>

                </div>

                {{-- Close button --}}

                <button
                    type="button"
                    class="absolute -top-3 -right-3 flex h-8 w-8 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-md transition-all duration-200 hover:bg-slate-100 hover:text-slate-900"
                    onclick="closeDirectApproveModal()"
                    title="Close direct approval modal"
                    aria-label="Close"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

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


        // =====================================================
        // SHOW MODAL
        // =====================================================

        modal.classList.remove('hidden');


        // =====================================================
        // SCALE RIS TO FIT VIEWPORT
        // =====================================================

        setTimeout(scaleRisPreviewToFit, 100);

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
    // SCALE RIS IFRAME TO FIT VIEWPORT
    // =====================================================

    function scaleRisPreviewToFit() {

        const iframe =
            document.getElementById('risPreviewIframe');

        if (!iframe) {

            return;

        }


        // =====================================================
        // DOCUMENT DIMENSIONS IN INCHES (LANDSCAPE)
        // =====================================================

        const docWidthInches = 11;
        const docHeightInches = 8.5;


        // =====================================================
        // CONVERT TO PIXELS (96 DPI)
        // =====================================================

        const docWidthPx = docWidthInches * 96;
        const docHeightPx = docHeightInches * 96;


        // =====================================================
        // CALCULATE AVAILABLE VIEWPORT (WITH MARGINS)
        // =====================================================

        const viewportWidth = window.innerWidth - 64;
        const viewportHeight = window.innerHeight - 64;


        // =====================================================
        // CALCULATE SCALE TO FIT
        // =====================================================

        const scaleX = viewportWidth / docWidthPx;
        const scaleY = viewportHeight / docHeightPx;

        const scale = Math.min(scaleX, scaleY, 1);


        // =====================================================
        // APPLY CSS TRANSFORM TO THE IFRAME
        // =====================================================

        iframe.style.transform = `scale(${scale})`;
        iframe.style.width = docWidthPx + 'px';
        iframe.style.height = docHeightPx + 'px';

    }


    // =====================================================
    // PRINT RIS FORM
    // =====================================================

    function printRisPreview() {

        const iframe =
            document.getElementById('risPreviewIframe');

        if (!iframe || !iframe.contentWindow) {

            return;

        }

        iframe.contentWindow.focus();
        iframe.contentWindow.print();

    }


    // =====================================================
    // EXPORT RIS AS PDF
    // =====================================================

    function exportRisPdf() {

        const iframe =
            document.getElementById('risPreviewIframe');

        if (!iframe || !iframe.contentWindow) {

            return;

        }

        iframe.contentWindow.focus();

        // Add a small delay for the iframe to be ready, then print
        setTimeout(function () {
            iframe.contentWindow.print();
        }, 300);

    }


    // =====================================================
    // AMEND MODAL
    // =====================================================

    function openAmendModal(risId) {

        const modal =
            document.getElementById('amendModal');

        const form =
            document.getElementById('amendForm');

        const textarea =
            document.getElementById('amend_remarks');

        if (!modal || !form || !textarea) {

            return;

        }


        // =====================================================
        // SET FORM ACTION URL
        // =====================================================

        form.action =
            `/admin/procurement-review/ris/${risId}/reject`;


        // =====================================================
        // RESET TEXTAREA
        // =====================================================

        textarea.value = '';


        // =====================================================
        // SHOW MODAL
        // =====================================================

        modal.classList.remove('hidden');


        // =====================================================
        // FOCUS TEXTAREA
        // =====================================================

        setTimeout(function () {
            textarea.focus();
        }, 200);

    }


    // =====================================================
    // CLOSE AMEND MODAL
    // =====================================================

    function closeAmendModal() {

        const modal =
            document.getElementById('amendModal');

        if (modal) {

            modal.classList.add('hidden');

        }

    }


    // =====================================================
    // DIRECT APPROVAL MODAL
    // =====================================================

    function openDirectApproveModal(risId) {

        const modal =
            document.getElementById('directApproveModal');

        const iframe =
            document.getElementById('directApproveIframe');

        const form =
            document.getElementById('directApproveForm');

        if (!modal || !iframe || !form) {

            return;

        }


        // =====================================================
        // LOAD RIS FORM IN PREVIEW
        // =====================================================

        iframe.src =
            `/admin/procurement-review/ris/${risId}/print?ts=${Date.now()}`;


        // =====================================================
        // SET FORM ACTION URL
        // =====================================================

        form.action =
            `/admin/procurement-review/ris/${risId}/direct-approve`;


        // =====================================================
        // RESET FORM FIELDS
        // =====================================================

        document.getElementById('da_admin_name').value =
            '{{ Auth::user()->user_full_name ?? '' }}';

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('da_admin_date').value = today;


        // =====================================================
        // SHOW MODAL
        // =====================================================

        modal.classList.remove('hidden');

    }


    // =====================================================
    // CLOSE DIRECT APPROVAL MODAL
    // =====================================================

    function closeDirectApproveModal() {

        const modal =
            document.getElementById('directApproveModal');

        const iframe =
            document.getElementById('directApproveIframe');


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

                scaleRisPreviewToFit();

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

