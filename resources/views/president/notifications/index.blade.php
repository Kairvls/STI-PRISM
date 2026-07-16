@extends('layouts.president-layout')

@section('title', 'System Alerts')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">System Alerts</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">
            Receive updates, pending items, and status changes from the procurement system.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <button
            type="button"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800"
            onclick="markAllRead()"
        >
            <i data-lucide="check-check" class="h-4 w-4"></i>
            Mark all as read
        </button>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Left: filters --}}
    <section class="lg:col-span-1 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Browse</h2>
                <p class="mt-1 text-xs text-gray-500">Filter alerts by category.</p>
            </div>
            <span class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-700 border border-gray-200">
                UI
            </span>
        </div>

        <div class="mt-4 flex flex-col gap-2">
            <button type="button" class="notif-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50" data-filter="all">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="bell" class="h-4 w-4 text-gray-600"></i>
                    All alerts
                </span>
            </button>
            <button type="button" class="notif-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50" data-filter="approval">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="h-4 w-4 text-emerald-600"></i>
                    Approvals
                </span>
            </button>
            <button type="button" class="notif-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50" data-filter="rejection">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="x-circle" class="h-4 w-4 text-rose-600"></i>
                    Rejections
                </span>
            </button>
            <button type="button" class="notif-filter-btn w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50" data-filter="system">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="settings" class="h-4 w-4 text-indigo-600"></i>
                    System
                </span>
            </button>
        </div>

        <div class="mt-5">
            <h3 class="text-xs font-semibold text-gray-900">Search</h3>
            <div class="relative mt-2">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                <input id="notifSearch" type="search" placeholder="Search alerts..." class="w-full rounded-lg border border-gray-200 bg-white px-9 py-2.5 text-sm text-gray-900 outline-none focus:ring-4 focus:ring-amber-100" />
            </div>
        </div>
    </section>

    {{-- Right: list --}}
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Inbox</h2>
                <p class="mt-1 text-xs text-gray-500">Latest system alert items.</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-semibold text-gray-600">Showing</p>
                <p id="notifCount" class="text-sm font-bold text-gray-900">0</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3">

            {{-- Expected future variables: $notifications (collection) --}}
            {{-- Each notification shape (suggested):
                - id
                - category: 'approval'|'rejection'|'system' (optional)
                - title
                - body
                - created_at / sent_at
                - is_read (boolean)
                - reference_url (optional)
            --}}

            <div id="notifList">
                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-6">
                    <p class="text-sm font-semibold text-gray-800">Notifications UI is ready.</p>
                    <p class="mt-1 text-xs text-gray-500">
                        Backend data will populate the inbox list once the controller is updated.
                    </p>
                </div>
            </div>
        </div>
    </section>

</div>

<script>
    function markAllRead() {
        // UI-only placeholder.
        // Later we can call a president notifications endpoint similar to maintenance.
        alert('Mark all as read (UI placeholder)');
    }

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
</script>

{{-- Ensure lucide icons render on this page. --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

@endsection

