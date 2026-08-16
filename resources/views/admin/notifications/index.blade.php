@extends('layouts.admin-layout')

@section('title', 'Notifications')

@section('content')
<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Notifications</h1>
        <p class="admin-page-subtitle">Inbox and compose stay in the top bar. This page does not duplicate that workflow.</p>
    </div>
    <div class="rounded-[18px] border border-gray-200 bg-white">
        @forelse ($items as $item)
            <a href="{{ $item->notification_url ?: '#' }}" class="block border-b border-gray-100 px-5 py-4 last:border-0 hover:bg-gray-50">
                <p class="text-sm font-semibold text-gray-900">{{ $item->notification_title }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $item->notification_message }}</p>
                <p class="mt-2 text-xs text-gray-400">{{ $item->notification_created_at ? \Carbon\Carbon::parse($item->notification_created_at)->format('M d, Y g:i A') : '' }}</p>
            </a>
        @empty
            <div class="px-5 py-16 text-center text-sm text-gray-500">No workflow notifications yet.</div>
        @endforelse
    </div>
</div>
@endsection
