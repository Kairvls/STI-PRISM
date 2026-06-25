@extends ("layouts.maintenance-layout")

@section ("title", "Assign Report")

@section ("content")
    <div class="mx-auto max-w-5xl">
        <!-- PAGE HEADER -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-white">Assign Report</h1>

            <p class="mt-2 text-gray-400">Assign maintenance personnel to handle this report.</p>
        </div>

        <!-- SUCCESS MESSAGE -->
        @if (session("success"))
            <div
                class="mb-6 rounded-2xl border border-green-500/20 bg-green-500/10 px-5 py-4 text-green-400"
            >
                {{
                    session(
                        "success",
                    )
                }}
            </div>

        @endif

        <!-- REPORT INFORMATION -->
        <div class="mb-8 rounded-3xl bg-[#1E293B] p-8">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold text-white">Report Information</h2>

                <span
                    class="
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
        "
                >
                    {{ $report->report_current_status }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-400">Report ID</p>

                    <h3 class="mt-2 text-lg font-bold text-white">
                        #{{ $report->report_id }}
                    </h3>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Date Submitted</p>

                    <h3 class="mt-2 text-lg font-semibold text-white">
                        {{
                            \Carbon\Carbon::parse(
                                $report->report_submitted_at,
                            )->format("F d, Y h:i A")
                        }}
                    </h3>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Room</p>

                    <h3 class="mt-2 text-lg font-semibold text-white">
                        {{
                            $report->room_name ??
                                "No Assigned Room"
                        }}
                    </h3>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Equipment</p>

                    <h3 class="mt-2 text-lg font-semibold text-white">
                        {{
                            $report->equipment_name ??
                                "Unlisted Equipment"
                        }}
                    </h3>
                </div>

                <div class="md:col-span-2">
                    <p class="text-sm text-gray-400">Problem Description</p>

                    <div
                        class="mt-2 rounded-2xl bg-[#0F172A] p-4 leading-relaxed text-gray-300"
                    >
                        {{ $report->report_problem_description }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ASSIGNMENT FORM -->
        <div class="rounded-3xl bg-[#1E293B] p-8">
            <h2 class="mb-6 text-xl font-bold text-white">
                Assign Maintenance Personnel
            </h2>

            <form
                action="/maintenance/reports/assign/{{ $report->report_id }}"
                method="POST"
            >
                @csrf

                <!-- PERSONNEL -->
                <div class="mb-6">
                    <label class="mb-3 block font-semibold text-white">
                        Select Personnel
                    </label>

                    <select
                        name="personnel_id"
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4 text-white focus:border-blue-500 focus:outline-none"
                        required
                    >
                        <option value="">Choose Personnel</option>

                        @foreach ($personnel as $user)
                            <option value="{{ $user->user_id }}">
                                {{ $user->user_full_name }}
                            </option>

                        @endforeach
                    </select>
                </div>

                <!-- REMARKS -->
                <div class="mb-8">
                    <label class="mb-3 block font-semibold text-white">
                        Assignment Remarks
                    </label>

                    <textarea
                        name="assignment_remarks"
                        rows="5"
                        class="w-full resize-none rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4 text-white focus:border-blue-500 focus:outline-none"
                        placeholder="Enter assignment remarks..."
                    ></textarea>
                </div>

                <!-- BUTTONS -->
                <div class="flex flex-col gap-4 sm:flex-row">
                    <button
                        type="submit"
                        class="rounded-2xl bg-blue-600 px-8 py-4 font-bold transition hover:bg-blue-700"
                    >
                        Assign Report
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
