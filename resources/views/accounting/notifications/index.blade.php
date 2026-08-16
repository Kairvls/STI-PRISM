@extends('layouts.accounting-layout')

@section('title', 'Notifications')

@section('content')
<div class="acc-page fade-in">
    <div class="acc-page-header">
        <div>
            <p class="acc-page-kicker">Alerts</p>
            <h1 class="acc-page-title">Notifications</h1>
            <p class="acc-page-subtitle">Accounting alerts from the topbar bell.</p>
        </div>
    </div>

    <div class="acc-card slide-up">
        @forelse ($items as $item)
            <div class="acc-notif-item">
                <p class="text-sm font-semibold text-slate-900">{{ $item->notification_title }}</p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-600">{{ $item->notification_message }}</p>
                <p class="mt-1.5 text-[11px] text-slate-400">{{ $item->notification_created_at ? \Carbon\Carbon::parse($item->notification_created_at)->format('M d, Y g:i A') : '' }}</p>
            </div>
        @empty
            <div class="p-4"><div class="acc-empty">No notifications for Accounting yet.</div></div>
        @endforelse
    </div>
</div>
@endsection
