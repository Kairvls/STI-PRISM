@php
    $equipmentHistory = collect($report->equipment_report_history ?? []);
    $itemName = $report->equipment_name
        ?? $report->report_unlisted_equipment_name
        ?? 'This item';
@endphp

<div id="equipment-history-{{ $report->report_id }}">
    @if (!$report->report_equipment_id || $equipmentHistory->isEmpty())
        <p class="py-6 text-sm text-slate-400">No earlier reports for this item.</p>
    @else
        <ol class="relative ml-3">
            @foreach ($equipmentHistory as $index => $pastReport)
                @php
                    $isCurrent = (int) $pastReport->report_id === (int) $report->report_id;
                    $isLast = $index === $equipmentHistory->count() - 1;
                    $statusLabel = match ($pastReport->report_current_status) {
                        'Pending' => 'Waiting for staff',
                        'Processing' => 'In progress',
                        'Resolved' => 'Fixed',
                        'Rejected' => 'Not accepted',
                        'For Replacement' => 'Needs replacement',
                        default => $pastReport->report_current_status,
                    };
                    $entryName = $report->equipment_name
                        ?? $pastReport->report_unlisted_equipment_name
                        ?? $itemName;
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
                                    {{ $entryName }}
                                </p>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $pastReport->report_urgency_level === 'Urgent' ? 'bg-rose-50 text-rose-700' : 'bg-neutral-50 text-slate-500 ring-1 ring-slate-200/80' }}">
                                    {{ $pastReport->report_urgency_level ?? 'Non-Urgent' }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <p class="mt-0.5 text-sm text-slate-600">
                                {{ $pastReport->report_suggested_issue ?: 'No problem named' }}
                                @if ($pastReport->room_name)
                                    in {{ $pastReport->room_name }}
                                @endif
                            </p>

                            @if ($pastReport->report_problem_description)
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    {{ \Illuminate\Support\Str::limit($pastReport->report_problem_description, 120) }}
                                </p>
                            @endif

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                {{ $pastReport->reporter_full_name ?? 'Unknown reporter' }}
                                @if ($pastReport->report_reporter_employee_id)
                                    · {{ $pastReport->report_reporter_employee_id }}
                                @endif
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ \Carbon\Carbon::parse($pastReport->report_submitted_at)->format('M d, Y g:i A') }}
                            </p>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>
