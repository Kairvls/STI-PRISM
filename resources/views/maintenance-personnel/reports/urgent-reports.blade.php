@extends('layouts.maintenance-layout')

@section('title', 'Urgent Reports')

@section('content')

<!-- PAGE HEADER -->
<div class="mb-6 flex items-center justify-between">

    <div>

        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">

            <span>Reports</span>

            <i data-lucide="chevron-right" class="w-4 h-4"></i>

            <span class="text-gray-700 font-medium">
                Urgent Reports
            </span>

        </div>

        <h1 class="text-3xl font-bold text-gray-900">
            Urgent Reports
        </h1>

        <p class="text-gray-500 mt-1">
            Monitor and prioritize critical maintenance reports requiring immediate attention.
        </p>

    </div>

    <div class="flex items-center gap-6">

        <div class="text-right">

            <p class="text-xs uppercase tracking-wider text-gray-400">
                Urgent Reports
            </p>

            <p class="text-2xl font-bold text-red-600">
                {{ $reports->total() }}
            </p>

        </div>

        <div class="w-px h-10 bg-gray-300"></div>

        <div class="flex items-center gap-2">

            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>

            <span class="text-sm font-medium text-gray-600">
                Priority Monitoring
            </span>

        </div>

    </div>

</div>

<!-- REUSABLE REPORT TABLE -->
@include(
    'components.tables.reports-table',
    [
        'reports' => $reports
    ]
)

@endsection

