@extends('layouts.maintenance-layout')

@section('title', 'All Reports')

@section('content')

@php
    $isUrgentPage = request()->is('maintenance/reports/urgent');
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

    <div>

        <nav class="text-xs font-semibold text-gray-400 mb-1 tracking-wide flex items-center gap-1.5 select-none">
            <span class="hover:text-gray-600 transition-colors cursor-pointer">
                Maintenance
            </span>

            <span>&rsaquo;</span>

            <span class="text-gray-600">
                {{ $isUrgentPage ? 'Urgent Reports' : 'Dashboard' }}
            </span>
        </nav>

        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">

            {{ $isUrgentPage ? 'Urgent Reports' : 'Reports' }}

            @if($isUrgentPage)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Priority
                </span>
            @endif

        </h1>

        <p class="text-sm font-medium text-gray-500 mt-1">

            @if($isUrgentPage)
                View and manage urgent maintenance reports requiring immediate attention.
            @else
                View and manage all maintenance reports.
            @endif

        </p>

    </div>

    <!-- Live Count Badge -->
    <div class="flex items-center gap-2 self-start sm:self-center">

        <div class="inline-flex items-center gap-2 bg-gray-100/80 border border-gray-200/60 px-3.5 py-2 rounded-xl shadow-3xs">

            <span class="w-2 h-2 rounded-full {{ $isUrgentPage ? 'bg-red-500' : 'bg-emerald-500' }} animate-pulse"></span>

            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                On This Page
            </span>

            <span class="text-xs font-black bg-gray-900 text-white px-2 py-0.5 rounded-md min-w-[24px] text-center shadow-2xs">
                {{ $reports->count() }}
            </span>

        </div>

    </div>

</div>

@include(
    'components.tables.reports-table',
    ['reports' => $reports]
)

@endsection