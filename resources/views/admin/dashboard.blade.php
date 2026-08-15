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

                <div class="stat-card" title="RIS waiting for Admin co-signature">
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
                    <p class="stat-label">For Co-signing</p>
                    <p class="stat-value">{{ $forCosigningCount }}</p>
                    <p class="stat-amount">President-approved, awaiting your signature</p>
                </div>

                <div class="stat-card stat-card-success" title="RIS forms approved by Admin">
                    <div class="stat-card-top">
                        <div class="stat-icon stat-icon-emerald">
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
                        <p class="hero-alert-desc">President-returned RIS waiting to be sent back to Purchaser, or rejected forms that need revision remarks.</p>
                    </div>
                </div>
                <a href="{{ route('admin.digital-signatures.sign-ris', ['filter' => 'for_cosign']) }}" class="hero-alert-btn hero-alert-btn-violet">
                    Review Now
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            @endif


{{-- Quick Access Dashboards --}}

            <div class="dashboard-quick-access">
                <div class="dashboard-quick-header">
                    <div>
                        <h3 class="dashboard-quick-title">Quick Access</h3>
                        <p class="dashboard-quick-subtitle">Click to open section overview in a modal</p>
                    </div>
                </div>
                <div class="dashboard-quick-grid">
                    <button type="button" onclick="openQuickAccessModal('procurement')" class="quick-access-card" style="cursor:pointer;border:none;width:100%;font-family:inherit;">
                        <div class="quick-access-icon quick-access-icon-blue">
                            <i data-lucide="clipboard-check"></i>
                        </div>
                        <span class="quick-access-label">Procurement Review</span>
                        <span class="quick-access-desc">Review and approve RIS submissions</span>
                    </button>
                    <button type="button" onclick="openQuickAccessModal('signris')" class="quick-access-card" style="cursor:pointer;border:none;width:100%;font-family:inherit;">
                        <div class="quick-access-icon quick-access-icon-violet">
                            <i data-lucide="pen-tool"></i>
                        </div>
                        <span class="quick-access-label">Sign RIS</span>
                        <span class="quick-access-desc">Co-sign President-approved RIS</span>
                    </button>
                    <button type="button" onclick="openQuickAccessModal('history')" class="quick-access-card" style="cursor:pointer;border:none;width:100%;font-family:inherit;">
                        <div class="quick-access-icon quick-access-icon-indigo">
                            <i data-lucide="history"></i>
                        </div>
                        <span class="quick-access-label">Signature History</span>
                        <span class="quick-access-desc">View completed signature records</span>
                    </button>
                    <button type="button" onclick="openQuickAccessModal('users')" class="quick-access-card" style="cursor:pointer;border:none;width:100%;font-family:inherit;">
                        <div class="quick-access-icon quick-access-icon-emerald">
                            <i data-lucide="users"></i>
                        </div>
                        <span class="quick-access-label">User Management</span>
                        <span class="quick-access-desc">Manage system users and roles</span>
                    </button>
                    <button type="button" onclick="openQuickAccessModal('reports')" class="quick-access-card" style="cursor:pointer;border:none;width:100%;font-family:inherit;">
                        <div class="quick-access-icon quick-access-icon-amber">
                            <i data-lucide="file-text"></i>
                        </div>
                        <span class="quick-access-label">System Reports</span>
                        <span class="quick-access-desc">Read-only maintenance, receiving, approvals, and access</span>
                    </button>
                    <button type="button" onclick="openQuickAccessModal('settings')" class="quick-access-card" style="cursor:pointer;border:none;width:100%;font-family:inherit;">
                        <div class="quick-access-icon quick-access-icon-rose">
                            <i data-lucide="settings"></i>
                        </div>
                        <span class="quick-access-label">System Settings</span>
                        <span class="quick-access-desc">Campus setup PIN and admin controls</span>
                    </button>
                </div>
            </div>


            {{-- RIS Monthly Trend Chart --}}

            <div class="dashboard-chart-card">
                <div class="dashboard-chart-header">
                    <div>
                        <h3 class="dashboard-chart-title">Monthly Trend</h3>
                        <p class="dashboard-chart-subtitle">Admin Approved, Forwarded, and Amend over the last 6 months</p>
                    </div>
                    <div class="dashboard-chart-legend">
                        <div class="dashboard-chart-legend-item">
                            <span class="dashboard-chart-legend-dot" style="background:#059669;"></span>
                            <span>Admin Approved</span>
                        </div>
                        <div class="dashboard-chart-legend-item">
                            <span class="dashboard-chart-legend-dot" style="background:#3b82f6;"></span>
                            <span>Forwarded to President</span>
                        </div>
                        <div class="dashboard-chart-legend-item">
                            <span class="dashboard-chart-legend-dot" style="background:#fb7185;"></span>
                            <span>Amend</span>
                        </div>
                    </div>
                </div>
                <div class="dashboard-chart-body dashboard-chart-body-trend">
                    <canvas id="risTrendChart"></canvas>
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
                                    <span class="table-equip">{{ $ris->ris_item_names ?: ($ris->ris_manual_title ?: ($ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? ($ris->ris_request_type === 'manual' ? 'Manual Procurement' : 'N/A'))) }}</span>
                                </td>
                                <td>
                                    @if(in_array($ris->ris_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true))
                                        <span class="status-badge status-badge-amber">Pending</span>
                                    @elseif($ris->ris_status === 'Directly Approved')
                                        <span class="status-badge status-badge-slate">Admin Approved</span>
                                    @elseif($ris->ris_status === 'Approved')
                                        <span class="status-badge status-badge-emerald">Forwarded to President</span>
                                    @elseif(in_array($ris->ris_status, ['Minor Revision', 'Rejected'], true))
                                        <span class="status-badge status-badge-rose">Amend</span>
                                    @else
                                        <span class="status-badge status-badge-gray">{{ $ris->ris_status }}</span>
                                    @endif
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

{{-- 1. RIS Status Distribution --}}

            <div class="sidebar-chart-card">
                <div class="sidebar-chart-header">
                    <h3 class="sidebar-chart-title">RIS Status Overview</h3>
                </div>
                <div class="sidebar-chart-body">
                    <canvas id="risStatusChart" height="200"></canvas>
                </div>
            </div>


            {{-- 2. Calendar of Events --}}

            <div class="sidebar-calendar-card">
                <div class="sidebar-calendar-header">
                    <h3 class="sidebar-calendar-title">
                        <i data-lucide="calendar" class="h-4 w-4" style="margin-right: 6px;"></i>
                        Calendar of Events
                    </h3>
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
                    <div class="calendar-grid">
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
                    <div class="cal-upcoming">
                        <h4 class="cal-upcoming-title">Upcoming Events</h4>
                        @forelse($calendarEvents->take(3) as $event)
                            <div class="cal-upcoming-item">
                                <div class="cal-upcoming-dot"></div>
                                <div class="cal-upcoming-content">
                                    <span class="cal-upcoming-name">{{ $event->equipment_name ?? 'Equipment' }}</span>
                                    <span class="cal-upcoming-date">
                                        {{ $event->maintenance_schedule_next_date ? \Carbon\Carbon::parse($event->maintenance_schedule_next_date)->format('M d, Y') : 'No date set' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="cal-upcoming-empty">No upcoming maintenance events</div>
                        @endforelse
                    </div>
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
                            <div class="sidebar-stat-dot sidebar-dot-emerald"></div>
                            <span class="sidebar-stat-label">Admin Approved</span>
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
                </div>
            </div>

        </div>

    </div>

</div>


{{-- ===================================================== --}}
{{-- RIS PREVIEW MODAL --}}
{{-- ===================================================== --}}

<div id="quickAccessModal" class="ris-preview-modal-overlay qa-modal-overlay" style="display: none;">
    <div class="ris-preview-modal-container" style="max-width: 95vw; width: 1400px;">
        <div class="ris-preview-modal-header">
            <h3 class="ris-preview-modal-title" id="qaModalTitle">Quick Access</h3>
            <button type="button" onclick="closeQuickAccessModal()" class="ris-preview-modal-close" title="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="ris-preview-modal-body" id="qaModalBody" style="background: #ffffff; min-height: 300px; max-height: calc(90vh - 110px); overflow: auto;">
            <div class="ris-preview-loading">
                <div class="ris-preview-spinner"></div>
                <span>Loading...</span>
            </div>
        </div>
        <div class="ris-preview-modal-footer">
            <button type="button" onclick="closeQuickAccessModal()" class="ris-preview-modal-btn-close">Close</button>
        </div>
    </div>
</div>


{{-- RIS Preview must come AFTER Quick Access in the DOM --}}
@include('admin.partials.ris-preview-modal', ['zIndex' => '11000'])

@include('admin.procurement-review._direct-approve-modal')


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
    color: #0037c7;
}

.stat-icon-indigo {
    background: #eff6ff;
    color: #0037c7;
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
    background: #eff6ff;
    color: #0037c7;
}

.stat-icon-teal {
    background: #f0fdfa;
    color: #0d9488;
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
    background: #ecfdf5;
    color: #059669;
}

.stat-change-warn {
    background: #fffbeb;
    color: #d97706;
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

.stat-dot-purple { background: #0037c7; }
.stat-dot-cyan { background: #06b6d4; }
.stat-dot-amber { background: #f59e0b; }
.stat-dot-emerald { background: #10b981; }
.stat-dot-rose { background: #f43f5e; }


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


/* ======================================
   QUICK ACCESS DASHBOARDS
====================================== */

.dashboard-quick-access {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 16px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.dashboard-quick-header {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-quick-title {
    font-family: "Outfit", sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.dashboard-quick-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.dashboard-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 14px 16px;
}

.quick-access-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 4px;
    padding: 12px 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.quick-access-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
    background: #ffffff;
}

.quick-access-card-disabled,
.quick-access-card-disabled:hover {
    opacity: 0.55;
    cursor: not-allowed;
    pointer-events: none;
    transform: none;
    box-shadow: none;
    background: #f8fafc;
    border-color: #e2e8f0;
}

.quick-access-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-bottom: 4px;
}

.quick-access-icon i,
.quick-access-icon svg {
    width: 16px;
    height: 16px;
}

.quick-access-icon-blue {
    background: #eff6ff;
    color: #2563eb;
}

.quick-access-icon-violet {
    background: #eff6ff;
    color: #0037c7;
}

.quick-access-icon-indigo {
    background: #eff6ff;
    color: #0037c7;
}

.quick-access-icon-emerald {
    background: #ecfdf5;
    color: #059669;
}

.quick-access-icon-amber {
    background: #fffbeb;
    color: #d97706;
}

.quick-access-icon-rose {
    background: #fff1f2;
    color: #e11d48;
}

.quick-access-label {
    font-size: 11px;
    font-weight: 600;
    color: #0f172a;
}

.quick-access-desc {
    font-size: 9px;
    color: #94a3b8;
    line-height: 1.3;
}


/* ======================================
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
    color: #e11d48;
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
    border-top-color: #0037c7;
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
    padding: 16px 18px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
    border-radius: 18px;
    margin-bottom: 16px;
}

.hero-alert-card-violet {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-color: #93c5fd;
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
    background: #f59e0b;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.hero-alert-icon-violet {
    background: #0037c7;
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
    color: #0037c7;
    font-weight: 700;
}

.cal-day-today:hover {
    background: #e0e7ff;
}

.cal-day-has-event {
    color: #0f172a;
    font-weight: 600;
}

.cal-day-num {
    line-height: 1;
}

.cal-day-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #f59e0b;
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
    background: #f59e0b;
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

.sidebar-dot-blue { background: #3b82f6; }
.sidebar-dot-amber { background: #f59e0b; }
.sidebar-dot-emerald { background: #10b981; }
.sidebar-dot-violet { background: #0037c7; }
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
    color: #0037c7;
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
    // RIS MONTHLY TREND CHART (LINE — Approved vs Amend)
    // =====================================================

    const trendCtx = document.getElementById('risTrendChart');
    const trendLabels = {!! json_encode($risTrendLabels ?? []) !!};
    const trendApproved = {!! json_encode($risTrendApproved ?? []) !!};
    const trendForwarded = {!! json_encode($risTrendForwarded ?? []) !!};
    const trendAmend = {!! json_encode($risTrendAmend ?? []) !!};

    if (trendCtx && trendLabels.length > 0) {
        const approvedGradient = trendCtx.getContext('2d').createLinearGradient(0, 0, 0, 240);
        approvedGradient.addColorStop(0, 'rgba(5, 150, 105, 0.28)');
        approvedGradient.addColorStop(0.4, 'rgba(5, 150, 105, 0.08)');
        approvedGradient.addColorStop(1, 'rgba(5, 150, 105, 0.01)');

        const forwardedGradient = trendCtx.getContext('2d').createLinearGradient(0, 0, 0, 240);
        forwardedGradient.addColorStop(0, 'rgba(59, 130, 246, 0.26)');
        forwardedGradient.addColorStop(0.4, 'rgba(59, 130, 246, 0.08)');
        forwardedGradient.addColorStop(1, 'rgba(59, 130, 246, 0.01)');

        const amendGradient = trendCtx.getContext('2d').createLinearGradient(0, 0, 0, 240);
        amendGradient.addColorStop(0, 'rgba(225, 29, 72, 0.18)');
        amendGradient.addColorStop(0.4, 'rgba(225, 29, 72, 0.06)');
        amendGradient.addColorStop(1, 'rgba(225, 29, 72, 0.01)');

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'Admin Approved',
                        data: trendApproved,
                        borderColor: '#059669',
                        backgroundColor: approvedGradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#059669',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2.5,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#059669',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                        borderWidth: 2.5,
                    },
                    {
                        label: 'Forwarded to President',
                        data: trendForwarded,
                        borderColor: '#3b82f6',
                        backgroundColor: forwardedGradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2.5,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#3b82f6',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                        borderWidth: 2.5,
                    },
                    {
                        label: 'Amend',
                        data: trendAmend,
                        borderColor: '#fb7185',
                        backgroundColor: amendGradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#fb7185',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2.5,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#fb7185',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                        borderWidth: 2.5,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeInOutQuart',
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#f3f4f6',
                        titleFont: { size: 12, weight: '600' },
                        bodyColor: '#e5e7eb',
                        bodyFont: { size: 11 },
                        padding: 12,
                        cornerRadius: 8,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 11 },
                            color: '#9ca3af',
                            padding: 8,
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.04)',
                            drawBorder: false,
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            font: { size: 11, weight: '500' },
                            color: '#6b7280',
                            maxRotation: 0,
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                hover: {
                    mode: 'index',
                    intersect: false,
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
                        '#0037c7',
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
    // MINI CALENDAR RENDER - REMOVED
    // =====================================================


    // =====================================================
    // CALENDAR MONTH NAVIGATION
    // =====================================================

    (function() {
        var prevBtn = document.getElementById('calPrevBtn');
        var nextBtn = document.getElementById('calNextBtn');
        var monthLabel = document.getElementById('calMonthLabel');

        if (prevBtn && nextBtn && monthLabel) {
            // Disable navigation buttons for static display
            prevBtn.style.opacity = '0.4';
            prevBtn.style.cursor = 'not-allowed';
            nextBtn.style.opacity = '0.4';
            nextBtn.style.cursor = 'not-allowed';
            prevBtn.title = 'Navigation disabled (static calendar)';
            nextBtn.title = 'Navigation disabled (static calendar)';
        }
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
        modal.classList.remove('hidden');
        modal.style.display = 'block';
        modal.style.zIndex = '11000';
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
    };

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        const risModal = document.getElementById('risPreviewModal');
        if (risModal && !risModal.classList.contains('hidden')) {
            closeRisPreviewModal();
            return;
        }
        if (typeof qaModalOpen !== 'undefined' && qaModalOpen) {
            closeQuickAccessModal();
        }
    });


    // =====================================================
    // QUICK ACCESS MODAL
    // =====================================================

    window.switchQaReportTab = function(tab) {
        if (!tab) return;
        var root = tab.closest('.space-y-4') || document.getElementById('qaModalBody');
        if (!root) return;
        root.querySelectorAll('.qa-report-tab').forEach(function(t) {
            t.classList.remove('border-[#0037c7]', 'bg-[#0037c7]', 'text-white');
            t.classList.add('border-gray-200', 'bg-white', 'text-gray-700');
        });
        tab.classList.add('border-[#0037c7]', 'bg-[#0037c7]', 'text-white');
        tab.classList.remove('border-gray-200', 'bg-white', 'text-gray-700');
        root.querySelectorAll('.qa-report-pane').forEach(function(p) { p.classList.add('hidden'); });
        var pane = document.getElementById(tab.getAttribute('data-pane'));
        if (pane) pane.classList.remove('hidden');
    };

    var qaModalOpen = false;

    window.openQuickAccessModal = function(section) {
        var modal = document.getElementById('quickAccessModal');
        var body = document.getElementById('qaModalBody');
        var title = document.getElementById('qaModalTitle');
        if (!modal || !body || !title) return;

        var titles = {
            'procurement': 'Procurement Review — All RIS Records',
            'signris': 'Sign RIS — President-Approved Records',
            'history': 'Signature History — Completed Records',
            'users': 'User Management',
            'reports': 'System Reports',
            'settings': 'System Settings'
        };
        title.textContent = titles[section] || 'Quick Access';

        modal.style.display = 'flex';
        body.innerHTML = '<div class="ris-preview-loading"><div class="ris-preview-spinner"></div><span>Loading...</span></div>';
        qaModalOpen = true;

        var ajaxUrls = {
            'procurement': '/admin/quick-access/procurement-content',
            'signris': '/admin/quick-access/signris-content',
            'history': '/admin/quick-access/history-content',
            'users': '/admin/quick-access/users-content',
            'reports': '/admin/quick-access/reports-content',
            'settings': '/admin/quick-access/settings-content'
        };

        if (!ajaxUrls[section]) {
            body.innerHTML = '<div class="ris-preview-loading" style="color:#64748b;"><span>This section is temporarily unavailable.</span></div>';
            return;
        }

        fetch(ajaxUrls[section], {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Failed to load');
            return response.text();
        })
        .then(function(html) {
            body.innerHTML = html;
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        })
        .catch(function(error) {
            console.error('Quick Access fetch error:', error);
            body.innerHTML = '<div class="ris-preview-loading" style="color:#e11d48;"><span>Failed to load. <a href="' + (ajaxUrls[section] || '#') + '" style="color:#0037c7;text-decoration:underline;">Open in new tab instead</a></span></div>';
        });
    };

    window.closeQuickAccessModal = function() {
        var modal = document.getElementById('quickAccessModal');
        var body = document.getElementById('qaModalBody');
        if (modal) {
            modal.style.display = 'none';
        }
        if (body) {
            body.innerHTML = '<div class="ris-preview-loading"><div class="ris-preview-spinner"></div><span>Loading...</span></div>';
        }
        qaModalOpen = false;
    };

    // Close Quick Access modal on overlay click
    document.addEventListener('click', function(e) {
        var modal = document.getElementById('quickAccessModal');
        if (modal && e.target === modal) {
            closeQuickAccessModal();
        }
    });

});
</script>

@endsection

