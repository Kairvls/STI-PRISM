@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Receiving Logs</h1>
        <p class="admin-page-subtitle">Audit trail of inspections, accepts, inventory updates, and returns.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            @include('layouts.partials.receiving-filters')
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Timestamp</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Reference</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A') }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $log->receiving_log_action }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—') }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $log->officer_name ?: 'Receiving Officer' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $log->receiving_log_remarks }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for the first inspection. Logs appear after you accept or return an approved ATP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
