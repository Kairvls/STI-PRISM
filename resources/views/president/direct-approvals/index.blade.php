@extends('layouts.president-layout')

@section('title', 'Admin Direct Approvals')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <p class="text-sm leading-6 text-gray-500">
            Read-only records of RIS documents that Admin approved directly, including the reason and any proof attached.
        </p>
    </div>
</div>

<div class="mt-6 slide-up" style="animation-delay: 0.05s">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[220px]">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                id="directApprovalSearch"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by ID, Reference No., reason, or Admin..."
                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200"
                autocomplete="off"
            />
        </div>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4">
    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.1s">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Direct Approvals by Admin</h2>
                <p class="mt-1 text-xs text-gray-500">
                    These bypassed presidential signing. Use this module for oversight and audit only.
                </p>
            </div>

            <span id="directApprovalCount" class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-800 border border-slate-200">
                {{ $records->total() }} total
            </span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table id="directApprovalTable" class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS ID</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference No.</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved by</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reason</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Amount</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Actions</th>
                    </tr>
                </thead>
                <tbody id="directApprovalTableBody">
                    @include('president.direct-approvals._table', ['records' => $records])
                </tbody>
            </table>
        </div>

        @include('president.partials.table-word-export', [
            'target' => '#directApprovalTable',
            'filename' => 'president-admin-direct-approvals',
        ])

        @if ($records->hasPages())
            <div id="directApprovalPagination" class="mt-4 border-t border-gray-100 pt-4">
                {{ $records->links('pagination.president') }}
            </div>
        @endif
    </section>
</div>

{{-- Detail modal --}}
<div id="directApprovalDetailModal" class="fixed inset-0 z-[12000] hidden">
    <div
        class="absolute inset-0 flex items-center justify-center bg-slate-900/55 p-4 backdrop-blur-[2px]"
        onclick="if (event.target === this) closeDirectApprovalDetail()"
    >
        <div
            class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200/80"
            onclick="event.stopPropagation()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="directApprovalDetailTitle"
        >
            <button
                type="button"
                onclick="closeDirectApprovalDetail()"
                class="absolute right-2.5 top-2.5 z-10 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>

            <div class="border-b border-slate-100 px-5 pb-2.5 pr-11 pt-4">
                <h3 id="directApprovalDetailTitle" class="text-base font-semibold tracking-tight text-slate-900">
                    Direct approval details
                </h3>
                <p id="directApprovalDetailRef" class="mt-0.5 text-sm text-slate-500">—</p>
            </div>

            <div class="space-y-4 px-5 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Approved by Admin</p>
                    <p id="directApprovalDetailAdmin" class="mt-1 text-sm font-medium text-slate-900">—</p>
                    <p id="directApprovalDetailDate" class="mt-0.5 text-xs text-slate-500">—</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Reason</p>
                    <p id="directApprovalDetailReason" class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-slate-800">—</p>
                </div>
                <div id="directApprovalDetailProofWrap" class="hidden">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Proof of Approval</p>
                    <a
                        id="directApprovalDetailProof"
                        href="#"
                        class="mt-1.5 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-white"
                    >
                        <i data-lucide="paperclip" class="h-4 w-4 text-slate-500"></i>
                        <span id="directApprovalDetailProofName">Download proof</span>
                    </a>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Supporting document of RIS</p>
                    <div id="directApprovalDetailDocs" class="mt-1.5 flex flex-wrap gap-2">
                        <p class="text-xs text-slate-400">None attached to this RIS.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
                <button
                    type="button"
                    onclick="closeDirectApprovalDetail()"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Close
                </button>
                <button
                    type="button"
                    id="directApprovalDetailViewRis"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800"
                >
                    View RIS
                </button>
            </div>
        </div>
    </div>
</div>

<div id="risViewModal" class="fixed inset-0 z-[12000] hidden" data-fullscreen="0">
    <div id="risViewBackdrop" class="absolute inset-0 flex items-center justify-center bg-slate-900/75 p-3 sm:p-5" onclick="closeRisViewModal()">
        <div
            id="risViewPanel"
            class="relative overflow-hidden rounded-xl bg-white shadow-2xl"
            style="width: max-content; max-width: 98vw; height: fit-content; max-height: 96vh;"
            onclick="event.stopPropagation()"
        >
            <div id="risViewStage" class="ris-view-stage">
                <div id="risViewFrameWrap" style="line-height: 0; font-size: 0;">
                    <iframe
                        id="risViewIframe"
                        class="block bg-white"
                        scrolling="no"
                        style="width: 11in; height: 0; max-width: none; border: 0; overflow: hidden; transform-origin: top left;"
                        src="about:blank"
                        title="RIS Form Preview"
                    ></iframe>
                </div>
            </div>
            <div
                id="risViewAttachments"
                class="hidden border-t border-slate-100 bg-slate-50 px-3 py-2"
                style="line-height: normal; font-size: medium;"
            >
                <p class="m-0 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Supporting document</p>
                <div class="mt-1.5 flex flex-wrap gap-1.5" id="risViewAttachmentList"></div>
            </div>
            <div class="absolute right-3 top-3 z-10 flex items-center gap-2" style="line-height: normal; font-size: medium;">
                <button
                    type="button"
                    id="risViewFsBtn"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-500 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90"
                    onclick="toggleRisViewFullscreen()"
                    data-tip="Full screen"
                    title="Full screen"
                    aria-label="Full screen"
                >
                    <svg data-fs-icon="expand" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"></path>
                    </svg>
                    <svg data-fs-icon="collapse" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5v4M15 4h4v4M9 20H5v-4M15 20h4v-4"></path>
                    </svg>
                </button>
                <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-xl bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="printRis()" data-tip="Print RIS" aria-label="Print RIS">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                </button>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisViewModal()" data-tip="Close" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .status-admin-approved {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .row-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .icon-btn {
        display: inline-flex;
        height: 2rem;
        width: 2rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.65rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        transition: 0.15s ease;
    }
    .icon-btn:hover {
        background: #f8fafc;
        color: #0f172a;
    }
    .ris-doc-link {
        display: inline-block;
        max-width: 100%;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #0ea5e9;
        text-decoration: none;
        background: none;
        border: 0;
        padding: 0;
        border-radius: 0;
    }
    .ris-doc-link:hover {
        color: #0284c7;
        text-decoration: underline;
        background: none;
        border: 0;
    }
    .ris-doc-link svg {
        display: none;
    }

    #risViewModal.is-fullscreen #risViewBackdrop {
        padding: 0 !important;
        align-items: stretch !important;
        justify-content: stretch !important;
    }
    #risViewModal.is-fullscreen #risViewPanel {
        width: 100vw !important;
        max-width: 100vw !important;
        height: 100vh !important;
        max-height: 100vh !important;
        border-radius: 0 !important;
        display: flex;
        flex-direction: column;
    }
    #risViewModal.is-fullscreen #risViewStage {
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        background: #e5e7eb;
        padding: 12px 0;
    }
    #risViewModal.is-fullscreen #risViewFrameWrap {
        margin: 0 auto;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        background: #fff;
    }
    #risViewModal.is-fullscreen #risViewAttachments {
        flex-shrink: 0;
    }
</style>

<script>
(function () {
    var searchInput = document.getElementById('directApprovalSearch');
    var tableBody = document.getElementById('directApprovalTableBody');
    var countEl = document.getElementById('directApprovalCount');
    var paginationEl = document.getElementById('directApprovalPagination');
    var searchTimer = null;

    function refreshIcons() {
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
    }

    function fetchPage(url) {
        var target = url || (window.location.pathname + window.location.search);
        fetch(target, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (tableBody && data.table_html) {
                tableBody.innerHTML = data.table_html;
            }
            if (countEl && typeof data.total !== 'undefined') {
                countEl.textContent = data.total + ' total';
            }
            refreshIcons();
        })
        .catch(function () {});
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                var params = new URLSearchParams(window.location.search);
                var value = searchInput.value.trim();
                if (value) params.set('search', value);
                else params.delete('search');
                params.delete('page');
                var qs = params.toString();
                var next = window.location.pathname + (qs ? ('?' + qs) : '');
                window.history.replaceState({}, '', next);
                fetchPage(next);
            }, 300);
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('#directApprovalPagination a');
        if (!link) return;
        event.preventDefault();
        var href = link.getAttribute('href');
        if (!href) return;
        window.history.pushState({}, '', href);
        fetchPage(href);
        fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(function () {
                // Reload pagination via full page fragment if needed — keep simple: navigate
            });
        window.location.href = href;
    });

    window.openDirectApprovalDetail = function (payload) {
        var modal = document.getElementById('directApprovalDetailModal');
        if (!modal || !payload) return;
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        document.getElementById('directApprovalDetailRef').textContent =
            payload.reference || ('RIS #' + payload.risId);
        document.getElementById('directApprovalDetailAdmin').textContent =
            payload.adminName || 'Administrator';
        document.getElementById('directApprovalDetailDate').textContent =
            payload.dateLabel || '—';
        document.getElementById('directApprovalDetailReason').textContent =
            payload.reason || 'No reason recorded.';

        var proofWrap = document.getElementById('directApprovalDetailProofWrap');
        var proofLink = document.getElementById('directApprovalDetailProof');
        var proofName = document.getElementById('directApprovalDetailProofName');
        if (payload.proofUrl) {
            proofWrap.classList.remove('hidden');
            proofLink.href = payload.proofUrl;
            proofName.textContent = payload.proofName || 'Download proof';
        } else {
            proofWrap.classList.add('hidden');
            proofLink.href = '#';
        }

        var docsBox = document.getElementById('directApprovalDetailDocs');
        if (docsBox) {
            var docs = Array.isArray(payload.supportingDocuments) ? payload.supportingDocuments : [];
            if (!docs.length) {
                docsBox.innerHTML = '<p class="text-xs text-slate-400">None attached to this RIS.</p>';
            } else {
                docsBox.innerHTML = docs.map(function (file) {
                    var name = (file && file.name) ? String(file.name) : 'Attachment';
                    var url = (file && file.url) ? String(file.url) : '#';
                    return '<a href="' + url + '" target="_blank" rel="noopener" download class="ris-doc-link" title="Download ' + name.replace(/"/g, '&quot;') + '">'
                        + name.replace(/</g, '&lt;')
                        + '</a>';
                }).join('');
            }
        }

        var viewBtn = document.getElementById('directApprovalDetailViewRis');
        viewBtn.onclick = function () {
            closeDirectApprovalDetail();
            openRisViewModal(payload.risId);
        };

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        refreshIcons();
    };

    window.closeDirectApprovalDetail = function () {
        var modal = document.getElementById('directApprovalDetailModal');
        if (modal) modal.classList.add('hidden');
        var risModal = document.getElementById('risViewModal');
        if (!risModal || risModal.classList.contains('hidden')) {
            document.body.style.overflow = '';
        }
    };

    function syncRisViewFsButton(isFs) {
        var btn = document.getElementById('risViewFsBtn');
        if (!btn) return;
        var expand = btn.querySelector('[data-fs-icon="expand"]');
        var collapse = btn.querySelector('[data-fs-icon="collapse"]');
        if (expand) expand.classList.toggle('hidden', !!isFs);
        if (collapse) collapse.classList.toggle('hidden', !isFs);
        btn.title = isFs ? 'Exit full screen' : 'Full screen';
        btn.setAttribute('aria-label', isFs ? 'Exit full screen' : 'Full screen');
        btn.setAttribute('data-tip', isFs ? 'Exit full screen' : 'Full screen');
    }

    window.setRisViewFullscreen = function (enabled) {
        var modal = document.getElementById('risViewModal');
        if (!modal) return;
        var isFs = !!enabled;
        modal.classList.toggle('is-fullscreen', isFs);
        modal.dataset.fullscreen = isFs ? '1' : '0';
        syncRisViewFsButton(isFs);
        requestAnimationFrame(function () {
            fitRisViewIframe();
            requestAnimationFrame(fitRisViewIframe);
        });
    };

    window.toggleRisViewFullscreen = function () {
        var modal = document.getElementById('risViewModal');
        if (!modal) return;
        window.setRisViewFullscreen(modal.dataset.fullscreen !== '1');
    };

    function fitRisViewIframe() {
        var iframe = document.getElementById('risViewIframe');
        var panel = document.getElementById('risViewPanel');
        var wrap = document.getElementById('risViewFrameWrap');
        var stage = document.getElementById('risViewStage');
        var attach = document.getElementById('risViewAttachments');
        var modal = document.getElementById('risViewModal');
        if (!iframe) return;

        var isFs = modal && modal.dataset.fullscreen === '1';
        var docW = 11 * 96;
        var docH = 0;

        try {
            var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
            if (!doc || !doc.body) return;

            doc.documentElement.style.cssText = 'height:auto!important;min-height:0!important;overflow:hidden!important;margin:0!important;padding:0!important;';
            doc.body.style.cssText = 'height:auto!important;min-height:0!important;overflow:hidden!important;margin:0!important;padding:0!important;background:#fff;';

            var root = doc.querySelector('.ris-document') || doc.body;
            if (root && root.style) {
                root.style.minHeight = '0';
                root.style.height = 'auto';
            }

            docH = Math.ceil(
                Math.max(
                    root.scrollHeight || 0,
                    root.offsetHeight || 0,
                    root.getBoundingClientRect ? root.getBoundingClientRect().height : 0
                )
            );
        } catch (e) {
            docH = 720;
        }

        if (!docH || docH < 240) docH = 560;

        iframe.style.width = docW + 'px';
        iframe.style.height = docH + 'px';
        iframe.style.maxWidth = 'none';
        iframe.style.display = 'block';
        iframe.style.transformOrigin = 'top left';

        if (isFs) {
            var stageW = Math.max(320, (stage && stage.clientWidth) || window.innerWidth);
            var scale = Math.min(stageW / docW, 1.5);
            if (!isFinite(scale) || scale <= 0.05) scale = 1;
            var scaledW = Math.max(280, Math.floor(docW * scale));
            var scaledH = Math.max(200, Math.floor(docH * scale));

            iframe.style.transform = 'scale(' + scale + ')';
            if (wrap) {
                wrap.style.width = scaledW + 'px';
                wrap.style.height = scaledH + 'px';
                wrap.style.overflow = 'hidden';
                wrap.style.lineHeight = '0';
                wrap.style.margin = '0 auto';
            }
            if (panel) {
                panel.style.width = '100vw';
                panel.style.maxWidth = '100vw';
                panel.style.height = '100vh';
                panel.style.maxHeight = '100vh';
            }
        } else {
            iframe.style.transform = 'none';
            if (wrap) {
                wrap.style.width = '';
                wrap.style.height = '';
                wrap.style.overflow = '';
                wrap.style.margin = '';
            }
            if (panel) {
                var total = docH;
                if (attach && !attach.classList.contains('hidden')) {
                    total += attach.offsetHeight;
                }
                panel.style.width = '';
                panel.style.maxWidth = '98vw';
                panel.style.height = total + 'px';
                panel.style.maxHeight = '96vh';
            }
        }
    }

    function fillRisViewAttachments(risId) {
        var box = document.getElementById('risViewAttachments');
        var list = document.getElementById('risViewAttachmentList');
        if (!box || !list) return;
        box.classList.add('hidden');
        list.innerHTML = '';

        fetch('/president/ris/' + encodeURIComponent(risId) + '/details', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.ok ? res.json() : { attachments: [] }; })
            .then(function (data) {
                var files = data.attachments || [];
                if (!files.length) {
                    fitRisViewIframe();
                    return;
                }
                files.forEach(function (file) {
                    var link = document.createElement('a');
                    link.href = file.url;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.download = '';
                    link.className = 'ris-doc-link';
                    link.title = 'Download ' + (file.name || 'attachment');
                    link.textContent = file.name || 'Attachment';
                    list.appendChild(link);
                });
                box.classList.remove('hidden');
                requestAnimationFrame(fitRisViewIframe);
                setTimeout(fitRisViewIframe, 60);
            })
            .catch(function () {});
    }

    window.openRisViewModal = function (risId) {
        var modal = document.getElementById('risViewModal');
        var iframe = document.getElementById('risViewIframe');
        var panel = document.getElementById('risViewPanel');
        if (!modal || !iframe) return;
        window.currentDirectRisId = risId;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        window.setRisViewFullscreen(false);
        if (panel) panel.style.height = 'auto';

        iframe.onload = function () {
            fitRisViewIframe();
            setTimeout(fitRisViewIframe, 80);
            setTimeout(fitRisViewIframe, 250);
        };
        iframe.style.height = '0px';
        iframe.src = '/president/ris/' + encodeURIComponent(risId) + '/view?preview=1';
        fillRisViewAttachments(risId);
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        refreshIcons();
    };

    window.closeRisViewModal = function () {
        var modal = document.getElementById('risViewModal');
        var iframe = document.getElementById('risViewIframe');
        var panel = document.getElementById('risViewPanel');
        var box = document.getElementById('risViewAttachments');
        var list = document.getElementById('risViewAttachmentList');
        window.setRisViewFullscreen(false);
        if (iframe) {
            iframe.onload = null;
            iframe.src = 'about:blank';
            iframe.style.height = '0px';
            iframe.style.transform = 'none';
        }
        if (panel) panel.style.height = 'auto';
        if (list) list.innerHTML = '';
        if (box) box.classList.add('hidden');
        if (modal) modal.classList.add('hidden');
        var detail = document.getElementById('directApprovalDetailModal');
        if (!detail || detail.classList.contains('hidden')) {
            document.body.style.overflow = '';
        }
    };

    window.printRis = function () {
        var iframe = document.getElementById('risViewIframe');
        var src = iframe && iframe.getAttribute('src');
        var match = src && src.match(/\/president\/ris\/([^/?#]+)/);
        var risId = (match && match[1]) || window.currentDirectRisId || null;
        // Use dedicated landscape print layout — preview iframe is screen-sized and gets clipped
        if (risId && typeof window.printRisDocument === 'function') {
            window.printRisDocument(risId);
            return;
        }
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }
    };

    window.printRisDocument = function (risId) {
        if (!risId) return;
        var url = '/president/ris/' + encodeURIComponent(risId) + '/print?ts=' + Date.now();
        var iframe = document.getElementById('presidentRisPrintFrame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'presidentRisPrintFrame';
            iframe.setAttribute('title', 'Print RIS');
            iframe.setAttribute('aria-hidden', 'true');
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;opacity:0;pointer-events:none;';
            document.body.appendChild(iframe);
        }

        var printed = false;
        var tryPrint = function () {
            if (printed) return;
            if (!iframe.contentWindow) return;
            printed = true;
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) { /* ignore */ }
        };

        iframe.onload = function () {
            setTimeout(tryPrint, 300);
        };
        iframe.src = url;
    };

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var risModal = document.getElementById('risViewModal');
        if (risModal && !risModal.classList.contains('hidden') && risModal.dataset.fullscreen === '1') {
            event.preventDefault();
            window.setRisViewFullscreen(false);
            return;
        }
        closeDirectApprovalDetail();
        closeRisViewModal();
    });

    window.addEventListener('resize', function () {
        var risModal = document.getElementById('risViewModal');
        if (risModal && !risModal.classList.contains('hidden')) {
            fitRisViewIframe();
        }
    });
})();
</script>

@endsection
