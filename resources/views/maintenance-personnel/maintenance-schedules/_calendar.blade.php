@php
    use Carbon\Carbon;

    $today = Carbon::today();

    $effectiveStatus = function ($schedule) use ($today) {
        if (($schedule->maintenance_schedule_status ?? "Active") === "Completed") {
            return "Completed";
        }

        if (
            !empty($schedule->maintenance_schedule_next_date) &&
            Carbon::parse($schedule->maintenance_schedule_next_date)->lt($today)
        ) {
            return "Overdue";
        }

        return $schedule->maintenance_schedule_status ?? "Active";
    };

    // =====================================================
    // BUILD CALENDAR DATA FROM ALL SCHEDULES
    // DO NOT USE PAGINATED TABLE DATA HERE
    // =====================================================

    $calendarSchedules = $calendarSchedulesData

        ->filter(
            fn($schedule) =>
                !empty(
                    $schedule->maintenance_schedule_next_date
                )
        )

        ->map(
            fn($schedule) => [

                "id" =>
                    (int) $schedule->maintenance_schedule_id,

                "equipment" =>
                    $schedule->equipment_name
                    ?? "Unassigned equipment",

                "room" =>
                    $schedule->room_name
                    ?? "No room assigned",

                "title" =>
                    $schedule->maintenance_schedule_title
                    ?? "Maintenance Schedule",

                "frequency" =>
                    $schedule->maintenance_schedule_frequency
                    ?? "Not set",

                "date" =>
                    Carbon::parse(
                        $schedule->maintenance_schedule_next_date
                    )->format("Y-m-d"),

                "status" =>
                    $effectiveStatus($schedule),

                "description" =>
                    $schedule->maintenance_schedule_description
                    ?? "",

            ]
        )

        ->values();
@endphp

<div class="space-y-6">
    @if (session("success"))
        <div
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700"
        >
            {{
                session(
                    "success",
                )
            }}
        </div>
    @endif

    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div>
            <h1 class="text-4xl font-black tracking-tight text-slate-950">
                Maintenance Calendar
            </h1>
            <p class="mt-1 text-slate-500">Schedule, track, complete, and reschedule preventive maintenance using live PRISM data.</p>
        </div>

        <button
            type="button"
            onclick="openScheduleModal()"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[rgba(0,55,199,0.85)] px-5 py-3 text-sm font-semibold font-sans-serif text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-[rgba(0,44,155,0.85)]"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Schedule Maintenance
        </button>
    </div>

    <div
        class="overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-200
                md:grid-cols-2 md:divide-y-0
                xl:grid-cols-[380px_1fr_1fr_1fr]"
        >

            {{-- ===================================================== --}}
            {{-- TOTAL SCHEDULES --}}
            {{-- ===================================================== --}}

            <div class="flex items-center justify-between px-8 py-6">

                <div class="flex flex-col">

                    <p class="text-sm font-medium text-slate-500">
                        Total Schedules
                    </p>

                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalSchedules) }}
                    </h2>

                    <p class="mt-3 text-sm">

                        @if ($scheduleMonthlyPercentage === null)

                            <span class="font-semibold text-emerald-500">
                                New activity
                            </span>

                        @else

                            <span
                                class="font-semibold
                                {{
                                    $scheduleMonthlyPercentage > 0
                                        ? 'text-emerald-500'
                                        : (
                                            $scheduleMonthlyPercentage < 0
                                                ? 'text-red-500'
                                                : 'text-slate-500'
                                        )
                                }}"
                            >
                                {{
                                    $scheduleMonthlyPercentage > 0
                                        ? '+'
                                        : ''
                                }}{{ number_format($scheduleMonthlyPercentage, 2) }}%
                            </span>

                        @endif

                        <span class="text-slate-500">
                            From last month
                        </span>

                    </p>

                </div>


                {{-- ===================================================== --}}
                {{-- REAL 12 MONTH TREND GRAPH --}}
                {{-- ===================================================== --}}

                @php

                    $scheduleCounts =
                        $scheduleMonthlyTrend->pluck('count');

                    $maxScheduleCount =
                        max(
                            1,
                            $scheduleCounts->max()
                        );

                    $schedulePointCount =
                        max(
                            1,
                            $scheduleMonthlyTrend->count() - 1
                        );

                    $schedulePoints =
                        $scheduleMonthlyTrend
                            ->values()
                            ->map(function ($item, $index) use (
                                $maxScheduleCount,
                                $schedulePointCount
                            ) {

                                $x =
                                    ($index / $schedulePointCount)
                                    * 300;

                                $y =
                                    90
                                    - (
                                        ($item['count'] / $maxScheduleCount)
                                        * 75
                                    );

                                return
                                    round($x, 2)
                                    . ','
                                    . round($y, 2);

                            })
                            ->implode(' ');

                    $scheduleAreaPoints =
                        '0,100 '
                        . $schedulePoints
                        . ' 300,100';

                @endphp


                <div class="ml-6 h-20 w-40 shrink-0">

                    <svg
                        viewBox="0 0 300 100"
                        class="h-full w-full"
                        fill="none"
                    >

                        <polygon
                            points="{{ $scheduleAreaPoints }}"
                            fill="#3b82f6"
                            fill-opacity=".08"
                        />

                        <polyline
                            points="{{ $schedulePoints }}"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </svg>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- UPCOMING MAINTENANCE --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%]
                        border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Upcoming Maintenance
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($upcomingMaintenance) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-slate-900">
                        {{ number_format($upcomingMaintenancePercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of outstanding schedules
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- COMPLETED MAINTENANCE --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%]
                        border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Completed Maintenance
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($completedMaintenance) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-slate-900">
                        {{ number_format($completedMaintenancePercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of all schedules
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- OVERDUE MAINTENANCE --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%]
                        border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Overdue Maintenance
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($overdueMaintenance) }}
                </h2>

                <p class="text-base">

                    <span
                        class="font-semibold
                        {{
                            $overdueMaintenancePercentage > 0
                                ? 'text-red-500'
                                : 'text-slate-500'
                        }}"
                    >
                        {{ number_format($overdueMaintenancePercentage, 2) }}%
                    </span>

                    <span class="text-slate-500">
                        of outstanding schedules
                    </span>

                </p>

            </div>

        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- SCHEDULE LIST --}}
    {{-- ========================================================= --}}

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
    >
        {{-- ===================================================== --}}
        {{-- HEADER --}}
        {{-- ===================================================== --}}

        <div
            class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4
                sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">

                {{-- HEADER ICON --}}
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center
                        rounded-lg bg-slate-100 text-slate-600"
                >
                    <i data-lucide="list-checks" class="h-4 w-4"></i>
                </div>

                {{-- HEADER TEXT --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Schedule List
                    </h2>

                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ $schedules->total() }}
                        saved maintenance
                        {{
                            $schedules->total() === 1
                                ? "schedule"
                                : "schedules"
                        }}
                    </p>
                </div>

            </div>


            {{-- TOTAL COUNT --}}
            <div
                class="inline-flex w-fit items-center gap-2 rounded-lg
                    border border-slate-200 bg-slate-50
                    px-3 py-2 text-xs font-medium text-slate-500"
            >
                <i data-lucide="calendar-range" class="h-3.5 w-3.5"></i>

                {{ $schedules->total() }} total
            </div>

        </div>

        {{-- ===================================================== --}}
        {{-- SEARCH AND FILTER BAR --}}
        {{-- ADD BETWEEN SCHEDULE LIST HEADER AND TABLE --}}
        {{-- ===================================================== --}}

        <form
            method="GET"
            action="{{ url()->current() }}"
            class="border-b border-slate-200 px-5 py-4"
        >

            <div
                class="flex flex-col gap-3
                    lg:flex-row lg:items-center"
            >

                {{-- ================================================= --}}
                {{-- SEARCH --}}
                {{-- ================================================= --}}

                <div class="relative min-w-0 flex-1">

                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute
                            left-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search equipment, maintenance, room, or description..."

                        class="h-10 w-full rounded-lg
                            border border-slate-200
                            bg-white pl-10 pr-3
                            text-sm text-slate-700
                            outline-none transition
                            placeholder:text-slate-400
                            focus:border-slate-400
                            focus:ring-2 focus:ring-slate-100"
                    >

                </div>


                {{-- ===================================================== --}}
                {{-- FREQUENCY FILTER --}}
                {{-- VALUES MATCH CREATE SCHEDULE MODAL --}}
                {{-- ===================================================== --}}

                <div class="relative">

                    <select
                        name="frequency"

                        class="h-10 min-w-[170px]
                            appearance-none rounded-lg
                            border border-slate-200
                            bg-white pl-3 pr-9
                            text-sm text-slate-600
                            outline-none transition
                            focus:border-slate-400
                            focus:ring-2 focus:ring-slate-100"
                    >

                        <option value="">
                            All Frequencies
                        </option>


                        {{-- ================================================= --}}
                        {{-- MONTHLY --}}
                        {{-- ================================================= --}}

                        <option
                            value="Monthly"
                            @selected(request('frequency') === 'Monthly')
                        >
                            Monthly
                        </option>


                        {{-- ================================================= --}}
                        {{-- QUARTERLY --}}
                        {{-- ================================================= --}}

                        <option
                            value="Quarterly"
                            @selected(request('frequency') === 'Quarterly')
                        >
                            Quarterly
                        </option>


                        {{-- ================================================= --}}
                        {{-- SEMI ANNUAL --}}
                        {{-- MUST MATCH VALUE SAVED BY CREATE MODAL --}}
                        {{-- ================================================= --}}

                        <option
                            value="Semi annual"
                            @selected(request('frequency') === 'Semi annual')
                        >
                            Semi annual
                        </option>


                        {{-- ================================================= --}}
                        {{-- ANNUAL --}}
                        {{-- ================================================= --}}

                        <option
                            value="Annual"
                            @selected(request('frequency') === 'Annual')
                        >
                            Annual
                        </option>

                    </select>


                    {{-- ===================================================== --}}
                    {{-- DROPDOWN ICON --}}
                    {{-- ===================================================== --}}

                    <i
                        data-lucide="chevron-down"
                        class="pointer-events-none absolute
                            right-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                </div>


                {{-- ================================================= --}}
                {{-- STATUS FILTER --}}
                {{-- ================================================= --}}

                <div class="relative">

                    <select
                        name="status"

                        class="h-10 min-w-[160px]
                            appearance-none rounded-lg
                            border border-slate-200
                            bg-white pl-3 pr-9
                            text-sm text-slate-600
                            outline-none transition
                            focus:border-slate-400
                            focus:ring-2 focus:ring-slate-100"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="Active"
                            @selected(request('status') === 'Active')
                        >
                            Active
                        </option>

                        <option
                            value="Completed"
                            @selected(request('status') === 'Completed')
                        >
                            Completed
                        </option>

                        <option
                            value="Overdue"
                            @selected(request('status') === 'Overdue')
                        >
                            Overdue
                        </option>

                    </select>

                    <i
                        data-lucide="chevron-down"
                        class="pointer-events-none absolute
                            right-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                </div>


                {{-- ================================================= --}}
                {{-- APPLY --}}
                {{-- ================================================= --}}

                <button
                    type="submit"

                    class="inline-flex h-10 items-center
                        justify-center gap-2 rounded-lg
                        bg-slate-950 px-4
                        text-sm font-semibold text-white
                        transition hover:bg-slate-800"
                >

                    <i
                        data-lucide="sliders-horizontal"
                        class="h-4 w-4"
                    ></i>

                    Apply

                </button>


                {{-- ================================================= --}}
                {{-- CLEAR --}}
                {{-- ================================================= --}}

                @if (
                    request()->filled('search')
                    || request()->filled('frequency')
                    || request()->filled('status')
                )

                    <a
                        href="{{ url()->current() }}"

                        class="inline-flex h-10 items-center
                            justify-center gap-2 rounded-lg
                            border border-slate-200
                            bg-white px-4
                            text-sm font-medium text-slate-600
                            transition
                            hover:border-slate-300
                            hover:bg-slate-50
                            hover:text-slate-900"
                    >

                        <i
                            data-lucide="x"
                            class="h-4 w-4"
                        ></i>

                        Clear

                    </a>

                @endif

            </div>

        </form>


        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-left">

                {{-- ================================================= --}}
                {{-- TABLE HEADER --}}
                {{-- ================================================= --}}

                <thead
                    class="border-b border-slate-200 bg-slate-50/70"
                >
                    <tr
                        class="text-[12px] font-semibold uppercase
                            tracking-[0.08em] text-black"
                    >
                        <th class="px-5 py-3">
                            Equipment
                        </th>

                        <th class="px-5 py-3">
                            Maintenance
                        </th>

                        <th class="px-5 py-3">
                            Frequency
                        </th>

                        <th class="px-5 py-3">
                            Next Date
                        </th>

                        <th class="px-5 py-3">
                            Last Date
                        </th>

                        <th class="px-5 py-3">
                            Status
                        </th>

                        <th class="w-16 px-5 py-3 text-center">
                            Actions
                        </th>
                    </tr>
                </thead>


                {{-- ================================================= --}}
                {{-- TABLE BODY --}}
                {{-- ================================================= --}}

                <tbody class="divide-y divide-slate-100">

                    @forelse ($schedules as $schedule)

                        @php
                            $rowStatus = $effectiveStatus($schedule);

                            $statusClass = match ($rowStatus) {
                                "Completed" =>
                                    "bg-emerald-50 text-emerald-700 ring-emerald-200",

                                "Overdue" =>
                                    "bg-red-50 text-red-700 ring-red-200",

                                default =>
                                    "bg-blue-50 text-blue-700 ring-blue-200",
                            };

                            $statusDotClass = match ($rowStatus) {
                                "Completed" => "bg-emerald-500",

                                "Overdue" => "bg-red-500",

                                default => "bg-blue-500",
                            };
                        @endphp


                        <tr
                            class="group transition-colors
                                hover:bg-slate-50/70"
                        >

                            {{-- ===================================== --}}
                            {{-- EQUIPMENT --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    {{-- EQUIPMENT ICON --}}
                                    <div
                                        class="flex h-9 w-9 shrink-0
                                            items-center justify-center
                                            rounded-lg border border-slate-200
                                            bg-white text-slate-400"
                                    >
                                        <i
                                            data-lucide="wrench"
                                            class="h-4 w-4"
                                        ></i>
                                    </div>


                                    {{-- EQUIPMENT INFORMATION --}}
                                    <div class="min-w-0">

                                        <p
                                            class="max-w-[220px] truncate
                                                text-sm font-semibold
                                                text-slate-800"
                                        >
                                            {{
                                                $schedule->equipment_name
                                                    ?? "Unassigned equipment"
                                            }}
                                        </p>


                                        <div
                                            class="mt-1 flex items-center gap-1
                                                text-[11px] text-slate-400"
                                        >
                                            <i
                                                data-lucide="map-pin"
                                                class="h-3 w-3"
                                            ></i>

                                            <span class="max-w-[190px] truncate">
                                                {{
                                                    $schedule->room_name
                                                        ?? "No room assigned"
                                                }}
                                            </span>
                                        </div>

                                    </div>

                                </div>

                            </td>


                            {{-- ===================================== --}}
                            {{-- MAINTENANCE TITLE --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <p
                                    class="max-w-[230px] truncate
                                        text-sm font-medium text-slate-700"
                                    title="{{ $schedule->maintenance_schedule_title }}"
                                >
                                    {{
                                        $schedule->maintenance_schedule_title
                                    }}
                                </p>

                            </td>


                            {{-- ===================================== --}}
                            {{-- FREQUENCY --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <span
                                    class="inline-flex rounded-md
                                        bg-slate-100 px-2 py-1
                                        text-[11px] font-medium
                                        text-slate-600"
                                >
                                    {{
                                        $schedule->maintenance_schedule_frequency
                                    }}
                                </span>

                            </td>


                            {{-- ===================================== --}}
                            {{-- NEXT DATE --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-2">

                                    <i
                                        data-lucide="calendar-clock"
                                        class="h-3.5 w-3.5 text-slate-400"
                                    ></i>

                                    <span
                                        class="whitespace-nowrap
                                            text-xs font-medium text-slate-700"
                                    >
                                        {{
                                            $schedule->maintenance_schedule_next_date
                                                ? Carbon::parse(
                                                    $schedule->maintenance_schedule_next_date
                                                )->format("M d, Y")
                                                : "-"
                                        }}
                                    </span>

                                </div>

                            </td>


                            {{-- ===================================== --}}
                            {{-- LAST DATE --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <span
                                    class="whitespace-nowrap
                                        text-xs text-slate-500"
                                >
                                    {{
                                        $schedule->maintenance_schedule_last_date
                                            ? Carbon::parse(
                                                $schedule->maintenance_schedule_last_date
                                            )->format("M d, Y")
                                            : "Never"
                                    }}
                                </span>

                            </td>


                            {{-- ===================================== --}}
                            {{-- STATUS --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <span
                                    class="inline-flex items-center gap-1.5
                                        rounded-full px-2.5 py-1
                                        text-[11px] font-medium
                                        ring-1 ring-inset
                                        {{ $statusClass }}"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full
                                            {{ $statusDotClass }}"
                                    ></span>

                                    {{ $rowStatus }}

                                </span>

                            </td>


                            {{-- ===================================== --}}
                            {{-- ACTION MENU --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4 text-center">

                                <div
                                    class="relative inline-block"
                                    x-data="{ open: false }"
                                >

                                    {{-- MENU BUTTON --}}
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        @click.outside="open = false"
                                        class="flex h-8 w-8 items-center
                                            justify-center rounded-lg
                                            text-slate-400 transition
                                            hover:bg-slate-200/70
                                            hover:text-slate-700"
                                    >
                                        <i
                                            data-lucide="ellipsis"
                                            class="h-4 w-4"
                                        ></i>
                                    </button>


                                    {{-- DROPDOWN --}}
                                    <div
                                        x-cloak
                                        x-show="open"
                                        x-transition.origin.top.right
                                        class="absolute right-0 top-10 z-50
                                            w-44 overflow-hidden rounded-xl
                                            border border-slate-200 bg-white
                                            p-1.5 text-left
                                            shadow-lg shadow-slate-900/10"
                                    >

                                        {{-- VIEW --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                viewScheduleById(
                                                    {{ (int) $schedule->maintenance_schedule_id }}
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-slate-600
                                                transition
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            View details
                                        </button>


                                        {{-- COMPLETE --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                openCompleteModal(
                                                    {{ (int) $schedule->maintenance_schedule_id }},
                                                    @js(
                                                        $schedule->equipment_name
                                                            ?? "Unassigned equipment"
                                                    )
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-slate-600
                                                transition
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="circle-check"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Mark complete
                                        </button>


                                        {{-- RESCHEDULE --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                openRescheduleModal(
                                                    {{ (int) $schedule->maintenance_schedule_id }},
                                                    @js(
                                                        $schedule->equipment_name
                                                            ?? "Unassigned equipment"
                                                    )
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-slate-600
                                                transition
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="calendar-sync"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Reschedule
                                        </button>


                                        <div
                                            class="my-1 border-t border-slate-100"
                                        ></div>


                                        {{-- DELETE --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                openDeleteModal(
                                                    {{ (int) $schedule->maintenance_schedule_id }},
                                                    @js(
                                                        $schedule->maintenance_schedule_title
                                                            ?? "this schedule"
                                                    )
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-red-600
                                                transition
                                                hover:bg-red-50"
                                        >
                                            <i
                                                data-lucide="trash-2"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Delete schedule
                                        </button>

                                    </div>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-6 py-16 text-center"
                            >

                                {{-- ===================================================== --}}
                                {{-- EMPTY STATE --}}
                                {{-- ===================================================== --}}

                                <div class="mx-auto flex max-w-sm flex-col items-center">

                                    {{-- ================================================= --}}
                                    {{-- ICON --}}
                                    {{-- ================================================= --}}

                                    <div
                                        class="flex h-12 w-12 items-center justify-center
                                            rounded-2xl border border-slate-200
                                            bg-slate-50 text-slate-400"
                                    >
                                        <i
                                            data-lucide="{{
                                                request()->filled('search')
                                                || request()->filled('frequency')
                                                || request()->filled('status')
                                                    ? 'search-x'
                                                    : 'calendar-plus'
                                            }}"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>


                                    {{-- ================================================= --}}
                                    {{-- TITLE --}}
                                    {{-- ================================================= --}}

                                    <h3 class="mt-4 text-sm font-semibold text-slate-800">

                                        {{
                                            request()->filled('search')
                                            || request()->filled('frequency')
                                            || request()->filled('status')

                                                ? 'No matching schedules'

                                                : 'No maintenance schedules yet'
                                        }}

                                    </h3>


                                    {{-- ================================================= --}}
                                    {{-- DESCRIPTION --}}
                                    {{-- ================================================= --}}

                                    <p class="mt-1.5 max-w-xs text-xs leading-5 text-slate-400">

                                        {{
                                            request()->filled('search')
                                            || request()->filled('frequency')
                                            || request()->filled('status')

                                                ? 'No maintenance schedules match your current search or filters.'

                                                : 'Create a maintenance schedule to track upcoming preventive maintenance for your equipment.'
                                        }}

                                    </p>


                                    {{-- ================================================= --}}
                                    {{-- EMPTY STATE ACTION --}}
                                    {{-- ================================================= --}}

                                    @if (
                                        request()->filled('search')
                                        || request()->filled('frequency')
                                        || request()->filled('status')
                                    )

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

                                    @else

                                        <button
                                            type="button"
                                            onclick="openScheduleModal()"

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
                                                data-lucide="plus"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Create schedule
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- ===================================================== --}}
        {{-- PAGINATION --}}
        {{-- ADD BELOW THE TABLE CONTAINER --}}
        {{-- ===================================================== --}}

        @if ($schedules->hasPages())

            <div
                class="flex flex-col gap-3
                    border-t border-slate-200
                    px-5 py-4
                    sm:flex-row
                    sm:items-center
                    sm:justify-between"
            >

                {{-- ================================================= --}}
                {{-- PAGINATION INFORMATION --}}
                {{-- ================================================= --}}

                <p class="text-xs text-slate-500">

                    Showing

                    <span class="font-semibold text-slate-700">
                        {{ $schedules->firstItem() }}
                    </span>

                    to

                    <span class="font-semibold text-slate-700">
                        {{ $schedules->lastItem() }}
                    </span>

                    of

                    <span class="font-semibold text-slate-700">
                        {{ $schedules->total() }}
                    </span>

                    maintenance schedules

                </p>


                {{-- ================================================= --}}
                {{-- PAGINATION LINKS --}}
                {{-- ================================================= --}}

                <div>
                    {{ $schedules->links() }}
                </div>

            </div>

        @endif

    </section>

    {{-- ========================================================= --}}
    {{-- MINIMALIST MAINTENANCE CALENDAR --}}
    {{-- ========================================================= --}}

    <section
        class="flex min-h-[1000px] flex-col overflow-hidden
            rounded-2xl border border-slate-200 bg-white"
    >

        {{-- ===================================================== --}}
        {{-- CALENDAR TOP TOOLBAR --}}
        {{-- ===================================================== --}}

        <header
            class="flex shrink-0 flex-col gap-3 border-b border-slate-200
                px-4 py-3
                xl:flex-row xl:items-center xl:justify-between"
        >

            {{-- LEFT SIDE --}}
            <div class="flex items-center gap-2">

                <div
                    class="flex h-9 w-9 items-center justify-center
                        rounded-lg bg-slate-950 text-white"
                >
                    <i
                        data-lucide="calendar-days"
                        class="h-4 w-4"
                    ></i>
                </div>


                <div>

                    <h2
                        class="text-sm font-bold text-slate-900"
                    >
                        Preventive Maintenance Planner
                    </h2>

                    <p
                        class="mt-0.5 text-[11px] text-slate-400"
                    >
                        Manage preventive maintenance schedules
                    </p>

                </div>

            </div>


            {{-- RIGHT SIDE --}}
            <div
                class="flex flex-col gap-2
                    sm:flex-row sm:items-center"
            >

                {{-- SEARCH --}}
                <div class="relative">

                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute
                            left-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                    <input
                        id="calendarSearchInput"
                        type="search"
                        placeholder="Search..."
                        oninput="renderCalendar()"
                        class="h-9 w-full rounded-lg
                            border border-slate-200
                            bg-white pl-9 pr-3
                            text-xs font-medium text-slate-700
                            outline-none transition
                            placeholder:text-slate-400
                            focus:border-slate-400
                            sm:w-52"
                    >

                </div>


                {{-- STATUS FILTER --}}
                <select
                    id="calendarStatusFilter"
                    onchange="renderCalendar()"
                    class="h-9 rounded-lg
                        border border-slate-200
                        bg-white px-3
                        text-xs font-semibold text-slate-600
                        outline-none transition
                        focus:border-slate-400"
                >
                    <option value="all">
                        All statuses
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                    <option value="overdue">
                        Overdue
                    </option>

                </select>

            </div>

        </header>



        {{-- ===================================================== --}}
        {{-- CALENDAR NAVIGATION --}}
        {{-- ===================================================== --}}

        <div
            class="flex shrink-0 flex-col gap-3
                border-b border-slate-200
                px-4 py-3
                lg:flex-row lg:items-center
                lg:justify-between"
        >

            {{-- DATE NAVIGATION --}}
            <div class="flex items-center gap-2">

                <button
                    type="button"
                    onclick="calendarPrevious()"
                    class="flex h-8 w-8 items-center justify-center
                        rounded-lg border border-slate-200
                        bg-white text-slate-500 transition
                        hover:bg-slate-50 hover:text-slate-900"
                >
                    <i
                        data-lucide="chevron-left"
                        class="h-4 w-4"
                    ></i>
                </button>


                <h3
                    id="calendarCurrentTitle"
                    class="min-w-[120px]
                        text-sm font-bold justify-center items-center flex text-slate-900"
                ></h3>


                <button
                    type="button"
                    onclick="calendarNext()"
                    class="flex h-8 w-8 items-center justify-center
                        rounded-lg border border-slate-200
                        bg-white text-slate-500 transition
                        hover:bg-slate-50 hover:text-slate-900"
                >
                    <i
                        data-lucide="chevron-right"
                        class="h-4 w-4"
                    ></i>
                </button>

            </div>


            {{-- VIEW CONTROLS --}}
            <div class="flex items-center gap-2">

                <div
                    class="flex rounded-lg bg-slate-100 p-1"
                >

                    <button
                        type="button"
                        data-calendar-view="week"
                        class="calendar-view-button
                            rounded-md px-3 py-1.5
                            text-xs font-semibold
                            text-slate-500 transition"
                    >
                        Week
                    </button>


                    <button
                        type="button"
                        data-calendar-view="month"
                        class="calendar-view-button
                            rounded-md bg-white
                            px-3 py-1.5
                            text-xs font-bold
                            text-slate-900
                            shadow-sm transition"
                    >
                        Month
                    </button>


                    <button
                        type="button"
                        data-calendar-view="year"
                        class="calendar-view-button
                            rounded-md px-3 py-1.5
                            text-xs font-semibold
                            text-slate-500 transition"
                    >
                        Year
                    </button>

                </div>


                <button
                    type="button"
                    onclick="calendarGoToday()"
                    class="h-8 rounded-lg
                        border border-slate-200
                        bg-white px-3
                        text-xs font-semibold text-slate-600
                        transition
                        hover:bg-slate-50"
                >
                    Today
                </button>

            </div>

        </div>



        {{-- ===================================================== --}}
        {{-- CALENDAR --}}
        {{-- ===================================================== --}}

        <div
            class="flex min-h-0 flex-1 flex-col
                bg-slate-50 p-2"
        >

            {{-- WEEKDAY HEADER --}}
            <div
                id="calendarWeekdayHeader"
                class="grid shrink-0 grid-cols-7"
            >
                <div class="calendar-weekday">SUN</div>

                <div class="calendar-weekday">MON</div>

                <div class="calendar-weekday">TUE</div>

                <div class="calendar-weekday">WED</div>

                <div class="calendar-weekday">THU</div>

                <div class="calendar-weekday">FRI</div>

                <div class="calendar-weekday calendar-weekend">
                    SAT
                </div>
            </div>


            {{-- MONTH GRID --}}
            <div
                id="calendarGrid"
                class="grid min-h-0 flex-1 grid-cols-7"
            ></div>

        </div>

    </section>

    
</div>

<div
    id="calendarEventPopover"
    class="fixed z-[1200] hidden w-[320px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.16)]"
>
    <!-- ===================================== -->
    <!-- POPOVER HEADER -->
    <!-- ===================================== -->
    <div class="px-5 pb-4 pt-5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <!-- STATUS -->
                <p
                    id="popoverStatus"
                    class="mb-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.08em]"
                ></p>

                <!-- SCHEDULE TITLE -->
                <h3
                    id="popoverTitle"
                    class="truncate text-base font-semibold tracking-tight text-slate-950"
                ></h3>

                <!-- EQUIPMENT -->
                <p
                    id="popoverEquipment"
                    class="mt-1 truncate text-sm text-slate-500"
                ></p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeEventPopover()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close popover"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <!-- ===================================== -->
    <!-- SCHEDULE DETAILS -->
    <!-- ===================================== -->
    <div class="border-y border-slate-100 px-5 py-1">
        <!-- LOCATION -->
        <div class="flex items-center justify-between gap-6 py-3">
            <span class="text-sm text-slate-500">
                Location
            </span>

            <span
                id="popoverRoom"
                class="max-w-[60%] truncate text-right text-sm font-medium text-slate-900"
            ></span>
        </div>

        <!-- FREQUENCY -->
        <div
            class="flex items-center justify-between gap-6 border-t border-slate-100 py-3"
        >
            <span class="text-sm text-slate-500">
                Frequency
            </span>

            <span
                id="popoverFrequency"
                class="max-w-[60%] truncate text-right text-sm font-medium text-slate-900"
            ></span>
        </div>

        <!-- SCHEDULED DATE -->
        <div
            class="flex items-center justify-between gap-6 border-t border-slate-100 py-3"
        >
            <span class="text-sm text-slate-500">
                Scheduled date
            </span>

            <span
                id="popoverDate"
                class="max-w-[60%] truncate text-right text-sm font-medium text-slate-900"
            ></span>
        </div>
    </div>

    <!-- ===================================== -->
    <!-- POPOVER ACTIONS -->
    <!-- ===================================== -->
    <div class="flex items-center justify-end gap-2 px-5 py-4">
        <button
            type="button"
            onclick="viewSelectedSchedule()"
            class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
        >
            View details
        </button>

        <button
            type="button"
            onclick="completeSelectedSchedule()"
            class="rounded-lg bg-slate-950 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
        >
            Complete
        </button>
    </div>
</div>

@include ("maintenance-personnel.maintenance-schedules._modals")

<style>
    .calendar-weekday {
        display: flex;

        height: 38px;

        align-items: center;

        justify-content: center;

        font-size: 11px;

        font-weight: 600;

        color: rgb(71 85 105);
    }
    .calendar-weekday:last-child {
        border-right: 0;
    }
    .calendar-day {
        position: relative;

        min-width: 0;

        min-height: 0;

        overflow: hidden;

        margin: 1px;

        border: 1px solid rgb(226 232 240);

        border-radius: 5px;

        background: white;

        padding: 8px;

        text-align: left;

        transition:
            border-color 0.15s ease,
            background-color 0.15s ease;
    }
    .calendar-day:hover {
        border-color: rgb(148 163 184);

        background: rgb(248 250 252);
    }
    .calendar-day-outside {
        background: rgb(248 250 252);
    }
    .calendar-day-number {
        display: flex;

        height: 24px;

        width: 24px;

        align-items: center;

        justify-content: center;

        border-radius: 6px;

        font-size: 12px;

        font-weight: 600;

        color: rgb(30 41 59);
    }
    .calendar-day-outside .calendar-day-number {
        color: rgb(148 163 184);
    }
    .calendar-today {
        border-color: rgb(15 23 42);
    }
    .calendar-today .calendar-day-number {
        background: rgb(15 23 42);
        color: white;
    }
    /*.calendar-day-weekend {
        background-color: rgb(250 250 250);

        background-image: repeating-linear-gradient(
            135deg,
            transparent,
            transparent 5px,
            rgb(226 232 240 / 0.45) 5px,
            rgb(226 232 240 / 0.45) 6px
        );
    }*/
    .calendar-events {
        display: flex;

        flex-direction: column;

        gap: 4px;

        margin-top: 20px;
    }
    .calendar-event {
        position: relative;

        width: 100%;

        overflow: hidden;

        border-radius: 5px;

        padding: 4px 6px 4px 9px;

        text-align: left;

        transition: background-color 0.15s ease;
    }
    .calendar-event:hover {
        transform: translateY(-1px);
        filter: brightness(0.97);
    }
    .calendar-event::before {
        position: absolute;

        top: 0;

        bottom: 0;

        left: 0;

        width: 3px;

        content: "";
    }
    .calendar-event-active {
        background: rgb(239 246 255);

        color: rgb(29 78 216);
    }
    .calendar-event-active::before {
        background: rgb(59 130 246);
    }
    .calendar-event-completed {
        background: rgb(240 253 244);

        color: rgb(21 128 61);
    }
    .calendar-event-completed::before {
        background: rgb(34 197 94);
    }
    .calendar-event-overdue {
        background: rgb(254 242 242);

        color: rgb(185 28 28);
    }
    .calendar-event-overdue::before {
        background: rgb(239 68 68);
    }
    .calendar-event-title,
    .calendar-event-equipment {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .calendar-event-title {
        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

        font-size: 10px;

        font-weight: 600;
    }
    .calendar-event-equipment {
        display: none;
    }
    .calendar-year-card {
        min-height: 210px;

        border: 1px solid rgb(226 232 240);

        border-radius: 8px;

        background: white;

        padding: 14px;
    }
    .calendar-year-day {
        display: flex;

        min-height: 28px;

        align-items: center;

        justify-content: center;

        border-radius: 5px;

        font-size: 10px;

        font-weight: 600;

        color: rgb(100 116 139);
    }
    .calendar-year-day.has-events {
        background: rgb(239 246 255);

        color: rgb(29 78 216);
    }
    @media (max-width: 768px) {

        .calendar-day {
            min-height: 90px;

            padding: 5px;
        }


        .calendar-events {
            margin-top: 8px;
        }

    }
</style>

<script>
    const calendarSchedules = @js ($calendarSchedules);
    let calendarCurrentDate = new Date();
    let calendarSelectedSchedule = null;
    let calendarCurrentView = "month";
    document.addEventListener("DOMContentLoaded", () => {
        bindCalendarViewButtons();
        renderCalendar();
        if (window.lucide) lucide.createIcons();
    });
    function bindCalendarViewButtons() {
        document.querySelectorAll(".calendar-view-button").forEach((button) => {
            button.addEventListener("click", () => {
                calendarCurrentView = button.dataset.calendarView;
                document
                    .querySelectorAll(".calendar-view-button")
                    .forEach((item) => {
                        item.classList.remove(
                            "bg-white",
                            "text-slate-950",
                            "font-black",
                            "shadow-sm",
                        );
                        item.classList.add("text-slate-500", "font-bold");
                    });
                button.classList.add(
                    "bg-white",
                    "text-slate-950",
                    "font-black",
                    "shadow-sm",
                );
                button.classList.remove("text-slate-500", "font-bold");
                closeEventPopover();
                renderCalendar();
            });
        });
    }
    function renderCalendar() {
        if (calendarCurrentView === "week") return renderWeekCalendar();
        if (calendarCurrentView === "year") return renderYearCalendar();
        return renderMonthCalendar();
    }
    function renderMonthCalendar() {

        const grid =
            document.getElementById(
                "calendarGrid",
            );

        const title =
            document.getElementById(
                "calendarCurrentTitle",
            );

        const header =
            document.getElementById(
                "calendarWeekdayHeader",
            );


        if (!grid || !title) {
            return;
        }


        // SHOW WEEKDAY HEADER
        header.classList.remove("hidden");


        // MONTH GRID
        grid.className =
            "grid min-h-0 flex-1 grid-cols-7";


        // ALWAYS USE SIX CALENDAR ROWS
        grid.style.gridTemplateRows =
            "repeat(6, minmax(0, 1fr))";


        // CLEAR OLD CALENDAR
        grid.innerHTML = "";


        const year =
            calendarCurrentDate.getFullYear();


        const month =
            calendarCurrentDate.getMonth();


        // UPDATE CALENDAR TITLE
        title.textContent =
            calendarCurrentDate.toLocaleDateString(
                "en-US",
                {
                    month: "long",

                    year: "numeric",
                },
            );


        const firstDay =
            new Date(
                year,
                month,
                1,
            );


        // =========================================================
        // SUNDAY FIRST CALENDAR
        // =========================================================
        //
        // JavaScript already uses:
        //
        // Sunday    = 0
        // Monday    = 1
        // Tuesday   = 2
        // Wednesday = 3
        // Thursday  = 4
        // Friday    = 5
        // Saturday  = 6
        //
        // So no conversion is needed.
        //

        const startIndex =
            firstDay.getDay();


        const startDate =
            new Date(
                year,
                month,
                1 - startIndex,
            );


        // CREATE 42 CALENDAR CELLS
        for (
            let index = 0;
            index < 42;
            index++
        ) {

            const date =
                new Date(startDate);


            date.setDate(
                startDate.getDate() +
                index,
            );


            grid.appendChild(
                createCalendarDay(
                    date,

                    month,
                ),
            );

        }


        if (window.lucide) {
            lucide.createIcons();
        }

    }
    function renderWeekCalendar() {
        const grid = document.getElementById("calendarGrid"),
            title = document.getElementById("calendarCurrentTitle"),
            header = document.getElementById("calendarWeekdayHeader");
        if (!grid || !title) return;
        header.classList.remove("hidden");
        grid.className = "grid min-h-[1000px] grid-cols-7 bg-slate-200";
        grid.style.gridTemplateRows = "1fr";
        grid.innerHTML = "";
        const start = startOfWeek(calendarCurrentDate),
            end = new Date(start);
        end.setDate(start.getDate() + 6);
        title.textContent = `${formatShortDate(start)} - ${formatShortDate(end)}`;
        for (let i = 0; i < 7; i++) {
            const d = new Date(start);
            d.setDate(start.getDate() + i);
            grid.appendChild(createCalendarDay(d, d.getMonth(), 8));
        }
        if (window.lucide) lucide.createIcons();
    }
    function renderYearCalendar() {
        const grid = document.getElementById("calendarGrid"),
            title = document.getElementById("calendarCurrentTitle"),
            header = document.getElementById("calendarWeekdayHeader");
        if (!grid || !title) return;
        header.classList.add("hidden");
        grid.className =
            "grid min-h-[1000px] grid-cols-1 gap-4 bg-slate-100 p-4 md:grid-cols-2 xl:grid-cols-3";
        grid.style.gridTemplateRows = "";
        grid.innerHTML = "";
        const year = calendarCurrentDate.getFullYear();
        title.textContent = String(year);
        for (let month = 0; month < 12; month++)
            grid.appendChild(createYearMonthCard(year, month));
        if (window.lucide) lucide.createIcons();
    }
    function createCalendarDay(
        date,
        currentMonth,
        max = 3,
    ) {

        const day =
            document.createElement(
                "div",
            );


        day.className =
            "calendar-day";


        // =====================================================
        // OUTSIDE CURRENT MONTH
        // =====================================================

        if (
            date.getMonth() !==
            currentMonth
        ) {

            day.classList.add(
                "calendar-day-outside",
            );

        }


        // =====================================================
        // SATURDAY OR SUNDAY
        // =====================================================

        if (
            date.getDay() === 0 ||
            date.getDay() === 6
        ) {

            day.classList.add(
                "calendar-day-weekend",
            );

        }


        // =====================================================
        // TODAY
        // =====================================================

        if (
            isSameCalendarDate(
                date,

                new Date(),
            )
        ) {

            day.classList.add(
                "calendar-today",
            );

        }


        // =====================================================
        // DATE NUMBER
        // =====================================================

        const number =
            document.createElement(
                "div",
            );


        number.className =
            "calendar-day-number";


        number.textContent =
            date.getDate();


        day.appendChild(
            number,
        );


        // =====================================================
        // EVENTS
        // =====================================================

        const events =
            document.createElement(
                "div",
            );


        events.className =
            "calendar-events";


        const schedules =
            getSchedulesForDate(
                date,
            );


        schedules
            .slice(
                0,

                max,
            )
            .forEach(
                (schedule) => {

                    events.appendChild(
                        createCalendarEvent(
                            schedule,
                        ),
                    );

                },
            );


        // =====================================================
        // MORE EVENTS
        // =====================================================

        if (
            schedules.length >
            max
        ) {

            const more =
                document.createElement(
                    "button",
                );


            more.type =
                "button";


            more.className =
                "mt-1 text-left text-[10px] font-semibold text-slate-400";


            more.textContent =
                `+${schedules.length - max} more`;


            events.appendChild(
                more,
            );

        }


        day.appendChild(
            events,
        );


        return day;

    }
    function createCalendarEvent(schedule) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `calendar-event ${getCalendarEventClass(schedule.status)}`;
        button.innerHTML = `<div class="calendar-event-title">${escapeCalendarHTML(schedule.title)}</div><div class="calendar-event-equipment">${escapeCalendarHTML(schedule.equipment)}</div>`;
        button.addEventListener("click", (event) => {
            event.stopPropagation();
            openEventPopover(schedule, button);
        });
        return button;
    }
    function createYearMonthCard(year, month) {
        const card = document.createElement("div");
        card.className = "calendar-year-card rounded-3xl";
        const title = document.createElement("div");
        title.className = "mb-3 flex items-center justify-between";
        title.innerHTML = `<h4 class="font-black text-slate-900">${new Date(year, month, 1).toLocaleDateString("en-US", { month: "long" })}</h4><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-500">${getSchedulesForMonth(year, month).length} events</span>`;
        card.appendChild(title);
        const mini = document.createElement("div");
        mini.className = "grid grid-cols-7 gap-1";
        const first = new Date(year, month, 1),
            days = new Date(year, month + 1, 0).getDate();
        for (let blank = 0; blank < first.getDay(); blank++)
            mini.appendChild(document.createElement("div"));
        for (let n = 1; n <= days; n++) {
            const date = new Date(year, month, n),
                schedules = getSchedulesForDate(date),
                cell = document.createElement("button");
            cell.type = "button";
            cell.className = `calendar-year-day ${schedules.length ? "has-events" : ""}`;
            cell.textContent = n;
            cell.addEventListener("click", () => {
                calendarCurrentDate = date;
                document.querySelector('[data-calendar-view="month"]')?.click();
            });
            mini.appendChild(cell);
        }
        card.appendChild(mini);
        return card;
    }
    function getSchedulesForDate(date) {
        const search =
                document
                    .getElementById("calendarSearchInput")
                    ?.value.trim()
                    .toLowerCase() ?? "",
            status =
                document.getElementById("calendarStatusFilter")?.value ?? "all";
        return calendarSchedules.filter((schedule) => {
            const scheduleDate = parseCalendarDate(schedule.date),
                text =
                    `${schedule.title} ${schedule.equipment} ${schedule.room}`.toLowerCase();
            return (
                isSameCalendarDate(date, scheduleDate) &&
                (!search || text.includes(search)) &&
                (status === "all" || schedule.status.toLowerCase() === status)
            );
        });
    }
    function getSchedulesForMonth(year, month) {
        return calendarSchedules.filter((schedule) => {
            const date = parseCalendarDate(schedule.date);
            return date.getFullYear() === year && date.getMonth() === month;
        });
    }
    function calendarPrevious() {
        if (calendarCurrentView === "week")
            calendarCurrentDate.setDate(calendarCurrentDate.getDate() - 7);
        else if (calendarCurrentView === "year")
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear() - 1,
                0,
                1,
            );
        else
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear(),
                calendarCurrentDate.getMonth() - 1,
                1,
            );
        closeEventPopover();
        renderCalendar();
    }
    function calendarNext() {
        if (calendarCurrentView === "week")
            calendarCurrentDate.setDate(calendarCurrentDate.getDate() + 7);
        else if (calendarCurrentView === "year")
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear() + 1,
                0,
                1,
            );
        else
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear(),
                calendarCurrentDate.getMonth() + 1,
                1,
            );
        closeEventPopover();
        renderCalendar();
    }
    function calendarGoToday() {
        calendarCurrentDate = new Date();
        closeEventPopover();
        renderCalendar();
    }
    function openEventPopover(schedule, eventElement) {
        calendarSelectedSchedule = schedule;
        const popover = document.getElementById("calendarEventPopover");
        document.getElementById("popoverTitle").textContent = schedule.title;
        document.getElementById("popoverEquipment").textContent =
            schedule.equipment;
        document.getElementById("popoverRoom").textContent = schedule.room;
        document.getElementById("popoverFrequency").textContent =
            schedule.frequency;
        document.getElementById("popoverDate").textContent = parseCalendarDate(
            schedule.date,
        ).toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
        });
        const status = document.getElementById("popoverStatus");
        status.textContent = schedule.status;
        status.className = `mb-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ${getCalendarPopoverStatusClass(schedule.status)}`;
        popover.classList.remove("hidden");
        const rect = eventElement.getBoundingClientRect(),
            pop = popover.getBoundingClientRect();
        let left = rect.right + 10,
            top = rect.top;
        if (left + pop.width > window.innerWidth - 12)
            left = rect.left - pop.width - 10;
        if (top + pop.height > window.innerHeight - 12)
            top = window.innerHeight - pop.height - 12;
        popover.style.left = `${Math.max(12, left)}px`;
        popover.style.top = `${Math.max(12, top)}px`;
        if (window.lucide) lucide.createIcons();
    }
    function closeEventPopover() {
        document.getElementById("calendarEventPopover")?.classList.add("hidden");
    }
    function openScheduleModal() {
        showModal("scheduleModal");
    }
    function closeScheduleModal() {
        hideModal("scheduleModal");
    }
    function closeViewModal() {
        hideModal("viewModal");
    }
    function closeCompleteModal() {
        hideModal("completeModal");
    }
    function closeRescheduleModal() {
        hideModal("rescheduleModal");
    }
    function closeDeleteModal() {
        hideModal("deleteModal");
    }
    // =========================================================
    // FIND SCHEDULE THEN OPEN VIEW MODAL
    // =========================================================

    function viewScheduleById(scheduleId) {
        // FIND THE SCHEDULE FROM THE CALENDAR DATA
        const schedule = calendarSchedules.find(
            (item) => Number(item.id) === Number(scheduleId),
        );

        // STOP IF SCHEDULE DOES NOT EXIST
        if (!schedule) {
            console.error("Schedule not found:", scheduleId);
            return;
        }

        // OPEN THE VIEW MODAL
        viewSchedule(schedule);
}
    function viewSchedule(schedule) {
        // =====================================
        // SAFE DISPLAY VALUES
        // =====================================
        const equipment = escapeCalendarHTML(schedule.equipment || "—");
        const room = escapeCalendarHTML(schedule.room || "—");
        const title = escapeCalendarHTML(schedule.title || "—");
        const frequency = escapeCalendarHTML(schedule.frequency || "—");
        const nextDate = escapeCalendarHTML(schedule.date || "—");
        const status = escapeCalendarHTML(schedule.status || "—");
        const description = escapeCalendarHTML(
            schedule.description || "No description provided",
        );

        // =====================================
        // SCHEDULE DETAILS CONTENT
        // =====================================
        document.getElementById("scheduleDetails").innerHTML = `

            <!-- ===================================== -->
            <!-- EQUIPMENT -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-8 py-3.5">
                <span class="shrink-0 text-sm text-slate-500">
                    Equipment
                </span>

                <span class="max-w-[65%] text-right text-sm font-medium text-slate-950">
                    ${equipment}
                </span>
            </div>

            <!-- ===================================== -->
            <!-- ROOM -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-8 py-3.5">
                <span class="shrink-0 text-sm text-slate-500">
                    Room
                </span>

                <span class="max-w-[65%] text-right text-sm font-medium text-slate-900">
                    ${room}
                </span>
            </div>

            <!-- ===================================== -->
            <!-- TITLE -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-8 py-3.5">
                <span class="shrink-0 text-sm text-slate-500">
                    Title
                </span>

                <span class="max-w-[65%] text-right text-sm font-medium text-slate-900">
                    ${title}
                </span>
            </div>

            <!-- ===================================== -->
            <!-- FREQUENCY -->
            <!-- ===================================== -->
            <div class="flex items-center justify-between gap-8 py-3.5">
                <span class="text-sm text-slate-500">
                    Frequency
                </span>

                <span class="text-sm font-medium text-slate-900">
                    ${frequency}
                </span>
            </div>

            <!-- ===================================== -->
            <!-- NEXT MAINTENANCE DATE -->
            <!-- ===================================== -->
            <div class="flex items-center justify-between gap-8 py-3.5">
                <span class="text-sm text-slate-500">
                    Next maintenance
                </span>

                <span class="text-sm font-medium text-slate-900">
                    ${nextDate}
                </span>
            </div>

            <!-- ===================================== -->
            <!-- STATUS -->
            <!-- ===================================== -->
            <div class="flex items-center justify-between gap-8 py-3.5">
                <span class="text-sm text-slate-500">
                    Status
                </span>

                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                    ${status}
                </span>
            </div>

            <!-- ===================================== -->
            <!-- DESCRIPTION -->
            <!-- ===================================== -->
            <div class="py-4">
                <span class="text-sm text-slate-500">
                    Description
                </span>

                <p class="mt-2 whitespace-pre-wrap border border-dashed border-slate-500 rounded-lg break-words text-sm leading-6 text-slate-700">
                    ${description}
                </p>
            </div>
        `;

        // =====================================
        // OPEN SCHEDULE DETAILS MODAL
        // =====================================
        showModal("viewModal");
    }
    function openCompleteModal(id, name) {
        document.getElementById("completeScheduleId").value = id;
        document.getElementById("completeEquipmentName").innerText = name;
        showModal("completeModal");
    }
    function openRescheduleModal(id, name) {
        document.getElementById("rescheduleScheduleId").value = id;
        document.getElementById("rescheduleEquipmentName").innerText = name;
        showModal("rescheduleModal");
    }
    function openDeleteModal(id, title) {
        document.getElementById("deleteScheduleId").value = id;
        document.getElementById("deleteScheduleTitle").innerText =
            `Are you sure you want to delete "${title}"?`;
        showModal("deleteModal");
    }
    function viewSelectedSchedule() {
        if (!calendarSelectedSchedule) return;
        viewSchedule(calendarSelectedSchedule);
        closeEventPopover();
    }
    function completeSelectedSchedule() {
        if (!calendarSelectedSchedule) return;
        openCompleteModal(
            calendarSelectedSchedule.id,
            calendarSelectedSchedule.equipment,
        );
        closeEventPopover();
    }
    function showModal(id) {
        const modal = document.getElementById(id);
        modal?.classList.remove("hidden");
        modal?.classList.add("flex");
        if (window.lucide) lucide.createIcons();
    }
    function hideModal(id) {
        const modal = document.getElementById(id);
        modal?.classList.add("hidden");
        modal?.classList.remove("flex");
    }
    function getCalendarEventClass(status) {
        switch (status.toLowerCase()) {
            case "completed":
                return "calendar-event-completed";
            case "overdue":
                return "calendar-event-overdue";
            default:
                return "calendar-event-active";
        }
    }
    function getCalendarPopoverStatusClass(status) {
        switch (status.toLowerCase()) {
            case "completed":
                return "bg-emerald-100 text-emerald-700";
            case "overdue":
                return "bg-red-100 text-red-700";
            default:
                return "bg-blue-100 text-blue-700";
        }
    }
    function parseCalendarDate(dateString) {
        const [y, m, d] = String(dateString).split("-").map(Number);
        return new Date(y, m - 1, d);
    }
    function startOfWeek(date) {
        const start = new Date(date);
        start.setDate(date.getDate() - date.getDay());
        return start;
    }
    function formatShortDate(date) {
        return date.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    }
    function isSameCalendarDate(a, b) {
        return (
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate()
        );
    }
    function escapeCalendarHTML(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }
    document.addEventListener("click", (event) => {
        const popover = document.getElementById("calendarEventPopover");
        if (!popover) return;
        if (
            !popover.contains(event.target) &&
            !event.target.closest(".calendar-event")
        )
            closeEventPopover();
    });
    window.addEventListener("resize", closeEventPopover);
</script>
