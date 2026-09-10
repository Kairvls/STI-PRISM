@extends('layouts.admin-layout')

@section('title', 'Equipment Reports')

@section('content')
<div class="admin-page space-y-6">
    <!--<div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="admin-page-title">Equipment Reports</h1>
            <p class="admin-page-subtitle">Monitor all reports. Day-to-day handling stays with Maintenance; you can override when needed.</p>
        </div>
        <a href="{{ route('admin.operations.overview') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">← Operations</a>
    </div>-->

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4">
            <form method="GET" class="relative min-w-[220px] flex-1">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search reports..." class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none">
            </form>
            <div class="flex flex-wrap gap-1">
                @foreach(['open' => 'Open', 'pending' => 'Pending', 'processing' => 'Processing', 'urgent' => 'Urgent', 'replacement' => 'For replacement', 'resolved' => 'Resolved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
                    <a href="{{ route('admin.operations.reports', ['filter' => $key, 'q' => $q]) }}"
                       class="rounded-lg px-3 py-2 text-xs font-semibold {{ $filter === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Report</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Assignee</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Admin action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $equipmentLabel = $row->equipment_name ?: ($row->report_unlisted_equipment_name ?: 'Unlisted');
                            $canProcess = $row->report_current_status === 'Pending';
                            $canClose = $row->report_current_status === 'Processing';
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-900">#{{ $row->report_id }} · {{ $equipmentLabel }}</p>
                                <p class="text-xs text-slate-500">{{ $row->room_name ?: 'No room' }} · {{ $row->report_urgency_level ?: 'Normal' }} · {{ \Illuminate\Support\Str::limit($row->report_suggested_issue, 60) }}</p>
                                <p class="text-xs text-slate-400">Reporter: {{ $row->reporter_name ?: '—' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">{{ $row->report_current_status }}</span>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $row->assignee_name ?: ($row->purchaser_name ? 'Purchaser: '.$row->purchaser_name : 'Unassigned') }}
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-500">
                                {{ $row->report_submitted_at ? \Carbon\Carbon::parse($row->report_submitted_at)->format('M j, Y g:i A') : '—' }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($canProcess || $canClose)
                                    <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                        @if($canProcess)
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="Processing">
                                                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Start processing</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline" onsubmit="return confirm('Reject this report as Admin override?')">
                                                @csrf
                                                <input type="hidden" name="status" value="Rejected">
                                                <input type="hidden" name="remarks" value="Rejected by Admin override">
                                                <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Reject</button>
                                            </form>
                                        @elseif($canClose)
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="Resolved">
                                                <input type="hidden" name="remarks" value="Resolved by Admin override">
                                                <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-600">Resolve</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.operations.reports.update', $row->report_id) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="status" value="For Replacement">
                                                <input type="hidden" name="remarks" value="Marked for replacement by Admin override">
                                                <button type="submit" class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">For replacement</button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">No reports found.</td></tr>
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
