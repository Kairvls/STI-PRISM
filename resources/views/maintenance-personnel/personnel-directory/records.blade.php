@extends('layouts.maintenance-layout')

@section('title', 'Personnel Directory')

@section('content')
@php
    $statusClass = [
        'Pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'Processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'Resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'For Replacement' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'Rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $chipClass = fn (bool $active) => 'inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition '
        .($active ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50');
    $recordsUrl = fn (?string $status) => route('maintenance.personnel-directory.index', array_filter([
        'reporter' => $reporter->reporter_id,
        'record_status' => $status,
    ]));
    $inactive = $reporter->reporter_status !== 'Active';
@endphp

<div class="space-y-6">
    <a href="{{ route('maintenance.personnel-directory.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back to directory
    </a>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-base font-semibold text-slate-700">
                    {{ strtoupper(substr($reporter->reporter_full_name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="truncate text-lg font-semibold text-slate-900">{{ $reporter->reporter_full_name }}</h2>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $inactive ? 'bg-slate-100 text-slate-600 ring-slate-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-100' }}">{{ $reporter->reporter_status }}</span>
                    </div>
                    <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $reporter->reporter_employee_id }}</p>
                </div>
            </div>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Type</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">{{ $reporter->reporter_employment_type ?? null ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Department</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">{{ $reporter->department_name ?? null ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                    <dd class="mt-0.5 break-all font-medium text-slate-800">{{ $reporter->reporter_email_address ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Contact</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">{{ $reporter->reporter_contact_number ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            ['label' => 'Reports filed', 'hint' => 'All time', 'value' => number_format($reporter->reports_total)],
            ['label' => 'Open', 'hint' => 'Pending, processing or for replacement', 'value' => number_format($reporter->reports_open)],
            ['label' => 'Resolved', 'hint' => 'Fixed and closed', 'value' => number_format($reporter->reports_resolved)],
            ['label' => 'Last report', 'hint' => $reporter->last_reported_at ? \Illuminate\Support\Carbon::parse($reporter->last_reported_at)->diffForHumans() : 'No reports yet', 'value' => $reporter->last_reported_at ? \Illuminate\Support\Carbon::parse($reporter->last_reported_at)->format('M j, Y') : '—'],
        ],
    ])

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
            <h3 class="mr-2 text-base font-semibold text-slate-900">Report records</h3>
            <a href="{{ $recordsUrl(null) }}" class="{{ $chipClass($recordStatus === '') }}">All</a>
            @foreach (\App\Http\Controllers\PersonnelDirectoryController::REPORT_STATUSES as $option)
                <a href="{{ $recordsUrl($option) }}" class="{{ $chipClass($recordStatus === $option) }}">{{ $option }}</a>
            @endforeach
        </div>

        @if ($records->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Report</th>
                            <th class="px-5 py-3">Submitted</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Room</th>
                            <th class="px-5 py-3">Issue</th>
                            <th class="px-5 py-3">Urgency</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            @php
                                $issue = $record->report_suggested_issue ?: $record->report_problem_description;
                            @endphp
                            <tr class="align-top">
                                <td class="px-5 py-3 font-mono text-[13px] font-medium">
                                    @if ($canOpenReports)
                                        <a href="{{ url('/maintenance/reports/details/'.$record->report_id) }}" class="text-[#0025cc] hover:underline">#{{ $record->report_id }}</a>
                                    @else
                                        <span class="text-slate-900">#{{ $record->report_id }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                    {{ $record->report_submitted_at ? \Illuminate\Support\Carbon::parse($record->report_submitted_at)->format('M j, Y g:i A') : '—' }}
                                </td>
                                <td class="px-5 py-3 text-slate-900">{{ $record->equipment_name ?? $record->report_unlisted_equipment_name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $record->room_name ?: '—' }}</td>
                                <td class="max-w-xs px-5 py-3 text-slate-600">{{ $issue ? \Illuminate\Support\Str::limit($issue, 90) : '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($record->report_urgency_level)
                                        <span class="rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $record->report_urgency_level === 'Urgent' ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $record->report_urgency_level }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="whitespace-nowrap rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $statusClass[$record->report_current_status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $record->report_current_status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $records->links() }}
                </div>
            @endif
        @else
            <div class="px-5 py-16 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <i data-lucide="clipboard-list" class="h-6 w-6"></i>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-900">
                    {{ $recordStatus !== '' ? 'No '.strtolower($recordStatus).' reports' : 'No reports filed yet' }}
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
