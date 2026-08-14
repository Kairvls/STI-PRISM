@php
$isUrgent = $report->report_urgency_level == "Urgent";

$urgencyPill = $isUrgent
    ? "bg-red-700 text-white"
    : "bg-neutral-50 text-slate-500 ring-1 ring-slate-200/80";

$statusMap = [
    "Pending" => "bg-orange-50 text-orange-700",
    "Processing" => "bg-sky-50 text-sky-700",
    "Resolved" => "bg-emerald-50 text-emerald-700",
    "Rejected" => "bg-rose-50 text-rose-700",
    "For Replacement" => "bg-orange-50 text-orange-700",
];

$currentStatus = $report->report_current_status;

$statusPill =
    $statusMap[$currentStatus]
    ?? "bg-slate-100 text-slate-600";

$nextOptions = [];
$relatedCount = (int) ($report->report_related_count ?? 1);

if ($currentStatus === "Pending") {
    $nextOptions = [
        [
            "label" => "Start processing",
            "value" => "Processing",
            "class" => "bg-neutral-100 text-slate-800 ring-1 ring-slate-200/80 hover:bg-neutral-200",
        ],
        [
            "label" => "Reject",
            "value" => "Rejected",
            "class" => "text-rose-600 hover:bg-rose-50",
        ],
    ];
} elseif ($currentStatus === "Processing") {
    $nextOptions = [
        [
            "label" => "Resolve",
            "value" => "Resolved",
            "class" => "bg-neutral-100 text-slate-800 ring-1 ring-slate-200/80 hover:bg-neutral-200",
        ],
        [
            "label" => "For replacement",
            "value" => "For Replacement",
            "class" => "text-slate-600 hover:bg-slate-50",
        ],
    ];
}

@endphp

<div class="group relative overflow-visible rounded-xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition hover:border-slate-300 hover:shadow-[0_8px_24px_rgba(15,23,42,0.06)]">
    @if ($relatedCount > 1)
        <button
            type="button"
            onclick="openReportHistory({{ $report->report_id }})"
            title="{{ $relatedCount }} reports on this equipment"
            class="absolute -right-1.5 -top-1.5 z-10 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white transition hover:bg-rose-700"
        >
            {{ $relatedCount }}
        </button>
    @endif

    <div class="overflow-hidden rounded-xl p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs text-slate-400">
                    #{{ $report->report_id }}
                    <span class="mx-1 text-slate-300">•</span>
                    {{ \Carbon\Carbon::parse($report->report_submitted_at)->diffForHumans() }}
                </p>

                <h3 class="mt-2 truncate text-[22px] font-semibold leading-tight tracking-tight text-slate-900">
                    {{ $report->equipment_name ?? "Unlisted Equipment" }}
                </h3>
                <p class="mt-1 truncate text-sm text-slate-500">
                    {{ $report->room_name ?? "No assigned room" }}
                </p>
            </div>

            <div class="flex shrink-0 flex-col items-end gap-1.5">
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold {{ $urgencyPill }}">
                    {{ $report->report_urgency_level }}
                </span>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-6 border-t border-slate-100 pt-4">
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Reporter</p>
                <p class="mt-1 truncate text-sm font-semibold text-slate-900">
                    {{ $report->reporter_full_name ?? "Unknown reporter" }}
                </p>
                <p class="mt-0.5 truncate text-xs text-slate-400">
                    {{ $report->reporter_employee_id }}
                </p>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Issue</p>
                <p class="mt-1 line-clamp-2 text-sm font-semibold text-slate-900">
                    {{ $report->report_suggested_issue ?? "No suggested issue" }}
                </p>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $statusPill }}">
                {{ $currentStatus }}
            </span>
            <span class="text-sm text-slate-400">
                {{ \Carbon\Carbon::parse($report->report_submitted_at)->format("M d, Y") }}
            </span>
        </div>

        @if (
            in_array($currentStatus, ["Resolved", "For Replacement", "Rejected"])
            && ($report->assigned_personnel_name || $report->assigned_purchaser_name)
        )
            <p class="mt-3 text-xs text-slate-400">
                Handled by
                <span class="font-medium text-slate-600">
                    {{ $report->assigned_personnel_name ?? $report->assigned_purchaser_name }}
                </span>
            </p>
        @endif

        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
            <div class="flex flex-wrap items-center gap-1.5">
                @if (!empty($nextOptions))
                    @foreach ($nextOptions as $option)
                        <button
                            type="button"
                            onclick="
                                openReportModal('update-modal-{{ $report->report_id }}');
                                const select = document.querySelector('#update-modal-{{ $report->report_id }} select[name=status]');
                                select.value='{{ $option['value'] }}';
                                toggleStatusFields(select);
                            "
                            class="h-9 rounded-lg px-3.5 text-xs font-medium transition {{ $option['class'] }}"
                        >
                            {{ $option["label"] }}
                        </button>
                    @endforeach
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <button
                    type="button"
                    onclick="openReportModal('view-modal-{{ $report->report_id }}'); switchReportViewTab({{ $report->report_id }}, 'details')"
                    title="View report"
                    class="flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                >
                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                    View
                </button>

                @if ($report->report_equipment_id)
                    <button
                        type="button"
                        onclick="openReportHistory({{ $report->report_id }})"
                        title="Previous reports for this equipment"
                        class="flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <i data-lucide="history" class="h-3.5 w-3.5"></i>
                        History
                    </button>
                @endif

                @if (
                    in_array($currentStatus, ["Resolved", "Rejected", "For Replacement"]) &&
                    !$report->report_is_archived
                )
                    <form method="POST" action="/maintenance/reports/archive/{{ $report->report_id }}">
                        @csrf
                        <button
                            class="flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                            Archive
                        </button>
                    </form>
                @endif

                @if ($report->report_is_archived)
                    <form method="POST" action="/maintenance/reports/restore/{{ $report->report_id }}">
                        @csrf
                        <button class="flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-50">
                            <i data-lucide="archive-restore" class="h-3.5 w-3.5"></i>
                            Restore
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
