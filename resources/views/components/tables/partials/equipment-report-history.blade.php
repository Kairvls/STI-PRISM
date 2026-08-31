@php
    $timeline = collect($report->report_timeline ?? []);
    if ($timeline->isEmpty() && ! empty($report->equipment_report_history)) {
        // Fallback for callers that have not attached the new timeline yet.
        $timeline = collect($report->equipment_report_history)->map(function ($pastReport) use ($report) {
            return (object) [
                'type' => 'past_report',
                'at' => $pastReport->report_submitted_at,
                'title' => $report->equipment_name ?? $pastReport->equipment_name ?? 'Equipment',
                'subtitle' => ($pastReport->report_suggested_issue ?: 'Report')
                    .(! empty($pastReport->room_name) ? ' in '.$pastReport->room_name : ''),
                'urgency' => $pastReport->report_urgency_level ?? null,
                'status_label' => $pastReport->report_current_status,
                'status_key' => $pastReport->report_current_status,
                'meta' => trim(
                    ($pastReport->reporter_full_name ?? '')
                    .(! empty($pastReport->report_reporter_employee_id) ? ' · '.$pastReport->report_reporter_employee_id : '')
                ),
                'notes' => $pastReport->report_problem_description ?? null,
                'is_current' => (int) $pastReport->report_id === (int) $report->report_id,
            ];
        });
    }
@endphp

<div id="equipment-history-{{ $report->report_id }}">
    @if ($timeline->isEmpty())
        <p class="py-6 text-sm text-slate-400">No timeline events for this ticket yet.</p>
    @else
        <ol class="relative ml-3">
            @foreach ($timeline as $index => $event)
                @php
                    $isLast = $index === $timeline->count() - 1;
                    $isCurrent = (bool) ($event->is_current ?? false);
                    $statusKey = (string) ($event->status_key ?? '');
                    $statusPill = match ($statusKey) {
                        'Pending' => 'bg-amber-50 text-amber-700',
                        'Processing' => 'bg-sky-50 text-sky-700',
                        'Resolved' => 'bg-emerald-50 text-emerald-700',
                        'Rejected' => 'bg-rose-50 text-rose-700',
                        'For Replacement' => 'bg-orange-50 text-orange-700',
                        default => 'bg-slate-100 text-slate-600',
                    };
                    $typeLabel = match ((string) ($event->type ?? '')) {
                        'filed' => 'This ticket',
                        'item_status' => 'Item update',
                        'past_report' => 'Earlier report',
                        default => null,
                    };
                @endphp

                <li class="relative pb-8 {{ $isLast ? 'pb-0' : '' }}">
                    @if (!$isLast)
                        <span class="absolute left-[9px] top-6 h-[calc(100%-8px)] w-px bg-slate-200"></span>
                    @endif

                    <div class="flex gap-4">
                        <div class="relative z-10 mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $isCurrent ? 'bg-slate-900' : 'bg-slate-300' }}">
                            <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1 -mt-0.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[15px] font-semibold text-slate-900">
                                    {{ $event->title }}
                                </p>
                                @if ($typeLabel)
                                    <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-slate-500 ring-1 ring-slate-200/80">
                                        {{ $typeLabel }}
                                    </span>
                                @endif
                                @if (!empty($event->urgency))
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $event->urgency === 'Urgent' ? 'bg-rose-50 text-rose-700' : 'bg-neutral-50 text-slate-500 ring-1 ring-slate-200/80' }}">
                                        {{ $event->urgency }}
                                    </span>
                                @endif
                                @if (!empty($event->status_label))
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusPill }}">
                                        {{ $event->status_label }}
                                    </span>
                                @endif
                            </div>

                            @if (!empty($event->subtitle))
                                <p class="mt-0.5 text-sm text-slate-600">
                                    {{ $event->subtitle }}
                                </p>
                            @endif

                            @if (!empty($event->notes))
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    {{ \Illuminate\Support\Str::limit($event->notes, 140) }}
                                </p>
                            @endif

                            @if (!empty($event->meta))
                                <p class="mt-2 text-xs leading-5 text-slate-500">
                                    {{ $event->meta }}
                                </p>
                            @endif
                            @if (!empty($event->at))
                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ \Carbon\Carbon::parse($event->at)->format('M d, Y g:i A') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
