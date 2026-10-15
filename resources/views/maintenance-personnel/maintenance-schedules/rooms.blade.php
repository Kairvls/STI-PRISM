@extends('layouts.maintenance-layout')

@section('title', 'Room Inspections')

@section('content')
@php
    use App\Support\RoomInspections;
    use Carbon\Carbon;

    $tablesMissing = $tablesMissing ?? false;
    $roomsUrl = \App\Support\SchedulePortal::schedulesUrl('/rooms');
    $fieldClass = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $areaClass = 'w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $filterSelect = 'h-10 min-w-0 rounded-xl border-0 bg-slate-50 px-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 lg:flex-1';
    $statusTone = fn ($status) => match ($status) {
        'Overdue' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        default => 'bg-blue-50 text-blue-700 ring-blue-100',
    };
@endphp

<div x-data="roomInspectionPage(@js($rooms ?? []))" @keydown.escape.window="closeAll()">
<div class="space-y-6">
    @include('maintenance-personnel.maintenance-schedules.partials.type-tabs')

    @if ($tablesMissing)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            Room inspection tables are missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable room inspections.
        </div>
    @else
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">The inspection could not be saved.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Scheduled rooms', 'hint' => 'Active and overdue schedules', 'value' => number_format($stats['active'])],
                ['label' => 'Due within 7 days', 'hint' => '', 'value' => number_format($stats['dueWeek']), 'href' => $roomsUrl.'?status=due_week', 'active' => $status === 'due_week'],
                ['label' => 'Overdue', 'hint' => '', 'value' => number_format($stats['overdue']), 'href' => $roomsUrl.'?status=Overdue', 'active' => $status === 'Overdue'],
                ['label' => 'Inspected this month', 'hint' => '', 'value' => number_format($stats['inspectedMonth'])],
            ],
        ])

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-4 px-6 pb-2 pt-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Room inspection schedules</h2>
                    <p class="mt-0.5 text-sm text-slate-400">
                        Recurring checks of a whole room: lights, outlets, ventilation, doors, cleanliness and safety.
                    </p>
                </div>
                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-[#001fad]"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    New schedule
                </button>
            </div>

            <form method="GET" action="{{ $roomsUrl }}" class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center">
                <div class="relative min-w-0 lg:flex-[1.6]">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search room, room type or inspection"
                        class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>
                <select name="status" class="{{ $filterSelect }}" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="due_week" @selected($status === 'due_week')>Due within 7 days</option>
                    @foreach (['Active', 'Overdue', 'Completed'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="frequency" class="{{ $filterSelect }}" onchange="this.form.submit()">
                    <option value="">All frequencies</option>
                    @foreach (RoomInspections::FREQUENCIES as $option)
                        <option value="{{ $option }}" @selected($frequency === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">Apply</button>
                @if ($search !== '' || $status !== '' || $frequency !== '')
                    <a href="{{ $roomsUrl }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl px-3 text-[13px] font-medium text-slate-500 transition hover:bg-slate-100">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left">
                    <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Room</th>
                            <th class="px-4 py-3">Inspection</th>
                            <th class="px-4 py-3">Frequency</th>
                            <th class="px-4 py-3">Next inspection</th>
                            <th class="px-4 py-3">Last inspection</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($schedules as $schedule)
                            @php
                                $next = $schedule->room_inspection_schedule_next_date ? Carbon::parse($schedule->room_inspection_schedule_next_date) : null;
                                $days = $next ? today()->diffInDays($next, false) : null;
                                $isDone = $schedule->room_inspection_schedule_status === 'Completed';
                                $payload = [
                                    'id' => (int) $schedule->room_inspection_schedule_id,
                                    'room' => $schedule->room_name,
                                    'title' => $schedule->room_inspection_schedule_title,
                                    'frequency' => $schedule->room_inspection_schedule_frequency,
                                    'next_date' => $next?->toDateString(),
                                    'next_label' => $next?->format('M j, Y'),
                                    'notes' => $schedule->room_inspection_schedule_notes,
                                    'checklist' => $schedule->checklist,
                                ];
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/60">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-900">{{ $schedule->room_name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $schedule->room_type ?: 'Room' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm text-slate-800">{{ $schedule->room_inspection_schedule_title }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ count($schedule->checklist) }} checklist items</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $schedule->room_inspection_schedule_frequency }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    @if ($isDone)
                                        <span class="text-sm text-slate-400">Done</span>
                                    @elseif ($next)
                                        <p class="text-sm {{ $days < 0 ? 'font-semibold text-rose-700' : 'text-slate-700' }}">{{ $next->format('M j, Y') }}</p>
                                        <p class="mt-0.5 text-xs {{ $days < 0 ? 'text-rose-600' : ($days <= 7 ? 'text-amber-600' : 'text-slate-400') }}">
                                            @if ($days < 0)
                                                {{ abs((int) $days) }} {{ \Illuminate\Support\Str::plural('day', abs((int) $days)) }} overdue
                                            @elseif ($days === 0)
                                                Due today
                                            @else
                                                In {{ (int) $days }} {{ \Illuminate\Support\Str::plural('day', (int) $days) }}
                                            @endif
                                        </p>
                                    @else
                                        <span class="text-sm text-slate-400">Not set</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    @if ($schedule->room_inspection_schedule_last_date)
                                        <p class="text-sm text-slate-700">{{ Carbon::parse($schedule->room_inspection_schedule_last_date)->format('M j, Y') }}</p>
                                        @if ($schedule->last_result)
                                            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ RoomInspections::resultTone($schedule->last_result) }}">
                                                {{ $schedule->last_result }}{{ (int) $schedule->last_issue_count > 0 ? ' · '.(int) $schedule->last_issue_count.' issue'.((int) $schedule->last_issue_count === 1 ? '' : 's') : '' }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-sm text-slate-400">Not yet inspected</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $statusTone($schedule->room_inspection_schedule_status) }}">
                                        {{ $schedule->room_inspection_schedule_status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        @unless ($isDone)
                                            <button
                                                type="button"
                                                @click='openInspect(@json($payload))'
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-medium text-white transition hover:bg-[#001fad]"
                                            >
                                                <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                                                Inspect
                                            </button>
                                        @endunless
                                        <button
                                            type="button"
                                            @click='openHistory(@json($payload))'
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition hover:bg-slate-200"
                                            data-tooltip="Inspection history"
                                            aria-label="Inspection history for {{ $schedule->room_name }}"
                                        >
                                            <i data-lucide="history" class="h-4 w-4"></i>
                                        </button>
                                        @unless ($isDone)
                                            <button
                                                type="button"
                                                @click='openReschedule(@json($payload))'
                                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition hover:bg-slate-200"
                                                data-tooltip="Reschedule"
                                                aria-label="Reschedule {{ $schedule->room_name }}"
                                            >
                                                <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                                            </button>
                                        @endunless
                                        <button
                                            type="button"
                                            @click='openDelete(@json($payload))'
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-rose-600 transition hover:bg-rose-50"
                                            data-tooltip="Delete schedule"
                                            aria-label="Delete schedule for {{ $schedule->room_name }}"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i data-lucide="door-open" class="h-6 w-6"></i>
                                    </div>
                                    @if ($search !== '' || $status !== '' || $frequency !== '')
                                        <p class="mt-4 text-sm font-semibold text-slate-900">No room inspections match these filters</p>
                                    @else
                                        <p class="mt-4 text-sm font-semibold text-slate-900">No room inspections scheduled yet</p>
                                        <p class="mt-1 text-sm text-slate-500">Schedule a recurring check for classrooms, labs and offices.</p>
                                        <button type="button" @click="openCreate()" class="mt-5 inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white">
                                            <i data-lucide="plus" class="h-4 w-4"></i>
                                            Schedule room inspection
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($schedules->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $schedules->links() }}</div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="px-6 pb-3 pt-6">
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Recent room inspections</h2>
                <p class="mt-0.5 text-sm text-slate-400">The latest inspections recorded for any room.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentLogs as $log)
                    <div class="flex flex-col gap-2 px-6 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">
                                {{ $log->room_name }}
                                <span class="font-normal text-slate-400">· {{ $log->inspection_title ?: 'Room inspection' }}</span>
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $log->inspector_name ?: 'Unknown' }} · {{ Carbon::parse($log->room_inspection_log_inspected_at)->format('M j, Y g:i A') }}
                                @if (filled($log->room_inspection_log_findings))
                                    · {{ \Illuminate\Support\Str::limit($log->room_inspection_log_findings, 80) }}
                                @endif
                            </p>
                        </div>
                        <span class="inline-flex shrink-0 self-start rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 sm:self-center {{ RoomInspections::resultTone($log->room_inspection_log_result) }}">
                            {{ $log->room_inspection_log_result }}{{ (int) $log->room_inspection_log_issue_count > 0 ? ' · '.(int) $log->room_inspection_log_issue_count.' issue'.((int) $log->room_inspection_log_issue_count === 1 ? '' : 's') : '' }}
                        </span>
                    </div>
                @empty
                    <p class="px-6 pb-6 text-sm text-slate-400">No room inspections recorded yet.</p>
                @endforelse
            </div>
        </section>

    @endif
</div>

    @unless ($tablesMissing)
        {{-- Schedule room inspection --}}
        <div x-show="modal === 'create'" x-cloak class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4" @click.self="closeAll()">
            <div class="my-auto flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Schedule Room Inspection</h2>
                        <!--<p class="mt-1 text-sm text-slate-500">Pick one or more rooms. Each room gets its own recurring schedule.</p>-->
                        <p class="mt-1 text-sm text-slate-500">Pick one or more rooms.</p>
                    </div>
                    <button type="button" @click="closeAll()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form action="{{ $roomsUrl }}" method="POST" class="flex min-h-0 flex-1 flex-col" @submit="if (!picked.length) { $event.preventDefault(); roomError = 'Pick at least one room.'; }">
                    @csrf
                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Rooms <span class="text-red-500">*</span></p>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="rounded-lg bg-white px-2.5 py-1 font-medium text-slate-600 ring-1 ring-slate-200/80" x-text="picked.length ? picked.length + ' selected' : 'None selected'"></span>
                                    <button type="button" @click="pickAllShown()" class="font-semibold text-[#0025cc] hover:underline">Select shown</button>
                                    <button type="button" x-show="picked.length" @click="picked = []" class="font-medium text-slate-500 hover:underline">Clear</button>
                                </div>
                            </div>
                            <div class="relative">
                                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" x-model="roomQuery" placeholder="Search room name or type" class="h-10 w-full rounded-xl border-0 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900/10" autocomplete="off">
                            </div>
                            <div class="max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-xl bg-white ring-1 ring-slate-200/80">
                                <template x-for="room in shownRooms()" :key="room.id">
                                    <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 transition hover:bg-slate-50">
                                        <input type="checkbox" name="room_ids[]" :value="room.id" x-model.number="picked" class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]">
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-slate-900" x-text="room.name"></span>
                                            <span class="block truncate text-xs text-slate-400" x-text="room.type || 'Room'"></span>
                                        </span>
                                    </label>
                                </template>
                                <p x-show="!shownRooms().length" class="px-4 py-6 text-center text-sm text-slate-400">No rooms match.</p>
                            </div>
                            <p x-show="roomError" x-cloak class="text-xs font-medium text-rose-600" x-text="roomError"></p>
                        </div>

                        <div>
                            <label for="roomInspectionTitle" class="mb-1.5 block text-sm text-slate-600">Schedule Name <span class="text-slate-400">(Title)</span> <span class="text-red-500">*</span></label>
                            <input id="roomInspectionTitle" type="text" name="title" value="Room inspection" required maxlength="255" class="{{ $fieldClass }}">
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="roomInspectionFrequency" class="mb-1.5 block text-sm text-slate-600">Frequency <span class="text-red-500">*</span></label>
                                <select id="roomInspectionFrequency" name="frequency" class="{{ $fieldClass }}">
                                    @foreach (RoomInspections::FREQUENCIES as $option)
                                        <option value="{{ $option }}" @selected($option === 'Monthly')>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="roomInspectionDate" class="mb-1.5 block text-sm text-slate-600">First inspection <span class="text-red-500">*</span></label>
                                <input id="roomInspectionDate" type="date" name="next_date" value="{{ today()->toDateString() }}" required class="{{ $fieldClass }}">
                            </div>
                        </div>

                        <div>
                            <label for="roomInspectionChecklist" class="mb-1.5 block text-sm text-slate-600">Checklist <!--<span class="text-slate-400">(one item per line)</span>--></label>
                            <textarea id="roomInspectionChecklist" name="checklist" rows="8" class="{{ $areaClass }}">{{ implode("\n", RoomInspections::DEFAULT_CHECKLIST) }}</textarea>
                            <!--<p class="mt-1.5 text-xs text-slate-400">Each item is marked Pass, Issue or N/A when the room is inspected.</p>-->
                        </div>

                        <div>
                            <label for="roomInspectionNotes" class="mb-1.5 block text-sm text-slate-600">Notes <span class="text-slate-400">(optional)</span></label>
                            <textarea id="roomInspectionNotes" name="notes" rows="2" maxlength="2000" placeholder="Instructions for whoever inspects the room" class="{{ $areaClass }}"></textarea>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="closeAll()" class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Cancel</button>
                        <button type="submit" class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]" x-text="picked.length > 1 ? 'Schedule ' + picked.length + ' rooms' : 'Schedule inspection'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Record an inspection --}}
        <div x-show="modal === 'inspect'" x-cloak class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4" @click.self="closeAll()">
            <div class="my-auto flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between gap-4 px-6 pt-6">
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Inspect <span x-text="current?.room"></span></h2>
                        <p class="mt-1 text-sm text-slate-500">
                            <span x-text="current?.title"></span>
                            <template x-if="current?.next_label"><span> · scheduled for <span x-text="current.next_label"></span></span></template>
                        </p>
                    </div>
                    <button type="button" @click="closeAll()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form :action="current ? '{{ $roomsUrl }}/' + current.id + '/inspect' : '#'" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <template x-if="current?.notes">
                            <p class="whitespace-pre-line rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200/80" x-text="current.notes"></p>
                        </template>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Checklist</p>
                                <p class="text-xs font-medium" :class="issueCount() ? 'text-rose-600' : 'text-emerald-600'" x-text="issueCount() ? issueCount() + ' issue' + (issueCount() === 1 ? '' : 's') : 'All clear so far'"></p>
                            </div>
                            <div class="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200/80">
                                <template x-for="(item, index) in (current?.checklist || [])" :key="index">
                                    <div class="flex flex-col gap-2 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm text-slate-800" x-text="item"></p>
                                        <input type="hidden" :name="'checks[' + index + ']'" :value="checks[index]">
                                        <div class="inline-flex shrink-0 rounded-lg bg-slate-100 p-0.5 text-xs font-medium">
                                            <template x-for="option in ['Pass', 'Issue', 'N/A']" :key="option">
                                                <button
                                                    type="button"
                                                    @click="setCheck(index, option)"
                                                    class="rounded-md px-3 py-1.5 transition"
                                                    :class="checks[index] === option
                                                        ? (option === 'Issue' ? 'bg-rose-600 text-white shadow-sm' : (option === 'Pass' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-900 shadow-sm'))
                                                        : 'text-slate-500 hover:text-slate-900'"
                                                    x-text="option"
                                                ></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <p class="mb-1.5 text-sm text-slate-600">Overall result <span class="text-red-500">*</span></p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (RoomInspections::RESULTS as $result)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="result" value="{{ $result }}" x-model="result" class="peer sr-only">
                                        <span class="flex h-11 items-center justify-center rounded-xl text-sm font-medium ring-1 ring-slate-200 transition peer-checked:ring-2 {{ match ($result) {
                                            RoomInspections::RESULT_GOOD => 'peer-checked:bg-emerald-50 peer-checked:text-emerald-700 peer-checked:ring-emerald-400',
                                            RoomInspections::RESULT_ATTENTION => 'peer-checked:bg-amber-50 peer-checked:text-amber-700 peer-checked:ring-amber-400',
                                            default => 'peer-checked:bg-rose-50 peer-checked:text-rose-700 peer-checked:ring-rose-400',
                                        } }} text-slate-600 hover:bg-slate-50">{{ $result }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label for="roomInspectFindings" class="mb-1.5 block text-sm text-slate-600">
                                Findings <span x-show="needsFindings()" class="text-red-500">*</span><span x-show="!needsFindings()" class="text-slate-400">(optional)</span>
                            </label>
                            <textarea id="roomInspectFindings" name="findings" rows="3" maxlength="5000" :required="needsFindings()" placeholder="What did you find? e.g. two lights flickering near the board" class="{{ $areaClass }}"></textarea>
                        </div>

                        <div>
                            <label for="roomInspectAction" class="mb-1.5 block text-sm text-slate-600">Action taken <span class="text-slate-400">(optional)</span></label>
                            <textarea id="roomInspectAction" name="action_taken" rows="2" maxlength="5000" placeholder="e.g. Reported to electrician, replaced bulbs" class="{{ $areaClass }}"></textarea>
                        </div>

                        <div>
                            <label for="roomInspectProof" class="mb-1.5 block text-sm text-slate-600">Photo <span class="text-slate-400">(optional)</span></label>
                            <input id="roomInspectProof" type="file" name="proof_image" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                        </div>

                        <p class="text-xs text-slate-400" x-show="current && current.frequency !== '{{ RoomInspections::ONE_TIME }}'">
                            Saving moves the next <span x-text="current?.frequency?.toLowerCase()"></span> inspection forward automatically.
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="closeAll()" class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Cancel</button>
                        <button type="submit" class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]">Save inspection</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Reschedule --}}
        <div x-show="modal === 'reschedule'" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center bg-[#0b1220]/70 p-4" @click.self="closeAll()">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
                <form :action="current ? '{{ $roomsUrl }}/' + current.id + '/reschedule' : '#'" method="POST">
                    @csrf
                    <div class="space-y-4 px-6 py-6">
                        <div>
                            <h2 class="text-lg font-semibold tracking-tight text-slate-900">Reschedule inspection</h2>
                            <p class="mt-1 text-sm text-slate-500"><span x-text="current?.room"></span> · <span x-text="current?.title"></span></p>
                        </div>
                        <div>
                            <label for="roomRescheduleDate" class="mb-1.5 block text-sm text-slate-600">New date <span class="text-red-500">*</span></label>
                            <input id="roomRescheduleDate" type="date" name="new_date" :value="current?.next_date" required class="{{ $fieldClass }}">
                        </div>
                        <div>
                            <label for="roomRescheduleReason" class="mb-1.5 block text-sm text-slate-600">Reason <span class="text-slate-400">(optional)</span></label>
                            <textarea id="roomRescheduleReason" name="reason" rows="2" maxlength="1000" class="{{ $areaClass }}"></textarea>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="closeAll()" class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]">Reschedule</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete --}}
        <div x-show="modal === 'delete'" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center bg-[#0b1220]/70 p-4" @click.self="closeAll()">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
                <form :action="current ? '{{ $roomsUrl }}/' + current.id : '#'" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="px-6 py-6">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                        </div>
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Delete this schedule?</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Future inspections of <span class="font-medium text-slate-700" x-text="current?.room"></span> stop. Past inspection records are kept.
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                        <button type="button" @click="closeAll()" class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700">Delete schedule</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- History --}}
        <div x-show="modal === 'history'" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center bg-[#0b1220]/70 p-4" @click.self="closeAll()">
            <div class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex shrink-0 items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900">Inspection history</h2>
                        <p class="mt-1 truncate text-sm text-slate-500"><span x-text="current?.room"></span> · <span x-text="current?.title"></span></p>
                    </div>
                    <button type="button" @click="closeAll()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-6">
                    <p x-show="historyLoading" class="text-sm text-slate-400">Loading inspections…</p>
                    <p x-show="!historyLoading && historyError" class="text-sm text-rose-600" x-text="historyError"></p>
                    <p x-show="!historyLoading && !historyError && !historyLogs.length" class="text-sm text-slate-400">This room has not been inspected on this schedule yet.</p>
                    <template x-for="(log, index) in historyLogs" :key="index">
                        <div class="rounded-xl p-4 ring-1 ring-slate-200/80">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-slate-900"><span x-text="log.inspected_at"></span> <span class="font-normal text-slate-400">· <span x-text="log.inspected_by"></span></span></p>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1" :class="log.tone" x-text="log.result + (log.issues ? ' · ' + log.issues + ' issue' + (log.issues === 1 ? '' : 's') : '')"></span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <template x-for="check in log.checklist" :key="check.item">
                                    <span
                                        class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium"
                                        :class="check.status === 'Issue' ? 'bg-rose-50 text-rose-700' : (check.status === 'Pass' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500')"
                                        x-text="check.item + ': ' + check.status"
                                    ></span>
                                </template>
                            </div>
                            <p x-show="log.findings" class="mt-3 whitespace-pre-line text-sm text-slate-700"><span class="font-medium">Findings:</span> <span x-text="log.findings"></span></p>
                            <p x-show="log.action_taken" class="mt-1 whitespace-pre-line text-sm text-slate-700"><span class="font-medium">Action taken:</span> <span x-text="log.action_taken"></span></p>
                            <a x-show="log.proof_url" :href="log.proof_url" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#0025cc] hover:underline">
                                <i data-lucide="image" class="h-3.5 w-3.5"></i>
                                View photo
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    @endunless
</div>

<script>
    function roomInspectionPage(rooms) {
        return {
            rooms,
            modal: null,
            current: null,
            roomQuery: '',
            picked: [],
            roomError: '',
            checks: [],
            result: @js(RoomInspections::RESULT_GOOD),
            historyLoading: false,
            historyError: '',
            historyLogs: [],
            show(name) {
                this.modal = name;
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },
            closeAll() {
                this.modal = null;
            },
            shownRooms() {
                const q = this.roomQuery.trim().toLowerCase();
                if (!q) return this.rooms;
                return this.rooms.filter((room) => (room.name + ' ' + room.type).toLowerCase().includes(q));
            },
            pickAllShown() {
                const ids = new Set(this.picked);
                this.shownRooms().forEach((room) => ids.add(room.id));
                this.picked = [...ids];
                this.roomError = '';
            },
            openCreate() {
                this.roomError = '';
                this.show('create');
            },
            openInspect(schedule) {
                this.current = schedule;
                this.checks = (schedule.checklist || []).map(() => 'Pass');
                this.result = @js(RoomInspections::RESULT_GOOD);
                this.show('inspect');
            },
            setCheck(index, value) {
                this.checks[index] = value;
                if (this.issueCount() > 0 && this.result === @js(RoomInspections::RESULT_GOOD)) {
                    this.result = @js(RoomInspections::RESULT_ATTENTION);
                }
            },
            issueCount() {
                return this.checks.filter((value) => value === 'Issue').length;
            },
            needsFindings() {
                return this.issueCount() > 0 || this.result !== @js(RoomInspections::RESULT_GOOD);
            },
            openReschedule(schedule) {
                this.current = schedule;
                this.show('reschedule');
            },
            openDelete(schedule) {
                this.current = schedule;
                this.show('delete');
            },
            async openHistory(schedule) {
                this.current = schedule;
                this.historyLogs = [];
                this.historyError = '';
                this.historyLoading = true;
                this.show('history');
                try {
                    const response = await fetch(@js($roomsUrl) + '/' + schedule.id + '/history', {
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) throw new Error();
                    const data = await response.json();
                    this.historyLogs = data.logs || [];
                } catch (e) {
                    this.historyError = 'Could not load the inspection history.';
                } finally {
                    this.historyLoading = false;
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                }
            },
        };
    }
</script>
@endsection
