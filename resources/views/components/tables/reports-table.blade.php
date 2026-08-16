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
    class="overflow-visible rounded-2xl border border-gray-200 bg-white shadow-sm"
>

    <!-- FILTER BAR -->
    <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <form
                method="GET"
                class="flex flex-1 flex-col items-stretch gap-2.5 lg:flex-row lg:items-center"
            >
                <input
                    type="hidden"
                    name="archive"
                    value="{{ request('archive', 0) }}"
                />

                <!-- SEARCH -->
                <div class="relative min-w-0 flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search report ID, equipment, room, reporter"
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-700 placeholder:text-slate-400 outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-900/5"
                    />
                </div>

                <!-- STATUS -->
                <div class="relative shrink-0">
                <select
                    name="status"
                    class="h-9 w-full cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white py-0 pl-3.5 pr-9 text-sm text-slate-700 outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-900/5 lg:w-[160px]"
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
                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </div>

                <!-- DIVIDER -->
                @if (!request()->is("maintenance/reports/urgent"))
                    <div class="hidden h-6 w-px bg-slate-200 lg:block"></div>
                @endif

                <!-- PRIORITIES BUTTON -->
                @if (!request()->is("maintenance/reports/urgent"))
                    <div class="relative shrink-0">
                    <select
                        name="urgency"
                        class="h-9 w-full cursor-pointer appearance-none rounded-lg border border-slate-200 bg-white py-0 pl-3.5 pr-9 text-sm text-slate-700 outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-900/5 lg:w-[160px]"
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
                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                    </div>

                @endif

                <!-- SEARCH BUTTON -->
                <button
                    type="submit"
                    
                    aria-label="Search"
                    class="group relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-slate-600 ring-1 ring-slate-200/80 transition hover:bg-neutral-200 hover:text-slate-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" viewBox="0 0 24 24">
                        <path d="M4 8h2" />
                        <circle cx="9" cy="8" r="2.2" fill="currentColor" stroke="none" />
                        <path d="M12 8h8" />
                        <path d="M4 16h8" />
                        <circle cx="15" cy="16" r="2.2" fill="currentColor" stroke="none" />
                        <path d="M18 16h2" />
                    </svg>
                    <span class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white opacity-0 shadow-sm transition group-hover:opacity-100">
                        Search
                    </span>
                </button>

                <!-- DIVIDER -->
                <div class="hidden h-6 w-px bg-slate-200 lg:block"></div>

                <div
                    class="flex shrink-0 items-center rounded-lg bg-slate-100 p-1"
                >
                    <a
                        href="{{ request()->url() }}?archive=0"
                        class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold transition
                    {{ !request('archive')
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-400 hover:text-slate-600'
                    }}"
                    >
                        <i data-lucide="folder-open" class="h-3.5 w-3.5"></i>
                        Active
                    </a>

                    <a
                        href="{{ request()->url() }}?archive=1"
                        class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold transition
                    {{ request('archive')
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-400 hover:text-slate-600'
                    }}"
                    >
                        <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                        Archive
                    </a>
                </div>
            </form>

            <!-- DIVIDER -->
            <div class="hidden h-6 w-px bg-slate-200 lg:block"></div>

            <!-- VIEW TOGGLE -->
            <div
                class="flex shrink-0 items-center rounded-lg bg-slate-100 p-1"
            >
                <button
                    type="button"
                    id="card-view-btn"
                    onclick="setReportView('card')"
                    class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold text-slate-400 transition hover:text-slate-600"
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
                    class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold text-slate-400 transition hover:text-slate-600"
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
        @include(
            'components.tables.partials.report-card',
            ['report' => $report]
        )
            

        @empty

            <div
                class="col-span-full flex min-h-[320px]
                    items-center justify-center px-6 py-16"
            >

                {{-- ===================================================== --}}
                {{-- EMPTY STATE --}}
                {{-- CARD VIEW --}}
                {{-- ===================================================== --}}

                <div class="flex max-w-sm flex-col items-center text-center">

                    <div
                        class="flex h-12 w-12 items-center justify-center
                            rounded-2xl border border-slate-200
                            bg-slate-50 text-slate-400"
                    >
                        <i
                            data-lucide="{{
                                request()->filled('search')
                                || request()->filled('status')
                                || request()->filled('urgency')
                                    ? 'search-x'
                                    : 'clipboard-list'
                            }}"
                            class="h-5 w-5"
                        ></i>
                    </div>


                    <h3 class="mt-4 text-sm font-semibold text-slate-800">

                        {{
                            request()->filled('search')
                            || request()->filled('status')
                            || request()->filled('urgency')

                                ? 'No matching reports'

                                : (
                                    request('archive')
                                        ? 'Archive is empty'
                                        : 'No reports yet'
                                )
                        }}

                    </h3>


                    <p class="mt-1.5 max-w-xs text-xs leading-5 text-slate-400">

                        {{
                            request()->filled('search')
                            || request()->filled('status')
                            || request()->filled('urgency')

                                ? 'No maintenance reports match your current search or filters.'

                                : (
                                    request('archive')
                                        ? 'Archived maintenance reports will appear here.'
                                        : 'Submitted maintenance reports will appear here.'
                                )
                        }}

                    </p>


                    @if (
                        request()->filled('search')
                        || request()->filled('status')
                        || request()->filled('urgency')
                    )

                        <a
                            href="{{ request()->url() }}?archive={{ request('archive', 0) }}"

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

            </div>

        @endforelse
    </div>

    <!-- TABLE VIEW -->
    <div id="table-view" class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black"
                    >
                        Report ID
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black"
                    >
                        Reporter
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black"
                    >
                        Room & Equipment
                    </th>
                    <!--<th
                        class="bg-gray-50 px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400"
                    >
                        Equipment
                    </th>-->
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black"
                    >
                        Urgency
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black"
                    >
                        Status
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black"
                    >
                        Date Submitted
                    </th>
                    <th
                        class="bg-gray-50 px-5 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black"
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
                                ? "bg-rose-50 text-rose-700"
                                : "bg-slate-100 text-slate-600";

                        $statusMap = [
                            "Pending" => "bg-orange-50 text-orange-700",
                            "Processing" => "bg-sky-50 text-sky-700",
                            "Resolved" => "bg-emerald-50 text-emerald-700",
                            "Rejected" => "bg-rose-50 text-rose-700",
                            "For Replacement" => "bg-orange-50 text-orange-700",
                        ];
                        $currentStatus = $report->report_current_status;
                        $statusPill = $statusMap[$currentStatus] ?? "bg-slate-100 text-slate-600";
                        $canUpdate = in_array($currentStatus, ["Pending", "Processing"]);
                        $rowBg = $loop->even ? "bg-gray-50/40" : "";
                    @endphp
                    <tr
                        class="border-b border-gray-100 hover:bg-yellow-50/30 transition {{ $rowBg }}"
                    >
                        <td class="px-5 py-4 text-sm font-semibold text-gray-500">
                            No.{{ $report->report_id }}
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
                                <div class="flex items-center gap-1.5">
                                    <p class="font-medium text-slate-800">
                                        {{ $report->equipment_name }}
                                    </p>
                                    @if ((int) ($report->report_related_count ?? 1) > 1)
                                        <button
                                            type="button"
                                            onclick="openReportHistory({{ $report->report_id }})"
                                            data-tooltip="{{ (int) $report->report_related_count }} reports on this equipment while it is still open"
                                            class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-none text-white transition hover:bg-rose-700"
                                        >
                                            {{ (int) $report->report_related_count }}
                                        </button>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-400">
                                    {{ $report->room_name }}
                                </p>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $urgencyPill }}">
                                {{ $report->report_urgency_level }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $statusPill }}">
                                {{ $currentStatus }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-[12px] text-gray-600">
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
                                    data-tooltip="View Report"
                                    onclick="openReportModal('view-modal-{{ $report->report_id }}'); switchReportViewTab({{ $report->report_id }}, 'details')"
                                    class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg  bg-slate-100 px-3 text-xs  text-slate-800 transition shadow-sm hover:bg-slate-200 hover:text-gray-600"
                                >
                                    <i
                                        data-lucide="eye"
                                        class="h-3.5 w-3.5"
                                    ></i>
                                    
                                </button>
                                @if ($report->report_equipment_id)
                                    <button
                                        type="button"
                                        data-tooltip="Previous reports for this equipment"
                                        onclick="openReportHistory({{ $report->report_id }})"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition hover:bg-slate-200"
                                    >
                                        <i data-lucide="history" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endif
                                @if ($canUpdate)
                                    <button
                                        type="button"
                                        data-tooltip="Update Report"
                                        onclick="openReportModal('update-modal-{{ $report->report_id }}')"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#FFF200] text-black transition hover:bg-[#E6E600]"
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
                                            data-tooltip="Archive Report"
                                            class="inline-flex h-9 items-center gap-2 rounded-lg bg-[rgba(0,55,199,0.85)] px-3 text-xs  text-white shadow-sm transition-all hover:bg-[rgba(0,44,155,0.85)] active:scale-95"
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

                                        <button data-tooltip="Restore Report" class="flex items-center gap-1.5 h-9 rounded-lg bg-emerald-100 px-3 text-xs text-emerald-700 transition hover:bg-emerald-200">
                                        <i data-lucide="archive-restore" class="h-3.5 w-3.5"></i>
                                        
                                        </button>
                                    </form>

                                @endif
                            </div>
                        </td>
                    </tr>
                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="px-6 py-16 text-center"
                        >

                            {{-- ===================================================== --}}
                            {{-- EMPTY STATE --}}
                            {{-- TABLE VIEW --}}
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
                                            || request()->filled('urgency')
                                                ? 'search-x'
                                                : 'clipboard-list'
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
                                        || request()->filled('urgency')

                                            ? 'No matching reports'

                                            : (
                                                request('archive')
                                                    ? 'Archive is empty'
                                                    : 'No reports yet'
                                            )
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
                                        || request()->filled('urgency')

                                            ? 'No maintenance reports match your current search or filters.'

                                            : (
                                                request('archive')
                                                    ? 'Archived maintenance reports will appear here.'
                                                    : 'Submitted maintenance reports will appear here.'
                                            )
                                    }}

                                </p>


                                {{-- ================================================= --}}
                                {{-- CLEAR FILTERS --}}
                                {{-- ONLY SHOW WHEN SEARCHING OR FILTERING --}}
                                {{-- ================================================= --}}

                                @if (
                                    request()->filled('search')
                                    || request()->filled('status')
                                    || request()->filled('urgency')
                                )

                                    <a
                                        href="{{ request()->url() }}?archive={{ request('archive', 0) }}"

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
</div>
<!-- VIEW MODALS -->
@foreach ($reports as $report)
    @php
        $statusMap = [
            "Pending" => "bg-amber-50 text-amber-700",
            "Processing" => "bg-sky-50 text-sky-700",
            "Resolved" => "bg-emerald-50 text-emerald-700",
            "Rejected" => "bg-rose-50 text-rose-700",
            "For Replacement" => "bg-orange-50 text-orange-700",
        ];
        $statusPill = $statusMap[$report->report_current_status] ?? "bg-slate-100 text-slate-600";
        $urgencyPill =
            $report->report_urgency_level == "Urgent"
                ? "bg-rose-50 text-rose-700"
                : "bg-slate-100 text-slate-500";
        $historyCount = collect($report->equipment_report_history ?? [])->count();
        $equipmentLabel = $report->equipment_name ?? ($report->report_unlisted_equipment_name ?? "Unlisted");
        $reporterInitials = collect(explode(" ", trim($report->reporter_full_name ?? "R")))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode("");
        $assignedName = $report->assigned_personnel_name ?? $report->assigned_purchaser_name ?? "Unassigned";
    @endphp

    <div
        id="view-modal-{{ $report->report_id }}"
        class="fixed inset-0 z-50 hidden overflow-hidden"
        onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
    >
        <div class="flex h-full justify-end bg-[#0b1220]/70">
            <div
                class="flex h-full w-full max-w-lg flex-col bg-white shadow-2xl sm:rounded-l-2xl"
                onclick="event.stopPropagation()"
            >
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-slate-800">Ticket details</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Review {{ $equipmentLabel }} in {{ $report->room_name ?? "an unassigned room" }}.
                        </p>
                    </div>
                    <button
                        type="button"
                        onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="flex items-start gap-4 px-6 pt-6">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700">
                        {{ $reporterInitials ?: "R" }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-base font-semibold text-slate-900">
                                {{ $report->reporter_full_name ?? "Unknown reporter" }}
                            </p>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $statusPill }}">
                                {{ $report->report_current_status }}
                            </span>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $urgencyPill }}">
                                {{ $report->report_urgency_level }}
                            </span>
                        </div>
                        <div class="mt-2 space-y-1.5 text-sm text-slate-500">
                            <p class="flex items-center gap-2">
                                <i data-lucide="wrench" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                <span class="truncate">{{ $equipmentLabel }}</span>
                            </p>
                            <p class="flex items-center gap-2">
                                <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                <span class="truncate">{{ $report->room_name ?? "No assigned room" }}</span>
                            </p>
                            <p class="flex items-center gap-2">
                                <i data-lucide="hash" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                <span>{{ $report->reporter_employee_id ?? "—" }} · Ticket #{{ $report->report_id }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-6 border-b border-slate-200 px-6">
                    <button
                        type="button"
                        id="report-tab-details-{{ $report->report_id }}"
                        onclick="switchReportViewTab({{ $report->report_id }}, 'details')"
                        class="border-b-2 border-[#0037C7] px-1 pb-3 text-sm font-medium text-slate-900"
                    >
                        Overview
                    </button>
                    <button
                        type="button"
                        id="report-tab-history-{{ $report->report_id }}"
                        onclick="switchReportViewTab({{ $report->report_id }}, 'history')"
                        class="border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-400 hover:text-slate-600"
                    >
                        Timeline{{ $historyCount ? " · ".$historyCount : "" }}
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div id="report-panel-details-{{ $report->report_id }}">
                        <p class="mb-3 text-sm font-medium text-slate-600">Report information</p>
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                <span class="text-sm text-slate-500">Issue</span>
                                <span class="text-right text-sm font-medium text-slate-800">{{ $report->report_suggested_issue ?? "None given" }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                <span class="text-sm text-slate-500">Assigned</span>
                                <span class="text-right text-sm font-medium text-slate-800">{{ $assignedName }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                <span class="text-sm text-slate-500">Filed</span>
                                <span class="text-right text-sm font-medium text-slate-800">
                                    {{ \Carbon\Carbon::parse($report->report_submitted_at)->format("M d, Y h:i A") }}
                                </span>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-sm text-slate-500">What happened</p>
                                <p class="mt-1.5 whitespace-pre-wrap text-sm leading-6 text-slate-800">
                                    {{ $report->report_problem_description ?? "No description provided." }}
                                </p>
                            </div>
                        </div>

                        @if ($report->report_uploaded_image)
                            <button
                                type="button"
                                onclick="window.open('{{ asset('storage/'.$report->report_uploaded_image) }}', '_blank')"
                                class="mt-4 block w-full overflow-hidden rounded-xl border border-slate-200"
                            >
                                <img
                                    src="{{ asset("storage/".$report->report_uploaded_image) }}"
                                    alt="Report photo"
                                    class="max-h-48 w-full object-cover"
                                />
                            </button>
                        @endif

                        @if ($report->report_current_status === "Resolved")
                            <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                                <p class="text-sm font-medium text-emerald-800">Resolution</p>
                                <p class="mt-1 text-sm leading-6 text-emerald-900/80">{{ $report->report_resolution_notes ?: "No notes." }}</p>
                            </div>
                        @endif
                        @if ($report->report_current_status === "Rejected")
                            <div class="mt-4 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3">
                                <p class="text-sm font-medium text-rose-800">Rejected</p>
                                <p class="mt-1 text-sm leading-6 text-rose-900/80">{{ $report->report_rejection_notes ?: "No notes." }}</p>
                            </div>
                        @endif
                        @if ($report->report_current_status === "For Replacement")
                            <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
                                <p class="text-sm font-medium text-amber-800">Replacement</p>
                                <p class="mt-1 text-sm leading-6 text-amber-900/80">{{ $report->report_replacement_notes ?: "No notes." }}</p>
                            </div>
                        @endif
                    </div>

                    <div id="report-panel-history-{{ $report->report_id }}" class="hidden">
                        <p class="mb-3 text-sm font-medium text-slate-600">Equipment timeline</p>
                        @include("components.tables.partials.equipment-report-history", ["report" => $report])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- UPDATE MODALS -->
@foreach ($reports as $report)
    @php
        $equipmentLabel = $report->equipment_name ?? ($report->report_unlisted_equipment_name ?? "Equipment Report");
    @endphp
    <div id="update-modal-{{ $report->report_id }}" class="fixed inset-0 z-50 hidden overflow-hidden">
        <div class="flex min-h-screen items-center justify-center bg-[#0b1220]/70 p-3 sm:p-6">
            <div
                class="flex max-h-[86vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                onclick="event.stopPropagation()"
            >
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <p class="text-xs text-slate-400">#{{ $report->report_id }} · {{ $report->report_current_status }}</p>
                        <h2 class="mt-1 truncate text-xl font-semibold tracking-tight text-slate-900">
                            {{ $equipmentLabel }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        onclick="closeReportModal('update-modal-{{ $report->report_id }}')"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Close modal"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form
                    action="/maintenance/reports/update-status/{{ $report->report_id }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="flex min-h-0 flex-1 flex-col"
                >
                    @csrf

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        <div>
                            <label for="status-{{ $report->report_id }}" class="mb-1.5 block text-sm text-slate-600">
                                Status
                            </label>
                            <select
                                id="status-{{ $report->report_id }}"
                                name="status"
                                required
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                            >
                                @if ($report->report_current_status == "Pending")
                                    <option value="" selected disabled>Select status</option>
                                    <option value="Processing">Processing</option>
                                    <option value="Rejected">Rejected</option>
                                @elseif ($report->report_current_status == "Processing")
                                    <option value="" selected disabled>Select status</option>
                                    <option value="Resolved">Resolved</option>
                                    <option value="For Replacement">For Replacement</option>
                                @else
                                    <option value="{{ $report->report_current_status }}" selected disabled>
                                        {{ $report->report_current_status }}
                                    </option>
                                @endif
                            </select>
                        </div>

                        <div id="notes-section-{{ $report->report_id }}" class="hidden">
                            <label for="remarks-{{ $report->report_id }}" class="mb-1.5 block text-sm text-slate-600">
                                Remarks <span class="text-slate-400">(optional)</span>
                            </label>
                            <textarea
                                id="remarks-{{ $report->report_id }}"
                                name="remarks"
                                rows="4"
                                placeholder="Findings, actions taken, or justification"
                                class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400"
                            ></textarea>
                        </div>

                        <div id="image-section-{{ $report->report_id }}" class="hidden">
                            <label class="mb-1.5 block text-sm text-slate-600">
                                Proof image <span class="text-slate-400">(optional)</span>
                            </label>
                            <label
                                for="proof_image_{{ $report->report_id }}"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-3 transition hover:bg-slate-50"
                            >
                                <div
                                    id="upload-icon-{{ $report->report_id }}"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-500"
                                >
                                    <i data-lucide="image-plus" class="h-4 w-4"></i>
                                </div>
                                <div id="upload-text-container-{{ $report->report_id }}" class="min-w-0 flex-1">
                                    <p class="text-sm text-slate-800">Upload image</p>
                                    <p class="text-xs text-slate-400">PNG, JPG, JPEG or WEBP · 10MB max</p>
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
                            <div
                                id="preview-container-{{ $report->report_id }}"
                                class="relative mt-3 hidden overflow-hidden rounded-lg border border-slate-200"
                            >
                                <img
                                    id="preview-img-{{ $report->report_id }}"
                                    class="max-h-48 w-full object-contain"
                                    alt="Proof preview"
                                />
                                <div class="absolute right-2 top-2 flex gap-1">
                                    <button
                                        type="button"
                                        onclick="openLightbox('preview-img-{{ $report->report_id }}')"
                                        data-tooltip="View image"
                                        class="flex h-8 w-8 items-center justify-center rounded-md bg-white text-slate-600 shadow-sm"
                                    >
                                        <i data-lucide="zoom-in" class="h-4 w-4"></i>
                                    </button>
                                    <button
                                        type="button"
                                        onclick="removeUploadedImage('{{ $report->report_id }}')"
                                        data-tooltip="Remove image"
                                        class="flex h-8 w-8 items-center justify-center rounded-md bg-white text-slate-600 shadow-sm hover:text-rose-600"
                                    >
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p
                            id="replacement-warning-{{ $report->report_id }}"
                            class="hidden text-sm text-amber-700"
                        >
                            This will create a procurement request for the Purchaser Department.
                        </p>
                    </div>

                    <div class="flex shrink-0 justify-end gap-2 px-6 pb-6">
                        <button
                            type="button"
                            onclick="closeReportModal('update-modal-{{ $report->report_id }}')"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#0037C7] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#002C9B]"
                        >
                            Update
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

    function switchReportViewTab(reportId, tab) {
        const detailsPanel = document.getElementById(`report-panel-details-${reportId}`);
        const historyPanel = document.getElementById(`report-panel-history-${reportId}`);
        const detailsTab = document.getElementById(`report-tab-details-${reportId}`);
        const historyTab = document.getElementById(`report-tab-history-${reportId}`);

        if (!detailsPanel || !historyPanel || !detailsTab || !historyTab) {
            return;
        }

        const showHistory = tab === "history";
        detailsPanel.classList.toggle("hidden", showHistory);
        historyPanel.classList.toggle("hidden", !showHistory);

        const historyCountBox = document.getElementById(`report-rail-history-count-${reportId}`);
        if (historyCountBox) {
            historyCountBox.classList.toggle("hidden", !showHistory);
        }

        const activeTab =
            "border-b-2 border-[#0037C7] px-1 pb-3 text-sm font-medium text-slate-900";
        const idleTab =
            "border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-400 hover:text-slate-600";

        detailsTab.className = showHistory ? idleTab : activeTab;
        historyTab.className = showHistory ? activeTab : idleTab;
    }

    function openReportHistory(reportId) {
        openReportModal(`view-modal-${reportId}`);
        switchReportViewTab(reportId, "history");
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

        const activeClass = ["bg-white", "text-slate-900", "shadow-sm"];
        const inactiveClass = ["text-slate-400"];

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
