@extends('layouts.maintenance-layout')

@section('title', 'Maintenance Dashboard')

@section('content')
@php
    $todayKey = now()->toDateString();
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <div class="flex flex-col gap-5 xl:col-span-2 self-start">
            @include('partials.nearest-lifespan-broadcasts', [
                'lifespan' => $nearestLifespan ?? ['total' => 0, 'rows' => [], 'due' => []],
                'lifespanIndexUrl' => route('maintenance.lifespan-broadcasts.index'),
                'includeLifespanConfirm' => false,
            ])

            <div class="grid grid-cols-1 items-start gap-5 md:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5" id="attentionToggle">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-950">Schedules &amp; warranty</h2>
                    <a id="attentionToggleLink" href="{{ url('/maintenance/schedules/all') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                </div>
                <div class="mt-3 inline-flex rounded-full bg-slate-100 p-0.5" role="group" aria-label="Overdue schedule and warranty alerts">
                    <button type="button" data-attention="schedules" class="attention-switch is-on rounded-full px-3 py-1 text-xs font-semibold" aria-pressed="true">Overdue schedule</button>
                    <button type="button" data-attention="warranty" class="attention-switch rounded-full px-3 py-1 text-xs font-semibold" aria-pressed="false">Warranty alerts</button>
                </div>
                @php
                    $schedulePreviewRows = collect($overdueSchedules ?? [])->values()->map(function ($schedule) {
                        $due = \Carbon\Carbon::parse($schedule->maintenance_schedule_next_date);
                        $title = $schedule->maintenance_schedule_title ?: ($schedule->equipment_name ?: 'Maintenance');
                        $identity = \App\Support\EquipmentIdentity::label($schedule) ?: 'No asset tag / serial';

                        return [
                            'title' => $title,
                            'equipment' => $schedule->equipment_name ?: 'Equipment not set',
                            'identity' => $identity,
                            'room' => $schedule->room_name ?: 'No room',
                            'frequency' => $schedule->maintenance_schedule_frequency ?: 'Frequency not set',
                            'due' => $due->format('M j, Y'),
                            'dueRelative' => $due->diffForHumans(),
                            'meta' => collect([$schedule->equipment_name ?? null, $schedule->room_name ?? null, $schedule->maintenance_schedule_frequency ?? null])->filter()->implode(' · '),
                            'href' => url('/maintenance/schedules/all?search='.urlencode($title)),
                        ];
                    });
                    $warrantyPreviewRows = collect($warrantyAlerts ?? [])->values()->map(function ($alert) {
                        $days = (int) ($alert->days_remaining ?? 0);
                        $expired = $days < 0;
                        $expiry = $alert->equipment_warranty_expiration ?? null;
                        $identity = \App\Support\EquipmentIdentity::label($alert) ?: ($alert->equipment_asset_tag ?: 'No asset tag / serial');

                        return [
                            'title' => $alert->equipment_name ?: 'Equipment',
                            'identity' => $identity,
                            'room' => $alert->room_name ?: 'No room',
                            'status' => $alert->equipment_inventory_status ?: 'Status not set',
                            'expiry' => $expiry ? \Carbon\Carbon::parse($expiry)->format('M j, Y') : 'Not on file',
                            'hint' => $expired ? 'Expired' : $days.'d left',
                            'hintClass' => $expired ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700',
                            'href' => $alert->view_url ?? url('/maintenance/equipment/view/'.$alert->equipment_id),
                        ];
                    });
                @endphp
                <div data-attention-panel="schedules" class="mt-3 divide-y divide-slate-100">
                    @forelse ($schedulePreviewRows as $index => $row)
                        <button type="button" data-schedule-open="{{ $index }}" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block text-[11px] font-semibold uppercase tracking-wide text-slate-400">Schedule name</span>
                                <span class="mt-0.5 block truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                                <span class="mt-0.5 block truncate text-xs font-semibold text-slate-600">{{ $row['identity'] }}</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['meta'] }}</span>
                            </span>
                            <span class="shrink-0 text-xs font-semibold text-rose-700">{{ $row['due'] }}</span>
                        </button>
                    @empty
                        <p class="py-8 text-center text-xs font-medium text-slate-400">No overdue schedules</p>
                    @endforelse
                </div>
                <div data-attention-panel="warranty" class="mt-3 hidden divide-y divide-slate-100">
                    @forelse ($warrantyPreviewRows as $index => $row)
                        <button type="button" data-warranty-open="{{ $index }}" class="flex w-full items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">
                                    {{ $row['identity'] }}
                                    @if ($row['expiry'] !== 'Not on file')
                                        · Ends {{ $row['expiry'] }}
                                    @endif
                                </span>
                            </span>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $row['hintClass'] }}">{{ $row['hint'] }}</span>
                        </button>
                    @empty
                        <p class="py-8 text-center text-xs font-medium text-slate-400">No warranty alerts</p>
                    @endforelse
                </div>
                <p class="mt-2 text-xs text-slate-500">Showing up to 5</p>
                <script type="application/json" id="schedulePreviewData">@json($schedulePreviewRows)</script>
                <script type="application/json" id="warrantyPreviewData">@json($warrantyPreviewRows)</script>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-950">Recent Activities</h2>
                    <a href="{{ route('maintenance.activities.index') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                </div>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse (($recentActivities ?? collect())->take(5) as $activity)
                        <div class="flex items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $activity->title }}</p>
                                <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $activity->description }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</p>
                            </div>
                            @if (!empty($activity->url))
                                <a href="{{ $activity->url }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View</a>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-xs font-medium text-slate-400">No recent activity</p>
                    @endforelse
                </div>
                <p class="mt-2 text-xs text-slate-500">Showing up to 5</p>
            </section>
            </div>

            <div id="schedulePreview" class="fixed inset-0 z-[12000] hidden items-start justify-center bg-black/50 px-4 pt-16">
                <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
                    <button type="button" data-schedule-close class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <div class="px-6 pb-2 pr-12 pt-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Overdue schedule</p>
                        <p class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Schedule name</p>
                        <h3 id="schedulePreviewTitle" class="mt-1 text-lg font-semibold text-slate-950"></h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Equipment</dt>
                                <dd id="schedulePreviewEquipment" class="mt-1 text-slate-700"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Identity</dt>
                                <dd id="schedulePreviewIdentity" class="mt-1 text-slate-700"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Room</dt>
                                <dd id="schedulePreviewRoom" class="mt-1 text-slate-700"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Frequency</dt>
                                <dd id="schedulePreviewFrequency" class="mt-1 text-slate-700"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Due date</dt>
                                <dd id="schedulePreviewDue" class="mt-1 font-semibold text-rose-700"></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
                        <button type="button" data-schedule-close class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">Close</button>
                        <a id="schedulePreviewGo" href="#" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001fad]">Go to page</a>
                    </div>
                </div>
            </div>

            <div id="warrantyPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center bg-black/50 px-4 pt-16">
                <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
                    <button type="button" data-warranty-close class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <div class="px-6 pb-2 pr-12 pt-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Warranty alert</p>
                        <h3 id="warrantyPreviewTitle" class="mt-1 text-lg font-semibold text-slate-950"></h3>
                        <p id="warrantyPreviewIdentity" class="mt-1 text-sm text-slate-600"></p>
                        <span id="warrantyPreviewHint" class="mt-3 inline-flex rounded-full px-2.5 py-0.5 text-sm font-semibold"></span>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Room</dt>
                                <dd id="warrantyPreviewRoom" class="mt-1 text-slate-700"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Inventory status</dt>
                                <dd id="warrantyPreviewStatus" class="mt-1 text-slate-700"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Warranty ends</dt>
                                <dd id="warrantyPreviewExpiry" class="mt-1 text-slate-700"></dd>
                            </div>
                        </dl>
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
                        <button type="button" data-warranty-close class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">Close</button>
                        <a id="warrantyPreviewGo" href="#" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001fad]">Go to page</a>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex flex-col gap-5">
        <section class="mt-0 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex min-h-7 items-center justify-between gap-3">
                <h2 class="text-sm font-semibold leading-none text-slate-950">5 latest urgent reports</h2>
                <a href="{{ url('/maintenance/reports/urgent') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
            </div>
            @php
                $urgentStatusMap = [
                    'Pending' => 'bg-orange-50 text-orange-700',
                    'Processing' => 'bg-sky-50 text-sky-700',
                    'For Replacement' => 'bg-orange-100 text-orange-700',
                    'Resolved' => 'bg-emerald-50 text-emerald-700',
                    'Rejected' => 'bg-rose-50 text-rose-700',
                    'Closed / Cancelled' => 'bg-slate-200 text-slate-700',
                ];
                $urgentPreviewRows = collect($urgentReportList ?? [])->values()->map(function ($report) use ($urgentStatusMap) {
                    $title = $report->equipment_name ?: ($report->report_unlisted_equipment_name ?? 'Reported issue');
                    $place = collect([$report->room_name ?? null, $report->building_name ?? null])->filter()->implode(' · ');
                    $submitted = $report->report_submitted_at ? \Carbon\Carbon::parse($report->report_submitted_at) : null;
                    $ticket = \App\Support\ReportGrouping::ticketCode($report);
                    $identity = \App\Support\EquipmentIdentity::label($report) ?: 'No asset tag / serial';
                    $status = $report->report_current_status ?: 'Urgent';

                    return [
                        'id' => $report->report_id,
                        'ticket' => $ticket,
                        'title' => $title,
                        'identity' => $identity,
                        'status' => $status,
                        'statusClass' => $urgentStatusMap[$status] ?? 'bg-slate-100 text-slate-700',
                        'urgency' => $report->report_urgency_level ?: 'Urgent',
                        'place' => $place ?: 'Location not set',
                        'floor' => $report->floor_level ?? null,
                        'reporter' => $report->reporter_full_name ?: 'Reporter not set',
                        'when' => $submitted ? $submitted->diffForHumans() : '',
                        'submitted' => $submitted ? $submitted->format('M j, Y g:i A') : 'Not on file',
                        'description' => trim((string) ($report->report_problem_description ?? '')) ?: 'No problem description on file.',
                        'meta' => collect([$ticket, $identity, $place ?: null])->filter()->implode(' · '),
                        'href' => url('/maintenance/reports/details/'.$report->report_id),
                        'filterHref' => url('/maintenance/reports?search='.urlencode($ticket)),
                    ];
                });
            @endphp
            <div class="mt-3 divide-y divide-slate-100">
                @forelse ($urgentPreviewRows as $index => $row)
                    <div data-urgent-open="{{ $index }}" class="flex w-full cursor-pointer items-center justify-between gap-3 py-3 text-left transition hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                            <span class="mt-0.5 block truncate text-xs font-semibold text-slate-600">
                                <a href="{{ $row['filterHref'] }}" class="text-[#0025cc] hover:underline" title="Open in Equipment Reports" onclick="event.stopPropagation()">{{ $row['ticket'] }}</a>
                                <span class="text-slate-600"> · {{ $row['identity'] }}</span>
                            </span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ collect([$row['place'] !== 'Location not set' ? $row['place'] : null, $row['reporter'] !== 'Reporter not set' ? $row['reporter'] : null])->filter()->implode(' · ') }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $row['statusClass'] }}">{{ $row['status'] }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-medium text-slate-400">No open urgent reports</p>
                @endforelse
            </div>
            <script type="application/json" id="urgentPreviewData">@json($urgentPreviewRows)</script>
        </section>

        <div id="urgentPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center bg-black/50 px-4 pt-16">
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
                <button type="button" data-urgent-close class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="px-6 pb-2 pr-12 pt-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Urgent report</p>
                    <a id="urgentPreviewTicket" href="#" class="mt-1 inline-flex text-xs font-semibold text-[#0025cc] hover:underline" title="Open in Equipment Reports"></a>
                    <h3 id="urgentPreviewTitle" class="mt-1 text-lg font-semibold text-slate-950"></h3>
                    <p id="urgentPreviewIdentity" class="mt-1 text-sm text-slate-600"></p>
                    <span id="urgentPreviewStatus" class="mt-3 inline-flex rounded-full px-2.5 py-0.5 text-sm font-semibold"></span>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Location</dt>
                            <dd id="urgentPreviewPlace" class="mt-1 text-slate-700"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Reporter</dt>
                            <dd id="urgentPreviewReporter" class="mt-1 text-slate-700"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Submitted</dt>
                            <dd id="urgentPreviewSubmitted" class="mt-1 text-slate-700"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Problem</dt>
                            <dd id="urgentPreviewDescription" class="mt-1 whitespace-pre-wrap text-slate-700"></dd>
                        </div>
                    </dl>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
                    <button type="button" data-urgent-close class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">Close</button>
                    <a id="urgentPreviewGo" href="#" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001fad]">Go to page</a>
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-950">Maintenance calendar</h2>
                <a href="{{ url('/maintenance/schedules/all') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <label class="sr-only" for="maintenanceDashMonth">Month</label>
                <select id="maintenanceDashMonth" class="rounded-md border border-slate-200 bg-white py-1 pl-2 pr-7 text-xs font-semibold text-slate-800 focus:border-slate-300 focus:outline-none"></select>
                <label class="sr-only" for="maintenanceDashYear">Year</label>
                <select id="maintenanceDashYear" class="rounded-md border border-slate-200 bg-white py-1 pl-2 pr-7 text-xs font-semibold text-slate-800 focus:border-slate-300 focus:outline-none"></select>
                <button type="button" id="maintenanceDashToday" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-[#0025cc]" title="Back to today" aria-label="Back to today">
                    <i data-lucide="calendar-check" class="h-3.5 w-3.5"></i>
                </button>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-semibold text-slate-500">
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>Equipment</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>Room</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Semestral</span>
            </div>
            <div class="mt-3 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-slate-400">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                    <div>{{ $weekday }}</div>
                @endforeach
            </div>
            <div id="maintenanceDashDays" class="mt-1 grid grid-cols-7 gap-1 text-center text-sm"></div>
            <div id="maintenanceDashEvents" class="mt-4 border-t border-slate-100 pt-3 text-sm text-slate-500">Nothing scheduled</div>
            <script type="application/json" id="maintenanceDashEventData">@json(collect($calendarEvents ?? [])->values())</script>
        </section>

        <div id="maintenanceDashCalendarPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center bg-black/50 px-4 pt-16">
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-[0_20px_50px_rgba(15,23,42,0.18)] ring-1 ring-slate-200/80">
                <button type="button" data-dash-cal-preview-close class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="px-6 pb-2 pr-12 pt-6">
                    <p id="dashCalPreviewDate" class="text-sm font-medium text-slate-400"></p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Schedule name</p>
                    <h3 id="dashCalPreviewTitle" class="mt-0.5 text-lg font-semibold tracking-tight text-slate-900"></h3>
                    <p id="dashCalPreviewMeta" class="mt-1 text-base text-slate-500"></p>
                    <p id="dashCalPreviewIdentity" class="mt-1 hidden text-base font-medium text-slate-700"></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span id="dashCalPreviewBadge" class="rounded-full px-2.5 py-0.5 text-sm font-semibold ring-1"></span>
                        <span id="dashCalPreviewStatus" class="rounded-full px-2.5 py-0.5 text-sm font-semibold"></span>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-3.5">
                    <button type="button" data-dash-cal-preview-close class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">Close</button>
                    <a id="dashCalPreviewGo" href="#" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001fad]">Go to page</a>
                </div>
            </div>
        </div>

        </div>
    </div>

</div>

<script>
    (function () {
        var preview = document.getElementById('urgentPreview');
        var rows = [];
        try {
            rows = JSON.parse((document.getElementById('urgentPreviewData') || {}).textContent || '[]');
        } catch (error) {
            rows = [];
        }
        if (preview && preview.parentElement !== document.body) {
            document.body.appendChild(preview);
        }
        function closeUrgentPreview() {
            if (!preview) return;
            preview.classList.add('hidden');
            preview.classList.remove('flex');
        }
        function openUrgentPreview(row) {
            if (!preview || !row) return;
            var ticketLink = document.getElementById('urgentPreviewTicket');
            ticketLink.textContent = row.ticket || ('RPT-' + (row.id || ''));
            ticketLink.href = row.filterHref || ('/maintenance/reports?search=' + encodeURIComponent(ticketLink.textContent));
            document.getElementById('urgentPreviewTitle').textContent = row.title || 'Reported issue';
            document.getElementById('urgentPreviewIdentity').textContent = row.identity || 'No asset tag / serial';
            var statusBadge = document.getElementById('urgentPreviewStatus');
            statusBadge.textContent = row.status || 'Urgent';
            statusBadge.className = 'mt-3 inline-flex rounded-full px-2.5 py-0.5 text-sm font-semibold ' + (row.statusClass || 'bg-slate-100 text-slate-700');
            document.getElementById('urgentPreviewPlace').textContent = row.place || 'Location not set';
            document.getElementById('urgentPreviewReporter').textContent = row.reporter || 'Reporter not set';
            var submitted = row.submitted || 'Not on file';
            if (row.when) submitted += ' · ' + row.when;
            document.getElementById('urgentPreviewSubmitted').textContent = submitted;
            document.getElementById('urgentPreviewDescription').textContent = row.description || 'No problem description on file.';
            document.getElementById('urgentPreviewGo').href = row.href || '#';
            preview.classList.remove('hidden');
            preview.classList.add('flex');
        }
        document.addEventListener('click', function (event) {
            var open = event.target.closest('[data-urgent-open]');
            if (open) {
                event.preventDefault();
                openUrgentPreview(rows[Number(open.getAttribute('data-urgent-open'))] || null);
                return;
            }
            if (event.target === preview || event.target.closest('[data-urgent-close]')) {
                closeUrgentPreview();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeUrgentPreview();
        });
    })();
    (function () {
        var schedulePreview = document.getElementById('schedulePreview');
        var warrantyPreview = document.getElementById('warrantyPreview');
        var scheduleRows = [];
        var warrantyRows = [];
        try {
            scheduleRows = JSON.parse((document.getElementById('schedulePreviewData') || {}).textContent || '[]');
        } catch (error) {
            scheduleRows = [];
        }
        try {
            warrantyRows = JSON.parse((document.getElementById('warrantyPreviewData') || {}).textContent || '[]');
        } catch (error) {
            warrantyRows = [];
        }
        if (schedulePreview && schedulePreview.parentElement !== document.body) {
            document.body.appendChild(schedulePreview);
        }
        if (warrantyPreview && warrantyPreview.parentElement !== document.body) {
            document.body.appendChild(warrantyPreview);
        }
        function closeSchedulePreview() {
            if (!schedulePreview) return;
            schedulePreview.classList.add('hidden');
            schedulePreview.classList.remove('flex');
        }
        function closeWarrantyPreview() {
            if (!warrantyPreview) return;
            warrantyPreview.classList.add('hidden');
            warrantyPreview.classList.remove('flex');
        }
        function openSchedulePreview(row) {
            if (!schedulePreview || !row) return;
            document.getElementById('schedulePreviewTitle').textContent = row.title || 'Maintenance';
            document.getElementById('schedulePreviewEquipment').textContent = row.equipment || 'Equipment not set';
            document.getElementById('schedulePreviewIdentity').textContent = row.identity || 'No asset tag / serial';
            document.getElementById('schedulePreviewRoom').textContent = row.room || 'No room';
            document.getElementById('schedulePreviewFrequency').textContent = row.frequency || 'Frequency not set';
            var due = row.due || 'Date not on file';
            if (row.dueRelative) due += ' · ' + row.dueRelative;
            document.getElementById('schedulePreviewDue').textContent = due;
            document.getElementById('schedulePreviewGo').href = row.href || '#';
            closeWarrantyPreview();
            schedulePreview.classList.remove('hidden');
            schedulePreview.classList.add('flex');
        }
        function openWarrantyPreview(row) {
            if (!warrantyPreview || !row) return;
            document.getElementById('warrantyPreviewTitle').textContent = row.title || 'Equipment';
            document.getElementById('warrantyPreviewIdentity').textContent = row.identity || 'No asset tag / serial';
            var hint = document.getElementById('warrantyPreviewHint');
            hint.textContent = row.hint || '';
            hint.className = 'mt-3 inline-flex rounded-full px-2.5 py-0.5 text-sm font-semibold ' + (row.hintClass || 'bg-amber-50 text-amber-700');
            document.getElementById('warrantyPreviewRoom').textContent = row.room || 'No room';
            document.getElementById('warrantyPreviewStatus').textContent = row.status || 'Status not set';
            document.getElementById('warrantyPreviewExpiry').textContent = row.expiry || 'Not on file';
            document.getElementById('warrantyPreviewGo').href = row.href || '#';
            closeSchedulePreview();
            warrantyPreview.classList.remove('hidden');
            warrantyPreview.classList.add('flex');
        }
        document.addEventListener('click', function (event) {
            var scheduleOpen = event.target.closest('[data-schedule-open]');
            if (scheduleOpen) {
                event.preventDefault();
                openSchedulePreview(scheduleRows[Number(scheduleOpen.getAttribute('data-schedule-open'))] || null);
                return;
            }
            var warrantyOpen = event.target.closest('[data-warranty-open]');
            if (warrantyOpen) {
                event.preventDefault();
                openWarrantyPreview(warrantyRows[Number(warrantyOpen.getAttribute('data-warranty-open'))] || null);
                return;
            }
            if (event.target === schedulePreview || event.target.closest('[data-schedule-close]')) {
                closeSchedulePreview();
            }
            if (event.target === warrantyPreview || event.target.closest('[data-warranty-close]')) {
                closeWarrantyPreview();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            closeSchedulePreview();
            closeWarrantyPreview();
        });
    })();
    (function () {
        var events = [];
        try {
            events = JSON.parse((document.getElementById('maintenanceDashEventData') || {}).textContent || '[]');
        } catch (error) {
            events = [];
        }
        var box = document.getElementById('maintenanceDashEvents');
        var days = document.getElementById('maintenanceDashDays');
        var monthSelect = document.getElementById('maintenanceDashMonth');
        var yearSelect = document.getElementById('maintenanceDashYear');
        var todayKey = @json($todayKey);
        var view = { year: Number(todayKey.slice(0, 4)), month: Number(todayKey.slice(5, 7)) - 1 };
        var selected = todayKey;
        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>]/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[char];
            });
        }
        function pad(number) {
            return number < 10 ? '0' + number : String(number);
        }
        function dateKey(year, month, day) {
            return year + '-' + pad(month + 1) + '-' + pad(day);
        }
        function fillYears() {
            var years = events.map(function (event) { return Number(String(event.date || '').slice(0, 4)); }).filter(Boolean);
            years.push(view.year);
            var min = Math.min.apply(null, years);
            var max = Math.max.apply(null, years);
            yearSelect.innerHTML = '';
            for (var year = min; year <= max; year++) {
                var option = document.createElement('option');
                option.value = String(year);
                option.textContent = String(year);
                yearSelect.appendChild(option);
            }
            yearSelect.value = String(view.year);
        }
        function fillMonths() {
            monthSelect.innerHTML = monthNames.map(function (name, index) {
                return '<option value="' + index + '">' + name + '</option>';
            }).join('');
            monthSelect.value = String(view.month);
        }
        var preview = document.getElementById('maintenanceDashCalendarPreview');
        var tones = {
            equipment: 'bg-sky-50 text-sky-700 ring-sky-200',
            room: 'bg-violet-50 text-violet-700 ring-violet-200',
            semestral: 'bg-amber-50 text-amber-700 ring-amber-200'
        };
        var dotTones = {
            equipment: 'bg-sky-500',
            room: 'bg-violet-500',
            semestral: 'bg-amber-500'
        };
        if (preview && preview.parentElement !== document.body) {
            document.body.appendChild(preview);
        }
        function openDashCalPreview(row) {
            if (!preview || !row) return;
            document.getElementById('dashCalPreviewDate').textContent = row.date_label || row.date || '';
            document.getElementById('dashCalPreviewTitle').textContent = row.title || 'Maintenance';
            var meta = document.getElementById('dashCalPreviewMeta');
            meta.textContent = row.meta || [row.description, row.location, row.frequency].filter(Boolean).join(' · ');
            meta.classList.toggle('hidden', !meta.textContent);
            var identity = document.getElementById('dashCalPreviewIdentity');
            identity.textContent = row.identity || '';
            identity.classList.toggle('hidden', !row.identity);
            var badge = document.getElementById('dashCalPreviewBadge');
            badge.textContent = row.badge || 'Equipment';
            badge.className = 'rounded-full px-2.5 py-0.5 text-sm font-semibold ring-1 ' + (tones[row.tone] || tones.equipment);
            var status = document.getElementById('dashCalPreviewStatus');
            status.textContent = row.status || '';
            status.className = 'rounded-full px-2.5 py-0.5 text-sm font-semibold ' + (row.status === 'Overdue' ? 'bg-rose-50 text-rose-700' : (row.status === 'Completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'));
            status.classList.toggle('hidden', !row.status);
            document.getElementById('dashCalPreviewGo').href = row.href || row.url || '#';
            preview.classList.remove('hidden');
            preview.classList.add('flex');
        }
        function closeDashCalPreview() {
            if (!preview) return;
            preview.classList.add('hidden');
            preview.classList.remove('flex');
        }
        function showDay(key) {
            if (!box) return;
            selected = key;
            var rows = events.filter(function (event) { return event.date === key; });
            days.querySelectorAll('[data-cal-day]').forEach(function (button) {
                var on = button.getAttribute('data-cal-day') === key;
                button.classList.toggle('ring-2', on);
                button.classList.toggle('ring-[#0025cc]', on);
            });
            if (!rows.length) {
                box.textContent = 'Nothing scheduled';
                return;
            }
            box.innerHTML = '';
            rows.forEach(function (event, index) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex w-full items-start justify-between gap-3 py-1.5 text-left transition hover:bg-slate-50';
                button.setAttribute('data-dash-cal-open', String(index));
                var text = document.createElement('span');
                text.className = 'min-w-0';
                var title = document.createElement('span');
                title.className = 'block font-semibold text-slate-900';
                title.textContent = event.title || 'Scheduled';
                text.appendChild(title);
                var detail = event.meta || [event.description, event.location].filter(Boolean).join(' · ');
                if (detail) {
                    var meta = document.createElement('span');
                    meta.className = 'mt-0.5 block text-xs text-slate-500';
                    meta.textContent = detail;
                    text.appendChild(meta);
                }
                button.appendChild(text);
                var badge = document.createElement('span');
                badge.className = 'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ' + (tones[event.tone] || tones.equipment);
                badge.textContent = event.badge || 'Equipment';
                button.appendChild(badge);
                button.addEventListener('click', function () { openDashCalPreview(event); });
                box.appendChild(button);
            });
        }
        function render() {
            var first = new Date(view.year, view.month, 1);
            var count = new Date(view.year, view.month + 1, 0).getDate();
            var html = '';
            for (var blank = 0; blank < first.getDay(); blank++) html += '<div></div>';
            for (var day = 1; day <= count; day++) {
                var key = dateKey(view.year, view.month, day);
                var dayEvents = events.filter(function (event) { return event.date === key; });
                var isToday = key === todayKey;
                var dots = '';
                if (dayEvents.length) {
                    var seen = {};
                    dayEvents.forEach(function (event) {
                        var tone = event.tone || 'equipment';
                        if (seen[tone]) return;
                        seen[tone] = true;
                        dots += '<span class="h-1 w-1 rounded-full ' + (isToday ? 'bg-white' : (dotTones[tone] || 'bg-rose-500')) + '"></span>';
                    });
                }
                html += '<button type="button" data-cal-day="' + key + '" class="relative mx-auto flex h-8 w-8 items-center justify-center rounded-full '
                    + (isToday ? 'bg-[#0025cc] text-white hover:bg-[#001fad]' : 'text-slate-700 hover:bg-slate-100') + '">'
                    + day
                    + (dots ? '<span class="absolute bottom-0.5 flex items-center gap-0.5">' + dots + '</span>' : '')
                    + '</button>';
            }
            days.innerHTML = html;
            var inView = selected.slice(0, 7) === dateKey(view.year, view.month, 1).slice(0, 7);
            showDay(inView ? selected : dateKey(view.year, view.month, 1));
        }
        fillMonths();
        fillYears();
        monthSelect.addEventListener('change', function () {
            view.month = Number(monthSelect.value);
            render();
        });
        yearSelect.addEventListener('change', function () {
            view.year = Number(yearSelect.value);
            render();
        });
        document.getElementById('maintenanceDashToday')?.addEventListener('click', function () {
            view.year = Number(todayKey.slice(0, 4));
            view.month = Number(todayKey.slice(5, 7)) - 1;
            selected = todayKey;
            monthSelect.value = String(view.month);
            if (![].some.call(yearSelect.options, function (option) { return option.value === String(view.year); })) fillYears();
            yearSelect.value = String(view.year);
            render();
        });
        days.addEventListener('click', function (event) {
            var button = event.target.closest('[data-cal-day]');
            if (!button) return;
            showDay(button.getAttribute('data-cal-day'));
        });
        if (preview) {
            preview.addEventListener('click', function (event) {
                if (event.target === preview || event.target.closest('[data-dash-cal-preview-close]')) {
                    closeDashCalPreview();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeDashCalPreview();
            });
        }
        render();
        if (window.lucide) window.lucide.createIcons();
    })();
    (function () {
        var card = document.getElementById('attentionToggle');
        if (!card) return;
        var link = document.getElementById('attentionToggleLink');
        var links = {
            schedules: @json(url('/maintenance/schedules/all')),
            warranty: @json(url('/maintenance/equipment/inventory'))
        };
        var labels = { schedules: 'View all', warranty: 'Inventory' };
        card.addEventListener('click', function (event) {
            var button = event.target.closest('[data-attention]');
            if (!button || button.closest('[data-attention-panel]')) return;
            var key = button.getAttribute('data-attention');
            card.querySelectorAll('.attention-switch').forEach(function (item) {
                var on = item === button;
                item.classList.toggle('is-on', on);
                item.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            card.querySelectorAll('[data-attention-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-attention-panel') !== key);
            });
            if (link) {
                link.href = links[key] || '#';
                link.textContent = labels[key] || 'View all';
            }
        });
    })();
</script>
<style>
    .attention-switch { color: #64748b; }
    .attention-switch.is-on { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08); }
</style>
@endsection
