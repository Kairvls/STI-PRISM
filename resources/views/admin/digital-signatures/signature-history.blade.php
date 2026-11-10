@extends('layouts.admin-layout')

@section('title', 'Signature History')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')

<div class="admin-page">
    <div id="signatureHistoryContentContainer">
        @include('admin.digital-signatures._signature-history-content')
    </div>

    @include('admin.partials.ris-preview-modal', [
        'iframeId' => 'signatureHistoryPreviewIframe',
        'closeFn' => 'closeSignatureHistoryPreviewModal',
        'printFn' => 'printSignatureHistoryPreview',
        'zIndex' => '50',
    ])
</div>

@include('admin.partials.view-mode-script')

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
    let currentDoc = '{{ $document ?? 'ris' }}';
    let currentSubmittedDate = @json($submittedDate ?? '');

    function syncSignatureHistoryDateFilterUi() {
        const btn = document.getElementById('signatureHistorySubmittedDateBtn');
        const input = document.getElementById('signatureHistorySubmittedDate');
        const clear = document.getElementById('signatureHistorySubmittedDateClear');
        const hasDate = !!currentSubmittedDate;

        if (input) input.value = currentSubmittedDate || '';
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
        if (clear) clear.classList.toggle('hidden', !hasDate);
    }

    function fetchSignatureHistoryData(search, page, filter) {

        const params = new URLSearchParams();

        params.set('doc', currentDoc || 'ris');
        params.set('filter', filter || currentFilter || 'all');

        if (search) {
            params.set('search', search);
        }

        if (currentSubmittedDate) {
            params.set('submitted', currentSubmittedDate);
        }

        if (page) {
            params.set('page', page);
        }

        const tableContainer =
            document.getElementById('signatureHistoryTableContainer');
        const requestUrl = new URL(
            '{{ \App\Support\AdminPortal::route('digital-signatures.history') }}?' + params.toString(),
            window.location.origin
        ).href;
        const cachedHtml = window.PrismListCache?.get?.(requestUrl) || null;

        if (!cachedHtml && tableContainer) {
            tableContainer.innerHTML = window.prismSkeleton('table', { rows: 6, label: 'Loading signature history' });
        }

        const applySignatureHistoryHtml = function (html) {
            const contentContainer =
                document.getElementById('signatureHistoryContentContainer');

            if (contentContainer) {
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const partial = parsed.querySelector('#signatureHistoryContent')
                    || parsed.querySelector('#signatureHistoryContentContainer');
                contentContainer.innerHTML = partial
                    ? (partial.id === 'signatureHistoryContent' ? partial.outerHTML : partial.innerHTML)
                    : html;
            }

            bindSignatureHistoryEventListeners();
            if (typeof window.bindPageCarousels === 'function') {
                window.bindPageCarousels();
            }
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            window.history.replaceState(
                { search: search, page: page, filter: currentFilter, submitted: currentSubmittedDate },
                '',
                requestUrl
            );
        };

        if (cachedHtml) {
            applySignatureHistoryHtml(cachedHtml);
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
                    window.PrismListCache?.set?.(requestUrl, html);
                    if (html !== cachedHtml) applySignatureHistoryHtml(html);
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
            window.PrismListCache?.set?.(requestUrl, html);
            applySignatureHistoryHtml(html);
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

    function applySignatureHistoryView(mode, animate) {
        if (typeof window.applyAdminViewMode === 'function') {
            window.applyAdminViewMode({
                mode: mode,
                tableId: 'signatureHistoryTableContainer',
                cardsId: 'signatureHistoryCardsContainer',
                buttonSelector: '.admin-history-view-btn',
                storageKey: 'admin_history_view',
                animate: animate !== false,
            });
            return;
        }
        var table = document.getElementById('signatureHistoryTableContainer');
        var cards = document.getElementById('signatureHistoryCardsContainer');
        var buttons = document.querySelectorAll('.admin-history-view-btn');
        if (!table || !cards) return;
        var useCards = mode === 'cards';
        table.classList.toggle('hidden', useCards);
        cards.classList.toggle('hidden', !useCards);
        buttons.forEach(function (btn) {
            var active = btn.getAttribute('data-view-mode') === mode;
            btn.classList.toggle('is-active', active);
        });
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

        const dateBtn = document.getElementById('signatureHistorySubmittedDateBtn');
        const dateInput = document.getElementById('signatureHistorySubmittedDate');
        const dateClear = document.getElementById('signatureHistorySubmittedDateClear');

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
                syncSignatureHistoryDateFilterUi();
                fetchSignatureHistoryData(currentSearch, null, currentFilter);
            });
        }

        if (dateClear) {
            dateClear.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                currentSubmittedDate = '';
                syncSignatureHistoryDateFilterUi();
                fetchSignatureHistoryData(currentSearch, null, currentFilter);
            });
        }

        syncSignatureHistoryDateFilterUi();

        document.querySelectorAll('[data-history-doc]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const next = this.getAttribute('data-history-doc');
                if (!next || next === currentDoc) return;
                currentDoc = next;
                currentFilter = 'all';
                fetchSignatureHistoryData(currentSearch, null, currentFilter);
            });
        });

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

        document.querySelectorAll('.admin-history-view-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                applySignatureHistoryView(btn.getAttribute('data-view-mode') || 'table', true);
            });
        });
        // Always reopen in table view when navigating / reloading content.
        applySignatureHistoryView('table', false);

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
            `{{ \App\Support\AdminPortal::url('procurement-review/ris') }}/${risId}/print?ts=${Date.now()}`;

        if (window.fillRisPreviewAttachments) {
            window.fillRisPreviewAttachments(risId);
        }

        modal.classList.remove('hidden');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('signatureHistoryPreviewIframe');
            }
        });
        iframe.onload = function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('signatureHistoryPreviewIframe');
            }
        };

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

        document.body.style.overflow = '';

    };


    // =====================================================
    // SCALE RIS IFRAME TO FIT VIEWPORT
    // =====================================================

    window.scaleSignatureHistoryPreviewToFit = function () {
        if (typeof window.scaleRisPreviewIframe === 'function') {
            window.scaleRisPreviewIframe('signatureHistoryPreviewIframe');
        }
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
