@extends("layouts.purchaser-layout")


{{-- ===================================================== --}}
{{-- PAGE TITLE --}}
{{-- ===================================================== --}}
{{-- PAGE TITLE --}}
{{-- ===================================================== --}}

@section("page-title", "Procurement for Replacement Requests")


{{-- ===================================================== --}}
{{-- PAGE SUBTITLE --}}
{{-- ===================================================== --}}

@section(
    "page-subtitle",
    "Overview of procurement requests and purchasing activity"
)


{{-- ===================================================== --}}
{{-- PAGE CONTENT --}}
{{-- ===================================================== --}}

@section("content")

{{-- ===================================================== --}}
{{-- PURCHASER REPLACEMENT REQUESTS PAGE --}}
{{-- ===================================================== --}}

<div>

    


    {{-- ===================================================== --}}
    {{-- FILTERS --}}
    {{-- ===================================================== --}}

    <form
        method="GET"
        class="mt-6 flex items-center gap-3"
    >

        {{-- ===================================================== --}}
        {{-- SEARCH HERE --}}
        {{-- ===================================================== --}}

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search request, report, equipment, or room"
            class="h-10 flex-1 rounded-lg border border-gray-300 px-3"
        >


        {{-- ===================================================== --}}
        {{-- STATUS FILTER HERE --}}
        {{-- ===================================================== --}}

        <select
            name="status"
            class="h-10 rounded-lg border border-gray-300 px-3"
        >

            <option value="">
                All Statuses
            </option>

            <option
                value="Pending"
                {{ request('status') === 'Pending' ? 'selected' : '' }}
            >
                Pending
            </option>

            <option
                value="Approved"
                {{ request('status') === 'Approved' ? 'selected' : '' }}
            >
                Approved
            </option>

            <option
                value="Rejected"
                {{ request('status') === 'Rejected' ? 'selected' : '' }}
            >
                Rejected
            </option>

            <option
                value="Completed"
                {{ request('status') === 'Completed' ? 'selected' : '' }}
            >
                Completed
            </option>

        </select>


        {{-- ===================================================== --}}
        {{-- FILTER BUTTON HERE --}}
        {{-- ===================================================== --}}

        <button
            type="submit"
            class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white"
        >
            Search
        </button>

    </form>


    {{-- ===================================================== --}}
    {{-- REPLACEMENT REQUEST TABLE --}}
    {{-- ===================================================== --}}

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="border-b border-gray-200 bg-gray-50">

                    <tr>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                            Request
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                            Report
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                            Equipment
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                            Room
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                            Reason
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                            Status
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">
                            Submitted
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse($replacementRequests as $request)

                        <tr>

                            <td class="px-4 py-4 text-sm">

                                #{{ $request->procurement_request_id }}

                            </td>


                            <td class="px-4 py-4 text-sm">

                                #{{ $request->report_id }}

                            </td>


                            <td class="px-4 py-4 text-sm">

                                {{
                                    $request->equipment_name
                                    ?? $request->report_unlisted_equipment_name
                                    ?? 'Unknown Equipment'
                                }}

                            </td>


                            <td class="px-4 py-4 text-sm">

                                {{ $request->room_name ?? 'Unknown Room' }}

                            </td>


                            <td class="px-4 py-4 text-sm">

                                {{
                                    $request->report_replacement_notes
                                    ?? 'No replacement reason provided.'
                                }}

                            </td>


                            <td class="px-4 py-4 text-sm">

                                {{ $request->procurement_request_status }}

                            </td>


                            <td class="px-4 py-4 text-sm">

                                {{
                                    \Carbon\Carbon::parse(
                                        $request->procurement_request_created_at
                                    )->format('M d, Y h:i A')
                                }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-4 py-12 text-center text-sm text-gray-500"
                            >
                                No replacement requests found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PAGINATION HERE --}}
    {{-- ===================================================== --}}

    <div class="mt-5">

        {{ $replacementRequests->links() }}

    </div>

</div>

@endsection