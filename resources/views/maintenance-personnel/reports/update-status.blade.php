@extends('layouts.maintenance-layout')

@section('title', 'Update Status')

@section('content')

<div class="max-w-5xl mx-auto">

    <!-- HEADER -->
    <div class="mb-8">

        <h1 class="text-3xl font-extrabold text-white">
            Update Status
        </h1>

        <p class="text-gray-400 mt-2">
            Change the current maintenance status for this report.
        </p>

    </div>

    <!-- REPORT CARD -->
    <div class="bg-[#1E293B] rounded-3xl p-8 mb-8">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>

                <p class="text-gray-400 text-sm">
                    Report ID
                </p>

                <h1 class="text-xl font-bold mt-2 text-white">
                    #{{ $report->report_id }}
                </h1>

            </div>

            <div>

                <p class="text-gray-400 text-sm">
                    Current Status
                </p>

                <span class="px-4 py-2 rounded-xl inline-block mt-2
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

            <div>

                <p class="text-gray-400 text-sm">
                    Problem Description
                </p>

                <h1 class="font-semibold mt-2 text-white">
                    {{ $report->report_problem_description }}
                </h1>

            </div>

            <div>

                <p class="text-gray-400 text-sm">
                    Room
                </p>

                <h1 class="font-semibold mt-2 text-white">
                    {{ $report->room_name ?? 'No Assigned Room' }}
                </h1>

            </div>

        </div>

    </div>

    <!-- UPDATE FORM -->
    <div class="bg-[#1E293B] rounded-3xl p-8">

        <form action="/maintenance/reports/update-status/{{ $report->report_id }}"
              method="POST">

            @csrf

            <div class="mb-6">

                <label class="block mb-3 font-semibold text-white">
                    Change Status
                </label>

                <select name="status"
                    required
                    class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-green-500">

                    @if($report->report_current_status == 'Pending')
                        <option value="Processing">Processing</option>
                        <option value="Rejected">Rejected</option>
                    @elseif($report->report_current_status == 'Processing')
                        <option value="Resolved">Resolved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="For Replacement">For Replacement</option>
                    @else
                        <option value="{{ $report->report_current_status }}" selected>
                            {{ $report->report_current_status }}
                        </option>
                    @endif

                </select>

            </div>

            <div class="flex flex-col sm:flex-row gap-4">

                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 px-8 py-4 rounded-2xl font-bold transition">
                    Update Status
                </button>

                <a href="/maintenance/reports/details/{{ $report->report_id }}"
                    class="px-8 py-4 rounded-2xl font-bold bg-gray-700 hover:bg-gray-600 transition text-center">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

@endsection