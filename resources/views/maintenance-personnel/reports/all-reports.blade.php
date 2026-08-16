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
    <div>
    @php
        $isUrgentPage = request()->is('maintenance/reports/urgent');
        $isPendingPage = request()->is('maintenance/reports/pending');
        $isTodayPage = request()->is('maintenance/reports/today');
        $isMainReportsPage = !$isUrgentPage && !$isPendingPage && !$isTodayPage;

        $countLabel = match (true) {
            $isUrgentPage => 'Urgent Reports',
            $isPendingPage => 'Pending Reports',
            $isTodayPage => 'Reports Today',
            default => 'On This Page',
        };
    @endphp

    @if (!$isMainReportsPage)
        <div class="mb-5 flex items-baseline justify-end gap-2">
            <span class="text-4xl font-black tracking-tight text-slate-950">
                {{ $reports->count() }}
            </span>
            <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">
                {{ $countLabel }}
            </span>
        </div>
    @endif

    @include ("components.tables.reports-table",
        ["reports" => $reports])
    </div>

@endsection
