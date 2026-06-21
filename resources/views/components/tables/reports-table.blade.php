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
                        class="w-full h-10 pl-10 pr-4 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 placeholder-gray-400 outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 focus:bg-white transition">
                </div>

                <!-- STATUS -->
                <select
                    name="status"
                    class="h-10 px-3 pr-8 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition">

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
                <button class="h-10 px-5 rounded-xl bg-[rgba(0,55,199,1)]
                                    hover:bg-[rgba(0,55,199,0.85)]
                                    border
                                    border-[rgba(0,55,199,0.4)]
                                    text-[#f0f2f8]
                                    text-sm transition shadow-sm">
                    Search
                </button>

                <!-- DIVIDER -->
                <div class="hidden lg:block w-px h-6 bg-gray-200"></div>

                <div class="flex items-center bg-gray-100 rounded-xl p-1 gap-0.5 shrink-0">

                    <a href="/maintenance/reports?archive=0"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                    {{ !request('archive')
                            ? 'bg-[#FFF200] text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'
                    }}">

                        <i data-lucide="folder-open" class="w-3.5 h-3.5"></i>
                        Active

                    </a>

                    <a href="/maintenance/reports?archive=1"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                    {{ request('archive')
                            ? 'bg-[#FFF200] text-gray-900 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'
                    }}">

                        <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                        Archive

                    </a>

                </div>

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
                    ['label' => 'Start Processing', 'value' => 'Processing', 'class' => 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100/70 hover:border-blue-300'],
                    ['label' => 'Reject', 'value' => 'Rejected', 'class' => 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100/70 hover:border-red-300'],
                ];
            } elseif ($currentStatus === 'Processing') {
                $nextOptions = [
                    ['label' => 'Resolve', 'value' => 'Resolved', 'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100/70 hover:border-emerald-300'],
                    ['label' => 'For Replacement', 'value' => 'For Replacement', 'class' => 'bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-100/70 hover:border-orange-300'],
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

                            <button
                                type="button"
                                onclick="
                                    openReportModal('update-modal-{{ $report->report_id }}');

                                    const select =
                                    document.querySelector(
                                        '#update-modal-{{ $report->report_id }} select[name=status]'
                                    );

                                    select.value='{{ $option['value'] }}';

                                    toggleStatusFields(select);
                                "
                                class="px-3 py-1 rounded-lg text-xs transition {{ $option['class'] }}">

                                {{ $option['label'] }}

                            </button>

                            @endforeach
                    </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" onclick="openReportModal('view-modal-{{ $report->report_id }}')"
                                class="h-9 px-3 flex justify-center items-center gap-1 rounded-lg bg-gray-50 border border-gray-200 text-gray-700 text-xs hover:text-gray-900 font-semibold hover:bg-gray-100 transition">
                                <i data-lucide="eye" class="w-3.5 h-3.5 text-gray-400"></i>
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
                                    class="inline-flex items-center gap-2 px-4 h-9 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition-all active:scale-95">
                                <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                Archive
                            </button>
                        </form>
                        @endif

                        @if($report->report_is_archived)

                        <form method="POST"
                            action="/maintenance/reports/restore/{{ $report->report_id }}">
                            @csrf

                            <button
                                class="h-8 px-3 rounded-lg
                                    bg-[rgba(0,55,199,0.85)]
                                    hover:bg-[rgba(0,55,199,1)]
                                    border
                                    border-[rgba(0,55,199,0.4)]
                                    text-[#f0f2f8]
                                    text-xs
                                    transition">
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
                                    class="h-9 px-3 flex justify-center items-center gap-1 rounded-lg bg-gray-50 border border-gray-200 text-gray-700 text-xs hover:text-gray-900 font-semibold hover:bg-gray-100 transition">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-gray-400"></i>
                                View
                            </button>
                            @if($canUpdate)
                            <button type="button" 
                                    onclick="openReportModal('update-modal-{{ $report->report_id }}')"
                                    class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg bg-[#FFF200] text-gray-900 text-xs font-bold hover:bg-yellow-300 transition shadow-sm active:scale-95">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-gray-900"></i>
                                <span>Update</span>
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
                                    class="inline-flex items-center gap-2 px-3 h-9 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition-all active:scale-95">
                                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                    Archive
                                </button>
                            </form>
                            @endif
                            @if($report->report_is_archived)

                            <form method="POST"
                                action="/maintenance/reports/restore/{{ $report->report_id }}">
                                @csrf

                                <button
                                    class="h-8 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs">
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

<div id="view-modal-{{ $report->report_id }}" class="hidden fixed inset-0 z-50 overflow-hidden">
    <div class="flex min-h-screen items-center justify-center bg-black/50 backdrop-blur-sm p-3 sm:p-4">
        
        <div class="w-full max-w-2xl max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-4rem)] rounded-2xl bg-white shadow-2xl overflow-hidden flex flex-col" onclick="event.stopPropagation()">

            <div class="relative shrink-0 overflow-hidden bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950">

                <!-- Decorative Glow -->
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,242,0,0.18),transparent_35%)]"></div>

                <div class="relative px-6 py-5">

                    <div class="flex items-start justify-between gap-4">

                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-center gap-2 mb-3">

                                <span class="px-2.5 py-1 rounded-lg bg-white/10 backdrop-blur-sm border border-white/10 text-white text-[10px] font-black uppercase tracking-[0.18em]">
                                    Maintenance Report
                                </span>

                                <span class="px-2.5 py-1 rounded-lg bg-[#FFF200] text-black text-[10px] font-black uppercase tracking-wider">
                                    Ticket #{{ $report->report_id }}
                                </span>

                            </div>

                            <h2 class="text-2xl font-black text-white leading-tight">
                                {{ $report->equipment_name ?? 'Unlisted Equipment' }}
                            </h2>

                            <p class="text-sm text-slate-300 mt-1">
                                Maintenance report details and workflow history
                            </p>

                        </div>

                        <button type="button"
                                onclick="closeReportModal('view-modal-{{ $report->report_id }}')"
                                class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 border border-white/10 text-white flex items-center justify-center transition">

                            <i data-lucide="x" class="w-5 h-5"></i>

                        </button>

                    </div>

                </div>

            </div>

            <div class="p-5 sm:p-6 space-y-5 overflow-y-auto bg-gradient-to-b from-slate-50 to-white">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Equipment</p>
                        <p class="text-sm text-gray-800">{{ $report->equipment_name ?? 'Unlisted Equipment' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Room</p>
                        <p class="text-sm text-gray-800">{{ $report->room_name ?? 'No Assigned Room' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">f
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Reporter</p>
                        <p class="text-sm text-gray-800">{{ $report->reporter_full_name ?? 'Unknown Reporter' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Date Submitted</p>
                        <p class="text-sm text-gray-800">{{ \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y h:i A') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1.5">Status</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusMap[$report->report_current_status] ?? 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                            {{ $report->report_current_status }}
                        </span>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1.5">Urgency</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $urgencyPill }}">
                            {{ $report->report_urgency_level }}
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400 mb-3">Suggested Issue</p>
                    <p class="text-sm text-gray-700 leading-relaxed">
                        {{ $report->report_suggested_issue ?? 'No suggested issue provided' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400 mb-3">Problem Description</p>
                    <p class="text-sm text-gray-700 leading-relaxed break-words">{{ $report->report_problem_description }}</p>
                </div>

                @if($report->report_current_status === 'Resolved')
                <div class="bg-white border-l-4 border-l-emerald-500 rounded-xl p-4 border border-emerald-100 shadow-sm">
                    <div class="flex items-center gap-1.5 text-emerald-800 mb-1.5">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                        <p class="text-[11px] font-bold uppercase tracking-wider">Resolution Notes</p>
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-line break-words">{{ $report->report_resolution_notes ?: 'No notes provided by maintenance operator.' }}</p>

                    @if($report->report_resolution_image)
                    <div class="mt-4 rounded-xl overflow-hidden border border-emerald-200 bg-gray-900/5 max-h-48 sm:max-h-60 flex justify-start">
                        <img src="{{ asset('storage/'.$report->report_resolution_image) }}" 
                             alt="Resolution Proof Image"
                             class="max-h-48 sm:max-h-60 w-full object-contain object-left hover:scale-[1.02] transition duration-200">
                    </div>
                    @endif
                </div>
                @endif

                @if($report->report_current_status === 'Rejected')
                <div class="bg-white border-l-4 border-l-red-500 rounded-xl p-4 border border-red-100 shadow-sm">
                    <div class="flex items-center gap-1.5 text-red-800 mb-1.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600"></i>
                        <p class="text-[11px] font-bold uppercase tracking-wider">Rejection Criteria & Notes</p>
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-line break-words">{{ $report->report_rejection_notes ?: 'No specific rejection details specified.' }}</p>
                </div>
                @endif

                @if($report->report_current_status === 'For Replacement')
                <div class="bg-white border-l-4 border-l-orange-500 rounded-xl p-4 border border-orange-100 shadow-sm">
                    <div class="flex items-center gap-1.5 text-orange-800 mb-1.5">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-orange-600"></i>
                        <p class="text-[11px] font-bold uppercase tracking-wider">Replacement Justification Context</p>
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed whitespace-pre-line break-words">{{ $report->report_replacement_notes ?: 'No technical justification details provided.' }}</p>

                    @if($report->report_replacement_image)
                    <div class="mt-4 rounded-xl overflow-hidden border border-orange-200 bg-gray-900/5 max-h-48 sm:max-h-60 flex justify-start">
                        <img src="{{ asset('storage/'.$report->report_replacement_image) }}" 
                             alt="Replacement Proof Image"
                             class="w-full h-auto max-h-[350px] object-cover hover:scale-[1.02] transition duration-300">
                    </div>
                    @endif
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endforeach


<!-- UPDATE MODALS -->
@foreach($reports as $report)
<div id="update-modal-{{ $report->report_id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center bg-black/50 backdrop-blur-sm p-2 sm:p-4"
         >
         
        <div class="w-full max-w-md h-auto max-h-[92vh] rounded-2xl bg-white shadow-2xl overflow-hidden border border-gray-100 flex flex-col"
             onclick="event.stopPropagation()">

            <div class="sticky top-0 z-20 overflow-hidden bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-4 sm:px-6 py-4 sm:py-5 shrink-0">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,242,0,0.18),transparent_40%)]"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="px-2 py-1 rounded-lg bg-white/10 text-white text-[10px] font-bold uppercase tracking-widest">
                                Maintenance Workflow
                            </span>
                            <span class="px-2 py-1 rounded-lg bg-[#FFF200] text-black text-[10px] font-black">
                                Ticket #{{ $report->report_id }}
                            </span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-white break-words leading-tight">
                            {{ $report->equipment_name ?? ($report->report_unlisted_equipment_name ?? 'Equipment Report') }}
                        </h3>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                            Update maintenance progress and documentation
                        </p>
                    </div>

                    <button type="button"
                            onclick="closeReportModal('update-modal-{{ $report->report_id }}')",
                            class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition shrink-0">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <form action="/maintenance/reports/update-status/{{ $report->report_id }}" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col min-h-0">
                @csrf
                
                <div class="flex-1 overflow-y-auto overflow-x-hidden scroll-smooth p-5 space-y-4">
                    
                    <div class="bg-gray-50/80 border border-gray-200 rounded-xl p-3.5 flex items-center justify-between gap-2">
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
                        <span class="px-2.5 py-1 text-xs font-bold uppercase tracking-wide rounded-lg border {{ $currentStyle }} shadow-sm">
                            {{ $report->report_current_status }}
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500">
                            Select Pipeline Action
                        </label>
                        <div class="relative">
                            <select name="status" required
                                    class="w-full rounded-xl border border-gray-200 bg-white pl-4 pr-10 py-3 text-sm text-gray-800 outline-none appearance-none focus:ring-4 focus:ring-yellow-300/30 focus:border-yellow-400 transition cursor-pointer shadow-sm">
                                @if($report->report_current_status == 'Pending')
                                    <option value="" selected disabled>Select status update</option>
                                    <option value="Processing" class="text-black">Processing</option>
                                    <option value="Rejected" class="text-black">Reject</option>
                                @elseif($report->report_current_status == 'Processing')
                                    <option value="" selected disabled>Select status update</option>
                                    <option value="Resolved" class="text-black">Resolved</option>
                                    <option value="For Replacement" class="text-black">For Replacement</option>
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

                    <div id="notes-section-{{ $report->report_id }}"
                        class="hidden rounded-2xl border border-slate-200 bg-slate-50/80 backdrop-blur-sm p-4 transition-all duration-300">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="file-text" class="w-4 h-4 text-slate-500"></i>
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-500">
                                Documentation & Remarks
                            </label>
                        </div>
                        <textarea
                            name="remarks"
                            rows="4"
                            placeholder="Enter findings, actions performed, replacement justification, or maintenance remarks..."
                            class="w-full rounded-xl border border-slate-200 text-black bg-white p-3 text-sm resize-none focus:ring-4 focus:ring-yellow-300/30 focus:border-yellow-400"></textarea>
                    </div>

                    <div id="image-section-{{ $report->report_id }}"
                        class="hidden rounded-2xl border border-slate-200 bg-slate-50/80 backdrop-blur-sm p-4 transition-all duration-300">
                        
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="camera" class="w-4 h-4 text-slate-500"></i>
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-500">
                                Proof Documentation
                            </label>
                        </div>

                        <div class="relative group">
                            <input type="file"
                                id="proof_image_{{ $report->report_id }}"
                                name="proof_image"
                                accept="image/*"
                                onchange="previewImage(this, '{{ $report->report_id }}')"
                                class="absolute inset-0 w-full h-full opacity-0 z-10 cursor-pointer">

                            <label for="proof_image_{{ $report->report_id }}" 
                                class="flex flex-col items-center justify-center text-center px-4 py-8 rounded-xl border-2 border-dashed border-slate-300 bg-white group-hover:border-yellow-400 group-hover:bg-slate-50/50 transition duration-200 min-h-[160px]">
                                
                                <div id="upload-icon-{{ $report->report_id }}" class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-blue-600 mb-3 group-hover:scale-110 group-hover:bg-yellow-50 group-hover:border-yellow-100 transition duration-200">
                                    <i data-lucide="image-plus" class="w-6 h-6"></i>
                                </div>

                                <div id="upload-text-container-{{ $report->report_id }}">
                                    <p class="text-sm font-bold text-slate-700">
                                        Click to upload photo <span class="text-xs font-medium text-slate-400 block sm:inline sm:ml-1">(Optional)</span>
                                    </p>
                                    <p class="text-[11px] font-medium text-slate-400 mt-1 uppercase tracking-wider">
                                        PNG, JPG, JPEG, WEBP up to 10MB
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Image Preview Window Block -->
                        <div id="preview-container-{{ $report->report_id }}" class="hidden mt-4 relative rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-slate-100 group/preview">
                            
                            <!-- Preview Image Element -->
                            <img id="preview-img-{{ $report->report_id }}" class="max-h-64 object-cover w-full" alt="Proof Preview">
                            
                            <!-- Top-Right Action Floating Utility Tray -->
                            <div class="absolute top-2 right-2 flex items-center gap-1.5 z-20">
                                <!-- New Premium Zoom Action Button -->
                                <button type="button" 
                                        onclick="openLightbox('preview-img-{{ $report->report_id }}')"
                                        title="Zoom Image"
                                        class="w-8 h-8 rounded-lg bg-black/70 hover:bg-black/90 text-white flex items-center justify-center backdrop-blur-xs transition">
                                    <i data-lucide="zoom-in" class="w-4 h-4"></i>
                                </button>

                                <!-- Quick Action Remove Floating Button -->
                                <button type="button" 
                                        onclick="removeUploadedImage('{{ $report->report_id }}')"
                                        title="Remove Image"
                                        class="w-8 h-8 rounded-lg bg-black/70 hover:bg-red-600 text-white flex items-center justify-center backdrop-blur-xs transition">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <p class="text-xs text-slate-400 mt-2.5 leading-relaxed">
                            Upload completed repair photos, damaged equipment evidence, or replacement proof.
                        </p>
                    </div>

                    <div id="replacement-warning-{{ $report->report_id }}"
                        class="hidden rounded-2xl border border-orange-200 bg-orange-50 p-4">
                        <div class="flex gap-3">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-orange-600 shrink-0 mt-0.5"></i>
                            <div>
                                <p class="font-bold text-orange-800">
                                    Procurement Workflow Trigger
                                </p>
                                <p class="text-sm text-orange-700 mt-1">
                                    This action will automatically create a procurement request and notify the Purchaser Department.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 flex flex-col-reverse sm:flex-row gap-3 p-4 border-t border-gray-100 bg-white">
                    <button type="button" onclick="closeReportModal('update-modal-{{ $report->report_id }}')"
                            class="flex-1 py-2.5 rounded-xl border border-gray-300 text-xs text-gray-500 font-bold hover:bg-gray-50 hover:text-gray-700 transition tracking-wide uppercase">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-[#FFF200] text-gray-900 text-xs font-black tracking-wide uppercase hover:bg-yellow-300 active:scale-[0.98] transition shadow-sm">
                        Commit Progress
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endforeach

<!-- GLOBAL LIGHTBOX MODAL CONTAINER -->
<div id="global-image-lightbox" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-md p-4 animate-fade-in" onclick="closeLightbox()">
    <!-- Top Bar Control Controls -->
    <div class="absolute top-4 right-4 flex items-center gap-3">
        <span id="lightbox-filename" class="text-xs font-mono text-slate-400 hidden sm:inline-block"></span>
        <button type="button" onclick="closeLightbox()" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    
    <!-- Large Zoomed Dynamic Target Element -->
    <div class="max-w-4xl max-h-[85vh] overflow-hidden rounded-xl border border-white/10 shadow-2xl" onclick="event.stopPropagation()">
        <img id="lightbox-target-img" class="w-full h-full object-contain max-h-[85vh]" src="" alt="Expanded Proof Preview">
    </div>
</div>


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

        const savedView =
            localStorage.getItem('prism-report-view') || 'table';

        setReportView(savedView);

        const toast = document.getElementById('undo-toast');

        if (toast) {
            toast.classList.remove('hidden');

            setTimeout(() => {
                toast.classList.add('hidden');
            }, 7000);
        }

    });

    /* ==========================================
    STATUS FIELD TOGGLER
    ========================================== */

    function toggleStatusFields(selectElement) {

        const form = selectElement.closest('form');

        if (!form) return;

        const notes =
            form.querySelector('[id^="notes-section"]');

        const image =
            form.querySelector('[id^="image-section"]');

        const replacementWarning =
            form.querySelector('[id^="replacement-warning"]');

        // Hide everything first
        if (notes) {
            notes.classList.add('hidden');
        }

        if (image) {
            image.classList.add('hidden');
        }

        if (replacementWarning) {
            replacementWarning.classList.add('hidden');
        }

        // RESOLVED
        if (selectElement.value === 'Resolved') {

            if (notes) {
                notes.classList.remove('hidden');
            }

            if (image) {
                image.classList.remove('hidden');
            }

        }

        // REJECTED
        else if (selectElement.value === 'Rejected') {

            if (notes) {
                notes.classList.remove('hidden');
            }

        }

        // FOR REPLACEMENT
        else if (selectElement.value === 'For Replacement') {

            if (notes) {
                notes.classList.remove('hidden');
            }

            if (image) {
                image.classList.remove('hidden');
            }

            if (replacementWarning) {
                replacementWarning.classList.remove('hidden');
            }

        }

    }

    /* ==========================================
    DROPDOWN CHANGE EVENT
    ========================================== */

    document.addEventListener('change', function (e) {

        if (!e.target.matches('select[name="status"]')) return;

        toggleStatusFields(e.target);

    });

    /* ==========================================
    IMAGE PREVIEW
    ========================================== */

    document.addEventListener('change', function (e) {

        if (!e.target.matches('input[name="proof_image"]')) return;

        const preview =
            e.target.closest('div')
                ?.querySelector('img');

        if (!preview) return;

        if (!e.target.files.length) {
            preview.classList.add('hidden');
            return;
        }

        preview.src =
            URL.createObjectURL(e.target.files[0]);

        preview.classList.remove('hidden');

    });

    function previewImage(input, reportId) {
        const container = document.getElementById(`preview-container-${reportId}`);
        const img = document.getElementById(`preview-img-${reportId}`);
        const textContainer = document.getElementById(`upload-text-container-${reportId}`);
        const iconContainer = document.getElementById(`upload-icon-${reportId}`);
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Convert file size into readable format (MB)
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            // 1. Update the preview window image view src
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                container.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
            
            // 2. Change upload area styling to a "Success/Ready" state
            iconContainer.className = "p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 mb-3";
            iconContainer.innerHTML = '<i data-lucide="check-circle-2" class="w-6 h-6"></i>';
            
            // 3. Swap text strings to display selected file info
            textContainer.innerHTML = `
                <p class="text-sm font-bold text-slate-800 break-all px-4">
                    Selected: <span class="text-blue-600 font-mono text-xs">${file.name}</span>
                </p>
                <div class="mt-1.5 flex items-center justify-center gap-2">
                    <span class="text-[10px] bg-slate-100 px-2 py-0.5 rounded-md text-slate-500 font-semibold">${fileSizeMB} MB</span>
                    <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-bold uppercase tracking-wider">Click to Replace</span>
                </div>
            `;
            
            // Force refresh Lucide vector icons if available
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function removeUploadedImage(reportId) {
        const fileInput = document.getElementById(`proof_image_${reportId}`);
        const container = document.getElementById(`preview-container-${reportId}`);
        const img = document.getElementById(`preview-img-${reportId}`);
        const textContainer = document.getElementById(`upload-text-container-${reportId}`);
        const iconContainer = document.getElementById(`upload-icon-${reportId}`);
        
        // Clear value logic states
        fileInput.value = "";
        img.src = "";
        container.classList.add('hidden');
        
        // Reset dropzone look back to standard empty default
        iconContainer.className = "p-3 rounded-xl bg-slate-50 border border-slate-100 text-blue-600 mb-3 group-hover:scale-110 group-hover:bg-yellow-50 group-hover:border-yellow-100 transition duration-200";
        iconContainer.innerHTML = '<i data-lucide="image-plus" class="w-6 h-6"></i>';
        
        textContainer.innerHTML = `
            <p class="text-sm font-bold text-slate-700">
                Click to upload photo <span class="text-xs font-medium text-slate-400 block sm:inline sm:ml-1">(Optional)</span>
            </p>
            <p class="text-[11px] font-medium text-slate-400 mt-1 uppercase tracking-wider">
                PNG, JPG, JPEG, WEBP up to 10MB
            </p>
        `;
        
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openLightbox(targetImgId) {
        const targetImg = document.getElementById(targetImgId);
        const lightbox = document.getElementById('global-image-lightbox');
        const lightboxImg = document.getElementById('lightbox-target-img');
        const filenameLabel = document.getElementById('lightbox-filename');
        const fileInput = document.getElementById(targetImgId.replace('preview-img-', 'proof_image_'));

        if (targetImg && targetImg.src) {
            // 1. Inject preview file src string target link directly 
            lightboxImg.src = targetImg.src;
            
            // 2. Set dynamic title header label text if a native file object exists
            if (fileInput && fileInput.files && fileInput.files[0]) {
                filenameLabel.innerText = fileInput.files[0].name;
            } else {
                filenameLabel.innerText = "Proof Documentation Image";
            }
            
            // 3. Make modal frame view visible
            lightbox.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Lock background body scrolling actions
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function closeLightbox() {
        const lightbox = document.getElementById('global-image-lightbox');
        const lightboxImg = document.getElementById('lightbox-target-img');
        
        lightbox.classList.add('hidden');
        lightboxImg.src = ""; // Flush memory out buffer trace links
        document.body.style.overflow = ''; // Unlock baseline window frame scroll wheels
    }

    // Option shortcut: Hit 'Escape' window layout keys to quickly exit lightbox screen viewport safely
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
</script>