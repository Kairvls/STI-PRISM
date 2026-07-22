@extends('layouts.purchaser-layout')

@section('page-title', 'Replacement Requests')
@section('page-subtitle', 'Review and manage equipment replacement requests')

@section('content')

{{-- REPLACEMENT REQUESTS MODULE --}}
<div
    x-data="{
        openModal: null,
        search: '',
        status: '',

        matchesSearch(request) {
            const query = this.search.toLowerCase().trim();

            const matchesText =
                !query ||
                request.id.toString().includes(query) ||
                request.report.toString().includes(query) ||
                request.equipment.toLowerCase().includes(query) ||
                request.room.toLowerCase().includes(query) ||
                request.problem.toLowerCase().includes(query);

            const matchesStatus =
                !this.status ||
                request.status === this.status;

            return matchesText && matchesStatus;
        }
    }"
    x-cloak
>
    @php
        $archiveView = request('view') === 'archive';

        $statusClasses = [
            'Pending' => 'border-amber-200 bg-amber-50 text-amber-700',
            'Approved' => 'border-green-200 bg-green-50 text-green-700',
            'Rejected' => 'border-red-200 bg-red-50 text-red-700',
            'Completed' => 'border-gray-200 bg-gray-100 text-gray-700',
        ];

        $totalRequests = method_exists($replacementRequests, 'total')
            ? $replacementRequests->total()
            : $replacementRequests->count();

        $currentCollection = $replacementRequests->getCollection();

        $pendingCount = $currentCollection->where('procurement_request_status', 'Pending')->count();
        $approvedCount = $currentCollection->where('procurement_request_status', 'Approved')->count();
        $rejectedCount = $currentCollection->where('procurement_request_status', 'Rejected')->count();
        $completedCount = $currentCollection->where('procurement_request_status', 'Completed')->count();
    @endphp

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-gray-900"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Procurement</span>
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-gray-950">
                    {{ $archiveView ? 'Archived Replacement Requests' : 'Replacement Requests' }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                    {{ $archiveView
                        ? 'Review replacement requests that have been moved to the archive.'
                        : 'Review equipment recommended for replacement and manage purchasing decisions.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if($archiveView)
                    <a href="{{ url()->current() }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">
                        Back to Requests
                    </a>
                @else
                    <a href="{{ url()->current() }}?view=archive" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">
                        View Archive
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid grid-cols-2 divide-gray-100 sm:grid-cols-3 lg:grid-cols-5 lg:divide-x">
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $totalRequests }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Total Requests</p>
            </div>

            <div class="px-5 py-5">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $pendingCount }}</p>
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Pending</p>
            </div>

            <div class="px-5 py-5">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $approvedCount }}</p>
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Approved</p>
            </div>

            <div class="px-5 py-5">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $rejectedCount }}</p>
                    <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Rejected</p>
            </div>

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $completedCount }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Completed</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            {{ $archiveView ? 'Archived Records' : 'Request Records' }}
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $totalRequests }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ $archiveView ? 'View and restore archived replacement requests.' : 'Review and manage submitted replacement requests.' }}
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                            />
                        </svg>

                        <input
                            type="text"
                            x-model.debounce.300ms="search"
                            placeholder="Search requests."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select
                        x-model="status"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white"
                    >
                        <option value="">All statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Completed">Completed</option>
                    </select>

                    <button
                        type="button"
                        x-show="search || status"
                        x-on:click="search = ''; status = ''"
                        class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Equipment</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Location</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Urgency</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($replacementRequests as $request)
                        @php
                            $requestStatusClass = $statusClasses[$request->procurement_request_status]
                                ?? 'border-gray-200 bg-gray-100 text-gray-700';

                            $equipmentName = $request->equipment_name
                                ?? $request->report_unlisted_equipment_name
                                ?? 'Unknown Equipment';
                        @endphp

                        <tr
                            x-show="matchesSearch({
                                id: @js($request->procurement_request_id),
                                report: @js($request->report_id ?? ''),
                                equipment: @js($equipmentName),
                                room: @js($request->room_name ?? ''),
                                problem: @js($request->report_problem_description ?? ''),
                                status: @js($request->procurement_request_status)
                            })"
                            class="transition hover:bg-gray-50/70"
                        >
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900">#{{ $request->procurement_request_id }}</p>
                                <p class="mt-1 text-xs text-gray-400">Report #{{ $request->report_id ?? 'N/A' }}</p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-800">{{ $equipmentName }}</p>
                                <p class="mt-1 max-w-xs truncate text-xs text-gray-400">
                                    {{ $request->report_problem_description ?? 'No problem description' }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-gray-600">
                                {{ $request->room_name ?? 'Unknown Room' }}
                            </td>

                            <td class="px-5 py-4">
                                @if(($request->report_urgency_level ?? '') === 'Urgent')
                                    <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                        Urgent
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600">
                                        {{ $request->report_urgency_level ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $requestStatusClass }}">
                                        {{ $request->procurement_request_status }}
                                    </span>

                                    @if($request->procurement_request_is_archived)
                                        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500">
                                            Archived
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-gray-700">
                                    {{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('M d, Y') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('h:i A') }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    x-on:click="openModal = 'replacement-{{ $request->procurement_request_id }}'"
                                    class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                >
                                    View
                                </button>
                            </td>
                        </tr>

                        <template x-teleport="body">
                            <div
                                x-show="openModal === 'replacement-{{ $request->procurement_request_id }}'"
                                x-transition.opacity
                                x-on:keydown.escape.window="openModal = null"
                                class="fixed inset-0 z-[1000] flex items-center justify-center bg-gray-950/50 p-4"
                                style="display: none;"
                            >
                                <div
                                    x-show="openModal === 'replacement-{{ $request->procurement_request_id }}'"
                                    x-transition
                                    x-on:click.outside="openModal = null"
                                    class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
                                >
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h2 class="text-xl font-semibold tracking-tight text-gray-950">
                                                    Replacement Request #{{ $request->procurement_request_id }}
                                                </h2>
                                                <span class="rounded-full border px-2.5 py-1 text-xs font-medium {{ $requestStatusClass }}">
                                                    {{ $request->procurement_request_status }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Report #{{ $request->report_id ?? 'N/A' }} · {{ $request->room_name ?? 'Unknown Room' }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="rounded-lg border border-gray-200 p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-700"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="min-h-0 flex-1 overflow-y-auto p-6">
                                        @if(($request->report_urgency_level ?? '') === 'Urgent')
                                            <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-red-500"></span>
                                                <div>
                                                    <p class="text-sm font-semibold text-red-800">Urgent Replacement</p>
                                                    <p class="mt-1 text-xs leading-5 text-red-600">
                                                        This maintenance report was marked urgent and requires priority review.
                                                    </p>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="grid gap-5 lg:grid-cols-2">
                                            <div class="rounded-xl border border-gray-200 p-5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Equipment</p>
                                                <h3 class="mt-3 text-lg font-semibold text-gray-950">{{ $equipmentName }}</h3>

                                                <div class="mt-5 grid grid-cols-2 gap-4">
                                                    <div>
                                                        <p class="text-xs text-gray-400">Location</p>
                                                        <p class="mt-1 text-sm font-medium text-gray-700">{{ $request->room_name ?? 'Unknown Room' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-400">Urgency</p>
                                                        <p class="mt-1 text-sm font-medium {{ ($request->report_urgency_level ?? '') === 'Urgent' ? 'text-red-600' : 'text-gray-700' }}">
                                                            {{ $request->report_urgency_level ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-400">Report</p>
                                                        <p class="mt-1 text-sm font-medium text-gray-700">#{{ $request->report_id ?? 'N/A' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-400">Submitted</p>
                                                        <p class="mt-1 text-sm font-medium text-gray-700">
                                                            {{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('M d, Y') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rounded-xl border border-gray-200 p-5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Request Information</p>

                                                <div class="mt-4 space-y-4">
                                                    <div>
                                                        <p class="text-xs text-gray-400">Requested By</p>
                                                        <p class="mt-1 text-sm font-medium text-gray-800">
                                                            {{ $request->reporter_full_name ?? 'Unknown Reporter' }}
                                                        </p>
                                                        @if($request->reporter_employee_id)
                                                            <p class="mt-0.5 text-xs text-gray-400">{{ $request->reporter_employee_id }}</p>
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <p class="text-xs text-gray-400">Maintenance Personnel</p>
                                                        <p class="mt-1 text-sm font-medium text-gray-800">
                                                            {{ $request->request_creator_name ?? 'Unknown Personnel' }}
                                                        </p>
                                                    </div>

                                                    @if($request->reporter_contact_number)
                                                        <div>
                                                            <p class="text-xs text-gray-400">Reporter Contact</p>
                                                            <p class="mt-1 text-sm font-medium text-gray-800">{{ $request->reporter_contact_number }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-5 rounded-xl border border-gray-200">
                                            <div class="border-b border-gray-100 px-5 py-4">
                                                <h3 class="text-sm font-semibold text-gray-900">Maintenance Assessment</h3>
                                                <p class="mt-1 text-xs text-gray-400">Reason and evidence provided for equipment replacement.</p>
                                            </div>

                                            <div class="grid gap-5 p-5 md:grid-cols-2">
                                                <div>
                                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Reported Problem</p>
                                                    <p class="mt-2 text-sm leading-6 text-gray-700">
                                                        {{ $request->report_problem_description ?? 'No problem description provided.' }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Replacement Reason</p>
                                                    <p class="mt-2 text-sm leading-6 text-gray-700">
                                                        {{ $request->report_replacement_notes ?? 'No replacement reason provided.' }}
                                                    </p>
                                                </div>
                                            </div>

                                            @if($request->report_replacement_image)
                                                <div class="border-t border-gray-100 p-5">
                                                    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-400">Replacement Evidence</p>
                                                    <a
                                                        href="{{ asset('storage/' . $request->report_replacement_image) }}"
                                                        target="_blank"
                                                        class="block max-w-sm overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
                                                    >
                                                        <img
                                                            src="{{ asset('storage/' . $request->report_replacement_image) }}"
                                                            alt="Replacement evidence"
                                                            class="max-h-64 w-full object-cover"
                                                        >
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            @if(!$request->procurement_request_is_archived)
                                                @if($request->procurement_request_status === 'Pending')
                                                    <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.reject', $request->procurement_request_id) }}">
                                                        @csrf
                                                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                                                            Reject
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.approve', $request->procurement_request_id) }}">
                                                        @csrf
                                                        <button type="submit" class="rounded-lg bg-gray-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                                            Approve Request
                                                        </button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.archive', $request->procurement_request_id) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
                                                        Archive
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('purchaser.procurement.replacement-requests.restore', $request->procurement_request_id) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg bg-gray-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                                        Restore Request
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                                        >
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7h-9m9 5h-9m9 5h-9M5 7h.01M5 12h.01M5 17h.01"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-medium text-gray-700">No replacement requests found</p>
                                <p class="mt-1 text-xs text-gray-400">Replacement requests will appear here when submitted by Maintenance.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($replacementRequests->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $replacementRequests->links() }}
            </div>
        @endif
    </div>
</div>  

@endsection