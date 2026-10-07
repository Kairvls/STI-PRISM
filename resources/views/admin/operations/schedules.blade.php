@extends('layouts.admin-layout')

@section('title', 'Maintenance Schedules')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $filters = [
        'all' => 'All',
        'overdue' => 'Overdue',
        'upcoming' => 'Next 14 days',
        'active' => 'Active',
        'completed' => 'Completed',
    ];

    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();

    $statusClasses = [
        'Overdue' => 'sched-status-overdue',
        'Active' => 'sched-status-active',
        'Completed' => 'sched-status-completed',
        'Upcoming' => 'sched-status-upcoming',
    ];
@endphp

{{-- Plain CSS colors so the admin grayscale theme (which targets Tailwind color utilities) leaves status badges colored. --}}
<style>
    .sched-status { background: #f8fafc; color: #475569; }
    .sched-status-overdue { background: #fff1f2; color: #be123c; }
    .sched-status-active { background: #f0f9ff; color: #0369a1; }
    .sched-status-completed { background: #ecfdf5; color: #047857; }
    .sched-status-upcoming { background: #fffbeb; color: #b45309; }
</style>

<div class="admin-page space-y-6">
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Schedule records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Monitor overdue and upcoming equipment maintenance schedules.</p>
                </div>

                <form method="GET" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Search schedules…"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
                        Search
                    </button>
                    @if($q !== '' && $q !== null)
                        <a
                            href="{{ \App\Support\AdminPortal::route('operations.schedules', ['filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($filters as $key => $label)
                    <a
                        href="{{ \App\Support\AdminPortal::route('operations.schedules', ['filter' => $key, 'q' => $q]) }}"
                        class="pur-filter-chip {{ $filter === $key && empty($recordId) ? 'is-active' : '' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>

            @if(!empty($recordId))
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-blue-100 bg-blue-50/60 px-3.5 py-2.5 text-xs text-gray-600">
                    <span>Showing only the schedule you selected.</span>
                    <a
                        href="{{ \App\Support\AdminPortal::route('operations.schedules', ['filter' => $filter]) }}"
                        class="font-semibold text-[#0025cc] hover:underline"
                    >Show all {{ strtolower($filters[$filter] ?? 'schedules') }} schedules</a>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table min-w-[900px]">
                <thead>
                    <tr>
                        <th>Schedule Title</th>
                        <th>Equipment</th>
                        <th>Next date for maintenance</th>
                        <th>Frequency</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $isOverdue = ($row->maintenance_schedule_status === 'Overdue')
                                || ($row->maintenance_schedule_status === 'Active' && $row->maintenance_schedule_next_date && $row->maintenance_schedule_next_date < now()->toDateString());
                            $displayStatus = $isOverdue ? 'Overdue' : ($row->maintenance_schedule_status ?: '—');
                            $badgeClass = $statusClasses[$displayStatus] ?? '';
                            $nextDate = $row->maintenance_schedule_next_date
                                ? \Carbon\Carbon::parse($row->maintenance_schedule_next_date)->format('M j, Y')
                                : '—';
                            $placementZone = trim((string) ($row->equipment_placement_zone ?: $row->equipment_current_location ?: ''));
                            $location = implode(' · ', array_filter([$row->room_name ?? null, $placementZone]));
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td>
                                <p class="font-semibold text-gray-900">{{ $row->maintenance_schedule_title }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($row->maintenance_schedule_description, 80) }}</p>
                            </td>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if(!empty($row->equipment_qr_code))
                                        <button
                                            type="button"
                                            class="shrink-0 rounded-md border border-gray-200 bg-white p-0.5 transition hover:border-gray-400"
                                            title="Show QR code"
                                            data-qr-code="{{ $row->equipment_qr_code }}"
                                            data-qr-name="{{ $row->equipment_name }}"
                                            data-qr-src="{{ url('/maintenance/equipment/qr-image/'.rawurlencode($row->equipment_qr_code)) }}"
                                            onclick="window.openScheduleQr(this)"
                                        >
                                            <img
                                                src="{{ url('/maintenance/equipment/qr-image/'.rawurlencode($row->equipment_qr_code)) }}"
                                                alt="QR code for {{ $row->equipment_name }}"
                                                loading="lazy"
                                                class="h-10 w-10 object-contain"
                                            >
                                        </button>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-700">{{ $row->equipment_name ?: '—' }}</p>
                                        @if($location !== '')
                                            <p class="mt-0.5 text-xs text-gray-400">{{ $location }}</p>
                                        @endif
                                        @if(!empty($row->equipment_qr_code))
                                            <button
                                                type="button"
                                                class="mt-0.5 font-mono text-[11px] text-[#0025cc] underline-offset-2 hover:underline"
                                                title="Show QR code"
                                                data-qr-code="{{ $row->equipment_qr_code }}"
                                                data-qr-name="{{ $row->equipment_name }}"
                                                data-qr-src="{{ url('/maintenance/equipment/qr-image/'.rawurlencode($row->equipment_qr_code)) }}"
                                                onclick="window.openScheduleQr(this)"
                                            >{{ $row->equipment_qr_code }}</button>
                                        @elseif($row->equipment_name)
                                            <p class="mt-0.5 font-mono text-[11px] text-gray-400">No QR code</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-sm {{ $isOverdue ? 'font-semibold text-rose-700' : 'text-gray-700' }}">
                                {{ $nextDate }}
                            </td>
                            <td class="text-sm text-gray-600">{{ $row->maintenance_schedule_frequency ?: '—' }}</td>
                            <td>
                                <span class="sched-status {{ $badgeClass }} inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium">
                                    {{ $displayStatus }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="pur-empty">No schedules found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($rows, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
</div>

<div id="scheduleQrModal" class="fixed inset-0 z-[11000] hidden items-center justify-center bg-gray-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="scheduleQrTitle">
    <div class="w-full max-w-xs overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 id="scheduleQrTitle" class="truncate text-sm font-semibold text-gray-950" data-qr-field="name"></h3>
            <p class="mt-0.5 text-xs text-gray-400">Equipment QR code</p>
        </div>
        <div class="flex flex-col items-center px-5 py-5">
            <div class="rounded-xl border border-gray-200 bg-white p-3">
                <img data-qr-field="image" alt="" class="h-48 w-48 object-contain">
            </div>
            <p class="mt-3 max-w-full break-all text-center font-mono text-xs font-semibold tracking-wide text-gray-700" data-qr-field="code"></p>
        </div>
        <div class="flex justify-end border-t border-gray-100 px-5 py-3">
            <button type="button" class="px-3 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950" onclick="window.closeScheduleQr()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('scheduleQrModal');
        if (!modal) return;

        let lastTrigger = null;

        window.openScheduleQr = function (button) {
            const name = button.getAttribute('data-qr-name') || 'Equipment';
            const image = modal.querySelector('[data-qr-field="image"]');
            modal.querySelector('[data-qr-field="name"]').textContent = name;
            modal.querySelector('[data-qr-field="code"]').textContent = button.getAttribute('data-qr-code') || '';
            image.src = button.getAttribute('data-qr-src') || '';
            image.alt = 'QR code for ' + name;

            lastTrigger = button;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.querySelector('button').focus();
        };

        window.closeScheduleQr = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (lastTrigger) lastTrigger.focus();
        };

        modal.addEventListener('click', function (event) {
            if (event.target === modal) window.closeScheduleQr();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) window.closeScheduleQr();
        });
    })();
</script>
@endsection
