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
                    <dd class="mt-0.5 font-medium text-slate-800">{{ \App\Support\PhoneNumber::formatForDisplay($reporter->reporter_contact_number) ?: '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <form method="GET" action="{{ url()->current() }}" data-auto-filter class="flex flex-col gap-2 border-b border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <input type="hidden" name="reporter" value="{{ $reporter->reporter_id }}">
            <h3 class="text-base font-semibold text-slate-900">Report records</h3>

            <div class="flex flex-col gap-2 sm:ml-auto sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-[240px]">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="record_search" value="{{ request('record_search') }}" placeholder="Search history..." class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-xs font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400">
                </div>

                <div class="relative">
                    <select name="record_status" class="h-9 min-w-[150px] appearance-none rounded-lg border border-slate-200 bg-white pl-3 pr-9 text-xs font-medium text-slate-600 outline-none transition focus:border-slate-400">
                        <option value="">All Status</option>
                        @foreach (['Pending', 'Processing', 'Resolved', 'For Replacement', 'Rejected'] as $option)
                            <option value="{{ $option }}" @selected(request('record_status') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                </div>

                <button type="submit" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Apply
                </button>
                @if (request()->filled('record_search') || request()->filled('record_status'))
                    <a href="{{ request()->fullUrlWithQuery(['record_search' => null, 'record_status' => null, 'records_page' => null]) }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Clear record filters">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </a>
                @endif
            </div>
        </form>

        @if ($records->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-600">
                        <tr>
                            <th class="w-[14%] px-5 py-3">Report</th>
                            <th class="w-[20%] px-5 py-3">Issue</th>
                            <th class="w-[16%] px-5 py-3">Equipment</th>
                            <th class="w-[14%] px-5 py-3">Room</th>
                            <th class="w-[10%] px-5 py-3">Urgency</th>
                            <th class="w-[12%] px-5 py-3">Status</th>
                            <th class="w-[14%] px-5 py-3">Submitted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($records as $record)
                            @php
                                $issue = $record->report_suggested_issue ?: $record->report_problem_description;
                            @endphp
                            <tr class="align-top">
                                <td class="px-5 py-3 font-mono text-[13px] font-medium text-slate-900">
                                    @php
                                        $recordTicketCode = \App\Support\ReportGrouping::ticketCode($record);
                                    @endphp
                                    @if ($canOpenReports)
                                        <a href="{{ \App\Support\ReportDetailsReturn::detailsUrl((int) $record->report_id) }}" class="text-[#0025cc] hover:underline">{{ $recordTicketCode }}</a>
                                    @else
                                        <span class="text-slate-900">{{ $recordTicketCode }}</span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-5 py-3 text-slate-600">{{ $issue ? \Illuminate\Support\Str::limit($issue, 90) : '—' }}</td>
                                <td class="px-5 py-3 text-slate-900">{{ $record->equipment_name ?? $record->report_unlisted_equipment_name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $record->room_name ?: '—' }}</td>

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
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                    {{ $record->report_submitted_at ? \Illuminate\Support\Carbon::parse($record->report_submitted_at)->format('M j, Y g:i A') : '—' }}
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
