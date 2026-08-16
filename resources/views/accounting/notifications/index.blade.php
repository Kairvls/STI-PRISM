@extends('layouts.accounting-layout')

@section('title', 'Notifications')

@section('content')
<div class="fade-in mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Notifications</h1>
    <p class="mt-1 text-sm text-gray-500">Accounting alerts. This page is not in the sidebar; use the topbar bell.</p>
</div>
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white slide-up">
    @forelse ($items as $item)
        <div class="border-b border-gray-100 px-5 py-4 last:border-0">
            <p class="text-sm font-semibold text-gray-900">{{ $item->notification_title }}</p>
            <p class="mt-1 text-sm text-gray-600">{{ $item->notification_message }}</p>
            <p class="mt-2 text-xs text-gray-400">{{ $item->notification_created_at ? \Carbon\Carbon::parse($item->notification_created_at)->format('M d, Y g:i A') : '' }}</p>
        </div>
    @empty
        <div class="acc-empty m-5 rounded-lg p-10 text-center text-sm text-gray-500">No notifications for Accounting yet.</div>
    @endforelse
</div>
@endsection
