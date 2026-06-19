@extends('layouts.maintenance-layout')

@section('title', 'Report Details')

@section('content')

<!-- PAGE HEADER -->
<div class="mb-8">

    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">

        <div>

            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">

                <a href="/maintenance/reports"
                   class="hover:text-yellow-600 transition">
                    Reports
                </a>

                <span>/</span>

                <span class="font-medium text-gray-700">
                    Report Details
                </span>

            </div>

            <h1 class="text-3xl xl:text-4xl font-extrabold text-gray-900">
                Report Details
            </h1>

            <p class="text-gray-500 mt-2">
                Complete maintenance report information and monitoring.
            </p>

        </div>

        <div>

            @if($report->report_current_status == 'Pending')

                <span class="inline-flex items-center gap-2 bg-yellow-100 text-yellow-800 px-5 py-3 rounded-2xl font-semibold shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                    Pending
                </span>

            @elseif($report->report_current_status == 'Processing')

                <span class="inline-flex items-center gap-2 bg-blue-100 text-blue-800 px-5 py-3 rounded-2xl font-semibold shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    Processing
                </span>

            @elseif($report->report_current_status == 'Resolved')

                <span class="inline-flex items-center gap-2 bg-green-100 text-green-800 px-5 py-3 rounded-2xl font-semibold shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Resolved
                </span>

            @elseif($report->report_current_status == 'Rejected')

                <span class="inline-flex items-center gap-2 bg-red-100 text-red-800 px-5 py-3 rounded-2xl font-semibold shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    Rejected
                </span>

            @else

                <span class="inline-flex items-center gap-2 bg-orange-100 text-orange-800 px-5 py-3 rounded-2xl font-semibold shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    For Replacement
                </span>

            @endif

        </div>

    </div>

</div>

@if(session('success'))
    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700">
        {{ session('success') }}
    </div>
@endif

<!-- MAIN GRID -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    <!-- LEFT SIDE -->
    <div class="xl:col-span-2 space-y-6">

        <!-- REPORT INFORMATION -->
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    Report Information
                </h2>
                <span class="text-sm text-gray-500">
                    #{{ $report->report_id }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Report ID</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">#{{ $report->report_id }}</h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Date Submitted</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">
                        {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('F d, Y h:i A') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Problem Category</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">
                        {{ $report->report_category ?? 'Unspecified' }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Urgency Level</p>
                    <h3 class="font-bold text-lg mt-1 {{ $report->report_urgency_level == 'Urgent' ? 'text-red-600' : 'text-green-600' }}">
                        {{ $report->report_urgency_level }}
                    </h3>
                </div>

            </div>

        </div>

        <!-- PROBLEM DESCRIPTION -->
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-2xl font-bold mb-4 text-gray-900">
                Problem Description
            </h2>

            <div class="rounded-2xl bg-gray-50 p-5 leading-relaxed text-gray-700">
                {{ $report->report_problem_description }}
            </div>

        </div>

        @if($report->report_uploaded_image)

        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-2xl font-bold mb-4 text-gray-900">
                Uploaded Evidence
            </h2>

            <img
                src="{{ asset('storage/' . $report->report_uploaded_image) }}"
                class="w-full rounded-2xl border border-gray-200 object-cover max-h-[500px]">

        </div>

        @endif

        <!-- EQUIPMENT INFORMATION -->
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-2xl font-bold mb-6 text-gray-900">
                Equipment Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Equipment Name</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">{{ $report->equipment_name }}</h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Equipment Status</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">{{ $report->equipment_inventory_status }}</h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Room</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">{{ $report->room_name }}</h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Building</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">{{ $report->building_name }}</h3>
                </div>

            </div>

        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="space-y-6">

        <!-- REPORTER -->
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-2xl font-bold mb-6 text-gray-900">
                Reporter Information
            </h2>

            <div class="space-y-5">

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Full Name</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">{{ $report->reporter_full_name }}</h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Employee ID</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">{{ $report->report_reporter_employee_id }}</h3>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <p class="text-gray-500 text-sm">Contact Number</p>
                    <h3 class="font-bold text-lg mt-1 text-gray-900">{{ $report->reporter_contact_number }}</h3>
                </div>

            </div>

        </div>

        <!-- ACTIONS -->
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-2xl font-bold mb-6 text-gray-900">
                Actions
            </h2>

            <div class="space-y-4">

                <a
                    href="/maintenance/reports/assign/{{ $report->report_id }}"
                    class="block w-full text-center bg-blue-600 hover:bg-blue-700 py-4 rounded-2xl font-bold text-white transition">
                    Assign Personnel
                </a>

                <a
                    href="/maintenance/reports/findings/{{ $report->report_id }}"
                    class="block w-full text-center bg-yellow-500 hover:bg-yellow-600 py-4 rounded-2xl font-bold text-black transition">
                    Add Findings
                </a>

                <a
                    href="/maintenance/reports/update-status/{{ $report->report_id }}"
                    class="block w-full text-center bg-green-600 hover:bg-green-700 py-4 rounded-2xl font-bold text-white transition">
                    Update Status
                </a>

            </div>

        </div>

        <!-- TIMELINE -->
        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-2xl font-bold mb-6 text-gray-900">
                Report Timeline
            </h2>

            <div class="space-y-5">

                <div class="flex gap-4">

                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-900">Report Submitted</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('F d, Y h:i A') }}
                        </p>
                    </div>

                </div>

            </div>

        </div>

        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-2xl font-bold mb-6 text-gray-900">
                Related Reports
            </h2>

            @forelse($relatedReports as $related)

                <a
                    href="/maintenance/reports/details/{{ $related->report_id }}"
                    class="block p-4 mb-3 rounded-2xl bg-gray-50 hover:bg-gray-100 transition text-gray-700 font-medium">
                    Report #{{ $related->report_id }}
                </a>

            @empty

                <p class="text-gray-500">
                    No related reports found.
                </p>

            @endforelse

        </div>

    </div>

</div>

@endsection

