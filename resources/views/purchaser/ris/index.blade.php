@extends("layouts.purchaser-layout")

{{-- ===================================================== --}}
{{-- ADDED RIS MODULE: PAGE TITLE --}}
{{-- ===================================================== --}}

@section("page-title", "Requisition and Issue Slip")

{{-- ===================================================== --}}
{{-- ADDED RIS MODULE: PAGE SUBTITLE --}}
{{-- ===================================================== --}}

@section(
    "page-subtitle",
    "Create, submit, print, and monitor RIS records"
)

{{-- ===================================================== --}}
{{-- ADDED RIS MODULE: PAGE CONTENT --}}
{{-- ===================================================== --}}

@section("content")

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: ALERT MESSAGES --}}
    {{-- ===================================================== --}}

    @if(session("success"))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session("success") }}
        </div>
    @endif

    @if(session("error"))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session("error") }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: DASHBOARD SUMMARY --}}
    {{-- ===================================================== --}}

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Total RIS</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $risSummary['total'] }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Draft</p>
            <p class="mt-2 text-3xl font-semibold text-gray-600">{{ $risSummary['draft'] }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Submitted</p>
            <p class="mt-2 text-3xl font-semibold text-blue-600">{{ $risSummary['submitted'] }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Approved</p>
            <p class="mt-2 text-3xl font-semibold text-green-600">{{ $risSummary['approved'] }}</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-2 text-3xl font-semibold text-red-600">{{ $risSummary['rejected'] }}</p>
        </div>

    </div>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: DATABASE LIMITATION NOTE --}}
    {{-- ===================================================== --}}

    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Submitted to Admin means status is still Pending, but Requested By and Requested Date are already filled. prism.sql has no separate Submitted status yet.
    </div>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: FILTERS --}}
    {{-- ===================================================== --}}

    <form
        method="GET"
        class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 lg:grid-cols-5"
    >
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search RIS, request, or item"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm lg:col-span-2"
        >

        <select
            name="status"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm"
        >
            <option value="">All Statuses</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <input
            type="date"
            name="date_from"
            value="{{ request('date_from') }}"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm"
        >

        <input
            type="date"
            name="date_to"
            value="{{ request('date_to') }}"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm"
        >

        <div class="flex gap-2 lg:col-span-5">
            <button
                type="submit"
                class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white"
            >
                Search
            </button>

            <a
                href="{{ route('purchaser.ris.index') }}"
                class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
            >
                Reset
            </a>
        </div>
    </form>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: CREATE RIS FROM REPLACEMENT REQUESTS --}}
    {{-- ===================================================== --}}

    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-900">Eligible Replacement Requests</h3>
            <p class="mt-1 text-xs text-gray-500">Each RIS can include multiple item rows.</p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($eligibleReplacementRequests as $replacementRequest)
                <form
                    method="POST"
                    action="{{ route('purchaser.ris.store') }}"
                    class="grid gap-4 p-4 xl:grid-cols-12"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="procurement_request_id"
                        value="{{ $replacementRequest->procurement_request_id }}"
                    >

                    <div class="xl:col-span-3">
                        <p class="text-sm font-semibold text-gray-900">
                            Request #{{ $replacementRequest->procurement_request_id }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            Report #{{ $replacementRequest->report_id }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $replacementRequest->equipment_name ?? $replacementRequest->report_unlisted_equipment_name ?? 'Unknown Equipment' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $replacementRequest->room_name ?? 'Unknown Room' }}
                        </p>
                    </div>

                    <div class="xl:col-span-3">
                        <label class="text-xs font-medium text-gray-500">Purpose</label>
                        <textarea
                            name="ris_purpose_description"
                            rows="3"
                            required
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >{{ $replacementRequest->report_replacement_notes ?? $replacementRequest->report_problem_description }}</textarea>
                    </div>
                    {{-- ===================================================== --}}
                    {{-- ADDED RIS MODULE: MULTIPLE RIS ITEM INPUT ROWS --}}
                    {{-- ===================================================== --}}

                    <div class="xl:col-span-6">
                        <label class="text-xs font-medium text-gray-500">Items</label>
                        <div class="mt-1 overflow-x-auto">
                            <table class="w-full min-w-[640px] border border-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                                    <tr>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Item</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Qty Requested</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left">Unit Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($itemIndex = 0; $itemIndex < 5; $itemIndex++)
                                        <tr>
                                            <td class="border border-gray-200 p-2">
                                                <input type="text" name="items[{{ $itemIndex }}][description]" value="{{ $itemIndex === 0 ? ($replacementRequest->equipment_name ?? $replacementRequest->report_unlisted_equipment_name ?? 'Replacement item') : '' }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                            </td>
                                            <td class="w-40 border border-gray-200 p-2">
                                                <input type="number" name="items[{{ $itemIndex }}][quantity_requested]" min="1" value="{{ $itemIndex === 0 ? 1 : '' }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                            </td>
                                            <td class="w-44 border border-gray-200 p-2">
                                                <input type="number" name="items[{{ $itemIndex }}][unit_cost]" min="0" step="0.01" value="{{ $itemIndex === 0 ? '0.00' : '' }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex items-end xl:col-span-1">
                        <button type="submit" class="h-10 w-full rounded-lg bg-blue-600 px-4 text-sm font-medium text-white">
                            Create
                        </button>
                    </div>
                </form>
            @empty
                <div class="px-4 py-10 text-center text-sm text-gray-500">
                    No eligible replacement requests found.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: RIS LIST --}}
    {{-- ===================================================== --}}

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px]">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">RIS No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Request</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Equipment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Purpose</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Submitted</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Created</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($risRecords as $ris)
                        <tr>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                Request #{{ $ris->procurement_request_id ?? 'N/A' }}<br>
                                <span class="text-xs text-gray-400">Report #{{ $ris->report_id ?? 'N/A' }}</span>
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? 'Unknown Equipment' }}
                            </td>

                            <td class="max-w-xs px-4 py-4 text-sm text-gray-600">
                                {{ $ris->ris_purpose_description ?? 'No purpose provided.' }}
                            </td>

                            <td class="px-4 py-4 text-sm">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $ris->ris_status === 'Approved' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $ris->ris_status === 'Rejected' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $ris->ris_status === 'Pending' && $ris->ris_requested_by_date ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $ris->ris_status === 'Pending' && !$ris->ris_requested_by_date ? 'bg-gray-100 text-gray-700' : '' }}
                                ">
                                    {{ $ris->ris_status === 'Pending' && $ris->ris_requested_by_date ? 'Submitted to Admin' : $ris->ris_status }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('M d, Y') : 'Not submitted' }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $ris->ris_created_at ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y') : 'N/A' }}
                            </td>

                            <td class="px-4 py-4 text-sm">
                                <div class="flex gap-2">
                                    @if($ris->ris_status === 'Pending' && !$ris->ris_requested_by_date)
                                        <form
                                            method="POST"
                                            action="{{ route('purchaser.ris.submit', $ris->ris_id) }}"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white"
                                            >
                                                Submit
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('purchaser.ris.print', $ris->ris_id) }}" target="_blank" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">Print Form</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="px-4 py-12 text-center text-sm text-gray-500"
                            >
                                No RIS records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- ADDED RIS MODULE: PAGINATION --}}
    {{-- ===================================================== --}}

    <div>
        {{ $risRecords->links() }}
    </div>

</div>

@endsection


