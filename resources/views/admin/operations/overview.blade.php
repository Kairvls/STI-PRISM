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
            <p class="mt-1 text-xs text-slate-500">{{ $stats['urgent_reports'] }} urgent</p>
        </a>
        <a href="{{ route('admin.operations.procurement', ['filter' => 'open']) }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Open RIS</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['open_ris'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['awaiting_admin_ris'] }} awaiting Admin accept</p>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.operations.movements', ['tab' => 'transfers', 'filter' => 'recent']) }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Transfers (30 days)</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['transfers_30d'] }}</p>
        </a>
        <a href="{{ route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']) }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Overdue borrows</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['overdue_borrows'] }}</p>
        </a>
        <a href="{{ route('admin.operations.movements', ['tab' => 'disposal']) }}" class="rounded-[18px] border border-gray-200 bg-white px-5 py-4 transition hover:border-slate-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Disposals</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['disposals_total'] }}</p>
        </a>
    </div>

    <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
        <h2 class="text-sm font-bold text-slate-900">Exceptions queue</h2>
        <p class="mt-1 text-xs text-slate-500">Items that usually need Admin visibility or action.</p>
        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($exceptions as $item)
                @php
                    $tone = $item['tone'] ?? 'slate';
                    $classes = match ($tone) {
                        'sky' => 'border-sky-200 bg-sky-50 text-sky-950',
                        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-950',
                        'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
                        'rose' => 'border-rose-200 bg-rose-50 text-rose-950',
                        'violet' => 'border-violet-200 bg-violet-50 text-violet-950',
                        default => 'border-slate-200 bg-slate-50 text-slate-900',
                    };
                @endphp
                <a href="{{ $item['url'] }}" class="rounded-xl border px-4 py-3 {{ $classes }}">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold">{{ $item['label'] }}</p>
                        <p class="text-xl font-bold">{{ $item['count'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.operations.equipment', ['filter' => 'lifecycle']) }}" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">Lifecycle / replacement horizon</a>
        <a href="{{ route('admin.operations.equipment', ['filter' => 'replacement']) }}" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-900">Equipment for replacement</a>
        <a href="{{ route('admin.operations.procurement') }}" class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900">Procurement pipeline monitor</a>
        <a href="{{ url('/admin/procurement-review') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900">Review & act on RIS</a>
    </div>
</div>
@endsection
