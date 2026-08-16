@extends('layouts.admin-layout')

@section('title', 'Maintenance History')

@section('content')

<div class="admin-page space-y-6">
    <div class="print-hidden">
        <h1 class="admin-page-title">Maintenance</h1>
        <p class="admin-page-subtitle">Read-only ticket summary. Open tickets stay on Maintenance Personnel.</p>
    </div>
    <h1 class="admin-page-title print-only">Maintenance report — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'maintenance'])

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Filed</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-slate-900">{{ $filed }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Resolved</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-emerald-700">{{ $resolved }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rejected</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-rose-600">{{ $rejected }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">For replacement</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-amber-600">{{ $replacement }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Average time to close</p>
            <p class="mt-2 font-['Outfit'] text-2xl font-bold text-slate-900">
                @if($avgCloseHours === null)
                    —
                @else
                    {{ number_format(((float) $avgCloseHours) / 24, 1) }} days
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-400">Resolved, rejected, and replacement tickets. Pending {{ $pending }} · Processing {{ $processing }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Repeat equipment</p>
            <ul class="mt-3 space-y-1 text-sm text-gray-700">
                @forelse($repeatEquipment as $item)
                    <li class="flex justify-between"><span>{{ $item->equipment_label }}</span><span class="font-semibold">{{ $item->report_count }}</span></li>
                @empty
                    <li class="text-gray-400">No repeat equipment in this period.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        @include('layouts.partials.admin-system-reports-filters', ['placeholder' => 'Search equipment, room, building, technician...'])
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">#</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Location</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Urgency</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Technician</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->report_id }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->equipment_name ?: ($row->report_unlisted_equipment_name ?: ($row->report_suggested_issue ?: '—')) }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ trim(($row->building_name ? $row->building_name.' · ' : '').($row->room_name ?: '')) ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->report_urgency_level }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->report_current_status }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->technician_name ?: 'Unassigned' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->report_submitted_at ? \Carbon\Carbon::parse($row->report_submitted_at)->format('M d, Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-16 text-center text-sm text-gray-400">No records in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('layouts.partials.table-showing-pager', ['pager' => $rows, 'noun' => 'tickets'])
    </div>
</div>

@endsection
