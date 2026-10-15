@extends ("layouts.maintenance-layout")

@section ("content")

<style>
    .reporter-employee-id-field {
        display: flex;
        align-items: stretch;
        height: 2.75rem;
        overflow: hidden;
        border-radius: 0.75rem;
        background: #f8fafc;
        box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.9);
        transition: background 0.15s ease, box-shadow 0.15s ease;
    }

    .reporter-employee-id-field:focus-within {
        background: #fff;
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
    }

    .reporter-employee-id-field.is-disabled {
        opacity: 0.85;
    }

    .reporter-employee-id-prefix,
    .reporter-employee-id-suffix {
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #64748b;
        user-select: none;
    }

    .reporter-employee-id-prefix { padding-left: 0.875rem; }
    .reporter-employee-id-suffix {
        min-width: 2rem;
        justify-content: center;
        padding-right: 0.875rem;
    }
    .reporter-employee-id-suffix.is-empty { color: #94a3b8; }

    .reporter-employee-id-field input {
        flex: 1;
        min-width: 0;
        border: 0;
        background: transparent;
        padding: 0 0.5rem;
        text-align: center;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        color: #0f172a;
        outline: none;
    }

    .reporter-employee-id-field.is-disabled input {
        pointer-events: none;
        color: #94a3b8;
    }
</style>

    <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        @if (!$historyReporter)
            <div class="flex flex-wrap items-center gap-2">
                @if (($pinsReady ?? false) && ($activeReportersWithoutPin ?? 0) > 0)
                    <button
                        type="button"
                        onclick="openBulkPinModal()"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-[13px] font-semibold text-[#0025cc] transition hover:bg-blue-100"
                    >
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                        Generate missing PINs
                        <span class="inline-flex min-w-[20px] items-center justify-center rounded-full bg-[#0025cc] px-1.5 text-[11px] font-bold text-white">
                            {{ $activeReportersWithoutPin }}
                        </span>
                    </button>
                @endif
                <button
                    type="button"
                    onclick="openImportModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Add Bulk Reporters
                </button>
                <button
                    type="button"
                    onclick="openCreateModal()"
                    class="inline-flex items-center justify-center gap-2
                        rounded-lg bg-[#0025cc]
                        px-4 py-2.5 text-[13px] font-semibold
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

    {{-- REPORTER DASHBOARD CARDS --}}
    @php
        $reporterMonthlyHint = is_null($reporterMonthlyPercentage)
            ? 'New activity vs last month'
            : (($reporterMonthlyPercentage > 0 ? '+' : '') . number_format($reporterMonthlyPercentage, 2) . '% vs last month');
    @endphp
    <div class="mb-6">
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Total Reporters', 'hint' => $reporterMonthlyHint, 'value' => number_format($totalReporters)],
                ['label' => 'With Email', 'hint' => number_format($emailCoveragePercentage, 2) . '% of all reporters', 'value' => number_format($reportersWithEmail)],
                ['label' => 'With Contact', 'hint' => number_format($contactCoveragePercentage, 2) . '% of all reporters', 'value' => number_format($reportersWithContact)],
            ],
        ])
    </div>


    @endif

    

        

    <div class="rounded-3xl border border-slate-100 bg-white shadow-sm">

        

        <!--<div
            class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Reporters
                </h1>
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
                                rounded-lg bg-[#0025cc] px-4
                                text-sm font-semibold text-white
                                transition hover:bg-blue-800"
                        >
                            <i
                                data-lucide="filter"
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

                    <table class="w-full table-fixed text-left">

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

                                <th class="w-[14%] px-4 py-3">
                                    Report
                                </th>

                                <th class="w-[20%] px-4 py-3">
                                    Issue
                                </th>

                                <th class="w-[16%] px-4 py-3">
                                    Equipment
                                </th>

                                <th class="w-[14%] px-4 py-3">
                                    Room
                                </th>

                                <th class="w-[10%] px-4 py-3">
                                    Urgency
                                </th>

                                <th class="w-[12%] px-4 py-3">
                                    Status
                                </th>

                                <th class="w-[14%] px-4 py-3">
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
                                            {{ \App\Support\ReportGrouping::ticketCode($report) }}
                                        </span>

                                    </td>


                                    {{-- ===================================== --}}
                                    {{-- ISSUE --}}
                                    {{-- ===================================== --}}

                                    <td class="px-5 py-4">

                                        <div class="min-w-0">

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
                                                    ?? 'No extra details from reporter'
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

                        {{-- ALL --}}

                        <a
                            href="{{ request()->fullUrlWithQuery([
                                'status' => null,
                                'page' => null,
                            ]) }}"

                            class="shrink-0 rounded-lg px-3 py-1.5
                                text-[13px] transition
                                {{
                                    !request()->filled('status')
                                        ? 'bg-slate-100/80 font-medium text-slate-900 shadow-sm'
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

                            class="shrink-0 rounded-lg px-3 py-1.5
                                text-[13px] transition
                                {{
                                    request('status') === 'Active'
                                        ? 'bg-slate-100/80 font-medium text-slate-900 hadow-sm'
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

                            class="shrink-0 rounded-lg px-3 py-1.5
                                text-[13px] transition
                                {{
                                    request('status') === 'Inactive'
                                        ? 'bg-slate-100/80 font-medium text-slate-900 border border-slate-200/80 shadow-sm'
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
                            rounded-lg bg-[#0025cc] px-4
                            text-sm font-semibold text-white
                            transition hover:bg-blue-800"
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

                <table class="w-full table-fixed text-left">

                    {{-- ================================================= --}}
                    {{-- TABLE HEADER --}}
                    {{-- ================================================= --}}

                    <thead class="border-b border-gray-100 bg-gray-50">

                        <tr
                            class="text-[12px] font-bold uppercase
                                tracking-wider text-black"
                        >
                            <th class="w-[13%] px-5 py-3 text-left">
                                Employee ID
                            </th>

                            <th class="w-[27%] px-5 py-3 text-left">
                                Reporter
                            </th>

                            <th class="w-[12%] px-5 py-3 text-left">
                                Type
                            </th>

                            <th class="w-[14%] px-5 py-3 text-left">
                                Contact
                            </th>

                            <th class="w-[10%] px-5 py-3 text-left">
                                Status
                            </th>

                            <th class="w-[24%] px-5 py-3 text-center">
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
                                class="reporter-row group border-b border-gray-100 transition-colors
                                    hover:bg-yellow-50/30"

                                data-status="{{ strtolower($reporterStatus) }}"
                            >

                                {{-- ===================================== --}}
                                {{-- EMPLOYEE ID --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="block truncate font-mono text-sm font-semibold
                                            tracking-wide text-gray-500"
                                        title="{{ $reporter->reporter_employee_id }}"
                                    >
                                        {{ $reporter->reporter_employee_id }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- REPORTER --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex min-w-0 items-center gap-3">

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
                                                class="truncate
                                                    text-sm font-semibold
                                                    text-gray-800"
                                                title="{{ $reporter->reporter_full_name }}"
                                            >
                                                {{ $reporter->reporter_full_name }}
                                            </p>


                                            <p
                                                class="mt-0.5 truncate text-xs
                                                    text-slate-400"
                                                title="{{ $reporter->reporter_email_address ?: '' }}"
                                            >
                                                {{
                                                    $reporter->reporter_email_address
                                                        ?: 'No email'
                                                }}
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- TYPE --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    @if ($reporter->reporter_employment_type)
                                        <span class="inline-flex max-w-full truncate rounded-md bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-700">
                                            {{ $reporter->reporter_employment_type }}
                                        </span>
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endif
                                    @if (!empty($reporter->reporter_department_name))
                                        <p class="mt-1 truncate text-xs text-slate-400" title="{{ $reporter->reporter_department_name }}">
                                            {{ $reporter->reporter_department_name }}
                                        </p>
                                    @endif
                                </td>



                                {{-- ===================================== --}}
                                {{-- CONTACT --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">
                                    @if ($reporter->reporter_contact_number)
                                        <button
                                            type="button"
                                            onclick="copyReporterContact(this)"
                                            data-contact="{{ $reporter->reporter_contact_number }}"
                                            data-tooltip="Click to copy"
                                            class="group inline-flex max-w-full items-center gap-1.5 rounded-md px-1.5 py-0.5 text-left text-sm text-gray-600 transition hover:bg-slate-100 hover:text-slate-900"
                                            aria-label="Copy contact number"
                                        >
                                            <span class="truncate font-medium select-all">
                                                {{ $reporter->reporter_contact_number }}
                                            </span>
                                            <i data-lucide="copy" class="h-3.5 w-3.5 shrink-0 text-slate-400 opacity-0 transition group-hover:opacity-100"></i>
                                        </button>
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endif
                                </td>



                                {{-- ===================================== --}}
                                {{-- STATUS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="inline-flex items-center gap-1.5
                                            rounded-md px-2.5 py-1
                                            text-[11px] font-medium
                                            ring-1 ring-inset
                                            {{ $reporterStatusClass }}"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 shrink-0 rounded-full
                                                {{ $reporterStatusDotClass }}"
                                        ></span>

                                        {{ $reporterStatus }}

                                    </span>

                                    @if (($pinsReady ?? false) && strtolower($reporterStatus) === 'active' && blank($reporter->reporter_pin_hash ?? null))
                                        <span class="mt-1.5 flex w-fit items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                            <i data-lucide="key-round" class="h-3 w-3"></i>
                                            No PIN
                                        </span>
                                    @endif

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center justify-center gap-1.5">

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
                                                @js($reporter->reporter_contact_number),
                                                @js($reporter->reporter_department_name ?? null)
                                            )"

                                            class="flex h-9 w-9 shrink-0 items-center
                                                justify-center rounded-lg
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
                                                rounded-lg bg-slate-100
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
                                                    @js($reporter->reporter_contact_number),
                                                    @js($reporter->reporter_department_id ?? null)
                                                )"

                                                class="flex h-9 w-9 shrink-0 items-center
                                                    justify-center rounded-lg
                                                    bg-[#0025cc] text-white
                                                    transition
                                                    hover:bg-[#001db3]
                                                    active:scale-95"

                                                data-tooltip="Edit reporter"

                                                aria-label="Edit reporter"
                                            >

                                                <i
                                                    data-lucide="edit-3"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                            </button>

                                            @if ($pinsReady ?? false)
                                                <button
                                                    type="button"
                                                    onclick="openPinModal(
                                                        @js($reporter->reporter_id),
                                                        @js($reporter->reporter_full_name),
                                                        @js($reporter->reporter_employee_id),
                                                        @js($reporter->reporter_email_address),
                                                        @js(filled($reporter->reporter_pin_hash ?? null)),
                                                        @js($reporter->reporter_pin_set_at ? \Illuminate\Support\Carbon::parse($reporter->reporter_pin_set_at)->format('M j, Y g:i A') : null)
                                                    )"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-[#0025cc] ring-1 ring-inset ring-blue-200 transition hover:bg-blue-100 active:scale-95"
                                                    data-tooltip="{{ filled($reporter->reporter_pin_hash ?? null) ? 'Reset PIN' : 'Create PIN' }}"
                                                    aria-label="{{ filled($reporter->reporter_pin_hash ?? null) ? 'Reset PIN' : 'Create PIN' }}"
                                                >
                                                    <i data-lucide="key-round" class="h-3.5 w-3.5"></i>
                                                </button>
                                            @endif

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

                                                class="flex h-9 w-9 shrink-0 items-center
                                                    justify-center rounded-lg
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

                                                class="flex h-9 w-9 shrink-0 items-center
                                                    justify-center rounded-lg
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
            $importUnknownDepartments = $importResult['unknown_departments'] ?? [];
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

                    @if ($importCreated > 0 && ($pinsReady ?? false))
                        <p class="mt-4 rounded-xl bg-blue-50 px-4 py-3 text-sm text-[#0025cc]">
                            Imported reporters don't have a PIN yet. Use <strong>Generate missing PINs</strong> to create and email their PINs.
                        </p>
                    @endif

                    @if (count($importUnknownDepartments) > 0)
                        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Departments not found (saved without a department):
                            <span class="font-medium">{{ implode(', ', $importUnknownDepartments) }}</span>.
                        </p>
                    @endif

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
                        class="h-10 rounded-lg bg-[#0025cc] px-4 text-sm font-medium text-white"
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
                    <p class="mt-1 text-sm text-slate-500">Download the sample file, fill in your faculty and staff, then upload. Each row is checked the same way as adding one reporter.</p>
                </div>
                <button type="button" onclick="closeImportModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="reporterImportForm" action="/admin/reporters/import" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="mapping" id="importMapping" value="" />

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <label id="importDropzone" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-slate-400 hover:bg-slate-100">
                        <i data-lucide="cloud-upload" class="h-8 w-8 text-slate-400"></i>
                        <p class="mt-3 text-sm font-medium text-slate-700">Drop a CSV or Excel file, or click to browse <span class="text-red-500">*</span></p>
                        <p class="mt-1 text-xs text-slate-400">Use the sample file so names and numbers import correctly</p>
                        <input id="importFile" name="file" type="file" accept=".csv,.txt,.xlsx" class="hidden" required />
                    </label>
                    <p id="importFileName" class="mt-3 hidden text-sm font-medium text-slate-600"></p>
                    <p id="importError" class="mt-3 hidden text-sm text-rose-600"></p>

                    <div id="importMappingPanel" class="mt-5 hidden">
                        <p id="importPreviewCount" class="text-sm font-medium text-slate-800"></p>
                        <p id="importUnknownDepartments" class="mt-2 hidden rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-100"></p>
                        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2">Row</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Employee ID</th>
                                        <th class="px-3 py-2">Name</th>
                                        <th class="px-3 py-2">Type</th>
                                        <th class="px-3 py-2">Department</th>
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
                    <a href="/admin/reporters/import/template" class="text-sm font-medium text-blue-600 hover:text-slate-900">
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
            id="createReporterForm"
            action="/admin/reporters/store"
            method="POST"
            novalidate
            class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
        >
            @csrf
            @php
                $reporterFieldClass = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
                $reporterLabelClass = 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
                $reporterSectionClass = 'mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400';
            @endphp

            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc]/5 text-[#0025cc]">
                        <i data-lucide="user-plus" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Add reporter</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Fields marked <span class="text-red-500">*</span> are required. The reporter's PIN is sent to their email.</p>
                    </div>
                </div>
                <button type="button" onclick="closeCreateModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
                <div data-duplicate-slot class="empty:hidden"></div>

                <section>
                    <p class="{{ $reporterSectionClass }}"><i data-lucide="briefcase" class="h-3.5 w-3.5"></i> Employment</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="type" class="{{ $reporterLabelClass }}">Type <span class="text-red-500">*</span></label>
                            <select id="type" name="type" required class="{{ $reporterFieldClass }}">
                                <option value="">Select type</option>
                                <option value="Faculty">Faculty</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div>
                            <label for="employee_id_digits" class="{{ $reporterLabelClass }}">Employee ID <span class="text-red-500">*</span></label>
                            <div id="createEmployeeIdField" class="reporter-employee-id-field is-disabled">
                                <span class="reporter-employee-id-prefix">OMC</span>
                                <input
                                    id="employee_id_digits"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="5"
                                    placeholder="00127"
                                    autocomplete="off"
                                    readonly
                                >
                                <span id="createEmployeeIdSuffix" class="reporter-employee-id-suffix is-empty">?</span>
                            </div>
                            <input type="hidden" id="employee_id" name="employee_id" value="" required>
                        </div>
                        <p id="createEmployeeIdHint" class="-mt-2 text-xs text-slate-400 sm:col-span-2">Select type first, then enter the 5-digit number.</p>
                        <div class="sm:col-span-2">
                            <label for="department_id" class="{{ $reporterLabelClass }}">Department</label>
                            <select id="department_id" name="department_id" class="{{ $reporterFieldClass }}" data-searchable="1" data-search-placeholder="Search department">
                                <option value="">No department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->department_id }}">{{ $department->department_name }}{{ $department->department_is_archived ? ' (archived)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section>
                    <p class="{{ $reporterSectionClass }}"><i data-lucide="id-card" class="h-3.5 w-3.5"></i> Name</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="first_name" class="{{ $reporterLabelClass }}">First name <span class="text-red-500">*</span></label>
                            <input id="first_name" name="first_name" type="text" placeholder="John" required class="{{ $reporterFieldClass }}" />
                        </div>
                        <div>
                            <label for="middle_name" class="{{ $reporterLabelClass }}">Middle name</label>
                            <input id="middle_name" name="middle_name" type="text" placeholder="Optional" class="{{ $reporterFieldClass }}" />
                        </div>
                        <div>
                            <label for="last_name" class="{{ $reporterLabelClass }}">Last name <span class="text-red-500">*</span></label>
                            <input id="last_name" name="last_name" type="text" placeholder="Smith" required class="{{ $reporterFieldClass }}" />
                        </div>
                    </div>
                </section>

                <section>
                    <p class="{{ $reporterSectionClass }}"><i data-lucide="mail" class="h-3.5 w-3.5"></i> Contact</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="email" class="{{ $reporterLabelClass }}">Email address <span class="text-red-500">*</span></label>
                            <input id="email" name="email" type="email" placeholder="name@ormoc.sti.edu.ph" required class="{{ $reporterFieldClass }}" />
                        </div>
                        <div>
                            <label for="contact" class="{{ $reporterLabelClass }}">Contact number</label>
                            <input id="contact" name="contact" type="text" placeholder="09103102012" maxlength="11" pattern="[0-9]*" inputmode="numeric" class="{{ $reporterFieldClass }}" />
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                <button type="button" onclick="closeCreateModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Add reporter
                </button>
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
                <button type="button" onclick="closeViewModal()" class="h-10 rounded-xl  px-4 text-sm font-medium text-slate-950 hover:text-slate-600">Close</button>
            </div>
        </div>
    </div>

    <div
        id="editModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form
            id="editReporterForm"
            action="/admin/reporters/update"
            method="POST"
            novalidate
            class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
        >
            @csrf
            <input type="hidden" name="reporter_id" id="editReporterId" />

            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc]/5 text-[#0025cc]">
                        <i data-lucide="pencil" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Edit reporter</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Fields marked <span class="text-red-500">*</span> are required.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
                <div data-duplicate-slot class="empty:hidden"></div>

                <section>
                    <p class="{{ $reporterSectionClass }}"><i data-lucide="briefcase" class="h-3.5 w-3.5"></i> Employment</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="editType" class="{{ $reporterLabelClass }}">Type <span class="text-red-500">*</span></label>
                            <select name="type" id="editType" required class="{{ $reporterFieldClass }}">
                                <option value="">Select type</option>
                                <option value="Faculty">Faculty</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div>
                            <label for="editEmployeeIdDigits" class="{{ $reporterLabelClass }}">Employee ID <span class="text-red-500">*</span></label>
                            <div id="editEmployeeIdField" class="reporter-employee-id-field is-disabled">
                                <span class="reporter-employee-id-prefix">OMC</span>
                                <input
                                    id="editEmployeeIdDigits"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="5"
                                    placeholder="00127"
                                    autocomplete="off"
                                    readonly
                                >
                                <span id="editEmployeeIdSuffix" class="reporter-employee-id-suffix is-empty">?</span>
                            </div>
                            <input type="hidden" name="employee_id" id="editEmployeeId" value="" required>
                        </div>
                        <p id="editEmployeeIdHint" class="-mt-2 text-xs text-slate-400 sm:col-span-2">Select type first, then enter the 5-digit number.</p>
                        <div class="sm:col-span-2">
                            <label for="editDepartment" class="{{ $reporterLabelClass }}">Department</label>
                            <select name="department_id" id="editDepartment" class="{{ $reporterFieldClass }}" data-searchable="1" data-search-placeholder="Search department">
                                <option value="">No department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->department_id }}">{{ $department->department_name }}{{ $department->department_is_archived ? ' (archived)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section>
                    <p class="{{ $reporterSectionClass }}"><i data-lucide="id-card" class="h-3.5 w-3.5"></i> Name</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="editFirstName" class="{{ $reporterLabelClass }}">First name <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" id="editFirstName" required class="{{ $reporterFieldClass }}" />
                        </div>
                        <div>
                            <label for="editMiddleName" class="{{ $reporterLabelClass }}">Middle name</label>
                            <input type="text" name="middle_name" id="editMiddleName" placeholder="Optional" class="{{ $reporterFieldClass }}" />
                        </div>
                        <div>
                            <label for="editLastName" class="{{ $reporterLabelClass }}">Last name <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" id="editLastName" required class="{{ $reporterFieldClass }}" />
                        </div>
                    </div>
                </section>

                <section>
                    <p class="{{ $reporterSectionClass }}"><i data-lucide="mail" class="h-3.5 w-3.5"></i> Contact</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="editEmail" class="{{ $reporterLabelClass }}">Email address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="editEmail" placeholder="name@ormoc.sti.edu.ph" required class="{{ $reporterFieldClass }}" />
                        </div>
                        <div>
                            <label for="editContact" class="{{ $reporterLabelClass }}">Contact number</label>
                            <input type="text" name="contact" id="editContact" placeholder="09103102012" maxlength="11" inputmode="numeric" class="{{ $reporterFieldClass }}" />
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                <button type="button" onclick="closeEditModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Save changes</button>
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

    {{-- ===================================================== --}}
    {{-- REPORTER PIN MODAL --}}
    {{-- ===================================================== --}}

    <div
        id="pinModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form
            id="pinForm"
            method="POST"
            action=""
            novalidate
            class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
        >
            @csrf

            <div class="flex items-start justify-between px-6 pb-4 pt-6">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">Reporting PIN</p>
                    <h2 id="pinModalTitle" class="mt-1 text-lg font-semibold tracking-tight text-slate-900">Create PIN</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        <span id="pinModalName"></span> &middot; <span id="pinModalEmployee" class="font-mono"></span>
                    </p>
                </div>
                <button type="button" onclick="closePinModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="space-y-4 px-6 pb-2">
                <p id="pinModalStatus" class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200/70"></p>

                <div class="space-y-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-4 py-3 has-[:checked]:border-[#0025cc] has-[:checked]:bg-blue-50/50">
                        <input type="radio" name="mode" value="generate" checked class="mt-0.5 text-[#0025cc]" onchange="syncPinMode()">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Generate a random PIN</span>
                            <span class="block text-xs text-slate-500">Recommended. The system picks 6 digits that are hard to guess.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-4 py-3 has-[:checked]:border-[#0025cc] has-[:checked]:bg-blue-50/50">
                        <input type="radio" name="mode" value="manual" class="mt-0.5 text-[#0025cc]" onchange="syncPinMode()">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Type a PIN</span>
                            <span class="block text-xs text-slate-500">Use when the reporter asked for a specific PIN.</span>
                        </span>
                    </label>
                </div>

                <div id="pinManualWrap" class="hidden">
                    <label for="pinManualInput" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">New PIN</label>
                    <input
                        id="pinManualInput"
                        name="pin"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="off"
                        placeholder="6 digits"
                        class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-center font-mono text-lg tracking-[0.4em] text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    />
                    <p class="mt-1.5 text-xs text-slate-400">No repeated or sequential digits like 111111 or 123456.</p>
                </div>

                <label id="pinEmailWrap" class="flex items-start gap-3 text-sm text-slate-600">
                    <input type="hidden" name="send_email" value="0">
                    <input id="pinSendEmail" type="checkbox" name="send_email" value="1" checked class="mt-0.5 rounded border-slate-300">
                    <span id="pinEmailLabel">Email the PIN to the reporter</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closePinModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" id="pinSubmitBtn" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Save PIN</button>
            </div>
        </form>
    </div>

    <div
        id="bulkPinModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form
            method="POST"
            action="{{ route('admin.reporters.pins.generate-missing') }}"
            class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
            onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').textContent = 'Generating…';"
        >
            @csrf
            <div class="px-6 pb-5 pt-6">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-[#0025cc]">
                    <i data-lucide="key-round" class="h-5 w-5"></i>
                </div>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Generate missing PINs?</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    {{ $activeReportersWithoutPin ?? 0 }} active {{ \Illuminate\Support\Str::plural('reporter', $activeReportersWithoutPin ?? 0) }}
                    can't make reports yet because they have no PIN. Each one gets a random 6-digit PIN by email.
                    PINs for reporters without an email are shown once on the next screen so you can hand them over.
                </p>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50/70 px-6 py-4">
                <button type="button" onclick="closeBulkPinModal()" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">Generate PINs</button>
            </div>
        </form>
    </div>

    @include('admin.reporters.partials.pin-result')

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
                action="/admin/reporters/delete"
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
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-slate-50"
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

        function clearDuplicateWarnings() {
            document.querySelectorAll('[data-duplicate-slot]').forEach((slot) => { slot.innerHTML = ''; });
        }

        function openCreateModal() {
            clearDuplicateWarnings();
            const form = document.getElementById('createReporterForm');
            if (form) form.reset();
            syncReporterEmployeeId('create');
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

        function suffixForReporterType(type) {
            if (type === 'Faculty') return 'F';
            if (type === 'Staff') return 'S';
            return '';
        }

        function syncReporterEmployeeId(mode) {
            const isEdit = mode === 'edit';
            const typeEl = document.getElementById(isEdit ? 'editType' : 'type');
            const digitsEl = document.getElementById(isEdit ? 'editEmployeeIdDigits' : 'employee_id_digits');
            const fullEl = document.getElementById(isEdit ? 'editEmployeeId' : 'employee_id');
            const suffixEl = document.getElementById(isEdit ? 'editEmployeeIdSuffix' : 'createEmployeeIdSuffix');
            const fieldEl = document.getElementById(isEdit ? 'editEmployeeIdField' : 'createEmployeeIdField');
            const hintEl = document.getElementById(isEdit ? 'editEmployeeIdHint' : 'createEmployeeIdHint');
            if (!typeEl || !digitsEl || !fullEl || !suffixEl || !fieldEl) return '';

            const type = typeEl.value || '';
            const suffix = suffixForReporterType(type);
            const digits = String(digitsEl.value || '').replace(/\D/g, '').slice(0, 5);
            digitsEl.value = digits;
            suffixEl.textContent = suffix || '?';
            suffixEl.classList.toggle('is-empty', !suffix);

            if (type) {
                fieldEl.classList.remove('is-disabled');
                digitsEl.removeAttribute('readonly');
                if (hintEl) hintEl.textContent = 'Enter the 5-digit number only. OMC and ' + suffix + ' are fixed.';
            } else {
                fieldEl.classList.add('is-disabled');
                digitsEl.setAttribute('readonly', 'readonly');
                if (hintEl) hintEl.textContent = 'Select type first, then enter the 5-digit number.';
            }

            const fullId = (type && digits.length === 5 && suffix) ? ('OMC' + digits + suffix) : '';
            fullEl.value = fullId;
            return fullId;
        }

        function parseReporterEmployeeId(employeeId) {
            const raw = String(employeeId || '').toUpperCase().replace(/\s+/g, '');
            const match = raw.match(/^OMC(\d{1,5})([FS])$/);
            if (!match) {
                return { digits: '', type: '', suffix: '' };
            }
            return {
                digits: match[1],
                type: match[2] === 'S' ? 'Staff' : 'Faculty',
                suffix: match[2],
            };
        }

        ['type', 'employee_id_digits'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener(id === 'type' ? 'change' : 'input', () => syncReporterEmployeeId('create'));
        });

        ['editType', 'editEmployeeIdDigits'].forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener(id === 'editType' ? 'change' : 'input', () => syncReporterEmployeeId('edit'));
        });

        syncReporterEmployeeId('create');
        syncReporterEmployeeId('edit');

        function copyReporterContact(button) {
            const contact = (button?.dataset?.contact || '').trim();
            if (!contact) return;

            const notifyCopied = () => {
                if (typeof window.showMpToast === 'function') {
                    window.showMpToast('Contact number copied!', {
                        type: 'success',
                        timer: 2200,
                        replaceGroup: 'reporter-contact-copy',
                    });
                    return;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Contact number copied!',
                        showConfirmButton: false,
                        timer: 2200,
                    });
                }
            };

            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(contact).then(notifyCopied).catch(() => {
                    window.prompt('Copy contact number:', contact);
                });
                return;
            }

            window.prompt('Copy contact number:', contact);
            notifyCopied();
        }

        function viewReporter(employee, name, type, email, contact, department) {
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
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100/80 text-sm font-semibold text-slate-950">${initials}</div>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-slate-900">${displayName}</p>
                            <p class="mt-0.5 font-mono text-xs text-slate-500">${employee || "—"}</p>
                        </div>
                    </div>
                    <div class="mt-3">${typeChip}</div>
                </div>
                <dl class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl ring-1 ring-slate-200/70">
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Department</dt>
                        <dd class="min-w-0 text-right text-sm font-medium text-slate-800">${department ? String(department).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') : "—"}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                        <dd class="min-w-0 break-all text-right text-sm font-medium text-slate-800">${email || "—"}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-4 py-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Contact</dt>
                        <dd class="text-right text-sm font-medium text-slate-800">
                            ${contact
                                ? `<button type="button" onclick="copyReporterContact(this)" data-contact="${String(contact).replace(/"/g, '&quot;')}" data-tooltip="Click to copy" class="inline-flex items-center gap-1.5 rounded-md px-1.5 py-0.5 transition hover:bg-slate-100">${contact}<i data-lucide="copy" class="h-3 w-3 text-slate-400"></i></button>`
                                : '—'}
                        </dd>
                    </div>
                </dl>
            `;

            viewModal.classList.remove("hidden");
            viewModal.classList.add("flex");
            if (typeof lucide !== "undefined") lucide.createIcons();
        }

        function editReporter(id, employee, first, middle, last, type, email, contact, departmentId) {
            clearDuplicateWarnings();
            document.getElementById("editReporterId").value = id;
            document.getElementById("editFirstName").value = first || "";
            document.getElementById("editMiddleName").value = middle || "";
            document.getElementById("editLastName").value = last || "";
            const typeMap = { 'Full-Time': 'Faculty', 'Part-Time': 'Staff' };
            const parsed = parseReporterEmployeeId(employee);
            const resolvedType = typeMap[type] || type || parsed.type || "";
            document.getElementById("editType").value = resolvedType;
            document.getElementById("editEmployeeIdDigits").value = parsed.digits || "";
            document.getElementById("editEmail").value = email || "";
            document.getElementById("editContact").value = contact || "";
            document.getElementById("editDepartment").value = departmentId ? String(departmentId) : "";
            syncReporterEmployeeId('edit');

            editModal.classList.remove("hidden");
            editModal.classList.add("flex");
        }

        function openDeleteModal(id) {
            document.getElementById("deleteReporterId").value = id;
            deleteModal.classList.remove("hidden");
            deleteModal.classList.add("flex");
        }

        const pinModal = document.getElementById("pinModal");
        const bulkPinModal = document.getElementById("bulkPinModal");

        function openPinModal(id, name, employeeId, email, hasPin, pinSetAt) {
            const form = document.getElementById("pinForm");
            form.reset();
            form.action = `/admin/reporters/${id}/pin`;

            document.getElementById("pinModalTitle").textContent = hasPin ? "Reset PIN" : "Create PIN";
            document.getElementById("pinModalName").textContent = name || "—";
            document.getElementById("pinModalEmployee").textContent = employeeId || "—";
            document.getElementById("pinModalStatus").textContent = hasPin
                ? `This reporter already has a PIN${pinSetAt ? " (set " + pinSetAt + ")" : ""}. Saving replaces it, and the old PIN stops working right away.`
                : "This reporter has no PIN yet, so they can't make reports until you create one.";

            const emailBox = document.getElementById("pinSendEmail");
            const emailLabel = document.getElementById("pinEmailLabel");
            emailBox.disabled = !email;
            emailBox.checked = !!email;
            emailLabel.textContent = email
                ? `Email the PIN to ${email}`
                : "No email on file. You'll see the PIN once after saving so you can hand it over.";

            syncPinMode();
            pinModal.classList.remove("hidden");
            pinModal.classList.add("flex");
            if (window.lucide) lucide.createIcons();
        }

        function closePinModal() {
            pinModal.classList.add("hidden");
            pinModal.classList.remove("flex");
        }

        function syncPinMode() {
            const manual = document.querySelector('#pinForm input[name="mode"]:checked')?.value === "manual";
            const input = document.getElementById("pinManualInput");
            document.getElementById("pinManualWrap").classList.toggle("hidden", !manual);
            input.disabled = !manual;
            if (manual) input.focus();
        }

        function openBulkPinModal() {
            bulkPinModal.classList.remove("hidden");
            bulkPinModal.classList.add("flex");
            if (window.lucide) lucide.createIcons();
        }

        function closeBulkPinModal() {
            bulkPinModal.classList.add("hidden");
            bulkPinModal.classList.remove("flex");
        }

        document.getElementById("pinManualInput").addEventListener("input", function () {
            this.value = this.value.replace(/\D/g, "").slice(0, 6);
        });

        document.getElementById("pinForm").addEventListener("submit", function (event) {
            const manual = this.querySelector('input[name="mode"]:checked')?.value === "manual";
            const pin = document.getElementById("pinManualInput").value;
            if (manual && !/^\d{6}$/.test(pin)) {
                event.preventDefault();
                const input = document.getElementById("pinManualInput");
                input.setCustomValidity("Enter exactly 6 digits.");
                input.reportValidity();
                input.addEventListener("input", () => input.setCustomValidity(""), { once: true });
                return;
            }
            document.getElementById("pinSubmitBtn").disabled = true;
        });

        [pinModal, bulkPinModal].forEach((modal) => {
            modal?.addEventListener("click", (event) => {
                if (event.target === modal) {
                    modal.classList.add("hidden");
                    modal.classList.remove("flex");
                }
            });
        });

        
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
                    `/admin/reporters/${reporterId}/deactivate`;


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
                    `/admin/reporters/${reporterId}/reactivate`;


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
                closePinModal();
                closeBulkPinModal();

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
        const importUnknownDepartments = document.getElementById('importUnknownDepartments');
        const importSubmitBtn = document.getElementById('importSubmitBtn');

        function showImportError(message) {

            importSubmitBtn.disabled = true;

            Swal.fire({
                icon: 'error',
                title: 'Import cannot continue',
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: '#0f172a',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl px-5 py-2.5'
                }
            });
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
                const name = [person.first_name, person.middle_name, person.last_name].filter(Boolean).join(' ');
                const status = person.error
                    ? `<span class="inline-flex rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">Skip</span>
                       <p class="mt-1 max-w-[220px] text-xs text-rose-600">${cellText(person.error)}</p>`
                    : '<span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Ready</span>';
                const department = person.department && !person.department_id
                    ? `${cellText(person.department)} <span class="text-xs text-amber-600">(not found)</span>`
                    : cellText(person.department);
                row.className = person.error ? 'bg-rose-50/40 align-top' : 'align-top';
                row.innerHTML = `
                    <td class="px-3 py-2 font-mono text-xs text-slate-400">${cellText(person.line)}</td>
                    <td class="px-3 py-2">${status}</td>
                    <td class="px-3 py-2 font-mono text-xs text-slate-700">${cellText(person.employee_id)}</td>
                    <td class="px-3 py-2 text-slate-900">${cellText(name)}</td>
                    <td class="px-3 py-2 text-slate-600">${cellText(person.type)}</td>
                    <td class="px-3 py-2 text-slate-600">${department}</td>
                    <td class="px-3 py-2 text-slate-600">${cellText(person.email_address)}</td>
                    <td class="px-3 py-2 text-slate-600">${cellText(person.contact_number)}</td>
                `;
                importPreviewBody.appendChild(row);
            });

            const total = data.total || 0;
            const ready = data.ready || 0;
            const skipped = data.skipped || 0;
            importPreviewCount.textContent = total
                ? `${total} row${total === 1 ? '' : 's'} found: ${ready} ready to add` + (skipped ? `, ${skipped} will be skipped` : '') + (total > 50 ? ' (showing the first 50, problems first)' : '')
                : 'No employees found in the file';

            const unknown = data.unknown_departments || [];
            importUnknownDepartments.classList.toggle('hidden', unknown.length === 0);
            importUnknownDepartments.textContent = unknown.length
                ? `Departments not found (these reporters will be saved without a department): ${unknown.join(', ')}. Add them in Departments first if you want them linked.`
                : '';

            importMappingPanel.classList.remove('hidden');
            importSubmitBtn.disabled = ready === 0;
            importSubmitBtn.textContent = ready ? `Add ${ready} reporter${ready === 1 ? '' : 's'}` : 'Import reporters';
        }

        async function previewImportFile(file) {

            const allowedExtensions = [
                'csv',
                'txt',
                'xlsx'
            ];

            const extension =
                file.name
                    .split('.')
                    .pop()
                    .toLowerCase();


            if (!allowedExtensions.includes(extension)) {

                showImportError(
                    'Please upload a CSV, TXT, or Excel file.'
                );

                return;
            }

            importError.classList.add('hidden');
            importFileName.textContent = file.name;
            importFileName.classList.remove('hidden');
            importSubmitBtn.disabled = true;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('/admin/reporters/import/preview', {
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

    <script>
        // =====================================================
        // REPORTER SWEETALERT VALIDATION
        // =====================================================

        function reporterAlert(
            icon,
            title,
            text
        ) {
            return Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'OK',
                confirmButtonColor: '#0f172a',
                allowOutsideClick: false,
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl px-5 py-2.5'
                }
            });
        }


        // =====================================================
        // GET FORM DATA
        // =====================================================

        function getReporterFormData(form) {

            return {
                employeeId:
                    form.querySelector('[name="employee_id"]')
                        ?.value
                        .trim(),

                firstName:
                    form.querySelector('[name="first_name"]')
                        ?.value
                        .trim(),

                middleName:
                    form.querySelector('[name="middle_name"]')
                        ?.value
                        .trim(),

                lastName:
                    form.querySelector('[name="last_name"]')
                        ?.value
                        .trim(),

                type:
                    form.querySelector('[name="type"]')
                        ?.value
                        .trim(),

                email:
                    form.querySelector('[name="email"]')
                        ?.value
                        .trim(),

                contact:
                    form.querySelector('[name="contact"]')
                        ?.value
                        .trim()
            };
        }


        // =====================================================
        // VALIDATE REPORTER
        // =====================================================

        function validateReporterForm(form) {

            const data = getReporterFormData(form);


            // =================================================
            // TYPE + EMPLOYEE ID (OMC####F/S)
            // =================================================

            if (!data.type) {
                reporterAlert(
                    'warning',
                    'Type required',
                    'Please select Faculty or Staff first.'
                );
                return false;
            }

            syncReporterEmployeeId(form.id === 'editReporterForm' ? 'edit' : 'create');
            data.employeeId = form.querySelector('[name="employee_id"]')?.value.trim() || '';

            if (!data.employeeId || !/^OMC\d{5}[FS]$/.test(data.employeeId)) {
                reporterAlert(
                    'warning',
                    'Employee ID required',
                    'Enter the 5-digit employee number. Format must be OMC00127F or OMC00127S.'
                );
                return false;
            }

            const expectedSuffix = data.type === 'Staff' ? 'S' : 'F';
            if (!data.employeeId.endsWith(expectedSuffix)) {
                reporterAlert(
                    'warning',
                    'Employee ID mismatch',
                    data.type === 'Staff'
                        ? 'Staff employee IDs must end with S.'
                        : 'Faculty employee IDs must end with F.'
                );
                return false;
            }


            // =================================================
            // FIRST NAME
            // =================================================

            if (!data.firstName) {

                reporterAlert(
                    'warning',
                    'First name required',
                    'Please enter the reporter first name.'
                );

                return false;
            }


            // =================================================
            // LAST NAME
            // =================================================

            if (!data.lastName) {

                reporterAlert(
                    'warning',
                    'Last name required',
                    'Please enter the reporter last name.'
                );

                return false;
            }

            const namePattern = /^[\p{L}\p{M}][\p{L}\p{M}\s'’.\-]*$/u;
            const badName = [
                ['First name', data.firstName],
                ['Middle name', data.middleName],
                ['Last name', data.lastName],
            ].find(([, value]) => value && !namePattern.test(value.replace(/\s+/g, ' ')));

            if (badName) {
                reporterAlert(
                    'warning',
                    badName[0] + ' not valid',
                    badName[0] + ' can only use letters, spaces, hyphens (-), apostrophes (\') and periods (.), and must start with a letter.'
                );

                return false;
            }


            // =================================================
            // EMAIL
            // =================================================

            if (!data.email) {

                reporterAlert(
                    'warning',
                    'Email address required',
                    'Please enter the reporter email address. Their PIN is sent there.'
                );

                return false;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {

                reporterAlert(
                    'warning',
                    'Invalid email address',
                    'Please enter a valid email address.'
                );

                return false;
            }


            // =================================================
            // CONTACT NUMBER
            // =================================================

            if (data.contact) {

                if (!/^[0-9]+$/.test(data.contact)) {

                    reporterAlert(
                        'warning',
                        'Invalid contact number',
                        'Contact number must contain numbers only.'
                    );

                    return false;
                }


                if (data.contact.length !== 11) {

                    reporterAlert(
                        'warning',
                        'Invalid contact number',
                        'Contact number must contain exactly 11 digits.'
                    );

                    return false;
                }
            }


            // =================================================
            // ALL VALID
            // =================================================

            return true;
        }


        // =====================================================
        // CREATE REPORTER VALIDATION
        // =====================================================

        document
            .getElementById('createReporterForm')
            ?.addEventListener('submit', function (event) {

                if (!validateReporterForm(this)) {

                    event.preventDefault();

                    return;
                }

            });


        // =====================================================
        // EDIT REPORTER VALIDATION
        // =====================================================

        document
            .getElementById('editReporterForm')
            ?.addEventListener('submit', function (event) {

                if (!validateReporterForm(this)) {

                    event.preventDefault();

                    return;
                }

            });

    </script>

    @if (session('reporter_duplicates') || session('reporter_form_mode'))
        @if (session('reporter_duplicates'))
            <template id="reporterDuplicateWarning">
                @include('partials.duplicate-name-warning', ['matches' => session('reporter_duplicates'), 'noun' => 'reporter', 'wrapperClass' => ''])
            </template>
        @endif
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const mode = @js(session('reporter_duplicate_mode') ?? session('reporter_form_mode', 'create'));
                const emailError = @js($errors->first('email'));
                const old = @js([
                    'reporter_id' => old('reporter_id'),
                    'employee_id' => old('employee_id'),
                    'first_name' => old('first_name'),
                    'middle_name' => old('middle_name'),
                    'last_name' => old('last_name'),
                    'type' => old('type'),
                    'email' => old('email'),
                    'contact' => old('contact'),
                    'department_id' => old('department_id'),
                ]);
                let form;

                if (mode === 'edit') {
                    editReporter(old.reporter_id, old.employee_id, old.first_name, old.middle_name, old.last_name, old.type, old.email, old.contact, old.department_id);
                    form = document.getElementById('editReporterForm');
                } else {
                    openCreateModal();
                    form = document.getElementById('createReporterForm');
                    const parsed = parseReporterEmployeeId(old.employee_id);
                    document.getElementById('type').value = old.type || parsed.type || '';
                    document.getElementById('employee_id_digits').value = parsed.digits || '';
                    ['first_name', 'middle_name', 'last_name', 'email', 'contact', 'department_id'].forEach((name) => {
                        const input = form.querySelector(`[name="${name}"]`);
                        if (input) input.value = old[name] || '';
                    });
                    syncReporterEmployeeId('create');
                }

                const slot = form?.querySelector('[data-duplicate-slot]');
                const template = document.getElementById('reporterDuplicateWarning');
                if (slot && template) {
                    slot.appendChild(template.content.cloneNode(true));
                    if (window.lucide) lucide.createIcons();
                }

                const emailInput = form?.querySelector('[name="email"]');
                if (emailInput && emailError) {
                    emailInput.classList.add('border-rose-400', 'ring-2', 'ring-rose-100');
                    const message = document.createElement('p');
                    message.className = 'mt-1.5 text-xs font-medium text-rose-600';
                    message.dataset.emailError = '1';
                    message.textContent = emailError;
                    emailInput.insertAdjacentElement('afterend', message);
                    emailInput.focus();
                    emailInput.addEventListener('input', () => {
                        emailInput.classList.remove('border-rose-400', 'ring-2', 'ring-rose-100');
                        message.remove();
                    }, { once: true });
                }
            });
        </script>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@endsection
