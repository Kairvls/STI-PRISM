<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

    <!-- FILTER BAR -->
    <div class="px-5 py-4 bg-white border-b border-gray-100">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">

            <form method="GET" class="flex flex-col lg:flex-row gap-3 flex-1 items-center">

                <input type="hidden"
                    name="archive"
                    value="{{ request('archive', 0) }}">

                <!-- SEARCH -->
                <div class="relative flex-1">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search report ID, equipment, room, reporter…"
                        class="w-full h-10 pl-10 pr-4 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 placeholder-gray-400 outline-none focus:ring-2 focus:ring-yellow-300 focus:border-yellow-400 focus:bg-white transition">
                </div>

                <!-- STATUS -->
                <select
                    name="status"
                    class="h-10 px-3 pr-8 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-yellow-300 focus:border-yellow-400 transition">

                    @if(request('archive'))

                        <option value="">All Archived Statuses</option>

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

                    @else

                        <option value="">All Statuses</option>

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

                    @endif

                </select>

                <!-- SEARCH BUTTON -->
                <button class="h-10 px-5 rounded-xl bg-[#0d1120] text-white text-sm hover:bg-gray-300 transition shadow-sm">
                    Search
                </button>

                <a href="/maintenance/reports?archive={{ request('archive') ? 0 : 1 }}"
                class="h-10 px-5 rounded-xl bg-gray-200 text-gray-700 text-sm flex items-center justify-center">

                    {{ request('archive') ? 'View Active' : 'View Archive' }}

                </a>

            </form>

            <!-- DIVIDER -->
            <div class="hidden lg:block w-px h-6 bg-gray-200"></div>

            <!-- VIEW TOGGLE -->
            <div class="flex items-center bg-gray-100 rounded-xl p-1 gap-0.5 shrink-0">
                <button type="button" id="card-view-btn" onclick="setReportView('card')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition text-gray-500 hover:text-gray-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    Cards
                </button>
                <button type="button" id="table-view-btn" onclick="setReportView('table')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition text-gray-500 hover:text-gray-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>
                    Table
                </button>
            </div>

        </div>
    </div>

    <!-- CARD VIEW -->
    <div id="card-view" class="hidden p-5 grid grid-cols-1 xl:grid-cols-2 gap-4">

        @forelse($reports as $report)
        @php
            $urgencyColor = $report->report_urgency_level == 'Urgent' ? 'bg-red-400' : 'bg-emerald-400';
            $urgencyPill  = $report->report_urgency_level == 'Urgent'
                ? 'bg-red-50 text-red-700 border border-red-200'
                : 'bg-emerald-50 text-emerald-700 border border-emerald-200';

            $statusMap = [
                'Pending'         => 'bg-amber-50 text-amber-700 border border-amber-200',
                'Processing'      => 'bg-blue-50 text-blue-700 border border-blue-200',
                'Resolved'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'Rejected'        => 'bg-red-50 text-red-700 border border-red-200',
                'For Replacement' => 'bg-orange-50 text-orange-700 border border-orange-200',
            ];
            $dotMap = [
                'Pending'         => 'bg-amber-400',
                'Processing'      => 'bg-blue-400',
                'Resolved'        => 'bg-emerald-400',
                'Rejected'        => 'bg-red-400',
                'For Replacement' => 'bg-orange-400',
            ];
            $currentStatus = $report->report_current_status;
            $statusPill    = $statusMap[$currentStatus] ?? 'bg-gray-100 text-gray-600 border border-gray-200';
            $statusDot     = $dotMap[$currentStatus]    ?? 'bg-gray-400';

            $nextOptions = [];
            if ($currentStatus === 'Pending') {
                $nextOptions = [
                    ['label' => 'Start Processing', 'value' => 'Processing', 'class' => 'bg-blue-50 text-blue-700 hover:bg-blue-100'],
                    ['label' => 'Reject', 'value' => 'Rejected', 'class' => 'bg-red-50 text-red-700 hover:bg-red-100'],
                ];
            } elseif ($currentStatus === 'Processing') {
                $nextOptions = [
                    ['label' => 'Resolve', 'value' => 'Resolved', 'class' => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'],
                    ['label' => 'For Replacement', 'value' => 'For Replacement', 'class' => 'bg-orange-50 text-orange-700 hover:bg-orange-100'],
                ];
            }

            $canUpdate = in_array($currentStatus, ['Pending', 'Processing']);
        @endphp

        <div class="border border-gray-200 rounded-xl bg-white hover:border-gray-300 hover:shadow-md transition-all overflow-hidden">

            <!-- Urgency accent bar -->
            <div class="h-1 w-full {{ $urgencyColor }}"></div>

            <div class="p-5">

                <!-- Header row -->
                
                <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">
                            Report #{{ $report->report_id }}
                        </p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900 leading-snug">
                            {{ $report->equipment_name ?? 'Unlisted Equipment' }}
                        </h3>
                    </div>

                    <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $urgencyPill }}">
                            {{ $report->report_urgency_level }}
                        </span>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-gray-400 block">Reporter</span>
                        <p class="font-semibold text-gray-700 truncate">{{ $report->reporter_full_name ?? 'Unknown Reporter' }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-gray-400 block">Location Room</span>
                        <p class="font-semibold text-gray-700 truncate">{{ $report->room_name ?? 'No Assigned Room' }}</p>
                    </div>
                    
                    <div class="space-y-1 pt-1">
                        <span class="text-xs font-medium text-gray-400 block">Date Submitted</span>
                        <p class="text-gray-600 font-medium text-xs">
                            {{ \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y h:i A') }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <span class="text-xs font-medium text-gray-400 block">Suggested Issue</span>
                        <p class="font-semibold text-gray-700 truncate">
                            {{ $report->report_suggested_issue ?? 'No suggested issue provided' }}
                        </p>
                    </div>
                </div>

                <hr class="mt-4 mb-2">

                @php
                    $pipelineSteps = match ($currentStatus) {
                        'Pending' => [
                            ['step' => 'Pending', 'state' => 'active'],
                        ],
                        'Processing' => [
                            ['step' => 'Pending', 'state' => 'done'],
                            ['step' => 'Processing', 'state' => 'active'],
                        ],
                        'Resolved' => [
                            ['step' => 'Pending', 'state' => 'done'],
                            ['step' => 'Processing', 'state' => 'done'],
                            ['step' => 'Resolved', 'state' => 'done'],
                        ],
                        'Rejected' => [
                            ['step' => 'Pending', 'state' => 'done'],
                            ['step' => 'Rejected', 'state' => 'done'],
                        ],
                        'For Replacement' => [
                            ['step' => 'Pending', 'state' => 'done'],
                            ['step' => 'Processing', 'state' => 'done'],
                            ['step' => 'For Replacement', 'state' => 'done'],
                        ],
                        default => [
                            ['step' => 'Pending', 'state' => 'active'],
                        ],
                    };

                    $ARROW = 14; // px
                @endphp

                <div class="space-y-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Workflow Pipeline Tracker</p>

                    <div class="flex items-stretch" style="height:36px; gap: 3px;">
                        @foreach($pipelineSteps as $i => $ps)
                        @php
                            $isFirst = $i === 0;
                            $isLast  = $i === count($pipelineSteps) - 1;
                            $total   = count($pipelineSteps);

                            $stepName = $ps['step'];
                            $isTerminalStep = in_array($stepName, ['Rejected', 'For Replacement']);
                            if ($stepName === 'Resolved') {

    $bg = 'bg-emerald-100';
    $text = 'text-emerald-700 font-semibold';

} elseif ($stepName === 'Rejected') {

    $bg = 'bg-red-100';
    $text = 'text-red-700 font-semibold';

} elseif ($stepName === 'For Replacement') {

    $bg = 'bg-orange-100';
    $text = 'text-orange-700 font-semibold';

} elseif ($ps['state'] === 'active') {

    $bg = 'bg-[#FFF200]';
    $text = 'text-gray-900 font-bold';

} elseif ($ps['state'] === 'done') {

    $bg = 'bg-blue-100';
    $text = 'text-blue-700 font-semibold';

} else {

    $bg = 'bg-gray-100';
    $text = 'text-gray-400 font-medium';

}

Result:

                            if ($isFirst) {
                                $clip = "polygon(0 0, calc(100% - {$ARROW}px) 0, 100% 50%, calc(100% - {$ARROW}px) 100%, 0 100%)";
                            } elseif ($isLast) {
                                $clip = "polygon({$ARROW}px 0, 100% 0, 100% 100%, 0 100%, {$ARROW}px 50%)";
                            } else {
                                $clip = "polygon({$ARROW}px 0, calc(100% - {$ARROW}px) 0, 100% 50%, calc(100% - {$ARROW}px) 100%, 0 100%, {$ARROW}px 50%)";
                            }

                            $pl = $isFirst ? 14 : $ARROW + 14;
                            $pr = $isLast  ? 14 : $ARROW + 10;
                            $ml = $isFirst ? 0  : -$ARROW;
                            $z  = $total - $i;
                        @endphp
                        <div class="relative flex items-center justify-center gap-1.5 text-[11px] whitespace-nowrap select-none {{ $bg }} {{ $text }}"
                            style="clip-path: {{ $clip }}; padding-left: {{ $pl }}px; padding-right: {{ $pr }}px; margin-left: {{ $ml }}px; z-index: {{ $z }}; min-width: 94px; max-width: 140px;">

                            @if($ps['state'] === 'done')
                                {{-- checkmark SVG --}}
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                            @elseif($ps['state'] === 'active')
                                {{-- spinner SVG --}}
                                <svg class="w-3 h-3 shrink-0 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            @endif

                            {{ $ps['step'] }}
                        </div>
                        @endforeach
                    </div>

                    
                </div>


                <!-- Footer: status + actions -->
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between gap-3">

                    <!-- Status pill -->
                    {{-- Quick Transition --}}
                    @if(!empty($nextOptions))
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-gray-500 font-medium">Update To:</span>
                        @foreach($nextOptions as $option)
                        <form action="/maintenance/reports/update-status/{{ $report->report_id }}" method="POST" class="inline-block">
                            @csrf
                            <input type="hidden" name="status" value="{{ $option['value'] }}">
                            <button type="submit" class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $option['class'] }}">
                                {{ $option['label'] }}
                            </button>
                        </form>
                        @endforeach
                    </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" onclick="openReportModal('view-modal-{{ $report->report_id }}')"
                                class="h-8 px-3 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 transition">
                            View
                        </button>

                        @if(
                            in_array(
                                $currentStatus,
                                ['Resolved','Rejected','For Replacement']
                            )
                            &&
                            !$report->report_is_archived
                        )
                        <form method="POST"
                            action="/maintenance/reports/archive/{{ $report->report_id }}">
                            @csrf

                            <button
                                class="h-8 px-3 rounded-lg bg-gray-800 text-white text-xs">
                                Archive
                            </button>
                        </form>
                        @endif

                        @if($report->report_is_archived)

                        <form method="POST"
                            action="/maintenance/reports/restore/{{ $report->report_id }}">
                            @csrf

                            <button
                                class="h-8 px-3 rounded-lg bg-blue-600 text-white text-xs">
                                Restore
                            </button>
                        </form>

                        @endif
                        
                    </div>

                </div>

            </div>
        </div>

        @empty
        <div class="col-span-full py-20 flex flex-col items-center justify-center gap-3 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center">
                <i data-lucide="file-search" class="w-6 h-6 text-gray-400"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-700">No Reports Found</p>
                <p class="text-sm text-gray-400 mt-1">No maintenance reports match the current filters.</p>
            </div>
        </div>
        @endforelse

    </div>

    <!-- TABLE VIEW -->
    <div id="table-view" class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Report ID</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Reporter</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Room</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Equipment</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Urgency</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Status</th>
                    <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Date Submitted</th>
                    <th class="px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $i => $report)
                @php
                    $urgencyPill = $report->report_urgency_level == 'Urgent'
                        ? 'bg-red-50 text-red-700 border border-red-200'
                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200';

                    $statusMap = [
                        'Pending'         => ['pill' => 'bg-amber-50 text-amber-700 border border-amber-200',   'dot' => 'bg-amber-400'],
                        'Processing'      => ['pill' => 'bg-blue-50 text-blue-700 border border-blue-200',       'dot' => 'bg-blue-400'],
                        'Resolved'        => ['pill' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'dot' => 'bg-emerald-400'],
                        'Rejected'        => ['pill' => 'bg-red-50 text-red-700 border border-red-200',         'dot' => 'bg-red-400'],
                        'For Replacement' => ['pill' => 'bg-orange-50 text-orange-700 border border-orange-200','dot' => 'bg-orange-400'],
                    ];
                    $currentStatus = $report->report_current_status;
                    $sCfg = $statusMap[$currentStatus] ?? ['pill' => 'bg-gray-100 text-gray-600 border border-gray-200', 'dot' => 'bg-gray-400'];
                    $canUpdate = in_array($currentStatus, ['Pending', 'Processing']);
                    $rowBg = $loop->even ? 'bg-gray-50/40' : '';
                @endphp
                <tr class="border-b border-gray-100 hover:bg-yellow-50/30 transition {{ $rowBg }}">
                    <td class="px-5 py-4 font-bold text-gray-900 text-sm">#{{ $report->report_id }}</td>
                    <td class="px-5 py-4 font-semibold text-gray-800 text-sm">{{ $report->reporter_full_name ?? 'Unknown Reporter' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $report->room_name ?? 'No Assigned Room' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-700 font-medium">{{ $report->equipment_name ?? 'Unlisted Equipment' }}</td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $urgencyPill }}">
                            {{ $report->report_urgency_level }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $sCfg['pill'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sCfg['dot'] }}"></span>
                            {{ $currentStatus }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-400">{{ \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y h:i A') }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" onclick="openReportModal('view-modal-{{ $report->report_id }}')"
                                    class="h-8 px-3 rounded-lg bg-gray-100 text-gray-700 text-xs font-semibold hover:bg-gray-200 transition">
                                View
                            </button>
                            @if($canUpdate)
                            <button type="button" onclick="openReportModal('update-modal-{{ $report->report_id }}')"
                                    class="h-8 px-3 rounded-lg bg-[#FFF200] text-gray-900 text-xs font-bold hover:bg-yellow-300 transition">
                                Update
                            </button>
                            @endif
                            @if(
                                in_array(
                                    $currentStatus,
                                    ['Resolved','Rejected','For Replacement']
                                )
                                &&
                                !$report->report_is_archived
                            )
                            <form method="POST"
                                action="/maintenance/reports/archive/{{ $report->report_id }}">
                                @csrf

                                <button
                                    class="h-8 px-3 rounded-lg bg-gray-800 text-white text-xs">
                                    Archive
                                </button>
                            </form>
                            @endif
                            @if($report->report_is_archived)

                            <form method="POST"
                                action="/maintenance/reports/restore/{{ $report->report_id }}">
                                @csrf

                                <button
                                    class="h-8 px-3 rounded-lg bg-blue-600 text-white text-xs">
                                    Restore
                                </button>
                            </form>

                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-20">
                        <div class="flex flex-col items-center justify-center gap-3 text-center">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center">
                                <i data-lucide="file-search" class="w-6 h-6 text-gray-400"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-700">No Reports Found</p>
                                <p class="text-sm text-gray-400 mt-1">No maintenance reports match the current filters.</p>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
        <p class="text-xs text-gray-400">{{ $reports->total() }} report{{ $reports->total() !== 1 ? 's' : '' }} found</p>
        {{ $reports->links() }}
    </div>

</div>


<!-- VIEW MODALS -->
@foreach($reports as $report)
@php
    $statusMap = [
        'Pending'         => 'bg-amber-50 text-amber-700 border border-amber-200',
        'Processing'      => 'bg-blue-50 text-blue-700 border border-blue-200',
        'Resolved'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'Rejected'        => 'bg-red-50 text-red-700 border border-red-200',
        'For Replacement' => 'bg-orange-50 text-orange-700 border border-orange-200',
    ];
    $urgencyPill = $report->report_urgency_level == 'Urgent'
        ? 'bg-red-50 text-red-700 border border-red-200'
        : 'bg-emerald-50 text-emerald-700 border border-emerald-200';
@endphp

<div id="view-modal-{{ $report->report_id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         onclick="closeReportModal('view-modal-{{ $report->report_id }}')">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl overflow-hidden" onclick="event.stopPropagation()">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Report Details</p>
                    <h3 class="text-lg font-bold text-gray-900 mt-0.5">#{{ $report->report_id }}</h3>
                </div>
                <button type="button" onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Equipment</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $report->equipment_name ?? 'Unlisted Equipment' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Room</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $report->room_name ?? 'No Assigned Room' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Reporter</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $report->reporter_full_name ?? 'Unknown Reporter' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Date Submitted</p>
                        <p class="text-sm font-semibold text-gray-800">{{ \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y h:i A') }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1.5">Status</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusMap[$report->report_current_status] ?? 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                            {{ $report->report_current_status }}
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1.5">Urgency</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $urgencyPill }}">
                            {{ $report->report_urgency_level }}
                        </span>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-2">Suggested Issue</p>
                    <p class="text-sm text-gray-700 leading-relaxed">
                        {{ $report->report_suggested_issue ?? 'No suggested issue provided' }}
                    </p>
                </div>

                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-2">Problem Description</p>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $report->report_problem_description }}</p>
                </div>
            </div>

        </div>
    </div>
</div>
@endforeach


<!-- UPDATE MODALS -->
@foreach($reports as $report)
<div id="update-modal-{{ $report->report_id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop Blur with Black/50 Split Layer -->
    <div class="flex min-h-screen items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         onclick="closeReportModal('update-modal-{{ $report->report_id }}')">
         
        <!-- Card Frame Container Block -->
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden transform transition-all border border-gray-100" 
             onclick="event.stopPropagation()">

            <!-- Modal Header with Top Context State Colors -->
            <div class="flex items-center justify-between px-6 py-4 bg-gray-50/70 border-b border-gray-100">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Update Workflow</p>
                        <span class="inline-flex px-1.5 py-0.5 rounded-md text-[9px] font-black bg-gray-200 text-gray-700 uppercase tracking-tight">
                            Ticket #{{ $report->report_id }}
                        </span>
                    </div>
                    <h3 class="text-base font-extrabold text-gray-900 mt-0.5">
                        {{ $report->equipment_name ?? ($report->report_unlisted_equipment_name ?? 'Equipment Report') }}
                    </h3>
                </div>
                <button type="button" onclick="closeReportModal('update-modal-{{ $report->report_id }}')"
                        class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-gray-400 text-gray-400 hover:text-gray-700 hover:bg-gray-50 transition shadow-3xs">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <hr>

            <form action="/maintenance/reports/update-status/{{ $report->report_id }}" method="POST" class="p-6 -mt-3 space-y-5">
                @csrf

                <!-- Current Workflow Status Box -->
                <div class="bg-gray-50/80 border border-gray-400/50 rounded-xl p-3.5 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Current Status</span>
                    </div>
                    
                    @php
                        $statusColors = [
                            'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'Processing' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'Resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'Rejected' => 'bg-red-50 text-red-700 border-red-200',
                            'For Replacement' => 'bg-yellow-50 text-yellow-800 border-yellow-200'
                        ];
                        $currentStyle = $statusColors[$report->report_current_status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                    @endphp
                    <span class="px-2.5 py-1 text-xs font-bold uppercase tracking-wide rounded-lg border {{ $currentStyle }} shadow-3xs">
                        {{ $report->report_current_status }}
                    </span>
                </div>

                <!-- Interactive Select Input Frame -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500">
                        Select Pipeline Action
                    </label>
                    <div class="relative">
                        <select name="status" required
                                class="w-full rounded-xl border border-gray-200 bg-white pl-4 pr-10 py-3 text-sm font-semibold text-gray-800 outline-none appearance-none focus:ring-4 focus:ring-yellow-300/30 focus:border-yellow-400 transition cursor-pointer shadow-3xs">
                            @if($report->report_current_status == 'Pending')
                                <option value="" disabled>-- Choose next milestone step --</option>
                                <option value="Processing" class="font-semibold text-black">Processing</option>
                                <option value="Rejected" class="font-semibold text-black">Reject</option>
                            @elseif($report->report_current_status == 'Processing')
                                <option value="" disabled>-- Choose next milestone step --</option>
                                <option value="Resolved" class="font-semibold text-black">Resolved</option>
                                <option value="For Replacement" class="font-semibold text-black">For Replacement</option>
                            @else
                                <option value="{{ $report->report_current_status }}" selected disabled>
                                    🔒 This report is archived as ({{ $report->report_current_status }})
                                </option>
                            @endif
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 border-l border-gray-100 my-2">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <!-- Context Form Footer Buttons Row Layout -->
                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeReportModal('update-modal-{{ $report->report_id }}')"
                            class="flex-1 py-2.5 rounded-xl border border-gray-400 text-xs text-gray-500 font-bold hover:bg-gray-50 hover:text-gray-700 transition tracking-wide uppercase">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-[#FFF200] text-gray-900 text-xs font-black tracking-wide uppercase hover:bg-yellow-300 active:scale-[0.98] transition shadow-xs">
                        Commit Progress
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endforeach


<!-- UNDO TOAST -->
@if(session('undo_report_id'))
<div id="undo-toast"
     class="hidden fixed bottom-6 right-6 z-[60] max-w-sm rounded-2xl bg-white border border-gray-200 shadow-xl p-4">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
            <i data-lucide="refresh-cw" class="w-4 h-4 text-emerald-600"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-gray-900">Status updated</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ session('success') }}</p>
        </div>
        <form action="/maintenance/reports/update-status/{{ session('undo_report_id') }}" method="POST">
            @csrf
            <input type="hidden" name="status" value="{{ session('undo_previous_status') }}">
            <input type="hidden" name="undo" value="1">
            <button type="submit"
                    class="px-3 py-1.5 rounded-lg bg-[#FFF200] text-gray-900 text-xs font-bold hover:bg-yellow-300 transition">
                Undo
            </button>
        </form>
    </div>
</div>
@endif


<script>
    function openReportModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('hidden');
    }

    function closeReportModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('hidden');
    }

    function setReportView(view) {
        const tableView = document.getElementById('table-view');
        const cardView  = document.getElementById('card-view');
        const tableBtn  = document.getElementById('table-view-btn');
        const cardBtn   = document.getElementById('card-view-btn');
        if (!tableView || !cardView || !tableBtn || !cardBtn) return;

        const showTable = view === 'table';
        tableView.classList.toggle('hidden', !showTable);
        cardView.classList.toggle('hidden', showTable);

        const activeClass   = ['bg-[#FFF200]', 'text-gray-900', 'shadow-sm'];
        const inactiveClass = ['text-gray-500'];

        [tableBtn, cardBtn].forEach(btn => {
            btn.classList.remove(...activeClass, ...inactiveClass);
        });

        if (showTable) {
            tableBtn.classList.add(...activeClass);
            cardBtn.classList.add(...inactiveClass);
        } else {
            cardBtn.classList.add(...activeClass);
            tableBtn.classList.add(...inactiveClass);
        }

        localStorage.setItem('prism-report-view', view);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const savedView = localStorage.getItem('prism-report-view') || 'table';
        setReportView(savedView);

        const toast = document.getElementById('undo-toast');
        if (toast) {
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 7000);
        }
    });
</script>