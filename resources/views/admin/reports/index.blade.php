@extends('layouts.admin-layout')

@section('title', 'System Reports')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">System Reports</h1>
        <p class="admin-page-subtitle">Read-only summaries. Filter by date and print. These pages do not approve, inspect, or edit records.</p>
    </div>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'hub'])

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <a href="{{ url('/admin/reports/maintenance-history') }}" class="rounded-[18px] border border-gray-200 bg-white p-5 no-underline transition hover:border-gray-300">
            <p class="font-['Outfit'] text-lg font-bold text-slate-900">Maintenance</p>
            <p class="mt-1 text-sm text-gray-500">Tickets filed, resolved, rejected, and for replacement — by location, urgency, and technician.</p>
        </a>
        <a href="{{ url('/admin/reports/receiving') }}" class="rounded-[18px] border border-gray-200 bg-white p-5 no-underline transition hover:border-gray-300">
            <p class="font-['Outfit'] text-lg font-bold text-slate-900">Receiving</p>
            <p class="mt-1 text-sm text-gray-500">Accepted vs returned deliveries, official receipts, and inventory lines added.</p>
        </a>
        <a href="{{ url('/admin/reports/approval-logs') }}" class="rounded-[18px] border border-gray-200 bg-white p-5 no-underline transition hover:border-gray-300">
            <p class="font-['Outfit'] text-lg font-bold text-slate-900">Approvals</p>
            <p class="mt-1 text-sm text-gray-500">Who signed or returned records, and when.</p>
        </a>
        <a href="{{ url('/admin/reports/user-login-logs') }}" class="rounded-[18px] border border-gray-200 bg-white p-5 no-underline transition hover:border-gray-300">
            <p class="font-['Outfit'] text-lg font-bold text-slate-900">User access</p>
            <p class="mt-1 text-sm text-gray-500">Roles and recent session activity. High-level access only.</p>
        </a>
    </div>
</div>

@endsection
