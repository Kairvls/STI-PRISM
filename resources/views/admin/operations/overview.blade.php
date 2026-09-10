@extends('layouts.admin-layout')

@section('title', 'Operations Overview')

@section('content')
<div class="admin-page space-y-6">
    

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.operations.equipment') }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['equipment_total'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['needs_maintenance'] }} under maintenance · {{ $stats['for_replacement'] }} for replacement</p>
        </a>
        <a href="{{ route('admin.operations.schedules', ['filter' => 'overdue']) }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Overdue schedules</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['overdue_schedules'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Needs attention</p>
        </a>
        <a href="{{ route('admin.operations.reports') }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Open reports</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['open_reports'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Pending or processing</p>
        </a>
        <a href="{{ route('admin.operations.procurement') }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Open RIS</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['open_ris'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['lifecycle_alerts'] }} lifecycle alerts</p>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.operations.equipment', ['filter' => 'lifecycle']) }}" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">Lifecycle / replacement horizon</a>
        <a href="{{ route('admin.operations.equipment', ['filter' => 'replacement']) }}" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-900">Equipment for replacement</a>
        <a href="{{ route('admin.operations.reports', ['filter' => 'urgent']) }}" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm font-semibold text-violet-900">Urgent equipment reports</a>
        <a href="{{ url('/admin/procurement-review') }}" class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900">Review & act on RIS</a>
    </div>
</div>
@endsection
