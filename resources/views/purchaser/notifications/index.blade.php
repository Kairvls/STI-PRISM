@extends('layouts.purchaser-layout')

@section('page-title', 'Notifications')

@section('content')
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    @forelse ($items as $item)
        <a href="{{ $item->notification_url ?: '#' }}" class="block border-b border-slate-100 px-5 py-4 last:border-0 hover:bg-slate-50">
            <p class="text-sm font-semibold text-slate-900">{{ $item->notification_title }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $item->notification_message }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ $item->notification_created_at ? \Carbon\Carbon::parse($item->notification_created_at)->format('M d, Y g:i A') : '' }}</p>
        </a>
    @empty
        <div class="p-10 text-center text-sm text-slate-500">No notifications yet.</div>
    @endforelse
</div>
@endsection
