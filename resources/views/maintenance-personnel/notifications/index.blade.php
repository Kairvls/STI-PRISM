@extends('layouts.maintenance-layout')

@section('title', 'Alerts')

@section('content')

@php
    $eqField = 'h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-10 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $hasCustomPeriod = request()->filled('date')
        || request()->filled('week_date')
        || request()->filled('month')
        || request()->filled('year');
    $hasFilters = request()->filled('search')
        || $period !== 'today'
        || $category !== 'all'
        || $hasCustomPeriod;

    $categoryTone = function ($value) {
        return match ($value) {
            'Reports' => ['bg-blue-50 text-blue-600 ring-blue-100', 'border-blue-100 bg-blue-50 text-blue-700'],
            'Maintenance' => ['bg-amber-50 text-amber-600 ring-amber-100', 'border-amber-100 bg-amber-50 text-amber-700'],
            'Equipment' => ['bg-indigo-50 text-indigo-600 ring-indigo-100', 'border-indigo-100 bg-indigo-50 text-indigo-700'],
            default => ['bg-slate-50 text-slate-500 ring-slate-200/80', 'border-slate-200 bg-slate-50 text-slate-600'],
        };
    };
@endphp

<div class="space-y-6">
    <header>
        <div class="mb-4 flex items-center gap-2 text-sm text-slate-400">
            <a href="{{ url('/maintenance/dashboard') }}" class="transition hover:text-slate-700">Dashboard</a>
            <i data-lucide="chevron-right" class="h-4 w-4"></i>
            <span class="font-medium text-slate-600">Alerts</span>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600">
                    <span class="h-2 w-2 rounded-full {{ $unreadCount > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                    {{ $unreadCount }} unread
                </div>

                @if ($unreadCount > 0)
                    <form action="/maintenance/notifications/mark-all-read" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                        >
                            <i data-lucide="check-check" class="h-4 w-4"></i>
                            Mark all as read
                        </button>
                    </form>
                @endif
            </div>

            <a
                href="{{ url('/maintenance/dashboard') }}"
                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
            >
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back
            </a>
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Unread alerts</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $unreadCount }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                    <i data-lucide="bell" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <span class="h-1.5 w-1.5 rounded-full {{ $unreadCount > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                <p class="text-xs text-slate-400">
                    {{ $unreadCount > 0 ? 'Requires your attention' : 'All caught up' }}
                </p>
            </div>
        </div>

        <a href="{{ url('/maintenance/reports/urgent') }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-rose-200 hover:bg-rose-50/40">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Urgent reports</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $urgentReports }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                    <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <span class="h-1.5 w-1.5 rounded-full {{ $urgentReports > 0 ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                <p class="text-xs text-slate-400">
                    {{ $urgentReports > 0 ? 'Needs immediate action' : 'No urgent reports' }}
                </p>
            </div>
        </a>

        <a href="{{ url('/maintenance/schedules/today') }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-amber-200 hover:bg-amber-50/40">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Due today</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $dueToday }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                    <i data-lucide="calendar-clock" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <span class="h-1.5 w-1.5 rounded-full {{ $dueToday > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                <p class="text-xs text-slate-400">
                    {{ $dueToday > 0 ? 'Scheduled for today' : 'Nothing due today' }}
                </p>
            </div>
        </a>

        <a href="{{ url('/maintenance/schedules') }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-orange-200 hover:bg-orange-50/40">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Overdue</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $overdueMaintenance }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                    <i data-lucide="calendar-x-2" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                <span class="h-1.5 w-1.5 rounded-full {{ $overdueMaintenance > 0 ? 'bg-orange-500' : 'bg-emerald-500' }}"></span>
                <p class="text-xs text-slate-400">
                    {{ $overdueMaintenance > 0 ? 'Past scheduled date' : 'No overdue maintenance' }}
                </p>
            </div>
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="p-4 sm:p-5">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" action="{{ url()->current() }}" class="relative w-full xl:max-w-sm">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="hidden" name="category" value="{{ $category }}">

                    @if (request('date'))
                        <input type="hidden" name="date" value="{{ request('date') }}">
                    @endif
                    @if (request('week_date'))
                        <input type="hidden" name="week_date" value="{{ request('week_date') }}">
                    @endif
                    @if (request('month'))
                        <input type="hidden" name="month" value="{{ request('month') }}">
                    @endif
                    @if (request('year'))
                        <input type="hidden" name="year" value="{{ request('year') }}">
                    @endif

                    <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search alerts..."
                        class="{{ $eqField }}"
                    >

                    @if (request('search'))
                        <a
                            href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-700"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </a>
                    @endif
                </form>

                <div x-data="{ calendarOpen: false }" class="relative flex w-full items-center gap-2 xl:w-auto">
                    <div class="flex flex-1 items-center rounded-xl bg-slate-100 p-1 xl:flex-none">
                        @php
                            $periods = [
                                'today' => 'Today',
                                'week' => 'Week',
                                'month' => 'Month',
                                'year' => 'Year',
                            ];
                        @endphp

                        @foreach ($periods as $value => $label)
                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'period' => $value,
                                    'date' => null,
                                    'week_date' => null,
                                    'month' => null,
                                    'year' => null,
                                    'page' => null
                                ]) }}"
                                class="flex-1 rounded-lg px-4 py-2 text-center text-xs font-semibold transition xl:flex-none
                                    {{ $period === $value && !$hasCustomPeriod
                                        ? 'bg-white text-slate-950 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-900' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <button
                        type="button"
                        @click="calendarOpen = !calendarOpen"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 {{ $hasCustomPeriod ? 'border-[#0025cc]/30 text-[#0025cc]' : '' }}"
                    >
                        <i data-lucide="calendar-days" class="h-4 w-4"></i>
                    </button>

                    <div
                        x-show="calendarOpen"
                        x-cloak
                        @click.outside="calendarOpen = false"
                        class="absolute right-0 top-12 z-40 w-[300px] rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_24px_64px_rgba(15,23,42,.16)]"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-slate-900">Select period</h3>
                            <p class="mt-1 text-xs text-slate-400">View alerts from a specific period.</p>
                        </div>

                        @php $popoverField = 'h-9 min-w-0 flex-1 rounded-lg border-0 bg-slate-50 px-3 text-xs text-slate-700 outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10'; @endphp

                        <form method="GET" action="{{ url()->current() }}" class="mb-3">
                            <input type="hidden" name="category" value="{{ $category }}">
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Specific date</label>
                            <div class="flex gap-2">
                                <input type="date" name="date" value="{{ request('date') }}" required class="{{ $popoverField }}">
                                <button type="submit" class="h-9 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-blue-800">Apply</button>
                            </div>
                        </form>

                        <form method="GET" action="{{ url()->current() }}" class="mb-3">
                            <input type="hidden" name="category" value="{{ $category }}">
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Week containing</label>
                            <div class="flex gap-2">
                                <input type="date" name="week_date" value="{{ request('week_date') }}" required class="{{ $popoverField }}">
                                <button type="submit" class="h-9 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-blue-800">Apply</button>
                            </div>
                        </form>

                        <form method="GET" action="{{ url()->current() }}" class="mb-3">
                            <input type="hidden" name="category" value="{{ $category }}">
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Month</label>
                            <div class="flex gap-2">
                                <input type="month" name="month" value="{{ request('month') }}" required class="{{ $popoverField }}">
                                <button type="submit" class="h-9 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-blue-800">Apply</button>
                            </div>
                        </form>

                        <form method="GET" action="{{ url()->current() }}">
                            <input type="hidden" name="category" value="{{ $category }}">
                            <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Year</label>
                            <div class="flex gap-2">
                                <input
                                    type="number"
                                    name="year"
                                    min="2000"
                                    max="{{ now()->year }}"
                                    value="{{ request('year') }}"
                                    placeholder="{{ now()->year }}"
                                    required
                                    class="{{ $popoverField }}"
                                >
                                <button type="submit" class="h-9 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-blue-800">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-slate-100 pt-3">
                @php
                    $categories = [
                        'all' => 'All',
                        'Reports' => 'Reports',
                        'Maintenance' => 'Maintenance',
                        'Equipment' => 'Equipment',
                    ];
                @endphp

                @foreach ($categories as $value => $label)
                    <a
                        href="{{ request()->fullUrlWithQuery(['category' => $value, 'page' => null]) }}"
                        class="inline-flex h-8 items-center rounded-lg px-3 text-xs font-semibold transition
                            {{ $category === $value
                                ? 'bg-[#0025cc] text-white'
                                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach

                @if ($hasFilters)
                    <div class="ml-auto">
                        <a
                            href="{{ url()->current() }}"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        >
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                            Reset
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="border-t border-slate-100">
            @forelse ($notifications as $notification)
                @php
                    $icon = match ($notification->notification_category) {
                        'Reports' => 'clipboard-list',
                        'Maintenance' => 'wrench',
                        'Equipment' => 'monitor',
                        default => 'bell',
                    };
                    [$iconClass, $pillClass] = $categoryTone($notification->notification_category);
                    $isUnread = !$notification->is_read;
                @endphp

                <a
                    href="/maintenance/notifications/{{ $notification->notification_id }}/open"
                    class="group flex items-start gap-3.5 border-b border-slate-100 px-5 py-4 transition last:border-b-0 hover:bg-slate-50/70 {{ $isUnread ? 'bg-slate-50/50' : '' }}"
                >
                    <div class="relative mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $isUnread ? $iconClass : 'bg-slate-50 text-slate-400 ring-slate-200/80' }}">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                        @if ($isUnread)
                            <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white"></span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-sm font-semibold text-slate-900">
                                        {{ $notification->notification_title }}
                                    </h3>
                                    @if ($isUnread)
                                        <span class="rounded-full border border-amber-100 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700">
                                            New
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    {{ $notification->notification_message }}
                                </p>
                            </div>

                            <time class="shrink-0 text-xs text-slate-400">
                                {{ \Carbon\Carbon::parse($notification->notification_created_at)->diffForHumans() }}
                            </time>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2">
                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $pillClass }}">
                                {{ $notification->notification_category ?? 'System' }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] text-slate-400">
                                <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                                {{ \Carbon\Carbon::parse($notification->notification_created_at)->format('M d, Y · h:i A') }}
                            </span>
                        </div>
                    </div>

                    @if ($notification->notification_url)
                        <i
                            data-lucide="chevron-right"
                            class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[#0025cc]"
                        ></i>
                    @endif
                </a>
            @empty
                <div class="flex min-h-[320px] items-center justify-center px-6 py-16 text-center">
                    <div class="flex max-w-sm flex-col items-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-200/80">
                            <i data-lucide="bell-off" class="h-6 w-6"></i>
                        </div>
                        <h3 class="mt-4 font-semibold text-slate-800">No alerts found</h3>
                        <p class="mt-1.5 max-w-xs text-sm leading-5 text-slate-400">
                            {{ $hasFilters
                                ? 'No alerts match the selected period and category. Try changing your filters.'
                                : 'New alerts will appear here when reports, maintenance, or equipment need attention.' }}
                        </p>
                        @if ($hasFilters)
                            <a
                                href="{{ url()->current() }}"
                                class="mt-5 inline-flex h-9 items-center gap-2 rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white transition hover:bg-blue-800"
                            >
                                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                Clear filters
                            </a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/80 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    Showing
                    <span class="font-medium text-slate-700">{{ $notifications->firstItem() }}</span>
                    to
                    <span class="font-medium text-slate-700">{{ $notifications->lastItem() }}</span>
                    of
                    <span class="font-medium text-slate-700">{{ $notifications->total() }}</span>
                    alerts
                </p>
                <div class="pagination-wrapper">
                    {{ $notifications->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
