@extends('layouts.maintenance-layout')

@section('title', 'Assign Report')

@section('content')

<div class="max-w-5xl mx-auto">


<!-- PAGE HEADER -->
<div class="mb-8">

    <h1 class="text-3xl font-extrabold text-white">

        Assign Report

    </h1>

    <p class="text-gray-400 mt-2">

        Assign maintenance personnel to handle this report.

    </p>

</div>

<!-- SUCCESS MESSAGE -->
@if(session('success'))

<div class="mb-6 bg-green-500/10 border border-green-500/20 text-green-400 px-5 py-4 rounded-2xl">

    {{ session('success') }}

</div>

@endif

<!-- REPORT INFORMATION -->
<div class="bg-[#1E293B] rounded-3xl p-8 mb-8">

    <div class="flex items-center justify-between mb-6">

        <h2 class="text-xl font-bold text-white">

            Report Information

        </h2>

        <span class="
            px-4 py-2 rounded-xl text-sm font-semibold

            @if($report->report_current_status == 'Pending')
                bg-yellow-500/20 text-yellow-400
            @elseif($report->report_current_status == 'Processing')
                bg-blue-500/20 text-blue-400
            @elseif($report->report_current_status == 'Resolved')
                bg-green-500/20 text-green-400
            @elseif($report->report_current_status == 'Rejected')
                bg-red-500/20 text-red-400
            @else
                bg-orange-500/20 text-orange-400
            @endif
        ">

            {{ $report->report_current_status }}

        </span>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div>

            <p class="text-gray-400 text-sm">

                Report ID

            </p>

            <h3 class="text-lg font-bold mt-2 text-white">

                #{{ $report->report_id }}

            </h3>

        </div>

        <div>

            <p class="text-gray-400 text-sm">

                Date Submitted

            </p>

            <h3 class="text-lg font-semibold mt-2 text-white">

                {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('F d, Y h:i A') }}

            </h3>

        </div>

        <div>

            <p class="text-gray-400 text-sm">

                Room

            </p>

            <h3 class="text-lg font-semibold mt-2 text-white">

                {{ $report->room_name ?? 'No Assigned Room' }}

            </h3>

        </div>

        <div>

            <p class="text-gray-400 text-sm">

                Equipment

            </p>

            <h3 class="text-lg font-semibold mt-2 text-white">

                {{ $report->equipment_name ?? 'Unlisted Equipment' }}

            </h3>

        </div>

        <div class="md:col-span-2">

            <p class="text-gray-400 text-sm">

                Problem Description

            </p>

            <div class="bg-[#0F172A] rounded-2xl p-4 mt-2 text-gray-300 leading-relaxed">

                {{ $report->report_problem_description }}

            </div>

        </div>

    </div>

</div>

<!-- ASSIGNMENT FORM -->
<div class="bg-[#1E293B] rounded-3xl p-8">

    <h2 class="text-xl font-bold text-white mb-6">

        Assign Maintenance Personnel

    </h2>

    <form
        action="/maintenance/reports/assign/{{ $report->report_id }}"
        method="POST"
    >

        @csrf

        <!-- PERSONNEL -->
        <div class="mb-6">

            <label class="block mb-3 font-semibold text-white">

                Select Personnel

            </label>

            <select
                name="personnel_id"
                class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-blue-500"
                required
            >

                <option value="">

                    Choose Personnel

                </option>

                @foreach($personnel as $user)

                <option value="{{ $user->user_id }}">

                    {{ $user->user_full_name }}

                </option>

                @endforeach

            </select>

        </div>

        <!-- REMARKS -->
        <div class="mb-8">

            <label class="block mb-3 font-semibold text-white">

                Assignment Remarks

            </label>

            <textarea
                name="assignment_remarks"
                rows="5"
                class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 text-white resize-none focus:outline-none focus:border-blue-500"
                placeholder="Enter assignment remarks..."
            ></textarea>

        </div>

        <!-- BUTTONS -->
        <div class="flex flex-col sm:flex-row gap-4">

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 px-8 py-4 rounded-2xl font-bold transition"
            >

                Assign Report

            </button>

            <a
                href="/maintenance/reports/details/{{ $report->report_id }}"
                class="px-8 py-4 rounded-2xl font-bold bg-gray-700 hover:bg-gray-600 transition text-center"
            >

                Cancel

            </a>

        </div>

    </form>

</div>


</div>

@endsection
