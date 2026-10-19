@extends('layouts.supply-officer-layout')

@section('title', 'Notifications')

@section('content')
<div class="space-y-3">
    @forelse ($items as $note)
        <a
            href="{{ \App\Support\NotificationLinks::openUrl($note) }}"
            class="block rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-blue-200 hover:bg-blue-50/40"
        >
            <p class="text-sm font-semibold text-slate-900">{{ $note->notification_title }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ $note->notification_message }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($note->notification_created_at)->diffForHumans() }}</p>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
            No notifications yet.
        </div>
    @endforelse
</div>
@endsection
