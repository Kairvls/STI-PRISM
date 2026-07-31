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
    {{-- STATISTIC CARDS ROW 1 --}}
    {{-- ===================================================== --}}

    <div class="stat-grid">

        {{-- PENDING RIS (conditional - only show when > 0, at very top) --}}

        @if($pendingRis > 0)
        <div class="stat-card stat-card-warning" title="RIS forms currently waiting for review">
            <div class="stat-card-top">
                <div class="stat-icon stat-icon-amber">
                    <i data-lucide="clock"></i>
                </div>
                <span class="stat-change stat-change-warn">
                    <i data-lucide="alert-circle" class="h-3 w-3"></i>
                    Needs attention
                </span>
            </div>
            <p class="stat-label">Pending RIS</p>
            <p class="stat-value">{{ $pendingRis }}</p>
            <p class="stat-amount">₱{{ number_format($pendingRisAmount, 2) }} pending value</p>
        </div>
        @endif

        {{-- TOTAL USERS --}}

        <div class="stat-card stat-card-primary" title="Total registered users in the system">
            <div class="stat-card-top">
                <div class="stat-icon stat-icon-blue">
                    <i data-lucide="users"></i>
                </div>
                <span class="stat-change stat-change-up">
                    <i data-lucide="trending-up" class="h-3 w-3"></i>
                    {{ $activeUsers }} active
                </span>
            </div>
            <p class="stat-label">Total Users</p>
            <p class="stat-value">{{ $totalUsers }}</p>
            <div class="stat-meta">
                <div class="stat-meta-item">
                    <span class="stat-meta-dot stat-dot-purple"></span>
                    {{ $maintenancePersonnel }} Maintenance
                </div>
                <div class="stat-meta-item">
                    <span class="stat-meta-dot stat-dot-cyan"></span>
                    {{ $purchasers }} Purchaser
                </div>
                <div class="stat-meta-item">
                    <span class="stat-meta-dot stat-dot-amber"></span>
                    {{ $presidents }} President
                </div>
                <div class="stat-meta-item">
                    <span class="stat-meta-dot stat-dot-emerald"></span>
                    {{ $accounting }} Accounting
                </div>
                <div class="stat-meta-item">
                    <span class="stat-meta-dot stat-dot-rose"></span>
                    {{ $receivingOfficers }} Receiving
                </div>
            </div>
        </div>


        {{-- TOTAL RIS --}}

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


        {{-- DIRECT APPROVED --}}

        <div class="stat-card stat-card-success" title="RIS forms directly approved by Admin">
            <div class="stat-card-top">
                <div class="stat-icon stat-icon-emerald">
                    <i data-lucide="check-circle"></i>
                </div>
            </div>
            <p class="stat-label">Direct Approved</p>
            <p class="stat-value">{{ $directApprovedRis }}</p>
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- STATISTIC CARDS ROW 2 --}}
    {{-- ===================================================== --}}

    <div class="stat-grid stat-grid-row-2">

        {{-- FORWARDED TO PRESIDENT --}}

        <div class="stat-card stat-card-info" title="RIS forwarded to President for approval">
            <div class="stat-card-top">
                <div class="stat-icon stat-icon-sky">
                    <i data-lucide="send"></i>
                </div>
            </div>
            <p class="stat-label">Forwarded to President</p>
            <p class="stat-value">{{ $approvedRis }}</p>
        </div>


        {{-- AMEND --}}

        <div class="stat-card stat-card-danger" title="RIS returned for amendment">
            <div class="stat-card-top">
                <div class="stat-icon stat-icon-rose">
                    <i data-lucide="rotate-ccw"></i>
                </div>
            </div>
            <p class="stat-label">Amend</p>
            <p class="stat-value">{{ $amendRis }}</p>
        </div>


        {{-- FOR CO-SIGNING --}}

        <div class="stat-card" title="RIS waiting for Admin co-signature">
            <div class="stat-card-top">
                <div class="stat-icon stat-icon-violet">
                    <i data-lucide="pen-tool"></i>
                </div>
            </div>
            <p class="stat-label">For Co-signing</p>
            <p class="stat-value">{{ $forCosigningCount }}</p>
        </div>


        {{-- CO-SIGNED --}}

        <div class="stat-card stat-card-success" title="RIS that have been fully co-signed">
            <div class="stat-card-top">
                <div class="stat-icon stat-icon-teal">
                    <i data-lucide="signature"></i>
                </div>
            </div>
            <p class="stat-label">Co-signed</p>
            <p class="stat-value">{{ $cosignedCount }}</p>
        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- MAIN CONTENT: HERO + ACTIVITY SIDEBAR --}}
    {{-- ===================================================== --}}

    <div class="dashboard-main-grid">

        {{-- LEFT: HERO SECTION --}}

        <div class="dashboard-hero">

            {{-- Pending RIS Alert --}}

            @if($pendingRis > 0)

            <div class="hero-alert-card">
                <div class="hero-alert-left">
                    <div class="hero-alert-icon">
                        <i data-lucide="bell-ringing"></i>
                    </div>
                    <div>
                        <h3 class="hero-alert-title">{{ $pendingRis }} RIS {{ $pendingRis === 1 ? 'is' : 'are' }} pending your review</h3>
                        <p class="hero-alert-desc">These Requisition Issue Slips need your decision — forward to President, direct approve, or return for amendment.</p>
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
                        <h3 class="hero-alert-title">{{ $forCosigningCount }} RIS {{ $forCosigningCount === 1 ? 'needs' : 'need' }} your co-signature</h3>
                        <p class="hero-alert-desc">President-approved RIS documents waiting for your digital signature to complete the approval chain.</p>
                    </div>
                </div>
                <a href="{{ route('admin.digital-signatures.sign-ris', ['filter' => 'for_cosign']) }}" class="hero-alert-btn hero-alert-btn-violet">
                    Sign Now
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            @endif


            {{-- RIS Monthly Trend Chart --}}

            <div class="dashboard-chart-card">
                <div class="dashboard-chart-header">
                    <div>
                        <h3 class="dashboard-chart-title">RIS Submission Trend</h3>
                        <p class="dashboard-chart-subtitle">Monthly submission volume over the last 6 months</p>
                    </div>
                </div>
                <div class="dashboard-chart-body">
                    <canvas id="risTrendChart" height="200"></canvas>
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
                                <th class="text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRisRecords as $ris)
                            <tr>
                                <td>
                                    <span class="table-ref-no">{{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}</span>
                                </td>
                                <td>
                                    <span class="table-equip">{{ $ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($ris->ris_status === 'Pending')
                                        <span class="status-badge status-badge-amber">Pending</span>
                                    @elseif($ris->ris_status === 'Approved' && !empty($ris->ris_approved_by_signature) && !str_starts_with($ris->ris_approved_by_signature ?? '', 'data:image'))
                                        <span class="status-badge status-badge-slate">Direct Approved</span>
                                    @elseif($ris->ris_status === 'Approved' && !empty($ris->ris_approved_by_date))
                                        <span class="status-badge status-badge-emerald">Forwarded to President</span>
                                    @elseif($ris->ris_status === 'Rejected')
                                        <span class="status-badge status-badge-rose">Amend</span>
                                    @else
                                        <span class="status-badge status-badge-gray">{{ $ris->ris_status }}</span>
                                    @endif
                                </td>
                                <td class="text-right font-semibold text-gray-900">
                                    ₱{{ number_format((float)($ris->ris_calculated_total ?? 0), 2) }}
                                </td>
                                <td class="text-right text-sm text-gray-500">
                                    {{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('M d, Y') : 'N/A' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-12 text-gray-400">
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

            {{-- 1. Calendar of Events --}}

            <div class="sidebar-calendar-card">
                <div class="sidebar-calendar-header">
                    <h3 class="sidebar-calendar-title">
                        <i data-lucide="calendar-days" class="sidebar-calendar-title-icon"></i>
                        Calendar of Events
                    </h3>
                    <span class="sidebar-calendar-month">{{ now()->format('F Y') }}</span>
                </div>
                <div class="sidebar-calendar-grid" id="miniCalendar">
                    {{-- Weekday headers --}}
                    <div class="cal-weekday">Sun</div>
                    <div class="cal-weekday">Mon</div>
                    <div class="cal-weekday">Tue</div>
                    <div class="cal-weekday">Wed</div>
                    <div class="cal-weekday">Thu</div>
                    <div class="cal-weekday">Fri</div>
                    <div class="cal-weekday">Sat</div>
                </div>
                {{-- Upcoming Events List --}}
                <div class="sidebar-calendar-events">
                    <div class="sidebar-calendar-events-title">Upcoming Schedules</div>
                    @forelse($calendarEvents->take(5) as $event)
                    <div class="sidebar-calendar-event-item">
                        <div class="sidebar-calendar-event-dot {{ $event->maintenance_schedule_status === 'Overdue' ? 'event-dot-danger' : 'event-dot-active' }}"></div>
                        <div class="sidebar-calendar-event-info">
                            <span class="sidebar-calendar-event-name">{{ $event->maintenance_schedule_title }}</span>
                            <span class="sidebar-calendar-event-date">
                                {{ $event->maintenance_schedule_next_date ? \Carbon\Carbon::parse($event->maintenance_schedule_next_date)->format('M d, Y') : 'No date' }}
                                @if($event->maintenance_schedule_frequency)
                                <span class="sidebar-calendar-event-freq">· {{ $event->maintenance_schedule_frequency }}</span>
                                @endif
                            </span>
                        </div>
                        @if($event->maintenance_schedule_status === 'Overdue')
                        <span class="sidebar-calendar-event-badge badge-overdue">Overdue</span>
                        @endif
                    </div>
                    @empty
                    <div class="sidebar-calendar-empty">
                        <i data-lucide="calendar-x" class="h-4 w-4"></i>
                        <span>No upcoming events</span>
                    </div>
                    @endforelse
                </div>
            </div>


            {{-- 2. RIS Status Distribution --}}

            <div class="sidebar-chart-card">
                <div class="sidebar-chart-header">
                    <h3 class="sidebar-chart-title">RIS Status Overview</h3>
                </div>
                <div class="sidebar-chart-body">
                    <canvas id="risStatusChart" height="200"></canvas>
                </div>
            </div>


            {{-- 3. Activity List (Split into Pending + Completed with Toggle) --}}

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
                            <div class="sidebar-stat-dot sidebar-dot-blue"></div>
                            <span class="sidebar-stat-label">Total Users</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $totalUsers }}</span>
                    </div>
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-emerald"></div>
                            <span class="sidebar-stat-label">Direct Approved</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $directApprovedRis }}</span>
                    </div>
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-violet"></div>
                            <span class="sidebar-stat-label">For Co-signing</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $forCosigningCount }}</span>
                    </div>
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-teal"></div>
                            <span class="sidebar-stat-label">Co-signed</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $cosignedCount }}</span>
                    </div>
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-rose"></div>
                            <span class="sidebar-stat-label">Amend</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $amendRis }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>


{{-- ===================================================== --}}
{{-- DASHBOARD STYLES --}}
{{-- ===================================================== --}}
<style>

/* ======================================
   DASHBOARD LAYOUT
====================================== */

.admin-dashboard {
    padding: 12px 16px;
    max-width: 1440px;
    margin: 0 auto;
}

.dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.dashboard-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.3px;
}

.dashboard-subtitle {
    margin-top: 1px;
    font-size: 11px;
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
    gap: 4px;
    padding: 4px 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 500;
    color: #475569;
}


/* ======================================
   STAT CARDS GRID - Compact
====================================== */

.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-bottom: 6px;
}

.stat-grid-row-2 {
    margin-bottom: 8px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 10px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.stat-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 4px;
}

.stat-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon i,
.stat-icon svg {
    width: 12px;
    height: 12px;
}

.stat-icon-blue {
    background: #eff6ff;
    color: #2563eb;
}

.stat-icon-indigo {
    background: #eef2ff;
    color: #4f46e5;
}

.stat-icon-amber {
    background: #fffbeb;
    color: #d97706;
}

.stat-icon-emerald {
    background: #ecfdf5;
    color: #059669;
}

.stat-icon-sky {
    background: #f0f9ff;
    color: #0284c7;
}

.stat-icon-rose {
    background: #fff1f2;
    color: #e11d48;
}

.stat-icon-violet {
    background: #f5f3ff;
    color: #7c3aed;
}

.stat-icon-teal {
    background: #f0fdfa;
    color: #0d9488;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 2px;
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 9px;
    font-weight: 600;
}

.stat-change-up {
    background: #ecfdf5;
    color: #059669;
}

.stat-change-warn {
    background: #fffbeb;
    color: #d97706;
}

.stat-label {
    font-size: 9px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 1px;
}

.stat-value {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.3px;
    line-height: 1;
}

.stat-amount {
    margin-top: 2px;
    font-size: 9px;
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
    font-size: 9px;
    font-weight: 500;
    color: #64748b;
}

.stat-meta-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
}

.stat-dot-purple { background: #8b5cf6; }
.stat-dot-cyan { background: #06b6d4; }
.stat-dot-amber { background: #f59e0b; }
.stat-dot-emerald { background: #10b981; }
.stat-dot-rose { background: #f43f5e; }


/* ======================================
   MAIN GRID (HERO + SIDEBAR)
====================================== */

.dashboard-main-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 12px;
    align-items: start;
}


/* ======================================
   SIDEBAR GAP
====================================== */

.dashboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 10px;
}


/* ======================================
   CALENDAR OF EVENTS
====================================== */

.sidebar-calendar-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}

.sidebar-calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px 4px;
}

.sidebar-calendar-title {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 4px;
}

.sidebar-calendar-title-icon {
    width: 14px;
    height: 14px;
    color: #6366f1;
}

.sidebar-calendar-month {
    font-size: 9px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.sidebar-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    padding: 4px 8px 2px;
    gap: 2px;
}

.cal-weekday {
    font-size: 8px;
    font-weight: 700;
    text-align: center;
    color: #94a3b8;
    text-transform: uppercase;
    padding: 2px 0;
    letter-spacing: 0.3px;
}

.cal-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 500;
    color: #475569;
    border-radius: 4px;
    cursor: default;
    transition: all 0.15s ease;
    position: relative;
}

.cal-day:hover {
    background: #f1f5f9;
}

.cal-day.other-month {
    color: #cbd5e1;
}

.cal-day.today {
    background: #6366f1;
    color: #fff;
    font-weight: 700;
}

.cal-day.today:hover {
    background: #4f46e5;
}

.cal-day.has-event::after {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #f59e0b;
}

.cal-day.today.has-event::after {
    background: #fff;
}

/* Events list */

.sidebar-calendar-events {
    padding: 2px 12px 10px;
}

.sidebar-calendar-events-title {
    font-size: 9px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 4px;
    border-top: 1px solid #f1f5f9;
    padding-top: 8px;
}

.sidebar-calendar-event-item {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    padding: 4px 0;
    border-bottom: 1px solid #f8fafc;
}

.sidebar-calendar-event-item:last-child {
    border-bottom: none;
}

.sidebar-calendar-event-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 4px;
}

.event-dot-active {
    background: #10b981;
}

.event-dot-danger {
    background: #ef4444;
}

.sidebar-calendar-event-info {
    flex: 1;
    min-width: 0;
}

.sidebar-calendar-event-name {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-calendar-event-date {
    display: block;
    font-size: 9px;
    color: #94a3b8;
    margin-top: 0;
}

.sidebar-calendar-event-freq {
    color: #94a3b8;
}

.sidebar-calendar-event-badge {
    font-size: 8px;
    font-weight: 700;
    padding: 1px 4px;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}

.badge-overdue {
    background: #fef2f2;
    color: #dc2626;
}

.sidebar-calendar-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 10px 0;
    color: #94a3b8;
    font-size: 11px;
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
    color: #059669;
}

.act-icon-danger {
    background: #fef2f2;
    color: #dc2626;
}

.act-icon-pending {
    background: #fffbeb;
    color: #d97706;
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
    color: #d97706;
}


/* ======================================
   HERO ALERT CARDS
====================================== */

.hero-alert-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
    border-radius: 10px;
    margin-bottom: 8px;
}

.hero-alert-card-violet {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    border-color: #c4b5fd;
}

.hero-alert-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.hero-alert-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f59e0b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.hero-alert-icon-violet {
    background: #7c3aed;
}

.hero-alert-icon i,
.hero-alert-icon svg {
    width: 16px;
    height: 16px;
}

.hero-alert-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.hero-alert-desc {
    margin-top: 1px;
    font-size: 11px;
    color: #64748b;
}

.hero-alert-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #0f172a;
    color: white;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.hero-alert-btn:hover {
    background: #1e293b;
    transform: translateY(-1px);
}

.hero-alert-btn-violet {
    background: #7c3aed;
}

.hero-alert-btn-violet:hover {
    background: #6d28d9;
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
    background: #10b981;
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

.dashboard-chart-body {
    padding: 10px 14px;
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
    color: #2563eb;
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
    color: #d97706;
    border: 1px solid #fde68a;
}

.status-badge-slate {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
}

.status-badge-emerald {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}

.status-badge-rose {
    background: #fff1f2;
    color: #e11d48;
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


/* Sidebar Stats */

.sidebar-stats-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
}

.sidebar-stats-title {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
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

.sidebar-dot-blue { background: #3b82f6; }
.sidebar-dot-amber { background: #f59e0b; }
.sidebar-dot-emerald { background: #10b981; }
.sidebar-dot-violet { background: #8b5cf6; }
.sidebar-dot-teal { background: #14b8a6; }
.sidebar-dot-rose { background: #f43f5e; }

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
    border-radius: 10px;
    overflow: hidden;
}

.sidebar-activity-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-activity-title {
    font-size: 12px;
    font-weight: 700;
    color: #0f172a;
}

.sidebar-activity-link {
    font-size: 11px;
    font-weight: 600;
    color: #2563eb;
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
    // RIS MONTHLY TREND CHART (BAR)
    // =====================================================

    const trendCtx = document.getElementById('risTrendChart');

    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($risTrendLabels) !!},
                datasets: [{
                    label: 'RIS Submitted',
                    data: {!! json_encode($risTrendData) !!},
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    borderColor: 'rgba(37, 99, 235, 0.8)',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' RIS';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#94a3b8',
                            font: { size: 11 }
                        },
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false,
                        }
                    },
                    x: {
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 }
                        },
                        grid: {
                            display: false,
                        }
                    }
                }
            }
        });
    }


    // =====================================================
    // RIS STATUS DISTRIBUTION CHART (DOUGHNUT)
    // =====================================================

    const statusCtx = document.getElementById('risStatusChart');

    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($risStatusChart['labels']) !!},
                datasets: [{
                    data: {!! json_encode($risStatusChart['data']) !!},
                    backgroundColor: [
                        '#f59e0b',
                        '#3b82f6',
                        '#10b981',
                        '#f43f5e',
                        '#8b5cf6',
                    ],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 14,
                            usePointStyle: true,
                            pointStyleWidth: 8,
                            font: { size: 10, weight: '500' },
                            color: '#475569',
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.parsed;
                                const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }


    // =====================================================
    // MINI CALENDAR RENDER
    // =====================================================

    const calendarGrid = document.getElementById('miniCalendar');
    if (calendarGrid) {
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth();

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startOffset = firstDay.getDay(); // 0=Sun

        // Parse backend events into a Set of date strings
        const eventDates = new Set();
        try {
            const events = {!! json_encode($calendarEventsByDate) !!};
            if (events) {
                Object.keys(events).forEach(function(dateStr) {
                    eventDates.add(dateStr);
                });
            }
        } catch(e) {}

        // Previous month trailing days
        const prevLastDay = new Date(year, month, 0);
        for (let i = startOffset - 1; i >= 0; i--) {
            const day = prevLastDay.getDate() - i;
            const cell = document.createElement('div');
            cell.className = 'cal-day other-month';
            cell.textContent = day;
            calendarGrid.appendChild(cell);
        }

        // Current month days
        for (let d = 1; d <= lastDay.getDate(); d++) {
            const cell = document.createElement('div');
            cell.className = 'cal-day';

            const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            if (eventDates.has(dateStr)) {
                cell.classList.add('has-event');
            }

            if (d === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                cell.classList.add('today');
            }

            cell.textContent = d;
            calendarGrid.appendChild(cell);
        }

        // Next month leading days (fill remaining)
        const totalCells = startOffset + lastDay.getDate();
        const remaining = 7 - (totalCells % 7);
        if (remaining < 7) {
            for (let i = 1; i <= remaining; i++) {
                const cell = document.createElement('div');
                cell.className = 'cal-day other-month';
                cell.textContent = i;
                calendarGrid.appendChild(cell);
            }
        }
    }


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

});
</script>

@endsection

