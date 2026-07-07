<style>
    @keyframes scanner {
        0% {
            left: -40%;
        }

        100% {
            left: 140%;
        }
    }

    .scanner-bar {
        position: absolute;
        top: -4px;
        bottom: -4px;
        width: 180px;

        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 1),
            rgba(255, 255, 255, 0.7),
            transparent
        );

        filter: blur(12px);

        animation: scanner 2s linear infinite;
    }
</style>

<div
    class="overflow-hidden rounded-lg border-t border-b border-slate-300 bg-gray-100 shadow-sm"
>
    <div
        class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr] "
    >
        <!-- Total Equipment -->
        <div class="flex items-center justify-between px-8 py-6">

            <!-- Left Content -->
            <div class="flex flex-col">
                <p class="text-sm font-medium text-slate-500">
                    Total Equipment
                </p>

                <h2 class="mt-2 text-5xl font-medium text-slate-900">
                    630
                </h2>

                <p class="mt-3 text-sm">
                    <span class="font-semibold text-emerald-500">
                        +12.45%
                    </span>

                    <span class="text-slate-500">
                        From last month
                    </span>
                </p>
            </div>

            <!-- Right Graph -->
            <div class="ml-6 h-20 w-40 shrink-0">
                <svg
                    viewBox="0 0 300 100"
                    class="h-full w-full"
                    fill="none"
                >
                    <path
                        d="M0 62
                        L35 28
                        L62 58
                        L82 52
                        L112 82
                        L162 82
                        L200 42
                        L232 64
                        L270 64
                        L300 18"
                        stroke="#3b82f6"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M0 62
                        L35 28
                        L62 58
                        L82 52
                        L112 82
                        L162 82
                        L200 42
                        L232 64
                        L270 64
                        L300 18
                        L300 100
                        L0 100 Z"
                        fill="#3b82f6"
                        fill-opacity=".08"
                    />
                </svg>
            </div>

        </div>

        <!-- Active -->
        <div class="relative flex flex-col justify-between px-8 py-7">

            <span
                class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
            ></span>

            <p class="text-md font-medium text-slate-600">
                Active
            </p>

            <h2 class="text-5xl font-medium text-slate-900">
                1,250
            </h2>

            <p class="text-base">
                <span class="font-semibold text-emerald-500">
                    +8.32%
                </span>

                <span class="text-slate-500">
                    From last month
                </span>
            </p>
        </div>

        <!-- Under Maintenance -->
        <div class="relative flex flex-col justify-between px-8 py-7">

            <span
                class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
            ></span>

            <p class="text-md font-medium text-slate-600">
                Under Maintenance
            </p>

            <h2 class="text-5xl font-medium text-slate-900">
                5
            </h2>

            <p class="text-base">
                <span class="font-semibold text-red-500">
                    -4.67%
                </span>

                <span class="text-slate-500">
                    From last month
                </span>
            </p>
        </div>

        <!-- Disposed -->
        <div class="relative flex flex-col justify-between px-8 py-7">

            <span
                class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
            ></span>

            <p class="text-md font-medium text-slate-600">
                Disposed
            </p>

            <h2 class="text-5xl font-medium text-slate-900">
                10
            </h2>

            <p class="text-base">
                <span class="font-semibold text-emerald-500">
                    +2.15%
                </span>

                <span class="text-slate-500">
                    From last month
                </span>
            </p>
        </div>
    </div>
</div>

<div
    class="overflow-hidden rounded-2xl mt-6 border border-gray-200 bg-white shadow-sm"
>

    <!-- FILTER BAR -->
    <div class="border-b border-gray-100 bg-white px-5 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <form
                method="GET"
                class="flex flex-1 flex-col items-center gap-3 lg:flex-row"
            >
                <input
                    type="hidden"
                    name="archive"
                    value="{{ request('archive', 0) }}"
                />

                <!-- SEARCH -->
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search report ID, equipment, room, reporter…"
                        class="h-9 w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200"
                    />
                </div>

                <!-- STATUS -->
                <select
                    name="status"
                    class="h-9 cursor-pointer rounded-xl border border-gray-200 bg-gray-50 px-3 pr-8 text-sm text-gray-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                >
                    @if (request("archive"))
                        <option value="">All Archived Statuses</option>

                        <option
                            value="Resolved"
                            {{
                                request("status") == "Resolved"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Resolved
                        </option>

                        <option
                            value="Rejected"
                            {{
                                request("status") == "Rejected"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Rejected
                        </option>

                        <option
                            value="For Replacement"
                            {{
                                request("status") == "For Replacement"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            For Replacement
                        </option>

                    @else
                        <option value="">All Statuses</option>

                        <option
                            value="Pending"
                            {{
                                request("status") == "Pending"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Pending
                        </option>

                        <option
                            value="Processing"
                            {{
                                request("status") == "Processing"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Processing
                        </option>

                        <option
                            value="Resolved"
                            {{
                                request("status") == "Resolved"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Resolved
                        </option>

                        <option
                            value="Rejected"
                            {{
                                request("status") == "Rejected"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Rejected
                        </option>

                        <option
                            value="For Replacement"
                            {{
                                request("status") == "For Replacement"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            For Replacement
                        </option>

                    @endif
                </select>

                <!-- DIVIDER -->
                @if (!request()->is("maintenance/reports/urgent"))
                    <div class="hidden h-6 w-px bg-gray-200 lg:block"></div>
                @endif

                <!-- PRIORITIES BUTTON -->
                @if (!request()->is("maintenance/reports/urgent"))
                    <select
                        name="urgency"
                        class="h-9 cursor-pointer rounded-xl border border-gray-200 bg-gray-50 px-3 pr-8 text-sm text-gray-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                    >
                        <option value="">All Priorities</option>

                        <option
                            value="Urgent"
                            {{
                                request("urgency") == "Urgent"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Urgent
                        </option>

                        <option
                            value="Non-Urgent"
                            {{
                                request("urgency") == "Non-Urgent"
                                    ? "selected"
                                    : ""
                            }}
                        >
                            Non-Urgent
                        </option>
                    </select>

                @endif

                <!-- SEARCH BUTTON -->
                <button
                    class="h-9 rounded-xl border border-[rgba(0,55,199,0.4)] bg-[rgba(0,55,199,0.85)] px-5 text-sm text-[#f0f2f8] font-semibold shadow-sm transition hover:bg-[rgba(0,44,155,0.85)]"
                >
                    Search
                </button>

                <!-- DIVIDER -->
                <div class="hidden h-6 w-px bg-gray-200 lg:block"></div>

                <div
                    class="flex shrink-0 items-center gap-0.5 rounded-xl bg-gray-100 p-1"
                >
                    <a
                        href="{{ request()->url() }}?archive=0"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition
                    {{ !request('archive')
                            ? 'bg-[#FFF200] text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'
                    }}"
                    >
                        <i data-lucide="folder-open" class="h-3.5 w-3.5"></i>
                        Active
                    </a>

                    <a
                        href="{{ request()->url() }}?archive=1"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition
                    {{ request('archive')
                            ? 'bg-[#FFF200] text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'
                    }}"
                    >
                        <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                        Archive
                    </a>
                </div>
            </form>

            <!-- DIVIDER -->
            <div class="hidden h-6 w-px bg-gray-200 lg:block"></div>

            <!-- VIEW TOGGLE -->
            <div
                class="flex shrink-0 items-center gap-0.5 rounded-xl bg-gray-100 p-1"
            >
                <button
                    type="button"
                    id="card-view-btn"
                    onclick="setReportView('card')"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-gray-500 transition hover:text-gray-700"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1" />
                        <rect x="14" y="3" width="7" height="7" rx="1" />
                        <rect x="3" y="14" width="7" height="7" rx="1" />
                        <rect x="14" y="14" width="7" height="7" rx="1" />
                    </svg>
                    Cards
                </button>
                <button
                    type="button"
                    id="table-view-btn"
                    onclick="setReportView('table')"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-gray-500 transition hover:text-gray-700"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h18M3 6h18M3 14h18M3 18h18" /></svg>
                    Table
                </button>
            </div>
        </div>
    </div>

    <!-- CARD VIEW -->
    <div
        id="card-view"
        class="grid hidden grid-cols-1 gap-4 p-5 xl:grid-cols-2"
    >
        @forelse ($reports as $report)
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
                    "For Replacement" =>
                        "bg-orange-50 text-orange-700 border border-orange-200",
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
                    $statusMap[$currentStatus] ??
                    "bg-gray-100 text-gray-600 border border-gray-200";
                $statusDot = $dotMap[$currentStatus] ?? "bg-gray-400";

                $nextOptions = [];

                if ($currentStatus === "Pending") {
                    $nextOptions = [
                        [
                            "label" => "Start Processing",
                            "value" => "Processing",
                            "icon" => "play-circle",
                            "class" =>
                                "bg-slate-100 hover:bg-slate-200 text-slate-800 ",
                        ],

                        [
                            "label" => "Reject",
                            "value" => "Rejected",
                            "icon" => "x-circle",
                            "class" =>
                                "bg-red-600 text-white border-b border-b-red-900 hover:bg-red-700 hover:border-red-900",
                        ],
                    ];
                } elseif ($currentStatus === "Processing") {
                    $nextOptions = [
                        [
                            "label" => "Resolve",
                            "value" => "Resolved",
                            "icon" => "check-circle-2",
                            "class" =>
                                "bg-slate-100 hover:bg-slate-200 text-slate-800 ",
                        ],

                        [
                            "label" => "For Replacement",
                            "value" => "For Replacement",
                            "icon" => "refresh-cw",
                            "class" =>
                                "bg-slate-100 hover:bg-slate-200 text-slate-800 ",
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
                                        class="inline-flex h-8 items-center gap-2 rounded-lg bg-slate-900 px-3 text-xs  text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95"
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

                                    <button class="flex items-center gap-1.5 h-8 rounded-lg border border-[rgba(0,55,199,0.4)] bg-[rgba(0,55,199,0.85)] px-3 text-xs text-[#f0f2f8] transition hover:bg-[rgba(0,55,199,1)]">
                                    <i data-lucide="archive-restore" class="h-3.5 w-3.5"></i>
                                    <span>Restore</span>
                                    </button>
                                </form>

                            @endif
                        </div>
                    </div>
                </div>
            </div>

        @empty
            <div
                class="col-span-full flex flex-col items-center justify-center gap-3 py-20 text-center"
            >
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100"
                >
                    <i
                        data-lucide="file-search"
                        class="h-6 w-6 text-gray-400"
                    ></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">No Reports Found</p>
                    <p class="mt-1 text-sm text-gray-400">No maintenance reports match the current filters.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- TABLE VIEW -->
    <div id="table-view" class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Report ID
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Reporter
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Room & Equipment
                    </th>
                    <!--<th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Equipment
                    </th>-->
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Urgency
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Status
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Date Submitted
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $i => $report)
                    @php
                        $urgencyPill =
                            $report->report_urgency_level == "Urgent"
                                ? "bg-red-50 text-red-700 border border-red-200"
                                : "bg-emerald-50 text-emerald-700 border border-emerald-200";

                        $statusMap = [
                            "Pending" => [
                                "pill" => "bg-amber-50 text-amber-700 border border-amber-200",
                                "dot" => "bg-amber-400",
                            ],
                            "Processing" => [
                                "pill" => "bg-blue-50 text-blue-700 border border-blue-200",
                                "dot" => "bg-blue-400",
                            ],
                            "Resolved" => [
                                "pill" => "bg-emerald-50 text-emerald-700 border border-emerald-200",
                                "dot" => "bg-emerald-400",
                            ],
                            "Rejected" => [
                                "pill" => "bg-red-50 text-red-700 border border-red-200",
                                "dot" => "bg-red-400",
                            ],
                            "For Replacement" => [
                                "pill" => "bg-orange-50 text-orange-700 border border-orange-200",
                                "dot" => "bg-orange-400",
                            ],
                        ];
                        $currentStatus = $report->report_current_status;
                        $sCfg = $statusMap[$currentStatus] ?? [
                            "pill" => "bg-gray-100 text-gray-600 border border-gray-200",
                            "dot" => "bg-gray-400",
                        ];
                        $canUpdate = in_array($currentStatus, ["Pending", "Processing"]);
                        $rowBg = $loop->even ? "bg-gray-50/40" : "";
                    @endphp
                    <tr
                        class="border-b border-gray-100 hover:bg-yellow-50/30 transition {{ $rowBg }}"
                    >
                        <td class="px-5 py-4 text-sm font-bold text-gray-900">
                            #{{ $report->report_id }}
                        </td>
                        <td class="px-5 py-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{
                                        $report->reporter_full_name ??
                                            "Unknown Reporter"
                                    }}
                                </p>

                                <hr class="mb-1 mt-1" />

                                <p class="text-xs text-black">
                                    ID: {{ $report->reporter_employee_id }}
                                </p>
                            </div>
                        </td>
                        <!--<td class="px-5 py-4 text-sm text-gray-600">
                            {{
                                $report->room_name ??
                                    "No Assigned Room"
                            }}
                        </td>
                        <td class="px-5 py-4 text-sm font-medium text-gray-700">
                            {{
                                $report->equipment_name ??
                                    "Unlisted Equipment"
                            }}
                        </td>-->

                        <td class="px-5 py-4 text-sm text-gray-600">
                            <div>
                                <p class="font-medium text-slate-800">
                                    {{ $report->equipment_name }}
                                </p>

                                <p class="text-xs text-slate-400">
                                    {{ $report->room_name }}
                                </p>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $urgencyPill }}"
                            >
                                {{ $report->report_urgency_level }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $sCfg['pill'] }}"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full {{ $sCfg['dot'] }}"
                                ></span>
                                {{ $currentStatus }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-400">
                            {{
                                \Carbon\Carbon::parse(
                                    $report->report_submitted_at,
                                )->format("M d, Y h:i A")
                            }}
                        </td>
                        <td class="px-5 py-4">
                            <div
                                class="flex items-center justify-center gap-1.5"
                            >
                                <button
                                    type="button"
                                    title="View Report"
                                    onclick="openReportModal('view-modal-{{ $report->report_id }}')"
                                    class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg  bg-slate-100 px-3 text-xs  text-slate-800 transition shadow-sm hover:bg-slate-200 hover:text-gray-600"
                                >
                                    <i
                                        data-lucide="eye"
                                        class="h-3.5 w-3.5"
                                    ></i>
                                    
                                </button>
                                @if ($canUpdate)
                                    <button
                                        type="button"
                                        title="Update Report"
                                        onclick="openReportModal('update-modal-{{ $report->report_id }}')"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[rgba(0,55,199,0.85)] text-white transition hover:bg-[rgba(0,44,155,0.85)]"
                                    >
                                        <i
                                            data-lucide="edit-3"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                        
                                    </button>
                                @endif
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
                                            title="Archive Report"
                                            class="inline-flex h-9 items-center gap-2 rounded-lg bg-slate-900 px-3 text-xs  text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95"
                                        >
                                            <i
                                                data-lucide="archive"
                                                class="h-3.5 w-3.5"
                                            ></i>
                                            
                                        </button>
                                    </form>
                                @endif
                                @if ($report->report_is_archived)
                                    <form
                                        method="POST"
                                        action="/maintenance/reports/restore/{{ $report->report_id }}"
                                    >
                                        @csrf

                                        <button title="Restore Report" class="flex items-center gap-1.5 h-9 rounded-lg border border-[rgba(0,55,199,0.4)] bg-[rgba(0,55,199,0.85)] px-3 text-xs text-[#f0f2f8] transition hover:bg-[rgba(0,55,199,1)]">
                                        <i data-lucide="archive-restore" class="h-3.5 w-3.5"></i>
                                        
                                        </button>
                                    </form>

                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-20">
                            <div
                                class="flex flex-col items-center justify-center gap-3 text-center"
                            >
                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100"
                                >
                                    <i
                                        data-lucide="file-search"
                                        class="h-6 w-6 text-gray-400"
                                    ></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700">No Reports Found</p>
                                    <p class="mt-1 text-sm text-gray-400">No maintenance reports match the current filters.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div
        class="flex items-center justify-between border-t border-gray-100 bg-gray-50/50 px-5 py-3"
    >
        <p class="text-xs text-gray-400">{{ $reports->total() }} report{{
            $reports->total() !== 1
                ? "s"
                : ""
        }} found</p>
        {{ $reports->links() }}
    </div>
</div>

<!-- VIEW MODALS -->
@foreach ($reports as $report)
    @php
        $statusMap = [
            "Pending" => "bg-amber-50 text-amber-700 border border-amber-200",
            "Processing" => "bg-blue-50 text-blue-700 border border-blue-200",
            "Resolved" => "bg-emerald-50 text-emerald-700 border border-emerald-200",
            "Rejected" => "bg-red-50 text-red-700 border border-red-200",
            "For Replacement" =>
                "bg-orange-50 text-orange-700 border border-orange-200",
        ];
        $urgencyPill =
            $report->report_urgency_level == "Urgent"
                ? "bg-red-50 text-red-700 border border-red-200"
                : "bg-emerald-50 text-emerald-700 border border-emerald-200";
    @endphp

    <div
    id="view-modal-{{ $report->report_id }}"
    class="fixed inset-0 z-50 hidden overflow-hidden"
>
    <!-- ===================================== -->
    <!-- MODAL BACKDROP -->
    <!-- ===================================== -->
    <div
        class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
    >
        <!-- ===================================== -->
        <!-- REPORT DETAILS MODAL -->
        <!-- ===================================== -->
        <div
            class="flex max-h-[80vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
            onclick="event.stopPropagation()"
        >
            <!-- ===================================== -->
            <!-- MODAL HEADER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6 border-b border-dashed border-slate-500"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p
                            class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                        >
                            Maintenance Report
                        </p>

                        <span class="text-xs text-slate-300">
                            /
                        </span>

                        <span class="text-xs font-medium text-slate-500">
                            Ticket #{{ $report->report_id }}
                        </span>
                    </div>

                    <h2
                        class="mt-2 truncate text-xl font-bold tracking-tight text-slate-950"
                    >
                        {{
                            $report->equipment_name ??
                                "Unlisted Equipment"
                        }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Report details and maintenance workflow information.
                    </p>
                </div>

                <!-- CLOSE BUTTON -->
                <button
                    type="button"
                    onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ===================================== -->
            <!-- SCROLLABLE CONTENT -->
            <!-- ===================================== -->
            <div
                class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100"
            >
                <!-- ===================================== -->
                <!-- STATUS SUMMARY -->
                <!-- ===================================== -->
                <div
                    class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-6 py-4"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusMap[$report->report_current_status] ?? 'bg-slate-100 text-slate-600' }}"
                        >
                            {{ $report->report_current_status }}
                        </span>

                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $urgencyPill }}"
                        >
                            {{ $report->report_urgency_level }}
                        </span>
                    </div>

                    <span class="text-xs text-slate-400">
                        Submitted
                        {{
                            \Carbon\Carbon::parse(
                                $report->report_submitted_at,
                            )->format("M d, Y · h:i A")
                        }}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- REPORT INFORMATION -->
                <!-- ===================================== -->
                <div class="px-6 py-2">
                    <!-- EQUIPMENT -->
                    <div
                        class="flex items-start justify-between gap-8 py-3.5"
                    >
                        <span class="shrink-0 text-sm text-slate-500">
                            Equipment
                        </span>

                        <span
                            class="max-w-[65%] break-words text-right text-sm font-medium text-slate-950"
                        >
                            {{
                                $report->equipment_name ??
                                    "Unlisted Equipment"
                            }}
                        </span>
                    </div>

                    <!-- ROOM -->
                    <div
                        class="flex items-start justify-between gap-8 border-t border-slate-100 py-3.5"
                    >
                        <span class="shrink-0 text-sm text-slate-500">
                            Room
                        </span>

                        <span
                            class="max-w-[65%] break-words text-right text-sm font-medium text-slate-900"
                        >
                            {{
                                $report->room_name ??
                                    "No Assigned Room"
                            }}
                        </span>
                    </div>

                    <!-- REPORTER -->
                    <div
                        class="flex items-start justify-between gap-8 border-t border-slate-100 py-3.5"
                    >
                        <span class="shrink-0 text-sm text-slate-500">
                            Reporter
                        </span>

                        <div class="max-w-[65%] text-right">
                            <p class="text-sm font-medium text-slate-900">
                                {{
                                    $report->reporter_full_name ??
                                        "Unknown Reporter"
                                }}
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                Employee ID:
                                {{
                                    $report->reporter_employee_id ??
                                        "—"
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- DATE SUBMITTED -->
                    <div
                        class="flex items-start justify-between gap-8 border-t border-slate-100 py-3.5"
                    >
                        <span class="shrink-0 text-sm text-slate-500">
                            Date submitted
                        </span>

                        <span
                            class="max-w-[65%] text-right text-sm font-medium text-slate-900"
                        >
                            {{
                                \Carbon\Carbon::parse(
                                    $report->report_submitted_at,
                                )->format("M d, Y h:i A")
                            }}
                        </span>
                    </div>
                </div>

                <!-- ===================================== -->
                <!-- REPORT CONTENT -->
                <!-- ===================================== -->
                <div
                    class="space-y-6 border-t border-slate-100 px-6 py-5"
                >
                    <!-- SUGGESTED ISSUE -->
                    <div>
                        <p
                            class="text-sm font-medium text-slate-700"
                        >
                            Suggested issue
                        </p>

                        <p
                            class="mt-2 whitespace-pre-wrap border border-dashed border-slate-500 rounded-lg break-words text-sm leading-6 text-slate-600"
                        >
                            {{
                                $report->report_suggested_issue ??
                                    "No suggested issue provided."
                            }}
                        </p>
                    </div>

                    <!-- PROBLEM DESCRIPTION -->
                    <div>
                        <p
                            class="text-sm font-medium text-slate-700"
                        >
                            Problem description
                        </p>

                        <p
                            class="mt-2 whitespace-pre-wrap border border-dashes border-slate-500 rounded-lg break-words text-sm leading-6 text-slate-600"
                        >
                            {{
                                $report->report_problem_description ??
                                    "No problem description provided."
                            }}
                        </p>
                    </div>
                </div>

                <!-- ===================================== -->
                <!-- RESOLVED RESULT -->
                <!-- ===================================== -->
                @if ($report->report_current_status === "Resolved")
                    <div
                        class="border-t border-slate-100 px-6 py-5"
                    >
                        <div
                            class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                                >
                                    <i
                                        data-lucide="check"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-medium text-emerald-900"
                                    >
                                        Resolution notes
                                    </p>

                                    <p
                                        class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600"
                                    >
                                        {{
                                            $report->report_resolution_notes ?:
                                                "No resolution notes provided."
                                        }}
                                    </p>

                                    @if ($report->report_resolution_image)
                                        <div class="mt-4">
                                            <img
                                                src="{{ asset('storage/'.$report->report_resolution_image) }}"
                                                alt="Resolution proof"
                                                class="max-h-64 w-full rounded-lg border border-emerald-200 object-contain"
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- ===================================== -->
                <!-- REJECTED RESULT -->
                <!-- ===================================== -->
                @if ($report->report_current_status === "Rejected")
                    <div
                        class="border-t border-slate-100 px-6 py-5"
                    >
                        <div
                            class="rounded-xl border border-rose-200 bg-rose-50/40 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600"
                                >
                                    <i
                                        data-lucide="x"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-medium text-rose-900"
                                    >
                                        Rejection notes
                                    </p>

                                    <p
                                        class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600"
                                    >
                                        {{
                                            $report->report_rejection_notes ?:
                                                "No rejection details provided."
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- ===================================== -->
                <!-- FOR REPLACEMENT RESULT -->
                <!-- ===================================== -->
                @if ($report->report_current_status === "For Replacement")
                    <div
                        class="border-t border-slate-100 px-6 py-5"
                    >
                        <div
                            class="rounded-xl border border-amber-200 bg-amber-50/40 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"
                                >
                                    <i
                                        data-lucide="refresh-cw"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-medium text-amber-900"
                                    >
                                        Replacement justification
                                    </p>

                                    <p
                                        class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600"
                                    >
                                        {{
                                            $report->report_replacement_notes ?:
                                                "No replacement justification provided."
                                        }}
                                    </p>

                                    @if ($report->report_replacement_image)
                                        <div class="mt-4">
                                            <img
                                                src="{{ asset('storage/'.$report->report_replacement_image) }}"
                                                alt="Replacement proof"
                                                class="max-h-64 w-full rounded-lg border border-amber-200 object-contain"
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="border-t border-dashed border-slate-500"></div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-center justify-end px-6 py-4"
            >
                <button
                    type="button"
                    onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- UPDATE MODALS -->
@foreach ($reports as $report)
    <div
    id="update-modal-{{ $report->report_id }}"
    class="fixed inset-0 z-50 hidden overflow-hidden"
>
    <!-- ===================================== -->
    <!-- MODAL BACKDROP -->
    <!-- ===================================== -->
    <div
        class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
    >
        <!-- ===================================== -->
        <!-- UPDATE STATUS MODAL -->
        <!-- ===================================== -->
        <div
            class="flex max-h-[80vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
            onclick="event.stopPropagation()"
        >
            <!-- ===================================== -->
            <!-- MODAL HEADER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p
                            class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                        >
                            Maintenance Workflow
                        </p>

                        <span class="text-xs text-slate-300">
                            /
                        </span>

                        <span class="text-xs font-medium text-slate-500">
                            Ticket #{{ $report->report_id }}
                        </span>
                    </div>

                    <h2
                        class="mt-2 truncate text-lg font-bold tracking-tight text-slate-950"
                    >
                        {{
                            $report->equipment_name ??
                                ($report->report_unlisted_equipment_name ??
                                    "Equipment Report")
                        }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Update maintenance progress and documentation.
                    </p>
                </div>

                <!-- CLOSE BUTTON -->
                <button
                    type="button"
                    onclick="closeReportModal('update-modal-{{ $report->report_id }}')"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ===================================== -->
            <!-- UPDATE STATUS FORM -->
            <!-- ===================================== -->
            <form
                action="/maintenance/reports/update-status/{{ $report->report_id }}"
                method="POST"
                enctype="multipart/form-data"
                class="flex min-h-0 flex-1 flex-col"
            >
                @csrf

                <!-- ===================================== -->
                <!-- SCROLLABLE CONTENT -->
                <!-- ===================================== -->
                <div
                    class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-5"
                >
                    <div class="space-y-5">
                        <!-- ===================================== -->
                        <!-- CURRENT STATUS -->
                        <!-- ===================================== -->
                        <div
                            class="flex items-center justify-between gap-6 rounded-xl border border-slate-200 px-4 py-3.5"
                        >
                            <span class="text-sm text-slate-500">
                                Current status
                            </span>

                            @php
                                $statusColors = [
                                    "Pending" =>
                                        "bg-amber-50 text-amber-700",
                                    "Processing" =>
                                        "bg-sky-50 text-sky-700",
                                    "Resolved" =>
                                        "bg-emerald-50 text-emerald-700",
                                    "Rejected" =>
                                        "bg-rose-50 text-rose-700",
                                    "For Replacement" =>
                                        "bg-orange-50 text-orange-700",
                                ];

                                $currentStyle =
                                    $statusColors[
                                        $report->report_current_status
                                    ] ??
                                    "bg-slate-100 text-slate-600";
                            @endphp

                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium {{ $currentStyle }}"
                            >
                                {{ $report->report_current_status }}
                            </span>
                        </div>

                        <!-- ===================================== -->
                        <!-- PIPELINE ACTION -->
                        <!-- ===================================== -->
                        <div>
                            <label
                                for="status-{{ $report->report_id }}"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Update status
                            </label>

                            <select
                                id="status-{{ $report->report_id }}"
                                name="status"
                                required
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                                @if ($report->report_current_status == "Pending")
                                    <option value="" selected disabled>
                                        Select status update
                                    </option>

                                    <option value="Processing">
                                        Processing
                                    </option>

                                    <option value="Rejected">
                                        Reject
                                    </option>
                                @elseif ($report->report_current_status == "Processing")
                                    <option value="" selected disabled>
                                        Select status update
                                    </option>

                                    <option value="Resolved">
                                        Resolved
                                    </option>

                                    <option value="For Replacement">
                                        For Replacement
                                    </option>
                                @else
                                    <option
                                        value="{{ $report->report_current_status }}"
                                        selected
                                        disabled
                                    >
                                        This report is archived as
                                        {{ $report->report_current_status }}
                                    </option>
                                @endif
                            </select>
                        </div>

                        <!-- ===================================== -->
                        <!-- NOTES SECTION -->
                        <!-- ===================================== -->
                        <div
                            id="notes-section-{{ $report->report_id }}"
                            class="hidden"
                        >
                            <div
                                class="mb-2 flex items-center justify-between gap-4"
                            >
                                <label
                                    for="remarks-{{ $report->report_id }}"
                                    class="text-sm font-medium text-slate-700"
                                >
                                    Documentation and remarks
                                </label>

                                <span class="text-xs text-slate-400">
                                    Optional
                                </span>
                            </div>

                            <textarea
                                id="remarks-{{ $report->report_id }}"
                                name="remarks"
                                rows="4"
                                placeholder="Add findings, actions taken, or justification"
                                class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            ></textarea>
                        </div>

                        <!-- ===================================== -->
                        <!-- PROOF IMAGE SECTION -->
                        <!-- ===================================== -->
                        <div
                            id="image-section-{{ $report->report_id }}"
                            class="hidden"
                        >
                            <div
                                class="mb-2 flex items-center justify-between gap-4"
                            >
                                <label
                                    class="text-sm font-medium text-slate-700"
                                >
                                    Proof image
                                </label>

                                <span class="text-xs text-slate-400">
                                    Optional
                                </span>
                            </div>

                            <!-- IMAGE UPLOAD -->
                            <label
                                for="proof_image_{{ $report->report_id }}"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-4 transition hover:border-slate-400 hover:bg-slate-50"
                            >
                                <div
                                    id="upload-icon-{{ $report->report_id }}"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                                >
                                    <i
                                        data-lucide="image-plus"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                                <div
                                    id="upload-text-container-{{ $report->report_id }}"
                                    class="min-w-0 flex-1"
                                >
                                    <p
                                        class="text-sm font-medium text-slate-700"
                                    >
                                        Upload proof image
                                    </p>

                                    <p class="mt-0.5 text-xs text-slate-400">
                                        PNG, JPG, JPEG or WEBP up to 10MB
                                    </p>
                                </div>

                                <input
                                    type="file"
                                    id="proof_image_{{ $report->report_id }}"
                                    name="proof_image"
                                    accept="image/*"
                                    onchange="previewImage(this, '{{ $report->report_id }}')"
                                    class="hidden"
                                />
                            </label>

                            <!-- ===================================== -->
                            <!-- IMAGE PREVIEW -->
                            <!-- ===================================== -->
                            <div
                                id="preview-container-{{ $report->report_id }}"
                                class="relative mt-3 hidden overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                            >
                                <img
                                    id="preview-img-{{ $report->report_id }}"
                                    class="max-h-64 w-full object-contain"
                                    alt="Proof preview"
                                />

                                <!-- IMAGE ACTIONS -->
                                <div
                                    class="absolute right-2 top-2 flex items-center gap-1.5"
                                >
                                    <button
                                        type="button"
                                        onclick="openLightbox('preview-img-{{ $report->report_id }}')"
                                        title="View image"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-black/70 text-white transition hover:bg-black"
                                    >
                                        <i
                                            data-lucide="zoom-in"
                                            class="h-4 w-4"
                                        ></i>
                                    </button>

                                    <button
                                        type="button"
                                        onclick="removeUploadedImage('{{ $report->report_id }}')"
                                        title="Remove image"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-black/70 text-white transition hover:bg-rose-600"
                                    >
                                        <i
                                            data-lucide="trash-2"
                                            class="h-4 w-4"
                                        ></i>
                                    </button>
                                </div>
                            </div>

                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                Upload repair photos, damage evidence, or
                                replacement documentation.
                            </p>
                        </div>

                        <!-- ===================================== -->
                        <!-- REPLACEMENT WARNING -->
                        <!-- ===================================== -->
                        <div
                            id="replacement-warning-{{ $report->report_id }}"
                            class="hidden rounded-xl border border-amber-200 bg-amber-50/50 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"
                                >
                                    <i
                                        data-lucide="triangle-alert"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-medium text-amber-900"
                                    >
                                        Procurement workflow
                                    </p>

                                    <p
                                        class="mt-1 text-sm leading-5 text-amber-700"
                                    >
                                        This action creates a procurement
                                        request and notifies the Purchaser
                                        Department.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===================================== -->
                <!-- MODAL FOOTER -->
                <!-- ===================================== -->
                <div
                    class="flex shrink-0 items-center justify-end gap-2 px-6 py-4"
                >
                    <button
                        type="button"
                        onclick="closeReportModal('update-modal-{{ $report->report_id }}')"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                    >
                        Update status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- GLOBAL LIGHTBOX MODAL CONTAINER -->
<div
    id="global-image-lightbox"
    class="animate-fade-in fixed inset-0 z-[100] flex hidden items-center justify-center bg-black/90 p-4 backdrop-blur-md"
    onclick="closeLightbox()"
>
    <!-- Top Bar Control Controls -->
    <div class="absolute right-4 top-4 flex items-center gap-3">
        <span
            id="lightbox-filename"
            class="hidden font-mono text-xs text-slate-400 sm:inline-block"
        ></span>
        <button
            type="button"
            onclick="closeLightbox()"
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-white/20"
        >
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>

    <!-- Large Zoomed Dynamic Target Element -->
    <div
        class="max-h-[85vh] max-w-4xl overflow-hidden rounded-xl border border-white/10 shadow-2xl"
        onclick="event.stopPropagation()"
    >
        <img
            id="lightbox-target-img"
            class="h-full max-h-[85vh] w-full object-contain"
            src=""
            alt="Expanded Proof Preview"
        />
    </div>
</div>

<!-- UNDO TOAST -->
@if (session("undo_report_id"))
    <div
        id="undo-toast"
        class="fixed bottom-6 right-6 z-[60] hidden max-w-sm rounded-2xl border border-gray-200 bg-white p-4 shadow-xl"
    >
        <div class="flex items-start gap-3">
            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100"
            >
                <i
                    data-lucide="refresh-cw"
                    class="h-4 w-4 text-emerald-600"
                ></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900">Status updated</p>
                <p class="mt-0.5 text-xs text-gray-500">{{
                    session(
                        "success",
                    )
                }}</p>
            </div>
            <form
                action="/maintenance/reports/update-status/{{ session('undo_report_id') }}"
                method="POST"
            >
                @csrf
                <input
                    type="hidden"
                    name="status"
                    value="{{ session('undo_previous_status') }}"
                />
                <input type="hidden" name="undo" value="1" />
                <button
                    type="submit"
                    class="rounded-lg bg-[#FFF200] px-3 py-1.5 text-xs font-bold text-gray-900 transition hover:bg-yellow-300"
                >
                    Undo
                </button>
            </form>
        </div>
    </div>
@endif

<script>
    function openReportModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove("hidden");
    }

    function closeReportModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add("hidden");
    }

    function setReportView(view) {
        const tableView = document.getElementById("table-view");
        const cardView = document.getElementById("card-view");
        const tableBtn = document.getElementById("table-view-btn");
        const cardBtn = document.getElementById("card-view-btn");
        if (!tableView || !cardView || !tableBtn || !cardBtn) return;

        const showTable = view === "table";
        tableView.classList.toggle("hidden", !showTable);
        cardView.classList.toggle("hidden", showTable);

        const activeClass = ["bg-[#FFF200]", "text-gray-900", "shadow-sm"];
        const inactiveClass = ["text-gray-500"];

        [tableBtn, cardBtn].forEach((btn) => {
            btn.classList.remove(...activeClass, ...inactiveClass);
        });

        if (showTable) {
            tableBtn.classList.add(...activeClass);
            cardBtn.classList.add(...inactiveClass);
        } else {
            cardBtn.classList.add(...activeClass);
            tableBtn.classList.add(...inactiveClass);
        }

        localStorage.setItem("prism-report-view", view);
    }

    document.addEventListener("DOMContentLoaded", function () {
        const savedView = localStorage.getItem("prism-report-view") || "table";

        setReportView(savedView);

        const toast = document.getElementById("undo-toast");

        if (toast) {
            toast.classList.remove("hidden");

            setTimeout(() => {
                toast.classList.add("hidden");
            }, 7000);
        }
    });

    /* ==========================================
    STATUS FIELD TOGGLER
    ========================================== */

    function toggleStatusFields(selectElement) {
        const form = selectElement.closest("form");

        if (!form) return;

        const notes = form.querySelector('[id^="notes-section"]');

        const image = form.querySelector('[id^="image-section"]');

        const replacementWarning = form.querySelector(
            '[id^="replacement-warning"]',
        );

        // Hide everything first
        if (notes) {
            notes.classList.add("hidden");
        }

        if (image) {
            image.classList.add("hidden");
        }

        if (replacementWarning) {
            replacementWarning.classList.add("hidden");
        }

        // RESOLVED
        if (selectElement.value === "Resolved") {
            if (notes) {
                notes.classList.remove("hidden");
            }

            if (image) {
                image.classList.remove("hidden");
            }
        }

        // REJECTED
        else if (selectElement.value === "Rejected") {
            if (notes) {
                notes.classList.remove("hidden");
            }
        }

        // FOR REPLACEMENT
        else if (selectElement.value === "For Replacement") {
            if (notes) {
                notes.classList.remove("hidden");
            }

            if (image) {
                image.classList.remove("hidden");
            }

            if (replacementWarning) {
                replacementWarning.classList.remove("hidden");
            }
        }
    }

    /* ==========================================
    DROPDOWN CHANGE EVENT
    ========================================== */

    document.addEventListener("change", function (e) {
        if (!e.target.matches('select[name="status"]')) return;

        toggleStatusFields(e.target);
    });

    /* ==========================================
    IMAGE PREVIEW
    ========================================== */

    document.addEventListener("change", function (e) {
        if (!e.target.matches('input[name="proof_image"]')) return;

        const preview = e.target.closest("div")?.querySelector("img");

        if (!preview) return;

        if (!e.target.files.length) {
            preview.classList.add("hidden");
            return;
        }

        preview.src = URL.createObjectURL(e.target.files[0]);

        preview.classList.remove("hidden");
    });

    function previewImage(input, reportId) {
        const container = document.getElementById(
            `preview-container-${reportId}`,
        );
        const img = document.getElementById(`preview-img-${reportId}`);
        const textContainer = document.getElementById(
            `upload-text-container-${reportId}`,
        );
        const iconContainer = document.getElementById(
            `upload-icon-${reportId}`,
        );

        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Convert file size into readable format (MB)
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

            // 1. Update the preview window image view src
            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                container.classList.remove("hidden");
            };
            reader.readAsDataURL(file);

            // 2. Change upload area styling to a "Success/Ready" state
            iconContainer.className =
                "p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 mb-3";
            iconContainer.innerHTML =
                '<i data-lucide="check-circle-2" class="w-6 h-6"></i>';

            // 3. Swap text strings to display selected file info
            textContainer.innerHTML = `
                <p class="text-sm font-bold text-slate-800 break-all px-4">
                    Selected: <span class="text-blue-600 font-mono text-xs">${file.name}</span>
                </p>
                <div class="mt-1.5 flex items-center justify-center gap-2">
                    <span class="text-[10px] bg-slate-100 px-2 py-0.5 rounded-md text-slate-500 font-semibold">${fileSizeMB} MB</span>
                    <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-bold uppercase tracking-wider">Click to Replace</span>
                </div>
            `;

            // Force refresh Lucide vector icons if available
            if (typeof lucide !== "undefined") lucide.createIcons();
        }
    }

    function removeUploadedImage(reportId) {
        const fileInput = document.getElementById(`proof_image_${reportId}`);
        const container = document.getElementById(
            `preview-container-${reportId}`,
        );
        const img = document.getElementById(`preview-img-${reportId}`);
        const textContainer = document.getElementById(
            `upload-text-container-${reportId}`,
        );
        const iconContainer = document.getElementById(
            `upload-icon-${reportId}`,
        );

        // Clear value logic states
        fileInput.value = "";
        img.src = "";
        container.classList.add("hidden");

        // Reset dropzone look back to standard empty default
        iconContainer.className =
            "p-3 rounded-xl bg-slate-50 border border-slate-100 text-blue-600 mb-3 group-hover:scale-110 group-hover:bg-yellow-50 group-hover:border-yellow-100 transition duration-200";
        iconContainer.innerHTML =
            '<i data-lucide="image-plus" class="w-6 h-6"></i>';

        textContainer.innerHTML = `
            <p class="text-sm font-bold text-slate-700">
                Click to upload photo <span class="text-xs font-medium text-slate-400 block sm:inline sm:ml-1">(Optional)</span>
            </p>
            <p class="text-[11px] font-medium text-slate-400 mt-1 uppercase tracking-wider">
                PNG, JPG, JPEG, WEBP up to 10MB
            </p>
        `;

        if (typeof lucide !== "undefined") lucide.createIcons();
    }

    function openLightbox(targetImgId) {
        const targetImg = document.getElementById(targetImgId);
        const lightbox = document.getElementById("global-image-lightbox");
        const lightboxImg = document.getElementById("lightbox-target-img");
        const filenameLabel = document.getElementById("lightbox-filename");
        const fileInput = document.getElementById(
            targetImgId.replace("preview-img-", "proof_image_"),
        );

        if (targetImg && targetImg.src) {
            // 1. Inject preview file src string target link directly
            lightboxImg.src = targetImg.src;

            // 2. Set dynamic title header label text if a native file object exists
            if (fileInput && fileInput.files && fileInput.files[0]) {
                filenameLabel.innerText = fileInput.files[0].name;
            } else {
                filenameLabel.innerText = "Proof Documentation Image";
            }

            // 3. Make modal frame view visible
            lightbox.classList.remove("hidden");
            document.body.style.overflow = "hidden"; // Lock background body scrolling actions

            if (typeof lucide !== "undefined") lucide.createIcons();
        }
    }

    function closeLightbox() {
        const lightbox = document.getElementById("global-image-lightbox");
        const lightboxImg = document.getElementById("lightbox-target-img");

        lightbox.classList.add("hidden");
        lightboxImg.src = ""; // Flush memory out buffer trace links
        document.body.style.overflow = ""; // Unlock baseline window frame scroll wheels
    }

    // Option shortcut: Hit 'Escape' window layout keys to quickly exit lightbox screen viewport safely
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeLightbox();
        }
    });
</script>
