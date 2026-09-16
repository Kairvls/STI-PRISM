@extends('layouts.admin-layout')

@section('title', 'Equipment Reports')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $filters = [
        'open' => 'Open',
        'pending' => 'Pending',
        'processing' => 'Processing',
        'urgent' => 'Urgent',
        'replacement' => 'For replacement',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected',
        'all' => 'All',
    ];

    $statusClasses = [
        'Pending' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Processing' => 'border-blue-200 bg-blue-50 text-blue-700',
        'Resolved' => 'border-green-200 bg-green-50 text-green-700',
        'Rejected' => 'border-red-200 bg-red-50 text-red-700',
        'For Replacement' => 'border-amber-200 bg-amber-50 text-amber-800',
        'Urgent' => 'border-rose-200 bg-rose-50 text-rose-700',
    ];

    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();
@endphp

<div class="admin-page space-y-6">
    @if(session('success'))
        <div class="pur-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pur-alert-error">{{ session('error') }}</div>
    @endif

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Report records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Monitor reports. Day-to-day handling stays with Maintenance; override when needed.</p>
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
                            placeholder="Search reports…"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
                        Search
                    </button>
                    @if($q !== '' && $q !== null)
                        <a
                            href="{{ route('admin.operations.reports', ['filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($filters as $key => $label)
                    <a
                        href="{{ route('admin.operations.reports', ['filter' => $key, 'q' => $q]) }}"
                        class="pur-filter-chip {{ $filter === $key ? 'is-active' : '' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table min-w-[1100px]">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th>Submitted</th>
                        <th class="text-center">Admin action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $equipmentLabel = $row->equipment_name ?: ($row->report_unlisted_equipment_name ?: 'Unlisted');
                            $canProcess = $row->report_current_status === 'Pending';
                            $canClose = $row->report_current_status === 'Processing';
                            $status = (string) ($row->report_current_status ?: '—');
                            $badgeClass = $statusClasses[$status] ?? 'border-gray-200 bg-gray-50 text-gray-600';
                            $urgency = (string) ($row->report_urgency_level ?: 'Normal');
                            $isUrgent = strcasecmp($urgency, 'Urgent') === 0 || strcasecmp($urgency, 'Critical') === 0;
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td>
                                <p class="font-semibold text-gray-900">#{{ $row->report_id }} · {{ $equipmentLabel }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $row->room_name ?: 'No room' }}
                                    ·
                                    <span class="{{ $isUrgent ? 'font-semibold text-rose-600' : 'text-gray-400' }}">{{ $urgency }}</span>
                                    ·
                                    {{ \Illuminate\Support\Str::limit($row->report_suggested_issue, 60) }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">Reporter: {{ $row->reporter_name ?: '—' }}</p>
                            </td>
                            <td>
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="text-sm text-gray-600">
                                {{ $row->assignee_name ?: ($row->purchaser_name ? 'Purchaser: '.$row->purchaser_name : 'Unassigned') }}
                            </td>
                            <td class="whitespace-nowrap text-sm text-gray-500">
                                {{ $row->report_submitted_at ? \Carbon\Carbon::parse($row->report_submitted_at)->format('M j, Y g:i A') : '—' }}
                            </td>
                            <td class="text-center">
                                @if($canProcess || $canClose)
                                    <div class="inline-flex flex-wrap items-center justify-center gap-2">
                                        @if($canProcess)
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="Processing">
                                                <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001fa8]">
                                                    Start processing
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline" onsubmit="return confirm('Reject this report as Admin override?')">
                                                @csrf
                                                <input type="hidden" name="status" value="Rejected">
                                                <input type="hidden" name="remarks" value="Rejected by Admin override">
                                                <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                                    Reject
                                                </button>
                                            </form>
                                        @elseif($canClose)
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="Resolved">
                                                <input type="hidden" name="remarks" value="Resolved by Admin override">
                                                <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-500">
                                                    Resolve
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="For Replacement">
                                                <input type="hidden" name="remarks" value="Marked for replacement by Admin override">
                                                <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-amber-200 bg-white px-3 text-xs font-semibold text-amber-800 transition hover:bg-amber-50">
                                                    For replacement
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="pur-empty">No reports found.</td>
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
