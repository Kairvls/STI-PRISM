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


    {{-- Direct approve / forward / amend modal lives on this page --}}
    @include('admin.procurement-review._direct-approve-modal')

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
    let risFilterFetchTimer = null;
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
            // Force layout so the next transition starts cleanly.
            void thumb.offsetWidth;
            thumb.style.transition = previous || 'transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1)';
            return;
        }

        thumb.style.width = w + 'px';
        thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
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
            );

        });

        // Snap into place after AJAX rebuild (no animation).
        updateRisFilterSlider(currentFilter, false);


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


        // =====================================================
        // SHOW MODAL
        // =====================================================

        modal.classList.remove('hidden');
        modal.style.display = 'block';

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

    };


    // =====================================================
    // SCALE RIS IFRAME TO FIT VIEWPORT
    // =====================================================

    window.scaleRisPreviewToFit = function () {

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
    // DIRECT APPROVAL MODAL (physical RIS form)
    // =====================================================

    window.openDirectApproveModal = function(risId, mode) {
        var modal = document.getElementById('directApproveModal');
        var body = document.getElementById('directApproveModalBody');
        var title = document.getElementById('directApproveModalTitle');
        var subtitle = document.getElementById('directApproveModalSubtitle');
        if (!modal || !body) return;

        var actionMode = 'direct';
        if (mode === 'forward') actionMode = 'forward';
        if (mode === 'amend') actionMode = 'amend';
        if (title) {
            title.textContent = actionMode === 'forward'
                ? 'Forward to President'
                : (actionMode === 'amend' ? 'Return for Amendment' : 'Admin Approval');
        }
        if (subtitle) {
            subtitle.textContent = actionMode === 'forward'
                ? 'Review the RIS form, then forward it to the President. Issued by is signed later on Sign RIS.'
                : (actionMode === 'amend'
                    ? 'Sign Issued by on the RIS form, then enter amendment remarks.'
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
            body.innerHTML = '<div class="px-6 py-16 text-center text-sm text-rose-600">Failed to load RIS form. Please try again.</div>';
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

