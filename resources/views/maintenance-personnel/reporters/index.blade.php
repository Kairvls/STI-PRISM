@extends ("layouts.maintenance-layout")

@section ("content")

    <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        @if (!$historyReporter)
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    onclick="openImportModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Upload CSV
                </button>
                <button
                    type="button"
                    onclick="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2
                        rounded-2xl bg-[rgba(0,55,199,0.85)]
                        px-5 py-3 text-sm font-semibold
                        text-white shadow-lg shadow-slate-900/10
                        transition
                        hover:-translate-y-0.5
                        hover:bg-[rgba(0,44,155,0.85)]"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Reporter
                </button>
            </div>
        @endif
    </div>

    @if (!$historyReporter)

    {{-- ===================================================== --}}
    {{-- REPORTER DASHBOARD CARDS --}}
    {{-- USES DATA FROM reporterDashboardData() --}}
    {{-- ===================================================== --}}

    @php

        // =====================================================
        // BUILD REPORTER GRAPH POINTS
        // =====================================================

        $reporterTrendCounts = collect($reporterMonthlyTrend)
            ->pluck('count');


        $reporterTrendMax = max(
            1,
            $reporterTrendCounts->max() ?? 0
        );


        $reporterTrendTotalPoints =
            max(
                1,
                $reporterTrendCounts->count() - 1
            );


        $reporterTrendPoints = collect($reporterMonthlyTrend)

            ->values()

            ->map(function ($item, $index) use (
                $reporterTrendMax,
                $reporterTrendTotalPoints
            ) {

                // =================================================
                // SVG X POSITION
                // =================================================

                $x =
                    ($index / $reporterTrendTotalPoints)
                    * 300;


                // =================================================
                // SVG Y POSITION
                //
                // HIGHER COUNT = HIGHER POINT ON GRAPH
                // =================================================

                $y =
                    90
                    - (
                        ($item['count'] / $reporterTrendMax)
                        * 70
                    );


                return
                    round($x, 2)
                    . ','
                    . round($y, 2);

            })

            ->implode(' ');


        // =====================================================
        // GRAPH AREA POINTS
        // =====================================================

        $reporterTrendAreaPoints =
            $reporterTrendPoints
            . ' 300,100 0,100';

    @endphp


    <div
        class="mb-6 overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr]"
        >

            {{-- ================================================= --}}
            {{-- TOTAL REPORTERS --}}
            {{-- ================================================= --}}

            <div class="flex items-center justify-between px-8 py-6">

                {{-- LEFT CONTENT --}}

                <div class="flex flex-col">

                    <p class="text-sm font-medium text-slate-500">
                        Total Reporters
                    </p>


                    <h2 class="mt-2 text-5xl font-medium text-slate-900">

                        {{ number_format($totalReporters) }}

                    </h2>


                    <p class="mt-3 text-sm">

                        {{-- ===================================== --}}
                        {{-- PREVIOUS MONTH WAS ZERO --}}
                        {{-- CURRENT MONTH HAS REPORTERS --}}
                        {{-- ===================================== --}}

                        @if (is_null($reporterMonthlyPercentage))

                            <span class="font-semibold text-emerald-500">
                                New activity
                            </span>

                            <span class="text-slate-500">
                                This month
                            </span>


                        {{-- ===================================== --}}
                        {{-- NORMAL PERCENTAGE --}}
                        {{-- ===================================== --}}

                        @else

                            <span
                                class="font-semibold
                                {{
                                    $reporterMonthlyPercentage > 0
                                        ? 'text-emerald-500'
                                        : (
                                            $reporterMonthlyPercentage < 0
                                                ? 'text-red-500'
                                                : 'text-slate-500'
                                        )
                                }}"
                            >

                                {{
                                    $reporterMonthlyPercentage > 0
                                        ? '+'
                                        : ''
                                }}

                                {{
                                    number_format(
                                        $reporterMonthlyPercentage,
                                        2
                                    )
                                }}%

                            </span>


                            <span class="text-slate-500">
                                From last month
                            </span>

                        @endif

                    </p>

                </div>


                {{-- ================================================= --}}
                {{-- REAL 12 MONTH REPORTER GRAPH --}}
                {{-- ================================================= --}}

                <div class="ml-6 h-20 w-40 shrink-0">

                    <svg
                        viewBox="0 0 300 100"
                        class="h-full w-full"
                        fill="none"
                        preserveAspectRatio="none"
                    >

                        {{-- ========================================= --}}
                        {{-- GRAPH AREA --}}
                        {{-- ========================================= --}}

                        <polygon
                            points="{{ $reporterTrendAreaPoints }}"
                            fill="#3b82f6"
                            fill-opacity=".08"
                        />


                        {{-- ========================================= --}}
                        {{-- GRAPH LINE --}}
                        {{-- ========================================= --}}

                        <polyline
                            points="{{ $reporterTrendPoints }}"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            fill="none"
                        />

                    </svg>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- WITH EMAIL --}}
            {{-- ================================================= --}}

            <div
                class="relative flex flex-col justify-between px-8 py-7"
            >

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>


                <p class="text-md font-medium text-slate-600">
                    With Email
                </p>


                <h2 class="text-5xl font-medium text-slate-900">

                    {{ number_format($reportersWithEmail) }}

                </h2>


                <p class="text-base">

                    <span class="font-semibold text-slate-900">

                        {{
                            number_format(
                                $emailCoveragePercentage,
                                2
                            )
                        }}%

                    </span>


                    <span class="text-slate-500">
                        of all reporters
                    </span>

                </p>

            </div>


            {{-- ================================================= --}}
            {{-- WITH CONTACT --}}
            {{-- ================================================= --}}

            <div
                class="relative flex flex-col justify-between px-8 py-7"
            >

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>


                <p class="text-md font-medium text-slate-600">
                    With Contact
                </p>


                <h2 class="text-5xl font-medium text-slate-900">

                    {{ number_format($reportersWithContact) }}

                </h2>


                <p class="text-base">

                    <span class="font-semibold text-slate-900">

                        {{
                            number_format(
                                $contactCoveragePercentage,
                                2
                            )
                        }}%

                    </span>


                    <span class="text-slate-500">
                        of all reporters
                    </span>

                </p>

            </div>


            {{-- ================================================= --}}
            {{-- NEW THIS MONTH --}}
            {{-- ================================================= --}}

            <div
                class="relative flex flex-col justify-between px-8 py-7"
            >

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>


                <p class="text-md font-medium text-slate-600">
                    New This Month
                </p>


                <h2 class="text-5xl font-medium text-slate-900">

                    {{ number_format($currentMonthReporters) }}

                </h2>


                {{-- ===================================================== --}}
                {{-- PREVIOUS MONTH COMPARISON --}}
                {{-- ===================================================== --}}

                <p class="text-base">

                    <span class="font-semibold text-slate-900">

                        {{ number_format($previousMonthReporters) }}

                    </span>

                    <span class="text-slate-500">

                        registered last month

                    </span>

                </p>

            </div>

        </div>
    </div>

    @endif

        

    <div class="rounded-3xl border border-slate-100 bg-white shadow-sm">
        @if (session("success"))
            <div
                class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
            >
                <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        

        <!--<div
            class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Reporters
                </h1>
                <p class="text-sm text-slate-500">Manage directory records and system contact profiles</p>
            </div>
            <button
                onclick="openCreateModal()"
                class="flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-medium text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700 active:bg-blue-800"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Reporter
            </button>
        </div>-->

        {{-- ========================================================= --}}
        {{-- REPORTER DIRECTORY --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

            @if ($historyReporter)

                {{-- ===================================================== --}}
                {{-- REPORTER HISTORY TOOLBAR --}}
                {{-- ===================================================== --}}

                <div
                    class="flex flex-col gap-4 border-b border-slate-200
                        px-5 py-4 lg:flex-row lg:items-center
                        lg:justify-between"
                >

                    {{-- ================================================= --}}
                    {{-- LEFT SIDE --}}
                    {{-- BACK BUTTON AND REPORTER INFO --}}
                    {{-- ================================================= --}}

                    <div class="flex min-w-0 items-center gap-3">

                        <a
                            href="{{ url()->current() }}"

                            class="flex h-9 w-9 shrink-0
                                items-center justify-center
                                rounded-lg border border-slate-200
                                bg-white text-slate-500
                                transition
                                hover:bg-slate-50
                                hover:text-slate-900"

                            data-tooltip="Back to reporters"
                            aria-label="Back to reporters"
                        >
                            <i
                                data-lucide="arrow-left"
                                class="h-4 w-4"
                            ></i>
                        </a>


                        <div
                            class="flex h-9 w-9 shrink-0
                                items-center justify-center
                                rounded-lg bg-slate-100
                                text-slate-600"
                        >
                            <i
                                data-lucide="history"
                                class="h-4 w-4"
                            ></i>
                        </div>


                        <div class="min-w-0">

                            <h2
                                class="truncate text-sm
                                    font-semibold text-slate-900"
                            >
                                {{ $historyReporter->reporter_full_name }}
                            </h2>


                            <p class="mt-0.5 text-xs text-slate-400">

                                {{ $historyReporter->reporter_employee_id }}

                                <span class="mx-1">
                                    ·
                                </span>

                                {{ $reportHistory->total() }}

                                {{
                                    $reportHistory->total() === 1
                                        ? 'report'
                                        : 'reports'
                                }}

                            </p>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- RIGHT SIDE --}}
                    {{-- HISTORY SEARCH AND STATUS FILTER --}}
                    {{-- ================================================= --}}

                    <form
                        method="GET"
                        action="{{ url()->current() }}"

                        class="flex w-full flex-col gap-2
                            sm:flex-row lg:w-auto lg:items-center"
                    >

                        {{-- KEEP HISTORY MODE ACTIVE --}}

                        <input
                            type="hidden"
                            name="history"
                            value="{{ $historyReporter->reporter_id }}"
                        >


                        {{-- HISTORY SEARCH --}}

                        <div class="relative w-full sm:w-[240px]">

                            <i
                                data-lucide="search"
                                class="pointer-events-none absolute
                                    left-3 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400"
                            ></i>


                            <input
                                type="search"
                                name="history_search"
                                value="{{ request('history_search') }}"
                                placeholder="Search history..."

                                class="h-9 w-full rounded-lg
                                    border border-slate-200
                                    bg-white pl-9 pr-3
                                    text-xs font-medium text-slate-700
                                    outline-none transition
                                    placeholder:text-slate-400
                                    focus:border-slate-400"
                            >

                        </div>


                        {{-- HISTORY STATUS --}}

                        <div class="relative">

                            <select
                                name="history_status"
                                class="h-9 min-w-[150px]
                                    appearance-none rounded-lg
                                    border border-slate-200
                                    bg-white pl-3 pr-9
                                    text-xs font-medium text-slate-600
                                    outline-none transition
                                    focus:border-slate-400"
                            >
                                <option value="">All Status</option>

                                @foreach ([
                                    'Pending',
                                    'Processing',
                                    'Resolved',
                                    'For Replacement',
                                    'Rejected',
                                ] as $historyStatus)

                                    <option
                                        value="{{ $historyStatus }}"
                                        @selected(
                                            request('history_status') === $historyStatus
                                        )
                                    >
                                        {{ $historyStatus }}
                                    </option>

                                @endforeach
                            </select>


                            <i
                                data-lucide="chevron-down"
                                class="pointer-events-none absolute
                                    right-3 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400"
                            ></i>

                        </div>


                        {{-- APPLY HISTORY FILTERS --}}

                        <button
                            type="submit"

                            class="inline-flex h-9 shrink-0
                                items-center justify-center gap-2
                                rounded-lg bg-slate-950 px-4
                                text-sm font-semibold text-white
                                transition hover:bg-slate-800"
                        >
                            <i
                                data-lucide="sliders-horizontal"
                                class="h-4 w-4"
                            ></i>

                            Apply
                        </button>


                        {{-- CLEAR HISTORY FILTERS --}}

                        @if (
                            request()->filled('history_search')
                            || request()->filled('history_status')
                        )

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'history_search' => null,
                                    'history_status' => null,
                                    'history_page' => null,
                                ]) }}"

                                class="inline-flex h-9 w-9 shrink-0
                                    items-center justify-center
                                    rounded-lg border border-slate-200
                                    bg-white text-slate-500
                                    transition
                                    hover:bg-slate-50
                                    hover:text-slate-900"

                                data-tooltip="Clear history filters"
                                aria-label="Clear history filters"
                            >
                                <i
                                    data-lucide="x"
                                    class="h-4 w-4"
                                ></i>
                            </a>

                        @endif

                    </form>

                </div>

                {{-- ===================================================== --}}
                {{-- REPORTER HISTORY TABLE --}}
                {{-- SHOWS REPORTS SUBMITTED BY SELECTED REPORTER --}}
                {{-- ===================================================== --}}

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1050px] text-left">

                        {{-- ================================================= --}}
                        {{-- TABLE HEADER --}}
                        {{-- ================================================= --}}

                        <thead
                            class="border-b border-slate-200
                                bg-slate-50/70"
                        >

                            <tr
                                class="text-[12px] font-semibold uppercase
                                    tracking-[0.08em] text-black"
                            >

                                <th class="px-5 py-3">
                                    Report
                                </th>

                                <th class="px-5 py-3">
                                    Issue
                                </th>

                                <th class="px-5 py-3">
                                    Equipment
                                </th>

                                <th class="px-5 py-3">
                                    Room
                                </th>

                                <th class="px-5 py-3">
                                    Urgency
                                </th>

                                <th class="px-5 py-3">
                                    Status
                                </th>

                                <th class="px-5 py-3">
                                    Submitted
                                </th>

                            </tr>

                        </thead>


                        {{-- ================================================= --}}
                        {{-- TABLE BODY --}}
                        {{-- ================================================= --}}

                        <tbody class="divide-y divide-slate-100">

                            @forelse ($reportHistory as $report)

                                @php

                                    // =================================================
                                    // DISPLAY EQUIPMENT NAME
                                    // USE UNLISTED EQUIPMENT AS FALLBACK
                                    // =================================================

                                    $historyEquipmentName =
                                        $report->equipment_name
                                        ?? $report->report_unlisted_equipment_name
                                        ?? 'No equipment';


                                    // =================================================
                                    // REPORT STATUS CLASSES
                                    // =================================================

                                    $historyStatusClass = match (
                                        $report->report_current_status
                                    ) {

                                        'Pending' =>
                                            'bg-amber-50 text-amber-700 ring-amber-200',

                                        'Processing' =>
                                            'bg-blue-50 text-blue-700 ring-blue-200',

                                        'Resolved' =>
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200',

                                        'For Replacement' =>
                                            'bg-violet-50 text-violet-700 ring-violet-200',

                                        'Rejected' =>
                                            'bg-rose-50 text-rose-700 ring-rose-200',

                                        default =>
                                            'bg-slate-100 text-slate-600 ring-slate-200',

                                    };


                                    // =================================================
                                    // REPORT STATUS DOT CLASSES
                                    // =================================================

                                    $historyStatusDotClass = match (
                                        $report->report_current_status
                                    ) {

                                        'Pending' =>
                                            'bg-amber-500',

                                        'Processing' =>
                                            'bg-blue-500',

                                        'Resolved' =>
                                            'bg-emerald-500',

                                        'For Replacement' =>
                                            'bg-violet-500',

                                        'Rejected' =>
                                            'bg-rose-500',

                                        default =>
                                            'bg-slate-400',

                                    };


                                    // =================================================
                                    // URGENCY CLASSES
                                    // =================================================

                                    $historyUrgencyClass =
                                        $report->report_urgency_level === 'Urgent'

                                            ? 'bg-rose-50 text-rose-700 ring-rose-200'

                                            : 'bg-slate-100 text-slate-600 ring-slate-200';

                                @endphp


                                <tr
                                    class="transition-colors
                                        hover:bg-slate-50/70"
                                >

                                    {{-- ===================================== --}}
                                    {{-- REPORT ID --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <span
                                            class="font-mono text-sm
                                                font-medium tracking-wider
                                                text-slate-700"
                                        >
                                            #{{ $report->report_id }}
                                        </span>

                                    </td>


                                    {{-- ===================================== --}}
                                    {{-- ISSUE --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <div class="max-w-[260px]">

                                            <p
                                                class="truncate text-sm
                                                    font-semibold text-slate-800"
                                            >
                                                {{
                                                    $report->report_suggested_issue
                                                    ?? 'Unspecified issue'
                                                }}
                                            </p>


                                            <p
                                                class="mt-1 truncate
                                                    text-xs text-slate-400"
                                                data-tooltip="{{ $report->report_problem_description }}"
                                            >
                                                {{
                                                    $report->report_problem_description
                                                    ?? 'No description provided'
                                                }}
                                            </p>

                                        </div>

                                    </td>


                                    {{-- ===================================== --}}
                                    {{-- EQUIPMENT --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <div class="flex items-center gap-2">

                                            <i
                                                data-lucide="monitor-cog"
                                                class="h-3.5 w-3.5 shrink-0
                                                    text-slate-400"
                                            ></i>


                                            <span
                                                class="max-w-[180px] truncate
                                                    text-xs text-slate-600"
                                                data-tooltip="{{ $historyEquipmentName }}"
                                            >
                                                {{ $historyEquipmentName }}
                                            </span>

                                        </div>

                                    </td>


                                    {{-- ===================================== --}}
                                    {{-- ROOM --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <div class="flex items-center gap-2">

                                            <i
                                                data-lucide="map-pin"
                                                class="h-3.5 w-3.5 shrink-0
                                                    text-slate-400"
                                            ></i>


                                            <span
                                                class="max-w-[150px] truncate
                                                    text-xs text-slate-600"
                                            >
                                                {{
                                                    $report->room_name
                                                    ?? 'No room'
                                                }}
                                            </span>

                                        </div>

                                    </td>


                                    {{-- ===================================== --}}
                                    {{-- URGENCY --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <span
                                            class="inline-flex items-center
                                                rounded-full px-2.5 py-1
                                                text-[11px] font-medium
                                                ring-1 ring-inset
                                                {{ $historyUrgencyClass }}"
                                        >
                                            {{ $report->report_urgency_level }}
                                        </span>

                                    </td>


                                    {{-- ===================================== --}}
                                    {{-- STATUS --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <span
                                            class="inline-flex items-center gap-1.5
                                                whitespace-nowrap rounded-full
                                                px-2.5 py-1
                                                text-[11px] font-medium
                                                ring-1 ring-inset
                                                {{ $historyStatusClass }}"
                                        >

                                            <span
                                                class="h-1.5 w-1.5 rounded-full
                                                    {{ $historyStatusDotClass }}"
                                            ></span>


                                            {{ $report->report_current_status }}

                                        </span>

                                    </td>


                                    {{-- ===================================== --}}
                                    {{-- SUBMITTED DATE --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <div class="whitespace-nowrap">

                                            <p
                                                class="text-xs font-medium
                                                    text-slate-700"
                                            >
                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $report->report_submitted_at
                                                    )->format('M d, Y')
                                                }}
                                            </p>


                                            <p
                                                class="mt-0.5 text-[11px]
                                                    text-slate-400"
                                            >
                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $report->report_submitted_at
                                                    )->format('h:i A')
                                                }}
                                            </p>

                                        </div>

                                    </td>

                                </tr>


                            @empty

                                {{-- ========================================= --}}
                                {{-- EMPTY HISTORY STATE --}}
                                {{-- ========================================= --}}

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-16 text-center"
                                    >

                                        <div
                                            class="mx-auto flex max-w-sm
                                                flex-col items-center"
                                        >

                                            {{-- ICON --}}

                                            <div
                                                class="flex h-12 w-12
                                                    items-center justify-center
                                                    rounded-2xl
                                                    border border-slate-200
                                                    bg-slate-50
                                                    text-slate-400"
                                            >
                                                <i
                                                    data-lucide="{{
                                                        request()->filled('history_search')
                                                        || request()->filled('history_status')
                                                            ? 'search-x'
                                                            : 'history'
                                                    }}"
                                                    class="h-5 w-5"
                                                ></i>
                                            </div>


                                            {{-- TITLE --}}

                                            <h3
                                                class="mt-4 text-sm
                                                    font-semibold text-slate-800"
                                            >

                                                {{
                                                    request()->filled('history_search')
                                                    || request()->filled('history_status')

                                                        ? 'No matching reports'

                                                        : 'No report history'
                                                }}

                                            </h3>


                                            {{-- DESCRIPTION --}}

                                            <p
                                                class="mt-1.5 max-w-xs
                                                    text-xs leading-5
                                                    text-slate-400"
                                            >

                                                {{
                                                    request()->filled('history_search')
                                                    || request()->filled('history_status')

                                                        ? 'No reports match the current history search or status filter.'

                                                        : 'Reports submitted by this reporter will appear here.'
                                                }}

                                            </p>


                                            {{-- CLEAR FILTERS --}}

                                            @if (
                                                request()->filled('history_search')
                                                || request()->filled('history_status')
                                            )

                                                <a
                                                    href="{{ request()->fullUrlWithQuery([
                                                        'history_search' => null,
                                                        'history_status' => null,
                                                        'history_page' => null,
                                                    ]) }}"

                                                    class="mt-5 inline-flex h-9
                                                        items-center gap-2
                                                        rounded-lg
                                                        border border-slate-200
                                                        bg-white px-3.5
                                                        text-xs font-semibold
                                                        text-slate-600
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

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- ===================================================== --}}
                {{-- REPORTER HISTORY PAGINATION --}}
                {{-- ===================================================== --}}

                @if ($reportHistory->hasPages())

                    <div
                        class="flex flex-col gap-3
                            border-t border-slate-200
                            px-5 py-4
                            sm:flex-row sm:items-center
                            sm:justify-between"
                    >

                        <p class="text-xs text-slate-500">

                            Showing

                            <span class="font-semibold text-slate-700">
                                {{ $reportHistory->firstItem() }}
                            </span>

                            to

                            <span class="font-semibold text-slate-700">
                                {{ $reportHistory->lastItem() }}
                            </span>

                            of

                            <span class="font-semibold text-slate-700">
                                {{ $reportHistory->total() }}
                            </span>

                            reports

                        </p>


                        <div>
                            {{ $reportHistory->links() }}
                        </div>

                    </div>

                @endif

            @else

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}

            {{-- ===================================================== --}}
            {{-- REPORTER DIRECTORY TOOLBAR --}}
            {{-- LEFT: TITLE --}}
            {{-- MIDDLE: STATUS TABS --}}
            {{-- RIGHT: SEARCH --}}
            {{-- ===================================================== --}}

            <div
                class="flex flex-col gap-4 border-b border-slate-200
                    px-5 py-4 xl:flex-row xl:items-center"
            >

                {{-- ================================================= --}}
                {{-- LEFT SIDE: TITLE --}}
                {{-- ================================================= --}}

                <div class="flex shrink-0 items-center gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center
                            justify-center rounded-lg
                            bg-slate-100 text-slate-600"
                    >
                        <i
                            data-lucide="users"
                            class="h-4 w-4"
                        ></i>
                    </div>


                    <div>

                        <h2 class="text-sm font-semibold text-slate-900">
                            Reporter Directory
                        </h2>


                        <p class="mt-0.5 text-xs text-slate-400">

                            {{ $reporters->total() }}

                            {{
                                $reporters->total() === 1
                                    ? 'registered reporter'
                                    : 'registered reporters'
                            }}

                        </p>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- MIDDLE: STATUS TABS --}}
                {{-- TAKES AVAILABLE SPACE --}}
                {{-- SCROLLS HORIZONTALLY IF NEEDED --}}
                {{-- ================================================= --}}

                <div class="min-w-0 flex-1 xl:ml-4">

                    <div
                        class="flex items-center gap-1
                            overflow-x-auto whitespace-nowrap
                            [scrollbar-width:none]
                            [&::-webkit-scrollbar]:hidden"
                    >

                        {{-- ALL REPORTERS --}}

                        <a
                            href="{{ request()->fullUrlWithQuery([
                                'status' => null,
                                'page' => null,
                            ]) }}"

                            class="shrink-0 rounded-lg px-3 py-2
                                text-sm transition
                                {{
                                    !request()->filled('status')
                                        ? 'bg-slate-900 font-medium text-white'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                }}"
                        >
                            All
                        </a>


                        {{-- ACTIVE --}}

                        <a
                            href="{{ request()->fullUrlWithQuery([
                                'status' => 'Active',
                                'page' => null,
                            ]) }}"

                            class="shrink-0 rounded-lg px-3 py-2
                                text-sm transition
                                {{
                                    request('status') === 'Active'
                                        ? 'bg-slate-900 font-medium text-white'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                }}"
                        >
                            Active
                        </a>


                        {{-- INACTIVE --}}

                        <a
                            href="{{ request()->fullUrlWithQuery([
                                'status' => 'Inactive',
                                'page' => null,
                            ]) }}"

                            class="shrink-0 rounded-lg px-3 py-2
                                text-sm transition
                                {{
                                    request('status') === 'Inactive'
                                        ? 'bg-slate-900 font-medium text-white'
                                        : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                }}"
                        >
                            Inactive
                        </a>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- RIGHT SIDE: SEARCH --}}
                {{-- ================================================= --}}

                <form
                    method="GET"
                    action="{{ url()->current() }}"

                    class="flex w-full shrink-0 items-center gap-2
                        sm:w-auto"
                >

                    {{-- ================================================= --}}
                    {{-- PRESERVE SELECTED STATUS TAB --}}
                    {{-- ================================================= --}}

                    @if (request()->filled('status'))

                        <input
                            type="hidden"
                            name="status"
                            value="{{ request('status') }}"
                        >

                    @endif


                    {{-- ================================================= --}}
                    {{-- SEARCH INPUT --}}
                    {{-- ================================================= --}}

                    <div class="relative min-w-0 flex-1 sm:w-[260px] sm:flex-none">

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
                            placeholder="Search reporters..."

                            class="h-9 w-full rounded-lg
                                border border-slate-200
                                bg-white pl-9 pr-3
                                text-xs font-medium text-slate-700
                                outline-none transition
                                placeholder:text-slate-400
                                focus:border-slate-400"
                        >

                    </div>


                    {{-- ================================================= --}}
                    {{-- SEARCH BUTTON --}}
                    {{-- ================================================= --}}

                    <button
                        type="submit"

                        class="inline-flex h-9 shrink-0
                            items-center justify-center gap-2
                            rounded-lg bg-slate-950 px-4
                            text-sm font-semibold text-white
                            transition hover:bg-slate-800"
                    >

                        <i
                            data-lucide="search"
                            class="h-4 w-4"
                        ></i>

                        Search

                    </button>


                    {{-- ================================================= --}}
                    {{-- CLEAR SEARCH --}}
                    {{-- KEEPS CURRENT STATUS TAB --}}
                    {{-- ================================================= --}}

                    @if (request()->filled('search'))

                        <a
                            href="{{ request()->fullUrlWithQuery([
                                'search' => null,
                                'page' => null,
                            ]) }}"

                            class="inline-flex h-9 w-9 shrink-0
                                items-center justify-center
                                rounded-lg border border-slate-200
                                bg-white text-slate-500
                                transition
                                hover:bg-slate-50
                                hover:text-slate-900"

                            data-tooltip="Clear search"
                            aria-label="Clear search"
                        >

                            <i
                                data-lucide="x"
                                class="h-4 w-4"
                            ></i>

                        </a>

                    @endif

                </form>

            </div>



            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="overflow-x-auto">

                <table class="w-full min-w-[950px] text-left">

                    {{-- ================================================= --}}
                    {{-- TABLE HEADER --}}
                    {{-- ================================================= --}}

                    <thead class="border-b border-slate-200 bg-slate-50/70">

                        <tr
                            class="text-[12px] font-semibold uppercase
                                tracking-[0.08em] text-black"
                        >
                            <th class="px-5 py-3">
                                Employee ID
                            </th>

                            <th class="px-5 py-3">
                                Reporter
                            </th>

                            <th class="px-5 py-3">
                                Type
                            </th>

                            <th class="px-5 py-3">
                                Email Address
                            </th>

                            <th class="px-5 py-3">
                                Contact
                            </th>

                            <th class="px-5 py-3 ">
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

                    <tbody
                        id="reporterTable"
                        class="divide-y divide-slate-100"
                    >

                        @forelse ($reporters as $reporter)

                            @php
                                // CHANGE THIS IF YOUR DATABASE COLUMN
                                // USES A DIFFERENT STATUS NAME

                                $reporterStatus = $reporter->reporter_status;


                                $reporterStatusClass =
                                    strtolower($reporterStatus) === "active"
                                        ? "bg-emerald-50 text-emerald-700 ring-emerald-200"
                                        : "bg-slate-100 text-slate-600 ring-slate-200";


                                $reporterStatusDotClass =
                                    strtolower($reporterStatus) === "active"
                                        ? "bg-emerald-500"
                                        : "bg-slate-400";

                                $nameParts = \App\Support\ReporterImport::splitFullName($reporter->reporter_full_name);
                                $editFirstName = $reporter->reporter_first_name ?: $nameParts['first'];
                                $editMiddleName = $reporter->reporter_middle_name ?: $nameParts['middle'];
                                $editLastName = $reporter->reporter_last_name ?: $nameParts['last'];
                            @endphp


                            <tr
                                class="reporter-row group transition-colors
                                    hover:bg-slate-50/70"

                                data-status="{{ strtolower($reporterStatus) }}"
                            >

                                {{-- ===================================== --}}
                                {{-- EMPLOYEE ID --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="font-mono text-sm font-medium
                                            tracking-wider text-black"
                                    >
                                        {{ $reporter->reporter_employee_id }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- REPORTER --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-3">

                                        {{-- AVATAR --}}
                                        <div
                                            class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-full bg-slate-100
                                                text-xs font-semibold
                                                text-slate-600"
                                        >
                                            {{
                                                strtoupper(
                                                    substr(
                                                        $reporter->reporter_full_name,
                                                        0,
                                                        1
                                                    )
                                                )
                                            }}
                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="max-w-[220px] truncate
                                                    text-sm font-semibold
                                                    text-slate-800"
                                            >
                                                {{ $reporter->reporter_full_name }}
                                            </p>


                                            <p
                                                class="mt-0.5 text-[11px]
                                                    text-slate-400"
                                            >
                                                Reporter account
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- TYPE --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    @if ($reporter->reporter_employment_type)
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                            {{ $reporter->reporter_employment_type }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>



                                {{-- ===================================== --}}
                                {{-- EMAIL --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-2">

                                        <i
                                            data-lucide="mail"
                                            class="h-3.5 w-3.5 shrink-0
                                                text-slate-400"
                                        ></i>

                                        <span
                                            class="max-w-[240px] truncate
                                                text-xs text-slate-600"
                                        >
                                            {{
                                                $reporter->reporter_email_address
                                                    ?? "No email provided"
                                            }}
                                        </span>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- CONTACT --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-2">

                                        <i
                                            data-lucide="phone"
                                            class="h-3.5 w-3.5 shrink-0
                                                text-slate-400"
                                        ></i>

                                        <span
                                            class="whitespace-nowrap
                                                text-xs text-slate-600"
                                        >
                                            {{
                                                $reporter->reporter_contact_number
                                                    ?? "No contact provided"
                                            }}
                                        </span>

                                    </div>

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
                                            {{ $reporterStatusClass }}"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full
                                                {{ $reporterStatusDotClass }}"
                                        ></span>

                                        {{ $reporterStatus }}

                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center justify-center gap-2">

                                        {{-- ================================= --}}
                                        {{-- VIEW BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"

                                            onclick="viewReporter(
                                                @js($reporter->reporter_employee_id),
                                                @js($reporter->reporter_full_name),
                                                @js($reporter->reporter_employment_type),
                                                @js($reporter->reporter_email_address),
                                                @js($reporter->reporter_contact_number)
                                            )"

                                            class="flex h-9 w-9 shrink-0 items-center
                                                justify-center rounded-xl
                                                bg-slate-100 text-slate-600
                                                transition
                                                hover:bg-slate-200
                                                hover:text-slate-900
                                                active:scale-95"

                                            data-tooltip="View reporter details"

                                            aria-label="View reporter details"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="h-3.5 w-3.5"
                                            ></i>
                                        </button>

                                        {{-- ===================================================== --}}
                                        {{-- REPORTER HISTORY BUTTON --}}
                                        {{-- SWITCHES CURRENT TABLE INTO HISTORY MODE --}}
                                        {{-- ===================================================== --}}

                                        <a
                                            href="{{ request()->fullUrlWithQuery([
                                                'history' => $reporter->reporter_id,

                                                'history_search' => null,
                                                'history_status' => null,
                                                'history_page' => null,

                                                'page' => null,
                                            ]) }}"

                                            class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-xl bg-slate-100
                                                text-slate-600 transition
                                                hover:bg-slate-200
                                                hover:text-slate-900
                                                active:scale-95"

                                            data-tooltip="View reporter history"
                                            aria-label="View reporter history"
                                        >
                                            <i
                                                data-lucide="history"
                                                class="h-3.5 w-3.5"
                                            ></i>
                                        </a>


                                        {{-- ===================================================== --}}
                                        {{-- EDIT BUTTON --}}
                                        {{-- ONLY ACTIVE REPORTERS CAN BE EDITED --}}
                                        {{-- ===================================================== --}}

                                        @if ($reporter->reporter_status === 'Active')

                                            <button
                                                type="button"

                                                onclick="editReporter(
                                                    @js($reporter->reporter_id),
                                                    @js($reporter->reporter_employee_id),
                                                    @js($editFirstName),
                                                    @js($editMiddleName),
                                                    @js($editLastName),
                                                    @js($reporter->reporter_employment_type ?? ''),
                                                    @js($reporter->reporter_email_address),
                                                    @js($reporter->reporter_contact_number)
                                                )"

                                                class="flex h-9 w-9 items-center
                                                    justify-center rounded-lg
                                                    bg-[#FFF200] text-black
                                                    transition
                                                    hover:bg-[#E6E600]
                                                    active:scale-95"

                                                data-tooltip="Edit reporter"

                                                aria-label="Edit reporter"
                                            >

                                                <i
                                                    data-lucide="edit-3"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                            </button>

                                        @endif


                                        {{-- ===================================================== --}}
                                        {{-- REPORTER STATUS ACTION --}}
                                        {{-- OPENS DEACTIVATE OR REACTIVATE MODAL --}}
                                        {{-- ===================================================== --}}

                                        @if ($reporter->reporter_status === 'Active')

                                            <button
                                                type="button"

                                                onclick="openReporterStatusModal(
                                                    @js($reporter->reporter_id),
                                                    @js($reporter->reporter_full_name),
                                                    'deactivate'
                                                )"

                                                class="flex h-9 w-9 items-center
                                                    justify-center rounded-xl
                                                    bg-amber-50 text-amber-700
                                                    ring-1 ring-inset ring-amber-200
                                                    transition
                                                    hover:bg-amber-100
                                                    active:scale-95"

                                                data-tooltip="Deactivate reporter"
                                                aria-label="Deactivate reporter"
                                            >
                                                <i
                                                    data-lucide="user-x"
                                                    class="h-3.5 w-3.5"
                                                ></i>
                                            </button>

                                        @else

                                            <button
                                                type="button"

                                                onclick="openReporterStatusModal(
                                                    @js($reporter->reporter_id),
                                                    @js($reporter->reporter_full_name),
                                                    'reactivate'
                                                )"

                                                class="flex h-9 w-9 items-center
                                                    justify-center rounded-xl
                                                    bg-emerald-50 text-emerald-700
                                                    ring-1 ring-inset ring-emerald-200
                                                    transition
                                                    hover:bg-emerald-100
                                                    active:scale-95"

                                                data-tooltip="Reactivate reporter"
                                                aria-label="Reactivate reporter"
                                            >
                                                <i
                                                    data-lucide="user-check"
                                                    class="h-3.5 w-3.5"
                                                ></i>
                                            </button>

                                        @endif

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="6"
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
                                                    || request()->filled('status')
                                                        ? 'search-x'
                                                        : 'users'
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
                                                || request()->filled('status')

                                                    ? 'No matching reporters'

                                                    : 'No reporters yet'
                                            }}

                                        </h3>


                                        {{-- ================================================= --}}
                                        {{-- DESCRIPTION --}}
                                        {{-- ================================================= --}}

                                        <p
                                            class="mt-1.5 max-w-xs text-xs leading-5
                                                text-slate-400"
                                        >

                                            {{
                                                request()->filled('search')
                                                || request()->filled('status')

                                                    ? 'No reporters match your current search or status filter.'

                                                    : 'Reporter accounts added to the directory will appear here.'
                                            }}

                                        </p>


                                        {{-- ================================================= --}}
                                        {{-- CLEAR FILTERS --}}
                                        {{-- ONLY SHOW WHEN SEARCHING OR FILTERING --}}
                                        {{-- ================================================= --}}

                                        @if (
                                            request()->filled('search')
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
            {{-- ADD HERE --}}
            {{-- ===================================================== --}}

            @if ($reporters->hasPages())

                <div
                    class="flex flex-col gap-3 border-t border-slate-200
                        px-5 py-4 sm:flex-row sm:items-center
                        sm:justify-between"
                >

                    <p class="text-xs text-slate-500">

                        Showing

                        <span class="font-semibold text-slate-700">
                            {{ $reporters->firstItem() }}
                        </span>

                        to

                        <span class="font-semibold text-slate-700">
                            {{ $reporters->lastItem() }}
                        </span>

                        of

                        <span class="font-semibold text-slate-700">
                            {{ $reporters->total() }}
                        </span>

                        reporters

                    </p>


                    <div>
                        {{ $reporters->links() }}
                    </div>

                </div>

            @endif

            @endif

        </section>
    </div>

    @if (session('import_result'))
        @php
            $importResult = session('import_result');
            $importCreated = (int) ($importResult['created'] ?? 0);
            $importSkipped = (int) ($importResult['skipped'] ?? 0);
            $importErrors = $importResult['errors'] ?? [];
        @endphp
        <div
            id="importResultModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1220]/70 p-4"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
                <div class="px-6 pb-4 pt-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $importSkipped > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                        <i data-lucide="{{ $importSkipped > 0 ? 'alert-triangle' : 'check' }}" class="h-5 w-5"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">
                        {{ $importSkipped > 0 ? 'Import finished with issues' : 'Import complete' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Review what was added and what was skipped.
                    </p>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-emerald-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-emerald-700">Added</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $importCreated }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Skipped</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $importSkipped }}</p>
                        </div>
                    </div>

                    @if (count($importErrors) > 0)
                        <div class="mt-5 max-h-52 overflow-y-auto rounded-xl border border-slate-200">
                            <table class="w-full text-left text-sm">
                                <thead class="sticky top-0 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-2">Row</th>
                                        <th class="px-4 py-2">Why it was skipped</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($importErrors as $importError)
                                        @php
                                            $rowLabel = '—';
                                            $reason = $importError;
                                            if (preg_match('/^Row\s+(\d+):\s*(.+)$/i', $importError, $match)) {
                                                $rowLabel = $match[1];
                                                $reason = $match[2];
                                            }
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $rowLabel }}</td>
                                            <td class="px-4 py-2.5 text-slate-700">{{ $reason }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        onclick="document.getElementById('importResultModal').classList.add('hidden'); document.getElementById('importResultModal').classList.remove('flex');"
                        class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-medium text-white"
                    >
                        Got it
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div
        id="importModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Add many reporters at once</h2>
                    <p class="mt-1 text-sm text-slate-500">Download the sample file, fill in your employees, then upload. You do not need to map columns.</p>
                </div>
                <button type="button" onclick="closeImportModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="reporterImportForm" action="/maintenance/reporters/import" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="mapping" id="importMapping" value="" />

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <label id="importDropzone" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-slate-400 hover:bg-slate-100">
                        <i data-lucide="cloud-upload" class="h-8 w-8 text-slate-400"></i>
                        <p class="mt-3 text-sm font-medium text-slate-700">Drop a CSV or Excel file, or click to browse</p>
                        <p class="mt-1 text-xs text-slate-400">Use the sample file so names and numbers import correctly</p>
                        <input id="importFile" name="file" type="file" accept=".csv,.txt,.xlsx" class="hidden" required />
                    </label>
                    <p id="importFileName" class="mt-3 hidden text-sm font-medium text-slate-600"></p>
                    <p id="importError" class="mt-3 hidden text-sm text-rose-600"></p>

                    <label class="mt-4 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="auto_assign_ids" value="1" checked class="rounded border-slate-300 text-slate-900" />
                        Auto-assign employee IDs when blank
                    </label>

                    <div id="importMappingPanel" class="mt-5 hidden">
                        <p id="importPreviewCount" class="text-sm font-medium text-slate-800"></p>
                        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">Employee ID</th>
                                        <th class="px-3 py-2">First name</th>
                                        <th class="px-3 py-2">Middle name</th>
                                        <th class="px-3 py-2">Last name</th>
                                        <th class="px-3 py-2">Type</th>
                                        <th class="px-3 py-2">Email</th>
                                        <th class="px-3 py-2">Contact</th>
                                    </tr>
                                </thead>
                                <tbody id="importPreviewBody" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                    <a href="/maintenance/reporters/import/template" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                        Download sample CSV
                    </a>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="closeImportModal()" class="h-10 rounded-lg px-4 text-sm font-medium text-slate-600 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button id="importSubmitBtn" type="submit" disabled class="h-10 rounded-lg bg-[rgba(0,55,199,0.85)] px-5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40">
                            Import reporters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div
        id="createModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form
            action="/maintenance/reporters/store"
            method="POST"
            class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
        >
            @csrf
            @php
                $reporterFieldClass = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
            @endphp

            <div class="flex items-start justify-between px-6 pb-4 pt-6">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">Reporter</p>
                    <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Add reporter</h2>
                    <p class="mt-1 text-sm text-slate-500">Fill in the employee details. Middle name, type, email, and contact are optional.</p>
                </div>
                <button type="button" onclick="closeCreateModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 pb-2">
                <div>
                    <label for="employee_id" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Employee ID <span class="text-rose-500">*</span></label>
                    <input id="employee_id" name="employee_id" type="text" placeholder="OMC****F" required class="{{ $reporterFieldClass }}" />
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">First name <span class="text-rose-500">*</span></label>
                        <input id="first_name" name="first_name" type="text" placeholder="John" required class="{{ $reporterFieldClass }}" />
                    </div>
                    <div>
                        <label for="middle_name" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Middle name</label>
                        <input id="middle_name" name="middle_name" type="text" placeholder="Optional" class="{{ $reporterFieldClass }}" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="last_name" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Last name <span class="text-rose-500">*</span></label>
                        <input id="last_name" name="last_name" type="text" placeholder="Smith" required class="{{ $reporterFieldClass }}" />
                    </div>
                    <div>
                        <label for="type" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
                        <select id="type" name="type" class="{{ $reporterFieldClass }}">
                            <option value="">Select type</option>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Email address</label>
                    <input id="email" name="email" type="email" placeholder="name@email.com" class="{{ $reporterFieldClass }}" />
                </div>
                <div>
                    <label for="contact" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Contact number</label>
                    <input id="contact" name="contact" type="text" placeholder="09103102012" maxlength="11" pattern="[0-9]*" inputmode="numeric" class="{{ $reporterFieldClass }}" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeCreateModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800">Add reporter</button>
            </div>
        </form>
    </div>

    <div
        id="viewModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            <div class="flex items-start justify-between px-6 pb-4 pt-6">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">Reporter</p>
                    <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Profile</h2>
                </div>
                <button type="button" onclick="closeViewModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <div id="reporterDetails" class="px-6 pb-2"></div>
            <div class="flex justify-end px-6 py-4">
                <button type="button" onclick="closeViewModal()" class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white">Close</button>
            </div>
        </div>
    </div>

    <div
        id="editModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form
            action="/maintenance/reporters/update"
            method="POST"
            class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
        >
            @csrf
            <input type="hidden" name="reporter_id" id="editReporterId" />

            <div class="flex items-start justify-between px-6 pb-4 pt-6">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">Reporter</p>
                    <h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Edit profile</h2>
                    <p class="mt-1 text-sm text-slate-500">Update employee details. Middle name, type, email, and contact are optional.</p>
                </div>
                <button type="button" onclick="closeEditModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 pb-2">
                <div>
                    <label for="editEmployeeId" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Employee ID</label>
                    <input type="text" name="employee_id" id="editEmployeeId" required class="{{ $reporterFieldClass }}" />
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="editFirstName" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">First name</label>
                        <input type="text" name="first_name" id="editFirstName" required class="{{ $reporterFieldClass }}" />
                    </div>
                    <div>
                        <label for="editMiddleName" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Middle name</label>
                        <input type="text" name="middle_name" id="editMiddleName" class="{{ $reporterFieldClass }}" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="editLastName" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Last name</label>
                        <input type="text" name="last_name" id="editLastName" required class="{{ $reporterFieldClass }}" />
                    </div>
                    <div>
                        <label for="editType" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
                        <select name="type" id="editType" class="{{ $reporterFieldClass }}">
                            <option value="">Select type</option>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="editEmail" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Email address</label>
                    <input type="email" name="email" id="editEmail" class="{{ $reporterFieldClass }}" />
                </div>
                <div>
                    <label for="editContact" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Contact number</label>
                    <input type="text" name="contact" id="editContact" class="{{ $reporterFieldClass }}" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeEditModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800">Save changes</button>
            </div>
        </form>
    </div>

    {{-- ===================================================== --}}
    {{-- REPORTER STATUS CONFIRMATION MODAL --}}
    {{-- REUSABLE FOR DEACTIVATE AND REACTIVATE --}}
    {{-- ===================================================== --}}

    <div
        id="reporterStatusModal"
        class="fixed inset-0 z-50 hidden
            items-center justify-center
            bg-[#0b1220]/70 p-4"
    >

        <div
            class="relative w-full max-w-md
                overflow-hidden rounded-2xl
                border border-slate-200
                bg-white shadow-2xl"
        >

            {{-- ================================================= --}}
            {{-- CLOSE BUTTON --}}
            {{-- ABSOLUTE TOP RIGHT OF MODAL CARD --}}
            {{-- ================================================= --}}

            <button
                type="button"
                onclick="closeReporterStatusModal()"

                class="absolute right-4 top-4 z-10
                    flex h-8 w-8 items-center justify-center
                    rounded-full text-slate-400
                    transition
                    hover:bg-slate-100
                    hover:text-slate-900"

                aria-label="Close modal"
                data-tooltip="Close"
            >
                <i
                    data-lucide="x"
                    class="h-4 w-4"
                ></i>
            </button>

            {{-- ================================================= --}}
            {{-- MODAL CONTENT --}}
            {{-- ================================================= --}}

            <div class="px-6 pb-5 pt-6">

                {{-- ICON --}}

                <div
                    id="reporterStatusIconContainer"
                    class="flex h-11 w-11
                        items-center justify-center
                        rounded-xl"
                >
                    <i
                        id="reporterStatusIcon"
                        data-lucide="user-x"
                        class="h-5 w-5"
                    ></i>
                </div>


                {{-- TITLE --}}

                <h2
                    id="reporterStatusTitle"
                    class="mt-4 text-base
                        font-semibold text-slate-900"
                >
                    Deactivate reporter?
                </h2>

                {{-- DESCRIPTION --}}

                <p
                    id="reporterStatusDescription"
                    class="mt-2 text-sm
                        leading-6 text-slate-500"
                >
                    This reporter will no longer be allowed
                    to submit new reports.
                </p>


                {{-- REPORTER NAME --}}

                <div
                    class="mt-4 rounded-xl
                        border border-slate-200
                        bg-slate-50 px-4 py-3"
                >
                    <p
                        class="text-xs font-medium
                            uppercase tracking-wide
                            text-slate-400"
                    >
                        Reporter
                    </p>

                    <p
                        id="reporterStatusName"
                        class="mt-1 text-sm
                            font-semibold text-slate-800"
                    >
                    </p>
                </div>

            </div>


            {{-- ================================================= --}}
            {{-- MODAL FOOTER --}}
            {{-- ================================================= --}}

            <div
                class="flex items-center justify-end gap-2
                    border-t border-slate-200
                    bg-slate-50/70 px-6 py-4"
            >

                {{-- CANCEL --}}

                <button
                    type="button"
                    onclick="closeReporterStatusModal()"

                    class="inline-flex h-10
                        items-center justify-center
                        rounded-lg border
                        border-slate-200
                        bg-white px-4
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-slate-900"
                >
                    Cancel
                </button>


                {{-- SUBMIT FORM --}}

                <form
                    id="reporterStatusForm"
                    method="POST"
                    action=""
                >
                    @csrf
                    @method('PATCH')


                    <button
                        id="reporterStatusSubmitButton"
                        type="submit"

                        class="inline-flex h-10
                            items-center justify-center
                            gap-2 rounded-lg px-4
                            text-sm font-semibold
                            transition"
                    >

                        <i
                            id="reporterStatusSubmitIcon"
                            data-lucide="user-x"
                            class="h-4 w-4"
                        ></i>

                        <span id="reporterStatusSubmitText">
                            Deactivate reporter
                        </span>

                    </button>

                </form>

            </div>

        </div>

    </div>

    <div
        id="deleteModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <!-- ===================================== -->
        <!-- DELETE REPORTER MODAL -->
        <!-- ===================================== -->
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
        >
            <!-- ===================================== -->
            <!-- MODAL HEADER -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div>
                    <!-- DELETE ICON -->
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600"
                    >
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </div>

                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Delete reporter?
                    </h2>

                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                        This reporter profile will be permanently deleted. This
                        action cannot be undone.
                    </p>
                </div>

                <!-- CLOSE BUTTON -->
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ===================================== -->
            <!-- DELETE FORM -->
            <!-- ===================================== -->
            <form
                action="/maintenance/reporters/delete"
                method="POST"
            >
                @csrf

                <input
                    type="hidden"
                    name="reporter_id"
                    id="deleteReporterId"
                />

                <!-- ===================================== -->
                <!-- MODAL FOOTER -->
                <!-- ===================================== -->
                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4"
                >
                    <button
                        type="button"
                        onclick="closeDeleteModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 active:bg-rose-800"
                    >
                        Delete reporter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const createModal = document.getElementById("createModal");
        const importModal = document.getElementById("importModal");
        const viewModal = document.getElementById("viewModal");
        const editModal = document.getElementById("editModal");
        const deleteModal = document.getElementById("deleteModal");

        function openImportModal() {
            importModal.classList.remove("hidden");
            importModal.classList.add("flex");
            if (window.lucide) lucide.createIcons();
        }

        function closeImportModal() {
            importModal.classList.add("hidden");
            importModal.classList.remove("flex");
        }

        function openCreateModal() {
            createModal.classList.remove("hidden");
            createModal.classList.add("flex");
        }

        function closeCreateModal() {
            createModal.classList.add("hidden");
            createModal.classList.remove("flex");
        }

        function closeViewModal() {
            viewModal.classList.add("hidden");
            viewModal.classList.remove("flex");
        }

        function closeEditModal() {
            editModal.classList.add("hidden");
            editModal.classList.remove("flex");
        }

        function closeDeleteModal() {
            deleteModal.classList.add("hidden");
            deleteModal.classList.remove("flex");
        }

        function viewReporter(employee, name, type, email, contact) {
            const displayName = name || "—";
            const initials = displayName
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0].toUpperCase())
                .join("") || "?";
            const typeChip = type
                ? `<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">${type}</span>`
                : `<span class="text-xs text-slate-400">No type set</span>`;

            document.getElementById("reporterDetails").innerHTML = `
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200/70">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white">${initials}</div>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-slate-900">${displayName}</p>
                            <p class="mt-0.5 font-mono text-xs text-slate-500">${employee || "—"}</p>
                        </div>
                    </div>
                    <div class="mt-3">${typeChip}</div>
                </div>
                <dl class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl ring-1 ring-slate-200/70">
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                        <dd class="min-w-0 break-all text-right text-sm font-medium text-slate-800">${email || "—"}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Contact</dt>
                        <dd class="text-right text-sm font-medium text-slate-800">${contact || "—"}</dd>
                    </div>
                </dl>
            `;

            // =====================================
            // OPEN VIEW MODAL
            // =====================================
            viewModal.classList.remove("hidden");
            viewModal.classList.add("flex");
        }

        function editReporter(id, employee, first, middle, last, type, email, contact) {
            document.getElementById("editReporterId").value = id;
            document.getElementById("editEmployeeId").value = employee;
            document.getElementById("editFirstName").value = first || "";
            document.getElementById("editMiddleName").value = middle || "";
            document.getElementById("editLastName").value = last || "";
            document.getElementById("editType").value = type || "";
            document.getElementById("editEmail").value = email || "";
            document.getElementById("editContact").value = contact || "";

            editModal.classList.remove("hidden");
            editModal.classList.add("flex");
        }

        function openDeleteModal(id) {
            document.getElementById("deleteReporterId").value = id;
            deleteModal.classList.remove("hidden");
            deleteModal.classList.add("flex");
        }

        
    </script>

    <script>
        // =====================================================
        // OPEN REPORTER STATUS MODAL
        // HANDLES DEACTIVATE AND REACTIVATE
        // =====================================================

        function openReporterStatusModal(
            reporterId,
            reporterName,
            action
        ) {
            const modal =
                document.getElementById('reporterStatusModal');

            const form =
                document.getElementById('reporterStatusForm');

            const name =
                document.getElementById('reporterStatusName');

            const title =
                document.getElementById('reporterStatusTitle');

            const description =
                document.getElementById(
                    'reporterStatusDescription'
                );

            const iconContainer =
                document.getElementById(
                    'reporterStatusIconContainer'
                );

            const icon =
                document.getElementById('reporterStatusIcon');

            const submitButton =
                document.getElementById(
                    'reporterStatusSubmitButton'
                );

            const submitIcon =
                document.getElementById(
                    'reporterStatusSubmitIcon'
                );

            const submitText =
                document.getElementById(
                    'reporterStatusSubmitText'
                );


            // =================================================
            // SET REPORTER NAME
            // =================================================

            name.textContent = reporterName;


            // =================================================
            // DEACTIVATE MODE
            // =================================================

            if (action === 'deactivate') {

                form.action =
                    `/maintenance/reporters/${reporterId}/deactivate`;


                title.textContent =
                    'Deactivate reporter?';


                description.textContent =
                    'This reporter will no longer be allowed to submit new reports. Existing reports and history will remain available.';


                iconContainer.className =
                    'flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700';


                icon.setAttribute(
                    'data-lucide',
                    'user-x'
                );


                submitButton.className =
                    'inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white transition hover:bg-amber-700 active:scale-[0.98]';


                submitIcon.setAttribute(
                    'data-lucide',
                    'user-x'
                );


                submitText.textContent =
                    'Deactivate reporter';

            }


            // =================================================
            // REACTIVATE MODE
            // =================================================

            if (action === 'reactivate') {

                form.action =
                    `/maintenance/reporters/${reporterId}/reactivate`;


                title.textContent =
                    'Reactivate reporter?';


                description.textContent =
                    'This reporter will become active again and will be allowed to submit new reports.';


                iconContainer.className =
                    'flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700';


                icon.setAttribute(
                    'data-lucide',
                    'user-check'
                );


                submitButton.className =
                    'inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.98]';


                submitIcon.setAttribute(
                    'data-lucide',
                    'user-check'
                );


                submitText.textContent =
                    'Reactivate reporter';

            }


            // =================================================
            // REINITIALIZE LUCIDE ICONS
            // REQUIRED AFTER CHANGING DATA-LUCIDE
            // =================================================

            if (window.lucide) {
                lucide.createIcons();
            }


            // =================================================
            // SHOW MODAL
            // =================================================

            modal.classList.remove('hidden');

            modal.classList.add('flex');
        }


        // =====================================================
        // CLOSE REPORTER STATUS MODAL
        // =====================================================

        function closeReporterStatusModal()
        {
            const modal =
                document.getElementById('reporterStatusModal');


            modal.classList.add('hidden');

            modal.classList.remove('flex');
        }


        // =====================================================
        // CLOSE WHEN CLICKING MODAL BACKDROP
        // =====================================================

        document
            .getElementById('reporterStatusModal')
            .addEventListener('click', function (event) {

                if (event.target === this) {

                    closeReporterStatusModal();

                }

            });


        // =====================================================
        // CLOSE WITH ESCAPE KEY
        // =====================================================

        document.addEventListener('keydown', function (event) {

            if (event.key === 'Escape') {

                closeReporterStatusModal();
                closeImportModal();

            }

        });

        const importFile = document.getElementById('importFile');
        const importDropzone = document.getElementById('importDropzone');
        const importFileName = document.getElementById('importFileName');
        const importError = document.getElementById('importError');
        const importMappingPanel = document.getElementById('importMappingPanel');
        const importPreviewBody = document.getElementById('importPreviewBody');
        const importMappingInput = document.getElementById('importMapping');
        const importPreviewCount = document.getElementById('importPreviewCount');
        const importSubmitBtn = document.getElementById('importSubmitBtn');

        function showImportError(message) {
            importError.textContent = message;
            importError.classList.remove('hidden');
            importSubmitBtn.disabled = true;
        }

        function cellText(value) {
            const text = String(value ?? '').trim();
            if (text === '') return '—';
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;');
        }

        function renderImportMapping(data) {
            importMappingInput.value = JSON.stringify(data.mapping || {});
            importPreviewBody.innerHTML = '';

            (data.people || []).forEach((person) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-3 py-2 font-mono text-xs text-slate-700">${cellText(person.employee_id)}</td>
                    <td class="px-3 py-2 text-slate-900">${cellText(person.first_name)}</td>
                    <td class="px-3 py-2 text-slate-600">${cellText(person.middle_name)}</td>
                    <td class="px-3 py-2 text-slate-900">${cellText(person.last_name)}</td>
                    <td class="px-3 py-2 text-slate-600">${cellText(person.type)}</td>
                    <td class="px-3 py-2 text-slate-600">${cellText(person.email_address)}</td>
                    <td class="px-3 py-2 text-slate-600">${cellText(person.contact_number)}</td>
                `;
                importPreviewBody.appendChild(row);
            });

            const total = data.total || 0;
            importPreviewCount.textContent = total
                ? `${total} employee${total === 1 ? '' : 's'} found in the file`
                : 'No employees found in the file';
            importMappingPanel.classList.remove('hidden');
            importSubmitBtn.disabled = total === 0 || !data.mapping?.first_name || !data.mapping?.last_name;
            if (importSubmitBtn.disabled && total > 0) {
                showImportError('The file needs First name and Last name columns.');
            }
        }

        async function previewImportFile(file) {
            importError.classList.add('hidden');
            importFileName.textContent = file.name;
            importFileName.classList.remove('hidden');
            importSubmitBtn.disabled = true;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('/maintenance/reporters/import/preview', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await response.json();
                if (!response.ok || !data.ok) {
                    showImportError(data.message || 'Could not read that file.');
                    return;
                }
                renderImportMapping(data);
            } catch (error) {
                showImportError('Could not read that file.');
            }
        }

        importFile?.addEventListener('change', function () {
            if (this.files[0]) previewImportFile(this.files[0]);
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            importDropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                importDropzone.classList.add('border-slate-500', 'bg-slate-100');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            importDropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                importDropzone.classList.remove('border-slate-500', 'bg-slate-100');
            });
        });

        importDropzone?.addEventListener('drop', (event) => {
            const file = event.dataTransfer.files[0];
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            importFile.files = transfer.files;
            previewImportFile(file);
        });
    </script>

@endsection
