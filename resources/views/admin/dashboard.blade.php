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


        {{-- PENDING RIS --}}

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


        {{-- RIGHT: ACTIVITY SIDEBAR --}}

        <div class="dashboard-sidebar">

            {{-- RIS Status Distribution --}}

            <div class="sidebar-chart-card">
                <div class="sidebar-chart-header">
                    <h3 class="sidebar-chart-title">RIS Status Overview</h3>
                </div>
                <div class="sidebar-chart-body">
                    <canvas id="risStatusChart" height="220"></canvas>
                </div>
            </div>


            {{-- Quick Stats Summary --}}

            <div class="sidebar-stats-card">
                <h3 class="sidebar-stats-title">Quick Summary</h3>
                <div class="sidebar-stats-list">
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-blue"></div>
                            <span class="sidebar-stat-label">Total Users</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $totalUsers }}</span>
                    </div>
                    <div class="sidebar-stat-item">
                        <div class="sidebar-stat-left">
                            <div class="sidebar-stat-dot sidebar-dot-amber"></div>
                            <span class="sidebar-stat-label">Pending RIS</span>
                        </div>
                        <span class="sidebar-stat-value">{{ $pendingRis }}</span>
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


            {{-- RECENT ACTIVITY FEED --}}

            <div class="sidebar-activity-card">
                <div class="sidebar-activity-header">
                    <h3 class="sidebar-activity-title">Recent Activity</h3>
                    <a href="{{ url('/admin/reports/approval-logs') }}" class="sidebar-activity-link">View all</a>
                </div>
                <div class="sidebar-activity-list">
                    @forelse($recentActivities as $activity)
                    <div class="sidebar-activity-item">
                        <div class="sidebar-activity-icon" style="background: {{ $activity->background }}; color: {{ $activity->color }};">
                            <i data-lucide="{{ $activity->icon }}" class="h-4 w-4"></i>
                        </div>
                        <div class="sidebar-activity-content">
                            <p class="sidebar-activity-title-text">{{ $activity->title }}</p>
                            <p class="sidebar-activity-desc">{{ $activity->description }}</p>
                            <p class="sidebar-activity-time">{{ $activity->created_at ? \Carbon\Carbon::parse($activity->created_at)->diffForHumans() : '' }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center py-8 text-gray-400">
                        <i data-lucide="activity" class="h-8 w-8 mb-2 text-gray-300"></i>
                        <span class="text-sm">No recent activity</span>
                    </div>
                    @endforelse
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
    padding: 28px 32px;
    max-width: 1440px;
    margin: 0 auto;
}

.dashboard-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 28px;
}

.dashboard-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.5px;
}

.dashboard-subtitle {
    margin-top: 4px;
    font-size: 14px;
    color: #64748b;
}

.dashboard-header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.dashboard-date-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
}


/* ======================================
   STAT CARDS GRID - ROW 1
====================================== */

.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.stat-grid-row-2 {
    margin-bottom: 28px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px 24px;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
}

.stat-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 14px;
}

.stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon i,
.stat-icon svg {
    width: 20px;
    height: 20px;
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
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
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
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -1px;
    line-height: 1;
}

.stat-amount {
    margin-top: 6px;
    font-size: 13px;
    color: #94a3b8;
    font-weight: 500;
}

.stat-meta {
    margin-top: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.stat-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
}

.stat-meta-dot {
    width: 7px;
    height: 7px;
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
    gap: 24px;
    align-items: start;
}


/* ======================================
   HERO ALERT CARDS
====================================== */

.hero-alert-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 24px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fde68a;
    border-radius: 16px;
    margin-bottom: 20px;
}

.hero-alert-card-violet {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    border-color: #c4b5fd;
}

.hero-alert-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.hero-alert-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
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
    width: 22px;
    height: 22px;
}

.hero-alert-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.hero-alert-desc {
    margin-top: 2px;
    font-size: 13px;
    color: #64748b;
}

.hero-alert-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #0f172a;
    color: white;
    border-radius: 10px;
    font-size: 13px;
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
    padding: 20px 24px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    margin-bottom: 20px;
}

.hero-empty-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.hero-empty-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #10b981;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.hero-empty-icon i,
.hero-empty-icon svg {
    width: 22px;
    height: 22px;
}

.hero-empty-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.hero-empty-desc {
    margin-top: 2px;
    font-size: 13px;
    color: #64748b;
}


/* ======================================
   CHART CARD
====================================== */

.dashboard-chart-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 20px;
}

.dashboard-chart-header {
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-chart-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.dashboard-chart-subtitle {
    font-size: 13px;
    color: #64748b;
    margin-top: 2px;
}

.dashboard-chart-body {
    padding: 16px 22px;
}


/* ======================================
   TABLE CARD
====================================== */

.dashboard-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.dashboard-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
}

.dashboard-table-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.dashboard-table-subtitle {
    font-size: 13px;
    color: #64748b;
    margin-top: 2px;
}

.dashboard-table-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
    transition: all 0.2s ease;
}

.dashboard-table-link:hover {
    color: #1d4ed8;
    gap: 10px;
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
    padding: 12px 22px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
}

.dashboard-table td {
    padding: 14px 22px;
    font-size: 13px;
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

.dashboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}


/* Sidebar Chart */

.sidebar-chart-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.sidebar-chart-header {
    padding: 18px 20px 0;
}

.sidebar-chart-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.sidebar-chart-body {
    padding: 10px 16px 16px;
}


/* Sidebar Stats */

.sidebar-stats-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
}

.sidebar-stats-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 14px;
}

.sidebar-stats-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.sidebar-stat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f8fafc;
}

.sidebar-stat-item:last-child {
    border-bottom: none;
}

.sidebar-stat-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.sidebar-dot-blue { background: #3b82f6; }
.sidebar-dot-amber { background: #f59e0b; }
.sidebar-dot-emerald { background: #10b981; }
.sidebar-dot-violet { background: #8b5cf6; }
.sidebar-dot-teal { background: #14b8a6; }
.sidebar-dot-rose { background: #f43f5e; }

.sidebar-stat-label {
    font-size: 13px;
    color: #475569;
}

.sidebar-stat-value {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}


/* Sidebar Activity Feed */

.sidebar-activity-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.sidebar-activity-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-activity-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}

.sidebar-activity-link {
    font-size: 13px;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
}

.sidebar-activity-link:hover {
    text-decoration: underline;
}

.sidebar-activity-list {
    padding: 8px 0;
}

.sidebar-activity-item {
    display: flex;
    gap: 12px;
    padding: 12px 20px;
    transition: background 0.2s ease;
}

.sidebar-activity-item:hover {
    background: #f8fafc;
}

.sidebar-activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
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
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-activity-desc {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.sidebar-activity-time {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
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
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { size: 11, weight: '500' },
                            color: '#475569',
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        padding: 12,
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

});
</script>

@endsection

