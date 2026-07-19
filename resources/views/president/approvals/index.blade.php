@extends('layouts.president-layout')

@section('title', 'RIS Management')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">RIS Management</h1>
    <p class="mt-1 text-sm leading-6 text-gray-500">Review and manage Requisition Issue Slips — approve or reject forwarded requests.</p>
    </div>
</div>

{{-- ============================== --}}
{{-- SUMMARY CARDS --}}
{{-- ============================== --}}
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5 slide-up summary-card" style="animation-delay: 0s">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total RIS</p>
        <p class="mt-2 text-3xl font-bold text-gray-900 count-up" data-target="{{ $totalRisCount ?? 0 }}">0</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 slide-up summary-card" style="animation-delay: 0.05s">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pending RIS</p>
        <p class="mt-2 text-3xl font-bold text-amber-600 count-up" data-target="{{ $totalPendingRis ?? 0 }}">0</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 slide-up summary-card" style="animation-delay: 0.1s">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Approved RIS</p>
        <p class="mt-2 text-3xl font-bold text-emerald-600 count-up" data-target="{{ $totalApprovedRis ?? 0 }}">0</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 slide-up summary-card" style="animation-delay: 0.15s">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Rejected RIS</p>
        <p class="mt-2 text-3xl font-bold text-rose-600 count-up" data-target="{{ $totalRejectedRis ?? 0 }}">0</p>
    </div>
</div>

{{-- ============================== --}}
{{-- SEARCH & STATUS FILTER BUTTONS --}}
{{-- ============================== --}}
<div class="mt-6 slide-up" style="animation-delay: 0.2s">
    <div class="flex flex-wrap items-center gap-3">
        {{-- Live Search --}}
        <div class="relative flex-1 min-w-[220px]">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                id="liveSearch"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by ID, Reference No., or Purpose..."
                class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-amber-100 transition-all duration-200"
                autocomplete="off"
            />
        </div>

        {{-- Status Filter Buttons --}}
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="status-filter-btn inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-all duration-200 active:scale-95 {{ !request('status') ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                data-status=""
            >
                All
            </button>
            <button
                type="button"
                class="status-filter-btn inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-all duration-200 active:scale-95 {{ request('status') == 'Pending' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                data-status="Pending"
            >
                Pending
            </button>
            <button
                type="button"
                class="status-filter-btn inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-all duration-200 active:scale-95 {{ request('status') == 'Approved' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                data-status="Approved"
            >
                Approved
            </button>
            <button
                type="button"
                class="status-filter-btn inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-all duration-200 active:scale-95 {{ request('status') == 'Rejected' ? 'bg-rose-500 text-white border-rose-500' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}"
                data-status="Rejected"
            >
                Rejected
            </button>
        </div>

        {{-- Hidden clear link --}}
        <a id="clearFiltersLink" href="{{ route('president.approvals') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition-all duration-200 hover:bg-gray-50 active:scale-95 {{ request('search') || request('status') ? '' : 'hidden' }}">
            Clear
        </a>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4">

    {{-- ============================== --}}
    {{-- RIS APPROVALS TABLE --}}
    {{-- ============================== --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.25s">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">RIS Records</h2>
                <p class="mt-1 text-xs text-gray-500">Requisition Issue Slips forwarded for your decision</p>
            </div>
            <span id="risTotalCount" class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 border border-amber-200">
                {{ $pendingRis->total() }} total
            </span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS ID</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference No.</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Purpose</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Status</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Total Amount</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Actions</th>
                    </tr>
                </thead>
                <tbody id="risTableBody">
                    @include('president.approvals._table', ['pendingRis' => $pendingRis])
                </tbody>
            </table>
        </div>

        {{-- ============================== --}}
        {{-- PAGINATION --}}
        {{-- ============================== --}}
        @if ($pendingRis->hasPages())
            <div id="risPagination" class="mt-4 border-t border-gray-100 pt-4">
                {{ $pendingRis->links() }}
            </div>
        @endif
    </section>

</div>

{{-- ============================== --}}
{{-- RIS VIEWER --}}
{{-- ============================== --}}
<div id="risFormModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 backdroop-overlay" onclick="closeRisFormModal()">
        <div class="relative flex items-center justify-center" onclick="event.stopPropagation()">
            <div id="risViewContainer" class="relative">
                <iframe id="risFormIframe" class="bg-white shadow-2xl" style="width: 11in; height: 8.5in; border: 1px solid #e5e7eb; transform-origin: center center;" src="about:blank"></iframe>
            </div>
            <div class="fixed top-4 right-4 z-10 flex items-center gap-2">
                <button type="button" class="print-btn inline-flex h-9 items-center justify-center rounded-lg bg-white border border-gray-200 px-3 text-xs font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="printRis()" title="Print RIS">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span class="ml-1.5">Print</span>
                </button>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white border border-gray-200 text-slate-400 shadow-sm transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisFormModal()" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- DECISION MODAL --}}
{{-- ============================== --}}
<div id="decisionModal" class="fixed inset-0 z-50 hidden">
    <div class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px] modal-overlay" onclick="closeDecisionModal()">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] modal-content" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Decision</h3>
                        <p id="decisionModalSubtitle" class="mt-1 text-sm text-slate-600">Approve or reject the selected RIS</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeDecisionModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <form id="decisionForm" method="POST" action="">
                @csrf
                <div class="px-6 py-6">
                    <input type="hidden" name="target_type" id="targetType" value="" />
                    <input type="hidden" name="target_id" id="targetId" value="" />
                    <input type="hidden" name="decision" id="targetDecision" value="" />
                    <input type="hidden" name="signature_data" id="signatureData" value="" />
                    <input type="hidden" name="signature_used" id="signatureUsed" value="0" />

                    <div class="space-y-6">
                        {{-- Signature area — shown only when approving --}}
                        <div id="signatureBlock" class="hidden">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Digital Signature</label>
                                <p class="mt-1 text-xs text-slate-500">Sign using your mouse or touch to approve this RIS.</p>
                                <div class="mt-3 rounded-lg border border-gray-200 bg-white p-2">
                                    <canvas
                                        id="signatureCanvas"
                                        width="520"
                                        height="180"
                                        class="w-full rounded-md border border-gray-100"
                                        style="touch-action: none;"
                                    ></canvas>

                                    <div class="mt-3">
                                        <button type="button" class="action-btn rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 border border-slate-200 transition-all duration-200 hover:bg-slate-100 active:scale-95" onclick="clearSignature()">
                                            Clear Signature
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Remarks below the signature area --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Remarks (optional)</label>
                            <textarea name="remarks" rows="3" placeholder="Add remarks for your decision..." class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-4 focus:ring-amber-100 transition-all duration-200 resize-none"></textarea>
                        </div>

                        <div class="text-xs text-slate-500">
                            This will update the RIS status accordingly.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <button type="button" class="action-btn rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-slate-100 hover:text-slate-950 active:scale-95" onclick="closeDecisionModal()">Cancel</button>
                    <button
                        type="button"
                        id="approveBtn"
                        class="action-btn rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-emerald-700 active:scale-95"
                        onclick="submitDecision('Approved')">
                        <i data-lucide="check" class="inline h-4 w-4 -ml-1 mr-1.5"></i>
                        Approve
                    </button>
                    <button
                        type="button"
                        id="rejectBtn"
                        class="action-btn rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-rose-700 active:scale-95"
                        onclick="submitDecision('Rejected')">
                        <i data-lucide="x" class="inline h-4 w-4 -ml-1 mr-1.5"></i>
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* ======================================
       ANIMATIONS
    ====================================== */

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    @keyframes overlayIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .fade-in {
        animation: fadeIn 0.4s ease-out forwards;
    }

    .slide-up {
        opacity: 0;
        animation: slideUp 0.5s ease-out forwards;
    }

    .modal-overlay {
        animation: overlayIn 0.2s ease-out forwards;
    }

    .modal-content {
        animation: modalIn 0.25s ease-out forwards;
    }

    .approval-row {
        transition: background-color 0.2s ease;
    }

    .approval-row:hover {
        background-color: rgba(254, 252, 232, 0.4);
    }

    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:active {
        transform: scale(0.95);
    }

    .summary-card {
        transition: all 0.25s ease;
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .status-filter-btn {
        transition: all 0.2s ease;
    }

    /* Smooth table fade on refresh */
    #risTableBody {
        transition: opacity 0.2s ease;
    }

    #risTableBody.updating {
        opacity: 0.5;
    }

    .backdroop-overlay {
        animation: overlayIn 0.2s ease-out forwards;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        #risFormModal, #risFormModal * {
            visibility: visible;
        }
        #risFormModal {
            position: static;
            background: white;
            backdrop-filter: none;
        }
        #risViewContainer {
            transform: scale(1) !important;
        }
        .print-btn {
            display: none !important;
        }
    }
</style>

<script>
    // =====================================================
    // COUNT-UP ANIMATION
    // =====================================================
    function animateCountUp() {
        document.querySelectorAll('.count-up').forEach(el => {
            const target = parseInt(el.getAttribute('data-target'));
            const duration = 800;
            const start = performance.now();

            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                // Ease out cubic
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(eased * target);
                el.textContent = current.toLocaleString();
                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = target.toLocaleString();
                }
            }

            requestAnimationFrame(update);
        });
    }

    // Run count-up after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', animateCountUp);
    } else {
        animateCountUp();
    }

    // =====================================================
    // LIVE SEARCH + STATUS FILTER (AJAX)
    // =====================================================
    let searchTimeout = null;
    const searchInput = document.getElementById('liveSearch');
    const filterButtons = document.querySelectorAll('.status-filter-btn');
    const clearLink = document.getElementById('clearFiltersLink');

    // Track current active status ('' = All)
    let activeStatus = '';

    function getActiveStatus() {
        for (const btn of filterButtons) {
            const status = btn.getAttribute('data-status');
            if (status === activeStatus) {
                return status;
            }
        }
        return '';
    }

    function fetchTableData(page) {
        const search = searchInput ? searchInput.value : '';
        const status = getActiveStatus();
        page = page || 1;

        // Show/hide clear link
        if (clearLink) {
            if (search || status) {
                clearLink.classList.remove('hidden');
            } else {
                clearLink.classList.add('hidden');
            }
        }

        const tbody = document.getElementById('risTableBody');
        const pagination = document.getElementById('risPagination');
        const totalSpan = document.getElementById('risTotalCount');

        if (tbody) tbody.classList.add('updating');

        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (status) params.set('status', status);
        params.set('page', page);

        fetch(`{{ route('president.approvals') }}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (tbody) {
                tbody.innerHTML = data.table_html;
                tbody.classList.remove('updating');
            }
            if (totalSpan) {
                totalSpan.textContent = data.total + ' total';
            }
            if (pagination) {
                if (data.last_page > 1) {
                    let html = buildPagination(data);
                    pagination.innerHTML = html;
                    pagination.classList.remove('hidden');
                } else {
                    pagination.innerHTML = '';
                }
            }
            if (window.lucide) {
                lucide.createIcons();
            }
        })
        .catch(err => {
            console.error('Search/filter error:', err);
            if (tbody) tbody.classList.remove('updating');
        });
    }

    function buildPagination(data) {
        let html = '<nav class="flex items-center justify-between"><div class="text-sm text-gray-500">Showing ' + data.from + ' to ' + data.to + ' of ' + data.total + ' results</div><ul class="flex items-center gap-1">';

        const prevDisabled = data.current_page <= 1;
        html += '<li class="' + (prevDisabled ? 'opacity-50 pointer-events-none' : '') + '"><button onclick="goToPage(' + (data.current_page - 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50">&laquo;</button></li>';

        for (let i = 1; i <= data.last_page; i++) {
            if (i === data.current_page) {
                html += '<li><span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-sm font-semibold text-white">' + i + '</span></li>';
            } else {
                html += '<li><button onclick="goToPage(' + i + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50">' + i + '</button></li>';
            }
        }

        const nextDisabled = data.current_page >= data.last_page;
        html += '<li class="' + (nextDisabled ? 'opacity-50 pointer-events-none' : '') + '"><button onclick="goToPage(' + (data.current_page + 1) + ')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm text-gray-600 hover:bg-gray-50">&raquo;</button></li>';

        html += '</ul></nav>';
        return html;
    }

    function goToPage(page) {
        fetchTableData(page);
    }

    // Live search with debounce — always resets to page 1
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                fetchTableData(1);
            }, 300);
        });
    }

    // Status filter buttons
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const status = this.getAttribute('data-status');

            // Update active status tracker
            activeStatus = status;

            // Update button visual states
            filterButtons.forEach(b => {
                const btnStatus = b.getAttribute('data-status');
                let classes = 'status-filter-btn inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-all duration-200 active:scale-95';

                if (btnStatus === activeStatus) {
                    if (btnStatus === '') {
                        classes += ' bg-gray-900 text-white border-gray-900';
                    } else if (btnStatus === 'Pending') {
                        classes += ' bg-amber-500 text-white border-amber-500';
                    } else if (btnStatus === 'Approved') {
                        classes += ' bg-emerald-500 text-white border-emerald-500';
                    } else if (btnStatus === 'Rejected') {
                        classes += ' bg-rose-500 text-white border-rose-500';
                    }
                } else {
                    classes += ' bg-white text-gray-600 border-gray-200 hover:bg-gray-50';
                }
                b.className = classes;
            });

            // Fetch with page 1
            fetchTableData(1);
        });
    });

    // Clear button — resets everything via AJAX
    if (clearLink) {
        clearLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Clear search input
            if (searchInput) {
                searchInput.value = '';
            }

            // Reset active status to All
            activeStatus = '';

            // Reset all buttons to inactive, then highlight All
            filterButtons.forEach(b => {
                const btnStatus = b.getAttribute('data-status');
                let classes = 'status-filter-btn inline-flex h-10 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition-all duration-200 active:scale-95';
                if (btnStatus === '') {
                    classes += ' bg-gray-900 text-white border-gray-900';
                } else {
                    classes += ' bg-white text-gray-600 border-gray-200 hover:bg-gray-50';
                }
                b.className = classes;
            });

            // Hide clear link
            clearLink.classList.add('hidden');

            // Fetch with page 1, no filters
            fetchTableData(1);
        });
    }

    // =====================================================
    // DECISION MODAL
    // =====================================================
    function openDecisionModal(type, id, presetDecision) {
        const modal = document.getElementById('decisionModal');
        const targetType = document.getElementById('targetType');
        const targetId = document.getElementById('targetId');
        const subtitle = document.getElementById('decisionModalSubtitle');
        const decisionForm = document.getElementById('decisionForm');
        const targetDecision = document.getElementById('targetDecision');
        const signatureBlock = document.getElementById('signatureBlock');
        const signatureCanvas = document.getElementById('signatureCanvas');
        const signatureDataInput = document.getElementById('signatureData');
        const signatureUsedInput = document.getElementById('signatureUsed');
        const remarksField = document.querySelector('textarea[name="remarks"]');

        // Reset form
        if (signatureDataInput) signatureDataInput.value = '';
        if (signatureUsedInput) signatureUsedInput.value = '0';
        if (remarksField) remarksField.value = '';
        if (signatureCanvas) {
            const ctx = signatureCanvas.getContext('2d');
            ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        }

        targetType.value = type;
        targetId.value = id;
        targetDecision.value = presetDecision || '';
        subtitle.textContent = `RIS #${id}`;
        decisionForm.action = `/president/approvals/ris/decide`;

        // Show signature block only when approving
        if (signatureBlock) {
            const isApproved = (presetDecision || '').toLowerCase() === 'approved';
            signatureBlock.classList.toggle('hidden', !isApproved);
        }

        modal.classList.remove('hidden');

        if (window.lucide) {
            lucide.createIcons();
        }
    }

    function closeDecisionModal() {
        document.getElementById('decisionModal').classList.add('hidden');
    }

    function submitDecision(decision) {
        const targetDecision = document.getElementById('targetDecision');
        const signatureDataInput = document.getElementById('signatureData');
        const signatureUsedInput = document.getElementById('signatureUsed');
        const form = document.getElementById('decisionForm');

        targetDecision.value = decision;

        // If approving, require signature
        if (decision === 'Approved') {
            // Auto-capture whatever is on the canvas
            const canvas = document.getElementById('signatureCanvas');
            if (canvas) {
                const dataUrl = canvas.toDataURL('image/png');
                // Check if canvas has any drawing (not blank)
                const ctx = canvas.getContext('2d');
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const pixels = imageData.data;
                let hasDrawing = false;
                for (let i = 3; i < pixels.length; i += 4) {
                    if (pixels[i] > 0) {
                        hasDrawing = true;
                        break;
                    }
                }
                if (!hasDrawing) {
                    alert('Please sign the RIS before approving.');
                    return;
                }
                signatureDataInput.value = dataUrl;
                signatureUsedInput.value = '1';
            }
        }

        form.submit();
    }

    // =====================================================
    // RIS FORM PREVIEW MODAL
    // =====================================================
    function openRisFormModal(risId) {
        const modal = document.getElementById('risFormModal');
        const iframe = document.getElementById('risFormIframe');
        if (!modal || !iframe) return;
        iframe.src = `/president/ris/${risId}/view?ts=${Date.now()}`;
        modal.classList.remove('hidden');
        scaleRisToFit();
    }

    function closeRisFormModal() {
        const modal = document.getElementById('risFormModal');
        const iframe = document.getElementById('risFormIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }

    function scaleRisToFit() {
        const iframe = document.getElementById('risFormIframe');
        if (!iframe) return;

        // Document dimensions in inches (landscape)
        const docWidthInches = 11;
        const docHeightInches = 8.5;
        
        // Convert to pixels (96 DPI)
        const docWidthPx = docWidthInches * 96;
        const docHeightPx = docHeightInches * 96;

        // Calculate available viewport (with margins)
        const viewportWidth = window.innerWidth - 64;
        const viewportHeight = window.innerHeight - 64;

        // Calculate scale to fit
        const scaleX = viewportWidth / docWidthPx;
        const scaleY = viewportHeight / docHeightPx;
        const scale = Math.min(scaleX, scaleY, 1);

        // Apply CSS transform to the iframe
        iframe.style.transform = `scale(${scale})`;
        iframe.style.width = docWidthPx + 'px';
        iframe.style.height = docHeightPx + 'px';
    }

    window.addEventListener('resize', function() {
        const modal = document.getElementById('risFormModal');
        if (modal && !modal.classList.contains('hidden')) {
            scaleRisToFit();
        }
    });

    function printRis() {
        const iframe = document.getElementById('risFormIframe');
        if (!iframe || !iframe.contentWindow) return;
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const risModal = document.getElementById('risFormModal');
            const decisionModal = document.getElementById('decisionModal');
            if (risModal && !risModal.classList.contains('hidden')) {
                closeRisFormModal();
            }
            if (decisionModal && !decisionModal.classList.contains('hidden')) {
                closeDecisionModal();
            }
        }
    });

    // =====================================================
    // SIGNATURE CANVAS
    // =====================================================
    function clearSignature() {
        const canvas = document.getElementById('signatureCanvas');
        const input = document.getElementById('signatureData');
        const usedInput = document.getElementById('signatureUsed');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (input) input.value = '';
        if (usedInput) usedInput.value = '0';
    }

    (function initSignatureCanvas() {
        const canvas = document.getElementById('signatureCanvas');
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
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', (e) => { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', end);
    })();
</script>

@endsection