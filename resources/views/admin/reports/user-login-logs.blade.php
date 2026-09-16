@extends('layouts.admin-layout')

@section('title', 'User Access')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')

@php
    $userCount = method_exists($users, 'total') ? $users->total() : $users->count();
    $sessionCount = method_exists($sessions, 'total') ? $sessions->total() : $sessions->count();
@endphp

<div class="admin-page space-y-6">
    <h1 class="admin-page-title print-only" hidden>User access — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'access'])

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold text-gray-950">User access</h2>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $userCount }}</span>
                </div>
                <p class="text-xs text-gray-400 sm:ml-1">Roles and recent sessions. This is not a full security audit.</p>
            </div>
            @include('layouts.partials.admin-system-reports-filters', ['placeholder' => 'Search name, username, role…'])
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table w-full min-w-[900px]">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Employee ID</th>
                        <th>Last seen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="text-sm font-semibold text-gray-900">{{ $user->user_full_name }}</td>
                            <td class="text-sm text-gray-600">{{ $user->user_username }}</td>
                            <td>
                                @if(!empty($user->role_name))
                                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $user->role_name }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-600">{{ $user->user_employee_id ?: '—' }}</td>
                            <td class="whitespace-nowrap text-sm text-gray-500">
                                @if(!empty($user->last_activity))
                                    {{ \Carbon\Carbon::createFromTimestamp((int) $user->last_activity)->format('M j, Y g:i A') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="pur-empty">No records in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('layouts.partials.table-showing-pager', ['pager' => $users, 'noun' => 'users'])
    </div>

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-semibold text-gray-950">Recent sessions</h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $sessionCount }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">Active or recently used browser sessions.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table w-full min-w-[900px]">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>IP</th>
                        <th>Last activity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="text-sm font-semibold text-gray-900">{{ $session->user_full_name ?: $session->user_username }}</td>
                            <td>
                                @if(!empty($session->role_name))
                                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $session->role_name }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-600">{{ $session->ip_address ?: '—' }}</td>
                            <td class="whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::createFromTimestamp((int) $session->last_activity)->format('M j, Y g:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="pur-empty">No records in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('layouts.partials.table-showing-pager', ['pager' => $sessions, 'noun' => 'sessions'])
    </div>
</div>

@endsection
