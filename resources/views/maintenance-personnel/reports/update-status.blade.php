@extends ("layouts.maintenance-layout")

@section ("title", "Update Status")

@section ("content")
    <div class="mx-auto max-w-5xl">
        <!-- HEADER -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-white">Update Status</h1>

            <p class="mt-2 text-gray-400">Change the current maintenance status for this report.</p>
        </div>

        <!-- REPORT CARD -->
        <div class="mb-8 rounded-3xl bg-[#1E293B] p-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-400">Report ID</p>

                    <h1 class="mt-2 text-xl font-bold text-white">
                        #{{ $report->report_id }}
                    </h1>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Current Status</p>

                    <span
                        class="px-4 py-2 rounded-xl inline-block mt-2
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
                "
                    >
                        {{ $report->report_current_status }}
                    </span>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Problem Description</p>

                    <h1 class="mt-2 font-semibold text-white">
                        {{ $report->report_problem_description }}
                    </h1>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Room</p>

                    <h1 class="mt-2 font-semibold text-white">
                        {{
                            $report->room_name ??
                                "No Assigned Room"
                        }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- UPDATE FORM -->
        <div class="rounded-3xl bg-[#1E293B] p-8">
            <form
                action="/maintenance/reports/update-status/{{ $report->report_id }}"
                method="POST"
            >
                @csrf

                <div class="mb-6">
                    <label class="mb-3 block font-semibold text-white">
                        Change Status
                    </label>

                    <select
                        name="status"
                        required
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4 text-white focus:border-green-500 focus:outline-none"
                    >
                        @if ($report->report_current_status == "Pending")
                            <option value="Processing">Processing</option>
                            <option value="Rejected">Rejected</option>
                        @elseif ($report->report_current_status == "Processing")
                            <option value="Resolved">Resolved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="For Replacement">
                                For Replacement
                            </option>
                        @else
                            <option
                                value="{{ $report->report_current_status }}"
                                selected
                            >
                                {{ $report->report_current_status }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row">
                    <button
                        type="submit"
                        class="rounded-2xl bg-green-600 px-8 py-4 font-bold transition hover:bg-green-700"
                    >
                        Update Status
                    </button>

                    <a
                        href="/maintenance/reports/details/{{ $report->report_id }}"
                        class="rounded-2xl bg-gray-700 px-8 py-4 text-center font-bold transition hover:bg-gray-600"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

@endsection
