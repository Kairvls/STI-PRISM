@extends('layouts.admin-layout')

@section('content')

{{-- Chart.js for dashboard charts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="admin-dashboard">

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Dashboard</h1>
            <p class="dashboard-subtitle">Overview of system activity, user management, and procurement operations.</p>
        </div>

        <div class="dashboard-header-right">
            <span class="dashboard-date-badge">
                <i data-lucide="calendar" class="h-4 w-4"></i>
                {{ now()->format('l, F j, Y') }}
            </span>
        </div>
    </div>


    {{-- ===================================================== --}}
    {{-- MAIN CONTENT: STATS + HERO | SIDEBAR (aligned top) --}}
    {{-- ===================================================== --}}

    <div class="dashboard-main-grid">

        {{-- LEFT: STATS + HERO SECTION --}}

        <div class="dashboard-hero">

            {{-- ===================================================== --}}
            {{-- STATISTIC CARDS --}}
            {{-- ===================================================== --}}

            <div class="stat-grid">

                {{-- PENDING RIS — always first --}}
                <div class="stat-card stat-card-warning" title="RIS forms currently waiting for review">
                    <div class="stat-card-top">
                        <div class="stat-icon stat-icon-amber">
                            <i data-lucide="clock"></i>
                        </div>
                        @if($pendingRis > 0)
                        <span class="stat-change stat-change-warn">
                            <i data-lucide="alert-circle" class="h-3 w-3"></i>
                            Needs attention
                        </span>
                        @else
                        <span class="stat-change stat-change-up">
                            <i data-lucide="check" class="h-3 w-3"></i>
                            All clear
                        </span>
                        @endif
                    </div>
                    <p class="stat-label">Pending RIS</p>
                    <p class="stat-value">{{ $pendingRis }}</p>
                    <p class="stat-amount">₱{{ number_format($pendingRisAmount, 2) }} pending value</p>
                </div>

                <div class="stat-card" title="RIS waiting for Admin Issued by signature">
                    <div class="stat-card-top">
                        <div class="stat-icon stat-icon-violet">
                            <i data-lucide="pen-tool"></i>
                        </div>
                        @if($forCosigningCount > 0)
                        <span class="stat-change stat-change-warn">
                            <i data-lucide="alert-circle" class="h-3 w-3"></i>
                            Needs attention
                        </span>
                        @endif
                    </div>
                    <p class="stat-label">Awaiting Action</p>
                    <p class="stat-value">{{ $forCosigningCount }}</p>
                    <p class="stat-amount">President-approved, awaiting your signature</p>
                </div>

                <div class="stat-card" title="RIS forms approved by Admin">
                    <div class="stat-card-top">
                        <div class="stat-icon stat-icon-sky">
                            <i data-lucide="check-circle"></i>
                        </div>
                    </div>
                    <p class="stat-label">Admin Approved</p>
                    <p class="stat-value">{{ $directApprovedRis }}</p>
                    <p class="stat-amount">Returned to Purchaser</p>
                </div>

                <div class="stat-card" title="Total Requisition Issue Slips submitted">
                    <div class="stat-card-top">
                        <div class="stat-icon stat-icon-indigo">
                            <i data-lucide="file-text"></i>
                        </div>
                    </div>
                    <p class="stat-label">Total RIS</p>
                    <p class="stat-value">{{ $totalRis }}</p>
                    <p class="stat-amount">₱{{ number_format($totalRisAmount, 2) }} total value</p>
                </div>

            </div>


            {{-- Pending RIS Alert --}}

            @if($pendingRis > 0)

            <div class="hero-alert-card">
                <div class="hero-alert-left">
                    <div class="hero-alert-icon">
                        <i data-lucide="bell-ringing"></i>
                    </div>
                    <div>
                        <h3 class="hero-alert-title">{{ $pendingRis }} RIS {{ $pendingRis === 1 ? 'is' : 'are' }} pending your review</h3>
                        <p class="hero-alert-desc">These Requisition Issue Slips need your decision — forward to President, admin approve, or return for amendment.</p>
                    </div>
                </div>
                <a href="{{ route('admin.procurement-review.ris', ['filter' => 'pending']) }}" class="hero-alert-btn">
                    Review Now
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            @else

            <div class="hero-empty-card">
                <div class="hero-empty-left">
                    <div class="hero-empty-icon">
                        <i data-lucide="check-circle-2"></i>
                    </div>
                    <div>
                        <h3 class="hero-empty-title">All clear — no pending RIS</h3>
                        <p class="hero-empty-desc">All Requisition Issue Slips have been reviewed. New submissions will appear here.</p>
                    </div>
                </div>
            </div>

            @endif


            {{-- For Co-signing Alert --}}

            @if($forCosigningCount > 0)

            <div class="hero-alert-card hero-alert-card-violet">
                <div class="hero-alert-left">
                    <div class="hero-alert-icon hero-alert-icon-violet">
                        <i data-lucide="signature"></i>
                    </div>
                    <div>
                        <h3 class="hero-alert-title">{{ $forCosigningCount }} RIS {{ $forCosigningCount === 1 ? 'needs' : 'need' }} your action</h3>
                        <p class="hero-alert-desc">President-approved RIS waiting for your Issued by signature so they can return to the Purchaser.</p>
                    </div>
                </div>
                <a href="{{ route('admin.digital-signatures.sign-ris', ['filter' => 'for_cosign']) }}" class="hero-alert-btn hero-alert-btn-violet">
                    Review Now
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            @endif


            @php
                $trendApprovedSeries = $risTrendApproved ?? [];
                $trendForwardedSeries = $risTrendForwarded ?? [];
                $trendAmendSeries = $risTrendAmend ?? [];
                $trendRejectedSeries = $risTrendRejected ?? [];
                $pctChange = function (array $series): float {
                    $n = count($series);
                    if ($n < 2) {
                        return 0.0;
                    }
                    $prev = (float) $series[$n - 2];
                    $curr = (float) $series[$n - 1];
                    if (abs($prev) < 0.00001) {
                        return $curr > 0 ? 100.0 : 0.0;
                    }
                    return round((($curr - $prev) / $prev) * 100, 1);
                };
                $approvedPct = $pctChange($trendApprovedSeries);
                $forwardedPct = $pctChange($trendForwardedSeries);
                $amendPct = $pctChange($trendAmendSeries);
                $rejectedPct = $pctChange($trendRejectedSeries);
                $latestApproved = (int) (end($trendApprovedSeries) ?: 0);
                $latestForwarded = (int) (end($trendForwardedSeries) ?: 0);
                $latestAmend = (int) (end($trendAmendSeries) ?: 0);
                $latestRejected = (int) (end($trendRejectedSeries) ?: 0);
            @endphp

            {{-- RIS Overview metrics (reference-style cards) --}}
            <div class="ris-metrics-grid">
                <div class="ris-metric-card ris-metric-card-wide">
                    <div class="ris-metric-top">
                        <span class="ris-metric-label">Proposed budget · {{ $budgetProposalYear ?? now()->year }}</span>
                        <div class="ris-metric-value-row">
                            <span class="ris-metric-value">₱{{ number_format((float) ($budgetProposalTotal ?? 0), 0) }}</span>
                        </div>
                        <span class="ris-metric-hint">vs selected period</span>
                    </div>
                    <div class="ris-metric-chart">
                        <canvas id="risProposedChart"></canvas>
                    </div>
                </div>

                <div class="ris-metric-card">
                    <div class="ris-metric-top">
                        <span class="ris-metric-label">Admin approved</span>
                        <div class="ris-metric-value-row">
                            <span class="ris-metric-value">{{ $latestApproved }}</span>
                            <span class="ris-metric-pill {{ $approvedPct >= 0 ? 'is-up' : 'is-down' }}">
                                {{ $approvedPct >= 0 ? '+' : '' }}{{ $approvedPct }}%
                            </span>
                        </div>
                        <span class="ris-metric-hint">vs last month</span>
                    </div>
                    <div class="ris-metric-chart ris-metric-chart-sm">
                        <canvas id="risApprovedSpark"></canvas>
                    </div>
                </div>

                <div class="ris-metric-card">
                    <div class="ris-metric-top">
                        <span class="ris-metric-label">President approved</span>
                        <div class="ris-metric-value-row">
                            <span class="ris-metric-value">{{ $latestForwarded }}</span>
                            <span class="ris-metric-pill {{ $forwardedPct >= 0 ? 'is-up' : 'is-down' }}">
                                {{ $forwardedPct >= 0 ? '+' : '' }}{{ $forwardedPct }}%
                            </span>
                        </div>
                        <span class="ris-metric-hint">vs last month</span>
                    </div>
                    <div class="ris-metric-chart ris-metric-chart-sm">
                        <canvas id="risPresidentSpark"></canvas>
                    </div>
                </div>

                <div class="ris-metric-card">
                    <div class="ris-metric-top">
                        <span class="ris-metric-label">Pending amount</span>
                        <div class="ris-metric-value-row">
                            <span class="ris-metric-value">₱{{ number_format((float) ($budgetPendingAmount ?? 0), 0) }}</span>
                        </div>
                        <span class="ris-metric-hint">awaiting action</span>
                    </div>
                    <div class="ris-metric-chart ris-metric-chart-sm">
                        <canvas id="risPendingBars"></canvas>
                    </div>
                </div>

                <div class="ris-metric-card">
                    <div class="ris-metric-top">
                        <span class="ris-metric-label">Amend / reject</span>
                        <div class="ris-metric-value-row">
                            <span class="ris-metric-value">{{ $latestAmend + $latestRejected }}</span>
                            <span class="ris-metric-pill {{ ($amendPct + $rejectedPct) <= 0 ? 'is-up' : 'is-down' }}">
                                {{ $amendPct >= 0 ? '+' : '' }}{{ $amendPct }}% amend
                            </span>
                        </div>
                        <span class="ris-metric-hint">vs last month</span>
                    </div>
                    <div class="ris-metric-chart ris-metric-chart-sm">
                        <canvas id="risAmendSpark"></canvas>
                    </div>
                </div>
            </div>


            {{-- RECENT RIS RECORDS TABLE --}}

            <div class="dashboard-table-card">
                <div class="dashboard-table-header">
                    <div>
                        <h3 class="dashboard-table-title">Recent RIS Records</h3>
                        <p class="dashboard-table-subtitle">Latest Requisition Issue Slip submissions</p>
                    </div>
                    <a href="{{ route('admin.procurement-review.ris') }}" class="dashboard-table-link">
                        View All
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
                <div class="dashboard-table-body">
                    <table class="dashboard-table">
<thead>
                            <tr>
                                <th>Reference</th>
                                <th>Equipment</th>
                                <th>Status</th>
                                <th class="text-right">Amount</th>
                                <th class="dashboard-table-date">Date</th>
                                <th class="dashboard-table-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRisRecords as $ris)
                            @php
                                $risDate = $ris->ris_submitted_at ?? $ris->ris_requested_by_date ?? $ris->ris_created_at ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <span class="table-ref-no">{{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}</span>
                                </td>
                                <td>
                                    <span class="table-equip">{{ \App\Support\RisWorkflow::sourceLabel($ris) }}</span>
                                    @include('admin.partials.ris-attachments', ['ris' => $ris])
                                </td>
                                <td>
                                    @include('admin.partials.ris-status-badge', ['ris' => $ris])
                                </td>
                                <td class="text-right font-semibold text-gray-900">
                                    ₱{{ number_format((float)($ris->ris_calculated_total ?? 0), 2) }}
                                </td>
                                <td class="dashboard-table-date">
                                    {{ $risDate ? \Carbon\Carbon::parse($risDate)->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="dashboard-table-actions">
                                    <button type="button" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')" class="table-action-icon-btn" title="View RIS form" aria-label="View RIS form">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i data-lucide="inbox" class="h-8 w-8 mb-2 text-gray-300"></i>
                                        <span class="text-sm">No RIS records found</span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>


        {{-- RIGHT: SIDEBAR (reordered) --}}

        <div class="dashboard-sidebar">

{{-- 1. RIS Status Overview --}}

            <div class="sidebar-chart-card ris-overview-card">
                <div class="sidebar-chart-header">
                    <h3 class="sidebar-chart-title">RIS Status Overview</h3>
                    <p class="ris-overview-sub">Breakdown for current pipeline</p>
                </div>
                <div class="ris-overview-chart-wrap">
                    <canvas id="risStatusChart" height="140"></canvas>
                </div>
                <div class="ris-overview-breakdown">
                    @php
                        $overviewRows = [
                            ['Pending', (int) ($pendingRis ?? 0), '#60a5fa'],
                            ['Admin Approved', (int) ($directApprovedRis ?? 0), '#93c5fd'],
                            ['President Approved', (int) ($approvedRis ?? 0), '#64748b'],
                            ['Amend', (int) ($amendRis ?? 0), '#fbbf24'],
                            ['Rejected', (int) ($presidentRejectedRis ?? 0), '#475569'],
                        ];
                        $overviewTotal = max(1, collect($overviewRows)->sum(fn ($r) => $r[1]));
                    @endphp
                    @foreach ($overviewRows as $row)
                        <div class="ris-overview-row">
                            <span class="ris-overview-dot" style="background:{{ $row[2] }}"></span>
                            <span class="ris-overview-name">{{ $row[0] }}</span>
                            <span class="ris-overview-count">{{ $row[1] }}</span>
                            <span class="ris-overview-amt {{ $row[1] > 0 ? 'is-pos' : '' }}">{{ number_format(($row[1] / $overviewTotal) * 100, 0) }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>


            {{-- 2. Calendar of Events --}}

            <div class="sidebar-calendar-card">
                <div class="sidebar-calendar-header">
                    <h3 class="sidebar-calendar-title">
                        <i data-lucide="calendar" class="h-4 w-4" style="margin-right: 6px;"></i>
                        Calendar of Events
                    </h3>
                    <p class="mt-1 text-[11px] font-normal text-slate-500">RIS submitted, forwarded, approved, and issued dates</p>
                </div>
                <div class="sidebar-calendar-body">

                    {{-- Calendar Header --}}
                    <div class="calendar-month-header">
                        <button type="button" id="calPrevBtn" class="cal-nav-btn" title="Previous month">
                            <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                        </button>
                        <span id="calMonthLabel" class="cal-month-label">{{ now()->format('F Y') }}</span>
                        <button type="button" id="calNextBtn" class="cal-nav-btn" title="Next month">
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>

                    {{-- Calendar Grid --}}
                    <div id="adminCalendarGrid" class="calendar-grid">
                        <div class="cal-day-header">Sun</div>
                        <div class="cal-day-header">Mon</div>
                        <div class="cal-day-header">Tue</div>
                        <div class="cal-day-header">Wed</div>
                        <div class="cal-day-header">Thu</div>
                        <div class="cal-day-header">Fri</div>
                        <div class="cal-day-header">Sat</div>

                        @php
                            $now = now();
                            $firstDay = $now->copy()->startOfMonth();
                            $lastDay = $now->copy()->endOfMonth();
                            $startPadding = $firstDay->dayOfWeek;
                            $totalCells = $startPadding + $lastDay->day;
                            $rows = ceil($totalCells / 7);
                            $totalSlots = $rows * 7;
                            $todayDate = $now->format('Y-m-d');
                            $currentMonthKey = $now->format('Y-m');
                        @endphp

                        {{-- Empty cells before first day --}}
                        @for($i = 0; $i < $startPadding; $i++)
                            <div class="cal-day cal-day-empty"></div>
                        @endfor

                        {{-- Actual days --}}
                        @for($day = 1; $day <= $lastDay->day; $day++)
                            @php
                                $dateKey = $currentMonthKey . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $hasEvents = isset($calendarEventsByDate[$dateKey]) && count($calendarEventsByDate[$dateKey]) > 0;
                                $isToday = $dateKey === $todayDate;
                                $dayEvents = $calendarEventsByDate[$dateKey] ?? [];
                                $eventCount = count($dayEvents);
                            @endphp
                            <div class="cal-day {{ $isToday ? 'cal-day-today' : '' }} {{ $hasEvents ? 'cal-day-has-event' : '' }}"
                                 data-date="{{ $dateKey }}"
                                 title="{{ $hasEvents ? $eventCount . ' event(s)' : '' }}">
                                <span class="cal-day-num">{{ $day }}</span>
                                @if($hasEvents)
                                    <span class="cal-day-dot"></span>
                                @endif
                            </div>
                        @endfor

                        {{-- Empty cells after last day --}}
                        @for($i = $startPadding + $lastDay->day; $i < $totalSlots; $i++)
                            <div class="cal-day cal-day-empty"></div>
                        @endfor
                    </div>

                    {{-- Upcoming Events List --}}
                    <div id="adminCalendarUpcoming" class="cal-upcoming">
                        <h4 class="cal-upcoming-title">Latest activity</h4>
                        @php
                            $adminUpcoming = collect($calendarEvents ?? []);
                            $adminUpcomingPreview = $adminUpcoming->take(3);
                            $adminUpcomingTotal = $adminUpcoming->count();
                        @endphp
                        @forelse($adminUpcomingPreview as $event)
                            <div class="cal-upcoming-item">
                                <div class="cal-upcoming-dot"></div>
                                <div class="cal-upcoming-content">
                                    <span class="cal-upcoming-name">{{ $event->event_name ?? 'RIS' }}</span>
                                    <span class="cal-upcoming-date">
                                        {{ !empty($event->event_date) ? \Carbon\Carbon::parse($event->event_date)->format('M d, Y') : 'No date set' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="cal-upcoming-empty">No procurement dates this month</div>
                        @endforelse
                        @if($adminUpcomingTotal > 0)
                            <a class="cal-view-all" href="{{ url('/admin/procurement-review') }}">View all</a>
                        @endif
                        @if($adminUpcomingTotal > 3)
                            <p class="cal-view-all-hint">Showing 3 of {{ $adminUpcomingTotal }}</p>
                        @endif
                    </div>
                </div>
            </div>


            {{-- 3. Supplier Comparison --}}

            <div class="sidebar-supplier-card">
                <div class="sidebar-supplier-header">
                    <h3 class="sidebar-supplier-title">
                        <i data-lucide="store" class="h-4 w-4" style="margin-right: 6px;"></i>
                        Supplier Comparison
                    </h3>
                </div>
                <div class="sidebar-supplier-body">
                    @php
                        $supplierComparison = $supplierComparison ?? collect();
                        $supplierComparisonMax = (float) ($supplierComparisonMax ?? 0);
                        $typeCompare = $supplierTypeComparison ?? ['physical_count' => 0, 'online_count' => 0, 'physical_amount' => 0, 'online_amount' => 0];
                        $typeTotalAmount = (float) $typeCompare['physical_amount'] + (float) $typeCompare['online_amount'];
                    @endphp

                    <div class="supplier-type-row">
                        <div class="supplier-type-item">
                            <span class="supplier-type-label">Physical</span>
                            <span class="supplier-type-value">{{ (int) $typeCompare['physical_count'] }} ATP</span>
                            <span class="supplier-type-amount">₱{{ number_format((float) $typeCompare['physical_amount'], 2) }}</span>
                        </div>
                        <div class="supplier-type-item">
                            <span class="supplier-type-label">Online</span>
                            <span class="supplier-type-value">{{ (int) $typeCompare['online_count'] }} ATP</span>
                            <span class="supplier-type-amount">₱{{ number_format((float) $typeCompare['online_amount'], 2) }}</span>
                        </div>
                    </div>

                    @if($typeTotalAmount > 0)
                        <div class="supplier-type-bar" title="Physical vs Online spend">
                            <span class="supplier-type-bar-physical" style="width: {{ round(((float) $typeCompare['physical_amount'] / $typeTotalAmount) * 100) }}%;"></span>
                            <span class="supplier-type-bar-online" style="width: {{ round(((float) $typeCompare['online_amount'] / $typeTotalAmount) * 100) }}%;"></span>
                        </div>
                    @endif

                    <p class="supplier-compare-caption">Top suppliers by ATP amount</p>

                    @forelse($supplierComparison as $supplier)
                        @php
                            $barPct = $supplierComparisonMax > 0
                                ? max(8, round(((float) $supplier->total_amount / $supplierComparisonMax) * 100))
                                : 8;
                        @endphp
                        <div class="supplier-compare-row">
                            <div class="supplier-compare-meta">
                                <span class="supplier-compare-name" title="{{ $supplier->supplier_name }}">{{ $supplier->supplier_name }}</span>
                                <span class="supplier-compare-amount">₱{{ number_format((float) $supplier->total_amount, 2) }}</span>
                            </div>
                            <div class="supplier-compare-track">
                                <span class="supplier-compare-fill" style="width: {{ $barPct }}%;"></span>
                            </div>
                            <span class="supplier-compare-count">{{ (int) $supplier->atp_count }} {{ (int) $supplier->atp_count === 1 ? 'ATP' : 'ATPs' }}</span>
                        </div>
                    @empty
                        <p class="supplier-compare-empty">No supplier ATP records yet.</p>
                    @endforelse
                </div>
            </div>


            {{-- 4. Activity List (Split into Pending + Completed with Toggle) --}}

            <div class="sidebar-activity-card" id="activityListCard">
                <div class="sidebar-activity-header">
                    <h3 class="sidebar-activity-title">
                        <i data-lucide="list-checks" class="h-4 w-4" style="margin-right: 6px;"></i>
                        Activity List
                    </h3>
                    <button type="button" id="activityToggleBtn" class="sidebar-activity-link" style="background:none;border:none;cursor:pointer;">
                        Show completed
                    </button>
                </div>
                <div class="sidebar-activity-list">

                    {{-- PENDING ACTIVITIES (always shown, up to 3) --}}
                    <div id="pendingActivities">
                        @forelse($pendingActivityLogs as $log)
                        <div class="sidebar-activity-item">
                            <div class="sidebar-activity-status-icon">
                                <div class="act-icon act-icon-pending">
                                    <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                </div>
                            </div>
                            <div class="sidebar-activity-content">
                                <p class="sidebar-activity-title-text">
                                    {{ $log->title }}
                                    @if($log->actor_name)
                                    <span class="sidebar-activity-actor">by {{ $log->actor_name }}</span>
                                    @endif
                                </p>
                                <p class="sidebar-activity-desc">{{ Str::limit($log->description ?? 'No remarks', 60) }}</p>
                                <p class="sidebar-activity-time">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '' }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="flex flex-col items-center py-4 text-gray-400">
                            <i data-lucide="inbox" class="h-5 w-5 mb-1 text-gray-300"></i>
                            <span class="text-xs">No pending activities</span>
                        </div>
                        @endforelse
                    </div>

                    {{-- COMPLETED ACTIVITIES (hidden by default, up to 2) --}}
                    <div id="completedActivities" style="display: none;">
                        <div class="sidebar-activity-separator">
                            <span class="sidebar-activity-separator-text">Completed</span>
                        </div>
                        @forelse($completedActivityLogs as $log)
                        <div class="sidebar-activity-item">
                            <div class="sidebar-activity-status-icon">
                                @if($log->status === 'Approved' || $log->status === 'Co-signed')
                                    <div class="act-icon act-icon-success">
                                        <i data-lucide="check-circle" class="h-3.5 w-3.5"></i>
                                    </div>
                                @elseif($log->status === 'Rejected')
                                    <div class="act-icon act-icon-danger">
                                        <i data-lucide="x-circle" class="h-3.5 w-3.5"></i>
                                    </div>
                                @else
                                    <div class="act-icon act-icon-pending">
                                        <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="sidebar-activity-content">
                                <p class="sidebar-activity-title-text">
                                    {{ $log->title }}
                                    @if($log->actor_name)
                                    <span class="sidebar-activity-actor">by {{ $log->actor_name }}</span>
                                    @endif
                                </p>
                                <p class="sidebar-activity-desc">{{ Str::limit($log->description ?? 'No remarks', 60) }}</p>
                                <p class="sidebar-activity-time">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->diffForHumans() : '' }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="flex flex-col items-center py-4 text-gray-400">
                            <i data-lucide="inbox" class="h-5 w-5 mb-1 text-gray-300"></i>
                            <span class="text-xs">No completed activities</span>
                        </div>
                        @endforelse
                    </div>

                </div>
            </div>


            {{-- 4. Quick Stats Summary (Pending RIS first) --}}

            <div class="sidebar-stats-card">
                <h3 class="sidebar-stats-title">
                    <i data-lucide="bar-chart-3" class="h-4 w-4" style="margin-right: 6px;"></i>
                    Quick Summary
                </h3>
                <div class="sidebar-stats-list">
                    {{-- PENDING RIS — FIRST PRIORITY --}}
                    <div class="sidebar-stat-item sidebar-stat-item-highlight">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-amber"></div>
                            <span class="sidebar-stat-label sidebar-stat-label-highlight">Pending RIS</span>
                        </div>
                        <span class="sidebar-stat-value sidebar-stat-value-highlight">{{ $pendingRis }}</span>
</div>

                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-slate"></div>
                            <span class="sidebar-stat-label">Admin Approved</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $directApprovedRis }}</span>
                    </div>
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-violet"></div>
                            <span class="sidebar-stat-label">Awaiting Action</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $forCosigningCount }}</span>
                    </div>
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-blue"></div>
                            <span class="sidebar-stat-label">Co-signed</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $cosignedCount }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>


{{-- Attention needed today (login popup) --}}
@php
    $attentionPending = (int) ($pendingPurchaserRisCount ?? $pendingRis ?? 0);
    $attentionCosign = (int) ($forCosigningCount ?? 0);
    $attentionAmend = (int) ($amendRis ?? 0);
    $showAttentionPopup = ($attentionPending + $attentionCosign + $attentionAmend) > 0;
@endphp
@if($showAttentionPopup)
<div id="adminDailyReminder" class="admin-daily-reminder hidden" role="dialog" aria-labelledby="adminDailyReminderTitle">
    <div class="admin-daily-reminder-card admin-attention-popup">
        <button type="button" class="admin-daily-reminder-close" onclick="dismissAdminDailyReminder()" aria-label="Dismiss">
            <i data-lucide="x" class="h-4 w-4"></i>
        </button>
        <h3 id="adminDailyReminderTitle" class="admin-attention-title">Attention needed today</h3>
        <p class="admin-attention-subtitle">Items that still need your action.</p>
        <div class="admin-attention-rows">
            <div class="admin-attention-row admin-attention-row-blue">
                <div>
                    <p class="admin-attention-label">Pending RIS needing review</p>
                    <p class="admin-attention-value">{{ $attentionPending }}</p>
                </div>
                <a href="{{ url('/admin/procurement-review') }}" class="admin-attention-cta">Review RIS</a>
            </div>
            <div class="admin-attention-row admin-attention-row-yellow">
                <div>
                    <p class="admin-attention-label">Awaiting Admin cosign</p>
                    <p class="admin-attention-value">{{ $attentionCosign }}</p>
                </div>
                <a href="{{ url('/admin/digital-signatures/sign-ris') }}" class="admin-attention-cta">Sign RIS</a>
            </div>
            <div class="admin-attention-row admin-attention-row-blue">
                <div>
                    <p class="admin-attention-label">Amendments / returned</p>
                    <p class="admin-attention-value">{{ $attentionAmend }}</p>
                </div>
                <a href="{{ url('/admin/procurement-review?filter=all') }}" class="admin-attention-cta">View all</a>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ===================================================== --}}
{{-- RIS PREVIEW MODAL --}}
{{-- ===================================================== --}}

{{-- RIS Preview modal --}}
@include('admin.partials.ris-preview-modal', ['zIndex' => '11000'])

@include('admin.procurement-review._direct-approve-modal')
@include('admin.digital-signatures._return-revision-modal')


{{-- ===================================================== --}}
{{-- DASHBOARD STYLES --}}
{{-- ===================================================== --}}
<style>

/* ======================================
   DASHBOARD LAYOUT
====================================== */

.admin-dashboard {
    margin: 0 auto;
    font-family: "Inter", sans-serif;
}

.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.dashboard-title {
    font-family: "Outfit", sans-serif;
    font-size: 2.25rem;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.dashboard-subtitle {
    margin-top: 4px;
    font-size: 0.875rem;
    color: #64748b;
}

.dashboard-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dashboard-date-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #475569;
}


/* ======================================
   STAT CARDS GRID - MP-aligned
====================================== */

.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 14px 16px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.stat-card:hover {
    transform: translateY(-1px);
    border-color: #d1d5db;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}

.stat-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 10px;
}

.stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon i,
.stat-icon svg {
    width: 18px;
    height: 18px;
}

.stat-icon-blue {
    background: #eff6ff;
    color: #3b82f6;
}

.stat-icon-indigo {
    background: #eff6ff;
    color: #3b82f6;
}

.stat-icon-amber {
    background: #fffbeb;
    color: #d97706;
}

.stat-icon-slate {
    background: #f1f5f9;
    color: #0f172a;
}

.stat-icon-sky {
    background: #eff6ff;
    color: #60a5fa;
}

.stat-icon-rose {
    background: #f1f5f9;
    color: #475569;
}

.stat-icon-violet {
    background: #eff6ff;
    color: #3b82f6;
}

.stat-icon-teal {
    background: #e2e8f0;
    color: #334155;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

.stat-change-up {
    background: #f1f5f9;
    color: #475569;
}

.stat-change-warn {
    background: #e2e8f0;
    color: #334155;
}

.stat-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
}

.stat-value {
    font-family: "Outfit", sans-serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1;
}

.stat-amount {
    margin-top: 6px;
    font-size: 12px;
    color: #94a3b8;
    font-weight: 500;
}

.stat-meta {
    margin-top: 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.stat-meta-item {
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
}

.stat-meta-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
}

.stat-dot-purple { background: #475569; }
.stat-dot-cyan { background: #64748b; }
.stat-dot-amber { background: #334155; }
.stat-dot-emerald { background: #94a3b8; }
.stat-dot-rose { background: #0f172a; }


/* ======================================
   MAIN GRID (HERO + SIDEBAR)
====================================== */

.dashboard-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 24px;
    align-items: start;
}

.dashboard-hero {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
}


/* ======================================
   SIDEBAR GAP
====================================== */

.dashboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 24px;
}


   TABLE PREVIEW BUTTON
====================================== */

.table-preview-btn,
.table-action-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #4b5563;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}

.table-preview-btn:hover,
.table-action-icon-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #111827;
}

.table-preview-btn i,
.table-preview-btn svg,
.table-action-icon-btn i,
.table-action-icon-btn svg {
    width: 16px;
    height: 16px;
}


/* ======================================
   RIS PREVIEW MODAL
====================================== */

.ris-preview-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.qa-modal-overlay {
    z-index: 10000;
}

.ris-preview-on-top {
    z-index: 11000;
}

.ris-preview-modal-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 1100px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.ris-preview-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.ris-preview-modal-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.ris-preview-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s ease;
}

.ris-preview-modal-close:hover {
    background: #fef2f2;
    border-color: #fecdd3;
    color: #334155;
}

.ris-preview-modal-body {
    flex: 1;
    overflow: auto;
    padding: 0;
    background: #f8fafc;
    min-height: 400px;
    max-height: calc(90vh - 110px);
}

.ris-preview-modal-body iframe {
    width: 100%;
    height: 100%;
    min-height: 400px;
    max-height: calc(90vh - 110px);
    border: none;
    display: block;
}

.ris-preview-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 60px 20px;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
}

.ris-preview-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid #e2e8f0;
    border-top-color: #334155;
    border-radius: 50%;
    animation: ris-preview-spin 0.8s linear infinite;
}

@keyframes ris-preview-spin {
    to { transform: rotate(360deg); }
}

.ris-preview-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 20px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.ris-preview-modal-btn-close {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ris-preview-modal-btn-close:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.ris-preview-modal-btn-print {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    background: #0f172a;
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.ris-preview-modal-btn-print:hover {
    background: #1e293b;
}

.ris-preview-modal-btn-print i,
.ris-preview-modal-btn-print svg {
    width: 14px;
    height: 14px;
}


/* ======================================
   ACTIVITY LIST - Status Icons
====================================== */

.sidebar-activity-status-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.act-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.act-icon-success {
    background: #ecfdf5;
    color: #475569;
}

.act-icon-danger {
    background: #fef2f2;
    color: #dc2626;
}

.act-icon-pending {
    background: #fffbeb;
    color: #475569;
}

.sidebar-activity-actor {
    font-weight: 400;
    font-size: 9px;
    color: #94a3b8;
}


/* ======================================
   QUICK SUMMARY - Pending Highlight
====================================== */

.sidebar-stat-item-highlight {
    background: #fffbeb;
    border-radius: 6px;
    padding: 5px 8px !important;
    margin: -1px -1px 1px -1px;
    border: 1px solid #fde68a;
}

.sidebar-stat-label-highlight {
    font-weight: 700;
    color: #92400e;
}

.sidebar-stat-value-highlight {
    font-size: 13px;
    font-weight: 800;
    color: #475569;
}


/* ======================================
   HERO ALERT CARDS
====================================== */

.hero-alert-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
    border-radius: 18px;
    margin-bottom: 16px;
}

.hero-alert-card-violet {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-color: #cbd5e1;
}

.hero-alert-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.hero-alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    background: #64748b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.hero-alert-icon-violet {
    background: #334155;
}

.hero-alert-icon i,
.hero-alert-icon svg {
    width: 18px;
    height: 18px;
}

.hero-alert-title {
    font-family: "Outfit", sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.hero-alert-desc {
    margin-top: 2px;
    font-size: 12px;
    color: #64748b;
}

.hero-alert-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: rgba(0, 55, 199, 0.85);
    color: white;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.hero-alert-btn:hover {
    background: rgba(0, 44, 155, 0.85);
    transform: translateY(-1px);
}

.hero-alert-btn-violet {
    background: rgba(0, 55, 199, 0.85);
}

.hero-alert-btn-violet:hover {
    background: rgba(0, 44, 155, 0.85);
}

.hero-empty-card {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    margin-bottom: 8px;
}

.hero-empty-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.hero-empty-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #64748b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.hero-empty-icon i,
.hero-empty-icon svg {
    width: 16px;
    height: 16px;
}

.hero-empty-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.hero-empty-desc {
    margin-top: 1px;
    font-size: 11px;
    color: #64748b;
}


/* ======================================
   CHART CARD
====================================== */

.dashboard-chart-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 8px;
}

.dashboard-chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-chart-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.dashboard-chart-subtitle {
    font-size: 11px;
    color: #64748b;
    margin-top: 1px;
}

.dashboard-chart-legend {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 12px;
    flex-shrink: 0;
}

.dashboard-chart-legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
}

.dashboard-chart-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
}

.dashboard-chart-body {
    padding: 10px 14px;
}

.dashboard-chart-body-trend {
    height: 240px;
    position: relative;
}

.budget-proposal-total {
    text-align: right;
    flex-shrink: 0;
}

.budget-proposal-total-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.budget-proposal-total-value {
    display: block;
    margin-top: 2px;
    font-family: "Outfit", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.budget-proposal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    padding: 12px 14px 14px;
}

.budget-proposal-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 10px 12px;
    background: #f8fafc;
}

.budget-proposal-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    margin-top: 6px;
    flex-shrink: 0;
}

.budget-proposal-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
}

.budget-proposal-value {
    margin-top: 2px;
    font-family: "Outfit", sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

@media (max-width: 640px) {
    .budget-proposal-grid {
        grid-template-columns: 1fr;
    }
}


/* ======================================
   TABLE CARD
====================================== */

.dashboard-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.dashboard-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-table-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.dashboard-table-subtitle {
    font-size: 11px;
    color: #64748b;
    margin-top: 1px;
}

.dashboard-table-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    transition: all 0.2s ease;
}

.dashboard-table-link:hover {
    color: #1d4ed8;
    gap: 8px;
}

.dashboard-table-body {
    overflow-x: auto;
}

.dashboard-table {
    width: 100%;
    border-collapse: collapse;
}

.dashboard-table thead {
    background: #f8fafc;
}

.dashboard-table th {
    padding: 8px 14px;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
}

.dashboard-table td {
    padding: 8px 14px;
    font-size: 12px;
    color: #475569;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-table tbody tr:last-child td {
    border-bottom: none;
}

.dashboard-table tbody tr:hover {
    background: #fafafa;
}

.dashboard-table .dashboard-table-date {
    text-align: left;
    white-space: nowrap;
    width: 1%;
    min-width: 110px;
    vertical-align: middle;
}

.dashboard-table .dashboard-table-actions {
    text-align: center;
    width: 1%;
    white-space: nowrap;
    vertical-align: middle;
}

.table-ref-no {
    font-weight: 600;
    color: #0f172a;
}

.table-equip {
    max-width: 180px;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.text-right {
    text-align: right;
}

.status-badge {
    display: inline-flex;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge-amber {
    background: #fffbeb;
    color: #475569;
    border: 1px solid #fde68a;
}

.status-badge-slate {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
}

.status-badge-emerald {
    background: #ecfdf5;
    color: #475569;
    border: 1px solid #a7f3d0;
}

.status-badge-rose {
    background: #fff1f2;
    color: #334155;
    border: 1px solid #fecdd3;
}

.status-badge-gray {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}


/* ======================================
   SIDEBAR COMPONENTS
====================================== */

/* Sidebar Chart */

.sidebar-chart-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.sidebar-chart-header {
    padding: 10px 14px 0;
}

.sidebar-chart-title {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}

.sidebar-chart-body {
    padding: 6px 12px 10px;
}


/* ======================================
   CALENDAR OF EVENTS
====================================== */

.sidebar-calendar-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.sidebar-calendar-header {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-calendar-title {
    font-family: "Outfit", sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
}

.sidebar-calendar-body {
    padding: 10px 12px 12px;
}

.sidebar-supplier-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.sidebar-supplier-header {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-supplier-title {
    font-family: "Outfit", sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
}

.sidebar-supplier-body {
    padding: 12px 14px 14px;
}

.supplier-type-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.supplier-type-item {
    border: 1px solid #f1f5f9;
    background: #f8fafc;
    border-radius: 10px;
    padding: 8px 10px;
}

.supplier-type-label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.supplier-type-value {
    display: block;
    margin-top: 2px;
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}

.supplier-type-amount {
    display: block;
    margin-top: 1px;
    font-size: 11px;
    color: #64748b;
}

.supplier-type-bar {
    display: flex;
    height: 6px;
    border-radius: 999px;
    overflow: hidden;
    margin: 10px 0 12px;
    background: #e2e8f0;
}

.supplier-type-bar-physical {
    background: #0f172a;
    display: block;
}

.supplier-type-bar-online {
    background: #334155;
    display: block;
}

.supplier-compare-caption {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 8px;
}

.supplier-compare-row {
    margin-bottom: 10px;
}

.supplier-compare-row:last-child {
    margin-bottom: 0;
}

.supplier-compare-meta {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.supplier-compare-name {
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.supplier-compare-amount {
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
    flex-shrink: 0;
}

.supplier-compare-track {
    height: 6px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.supplier-compare-fill {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: #334155;
}

.supplier-compare-count {
    display: block;
    margin-top: 3px;
    font-size: 10px;
    color: #94a3b8;
}

.supplier-compare-empty {
    font-size: 12px;
    color: #94a3b8;
    text-align: center;
    padding: 12px 0 4px;
}

.calendar-month-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.cal-nav-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s ease;
}

.cal-nav-btn:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    color: #0f172a;
}

.cal-nav-btn:disabled,
.cal-nav-btn:disabled:hover {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #94a3b8;
}

.cal-month-label {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    margin-bottom: 10px;
}

.cal-day-header {
    text-align: center;
    font-size: 8px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 2px 0;
}

.cal-day {
    text-align: center;
    padding: 4px 1px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 500;
    color: #475569;
    cursor: default;
    position: relative;
    min-height: 22px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease;
}

.cal-day:hover {
    background: #f8fafc;
}

.cal-day-empty {
    cursor: default;
    opacity: 0.3;
}

.cal-day-empty:hover {
    background: transparent;
}

.cal-day-today {
    background: #eef2ff;
    color: #334155;
    font-weight: 700;
}

.cal-day-today:hover {
    background: #e0e7ff;
}

.cal-day-has-event {
    color: #0f172a;
    font-weight: 600;
    cursor: pointer;
}

.cal-day-selected {
    outline: 2px solid #475569;
    outline-offset: 1px;
    background: #eff6ff !important;
}

.cal-upcoming-item.is-highlighted {
    background: #eff6ff;
    border-radius: 8px;
    padding: 6px 8px;
    margin: 0 -4px;
}

.cal-upcoming-item a {
    color: inherit;
    text-decoration: none;
}

.cal-upcoming-item a:hover .cal-upcoming-name {
    color: #1d4ed8;
    text-decoration: underline;
}

.admin-attention-popup {
    width: min(480px, 100%);
    text-align: left;
    padding: 22px 20px 18px;
}

.admin-attention-popup .admin-attention-title {
    margin-right: 36px;
}

.admin-attention-popup .admin-attention-rows {
    margin-top: 16px;
}

.ris-metrics-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}

.ris-metric-card {
    background: #ffffff;
    border: 1px solid #e8eaef;
    border-radius: 16px;
    padding: 16px 16px 10px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    min-width: 0;
}

.ris-metric-card-wide {
    grid-column: 1 / -1;
}

.ris-metric-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #94a3b8;
}

.ris-metric-value-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 6px;
    flex-wrap: wrap;
}

.ris-metric-value {
    font-family: "Outfit", "Inter", sans-serif;
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.03em;
    line-height: 1.1;
}

.ris-metric-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
}

.ris-metric-pill.is-up {
    background: #ecfdf5;
    color: #475569;
}

.ris-metric-pill.is-down {
    background: #fff1f2;
    color: #334155;
}

.ris-metric-hint {
    display: block;
    margin-top: 4px;
    font-size: 11px;
    color: #94a3b8;
}

.ris-metric-chart {
    height: 120px;
    margin-top: 8px;
}

.ris-metric-chart-sm {
    height: 72px;
}

.ris-overview-card .ris-overview-sub {
    margin-top: 2px;
    font-size: 11px;
    color: #94a3b8;
    font-weight: 400;
}

.ris-overview-chart-wrap {
    padding: 8px 14px 4px;
    height: 140px;
}

.ris-overview-breakdown {
    border-top: 1px solid #f1f5f9;
    padding: 8px 14px 12px;
}

.ris-overview-row {
    display: grid;
    grid-template-columns: 10px 1fr auto auto;
    gap: 8px;
    align-items: center;
    padding: 7px 0;
}

.ris-overview-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.ris-overview-name {
    font-size: 12px;
    color: #334155;
    font-weight: 500;
}

.ris-overview-count {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}

.ris-overview-amt {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    min-width: 36px;
    text-align: right;
}

.ris-overview-amt.is-pos {
    color: #475569;
}

@media (max-width: 900px) {
    .ris-metrics-grid {
        grid-template-columns: 1fr;
    }
    .ris-metric-card-wide {
        grid-column: auto;
    }
}

.admin-attention-title {
    font-family: "Outfit", sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0a0a0a;
}

.admin-attention-subtitle {
    margin-top: 2px;
    font-size: 0.8rem;
    color: #64748b;
}

.admin-attention-rows {
    margin-top: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.admin-attention-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #fff;
}

.admin-attention-row-blue {
    border-left: 4px solid #93c5fd;
    background: #f8fbff;
}

.admin-attention-row-yellow {
    border-left: 4px solid #fde68a;
    background: #fffdf5;
}

.admin-attention-label {
    font-size: 0.8rem;
    color: #334155;
    font-weight: 600;
}

.admin-attention-value {
    margin-top: 2px;
    font-size: 1.25rem;
    font-weight: 800;
    color: #0a0a0a;
}

.admin-attention-cta {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #0a0a0a;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
}

.admin-attention-cta:hover {
    background: #f8fafc;
}

.admin-daily-reminder {
    position: fixed;
    inset: 0;
    z-index: 12000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.45);
    padding: 16px;
}

.admin-daily-reminder.hidden {
    display: none !important;
}

.admin-daily-reminder-card {
    position: relative;
    width: min(420px, 100%);
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 24px 22px 20px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
    text-align: left;
}

.admin-daily-reminder-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    cursor: pointer;
}

.admin-daily-reminder-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #eff6ff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.admin-daily-reminder-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0a0a0a;
}

.admin-daily-reminder-text {
    margin-top: 8px;
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.45;
}

.admin-daily-reminder-cta {
    margin-top: 16px;
    display: inline-flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 10px;
    background: #0a0a0a;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
}

.cal-day-num {
    line-height: 1;
}

.cal-day-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #64748b;
    margin-top: 1px;
    flex-shrink: 0;
}

.cal-upcoming {
    border-top: 1px solid #f1f5f9;
    padding-top: 8px;
}

.cal-upcoming-title {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 6px;
}

.cal-upcoming-item {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    padding: 4px 0;
}

.cal-upcoming-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #64748b;
    margin-top: 4px;
    flex-shrink: 0;
}

.cal-upcoming-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.cal-upcoming-name {
    font-size: 11px;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cal-upcoming-date {
    font-size: 9px;
    color: #94a3b8;
}

.cal-upcoming-empty {
    font-size: 10px;
    color: #94a3b8;
    text-align: center;
    padding: 6px 0;
}

.cal-view-all {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    margin-top: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #0f172a;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.cal-view-all:hover {
    background: #fff;
    border-color: #cbd5e1;
    color: #1d4ed8;
}

.cal-view-all-hint {
    margin-top: 6px;
    font-size: 10px;
    color: #94a3b8;
    text-align: center;
}


/* Sidebar Stats */

.sidebar-stats-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 16px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.sidebar-stats-title {
    font-family: "Outfit", sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
}

.sidebar-stats-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.sidebar-stat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #f8fafc;
}

.sidebar-stat-item:last-child {
    border-bottom: none;
}

.sidebar-stat-left {
    display: flex;
    align-items: center;
    gap: 6px;
}

.sidebar-stat-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.sidebar-dot-blue { background: #60a5fa; }
.sidebar-dot-amber { background: #fbbf24; }
.sidebar-dot-slate { background: #93c5fd; }
.sidebar-dot-emerald { background: #94a3b8; }
.sidebar-dot-violet { background: #3b82f6; }
.sidebar-dot-teal { background: #64748b; }
.sidebar-dot-rose { background: #475569; }

.sidebar-stat-label {
    font-size: 11px;
    color: #475569;
}

.sidebar-stat-value {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}


/* Sidebar Activity Feed */

.sidebar-activity-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.sidebar-activity-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-activity-title {
    font-family: "Outfit", sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.sidebar-activity-link {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    text-decoration: none;
}

.sidebar-activity-link:hover {
    text-decoration: underline;
}

.sidebar-activity-list {
    padding: 4px 0;
}

.sidebar-activity-item {
    display: flex;
    gap: 8px;
    padding: 8px 14px;
    transition: background 0.2s ease;
}

.sidebar-activity-item:hover {
    background: #f8fafc;
}

.sidebar-activity-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}

.sidebar-activity-content {
    min-width: 0;
    flex: 1;
}

.sidebar-activity-title-text {
    font-size: 11px;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-activity-desc {
    font-size: 10px;
    color: #64748b;
    margin-top: 1px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.sidebar-activity-time {
    font-size: 9px;
    color: #94a3b8;
    margin-top: 2px;
}

/* Activity Separator */
.sidebar-activity-separator {
    display: flex;
    align-items: center;
    padding: 4px 14px 2px;
    gap: 6px;
}

.sidebar-activity-separator::before,
.sidebar-activity-separator::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}

.sidebar-activity-separator-text {
    font-size: 9px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}


/* ======================================
   RESPONSIVE
====================================== */

@media (max-width: 1200px) {
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .dashboard-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .admin-dashboard {
        padding: 20px 16px;
    }
    .stat-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-header {
        flex-direction: column;
        gap: 12px;
    }
    .hero-alert-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .hero-alert-btn {
        width: 100%;
        justify-content: center;
    }
}

</style>


{{-- ===================================================== --}}
{{-- DASHBOARD CHARTS JAVASCRIPT --}}
{{-- ===================================================== --}}
<script>
document.addEventListener('DOMContentLoaded', function() {

    // =====================================================
    // RIS OVERVIEW METRIC CHARTS (reference-style)
    // =====================================================

    const trendLabels = {!! json_encode($risTrendLabels ?? []) !!};
    const trendApproved = {!! json_encode($risTrendApproved ?? []) !!};
    const trendForwarded = {!! json_encode($risTrendForwarded ?? []) !!};
    const trendAmend = {!! json_encode($risTrendAmend ?? []) !!};
    const trendRejected = {!! json_encode($risTrendRejected ?? []) !!};
    const amountSeries = [
        {{ (float) ($budgetPendingAmount ?? 0) }},
        {{ (float) ($budgetAdminApprovedAmount ?? 0) }},
        {{ (float) ($budgetPresidentApprovedAmount ?? 0) }},
        {{ (float) ($budgetPresidentRejectedAmount ?? 0) }},
        {{ (float) ($budgetProposalTotal ?? 0) }}
    ];
    const amountLabels = ['Pending', 'Admin', 'President', 'Rejected', 'Total'];

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: true } },
    };

    function blueLine(ctx, data, labels, withPoints) {
        if (!ctx || !labels.length) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    borderColor: '#60a5fa',
                    backgroundColor: 'transparent',
                    borderWidth: 2.25,
                    tension: 0.4,
                    pointRadius: withPoints ? 3 : 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#60a5fa',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    fill: false,
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            display: !!withPoints,
                            color: '#94a3b8',
                            font: { size: 10 },
                            maxRotation: 0,
                        },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)',
                            drawBorder: false,
                        },
                        ticks: {
                            display: !!withPoints,
                            color: '#94a3b8',
                            font: { size: 10 },
                            padding: 6,
                        },
                        border: { display: false },
                    },
                },
                interaction: { intersect: false, mode: 'index' },
            }
        });
    }

    function blueBars(ctx, data, labels) {
        if (!ctx) return;
        const g = ctx.getContext('2d').createLinearGradient(0, 0, 0, 120);
        g.addColorStop(0, 'rgba(59, 130, 246, 0.95)');
        g.addColorStop(1, 'rgba(59, 130, 246, 0.18)');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: g,
                    borderRadius: 10,
                    borderSkipped: false,
                    barPercentage: 0.55,
                    categoryPercentage: 0.7,
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 0 },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.18)', drawBorder: false },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 10 },
                            callback: function (v) {
                                if (v >= 1000000) return (v / 1000000) + 'm';
                                if (v >= 1000) return (v / 1000) + 'k';
                                return v;
                            }
                        },
                        border: { display: false },
                    },
                },
            }
        });
    }

    blueLine(document.getElementById('risProposedChart'), amountSeries, amountLabels, true);
    blueLine(document.getElementById('risApprovedSpark'), trendApproved, trendLabels, false);
    blueBars(document.getElementById('risPresidentSpark'), trendForwarded, trendLabels);
    blueBars(document.getElementById('risPendingBars'), amountSeries.slice(0, 4), amountLabels.slice(0, 4));
    blueLine(document.getElementById('risAmendSpark'), trendAmend.map(function (a, i) {
        return a + (trendRejected[i] || 0);
    }), trendLabels, false);

    // =====================================================
    // RIS STATUS OVERVIEW (rounded bars)
    // =====================================================

    const statusCtx = document.getElementById('risStatusChart');
    if (statusCtx) {
        const statusData = {!! json_encode($risStatusChart['data'] ?? []) !!};
        const statusLabels = {!! json_encode($risStatusChart['labels'] ?? []) !!};
        const barGrad = statusCtx.getContext('2d').createLinearGradient(0, 0, 0, 140);
        barGrad.addColorStop(0, 'rgba(59, 130, 246, 0.95)');
        barGrad.addColorStop(1, 'rgba(59, 130, 246, 0.2)');
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: statusLabels.map(function (l) {
                    return String(l).replace('Approved by the President', 'President')
                        .replace('Rejected by the President', 'Rejected')
                        .replace('Admin Approved', 'Admin');
                }),
                datasets: [{
                    data: statusData,
                    backgroundColor: barGrad,
                    borderRadius: 12,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.75,
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 0 },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#94a3b8', font: { size: 10 } },
                        grid: { color: 'rgba(148, 163, 184, 0.16)', drawBorder: false },
                        border: { display: false },
                    },
                },
            }
        });
    }


    // =====================================================
    // MINI CALENDAR RENDER - REMOVED
    // =====================================================


    // =====================================================
    // CALENDAR MONTH NAVIGATION
    // =====================================================

    (function() {
        var prevBtn = document.getElementById('calPrevBtn');
        var nextBtn = document.getElementById('calNextBtn');
        var monthLabel = document.getElementById('calMonthLabel');
        var grid = document.getElementById('adminCalendarGrid');
        var upcoming = document.getElementById('adminCalendarUpcoming');
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

        function canGoPrev() {
            return monthIndex(view) > minMonthIndex;
        }

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

            var list = '<h4 class="cal-upcoming-title">' + escapeHtml(title) + '</h4>';
            if (!listEvents.length) {
                list += '<div class="cal-upcoming-empty">' + (dateKey ? 'No events on this day' : 'No procurement dates this month') + '</div>';
            } else {
                listEvents.forEach(function (e) {
                    var p = e.date.split('-');
                    var label = monthNames[parseInt(p[1], 10) - 1] + ' ' + parseInt(p[2], 10) + ', ' + p[0];
                    var href = e.url || '/admin/procurement-review';
                    var highlight = dateKey ? ' is-highlighted' : '';
                    list += '<div class="cal-upcoming-item' + highlight + '" data-event-date="' + escapeHtml(e.date) + '">';
                    list += '<div class="cal-upcoming-dot"></div><div class="cal-upcoming-content">';
                    list += '<a href="' + escapeHtml(href) + '"><span class="cal-upcoming-name">' + escapeHtml(e.name) + '</span></a>';
                    list += '<span class="cal-upcoming-date">' + escapeHtml(label) + '</span></div></div>';
                });
            }

            if (totalCount > 0) {
                list += '<a class="cal-view-all" href="' + escapeHtml(viewAllHref) + '">View all</a>';
            }
            if (totalCount > 3) {
                list += '<p class="cal-view-all-hint">Showing 3 of ' + totalCount + (dateKey ? ' on this day' : '') + '</p>';
            }

            upcoming.innerHTML = list;
        }

        function render() {
            if (!grid || !monthLabel) return;
            var year = view.getFullYear();
            var month = view.getMonth();
            monthLabel.textContent = monthNames[month] + ' ' + year;

            var first = new Date(year, month, 1);
            var lastDate = new Date(year, month + 1, 0).getDate();
            var startPad = first.getDay();
            var todayKey = ymd(new Date());
            var html = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function (d) {
                html += '<div class="cal-day-header">' + d + '</div>';
            });
            var totalSlots = Math.ceil((startPad + lastDate) / 7) * 7;
            for (var i = 0; i < totalSlots; i++) {
                var dayNum = i - startPad + 1;
                if (dayNum < 1 || dayNum > lastDate) {
                    html += '<div class="cal-day cal-day-empty"></div>';
                    continue;
                }
                var dateKey = year + '-' + pad(month + 1) + '-' + pad(dayNum);
                var dayEvents = eventsOn(dateKey);
                var cls = 'cal-day';
                if (dateKey === todayKey) cls += ' cal-day-today';
                if (dayEvents.length) cls += ' cal-day-has-event';
                if (selectedDate === dateKey) cls += ' cal-day-selected';
                html += '<div class="' + cls + '" data-date="' + dateKey + '" title="' + (dayEvents.length ? dayEvents.length + ' event(s)' : '') + '">';
                html += '<span class="cal-day-num">' + dayNum + '</span>';
                if (dayEvents.length) html += '<span class="cal-day-dot"></span>';
                html += '</div>';
            }
            grid.innerHTML = html;
            renderUpcoming(selectedDate);
            updateNavButtons();
        }

        if (grid) {
            grid.addEventListener('click', function (e) {
                var dayEl = e.target.closest('.cal-day[data-date]');
                if (!dayEl || dayEl.classList.contains('cal-day-empty')) return;
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
        }

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


// =====================================================
    // ACTIVITY LIST TOGGLE (Pending / Completed)
    // =====================================================

    const toggleBtn = document.getElementById('activityToggleBtn');
    const completedSection = document.getElementById('completedActivities');

    if (toggleBtn && completedSection) {
        let expanded = false;

        toggleBtn.addEventListener('click', function() {
            expanded = !expanded;

            if (expanded) {
                completedSection.style.display = 'block';
                toggleBtn.textContent = 'Hide completed';
            } else {
                completedSection.style.display = 'none';
                toggleBtn.textContent = 'Show completed';
            }
        });
    }


    // =====================================================
    // RIS PREVIEW MODAL
    // =====================================================

    window.openRisPreviewModal = function(risId) {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');

        if (!modal || !iframe) return;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        iframe.src = '/admin/procurement-review/ris/' + risId + '/print?ts=' + Date.now();
        if (window.fillRisPreviewAttachments) {
            window.fillRisPreviewAttachments(risId);
        }
        modal.classList.remove('hidden');
        modal.style.display = 'block';
        modal.style.zIndex = '11000';
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
        requestAnimationFrame(function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('risPreviewIframe');
            }
        });
        iframe.onload = function () {
            if (typeof window.scaleRisPreviewIframe === 'function') {
                window.scaleRisPreviewIframe('risPreviewIframe');
            }
        };
    };

    window.openSignRisPreviewModal = function(risId) {
        window.openRisPreviewModal(risId);
    };
    window.openSignatureHistoryPreviewModal = function(risId) {
        window.openRisPreviewModal(risId);
    };

    window.printRisPreview = function() {
        const iframe = document.getElementById('risPreviewIframe');
        if (!iframe || !iframe.contentWindow) return;
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };

    window.closeRisPreviewModal = function() {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = '';
        }
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        const risModal = document.getElementById('risPreviewModal');
        if (risModal && !risModal.classList.contains('hidden')) {
            closeRisPreviewModal();
            return;
        }
        const reminder = document.getElementById('adminDailyReminder');
        if (reminder && !reminder.classList.contains('hidden')) {
            dismissAdminDailyReminder();
        }
    });

    // =====================================================
    // ATTENTION NEEDED TODAY (once per browser login/session)
    // =====================================================

    window.dismissAdminDailyReminder = function () {
        var el = document.getElementById('adminDailyReminder');
        if (el) el.classList.add('hidden');
        try { sessionStorage.setItem(@json('admin_attention_popup_shown_' . (session('attention_popup_token') ?: 'default')), '1'); } catch (e) {}
    };

    (function () {
        var el = document.getElementById('adminDailyReminder');
        if (!el) return;
        var storageKey = @json('admin_attention_popup_shown_' . (session('attention_popup_token') ?: 'default'));
        try {
            if (sessionStorage.getItem(storageKey) === '1') return;
            el.classList.remove('hidden');
            sessionStorage.setItem(storageKey, '1');
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        } catch (err) {
            el.classList.remove('hidden');
        }
    })();

});
</script>

@endsection

