@extends('layouts.maintenance-layout')

@section('title', 'Rooms')

@section('content')
@php
    $nextDir = fn ($column) => ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
    $sortLink = function ($column, $label) use ($nextDir, $sort, $dir) {
        $arrow = $sort === $column ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
        $href = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir($column)]);
        return '<a href="'.e($href).'" class="hover:text-slate-800">'.e($label.$arrow).'</a>';
    };
    $eqField = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $eqLabel = 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';

    $roomTrendCounts = collect($roomMonthlyTrend)->pluck('count');
    $roomTrendMax = max(1, $roomTrendCounts->max() ?? 0);
    $roomTrendTotalPoints = max(1, $roomTrendCounts->count() - 1);

    $roomTrendPoints = collect($roomMonthlyTrend)
        ->values()
        ->map(function ($item, $index) use ($roomTrendMax, $roomTrendTotalPoints) {
            $x = ($index / $roomTrendTotalPoints) * 300;
            $y = 90 - (($item['count'] / $roomTrendMax) * 75);

            return round($x, 2).','.round($y, 2);
        })
        ->implode(' ');
@endphp

    @if (!($historyRoom ?? null))
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end">
        <form method="POST" action="{{ route('admin.rooms.merge') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="git-merge" class="h-4 w-4"></i>
                Merge duplicates
            </button>
        </form>

        <button
            type="button"
            onclick="openRoomTypesModal()"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
        >
            <i data-lucide="tags" class="h-4 w-4"></i>
            Room types
        </button>

        <button
            type="button"
            onclick="openAddRoomModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Room
        </button>
    </div>
    @endif

    @if (($duplicateRoomGroups ?? 0) > 0)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $duplicateRoomGroups }} duplicate {{ \Illuminate\Support\Str::plural('room name', $duplicateRoomGroups) }} found.
            Use Merge duplicates to keep one room and move its equipment.
        </div>
    @endif

    @if (!($historyRoom ?? null))
    {{-- ROOMS DASHBOARD --}}
    @php
        $roomMonthlyHint = $roomMonthlyPercentage === null
            ? 'New activity vs last month'
            : (($roomMonthlyPercentage > 0 ? '+' : '') . number_format($roomMonthlyPercentage, 2) . '% vs last month');
    @endphp
    <div class="mb-6 mt-6">
    </div>

    @endif

    {{-- ===================================================== --}}
    {{-- ROOMS TABLE / HISTORY --}}
    {{-- ===================================================== --}}

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">

        @if ($historyRoom ?? null)

            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <a
                        href="{{ route('admin.rooms.index') }}"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                        title="Back to rooms"
                        aria-label="Back to rooms"
                    >
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    </a>

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <i data-lucide="history" class="h-4 w-4"></i>
                    </div>

                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-semibold text-slate-900">
                            {{ $historyRoom->room_name }}
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ $historyRoom->building_name ?: 'Building' }}
                            <span class="mx-1">·</span>
                            {{ $historyRoom->floor_level ?: 'Floor' }}
                            <span class="mx-1">·</span>
                            {{ $roomTypes[$historyRoom->room_type] ?? ($historyRoom->room_type ?: 'Room') }}
                        </p>
                    </div>
                </div>

                <form method="GET" action="{{ url()->current() }}" data-auto-filter
                    class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap lg:w-auto lg:items-center lg:justify-end"
                >
                    <input type="hidden" name="history" value="{{ $historyRoom->room_id }}" />
                    @if (request()->filled('history_period') && ! request()->filled('history_month'))
                        <input type="hidden" name="history_period" value="{{ request('history_period') }}" />
                    @endif

                    <div class="relative">
                        <i data-lucide="calendar" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="month"
                            name="history_month"
                            value="{{ request('history_month') }}"
                            title="Pick any month and year"
                            class="h-9 min-w-[170px] rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-xs font-medium text-slate-600 outline-none transition focus:border-slate-400"
                            onchange="const p=this.form.querySelector('input[name=history_period]'); if (p) p.remove();"
                        />
                    </div>

                    <div class="relative w-full sm:w-[200px]">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="search"
                            name="history_search"
                            value="{{ request('history_search') }}"
                            placeholder="Search reports…"
                            class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-xs font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400"
                        />
                    </div>

                    <div class="relative">
                        <select
                            name="history_status"
                            class="h-9 min-w-[160px] appearance-none rounded-lg border border-slate-200 bg-white pl-3 pr-9 text-xs font-medium text-slate-600 outline-none transition focus:border-slate-400"
                        >
                            <option value="">All statuses</option>
                            @foreach (['Pending', 'Processing', 'Resolved', 'For Replacement', 'Rejected'] as $historyStatus)
                                <option value="{{ $historyStatus }}" @selected(request('history_status') === $historyStatus)>
                                    {{ $historyStatus }}
                                </option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Apply
                    </button>

                    @if (
                        request()->filled('history_search')
                        || request()->filled('history_status')
                        || request()->filled('history_period')
                        || request()->filled('history_month')
                    )
                        <a
                            href="{{ route('admin.rooms.index', ['history' => $historyRoom->room_id]) }}"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                            title="Clear filters"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </a>
                    @endif
                </form>
            </div>

            @if (request()->filled('history_month'))
                <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-2.5 text-xs text-slate-500">
                    Showing history for
                    <span class="font-semibold text-slate-700">
                        {{ \Carbon\Carbon::createFromFormat('Y-m', request('history_month'))->format('F Y') }}
                    </span>
                </div>
            @endif

            <div class="overflow-x-auto border-b border-slate-200">
                <table class="w-full min-w-[1100px] text-left">
                    <thead class="border-b border-slate-200 bg-slate-50/70">
                        <tr class="text-[12px] font-semibold uppercase tracking-[0.08em] text-black">
                            <th class="px-5 py-3">Report</th>
                            <th class="px-5 py-3">Issue</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Reporter</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Submitted</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse (($reportHistory ?? collect()) as $report)
                            @php
                                $historyEquipmentName = $report->equipment_name
                                    ?? $report->report_unlisted_equipment_name
                                    ?? 'No equipment';
                                $status = $report->report_current_status ?: 'Pending';
                                $statusClass = match ($status) {
                                    'Resolved' => 'bg-emerald-50 text-emerald-700',
                                    'For Replacement' => 'bg-amber-50 text-amber-700',
                                    'Processing' => 'bg-sky-50 text-sky-700',
                                    'Rejected' => 'bg-slate-100 text-slate-600',
                                    default => 'bg-rose-50 text-rose-700',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-5 py-4 text-sm font-semibold text-slate-800">
                                    <a
                                        href="{{ \App\Support\ReportDetailsReturn::detailsUrl((int) $report->report_id) }}"
                                        class="text-[#0025cc] hover:underline"
                                    >
                                        {{ \App\Support\ReportGrouping::ticketCode($report) }}
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <p class="max-w-[260px] truncate font-semibold text-slate-800">
                                        {{ $report->issue_display ?: ($report->report_suggested_issue ?: 'Unspecified issue') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $historyEquipmentName }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $report->reporter_full_name ?: ($report->report_reporter_employee_id ?: '—') }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $report->report_submitted_at ? \Carbon\Carbon::parse($report->report_submitted_at)->format('M j, Y g:i A') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end">
                                        <button
                                            type="button"
                                            data-tooltip="View Report"
                                            onclick="openReportModal('view-modal-{{ $report->report_id }}'); switchReportViewTab({{ $report->report_id }}, 'details')"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-800 transition hover:bg-slate-200"
                                        >
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center text-sm text-slate-500">
                                    No reports found for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reportHistory && method_exists($reportHistory, 'hasPages') && $reportHistory->hasPages())
                <div class="border-b border-slate-200 px-5 py-4">
                    {{ $reportHistory->links() }}
                </div>
            @endif

            @include('admin.rooms.partials.lifecycle-timeline', [
                'lifecycleEvents' => $roomLifecycleEvents ?? collect(),
                'lifecycleSummary' => $roomLifecycleSummary ?? [],
                'lifecycleStageCounts' => $roomLifecycleStageCounts ?? [],
                'historyRoom' => $historyRoom,
            ])

            @include('components.tables.partials.report-view-drawers', [
                'reports' => $reportHistory,
                'ticketLinkBase' => null,
            ])

        @else

        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <i data-lucide="door-open" class="h-4 w-4"></i>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Room Directory
                    </h2>
                </div>
            </div>

            <div class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-500">
                <i data-lucide="layout-grid" class="h-3.5 w-3.5"></i>
                {{ $rooms->total() }} total
            </div>
        </div>

        <form method="GET" action="{{ url()->current() }}" data-auto-filter class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                <div class="relative min-w-0 flex-1">
                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>

                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search room, building, floor, or department..."
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    />
                </div>

                <div class="relative">
                    <select
                        name="building"
                        class="h-9 min-w-[175px] appearance-none rounded-lg border border-slate-200 bg-white pl-3 pr-9 text-sm text-slate-600 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    >
                        <option value="">All Buildings</option>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->building_id }}" @selected(request('building') == $building->building_id)>
                                {{ $building->building_name }}
                            </option>
                        @endforeach
                    </select>

                    <i
                        data-lucide="chevron-down"
                        class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>
                </div>

                <input type="hidden" name="sort" value="{{ $sort }}" />
                <input type="hidden" name="dir" value="{{ $dir }}" />

                <button
                    type="submit"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Apply
                </button>

                @if (request()->filled('search') || request()->filled('building'))
                    <a
                        href="{{ url()->current() }}"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Clear
                    </a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-slate-200 bg-slate-50/70">
                    <tr class="text-[12px] font-semibold uppercase tracking-[0.08em] text-black">
                        <th class="px-5 py-3">{!! $sortLink('room_name', 'Room') !!}</th>
                        <th class="px-5 py-3">{!! $sortLink('type', 'Room Type') !!}</th>
                        <th class="px-5 py-3">Departments</th>
                        <th class="px-5 py-3">{!! $sortLink('floor', 'Floor') !!}</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">{!! $sortLink('equipment', 'Equipment') !!}</th>
                        <th class="w-[220px] px-5 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($rooms as $room)
                        @php
                            $status = \App\Support\RoomStatuses::normalize($room->room_status ?? null);
                            $statusClass = match ($status) {
                                \App\Support\RoomStatuses::NORMAL => 'bg-emerald-50 text-emerald-700',
                                \App\Support\RoomStatuses::UNDER_MAINTENANCE => 'bg-amber-50 text-amber-700',
                                \App\Support\RoomStatuses::CRITICAL => 'bg-rose-50 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                            $statusDotClass = match ($status) {
                                \App\Support\RoomStatuses::NORMAL => 'bg-emerald-500',
                                \App\Support\RoomStatuses::UNDER_MAINTENANCE => 'bg-amber-500',
                                \App\Support\RoomStatuses::CRITICAL => 'bg-rose-500',
                                default => 'bg-slate-400',
                            };
                        @endphp

                        <tr class="group transition-colors hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400">
                                        <i data-lucide="door-open" class="h-4 w-4"></i>
                                    </div>

                                    <div class="min-w-0">
                                        <button
                                            type="button"
                                            class="max-w-[220px] truncate text-left text-sm font-semibold text-slate-800 transition hover:text-[#0025cc]"
                                            onclick="openRoomPeek({{ $room->room_id }}, {{ json_encode($room->room_name) }})"
                                        >
                                            {{ $room->room_name }}
                                        </button>

                                        @if ($room->building_name)
                                            <p class="mt-0.5 text-[11px] text-slate-400">
                                                {{ $room->building_name }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $roomTypes[$room->room_type] ?? ($room->room_type ?: '—') }}
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-600">
                                @php
                                    $roomDepts = collect($room->departments ?? []);
                                    $visibleDepts = $roomDepts->take(3);
                                    $extraDeptCount = max(0, $roomDepts->count() - 3);
                                @endphp
                                @if ($roomDepts->isEmpty())
                                    —
                                @else
                                    <div class="flex max-w-[240px] flex-wrap items-center gap-1">
                                        @foreach ($visibleDepts as $dept)
                                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ $dept['name'] ?? $dept }}</span>
                                        @endforeach
                                        @if ($extraDeptCount > 0)
                                            <span class="room-dept-more relative inline-flex shrink-0" data-room-dept-more>
                                                <button
                                                    type="button"
                                                    data-room-dept-more-btn
                                                    aria-expanded="false"
                                                    onclick="toggleRoomDeptMore(this, event)"
                                                    class="inline-flex items-center rounded-md bg-[#0037C7]/10 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-[#0037C7] ring-1 ring-[#0037C7]/20 transition hover:bg-[#0037C7] hover:text-white"
                                                >
                                                    +{{ $extraDeptCount }}
                                                </button>
                                                <span class="room-dept-more-panel" role="tooltip">
                                                    <span class="block px-3 pb-1.5 pt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                                        All departments · {{ $roomDepts->count() }}
                                                    </span>
                                                    @foreach ($roomDepts as $dept)
                                                        <span class="block truncate px-3 py-1.5 text-xs font-medium text-slate-700">
                                                            {{ $dept['name'] ?? $dept }}
                                                        </span>
                                                    @endforeach
                                                </span>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $room->floor_level ?: '—' }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ $status }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    <i data-lucide="package" class="h-3.5 w-3.5 text-slate-400"></i>
                                    {{ number_format($room->equipment_count) }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button
                                        type="button"
                                        onclick="openRoomPeek({{ $room->room_id }}, {{ json_encode($room->room_name) }})"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                                        title="Peek"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>

                                    <button
                                        type="button"
                                        onclick='openEditRoomModal(@json($room))'
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                        title="Edit"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>

                                    <a
                                        href="{{ url('/admin/equipment/all') }}?room={{ $room->room_id }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-blue-800"
                                        title="View equipment"
                                    >
                                        <i data-lucide="package" class="h-4 w-4"></i>
                                    </a>

                                    <a
                                        href="{{ url('/admin/rooms') }}?history={{ $room->room_id }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                                        title="History"
                                    >
                                        <i data-lucide="history" class="h-4 w-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i data-lucide="door-open" class="h-5 w-5"></i>
                                    </div>
                                    <p class="mt-4 text-sm font-medium text-slate-700">No rooms found</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Try adjusting your search or add a new room.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rooms->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $rooms->links() }}
            </div>
        @endif

        @endif
    </section>

@if ($errors->any())
<div
    id="roomValidationModal"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1220]/70 p-4"
>
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20">
        <div class="px-6 pt-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                <i data-lucide="alert-circle" class="h-6 w-6"></i>
            </div>
            <h2 class="mt-4 text-lg font-semibold tracking-tight text-slate-900">Could not add rooms</h2>
            <ul class="mt-3 max-h-48 space-y-1.5 overflow-y-auto text-sm leading-relaxed text-slate-500">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-5">
            <button type="button" onclick="closeRoomValidationModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Close</button>
            <button type="button" onclick="retryAddRoomFromValidation()" class="h-10 rounded-xl bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-[#001fad]">Fix & retry</button>
        </div>
    </div>
</div>
@endif

@php
    $departments = $departments ?? collect();
    $oldRooms = old('rooms', [['room_name' => '', 'room_floor_id' => '', 'room_type' => 'Lecture Room', 'room_status' => 'Normal', 'department_ids' => []]]);
    if (! is_array($oldRooms) || count($oldRooms) === 0) {
        $oldRooms = [['room_name' => '', 'room_floor_id' => '', 'room_type' => 'Lecture Room', 'room_status' => 'Normal', 'department_ids' => []]];
    }
@endphp

<div
    id="addRoomModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    data-reopen="{{ $errors->any() && (old('rooms') || old('room_name')) ? '1' : '0' }}"
>
    <form
        action="{{ route('admin.rooms.store') }}"
        method="POST"
        class="flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
    >
        @csrf
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="plus" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Add rooms</h2>
                </div>
            </div>
            <button type="button" onclick="closeAddRoomModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div class="mb-3 hidden gap-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400 lg:grid lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,0.9fr)_40px]">
                <span>Room name <span class="text-red-500">*</span></span>
                <span>Floor <span class="text-red-500">*</span></span>
                <span>Room type</span>
                <span>Departments</span>
                <span>Status</span>
                <span></span>
            </div>

            <div id="addRoomRows" class="space-y-3">
                @foreach ($oldRooms as $i => $row)
                    @php $selectedDeptIds = array_map('strval', $row['department_ids'] ?? []); @endphp
                    <div class="add-room-row rounded-xl border border-slate-200 bg-slate-50/60 p-3 lg:border-0 lg:bg-transparent lg:p-0" data-row>
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,0.9fr)_40px] lg:items-center">
                            <div>
                                <label class="{{ $eqLabel }} lg:hidden">Room name <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    name="rooms[{{ $i }}][room_name]"
                                    required
                                    value="{{ $row['room_name'] ?? '' }}"
                                    placeholder="e.g. ComLab 1"
                                    class="{{ $eqField }} add-room-name"
                                />
                            </div>
                            <div>
                                <label class="{{ $eqLabel }} lg:hidden">Floor <span class="text-red-500">*</span></label>
                                <select name="rooms[{{ $i }}][room_floor_id]" required class="{{ $eqField }} add-room-floor">
                                    <option value="">Select floor</option>
                                    @forelse ($floors as $floor)
                                        <option value="{{ $floor->floor_id }}" @selected(($row['room_floor_id'] ?? '') == $floor->floor_id)>
                                            {{ $floor->building_name ? $floor->building_name.' · ' : '' }}{{ $floor->floor_level }}
                                        </option>
                                    @empty
                                        <option value="" disabled>No floors yet. Add them in Buildings Layout.</option>
                                    @endforelse
                                </select>
                            </div>
                            <div>
                                <label class="{{ $eqLabel }} lg:hidden">Room type</label>
                                <select name="rooms[{{ $i }}][room_type]" class="{{ $eqField }} add-room-type">
                                    @foreach ($roomTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(($row['room_type'] ?? 'Lecture Room') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $eqLabel }} lg:hidden">Departments</label>
                                @include('admin.rooms.partials.department-picker', [
                                    'inputName' => 'rooms['.$i.'][department_ids][]',
                                    'selectedIds' => $selectedDeptIds,
                                    'departments' => $departments,
                                    'fieldClass' => $eqField,
                                ])
                            </div>
                            <div>
                                <label class="{{ $eqLabel }} lg:hidden">Status</label>
                                <select name="rooms[{{ $i }}][room_status]" class="{{ $eqField }} add-room-status">
                                    <option value="Normal" @selected(($row['room_status'] ?? 'Normal') === 'Normal')>Normal</option>
                                    <option value="Under Maintenance" @selected(($row['room_status'] ?? '') === 'Under Maintenance')>Under Maintenance</option>
                                    <option value="Critical" @selected(($row['room_status'] ?? '') === 'Critical')>Critical</option>
                                </select>
                            </div>
                            <div class="flex items-center justify-end lg:justify-center">
                                <button
                                    type="button"
                                    onclick="removeAddRoomRow(this)"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50 disabled:pointer-events-none disabled:opacity-30"
                                    title="Remove row"
                                    @disabled(count($oldRooms) === 1)
                                >
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button
                type="button"
                onclick="addRoomRow()"
                class="mt-4 inline-flex items-center gap-2 rounded-xl border border-dashed border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-slate-400 hover:bg-slate-50 hover:text-slate-900"
                @disabled($floors->isEmpty())
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Add another room
            </button>
        </div>

        <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-6 py-4">
            <p id="addRoomCountHint" class="text-xs text-slate-400">
                {{ count($oldRooms) }} {{ \Illuminate\Support\Str::plural('room', count($oldRooms)) }} ready
            </p>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeAddRoomModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800" @disabled($floors->isEmpty())>
                    Add rooms
                </button>
            </div>
        </div>
    </form>
</div>

<template id="addRoomRowTemplate">
    <div class="add-room-row rounded-xl border border-slate-200 bg-slate-50/60 p-3 lg:border-0 lg:bg-transparent lg:p-0" data-row>
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,0.9fr)_40px] lg:items-center">
            <div>
                <label class="{{ $eqLabel }} lg:hidden">Room name <span class="text-red-500">*</span></label>
                <input type="text" name="rooms[__INDEX__][room_name]" required value="" placeholder="e.g. ComLab 1" class="{{ $eqField }} add-room-name" />
            </div>
            <div>
                <label class="{{ $eqLabel }} lg:hidden">Floor <span class="text-red-500">*</span></label>
                <select name="rooms[__INDEX__][room_floor_id]" required class="{{ $eqField }} add-room-floor">
                    <option value="">Select floor</option>
                    @foreach ($floors as $floor)
                        <option value="{{ $floor->floor_id }}">
                            {{ $floor->building_name ? $floor->building_name.' · ' : '' }}{{ $floor->floor_level }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $eqLabel }} lg:hidden">Room type</label>
                <select name="rooms[__INDEX__][room_type]" class="{{ $eqField }} add-room-type">
                    @foreach ($roomTypes as $value => $label)
                        <option value="{{ $value }}" @selected($value === 'Lecture Room')>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $eqLabel }} lg:hidden">Departments</label>
                @include('admin.rooms.partials.department-picker', [
                    'inputName' => 'rooms[__INDEX__][department_ids][]',
                    'selectedIds' => [],
                    'departments' => $departments,
                    'fieldClass' => $eqField,
                ])
            </div>
            <div>
                <label class="{{ $eqLabel }} lg:hidden">Status</label>
                <select name="rooms[__INDEX__][room_status]" class="{{ $eqField }} add-room-status">
                    <option value="Normal" selected>Normal</option>
                    <option value="Under Maintenance">Under Maintenance</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div class="flex items-center justify-end lg:justify-center">
                <button
                    type="button"
                    onclick="removeAddRoomRow(this)"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50 disabled:pointer-events-none disabled:opacity-30"
                    title="Remove row"
                >
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<div
    id="editRoomModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <form
        id="editRoomForm"
        method="POST"
        class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
    >
        @csrf
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="pencil" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Edit room</h2>
                </div>
            </div>
            <button type="button" onclick="closeEditRoomModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 gap-6 px-6 py-5 lg:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <label for="edit_room_name" class="{{ $eqLabel }}">Room name <span class="text-red-500">*</span></label>
                    <input id="edit_room_name" type="text" name="room_name" required class="{{ $eqField }}" />
                </div>
                <div>
                    <label for="edit_room_floor" class="{{ $eqLabel }}">Floor <span class="text-red-500">*</span></label>
                    <select id="edit_room_floor" name="room_floor_id" required class="{{ $eqField }}">
                        <option value="">Select floor</option>
                        @foreach ($floors as $floor)
                            <option value="{{ $floor->floor_id }}">
                                {{ $floor->building_name ? $floor->building_name.' · ' : '' }}{{ $floor->floor_level }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label for="edit_room_type" class="{{ $eqLabel }}">Room type</label>
                    <select id="edit_room_type" name="room_type" class="{{ $eqField }}">
                        @foreach ($roomTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $eqLabel }}">Departments</label>
                    @include('admin.rooms.partials.department-picker', [
                        'inputName' => 'department_ids[]',
                        'selectedIds' => [],
                        'departments' => $departments,
                        'fieldClass' => $eqField,
                    ])
                </div>
                <div>
                    <label for="edit_room_status" class="{{ $eqLabel }}">Status</label>
                    <select id="edit_room_status" name="room_status" class="{{ $eqField }}">
                        <option value="Normal">Normal</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" onclick="closeEditRoomModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
            <button type="submit" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Save changes</button>
        </div>
    </form>
</div>

@php
    $managedRoomTypes = $managedRoomTypes ?? collect();
    $roomTypesReady = $roomTypesReady ?? false;
@endphp
<div
    id="roomTypesModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    data-open="{{ request()->boolean('manage_types') || ($errors->has('room_type_name') || $errors->has('room_type_label')) ? '1' : '0' }}"
>
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="tags" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Room types</h2>
                    <p class="mt-1 text-sm text-slate-500">Create and manage types you can assign when adding or editing rooms.</p>
                </div>
            </div>
            <button type="button" onclick="closeRoomTypesModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
            @unless ($roomTypesReady)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Room types table is missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable CRUD.
                </div>
            @else
                <form action="{{ route('admin.rooms.types.store') }}" method="POST" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    @csrf
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Add type</p>
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto_auto]">
                        <div>
                            <label class="{{ $eqLabel }}">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="room_type_name" required maxlength="120" value="{{ old('room_type_name') }}" placeholder="e.g. Maintenance" class="{{ $eqField }}">
                            @error('room_type_name')
                                <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="{{ $eqLabel }}">Label <span class="text-slate-400">(optional)</span></label>
                            <input type="text" name="room_type_label" maxlength="120" value="{{ old('room_type_label') }}" placeholder="Display name" class="{{ $eqField }}">
                        </div>
                        <label class="flex items-end gap-2 pb-2 text-sm text-slate-600">
                            <input type="checkbox" name="room_type_is_storage" value="1" class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]" @checked(old('room_type_is_storage'))>
                            Storage
                        </label>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001db3]">
                                <i data-lucide="plus" class="h-4 w-4"></i>
                                Add
                            </button>
                        </div>
                    </div>
                </form>

                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-2.5">Type</th>
                                <th class="px-4 py-2.5">Rooms</th>
                                <th class="px-4 py-2.5">Flags</th>
                                <th class="px-4 py-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($managedRoomTypes as $type)
                                <tr class="align-top {{ $type->room_type_is_archived ? 'bg-slate-50/60' : '' }}">
                                    <td class="px-4 py-3">
                                        <form id="edit-room-type-{{ $type->room_type_id }}" action="{{ route('admin.rooms.types.update', $type->room_type_id) }}" method="POST" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="room_type_name" required maxlength="120" value="{{ $type->room_type_name }}" class="{{ $eqField }}">
                                            <input type="text" name="room_type_label" maxlength="120" value="{{ $type->room_type_label }}" placeholder="Label (optional)" class="{{ $eqField }}">
                                            <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                                                <input type="checkbox" name="room_type_is_storage" value="1" class="h-3.5 w-3.5 rounded border-slate-300 text-[#0025cc]" @checked($type->room_type_is_storage)>
                                                Storage / stockroom type
                                            </label>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ (int) $type->rooms_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @if ($type->room_type_is_storage)
                                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Storage</span>
                                            @endif
                                            @if ($type->room_type_is_archived)
                                                <span class="rounded-md bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Archived</span>
                                            @else
                                                <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Active</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                                            <button type="submit" form="edit-room-type-{{ $type->room_type_id }}" class="inline-flex h-8 items-center rounded-lg bg-[#0025cc] px-2.5 text-xs font-semibold text-white hover:bg-[#001db3]">Save</button>
                                            <form action="{{ route('admin.rooms.types.archive', $type->room_type_id) }}" method="POST">
                                                @csrf
                                                @if ($type->room_type_is_archived)
                                                    <input type="hidden" name="restore" value="1">
                                                    <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Restore</button>
                                                @else
                                                    <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Archive</button>
                                                @endif
                                            </form>
                                            <form action="{{ route('admin.rooms.types.destroy', $type->room_type_id) }}" method="POST" onsubmit="return confirm('Delete this room type? Only unused types can be deleted.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 items-center rounded-lg border border-rose-200 bg-white px-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50" @disabled((int) $type->rooms_count > 0)>Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">No room types yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endunless
        </div>

        <div class="flex items-center justify-end border-t border-slate-100 px-6 py-4">
            <button type="button" onclick="closeRoomTypesModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Close</button>
        </div>
    </div>
</div>

<div
    id="roomPeekModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <div class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="eye" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 id="roomPeekTitle" class="text-lg font-semibold tracking-tight text-slate-900">Room</h2>
                    <p id="roomPeekCount" class="mt-1 text-sm text-slate-500"></p>
                </div>
            </div>
            <button type="button" onclick="closeRoomPeek()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div id="roomPeekList" class="min-h-0 flex-1 overflow-y-auto pb-2"></div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" onclick="closeRoomPeek()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Close</button>
            <a
                id="roomPeekInventory"
                href="/admin/inventory/deployed"
                class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-blue-800"
            >
                Open in Deployed Equipment
            </a>
        </div>
    </div>
</div>

<style>
    .room-dept-more-panel {
        position: fixed;
        z-index: 80;
        min-width: 180px;
        max-width: 260px;
        max-height: 220px;
        overflow-y: auto;
        padding: 4px 0 6px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(-3px);
        transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
    }

    .room-dept-more.is-visible .room-dept-more-panel {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
    }
</style>

<script>
    function positionRoomDeptMorePanel(wrap) {
        const panel = wrap.querySelector('.room-dept-more-panel');
        const btn = wrap.querySelector('[data-room-dept-more-btn]');
        if (!panel || !btn) return;
        const rect = btn.getBoundingClientRect();
        const gap = 6;
        panel.style.visibility = 'hidden';
        panel.style.opacity = '1';
        panel.style.pointerEvents = 'none';
        panel.style.top = '0px';
        panel.style.left = '0px';
        const width = panel.offsetWidth || 180;
        const height = panel.offsetHeight || 120;
        let top = rect.bottom + gap;
        let left = rect.left;
        if (left + width > window.innerWidth - 8) {
            left = Math.max(8, window.innerWidth - width - 8);
        }
        if (top + height > window.innerHeight - 8) {
            top = Math.max(8, rect.top - height - gap);
        }
        panel.style.top = top + 'px';
        panel.style.left = left + 'px';
        panel.style.visibility = '';
        panel.style.opacity = '';
        panel.style.pointerEvents = '';
    }

    function closeRoomDeptMore(wrap) {
        wrap.classList.remove('is-visible', 'is-open');
        wrap.querySelector('[data-room-dept-more-btn]')?.setAttribute('aria-expanded', 'false');
    }

    function closeAllRoomDeptMore() {
        document.querySelectorAll('[data-room-dept-more].is-visible').forEach(closeRoomDeptMore);
    }

    function showRoomDeptMore(wrap) {
        document.querySelectorAll('[data-room-dept-more].is-visible').forEach(function (other) {
            if (other !== wrap) closeRoomDeptMore(other);
        });
        wrap.classList.add('is-visible');
        positionRoomDeptMorePanel(wrap);
    }

    function bindRoomDeptMore(wrap) {
        if (!wrap || wrap.dataset.bound) return;
        wrap.dataset.bound = '1';
        const panel = wrap.querySelector('.room-dept-more-panel');
        let hideTimer = null;

        const cancelHide = function () {
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
        };

        const scheduleHide = function () {
            cancelHide();
            hideTimer = setTimeout(function () {
                if (!wrap.classList.contains('is-open')) {
                    closeRoomDeptMore(wrap);
                }
            }, 120);
        };

        wrap.addEventListener('mouseenter', function () {
            cancelHide();
            showRoomDeptMore(wrap);
        });
        wrap.addEventListener('mouseleave', scheduleHide);
        if (panel) {
            panel.addEventListener('mouseenter', cancelHide);
            panel.addEventListener('mouseleave', scheduleHide);
        }
    }

    window.toggleRoomDeptMore = function (button, event) {
        event.preventDefault();
        event.stopPropagation();
        const wrap = button.closest('[data-room-dept-more]');
        if (!wrap) return;
        bindRoomDeptMore(wrap);
        const willOpen = !wrap.classList.contains('is-open');
        closeAllRoomDeptMore();
        if (willOpen) {
            wrap.classList.add('is-open');
            button.setAttribute('aria-expanded', 'true');
            showRoomDeptMore(wrap);
        } else {
            button.setAttribute('aria-expanded', 'false');
        }
    };

    document.addEventListener('mouseover', function (event) {
        const wrap = event.target.closest('[data-room-dept-more]');
        if (wrap) bindRoomDeptMore(wrap);
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-room-dept-more]')) return;
        closeAllRoomDeptMore();
    }, true);

    window.addEventListener('scroll', closeAllRoomDeptMore, true);
    window.addEventListener('resize', closeAllRoomDeptMore);

    let addRoomRowIndex = {{ count($oldRooms ?? [['room_name' => '']]) }};

    function refreshDeptPickerSummary(picker) {
        if (!picker) return;
        const summary = picker.querySelector('[data-dept-summary]');
        const checks = Array.from(picker.querySelectorAll('[data-dept-check]:checked'));
        if (!summary) return;
        if (!checks.length) {
            summary.textContent = 'No department';
            summary.classList.add('text-slate-400');
            summary.classList.remove('text-slate-900');
            return;
        }
        summary.classList.remove('text-slate-400');
        summary.classList.add('text-slate-900');
        if (checks.length === 1) {
            summary.textContent = checks[0].dataset.deptName || '1 department';
        } else {
            summary.textContent = checks.length + ' departments';
        }
    }

    function setDeptPickerValues(picker, ids) {
        if (!picker) return;
        const selected = (ids || []).map(String);
        picker.querySelectorAll('[data-dept-check]').forEach(function (check) {
            check.checked = selected.includes(String(check.value));
        });
        refreshDeptPickerSummary(picker);
    }

    function closeAllDeptPickers(except) {
        document.querySelectorAll('[data-dept-picker]').forEach(function (picker) {
            if (except && picker === except) return;
            const panel = picker.querySelector('[data-dept-panel]');
            const trigger = picker.querySelector('[data-dept-trigger]');
            if (panel) panel.classList.add('hidden');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
    }

    function bindDeptPicker(picker) {
        if (!picker || picker.dataset.bound === '1') return;
        picker.dataset.bound = '1';

        const trigger = picker.querySelector('[data-dept-trigger]');
        const panel = picker.querySelector('[data-dept-panel]');
        const search = picker.querySelector('[data-dept-search]');
        const clearBtn = picker.querySelector('[data-dept-clear]');
        const doneBtn = picker.querySelector('[data-dept-done]');

        if (trigger && panel) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                const open = !panel.classList.contains('hidden');
                closeAllDeptPickers();
                if (!open) {
                    const rect = trigger.getBoundingClientRect();
                    const width = Math.max(rect.width, 240);
                    let left = rect.left;
                    if (left + width > window.innerWidth - 8) {
                        left = Math.max(8, window.innerWidth - width - 8);
                    }
                    panel.style.position = 'fixed';
                    panel.style.left = left + 'px';
                    panel.style.width = width + 'px';
                    panel.style.right = 'auto';
                    panel.style.top = (rect.bottom + 4) + 'px';
                    panel.style.zIndex = '80';
                    panel.classList.remove('hidden');
                    trigger.setAttribute('aria-expanded', 'true');
                    if (search) {
                        search.value = '';
                        picker.querySelectorAll('[data-dept-option]').forEach(function (option) {
                            option.classList.remove('hidden');
                        });
                        setTimeout(function () { search.focus(); }, 0);
                    }
                }
            });
        }

        picker.querySelectorAll('[data-dept-check]').forEach(function (check) {
            check.addEventListener('change', function () {
                refreshDeptPickerSummary(picker);
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                const q = search.value.trim().toLowerCase();
                picker.querySelectorAll('[data-dept-option]').forEach(function (option) {
                    const label = option.dataset.label || '';
                    option.classList.toggle('hidden', q !== '' && !label.includes(q));
                });
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                picker.querySelectorAll('[data-dept-check]').forEach(function (check) {
                    check.checked = false;
                });
                refreshDeptPickerSummary(picker);
            });
        }

        if (doneBtn) {
            doneBtn.addEventListener('click', function () {
                closeAllDeptPickers();
            });
        }

        refreshDeptPickerSummary(picker);
    }

    function initDeptPickers(root) {
        (root || document).querySelectorAll('[data-dept-picker]').forEach(bindDeptPicker);
    }

    function refreshAddRoomUi() {
        const rows = document.querySelectorAll('#addRoomRows [data-row]');
        const hint = document.getElementById('addRoomCountHint');
        const count = rows.length;

        rows.forEach(function (row, index) {
            row.querySelectorAll('[name^="rooms["]').forEach(function (field) {
                field.name = field.name.replace(/rooms\[\d+\]/, 'rooms[' + index + ']');
            });

            const removeBtn = row.querySelector('button[title="Remove row"]');
            if (removeBtn) {
                removeBtn.disabled = count <= 1;
            }
        });

        if (hint) {
            hint.textContent = count + (count === 1 ? ' room ready' : ' rooms ready');
        }

        initDeptPickers(document.getElementById('addRoomRows'));

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function addRoomRow(prefill) {
        const template = document.getElementById('addRoomRowTemplate');
        const container = document.getElementById('addRoomRows');
        if (!template || !container) {
            return;
        }

        const html = template.innerHTML.replace(/__INDEX__/g, String(addRoomRowIndex++));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;

        if (prefill) {
            const name = row.querySelector('.add-room-name');
            const floor = row.querySelector('.add-room-floor');
            const type = row.querySelector('.add-room-type');
            const status = row.querySelector('.add-room-status');
            if (name && prefill.room_name) name.value = prefill.room_name;
            if (floor && prefill.room_floor_id) floor.value = prefill.room_floor_id;
            if (type && prefill.room_type) type.value = prefill.room_type;
            if (status && prefill.room_status) status.value = prefill.room_status;
        }

        container.appendChild(row);
        initDeptPickers(row);
        refreshAddRoomUi();

        const focusInput = row.querySelector('.add-room-name');
        if (focusInput) {
            focusInput.focus();
        }
    }

    function removeAddRoomRow(button) {
        const rows = document.querySelectorAll('#addRoomRows [data-row]');
        if (rows.length <= 1) {
            return;
        }

        const row = button.closest('[data-row]');
        if (row) {
            row.remove();
            refreshAddRoomUi();
        }
    }

    function openRoomTypesModal() {
        const modal = document.getElementById('roomTypesModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) window.lucide.createIcons();
    }

    function closeRoomTypesModal() {
        const modal = document.getElementById('roomTypesModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        const url = new URL(window.location.href);
        if (url.searchParams.has('manage_types')) {
            url.searchParams.delete('manage_types');
            window.history.replaceState({}, '', url.pathname + (url.search ? url.search : '') + url.hash);
        }
    }

    function openAddRoomModal() {
        const modal = document.getElementById('addRoomModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        refreshAddRoomUi();
    }

    function closeAddRoomModal() {
        const modal = document.getElementById('addRoomModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditRoomModal(room) {
        const form = document.getElementById('editRoomForm');
        form.action = '/admin/rooms/' + room.room_id;
        document.getElementById('edit_room_name').value = room.room_name || '';
        document.getElementById('edit_room_floor').value = room.room_floor_id || '';
        document.getElementById('edit_room_type').value = room.room_type || 'Lecture Room';
        const deptPicker = document.querySelector('#editRoomForm [data-dept-picker]');
        setDeptPickerValues(deptPicker, room.department_ids || []);
        document.getElementById('edit_room_status').value = room.room_status || 'Normal';
        const modal = document.getElementById('editRoomModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        initDeptPickers(modal);
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function closeEditRoomModal() {
        const modal = document.getElementById('editRoomModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function closeRoomValidationModal() {
        const modal = document.getElementById('roomValidationModal');
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function retryAddRoomFromValidation() {
        closeRoomValidationModal();
        openAddRoomModal();
    }

    function closeRoomPeek() {
        const modal = document.getElementById('roomPeekModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openRoomPeek(roomId, roomName) {
        const modal = document.getElementById('roomPeekModal');
        const list = document.getElementById('roomPeekList');
        const title = document.getElementById('roomPeekTitle');
        const count = document.getElementById('roomPeekCount');
        const inventory = document.getElementById('roomPeekInventory');

        title.textContent = roomName;
        count.textContent = 'Loading…';
        list.innerHTML = window.prismSkeleton('list', { rows: 4, label: 'Loading equipment' });
        inventory.href = '/admin/inventory/deployed?room=' + roomId;
        inventory.textContent = 'Open in Deployed Equipment';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) {
            window.lucide.createIcons();
        }

        fetch('/admin/rooms/' + roomId + '/equipment', {
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Could not load equipment.');
                }
                return response.json();
            })
            .then(function (data) {
                count.textContent = data.count + (data.count === 1 ? ' unit' : ' units');
                inventory.href = data.inventory_url || ('/admin/inventory/deployed?room=' + roomId);
                if (!data.items.length) {
                    list.innerHTML = '<p class="px-6 py-8 text-sm text-slate-400">No equipment in this room.</p>';
                    return;
                }
                list.innerHTML = data.items
                    .map(function (item) {
                        const category = item.equipment_category_name || 'Uncategorized';
                        const status = item.equipment_inventory_status || '';
                        const uniqueness = item.uniqueness || 'No asset tag / serial';
                        return (
                            '<div class="flex items-start justify-between gap-4 border-t border-slate-100 px-6 py-3">' +
                            '<div class="min-w-0">' +
                            '<p class="truncate text-sm font-medium text-slate-900">' +
                            escapeHtml(item.equipment_name) +
                            '</p>' +
                            '<p class="mt-0.5 truncate font-mono text-[11px] text-slate-600">' +
                            escapeHtml(uniqueness) +
                            '</p>' +
                            '<p class="mt-0.5 text-xs text-slate-400">' +
                            escapeHtml(category) +
                            '</p>' +
                            '</div>' +
                            '<span class="shrink-0 text-xs text-slate-500">' +
                            escapeHtml(status) +
                            '</span>' +
                            '</div>'
                        );
                    })
                    .join('');
            })
            .catch(function () {
                count.textContent = '';
                list.innerHTML = '<p class="px-6 py-8 text-sm text-rose-600">Could not load equipment.</p>';
            });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-dept-picker]')) {
            closeAllDeptPickers();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllDeptPickers();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        refreshAddRoomUi();
        initDeptPickers(document);

        const typesModal = document.getElementById('roomTypesModal');
        if (typesModal && typesModal.dataset.open === '1') {
            openRoomTypesModal();
        }

        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>
@endsection
