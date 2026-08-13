@extends('layouts.admin-layout')

@section('title', 'Sign RIS')

@section('content')

<div class="space-y-6">


    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div>

        <h1 class="text-2xl font-bold text-gray-900">
            Sign RIS
        </h1>

        <p class="mt-1 text-sm text-gray-600">
            Review and co-sign President-approved Requisition Issue Slips.
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

    <div
        id="risPreviewModal"
        class="fixed inset-0 z-50 hidden"
    >

        <div
            class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
            onclick="window.closeSignRisPreviewModal()"
        >

            <div
                class="relative flex items-center justify-center"
                onclick="event.stopPropagation()"
            >

                <div id="signRisViewContainer" class="relative">

                    <iframe
                        id="signRisPreviewIframe"
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
                        onclick="window.printSignRisPreview()"
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
                        onclick="window.closeSignRisPreviewModal()"
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
    {{-- CO-SIGN MODAL (Two-column: RIS preview + Co-sign form) --}}
    {{-- ===================================================== --}}

    <div
        id="coSignModal"
        class="fixed inset-0 z-50 hidden"
    >

        <div
            class="flex h-screen items-center justify-center bg-black/60 p-2 backdrop-blur-sm"
            onclick="closeCoSignModal()"
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
                            id="coSignPreviewIframe"
                            class="bg-white shadow-md"
                            style="width: 100%; height: 75vh; border: 1px solid #e5e7eb;"
                            src="about:blank"
                            title="RIS Form Preview"
                        ></iframe>
                    </div>

                </div>

                {{-- RIGHT: CO-SIGN FORM --}}

                <div class="w-[380px] overflow-hidden rounded-2xl bg-white shadow-2xl">

                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-lg font-bold text-gray-900">Co-sign RIS</h3>
                        <p class="mt-1 text-sm text-gray-500">Sign to co-sign this President-approved RIS.</p>
                    </div>

                    <form id="coSignForm" method="POST" action="{{ route('admin.digital-signatures.ris.decide') }}">
                        @csrf

                        <div class="space-y-5 px-5 py-5">

                            <input type="hidden" name="target_id" id="coSignTargetId" value="" />
                            <input type="hidden" name="decision" value="Approved" />

                            {{-- Admin Name --}}

                            <div>
                                <label for="coSignAdminName" class="block text-sm font-medium text-gray-700">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="coSignAdminName"
                                    name="admin_name"
                                    required
                                    placeholder="Enter your full name"
                                    title="Enter the name to display in the Issued by section"
                                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                                >
                            </div>

                            {{-- Date --}}

                            <div>
                                <label for="coSignAdminDate" class="block text-sm font-medium text-gray-700">
                                    Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="coSignAdminDate"
                                    name="admin_date"
                                    required
                                    value="{{ date('Y-m-d') }}"
                                    title="Select the date of co-sign"
                                    class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-400 focus:ring-2 focus:ring-gray-100"
                                >
                            </div>

                            {{-- Signature Display (visual only) --}}

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Digital Signature (display only)
                                </label>
                                <div class="mt-2 rounded-lg border border-gray-200 bg-white p-2">
                                    <canvas
                                        id="coSignCanvas"
                                        width="520"
                                        height="150"
                                        class="w-full rounded-md border border-gray-100 bg-white"
                                        style="touch-action: none;"
                                    ></canvas>

                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <button type="button" class="rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 border border-slate-200 hover:bg-slate-100" onclick="clearCoSignSignature()">
                                            Clear
                                        </button>
                                        <span id="coSignSignatureStatus" class="text-xs text-slate-500">Draw your signature</span>
                                    </div>

                                    <div class="mt-2 hidden" id="coSignSignatureHelpText">
                                        <p class="text-sm text-green-600 font-medium">✓ Signature captured and ready</p>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Optional: draw a signature for visual display. The name above will be used as the primary signature.</p>
                            </div>

                            {{-- Info Box --}}

                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                                <div class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-xs text-amber-800">
                                        This RIS has been approved by the President. Your co-sign will finalize the document.
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-4">
                            <button
                                type="button"
                                onclick="closeCoSignModal()"
                                title="Cancel co-sign"
                                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                title="Co-sign this RIS"
                                class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                            >
                                Confirm Co-sign
                            </button>
                        </div>

                    </form>

                </div>

                {{-- Close button --}}

                <button
                    type="button"
                    class="absolute -top-3 -right-3 flex h-8 w-8 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-md transition-all duration-200 hover:bg-slate-100 hover:text-slate-900"
                    onclick="closeCoSignModal()"
                    title="Close co-sign modal"
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
{{-- SIGN RIS JAVASCRIPT --}}
{{-- (SEARCH, FILTERS, PAGINATION & PREVIEW & CO-SIGN) --}}
{{-- ===================================================== --}}

<script>


    // =====================================================
    // SIGN RIS AJAX STATE
    // =====================================================

    let signRisSearchTimer = null;
    let currentFilter = '{{ $filter ?? 'all' }}';
    let currentSearch = '{{ $search ?? '' }}';


    // =====================================================
    // FETCH SIGN RIS DATA VIA AJAX
    // =====================================================

    function fetchSignRisData(filter, search, page) {

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

    function bindSignRisEventListeners() {

        // =====================================================
        // SEARCH INPUT
        // =====================================================

        const searchInput =
            document.getElementById('signRisLiveSearch');

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                function () {

                    clearTimeout(signRisSearchTimer);

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

        // =====================================================
        // FILTER BUTTONS
        // =====================================================

        const filterButtons =
            document.querySelectorAll('.sign-ris-filter-btn');

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

                    fetchSignRisData(
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

                    fetchSignRisData(
                        currentFilter,
                        currentSearch,
                        page
                    );

                }
            );

        });

    }


    // =====================================================
    // OPEN SIGN RIS PREVIEW
    // =====================================================

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
        setTimeout(window.scaleSignRisPreviewToFit, 100);

    };


    // =====================================================
    // CLOSE SIGN RIS PREVIEW
    // =====================================================

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


    // =====================================================
    // SCALE RIS IFRAME TO FIT VIEWPORT
    // =====================================================

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


    // =====================================================
    // PRINT RIS FORM
    // =====================================================

    window.printSignRisPreview = function () {

        const iframe =
            document.getElementById('signRisPreviewIframe');

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
                window.scaleSignRisPreviewToFit();
            }

        }
    );


    // =====================================================
    // CO-SIGN MODAL
    // =====================================================

    function openCoSignModal(risId) {

        const modal =
            document.getElementById('coSignModal');

        const iframe =
            document.getElementById('coSignPreviewIframe');

        const form =
            document.getElementById('coSignForm');

        const targetId =
            document.getElementById('coSignTargetId');

        if (!modal || !iframe || !form || !targetId) {
            return;
        }

        // Load RIS form in preview
        iframe.src =
            `/admin/procurement-review/ris/${risId}/print?ts=${Date.now()}`;

        // Set form values
        targetId.value = risId;

        // Set default name from authenticated user
        const nameInput = document.getElementById('coSignAdminName');
        if (nameInput) {
            nameInput.value = '{{ Auth::user()->user_full_name ?? '' }}';
        }

        // Set default date to today
        const dateInput = document.getElementById('coSignAdminDate');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.value = today;
        }

        // Reset signature canvas (display only)
        const canvas = document.getElementById('coSignCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        const helpText = document.getElementById('coSignSignatureHelpText');
        if (helpText) helpText.classList.add('hidden');

        const status = document.getElementById('coSignSignatureStatus');
        if (status) {
            status.textContent = 'Draw your signature (optional)';
            status.className = 'text-xs text-slate-500';
        }

        // Show modal
        modal.classList.remove('hidden');

    }


    // =====================================================
    // CLOSE CO-SIGN MODAL
    // =====================================================

    function closeCoSignModal() {

        const modal =
            document.getElementById('coSignModal');

        const iframe =
            document.getElementById('coSignPreviewIframe');

        if (iframe) {
            iframe.src = 'about:blank';
        }

        if (modal) {
            modal.classList.add('hidden');
        }

    }


    // =====================================================
    // CO-SIGN SIGNATURE CANVAS
    // =====================================================

    (function initCoSignCanvas() {

        const canvas = document.getElementById('coSignCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1f2937';

        let drawing = false;
        let lastX = 0;
        let lastY = 0;

        function getPos(evt) {
            const rect = canvas.getBoundingClientRect();
            const clientX = evt.touches ? evt.touches[0].clientX : evt.clientX;
            const clientY = evt.touches ? evt.touches[0].clientY : evt.clientY;
            const x = (clientX - rect.left) * (canvas.width / rect.width);
            const y = (clientY - rect.top) * (canvas.height / rect.height);
            return { x, y };
        }

        function start(evt) {
            drawing = true;
            const p = getPos(evt);
            lastX = p.x;
            lastY = p.y;
        }

        function move(evt) {
            if (!drawing) return;
            const p = getPos(evt);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            lastX = p.x;
            lastY = p.y;
        }

        function end() {
            drawing = false;
            captureCoSignSignature();
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);

        canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', (e) => { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', end);

    })();


    // =====================================================
    // CLEAR CO-SIGN SIGNATURE
    // =====================================================

    function clearCoSignSignature() {

        const canvas = document.getElementById('coSignCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const helpText = document.getElementById('coSignSignatureHelpText');
        if (helpText) helpText.classList.add('hidden');

        const status = document.getElementById('coSignSignatureStatus');
        if (status) {
            status.textContent = 'Draw your signature (optional)';
            status.className = 'text-xs text-slate-500';
        }

    }


    // =====================================================
    // CAPTURE CO-SIGN SIGNATURE
    // =====================================================

    function captureCoSignSignature() {

        const canvas = document.getElementById('coSignCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        let hasDrawn = false;

        for (let i = 3; i < data.length; i += 4) {
            if (data[i] > 128) {
                hasDrawn = true;
                break;
            }
        }

        if (!hasDrawn) return;

        const helpText = document.getElementById('coSignSignatureHelpText');
        if (helpText) helpText.classList.remove('hidden');

        const status = document.getElementById('coSignSignatureStatus');
        if (status) {
            status.textContent = '✓ Signature drawn (display only)';
            status.className = 'text-xs text-green-600 font-medium';
        }

        return true;

    }


    {{-- Canvas signature validation removed -- now using Name + Date fields --}}


    // =====================================================
    // CLOSE MODALS WITH ESCAPE KEY
    // =====================================================

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeSignRisPreviewModal();
                closeCoSignModal();
            }

        }
    );


    // =====================================================
    // INITIALISE EVENT LISTENERS ON PAGE LOAD
    // =====================================================

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            bindSignRisEventListeners();
        }
    );


</script>

@endsection