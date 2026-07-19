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

<div x-data="{ openModal: null }">

    @php($archiveView = request('view') === 'archive')

    


    {{-- ===================================================== --}}
    {{-- FILTERS --}}
    {{-- ===================================================== --}}

    <form
        method="GET"
        class="mt-6 flex items-center gap-3"
    >

        @if($archiveView)
            <input type="hidden" name="view" value="archive">
        @endif

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
                            Action
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

                                @if($request->procurement_request_is_archived)
                                    <div class="mt-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                        Archived
                                    </div>
                                @endif

                            </td>

                            <td class="px-4 py-4 text-sm">
                                <button
                                    type="button"
                                    x-on:click="openModal = 'replacement-{{ $request->procurement_request_id }}'"
                                    class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white"
                                >
                                    View
                                </button>
                            </td>

                            <td class="px-4 py-4 text-sm">

                                {{
                                    \Carbon\Carbon::parse(
                                        $request->procurement_request_created_at
                                    )->format('M d, Y h:i A')
                                }}

                            </td>


                        </tr>

                        {{-- Modal: Replacement Request Details --}}
                        <div
                            x-show="openModal === 'replacement-{{ $request->procurement_request_id }}'"
                            x-cloak
                            class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 p-4"
                        >
                            <div
                                class="mx-auto w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl"
                                x-on:click.outside="openModal = null"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900">
                                            Replacement Request #{{ $request->procurement_request_id }}
                                        </h2>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Report #{{ $request->report_id ?? 'N/A' }} • {{ $request->room_name ?? 'Unknown Room' }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        x-on:click="openModal = null"
                                        class="rounded-full bg-gray-100 p-2 text-gray-700 hover:bg-gray-200"
                                    >
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </button>
                                </div>

                                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs uppercase tracking-wide text-gray-500">Request Details</p>
                                        <dl class="mt-3 space-y-3 text-sm text-gray-700">
                                            <div>
                                                <dt class="font-semibold">Status</dt>
                                                <dd>{{ $request->procurement_request_status }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold">Submitted</dt>
                                                <dd>{{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('M d, Y h:i A') }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold">Equipment</dt>
                                                <dd>{{ $request->equipment_name ?? $request->report_unlisted_equipment_name ?? 'Unknown Equipment' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold">Room</dt>
                                                <dd>{{ $request->room_name ?? 'Unknown Room' }}</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs uppercase tracking-wide text-gray-500">Report Info</p>
                                        <dl class="mt-3 space-y-3 text-sm text-gray-700">
                                            <div>
                                                <dt class="font-semibold">Problem</dt>
                                                <dd>{{ $request->report_problem_description ?? 'No problem description.' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold">Replacement Notes</dt>
                                                <dd>{{ $request->report_replacement_notes ?? 'No replacement notes.' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="font-semibold">Urgency</dt>
                                                <dd>{{ $request->report_urgency_level ?? 'N/A' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <div class="mt-6 space-y-4 rounded-2xl border border-gray-200 bg-white p-4">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Requested By</p>
                                            <p class="mt-1 text-sm text-gray-700">{{ $request->reporter_full_name ?? 'Unknown Reporter' }}</p>
                                            <p class="text-xs text-gray-500">{{ $request->reporter_employee_id ?? '' }}</p>
                                            <p class="text-xs text-gray-500">{{ $request->reporter_contact_number ?? '' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Maintenance</p>
                                            <p class="mt-1 text-sm text-gray-700">{{ $request->request_creator_name ?? 'Unknown Personnel' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-wrap items-center gap-3">
                                    @if(!$request->procurement_request_is_archived)
                                        @if($request->procurement_request_status === 'Pending')
                                            <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.approve', $request->procurement_request_id) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                                    Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.reject', $request->procurement_request_id) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                                    Reject
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.archive', $request->procurement_request_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                                Archive
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.restore', $request->procurement_request_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                                Restore
                                            </button>
                                        </form>
                                    @endif
                                    <button
                                        type="button"
                                        x-on:click="openModal = null"
                                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>

                    @empty

                        <tr>

                            <td
                                colspan="8"
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