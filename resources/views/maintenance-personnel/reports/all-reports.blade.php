@extends ("layouts.maintenance-layout")

@section(
    "title",
    request()->is("maintenance/reports/urgent")
        ? "Urgent Reports"
        : (
            request()->is("maintenance/reports/pending")
                ? "Pending Reports"
                : (
                    request()->is("maintenance/reports/today")
                        ? "Today's Reports"
                        : "All Reports"
                )
        )
)

@section ("content")
    <div class="-mt-8">
    @php
        $isUrgentPage = request()->is('maintenance/reports/urgent');
        $isPendingPage = request()->is('maintenance/reports/pending');
        $isTodayPage = request()->is('maintenance/reports/today');
        $isMainReportsPage = !$isUrgentPage && !$isPendingPage && !$isTodayPage;

        $pageTitle = match (true) {
            $isUrgentPage => 'Urgent Reports',
            $isPendingPage => 'Pending Reports',
            $isTodayPage => "Today's Reports",
            default => 'Reports',
        };

        $pageDescription = match (true) {
            $isUrgentPage => 'View and manage urgent maintenance reports requiring immediate attention.',
            $isPendingPage => 'View and manage maintenance reports waiting for review and action.',
            $isTodayPage => 'View and manage maintenance reports submitted today.',
            default => 'View and manage all maintenance reports.',
        };

        $countLabel = match (true) {
            $isUrgentPage => 'Urgent Reports',
            $isPendingPage => 'Pending Reports',
            $isTodayPage => 'Reports Today',
            default => 'On This Page',
        };
    @endphp

    @if ($isMainReportsPage)
        <div class="mb-4">
            <h1 class="text-4xl font-black tracking-tight text-slate-950">
                Reports
            </h1>
            <p class="mt-1 text-slate-500">
                View and manage maintenance reports.
            </p>
        </div>
    @else
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="flex items-center gap-3 text-4xl font-black tracking-tight text-slate-950">
                    {{ $pageTitle }}
                    @if ($isUrgentPage)
                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-medium text-rose-700">
                            Priority
                        </span>
                    @endif
                </h1>
                <p class="mt-1 text-slate-500">
                    {{ $pageDescription }}
                </p>
            </div>

            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-black tracking-tight text-slate-950">
                    {{ $reports->count() }}
                </span>
                <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">
                    {{ $countLabel }}
                </span>
            </div>
        </div>
    @endif

    @include ("components.tables.reports-table",
        ["reports" => $reports])
    </div>

@endsection
