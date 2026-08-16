@extends('layouts.admin-layout')

@section('title', 'User Access')

@section('content')

<div class="admin-page space-y-6">
    <div class="print-hidden">
        <h1 class="admin-page-title">User access</h1>
        <p class="admin-page-subtitle">Roles and recent sessions. This is not a full security audit.</p>
    </div>
    <h1 class="admin-page-title print-only">User access — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'access'])

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        @include('layouts.partials.admin-system-reports-filters', ['placeholder' => 'Search name, username, role...'])
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Username</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Employee ID</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Last seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $user->user_full_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $user->user_username }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $user->role_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $user->user_employee_id ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">
                                @if(!empty($user->last_activity))
                                    {{ \Carbon\Carbon::createFromTimestamp((int) $user->last_activity)->format('M d, Y g:i A') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">No records in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('layouts.partials.table-showing-pager', ['pager' => $users, 'noun' => 'users'])
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Recent sessions</h2>
            <p class="mt-1 text-xs text-gray-500">Active or recently used browser sessions.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">IP</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Last activity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sessions as $session)
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $session->user_full_name ?: $session->user_username }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $session->role_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $session->ip_address ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::createFromTimestamp((int) $session->last_activity)->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-16 text-center text-sm text-gray-400">No records in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('layouts.partials.table-showing-pager', ['pager' => $sessions, 'noun' => 'sessions'])
    </div>
</div>

@endsection
