@extends('layouts.admin-layout')

@section('title', 'User Activity Logs')

@section('content')

<div class="admin-page space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a
                href="{{ route('admin.users') }}"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 transition hover:text-slate-900"
            >
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to system users
            </a>
            <h1 class="admin-page-title mt-2">User Activity Logs</h1>
            @if (!empty($filterUser))
                <p class="mt-1 text-sm text-slate-500">
                    Showing activity for
                    <span class="font-semibold text-slate-800">{{ $filterUser->user_full_name }}</span>
                    @if (!empty($filterUser->user_employee_id))
                        <span class="text-slate-400">· {{ $filterUser->user_employee_id }}</span>
                    @endif
                </p>
            @else
                <p class="mt-1 text-sm text-slate-500">
                    Role-based actions across approvals, maintenance, receiving, equipment, inspections, and sign-ins.
                </p>
            @endif
        </div>
        @if (!empty($filterUser))
            <a
                href="{{ route('admin.users.activity-logs') }}"
                class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                View all users
            </a>
        @endif
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Who</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Module</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">When</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $log->who ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ $log->module ?? 'System' }}
                                </span>
                            </td>
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
                            <td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">
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
