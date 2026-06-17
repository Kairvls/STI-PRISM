<div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">

    <!-- FILTER BAR -->
    <div class="p-6 bg-gray-50 border-b border-gray-200">

        <form method="GET"
              class="flex flex-col lg:flex-row gap-4">

            <!-- SEARCH -->
            <div class="relative flex-1">

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search report ID, equipment, room, reporter..."
                    class="w-full h-12 pl-4 pr-4 rounded-xl border border-gray-300 bg-white text-gray-700 outline-none focus:ring-4 focus:ring-yellow-100 focus:border-yellow-400">

            </div>

            <!-- STATUS -->
            <select
                name="status"
                class="h-12 px-4 rounded-xl border border-gray-300 bg-white text-gray-700 outline-none focus:ring-4 focus:ring-yellow-100 focus:border-yellow-400">

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

            <!-- BUTTON -->
            <button
                class="h-12 px-8 rounded-xl bg-[#FFF200] text-gray-900 font-semibold hover:bg-yellow-300 transition">

                Search

            </button>

        </form>

    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto">

        <table class="w-full">

            <thead>

                <tr class="bg-gray-50 border-b border-gray-200">

                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Report ID
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Reporter
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Room
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Equipment
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Urgency
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Status
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Date Submitted
                    </th>

                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($reports as $report)

                <tr class="border-b border-gray-100 hover:bg-gray-50 transition">

                    <!-- ID -->
                    <td class="px-6 py-5 font-bold text-gray-900">

                        #{{ $report->report_id }}

                    </td>

                    <!-- REPORTER -->
                    <td class="px-6 py-5">

                        <div>

                            <h4 class="font-semibold text-gray-900">

                                {{ $report->reporter_full_name ?? 'Unknown Reporter' }}

                            </h4>

                        </div>

                    </td>

                    <!-- ROOM -->
                    <td class="px-6 py-5 text-gray-700">

                        {{ $report->room_name ?? 'No Assigned Room' }}

                    </td>

                    <!-- EQUIPMENT -->
                    <td class="px-6 py-5 text-gray-700">

                        {{ $report->equipment_name ?? 'Unlisted Equipment' }}

                    </td>

                    <!-- URGENCY -->
                    <td class="px-6 py-5">

                        @if($report->report_urgency_level == 'Urgent')

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">

                            Urgent

                        </span>

                        @else

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">

                            Non-Urgent

                        </span>

                        @endif

                    </td>

                    <!-- STATUS -->
                    <td class="px-6 py-5">

                        @if($report->report_current_status == 'Pending')

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">

                            Pending

                        </span>

                        @elseif($report->report_current_status == 'Processing')

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">

                            Processing

                        </span>

                        @elseif($report->report_current_status == 'Resolved')

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">

                            Resolved

                        </span>

                        @elseif($report->report_current_status == 'Rejected')

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">

                            Rejected

                        </span>

                        @else

                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">

                            For Replacement

                        </span>

                        @endif

                    </td>

                    <!-- DATE -->
                    <td class="px-6 py-5 text-sm text-gray-500">

                        {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y h:i A') }}

                    </td>

                    <!-- ACTIONS -->
                    <td class="px-6 py-5">

                        <div class="flex items-center justify-center gap-2">

                            <a href="/maintenance/reports/details/{{ $report->report_id }}"
                               class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition">

                                View

                            </a>

                            <a href="/maintenance/reports/assign/{{ $report->report_id }}"
                               class="px-4 py-2 rounded-lg bg-yellow-100 text-yellow-800 text-sm font-medium hover:bg-yellow-200 transition">

                                Assign

                            </a>

                            <a href="/maintenance/reports/update-status/{{ $report->report_id }}"
                               class="px-4 py-2 rounded-lg bg-green-100 text-green-700 text-sm font-medium hover:bg-green-200 transition">

                                Update

                            </a>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="8" class="py-20">

                        <div class="flex flex-col items-center justify-center">

                            <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">

                                <i data-lucide="file-search"
                                   class="w-8 h-8 text-gray-400"></i>

                            </div>

                            <h3 class="text-lg font-semibold text-gray-700">

                                No Reports Found

                            </h3>

                            <p class="text-gray-500 mt-2">

                                No maintenance reports match the current filters.

                            </p>

                        </div>

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <!-- PAGINATION -->

    <div class="p-6 border-t border-gray-200 bg-gray-50">

        {{ $reports->links() }}

    </div>

</div>