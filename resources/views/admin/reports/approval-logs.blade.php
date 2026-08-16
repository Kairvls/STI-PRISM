@extends('layouts.admin-layout')

@section('title', 'Approval Logs')

@section('content')

<div class="admin-page space-y-6">
    <div class="print-hidden">
        <h1 class="admin-page-title">Approvals</h1>
        <p class="admin-page-subtitle">Read-only signature and decision log.</p>
    </div>
    <h1 class="admin-page-title print-only">Approval log — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'approvals'])

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        @include('layouts.partials.admin-system-reports-filters', ['placeholder' => 'Search officer, status, remarks...'])
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">When</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Level</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Record</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Decision</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->approval_log_approved_at ? \Carbon\Carbon::parse($row->approval_log_approved_at)->format('M d, Y g:i A') : '—' }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->officer_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->approval_log_level ?? '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->approval_log_reference_type }} #{{ $row->approval_log_reference_id }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->approval_log_approval_status }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->approval_log_approval_remarks ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-gray-400">No records in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('layouts.partials.table-showing-pager', ['pager' => $rows, 'noun' => 'records'])
    </div>
</div>

@endsection
