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

    @if ($isMainReportsPage && isset($pendingReports))
        @php
            $monthlyHint = fn ($percentage) => is_null($percentage)
                ? 'New activity vs last month'
                : (($percentage > 0 ? '+' : '') . number_format($percentage, 2) . '% vs last month');
            $statusCard = function (string $label, string $status, $count, $percentage) use ($monthlyHint) {
                $isActive = request('status') === $status;

                return [
                    'label' => $label,
                    'hint' => $monthlyHint($percentage),
                    'value' => number_format((int) $count),
                    'href' => request()->fullUrlWithQuery(['status' => $isActive ? null : $status, 'page' => null, 'focus' => null]),
                    'active' => $isActive,
                    'title' => $isActive ? 'Show all statuses' : 'Show only '.$status.' reports',
                ];
            };
        @endphp
        <div class="mb-6">
            @include('layouts.partials.maintenance-stat-cards', [
                'cards' => [
                    $statusCard('Pending', 'Pending', $pendingReports, $pendingMonthlyPercentage ?? 0),
                    $statusCard('Processing', 'Processing', $processingReports, $processingMonthlyPercentage ?? 0),
                    $statusCard('Resolved', 'Resolved', $resolvedReports, $resolvedMonthlyPercentage ?? 0),
                ],
            ])
        </div>
    @endif

    @if (!$isMainReportsPage && empty($attentionFocus))
        <div class="mb-5 flex items-baseline justify-end gap-2">
            <span class="text-4xl font-black tracking-tight text-slate-950">
                {{ $reports->count() }}
            </span>
            <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">
                {{ $countLabel }}
            </span>
        </div>
    @endif

    @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $reports->total()])

    @include ("components.tables.reports-table",
        ["reports" => $reports])
    </div>

@endsection
