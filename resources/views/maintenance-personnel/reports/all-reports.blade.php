@extends ("layouts.maintenance-layout")

@section(
    "title",
    request()->is("maintenance/reports/urgent")
        ? "Urgent Reports"
        : (
            request()->is("maintenance/reports/today")
                ? "Today's Reports"
                : "All Reports"
        )
)

@section ("content")
    @php
        // =====================================================
        // CURRENT REPORT PAGE TYPE
        // =====================================================

        $isUrgentPage = request()->is('maintenance/reports/urgent');

        $isTodayPage = request()->is('maintenance/reports/today');


        // =====================================================
        // LIVE PAGE TITLE
        // =====================================================

        $pageTitle = match (true) {

            $isUrgentPage => 'Urgent Reports',

            $isTodayPage => "Today's Reports",

            default => 'Reports',

        };


        // =====================================================
        // LIVE PAGE DESCRIPTION
        // =====================================================

        $pageDescription = match (true) {

            $isUrgentPage =>
                'View and manage urgent maintenance reports requiring immediate attention.',

            $isTodayPage =>
                'View and manage maintenance reports submitted today.',

            default =>
                'View and manage all maintenance reports.',

        };


        // =====================================================
        // LIVE COUNT LABEL
        // =====================================================

        $countLabel = match (true) {

            $isUrgentPage => 'Urgent Reports',

            $isTodayPage => 'Reports Today',

            default => 'On This Page',

        };
    @endphp

    <div
        class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <!--<nav
                class="mb-1 flex select-none items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-400"
            >
                <span
                    class="cursor-pointer transition-colors hover:text-gray-600"
                >
                    Maintenance
                </span>

                <span>&rsaquo;</span>

                <span class="text-gray-600">
                    {{
                        $isUrgentPage
                            ? "Urgent Reports"
                            : "Dashboard"
                    }}
                </span>
            </nav>-->

            {{-- ===================================================== --}}
            {{-- LIVE PAGE TITLE --}}
            {{-- ===================================================== --}}

            <h1
                class="flex items-center gap-3
                    text-4xl font-extrabold
                    tracking-tight text-gray-900"
            >

                {{ $pageTitle }}


                {{-- ================================================= --}}
                {{-- URGENT PAGE PRIORITY BADGE --}}
                {{-- ================================================= --}}

                @if ($isUrgentPage)

                    <span
                        class="inline-flex items-center gap-1
                            rounded-full border border-red-200
                            bg-red-50 px-2.5 py-1
                            text-xs font-bold uppercase
                            tracking-wider text-red-700"
                    >

                        <span
                            class="h-2 w-2 animate-pulse
                                rounded-full bg-red-500"
                        ></span>

                        Priority

                    </span>

                @endif

            </h1>

            {{-- ===================================================== --}}
            {{-- LIVE PAGE DESCRIPTION --}}
            {{-- ===================================================== --}}

            <p class="mt-1 text-gray-500">

                {{ $pageDescription }}

            </p>
        </div>

        <!-- Live Count Badge -->
        <div class="flex items-center gap-2 self-start sm:self-center">
            <div
                class="shadow-3xs inline-flex items-center gap-2 rounded-xl border border-gray-200/60 bg-gray-100/80 px-3.5 py-2"
            >
                <span
                    class="w-2 h-2 rounded-full {{ $isUrgentPage ? 'bg-red-500' : 'bg-emerald-500' }} animate-pulse"
                ></span>

                {{-- ===================================================== --}}
                {{-- LIVE COUNT LABEL --}}
                {{-- ===================================================== --}}

                <span
                    class="text-xs font-bold uppercase
                        tracking-wider text-gray-400"
                >

                    {{ $countLabel }}

                </span>

                <span
                    class="shadow-2xs min-w-[24px] rounded-md bg-gray-900 px-2 py-0.5 text-center text-xs font-black text-white"
                >
                    {{ $reports->count() }}
                </span>
            </div>
        </div>
    </div>

    @include ("components.tables.reports-table",
        ["reports" => $reports])

@endsection
