{{-- Top of report-card.blade.php --}}
@php

$urgencyColor =
    $report->report_urgency_level == "Urgent"
        ? "bg-gradient-to-r from-red-900 via-red-300 to-red-500"
        : "bg-gradient-to-r from-emerald-900 via-emerald-300 to-emerald-500";

$urgencyPill =
    $report->report_urgency_level == "Urgent"
        ? "bg-red-50 text-red-700 border border-red-200"
        : "bg-emerald-50 text-emerald-700 border border-emerald-200";

$statusMap = [
    "Pending" => "bg-amber-50 text-amber-700 border border-amber-200",
    "Processing" => "bg-blue-50 text-blue-700 border border-blue-200",
    "Resolved" => "bg-emerald-50 text-emerald-700 border border-emerald-200",
    "Rejected" => "bg-red-50 text-red-700 border border-red-200",
    "For Replacement" => "bg-orange-50 text-orange-700 border border-orange-200",
];

$dotMap = [
    "Pending" => "bg-amber-400",
    "Processing" => "bg-blue-400",
    "Resolved" => "bg-emerald-400",
    "Rejected" => "bg-red-400",
    "For Replacement" => "bg-orange-400",
];

$currentStatus = $report->report_current_status;

$statusPill =
    $statusMap[$currentStatus]
    ?? "bg-gray-100 text-gray-600 border border-gray-200";

$statusDot =
    $dotMap[$currentStatus]
    ?? "bg-gray-400";

$nextOptions = [];

if ($currentStatus === "Pending") {

    $nextOptions = [
        [
            "label" => "Start Processing",
            "value" => "Processing",
            "icon" => "play-circle",
            "class" => "bg-slate-100 hover:bg-slate-200 text-slate-800",
        ],
        [
            "label" => "Reject",
            "value" => "Rejected",
            "icon" => "x-circle",
            "class" => "bg-red-600 text-white border-b border-b-red-900 hover:bg-red-700",
        ],
    ];

} elseif ($currentStatus === "Processing") {

    $nextOptions = [
        [
            "label" => "Resolve",
            "value" => "Resolved",
            "icon" => "check-circle-2",
            "class" => "bg-slate-100 hover:bg-slate-200 text-slate-800",
        ],
        [
            "label" => "For Replacement",
            "value" => "For Replacement",
            "icon" => "refresh-cw",
            "class" => "bg-slate-100 hover:bg-slate-200 text-slate-800",
        ],
    ];

}

$canUpdate = in_array($currentStatus, ["Pending", "Processing"]);

@endphp

            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white transition-all hover:border-gray-300 hover:shadow-md"
            >
                <!-- Urgency accent bar -->
                <div class="relative h-1 w-full overflow-hidden">
                    <!-- Base Color -->
                    <div class="absolute inset-0 {{ $urgencyColor }}"></div>

                    <!-- Moving Shine -->
                    <div class="scanner-bar"></div>
                </div>

                <div class="p-5">
                    <!-- Header row -->

                    <div
                        class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4"
                    >
                        <div>
                            <div class="mb-2 flex items-center gap-2">
                                <span
                                    class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-500"
                                >
                                    Report #{{ $report->report_id }}
                                </span>

                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-slate-300"
                                ></span>

                                <span class="text-xs text-slate-400">
                                    {{
                                        \Carbon\Carbon::parse(
                                            $report->report_submitted_at,
                                        )->diffForHumans()
                                    }}
                                </span>
                            </div>

                            <h3
                                class="mt-1 text-lg font-bold leading-snug text-gray-900"
                            >
                                {{
                                    $report->equipment_name ??
                                        "Unlisted Equipment"
                                }}
                            </h3>
                        </div>

                        <span
                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold {{ $urgencyPill }}"
                        >
                            {{ $report->report_urgency_level }}
                        </span>
                    </div>

                    <div
                        class="mt-4 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2"
                    >
                        <div>
                            <span
                                class="mb-2 block text-xs font-medium text-gray-400"
                            >
                                Reporter
                            </span>

                            <div class="black items-center">
                                <p class="truncate font-semibold text-gray-700">
                                    {{
                                        $report->reporter_full_name ??
                                            "Unknown Reporter"
                                    }}
                                </p>

                                <span
                                    class="font-mono text-sm tracking-wider text-black"
                                >
                                    ID: {{ $report->reporter_employee_id }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span
                                class="block text-xs font-medium text-gray-400"
                                >Location Room</span
                            >
                            <p class="truncate font-semibold text-gray-700">{{
                                $report->room_name ??
                                    "No Assigned Room"
                            }}</p>
                        </div>

                        <div class="space-y-1 pt-1">
                            <span
                                class="block text-xs font-medium text-gray-400"
                                >Date Submitted</span
                            >
                            <p class="text-xs font-medium text-gray-600">
                                {{
                                    \Carbon\Carbon::parse(
                                        $report->report_submitted_at,
                                    )->format("M d, Y h:i A")
                                }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <span
                                class="block text-xs font-medium text-gray-400"
                                >Suggested Issue</span
                            >
                            <p class="truncate font-semibold text-gray-700">
                                {{
                                    $report->report_suggested_issue ??
                                        "No suggested issue provided"
                                }}
                            </p>
                        </div>
                    </div>

                    @php
                        $pipelineSteps = match ($currentStatus) {
                            "Pending" => [["step" => "Pending", "state" => "active"]],
                            "Processing" => [
                                ["step" => "Pending", "state" => "done"],
                                ["step" => "Processing", "state" => "active"],
                            ],
                            "Resolved" => [
                                ["step" => "Pending", "state" => "done"],
                                ["step" => "Processing", "state" => "done"],
                                ["step" => "Resolved", "state" => "done"],
                            ],
                            "Rejected" => [
                                ["step" => "Pending", "state" => "done"],
                                ["step" => "Rejected", "state" => "done"],
                            ],
                            "For Replacement" => [
                                ["step" => "Pending", "state" => "done"],
                                ["step" => "Processing", "state" => "done"],
                                ["step" => "For Replacement", "state" => "done"],
                            ],
                            default => [["step" => "Pending", "state" => "active"]],
                        };

                        $ARROW = 14; // px
                    @endphp

                    <div class="mt-4 space-y-2.5 border-t border-gray-100">
                        <p class="mt-4 text-[10px] font-bold uppercase tracking-widest text-gray-400">Workflow Pipeline Tracker</p>

                        <div
                            class="flex items-stretch"
                            style="height: 36px; gap: 3px"
                        >
                            @foreach ($pipelineSteps as $i => $ps)
                                @php
                                    $isFirst = $i === 0;
                                    $isLast = $i === count($pipelineSteps) - 1;
                                    $total = count($pipelineSteps);

                                    $stepName = $ps["step"];
                                    $isTerminalStep = in_array($stepName, ["Rejected", "For Replacement"]);
                                    if ($stepName === "Resolved") {
                                        $bg = "bg-emerald-100";
                                        $text = "text-emerald-700 font-semibold";
                                    } elseif ($stepName === "Rejected") {
                                        $bg = "bg-red-100";
                                        $text = "text-red-700 font-semibold";
                                    } elseif ($stepName === "For Replacement") {
                                        $bg = "bg-orange-100";
                                        $text = "text-orange-700 font-semibold";
                                    } elseif ($ps["state"] === "active") {
                                        $bg = "bg-[#FFF200]";
                                        $text = "text-gray-900 font-bold";
                                    } elseif ($ps["state"] === "done") {
                                        $bg = "bg-blue-100";
                                        $text = "text-blue-700 font-semibold";
                                    } else {
                                        $bg = "bg-gray-100";
                                        $text = "text-gray-400 font-medium";
                                    }

                                    if ($isFirst) {
                                        $clip = "polygon(0 0, calc(100% - {$ARROW}px) 0, 100% 50%, calc(100% - {$ARROW}px) 100%, 0 100%)";
                                    } elseif ($isLast) {
                                        $clip = "polygon({$ARROW}px 0, 100% 0, 100% 100%, 0 100%, {$ARROW}px 50%)";
                                    } else {
                                        $clip = "polygon({$ARROW}px 0, calc(100% - {$ARROW}px) 0, 100% 50%, calc(100% - {$ARROW}px) 100%, 0 100%, {$ARROW}px 50%)";
                                    }

                                    $pl = $isFirst ? 14 : $ARROW + 14;
                                    $pr = $isLast ? 14 : $ARROW + 10;
                                    $ml = $isFirst ? 0 : -$ARROW;
                                    $z = $total - $i;
                                @endphp
                                <div
                                    class="relative flex items-center justify-center gap-1.5 text-[11px] whitespace-nowrap select-none {{ $bg }} {{ $text }}"
                                    style="clip-path: {{ $clip }}; padding-left: {{ $pl }}px; padding-right: {{ $pr }}px; margin-left: {{ $ml }}px; z-index: {{ $z }}; min-width: 94px; max-width: 140px;"
                                >
                                    @if ($ps["state"] === "done")
                                        {{-- checkmark SVG --}}
                                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5" /></svg>
                                    @elseif ($ps["state"] === "active")
                                        {{-- spinner SVG --}}
                                        <svg class="h-3 w-3 shrink-0 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-6.219-8.56" /></svg>
                                    @endif

                                    {{ $ps["step"] }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Footer: status + actions -->
                    <div
                        class="mt-4 flex items-center justify-between gap-3 border-t border-gray-100 pt-4"
                    >
                        <!-- Status pill -->
                        {{-- Quick Transition --}}
                        @if (!empty($nextOptions))
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100"
                                    >
                                        <i
                                            data-lucide="workflow"
                                            class="h-4 w-4 text-slate-500"
                                        ></i>
                                    </div>

                                    <div>
                                        

                                        <p class="text-xs font-medium text-slate-600">Update status to</p>
                                    </div>
                                </div>

                                <!-- DIVIDER -->
                                <div
                                    class="hidden h-6 w-px bg-gray-200 lg:block"
                                ></div>
                                @foreach ($nextOptions as $option)
                                    <button
                                        type="button"
                                        onclick="
                                    openReportModal('update-modal-{{ $report->report_id }}');

                                    const select =
                                    document.querySelector(
                                        '#update-modal-{{ $report->report_id }} select[name=status]'
                                    );

                                    select.value='{{ $option['value'] }}';

                                    toggleStatusFields(select);
                                "
                                        class="h-8 px-3 rounded-lg text-[12px] transition {{ $option['class'] }}"
                                    >
                                        <div class="flex items-center gap-2">
                                            <!--<div
                                                class="flex h-4 w-4 items-center justify-center"
                                            >
                                                <i
                                                    data-lucide="{{ $option['icon'] }}"
                                                    class="h-4 w-4"
                                                >
                                                </i>
                                            </div>-->

                                            <span>
                                                {{
                                                    $option[
                                                        "label"
                                                    ]
                                                }}
                                            </span>
                                        </div>
                                    </button>

                                @endforeach
                            </div>
                        @endif

                        

                        {{-- ===================================================== --}}
                        {{-- HANDLED BY SECTION HERE --}}
                        {{-- ONLY SHOW FOR RESOLVED OR FOR REPLACEMENT REPORTS --}}
                        {{-- ===================================================== --}}

                        @if (
                            in_array(
                                $currentStatus,
                                [
                                    "Resolved",
                                    "For Replacement",
                                    "Rejected",
                                ]
                            )
                        )

                            <div class="min-w-0">

                                <p class="text-xs font-medium text-gray-400">
                                    Handled By
                                </p>


                                @if ($report->assigned_personnel_name)

                                    <div class="mt-1">

                                        <p
                                            class="
                                                max-w-36
                                                truncate
                                                text-sm
                                                font-semibold
                                                text-gray-800
                                            "
                                            title="{{ $report->assigned_personnel_name }}"
                                        >
                                            {{ $report->assigned_personnel_name }}
                                        </p>

                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Maintenance Personnel
                                        </p>

                                    </div>


                                @elseif ($report->assigned_purchaser_name)

                                    <div class="mt-1">

                                        <p
                                            class="
                                                max-w-36
                                                truncate
                                                text-sm
                                                font-semibold
                                                text-gray-800
                                            "
                                            title="{{ $report->assigned_purchaser_name }}"
                                        >
                                            {{ $report->assigned_purchaser_name }}
                                        </p>

                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Purchaser
                                        </p>

                                    </div>

                                @endif

                            </div>

                        @endif

                        <!-- Actions -->
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                onclick="openReportModal('view-modal-{{ $report->report_id }}')"
                                title="View Report"
                                class="flex h-8 items-center justify-center gap-x-1.5 rounded-lg  bg-slate-100 px-3 text-xs  text-slate-800 transition shadow-sm hover:bg-slate-200 hover:text-gray-600"
                            >
                                <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                View
                            </button>

                            @if (
                                in_array($currentStatus, ["Resolved", "Rejected", "For Replacement"]) &&
                                !$report->report_is_archived
                            )
                                <form
                                    method="POST"
                                    action="/maintenance/reports/archive/{{ $report->report_id }}"
                                >
                                    @csrf

                                    <button
                                        class="inline-flex h-8 items-center gap-2 rounded-lg bg-[rgba(0,55,199,0.85)] px-3 text-xs  text-white shadow-sm transition-all hover:bg-[rgba(0,44,155,0.85)] active:scale-95"
                                    >
                                        <i
                                            data-lucide="archive"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                        Archive
                                    </button>
                                </form>
                            @endif

                            @if ($report->report_is_archived)
                                <form
                                    method="POST"
                                    action="/maintenance/reports/restore/{{ $report->report_id }}"
                                >
                                    @csrf

                                    <button class="flex items-center gap-1.5 h-8 rounded-lg border bg-emerald-100 px-3 text-xs text-emerald-700 transition hover:bg-emerald-200">
                                    <i data-lucide="archive-restore" class="h-3.5 w-3.5"></i>
                                    <span>Restore</span>
                                    </button>
                                </form>

                            @endif
                        </div>

                        
                    </div>
                </div>
            </div>

        

        