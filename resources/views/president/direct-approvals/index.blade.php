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

<div id="risViewModal" class="fixed inset-0 z-[12000] hidden">
    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/75 p-3 sm:p-5" onclick="closeRisViewModal()">
        <div
            id="risViewPanel"
            class="relative overflow-hidden rounded-xl bg-white shadow-2xl"
            style="width: max-content; max-width: 98vw; height: fit-content; max-height: 96vh;"
            onclick="event.stopPropagation()"
        >
            <div id="risViewFrameWrap" style="line-height: 0; font-size: 0;">
                <iframe
                    id="risViewIframe"
                    class="block bg-white"
                    scrolling="no"
                    style="width: 11in; height: 0; max-width: 100%; border: 0; overflow: hidden;"
                    src="about:blank"
                    title="RIS Form Preview"
                ></iframe>
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
                <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-xl bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="printRis()" data-tip="Print RIS" aria-label="Print RIS">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span class="ml-1.5">Print</span>
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
        display: inline-flex;
        max-width: 100%;
        align-items: center;
        gap: 0.4rem;
        border-radius: 0.7rem;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        padding: 0.4rem 0.7rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #3b82f6;
        text-decoration: none;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .ris-doc-link:hover {
        background: #dbeafe;
        border-color: #93c5fd;
        color: #2563eb;
        text-decoration: underline;
    }
    .ris-doc-link svg {
        width: 0.9rem;
        height: 0.9rem;
        flex-shrink: 0;
        color: #60a5fa;
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
                        + '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>'
                        + '<span class="truncate">' + name.replace(/</g, '&lt;') + '</span>'
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

    function fitRisViewIframe() {
        var iframe = document.getElementById('risViewIframe');
        var panel = document.getElementById('risViewPanel');
        var attach = document.getElementById('risViewAttachments');
        if (!iframe) return;
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

            var height = Math.ceil(
                Math.max(
                    root.scrollHeight || 0,
                    root.offsetHeight || 0,
                    root.getBoundingClientRect ? root.getBoundingClientRect().height : 0
                )
            );

            if (height > 0) {
                iframe.style.height = height + 'px';
            }

            // Lock panel to exact content height so no empty white band remains under docs.
            if (panel) {
                var total = iframe.offsetHeight;
                if (attach && !attach.classList.contains('hidden')) {
                    total += attach.offsetHeight;
                }
                panel.style.height = total + 'px';
            }
        } catch (e) {
            iframe.style.height = '720px';
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
                    link.innerHTML =
                        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>'
                        + '<span class="truncate"></span>';
                    var label = link.querySelector('span');
                    if (label) label.textContent = file.name || 'Attachment';
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

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

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
        if (iframe) {
            iframe.onload = null;
            iframe.src = 'about:blank';
            iframe.style.height = '0px';
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
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }
    };

    window.printRisDocument = function (risId) {
        window.open('/president/ris/' + encodeURIComponent(risId) + '/print', '_blank');
    };

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeDirectApprovalDetail();
        closeRisViewModal();
    });
})();
</script>

@endsection
