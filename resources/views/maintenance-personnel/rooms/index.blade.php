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
        <form method="POST" action="{{ route('maintenance.rooms.merge') }}">
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
    {{-- ===================================================== --}}
    {{-- ROOMS DASHBOARD --}}
    {{-- ===================================================== --}}

    <div class="mb-6 mt-6 overflow-hidden rounded-lg border-t border-b border-slate-300 bg-gray-100 shadow-sm">
        <div class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr]">

            {{-- TOTAL ROOMS --}}
            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">
                        Total Rooms
                    </p>

                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalRooms) }}
                    </h2>

                    <p class="mt-3 text-sm">
                        @if ($roomMonthlyPercentage === null)
                            <span class="font-semibold text-emerald-500">
                                New activity
                            </span>
                        @else
                            <span
                                class="font-semibold
                                    {{
                                        $roomMonthlyPercentage > 0
                                            ? 'text-emerald-500'
                                            : (
                                                $roomMonthlyPercentage < 0
                                                    ? 'text-red-500'
                                                    : 'text-slate-500'
                                            )
                                    }}"
                            >
                                {{ $roomMonthlyPercentage > 0 ? '+' : '' }}{{ number_format($roomMonthlyPercentage, 2) }}%
                            </span>
                        @endif

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>

                <div class="ml-6 h-20 w-40 shrink-0">
                    <svg viewBox="0 0 300 100" class="h-full w-full" fill="none">
                        <polyline
                            points="{{ $roomTrendPoints }}"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </div>

            {{-- NORMAL --}}
            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>

                <p class="text-md font-medium text-slate-600">
                    Normal
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($normalRooms) }}
                </h2>

                <p class="text-base">
                    <span class="font-semibold text-slate-900">
                        {{ number_format($normalRoomsPercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">
                        of all rooms
                    </span>
                </p>
            </div>

            {{-- NEEDS ATTENTION --}}
            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>

                <p class="text-md font-medium text-slate-600">
                    Needs Attention
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($needsAttentionRooms) }}
                </h2>

                <p class="text-base">
                    <span class="font-semibold text-slate-900">
                        {{ number_format($needsAttentionPercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">
                        of all rooms
                    </span>
                </p>
            </div>

            {{-- WITH EQUIPMENT --}}
            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>

                <p class="text-md font-medium text-slate-600">
                    With Equipment
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($roomsWithEquipment) }}
                </h2>

                <p class="text-base">
                    <span class="font-semibold text-slate-900">
                        {{ number_format($roomsWithEquipmentPercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">
                        of all rooms
                    </span>
                </p>
            </div>

        </div>
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
                        href="{{ route('maintenance.rooms.index') }}"
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

                <form
                    method="GET"
                    action="{{ url()->current() }}"
                    class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap lg:w-auto lg:items-center lg:justify-end"
                >
                    <input type="hidden" name="history" value="{{ $historyRoom->room_id }}" />
                    @if (request()->filled('history_focus'))
                        <input type="hidden" name="history_focus" value="{{ request('history_focus') }}" />
                    @endif
                    @if (request()->filled('history_period'))
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
                            placeholder="Search reports..."
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
                        <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                        Apply
                    </button>

                    @if (
                        request()->filled('history_search')
                        || request()->filled('history_status')
                        || request()->filled('history_period')
                        || request()->filled('history_month')
                        || request()->filled('history_focus')
                    )
                        <a
                            href="{{ route('maintenance.rooms.index', ['history' => $historyRoom->room_id]) }}"
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

            @php
                $activePeriod = $historyPeriod ?? '';
                $activeStatus = request('history_status');
                $activeFocus = request('history_focus');
                $activeMonth = request('history_month');
            @endphp

            <div class="grid grid-cols-2 divide-y divide-slate-200 border-b border-slate-200 sm:grid-cols-4 sm:divide-x sm:divide-y-0 xl:grid-cols-7">
                @foreach ([
                    ['label' => 'Today', 'value' => $roomReportStats['today'], 'period' => 'today', 'status' => null, 'focus' => null],
                    ['label' => 'This week', 'value' => $roomReportStats['week'], 'period' => 'week', 'status' => null, 'focus' => null],
                    ['label' => 'This month', 'value' => $roomReportStats['month'], 'period' => 'month', 'status' => null, 'focus' => null],
                    ['label' => 'This year', 'value' => $roomReportStats['year'], 'period' => 'year', 'status' => null, 'focus' => null],
                    ['label' => 'Resolved', 'value' => $roomReportStats['resolved'], 'period' => $activePeriod ?: null, 'status' => 'Resolved', 'focus' => null],
                    ['label' => 'For replacement', 'value' => $roomReportStats['for_replacement'], 'period' => $activePeriod ?: null, 'status' => 'For Replacement', 'focus' => null],
                    ['label' => 'Eq. for replacement', 'value' => $roomEquipmentStats['for_replacement_qty'] ?: $roomEquipmentStats['for_replacement'], 'period' => null, 'status' => null, 'focus' => 'equipment_for_replacement'],
                ] as $stat)
                    @php
                        $isActive = ($stat['period'] && $activePeriod === $stat['period'] && ! $stat['status'] && ! $stat['focus'] && ! $activeMonth)
                            || ($stat['status'] && $activeStatus === $stat['status'] && ! $stat['focus'])
                            || ($stat['focus'] && $activeFocus === $stat['focus']);
                        $statQuery = array_filter([
                            'history' => $historyRoom->room_id,
                            'history_period' => ($stat['period'] && ! $stat['status'] && ! $stat['focus'])
                                ? $stat['period']
                                : (($stat['status'] || $stat['focus']) && ! $activeMonth ? ($activePeriod ?: null) : null),
                            'history_month' => ($stat['status'] || $stat['focus']) ? $activeMonth : null,
                            'history_status' => $stat['status'],
                            'history_focus' => $stat['focus'],
                            'history_search' => request('history_search') ?: null,
                        ], fn ($v) => $v !== null && $v !== '');
                    @endphp
                    <a
                        href="{{ route('maintenance.rooms.index', $statQuery) }}"
                        class="block px-4 py-3 transition hover:bg-slate-50 {{ $isActive ? 'bg-blue-50/70' : '' }}"
                    >
                        <p class="text-[11px] font-medium uppercase tracking-wide {{ $isActive ? 'text-[#0025cc]' : 'text-slate-400' }}">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold {{ $isActive ? 'text-[#0025cc]' : 'text-slate-900' }}">{{ number_format($stat['value']) }}</p>
                    </a>
                @endforeach
            </div>

            @if (($activeFocus ?? '') === 'equipment_for_replacement')
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Equipment for replacement</h3>
                            <p class="mt-0.5 text-xs text-slate-400">Assets in this room marked for replacement</p>
                        </div>
                        <a
                            href="{{ route('maintenance.rooms.index', ['history' => $historyRoom->room_id, 'history_period' => $activePeriod ?: null]) }}"
                            class="text-xs font-medium text-slate-500 hover:text-slate-800"
                        >
                            Back to reports
                        </a>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full min-w-[720px] text-left">
                            <thead class="border-b border-slate-200 bg-slate-50/70">
                                <tr class="text-[12px] font-semibold uppercase tracking-[0.08em] text-black">
                                    <th class="px-4 py-3">Equipment</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">Qty</th>
                                    <th class="px-4 py-3">Condition</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse (($roomEquipmentList ?? collect()) as $item)
                                    <tr class="hover:bg-slate-50/70">
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $item->equipment_name }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $item->equipment_category_name ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ number_format($item->equipment_quantity ?? 1) }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $item->equipment_condition_status ?: '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                                {{ $item->equipment_inventory_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                                            No equipment for replacement in this room.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead class="border-b border-slate-200 bg-slate-50/70">
                        <tr class="text-[12px] font-semibold uppercase tracking-[0.08em] text-black">
                            <th class="px-5 py-3">Report</th>
                            <th class="px-5 py-3">Issue</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Reporter</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Submitted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reportHistory as $report)
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
                                    {{ \App\Support\ReportGrouping::ticketCode($report) }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <p class="max-w-[260px] truncate">{{ $report->report_problem_description ?: '—' }}</p>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-500">
                                    No reports found for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reportHistory && $reportHistory->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $reportHistory->links() }}
                </div>
            @endif

            @endif
            <div class="border-t border-slate-200 px-5 py-5">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Room activity</h3>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Latest {{ method_exists($roomActivityHistory, 'count') ? $roomActivityHistory->count() : 0 }}
                            @if (method_exists($roomActivityHistory, 'total'))
                                of {{ number_format($roomActivityHistory->total()) }}
                            @endif
                            changes for this room
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-0 divide-y divide-slate-100 border-y border-slate-200">
                    @forelse ($roomActivityHistory as $activity)
                        <div class="flex items-start gap-3 py-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <i data-lucide="activity" class="h-3.5 w-3.5"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800">{{ $activity->activity_title ?: $activity->activity_type }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $activity->activity_description ?: '—' }}</p>
                            </div>
                            <p class="shrink-0 text-[11px] text-slate-400">
                                {{ $activity->created_at ? \Carbon\Carbon::parse($activity->created_at)->format('M j, Y g:i A') : '' }}
                            </p>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-400">No activity logged yet.</p>
                    @endforelse
                </div>

                @if (method_exists($roomActivityHistory, 'hasPages') && $roomActivityHistory->hasPages())
                    <div class="mt-4">
                        {{ $roomActivityHistory->links() }}
                    </div>
                @endif
            </div>

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
                    <p class="mt-0.5 text-xs text-slate-400">
                        Manage rooms, floors, and assigned equipment
                    </p>
                </div>
            </div>

            <div class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-500">
                <i data-lucide="layout-grid" class="h-3.5 w-3.5"></i>
                {{ $rooms->total() }} total
            </div>
        </div>

        <form method="GET" action="{{ url()->current() }}" class="border-b border-slate-200 px-5 py-4">
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
                        placeholder="Search room, building, or floor..."
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
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
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
            <table class="w-full min-w-[980px] text-left">
                <thead class="border-b border-slate-200 bg-slate-50/70">
                    <tr class="text-[12px] font-semibold uppercase tracking-[0.08em] text-black">
                        <th class="px-5 py-3">{!! $sortLink('room_name', 'Room') !!}</th>
                        <th class="px-5 py-3">{!! $sortLink('type', 'Room Type') !!}</th>
                        <th class="px-5 py-3">{!! $sortLink('floor', 'Floor') !!}</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">{!! $sortLink('equipment', 'Equipment') !!}</th>
                        <th class="w-[220px] px-5 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($rooms as $room)
                        @php
                            $status = $room->room_status ?: 'Normal';
                            $statusClass = match ($status) {
                                'Normal' => 'bg-emerald-50 text-emerald-700',
                                'Maintenance Needed' => 'bg-amber-50 text-amber-700',
                                'Critical' => 'bg-rose-50 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                            $statusDotClass = match ($status) {
                                'Normal' => 'bg-emerald-500',
                                'Maintenance Needed' => 'bg-amber-500',
                                'Critical' => 'bg-rose-500',
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
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                                        title="Edit"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>

                                    <a
                                        href="{{ url('/maintenance/equipment/all') }}?room={{ $room->room_id }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-blue-800"
                                        title="View equipment"
                                    >
                                        <i data-lucide="package" class="h-4 w-4"></i>
                                    </a>

                                    <a
                                        href="{{ url('/maintenance/rooms') }}?history={{ $room->room_id }}"
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
                            <td colspan="6" class="px-5 py-16 text-center">
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
            <button type="button" onclick="retryAddRoomFromValidation()" class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800">Fix & retry</button>
        </div>
    </div>
</div>
@endif

@php
    $oldRooms = old('rooms', [['room_name' => '', 'room_floor_id' => '', 'room_type' => 'Lecture Room', 'room_status' => 'Normal']]);
    if (! is_array($oldRooms) || count($oldRooms) === 0) {
        $oldRooms = [['room_name' => '', 'room_floor_id' => '', 'room_type' => 'Lecture Room', 'room_status' => 'Normal']];
    }
@endphp

<div
    id="addRoomModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    data-reopen="{{ $errors->any() && (old('rooms') || old('room_name')) ? '1' : '0' }}"
>
    <form
        action="{{ route('maintenance.rooms.store') }}"
        method="POST"
        class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
    >
        @csrf
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i data-lucide="plus" class="h-5 w-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Add rooms</h2>
                    <p class="mt-1 text-sm text-slate-500">Add one or many rooms — each row keeps its own name, floor, type, and status.</p>
                </div>
            </div>
            <button type="button" onclick="closeAddRoomModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div class="mb-3 hidden gap-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400 lg:grid lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)_40px]">
                <span>Room name <span class="text-rose-400">*</span></span>
                <span>Floor <span class="text-rose-400">*</span></span>
                <span>Room type</span>
                <span>Status</span>
                <span></span>
            </div>

            <div id="addRoomRows" class="space-y-3">
                @foreach ($oldRooms as $i => $row)
                    <div class="add-room-row rounded-xl border border-slate-200 bg-slate-50/60 p-3 lg:border-0 lg:bg-transparent lg:p-0" data-row>
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)_40px] lg:items-start">
                            <div>
                                <label class="{{ $eqLabel }} lg:hidden">Room name <span class="text-rose-500">*</span></label>
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
                                <label class="{{ $eqLabel }} lg:hidden">Floor <span class="text-rose-500">*</span></label>
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
                                <label class="{{ $eqLabel }} lg:hidden">Status</label>
                                <select name="rooms[{{ $i }}][room_status]" class="{{ $eqField }} add-room-status">
                                    <option value="Normal" @selected(($row['room_status'] ?? 'Normal') === 'Normal')>Normal</option>
                                    <option value="Maintenance Needed" @selected(($row['room_status'] ?? '') === 'Maintenance Needed')>Maintenance Needed</option>
                                    <option value="Critical" @selected(($row['room_status'] ?? '') === 'Critical')>Critical</option>
                                </select>
                            </div>
                            <div class="flex items-end justify-end lg:items-center lg:justify-center lg:pt-1">
                                <button
                                    type="button"
                                    onclick="removeAddRoomRow(this)"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:pointer-events-none disabled:opacity-30"
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
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)_40px] lg:items-start">
            <div>
                <label class="{{ $eqLabel }} lg:hidden">Room name <span class="text-rose-500">*</span></label>
                <input type="text" name="rooms[__INDEX__][room_name]" required value="" placeholder="e.g. ComLab 1" class="{{ $eqField }} add-room-name" />
            </div>
            <div>
                <label class="{{ $eqLabel }} lg:hidden">Floor <span class="text-rose-500">*</span></label>
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
                <label class="{{ $eqLabel }} lg:hidden">Status</label>
                <select name="rooms[__INDEX__][room_status]" class="{{ $eqField }} add-room-status">
                    <option value="Normal" selected>Normal</option>
                    <option value="Maintenance Needed">Maintenance Needed</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div class="flex items-end justify-end lg:items-center lg:justify-center lg:pt-1">
                <button
                    type="button"
                    onclick="removeAddRoomRow(this)"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:pointer-events-none disabled:opacity-30"
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
                    <p class="mt-1 text-sm text-slate-500">Update the name, floor, or type.</p>
                </div>
            </div>
            <button type="button" onclick="closeEditRoomModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 gap-6 px-6 py-5 lg:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <label for="edit_room_name" class="{{ $eqLabel }}">Room name <span class="text-rose-500">*</span></label>
                    <input id="edit_room_name" type="text" name="room_name" required class="{{ $eqField }}" />
                </div>
                <div>
                    <label for="edit_room_floor" class="{{ $eqLabel }}">Floor <span class="text-rose-500">*</span></label>
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
                    <label for="edit_room_status" class="{{ $eqLabel }}">Status</label>
                    <select id="edit_room_status" name="room_status" class="{{ $eqField }}">
                        <option value="Normal">Normal</option>
                        <option value="Maintenance Needed">Maintenance Needed</option>
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
                href="/maintenance/equipment/inventory"
                class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-blue-800"
            >
                Open in Inventory
            </a>
        </div>
    </div>
</div>

<script>
    let addRoomRowIndex = {{ count($oldRooms ?? [['room_name' => '']]) }};

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
        form.action = '/maintenance/rooms/' + room.room_id;
        document.getElementById('edit_room_name').value = room.room_name || '';
        document.getElementById('edit_room_floor').value = room.room_floor_id || '';
        document.getElementById('edit_room_type').value = room.room_type || 'Lecture Room';
        document.getElementById('edit_room_status').value = room.room_status || 'Normal';
        const modal = document.getElementById('editRoomModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
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
        list.innerHTML = '<p class="px-6 py-8 text-sm text-slate-400">Loading equipment…</p>';
        inventory.href = '/maintenance/equipment/all?room=' + roomId;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) {
            window.lucide.createIcons();
        }

        fetch('/maintenance/rooms/' + roomId + '/equipment', {
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Could not load equipment.');
                }
                return response.json();
            })
            .then(function (data) {
                count.textContent = data.count + (data.count === 1 ? ' item' : ' items');
                inventory.href = data.inventory_url;
                if (!data.items.length) {
                    list.innerHTML = '<p class="px-6 py-8 text-sm text-slate-400">No equipment in this room.</p>';
                    return;
                }
                list.innerHTML = data.items
                    .map(function (item) {
                        const qty = item.equipment_quantity ? ' × ' + item.equipment_quantity : '';
                        const category = item.equipment_category_name || 'Uncategorized';
                        const status = item.equipment_inventory_status || '';
                        return (
                            '<div class="flex items-start justify-between gap-4 border-t border-slate-100 px-6 py-3">' +
                            '<div class="min-w-0">' +
                            '<p class="truncate text-sm font-medium text-slate-900">' +
                            escapeHtml(item.equipment_name) +
                            qty +
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

    document.addEventListener('DOMContentLoaded', function () {
        refreshAddRoomUi();

        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>
@endsection
