@extends('layouts.maintenance-layout')

@section('title', 'Alerts')

@section('content')

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- HEADER ACTIONS --}}
    {{-- ===================================================== --}}

    <div class="flex flex-wrap items-center justify-end gap-2">

            {{-- ================================================= --}}
            {{-- UNREAD COUNT --}}
            {{-- ================================================= --}}

            <div
                class="inline-flex items-center gap-2
                    rounded-full border border-slate-200
                    bg-white px-3 py-2
                    text-sm text-slate-600">

                <span
                    class="h-2 w-2 rounded-full
                        {{ $unreadCount > 0
                            ? 'bg-amber-500'
                            : 'bg-slate-300' }}"></span>

                <span>
                    {{ $unreadCount }} unread
                </span>

            </div>


            {{-- ================================================= --}}
            {{-- MARK ALL AS READ --}}
            {{-- ================================================= --}}

            @if ($unreadCount > 0)

            <form
                action="/maintenance/notifications/mark-all-read"
                method="POST">

                @csrf

                <button
                    type="submit"

                    class="inline-flex items-center gap-2
                            rounded-full border border-slate-200
                            bg-white px-3 py-2
                            text-sm font-medium text-slate-600
                            transition
                            hover:border-slate-300
                            hover:bg-slate-50
                            hover:text-slate-950">

                    <i
                        data-lucide="check-check"
                        class="h-4 w-4"></i>

                    <span>
                        Mark all as read
                    </span>

                </button>

            </form>

            @endif

        </div>

        {{-- ===================================================== --}}
    {{-- ALERT SUMMARY CARDS --}}
    {{-- QUICK STATUS OVERVIEW --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- ================================================= --}}
        {{-- UNREAD ALERTS --}}
        {{-- ================================================= --}}

        <div
            class="group rounded-2xl border border-slate-200
                bg-white p-5 transition
                hover:border-slate-300"
        >
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Unread Alerts
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold
                            tracking-tight text-slate-950"
                    >
                        {{ $unreadCount }}
                    </p>
                </div>

                <div
                    class="flex h-10 w-10 items-center
                        justify-center rounded-xl
                        bg-slate-100 text-slate-600"
                >
                    <i
                        data-lucide="bell"
                        class="h-5 w-5"
                    ></i>
                </div>

            </div>

            <div
                class="mt-4 flex items-center gap-2
                    border-t border-slate-100 pt-3"
            >
                <span
                    class="h-1.5 w-1.5 rounded-full
                    {{ $unreadCount > 0
                        ? 'bg-amber-500'
                        : 'bg-emerald-500' }}"
                ></span>

                <p class="text-xs text-slate-400">
                    {{ $unreadCount > 0
                        ? 'Requires your attention'
                        : 'All caught up' }}
                </p>
            </div>
        </div>


        {{-- ================================================= --}}
        {{-- URGENT REPORTS --}}
        {{-- ================================================= --}}

        <div
            class="group rounded-2xl border border-slate-200
                bg-white p-5 transition
                hover:border-slate-300"
        >
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Urgent Reports
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold
                            tracking-tight text-slate-950"
                    >
                        {{ $urgentReports }}
                    </p>
                </div>

                <div
                    class="flex h-10 w-10 items-center
                        justify-center rounded-xl
                        bg-rose-50 text-rose-600"
                >
                    <i
                        data-lucide="triangle-alert"
                        class="h-5 w-5"
                    ></i>
                </div>

            </div>

            <div
                class="mt-4 flex items-center gap-2
                    border-t border-slate-100 pt-3"
            >
                <span
                    class="h-1.5 w-1.5 rounded-full
                    {{ $urgentReports > 0
                        ? 'bg-rose-500'
                        : 'bg-emerald-500' }}"
                ></span>

                <p class="text-xs text-slate-400">
                    {{ $urgentReports > 0
                        ? 'Needs immediate action'
                        : 'No urgent reports' }}
                </p>
            </div>
        </div>


        {{-- ================================================= --}}
        {{-- MAINTENANCE DUE TODAY --}}
        {{-- ================================================= --}}

        <div
            class="group rounded-2xl border border-slate-200
                bg-white p-5 transition
                hover:border-slate-300"
        >
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Due Today
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold
                            tracking-tight text-slate-950"
                    >
                        {{ $dueToday }}
                    </p>
                </div>

                <div
                    class="flex h-10 w-10 items-center
                        justify-center rounded-xl
                        bg-amber-50 text-amber-600"
                >
                    <i
                        data-lucide="calendar-clock"
                        class="h-5 w-5"
                    ></i>
                </div>

            </div>

            <div
                class="mt-4 flex items-center gap-2
                    border-t border-slate-100 pt-3"
            >
                <span
                    class="h-1.5 w-1.5 rounded-full
                    {{ $dueToday > 0
                        ? 'bg-amber-500'
                        : 'bg-emerald-500' }}"
                ></span>

                <p class="text-xs text-slate-400">
                    {{ $dueToday > 0
                        ? 'Scheduled for today'
                        : 'Nothing due today' }}
                </p>
            </div>
        </div>


        {{-- ================================================= --}}
        {{-- OVERDUE MAINTENANCE --}}
        {{-- ================================================= --}}

        <div
            class="group rounded-2xl border border-slate-200
                bg-white p-5 transition
                hover:border-slate-300"
        >
            <div class="flex items-start justify-between">

                <div>
                    <p class="text-xs font-medium text-slate-500">
                        Overdue
                    </p>

                    <p
                        class="mt-2 text-3xl font-semibold
                            tracking-tight text-slate-950"
                    >
                        {{ $overdueMaintenance }}
                    </p>
                </div>

                <div
                    class="flex h-10 w-10 items-center
                        justify-center rounded-xl
                        bg-orange-50 text-orange-600"
                >
                    <i
                        data-lucide="calendar-x-2"
                        class="h-5 w-5"
                    ></i>
                </div>

            </div>

            <div
                class="mt-4 flex items-center gap-2
                    border-t border-slate-100 pt-3"
            >
                <span
                    class="h-1.5 w-1.5 rounded-full
                    {{ $overdueMaintenance > 0
                        ? 'bg-orange-500'
                        : 'bg-emerald-500' }}"
                ></span>

                <p class="text-xs text-slate-400">
                    {{ $overdueMaintenance > 0
                        ? 'Past scheduled date'
                        : 'No overdue maintenance' }}
                </p>
            </div>
        </div>

    </div>

    {{-- ===================================================== --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ===================================================== --}}

    @if (session('success'))

    <div
        class="rounded-xl border border-emerald-200
                bg-emerald-50 px-4 py-3
                text-sm text-emerald-700">
        {{ session('success') }}
    </div>

    @endif


    {{-- ===================================================== --}}
    {{-- ALERTS PANEL --}}
    {{-- FILTERS + DATA + PAGINATION --}}
    {{-- ===================================================== --}}

    <div
        class="overflow-hidden rounded-2xl
            border border-slate-200 bg-white">

        {{-- ================================================= --}}
        {{-- FILTER TOOLBAR --}}
        {{-- ================================================= --}}

        <div class="p-4">

            {{-- ================================================= --}}
            {{-- TOP ROW --}}
            {{-- ================================================= --}}

            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">

                {{-- ============================================= --}}
                {{-- SEARCH --}}
                {{-- ============================================= --}}

                <form
                    method="GET"
                    action="{{ url()->current() }}"
                    class="relative w-full xl:max-w-sm">

                    {{-- ========================================= --}}
                    {{-- KEEP CURRENT FILTERS --}}
                    {{-- ========================================= --}}

                    <input
                        type="hidden"
                        name="period"
                        value="{{ $period }}">

                    <input
                        type="hidden"
                        name="category"
                        value="{{ $category }}">


                    {{-- ========================================= --}}
                    {{-- KEEP CUSTOM CALENDAR FILTER --}}
                    {{-- ADD THIS PART HERE --}}
                    {{-- ========================================= --}}

                    @if (request('date'))
                        <input
                            type="hidden"
                            name="date"
                            value="{{ request('date') }}"
                        >
                    @endif

                    @if (request('week_date'))
                        <input
                            type="hidden"
                            name="week_date"
                            value="{{ request('week_date') }}"
                        >
                    @endif

                    @if (request('month'))
                        <input
                            type="hidden"
                            name="month"
                            value="{{ request('month') }}"
                        >
                    @endif

                    @if (request('year'))
                        <input
                            type="hidden"
                            name="year"
                            value="{{ request('year') }}"
                        >
                    @endif


                    {{-- ========================================= --}}
                    {{-- SEARCH ICON --}}
                    {{-- EXISTING CODE CONTINUES BELOW --}}
                    {{-- ========================================= --}}

                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute
                            left-3 top-1/2
                            h-4 w-4
                            -translate-y-1/2
                            text-slate-400"></i>


                    {{-- ========================================= --}}
                    {{-- SEARCH ICON --}}
                    {{-- ========================================= --}}

                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute
                        left-3 top-1/2
                        h-4 w-4
                        -translate-y-1/2
                        text-slate-400"></i>


                    {{-- ========================================= --}}
                    {{-- SEARCH INPUT --}}
                    {{-- ========================================= --}}

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search alerts..."

                        class="h-10 w-full
                        rounded-xl
                        border border-slate-200
                        bg-slate-50
                        pl-10 pr-10
                        text-sm text-slate-800
                        outline-none
                        transition
                        placeholder:text-slate-400
                        focus:border-slate-300
                        focus:bg-white
                        focus:ring-2
                        focus:ring-slate-100">


                    {{-- ========================================= --}}
                    {{-- CLEAR SEARCH --}}
                    {{-- ========================================= --}}

                    @if (request('search'))

                    <a
                        href="{{ request()->fullUrlWithQuery([
                            'search' => null,
                            'page' => null
                        ]) }}"

                        class="absolute right-3 top-1/2
                            -translate-y-1/2
                            text-slate-400
                            transition
                            hover:text-slate-700">
                        <i
                            data-lucide="x"
                            class="h-4 w-4"></i>
                    </a>

                    @endif

                </form>


                {{-- ============================================= --}}
                {{-- PERIOD + CALENDAR FILTER --}}
                {{-- ============================================= --}}

                <div
                    x-data="{ calendarOpen: false }"
                    class="relative flex w-full items-center gap-2 xl:w-auto"
                >

                    {{-- ========================================= --}}
                    {{-- QUICK PERIOD SWITCH --}}
                    {{-- ========================================= --}}

                    <div
                        class="flex flex-1 items-center rounded-xl
                            bg-slate-100 p-1 xl:flex-none"
                    >

                        @php

                            // =====================================
                            // QUICK PERIOD OPTIONS
                            // =====================================

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

                                class="flex-1 rounded-lg px-4 py-2
                                    text-center text-xs font-medium
                                    transition xl:flex-none

                                    {{ $period === $value
                                        && !request('date')
                                        && !request('week_date')
                                        && !request('month')
                                        && !request('year')
                                            ? 'bg-white text-slate-950 shadow-sm'
                                            : 'text-slate-500 hover:text-slate-900' }}"
                            >
                                {{ $label }}
                            </a>

                        @endforeach

                    </div>


                    {{-- ========================================= --}}
                    {{-- CALENDAR BUTTON --}}
                    {{-- ========================================= --}}

                    <button
                        type="button"
                        @click="calendarOpen = !calendarOpen"

                        class="flex h-10 w-10 shrink-0
                            items-center justify-center
                            rounded-xl border border-slate-200
                            bg-white text-slate-500
                            transition
                            hover:bg-slate-50
                            hover:text-slate-900"
                    >
                        <i
                            data-lucide="calendar-days"
                            class="h-4 w-4"
                        ></i>
                    </button>


                    {{-- ========================================= --}}
                    {{-- CALENDAR POPOVER --}}
                    {{-- ========================================= --}}

                    <div
                        x-show="calendarOpen"
                        x-cloak
                        @click.outside="calendarOpen = false"

                        class="absolute right-0 top-12 z-40
                            w-[300px]
                            rounded-2xl border border-slate-200
                            bg-white p-4
                            shadow-xl"
                    >

                        <div class="mb-4">

                            <h3 class="text-sm font-semibold text-slate-900">
                                Select period
                            </h3>

                            <p class="mt-1 text-xs text-slate-400">
                                View alerts from a specific period.
                            </p>

                        </div>


                        {{-- ===================================== --}}
                        {{-- SPECIFIC DATE --}}
                        {{-- ===================================== --}}

                        <form
                            method="GET"
                            action="{{ url()->current() }}"
                            class="mb-3"
                        >

                            <input
                                type="hidden"
                                name="category"
                                value="{{ $category }}"
                            >

                            <label
                                class="mb-1.5 block
                                    text-xs font-medium text-slate-600"
                            >
                                Specific date
                            </label>

                            <div class="flex gap-2">

                                <input
                                    type="date"
                                    name="date"
                                    value="{{ request('date') }}"
                                    required

                                    class="h-9 min-w-0 flex-1
                                        rounded-lg border border-slate-200
                                        px-3 text-xs
                                        text-slate-700
                                        outline-none
                                        focus:border-slate-400"
                                >

                                <button
                                    type="submit"
                                    class="h-9 rounded-lg
                                        bg-slate-950 px-3
                                        text-xs font-medium text-white"
                                >
                                    Apply
                                </button>

                            </div>

                        </form>


                        {{-- ===================================== --}}
                        {{-- SPECIFIC WEEK --}}
                        {{-- ===================================== --}}

                        <form
                            method="GET"
                            action="{{ url()->current() }}"
                            class="mb-3"
                        >

                            <input
                                type="hidden"
                                name="category"
                                value="{{ $category }}"
                            >

                            <label
                                class="mb-1.5 block
                                    text-xs font-medium text-slate-600"
                            >
                                Week containing
                            </label>

                            <div class="flex gap-2">

                                <input
                                    type="date"
                                    name="week_date"
                                    value="{{ request('week_date') }}"
                                    required

                                    class="h-9 min-w-0 flex-1
                                        rounded-lg border border-slate-200
                                        px-3 text-xs text-slate-700
                                        outline-none
                                        focus:border-slate-400"
                                >

                                <button
                                    type="submit"
                                    class="h-9 rounded-lg
                                        bg-slate-950 px-3
                                        text-xs font-medium text-white"
                                >
                                    Apply
                                </button>

                            </div>

                        </form>


                        {{-- ===================================== --}}
                        {{-- SPECIFIC MONTH --}}
                        {{-- ===================================== --}}

                        <form
                            method="GET"
                            action="{{ url()->current() }}"
                            class="mb-3"
                        >

                            <input
                                type="hidden"
                                name="category"
                                value="{{ $category }}"
                            >

                            <label
                                class="mb-1.5 block
                                    text-xs font-medium text-slate-600"
                            >
                                Month
                            </label>

                            <div class="flex gap-2">

                                <input
                                    type="month"
                                    name="month"
                                    value="{{ request('month') }}"
                                    required

                                    class="h-9 min-w-0 flex-1
                                        rounded-lg border border-slate-200
                                        px-3 text-xs text-slate-700
                                        outline-none
                                        focus:border-slate-400"
                                >

                                <button
                                    type="submit"
                                    class="h-9 rounded-lg
                                        bg-slate-950 px-3
                                        text-xs font-medium text-white"
                                >
                                    Apply
                                </button>

                            </div>

                        </form>


                        {{-- ===================================== --}}
                        {{-- SPECIFIC YEAR --}}
                        {{-- ===================================== --}}

                        <form
                            method="GET"
                            action="{{ url()->current() }}"
                        >

                            <input
                                type="hidden"
                                name="category"
                                value="{{ $category }}"
                            >

                            <label
                                class="mb-1.5 block
                                    text-xs font-medium text-slate-600"
                            >
                                Year
                            </label>

                            <div class="flex gap-2">

                                <input
                                    type="number"
                                    name="year"
                                    min="2000"
                                    max="{{ now()->year }}"
                                    value="{{ request('year') }}"
                                    placeholder="{{ now()->year }}"
                                    required

                                    class="h-9 min-w-0 flex-1
                                        rounded-lg border border-slate-200
                                        px-3 text-xs text-slate-700
                                        outline-none
                                        focus:border-slate-400"
                                >

                                <button
                                    type="submit"
                                    class="h-9 rounded-lg
                                        bg-slate-950 px-3
                                        text-xs font-medium text-white"
                                >
                                    Apply
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- CATEGORY FILTERS --}}
            {{-- ================================================= --}}

            <div
                class="mt-3 flex flex-wrap
                items-center gap-1.5
                border-t border-slate-100
                pt-3">

                @php

                // =====================================
                // CATEGORY FILTER OPTIONS
                // =====================================

                $categories = [

                'all' => 'All',

                'Reports' => 'Reports',

                'Maintenance' => 'Maintenance',

                'Equipment' => 'Equipment',

                ];

                @endphp


                @foreach ($categories as $value => $label)

                <a
                    href="{{ request()->fullUrlWithQuery([
                        'category' => $value,
                        'page' => null
                    ]) }}"

                    class="inline-flex h-8
                        items-center
                        rounded-lg
                        px-3
                        text-xs font-medium
                        transition

                        {{ $category === $value
                            ? 'bg-slate-950 text-white'
                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">

                    {{ $label }}

                </a>

                @endforeach


                {{-- ============================================= --}}
                {{-- ACTIVE FILTER STATUS --}}
                {{-- ============================================= --}}

                @if (
                request('search')
                || $period !== 'today'
                || $category !== 'all'
                )

                <div class="ml-auto">

                    <a
                        href="{{ url()->current() }}"

                        class="inline-flex h-8
                            items-center gap-1.5
                            rounded-lg px-2.5
                            text-xs font-medium
                            text-slate-400
                            transition
                            hover:bg-slate-100
                            hover:text-slate-700">

                        <i
                            data-lucide="rotate-ccw"
                            class="h-3.5 w-3.5"></i>

                        Reset

                    </a>

                </div>

                @endif

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- NOTIFICATION FEED --}}
        {{-- SAME CONTAINER AS FILTERS --}}
        {{-- ===================================================== --}}

        <div class="border-t border-slate-200">

            @forelse ($notifications as $notification)

            @php

            // =====================================================
            // NOTIFICATION ICON
            // =====================================================

            $icon = match (
            $notification->notification_category
            ) {

            'Reports' =>
            'file-warning',

            'Maintenance' =>
            'wrench',

            'Equipment' =>
            'package',

            default =>
            'bell',

            };


            // =====================================================
            // NOTIFICATION URL
            // =====================================================

            //$notificationUrl =
            //$notification->notification_url
            //?: '#';

            @endphp


            {{-- ===================================================== --}}
            {{-- OPEN NOTIFICATION --}}
            {{-- MARK AS READ THEN REDIRECT --}}
            {{-- ===================================================== --}}

            <a
                href="/maintenance/notifications/{{ $notification->notification_id }}/open"

                class="group flex items-start gap-4
                    border-b border-slate-100
                    px-5 py-4 transition
                    last:border-b-0
                    hover:bg-slate-50">

                {{-- ================================================= --}}
                {{-- ICON --}}
                {{-- ================================================= --}}

                <div
                    class="mt-0.5 flex h-9 w-9
                        shrink-0 items-center justify-center
                        rounded-xl

                        {{ $notification->is_read
                            ? 'bg-slate-100 text-slate-400'
                            : 'bg-amber-50 text-amber-600' }}">

                    <i
                        data-lucide="{{ $icon }}"
                        class="h-4 w-4"></i>

                </div>


                {{-- ================================================= --}}
                {{-- CONTENT --}}
                {{-- ================================================= --}}

                <div class="min-w-0 flex-1">

                    <div
                        class="flex items-start
                            justify-between gap-4">

                        <div class="min-w-0">

                            <div
                                class="flex flex-wrap
                                    items-center gap-2">

                                <h3
                                    class="truncate text-sm
                                        font-semibold text-slate-900">
                                    {{
                                        $notification
                                            ->notification_title
                                    }}
                                </h3>


                                {{-- ================================= --}}
                                {{-- UNREAD INDICATOR --}}
                                {{-- ================================= --}}

                                @if (!$notification->is_read)

                                <span
                                    class="h-1.5 w-1.5
                                            rounded-full bg-amber-500"></span>

                                @endif

                            </div>


                            <p
                                class="mt-1
                                    text-sm leading-6
                                    text-slate-500">
                                {{
                                    $notification
                                        ->notification_message
                                }}
                            </p>

                        </div>


                        {{-- ========================================= --}}
                        {{-- DATE --}}
                        {{-- ========================================= --}}

                        <time
                            class="shrink-0
                                text-xs text-slate-400">
                            {{
                                \Carbon\Carbon::parse(
                                    $notification
                                        ->notification_created_at
                                )->diffForHumans()
                            }}
                        </time>

                    </div>


                    {{-- ================================================= --}}
                    {{-- METADATA --}}
                    {{-- ================================================= --}}

                    <div
                        class="mt-3 flex flex-wrap
                            items-center gap-2">

                        <span
                            class="rounded-full
                                bg-slate-100 px-2 py-1
                                text-[11px] font-medium
                                text-slate-500">
                            {{
                                $notification
                                    ->notification_category
                                ?? 'System'
                            }}
                        </span>


                        <span
                            class="text-[11px]
                                text-slate-400">
                            {{
                                \Carbon\Carbon::parse(
                                    $notification
                                        ->notification_created_at
                                )->format(
                                    'M d, Y • h:i A'
                                )
                            }}
                        </span>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- ARROW --}}
                {{-- ================================================= --}}

                @if ($notification->notification_url)

                <i
                    data-lucide="chevron-right"
                    class="mt-2 h-4 w-4
                            shrink-0 text-slate-300
                            transition
                            group-hover:translate-x-0.5
                            group-hover:text-slate-600"></i>

                @endif

            </a>


            @empty

            {{-- ===================================================== --}}
            {{-- EMPTY STATE --}}
            {{-- ===================================================== --}}

            <div
                class="flex min-h-[320px]
                    items-center justify-center
                    px-6 py-16 text-center">

                <div class="flex max-w-sm flex-col items-center">

                    {{-- ================================================= --}}
                    {{-- ICON --}}
                    {{-- ================================================= --}}

                    <div
                        class="flex h-12 w-12 items-center justify-center
                            rounded-2xl border border-slate-200
                            bg-slate-50 text-slate-400">
                        <i
                            data-lucide="bell-off"
                            class="h-5 w-5"></i>
                    </div>


                    {{-- ================================================= --}}
                    {{-- TITLE --}}
                    {{-- ================================================= --}}

                    <h3 class="mt-4 text-sm font-semibold text-slate-800">

                        No alerts found

                    </h3>


                    {{-- ================================================= --}}
                    {{-- DESCRIPTION --}}
                    {{-- ================================================= --}}

                    <p
                        class="mt-1.5 max-w-xs
                            text-xs leading-5 text-slate-400">

                        No alerts match the selected period and category.
                        Try changing your filters to view more activity.

                    </p>


                    {{-- ================================================= --}}
                    {{-- CLEAR FILTERS --}}
                    {{-- ONLY SHOW WHEN NOT USING DEFAULT FILTERS --}}
                    {{-- ================================================= --}}

                    @if ($period !== 'today' || $category !== 'all')

                    <a
                        href="{{ url()->current() }}"

                        class="mt-5 inline-flex h-9 items-center gap-2
                                rounded-lg border border-slate-200
                                bg-white px-3.5
                                text-xs font-semibold text-slate-600
                                shadow-sm transition
                                hover:border-slate-300
                                hover:bg-slate-50
                                hover:text-slate-900">

                        <i
                            data-lucide="rotate-ccw"
                            class="h-3.5 w-3.5"></i>

                        Clear filters

                    </a>

                    @endif

                </div>

            </div>

            @endforelse

        </div>


        {{-- ===================================================== --}}
        {{-- PAGINATION --}}
        {{-- INSIDE ALERTS PANEL --}}
        {{-- ===================================================== --}}

        @if ($notifications->hasPages())

        <div
            class="flex flex-col gap-3
                border-t border-slate-200
                bg-slate-50/50
                px-5 py-3
                sm:flex-row
                sm:items-center
                sm:justify-between">

            {{-- ============================================= --}}
            {{-- RESULT INFORMATION --}}
            {{-- ============================================= --}}

            <p class="text-xs text-slate-500">

                Showing

                <span class="font-medium text-slate-700">
                    {{ $notifications->firstItem() }}
                </span>

                to

                <span class="font-medium text-slate-700">
                    {{ $notifications->lastItem() }}
                </span>

                of

                <span class="font-medium text-slate-700">
                    {{ $notifications->total() }}
                </span>

                alerts

            </p>


            {{-- ============================================= --}}
            {{-- PAGINATION BUTTONS --}}
            {{-- ============================================= --}}

            <div class="pagination-wrapper">

                {{ $notifications->withQueryString()->links() }}

            </div>

        </div>

        @endif
    </div>

</div>

@endsection