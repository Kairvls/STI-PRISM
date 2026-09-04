@extends('layouts.purchaser-layout')

@section('page-title', 'Replacement Requests')
@section('page-subtitle', 'Review and manage equipment replacement requests')

@section('content')

{{-- REPLACEMENT REQUESTS MODULE --}}
<div
    x-data="{
        openModal: null,
        approveModal: null,
        archiveModal: null,
        createRisModal: null,
        rejectModal: {{ old('reject_request_id') ? (int) old('reject_request_id') : 'null' }},
        search: '',
        status: '',

        openApprove(id) {
            this.approveModal = id;
        },

        openArchive(id) {
            this.archiveModal = id;
            this.openModal = null;
        },

        openCreateRis(id) {
            this.createRisModal = id;
        },

        openReject(id) {
            this.rejectModal = id;
            this.$nextTick(() => {
                if (this.$refs.rejectRemarks) {
                    this.$refs.rejectRemarks.focus();
                }
            });
        },

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
>
    @php
        $archiveView = request('view') === 'archive';

        $statusClasses = [
            'Pending' => 'border-amber-200 bg-amber-50 text-amber-700',
            'Approved' => 'border-green-200 bg-green-50 text-green-700',
            'Rejected' => 'border-red-200 bg-red-50 text-red-700',
            'Completed' => 'border-gray-200 bg-gray-100 text-gray-700',
        ];

        // =====================================================
        // RIS STATUS BADGE COLORS
        // =====================================================
        $risStatusClasses = [
            'Draft' => 'border-gray-200 bg-gray-100 text-gray-700',
            'Submitted' => 'border-amber-200 bg-amber-50 text-amber-700',
            'Under Review' => 'border-amber-200 bg-amber-50 text-amber-700',
            'Resubmitted' => 'border-amber-200 bg-amber-50 text-amber-700',
            'Minor Revision' => 'border-yellow-300 bg-yellow-50 text-amber-600',
            'Approved' => 'border-green-200 bg-green-50 text-green-700',
            'Rejected' => 'border-red-200 bg-red-50 text-red-700',
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

    <div class="pur-card mb-6">
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

    <div class="pur-card">
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

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    

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
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 h-9 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select
                        x-model="status"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-4 h-9 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white"
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
                        class="rounded-lg border border-gray-100/80 px-3.5 py-2.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                    >
                        Clear
                    </button>

                    <div
                        class="flex shrink-0 items-center rounded-lg bg-slate-100 h-9 p-1"
                    >
                        <a
                            href="{{ route('purchaser.procurement.replacement-requests') }}"
                            class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold transition
                                {{ !$archiveView
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-400 hover:text-slate-600'
                                }}"
                        >
                            <i data-lucide="folder-open" class="h-3.5 w-3.5"></i>
                            Requests
                        </a>

                        <a
                            href="{{ route('purchaser.procurement.replacement-requests', ['view' => 'archive']) }}"
                            class="flex items-center gap-1.5 rounded-md px-3.5 py-1.5 text-xs font-semibold transition
                                {{ $archiveView
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-400 hover:text-slate-600'
                                }}"
                        >
                            <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                            Archive
                        </a>
                    </div>
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
                        {{-- RIS COLUMN --}}
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RIS</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
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

                            // =====================================================
                            // RIS LINK STATE FOR THIS REQUEST
                            // =====================================================
                            $hasRis = !empty($request->ris_id);
                            $canCreateRis = $request->procurement_request_status === 'Approved' && !$hasRis;
                            $risStatusClass = $risStatusClasses[$request->ris_status ?? ''] ?? 'border-gray-200 bg-gray-100 text-gray-700';
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

                            {{-- RIS CELL --}}
                            <td class="px-5 py-4">
                                @if($hasRis)
                                    <p class="text-xs font-semibold text-gray-800">
                                        {{ $request->ris_form_number ?: 'RIS #' . $request->ris_id }}
                                    </p>
                                    <span class="mt-1 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-medium {{ $risStatusClass }}">
                                        {{ $request->ris_status }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">{{ $canCreateRis ? 'Ready for RIS' : 'Not created' }}</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-gray-700">
                                    {{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('M d, Y') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('h:i A') }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex justify-end items-center gap-1.5">

                                    {{-- VIEW --}}
                                    <button
                                        type="button"
                                        x-on:click="openModal = 'replacement-{{ $request->procurement_request_id }}'"
                                        data-tooltip="View"
                                        aria-label="View"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </button>

                                    {{-- APPROVE / REJECT (Pending only) --}}
                                    @if(
                                        !$request->procurement_request_is_archived
                                        && $request->procurement_request_status === 'Pending'
                                    )
                                        <button
                                            type="button"
                                            x-on:click="openApprove({{ $request->procurement_request_id }})"
                                            data-tooltip="Approve"
                                            aria-label="Approve"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-blue-800"
                                        >
                                            <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                        </button>

                                        <button
                                            type="button"
                                            x-on:click="openReject({{ $request->procurement_request_id }})"
                                            data-tooltip="Reject"
                                            aria-label="Reject"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition hover:bg-red-50"
                                        >
                                            <i data-lucide="thumbs-down" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif

                                    {{-- CREATE RIS / VIEW RIS --}}
                                    @if($canCreateRis)
                                        <button
                                            type="button"
                                            x-on:click="openCreateRis({{ $request->procurement_request_id }})"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]"
                                            title="Create RIS"
                                            aria-label="Create RIS"
                                            data-tooltip="Create RIS"
                                        >
                                            <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                                        </button>
                                    @elseif($hasRis)
                                        <a
                                            href="{{ route('purchaser.ris.index') }}?ris_id={{ $request->ris_id }}"
                                            data-tooltip="View RIS"
                                            aria-label="View RIS"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-green-200 bg-green-50 text-green-700 transition hover:bg-green-100"
                                        >
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif

                                    {{-- ARCHIVE / RESTORE --}}
                                    @if(
                                        !$request->procurement_request_is_archived
                                        && in_array($request->procurement_request_status, ['Approved', 'Rejected', 'Completed'], true)
                                    )
                                        <button
                                            type="button"
                                            x-on:click="openArchive({{ $request->procurement_request_id }})"
                                            data-tooltip="Archive"
                                            aria-label="Archive"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100"
                                        >
                                            <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @elseif($request->procurement_request_is_archived)
                                        <form
                                            method="POST"
                                            action="{{ route('purchaser.procurement.replacement-requests.restore', $request->procurement_request_id) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                data-tooltip="Restore"
                                                aria-label="Restore"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 transition hover:bg-emerald-200"
                                            >
                                                <i data-lucide="archive-restore" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </form>
                                    @endif

                                </div>
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
                                    class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
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

                                        {{-- RIS STATUS BANNER --}}
                                        @if($hasRis)
                                            <div class="mb-5 flex items-start justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-800">
                                                        Linked RIS: {{ $request->ris_form_number ?: '#' . $request->ris_id }}
                                                    </p>
                                                    <p class="mt-1 text-xs leading-5 text-gray-500">
                                                        Procurement paperwork has started for this replacement request.
                                                    </p>
                                                </div>
                                                <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium {{ $risStatusClass }}">
                                                    {{ $request->ris_status }}
                                                </span>
                                            </div>
                                        @elseif($request->procurement_request_status === 'Approved')
                                            <div class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3">
                                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-green-500"></span>
                                                <div>
                                                    <p class="text-sm font-semibold text-green-800">Ready for RIS</p>
                                                    <p class="mt-1 text-xs leading-5 text-green-700">
                                                        This request is approved and does not have an RIS yet.
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

                                    <div class="flex items-center justify-end border-t border-gray-100 bg-gray-50 px-6 py-4">
                                        <button
                                            type="button"
                                            x-on:click="openModal = null"
                                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                                        >
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
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

    {{-- CREATE RIS CONFIRMATION MODAL --}}
    <template x-teleport="body">
        <div
            x-show="createRisModal !== null"
            x-transition.opacity
            x-on:keydown.escape.window="createRisModal = null"
            class="fixed inset-0 z-[1100] flex items-center justify-center bg-gray-950/50 p-4"
            style="display: none;"
        >
            <div
                x-show="createRisModal !== null"
                x-transition
                x-on:click.outside="createRisModal = null"
                class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-950">Create RIS</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Continue to create a Requisition &amp; Issue Slip from request
                            <span class="font-semibold text-gray-700" x-text="'#' + createRisModal"></span>.
                        </p>
                    </div>
                    <button
                        type="button"
                        x-on:click="createRisModal = null"
                        aria-label="Close"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        You will be taken to the RIS page with this approved replacement request ready to use.
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            x-on:click="createRisModal = null"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <a
                            x-bind:href="'{{ route('purchaser.ris.index') }}?replacement_request=' + createRisModal"
                            class="rounded-lg bg-[#FFF200] px-4 py-2 text-sm font-semibold text-black transition hover:bg-[#E6E600]"
                        >
                            Continue to RIS
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- ARCHIVE CONFIRMATION MODAL --}}
    <template x-teleport="body">
        <div
            x-show="archiveModal !== null"
            x-transition.opacity
            x-on:keydown.escape.window="archiveModal = null"
            class="fixed inset-0 z-[1100] flex items-center justify-center bg-gray-950/50 p-4"
            style="display: none;"
        >
            <div
                x-show="archiveModal !== null"
                x-transition
                x-on:click.outside="archiveModal = null"
                class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-950">Archive Replacement Request</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Confirm archiving request
                            <span class="font-semibold text-gray-700" x-text="'#' + archiveModal"></span>.
                            You can restore it later from the Archive tab.
                        </p>
                    </div>
                    <button
                        type="button"
                        x-on:click="archiveModal = null"
                        aria-label="Close"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form
                    method="POST"
                    x-bind:action="'/purchaser/procurement/replacement-requests/' + archiveModal + '/archive'"
                    class="space-y-4 p-5"
                >
                    @csrf

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        This will move the replacement request to <span class="font-semibold">Archive</span>.
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            x-on:click="archiveModal = null"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg border border-slate-800 bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                        >
                            Confirm Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- APPROVE CONFIRMATION MODAL --}}
    <template x-teleport="body">
        <div
            x-show="approveModal !== null"
            x-transition.opacity
            x-on:keydown.escape.window="approveModal = null"
            class="fixed inset-0 z-[1100] flex items-center justify-center bg-gray-950/50 p-4"
            style="display: none;"
        >
            <div
                x-show="approveModal !== null"
                x-transition
                x-on:click.outside="approveModal = null"
                class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-950">Approve Replacement Request</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Confirm approval for request
                            <span class="font-semibold text-gray-700" x-text="'#' + approveModal"></span>.
                            After approval, you can create an RIS to start purchasing.
                        </p>
                    </div>
                    <button
                        type="button"
                        x-on:click="approveModal = null"
                        aria-label="Close"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form
                    method="POST"
                    x-bind:action="'/purchaser/procurement/replacement-requests/' + approveModal + '/approve'"
                    class="space-y-4 p-5"
                >
                    @csrf

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        This will mark the replacement request as <span class="font-semibold">Approved</span>.
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            x-on:click="approveModal = null"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
                        >
                            Confirm Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- REJECT REASON MODAL --}}
    <template x-teleport="body">
        <div
            x-show="rejectModal !== null"
            x-transition.opacity
            x-on:keydown.escape.window="rejectModal = null"
            class="fixed inset-0 z-[1100] flex items-center justify-center bg-gray-950/50 p-4"
            style="display: none;"
        >
            <div
                x-show="rejectModal !== null"
                x-transition
                x-on:click.outside="rejectModal = null"
                class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-950">Reject Replacement Request</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Provide a clear reason before rejecting request
                            <span class="font-semibold text-gray-700" x-text="'#' + rejectModal"></span>.
                        </p>
                    </div>
                    <button
                        type="button"
                        x-on:click="rejectModal = null"
                        aria-label="Close"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form
                    method="POST"
                    x-bind:action="'/purchaser/procurement/replacement-requests/' + rejectModal + '/reject'"
                    class="space-y-4 p-5"
                >
                    @csrf
                    <input type="hidden" name="reject_request_id" x-bind:value="rejectModal">

                    <div>
                        <label for="reject-remarks-input" class="mb-1.5 block text-xs font-medium text-gray-600">
                            Reason for rejection
                        </label>
                        <textarea
                            id="reject-remarks-input"
                            name="remarks"
                            required
                            minlength="8"
                            maxlength="2000"
                            rows="4"
                            placeholder="Explain why this replacement request is being rejected..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 outline-none transition focus:border-gray-300 focus:bg-white"
                            x-ref="rejectRemarks"
                        >{{ old('remarks') }}</textarea>
                        <p class="mt-1.5 text-[11px] text-gray-400">Minimum 8 characters.</p>
                        @error('remarks')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            x-on:click="rejectModal = null"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg border border-red-200 bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                        >
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

@endsection