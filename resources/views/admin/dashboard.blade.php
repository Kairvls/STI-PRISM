@extends('layouts.admin-layout')

@section('title', 'Administrator Dashboard')

@section('content')
@php
    $canPurchaser = (bool) ($overview['can_purchaser'] ?? false);
    $stages = $overview['stage_counts'] ?? [];
@endphp

<div class="admin-page admin-dash">

    {{-- ========== Header ========== --}}
    <header class="admin-dash-header">
        <div>
            <p class="admin-dash-kicker">Administrator</p>
            <h1 class="admin-page-title">Overview</h1>
            <p class="admin-page-subtitle">Actions, procurement, and campus movements — all in one view.</p>
        </div>
        <div class="admin-dash-header-meta">
            <time datetime="{{ now()->toDateString() }}">{{ now()->format('D, M j · Y') }}</time>
            <span class="admin-dash-pill {{ $attentionTotal > 0 ? 'is-alert' : 'is-ok' }}">
                {{ $attentionTotal > 0 ? $attentionTotal.' need attention' : 'All clear' }}
            </span>
        </div>
    </header>

    {{-- ========== Attention strip ========== --}}
    <section class="admin-dash-strip" aria-label="Attention metrics">
        <a href="{{ route('admin.procurement-review.ris', ['filter' => 'pending']) }}" class="admin-dash-metric {{ $pendingRis > 0 ? 'is-hot' : '' }}">
            <span class="admin-dash-metric-label">Accept</span>
            <span class="admin-dash-metric-value">{{ $pendingRis }}</span>
            <span class="admin-dash-metric-hint">₱{{ number_format((float) $pendingRisAmount, 0) }}</span>
        </a>
        <a href="{{ route('admin.digital-signatures.sign-ris', ['filter' => 'pending']) }}" class="admin-dash-metric {{ $forCosigningCount > 0 ? 'is-hot' : '' }}">
            <span class="admin-dash-metric-label">Sign</span>
            <span class="admin-dash-metric-value">{{ $forCosigningCount }}</span>
            <span class="admin-dash-metric-hint">Issued-by</span>
        </a>
        <a href="{{ route('admin.procurement-review.ris') }}" class="admin-dash-metric {{ $amendRis > 0 ? 'is-hot' : '' }}">
            <span class="admin-dash-metric-label">Amend</span>
            <span class="admin-dash-metric-value">{{ $amendRis }}</span>
            <span class="admin-dash-metric-hint">Needs revision</span>
        </a>
        <a href="{{ route('admin.operations.reports', ['filter' => 'urgent']) }}" class="admin-dash-metric {{ ($overview['urgent_reports'] ?? 0) > 0 ? 'is-hot' : '' }}">
            <span class="admin-dash-metric-label">Urgent</span>
            <span class="admin-dash-metric-value">{{ $overview['urgent_reports'] ?? 0 }}</span>
            <span class="admin-dash-metric-hint">{{ $overview['open_reports'] ?? 0 }} open</span>
        </a>
        <a href="{{ route('admin.operations.schedules', ['filter' => 'overdue']) }}" class="admin-dash-metric {{ ($overview['overdue_schedules'] ?? 0) > 0 ? 'is-hot' : '' }}">
            <span class="admin-dash-metric-label">Schedules</span>
            <span class="admin-dash-metric-value">{{ $overview['overdue_schedules'] ?? 0 }}</span>
            <span class="admin-dash-metric-hint">Overdue</span>
        </a>
        <a href="{{ route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']) }}" class="admin-dash-metric {{ ($overview['overdue_borrows'] ?? 0) > 0 ? 'is-hot' : '' }}">
            <span class="admin-dash-metric-label">Borrows</span>
            <span class="admin-dash-metric-value">{{ $overview['overdue_borrows'] ?? 0 }}</span>
            <span class="admin-dash-metric-hint">{{ $overview['active_borrows'] ?? 0 }} active</span>
        </a>
    </section>

    {{-- ========== Main: Actions + Procurement ========== --}}
    <div class="admin-dash-grid-main">

        <div class="admin-dash-side">
        <section class="admin-dash-panel">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Your queue</h2>
                    <p class="admin-dash-panel-sub">Work waiting on Administrator</p>
                </div>
                <div class="admin-dash-links">
                    <a href="{{ route('admin.procurement-review.ris', ['filter' => 'pending']) }}">Accept</a>
                    <a href="{{ route('admin.digital-signatures.sign-ris', ['filter' => 'pending']) }}">Sign</a>
                    @if($canPurchaser)
                        <a href="{{ url('/purchaser/dashboard') }}">Purchaser</a>
                    @endif
                </div>
            </div>

            <div class="admin-dash-queue-block">
                <div class="admin-dash-queue-label">
                    <span>Accept RIS</span>
                    <a href="{{ route('admin.procurement-review.ris', ['filter' => 'pending']) }}">View all</a>
                </div>
                <ul class="admin-dash-list">
                    @forelse($actionPendingRis as $ris)
                        <li>
                            <div class="admin-dash-list-main">
                                <p class="admin-dash-list-title">{{ \App\Support\RisWorkflow::formNumber($ris) }}</p>
                                <p class="admin-dash-list-meta">{{ \Illuminate\Support\Str::limit($ris->ris_purpose_description, 56) ?: $ris->ris_status }}</p>
                            </div>
                            <div class="admin-dash-list-aside">
                                <span>₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 0) }}</span>
                                <button type="button" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')">View</button>
                            </div>
                        </li>
                    @empty
                        <li class="admin-dash-empty">Nothing waiting for accept.</li>
                    @endforelse
                </ul>
            </div>

            <div class="admin-dash-queue-block">
                <div class="admin-dash-queue-label">
                    <span>Sign RIS</span>
                    <a href="{{ route('admin.digital-signatures.sign-ris', ['filter' => 'pending']) }}">View all</a>
                </div>
                <ul class="admin-dash-list">
                    @forelse($actionSignRis as $ris)
                        <li>
                            <div class="admin-dash-list-main">
                                <p class="admin-dash-list-title">{{ \App\Support\RisWorkflow::formNumber($ris) }}</p>
                                <p class="admin-dash-list-meta">{{ $ris->ris_status }}</p>
                            </div>
                            <div class="admin-dash-list-aside">
                                <span>₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 0) }}</span>
                                <button type="button" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')">View</button>
                            </div>
                        </li>
                    @empty
                        <li class="admin-dash-empty">Nothing waiting for signature.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- Equipment broadcasting / replacement suggestions --}}
        <section class="admin-dash-panel">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Equipment broadcasts</h2>
                    <p class="admin-dash-panel-sub">Aging assets nearing end of useful life</p>
                </div>
                <a class="admin-dash-text-link" href="{{ route('admin.operations.equipment', ['filter' => 'lifecycle']) }}">Lifecycle</a>
            </div>

            <ul class="admin-dash-list">
                @forelse(($lifecycleAlerts ?? collect()) as $alert)
                    @php
                        $yearsLeft = (int) ($alert->years_remaining ?? 0);
                        $lifeYears = (int) ($alert->useful_life_years ?? ($usefulLifeYears ?? 5));
                        $alreadyMarked = strcasecmp((string) ($alert->equipment_inventory_status ?? ''), 'For Replacement') === 0;
                        if ($yearsLeft < 0) {
                            $actionLabel = 'Replace overdue';
                            $actionHint = abs($yearsLeft) . 'y past lifespan';
                            $tagClass = 'is-alert';
                        } elseif ($yearsLeft === 0) {
                            $actionLabel = 'Replace this year';
                            $actionHint = 'End of ' . $lifeYears . 'y life';
                            $tagClass = 'is-alert';
                        } else {
                            $actionLabel = 'Plan replacement';
                            $actionHint = '~' . $yearsLeft . 'y left';
                            $tagClass = '';
                        }
                        if ($alreadyMarked) {
                            $actionLabel = 'Marked for replacement';
                            $tagClass = 'is-alert';
                        }
                    @endphp
                    <li>
                        <div class="admin-dash-list-main">
                            <p class="admin-dash-list-title">{{ $alert->equipment_name }}</p>
                            <p class="admin-dash-list-meta">
                                {{ $alert->room_name ?: 'No room' }}
                                · Age {{ (int) ($alert->age_years ?? 0) }}y
                                · Lifespan {{ $lifeYears }}y
                                · Suggested: {{ $actionLabel }}
                            </p>
                        </div>
                        <span class="admin-dash-tag {{ $tagClass }}">{{ $actionHint }}</span>
                    </li>
                @empty
                    <li class="admin-dash-empty">No aging equipment needing replacement attention.</li>
                @endforelse
            </ul>

            @if(($lifecycleAlerts ?? collect())->isNotEmpty())
                <p class="admin-dash-panel-sub" style="margin-top: 12px;">
                    Maintenance can set each asset’s useful lifespan. Assets within 1 year of that horizon appear here for replacement planning.
                </p>
            @endif
        </section>

        {{-- Top 3 near-due maintenance schedules --}}
        <section class="admin-dash-panel">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Due for maintenance</h2>
                    <p class="admin-dash-panel-sub">Top 3 schedules overdue or due within 14 days</p>
                </div>
                <a class="admin-dash-text-link" href="{{ route('admin.operations.schedules', ['filter' => 'upcoming']) }}">Schedules</a>
            </div>
            <ul class="admin-dash-list">
                @forelse(($upcomingMaintenanceSchedules ?? collect()) as $schedule)
                    @php
                        $nextDate = !empty($schedule->maintenance_schedule_next_date)
                            ? \Carbon\Carbon::parse($schedule->maintenance_schedule_next_date)->startOfDay()
                            : null;
                        $today = now()->startOfDay();
                        if ($nextDate) {
                            if ($nextDate->lt($today)) {
                                $daysUntil = -(int) $nextDate->diffInDays($today);
                            } elseif ($nextDate->equalTo($today)) {
                                $daysUntil = 0;
                            } else {
                                $daysUntil = (int) $today->diffInDays($nextDate);
                            }
                        } else {
                            $daysUntil = null;
                        }
                        $isOverdue = ($daysUntil !== null && $daysUntil < 0)
                            || strcasecmp((string) ($schedule->maintenance_schedule_status ?? ''), 'Overdue') === 0;
                        if ($isOverdue) {
                            $tagLabel = $daysUntil !== null && $daysUntil < 0
                                ? abs($daysUntil).'d overdue'
                                : 'Overdue';
                            $tagClass = 'is-alert';
                        } elseif ($daysUntil === 0) {
                            $tagLabel = 'Due today';
                            $tagClass = 'is-alert';
                        } elseif ($daysUntil !== null) {
                            $tagLabel = 'In '.$daysUntil.'d';
                            $tagClass = '';
                        } else {
                            $tagLabel = $schedule->maintenance_schedule_status ?: 'Scheduled';
                            $tagClass = '';
                        }
                    @endphp
                    <li>
                        <div class="admin-dash-list-main">
                            <p class="admin-dash-list-title">
                                {{ $schedule->equipment_name ?: ($schedule->maintenance_schedule_title ?: 'Equipment') }}
                            </p>
                            <p class="admin-dash-list-meta">
                                {{ $schedule->room_name ?: 'No room' }}
                                @if(!empty($schedule->maintenance_schedule_title) && $schedule->equipment_name)
                                    · {{ $schedule->maintenance_schedule_title }}
                                @endif
                                @if($nextDate)
                                    · {{ $nextDate->format('M j, Y') }}
                                @endif
                                @if(!empty($schedule->maintenance_schedule_frequency))
                                    · {{ $schedule->maintenance_schedule_frequency }}
                                @endif
                            </p>
                        </div>
                        <span class="admin-dash-tag {{ $tagClass }}">{{ $tagLabel }}</span>
                    </li>
                @empty
                    <li class="admin-dash-empty">No schedules due soon.</li>
                @endforelse
            </ul>
        </section>

        {{-- Semester school inspections --}}
        <section class="admin-dash-panel">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Semester inspections</h2>
                    <p class="admin-dash-panel-sub">School checks overdue or due within 7 days</p>
                </div>
            </div>
            <ul class="admin-dash-list">
                @forelse(($semesterInspectionDue ?? collect()) as $campaign)
                    @php
                        $due = \Carbon\Carbon::parse($campaign->campaign_due_date)->startOfDay();
                        $today = now()->startOfDay();
                        $daysUntil = (int) $today->diffInDays($due, false);
                        if ($daysUntil < 0) {
                            $tagLabel = abs($daysUntil).'d overdue';
                            $tagClass = 'is-alert';
                        } elseif ($daysUntil === 0) {
                            $tagLabel = 'Due today';
                            $tagClass = 'is-alert';
                        } else {
                            $tagLabel = 'In '.$daysUntil.'d';
                            $tagClass = '';
                        }
                    @endphp
                    <li>
                        <div class="admin-dash-list-main">
                            <p class="admin-dash-list-title">{{ $campaign->campaign_title }}</p>
                            <p class="admin-dash-list-meta">
                                {{ $campaign->campaign_semester }}
                                @if (!empty($campaign->campaign_academic_year))
                                    · {{ $campaign->campaign_academic_year }}
                                @endif
                                · {{ $due->format('M j, Y') }}
                                · {{ $campaign->campaign_status }}
                            </p>
                        </div>
                        <span class="admin-dash-tag {{ $tagClass }}">{{ $tagLabel }}</span>
                    </li>
                @empty
                    <li class="admin-dash-empty">No semester inspections due soon.</li>
                @endforelse
            </ul>
        </section>

        {{-- Top 3 overdue borrowings --}}
        <section class="admin-dash-panel">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Overdue borrows</h2>
                    <p class="admin-dash-panel-sub">Top 3 items past expected return</p>
                </div>
                <a class="admin-dash-text-link" href="{{ route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']) }}">
                    All {{ (int) ($overview['overdue_borrows'] ?? 0) }}
                </a>
            </div>
            <ul class="admin-dash-list">
                @forelse(($overdueBorrowsPreview ?? collect()) as $borrow)
                    @php
                        $returnDate = !empty($borrow->borrowing_expected_return_date)
                            ? \Carbon\Carbon::parse($borrow->borrowing_expected_return_date)->startOfDay()
                            : null;
                        $today = now()->startOfDay();
                        if ($returnDate && $returnDate->lt($today)) {
                            $daysOverdue = (int) $returnDate->diffInDays($today);
                        } else {
                            $daysOverdue = null;
                        }
                    @endphp
                    <li>
                        <div class="admin-dash-list-main">
                            <p class="admin-dash-list-title">{{ $borrow->equipment_name ?: 'Equipment' }}</p>
                            <p class="admin-dash-list-meta">
                                {{ $borrow->borrowing_borrower_name ?: 'Unknown borrower' }}
                                @if($returnDate)
                                    · Due {{ $returnDate->format('M j, Y') }}
                                @endif
                            </p>
                        </div>
                        <span class="admin-dash-tag is-alert">
                            {{ $daysOverdue !== null ? $daysOverdue.'d overdue' : 'Overdue' }}
                        </span>
                    </li>
                @empty
                    <li class="admin-dash-empty">No overdue borrows.</li>
                @endforelse
            </ul>
        </section>

        {{-- Proposed budget by year --}}
        <section class="admin-dash-panel">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Proposed budget</h2>
                    <p class="admin-dash-panel-sub">RIS totals for the selected year</p>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-dash-year-filter">
                    <label for="budget_year" class="sr-only">Budget year</label>
                    <select id="budget_year" name="budget_year" onchange="this.form.submit()" class="admin-dash-year-select">
                        @foreach(($budgetProposalYears ?? collect([(int) now()->year])) as $yearOption)
                            <option value="{{ $yearOption }}" @selected((int) ($budgetProposalYear ?? now()->year) === (int) $yearOption)>
                                {{ $yearOption }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="admin-dash-budget-hero">
                <p class="admin-dash-budget-label">Proposed {{ $budgetProposalYear ?? now()->year }}</p>
                <p class="admin-dash-budget-hero-value">₱{{ number_format((float) ($budgetProposalTotal ?? 0), 2) }}</p>
                <p class="admin-dash-panel-sub">{{ (int) ($budgetProposalRisCount ?? 0) }} RIS record{{ (int) ($budgetProposalRisCount ?? 0) === 1 ? '' : 's' }}</p>
            </div>

            <div class="admin-dash-budget">
                <div>
                    <p class="admin-dash-budget-label">Pending</p>
                    <p class="admin-dash-budget-value">₱{{ number_format((float) ($budgetPendingAmount ?? 0), 0) }}</p>
                </div>
                <div>
                    <p class="admin-dash-budget-label">Administrator OK</p>
                    <p class="admin-dash-budget-value">₱{{ number_format((float) ($budgetAdminApprovedAmount ?? 0), 0) }}</p>
                </div>
                <div>
                    <p class="admin-dash-budget-label">President</p>
                    <p class="admin-dash-budget-value">₱{{ number_format((float) ($budgetPresidentApprovedAmount ?? 0), 0) }}</p>
                </div>
            </div>

            @if((float) ($budgetPresidentRejectedAmount ?? 0) > 0)
                <p class="admin-dash-list-meta" style="margin-top: 12px;">
                    Rejected this year: ₱{{ number_format((float) $budgetPresidentRejectedAmount, 2) }}
                </p>
            @endif
        </section>

        </div>

        <aside class="admin-dash-side">
            <section class="admin-dash-panel">
                <div class="admin-dash-panel-head">
                    <div>
                        <h2 class="admin-dash-panel-title">Procurement</h2>
                        <p class="admin-dash-panel-sub">Pipeline volume</p>
                    </div>
                    <a class="admin-dash-text-link" href="{{ route('admin.operations.procurement') }}">Monitor</a>
                </div>

                <div class="admin-dash-pipeline">
                    @foreach([
                        'ris' => 'RIS',
                        'atp' => 'ATP',
                        'rfc' => 'RFC/CA',
                        'receiving' => 'RR',
                        'liquidation' => 'LIQ',
                    ] as $key => $label)
                        <div class="admin-dash-pipe-step">
                            <span class="admin-dash-pipe-count">{{ $stages[$key] ?? 0 }}</span>
                            <span class="admin-dash-pipe-label">{{ $label }}</span>
                        </div>
                        @if(!$loop->last)
                            <span class="admin-dash-pipe-sep" aria-hidden="true"></span>
                        @endif
                    @endforeach
                </div>

                <div class="admin-dash-budget">
                    <div>
                        <p class="admin-dash-budget-label">Open RIS</p>
                        <p class="admin-dash-budget-value">{{ $overview['open_ris'] ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="admin-dash-budget-label">Pending ₱</p>
                        <p class="admin-dash-budget-value">{{ number_format((float) ($budgetPendingAmount ?? 0), 0) }}</p>
                    </div>
                    <div>
                        <p class="admin-dash-budget-label">Year {{ $budgetProposalYear ?? now()->year }}</p>
                        <p class="admin-dash-budget-value">{{ number_format((float) ($budgetProposalTotal ?? 0), 0) }}</p>
                    </div>
                </div>

                @if($canPurchaser)
                    <a href="{{ url('/purchaser/ris') }}" class="admin-dash-cta">Create documents in Purchaser</a>
                @endif
            </section>

            <section class="admin-dash-panel">
                <div class="admin-dash-panel-head">
                    <div>
                        <h2 class="admin-dash-panel-title">Urgent reports</h2>
                        <p class="admin-dash-panel-sub">Open high-priority tickets</p>
                    </div>
                    <a class="admin-dash-text-link" href="{{ route('admin.operations.reports', ['filter' => 'urgent']) }}">All</a>
                </div>
                <ul class="admin-dash-list">
                    @forelse($urgentReportsList as $report)
                        <li>
                            <div class="admin-dash-list-main">
                                <p class="admin-dash-list-title">#{{ $report->report_id }} · {{ $report->equipment_name ?: 'Unlisted' }}</p>
                                <p class="admin-dash-list-meta">{{ $report->room_name ?: 'No room' }} · {{ $report->report_current_status }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="admin-dash-empty">No urgent reports.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Calendar of Events --}}
            @php
                $calendarEvents = $calendarEvents ?? collect();
                $calendarEventsByDate = $calendarEventsByDate ?? [];
            @endphp
            <section class="admin-dash-panel admin-dash-cal">
                <div class="admin-dash-panel-head">
                    <div>
                        <h2 class="admin-dash-panel-title">Calendar</h2>
                        <p class="admin-dash-panel-sub">RIS submitted, forwarded, approved, issued</p>
                    </div>
                    <a class="admin-dash-text-link" href="{{ url('/admin/procurement-review') }}">Review</a>
                </div>

                <div class="admin-dash-cal-month">
                    <button type="button" id="calPrevBtn" class="admin-dash-cal-nav" title="Previous month">
                        <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                    </button>
                    <span id="calMonthLabel" class="admin-dash-cal-label">{{ now()->format('F Y') }}</span>
                    <button type="button" id="calNextBtn" class="admin-dash-cal-nav" title="Next month">
                        <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                    </button>
                </div>

                <div id="adminCalendarGrid" class="admin-dash-cal-grid">
                    <div class="admin-dash-cal-dow">Sun</div>
                    <div class="admin-dash-cal-dow">Mon</div>
                    <div class="admin-dash-cal-dow">Tue</div>
                    <div class="admin-dash-cal-dow">Wed</div>
                    <div class="admin-dash-cal-dow">Thu</div>
                    <div class="admin-dash-cal-dow">Fri</div>
                    <div class="admin-dash-cal-dow">Sat</div>
                    @php
                        $now = now();
                        $firstDay = $now->copy()->startOfMonth();
                        $lastDay = $now->copy()->endOfMonth();
                        $startPadding = $firstDay->dayOfWeek;
                        $totalSlots = (int) ceil(($startPadding + $lastDay->day) / 7) * 7;
                        $todayDate = $now->format('Y-m-d');
                        $currentMonthKey = $now->format('Y-m');
                    @endphp
                    @for($i = 0; $i < $startPadding; $i++)
                        <div class="admin-dash-cal-day is-empty"></div>
                    @endfor
                    @for($day = 1; $day <= $lastDay->day; $day++)
                        @php
                            $dateKey = $currentMonthKey . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                            $dayEvents = $calendarEventsByDate[$dateKey] ?? [];
                            $hasEvents = count($dayEvents) > 0;
                            $isToday = $dateKey === $todayDate;
                        @endphp
                        <div class="admin-dash-cal-day {{ $isToday ? 'is-today' : '' }} {{ $hasEvents ? 'has-event' : '' }}"
                             data-date="{{ $dateKey }}"
                             title="{{ $hasEvents ? count($dayEvents).' event(s)' : '' }}">
                            <span>{{ $day }}</span>
                            @if($hasEvents)
                                <i class="admin-dash-cal-dot"></i>
                            @endif
                        </div>
                    @endfor
                    @for($i = $startPadding + $lastDay->day; $i < $totalSlots; $i++)
                        <div class="admin-dash-cal-day is-empty"></div>
                    @endfor
                </div>

                <div id="adminCalendarUpcoming" class="admin-dash-cal-upcoming">
                    <h3 class="admin-dash-cal-upcoming-title">Latest activity</h3>
                    @php
                        $adminUpcoming = collect($calendarEvents ?? []);
                        $adminUpcomingPreview = $adminUpcoming->take(3);
                        $adminUpcomingTotal = $adminUpcoming->count();
                    @endphp
                    @forelse($adminUpcomingPreview as $event)
                        <div class="admin-dash-cal-item">
                            <i class="admin-dash-cal-item-dot"></i>
                            <div>
                                <p class="admin-dash-list-title">{{ $event->event_name ?? 'RIS' }}</p>
                                <p class="admin-dash-list-meta">
                                    {{ !empty($event->event_date) ? \Carbon\Carbon::parse($event->event_date)->format('M d, Y') : 'No date set' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="admin-dash-empty" style="padding: 12px 0 !important;">No procurement dates this month</p>
                    @endforelse
                    @if($adminUpcomingTotal > 0)
                        <a class="admin-dash-cal-all" href="{{ url('/admin/procurement-review') }}">View all</a>
                    @endif
                    @if($adminUpcomingTotal > 3)
                        <p class="admin-dash-cal-hint">Showing 3 of {{ $adminUpcomingTotal }}</p>
                    @endif
                </div>
            </section>

            {{-- Recent activities --}}
            <section class="admin-dash-panel" id="activityListCard">
                <div class="admin-dash-panel-head">
                    <div>
                        <h2 class="admin-dash-panel-title">Recent activities</h2>
                        <p class="admin-dash-panel-sub">Pending queue &amp; latest decisions</p>
                    </div>
                    <button type="button" id="activityToggleBtn" class="admin-dash-text-link" style="background:none;border:none;cursor:pointer;padding:0;">
                        Show completed
                    </button>
                </div>

                <div id="pendingActivities">
                    <ul class="admin-dash-list compact">
                        @forelse(($pendingActivityLogs ?? collect()) as $log)
                            <li>
                                <div class="admin-dash-act-icon is-pending">
                                    <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                </div>
                                <div class="admin-dash-list-main">
                                    <p class="admin-dash-list-title">
                                        {{ $log->title }}
                                        @if(!empty($log->actor_name))
                                            <span class="admin-dash-act-actor">by {{ $log->actor_name }}</span>
                                        @endif
                                    </p>
                                    <p class="admin-dash-list-meta">{{ \Illuminate\Support\Str::limit($log->description ?? 'No remarks', 60) }}</p>
                                    <p class="admin-dash-list-meta">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '' }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="admin-dash-empty">No pending activities.</li>
                        @endforelse
                    </ul>
                </div>

                <div id="completedActivities" style="display:none;">
                    <p class="admin-dash-col-title" style="margin: 12px 0 4px;">Completed</p>
                    <ul class="admin-dash-list compact">
                        @forelse(($completedActivityLogs ?? collect()) as $log)
                            <li>
                                @php
                                    $isOk = in_array((string) ($log->status ?? ''), ['Approved', 'Co-signed', 'Directly Approved', 'Admin Approved'], true);
                                    $isBad = (string) ($log->status ?? '') === 'Rejected';
                                @endphp
                                <div class="admin-dash-act-icon {{ $isOk ? 'is-ok' : ($isBad ? 'is-bad' : 'is-pending') }}">
                                    <i data-lucide="{{ $isOk ? 'check-circle' : ($isBad ? 'x-circle' : 'clock') }}" class="h-3.5 w-3.5"></i>
                                </div>
                                <div class="admin-dash-list-main">
                                    <p class="admin-dash-list-title">
                                        {{ $log->title }}
                                        @if(!empty($log->actor_name))
                                            <span class="admin-dash-act-actor">by {{ $log->actor_name }}</span>
                                        @endif
                                    </p>
                                    <p class="admin-dash-list-meta">{{ \Illuminate\Support\Str::limit($log->description ?? 'No remarks', 60) }}</p>
                                    <p class="admin-dash-list-meta">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '' }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="admin-dash-empty">No completed activities.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            {{-- Supplier comparison --}}
            @php
                $supplierComparison = $supplierComparison ?? collect();
                $supplierComparisonMax = (float) ($supplierComparisonMax ?? 0);
                $typeCompare = $supplierTypeComparison ?? [
                    'physical_count' => 0,
                    'online_count' => 0,
                    'physical_amount' => 0,
                    'online_amount' => 0,
                ];
                $typeTotalAmount = (float) $typeCompare['physical_amount'] + (float) $typeCompare['online_amount'];
            @endphp
            <section class="admin-dash-panel">
                <div class="admin-dash-panel-head">
                    <div>
                        <h2 class="admin-dash-panel-title">Supplier comparison</h2>
                        <p class="admin-dash-panel-sub">ATP spend by store type &amp; supplier</p>
                    </div>
                </div>

                <div class="admin-dash-supplier-types">
                    <div>
                        <p class="admin-dash-budget-label">Physical</p>
                        <p class="admin-dash-budget-value">{{ (int) $typeCompare['physical_count'] }} ATP</p>
                        <p class="admin-dash-list-meta">₱{{ number_format((float) $typeCompare['physical_amount'], 0) }}</p>
                    </div>
                    <div>
                        <p class="admin-dash-budget-label">Online</p>
                        <p class="admin-dash-budget-value">{{ (int) $typeCompare['online_count'] }} ATP</p>
                        <p class="admin-dash-list-meta">₱{{ number_format((float) $typeCompare['online_amount'], 0) }}</p>
                    </div>
                </div>

                @if($typeTotalAmount > 0)
                    <div class="admin-dash-supplier-split" title="Physical vs Online spend">
                        <span class="is-physical" style="width: {{ round(((float) $typeCompare['physical_amount'] / $typeTotalAmount) * 100) }}%;"></span>
                        <span class="is-online" style="width: {{ round(((float) $typeCompare['online_amount'] / $typeTotalAmount) * 100) }}%;"></span>
                    </div>
                @endif

                <p class="admin-dash-col-title" style="margin: 14px 0 6px;">Top suppliers by ATP amount</p>

                @forelse($supplierComparison as $supplier)
                    @php
                        $barPct = $supplierComparisonMax > 0
                            ? max(8, round(((float) $supplier->total_amount / $supplierComparisonMax) * 100))
                            : 8;
                    @endphp
                    <div class="admin-dash-supplier-row">
                        <div class="admin-dash-supplier-meta">
                            <span class="admin-dash-list-title" title="{{ $supplier->supplier_name }}">{{ $supplier->supplier_name }}</span>
                            <span class="admin-dash-supplier-amount">₱{{ number_format((float) $supplier->total_amount, 0) }}</span>
                        </div>
                        <div class="admin-dash-supplier-track">
                            <span style="width: {{ $barPct }}%;"></span>
                        </div>
                        <p class="admin-dash-list-meta">
                            {{ (int) $supplier->atp_count }} {{ (int) $supplier->atp_count === 1 ? 'ATP' : 'ATPs' }}
                            @if(!empty($supplier->supplier_type))
                                · {{ $supplier->supplier_type }}
                            @endif
                        </p>
                    </div>
                @empty
                    <p class="admin-dash-empty" style="padding: 16px 0 !important;">No supplier ATP records yet.</p>
                @endforelse
            </section>
        </aside>
    </div>

    {{-- ========== Movements ========== --}}
    <section class="admin-dash-panel">
        <div class="admin-dash-panel-head">
            <div>
                <h2 class="admin-dash-panel-title">Movements</h2>
                <p class="admin-dash-panel-sub">Transfers, borrowing, and disposal</p>
            </div>
            <div class="admin-dash-links">
                <a href="{{ route('admin.operations.movements') }}">All movements</a>
                <a href="{{ route('admin.operations.overview') }}">Command Center</a>
            </div>
        </div>

        <div class="admin-dash-ops-strip">
            <a href="{{ route('admin.operations.equipment') }}"><em>{{ $overview['equipment_total'] ?? 0 }}</em> Equipment</a>
            <a href="{{ route('admin.operations.equipment', ['filter' => 'maintenance']) }}"><em>{{ $overview['needs_maintenance'] ?? 0 }}</em> Under maint.</a>
            <a href="{{ route('admin.operations.equipment', ['filter' => 'replacement']) }}"><em>{{ $overview['for_replacement'] ?? 0 }}</em> Replace</a>
            <a href="{{ route('admin.operations.equipment', ['filter' => 'lifecycle']) }}"><em>{{ $overview['lifecycle_alerts'] ?? 0 }}</em> Lifecycle</a>
            <a href="{{ route('admin.operations.movements', ['tab' => 'transfers', 'filter' => 'recent']) }}"><em>{{ $overview['transfers_30d'] ?? 0 }}</em> Transfers 30d</a>
            <a href="{{ route('admin.operations.movements', ['tab' => 'disposal']) }}"><em>{{ $overview['disposals_total'] ?? 0 }}</em> Disposals</a>
        </div>

        <div class="admin-dash-grid-3">
            <div>
                <h3 class="admin-dash-col-title">Transfers</h3>
                <ul class="admin-dash-list compact">
                    @forelse($movementTransfers as $row)
                        <li>
                            <div class="admin-dash-list-main">
                                <p class="admin-dash-list-title">{{ $row->equipment_name ?: ('#'.$row->equipment_id) }}</p>
                                <p class="admin-dash-list-meta">{{ $row->from_room_name ?: '—' }} → {{ $row->to_room_name ?: '—' }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="admin-dash-empty">No recent transfers.</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3 class="admin-dash-col-title">Borrowing</h3>
                <ul class="admin-dash-list compact">
                    @forelse($movementBorrows as $row)
                        <li>
                            <div class="admin-dash-list-main">
                                <p class="admin-dash-list-title">{{ $row->equipment_name ?: '—' }}</p>
                                <p class="admin-dash-list-meta">{{ $row->borrowing_borrower_name ?: '—' }} · {{ $row->borrowing_expected_return_date ?: '—' }}</p>
                            </div>
                            <span class="admin-dash-tag {{ $row->borrowing_status === 'Overdue' ? 'is-alert' : '' }}">{{ $row->borrowing_status }}</span>
                        </li>
                    @empty
                        <li class="admin-dash-empty">No active borrows.</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3 class="admin-dash-col-title">Disposal</h3>
                <ul class="admin-dash-list compact">
                    @forelse($movementDisposals as $row)
                        <li>
                            <div class="admin-dash-list-main">
                                <p class="admin-dash-list-title">{{ $row->equipment_name ?: '—' }}</p>
                                <p class="admin-dash-list-meta">{{ $row->disposal_reason ?: '—' }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="admin-dash-empty">No disposals yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    {{-- ========== Bottom: RIS table + system ========== --}}
    <div class="admin-dash-grid-bottom">
        <section class="admin-dash-panel">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Recent RIS</h2>
                    <p class="admin-dash-panel-sub">Latest requisition activity</p>
                </div>
                <a class="admin-dash-text-link" href="{{ route('admin.operations.procurement') }}">Pipeline</a>
            </div>
            <div class="admin-dash-table-wrap">
                <table class="admin-dash-table">
                    <thead>
                        <tr>
                            <th>RIS Number</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th class="is-right">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRisRecords as $ris)
                            <tr>
                                <td class="is-strong">{{ \App\Support\RisWorkflow::formNumber($ris) }}</td>
                                <td>
                                    <span class="admin-dash-ellipsis">{{ \App\Support\RisWorkflow::sourceLabel($ris) }}</span>
                                </td>
                                <td>@include('admin.partials.ris-status-badge', ['ris' => $ris])</td>
                                <td class="is-right is-strong">₱{{ number_format((float) ($ris->ris_calculated_total ?? 0), 2) }}</td>
                                <td class="is-right">
                                    <button type="button" class="admin-dash-ghost-btn" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')">View</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="admin-dash-empty">No RIS records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="admin-dash-side-stack">
            <section class="admin-dash-panel">
                <div class="admin-dash-panel-head">
                    <div>
                        <h2 class="admin-dash-panel-title">System</h2>
                        <p class="admin-dash-panel-sub">People & access</p>
                    </div>
                    <a class="admin-dash-text-link" href="{{ url('/admin/users') }}">Users</a>
                </div>
                <div class="admin-dash-system-grid">
                    <div><em>{{ $totalUsers }}</em><span>Users</span></div>
                    <div><em>{{ $activeUsers }}</em><span>Active 7d</span></div>
                    <div><em>{{ $maintenancePersonnel }}</em><span>Maint.</span></div>
                    <div><em>{{ $purchasers }}</em><span>Purchaser</span></div>
                    <div><em>{{ $accounting }}</em><span>Acct.</span></div>
                    <div><em>{{ $receivingOfficers }}</em><span>Receiving</span></div>
                </div>
            </section>

            <section class="admin-dash-panel">
                <div class="admin-dash-panel-head">
                    <div>
                        <h2 class="admin-dash-panel-title">Approvals</h2>
                        <p class="admin-dash-panel-sub">Latest decisions</p>
                    </div>
                    <a class="admin-dash-text-link" href="{{ route('admin.reports.approval-logs') }}">Logs</a>
                </div>
                <ul class="admin-dash-list compact">
                    @forelse($recentApprovals as $log)
                        <li>
                            <div class="admin-dash-list-main">
                                <p class="admin-dash-list-title">{{ $log->approval_log_reference_type }} #{{ $log->approval_log_reference_id }}</p>
                                <p class="admin-dash-list-meta">
                                    {{ $log->approval_log_approval_status }}
                                    · {{ $log->actor_name ?: 'System' }}
                                    @if(!empty($log->approval_log_approved_at))
                                        · {{ \Carbon\Carbon::parse($log->approval_log_approved_at)->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="admin-dash-empty">No approval activity.</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
</div>

@include('admin.partials.ris-preview-modal', ['zIndex' => '11000'])

<style>
/* Modern minimal Admin overview */
.admin-dash {
    --dash-ink: #0f172a;
    --dash-muted: #64748b;
    --dash-line: #e2e8f0;
    --dash-soft: #f8fafc;
    --dash-radius: 16px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.admin-dash-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
}

.admin-dash-kicker {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--dash-muted);
    margin-bottom: 4px;
}

.admin-dash-header-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    font-weight: 500;
    color: var(--dash-muted);
}

.admin-dash-pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 650;
    border: 1px solid var(--dash-line);
    background: #fff;
    color: var(--dash-ink);
}
.admin-dash-pill.is-alert {
    border-color: #fde68a;
    background: #fffbeb;
    color: #92400e;
}
.admin-dash-pill.is-ok {
    border-color: #e2e8f0;
    background: var(--dash-soft);
    color: #334155;
}

/* Attention strip — unified metric bar */
.admin-dash-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}
@media (min-width: 768px) {
    .admin-dash-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (min-width: 1200px) {
    .admin-dash-strip { grid-template-columns: repeat(6, minmax(0, 1fr)); }
}

.admin-dash-metric {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 18px 20px 22px;
    background: #fff;
    text-decoration: none;
    border: 0;
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 0;
    box-shadow: none;
    transition: background .15s ease;
}
/* 2-col: clear right border on even items */
.admin-dash-metric:nth-child(2n) { border-right: 0; }
/* last row: no bottom border */
.admin-dash-metric:nth-last-child(-n + 2) { border-bottom: 0; }

@media (min-width: 768px) {
    .admin-dash-metric { border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
    .admin-dash-metric:nth-child(2n) { border-right: 1px solid #e5e7eb; }
    .admin-dash-metric:nth-child(3n) { border-right: 0; }
    .admin-dash-metric:nth-last-child(-n + 2) { border-bottom: 1px solid #e5e7eb; }
    .admin-dash-metric:nth-last-child(-n + 3) { border-bottom: 0; }
}

@media (min-width: 1200px) {
    .admin-dash-metric {
        border-right: 1px solid #e5e7eb;
        border-bottom: 0;
    }
    .admin-dash-metric:nth-child(2n),
    .admin-dash-metric:nth-child(3n) { border-right: 1px solid #e5e7eb; }
    .admin-dash-metric:last-child { border-right: 0; }
}

.admin-dash-metric:hover {
    background: #f8fafc;
    box-shadow: none;
}
.admin-dash-metric.is-hot::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: #0025cc;
    z-index: 1;
}
.admin-dash-metric-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
}
.admin-dash-metric-value {
    font-family: "Outfit", sans-serif;
    font-size: 1.85rem;
    font-weight: 700;
    line-height: 1;
    color: #0f172a;
    letter-spacing: -0.03em;
}
.admin-dash-metric-hint {
    font-size: 12px;
    font-weight: 400;
    color: #94a3b8;
}

/* Panels */
.admin-dash-panel {
    border: 1px solid var(--dash-line);
    border-radius: var(--dash-radius);
    background: #fff;
    padding: 20px;
}
.admin-dash-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.admin-dash-panel-title {
    font-family: "Outfit", sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dash-ink);
    letter-spacing: -0.02em;
}
.admin-dash-panel-sub {
    margin-top: 2px;
    font-size: 12px;
    color: var(--dash-muted);
}
.admin-dash-text-link,
.admin-dash-links a {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
}
.admin-dash-text-link:hover,
.admin-dash-links a:hover { color: var(--dash-ink); }
.admin-dash-links {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

/* Main layout */
.admin-dash-grid-main {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 1100px) {
    .admin-dash-grid-main {
        grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.85fr);
        align-items: start;
    }
}
.admin-dash-side,
.admin-dash-side-stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.admin-dash-queue-block + .admin-dash-queue-block {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid var(--dash-line);
}
.admin-dash-queue-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 11px;
    font-weight: 650;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--dash-muted);
}
.admin-dash-queue-label a {
    letter-spacing: 0;
    text-transform: none;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
}

/* Lists */
.admin-dash-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.admin-dash-list > li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}
.admin-dash-list > li:last-child { border-bottom: 0; }
.admin-dash-list.compact > li { padding: 8px 0; }
.admin-dash-list-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--dash-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.admin-dash-list-meta {
    margin-top: 2px;
    font-size: 12px;
    color: var(--dash-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.admin-dash-list-main { min-width: 0; flex: 1; }
.admin-dash-list-aside {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
}
.admin-dash-list-aside button,
.admin-dash-ghost-btn {
    border: 1px solid var(--dash-line);
    background: #fff;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}
.admin-dash-list-aside button:hover,
.admin-dash-ghost-btn:hover { background: var(--dash-soft); }
.admin-dash-empty {
    padding: 20px 0 !important;
    text-align: center;
    font-size: 13px;
    color: #94a3b8;
    display: block !important;
    border: 0 !important;
}

/* Pipeline */
.admin-dash-pipeline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 4px;
    padding: 4px 0 14px;
}
.admin-dash-pipe-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    min-width: 0;
}
.admin-dash-pipe-count {
    font-family: "Outfit", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dash-ink);
}
.admin-dash-pipe-label {
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--dash-muted);
}
.admin-dash-pipe-sep {
    flex: 1;
    height: 1px;
    background: var(--dash-line);
    margin: 0 2px 14px;
    max-width: 28px;
}

.admin-dash-budget {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    padding-top: 4px;
}
.admin-dash-budget > div {
    background: var(--dash-soft);
    border-radius: 12px;
    padding: 12px;
}
.admin-dash-budget-label {
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--dash-muted);
}
.admin-dash-budget-value {
    margin-top: 4px;
    font-family: "Outfit", sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dash-ink);
}

.admin-dash-budget-hero {
    margin-bottom: 14px;
    padding: 14px 16px;
    border-radius: 12px;
    background: var(--dash-soft);
}
.admin-dash-budget-hero-value {
    margin-top: 4px;
    font-family: "Outfit", sans-serif;
    font-size: 1.55rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--dash-ink);
    line-height: 1.15;
}
.admin-dash-year-filter {
    flex-shrink: 0;
}
.admin-dash-year-select {
    appearance: none;
    border: 1px solid var(--dash-line);
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
    border-radius: 8px;
    padding: 6px 28px 6px 10px;
    font-size: 12px;
    font-weight: 650;
    color: var(--dash-ink);
    cursor: pointer;
}
.admin-dash-year-select:hover,
.admin-dash-year-select:focus {
    border-color: #cbd5e1;
    outline: none;
}
.admin-dash .sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Supplier comparison */
.admin-dash-supplier-types {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.admin-dash-supplier-types > div {
    background: var(--dash-soft);
    border-radius: 12px;
    padding: 12px;
}
.admin-dash-supplier-split {
    display: flex;
    height: 6px;
    border-radius: 999px;
    overflow: hidden;
    margin: 10px 0 0;
    background: var(--dash-line);
}
.admin-dash-supplier-split > span {
    display: block;
    height: 100%;
}
.admin-dash-supplier-split .is-physical { background: #0f172a; }
.admin-dash-supplier-split .is-online { background: #64748b; }
.admin-dash-supplier-row {
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}
.admin-dash-supplier-row:last-child { border-bottom: 0; }
.admin-dash-supplier-meta {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}
.admin-dash-supplier-meta .admin-dash-list-title {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}
.admin-dash-supplier-amount {
    flex-shrink: 0;
    font-size: 12px;
    font-weight: 700;
    color: var(--dash-ink);
}
.admin-dash-supplier-track {
    height: 6px;
    border-radius: 999px;
    background: var(--dash-line);
    overflow: hidden;
    margin-bottom: 3px;
}
.admin-dash-supplier-track > span {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: #334155;
}

.admin-dash-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 14px;
    border-radius: 12px;
    background: #475569;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 11px 14px;
    text-decoration: none;
}
.admin-dash-cta:hover { background: #334155; color: #fff; }

/* Movements */
.admin-dash-ops-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}
.admin-dash-ops-strip a {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    border: 1px solid var(--dash-line);
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    color: var(--dash-muted);
    text-decoration: none;
    background: #fff;
}
.admin-dash-ops-strip a:hover { border-color: #cbd5e1; color: var(--dash-ink); }
.admin-dash-ops-strip em {
    font-style: normal;
    font-family: "Outfit", sans-serif;
    font-weight: 700;
    color: var(--dash-ink);
}

.admin-dash-grid-3 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}
@media (min-width: 900px) {
    .admin-dash-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; }
}
.admin-dash-col-title {
    font-size: 11px;
    font-weight: 650;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--dash-muted);
    margin-bottom: 6px;
}
.admin-dash-tag {
    flex-shrink: 0;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: #475569;
    background: var(--dash-soft);
    border-radius: 999px;
    padding: 3px 8px;
}
.admin-dash-tag.is-alert {
    color: #92400e;
    background: #fffbeb;
}

/* Bottom grid */
.admin-dash-grid-bottom {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 1100px) {
    .admin-dash-grid-bottom {
        grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.8fr);
        align-items: start;
    }
}

.admin-dash-table-wrap { overflow-x: auto; margin: 0 -4px; }
.admin-dash-table {
    width: 100%;
    min-width: 560px;
    border-collapse: collapse;
}
.admin-dash-table th {
    text-align: left;
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--dash-muted);
    padding: 0 12px 10px;
    border-bottom: 1px solid var(--dash-line);
}
.admin-dash-table td {
    padding: 12px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.admin-dash-table tr:last-child td { border-bottom: 0; }
.admin-dash-table .is-right { text-align: right; }
.admin-dash-table .is-strong {
    font-weight: 650;
    color: var(--dash-ink);
}
.admin-dash-ellipsis {
    display: inline-block;
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-dash-system-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}
.admin-dash-system-grid > div {
    background: var(--dash-soft);
    border-radius: 12px;
    padding: 12px 10px;
    text-align: center;
}
.admin-dash-system-grid em {
    display: block;
    font-style: normal;
    font-family: "Outfit", sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dash-ink);
}
.admin-dash-system-grid span {
    display: block;
    margin-top: 2px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--dash-muted);
}

/* Calendar */
.admin-dash-cal-month {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.admin-dash-cal-nav {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1px solid var(--dash-line);
    background: var(--dash-soft);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--dash-muted);
    cursor: pointer;
}
.admin-dash-cal-nav:hover {
    background: #fff;
    color: var(--dash-ink);
    border-color: #cbd5e1;
}
.admin-dash-cal-nav:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.admin-dash-cal-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--dash-ink);
}
.admin-dash-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    margin-bottom: 10px;
}
.admin-dash-cal-dow {
    text-align: center;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
    padding: 2px 0;
}
.admin-dash-cal-day {
    min-height: 26px;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1px;
    font-size: 10px;
    font-weight: 500;
    color: #475569;
    position: relative;
}
.admin-dash-cal-day.is-empty { opacity: 0.25; }
.admin-dash-cal-day.is-today {
    background: #eef2ff;
    color: #334155;
    font-weight: 700;
}
.admin-dash-cal-day.has-event {
    font-weight: 650;
    color: var(--dash-ink);
    cursor: pointer;
}
.admin-dash-cal-day.has-event:hover { background: var(--dash-soft); }
.admin-dash-cal-day.is-selected {
    outline: 2px solid #475569;
    outline-offset: 1px;
    background: #eff6ff;
}
.admin-dash-cal-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #64748b;
    display: block;
}
.admin-dash-cal-upcoming {
    border-top: 1px solid var(--dash-line);
    padding-top: 10px;
}
.admin-dash-cal-upcoming-title {
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--dash-muted);
    margin-bottom: 6px;
}
.admin-dash-cal-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 6px 0;
}
.admin-dash-cal-item.is-highlighted {
    background: #eff6ff;
    border-radius: 8px;
    padding: 6px 8px;
    margin: 0 -4px;
}
.admin-dash-cal-item-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #64748b;
    margin-top: 5px;
    flex-shrink: 0;
    display: block;
}
.admin-dash-cal-item a {
    color: inherit;
    text-decoration: none;
}
.admin-dash-cal-item a:hover .admin-dash-list-title {
    color: #1d4ed8;
    text-decoration: underline;
}
.admin-dash-cal-all {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid var(--dash-line);
    background: var(--dash-soft);
    color: var(--dash-ink);
    font-size: 11px;
    font-weight: 650;
    text-decoration: none;
}
.admin-dash-cal-all:hover {
    background: #fff;
    border-color: #cbd5e1;
}
.admin-dash-cal-hint {
    margin-top: 6px;
    font-size: 10px;
    color: #94a3b8;
    text-align: center;
}

/* Recent activities */
.admin-dash-act-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.admin-dash-act-icon.is-pending { background: #fffbeb; color: #475569; }
.admin-dash-act-icon.is-ok { background: #ecfdf5; color: #475569; }
.admin-dash-act-icon.is-bad { background: #fef2f2; color: #dc2626; }
.admin-dash-act-actor {
    font-weight: 400;
    font-size: 10px;
    color: #94a3b8;
}
.admin-dash-list > li:has(.admin-dash-act-icon) {
    align-items: flex-start;
}
</style>

@push('scripts')
<script>
    window.openRisPreviewModal = function (risId) {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');
        if (!modal || !iframe) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.classList.remove('hidden');
        iframe.src = '/admin/procurement-review/ris/' + risId + '/print?ts=' + Date.now();
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    };

    window.closeRisPreviewModal = function () {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    };

    (function () {
        var prevBtn = document.getElementById('calPrevBtn');
        var nextBtn = document.getElementById('calNextBtn');
        var monthLabel = document.getElementById('calMonthLabel');
        var grid = document.getElementById('adminCalendarGrid');
        var upcoming = document.getElementById('adminCalendarUpcoming');
        if (!grid || !monthLabel) return;

        var events = {!! json_encode(
            collect($calendarEvents ?? [])->map(function ($event) {
                return [
                    'date' => $event->event_date ?? null,
                    'name' => $event->event_name ?? 'RIS',
                    'id' => $event->ris_id ?? null,
                    'url' => $event->url ?? '/admin/procurement-review',
                ];
            })->filter(fn ($e) => !empty($e['date']))->values()
        ) !!};
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var view = new Date();
        view.setDate(1);
        var now = new Date();
        var minMonthIndex = now.getFullYear() * 12 + now.getMonth() - 1;
        var selectedDate = null;

        function pad(n) { return n < 10 ? '0' + n : String(n); }
        function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function monthIndex(d) { return d.getFullYear() * 12 + d.getMonth(); }
        function escapeHtml(str) {
            return String(str || '').replace(/[&<>"']/g, function (c) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
            });
        }
        function canGoPrev() { return monthIndex(view) > minMonthIndex; }
        function updateNavButtons() {
            if (!prevBtn) return;
            var allowed = canGoPrev();
            prevBtn.disabled = !allowed;
            prevBtn.title = allowed ? 'Previous month' : 'Cannot go back more than one month';
        }
        function eventsOn(dateKey) {
            return events.filter(function (e) { return e.date === dateKey; });
        }

        function renderUpcoming(dateKey) {
            if (!upcoming) return;
            var year = view.getFullYear();
            var month = view.getMonth();
            var listEvents;
            var title;
            var totalCount = 0;
            var viewAllHref = '/admin/procurement-review';

            if (dateKey) {
                listEvents = eventsOn(dateKey).slice().reverse();
                totalCount = listEvents.length;
                listEvents = listEvents.slice(0, 3);
                var parts = dateKey.split('-');
                title = monthNames[parseInt(parts[1], 10) - 1] + ' ' + parseInt(parts[2], 10);
            } else {
                var monthPrefix = year + '-' + pad(month + 1);
                listEvents = events.filter(function (e) { return e.date.indexOf(monthPrefix) === 0; })
                    .sort(function (a, b) { return b.date.localeCompare(a.date); });
                totalCount = listEvents.length;
                listEvents = listEvents.slice(0, 3);
                title = 'Latest activity';
            }

            var list = '<h3 class="admin-dash-cal-upcoming-title">' + escapeHtml(title) + '</h3>';
            if (!listEvents.length) {
                list += '<p class="admin-dash-empty" style="padding:12px 0 !important;">'
                    + (dateKey ? 'No events on this day' : 'No procurement dates this month')
                    + '</p>';
            } else {
                listEvents.forEach(function (e) {
                    var p = e.date.split('-');
                    var label = monthNames[parseInt(p[1], 10) - 1] + ' ' + parseInt(p[2], 10) + ', ' + p[0];
                    var href = e.url || '/admin/procurement-review';
                    var highlight = dateKey ? ' is-highlighted' : '';
                    list += '<div class="admin-dash-cal-item' + highlight + '">';
                    list += '<i class="admin-dash-cal-item-dot"></i><div>';
                    list += '<a href="' + escapeHtml(href) + '"><p class="admin-dash-list-title">' + escapeHtml(e.name) + '</p></a>';
                    list += '<p class="admin-dash-list-meta">' + escapeHtml(label) + '</p></div></div>';
                });
            }
            if (totalCount > 0) {
                list += '<a class="admin-dash-cal-all" href="' + escapeHtml(viewAllHref) + '">View all</a>';
            }
            if (totalCount > 3) {
                list += '<p class="admin-dash-cal-hint">Showing 3 of ' + totalCount + (dateKey ? ' on this day' : '') + '</p>';
            }
            upcoming.innerHTML = list;
        }

        function render() {
            var year = view.getFullYear();
            var month = view.getMonth();
            monthLabel.textContent = monthNames[month] + ' ' + year;

            var first = new Date(year, month, 1);
            var lastDate = new Date(year, month + 1, 0).getDate();
            var startPad = first.getDay();
            var todayKey = ymd(new Date());
            var html = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function (d) {
                html += '<div class="admin-dash-cal-dow">' + d + '</div>';
            });
            var totalSlots = Math.ceil((startPad + lastDate) / 7) * 7;
            for (var i = 0; i < totalSlots; i++) {
                var dayNum = i - startPad + 1;
                if (dayNum < 1 || dayNum > lastDate) {
                    html += '<div class="admin-dash-cal-day is-empty"></div>';
                    continue;
                }
                var dateKey = year + '-' + pad(month + 1) + '-' + pad(dayNum);
                var dayEvents = eventsOn(dateKey);
                var cls = 'admin-dash-cal-day';
                if (dateKey === todayKey) cls += ' is-today';
                if (dayEvents.length) cls += ' has-event';
                if (selectedDate === dateKey) cls += ' is-selected';
                html += '<div class="' + cls + '" data-date="' + dateKey + '" title="'
                    + (dayEvents.length ? dayEvents.length + ' event(s)' : '') + '">';
                html += '<span>' + dayNum + '</span>';
                if (dayEvents.length) html += '<i class="admin-dash-cal-dot"></i>';
                html += '</div>';
            }
            grid.innerHTML = html;
            renderUpcoming(selectedDate);
            updateNavButtons();
        }

        grid.addEventListener('click', function (e) {
            var dayEl = e.target.closest('.admin-dash-cal-day[data-date]');
            if (!dayEl || dayEl.classList.contains('is-empty')) return;
            var dateKey = dayEl.getAttribute('data-date');
            if (!dateKey) return;
            if (!eventsOn(dateKey).length) {
                selectedDate = null;
                render();
                return;
            }
            selectedDate = dateKey;
            render();
        });

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', function () {
                if (!canGoPrev()) return;
                view.setMonth(view.getMonth() - 1);
                selectedDate = null;
                render();
            });
            nextBtn.addEventListener('click', function () {
                view.setMonth(view.getMonth() + 1);
                selectedDate = null;
                render();
            });
        }
        render();
    })();

    (function () {
        var toggleBtn = document.getElementById('activityToggleBtn');
        var completedSection = document.getElementById('completedActivities');
        if (!toggleBtn || !completedSection) return;
        var expanded = false;
        toggleBtn.addEventListener('click', function () {
            expanded = !expanded;
            completedSection.style.display = expanded ? 'block' : 'none';
            toggleBtn.textContent = expanded ? 'Hide completed' : 'Show completed';
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    })();
</script>
@endpush
@endsection
