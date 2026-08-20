@extends('layouts.president-layout')

@section('title', 'Alerts')

@section('content')

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-2">
        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
            <span class="h-2 w-2 rounded-full {{ ($unreadCount ?? 0) > 0 ? 'bg-slate-500' : 'bg-slate-300' }}"></span>
            <span>{{ $unreadCount ?? 0 }} unread</span>
        </div>

        @if (($unreadCount ?? 0) > 0)
            <form action="/president/notifications/mark-all-read" method="POST">
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950"
                >
                    <i data-lucide="check-check" class="h-4 w-4"></i>
                    <span>Mark all as read</span>
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Unread Alerts</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $unreadCount ?? 0 }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="bell" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <span class="h-1.5 w-1.5 rounded-full {{ ($unreadCount ?? 0) > 0 ? 'bg-slate-500' : 'bg-blue-500' }}"></span>
                <p class="text-xs text-slate-400">
                    {{ ($unreadCount ?? 0) > 0 ? 'Requires your attention' : 'All caught up' }}
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Inbox</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ ($notifications ?? collect())->count() }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="inbox" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-400">Showing latest President alerts</p>
            </div>
        </div>

        <a
            href="/president/approvals"
            class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-300 sm:col-span-2 xl:col-span-1"
        >
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">RIS Approvals</p>
                    <p class="mt-2 text-base font-semibold tracking-tight text-slate-950">Open approval queue</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="clipboard-check" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-400">Review forwarded RIS documents</p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-slate-900">Browse</h2>
            <p class="mt-1 text-xs text-slate-500">Filter alerts by category.</p>

            <div class="mt-4 flex flex-col gap-2">
                @php
                    $filters = [
                        'all' => ['All alerts', 'bell'],
                        'Approvals' => ['Approvals', 'clipboard-check'],
                        'Rejections' => ['Rejections', 'x-circle'],
                        'workflow' => ['Workflow', 'git-branch'],
                    ];
                @endphp

                @foreach ($filters as $key => [$label, $icon])
                    <a
                        href="{{ url('/president/notifications') }}{{ $key === 'all' ? '' : '?category=' . urlencode($key) }}"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition
                            {{ ($activeCategory ?? 'all') === $key
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                    >
                        <i data-lucide="{{ $icon }}" class="h-4 w-4 {{ ($activeCategory ?? 'all') === $key ? 'text-slate-300' : 'text-slate-500' }}"></i>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Inbox</h2>
                    <p class="mt-1 text-xs text-slate-500">Updates and status changes for presidential review.</p>
                </div>
                <p class="text-xs font-medium text-slate-500">{{ ($notifications ?? collect())->count() }} shown</p>
            </div>

            <div class="mt-4 flex flex-col gap-2">
                @forelse (($notifications ?? collect()) as $notification)
                    @php
                        $icon = match ($notification->notification_type) {
                            'ris_forwarded' => 'clipboard-check',
                            'ris_approved', 'decision_approved' => 'circle-check-big',
                            'ris_rejected', 'decision_rejected' => 'x-circle',
                            'admin_notified' => 'send',
                            default => 'bell',
                        };
                        $iconStyle = match ($notification->notification_category) {
                            'Approvals', 'approval', 'workflow' => 'bg-blue-50 text-blue-600',
                            'Rejections', 'rejection' => 'bg-slate-100 text-slate-600',
                            default => 'bg-slate-100 text-slate-500',
                        };
                        $isUnread = empty($notification->is_read);
                    @endphp

                    <a
                        href="/president/notifications/{{ $notification->notification_id }}/open"
                        class="flex items-start gap-3 rounded-xl border px-4 py-3 transition hover:border-slate-300 hover:bg-slate-50
                            {{ $isUnread ? 'border-slate-200 bg-slate-50/70' : 'border-slate-100 bg-white' }}"
                    >
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $iconStyle }}">
                            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="truncate text-sm font-semibold text-slate-900">
                                    {{ $notification->notification_title }}
                                </h3>
                                @if ($isUnread)
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-600"></span>
                                @endif
                            </div>
                            <p class="mt-0.5 line-clamp-2 text-xs leading-5 text-slate-500">
                                {{ $notification->notification_message }}
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{ \Carbon\Carbon::parse($notification->notification_created_at)->diffForHumans() }}
                            </p>
                        </div>
                    </a>
                @empty
                    <div class="flex min-h-[220px] flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 px-6 text-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i data-lucide="bell-off" class="h-4 w-4"></i>
                        </div>
                        <h3 class="mt-3 text-sm font-medium text-slate-700">No notifications</h3>
                        <p class="mt-1 text-xs text-slate-400">New system activity will appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>

@endsection
