@extends('layouts.maintenance-layout')

@section('title', 'Alerts')

@section('content')

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div
        class="flex flex-col gap-4
            lg:flex-row lg:items-end lg:justify-between"
    >

        <div>

            <p
                class="text-xs font-medium uppercase
                    tracking-[0.16em] text-slate-400"
            >
                Activity Center
            </p>

            <h1
                class="mt-1 text-3xl font-semibold
                    tracking-tight text-slate-950"
            >
                Alerts
            </h1>

            <p
                class="mt-1 max-w-2xl
                    text-sm leading-6 text-slate-500"
            >
                Review report activity, maintenance reminders,
                and equipment events across the system.
            </p>

        </div>


        {{-- ===================================================== --}}
        {{-- HEADER ACTIONS --}}
        {{-- ===================================================== --}}

        <div class="flex flex-wrap items-center gap-2">

            {{-- ================================================= --}}
            {{-- UNREAD COUNT --}}
            {{-- ================================================= --}}

            <div
                class="inline-flex items-center gap-2
                    rounded-full border border-slate-200
                    bg-white px-3 py-2
                    text-sm text-slate-600"
            >

                <span
                    class="h-2 w-2 rounded-full
                        {{ $unreadCount > 0
                            ? 'bg-amber-500'
                            : 'bg-slate-300' }}"
                ></span>

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
                    method="POST"
                >

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
                            hover:text-slate-950"
                    >

                        <i
                            data-lucide="check-check"
                            class="h-4 w-4"
                        ></i>

                        <span>
                            Mark all as read
                        </span>

                    </button>

                </form>

            @endif

        </div>

    </div>

    {{-- ===================================================== --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ===================================================== --}}

    @if (session('success'))

        <div
            class="rounded-xl border border-emerald-200
                bg-emerald-50 px-4 py-3
                text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- PERIOD FILTER CARDS --}}
    {{-- ===================================================== --}}

    <div
        class="grid grid-cols-2 gap-3
            lg:grid-cols-4"
    >

        {{-- TODAY --}}

        <a
            href="{{ request()->fullUrlWithQuery(['period' => 'today', 'page' => null]) }}"

            class="rounded-2xl border p-4 transition

                {{ $period === 'today'
                    ? 'border-slate-950 bg-slate-950 text-white'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}"
        >

            <p
                class="text-xs font-medium
                    {{ $period === 'today'
                        ? 'text-slate-300'
                        : 'text-slate-400' }}"
            >
                Today
            </p>

            <p class="mt-3 text-2xl font-semibold">
                {{ $todayCount }}
            </p>

        </a>


        {{-- THIS WEEK --}}

        <a
            href="{{ request()->fullUrlWithQuery(['period' => 'week', 'page' => null]) }}"

            class="rounded-2xl border p-4 transition

                {{ $period === 'week'
                    ? 'border-slate-950 bg-slate-950 text-white'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}"
        >

            <p
                class="text-xs font-medium
                    {{ $period === 'week'
                        ? 'text-slate-300'
                        : 'text-slate-400' }}"
            >
                This Week
            </p>

            <p class="mt-3 text-2xl font-semibold">
                {{ $weekCount }}
            </p>

        </a>


        {{-- THIS MONTH --}}

        <a
            href="{{ request()->fullUrlWithQuery(['period' => 'month', 'page' => null]) }}"

            class="rounded-2xl border p-4 transition

                {{ $period === 'month'
                    ? 'border-slate-950 bg-slate-950 text-white'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}"
        >

            <p
                class="text-xs font-medium
                    {{ $period === 'month'
                        ? 'text-slate-300'
                        : 'text-slate-400' }}"
            >
                This Month
            </p>

            <p class="mt-3 text-2xl font-semibold">
                {{ $monthCount }}
            </p>

        </a>


        {{-- THIS YEAR --}}

        <a
            href="{{ request()->fullUrlWithQuery(['period' => 'year', 'page' => null]) }}"

            class="rounded-2xl border p-4 transition

                {{ $period === 'year'
                    ? 'border-slate-950 bg-slate-950 text-white'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}"
        >

            <p
                class="text-xs font-medium
                    {{ $period === 'year'
                        ? 'text-slate-300'
                        : 'text-slate-400' }}"
            >
                This Year
            </p>

            <p class="mt-3 text-2xl font-semibold">
                {{ $yearCount }}
            </p>

        </a>

    </div>


    {{-- ===================================================== --}}
    {{-- CATEGORY FILTER --}}
    {{-- ===================================================== --}}

    <div
        class="flex flex-wrap items-center gap-2
            border-b border-slate-200 pb-4"
    >

        @php

            $categories = [

                'all' =>
                    'All',

                'Reports' =>
                    'Reports',

                'Maintenance' =>
                    'Maintenance',

                'Equipment' =>
                    'Equipment',

            ];

        @endphp


        @foreach ($categories as $value => $label)

            <a
                href="{{ request()->fullUrlWithQuery(['category' => $value, 'page' => null]) }}"

                class="rounded-full px-3 py-1.5
                    text-xs font-medium transition

                    {{ $category === $value
                        ? 'bg-slate-950 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
            >

                {{ $label }}

            </a>

        @endforeach

    </div>


    {{-- ===================================================== --}}
    {{-- NOTIFICATION FEED --}}
    {{-- ===================================================== --}}

    <div
        class="overflow-hidden rounded-2xl
            border border-slate-200 bg-white"
    >

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
                    hover:bg-slate-50"
            >

                {{-- ================================================= --}}
                {{-- ICON --}}
                {{-- ================================================= --}}

                <div
                    class="mt-0.5 flex h-9 w-9
                        shrink-0 items-center justify-center
                        rounded-xl

                        {{ $notification->is_read
                            ? 'bg-slate-100 text-slate-400'
                            : 'bg-amber-50 text-amber-600' }}"
                >

                    <i
                        data-lucide="{{ $icon }}"
                        class="h-4 w-4"
                    ></i>

                </div>


                {{-- ================================================= --}}
                {{-- CONTENT --}}
                {{-- ================================================= --}}

                <div class="min-w-0 flex-1">

                    <div
                        class="flex items-start
                            justify-between gap-4"
                    >

                        <div class="min-w-0">

                            <div
                                class="flex flex-wrap
                                    items-center gap-2"
                            >

                                <h3
                                    class="truncate text-sm
                                        font-semibold text-slate-900"
                                >
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
                                            rounded-full bg-amber-500"
                                    ></span>

                                @endif

                            </div>


                            <p
                                class="mt-1
                                    text-sm leading-6
                                    text-slate-500"
                            >
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
                                text-xs text-slate-400"
                        >
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
                            items-center gap-2"
                    >

                        <span
                            class="rounded-full
                                bg-slate-100 px-2 py-1
                                text-[11px] font-medium
                                text-slate-500"
                        >
                            {{
                                $notification
                                    ->notification_category
                                ?? 'System'
                            }}
                        </span>


                        <span
                            class="text-[11px]
                                text-slate-400"
                        >
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
                            group-hover:text-slate-600"
                    ></i>

                @endif

            </a>


        @empty

            {{-- ===================================================== --}}
            {{-- EMPTY STATE --}}
            {{-- ===================================================== --}}

            <div
                class="flex min-h-[320px]
                    items-center justify-center
                    px-6 py-16 text-center"
            >

                <div class="flex max-w-sm flex-col items-center">

                    {{-- ================================================= --}}
                    {{-- ICON --}}
                    {{-- ================================================= --}}

                    <div
                        class="flex h-12 w-12 items-center justify-center
                            rounded-2xl border border-slate-200
                            bg-slate-50 text-slate-400"
                    >
                        <i
                            data-lucide="bell-off"
                            class="h-5 w-5"
                        ></i>
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
                            text-xs leading-5 text-slate-400"
                    >

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
                                hover:text-slate-900"
                        >

                            <i
                                data-lucide="rotate-ccw"
                                class="h-3.5 w-3.5"
                            ></i>

                            Clear filters

                        </a>

                    @endif

                </div>

            </div>

        @endforelse

    </div>


    {{-- ===================================================== --}}
    {{-- PAGINATION --}}
    {{-- ===================================================== --}}

    @if ($notifications->hasPages())

        <div>

            {{
                $notifications->links()
            }}

        </div>

    @endif

</div>

@endsection