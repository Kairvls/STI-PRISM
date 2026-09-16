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
        'Overdue' => 'border-rose-200 bg-rose-50 text-rose-700',
        'Active' => 'border-blue-200 bg-blue-50 text-blue-700',
        'Completed' => 'border-green-200 bg-green-50 text-green-700',
        'Upcoming' => 'border-amber-200 bg-amber-50 text-amber-700',
    ];
@endphp

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
                            href="{{ route('admin.operations.schedules', ['filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($filters as $key => $label)
                    <a
                        href="{{ route('admin.operations.schedules', ['filter' => $key, 'q' => $q]) }}"
                        class="pur-filter-chip {{ $filter === $key ? 'is-active' : '' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table min-w-[900px]">
                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Equipment</th>
                        <th>Next date</th>
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
                            $badgeClass = $statusClasses[$displayStatus] ?? 'border-gray-200 bg-gray-50 text-gray-600';
                            $nextDate = $row->maintenance_schedule_next_date
                                ? \Carbon\Carbon::parse($row->maintenance_schedule_next_date)->format('M j, Y')
                                : '—';
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td>
                                <p class="font-semibold text-gray-900">{{ $row->maintenance_schedule_title }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($row->maintenance_schedule_description, 80) }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-medium text-gray-700">{{ $row->equipment_name ?: '—' }}</p>
                                @if(!empty($row->room_name))
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $row->room_name }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm {{ $isOverdue ? 'font-semibold text-rose-700' : 'text-gray-700' }}">
                                {{ $nextDate }}
                            </td>
                            <td class="text-sm text-gray-600">{{ $row->maintenance_schedule_frequency ?: '—' }}</td>
                            <td>
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">
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
@endsection
