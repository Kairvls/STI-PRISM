<div class="bg-[#1E293B] rounded-3xl p-6 overflow-x-auto border border-white/5">

    <!-- HEADER -->
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-6">

        <!-- SEARCH -->
        <form method="GET"
              class="flex flex-col lg:flex-row gap-4 w-full">

            <!-- SEARCH INPUT -->
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search report ID, equipment, room, reporter..."
                class="bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-3 w-full lg:w-[420px] outline-none focus:border-blue-500">

            <!-- STATUS FILTER -->
            <select
                name="status"
                class="bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-3 outline-none focus:border-blue-500">

                <option value="">
                    All Status
                </option>

                <option value="Pending"
                    {{ request('status') == 'Pending' ? 'selected' : '' }}>
                    Pending
                </option>

                <option value="Processing"
                    {{ request('status') == 'Processing' ? 'selected' : '' }}>
                    Processing
                </option>

                <option value="Resolved"
                    {{ request('status') == 'Resolved' ? 'selected' : '' }}>
                    Resolved
                </option>

                <option value="Rejected"
                    {{ request('status') == 'Rejected' ? 'selected' : '' }}>
                    Rejected
                </option>

                <option value="For Replacement"
                    {{ request('status') == 'For Replacement' ? 'selected' : '' }}>
                    For Replacement
                </option>

            </select>

            <!-- SEARCH BUTTON -->
            <button
                class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-2xl font-semibold transition">

                Search

            </button>

        </form>

    </div>

    <!-- TABLE -->
    <table class="w-full text-left">

        <!-- TABLE HEADER -->
        <thead>

            <tr class="border-b border-white/10 text-gray-400 text-sm">

                <th class="pb-5 px-3">
                    Report ID
                </th>

                <th class="pb-5 px-3">
                    Reporter
                </th>

                <th class="pb-5 px-3">
                    Room
                </th>

                <th class="pb-5 px-3">
                    Equipment
                </th>

                <th class="pb-5 px-3">
                    Urgency
                </th>

                <th class="pb-5 px-3">
                    Status
                </th>

                <th class="pb-5 px-3">
                    Date Submitted
                </th>

                <th class="pb-5 px-3 text-center">
                    Actions
                </th>

            </tr>

        </thead>

        <!-- TABLE BODY -->
        <tbody>

            @forelse($reports as $report)

            <tr class="border-b border-white/5 hover:bg-[#0F172A] transition duration-200">

                <!-- REPORT ID -->
                <td class="py-5 px-3 font-bold text-white">

                    #{{ $report->report_id }}

                </td>

                <!-- REPORTER -->
                <td class="py-5 px-3">

                    <div>

                        <h1 class="font-semibold">

                            {{ $report->reporter_full_name ?? 'Unknown Reporter' }}

                        </h1>

                    </div>

                </td>

                <!-- ROOM -->
                <td class="py-5 px-3">

                    {{ $report->room_name ?? 'No Assigned Room' }}

                </td>

                <!-- EQUIPMENT -->
                <td class="py-5 px-3">

                    {{ $report->equipment_name ?? 'Unlisted Equipment' }}

                </td>

                <!-- URGENCY -->
                <td class="py-5 px-3">

                    @if($report->report_urgency_level == 'Urgent')

                        <span class="bg-red-500/20 text-red-400 px-4 py-1.5 rounded-full text-xs font-bold">

                            Urgent

                        </span>

                    @else

                        <span class="bg-green-500/20 text-green-400 px-4 py-1.5 rounded-full text-xs font-bold">

                            Non-Urgent

                        </span>

                    @endif

                </td>

                <!-- STATUS -->
                <td class="py-5 px-3">

                    @if($report->report_current_status == 'Pending')

                        <span class="bg-yellow-500/20 text-yellow-400 px-4 py-1.5 rounded-full text-xs font-bold">

                            Pending

                        </span>

                    @elseif($report->report_current_status == 'Processing')

                        <span class="bg-blue-500/20 text-blue-400 px-4 py-1.5 rounded-full text-xs font-bold">

                            Processing

                        </span>

                    @elseif($report->report_current_status == 'Resolved')

                        <span class="bg-green-500/20 text-green-400 px-4 py-1.5 rounded-full text-xs font-bold">

                            Resolved

                        </span>

                    @elseif($report->report_current_status == 'Rejected')

                        <span class="bg-red-500/20 text-red-400 px-4 py-1.5 rounded-full text-xs font-bold">

                            Rejected

                        </span>

                    @else

                        <span class="bg-orange-500/20 text-orange-400 px-4 py-1.5 rounded-full text-xs font-bold">

                            For Replacement

                        </span>

                    @endif

                </td>

                <!-- DATE -->
                <td class="py-5 px-3 text-gray-400 text-sm">

                    {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y h:i A') }}

                </td>

                <!-- ACTION BUTTONS -->
                <td class="py-5 px-3">

                    <div class="flex items-center justify-center gap-2">

                        <!-- VIEW -->
                        <a href="/maintenance/reports/details/{{ $report->report_id }}"
                            class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl text-sm font-semibold transition">

                            View

                        </a>

                        <!-- ASSIGN -->
                        <a href="/maintenance/reports/assign/{{ $report->report_id }}"
                            class="bg-yellow-500 hover:bg-yellow-600 text-black px-4 py-2 rounded-xl text-sm font-semibold transition">

                            Assign

                        </a>

                        <!-- UPDATE -->
                        <a href="/maintenance/reports/update-status/{{ $report->report_id }}"
                            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-xl text-sm font-semibold transition">

                            Update

                        </a>

                    </div>

                </td>

            </tr>

            @empty

            <!-- EMPTY STATE -->
            <tr>

                <td colspan="8"
                    class="text-center py-14">

                    <div class="flex flex-col items-center justify-center">

                        <div class="w-20 h-20 rounded-full bg-[#0F172A] flex items-center justify-center mb-4">

                            <i data-lucide="file-search"
                               class="w-9 h-9 text-gray-500"></i>

                        </div>

                        <h1 class="text-lg font-bold text-gray-300">

                            No Reports Found

                        </h1>

                        <p class="text-gray-500 mt-2">

                            No maintenance reports match the current filters.

                        </p>

                    </div>

                </td>

            </tr>

            @endforelse

        </tbody>

    </table>

    <!-- PAGINATION -->
    <div class="mt-8">

        {{ $reports->links() }}

    </div>

</div>