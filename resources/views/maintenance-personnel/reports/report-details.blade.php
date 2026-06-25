@extends ("layouts.maintenance-layout")

@section ("title", "Report Details")

@section ("content")
    <!-- PAGE HEADER -->
    <div class="mb-8">
        <div
            class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
        >
            <div>
                <div class="mb-2 flex items-center gap-2 text-sm text-gray-500">
                    <a
                        href="/maintenance/reports"
                        class="transition hover:text-yellow-600"
                    >
                        Reports
                    </a>

                    <span>/</span>

                    <span class="font-medium text-gray-700">
                        Report Details
                    </span>
                </div>

                <h1 class="text-3xl font-extrabold text-gray-900 xl:text-4xl">
                    Report Details
                </h1>

                <p class="mt-2 text-gray-500">Complete maintenance report information and monitoring.</p>
            </div>

            <div>
                @if ($report->report_current_status == "Pending")
                    <span
                        class="inline-flex items-center gap-2 rounded-2xl bg-yellow-100 px-5 py-3 font-semibold text-yellow-800 shadow-sm"
                    >
                        <span class="h-2 w-2 rounded-full bg-yellow-500"></span>
                        Pending
                    </span>

                @elseif ($report->report_current_status == "Processing")
                    <span
                        class="inline-flex items-center gap-2 rounded-2xl bg-blue-100 px-5 py-3 font-semibold text-blue-800 shadow-sm"
                    >
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        Processing
                    </span>

                @elseif ($report->report_current_status == "Resolved")
                    <span
                        class="inline-flex items-center gap-2 rounded-2xl bg-green-100 px-5 py-3 font-semibold text-green-800 shadow-sm"
                    >
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        Resolved
                    </span>

                @elseif ($report->report_current_status == "Rejected")
                    <span
                        class="inline-flex items-center gap-2 rounded-2xl bg-red-100 px-5 py-3 font-semibold text-red-800 shadow-sm"
                    >
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        Rejected
                    </span>

                @else
                    <span
                        class="inline-flex items-center gap-2 rounded-2xl bg-orange-100 px-5 py-3 font-semibold text-orange-800 shadow-sm"
                    >
                        <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                        For Replacement
                    </span>

                @endif
            </div>
        </div>
    </div>

    @if (session("success"))
        <div
            class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700"
        >
            {{
                session(
                    "success",
                )
            }}
        </div>
    @endif

    <!-- MAIN GRID -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <!-- LEFT SIDE -->
        <div class="space-y-6 xl:col-span-2">
            <!-- REPORT INFORMATION -->
            <div
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900">
                        Report Information
                    </h2>
                    <span class="text-sm text-gray-500">
                        #{{ $report->report_id }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Report ID</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            #{{ $report->report_id }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Date Submitted</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{
                                \Carbon\Carbon::parse(
                                    $report->report_submitted_at,
                                )->format("F d, Y h:i A")
                            }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Problem Category</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{
                                $report->report_category ??
                                    "Unspecified"
                            }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Urgency Level</p>
                        <h3
                            class="font-bold text-lg mt-1 {{ $report->report_urgency_level == 'Urgent' ? 'text-red-600' : 'text-green-600' }}"
                        >
                            {{ $report->report_urgency_level }}
                        </h3>
                    </div>
                </div>
            </div>

            <!-- PROBLEM DESCRIPTION -->
            <div
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-4 text-2xl font-bold text-gray-900">
                    Problem Description
                </h2>

                <div
                    class="rounded-2xl bg-gray-50 p-5 leading-relaxed text-gray-700"
                >
                    {{ $report->report_problem_description }}
                </div>
            </div>

            @if ($report->report_uploaded_image)
                <div
                    class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">
                        Uploaded Evidence
                    </h2>

                    <img
                        src="{{ asset('storage/' . $report->report_uploaded_image) }}"
                        class="max-h-[500px] w-full rounded-2xl border border-gray-200 object-cover"
                    />
                </div>

            @endif

            <!-- EQUIPMENT INFORMATION -->
            <div
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-6 text-2xl font-bold text-gray-900">
                    Equipment Information
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Equipment Name</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{ $report->equipment_name }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Equipment Status</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{ $report->equipment_inventory_status }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Room</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{ $report->room_name }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Building</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{ $report->building_name }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="space-y-6">
            <!-- REPORTER -->
            <div
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-6 text-2xl font-bold text-gray-900">
                    Reporter Information
                </h2>

                <div class="space-y-5">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Full Name</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{ $report->reporter_full_name }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Employee ID</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{ $report->report_reporter_employee_id }}
                        </h3>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Contact Number</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">
                            {{ $report->reporter_contact_number }}
                        </h3>
                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-6 text-2xl font-bold text-gray-900">Actions</h2>

                <div class="space-y-4">
                    <a
                        href="/maintenance/reports/assign/{{ $report->report_id }}"
                        class="block w-full rounded-2xl bg-blue-600 py-4 text-center font-bold text-white transition hover:bg-blue-700"
                    >
                        Assign Personnel
                    </a>

                    <a
                        href="/maintenance/reports/findings/{{ $report->report_id }}"
                        class="block w-full rounded-2xl bg-yellow-500 py-4 text-center font-bold text-black transition hover:bg-yellow-600"
                    >
                        Add Findings
                    </a>

                    <a
                        href="/maintenance/reports/update-status/{{ $report->report_id }}"
                        class="block w-full rounded-2xl bg-green-600 py-4 text-center font-bold text-white transition hover:bg-green-700"
                    >
                        Update Status
                    </a>
                </div>
            </div>

            <!-- TIMELINE -->
            <div
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-6 text-2xl font-bold text-gray-900">
                    Report Timeline
                </h2>

                <div class="space-y-5">
                    <div class="flex gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50"
                        >
                            <i
                                data-lucide="file-text"
                                class="h-5 w-5 text-blue-600"
                            ></i>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-900">
                                Report Submitted
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    \Carbon\Carbon::parse(
                                        $report->report_submitted_at,
                                    )->format("F d, Y h:i A")
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-6 text-2xl font-bold text-gray-900">
                    Related Reports
                </h2>

                @forelse ($relatedReports as $related)
                    <a
                        href="/maintenance/reports/details/{{ $related->report_id }}"
                        class="mb-3 block rounded-2xl bg-gray-50 p-4 font-medium text-gray-700 transition hover:bg-gray-100"
                    >
                        Report #{{ $related->report_id }}
                    </a>

                @empty
                    <p class="text-gray-500">No related reports found.</p>

                @endforelse
            </div>
        </div>
    </div>

@endsection
