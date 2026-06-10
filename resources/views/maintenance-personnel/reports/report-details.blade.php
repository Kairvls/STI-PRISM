@extends('layouts.maintenance-layout')

@section('title', 'Report Details')

@section('content')

<!-- PAGE HEADER -->
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-8">

    <div>

        <h1 class="text-4xl font-extrabold">

            Report Details

        </h1>

        <p class="text-gray-400 mt-2">

            Complete maintenance report information and monitoring.

        </p>

    </div>

    <!-- STATUS BADGE -->
    <div>

        @if($report->report_current_status == 'Pending')

            <span class="bg-yellow-500/20 text-yellow-400 px-5 py-3 rounded-2xl font-bold">

                Pending

            </span>

        @elseif($report->report_current_status == 'Processing')

            <span class="bg-blue-500/20 text-blue-400 px-5 py-3 rounded-2xl font-bold">

                Processing

            </span>

        @elseif($report->report_current_status == 'Resolved')

            <span class="bg-green-500/20 text-green-400 px-5 py-3 rounded-2xl font-bold">

                Resolved

            </span>

        @elseif($report->report_current_status == 'Rejected')

            <span class="bg-red-500/20 text-red-400 px-5 py-3 rounded-2xl font-bold">

                Rejected

            </span>

        @else

            <span class="bg-orange-500/20 text-orange-400 px-5 py-3 rounded-2xl font-bold">

                For Replacement

            </span>

        @endif

    </div>

</div>

<!-- MAIN GRID -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    <!-- LEFT SIDE -->
    <div class="xl:col-span-2 space-y-6">

        <!-- REPORT INFORMATION -->
        <div class="bg-[#1E293B] rounded-3xl p-6">

            <h1 class="text-2xl font-bold mb-6">

                Report Information

            </h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>

                    <p class="text-gray-400 text-sm">
                        Report ID
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        #{{ $report->report_id }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Date Submitted
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('F d, Y h:i A') }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Problem Category
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->report_category }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Urgency Level
                    </p>

                    <h1 class="font-bold text-lg mt-1 text-red-400">

                        {{ $report->report_urgency_level }}

                    </h1>

                </div>

            </div>

        </div>

        <!-- PROBLEM DESCRIPTION -->
        <div class="bg-[#1E293B] rounded-3xl p-6">

            <h1 class="text-2xl font-bold mb-6">

                Problem Description

            </h1>

            <div class="bg-[#0F172A] rounded-2xl p-5 leading-relaxed text-gray-300">

                {{ $report->report_problem_description }}

            </div>

        </div>

        <!-- EQUIPMENT INFORMATION -->
        <div class="bg-[#1E293B] rounded-3xl p-6">

            <h1 class="text-2xl font-bold mb-6">

                Equipment Information

            </h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>

                    <p class="text-gray-400 text-sm">
                        Equipment Name
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->equipment_name }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Equipment Status
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->equipment_inventory_status }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Room
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->room_name }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Building
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->building_name }}

                    </h1>

                </div>

            </div>

        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="space-y-6">

        <!-- REPORTER -->
        <div class="bg-[#1E293B] rounded-3xl p-6">

            <h1 class="text-2xl font-bold mb-6">

                Reporter Information

            </h1>

            <div class="space-y-5">

                <div>

                    <p class="text-gray-400 text-sm">
                        Full Name
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->reporter_full_name }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Employee ID
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->report_reporter_employee_id }}

                    </h1>

                </div>

                <div>

                    <p class="text-gray-400 text-sm">
                        Contact Number
                    </p>

                    <h1 class="font-bold text-lg mt-1">

                        {{ $report->reporter_contact_number }}

                    </h1>

                </div>

            </div>

        </div>

        <!-- ACTIONS -->
        <div class="bg-[#1E293B] rounded-3xl p-6">

            <h1 class="text-2xl font-bold mb-6">

                Actions

            </h1>

            <div class="space-y-4">

                <button class="w-full bg-blue-600 hover:bg-blue-700 py-4 rounded-2xl font-bold transition">

                    Assign Personnel

                </button>

                <button class="w-full bg-yellow-500 hover:bg-yellow-600 py-4 rounded-2xl font-bold text-black transition">

                    Add Findings

                </button>

                <button class="w-full bg-green-600 hover:bg-green-700 py-4 rounded-2xl font-bold transition">

                    Update Status

                </button>

            </div>

        </div>

        <!-- TIMELINE -->
        <div class="bg-[#1E293B] rounded-3xl p-6">

            <h1 class="text-2xl font-bold mb-6">

                Report Timeline

            </h1>

            <div class="space-y-5">

                <div class="flex gap-4">

                    <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">

                        <i data-lucide="file-text"
                           class="w-5 h-5 text-blue-400"></i>

                    </div>

                    <div>

                        <h1 class="font-semibold">

                            Report Submitted

                        </h1>

                        <p class="text-sm text-gray-400 mt-1">

                            {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('F d, Y h:i A') }}

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

