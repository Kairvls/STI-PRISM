@extends($procurementLayout ?? 'layouts.purchaser-layout')

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
            'Accepted' => 'border-sky-200 bg-sky-50 text-sky-700',
            'Minor Revision' => 'border-yellow-300 bg-yellow-50 text-amber-600',
            'Forwarded to President' => 'border-blue-200 bg-blue-50 text-blue-700',
            'Directly Approved' => 'border-slate-200 bg-slate-50 text-slate-600',
            'Approved' => 'border-green-200 bg-green-50 text-green-700',
            'Approved by the President' => 'border-green-200 bg-green-50 text-green-700',
            'Rejected' => 'border-red-200 bg-red-50 text-red-700',
            'Rejected by the President' => 'border-slate-500 bg-slate-800 text-slate-100',
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

    <div class="mb-6">
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                [
                    'label' => 'Pending',
                    'value' => $pendingCount,
                ],
                [
                    'label' => 'Approved',
                    'value' => $approvedCount,
                ],
                [
                    'label' => 'Rejected',
                    'value' => $rejectedCount,
                ],
            ],
        ])
    </div>

    <div class="pur-card">
        @if(!empty($attentionFocus))
            <div class="px-5 pt-5">
                @include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $totalRequests])
            </div>
        @endif
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
                            href="{{ route(($pp ?? 'purchaser').'.procurement.replacement-requests') }}"
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
                            href="{{ route(($pp ?? 'purchaser').'.procurement.replacement-requests', ['view' => 'archive']) }}"
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
            <table class="w-full table-fixed text-sm">
                <colgroup>
                    <col class="w-[20%]">
                    <col class="w-[22%]">
                    <col class="w-[9%]">
                    <col class="w-[11%]">
                    <col class="w-[12%]">
                    <col class="w-[11%]">
                    <col class="w-[15%]">
                </colgroup>
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Equipment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Urgency</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RIS</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($replacementRequests as $request)
                        @php
                            $requestStatusClass = $statusClasses[$request->procurement_request_status]
                                ?? 'border-gray-200 bg-gray-100 text-gray-700';

                            $equipmentName = $request->equipment_display
                                ?? $request->equipment_name
                                ?? $request->report_unlisted_equipment_name
                                ?? 'Unknown Equipment';
                            $lineItems = collect($request->line_items ?? []);
                            $roomLabel = $request->room_display ?? ($request->room_name ?? 'Unknown Room');
                            $searchEquipment = trim(implode(' ', $lineItems->map(function ($line) {
                                return trim(($line->display_name ?? '').' '.($line->room_name ?? ''));
                            })->all()).' '.$equipmentName.' '.$roomLabel);

                            $hasRis = !empty($request->ris_id);
                            $canCreateRis = $request->procurement_request_status === 'Approved' && !$hasRis;
                            $requestCode = \App\Support\ReplacementRequestCode::code($request);
                            $reportCode = \App\Support\ReportGrouping::ticketCode(
                                $request->report_id ?? null,
                                $request->report_submitted_at ?? null
                            );
                            $risStatusLabel = $hasRis
                                ? \App\Support\RisWorkflow::statusLabel($request)
                                : '';
                            $risStatusClass = $risStatusClasses[$request->ris_status ?? ''] ?? 'border-gray-200 bg-gray-100 text-gray-700';

                            $firstEquipment = $lineItems->isNotEmpty()
                                ? ($lineItems->first()->display_name ?? $equipmentName)
                                : $equipmentName;
                            $extraEquipmentCount = max(0, $lineItems->count() - 1);
                            $equipmentTooltipLines = $lineItems->map(function ($line) {
                                $name = $line->display_name ?? 'Equipment';
                                $room = $line->room_name ?: 'Unknown room';
                                $ticket = \App\Support\ReportGrouping::ticketCode(
                                    $line->report_id ?? null,
                                    $line->report_submitted_at ?? null
                                );

                                return $name.' · '.$room.' · '.$ticket;
                            })->values()->all();
                            $uniqueRooms = $lineItems
                                ->map(fn ($line) => trim((string) ($line->room_name ?? '')))
                                ->filter()
                                ->unique()
                                ->values();
                            $locationTooltip = $uniqueRooms->isNotEmpty()
                                ? $uniqueRooms->implode("\n")
                                : $roomLabel;
                        @endphp

                        <tr
                            x-show="matchesSearch({
                                id: @js($requestCode),
                                report: @js($reportCode),
                                equipment: @js($searchEquipment),
                                room: @js($roomLabel),
                                problem: @js($request->report_problem_description ?? ''),
                                status: @js($request->procurement_request_status)
                            })"
                            class="transition hover:bg-gray-50/70"
                        >
                            <td class="px-4 py-3.5 align-middle">
                                <p class="truncate font-semibold text-gray-900" title="{{ $requestCode }} · Request #{{ $request->procurement_request_id }}">{{ $requestCode }}</p>
                                @if($lineItems->count() > 1)
                                    <p class="mt-1 truncate text-xs text-gray-400">{{ $lineItems->count() }} items · {{ collect($request->report_ids ?? [])->count() ?: 1 }} reports</p>
                                @else
                                    <p class="mt-1 truncate text-xs text-gray-400" title="Report #{{ $request->report_id ?? 'N/A' }}">{{ $reportCode }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 align-middle">
                                @if($extraEquipmentCount > 0)
                                    <div class="group relative inline-flex max-w-full">
                                        <p class="truncate cursor-help font-medium text-gray-800 underline decoration-dotted decoration-gray-300 underline-offset-2">
                                            {{ $firstEquipment }}
                                            <span class="text-gray-500">+{{ $extraEquipmentCount }}</span>
                                        </p>
                                        <div
                                            class="pointer-events-none absolute left-0 top-full z-30 mt-2 hidden w-72 rounded-xl border border-gray-200 bg-white p-3 text-left shadow-lg group-hover:block"
                                            role="tooltip"
                                        >
                                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                                {{ $lineItems->count() }} items on this request
                                            </p>
                                            <ul class="space-y-1.5">
                                                @foreach($equipmentTooltipLines as $tooltipLine)
                                                    <li class="text-xs leading-5 text-gray-700">{{ $tooltipLine }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @else
                                    <p class="truncate font-medium text-gray-800" title="{{ $firstEquipment }}">{{ $firstEquipment }}</p>
                                @endif
                                <p class="mt-1 truncate text-xs text-gray-400" title="{{ $locationTooltip }}">{{ $roomLabel }}</p>
                            </td>

                            <td class="px-4 py-3.5 align-middle">
                                @if(($request->report_urgency_level ?? '') === 'Urgent')
                                    <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                        Urgent
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600">
                                        Standard
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 align-middle">
                                <span class="inline-flex max-w-full truncate rounded-full border px-2.5 py-1 text-xs font-medium {{ $requestStatusClass }}">
                                    {{ $request->procurement_request_status }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5 align-middle">
                                @if($hasRis)
                                    <p class="truncate text-xs font-semibold text-gray-800" title="{{ \App\Support\RisWorkflow::formNumber($request) }}">
                                        {{ \App\Support\RisWorkflow::formNumber($request) }}
                                    </p>
                                    <p class="mt-1 truncate text-[11px] text-gray-500" title="{{ $risStatusLabel }}">{{ $risStatusLabel }}</p>
                                @else
                                    <span class="text-xs text-gray-400">{{ $canCreateRis ? 'Ready for RIS' : 'Not created' }}</span>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 align-middle">
                                <p class="truncate text-sm text-gray-700" title="{{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('M d, Y h:i A') }}">
                                    {{ \Carbon\Carbon::parse($request->procurement_request_created_at)->format('M d, Y') }}
                                </p>
                            </td>

                            <td class="px-4 py-3.5 align-middle">
                                <div class="flex flex-wrap items-center justify-center gap-1.5">

                                    <button
                                        type="button"
                                        x-on:click="openModal = 'replacement-{{ $request->procurement_request_id }}'"
                                        data-tooltip="View"
                                        aria-label="View"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </button>

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

                                    @if($canCreateRis)
                                        <button
                                            type="button"
                                            x-on:click="openCreateRis({{ $request->procurement_request_id }})"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                            title="Create RIS"
                                            aria-label="Create RIS"
                                            data-tooltip="Create RIS"
                                        >
                                            <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @elseif($hasRis)
                                        <a
                                            href="{{ route(($pp ?? 'purchaser').'.ris.index') }}?ris_id={{ $request->ris_id }}"
                                            data-tooltip="View RIS"
                                            aria-label="View RIS"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-green-200 bg-green-50 text-green-700 transition hover:bg-green-100"
                                        >
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif

                                    @if(
                                        !$request->procurement_request_is_archived
                                        && in_array($request->procurement_request_status, ['Approved', 'Rejected', 'Completed'], true)
                                    )
                                        <button
                                            type="button"
                                            x-on:click="openArchive({{ $request->procurement_request_id }})"
                                            data-tooltip="Archive"
                                            aria-label="Archive"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50"
                                        >
                                            <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @elseif($request->procurement_request_is_archived)
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.procurement.replacement-requests.restore', $request->procurement_request_id) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                data-tooltip="Restore"
                                                aria-label="Restore"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50"
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
                                    x-on:click.outside="if (! $event.target.closest('#rrEquipmentDrawer')) openModal = null"
                                    class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
                                >
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h2 class="text-xl font-semibold tracking-tight text-gray-950" title="Request #{{ $request->procurement_request_id }}">
                                                    {{ $requestCode }}
                                                </h2>
                                                <span class="rounded-full border px-2.5 py-1 text-xs font-medium {{ $requestStatusClass }}">
                                                    {{ $request->procurement_request_status }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500" title="Report #{{ $request->report_id ?? 'N/A' }}">
                                                @if($lineItems->count() > 1)
                                                    {{ $lineItems->count() }} items · {{ $roomLabel }}
                                                @else
                                                    {{ $reportCode }} · {{ $request->room_name ?? 'Unknown Room' }}
                                                @endif
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
                                                        Linked RIS: {{ \App\Support\RisWorkflow::formNumber($request) }}
                                                    </p>
                                                    <p class="mt-1 text-xs leading-5 text-gray-500">
                                                        Procurement paperwork has started for this replacement request.
                                                    </p>
                                                </div>
                                                <span class="inline-flex max-w-[220px] shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium {{ $risStatusClass }}" title="{{ $risStatusLabel }}">
                                                    {{ $risStatusLabel }}
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
                                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">
                                                    {{ $lineItems->count() > 1 ? 'Equipment items' : 'Equipment' }}
                                                </p>
                                                @if($lineItems->isNotEmpty())
                                                    <ul class="mt-3 divide-y divide-gray-100">
                                                        @foreach($lineItems as $line)
                                                            @php
                                                                $singleLine = $lineItems->count() === 1;
                                                                $lineTicket = \App\Support\ReportGrouping::ticketCode($line->report_id ?? null, $line->report_submitted_at ?? null);
                                                                $diagnosisImage = ($line->replacement_image ?? null) ?: ($singleLine ? $request->report_replacement_image : null);
                                                                $reportedImage = ($line->uploaded_image ?? null) ?: ($singleLine ? ($request->report_uploaded_image ?? null) : null);
                                                                $thumbImage = $diagnosisImage ?: $reportedImage;
                                                                $linePayload = [
                                                                    'equipmentId' => (int) ($line->resolved_equipment_id ?? $line->equipment_id ?? 0) ?: null,
                                                                    'name' => $line->display_name,
                                                                    'room' => $line->room_name ?: 'Unknown room',
                                                                    'ticket' => $lineTicket,
                                                                    'reason' => $line->replacement_notes ?: ($line->problem_description ?: ($singleLine ? ($request->report_replacement_notes ?: $request->report_problem_description) : null)),
                                                                    'diagnosisImage' => $diagnosisImage ? asset('storage/'.$diagnosisImage) : null,
                                                                    'reportedImage' => $reportedImage ? asset('storage/'.$reportedImage) : null,
                                                                ];
                                                            @endphp
                                                            <li class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                                                                @if($thumbImage)
                                                                    <img src="{{ asset('storage/'.$thumbImage) }}" alt="" class="h-11 w-11 shrink-0 rounded-lg border border-gray-200 object-cover">
                                                                @else
                                                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-400">
                                                                        <i data-lucide="image-off" class="h-4 w-4"></i>
                                                                    </span>
                                                                @endif
                                                                <div class="min-w-0 flex-1">
                                                                    <h3 class="{{ $singleLine ? 'text-base' : 'text-sm' }} font-semibold text-gray-950">{{ $line->display_name }}</h3>
                                                                    <p class="mt-1 text-xs text-gray-500">
                                                                        {{ $line->room_name ?: 'Unknown room' }}
                                                                        · {{ $lineTicket }}
                                                                        @if(!empty($line->equipment_asset_tag))
                                                                            · {{ $line->equipment_asset_tag }}
                                                                        @endif
                                                                    </p>
                                                                    @if(! $singleLine && (!empty($line->problem_description) || !empty($line->replacement_notes)))
                                                                        <p class="mt-1 text-xs leading-5 text-gray-400">
                                                                            {{ $line->replacement_notes ?: $line->problem_description }}
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                                <button
                                                                    type="button"
                                                                    onclick="openReplacementEquipment(@js($linePayload))"
                                                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-[#0025cc] transition hover:bg-gray-50"
                                                                >
                                                                    <i data-lucide="panel-right-open" class="h-3.5 w-3.5"></i>
                                                                    Details
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <h3 class="mt-3 text-lg font-semibold text-gray-950">{{ $equipmentName }}</h3>
                                                @endif

                                                <div class="mt-5 grid grid-cols-2 gap-4">
                                                    <div>
                                                        <p class="text-xs text-gray-400">Location</p>
                                                        <p class="mt-1 text-sm font-medium text-gray-700">{{ $roomLabel }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-400">Urgency</p>
                                                        <p class="mt-1 text-sm font-medium {{ ($request->report_urgency_level ?? '') === 'Urgent' ? 'text-red-600' : 'text-gray-700' }}">
                                                            {{ $request->report_urgency_level ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-400">{{ $lineItems->count() > 1 ? 'Reports' : 'Report' }}</p>
                                                        <p class="mt-1 text-sm font-medium text-gray-700" title="Report #{{ $request->report_id ?? 'N/A' }}">
                                                            @if(($request->report_ids ?? collect())->count() > 1)
                                                                {{ $request->report_ids->count() }} linked reports
                                                            @else
                                                                {{ $reportCode }}
                                                            @endif
                                                        </p>
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
                            x-bind:href="'{{ route(($pp ?? 'purchaser').'.ris.index') }}?replacement_request=' + createRisModal"
                            class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
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
                    x-bind:action="'{{ url('/'.($pp ?? 'purchaser').'/procurement/replacement-requests') }}/' + archiveModal + '/archive'"
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
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-[#007a3f] transition hover:bg-slate-50"
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
                    x-bind:action="'{{ url('/'.($pp ?? 'purchaser').'/procurement/replacement-requests') }}/' + approveModal + '/approve'"
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
                    x-bind:action="'{{ url('/'.($pp ?? 'purchaser').'/procurement/replacement-requests') }}/' + rejectModal + '/reject'"
                    class="space-y-4 p-5"
                >
                    @csrf
                    <input type="hidden" name="reject_request_id" x-bind:value="rejectModal">

                    <div>
                        <label for="reject-remarks-input" class="mb-1.5 block text-xs font-medium text-gray-600">
                            Reason for rejection <span class="text-red-500">*</span>
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

{{-- EQUIPMENT DETAILS DRAWER --}}
<div id="rrEquipmentDrawer" class="fixed inset-0 z-[1200] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-gray-950/40" onclick="closeReplacementEquipment()"></div>

    <aside
        id="rrEquipmentPanel"
        class="absolute right-0 top-0 flex h-full w-full max-w-[460px] translate-x-full flex-col overflow-hidden rounded-l-2xl border-l border-gray-200 bg-white shadow-2xl transition-transform duration-300 ease-out"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rrEquipmentTitle"
    >
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div class="min-w-0">
                <h2 id="rrEquipmentTitle" class="truncate text-lg font-semibold text-gray-950">Equipment</h2>
                <p id="rrEquipmentSubtitle" class="mt-1 text-xs text-gray-500"></p>
            </div>
            <button type="button" onclick="closeReplacementEquipment()" aria-label="Close" class="rounded-lg border border-gray-200 p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-700">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
            <section>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Photos</p>
                <div id="rrEquipmentPhotos" class="mt-3 grid grid-cols-2 gap-3"></div>
                <div id="rrEquipmentReasonWrap" class="mt-3 hidden rounded-xl bg-gray-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Replacement reason</p>
                    <p id="rrEquipmentReason" class="mt-1 text-sm leading-6 text-gray-700"></p>
                </div>
            </section>

            <p id="rrEquipmentUnlisted" class="hidden rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-500">
                This item isn't in the equipment register, so there are no equipment details or lifecycle history.
            </p>
            <p id="rrEquipmentLoading" class="hidden rounded-xl border border-gray-200 px-4 py-6 text-center text-sm text-gray-400">Loading equipment details…</p>
            <p id="rrEquipmentError" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Could not load the equipment details.</p>

            <div id="rrEquipmentContent" class="hidden space-y-6">
                <section>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Equipment details</p>
                    <dl id="rrEquipmentRows" class="mt-3 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200"></dl>
                </section>

                <section id="rrEquipmentReportsWrap" class="hidden">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Report history</p>
                    <div id="rrEquipmentReports" class="mt-3 grid grid-cols-3 gap-3"></div>
                </section>

                <section>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Lifecycle timeline</p>
                        <span id="rrEquipmentTimelineNote" class="text-[11px] text-gray-400"></span>
                    </div>
                    <div id="rrEquipmentStages" class="mt-3 flex flex-wrap gap-1.5"></div>
                    <div id="rrEquipmentTimeline" class="mt-4"></div>
                    <button id="rrEquipmentTimelineToggle" type="button" onclick="toggleReplacementTimeline()" class="mt-3 hidden items-center gap-1.5 text-sm font-semibold text-[#0025cc] hover:underline">
                        <span id="rrEquipmentTimelineToggleIcon" class="inline-flex transition-transform">
                            <i data-lucide="chevron-down" class="h-4 w-4"></i>
                        </span>
                        <span id="rrEquipmentTimelineToggleLabel">Show older events</span>
                    </button>
                </section>
            </div>
        </div>
    </aside>
</div>

<script>
    (function () {
        const equipmentUrl = @json(route(($pp ?? 'purchaser').'.procurement.replacement-requests.equipment', ['equipmentId' => '__ID__']));
        const TIMELINE_LIMIT = 10;
        const STAGE_CLASSES = {
            purchase: 'bg-indigo-50 text-indigo-700',
            received: 'bg-sky-50 text-sky-700',
            inventory: 'bg-slate-100 text-slate-700',
            assigned: 'bg-violet-50 text-violet-700',
            borrowed: 'bg-cyan-50 text-cyan-700',
            repair: 'bg-amber-50 text-amber-700',
            for_replacement: 'bg-orange-50 text-orange-700',
            replacement: 'bg-emerald-50 text-emerald-700',
            disposal: 'bg-rose-50 text-rose-700',
        };
        const state = { request: 0, events: [], stages: {}, counts: {}, stage: 'all', expanded: false };

        const el = (id) => document.getElementById(id);
        const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[ch]);
        const fmtDate = (value) => {
            if (!value) return '—';
            const date = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(date.getTime())
                ? String(value)
                : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        };
        const fmtMoney = (value) => value === null || value === undefined || value === ''
            ? '—'
            : '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const icons = () => window.lucide && window.lucide.createIcons();

        function renderPhotos(item, equipmentImage) {
            const photos = [
                ['Diagnosis photo', item.diagnosisImage],
                ['Reported photo', item.reportedImage],
                ['Equipment photo', equipmentImage],
            ].filter(([, url]) => url);

            el('rrEquipmentPhotos').innerHTML = photos.length
                ? photos.map(([label, url]) => `
                    <a href="${esc(url)}" target="_blank" rel="noopener" class="group block overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                        <img src="${esc(url)}" alt="${esc(label)}" class="h-36 w-full object-cover transition group-hover:opacity-90">
                        <span class="block border-t border-gray-100 bg-white px-3 py-2 text-xs font-medium text-gray-600">${esc(label)}</span>
                    </a>`).join('')
                : '<p class="col-span-2 rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400">No photos were attached.</p>';
        }

        function renderDetails(equipment) {
            const brandModel = [equipment.brand, equipment.model].filter(Boolean).join(' · ');
            const rows = [
                ['Asset tag', equipment.asset_tag],
                ['Serial number', equipment.serial_number],
                ['Category', equipment.category],
                ['Brand / model', brandModel],
                ['Status', equipment.inventory_status],
                ['Condition', equipment.condition_status],
                ['Location', equipment.room_name],
                ['Tracking', equipment.tracking_mode ? `${equipment.tracking_mode} · Qty ${equipment.quantity ?? 1}` : null],
                ['Acquired via', equipment.acquisition_source_label],
                ['Supplier', equipment.supplier_name],
                ['Purchase order', equipment.purchase_order_number],
                ['Receiving report', equipment.receiving_report_number],
                ['Purchase date', fmtDate(equipment.purchase_date)],
                ['Purchase cost', fmtMoney(equipment.purchase_cost)],
                ['Added to inventory', fmtDate(equipment.acquired_date)],
                ['First deployed', equipment.deployed_at ? `${fmtDate(equipment.deployed_at)}${equipment.deployed_room ? ' · ' + equipment.deployed_room : ''}` : null],
                ['Warranty until', fmtDate(equipment.warranty_expiration)],
                ['Useful life', equipment.useful_life_years ? `${equipment.useful_life_years} year${Number(equipment.useful_life_years) === 1 ? '' : 's'}` : null],
            ];

            el('rrEquipmentRows').innerHTML = rows.map(([label, value]) => `
                <div class="flex items-start justify-between gap-4 px-4 py-2.5">
                    <dt class="text-xs text-gray-500">${esc(label)}</dt>
                    <dd class="text-right text-sm font-medium text-gray-800">${esc(value || '—')}</dd>
                </div>`).join('');
        }

        function renderReports(summary) {
            const wrap = el('rrEquipmentReportsWrap');
            if (!summary || !summary.times_reported) {
                wrap.classList.add('hidden');
                return;
            }
            wrap.classList.remove('hidden');
            const tile = (label, value, note) => `
                <div class="rounded-xl border border-gray-200 px-3 py-2.5">
                    <p class="text-[11px] text-gray-400">${esc(label)}</p>
                    <p class="mt-0.5 text-sm font-semibold text-gray-900">${esc(value)}</p>
                    ${note ? `<p class="text-[11px] text-gray-400">${esc(note)}</p>` : ''}
                </div>`;
            el('rrEquipmentReports').innerHTML =
                tile('Right now', summary.state || '—')
                + tile('Times reported', summary.times_reported, 'Last ' + fmtDate(summary.last_reported_at))
                + tile('Times fixed', summary.times_fixed ?? 0, summary.last_fixed_at ? 'Last ' + fmtDate(summary.last_fixed_at) : '');
        }

        function renderTimeline() {
            const { events, stages, counts, stage, expanded } = state;
            const chip = (key, label, count) => {
                const on = stage === key;
                return `<button type="button" onclick="filterReplacementTimeline('${key}')" class="rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition ${on ? 'bg-[#0025cc] text-white ring-[#0025cc]' : 'bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100'}">${esc(label)} · ${count}</button>`;
            };

            el('rrEquipmentStages').innerHTML = events.length
                ? chip('all', 'All', events.length) + Object.keys(stages)
                    .filter((key) => counts[key])
                    .map((key) => chip(key, stages[key], counts[key]))
                    .join('')
                : '';

            const matching = stage === 'all' ? events : events.filter((event) => event.stage === stage);
            const visible = expanded ? matching : matching.slice(0, TIMELINE_LIMIT);

            el('rrEquipmentTimeline').innerHTML = visible.length
                ? visible.map((event, index) => `
                    <div class="relative flex gap-3 ${index < visible.length - 1 ? 'pb-4' : ''}">
                        ${index < visible.length - 1 ? '<span class="absolute left-[5px] top-4 bottom-0 w-px bg-slate-200"></span>' : ''}
                        <span class="relative z-10 mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ring-4 ring-white ${esc(event.meta?.dot || 'bg-slate-400')}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="text-sm font-semibold text-slate-900">${esc(event.title)}</p>
                                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold ${STAGE_CLASSES[event.stage] || 'bg-slate-100 text-slate-700'}">${esc(event.stage_label || event.type_label)}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">${esc(fmtDate(event.occurred_at))}</p>
                            ${event.description ? `<p class="mt-1 text-xs leading-5 text-slate-600">${esc(event.description)}</p>` : ''}
                        </div>
                    </div>`).join('')
                : `<p class="rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400">${events.length ? 'No events for this stage.' : 'No lifecycle events recorded yet.'}</p>`;

            const older = matching.length - TIMELINE_LIMIT;
            el('rrEquipmentTimelineNote').textContent = older > 0 && !expanded ? `Latest ${TIMELINE_LIMIT} of ${matching.length}` : '';
            const toggle = el('rrEquipmentTimelineToggle');
            toggle.classList.toggle('hidden', older <= 0);
            toggle.classList.toggle('inline-flex', older > 0);
            el('rrEquipmentTimelineToggleIcon').classList.toggle('rotate-180', expanded);
            el('rrEquipmentTimelineToggleLabel').textContent = expanded
                ? 'Show fewer'
                : `Show ${older} older ${older === 1 ? 'event' : 'events'}`;
        }

        window.filterReplacementTimeline = function (stage) {
            state.stage = stage;
            state.expanded = false;
            renderTimeline();
        };

        window.toggleReplacementTimeline = function () {
            const collapsing = state.expanded;
            state.expanded = !collapsing;
            renderTimeline();
            if (collapsing) {
                el('rrEquipmentTimelineToggle').scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        };

        window.openReplacementEquipment = async function (item) {
            const requestId = ++state.request;
            Object.assign(state, { events: [], stages: {}, counts: {}, stage: 'all', expanded: false });

            el('rrEquipmentTitle').textContent = item.name || 'Equipment';
            el('rrEquipmentSubtitle').textContent = [item.room, item.ticket].filter(Boolean).join(' · ');
            el('rrEquipmentReason').textContent = item.reason || '';
            el('rrEquipmentReasonWrap').classList.toggle('hidden', !item.reason);
            renderPhotos(item, null);

            ['rrEquipmentContent', 'rrEquipmentError', 'rrEquipmentLoading', 'rrEquipmentUnlisted']
                .forEach((id) => el(id).classList.add('hidden'));
            el(item.equipmentId ? 'rrEquipmentLoading' : 'rrEquipmentUnlisted').classList.remove('hidden');

            const drawer = el('rrEquipmentDrawer');
            drawer.classList.remove('hidden');
            drawer.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => el('rrEquipmentPanel').classList.remove('translate-x-full'));
            icons();

            if (!item.equipmentId) return;

            try {
                const response = await fetch(equipmentUrl.replace('__ID__', encodeURIComponent(item.equipmentId)), {
                    headers: { Accept: 'application/json' },
                });
                if (requestId !== state.request) return;
                if (!response.ok) throw new Error('Request failed');

                const data = await response.json();
                if (requestId !== state.request) return;

                renderPhotos(item, data.equipment?.image_url || null);
                renderDetails(data.equipment || {});
                renderReports(data.report_summary);
                Object.assign(state, {
                    events: Array.isArray(data.events) ? data.events : [],
                    stages: data.stages || {},
                    counts: data.stage_counts || {},
                });
                renderTimeline();

                el('rrEquipmentLoading').classList.add('hidden');
                el('rrEquipmentContent').classList.remove('hidden');
                icons();
            } catch (error) {
                if (requestId !== state.request) return;
                el('rrEquipmentLoading').classList.add('hidden');
                el('rrEquipmentError').classList.remove('hidden');
            }
        };

        window.closeReplacementEquipment = function () {
            state.request++;
            const drawer = el('rrEquipmentDrawer');
            el('rrEquipmentPanel').classList.add('translate-x-full');
            drawer.setAttribute('aria-hidden', 'true');
            setTimeout(() => {
                if (el('rrEquipmentPanel').classList.contains('translate-x-full')) {
                    drawer.classList.add('hidden');
                }
            }, 300);
        };

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape' || el('rrEquipmentDrawer').classList.contains('hidden')) return;
            event.stopPropagation();
            window.closeReplacementEquipment();
        }, true);
    })();
</script>

@endsection