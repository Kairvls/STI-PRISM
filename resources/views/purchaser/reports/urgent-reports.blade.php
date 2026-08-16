@extends("layouts.purchaser-layout")


{{-- ===================================================== --}}
{{-- PAGE TITLE --}}
{{-- ===================================================== --}}

@section("page-title", "Urgent Reports")


{{-- ===================================================== --}}
{{-- PAGE SUBTITLE --}}
{{-- ===================================================== --}}

@section(
    "page-subtitle",
    "Emergency reports available for backup response"
)


{{-- ===================================================== --}}
{{-- PAGE CONTENT --}}
{{-- ===================================================== --}}

@section("content")

<div>


    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div
        class="
            flex
            flex-col
            gap-4
            lg:flex-row
            lg:items-end
            lg:justify-between
        "
    >

        <div>

            <p class="pur-page-kicker">Emergency Response</p>
            <h1 class="pur-page-title">
                Urgent Reports
            </h1>

        </div>


        {{-- ================================================= --}}
        {{-- REPORT COUNT --}}
        {{-- ================================================= --}}

        <div
            class="
                inline-flex
                w-fit
                items-center
                gap-2
                rounded-xl
                border
                border-slate-200
                bg-white
                px-4
                py-2.5
            "
        >

            <span
                class="
                    h-2
                    w-2
                    rounded-full
                    bg-rose-500
                "
            ></span>

            <span
                class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-500
                "
            >
                On This Page
            </span>

            <span
                class="
                    rounded-md
                    bg-slate-900
                    px-2
                    py-1
                    text-xs
                    font-semibold
                    text-white
                "
            >
                {{ $urgentReports->count() }}
            </span>

        </div>

    </div>



    {{-- ===================================================== --}}
    {{-- FLASH MESSAGES --}}
    {{-- ===================================================== --}}

    @if (session('success'))

        <div
            class="
                mt-6
                rounded-xl
                border
                border-emerald-200
                bg-emerald-50
                px-4
                py-3
                text-sm
                text-emerald-700
            "
        >

            {{ session('success') }}

        </div>

    @endif


    @if (session('error'))

        <div
            class="
                mt-6
                rounded-xl
                border
                border-rose-200
                bg-rose-50
                px-4
                py-3
                text-sm
                text-rose-700
            "
        >

            {{ session('error') }}

        </div>

    @endif



    {{-- ===================================================== --}}
    {{-- FILTERS --}}
    {{-- ===================================================== --}}

    {{-- ===================================================== --}}
    {{-- ACTIVE AND ARCHIVE VIEW TOGGLE --}}
    {{-- ===================================================== --}}

    <div
        class="
            mt-7
            flex
            items-center
            justify-between
            gap-4
        "
    >

        <div
            class="
                inline-flex
                items-center
                rounded-xl
                bg-slate-200/70
                p-1
            "
        >


            {{-- ================================================= --}}
            {{-- ACTIVE REPORTS --}}
            {{-- ================================================= --}}

            <a
                href="{{
                    route(
                        'purchaser.reports.urgent',
                        array_filter([
                            'search' => request('search'),
                            'status' => request('status'),
                        ])
                    )
                }}"

                class="
                    flex
                    h-9
                    items-center
                    gap-2
                    rounded-lg
                    px-4
                    text-xs
                    font-semibold
                    transition

                    {{
                        !$archiveView

                            ? 'bg-white text-slate-950 shadow-sm'

                            : 'text-slate-500 hover:text-slate-900'
                    }}
                "
            >

                <i
                    data-lucide="folder-open"
                    class="h-4 w-4"
                ></i>

                Active

            </a>


            {{-- ================================================= --}}
            {{-- ARCHIVED REPORTS --}}
            {{-- ================================================= --}}

            <a
                href="{{
                    route(
                        'purchaser.reports.urgent',
                        array_filter([
                            'view' => 'archive',
                            'search' => request('search'),
                            'status' => request('status'),
                        ])
                    )
                }}"

                class="
                    flex
                    h-9
                    items-center
                    gap-2
                    rounded-lg
                    px-4
                    text-xs
                    font-semibold
                    transition

                    {{
                        $archiveView

                            ? 'bg-white text-slate-950 shadow-sm'

                            : 'text-slate-500 hover:text-slate-900'
                    }}
                "
            >

                <i
                    data-lucide="archive"
                    class="h-4 w-4"
                ></i>

                Archive

            </a>

        </div>

    </div>

    <form
        method="GET"

        class="
            mt-7
            flex
            flex-col
            gap-3
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-4
            lg:flex-row
            lg:items-center
        "
    >

        {{-- ===================================================== --}}
        {{-- KEEP ACTIVE OR ARCHIVE VIEW DURING SEARCH --}}
        {{-- ===================================================== --}}

        @if ($archiveView)

            <input
                type="hidden"
                name="view"
                value="archive"
            >

        @endif


        {{-- ================================================= --}}
        {{-- SEARCH --}}
        {{-- ================================================= --}}

        <div class="relative min-w-0 flex-1">

            <i
                data-lucide="search"

                class="
                    pointer-events-none
                    absolute
                    left-3.5
                    top-1/2
                    h-4
                    w-4
                    -translate-y-1/2
                    text-slate-400
                "
            ></i>


            <input
                type="text"

                name="search"

                value="{{ request('search') }}"

                placeholder="Search report, equipment, room, or reporter"

                class="
                    h-11
                    w-full
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    pl-10
                    pr-4
                    text-sm
                    outline-none
                    transition

                    focus:border-slate-400
                    focus:ring-4
                    focus:ring-slate-100
                "
            >

        </div>



        {{-- ================================================= --}}
        {{-- STATUS FILTER --}}
        {{-- ================================================= --}}

        <select
            name="status"

            class="
                h-11
                rounded-xl
                border
                border-slate-200
                bg-white
                px-4
                text-sm
                outline-none

                focus:border-slate-400
                focus:ring-4
                focus:ring-slate-100
            "
        >

            <option value="">
                All Statuses
            </option>


            <option
                value="Pending"
                {{ request('status') === 'Pending' ? 'selected' : '' }}
            >
                Pending
            </option>


            <option
                value="Processing"
                {{ request('status') === 'Processing' ? 'selected' : '' }}
            >
                Processing
            </option>


            <option
                value="Resolved"
                {{ request('status') === 'Resolved' ? 'selected' : '' }}
            >
                Resolved
            </option>


            <option
                value="For Replacement"
                {{
                    request('status') === 'For Replacement'
                        ? 'selected'
                        : ''
                }}
            >
                For Replacement
            </option>

        </select>



        {{-- ================================================= --}}
        {{-- SEARCH BUTTON --}}
        {{-- ================================================= --}}

        <button
            type="submit"

            class="
                flex
                h-11
                items-center
                justify-center
                gap-2
                rounded-xl
                bg-[#fff200]
                px-5
                text-sm
                font-semibold
                text-slate-950
                transition
                hover:bg-[#e6dc00]
            "
        >

            <i
                data-lucide="search"
                class="h-4 w-4"
            ></i>

            Search

        </button>

    </form>



    {{-- ===================================================== --}}
    {{-- REPORT CARDS --}}
    {{-- ===================================================== --}}

    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2">


        @forelse ($urgentReports as $report)


            {{-- ================================================= --}}
            {{-- REPORT CARD --}}
            {{-- ================================================= --}}

            <article
                class="
                    overflow-hidden
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                "
            >


                {{-- ================================================= --}}
                {{-- URGENT TOP BORDER --}}
                {{-- ================================================= --}}

                <div class="h-1 bg-rose-500"></div>



                <div class="p-5">


                    {{-- ================================================= --}}
                    {{-- REPORT HEADER --}}
                    {{-- ================================================= --}}

                    <div
                        class="
                            flex
                            items-start
                            justify-between
                            gap-4
                        "
                    >

                        <div class="min-w-0">

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-2
                                "
                            >

                                <span
                                    class="
                                        rounded-md
                                        bg-slate-100
                                        px-2
                                        py-1
                                        text-[11px]
                                        font-semibold
                                        uppercase
                                        tracking-wide
                                        text-slate-500
                                    "
                                >

                                    Report #{{ $report->report_id }}

                                </span>


                                <span
                                    class="
                                        text-xs
                                        text-slate-400
                                    "
                                >

                                    {{
                                        \Carbon\Carbon::parse(
                                            $report->report_submitted_at
                                        )->diffForHumans()
                                    }}

                                </span>

                            </div>


                            <h2
                                class="
                                    mt-3
                                    truncate
                                    text-lg
                                    font-semibold
                                    text-slate-950
                                "
                            >

                                {{
                                    $report->equipment_name
                                    ??
                                    $report->report_unlisted_equipment_name
                                    ??
                                    'Unknown Equipment'
                                }}

                            </h2>

                        </div>



                        {{-- ================================================= --}}
                        {{-- URGENT BADGE --}}
                        {{-- ================================================= --}}

                        <span
                            class="
                                shrink-0
                                rounded-full
                                border
                                border-rose-200
                                bg-rose-50
                                px-3
                                py-1.5
                                text-xs
                                font-semibold
                                text-rose-600
                            "
                        >

                            Urgent

                        </span>

                    </div>



                    {{-- ================================================= --}}
                    {{-- REPORT INFORMATION --}}
                    {{-- ================================================= --}}

                    <div
                        class="
                            mt-5
                            grid
                            grid-cols-1
                            gap-4
                            border-y
                            border-slate-100
                            py-5
                            sm:grid-cols-2
                        "
                    >


                        {{-- ================================================= --}}
                        {{-- REPORTER --}}
                        {{-- ================================================= --}}

                        <div>

                            <p class="text-xs text-slate-400">
                                Reporter
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-sm
                                    font-medium
                                    text-slate-800
                                "
                            >

                                {{
                                    $report->reporter_full_name
                                    ??
                                    'Unknown Reporter'
                                }}

                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- ROOM --}}
                        {{-- ================================================= --}}

                        <div>

                            <p class="text-xs text-slate-400">
                                Location Room
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-sm
                                    font-medium
                                    text-slate-800
                                "
                            >

                                {{
                                    $report->room_name
                                    ??
                                    'Unknown Room'
                                }}

                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- SUGGESTED ISSUE --}}
                        {{-- ================================================= --}}

                        <div>

                            <p class="text-xs text-slate-400">
                                Suggested Issue
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-sm
                                    font-medium
                                    text-slate-800
                                "
                            >

                                {{
                                    $report->report_suggested_issue
                                    ??
                                    'No suggested issue'
                                }}

                            </p>

                        </div>


                        {{-- ================================================= --}}
                        {{-- DATE SUBMITTED --}}
                        {{-- ================================================= --}}

                        <div>

                            <p class="text-xs text-slate-400">
                                Date Submitted
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-sm
                                    font-medium
                                    text-slate-800
                                "
                            >
                                {{
                                    \Carbon\Carbon::parse(
                                        $report->report_submitted_at
                                    )->format('M d, Y h:i A')
                                }}
                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- CURRENT STATUS --}}
                        {{-- ================================================= --}}

                        <div>

                            <p class="text-xs text-slate-400">
                                Status
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-sm
                                    font-semibold
                                    text-slate-800
                                "
                            >

                                {{ $report->report_current_status }}

                            </p>

                        </div>

                        <div>
                            <p class="text-xs text-slate-400 mb-1">
                                    Click to
                                </p>

                                {{-- ================================================= --}}
                                {{-- VIEW REPORT --}}
                                {{-- ALWAYS AVAILABLE --}}
                                {{-- ================================================= --}}

                                <button
                                    type="button"

                                    onclick="
                                        openUrgentReportModal(
                                            'urgentReportModal{{ $report->report_id }}'
                                        )
                                    "

                                    class="
                                        flex
                                        h-9
                                        w-20
                                        items-center
                                        gap-2
                                        rounded-lg
                                        bg-slate-100
                                        px-4
                                        text-xs
                                        font-semibold
                                        text-slate-700
                                        transition
                                        hover:bg-slate-100
                                    "
                                >

                                    <i
                                        data-lucide="eye"
                                        class="h-4 w-4"
                                    ></i>

                                    View

                                </button>
                            </div>

                    </div>



                    {{-- ================================================= --}}
                    {{-- CARD FOOTER --}}
                    {{-- HANDLED BY LEFT --}}
                    {{-- ACTION BUTTONS RIGHT --}}
                    {{-- ================================================= --}}

                    <div
                        class="
                            mt-5
                            flex
                            items-end
                            gap-4
                        "
                    >


                        {{-- ================================================= --}}
                        {{-- LEFT SIDE HANDLED BY --}}
                        {{-- ONLY SHOW AFTER PURCHASER ACCEPTS REPORT --}}
                        {{-- ================================================= --}}

                        @if (

                            $report->report_assigned_purchaser_id
                            !==
                            null

                            &&

                            in_array(
                                $report->report_current_status,
                                [
                                    'Processing',
                                    'Resolved',
                                    'For Replacement',
                                ],
                                true
                            )

                        )

                            <div class="min-w-0 shrink">

                                <p class="text-xs text-slate-400">
                                    Handled By
                                </p>


                                <p
                                    class="
                                        mt-1
                                        max-w-44
                                        truncate
                                        text-sm
                                        font-semibold
                                        text-slate-900
                                    "

                                    title="{{ auth()->user()->user_full_name }}"
                                >

                                    {{ auth()->user()->user_full_name }}

                                </p>


                                <p class="mt-0.5 text-xs text-slate-500">
                                    Purchaser
                                </p>

                            </div>

                        @endif



                        {{-- ================================================= --}}
                        {{-- RIGHT SIDE ACTION BUTTONS --}}
                        {{-- ML-AUTO ALWAYS PUSHES BUTTONS TO RIGHT --}}
                        {{-- ================================================= --}}

                        <div
                            class="
                                ml-auto
                                flex
                                flex-wrap
                                items-center
                                justify-end
                                gap-2
                            "
                        >


                            



                            {{-- ================================================= --}}
                            {{-- ACCEPT REPORT --}}
                            {{-- PENDING REPORT ONLY --}}
                            {{-- ================================================= --}}

                            @if (
                                $report->report_current_status === 'Pending'
                            )

                                <form
                                    method="POST"

                                    action="{{
                                        route(
                                            'purchaser.reports.urgent.accept',
                                            $report->report_id
                                        )
                                    }}"
                                >

                                    @csrf


                                    <button
                                        type="submit"

                                        class="
                                            flex
                                            h-9
                                            items-center
                                            gap-2
                                            rounded-lg
                                            bg-rose-600
                                            px-4
                                            text-xs
                                            font-semibold
                                            text-white
                                            transition
                                            hover:bg-rose-700
                                        "
                                    >

                                        <i
                                            data-lucide="siren"
                                            class="h-4 w-4"
                                        ></i>

                                        Accept Report

                                    </button>

                                </form>

                            @endif



                            {{-- ================================================= --}}
                            {{-- PROCESSING ACTIONS --}}
                            {{-- CURRENT PURCHASER ONLY --}}
                            {{-- ================================================= --}}

                            @if (

                                $report->report_current_status === 'Processing'

                                &&

                                $report->report_assigned_purchaser_id
                                    ==
                                Auth::id()

                            )


                                {{-- ================================================= --}}
                                {{-- RESOLVE REPORT --}}
                                {{-- ================================================= --}}

                                <button
                                    type="button"

                                    onclick="
                                        document
                                            .getElementById(
                                                'resolveModal{{ $report->report_id }}'
                                            )
                                            .classList
                                            .remove('hidden')
                                    "

                                    class="
                                        flex
                                        h-9
                                        items-center
                                        gap-2
                                        rounded-lg
                                        bg-emerald-600
                                        px-4
                                        text-xs
                                        font-semibold
                                        text-white
                                        transition
                                        hover:bg-emerald-700
                                    "
                                >

                                    <i
                                        data-lucide="circle-check"
                                        class="h-4 w-4"
                                    ></i>

                                    Resolve

                                </button>



                                {{-- ================================================= --}}
                                {{-- FOR REPLACEMENT --}}
                                {{-- ================================================= --}}

                                <button
                                    type="button"

                                    onclick="
                                        document
                                            .getElementById(
                                                'replacementModal{{ $report->report_id }}'
                                            )
                                            .classList
                                            .remove('hidden')
                                    "

                                    class="
                                        flex
                                        h-9
                                        items-center
                                        gap-2
                                        rounded-lg
                                        border
                                        border-slate-200
                                        bg-white
                                        px-4
                                        text-xs
                                        font-semibold
                                        text-slate-700
                                        transition
                                        hover:bg-slate-50
                                    "
                                >

                                    <i
                                        data-lucide="package-search"
                                        class="h-4 w-4"
                                    ></i>

                                    For Replacement

                                </button>

                            @endif



                            {{-- ================================================= --}}
                            {{-- ARCHIVE REPORT --}}
                            {{-- ================================================= --}}

                            @if (

                                !$archiveView

                                &&

                                $report->report_assigned_purchaser_id
                                    ==
                                Auth::id()

                                &&

                                in_array(
                                    $report->report_current_status,
                                    [
                                        'Resolved',
                                        'For Replacement',
                                    ],
                                    true
                                )

                            )

                                <form
                                    method="POST"

                                    action="{{
                                        route(
                                            'purchaser.reports.urgent.archive',
                                            $report->report_id
                                        )
                                    }}"
                                >

                                    @csrf


                                    <button
                                        type="submit"

                                        class="
                                            flex
                                            h-9
                                            items-center
                                            gap-2
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-4
                                            text-xs
                                            font-semibold
                                            text-slate-600
                                            transition
                                            hover:bg-slate-50
                                        "
                                    >

                                        <i
                                            data-lucide="archive"
                                            class="h-4 w-4"
                                        ></i>

                                        Archive

                                    </button>

                                </form>

                            @endif



                            {{-- ================================================= --}}
                            {{-- RESTORE REPORT --}}
                            {{-- ================================================= --}}

                            @if ($archiveView)

                                <form
                                    method="POST"

                                    action="{{
                                        route(
                                            'purchaser.reports.urgent.restore',
                                            $report->report_id
                                        )
                                    }}"
                                >

                                    @csrf


                                    <button
                                        type="submit"

                                        class="
                                            flex
                                            h-9
                                            items-center
                                            gap-2
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-4
                                            text-xs
                                            font-semibold
                                            text-slate-600
                                            transition
                                            hover:bg-slate-50
                                        "
                                    >

                                        <i
                                            data-lucide="archive-restore"
                                            class="h-4 w-4"
                                        ></i>

                                        Restore

                                    </button>

                                </form>

                            @endif

                        </div>

                    </div>

                </div>

            </article>

            {{-- ================================================= --}}
            {{-- VIEW REPORT MODAL --}}
            {{-- ================================================= --}}

            <div
                id="urgentReportModal{{ $report->report_id }}"

                class="
                    fixed
                    inset-0
                    z-[1000]
                    hidden
                    overflow-hidden
                "
            >


                {{-- ================================================= --}}
                {{-- MODAL BACKDROP --}}
                {{-- ================================================= --}}

                <div
                    class="
                        flex
                        min-h-screen
                        items-center
                        justify-center
                        bg-black/40
                        p-4
                        backdrop-blur-[2px]
                    "

                    onclick="
                        closeUrgentReportModal(
                            'urgentReportModal{{ $report->report_id }}'
                        )
                    "
                >


                    {{-- ================================================= --}}
                    {{-- MODAL PANEL --}}
                    {{-- ================================================= --}}

                    <div
                        class="
                            flex
                            max-h-[85vh]
                            w-full
                            max-w-2xl
                            flex-col
                            overflow-hidden
                            rounded-2xl
                            border
                            border-black/5
                            bg-white
                            shadow-2xl
                        "

                        onclick="event.stopPropagation()"
                    >


                        {{-- ================================================= --}}
                        {{-- MODAL HEADER --}}
                        {{-- ================================================= --}}

                        <div
                            class="
                                flex
                                shrink-0
                                items-start
                                justify-between
                                gap-6
                                border-b
                                border-slate-100
                                px-6
                                py-5
                            "
                        >

                            <div class="min-w-0">

                                <div
                                    class="
                                        flex
                                        flex-wrap
                                        items-center
                                        gap-2
                                    "
                                >

                                    <p
                                        class="
                                            text-[11px]
                                            font-semibold
                                            uppercase
                                            tracking-widest
                                            text-slate-400
                                        "
                                    >
                                        Urgent Maintenance Report
                                    </p>


                                    <span class="text-slate-300">
                                        /
                                    </span>


                                    <span class="text-xs text-slate-500">

                                        Ticket #{{ $report->report_id }}

                                    </span>

                                </div>


                                <h2
                                    class="
                                        mt-2
                                        truncate
                                        text-xl
                                        font-bold
                                        text-slate-950
                                    "
                                >

                                    {{
                                        $report->equipment_name
                                        ??
                                        $report->report_unlisted_equipment_name
                                        ??
                                        'Unknown Equipment'
                                    }}

                                </h2>


                                <p class="mt-1 text-sm text-slate-500">
                                    Report details and emergency response information.
                                </p>

                            </div>


                            {{-- ================================================= --}}
                            {{-- CLOSE BUTTON --}}
                            {{-- ================================================= --}}

                            <button
                                type="button"

                                onclick="
                                    closeUrgentReportModal(
                                        'urgentReportModal{{ $report->report_id }}'
                                    )
                                "

                                class="
                                    flex
                                    h-9
                                    w-9
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-full
                                    text-slate-400
                                    transition
                                    hover:bg-slate-100
                                    hover:text-slate-900
                                "
                            >

                                <i
                                    data-lucide="x"
                                    class="h-4 w-4"
                                ></i>

                            </button>

                        </div>


                        {{-- ================================================= --}}
                        {{-- SCROLLABLE CONTENT --}}
                        {{-- ================================================= --}}

                        <div class="min-h-0 flex-1 overflow-y-auto">


                            {{-- ================================================= --}}
                            {{-- STATUS SUMMARY --}}
                            {{-- ================================================= --}}

                            <div
                                class="
                                    flex
                                    flex-wrap
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-slate-100
                                    px-6
                                    py-4
                                "
                            >

                                <div class="flex flex-wrap items-center gap-2">

                                    <span
                                        class="
                                            rounded-full
                                            border
                                            border-slate-200
                                            bg-slate-50
                                            px-3
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-slate-700
                                        "
                                    >
                                        {{ $report->report_current_status }}
                                    </span>


                                    <span
                                        class="
                                            rounded-full
                                            border
                                            border-rose-200
                                            bg-rose-50
                                            px-3
                                            py-1
                                            text-xs
                                            font-semibold
                                            text-rose-600
                                        "
                                    >
                                        Urgent
                                    </span>

                                </div>


                                <span class="text-xs text-slate-400">

                                    Submitted

                                    {{
                                        \Carbon\Carbon::parse(
                                            $report->report_submitted_at
                                        )->format('M d, Y · h:i A')
                                    }}

                                </span>

                            </div>


                            {{-- ================================================= --}}
                            {{-- REPORT INFORMATION --}}
                            {{-- ================================================= --}}

                            <div class="px-6 py-2">


                                {{-- EQUIPMENT --}}

                                <div
                                    class="
                                        flex
                                        items-start
                                        justify-between
                                        gap-8
                                        py-3.5
                                    "
                                >

                                    <span class="text-sm text-slate-500">
                                        Equipment
                                    </span>


                                    <div class="max-w-[65%] text-right">

                                        <p class="text-sm font-semibold text-slate-900">

                                            {{
                                                $report->equipment_name
                                                ??
                                                $report->report_unlisted_equipment_name
                                                ??
                                                'Unknown Equipment'
                                            }}

                                        </p>


                                        @if ($report->equipment_asset_tag)

                                            <p class="mt-0.5 text-xs text-slate-400">

                                                Asset Tag:
                                                {{ $report->equipment_asset_tag }}

                                            </p>

                                        @endif

                                    </div>

                                </div>


                                {{-- ROOM --}}

                                <div
                                    class="
                                        flex
                                        items-start
                                        justify-between
                                        gap-8
                                        border-t
                                        border-slate-100
                                        py-3.5
                                    "
                                >

                                    <span class="text-sm text-slate-500">
                                        Room
                                    </span>


                                    <span
                                        class="
                                            max-w-[65%]
                                            text-right
                                            text-sm
                                            font-semibold
                                            text-slate-900
                                        "
                                    >

                                        {{
                                            $report->room_name
                                            ??
                                            'Unknown Room'
                                        }}

                                    </span>

                                </div>


                                {{-- REPORTER --}}

                                <div
                                    class="
                                        flex
                                        items-start
                                        justify-between
                                        gap-8
                                        border-t
                                        border-slate-100
                                        py-3.5
                                    "
                                >

                                    <span class="text-sm text-slate-500">
                                        Reporter
                                    </span>


                                    <div class="max-w-[65%] text-right">

                                        <p class="text-sm font-semibold text-slate-900">

                                            {{
                                                $report->reporter_full_name
                                                ??
                                                'Unknown Reporter'
                                            }}

                                        </p>


                                        <p class="mt-0.5 text-xs text-slate-400">

                                            Employee ID:

                                            {{
                                                $report->reporter_employee_id
                                                ??
                                                'N/A'
                                            }}

                                        </p>

                                    </div>

                                </div>


                                {{-- DATE SUBMITTED --}}

                                <div
                                    class="
                                        flex
                                        items-start
                                        justify-between
                                        gap-8
                                        border-t
                                        border-slate-100
                                        py-3.5
                                    "
                                >

                                    <span class="text-sm text-slate-500">
                                        Date Submitted
                                    </span>


                                    <span
                                        class="
                                            max-w-[65%]
                                            text-right
                                            text-sm
                                            font-semibold
                                            text-slate-900
                                        "
                                    >

                                        {{
                                            \Carbon\Carbon::parse(
                                                $report->report_submitted_at
                                            )->format('M d, Y h:i A')
                                        }}

                                    </span>

                                </div>


                                {{-- HANDLED BY --}}

                                @if (
                                    $report->report_assigned_purchaser_id
                                    !==
                                    null
                                )

                                    <div
                                        class="
                                            flex
                                            items-start
                                            justify-between
                                            gap-8
                                            border-t
                                            border-slate-100
                                            py-3.5
                                        "
                                    >

                                        <span class="text-sm text-slate-500">
                                            Handled By
                                        </span>


                                        <div class="max-w-[65%] text-right">

                                            <p class="text-sm font-semibold text-slate-900">

                                                {{ auth()->user()->user_full_name }}

                                            </p>


                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Purchaser
                                            </p>

                                        </div>

                                    </div>

                                @endif

                            </div>


                            {{-- ================================================= --}}
                            {{-- REPORT CONTENT --}}
                            {{-- ================================================= --}}

                            <div
                                class="
                                    space-y-5
                                    border-t
                                    border-slate-100
                                    px-6
                                    py-5
                                "
                            >


                                {{-- SUGGESTED ISSUE --}}

                                <div>

                                    <p class="text-sm font-semibold text-slate-700">
                                        Suggested Issue
                                    </p>


                                    <div
                                        class="
                                            mt-2
                                            whitespace-pre-wrap
                                            rounded-xl
                                            border
                                            border-slate-200
                                            bg-slate-50
                                            p-4
                                            text-sm
                                            leading-6
                                            text-slate-600
                                        "
                                    >

                                        {{
                                            $report->report_suggested_issue
                                            ??
                                            'No suggested issue provided.'
                                        }}

                                    </div>

                                </div>


                                {{-- PROBLEM DESCRIPTION --}}

                                <div>

                                    <p class="text-sm font-semibold text-slate-700">
                                        Problem Description
                                    </p>


                                    <div
                                        class="
                                            mt-2
                                            whitespace-pre-wrap
                                            rounded-xl
                                            border
                                            border-slate-200
                                            bg-slate-50
                                            p-4
                                            text-sm
                                            leading-6
                                            text-slate-600
                                        "
                                    >

                                        {{
                                            $report->report_problem_description
                                            ??
                                            'No problem description provided.'
                                        }}

                                    </div>

                                </div>


                                {{-- ORIGINAL REPORT IMAGE --}}

                                <div>

                                    <p class="text-sm font-semibold text-slate-700">
                                        Report Image
                                    </p>


                                    <p class="mt-1 text-xs text-slate-400">
                                        Image submitted by the reporter
                                    </p>


                                    @if ($report->report_uploaded_image)

                                        <div
                                            class="
                                                mt-3
                                                overflow-hidden
                                                rounded-xl
                                                border
                                                border-slate-200
                                                bg-slate-50
                                            "
                                        >

                                            <img

                                                src="{{
                                                    asset(
                                                        'storage/'
                                                        .
                                                        $report->report_uploaded_image
                                                    )
                                                }}"

                                                alt="Report image"

                                                class="
                                                    max-h-96
                                                    w-full
                                                    cursor-pointer
                                                    object-contain
                                                "

                                                onclick="
                                                    window.open(
                                                        this.src,
                                                        '_blank'
                                                    )
                                                "

                                            >

                                        </div>

                                    @else

                                        <div
                                            class="
                                                mt-3
                                                flex
                                                min-h-32
                                                items-center
                                                justify-center
                                                rounded-xl
                                                border
                                                border-dashed
                                                border-slate-200
                                                bg-slate-50
                                            "
                                        >

                                            <p class="text-sm text-slate-400">
                                                No report image submitted
                                            </p>

                                        </div>

                                    @endif

                                </div>


                                {{-- RESOLUTION INFORMATION --}}

                                @if (
                                    $report->report_current_status === 'Resolved'
                                )

                                    <div>

                                        <p class="text-sm font-semibold text-emerald-700">
                                            Resolution Notes
                                        </p>


                                        <div
                                            class="
                                                mt-2
                                                whitespace-pre-wrap
                                                rounded-xl
                                                border
                                                border-emerald-200
                                                bg-emerald-50
                                                p-4
                                                text-sm
                                                leading-6
                                                text-slate-700
                                            "
                                        >

                                            {{
                                                $report->report_resolution_notes
                                                ??
                                                'No resolution notes provided.'
                                            }}

                                        </div>


                                        @if ($report->report_resolution_image)

                                            <img

                                                src="{{
                                                    asset(
                                                        'storage/'
                                                        .
                                                        $report->report_resolution_image
                                                    )
                                                }}"

                                                alt="Resolution proof"

                                                class="
                                                    mt-3
                                                    max-h-96
                                                    w-full
                                                    cursor-pointer
                                                    rounded-xl
                                                    border
                                                    border-emerald-200
                                                    object-contain
                                                "

                                                onclick="
                                                    window.open(
                                                        this.src,
                                                        '_blank'
                                                    )
                                                "

                                            >

                                        @endif

                                    </div>

                                @endif


                                {{-- REPLACEMENT INFORMATION --}}

                                @if (
                                    $report->report_current_status
                                    ===
                                    'For Replacement'
                                )

                                    <div>

                                        <p class="text-sm font-semibold text-orange-700">
                                            Replacement Reason
                                        </p>


                                        <div
                                            class="
                                                mt-2
                                                whitespace-pre-wrap
                                                rounded-xl
                                                border
                                                border-orange-200
                                                bg-orange-50
                                                p-4
                                                text-sm
                                                leading-6
                                                text-slate-700
                                            "
                                        >

                                            {{
                                                $report->report_replacement_notes
                                                ??
                                                'No replacement reason provided.'
                                            }}

                                        </div>


                                        @if ($report->report_replacement_image)

                                            <img

                                                src="{{
                                                    asset(
                                                        'storage/'
                                                        .
                                                        $report->report_replacement_image
                                                    )
                                                }}"

                                                alt="Replacement supporting image"

                                                class="
                                                    mt-3
                                                    max-h-96
                                                    w-full
                                                    cursor-pointer
                                                    rounded-xl
                                                    border
                                                    border-orange-200
                                                    object-contain
                                                "

                                                onclick="
                                                    window.open(
                                                        this.src,
                                                        '_blank'
                                                    )
                                                "

                                            >

                                        @endif

                                    </div>

                                @endif

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- MODAL FOOTER --}}
                        {{-- ================================================= --}}

                        <div
                            class="
                                flex
                                shrink-0
                                justify-end
                                border-t
                                border-slate-100
                                px-6
                                py-4
                            "
                        >

                            <button
                                type="button"

                                onclick="
                                    closeUrgentReportModal(
                                        'urgentReportModal{{ $report->report_id }}'
                                    )
                                "

                                class="
                                    rounded-lg
                                    border
                                    border-slate-200
                                    px-4
                                    py-2
                                    text-sm
                                    font-medium
                                    text-slate-700
                                    transition
                                    hover:bg-slate-50
                                "
                            >
                                Close
                            </button>

                        </div>

                    </div>

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- RESOLVE MODAL --}}
            {{-- ================================================= --}}

            @if (
                $report->report_current_status === 'Processing'

                &&

                $report->report_assigned_purchaser_id
                ==
                auth()->user()->user_id
            )

                <div
                    id="resolveModal{{ $report->report_id }}"

                    class="
                        fixed
                        inset-0
                        z-[1000]
                        hidden
                        items-center
                        justify-center
                        bg-black/60
                        p-4
                    "
                >

                    <div
                        class="
                            mx-auto
                            mt-[10vh]
                            w-full
                            max-w-lg
                            rounded-2xl
                            bg-white
                            p-6
                            shadow-2xl
                        "
                    >

                        <h3 class="text-lg font-semibold text-slate-950">
                            Resolve Urgent Report
                        </h3>


                        <p class="mt-1 text-sm text-slate-500">
                            Add resolution notes and optional proof image.
                        </p>


                        <form
                            method="POST"

                            enctype="multipart/form-data"

                            action="{{
                                route(
                                    'purchaser.reports.urgent.resolve',
                                    $report->report_id
                                )
                            }}"

                            class="mt-5"
                        >

                            @csrf


                            {{-- ================================================= --}}
                            {{-- RESOLUTION NOTES --}}
                            {{-- ================================================= --}}

                            <label
                                class="
                                    text-xs
                                    font-semibold
                                    text-slate-600
                                "
                            >
                                Resolution Notes
                            </label>


                            <textarea
                                name="resolution_notes"

                                rows="4"

                                class="
                                    mt-2
                                    w-full
                                    resize-none
                                    rounded-xl
                                    border
                                    border-slate-200
                                    p-3
                                    text-sm
                                    outline-none

                                    focus:border-slate-400
                                    focus:ring-4
                                    focus:ring-slate-100
                                "
                            ></textarea>



                            {{-- ================================================= --}}
                            {{-- RESOLUTION IMAGE --}}
                            {{-- ================================================= --}}

                            <label
                                class="
                                    mt-4
                                    block
                                    text-xs
                                    font-semibold
                                    text-slate-600
                                "
                            >
                                Proof Image
                            </label>


                            <input
                                type="file"

                                name="resolution_image"

                                accept="image/*"

                                class="
                                    mt-2
                                    block
                                    w-full
                                    text-sm
                                    text-slate-500
                                "
                            >



                            {{-- ================================================= --}}
                            {{-- MODAL ACTIONS --}}
                            {{-- ================================================= --}}

                            <div
                                class="
                                    mt-6
                                    flex
                                    justify-end
                                    gap-2
                                "
                            >

                                <button
                                    type="button"

                                    onclick="
                                        document
                                            .getElementById(
                                                'resolveModal{{ $report->report_id }}'
                                            )
                                            .classList
                                            .add('hidden')
                                    "

                                    class="
                                        h-10
                                        rounded-lg
                                        border
                                        border-slate-200
                                        px-4
                                        text-sm
                                        font-medium
                                        text-slate-600
                                    "
                                >
                                    Cancel
                                </button>


                                <button
                                    type="submit"

                                    class="
                                        h-10
                                        rounded-lg
                                        bg-emerald-600
                                        px-4
                                        text-sm
                                        font-semibold
                                        text-white
                                    "
                                >
                                    Resolve Report
                                </button>

                            </div>

                        </form>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- REPLACEMENT MODAL --}}
                {{-- ================================================= --}}

                <div
                    id="replacementModal{{ $report->report_id }}"

                    class="
                        fixed
                        inset-0
                        z-[1000]
                        hidden
                        items-center
                        justify-center
                        bg-black/60
                        p-4
                    "
                >

                    <div
                        class="
                            mx-auto
                            mt-[10vh]
                            w-full
                            max-w-lg
                            rounded-2xl
                            bg-white
                            p-6
                            shadow-2xl
                        "
                    >

                        <h3 class="text-lg font-semibold text-slate-950">
                            Send for Replacement
                        </h3>


                        <p class="mt-1 text-sm text-slate-500">
                            Explain why this equipment requires replacement.
                        </p>


                        <form
                            method="POST"

                            enctype="multipart/form-data"

                            action="{{
                                route(
                                    'purchaser.reports.urgent.replacement',
                                    $report->report_id
                                )
                            }}"

                            class="mt-5"
                        >

                            @csrf


                            {{-- ================================================= --}}
                            {{-- REPLACEMENT NOTES --}}
                            {{-- ================================================= --}}

                            <label
                                class="
                                    text-xs
                                    font-semibold
                                    text-slate-600
                                "
                            >
                                Replacement Reason
                            </label>


                            <textarea
                                name="replacement_notes"

                                rows="4"

                                required

                                class="
                                    mt-2
                                    w-full
                                    resize-none
                                    rounded-xl
                                    border
                                    border-slate-200
                                    p-3
                                    text-sm
                                    outline-none

                                    focus:border-slate-400
                                    focus:ring-4
                                    focus:ring-slate-100
                                "
                            ></textarea>



                            {{-- ================================================= --}}
                            {{-- REPLACEMENT IMAGE --}}
                            {{-- ================================================= --}}

                            <label
                                class="
                                    mt-4
                                    block
                                    text-xs
                                    font-semibold
                                    text-slate-600
                                "
                            >
                                Supporting Image
                            </label>


                            <input
                                type="file"

                                name="replacement_image"

                                accept="image/*"

                                class="
                                    mt-2
                                    block
                                    w-full
                                    text-sm
                                    text-slate-500
                                "
                            >



                            {{-- ================================================= --}}
                            {{-- MODAL ACTIONS --}}
                            {{-- ================================================= --}}

                            <div
                                class="
                                    mt-6
                                    flex
                                    justify-end
                                    gap-2
                                "
                            >

                                <button
                                    type="button"

                                    onclick="
                                        document
                                            .getElementById(
                                                'replacementModal{{ $report->report_id }}'
                                            )
                                            .classList
                                            .add('hidden')
                                    "

                                    class="
                                        h-10
                                        rounded-lg
                                        border
                                        border-slate-200
                                        px-4
                                        text-sm
                                        font-medium
                                        text-slate-600
                                    "
                                >
                                    Cancel
                                </button>


                                <button
                                    type="submit"

                                    class="
                                        h-10
                                        rounded-lg
                                        bg-slate-900
                                        px-4
                                        text-sm
                                        font-semibold
                                        text-white
                                    "
                                >
                                    Send for Replacement
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            @endif


        @empty


            {{-- ================================================= --}}
            {{-- EMPTY STATE --}}
            {{-- ================================================= --}}

            <div
                class="
                    col-span-full
                    flex
                    min-h-[360px]
                    flex-col
                    items-center
                    justify-center
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    px-6
                    text-center
                "
            >

                <div
                    class="
                        flex
                        h-12
                        w-12
                        items-center
                        justify-center
                        rounded-full
                        bg-slate-100
                        text-slate-400
                    "
                >

                    <i
                        data-lucide="shield-check"
                        class="h-5 w-5"
                    ></i>

                </div>


                <h3
                    class="
                        mt-4
                        text-sm
                        font-semibold
                        text-slate-800
                    "
                >
                    No urgent reports available
                </h3>


                <p
                    class="
                        mt-1
                        max-w-sm
                        text-sm
                        leading-6
                        text-slate-500
                    "
                >
                    New urgent reports will appear here when emergency
                    response assistance is needed.
                </p>

            </div>

            


        @endforelse

    </div>



    {{-- ===================================================== --}}
    {{-- PAGINATION --}}
    {{-- ===================================================== --}}

    @if ($urgentReports->hasPages())

        <div class="mt-6">

            {{ $urgentReports->links() }}

        </div>

    @endif


</div>

{{-- ===================================================== --}}
            {{-- VIEW REPORT MODAL JAVASCRIPT --}}
            {{-- ===================================================== --}}

            <script>

                // =====================================================
                // OPEN VIEW REPORT MODAL HERE
                // =====================================================

                function openUrgentReportModal(modalId)
                {
                    const modal = document.getElementById(modalId);

                    if (!modal) {
                        return;
                    }

                    modal.classList.remove('hidden');

                    document.body.classList.add('overflow-hidden');
                }


                // =====================================================
                // CLOSE VIEW REPORT MODAL HERE
                // =====================================================

                function closeUrgentReportModal(modalId)
                {
                    const modal = document.getElementById(modalId);

                    if (!modal) {
                        return;
                    }

                    modal.classList.add('hidden');

                    document.body.classList.remove('overflow-hidden');
                }


                // =====================================================
                // CLOSE VIEW REPORT MODAL USING ESCAPE KEY HERE
                // =====================================================

                document.addEventListener('keydown', function (event)
                {
                    if (event.key !== 'Escape') {
                        return;
                    }

                    document
                        .querySelectorAll('[id^="urgentReportModal"]')
                        .forEach(function (modal)
                        {
                            modal.classList.add('hidden');
                        });

                    document.body.classList.remove('overflow-hidden');
                });

            </script>

@endsection