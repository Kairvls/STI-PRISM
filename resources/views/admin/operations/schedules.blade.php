@extends('layouts.admin-layout')

@section('title', 'Maintenance Schedules')

@section('content')
<div class="admin-page space-y-6">
    <!--<div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="admin-page-title">Maintenance Schedules</h1>
            <p class="admin-page-subtitle">Monitor overdue and upcoming equipment maintenance schedules.</p>
        </div>
        <a href="{{ route('admin.operations.overview') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">← Operations</a>
    </div>-->

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4">
            <form method="GET" class="relative min-w-[220px] flex-1">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search schedules..." class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none">
            </form>
            <div class="flex flex-wrap gap-1">
                @foreach(['all' => 'All', 'overdue' => 'Overdue', 'upcoming' => 'Next 14 days', 'active' => 'Active', 'completed' => 'Completed'] as $key => $label)
                    <a href="{{ route('admin.operations.schedules', ['filter' => $key, 'q' => $q]) }}"
                       class="rounded-lg px-3 py-2 text-xs font-semibold {{ $filter === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Schedule</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Next date</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Frequency</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $isOverdue = ($row->maintenance_schedule_status === 'Overdue')
                                || ($row->maintenance_schedule_status === 'Active' && $row->maintenance_schedule_next_date && $row->maintenance_schedule_next_date < now()->toDateString());
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $row->maintenance_schedule_title }}</p>
                                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($row->maintenance_schedule_description, 80) }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $row->equipment_name ?: '—' }}
                                <span class="block text-xs text-slate-400">{{ $row->room_name ?: '' }}</span>
                            </td>
                            <td class="px-5 py-4 text-sm {{ $isOverdue ? 'font-semibold text-rose-700' : 'text-slate-700' }}">{{ $row->maintenance_schedule_next_date ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $row->maintenance_schedule_frequency ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $isOverdue ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                    {{ $isOverdue ? 'Overdue' : ($row->maintenance_schedule_status ?: '—') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">No schedules found.</td></tr>
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
