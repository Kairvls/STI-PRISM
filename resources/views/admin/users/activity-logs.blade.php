@extends('layouts.admin-layout')

@section('title', 'User Activity Logs')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">User Activity Logs</h1>
        <p class="admin-page-subtitle">Recent approvals and sign-in activity across the system.</p>
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Who</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">When</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $log->who ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $log->action ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">
                                @if(!empty($log->happened_at))
                                    {{ \Carbon\Carbon::parse($log->happened_at)->format('M d, Y g:i A') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ $log->details ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center text-sm text-gray-400">
                                No recent activity found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($logs, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
