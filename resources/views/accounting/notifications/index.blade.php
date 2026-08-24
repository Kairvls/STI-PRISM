@extends('layouts.accounting-layout')

@section('title', 'Notifications')

@section('content')
<div class="acc-page fade-in space-y-6">
    <div>
        <p class="text-sm leading-6 text-gray-500">Accounting alerts from the topbar bell.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="pm-card p-5 slide-up">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Inbox</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ ($items->total() ?? 0) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="bell" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-400">Latest Accounting alerts</p>
            </div>
        </div>
        <a href="/accounting/dashboard" class="pm-card p-5 slide-up" style="animation-delay:.06s">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Queues</p>
                    <p class="mt-2 text-base font-semibold tracking-tight text-slate-950">Open dashboard</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-400">ATP, Request Checks, and liquidations</p>
            </div>
        </a>
    </div>

    <section class="pm-card overflow-hidden slide-up" style="animation-delay:.1s">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-gray-900">All alerts</h2>
            <p class="mt-0.5 text-xs text-gray-400">Newest first</p>
        </div>
        <div id="notifItems" class="acc-list-fill-lg">
            @include('accounting._notif-items', ['items' => $items])
        </div>
        <div id="notifPagination">
            @if ($items->hasPages())
                <div class="acc-pagination acc-pagination--flush border-t border-slate-100">{{ $items->links('pagination.president') }}</div>
            @endif
        </div>
    </section>
</div>

<script>
    (function () {
        const container = document.getElementById('notifPagination');
        if (!container) return;
        container.addEventListener('click', function (e) {
            const link = e.target.closest('a[href]');
            if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') return;
            e.preventDefault();
            const url = new URL(link.href, window.location.origin);
            fetch(url.pathname + url.search, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                const items = document.getElementById('notifItems');
                if (items && data.table_html !== undefined) items.innerHTML = data.table_html;
                container.innerHTML = data.pagination_html
                    ? '<div class="acc-pagination acc-pagination--flush border-t border-slate-100">' + data.pagination_html + '</div>'
                    : '';
                if (window.lucide) lucide.createIcons();
                window.history.replaceState({}, '', url.pathname + url.search);
            })
            .catch(err => console.error(err));
        });
    })();
</script>
@endsection
