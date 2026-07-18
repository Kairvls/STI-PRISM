@extends('layouts.president-layout')

@section('title', 'System Alerts')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">System Alerts</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">
            Receive updates, pending items, and status changes from the procurement system.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <button
            type="button"
            class="action-btn inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition-all duration-200 hover:bg-gray-800 active:scale-95"
            onclick="markAllRead()"
        >
            <i data-lucide="check-check" class="h-4 w-4"></i>
            Mark all as read
        </button>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Left: filters --}}
    <section class="lg:col-span-1 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.05s">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Browse</h2>
                <p class="mt-1 text-xs text-gray-500">Filter alerts by category.</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-2">
            <button type="button" class="notif-filter-btn w-full rounded-lg border border-gray-200 bg-gray-900 text-white px-3 py-2 text-left text-sm font-semibold transition-all duration-200 hover:bg-gray-800 active:scale-[0.98]" data-filter="all">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="bell" class="h-4 w-4 text-gray-300"></i>
                    All alerts
                </span>
            </button>
            <button type="button" class="notif-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]" data-filter="approval">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="h-4 w-4 text-emerald-600"></i>
                    Approvals
                </span>
            </button>
            <button type="button" class="notif-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]" data-filter="rejection">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="x-circle" class="h-4 w-4 text-rose-600"></i>
                    Rejections
                </span>
            </button>
        </div>

        <div class="mt-5">
            <h3 class="text-xs font-semibold text-gray-900">Search</h3>
            <div class="relative mt-2">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input id="notifSearch" type="search" placeholder="Search alerts..." class="w-full rounded-lg border border-gray-200 bg-white px-9 py-2.5 text-sm text-gray-900 outline-none transition-all duration-200 focus:ring-4 focus:ring-amber-100" />
            </div>
        </div>
    </section>

    {{-- Right: list --}}
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.1s">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Inbox</h2>
                <p class="mt-1 text-xs text-gray-500">Receive updates, pending items, and status changes from the procurement system.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs font-semibold text-gray-600">Showing</p>
                    <p id="notifCount" class="text-sm font-bold text-gray-900">0</p>
                </div>
                <button type="button" class="action-btn inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 active:scale-95" onclick="openAllAlertsModal()">
                    View All
                </button>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3" id="notifList">
            @if(count($recentRis) > 0)
                @foreach($recentRis as $ris)
                    @php
                        $statusLower = strtolower($ris->ris_status ?? '');
                        $category = $statusLower === 'approved' ? 'approval' : ($statusLower === 'rejected' ? 'rejection' : 'approval');
                        $title = 'RIS ' . ($ris->ris_form_number ?? '#' . $ris->ris_id);
                        $body = $ris->ris_purpose_description ?? 'No description provided.';
                        $created = $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—';
                        $searchBlob = strtolower($title . ' ' . $body . ' ' . $created);
                    @endphp
                    <div class="notif-row rounded-lg border border-gray-100 bg-white p-4 transition-all duration-200 hover:border-gray-200 hover:shadow-sm"
                         data-category="{{ $category }}"
                         data-search="{{ $searchBlob }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg {{ $category === 'approval' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                    <i data-lucide="{{ $category === 'approval' ? 'clipboard-check' : 'x-circle' }}" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $body }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400">{{ $created }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $category === 'approval' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $ris->ris_status ?? 'Pending' }}
                            </span>
                            <button type="button" class="text-xs font-medium text-blue-600 transition-all duration-200 hover:text-blue-800 active:scale-95" onclick="openRisFormModal({{ $ris->ris_id }})">Review →</button>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-6 text-center fade-in">
                    <p class="text-sm font-semibold text-gray-800">No alerts yet</p>
                    <p class="mt-1 text-xs text-gray-500">RIS records forwarded to you will appear here.</p>
                </div>
            @endif
        </div>
    </section>

</div>

{{-- ============================== --}}
{{-- VIEW ALL MODAL --}}
{{-- ============================== --}}
<div id="allAlertsModal" class="fixed inset-0 z-50 hidden">
    <div class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px] modal-overlay" onclick="closeAllAlertsModal()">
        <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] modal-content" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">All Inbox History</h3>
                        <p class="mt-1 text-sm text-slate-600">Complete list of RIS records forwarded to the President.</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeAllAlertsModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                @if(count($allRis) > 0)
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-2 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS #</th>
                                <th class="px-2 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Form Number</th>
                                <th class="px-2 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Status</th>
                                <th class="px-2 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                                <th class="px-2 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-black bg-gray-50">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allRis as $ris)
                                <tr class="border-b border-gray-100 transition-all duration-200 hover:bg-yellow-50/40">
                                    <td class="px-2 py-3 text-sm font-semibold text-gray-700">#{{ $ris->ris_id }}</td>
                                    <td class="px-2 py-3 text-sm text-gray-600">{{ $ris->ris_form_number ?? '—' }}</td>
                                    <td class="px-2 py-3 text-sm">
                                        @php
                                            $s = strtolower($ris->ris_status ?? '');
                                        @endphp
                                        @if($s === 'approved')
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Approved</span>
                                        @elseif($s === 'rejected')
                                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">Rejected</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-sm text-gray-600">{{ $ris->ris_created_at ? date('M d, Y', strtotime($ris->ris_created_at)) : '—' }}</td>
                                    <td class="px-2 py-3 text-center">
                                        <button type="button" class="action-btn inline-flex items-center justify-center rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-blue-600 border border-gray-200 transition-all duration-200 hover:bg-blue-50 active:scale-95" onclick="openRisFormModal({{ $ris->ris_id }})">Review</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-6 text-center">
                        <p class="text-sm font-semibold text-gray-800">No records found.</p>
                        <p class="mt-1 text-xs text-gray-500">RIS records forwarded to you will appear here.</p>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                <div class="flex items-center justify-end">
                    <button type="button" class="action-btn rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-gray-800 active:scale-95" onclick="closeAllAlertsModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- RIS FORM MODAL --}}
{{-- ============================== --}}
<div id="risFormModal" class="fixed inset-0 z-50 hidden">
    <div class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px] modal-overlay" onclick="closeRisFormModal()">
        <div class="w-full max-w-6xl h-[90vh] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] modal-content" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">RIS Form</h3>
                        <p id="risFormModalSubtitle" class="mt-0.5 text-sm text-slate-600">Preview of the RIS document</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisFormModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="h-full">
                <div class="h-full w-full overflow-hidden rounded-b-2xl border border-gray-200 bg-gray-50">
                    <iframe id="risFormIframe" class="w-full h-full" style="min-height: calc(90vh - 80px);" src="about:blank"></iframe>
                </div>
            </div>
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

    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:active {
        transform: scale(0.95);
    }

    .notif-row {
        transition: all 0.2s ease;
    }

    .notif-row:hover {
        border-color: #d1d5db;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }

    .notif-filter-btn {
        transition: all 0.2s ease;
    }

    .notif-filter-btn:active {
        transform: scale(0.98);
    }
</style>

<script>
    // =====================================================
    // MODALS
    // =====================================================

    function openAllAlertsModal() {
        document.getElementById('allAlertsModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeAllAlertsModal() {
        document.getElementById('allAlertsModal').classList.add('hidden');
    }

    // =====================================================
    // RIS FORM MODAL
    // =====================================================

    function openRisFormModal(risId) {
        const modal = document.getElementById('risFormModal');
        const iframe = document.getElementById('risFormIframe');
        const subtitle = document.getElementById('risFormModalSubtitle');
        if (!modal || !iframe) return;

        iframe.src = `/president/ris/${risId}/print?ts=${Date.now()}`;
        if (subtitle) subtitle.textContent = `RIS #${risId}`;
        modal.classList.remove('hidden');
    }

    function closeRisFormModal() {
        const modal = document.getElementById('risFormModal');
        const iframe = document.getElementById('risFormIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }

    // =====================================================
    // MARK ALL AS READ
    // =====================================================

    function markAllRead() {
        // Placeholder — marks all as read visually
        document.querySelectorAll('.notif-row').forEach(row => {
            row.style.opacity = '0.6';
        });
    }

    // =====================================================
    // FILTER + SEARCH
    // =====================================================

    function setNotifCount() {
        const list = document.getElementById('notifList');
        const rows = list ? list.querySelectorAll('.notif-row') : [];
        const visible = Array.from(rows).filter(r => r.style.display !== 'none');
        document.getElementById('notifCount').textContent = visible.length;
    }

    function applyNotifFilters() {
        const searchInput = document.getElementById('notifSearch');
        const search = (searchInput?.value || '').trim().toLowerCase();

        const activeBtn = document.querySelector('.notif-filter-btn.bg-gray-900');
        const activeFilter = activeBtn?.dataset?.filter || 'all';

        const list = document.getElementById('notifList');
        if (!list) return;

        const rows = list.querySelectorAll('.notif-row');
        rows.forEach(row => {
            const category = (row.dataset.category || '').toLowerCase();
            const rowSearch = (row.dataset.search || '').toLowerCase();

            const matchesFilter = (activeFilter === 'all') || (category === activeFilter);
            const matchesSearch = !search || rowSearch.includes(search);

            row.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
        });

        setNotifCount();
    }

    (function initNotifUI() {
        const filterBtns = document.querySelectorAll('.notif-filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => {
                    b.classList.remove('bg-gray-900', 'text-white');
                    b.classList.add('text-gray-700');
                });
                btn.classList.add('bg-gray-900', 'text-white');
                btn.classList.remove('text-gray-700');
                applyNotifFilters();
            });
        });

        const searchInput = document.getElementById('notifSearch');
        if (searchInput) {
            searchInput.addEventListener('input', applyNotifFilters);
        }

        // Set default active filter
        const defaultBtn = document.querySelector('.notif-filter-btn[data-filter="all"]');
        if (defaultBtn) defaultBtn.click();

        // Init count
        setNotifCount();
    })();

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

@endsection