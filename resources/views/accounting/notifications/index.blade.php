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
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ ($items ?? collect())->count() }}</p>
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
        @forelse ($items as $item)
            <div class="acc-notif-item">
                <p class="text-sm font-semibold text-slate-900">{{ $item->notification_title }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-600">{{ $item->notification_message }}</p>
                <p class="mt-1.5 text-[11px] text-slate-400">{{ $item->notification_created_at ? \Carbon\Carbon::parse($item->notification_created_at)->format('M d, Y g:i A') : '' }}</p>
            </div>
        @empty
            <div class="p-6"><div class="acc-empty">No notifications for Accounting yet.</div></div>
        @endforelse
    </section>
</div>
@endsection
