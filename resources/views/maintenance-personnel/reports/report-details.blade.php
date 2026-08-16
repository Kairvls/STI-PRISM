@extends("layouts.maintenance-layout")

@section("title", "Report #" . $report->report_id)

@section("content")

@php
    $statusClasses = match (
        $report->report_current_status
    ) {
        'Pending' =>
            'border-amber-200 bg-amber-50 text-amber-700',

        'Processing' =>
            'border-blue-200 bg-blue-50 text-blue-700',

        'Resolved' =>
            'border-emerald-200 bg-emerald-50 text-emerald-700',

        'Rejected' =>
            'border-red-200 bg-red-50 text-red-700',

        'For Replacement' =>
            'border-orange-200 bg-orange-50 text-orange-700',

        default =>
            'border-gray-200 bg-gray-50 text-gray-700',
    };

    $statusDotClasses = match (
        $report->report_current_status
    ) {
        'Pending' =>
            'bg-amber-500',

        'Processing' =>
            'bg-blue-500',

        'Resolved' =>
            'bg-emerald-500',

        'Rejected' =>
            'bg-red-500',

        'For Replacement' =>
            'bg-orange-500',

        default =>
            'bg-gray-400',
    };
@endphp

    {{-- ===================================================== --}}
    {{-- REPORT DETAILS PAGE --}}
    {{-- MODERN MINIMALIST DESIGN --}}
    {{-- ===================================================== --}}

    <div>

        {{-- ================================================= --}}
        {{-- PAGE HEADER --}}
        {{-- ================================================= --}}

        <header class="mb-6">

            {{-- BREADCRUMB --}}

            <div
                class="mb-4 flex items-center gap-2 text-sm text-gray-400"
            >
                <a
                    href="{{ url('/maintenance/reports') }}"
                    class="transition hover:text-gray-700"
                >
                    Reports
                </a>

                <i
                    data-lucide="chevron-right"
                    class="h-4 w-4"
                ></i>

                <span class="font-medium text-gray-600">
                    Report #{{ $report->report_id }}
                </span>

                
            </div>


            {{-- ================================================= --}}
            {{-- REPORT IDENTITY --}}
            {{-- ================================================= --}}

            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
            >

                <div class="min-w-0">

                    {{-- META --}}

                    


                    {{-- TITLE --}}

                    <h1
                        class="text-3xl font-bold tracking-tight text-gray-950 sm:text-4xl"
                    >
                        {{
                            $report->equipment_name
                            ?? $report->report_unlisted_equipment_name
                            ?? $report->report_suggested_issue
                            ?? "Reported Issue"
                        }}
                    </h1>


                    {{-- SUBMITTED DATE --}}

                    <div
                        class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500"
                    >

                        <span class="inline-flex items-center gap-2">
                            <i
                                data-lucide="calendar"
                                class="h-4 w-4"
                            ></i>

                            Submitted
                            {{
                                \Carbon\Carbon::parse(
                                    $report->report_submitted_at
                                )->format("M d, Y")
                            }}
                        </span>

                        <span class="inline-flex items-center gap-2">
                            <i
                                data-lucide="clock-3"
                                class="h-4 w-4"
                            ></i>

                            {{
                                \Carbon\Carbon::parse(
                                    $report->report_submitted_at
                                )->format("h:i A")
                            }}
                        </span>

                    </div>

                </div>


                

            </div>

        </header>


        {{-- ================================================= --}}
        {{-- SUCCESS MESSAGE --}}
        {{-- ================================================= --}}

        @if (session("success"))

            <div
                class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-800"
            >
                <i
                    data-lucide="circle-check"
                    class="mt-0.5 h-4 w-4 shrink-0"
                ></i>

                <span>
                    {{ session("success") }}
                </span>
            </div>

        @endif


        {{-- ================================================= --}}
        {{-- MAIN LAYOUT --}}
        {{-- ================================================= --}}

        <div
            class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
        >

            {{-- ================================================= --}}
            {{-- LEFT COLUMN --}}
            {{-- ================================================= --}}

            <main class="min-w-0">

                {{-- ================================================= --}}
                {{-- PRIMARY REPORT PANEL --}}
                {{-- ================================================= --}}

                <section
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white"
                >

                    {{-- PANEL HEADER --}}

                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-6 py-5"
                    >
                        <div>
                            <h2
                                class="text-base font-semibold text-gray-900"
                            >
                                Report Details
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Issue, equipment and location information.
                            </p>
                        </div>

                        <i
                            data-lucide="file-text"
                            class="h-5 w-5 text-gray-400"
                        ></i>
                    </div>


                    {{-- ================================================= --}}
                    {{-- ISSUE DETAILS --}}
                    {{-- ================================================= --}}

                    <div class="px-6 py-6">

                        {{-- SUGGESTED ISSUE --}}

                        <div
                            class="border-b border-gray-100 pb-6"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Suggested Issue
                            </p>

                            <p
                                class="mt-2 text-lg font-semibold text-gray-900"
                            >
                                {{
                                    $report->report_suggested_issue
                                    ?? "Not specified"
                                }}
                            </p>
                        </div>


                        {{-- PROBLEM DESCRIPTION --}}

                        <div
                            class="border-b border-gray-100 py-6"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Problem Description
                            </p>

                            <p
                                class="mt-2 whitespace-pre-line text-sm leading-7 text-gray-600"
                            >{{
                                $report->report_problem_description
                                ?? "No description provided."
                            }}</p>
                        </div>


                        {{-- ================================================= --}}
                        {{-- EQUIPMENT + LOCATION --}}
                        {{-- ================================================= --}}

                        <div
                            class="grid grid-cols-1 gap-6 border-b border-gray-100 py-6 md:grid-cols-2"
                        >

                            {{-- EQUIPMENT --}}

                            <div>

                                <div
                                    class="mb-4 flex items-center gap-2"
                                >
                                    <i
                                        data-lucide="monitor"
                                        class="h-4 w-4 text-gray-400"
                                    ></i>

                                    <p
                                        class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                                    >
                                        Equipment
                                    </p>
                                </div>

                                <p
                                    class="font-semibold text-gray-900"
                                >
                                    {{
                                        $report->equipment_name
                                        ?? $report->report_unlisted_equipment_name
                                        ?? "Not specified"
                                    }}
                                </p>

                                @if ($report->equipment_inventory_status)

                                    <p
                                        class="mt-1 text-sm text-gray-500"
                                    >
                                        {{
                                            $report->equipment_inventory_status
                                        }}
                                    </p>

                                @endif

                            </div>


                            {{-- LOCATION --}}

                            <div>

                                <div
                                    class="mb-4 flex items-center gap-2"
                                >
                                    <i
                                        data-lucide="map-pin"
                                        class="h-4 w-4 text-gray-400"
                                    ></i>

                                    <p
                                        class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                                    >
                                        Location
                                    </p>
                                </div>

                                <p
                                    class="font-semibold text-gray-900"
                                >
                                    {{
                                        $report->room_name
                                        ?? "Room not specified"
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-sm text-gray-500"
                                >
                                    @if ($report->floor_level)
                                        {{ $report->floor_level }}
                                    @endif

                                    @if (
                                        $report->floor_level
                                        && $report->building_name
                                    )
                                        ·
                                    @endif

                                    @if ($report->building_name)
                                        {{ $report->building_name }}
                                    @endif
                                </p>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- ADDITIONAL REPORT INFORMATION --}}
                        {{-- ================================================= --}}

                        <div class="pt-6">

                            <p
                                class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-400"
                            >
                                Additional Information
                            </p>

                            <dl
                                class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2"
                            >

                                <div>
                                    <dt class="text-sm text-gray-500">
                                        Problem Category
                                    </dt>

                                    <dd
                                        class="mt-1 text-sm font-semibold text-gray-900"
                                    >
                                        {{
                                            $report->report_category
                                            ?? "Unspecified"
                                        }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm text-gray-500">
                                        Urgency
                                    </dt>

                                    <!--<dd
                                        class="mt-1 text-sm font-semibold {{ $report->report_urgency_level === 'Urgent' ? 'text-red-600' : 'text-gray-900' }}"
                                    >
                                        {{
                                            $report->report_urgency_level
                                            ?? "Unspecified"
                                        }}
                                    </dd>-->

                                    {{-- URGENCY --}}

                                        @if ($report->report_urgency_level === "Urgent")

                                            <span
                                                class="inline-flex items-center gap-1.5 mt-1 -ml-1 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-red-500"
                                                ></span>

                                                Urgent
                                            </span>

                                        @else

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-600"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-gray-400"
                                                ></span>

                                                {{ $report->report_urgency_level ?? "Normal" }}
                                            </span>

                                        @endif
                                </div>

                            </dl>

                        </div>

                    </div>

                </section>


                {{-- ================================================= --}}
                {{-- EVIDENCE --}}
                {{-- ================================================= --}}

                @if ($report->report_uploaded_image)

                    <section
                        class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white"
                    >

                        <div
                            class="flex items-center justify-between border-b border-gray-100 px-6 py-5"
                        >
                            <div>
                                <h2
                                    class="text-base font-semibold text-gray-900"
                                >
                                    Uploaded Evidence
                                </h2>

                                <p class="mt-1 text-sm text-gray-500">
                                    Image attached to this report.
                                </p>
                            </div>

                            <i
                                data-lucide="image"
                                class="h-5 w-5 text-gray-400"
                            ></i>
                        </div>

                        <div class="p-4">

                            <div
                                class="overflow-hidden rounded-xl bg-gray-50"
                            >
                                <img
                                    src="{{ asset(
                                        'storage/'
                                        . $report->report_uploaded_image
                                    ) }}"
                                    alt="Evidence for report #{{ $report->report_id }}"
                                    class="max-h-[520px] w-full object-contain"
                                >
                            </div>

                        </div>

                    </section>

                @endif

            </main>


            {{-- ================================================= --}}
            {{-- RIGHT SIDEBAR --}}
            {{-- ================================================= --}}

            <aside class="space-y-5">

                {{-- ================================================= --}}
                {{-- REPORTER --}}
                {{-- ================================================= --}}

                <section
                    class="rounded-2xl border border-gray-200 bg-white p-5"
                >

                    <div
                        class="mb-5 flex items-center justify-between"
                    >
                        <h2
                            class="text-sm font-semibold text-gray-900"
                        >
                            Reporter
                        </h2>

                        <i
                            data-lucide="user-round"
                            class="h-4 w-4 text-gray-400"
                        ></i>
                    </div>


                    {{-- REPORTER IDENTITY --}}

                    <div class="mb-5">

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-600"
                        >
                            {{
                                strtoupper(
                                    substr(
                                        $report->reporter_full_name
                                        ?? "R",
                                        0,
                                        1
                                    )
                                )
                            }}
                        </div>

                        <p
                            class="mt-3 font-semibold text-gray-900"
                        >
                            {{
                                $report->reporter_full_name
                                ?? "Unknown Reporter"
                            }}
                        </p>

                        <p class="mt-1 text-sm text-gray-500">
                            Employee ID:
                            {{
                                $report->report_reporter_employee_id
                                ?? "Unavailable"
                            }}
                        </p>

                    </div>


                    {{-- REPORTER CONTACT --}}

                    <div
                        class="border-t border-gray-100 pt-4"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                        >
                            Contact Number
                        </p>

                        <p
                            class="mt-1.5 text-sm font-medium text-gray-700"
                        >
                            {{
                                $report->reporter_contact_number
                                ?? "Not provided"
                            }}
                        </p>
                    </div>

                </section>


                {{-- ================================================= --}}
                {{-- ACTIONS --}}
                {{-- ================================================= --}}

                {{-- ===================================================== --}}
                {{-- REPORT ACTION --}}
                {{-- ===================================================== --}}

                {{-- ===================================================== --}}
                {{-- REPORT ACTION --}}
                {{-- ===================================================== --}}

                <section
                    class="rounded-2xl border border-gray-200 bg-white p-5"
                >

                    {{-- ================================================= --}}
                    {{-- HEADER + CURRENT STATUS --}}
                    {{-- ================================================= --}}

                    <div
                        class="mb-5 flex items-start justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">
                                Report Action
                            </h2>

                            <p class="mt-1 text-xs leading-5 text-gray-500">
                                Update the current maintenance status.
                            </p>
                        </div>


                        {{-- ================================================= --}}
                        {{-- CURRENT STATUS --}}
                        {{-- ================================================= --}}

                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full {{ $statusDotClasses }}"
                            ></span>

                            {{ $report->report_current_status }}
                        </span>
                    </div>


                    {{-- ================================================= --}}
                    {{-- UPDATE STATUS --}}
                    {{-- ================================================= --}}

                    @if (
                        in_array(
                            $report->report_current_status,
                            [
                                "Pending",
                                "Processing"
                            ]
                        )
                    )

                        <button
                            type="button"
                            onclick="openUpdateStatusModal()"
                            class="flex w-full items-center justify-between rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800"
                        >
                            <span class="flex items-center gap-2.5">

                                <i
                                    data-lucide="refresh-cw"
                                    class="h-4 w-4"
                                ></i>

                                Update Status

                            </span>

                            <i
                                data-lucide="chevron-right"
                                class="h-4 w-4 text-gray-400"
                            ></i>
                        </button>

                    @else

                        {{-- ================================================= --}}
                        {{-- FINAL STATUS --}}
                        {{-- ================================================= --}}

                        <div
                            class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500"
                        >
                            This report has reached a final status.
                        </div>

                    @endif

                </section>


                {{-- ================================================= --}}
                {{-- REPORT TIMELINE --}}
                {{-- ================================================= --}}

                <section
                    class="rounded-2xl border border-gray-200 bg-white p-5"
                >

                    <div
                        class="mb-5 flex items-center justify-between"
                    >
                        <h2
                            class="text-sm font-semibold text-gray-900"
                        >
                            Timeline
                        </h2>

                        <i
                            data-lucide="history"
                            class="h-4 w-4 text-gray-400"
                        ></i>
                    </div>


                    {{-- ================================================= --}}
                    {{-- SUBMITTED EVENT --}}
                    {{-- ================================================= --}}

                    <div class="relative pl-7">

                        <span
                            class="absolute left-[5px] top-2 h-[calc(100%-8px)] w-px bg-gray-200"
                        ></span>

                        <div class="relative pb-1">

                            <span
                                class="absolute -left-7 top-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-gray-900 ring-4 ring-white"
                            ></span>

                            <p
                                class="text-sm font-semibold text-gray-900"
                            >
                                Report Submitted
                            </p>

                            <p
                                class="mt-1 text-xs leading-5 text-gray-500"
                            >
                                {{
                                    \Carbon\Carbon::parse(
                                        $report->report_submitted_at
                                    )->format(
                                        "M d, Y · h:i A"
                                    )
                                }}
                            </p>

                        </div>

                    </div>

                </section>

            </aside>

        </div>

    </div>

    {{-- ===================================================== --}}
{{-- UPDATE STATUS MODAL --}}
{{-- ===================================================== --}}

<div
    id="updateStatusModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-[#0b1220]/70 p-4"
    aria-hidden="true"
    onclick="closeUpdateStatusModal()"
>
    {{-- ================================================= --}}
    {{-- MODAL CONTAINER --}}
    {{-- ================================================= --}}

    <div
        class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
        onclick="event.stopPropagation()"
    >

        {{-- ================================================= --}}
        {{-- HEADER --}}
        {{-- ================================================= --}}

        <div
            class="flex items-start justify-between border-b border-gray-100 px-6 py-5"
        >
            <div>
                <p
                    class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                >
                    Report #{{ $report->report_id }}
                </p>

                <h2
                    class="mt-1 text-xl font-semibold text-gray-900"
                >
                    Update Status
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Change the current maintenance status.
                </p>
            </div>

            <button
                type="button"
                onclick="closeUpdateStatusModal()"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                aria-label="Close"
            >
                <i
                    data-lucide="x"
                    class="h-5 w-5"
                ></i>
            </button>
        </div>


        {{-- ================================================= --}}
        {{-- FORM --}}
        {{-- ================================================= --}}

        <form
            action="{{ url(
                '/maintenance/reports/update-status/'
                . $report->report_id
            ) }}"
            method="POST"
        >
            @csrf

            <div class="space-y-6 px-6 py-6">

                {{-- ================================================= --}}
                {{-- CURRENT STATUS --}}
                {{-- ================================================= --}}

                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                    >
                        Current Status
                    </p>

                    <div class="mt-2 flex items-center gap-2">

                        <span
                            class="h-2 w-2 rounded-full {{ $statusDotClasses }}"
                        ></span>

                        <span
                            class="text-sm font-semibold text-gray-900"
                        >
                            {{ $report->report_current_status }}
                        </span>

                    </div>
                </div>


                {{-- ================================================= --}}
                {{-- STATUS SELECTION --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        for="reportStatus"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Change Status
                    </label>

                    <select
                        id="reportStatus"
                        name="status"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-gray-400 focus:ring-4 focus:ring-gray-100"
                    >

                        <option value="" disabled selected>
                            Select new status
                        </option>

                        {{-- ========================================= --}}
                        {{-- PENDING --}}
                        {{-- ========================================= --}}

                        @if (
                            $report->report_current_status ===
                            "Pending"
                        )

                            <option value="Processing">
                                Start Processing
                            </option>

                            <option value="Rejected">
                                Reject Report
                            </option>


                        {{-- ========================================= --}}
                        {{-- PROCESSING --}}
                        {{-- ========================================= --}}

                        @elseif (
                            $report->report_current_status ===
                            "Processing"
                        )

                            <option value="Resolved">
                                Mark as Resolved
                            </option>

                            <option value="For Replacement">
                                Mark for Replacement
                            </option>

                            <option value="Rejected">
                                Reject Report
                            </option>

                        @endif

                    </select>

                    {{-- ================================================= --}}
                    {{-- WORKFLOW INFORMATION --}}
                    {{-- ================================================= --}}

                    @if (
                        $report->report_current_status ===
                        "Pending"
                    )

                        <div
                            class="mt-3 flex items-start gap-2 rounded-xl bg-gray-50 px-3.5 py-3 text-xs leading-5 text-gray-500"
                        >
                            <i
                                data-lucide="info"
                                class="mt-0.5 h-4 w-4 shrink-0"
                            ></i>

                            <p>
                                Starting this report will make you
                                responsible for handling it.
                            </p>
                        </div>

                    @endif

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- FOOTER --}}
            {{-- ================================================= --}}

            <div
                class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/70 px-6 py-4"
            >

                <button
                    type="button"
                    onclick="closeUpdateStatusModal()"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Cancel
                </button>

                @if (
                    in_array(
                        $report->report_current_status,
                        [
                            "Pending",
                            "Processing"
                        ]
                    )
                )

                    <button
                        type="submit"
                        class="rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800"
                    >
                        Update Status
                    </button>

                @endif

            </div>

        </form>

    </div>
</div>

<script>
    // =====================================================
    // OPEN UPDATE STATUS MODAL
    // =====================================================

    function openUpdateStatusModal() {

        const modal =
            document.getElementById(
                'updateStatusModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.classList.add(
            'overflow-hidden'
        );
    }


    // =====================================================
    // CLOSE UPDATE STATUS MODAL
    // =====================================================

    function closeUpdateStatusModal() {

        const modal =
            document.getElementById(
                'updateStatusModal'
            );

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.classList.remove(
            'overflow-hidden'
        );
    }


    // =====================================================
    // CLOSE MODAL WITH ESCAPE KEY
    // =====================================================

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeUpdateStatusModal();
            }

        }
    );
</script>

@endsection