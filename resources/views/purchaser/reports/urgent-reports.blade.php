@extends('layouts.purchaser-layout')

@section('page-title', 'Urgent Reports')
@section('page-subtitle', 'Reports that need immediate purchasing attention.')

@section('content')
@php
    $isArchiveView = request('archive') == 1 || request('view') === 'archive';
    $urgentBaseQuery = $isArchiveView ? ['archive' => 1] : [];
    $activeStatus = request('status');
    $scopeHint = $isArchiveView
        ? 'All archived urgent reports (any date)'
        : 'All active urgent reports (any date)';
@endphp
<div>
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a
            href="{{ route('purchaser.reports.urgent', $urgentBaseQuery) }}"
            class="pur-stat-card group {{ $activeStatus === null || $activeStatus === '' ? 'is-active' : '' }}"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Urgent</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($urgentSummary['total'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>{{ $scopeHint }}</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.reports.urgent', array_merge($urgentBaseQuery, ['status' => 'Pending'])) }}"
            class="pur-stat-card group {{ $activeStatus === 'Pending' ? 'is-active' : '' }}"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($urgentSummary['pending'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i data-lucide="clock-3" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Any date · awaiting action</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.reports.urgent', array_merge($urgentBaseQuery, ['status' => 'Processing'])) }}"
            class="pur-stat-card group {{ $activeStatus === 'Processing' ? 'is-active' : '' }}"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Processing</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($urgentSummary['processing'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <i data-lucide="loader" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Any date · currently handled</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.reports.urgent', array_merge($urgentBaseQuery, ['status' => 'For Replacement'])) }}"
            class="pur-stat-card group {{ $activeStatus === 'For Replacement' ? 'is-active' : '' }}"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">For Replacement</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($urgentSummary['for_replacement'] ?? 0) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50 text-orange-700">
                    <i data-lucide="refresh-cw" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Any date · ready for procurement</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
    </div>

    @include('components.tables.reports-table', [
        'reports' => $reports,
        'context' => 'purchaser-urgent',
    ])
</div>
@endsection
